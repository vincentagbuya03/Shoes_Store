<?php
/**
 * Rider Notifications API
 * Handles notification operations for delivery riders
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db_connection.php';
session_start();

// Authentication check
if (!isset($_SESSION['rider_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$rider_id = $_SESSION['rider_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function jsonErr($m) {
    echo json_encode(['success' => false, 'message' => $m]);
    exit;
}

function jsonSuccess($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

// Check if notifications table exists
function ensureNotificationsTable($conn) {
    $check = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($check && $check->num_rows > 0) {
        return true;
    }
    
    // Create the table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` int NOT NULL AUTO_INCREMENT,
        `user_type` enum('customer','rider','admin') NOT NULL,
        `user_id` int NOT NULL,
        `type` varchar(80) NOT NULL,
        `title` varchar(255) DEFAULT NULL,
        `body` text,
        `payload` json DEFAULT NULL,
        `url` varchar(255) DEFAULT NULL,
        `read_at` timestamp NULL DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_user` (`user_type`, `user_id`),
        KEY `idx_read` (`read_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
    
    return $conn->query($sql);
}

ensureNotificationsTable($conn);

// Get unread count
if ($action === 'count') {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_type = 'rider' AND user_id = ? AND read_at IS NULL");
    if (!$stmt) jsonErr('Query failed');
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    jsonSuccess(['unread' => (int)($row['cnt'] ?? 0)]);
}

// List notifications
if ($action === 'list') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $limit = min(max($limit, 1), 50);
    
    // Check if title and body columns exist
    $cols = [];
    $colRes = $conn->query("SHOW COLUMNS FROM notifications");
    if ($colRes) {
        while ($c = $colRes->fetch_assoc()) {
            $cols[] = $c['Field'];
        }
    }
    
    $hasTitle = in_array('title', $cols);
    $hasBody = in_array('body', $cols);
    $hasUrl = in_array('url', $cols);
    
    $selectCols = "id, type, payload, read_at, created_at";
    if ($hasTitle) $selectCols .= ", title";
    if ($hasBody) $selectCols .= ", body";
    if ($hasUrl) $selectCols .= ", url";
    
    $stmt = $conn->prepare("SELECT {$selectCols} FROM notifications WHERE user_type = 'rider' AND user_id = ? ORDER BY created_at DESC LIMIT ?");
    if (!$stmt) jsonErr('Prepare failed: ' . $conn->error);
    $stmt->bind_param('ii', $rider_id, $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $notifications = [];
    while ($row = $res->fetch_assoc()) {
        // Parse payload if it's JSON
        if (!empty($row['payload'])) {
            $row['payload'] = json_decode($row['payload'], true);
        }
        
        // Set defaults for missing columns
        if (!$hasTitle) $row['title'] = $row['type'] ?? 'Notification';
        if (!$hasBody) $row['body'] = '';
        if (!$hasUrl) $row['url'] = null;
        
        // Format the notification
        $notifications[] = [
            'id' => (int)$row['id'],
            'type' => $row['type'],
            'title' => $row['title'] ?? formatNotificationType($row['type']),
            'body' => $row['body'] ?? '',
            'url' => $row['url'],
            'payload' => $row['payload'],
            'is_read' => !is_null($row['read_at']),
            'created_at' => $row['created_at'],
            'time_ago' => timeAgo($row['created_at'])
        ];
    }
    
    jsonSuccess(['notifications' => $notifications]);
}

// Mark notification as read
if ($action === 'mark_read') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id > 0) {
        // Mark single notification
        $stmt = $conn->prepare("UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_type = 'rider' AND user_id = ?");
        if (!$stmt) jsonErr('Prepare failed');
        $stmt->bind_param('ii', $id, $rider_id);
        $stmt->execute();
    } else {
        // Mark all as read
        $stmt = $conn->prepare("UPDATE notifications SET read_at = NOW() WHERE user_type = 'rider' AND user_id = ? AND read_at IS NULL");
        if (!$stmt) jsonErr('Prepare failed');
        $stmt->bind_param('i', $rider_id);
        $stmt->execute();
    }
    
    jsonSuccess(['marked' => $stmt->affected_rows]);
}

// Delete notification
if ($action === 'delete') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if ($id > 0) {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_type = 'rider' AND user_id = ?");
        if (!$stmt) jsonErr('Prepare failed');
        $stmt->bind_param('ii', $id, $rider_id);
        $stmt->execute();
        jsonSuccess(['deleted' => $stmt->affected_rows > 0]);
    }
    
    jsonErr('Invalid notification ID');
}

// Clear all notifications
if ($action === 'clear_all') {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE user_type = 'rider' AND user_id = ?");
    if (!$stmt) jsonErr('Prepare failed');
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    jsonSuccess(['cleared' => $stmt->affected_rows]);
}

jsonErr('Invalid action');

// Helper functions
function formatNotificationType($type) {
    $types = [
        'new_order' => 'New Delivery Assigned',
        'order_update' => 'Order Update',
        'order_cancelled' => 'Order Cancelled',
        'payment_received' => 'Payment Received',
        'earnings_credited' => 'Earnings Credited',
        'system' => 'System Notification',
        'reminder' => 'Reminder'
    ];
    return $types[$type] ?? ucwords(str_replace('_', ' ', $type));
}

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
?>
