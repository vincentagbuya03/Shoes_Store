<?php
// orders_track.php
// Returns JSON with tracking/location info for an order if available
require_once 'db_connection.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}
$customer_id = (int)$_SESSION['customer_id'];

// Debug mode: append diagnostic info when ?debug=1 is present (developer aid)
$debugMode = isset($_GET['debug']) && ($_GET['debug'] === '1' || $_GET['debug'] === 'true');
$debugAttempts = [];

$order_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
} else {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
}
if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Missing order_id']);
    exit();
}

$cols = [];
$colsRes = $conn->query("SHOW COLUMNS FROM `orders`");
if ($colsRes) {
    while ($c = $colsRes->fetch_assoc()) { $cols[] = $c['Field']; }
}

// Helper to find first existing candidate column
$find = function(array $candidates) use ($cols) {
    foreach ($candidates as $c) {
        if (in_array($c, $cols, true)) return $c;
    }
    return null;
};

// common latitude/longitude column name candidates
$latCols = ['lat','latitude','current_lat','tracking_lat','last_lat','delivery_lat','delivery_latitude','shipping_lat','delivery_lat_deg'];
$lngCols = ['lng','longitude','current_lng','tracking_lng','last_lng','delivery_lng','delivery_longitude','shipping_lng','delivery_lng_deg'];

// tracking fields fallback
$trackingNumberCols = ['tracking_number','tracking_no','tracking_id','tracking'];
$trackingUrlCols = ['tracking_url','tracking_link','tracking_uri','tracking_web'];

// find actual column names
$latCol = $find($latCols);
$lngCol = $find($lngCols);
$trackingNumberCol = $find($trackingNumberCols);
$trackingUrlCol = $find($trackingUrlCols);
$providerCol = $find(['carrier','provider','shipping_provider','courier']);

// detect order-level rider column early so we can SELECT it (e.g. `rider_id`, `assigned_rider`)
$riderColCandidates = ['rider_id','assigned_rider','rider','delivery_rider_id'];
$riderCol = null;
foreach ($riderColCandidates as $c) { if (in_array($c, $cols, true)) { $riderCol = $c; break; } }

// Prepare SELECT that includes relevant found columns and validates ownership
$selectParts = ['order_id'];
if ($latCol && $lngCol) {
    $selectParts[] = "`{$latCol}` AS lat";
    $selectParts[] = "`{$lngCol}` AS lng";
}
// include assigned rider column (if any) so later logic can read it from the fetched row
if (!empty($riderCol)) {
    $selectParts[] = "`{$riderCol}`";
}
if ($trackingNumberCol) $selectParts[] = "`{$trackingNumberCol}` AS tracking_number";
if ($trackingUrlCol) $selectParts[] = "`{$trackingUrlCol}` AS tracking_url";
if ($providerCol) $selectParts[] = "`{$providerCol}` AS provider";

$selectSQL = implode(', ', $selectParts) . ' FROM `orders` WHERE order_id = ? AND customer_id = ? LIMIT 1';
$sql = 'SELECT ' . $selectSQL;

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
$stmt->bind_param('ii', $order_id, $customer_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Order not found or access denied']);
    exit();
}

