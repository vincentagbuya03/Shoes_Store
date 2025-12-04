<?php
// Debug endpoint to return a rider's latest location by rider_id.
// Usage (token protected):
//  http://.../delivery_rider/api/debug_get_rider_location.php?rider_id=2&token=localdebug123

header('Content-Type: application/json; charset=utf-8');

$ALLOWED_TOKEN = 'localdebug123'; // local dev token — change if needed

session_start();

$providedToken = isset($_GET['token']) ? $_GET['token'] : null;
$useToken = ($providedToken === $ALLOWED_TOKEN);

require_once __DIR__ . '/../../db_connection.php';

// If token not provided, require session rider (same behavior as production API)
if (!$useToken && !isset($_SESSION['rider_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$rider_id = 0;
if (isset($_GET['rider_id'])) {
    $rider_id = (int)$_GET['rider_id'];
} elseif (isset($_SESSION['rider_id'])) {
    $rider_id = (int)$_SESSION['rider_id'];
}

if ($rider_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid rider_id']);
    exit;
}

$result = ['success' => false];

// Prefer rider_location_history if available
$tblRes = $conn->query("SHOW TABLES LIKE 'rider_location_history'");
if ($tblRes && $tblRes->num_rows > 0) {
    $cols = [];
    $cRes = $conn->query("SHOW COLUMNS FROM rider_location_history");
    if ($cRes) {
        while ($c = $cRes->fetch_assoc()) { $cols[] = $c['Field']; }
    }

    $latCandidates = ['lat','latitude','location_lat','lat_deg','coord_lat','lat_value'];
    $lngCandidates = ['lng','longitude','long','lon','location_lng','lng_deg','coord_lng','lon_value'];
    $timeCandidates = ['recorded_at','created_at','timestamp','ts','created','time','updated_at'];

    $find = function(array $candidates) use ($cols) {
        foreach ($candidates as $c) { if (in_array($c, $cols, true)) return $c; }
        return null;
    };

    $latCol = $find($latCandidates);
    $lngCol = $find($lngCandidates);
    $timeCol = $find($timeCandidates) ?: 'id';

    if ($latCol && $lngCol) {
        $select = "{$latCol} AS lat, {$lngCol} AS lng";
        $orderBy = in_array($timeCol, $cols, true) ? $timeCol : 'id';
        $sql = "SELECT {$select}, {$orderBy} AS ts FROM rider_location_history WHERE rider_id = ? ORDER BY {$orderBy} DESC LIMIT 1";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('i', $rider_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if ($row['lat'] !== null && $row['lng'] !== null && $row['lat'] !== '' && $row['lng'] !== '') {
                    $result = ['success' => true, 'source' => 'rider_location_history', 'lat' => (float)$row['lat'], 'lng' => (float)$row['lng'], 'ts' => $row['ts']];
                    echo json_encode($result);
                    exit;
                }
            }
            $stmt->close();
        }
    }
}

// Fallback: check rider table for common columns
$cols = [];
$cRes = $conn->query("SHOW COLUMNS FROM rider");
if ($cRes) { while ($c = $cRes->fetch_assoc()) { $cols[] = $c['Field']; } }

$latCandidates = ['current_lat','lat','latitude'];
$lngCandidates = ['current_lng','lng','longitude'];

$find = function(array $candidates) use ($cols) {
    foreach ($candidates as $c) { if (in_array($c, $cols, true)) return $c; }
    return null;
};

$latCol = $find($latCandidates);
$lngCol = $find($lngCandidates);

if ($latCol && $lngCol) {
    $sql = "SELECT {$latCol} AS lat, {$lngCol} AS lng FROM rider WHERE rider_id = ? LIMIT 1";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('i', $rider_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if ($row['lat'] !== null && $row['lng'] !== null && $row['lat'] !== '' && $row['lng'] !== '') {
                $result = ['success' => true, 'source' => 'rider', 'lat' => (float)$row['lat'], 'lng' => (float)$row['lng']];
                echo json_encode($result);
                exit;
            }
        }
        $stmt->close();
    }
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'No location available for rider_id ' . $rider_id]);
exit;

?>
