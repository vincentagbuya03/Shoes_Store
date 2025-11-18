<?php
require_once 'db_connection.php';
session_start();

header('Content-Type: application/jso   n');

if (!isset($_SESSION['customer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart.']);
    exit();
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

if ($quantity <= 0) {
     $quantity = 1;
}

$customer_id = (int)$_SESSION['customer_id'];

$query = "
    SELECT 
        s.product_id, 
        s.name, 
        s.Price, 
        s.stock,
        b.brand_name, 
        si.image_url
    FROM Product s
    LEFT JOIN Brand b ON s.brand_id = b.brand_id
    LEFT JOIN product_image si ON s.product_id = si.product_id AND si.sort_order = 1
    WHERE s.product_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

$product = $result->fetch_assoc();

if ($product['stock'] < $$quantity) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Not enough stock available. Stock: ' . $product['stock']]);
    exit();
}

$check_query = "SELECT cart_id, quantity FROM cart WHERE customer_id = ? AND product_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('ii', $customer_id, $product_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $cart_item = $check_result->fetch_assoc();
    $new_quantity = $cart_item['quantity'] + $quantity;
    
    if ($new_quantity > $product['stock']) {
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
            'message' => $product['name'] . ' quantity updated in cart!',
            'product_name' => $product['name'],
            'quantity' => $new_quantity,
            'cart_count' => $cart_count
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating cart.']);
    }
    $update_stmt->close();
} else {
    $insert_query = "INSERT INTO cart (customer_id, product_id, quantity) VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('iii', $customer_id, $product_id, $quantity);
    
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
            'message' => $product['name'] . ' added to cart!',
            'product_name' => $product['name'],
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