// If we have latitude & longitude return them
// If the order row has coordinates, prepare customer coords response. If an assigned rider also exists try to fetch rider coords so the client can draw a line between them.
$orderHasCoords = isset($row['lat']) && isset($row['lng']) && $row['lat'] !== null && $row['lng'] !== null && $row['lat'] !== '' && $row['lng'] !== '';
// If the order has no coords, attempt to read possible customer coordinates from the `customer` table (some setups store delivery coords on the customer record)
if (!$orderHasCoords) {
    $custCols = [];
    $ccRes = $conn->query("SHOW COLUMNS FROM customer");
    if ($ccRes) { while ($c = $ccRes->fetch_assoc()) { $custCols[] = $c['Field']; } }
    if ($debugMode) $debugAttempts[] = ['customer_table_cols' => $custCols];
    $findCustCol = function(array $candidates) use ($custCols) {
        foreach ($candidates as $x) { if (in_array($x, $custCols, true)) return $x; }
        return null;
    };
    $custLatCol = $findCustCol(['lat','latitude','current_lat','delivery_lat','shipping_lat']);
    $custLngCol = $findCustCol(['lng','longitude','current_lng','delivery_lng','shipping_lng']);
    if ($custLatCol && $custLngCol) {
        if ($stmtC = $conn->prepare("SELECT `".$custLatCol."` AS lat, `".$custLngCol."` AS lng FROM customer WHERE customer_id = ? LIMIT 1")) {
            $stmtC->bind_param('i', $customer_id);
            $stmtC->execute();
            $cres = $stmtC->get_result();
            if ($crow = $cres->fetch_assoc()) {
                if ($crow['lat'] !== null && $crow['lng'] !== null && $crow['lat'] !== '' && $crow['lng'] !== '') {
                    $orderHasCoords = true;
                    $row['lat'] = $crow['lat'];
                    $row['lng'] = $crow['lng'];
                    if ($debugMode) $debugAttempts[] = ['found_customer_table_coords' => [$custLatCol, $custLngCol]];
                }
            }
            $stmtC->close();
        }
    }
}
if ($orderHasCoords) {
    $custLat = (float)$row['lat'];
    $custLng = (float)$row['lng'];
    $response = [
        'success' => true,
        'order_id' => (int)$row['order_id'],
        'customer_lat' => $custLat,
        'customer_lng' => $custLng,
        'label' => 'Customer location'
    ];
    if (isset($row['provider'])) $response['provider'] = $row['provider'];

    // If we also have an assigned rider on the order, attempt to fetch the rider's last-known location so the client can render a route/line
    if (!empty($riderCol) && !empty($row[$riderCol])) {
        $assignedRiderId = (int)$row[$riderCol];
        if ($debugMode) $debugAttempts[] = ['order_has_assigned_rider' => $assignedRiderId];
        if ($assignedRiderId > 0) {
            // attempt rider_location_history first
            $tblRes = $conn->query("SHOW TABLES LIKE 'rider_location_history'");
            if ($tblRes && $tblRes->num_rows > 0) {
                $rlhCols = [];
                $cRes = $conn->query("SHOW COLUMNS FROM rider_location_history");
                if ($cRes) { while ($c = $cRes->fetch_assoc()) { $rlhCols[] = $c['Field']; } }
                $findRlh = function(array $candidates) use ($rlhCols) {
                    foreach ($candidates as $x) { if (in_array($x, $rlhCols, true)) return $x; }
                    return null;
                };
                $latCandidates = ['lat','latitude','location_lat','lat_deg','coord_lat','lat_value'];
                $lngCandidates = ['lng','longitude','location_lng','lng_deg','coord_lng','lon_value'];
                $timeCandidates = ['recorded_at','created_at','timestamp','ts','created','time','updated_at'];
                $latColRlh = $findRlh($latCandidates);
                $lngColRlh = $findRlh($lngCandidates);
                $timeColRlh = $findRlh($timeCandidates) ?: 'id';
                if ($latColRlh && $lngColRlh) {
                    $orderBy = in_array($timeColRlh, $rlhCols, true) ? $timeColRlh : 'id';
                    $sqlR = "SELECT `".$latColRlh."` AS lat, `".$lngColRlh."` AS lng FROM rider_location_history WHERE rider_id = ? ORDER BY `".$orderBy."` DESC LIMIT 1";
                    if ($s = $conn->prepare($sqlR)) {
                        $s->bind_param('i', $assignedRiderId);
                        $s->execute();
                        $rres = $s->get_result();
                        if ($rrow = $rres->fetch_assoc()) {
                            if ($rrow['lat'] !== null && $rrow['lng'] !== null && $rrow['lat'] !== '' && $rrow['lng'] !== '') {
                                $response['rider_lat'] = (float)$rrow['lat'];
                                $response['rider_lng'] = (float)$rrow['lng'];
                                $response['label'] = 'Customer and rider locations';
                                if ($debugMode) $response['_debug'] = $debugAttempts;
                                echo json_encode($response);
                                $s->close();
                                exit();
                            }
                        }
                        $s->close();
                    }
                }
            }
            // fallback: check rider table
            $rCols = [];
            $rcRes = $conn->query("SHOW COLUMNS FROM rider");
            if ($rcRes) { while ($c = $rcRes->fetch_assoc()) { $rCols[] = $c['Field']; } }
            $findRiderCol = function(array $candidates) use ($rCols) {
                foreach ($candidates as $x) { if (in_array($x, $rCols, true)) return $x; }
                return null;
            };
            $latRider = $findRiderCol(['current_lat','lat','latitude']);
            $lngRider = $findRiderCol(['current_lng','lng','longitude']);
            if ($latRider && $lngRider) {
                $sqlR = "SELECT `".$latRider."` AS lat, `".$lngRider."` AS lng FROM rider WHERE rider_id = ? LIMIT 1";
                if ($s2 = $conn->prepare($sqlR)) {
                    $s2->bind_param('i', $assignedRiderId);
                    $s2->execute();
                    $rres2 = $s2->get_result();
                    if ($r2 = $rres2->fetch_assoc()) {
                        if ($r2['lat'] !== null && $r2['lng'] !== null && $r2['lat'] !== '' && $r2['lng'] !== '') {
                            $response['rider_lat'] = (float)$r2['lat'];
                            $response['rider_lng'] = (float)$r2['lng'];
                            $response['label'] = 'Customer and rider locations';
                            if ($debugMode) $response['_debug'] = $debugAttempts;
                            echo json_encode($response);
                            $s2->close();
                            exit();
                        }
                    }
                    $s2->close();
                }
            }
        }
    }

    if ($debugMode) $response['_debug'] = $debugAttempts;
    echo json_encode($response);
    exit();
}

