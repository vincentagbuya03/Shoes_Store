<?php
require_once 'db_connection.php';
session_start();

header('Content-Type: application/jso   n');

if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart.']);
    exit();
}


$variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($variant_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid variant ID']);
    exit();
}

if ($quantity <= 0) {
     $quantity = 1;
}

$customer_id = (int)$_SESSION['customer_id'];


$query = "
    SELECT 
        v.variant_id,
        v.product_id,
        v.stock,
        v.price,
        p.name,
        b.brand_name,
        c.color_name,
        s.size_name,
        v.image_url
    FROM product_variant v
    LEFT JOIN product p ON v.product_id = p.product_id
    LEFT JOIN brand b ON p.brand_id = b.brand_id
    LEFT JOIN color c ON v.color_id = c.color_id
    LEFT JOIN size s ON v.size_id = s.size_id
    WHERE v.variant_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $variant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Variant not found']);
    exit();
}

$variant = $result->fetch_assoc();

if ($variant['stock'] < $quantity) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Not enough stock available. Stock: ' . $variant['stock']]);
    exit();
}


$check_query = "SELECT cart_id, quantity FROM cart WHERE customer_id = ? AND variant_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('ii', $customer_id, $variant_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();


if ($check_result->num_rows > 0) {
    $cart_item = $check_result->fetch_assoc();
    $new_quantity = $cart_item['quantity'] + $quantity;
    if ($new_quantity > $variant['stock']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Not enough stock available for this quantity.']);
        exit();
    }
    $update_query = "UPDATE cart SET quantity = ? WHERE cart_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('ii', $new_quantity, $cart_item['cart_id']);
    if ($update_stmt->execute()) {
        $count_query = "SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param('i', $customer_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $cart_count = (int)($count_row['total'] ?? 0);
        $count_stmt->close();
        echo json_encode([
            'success' => true,
            'message' => $variant['name'] . ' (' . $variant['color_name'] . ' / ' . $variant['size_name'] . ") quantity updated in cart!",
            'product_name' => $variant['name'],
            'quantity' => $new_quantity,
            'cart_count' => $cart_count
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating cart.']);
    }
    $update_stmt->close();
} else {
    $insert_query = "INSERT INTO cart (customer_id, variant_id, quantity) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('iii', $customer_id, $variant_id, $quantity);
    if ($insert_stmt->execute()) {
        $count_query = "SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param('i', $customer_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $cart_count = (int)($count_row['total'] ?? 0);
        $count_stmt->close();
        echo json_encode([
            'success' => true,
            'message' => $variant['name'] . ' (' . $variant['color_name'] . ' / ' . $variant['size_name'] . ") added to cart!",
            'product_name' => $variant['name'],
            'quantity' => $quantity,
            'cart_count' => $cart_count
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error adding item to cart.']);
    }
    $insert_stmt->close();
}
$stmt->close();
$check_stmt->close();
$conn->close();
?>
