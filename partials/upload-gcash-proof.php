<?php
require_once __DIR__ . '/../db_connection.php';
session_start();

header('Content-Type: application/json');
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (empty($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['payment_proof'];
$allowed = ['image/jpeg','image/png','image/webp'];
if (!in_array($file['type'], $allowed)) {
    // still allow other types but warn
    // echo json_encode(['success' => false, 'error' => 'Invalid file type']); exit;
}

$uploadDir = __DIR__ . '/../upload/payment-proof/';
    $uploadDir = __DIR__ . '/../admin/upload/payment-proof/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
$safeName = uniqid('proof_') . '.' . $ext;
$dest = $uploadDir . $safeName;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

    $publicPath = 'admin/upload/payment-proof/' . $safeName;
echo json_encode(['success' => true, 'path' => $publicPath]);

?>
