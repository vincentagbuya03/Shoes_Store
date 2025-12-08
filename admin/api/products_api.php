<?php

header('Content-Type: application/json; charset=utf-8');

// Basic runtime safeguards: return JSON on uncaught exceptions / fatal errors
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Determine whether the request is local (development). Only expose internal errors when local.
$isLocalRequest = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
set_exception_handler(function($ex) use ($isLocalRequest){
    http_response_code(500);
    error_log('Uncaught exception in products_api: ' . $ex->getMessage());
    $msg = $isLocalRequest ? ('Internal server error: ' . $ex->getMessage()) : 'Internal server error';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
});

register_shutdown_function(function(){
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // fatal error — log and return JSON (if nothing sent yet)
        http_response_code(500);
        error_log('Shutdown fatal error in products_api: ' . ($err['message'] ?? ''));
        $msg = $isLocalRequest ? ('Internal server error (fatal): ' . ($err['message'] ?? '')) : 'Internal server error (fatal)';
        // try to emit JSON safely
        echo json_encode(['success' => false, 'message' => $msg]);
        // no exit here (shutdown)
    }
});

// Ensure DB connection file exists before requiring it (avoid a fatal require_once)
$dbConnFile = __DIR__ . '/../db_connection.php';
if (!is_file($dbConnFile)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Missing DB connection file']);
    exit;
}
require_once $dbConnFile;

session_start();


if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? ($_GET['action'] ?? 'list');

