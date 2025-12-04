<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db_connection.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function jsonErr($m){ echo json_encode(['success'=>false,'message'=>$m]); exit; }

// pick table name that exists
function notifications_table($conn) {
    $t = 'admin_notifications';
    $q = $conn->query("SHOW TABLES LIKE 'admin_notifications'");
    if ($q && $q->num_rows) return 'admin_notifications';
    $q2 = $conn->query("SHOW TABLES LIKE 'admin_notification'");
    if ($q2 && $q2->num_rows) return 'admin_notification';
    return 'admin_notifications';
}

$table = notifications_table($conn);

if ($action === 'count') {
    $res = $conn->query("SELECT COUNT(*) AS cnt FROM `{$table}` WHERE is_read = 0");
    if (!$res) jsonErr('Query failed');
    $r = $res->fetch_assoc();
    echo json_encode(['success'=>true,'unread'=> (int)($r['cnt'] ?? 0)]);
    exit;
}

if ($action === 'list') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
    $limit = $limit > 100 ? 100 : max(1,$limit);
    $stmt = $conn->prepare("SELECT id, type, title, body, level, meta, url, is_read, created_at FROM `{$table}` ORDER BY created_at DESC LIMIT ?");
    if (!$stmt) jsonErr('Prepare failed: ' . $conn->error);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success'=>true,'notifications'=>$rows]);
    exit;
}

if ($action === 'mark_read') {
    // mark single id or all
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE `{$table}` SET is_read = 1, read_at = NOW() WHERE id = ?");
        if (!$stmt) jsonErr('Prepare failed');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success'=>true]);
        exit;
    }
    // mark all
    $conn->query("UPDATE `{$table}` SET is_read = 1, read_at = NOW() WHERE is_read = 0");
    echo json_encode(['success'=>true]);
    exit;
}

jsonErr('Invalid action');

?>
