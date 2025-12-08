<?php

header('Content-Type: application/json; charset=utf-8');

// Basic runtime safeguards: return JSON on uncaught exceptions / fatal errors
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Determine whether the request is local (development). Only expose internal errors when local.
$isLocalRequest = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
set_exception_handler(function($ex) use ($isLocalRequest){
    http_response_code(500);
    error_log('Uncaught exception in brands_api: ' . $ex->getMessage());
    $msg = $isLocalRequest ? ('Internal server error: ' . $ex->getMessage()) : 'Internal server error';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
});

register_shutdown_function(function() use ($isLocalRequest){
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        error_log('Shutdown fatal error in brands_api: ' . ($err['message'] ?? ''));
        $msg = $isLocalRequest ? ('Internal server error (fatal): ' . ($err['message'] ?? '')) : 'Internal server error (fatal)';
        echo json_encode(['success' => false, 'message' => $msg]);
    }
});

// Ensure DB connection file exists before requiring it
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

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function jsonError($msg) {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function jsonSuccess($data = []) {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

// List all brands
if ($action === 'list') {
    $q = $_GET['q'] ?? '';
    
    $sql = "SELECT b.brand_id, b.brand_name, b.brand_logo, b.created_at,
                   (SELECT COUNT(*) FROM product p WHERE p.brand_id = b.brand_id) AS product_count
            FROM brand b
            WHERE 1=1";
    
    if ($q !== '') {
        $sql .= " AND (b.brand_name LIKE ? OR b.brand_id = ?)";
    }
    $sql .= " ORDER BY b.brand_name ASC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);
    
    if ($q !== '') {
        $like = "%$q%";
        if (!$stmt->bind_param('ss', $like, $q)) {
            jsonError('Bind failed: ' . $stmt->error);
        }
    }
    
    if (!$stmt->execute()) jsonError('Execute failed: ' . $stmt->error);
    $res = $stmt->get_result();
    $brands = $res->fetch_all(MYSQLI_ASSOC);
    
    // Normalize logo paths
    foreach ($brands as &$b) {
        if (isset($b['brand_logo']) && $b['brand_logo']) {
            $b['brand_logo'] = str_replace('\\', '/', $b['brand_logo']);
        } else {
            $b['brand_logo'] = 'upload/brand-picture/placeholder.png';
        }
    }
    unset($b);
    
    jsonSuccess(['brands' => $brands]);
}

// Get single brand
if ($action === 'get') {
    $id = $_GET['id'] ?? '';
    if (!$id) jsonError('Missing id');
    
    $stmt = $conn->prepare("SELECT brand_id, brand_name, brand_logo, created_at FROM brand WHERE brand_id = ?");
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    
    if (!$row) jsonError('Brand not found');
    
    // Normalize logo path
    if (isset($row['brand_logo']) && $row['brand_logo']) {
        $row['brand_logo'] = str_replace('\\', '/', $row['brand_logo']);
    } else {
        $row['brand_logo'] = 'upload/brand-picture/placeholder.png';
    }
    
    jsonSuccess(['brand' => $row]);
}

// Create new brand
if ($action === 'create') {
    $brand_name = trim($_POST['brand_name'] ?? '');
    
    if (!$brand_name) jsonError('Brand name is required');
    
    // Check if brand already exists
    $checkStmt = $conn->prepare("SELECT brand_id FROM brand WHERE brand_name = ?");
    $checkStmt->bind_param('s', $brand_name);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
        jsonError('A brand with this name already exists');
    }
    $checkStmt->close();
    
    // Handle logo upload
    $brand_logo = null;
    if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../upload/brand-picture/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['brand_logo']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (!in_array($ext, $allowedExts)) {
            jsonError('Invalid image format. Allowed: ' . implode(', ', $allowedExts));
        }
        
        $filename = preg_replace('/[^a-z0-9]/i', '', strtolower($brand_name)) . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['brand_logo']['tmp_name'], $targetPath)) {
            $brand_logo = 'upload/brand-picture/' . $filename;
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO brand (brand_name, brand_logo, created_at) VALUES (?, ?, NOW())");
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);
    
    $stmt->bind_param('ss', $brand_name, $brand_logo);
    
    if (!$stmt->execute()) {
        jsonError('Failed to create brand: ' . $stmt->error);
    }
    
    $newId = $conn->insert_id;
    jsonSuccess(['message' => 'Brand created successfully', 'brand_id' => $newId]);
}