function jsonError($msg) {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Get all colors list
if ($action === 'colors') {
    $sql = "SELECT color_id, color_name FROM color ORDER BY color_name ASC";
    $res = $conn->query($sql);
    $colors = [];
    while ($row = $res->fetch_assoc()) {
        $colors[] = $row;
    }
    echo json_encode(['success' => true, 'colors' => $colors]);
    exit;
}

// Get all sizes list
if ($action === 'sizes') {
    $sql = "SELECT size_id, size_name FROM size ORDER BY size_name ASC";
    $res = $conn->query($sql);
    $sizes = [];
    while ($row = $res->fetch_assoc()) {
        $sizes[] = $row;
    }
    echo json_encode(['success' => true, 'sizes' => $sizes]);
    exit;
}

// Get color variants for a product (color + price + stock + images)
if ($action === 'get_color_variants') {
    $productId = $_GET['product_id'] ?? '';
    if (!$productId) jsonError('Missing product_id');
    
    // Get unique colors from both product_variant and product_color_image
    $colorVariants = [];
    
    // First, get colors with prices from product_variant
    $sql = "SELECT pv.color_id, c.color_name, 
                   MIN(pv.price) as price, 
                   SUM(pv.stock) as stock
            FROM product_variant pv
            JOIN color c ON pv.color_id = c.color_id
            WHERE pv.product_id = ?
            GROUP BY pv.color_id, c.color_name
            ORDER BY c.color_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    while ($row = $res->fetch_assoc()) {
        $colorVariants[$row['color_id']] = [
            'color_id' => $row['color_id'],
            'color_name' => $row['color_name'],
            'price' => $row['price'],
            'stock' => $row['stock'],
            'images' => []
        ];
    }
    $stmt->close();
    
    // Also get colors from product_color_image that may not have variants yet
    $imgColorSql = "SELECT DISTINCT pci.color_id, c.color_name
                    FROM product_color_image pci
                    JOIN color c ON pci.color_id = c.color_id
                    WHERE pci.product_id = ?";
    $imgColorStmt = $conn->prepare($imgColorSql);
    $imgColorStmt->bind_param('i', $productId);
    $imgColorStmt->execute();
    $imgColorRes = $imgColorStmt->get_result();
    
    while ($row = $imgColorRes->fetch_assoc()) {
        if (!isset($colorVariants[$row['color_id']])) {
            $colorVariants[$row['color_id']] = [
                'color_id' => $row['color_id'],
                'color_name' => $row['color_name'],
                'price' => null,
                'stock' => 0,
                'images' => []
            ];
        }
    }
    $imgColorStmt->close();
    
    // Get images for each color (excluding empty placeholders)
    $imgSql = "SELECT pci.color_id, pci.color_image_id, pci.image_url, pci.sort_order
               FROM product_color_image pci
               WHERE pci.product_id = ? AND pci.image_url != ''
               ORDER BY pci.color_id, pci.sort_order";
    $imgStmt = $conn->prepare($imgSql);
    $imgStmt->bind_param('i', $productId);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result();
    
    while ($row = $imgRes->fetch_assoc()) {
        $cid = $row['color_id'];
        $imgUrl = str_replace('\\', '/', $row['image_url']);
        if (isset($colorVariants[$cid])) {
            $colorVariants[$cid]['images'][] = [
                'color_image_id' => $row['color_image_id'],
                'image_url' => $imgUrl,
                'sort_order' => $row['sort_order']
            ];
        }
    }
    $imgStmt->close();
    
    echo json_encode(['success' => true, 'color_variants' => array_values($colorVariants)]);
    exit;
}

// Save color variant (price/stock for a color)
if ($action === 'save_color_variant') {
    $productId = $_POST['product_id'] ?? '';
    $colorId = $_POST['color_id'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $sizeId = $_POST['size_id'] ?? 1; // Default size
    
    if (!$productId || !$colorId) jsonError('Missing product_id or color_id');
    
    // Check if variant exists
    $checkSql = "SELECT variant_id FROM product_variant WHERE product_id = ? AND color_id = ? AND size_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('iii', $productId, $colorId, $sizeId);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    $existing = $checkRes->fetch_assoc();
    $checkStmt->close();
    
    if ($existing) {
        // Update existing variant
        $updateSql = "UPDATE product_variant SET price = ?, stock = ? WHERE variant_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('dii', $price, $stock, $existing['variant_id']);
        if ($updateStmt->execute()) {
            echo json_encode(['success' => true, 'variant_id' => $existing['variant_id']]);
        } else {
            jsonError('Failed to update variant');
        }
    } else {
        // Insert new variant
        $insertSql = "INSERT INTO product_variant (product_id, color_id, size_id, price, stock) VALUES (?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param('iiidi', $productId, $colorId, $sizeId, $price, $stock);
        if ($insertStmt->execute()) {
            echo json_encode(['success' => true, 'variant_id' => $conn->insert_id]);
        } else {
            jsonError('Failed to create variant');
        }
    }
    exit;
}

// Register a color for a product (ensures entry exists in product_color_image)
if ($action === 'register_color') {
    $productId = $_POST['product_id'] ?? '';
    $colorId = $_POST['color_id'] ?? '';
    
    if (!$productId || !$colorId) jsonError('Missing product_id or color_id');
    
    // Check if color already has images for this product
    $checkSql = "SELECT color_image_id FROM product_color_image WHERE product_id = ? AND color_id = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param('ii', $productId, $colorId);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();
    $existing = $checkRes->fetch_assoc();
    $checkStmt->close();
    
    if (!$existing) {
        // Insert a placeholder entry (with empty image_url that can be updated later)
        // This ensures the color is registered for the product
        $insertSql = "INSERT INTO product_color_image (product_id, color_id, image_url, sort_order) VALUES (?, ?, '', 0)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param('ii', $productId, $colorId);
        $insertStmt->execute();
        $insertStmt->close();
    }
    
    echo json_encode(['success' => true]);
    exit;
}

// Delete color variant (removes variant and images for that color)
if ($action === 'delete_color_variant') {
    $productId = $_POST['product_id'] ?? '';
    $colorId = $_POST['color_id'] ?? '';
    
    if (!$productId || !$colorId) jsonError('Missing product_id or color_id');
    
    // Delete variants
    $delVariant = $conn->prepare("DELETE FROM product_variant WHERE product_id = ? AND color_id = ?");
    $delVariant->bind_param('ii', $productId, $colorId);
    $delVariant->execute();
    $delVariant->close();
    
    // Delete images
    $delImages = $conn->prepare("DELETE FROM product_color_image WHERE product_id = ? AND color_id = ?");
    $delImages->bind_param('ii', $productId, $colorId);
    $delImages->execute();
    $delImages->close();
    
    echo json_encode(['success' => true]);
    exit;
}

// Get color images for a product
if ($action === 'get_color_images') {
    $productId = $_GET['product_id'] ?? '';
    if (!$productId) jsonError('Missing product_id');
    
    $sql = "SELECT pci.color_image_id, pci.color_id, c.color_name, pci.image_url, pci.sort_order
            FROM product_color_image pci
            JOIN color c ON pci.color_id = c.color_id
            WHERE pci.product_id = ?
            ORDER BY pci.color_id, pci.sort_order";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $colorImages = [];
    while ($row = $res->fetch_assoc()) {
        $cid = $row['color_id'];
        if (!isset($colorImages[$cid])) {
            $colorImages[$cid] = [
                'color_id' => $cid,
                'color_name' => $row['color_name'],
                'images' => []
            ];
        }
        $imgUrl = str_replace('\\', '/', $row['image_url']);
        $colorImages[$cid]['images'][] = [
            'color_image_id' => $row['color_image_id'],
            'image_url' => $imgUrl,
            'sort_order' => $row['sort_order']
        ];
    }
    
    echo json_encode(['success' => true, 'color_images' => array_values($colorImages)]);
    exit;
}

// Upload image for a specific color
if ($action === 'upload_color_image') {
    $productId = $_POST['product_id'] ?? '';
    $colorId = $_POST['color_id'] ?? '';
    
    if (!$productId || !$colorId) jsonError('Missing product_id or color_id');
    
    if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
        jsonError('No image uploaded');
    }
    
    // Auto-calculate sort_order: get max sort_order for this product/color and add 1
    $stmtMax = $conn->prepare("SELECT COALESCE(MAX(sort_order), 0) as max_sort FROM product_color_image WHERE product_id = ? AND color_id = ?");
    $stmtMax->bind_param('ii', $productId, $colorId);
    $stmtMax->execute();
    $maxResult = $stmtMax->get_result()->fetch_assoc();
    $sortOrder = ($maxResult['max_sort'] ?? 0) + 1;
    
    $uploadDir = __DIR__ . '/../../upload/product-image/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    
    $orig = basename($_FILES['image']['name']);
    $ext = pathinfo($orig, PATHINFO_EXTENSION);
    $safe = preg_replace('/[^a-zA-Z0-9-_\.]/','_', pathinfo($orig, PATHINFO_FILENAME));
    $filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safe . ($ext ? '.' . $ext : '');
    $dest = $uploadDir . $filename;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        $publicPath = 'upload/product-image/' . $filename;
        $stmt = $conn->prepare("INSERT INTO product_color_image (product_id, color_id, image_url, sort_order) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iisi', $productId, $colorId, $publicPath, $sortOrder);
        if ($stmt->execute()) {
            $insertId = $conn->insert_id;
            echo json_encode(['success' => true, 'color_image_id' => $insertId, 'image_url' => $publicPath, 'sort_order' => $sortOrder]);
        } else {
            jsonError('Failed to save image record');
        }
    } else {
        jsonError('Failed to upload image');
    }
    exit;
}

// Delete a color image
if ($action === 'delete_color_image') {
    $imageId = $_POST['color_image_id'] ?? '';
    if (!$imageId) jsonError('Missing color_image_id');
    
    // Get the file path first
    $stmt = $conn->prepare("SELECT image_url FROM product_color_image WHERE color_image_id = ?");
    $stmt->bind_param('i', $imageId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    
    if ($row) {
        // Delete from DB
        $delStmt = $conn->prepare("DELETE FROM product_color_image WHERE color_image_id = ?");
        $delStmt->bind_param('i', $imageId);
        if ($delStmt->execute()) {
            // Optionally delete the file
            $filePath = __DIR__ . '/../../' . str_replace('\\', '/', $row['image_url']);
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            echo json_encode(['success' => true]);
        } else {
            jsonError('Failed to delete image');
        }
    } else {
        jsonError('Image not found');
    }
    exit;
}

if ($action === 'list') {
    $q = $_GET['q'] ?? '';
            // product table stores core product metadata; prices and stock live in `product_variant`.
            // Use subqueries to surface a representative price and aggregated stock per product.
            // Get the first image using a subquery to handle products without images (exclude empty placeholders)
            $sql = "SELECT p.product_id, p.name,
                        COALESCE((SELECT MIN(price) FROM product_variant v WHERE v.product_id = p.product_id), 0.00) AS price,
                        COALESCE((SELECT SUM(stock) FROM product_variant v WHERE v.product_id = p.product_id), 0) AS stock,
                        p.category,
                        (SELECT pci.image_url FROM product_color_image pci WHERE pci.product_id = p.product_id AND pci.image_url != '' ORDER BY pci.sort_order ASC LIMIT 1) AS image_url,
                        b.brand_name
                    FROM product p
                    LEFT JOIN brand b ON p.brand_id = b.brand_id
                    WHERE 1=1 ";
    if ($q !== '') {
        $sql .= " AND (p.name LIKE ? OR b.brand_name LIKE ? OR p.category LIKE ? OR p.product_id = ?) ";
    }
    $sql .= " ORDER BY p.created_at DESC LIMIT 100";

    $stmt = $conn->prepare($sql);
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);

    if ($q !== '') {
        $like = "%$q%";
        // bind as strings; product_id comparison accepts the string form
        if (!$stmt->bind_param('ssss', $like, $like, $like, $q)) {
            jsonError('Bind failed: ' . $stmt->error);
        }
    }

    if (!$stmt->execute()) jsonError('Execute failed: ' . $stmt->error);
    $res = $stmt->get_result();
    $products = $res->fetch_all(MYSQLI_ASSOC);
    
    // Get all product IDs
    $productIds = array_column($products, 'product_id');
    
    // Fetch color variants with prices for all products
    $colorVariants = [];
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $types = str_repeat('i', count($productIds));
        
        // Get color variants (price, stock per color)
        $variantSql = "SELECT pv.product_id, pv.color_id, c.color_name, 
                              MIN(pv.price) as price, SUM(pv.stock) as stock
                       FROM product_variant pv
                       JOIN color c ON pv.color_id = c.color_id
                       WHERE pv.product_id IN ($placeholders)
                       GROUP BY pv.product_id, pv.color_id, c.color_name
                       ORDER BY pv.product_id, c.color_name";
        $variantStmt = $conn->prepare($variantSql);
        if ($variantStmt) {
            $variantStmt->bind_param($types, ...$productIds);
            $variantStmt->execute();
            $variantRes = $variantStmt->get_result();
            while ($row = $variantRes->fetch_assoc()) {
                $pid = $row['product_id'];
                if (!isset($colorVariants[$pid])) {
                    $colorVariants[$pid] = [];
                }
                $colorVariants[$pid][$row['color_id']] = [
                    'color_id' => $row['color_id'],
                    'color_name' => $row['color_name'],
                    'price' => $row['price'],
                    'stock' => $row['stock'],
                    'image_url' => null
                ];
            }
            $variantStmt->close();
        }
        
        // Also get colors from product_color_image that may not have variants yet
        $imgColorSql = "SELECT DISTINCT pci.product_id, pci.color_id, c.color_name
                        FROM product_color_image pci
                        JOIN color c ON pci.color_id = c.color_id
                        WHERE pci.product_id IN ($placeholders)";
        $imgColorStmt = $conn->prepare($imgColorSql);
        if ($imgColorStmt) {
            $imgColorStmt->bind_param($types, ...$productIds);
            $imgColorStmt->execute();
            $imgColorRes = $imgColorStmt->get_result();
            while ($row = $imgColorRes->fetch_assoc()) {
                $pid = $row['product_id'];
                $cid = $row['color_id'];
                if (!isset($colorVariants[$pid])) {
                    $colorVariants[$pid] = [];
                }
                if (!isset($colorVariants[$pid][$cid])) {
                    $colorVariants[$pid][$cid] = [
                        'color_id' => $cid,
                        'color_name' => $row['color_name'],
                        'price' => null,
                        'stock' => 0,
                        'image_url' => null
                    ];
                }
            }
            $imgColorStmt->close();
        }
        
        // Get first image for each color (excluding empty placeholders)
        $imgSql = "SELECT pci.product_id, pci.color_id, pci.image_url
                   FROM product_color_image pci
                   WHERE pci.product_id IN ($placeholders) 
                   AND pci.image_url != '' 
                   AND pci.sort_order = (
                       SELECT MIN(pci2.sort_order) 
                       FROM product_color_image pci2 
                       WHERE pci2.product_id = pci.product_id 
                       AND pci2.color_id = pci.color_id 
                       AND pci2.image_url != ''
                   )
                   ORDER BY pci.product_id, pci.color_id";
        $imgStmt = $conn->prepare($imgSql);
        if ($imgStmt) {
            $imgStmt->bind_param($types, ...$productIds);
            $imgStmt->execute();
            $imgRes = $imgStmt->get_result();
            while ($row = $imgRes->fetch_assoc()) {
                $pid = $row['product_id'];
                $cid = $row['color_id'];
                $imgUrl = str_replace('\\', '/', $row['image_url']);
                if ($imgUrl && isset($colorVariants[$pid][$cid])) {
                    $colorVariants[$pid][$cid]['image_url'] = $imgUrl;
                }
            }
            $imgStmt->close();
        }
    }
    
    // Normalize image paths and add color variant data
    foreach ($products as &$p) {
        if (isset($p['image_url'])) {
            $img = $p['image_url'];
            $img = str_replace('\\', '/', $img);
            if ($img === '') $img = null;
            $p['image_url'] = $img;
        } else {
            $p['image_url'] = null;
        }
        
        // Add color variants array (with price, stock, image per color)
        $pid = $p['product_id'];
        $p['color_variants'] = isset($colorVariants[$pid]) ? array_values($colorVariants[$pid]) : [];
    }
    unset($p);
    echo json_encode(['success' => true, 'products' => $products]);
    exit;
}

