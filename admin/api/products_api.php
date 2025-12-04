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

if ($action === 'list') {
    $q = $_GET['q'] ?? '';
            // product table stores core product metadata; prices and stock live in `product_variant`.
            // Use subqueries to surface a representative price and aggregated stock per product.
            $sql = "SELECT p.product_id, p.name,
                        COALESCE((SELECT MIN(price) FROM product_variant v WHERE v.product_id = p.product_id), 0.00) AS price,
                        COALESCE((SELECT SUM(stock) FROM product_variant v WHERE v.product_id = p.product_id), 0) AS stock,
                        p.category,
                        COALESCE(NULLIF(pi.image_url, ''), 'upload/product-image/placeholder.png') AS image_url,
                        b.brand_name
                    FROM product p
                    LEFT JOIN brand b ON p.brand_id = b.brand_id
                    LEFT JOIN product_color_image pi ON p.product_id = pi.product_id AND pi.sort_order = 1
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
    // Normalize image paths returned by DB: convert backslashes to forward slashes
    foreach ($products as &$p) {
        if (isset($p['image_url'])) {
            $img = $p['image_url'];
            $img = str_replace('\\', '/', $img);
            if ($img === '') $img = 'upload/product-image/placeholder.png';
            $p['image_url'] = $img;
        } else {
            $p['image_url'] = 'upload/product-image/placeholder.png';
        }
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
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');

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
        // Note: the `product` table does not include an `image_url` column in the current schema.
        // We insert core product fields here and ignore any supplied `image_url` (color images
        // should be managed via `product_color_image`).
        $stmt = $conn->prepare("INSERT INTO product (name, brand_id, price, stock, category, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if (!$stmt) jsonError('Prepare failed (create): ' . $conn->error);
        // types: name (s), brand_id (i), price (d), stock (i), category (s)
        if (!$stmt->bind_param('sidis', $name, $brand_id, $price, $stock, $category)) {
            jsonError('Bind failed (create): ' . $stmt->error);
        }
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            // handle uploaded images (image_1 .. image_5)
            $uploadDir = __DIR__ . '/../../upload/product-image/';
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
            // determine fallback color_id
            $color_id = 1;
            $cres = $conn->query("SELECT color_id FROM color LIMIT 1");
            if ($cres && ($crow = $cres->fetch_assoc())) $color_id = (int)$crow['color_id'];
            for ($i=1;$i<=5;$i++) {
                $fkey = 'image_' . $i;
                if (!empty($_FILES[$fkey]) && isset($_FILES[$fkey]['tmp_name']) && is_uploaded_file($_FILES[$fkey]['tmp_name'])) {
                    $orig = basename($_FILES[$fkey]['name']);
                    $ext = pathinfo($orig, PATHINFO_EXTENSION);
                    $safe = preg_replace('/[^a-zA-Z0-9-_\.]/','_', pathinfo($orig, PATHINFO_FILENAME));
                    $filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safe . ($ext ? '.' . $ext : '');
                    $dest = $uploadDir . $filename;
                    if (move_uploaded_file($_FILES[$fkey]['tmp_name'], $dest)) {
                        $publicPath = 'upload/product-image/' . $filename;
                        $ins = $conn->prepare("INSERT INTO product_color_image (product_id, color_id, image_url, sort_order) VALUES (?, ?, ?, ?)");
                        if ($ins) { $so = $i; $ins->bind_param('iisi', $newId, $color_id, $publicPath, $so); $ins->execute(); $ins->close(); }
                    }
                }
            }
            // low-stock notification
            $lowStockThreshold = 5;
            if ($stock <= $lowStockThreshold) {
                try {
                    require_once __DIR__ . '/../../inc/admin_notifications.php';
                    $title = "Low stock: {$name}";
                    $body = "Product #{$newId} has stock {$stock}";
                    $meta = ['product_id' => $newId, 'stock' => $stock];
                    $url = '/admin/products.php?id=' . $newId;
                    @create_admin_notification($conn, 'low_stock', $title, $body, 'medium', $meta, $url);
                } catch (Exception $e) { error_log('Low stock notification failed: ' . $e->getMessage()); }
            }

            echo json_encode(['success' => true, 'id' => $newId]);
            exit;
        } else {
            jsonError('Insert failed: ' . $stmt->error);
        }
    } else {
        if (!$id) jsonError('Missing id');
        // Update core product fields only; color images are stored separately in product_color_image.
        $stmt = $conn->prepare("UPDATE product SET name = ?, brand_id = ?, price = ?, stock = ?, category = ? WHERE product_id = ?");
        if (!$stmt) jsonError('Prepare failed (update): ' . $conn->error);
        // types: name (s), brand_id (i), price (d), stock (i), category (s), id (i)
        if (!$stmt->bind_param('sidisi', $name, $brand_id, $price, $stock, $category, $id)) {
            jsonError('Bind failed (update): ' . $stmt->error);
        }
        if ($stmt->execute()) {
            $updatedId = (int)$id;
            // If images were uploaded, replace product_color_image entries for this product
            $hasAnyUpload = false;
            for ($i=1;$i<=5;$i++) {
                $fkey = 'image_' . $i;
                if (!empty($_FILES[$fkey]) && isset($_FILES[$fkey]['tmp_name']) && is_uploaded_file($_FILES[$fkey]['tmp_name'])) { $hasAnyUpload = true; break; }
            }
            if ($hasAnyUpload) {
                // delete existing images
                $d = $conn->prepare("DELETE FROM product_color_image WHERE product_id = ?");
                if ($d) { $d->bind_param('i', $updatedId); $d->execute(); $d->close(); }
                $uploadDir = __DIR__ . '/../../upload/product-image/';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                $color_id = 1;
                $cres = $conn->query("SELECT color_id FROM color LIMIT 1");
                if ($cres && ($crow = $cres->fetch_assoc())) $color_id = (int)$crow['color_id'];
                for ($i=1;$i<=5;$i++) {
                    $fkey = 'image_' . $i;
                    if (!empty($_FILES[$fkey]) && isset($_FILES[$fkey]['tmp_name']) && is_uploaded_file($_FILES[$fkey]['tmp_name'])) {
                        $orig = basename($_FILES[$fkey]['name']);
                        $ext = pathinfo($orig, PATHINFO_EXTENSION);
                        $safe = preg_replace('/[^a-zA-Z0-9-_\.]/','_', pathinfo($orig, PATHINFO_FILENAME));
                        $filename = time() . '_' . bin2hex(random_bytes(4)) . '_' . $safe . ($ext ? '.' . $ext : '');
                        $dest = $uploadDir . $filename;
                        if (move_uploaded_file($_FILES[$fkey]['tmp_name'], $dest)) {
                            $publicPath = 'upload/product-image/' . $filename;
                            $ins = $conn->prepare("INSERT INTO product_color_image (product_id, color_id, image_url, sort_order) VALUES (?, ?, ?, ?)");
                            if ($ins) { $so = $i; $ins->bind_param('iisi', $updatedId, $color_id, $publicPath, $so); $ins->execute(); $ins->close(); }
                        }
                    }
                }
            }
            // low-stock notification on update
            $lowStockThreshold = 5;
            if ($stock <= $lowStockThreshold) {
                try {
                    require_once __DIR__ . '/../../inc/admin_notifications.php';
                    $title = "Low stock: {$name}";
                    $body = "Product #{$updatedId} has stock {$stock}";
                    $meta = ['product_id' => $updatedId, 'stock' => $stock];
                    $url = '/admin/products.php?id=' . $updatedId;
                    @create_admin_notification($conn, 'low_stock', $title, $body, 'medium', $meta, $url);
                } catch (Exception $e) { error_log('Low stock notification failed: ' . $e->getMessage()); }
            }

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