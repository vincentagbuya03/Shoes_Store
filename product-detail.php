<?php
// product-details.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "vincentagbuya123";
$database   = "shoestore";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Load store settings early (before conn->close)
require_once 'inc/store_settings.php';

// Load the Gemini description helper from `inc/` if present; fall back to project root.
$helper_included = false;
if (file_exists(__DIR__ . '/inc/gemini_description.php')) {
    include_once __DIR__ . '/inc/gemini_description.php';
    $helper_included = true;
} elseif (file_exists(__DIR__ . '/gemini_description.php')) {
    include_once __DIR__ . '/gemini_description.php';
    $helper_included = true;
}
if (!$helper_included && function_exists('error_log')) {
    error_log('[gemini] helper not loaded: looked in /inc and project root');
}

// Get product ID
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($product_id <= 0) {
    die("Product not found.");
}

$product_stmt = $conn->prepare(
    "SELECT p.product_id, p.name, p.category, b.brand_name FROM product p LEFT JOIN brand b ON p.brand_id = b.brand_id WHERE p.product_id = ?"
);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();
if (!$product) {
    die("Product not found.");
}

// This now correctly fetches all 5 images per color
$images_stmt = $conn->prepare("
    SELECT pci.image_url, c.color_name
    FROM product_color_image pci
    LEFT JOIN color c ON pci.color_id = c.color_id
    WHERE pci.product_id = ?
    ORDER BY c.color_name ASC, pci.color_id ASC
");
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();

$images = [];
$colorToImages = []; // Store all images per color (up to 5 per color)
while ($row = $images_result->fetch_assoc()) {
    $images[] = $row['image_url'];
    
    if (!empty($row['color_name'])) {
        if (!isset($colorToImages[$row['color_name']])) {
            $colorToImages[$row['color_name']] = [];
        }
        $colorToImages[$row['color_name']][] = $row['image_url'];
    }
}

// Fetch variants
$variants_stmt = $conn->prepare("
    SELECT pv.variant_id, pv.price, pv.stock, s.size_name AS size, c.color_name AS color
    FROM product_variant pv
    LEFT JOIN size s ON pv.size_id = s.size_id
    LEFT JOIN color c ON pv.color_id = c.color_id
    WHERE pv.product_id = ?
    ORDER BY s.size_name ASC, c.color_name ASC
");
$variants_stmt->bind_param("i", $product_id);
$variants_stmt->execute();
$variants_result = $variants_stmt->get_result();

$variants = [];
$min_price = PHP_INT_MAX;
$max_price = 0;

while ($row = $variants_result->fetch_assoc()) {
    $variants[] = $row;

    if ($row['price'] < $min_price) $min_price = $row['price'];
    if ($row['price'] > $max_price) $max_price = $row['price'];
}

// Build variant map for JS
$variantMap = [];
foreach ($variants as $v) {
    $key = $v['size'] . '|' . $v['color'];
    $variantMap[$key] = $v;
}

// Count how many of this product are in the cart for this user
$cart_count = 0;
if (isset($_SESSION['customer_id'])) {
    $cart_conn = new mysqli($servername, $username, $password, $database);
    if (!$cart_conn->connect_error) {
        $cart_stmt = $cart_conn->prepare("SELECT SUM(c.quantity) as total FROM cart c INNER JOIN product_variant v ON c.variant_id = v.variant_id WHERE c.customer_id = ? AND v.product_id = ?");
        $cart_stmt->bind_param('ii', $_SESSION['customer_id'], $product_id);
        $cart_stmt->execute();
        $cart_result = $cart_stmt->get_result();
        if ($cart_result && ($row = $cart_result->fetch_assoc())) {
            $cart_count = (int)($row['total'] ?? 0);
        }
        $cart_stmt->close();
    }
    $cart_conn->close();
}

$final_description = '';
// Ensure product array contains a price for better prompts
if (!isset($product['price']) && isset($min_price) && $min_price !== PHP_INT_MAX) {
    $product['price'] = $min_price;
}

// Allow forcing regeneration from the UI for debugging: ?regen=1
$cache_file_path = __DIR__ . '/upload/product-descriptions/' . ($product['product_id'] ?? 'unknown') . '-long-en.txt';
if (isset($_GET['regen']) && $_GET['regen']) {
    @unlink($cache_file_path);
    if (function_exists('error_log')) error_log("[gemini_test] regen requested, deleted cache: {$cache_file_path}");
}

if (function_exists('generate_product_description')) {
    $final_description = (string) generate_product_description($product, 'en');
}

// If the LLM helper returned nothing or empty, use fallback (if available)
if ((trim($final_description) === '') && function_exists('generate_fallback_description')) {
    $final_description = generate_fallback_description($product, 'en');
}

// Admin debug: infer whether gemini or fallback was used (best-effort)
$display_used = 'unknown';
if (function_exists('generate_fallback_description')) {
    $fb = generate_fallback_description($product, 'en');
    if (trim((string)$final_description) === trim((string)$fb)) {
        $display_used = 'fallback';
    } else {
        $display_used = 'gemini';
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?> - <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php echo getStoreThemeCSS(); ?>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        :root {
            --primary: #0f0f0f;
            --secondary: #1a1a1a;
            --accent: #c9a87c;
            --accent-hover: #b8956a;
            --accent-light: rgba(201, 168, 124, 0.1);
            --accent-glow: rgba(201, 168, 124, 0.3);
            --white: #ffffff;
            --off-white: #fafafa;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --radius-sm: 8px;
            --radius: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --transition: all 0.2s ease;
            --transition-slow: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--white);
            color: var(--primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* ========== NAVIGATION ========== */
        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
        }

        .navbar-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 72px;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--primary);
        }

        .navbar-brand i {
            font-size: 1.5rem;
            color: var(--accent);
        }

        .navbar-brand span {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .navbar-nav {
            display: flex;
            list-style: none;
            gap: 0.5rem;
        }

        .navbar-nav a {
            text-decoration: none;
            color: var(--gray-600);
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.625rem 1rem;
            border-radius: var(--radius-sm);
            transition: var(--transition);
        }

        .navbar-nav a:hover {
            color: var(--primary);
            background: var(--gray-100);
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cart-btn {
            position: relative;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-100);
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            color: var(--primary);
            font-size: 1.25rem;
        }

        .cart-btn:hover {
            background: var(--gray-200);
            transform: translateY(-2px);
        }

        .cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--accent);
            color: var(--white);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            border: 2px solid var(--white);
        }

        .menu-toggle {
            display: none;
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--primary);
        }

        /* ========== MAIN CONTAINER ========== */
        .main-container {
            max-width: 1440px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* ========== BREADCRUMB ========== */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        .breadcrumb a {
            color: var(--gray-500);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb a:hover {
            color: var(--accent);
        }

        .breadcrumb i {
            font-size: 0.625rem;
            color: var(--gray-400);
        }

        .breadcrumb .current {
            color: var(--primary);
            font-weight: 500;
        }

        /* ========== PRODUCT SECTION ========== */
        .product-section {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 4rem;
            margin-bottom: 4rem;
        }

        /* ========== GALLERY ========== */
        .gallery {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .gallery-main {
            position: relative;
            aspect-ratio: 1;
            background: linear-gradient(145deg, var(--gray-50) 0%, var(--gray-100) 100%);
            border-radius: var(--radius-xl);
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .gallery-main img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 2rem;
            transition: var(--transition-slow);
        }

        .gallery-main:hover img {
            transform: scale(1.05);
        }

        .gallery-badge {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            background: var(--accent);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .gallery-zoom {
            position: absolute;
            bottom: 1.5rem;
            right: 1.5rem;
            width: 44px;
            height: 44px;
            background: var(--white);
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            color: var(--gray-600);
        }

        .gallery-zoom:hover {
            transform: scale(1.1);
            color: var(--accent);
        }

        .gallery-thumbs {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.75rem;
        }

        .gallery-thumb {
            aspect-ratio: 1;
            background: var(--gray-100);
            border-radius: var(--radius);
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: var(--transition);
        }

        .gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-thumb:hover {
            border-color: var(--gray-300);
        }

        .gallery-thumb.active {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        /* ========== PRODUCT INFO ========== */
        .product-info {
            padding-top: 1rem;
        }

        .product-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent-light);
            color: var(--accent);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .product-title {
            font-size: 2.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .product-rating {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .rating-stars {
            display: flex;
            gap: 0.25rem;
            color: var(--accent);
        }

        .rating-text {
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        .product-price-box {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1.25rem;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
        }

        .price-current {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--primary);
        }

        .price-original {
            font-size: 1.25rem;
            color: var(--gray-400);
            text-decoration: line-through;
        }

        .price-discount {
            background: var(--success);
            color: var(--white);
            padding: 0.375rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .cart-notice {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            background: linear-gradient(135deg, var(--accent-light) 0%, rgba(201, 168, 124, 0.05) 100%);
            border: 1px solid var(--accent);
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
        }

        .cart-notice i {
            color: var(--accent);
            font-size: 1.25rem;
        }

        .cart-notice span {
            font-weight: 600;
            color: var(--accent-hover);
        }

        /* ========== DESCRIPTION SECTION ========== */
        .description-box {
            margin-bottom: 2rem;
        }

        .description-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .description-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 700;
            color: var(--primary);
        }

        .description-title i {
            color: var(--accent);
        }

        .btn-ai-generate {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--white);
            border: none;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-ai-generate:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-ai-generate:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-ai-generate.loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .description-content {
            font-size: 0.95rem;
            line-height: 1.8;
            color: var(--gray-600);
            padding: 1.25rem;
            background: var(--gray-50);
            border-radius: var(--radius);
            border: 1px solid var(--gray-200);
        }

        /* ========== VARIANT SELECTORS ========== */
        .variant-selectors {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .selector-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .selector-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--primary);
        }

        .selector-label i {
            color: var(--accent);
            font-size: 0.85rem;
        }

        .selector-label span {
            font-weight: 400;
            color: var(--gray-500);
        }

        .size-options {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .size-btn {
            min-width: 56px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--white);
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary);
            cursor: pointer;
            transition: var(--transition);
        }

        .size-btn:hover:not(:disabled) {
            border-color: var(--accent);
            background: var(--accent-light);
        }

        .size-btn.selected {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--white);
        }

        .size-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: var(--gray-100);
        }

        .color-options {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .color-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .color-btn {
            width: 48px;
            height: 48px;
            border: 3px solid var(--gray-200);
            border-radius: var(--radius);
            cursor: pointer;
            transition: var(--transition);
            position: relative;
        }

        .color-btn:hover:not(:disabled) {
            transform: scale(1.1);
            border-color: var(--gray-400);
        }

        .color-btn.selected {
            border-color: var(--primary);
            transform: scale(1.1);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        .color-btn.selected::after {
            content: '✓';
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            color: var(--white);
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        }

        .color-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .color-name {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--gray-500);
        }

        /* ========== PURCHASE BOX ========== */
        .purchase-box {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: var(--transition-slow);
        }

        .purchase-box.show {
            opacity: 1;
            max-height: 400px;
        }

        .purchase-price {
            font-size: 2rem;
            font-weight: 800;
            color: var(--accent);
        }

        .purchase-stock {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .purchase-stock.in-stock {
            color: var(--success);
        }

        .purchase-stock.low-stock {
            color: var(--warning);
        }

        .purchase-stock.out-of-stock {
            color: var(--danger);
        }

        .purchase-stock i {
            font-size: 1rem;
        }

        .quantity-row {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .quantity-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-700);
        }

        .quantity-control {
            display: flex;
            align-items: center;
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            overflow: hidden;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.25rem;
            color: var(--gray-600);
            transition: var(--transition);
        }

        .qty-btn:hover {
            background: var(--gray-100);
            color: var(--accent);
        }

        .qty-input {
            width: 50px;
            height: 40px;
            border: none;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
            background: transparent;
        }

        .qty-input:focus {
            outline: none;
        }

        .btn-add-cart {
            width: 100%;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-hover) 100%);
            color: var(--white);
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 14px var(--accent-glow);
        }

        .btn-add-cart:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px var(--accent-glow);
        }

        .btn-add-cart:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-add-cart:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-add-cart i {
            font-size: 1.1rem;
        }

        .purchase-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn-wishlist, .btn-share {
            flex: 1;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-600);
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-wishlist:hover, .btn-share:hover {
            border-color: var(--gray-300);
            color: var(--primary);
        }

        /* ========== FEATURES ========== */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin: 4rem 0;
            padding: 2.5rem;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-xl);
        }

        .feature-card {
            text-align: center;
            padding: 1.5rem;
        }

        .feature-icon {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--accent-light);
            border-radius: var(--radius);
            margin: 0 auto 1rem;
            color: var(--accent);
            font-size: 1.5rem;
        }

        .feature-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.375rem;
        }

        .feature-text {
            font-size: 0.8rem;
            color: var(--gray-500);
            line-height: 1.5;
        }

        /* ========== NEWSLETTER ========== */
        .newsletter-section {
            padding: 4rem;
            background: linear-gradient(135deg, var(--primary) 0%, #2a2a2a 100%);
            border-radius: var(--radius-xl);
            text-align: center;
            margin: 4rem 0;
            position: relative;
            overflow: hidden;
        }

        .newsletter-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
            pointer-events: none;
        }

        .newsletter-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 0.5rem;
            position: relative;
        }

        .newsletter-text {
            font-size: 1rem;
            color: var(--gray-400);
            margin-bottom: 2rem;
            position: relative;
        }

        .newsletter-form {
            display: flex;
            gap: 0.75rem;
            max-width: 420px;
            margin: 0 auto;
            position: relative;
        }

        .newsletter-input {
            flex: 1;
            height: 52px;
            padding: 0 1.25rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--radius);
            color: var(--white);
            font-size: 0.95rem;
        }

        .newsletter-input::placeholder {
            color: var(--gray-400);
        }

        .newsletter-input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255, 255, 255, 0.15);
        }

        .newsletter-btn {
            height: 52px;
            padding: 0 2rem;
            background: var(--accent);
            border: none;
            border-radius: var(--radius);
            color: var(--white);
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
        }

        .newsletter-btn:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        /* ========== FOOTER ========== */
        .footer {
            background: var(--primary);
            color: var(--white);
            padding: 4rem 2rem 2rem;
            margin-top: 4rem;
        }

        .footer-container {
            max-width: 1440px;
            margin: 0 auto;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.5fr repeat(4, 1fr);
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-brand {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--white);
        }

        .footer-logo i {
            color: var(--accent);
        }

        .footer-tagline {
            font-size: 0.9rem;
            color: var(--gray-400);
            line-height: 1.6;
        }

        .footer-social {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }

        .footer-social a {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--secondary);
            border-radius: var(--radius-sm);
            color: var(--gray-400);
            transition: var(--transition);
        }

        .footer-social a:hover {
            background: var(--accent);
            color: var(--white);
        }

        .footer-column h4 {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 1.25rem;
        }

        .footer-column ul {
            list-style: none;
        }

        .footer-column li {
            margin-bottom: 0.75rem;
        }

        .footer-column a {
            color: var(--gray-400);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .footer-column a:hover {
            color: var(--accent);
        }

        .footer-bottom {
            padding-top: 2rem;
            border-top: 1px solid var(--secondary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-copyright {
            font-size: 0.85rem;
            color: var(--gray-500);
        }

        .footer-payments {
            display: flex;
            gap: 0.75rem;
        }

        .footer-payments i {
            font-size: 1.75rem;
            color: var(--gray-500);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .product-section {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .gallery {
                position: static;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .navbar-container {
                padding: 0 1rem;
            }

            .navbar-nav {
                display: none;
            }

            .menu-toggle {
                display: flex;
            }

            .main-container {
                padding: 1.5rem 1rem;
            }

            .product-title {
                font-size: 1.75rem;
            }

            .price-current {
                font-size: 1.75rem;
            }

            .gallery-thumbs {
                grid-template-columns: repeat(4, 1fr);
            }

            .features-grid {
                grid-template-columns: 1fr 1fr;
                padding: 1.5rem;
            }

            .newsletter-section {
                padding: 2.5rem 1.5rem;
            }

            .newsletter-form {
                flex-direction: column;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .footer-bottom {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .description-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .btn-ai-generate {
                width: 100%;
                justify-content: center;
            }

            .size-options {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
            }

            .size-btn {
                min-width: auto;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- NAVIGATION -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="user-interface.php" class="navbar-brand">
                <i class="fas fa-shoe-prints"></i>
                <span><?php echo htmlspecialchars($store_settings['store_name']); ?></span>
            </a>

            <ul class="navbar-nav">
                <li><a href="index.php">Home</a></li>
                <li><a href="shoes.php">Shop</a></li>
                <li><a href="brand.php">Brands</a></li>
                <li><a href="best-seller.php">Best Sellers</a></li>
            </ul>

            <div class="navbar-actions">
                <a href="cart.php" class="cart-btn">
                    <i class="fas fa-shopping-bag"></i>
                    <?php
                    $cart_product_count = 0;
                    if (isset($_SESSION['customer_id'])) {
                        $cart_conn = new mysqli($servername, $username, $password, $database);
                        if (!$cart_conn->connect_error) {
                            $cart_stmt = $cart_conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?");
                            $cart_stmt->bind_param('i', $_SESSION['customer_id']);
                            $cart_stmt->execute();
                            $cart_result = $cart_stmt->get_result();
                            if ($cart_result && ($row = $cart_result->fetch_assoc())) {
                                $cart_product_count = (int)($row['total'] ?? 0);
                            }
                            $cart_stmt->close();
                        }
                        $cart_conn->close();
                    }
                    if ($cart_product_count > 0) {
                        echo '<span class="cart-badge" id="cartBadge">' . $cart_product_count . '</span>';
                    }
                    ?>
                </a>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <i class="fas fa-chevron-right"></i>
            <a href="shoes.php">Shop</a>
            <i class="fas fa-chevron-right"></i>
            <span class="current"><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <!-- Product Section -->
        <section class="product-section">
            <!-- Gallery -->
            <div class="gallery">
                <?php
                    $firstColor = isset($_GET['color']) && isset($colorToImages[$_GET['color']]) ? $_GET['color'] : array_key_first($colorToImages);
                    $defaultImages = $firstColor ? $colorToImages[$firstColor] : [];
                ?>
                <div class="gallery-main">
                    <span class="gallery-badge">New Arrival</span>
                    <img id="mainImage" 
                         src="<?php echo htmlspecialchars($defaultImages[0] ?? 'upload/product-image/placeholder.png'); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <button class="gallery-zoom" title="Zoom">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
                <div class="gallery-thumbs" id="thumbnailList">
                    <?php foreach ($defaultImages as $index => $img): ?>
                        <div class="gallery-thumb <?php echo $index === 0 ? 'active' : ''; ?>"
                             data-image="<?php echo htmlspecialchars($img); ?>">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <span class="product-brand">
                    <i class="fas fa-award"></i>
                    <?php echo htmlspecialchars($product['brand_name'] ?? 'Premium Brand'); ?>
                </span>

                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                <div class="product-rating">
                    <div class="rating-stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <span class="rating-text">4.5 (128 reviews)</span>
                </div>

                <div class="product-price-box">
                    <span class="price-current">
                        ₱<?php echo number_format((float)$min_price, 2); ?>
                    </span>
                    <?php if ($min_price != $max_price): ?>
                        <span class="price-original">₱<?php echo number_format((float)$max_price + 500, 2); ?></span>
                        <span class="price-discount">Save 15%</span>
                    <?php endif; ?>
                </div>

                <?php if ($cart_count > 0): ?>
                    <div class="cart-notice">
                        <i class="fas fa-shopping-cart"></i>
                        <span><?php echo $cart_count; ?> item(s) already in your cart</span>
                    </div>
                <?php endif; ?>

                <!-- Description -->
                <div class="description-box">
                    <div class="description-header">
                        <span class="description-title">
                            <i class="fas fa-align-left"></i>
                            Product Description
                        </span>
                        <button class="btn-ai-generate" id="generateDescBtn" title="Generate description with AI">
                            <i class="fas fa-wand-magic-sparkles"></i>
                            AI Generate
                        </button>
                    </div>
                    <p class="description-content" id="productDesc">
                        <?php 
                            $display_desc = is_string($final_description) ? trim($final_description) : '';
                            $display_desc = str_ireplace('N/A', '', $display_desc);
                            $display_desc = trim($display_desc);
                            if ($display_desc === '') {
                                if (function_exists('generate_fallback_description')) {
                                    $display_desc = generate_fallback_description($product, 'en');
                                } else {
                                    $display_desc = 'Experience unparalleled comfort and style with these premium shoes. Crafted with attention to detail, featuring high-quality materials and expert craftsmanship for lasting durability.';
                                }
                            }
                            echo nl2br(htmlspecialchars($display_desc));
                        ?>
                    </p>
                </div>

                <?php if (count($variants) > 0): ?>
                    <!-- Variant Selectors -->
                    <div class="variant-selectors">
                        <?php
                        $sizes = [];
                        $colors = [];
                        foreach ($variants as $v) {
                            $sizes[$v['size']] = true;
                            $colors[$v['color']] = true;
                        }
                        $sizes = array_keys($sizes);
                        $colors = array_keys($colors);
                        ?>

                        <!-- Size Selector -->
                        <div class="selector-group">
                            <label class="selector-label">
                                <i class="fas fa-ruler-horizontal"></i>
                                Select Size
                                <span>— Size Guide</span>
                            </label>
                            <div class="size-options" id="sizeOptions">
                                <?php foreach ($sizes as $size): ?>
                                    <button class="size-btn" data-size="<?php echo htmlspecialchars($size); ?>">
                                        <?php echo htmlspecialchars($size); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Color Selector -->
                        <div class="selector-group">
                            <label class="selector-label">
                                <i class="fas fa-palette"></i>
                                Select Color
                            </label>
                            <div class="color-options" id="colorOptions">
                                <?php foreach ($colors as $color): ?>
                                    <div class="color-option">
                                        <button class="color-btn"
                                                data-color="<?php echo htmlspecialchars($color); ?>"
                                                title="<?php echo htmlspecialchars($color); ?>"
                                                style="background-color: <?php echo htmlspecialchars($color); ?>;">
                                        </button>
                                        <span class="color-name"><?php echo htmlspecialchars($color); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Box -->
                    <div class="purchase-box" id="variantInfo">
                        <div class="purchase-price" id="variantPrice">₱0.00</div>
                        <div class="purchase-stock" id="variantStock">
                            <i class="fas fa-circle"></i>
                            Select size and color
                        </div>

                        <div class="quantity-row">
                            <span class="quantity-label">Quantity:</span>
                            <div class="quantity-control">
                                <button type="button" id="qty-decrease" class="qty-btn">−</button>
                                <input type="number" id="quantity" class="qty-input" value="1" min="1" max="10">
                                <button type="button" id="qty-increase" class="qty-btn">+</button>
                            </div>
                        </div>

                        <button class="btn-add-cart" id="addToCartBtn" data-variant-id="">
                            <i class="fas fa-shopping-bag"></i>
                            Add to Cart
                        </button>

                        <div class="purchase-actions">
                            <button class="btn-wishlist">
                                <i class="far fa-heart"></i>
                                Wishlist
                            </button>
                            <button class="btn-share">
                                <i class="fas fa-share-alt"></i>
                                Share
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Features Grid -->
        <section class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-truck-fast"></i>
                </div>
                <h3 class="feature-title">Free Shipping</h3>
                <p class="feature-text">Free delivery on orders over ₱2,500</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-rotate-left"></i>
                </div>
                <h3 class="feature-title">Easy Returns</h3>
                <p class="feature-text">30-day hassle-free return policy</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-check"></i>
                </div>
                <h3 class="feature-title">Secure Payment</h3>
                <p class="feature-text">100% secure payment methods</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-text">Dedicated customer service team</p>
            </div>
        </section>

        <!-- Newsletter -->
        <section class="newsletter-section">
            <h2 class="newsletter-title">Stay in the Loop</h2>
            <p class="newsletter-text">Subscribe for exclusive offers and new arrivals</p>
            <form class="newsletter-form" onsubmit="return false;">
                <input type="email" class="newsletter-input" placeholder="Enter your email address" required>
                <button type="submit" class="newsletter-btn">Subscribe</button>
            </form>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <i class="fas fa-shoe-prints"></i>
                        <?php echo htmlspecialchars($store_settings['store_name']); ?>
                    </div>
                    <p class="footer-tagline">Premium footwear for the modern lifestyle. Quality, comfort, and style combined.</p>
                    <div class="footer-social">
                        <?php if (!empty($store_settings['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_facebook']); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($store_settings['social_instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_instagram']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($store_settings['social_twitter'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_twitter']); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($store_settings['social_tiktok'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_tiktok']); ?>" target="_blank"><i class="fab fa-tiktok"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Shop</h4>
                    <ul>
                        <li><a href="shoes.php">All Shoes</a></li>
                        <li><a href="brand.php">Brands</a></li>
                        <li><a href="best-seller.php">Best Sellers</a></li>
                        <li><a href="#">New Arrivals</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Shipping Info</a></li>
                        <li><a href="#">Size Guide</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Press</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">Returns Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="footer-copyright">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($store_settings['store_name']); ?>. All rights reserved.</p>
                <div class="footer-payments">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-paypal"></i>
                    <i class="fab fa-google-pay"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- JAVASCRIPT -->
    <script>
        const variantMap = <?php echo json_encode($variantMap); ?>;
        const colorToImages = <?php echo json_encode($colorToImages); ?>;
        let selectedSize = null;
        let selectedColor = null;

        const urlParams = new URLSearchParams(window.location.search);
        const initialColor = urlParams.get('color');

        function updateVariantInfo() {
            if (!selectedSize || !selectedColor) {
                document.getElementById('variantInfo').classList.remove('show');
                return;
            }

            const key = selectedSize + "|" + selectedColor;
            const variant = variantMap[key];

            if (!variant) {
                document.getElementById('variantInfo').classList.remove('show');
                return;
            }

            // Price
            document.getElementById('variantPrice').textContent = '₱' + parseFloat(variant.price).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Stock state
            const stock = parseInt(variant.stock) || 0;
            const stockEl = document.getElementById('variantStock');

            if (stock > 10) {
                stockEl.innerHTML = '<i class="fas fa-check-circle"></i> In Stock';
                stockEl.className = "purchase-stock in-stock";
            } else if (stock > 0) {
                stockEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Only ' + stock + ' left';
                stockEl.className = "purchase-stock low-stock";
            } else {
                stockEl.innerHTML = '<i class="fas fa-times-circle"></i> Out of Stock';
                stockEl.className = "purchase-stock out-of-stock";
            }

            const addBtn = document.getElementById('addToCartBtn');
            addBtn.dataset.variantId = variant.variant_id;
            addBtn.disabled = stock === 0;

            document.getElementById('variantInfo').classList.add('show');
        }

        // Size selector
        document.querySelectorAll('.size-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                selectedSize = this.dataset.size;
                document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');

                document.querySelectorAll('.color-btn').forEach(b => {
                    const color = b.dataset.color;
                    b.disabled = !(selectedSize + "|" + color in variantMap);
                });

                updateVariantInfo();
            });
        });

        // Color selector
        document.querySelectorAll('.color-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                selectedColor = this.dataset.color;
                document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');

                // Update gallery images
                const galleryImages = colorToImages[selectedColor] || [];
                const mainImage = document.getElementById('mainImage');
                const thumbnailList = document.getElementById('thumbnailList');

                if (galleryImages.length > 0) {
                    mainImage.src = galleryImages[0];
                } else {
                    mainImage.src = 'upload/product-image/placeholder.png';
                }

                // Update thumbnails
                let thumbsHtml = '';
                galleryImages.forEach((img, idx) => {
                    thumbsHtml += `<div class="gallery-thumb ${idx === 0 ? 'active' : ''}" data-image="${img}">
                        <img src="${img}" alt="Thumbnail ${idx + 1}">
                    </div>`;
                });
                thumbnailList.innerHTML = thumbsHtml;

                // Re-attach click events to thumbnails
                thumbnailList.querySelectorAll('.gallery-thumb').forEach(thumb => {
                    thumb.addEventListener('click', function () {
                        thumbnailList.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                        mainImage.src = this.dataset.image;
                    });
                });

                updateVariantInfo();
            });
        });

        // Thumbnail click
        document.querySelectorAll('.gallery-thumb').forEach(thumb => {
            thumb.addEventListener('click', function () {
                document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('mainImage').src = this.dataset.image;
            });
        });

        // Quantity controls
        const qtyInput = document.getElementById('quantity');
        const qtyDec = document.getElementById('qty-decrease');
        const qtyInc = document.getElementById('qty-increase');

        function clampQuantity() {
            if (!qtyInput) return 1;
            let v = parseInt(qtyInput.value) || 1;
            const min = parseInt(qtyInput.getAttribute('min')) || 1;
            const max = parseInt(qtyInput.getAttribute('max')) || 10;
            if (v < min) v = min;
            if (v > max) v = max;
            qtyInput.value = v;
            return v;
        }

        qtyDec?.addEventListener('click', () => {
            const cur = clampQuantity();
            qtyInput.value = Math.max(cur - 1, 1);
        });

        qtyInc?.addEventListener('click', () => {
            const cur = clampQuantity();
            qtyInput.value = Math.min(cur + 1, 10);
        });

        qtyInput?.addEventListener('change', clampQuantity);

        // Add to cart
        document.getElementById('addToCartBtn')?.addEventListener('click', async function (e) {
            e.preventDefault();
            const variantId = this.dataset.variantId;
            const quantity = parseInt(document.getElementById('quantity').value) || 1;

            if (!variantId) {
                alert('Please select size and color');
                return;
            }

            const btn = this;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            try {
                const res = await fetch("partials/add-to-cart.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `variant_id=${encodeURIComponent(variantId)}&quantity=${encodeURIComponent(quantity)}`
                });

                const data = await res.json();

                if (data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                    setTimeout(() => { btn.innerHTML = originalText; }, 2000);
                    
                    if (data.cart_count !== undefined) {
                        const badge = document.getElementById('cartBadge');
                        if (badge) {
                            badge.textContent = data.cart_count;
                        } else {
                            const cartBtn = document.querySelector('.cart-btn');
                            if (cartBtn) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'cart-badge';
                                newBadge.id = 'cartBadge';
                                newBadge.textContent = data.cart_count;
                                cartBtn.appendChild(newBadge);
                            }
                        }
                    }
                } else {
                    alert(data.message || 'Failed to add to cart');
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                console.error(err);
                alert('Failed to add to cart');
                btn.innerHTML = originalText;
            } finally {
                btn.disabled = false;
            }
        });

        // AI Description Generator
        document.getElementById('generateDescBtn')?.addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.innerHTML;
            const descEl = document.getElementById('productDesc');
            
            btn.disabled = true;
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner"></i> Generating...';
            
            try {
                const response = await fetch('api/gemini_generate_description.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        product_id: <?php echo $product_id; ?>,
                        product_name: <?php echo json_encode($product['name']); ?>,
                        brand_name: <?php echo json_encode($product['brand_name'] ?? ''); ?>,
                        category: <?php echo json_encode($product['category'] ?? ''); ?>
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.description) {
                    descEl.innerHTML = data.description.replace(/\n/g, '<br>');
                    btn.innerHTML = '<i class="fas fa-check"></i> Done!';
                    setTimeout(() => { btn.innerHTML = originalText; }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to generate description');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error: ' + error.message);
                btn.innerHTML = originalText;
            } finally {
                btn.disabled = false;
                btn.classList.remove('loading');
            }
        });

        // Mobile menu toggle
        document.getElementById('menuToggle')?.addEventListener('click', function () {
            document.querySelector('.navbar-nav')?.classList.toggle('show');
        });

        // Initialize: click first color on load
        document.addEventListener('DOMContentLoaded', () => {
            if (initialColor) {
                const colorButton = document.querySelector(`.color-btn[data-color="${CSS.escape(initialColor)}"]`);
                if (colorButton && !colorButton.disabled) {
                    colorButton.click();
                    return;
                }
            }
            const firstColorButton = document.querySelector('.color-btn:not(:disabled)');
            if (firstColorButton) {
                firstColorButton.click();
            }
        });
    </script>
</body>
</html>