if ($action === 'get') {
    $id = $_GET['id'] ?? '';
    if (!$id) jsonError('Missing id');
    $stmt = $conn->prepare("SELECT p.product_id, p.name, p.price, p.stock, p.category, COALESCE(NULLIF(pi.image_url, ''), 'upload/product-image/placeholder.png') AS image_url, p.brand_id, p.created_at
                              FROM product p
                              LEFT JOIN product_color_image pi ON p.product_id = pi.product_id AND pi.sort_order = 1
                              WHERE p.product_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!$row) jsonError('Not found');
    // normalize image path
    if (isset($row['image_url'])) {
        $row['image_url'] = str_replace('\\', '/', $row['image_url']);
        if ($row['image_url'] === '') $row['image_url'] = 'upload/product-image/placeholder.png';
    } else {
        $row['image_url'] = 'upload/product-image/placeholder.png';
    }
    // fetch all color images for this product (product_color_image)
    $imgs = [];
    $iStmt = $conn->prepare("SELECT color_id, image_url, sort_order FROM product_color_image WHERE product_id = ? ORDER BY sort_order ASC");
    if ($iStmt) {
        $iStmt->bind_param('i', $id);
        $iStmt->execute();
        $iRes = $iStmt->get_result();
        while ($ir = $iRes->fetch_assoc()) {
            $img = $ir;
            $img['image_url'] = isset($img['image_url']) ? str_replace('\\', '/', $img['image_url']) : 'upload/product-image/placeholder.png';
            if ($img['image_url'] === '') $img['image_url'] = 'upload/product-image/placeholder.png';
            $imgs[] = $img;
        }
        $iStmt->close();
    }
    $row['images'] = $imgs;
    echo json_encode(['success' => true, 'product' => $row]);
    exit;
}