// If the order itself doesn't have lat/lng, try to resolve the assigned rider's last-known location.
// Many setups store the active rider on the `orders` row as `rider_id` or similar.
$riderColCandidates = ['rider_id','assigned_rider','rider','delivery_rider_id'];
$riderCol = null;
foreach ($riderColCandidates as $c) { if (in_array($c, $cols, true)) { $riderCol = $c; break; } }
if ($debugMode) $debugAttempts[] = ['order_columns_checked' => $riderColCandidates, 'order_found_rider_column' => $riderCol];
if ($riderCol && !empty($row[$riderCol])) {
    $assignedRiderId = (int)$row[$riderCol];
    if ($debugMode) $debugAttempts[] = ['assigned_rider_id' => $assignedRiderId];
    if ($assignedRiderId > 0) {
        // Prefer rider_location_history when present
        $tblRes = $conn->query("SHOW TABLES LIKE 'rider_location_history'");
        if ($tblRes && $tblRes->num_rows > 0) {
            if ($debugMode) $debugAttempts[] = ['rider_location_history_table' => true];
            $rlhCols = [];
            $cRes = $conn->query("SHOW COLUMNS FROM rider_location_history");
            if ($cRes) { while ($c = $cRes->fetch_assoc()) { $rlhCols[] = $c['Field']; } }
            if ($debugMode) $debugAttempts[] = ['rider_location_history_cols' => $rlhCols];

            $findRlh = function(array $candidates) use ($rlhCols) {
                foreach ($candidates as $x) { if (in_array($x, $rlhCols, true)) return $x; }
                return null;
            };

            $latCandidates = ['lat','latitude','location_lat','lat_deg','coord_lat','lat_value'];
            $lngCandidates = ['lng','longitude','location_lng','lng_deg','coord_lng','lon_value'];
            $timeCandidates = ['recorded_at','created_at','timestamp','ts','created','time','updated_at'];

            $latColRlh = $findRlh($latCandidates);
            $lngColRlh = $findRlh($lngCandidates);
            $timeColRlh = $findRlh($timeCandidates) ?: 'id';

            if ($latColRlh && $lngColRlh) {
                if ($debugMode) $debugAttempts[] = ['rlh_lat' => $latColRlh, 'rlh_lng' => $lngColRlh, 'rlh_time' => $timeColRlh];
                $orderBy = in_array($timeColRlh, $rlhCols, true) ? $timeColRlh : 'id';
                $sqlR = "SELECT `".$latColRlh."` AS lat, `".$lngColRlh."` AS lng FROM rider_location_history WHERE rider_id = ? ORDER BY `".$orderBy."` DESC LIMIT 1";
                if ($s = $conn->prepare($sqlR)) {
                    $s->bind_param('i', $assignedRiderId);
                    $s->execute();
                    $rres = $s->get_result();
                    if ($rrow = $rres->fetch_assoc()) {
                        if ($rrow['lat'] !== null && $rrow['lng'] !== null && $rrow['lat'] !== '' && $rrow['lng'] !== '') {
                            $out = ['success' => true, 'order_id' => (int)$row['order_id'], 'lat' => (float)$rrow['lat'], 'lng' => (float)$rrow['lng'], 'provider' => $row['provider'] ?? null, 'label' => 'Rider last-known location'];
                            if ($debugMode) $out['_debug'] = $debugAttempts;
                            echo json_encode($out);
                            $s->close();
                            exit();
                        }
                    }
                    $s->close();
                }
            }
        }
        else { if ($debugMode) $debugAttempts[] = ['rider_location_history_table' => false]; }

        // Fallback: check rider table for current_lat/current_lng-like columns
        $rCols = [];
        $rcRes = $conn->query("SHOW COLUMNS FROM rider");
        if ($rcRes) { while ($c = $rcRes->fetch_assoc()) { $rCols[] = $c['Field']; } }
        if ($debugMode) $debugAttempts[] = ['rider_table_columns' => $rCols];
        $findRiderCol = function(array $candidates) use ($rCols) {
            foreach ($candidates as $x) { if (in_array($x, $rCols, true)) return $x; }
            return null;
        };
        $latRider = $findRiderCol(['current_lat','lat','latitude']);
        $lngRider = $findRiderCol(['current_lng','lng','longitude']);
        if ($latRider && $lngRider) {
            $sqlR = "SELECT `".$latRider."` AS lat, `".$lngRider."` AS lng FROM rider WHERE rider_id = ? LIMIT 1";
            if ($s2 = $conn->prepare($sqlR)) {
                $s2->bind_param('i', $assignedRiderId);
                $s2->execute();
                $rres2 = $s2->get_result();
                if ($r2 = $rres2->fetch_assoc()) {
                    if ($r2['lat'] !== null && $r2['lng'] !== null && $r2['lat'] !== '' && $r2['lng'] !== '') {
                        echo json_encode(['success' => true, 'order_id' => (int)$row['order_id'], 'lat' => (float)$r2['lat'], 'lng' => (float)$r2['lng'], 'provider' => $row['provider'] ?? null, 'label' => 'Rider current location']);
                        $s2->close();
                        exit();
                    }
                }
                $s2->close();
            }
        }
    }
}

// Otherwise, return tracking link / number if available
if (!empty($row['tracking_url'])) {
    echo json_encode(['success' => true, 'order_id' => (int)$row['order_id'], 'tracking_url' => $row['tracking_url'], 'provider' => $row['provider'] ?? null]);
    exit();
}
if (!empty($row['tracking_number'])) {
    echo json_encode(['success' => true, 'order_id' => (int)$row['order_id'], 'tracking_number' => $row['tracking_number'], 'provider' => $row['provider'] ?? null]);
    exit();
}

// Nothing available
echo json_encode(['success' => false, 'message' => 'No tracking or location data available for this order']);
exit();
?>