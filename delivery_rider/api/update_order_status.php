<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db_connection.php';

if (!isset($_SESSION['rider_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$rider_id = (int)$_SESSION['rider_id'];

// Handle both JSON and form-encoded POST data
$order_id = 0;
$action = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (stripos($contentType, 'application/json') !== false) {
        // JSON payload
        $input = json_decode(file_get_contents('php://input'), true);
        $order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
        $action = isset($input['action']) ? trim($input['action']) : '';
    } else {
        // Form-encoded payload
        $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
        $action = isset($_POST['action']) ? trim($_POST['action']) : '';
    }
}

if ($order_id <= 0 || $action === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Fetch order to validate
$stmt = $conn->prepare('SELECT order_id, rider_id, status FROM orders WHERE order_id = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
$stmt->bind_param('i', $order_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$current_status = $order['status'];
$assigned_rider = isset($order['rider_id']) ? (int)$order['rider_id'] : 0;

// Allowed actions: start -> set delivering + assign rider if not assigned
// delivered -> set completed (only by assigned rider)
// contact -> return customer phone (handled on client by existing data attribute but accept here too)

if ($action === 'start') {
    // Allow starting if order is pending or confirmed
    $statusLower = strtolower((string)$current_status);
    if ($statusLower !== 'pending' && $statusLower !== 'confirmed') {
        echo json_encode(['success' => false, 'message' => 'Order cannot be started']);
        exit;
    }

    // If the order is already assigned to another rider, prevent starting
    if ($assigned_rider > 0 && $assigned_rider !== $rider_id) {
        echo json_encode(['success' => false, 'message' => 'Order assigned to another rider']);
        exit;
    }

    // Assign current rider (if not already) and set status to delivering
    $upd = $conn->prepare('UPDATE orders SET status = ?, rider_id = ? WHERE order_id = ?');
    $new_status = 'delivering';
    $upd->bind_param('sii', $new_status, $rider_id, $order_id);
    if ($upd->execute()) {
        echo json_encode(['success' => true, 'message' => 'Delivery started', 'new_status' => $new_status, 'assigned_rider' => $rider_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not update order']);
    }
    $upd->close();
    exit;
}

if ($action === 'delivered') {
    // Only assigned rider can mark delivered
    if ($assigned_rider !== $rider_id) {
        echo json_encode(['success' => false, 'message' => 'You are not assigned to this order']);
        exit;
    }
    $upd = $conn->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
    $new_status = 'completed';
    $upd->bind_param('si', $new_status, $order_id);
    if ($upd->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order marked as delivered', 'new_status' => $new_status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not update order']);
    }
    $upd->close();
    exit;
}

if ($action === 'contact') {
    // Return customer phone if available
    $stmt = $conn->prepare('SELECT c.phone FROM orders o LEFT JOIN customer c ON o.customer_id = c.customer_id WHERE o.order_id = ? LIMIT 1');
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    $phone = $row['phone'] ?? '';
    if ($phone) {
        echo json_encode(['success' => true, 'phone' => $phone]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Phone not available']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);

?>