if ($action === 'create' || $action === 'update') {
    // sanitize and validate
    $id = $_POST['product_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $brand_name = trim($_POST['brand_name'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if ($name === '') jsonError('Product name required');

    // ensure brand exists or null
    $brand_id = null;
    if ($brand_name !== '') {
        // try find brand
        $bstmt = $conn->prepare("SELECT brand_id FROM brand WHERE brand_name = ? LIMIT 1");
        $bstmt->bind_param('s', $brand_name);
        $bstmt->execute();
        $bres = $bstmt->get_result();
        $brow = $bres->fetch_assoc();
        if ($brow) $brand_id = $brow['brand_id'];
        else {
            // insert brand
            $ins = $conn->prepare("INSERT INTO brand (brand_name) VALUES (?)");
            $ins->bind_param('s', $brand_name);
            $ins->execute();
            $brand_id = $conn->insert_id;
        }
    }

    if ($action === 'create') {
        // Note: the `product` table does not include price/stock columns.
        // Prices and stock are stored in `product_variant` per color/size.
        $stmt = $conn->prepare("INSERT INTO product (name, brand_id, category, created_at) VALUES (?, ?, ?, NOW())");
        if (!$stmt) jsonError('Prepare failed (create): ' . $conn->error);
        // types: name (s), brand_id (i), category (s)
        if (!$stmt->bind_param('sis', $name, $brand_id, $category)) {
            jsonError('Bind failed (create): ' . $stmt->error);
        }
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            echo json_encode(['success' => true, 'id' => $newId]);
            exit;
        } else {
            jsonError('Insert failed: ' . $stmt->error);
        }
    } else {
        if (!$id) jsonError('Missing id');
        // Update core product fields only; prices/stock are in product_variant.
        $stmt = $conn->prepare("UPDATE product SET name = ?, brand_id = ?, category = ? WHERE product_id = ?");
        if (!$stmt) jsonError('Prepare failed (update): ' . $conn->error);
        // types: name (s), brand_id (i), category (s), id (i)
        if (!$stmt->bind_param('sisi', $name, $brand_id, $category, $id)) {
            jsonError('Bind failed (update): ' . $stmt->error);
        }
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
            exit;
        } else {
            jsonError('Update failed: ' . $stmt->error);
        }
    }
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? $_GET['id'] ?? '';
    if (!$id) jsonError('Missing id');
    $stmt = $conn->prepare("DELETE FROM product WHERE product_id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
        exit;
    } else {
        jsonError('Delete failed');
    }
}

jsonError('Invalid action');