<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['rider_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../db_connection.php';

$rider_id = (int)$_SESSION['rider_id'];

$result = ['success' => false];

// Prefer rider_location_history if the table exists
$tblRes = $conn->query("SHOW TABLES LIKE 'rider_location_history'");
if ($tblRes && $tblRes->num_rows > 0) {
    $cols = [];
    $cRes = $conn->query("SHOW COLUMNS FROM rider_location_history");
    if ($cRes) {
        while ($c = $cRes->fetch_assoc()) { $cols[] = $c['Field']; }
    }

    $find = function(array $candidates) use ($cols) {
        foreach ($candidates as $c) { if (in_array($c, $cols, true)) return $c; }
        return null;
    };

    $latCols = ['lat','latitude','latitude_deg','location_lat','lat_deg','coord_lat','lat_value'];
    $lngCols = ['lng','longitude','long','lon','location_lng','lng_deg','coord_lng','lon_value'];
    $timeCols = ['created_at','recorded_at','timestamp','ts','created','time','updated_at'];

    $latCol = $find($latCols);
    $lngCol = $find($lngCols);
    $timeCol = $find($timeCols) ?: 'id';

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
                    $result = ['success' => true, 'lat' => (float)$row['lat'], 'lng' => (float)$row['lng'], 'ts' => $row['ts']];
                    echo json_encode($result);
                    exit;
                }
            }
            $stmt->close();
        }
    }
}

// Fallback to rider.current_lat/current_lng
if ($stmt = $conn->prepare("SELECT current_lat, current_lng FROM rider WHERE rider_id = ? LIMIT 1")) {
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (!is_null($row['current_lat']) && !is_null($row['current_lng']) && $row['current_lat'] !== '' && $row['current_lng'] !== '') {
            $result = ['success' => true, 'lat' => (float)$row['current_lat'], 'lng' => (float)$row['current_lng']];
            echo json_encode($result);
            exit;
        }
    }
    $stmt->close();
}

// No data found
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'No location available']);
exit;

?>
