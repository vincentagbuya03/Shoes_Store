<?php
header('Content-Type: application/json');

session_start();

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "vincentagbuya123";
$database   = "shoestore";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$conn->set_charset("utf8mb4");

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['product_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$product_id = (int)$input['product_id'];
$product_name = $input['product_name'] ?? 'Product';
$brand_name = $input['brand_name'] ?? '';
$category = $input['category'] ?? '';

// Load Gemini helper
$helper_included = false;
if (file_exists(__DIR__ . '/../inc/gemini_description.php')) {
    include_once __DIR__ . '/../inc/gemini_description.php';
    $helper_included = true;
} elseif (file_exists(__DIR__ . '/../gemini_description.php')) {
    include_once __DIR__ . '/../gemini_description.php';
    $helper_included = true;
}

if (!$helper_included || !function_exists('generate_product_description')) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gemini service not available']);
    exit;
}

try {
    // Fetch product details from database
    $stmt = $conn->prepare("
        SELECT p.product_id, p.name, p.category, b.brand_name, COUNT(oi.id) as total_sold
        FROM product p
        LEFT JOIN brand b ON p.brand_id = b.brand_id
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        WHERE p.product_id = ?
        GROUP BY p.product_id
    ");
    
    if (!$stmt) {
        throw new Exception('Database query failed: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || $result->num_rows === 0) {
        throw new Exception('Product not found');
    }
    
    $product = $result->fetch_assoc();
    $stmt->close();
    
    // Generate description using Gemini
    $description = generate_product_description($product, 'en');
    
    if (!$description || trim($description) === '') {
        throw new Exception('Failed to generate description');
    }
    
    // Clean up the description
    $description = trim($description);
    $description = str_ireplace('N/A', '', $description);
    $description = trim($description);
    
    if ($description === '') {
        throw new Exception('Generated description is empty');
    }
    
    // Save to cache file
    $cache_dir = __DIR__ . '/../upload/product-descriptions/';
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    $cache_file = $cache_dir . $product_id . '-long-en.txt';
    file_put_contents($cache_file, $description);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'description' => htmlspecialchars($description),
        'message' => 'Description generated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>
