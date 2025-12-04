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

// Ensure refund_requests has admin_note/processed_at/processed_by columns
function ensure_refund_columns($conn) {
    $cols = [];
    $res = $conn->query("SHOW COLUMNS FROM refund_requests");
    if ($res) { while ($r = $res->fetch_assoc()) $cols[] = $r['Field']; }
    if (!in_array('admin_note', $cols)) {
        $conn->query("ALTER TABLE refund_requests ADD COLUMN admin_note TEXT NULL");
    }
    if (!in_array('processed_at', $cols)) {
        $conn->query("ALTER TABLE refund_requests ADD COLUMN processed_at TIMESTAMP NULL");
    }
    if (!in_array('processed_by', $cols)) {
        $conn->query("ALTER TABLE refund_requests ADD COLUMN processed_by INT NULL");
    }
}

if ($action === 'list') {
    $sql = "SELECT rr.*, o.total_amount, c.name AS customer_name, c.email AS customer_email FROM refund_requests rr LEFT JOIN orders o ON o.order_id = rr.order_id LEFT JOIN customer c ON c.customer_id = rr.customer_id ORDER BY rr.created_at DESC LIMIT 500";
    $res = $conn->query($sql);
    if (!$res) jsonErr('Query failed: ' . $conn->error);
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success'=>true,'refunds'=>$rows]); exit;
}

if ($action === 'get') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$id) jsonErr('Missing id');
    $stmt = $conn->prepare("SELECT rr.*, o.total_amount, c.name AS customer_name, c.email AS customer_email FROM refund_requests rr LEFT JOIN orders o ON o.order_id = rr.order_id LEFT JOIN customer c ON c.customer_id = rr.customer_id WHERE rr.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    echo json_encode(['success'=>true,'refund'=>$row]); exit;
}

if ($action === 'update_status') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = trim($_POST['status'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $allowed = ['requested','processing','resolved','rejected'];
    if (!$id || !in_array($status, $allowed)) jsonErr('Invalid input');

    ensure_refund_columns($conn);

    $stmt = $conn->prepare('UPDATE refund_requests SET status = ?, admin_note = ?, processed_at = NOW(), processed_by = ? WHERE id = ?');
    if (!$stmt) jsonErr('Prepare failed: ' . $conn->error);
    $admin_id = (int)$_SESSION['admin_id'];
    $stmt->bind_param('siii', $status, $note, $admin_id, $id);
    if (!$stmt->execute()) jsonErr('Update failed: ' . $stmt->error);

    // If resolved, optionally mark order cancelled and notify admin
    if ($status === 'resolved') {
        // fetch order id
        $s = $conn->prepare('SELECT order_id FROM refund_requests WHERE id = ?');
        if ($s) { $s->bind_param('i',$id); $s->execute(); $r = $s->get_result()->fetch_assoc(); $s->close(); }
        $order_id = isset($r['order_id']) ? (int)$r['order_id'] : 0;
        if ($order_id) {
            // set orders.status = 'cancelled' if column exists
            $qc = $conn->query("SHOW COLUMNS FROM orders LIKE 'status'");
            if ($qc && $qc->num_rows) {
                $u = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
                if ($u) { $u->bind_param('i',$order_id); $u->execute(); $u->close(); }
            }
            // add a notification to admin (refund resolved)
            if (is_file(__DIR__ . '/../../inc/admin_notifications.php')) {
                require_once __DIR__ . '/../../inc/admin_notifications.php';
                $title = "Refund resolved for Order #{$order_id}";
                $body = "Refund request #{$id} has been resolved by admin.";
                $meta = ['refund_id'=>$id,'order_id'=>$order_id];
                @create_admin_notification($conn, 'refund_resolved', $title, $body, 'high', $meta, '/admin/orders.php?id=' . $order_id);
            }
        }
    }

    echo json_encode(['success'=>true]); exit;
}

jsonErr('Invalid action');
