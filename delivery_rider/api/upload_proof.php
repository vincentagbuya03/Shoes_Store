<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../db_connection.php';

// Small debug helper to surface helpful local diagnostics when upload fails
$debugInfo = [];
$debugInfo['php_upload_max_filesize'] = ini_get('upload_max_filesize');
$debugInfo['php_post_max_size'] = ini_get('post_max_size');

if (!isset($_SESSION['rider_id'])) {
    $debugInfo['session_rider_id'] = null;
    echo json_encode(['success' => false, 'message' => 'Not authenticated', 'debug' => $debugInfo]);
    exit;
}

$rider_id = (int)$_SESSION['rider_id'];
$debugInfo['session_rider_id'] = $rider_id;

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order', 'debug' => $debugInfo]);
    exit;
}

if (!isset($_FILES['proof'])) {
    $debugInfo['file_present'] = false;
    echo json_encode(['success' => false, 'message' => 'Please provide a photo (no file found in request).', 'debug' => $debugInfo]);
    exit;
}

// If file present but error set
if ($_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
    $debugInfo['file_present'] = true;
    $debugInfo['file_error_code'] = (int)$_FILES['proof']['error'];
    echo json_encode(['success' => false, 'message' => 'File upload error. Code: ' . (int)$_FILES['proof']['error'], 'debug' => $debugInfo]);
    exit;
}

// Validate order and assignment
$stmt = $conn->prepare('SELECT order_id, rider_id, status FROM orders WHERE order_id = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
$stmt->bind_param('i', $order_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found', 'debug' => $debugInfo]);
    exit;
}

// Only allow assigned rider to mark delivered
if ((int)$order['rider_id'] !== $rider_id) {
    $debugInfo['order_rider_id'] = isset($order['rider_id']) ? (int)$order['rider_id'] : null;
    echo json_encode(['success' => false, 'message' => 'You are not assigned to this order', 'debug' => $debugInfo]);
    exit;
}

// Save uploaded file into admin upload directory so admin can serve it
// Desired path: <repo_root>/admin/upload/delivery-proof
$projectRoot = realpath(__DIR__ . '/../../') ?: (__DIR__ . '/../../');
$deliveryDir = $projectRoot . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'delivery-proof';
if (!is_dir($deliveryDir)) {
    @mkdir($deliveryDir, 0755, true);
}
// normalize and ensure no trailing slash/backslash
$deliveryDir = rtrim($deliveryDir, '/\\');

$file = $_FILES['proof'];
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$safeExt = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
$filename = 'proof_' . $order_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . ($safeExt ? '.' . $safeExt : '');
// Defensive sanitization: remove surrounding whitespace, replace internal whitespace with underscore,
// and allow only safe filename characters to avoid leading spaces (which become %20 in URLs).
$filename = trim($filename);
$filename = preg_replace('/\s+/', '_', $filename);
$filename = preg_replace('/[^A-Za-z0-9._-]/', '', $filename);
$dest = $deliveryDir . DIRECTORY_SEPARATOR . $filename;
// Public (web) relative path to store in DB so front-end can reference it directly
// Use forward slashes and no stray spaces so the stored URL does not contain "%20"
$publicPath = 'upload/delivery-proof/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    $debugInfo['move_dest'] = $dest;
    $debugInfo['is_writable_upload_dir'] = is_writable(dirname($dest));
    echo json_encode(['success' => false, 'message' => 'Failed to save photo', 'debug' => $debugInfo]);
    exit;
}

// Update order status to completed; if orders.delivery_proof column exists, set it
$conn->begin_transaction();
try {
    // Check for delivery_proof column
    $hasProofCol = false;
    $colCheck = $conn->prepare("SHOW COLUMNS FROM orders LIKE 'delivery_proof'");
    if ($colCheck) {
        $colCheck->execute();
        $res = $colCheck->get_result();
        if ($res && $res->num_rows > 0) $hasProofCol = true;
        $colCheck->close();
    }

    if ($hasProofCol) {
        $upd = $conn->prepare('UPDATE orders SET status = ?, delivery_proof = ? WHERE order_id = ?');
        $new_status = 'completed';
        $upd->bind_param('ssi', $new_status, $publicPath, $order_id);
    } else {
        $upd = $conn->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
        $new_status = 'completed';
        $upd->bind_param('si', $new_status, $order_id);
    }

    if (!$upd || !$upd->execute()) {
        $conn->rollback();
        $debugInfo['db_error'] = $conn->error ?? null;
        echo json_encode(['success' => false, 'message' => 'Could not update order status', 'debug' => $debugInfo]);
        exit;
    }
    $upd->close();

    // If order has an assigned rider, mark that rider as available now that delivery is completed
    try {
        $assignedRid = isset($order['rider_id']) ? (int)$order['rider_id'] : 0;
        if ($assignedRid > 0) {
            $ru = $conn->prepare("UPDATE rider SET status = 'available' WHERE rider_id = ?");
            if ($ru) {
                $ru->bind_param('i', $assignedRid);
                $ru->execute();
                $ru->close();
            }
        }
    } catch (Exception $e) {
        // Non-fatal: include debug so local dev can inspect if needed
        $debugInfo['rider_update_error'] = $e->getMessage();
    }

    $conn->commit();
    // Optionally include debug info on success for local verification
    $resp = ['success' => true, 'message' => 'Proof uploaded, order marked delivered', 'new_status' => $new_status];
    if (!empty($debugInfo)) $resp['debug'] = $debugInfo;
    echo json_encode($resp);
    exit;

} catch (Exception $ex) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit;
}

?>
