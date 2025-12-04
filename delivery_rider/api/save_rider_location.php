<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Local/dev friendly: include DB errors in responses when running from localhost
$isLocalRequest = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);

if (!isset($_SESSION['rider_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../db_connection.php';

$rider_id = (int)$_SESSION['rider_id'];

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
// Temporary debug logging to help diagnose why coords may not be saved
$debugPath = __DIR__ . '/../../scripts/save_rider_location_debug.log';
$dbg = [
    'ts' => date('c'),
    'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
    'session_rider_id' => $_SESSION['rider_id'] ?? null,
    'raw' => $raw,
    'headers' => (function_exists('getallheaders') ? getallheaders() : []),
];
@file_put_contents($debugPath, json_encode($dbg, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$lat = isset($data['lat']) ? $data['lat'] : (isset($data['latitude']) ? $data['latitude'] : null);
$lng = isset($data['lng']) ? $data['lng'] : (isset($data['longitude']) ? $data['longitude'] : null);
$heading = isset($data['heading']) ? $data['heading'] : null;
$accuracy = isset($data['accuracy']) ? $data['accuracy'] : null;

if (!is_numeric($lat) || !is_numeric($lng)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing or invalid lat/lng']);
    exit;
}

$lat = (float)$lat; $lng = (float)$lng;

// Try inserting into rider_location_history if table exists
$tblRes = $conn->query("SHOW TABLES LIKE 'rider_location_history'");
if ($tblRes && $tblRes->num_rows > 0) {
    $cols = [];
    $cRes = $conn->query("SHOW COLUMNS FROM rider_location_history");
    if ($cRes) { while ($r = $cRes->fetch_assoc()) { $cols[] = $r['Field']; } }

    $find = function(array $candidates) use ($cols) {
        foreach ($candidates as $c) { if (in_array($c, $cols, true)) return $c; }
        return null;
    };

    $latCol = $find(['lat','latitude','location_lat','lat_deg','coord_lat']);
    $lngCol = $find(['lng','longitude','location_lng','lng_deg','coord_lng']);
    $headingCol = $find(['heading','bearing']);
    $accCol = $find(['accuracy','acc']);
    $timeCol = $find(['recorded_at','created_at','timestamp','ts']);

    if ($latCol && $lngCol) {
        @file_put_contents($debugPath, json_encode(['ts' => date('c'), 'event' => 'history_insert_prepare', 'latCol' => $latCol, 'lngCol' => $lngCol, 'headingCol' => $headingCol, 'accCol' => $accCol]) . PHP_EOL, FILE_APPEND);
        $colsToInsert = ['rider_id', $latCol, $lngCol];
        $placeholders = ['?', '?', '?'];
        $types = 'idd';
        $values = [$rider_id, $lat, $lng];

        if ($headingCol && !is_null($heading)) { $colsToInsert[] = $headingCol; $placeholders[] = '?'; $types .= 'd'; $values[] = (float)$heading; }
        if ($accCol && !is_null($accuracy)) { $colsToInsert[] = $accCol; $placeholders[] = '?'; $types .= 'd'; $values[] = (float)$accuracy; }

        $colsList = implode(', ', array_map(function($c){ return "`$c`"; }, $colsToInsert));
        $placeStr = implode(', ', $placeholders);

        $sql = "INSERT INTO rider_location_history ({$colsList}) VALUES ({$placeStr})";
        @file_put_contents($debugPath, json_encode(['ts' => date('c'), 'event' => 'history_sql', 'sql' => $sql]) . PHP_EOL, FILE_APPEND);
        if ($stmt = $conn->prepare($sql)) {
            @file_put_contents($debugPath, json_encode(['ts' => date('c'), 'event' => 'history_prepare_ok']) . PHP_EOL, FILE_APPEND);
            $bindNames = [];
            $bindNames[] = $types;
            for ($i=0;$i<count($values);$i++) { $bindNames[] = &$values[$i]; }
            call_user_func_array([$stmt, 'bind_param'], $bindNames);
            $ok = $stmt->execute();
            if (!$ok) {
                error_log('save_rider_location: history insert failed: ' . $stmt->error);
                if ($isLocalRequest) {
                    // return early with error details for easier debugging
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'history_insert_failed', 'details' => $stmt->error]);
                    exit;
                }
            }
            $stmt->close();
        } else {
            error_log('save_rider_location: prepare failed for history insert: ' . $conn->error);
            if ($isLocalRequest) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'prepare_failed', 'details' => $conn->error]);
                exit;
            }
        }
    }
}

$colLatRes = $conn->query("SHOW COLUMNS FROM rider LIKE 'current_lat'");
$colLngRes = $conn->query("SHOW COLUMNS FROM rider LIKE 'current_lng'");
$hasCurrentCols = ($colLatRes && $colLatRes->num_rows > 0) && ($colLngRes && $colLngRes->num_rows > 0);
@file_put_contents($debugPath, json_encode(['ts' => date('c'), 'event' => 'check_has_current_cols', 'hasCurrentCols' => (bool)$hasCurrentCols, 'colLatRes' => (bool)$colLatRes, 'colLngRes' => (bool)$colLngRes, 'conn_error' => $conn->error]) . PHP_EOL, FILE_APPEND);
if ($hasCurrentCols) {
    if ($ustmt = $conn->prepare("UPDATE rider SET current_lat = ?, current_lng = ? WHERE rider_id = ?")) {
        if (!$ustmt->bind_param('ddi', $lat, $lng, $rider_id)) {
            error_log('save_rider_location: bind_param failed for rider update: ' . $ustmt->error);
            if ($isLocalRequest) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'bind_failed', 'details' => $ustmt->error]);
                exit;
            }
        }
        if (!$ustmt->execute()) {
            error_log('save_rider_location: rider update failed: ' . $ustmt->error);
            if ($isLocalRequest) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'update_failed', 'details' => $ustmt->error]);
                exit;
            }
        } else {
            // Log affected rows for debugging
            $af = $ustmt->affected_rows;
            @file_put_contents($debugPath, json_encode(['ts' => date('c'), 'event' => 'update_result', 'rider_id' => $rider_id, 'affected_rows' => $af]) . PHP_EOL, FILE_APPEND);
        }
        $ustmt->close();
    } else {
        error_log('save_rider_location: prepare failed for rider update: ' . $conn->error);
        if ($isLocalRequest) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'prepare_failed_update', 'details' => $conn->error]);
            exit;
        }
    }
}

echo json_encode(['success' => true, 'lat' => $lat, 'lng' => $lng]);
exit;

?>
