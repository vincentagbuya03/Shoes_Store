<?php
require_once 'db_connection.php';
session_start();
header('Content-Type: application/json');
$cart_count = 0;
if (isset($_SESSION['customer_id'])) {
    $cid = (int)$_SESSION['customer_id'];
        $cart_count_query = $conn->prepare('SELECT COUNT(*) as total FROM cart WHERE customer_id = ?');
        $cart_count_query->bind_param('i', $cid);
        $cart_count_query->execute();
        $cart_count_result = $cart_count_query->get_result();
        if ($cart_count_result && ($row = $cart_count_result->fetch_assoc())) {
            $cart_count = (int)($row['total'] ?? 0);
        }
        $cart_count_query->close();
}
echo json_encode(['cart_count' => $cart_count]);
?>