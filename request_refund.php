<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/inc/admin_notifications.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$reason = trim($_POST['reason'] ?? '') ?: 'No reason provided';

if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing order_id']);
    exit;
}

// Ensure table exists
$create = "CREATE TABLE IF NOT EXISTS refund_requests (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NULL,
    reason TEXT,
    status ENUM('requested','processing','resolved','rejected') DEFAULT 'requested',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($create);

$stmt = $conn->prepare("INSERT INTO refund_requests (order_id, customer_id, reason) VALUES (?, ?, ?)");
if (!$stmt) {
    error_log('request_refund: prepare failed: ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
$stmt->bind_param('iis', $order_id, $customer_id, $reason);
if (!$stmt->execute()) {
    error_log('request_refund: execute failed: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
$id = $stmt->insert_id;
$stmt->close();

$title = "Refund requested for Order #{$order_id}";
$body = strlen($reason) > 200 ? substr($reason,0,197).'...' : $reason;
$meta = ['refund_id' => $id, 'order_id' => $order_id, 'customer_id' => $customer_id];
$url = '/admin/orders.php?id=' . $order_id;
@create_admin_notification($conn, 'refund_request', $title, $body, 'high', $meta, $url);

echo json_encode(['success' => true, 'id' => $id]);
exit;