// Update brand
if ($action === 'update') {
    $brand_id = $_POST['brand_id'] ?? '';
    $brand_name = trim($_POST['brand_name'] ?? '');
    
    if (!$brand_id) jsonError('Brand ID is required');
    if (!$brand_name) jsonError('Brand name is required');
    
    // Check if another brand with the same name exists
    $checkStmt = $conn->prepare("SELECT brand_id FROM brand WHERE brand_name = ? AND brand_id != ?");
    $checkStmt->bind_param('si', $brand_name, $brand_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
        jsonError('Another brand with this name already exists');
    }
    $checkStmt->close();
    
    // Get current brand data
    $currentStmt = $conn->prepare("SELECT brand_logo FROM brand WHERE brand_id = ?");
    $currentStmt->bind_param('i', $brand_id);
    $currentStmt->execute();
    $currentResult = $currentStmt->get_result();
    $currentBrand = $currentResult->fetch_assoc();
    $currentStmt->close();
    
    if (!$currentBrand) jsonError('Brand not found');
    
    $brand_logo = $currentBrand['brand_logo'];
    
    // Handle logo upload
    if (isset($_FILES['brand_logo']) && $_FILES['brand_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../upload/brand-picture/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['brand_logo']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (!in_array($ext, $allowedExts)) {
            jsonError('Invalid image format. Allowed: ' . implode(', ', $allowedExts));
        }
        
        $filename = preg_replace('/[^a-z0-9]/i', '', strtolower($brand_name)) . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['brand_logo']['tmp_name'], $targetPath)) {
            // Delete old logo if it exists and is different
            if ($brand_logo && file_exists(__DIR__ . '/../' . $brand_logo)) {
                @unlink(__DIR__ . '/../' . $brand_logo);
            }
            $brand_logo = 'upload/brand-picture/' . $filename;
        }
    }
    
    $stmt = $conn->prepare("UPDATE brand SET brand_name = ?, brand_logo = ? WHERE brand_id = ?");
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);
    
    $stmt->bind_param('ssi', $brand_name, $brand_logo, $brand_id);
    
    if (!$stmt->execute()) {
        jsonError('Failed to update brand: ' . $stmt->error);
    }
    
    jsonSuccess(['message' => 'Brand updated successfully']);
}

// Delete brand
if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    if (!$id) jsonError('Brand ID is required');
    
    // Check if brand has products
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM product WHERE brand_id = ?");
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $productCount = $checkResult->fetch_assoc()['count'];
    $checkStmt->close();
    
    if ($productCount > 0) {
        jsonError("Cannot delete brand: $productCount product(s) are associated with this brand. Please reassign or delete the products first.");
    }
    
    // Get brand logo to delete file
    $logoStmt = $conn->prepare("SELECT brand_logo FROM brand WHERE brand_id = ?");
    $logoStmt->bind_param('i', $id);
    $logoStmt->execute();
    $logoResult = $logoStmt->get_result();
    $brandData = $logoResult->fetch_assoc();
    $logoStmt->close();
    
    // Delete brand
    $stmt = $conn->prepare("DELETE FROM brand WHERE brand_id = ?");
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);
    
    $stmt->bind_param('i', $id);
    
    if (!$stmt->execute()) {
        jsonError('Failed to delete brand: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        jsonError('Brand not found');
    }
    
    // Delete logo file if it exists
    if ($brandData && $brandData['brand_logo'] && file_exists(__DIR__ . '/../' . $brandData['brand_logo'])) {
        @unlink(__DIR__ . '/../' . $brandData['brand_logo']);
    }
    
    jsonSuccess(['message' => 'Brand deleted successfully']);
}

// Unknown action
jsonError('Unknown action: ' . $action);
