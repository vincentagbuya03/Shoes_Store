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
$name = trim($_POST['name'] ?? '') ?: null;
$email = trim($_POST['email'] ?? '') ?: null;
$subject = trim($_POST['subject'] ?? '') ?: 'Customer message';
$message = trim($_POST['message'] ?? '');

if (!$message) {
    echo json_encode(['success' => false, 'message' => 'Message is required']);
    exit;
}

// Ensure table exists
$create = "CREATE TABLE IF NOT EXISTS customer_messages (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    name VARCHAR(150) NULL,
    email VARCHAR(150) NULL,
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($create);

$stmt = $conn->prepare("INSERT INTO customer_messages (customer_id, name, email, subject, message) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    error_log('customer_message: prepare failed: ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
$stmt->bind_param('issss', $customer_id, $name, $email, $subject, $message);
if (!$stmt->execute()) {
    error_log('customer_message: execute failed: ' . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}
$id = $stmt->insert_id;
$stmt->close();

// Create admin notification
$title = $subject;
$body = ($name ? $name . ' — ' : '') . (strlen($message) > 200 ? substr($message,0,197).'...' : $message);
$meta = ['message_id' => $id, 'customer_id' => $customer_id, 'email' => $email];
$url = '/admin/messages.php?id=' . $id;
@create_admin_notification($conn, 'customer_message', $title, $body, 'medium', $meta, $url);

echo json_encode(['success' => true, 'id' => $id]);
exit;
