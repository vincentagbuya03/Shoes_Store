<?php
require_once 'db_connection.php';
session_start();

header('Content-Type: application/json');


if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$cart_id = (int)($_POST['cart_id'] ?? 0);
$customer_id = (int)$_SESSION['user_id'];

if ($cart_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid cart ID']);
    exit();
}

// Delete cart item (verify ownership)
$delete_query = "DELETE FROM cart WHERE cart_id = ? AND customer_id = ?";
$stmt = $conn->prepare($delete_query);
$stmt->bind_param('ii', $cart_id, $customer_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error removing item']);
}

$stmt->close();
$conn->close();
?>
