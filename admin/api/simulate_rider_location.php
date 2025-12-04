<?php
// Admin-only helper: insert a simulated rider location for testing.
require_once __DIR__ . '/../db_connection.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$rider_id = $_POST['rider_id'] ?? null;
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

if (!$rider_id || $lat === null || $lng === null) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

$rider_id = trim($rider_id);
$lat = trim($lat);
$lng = trim($lng);

if (!is_numeric($lat) || !is_numeric($lng)) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates']);
    exit();
}

$lat = (float)$lat;
$lng = (float)$lng;

// Try to insert into rider_location_history when available
$tblRes = $conn->query("SHOW TABLES LIKE 'rider_location_history'");
if ($tblRes && $tblRes->num_rows) {
    $cRes = $conn->query("SHOW COLUMNS FROM rider_location_history");
    $cols = [];
    while ($c = $cRes->fetch_assoc()) $cols[] = $c['Field'];

    // pick candidate names
    $latCandidates = ['lat','latitude','current_lat','tracking_lat','last_lat','delivery_lat'];
    $lngCandidates = ['lng','longitude','current_lng','tracking_lng','last_lng','delivery_lng'];
    $riderCandidates = ['rider_id','rider','driver_id','driver'];
    $timeCandidates = ['ts','created_at','created','timestamp','recorded_at'];

    $colLat = null; $colLng = null; $colRider = null; $colTime = null;
    foreach ($latCandidates as $c) if (in_array($c, $cols)) { $colLat = $c; break; }
    foreach ($lngCandidates as $c) if (in_array($c, $cols)) { $colLng = $c; break; }
    foreach ($riderCandidates as $c) if (in_array($c, $cols)) { $colRider = $c; break; }
    foreach ($timeCandidates as $c) if (in_array($c, $cols)) { $colTime = $c; break; }

    if ($colLat && $colLng && $colRider) {
        $insertCols = [$colRider, $colLat, $colLng];
        $placeholders = implode(',', array_fill(0, count($insertCols), '?'));
        $sql = 'INSERT INTO rider_location_history (' . implode(',', $insertCols) . ') VALUES (' . $placeholders . ')';
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('idd', $rider_id, $lat, $lng);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'source' => 'rider_location_history']);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => 'Insert failed']);
                exit();
            }
        }
    }
}

// Fallback: update rider.current_lat/current_lng if columns exist
$colLatRes = $conn->query("SHOW COLUMNS FROM rider LIKE 'current_lat'");
$colLngRes = $conn->query("SHOW COLUMNS FROM rider LIKE 'current_lng'");
if ($colLatRes && $colLatRes->num_rows && $colLngRes && $colLngRes->num_rows) {
    if ($ustmt = $conn->prepare("UPDATE rider SET current_lat = ?, current_lng = ? WHERE rider_id = ?")) {
        $ustmt->bind_param('ddi', $lat, $lng, $rider_id);
        if ($ustmt->execute()) {
            echo json_encode(['success' => true, 'source' => 'rider_table']);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Update failed']);
            exit();
        }
    }
}

// If we reach here, can't store location
echo json_encode(['success' => false, 'message' => 'No suitable storage for rider location']);
exit();

?>
