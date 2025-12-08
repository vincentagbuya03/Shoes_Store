<?php
require_once __DIR__ . '/db_connection.php';
require_once __DIR__ . '/inc/admin_notifications.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// Robust error handling: log full details to a file and return a short debug id in JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

set_exception_handler(function($ex) {
    $id = uniqid('rf_', true);
    $logDir = __DIR__ . '/storage';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . '/refund_errors.log';
    $entry = [
        'id' => $id,
        'time' => date('c'),
        'message' => $ex->getMessage(),
        'file' => $ex->getFile(),
        'line' => $ex->getLine(),
        'trace' => $ex->getTraceAsString()
    ];
    @file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    // return a safe JSON with reference id
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error', 'debug_id' => $id]);
    exit;
});

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err) {
        $id = uniqid('rf_', true);
        $logDir = __DIR__ . '/storage';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $logFile = $logDir . '/refund_errors.log';
        $entry = [
            'id' => $id,
            'time' => date('c'),
            'fatal' => $err
        ];
        @file_put_contents($logFile, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
        http_response_code(500);
        // attempt to return JSON if not already sent
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Internal server error', 'debug_id' => $id]);
    }
});

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

// Ensure table exists with all required columns
$create = "CREATE TABLE IF NOT EXISTS refund_requests (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NULL,
    reason TEXT,
    status ENUM('requested','processing','pickup_scheduled','picked_up','resolved','rejected') DEFAULT 'requested',
    rider_pickup_id INT DEFAULT NULL,
    pickup_date DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($create);

// Add columns if they don't exist (for existing installations) - MySQL 5.7 compatible
try {
    $cols = $conn->query("SHOW COLUMNS FROM refund_requests LIKE 'rider_pickup_id'");
    if ($cols && $cols->num_rows === 0) {
        $conn->query("ALTER TABLE refund_requests ADD COLUMN rider_pickup_id INT DEFAULT NULL");
    }
} catch (Exception $e) { /* ignore */ }

try {
    $cols = $conn->query("SHOW COLUMNS FROM refund_requests LIKE 'pickup_date'");
    if ($cols && $cols->num_rows === 0) {
        $conn->query("ALTER TABLE refund_requests ADD COLUMN pickup_date DATETIME DEFAULT NULL");
    }
} catch (Exception $e) { /* ignore */ }

try {
    $cols = $conn->query("SHOW COLUMNS FROM refund_requests LIKE 'updated_at'");
    if ($cols && $cols->num_rows === 0) {
        $conn->query("ALTER TABLE refund_requests ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
} catch (Exception $e) { /* ignore */ }

// Update status enum to include new pickup statuses (ignore errors if already updated)
@$conn->query("ALTER TABLE refund_requests MODIFY COLUMN status ENUM('requested','processing','pickup_scheduled','picked_up','resolved','rejected') DEFAULT 'requested'");

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

// Update order status to 'refund_requested'
// First, ensure the ENUM includes 'refund_requested' (add if not already present)
@$conn->query("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','delivering','completed','cancelled','refund_requested') DEFAULT 'pending'");

$update_status = $conn->prepare("UPDATE orders SET status = 'refund_requested' WHERE order_id = ?");
if ($update_status) {
    $update_status->bind_param('i', $order_id);
    $update_status->execute();
    $update_status->close();
}

// Ensure `orders` has columns to track refund status and latest refund id (idempotent)
try {
    $col = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'refund_status'");
    if (!($col && $col->num_rows > 0)) {
        $conn->query("ALTER TABLE `orders` ADD COLUMN `refund_status` ENUM('requested','processing','resolved','rejected') DEFAULT NULL AFTER `status`");
    }
} catch (Exception $e) { /* ignore */ }
try {
    $col2 = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'refund_id'");
    if (!($col2 && $col2->num_rows > 0)) {
        $conn->query("ALTER TABLE `orders` ADD COLUMN `refund_id` INT DEFAULT NULL AFTER `refund_status`");
    }
} catch (Exception $e) { /* ignore */ }

// Update orders table to reflect refund request (set refund_status and refund_id)
try {
    $u = $conn->prepare("UPDATE orders SET refund_status = 'requested', refund_id = ? WHERE order_id = ?");
    if ($u) {
        $u->bind_param('ii', $id, $order_id);
        $u->execute();
        $u->close();
    }
} catch (Exception $e) { /* ignore */ }

$title = "Refund requested for Order #{$order_id}";
$body = strlen($reason) > 200 ? substr($reason,0,197).'...' : $reason;
$meta = ['refund_id' => $id, 'order_id' => $order_id, 'customer_id' => $customer_id];
$url = '/admin/orders.php?id=' . $order_id;
@create_admin_notification($conn, 'refund_request', $title, $body, 'high', $meta, $url);

echo json_encode(['success' => true, 'id' => $id]);
exit;
