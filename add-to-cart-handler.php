<?php
require_once 'db_connection.php';
session_start();
header('Content-Type: application/json');


$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$query = "
    SELECT 
        s.product_id, 
        s.name, 
        s.Price, 
        b.brand_name, 
        si.image_url
    FROM Product s
    LEFT JOIN Brand b ON s.brand_id = b.brand_id
    LEFT JOIN product_image si ON s.product_id = si.product_id AND si.sort_order = 1
    WHERE s.product_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

$product = $result->fetch_assoc();

// Initialize cart in session if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Add or update product in cart
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['quantity']++;
} else {
    $_SESSION['cart'][$product_id] = [
        'product_id' => $product['product_id'],
        'name' => $product['name'],
        'price' => $product['Price'],
        'brand' => $product['brand_name'],
        'image_url' => $product['image_url'],
        'quantity' => 1
    ];
}

$total_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_items += $item['quantity'];
}

echo json_encode([
    'success' => true,
    'message' => $product['name'] . ' added to cart!',
    'cart_count' => $total_items,
    'product' => $product
]);
?>
