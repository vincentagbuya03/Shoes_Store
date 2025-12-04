<?php
// API for customers CRUD
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db_connection.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function jsonError($m){ echo json_encode(['success'=>false,'message'=>$m]); exit; }

if ($action === 'list') {
    $stmt = $conn->prepare("SELECT customer_id, name, email, phone, address, created_at FROM customer ORDER BY created_at DESC LIMIT 500");
    $stmt->execute();
    $res = $stmt->get_result();
    $customers = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success'=>true,'customers'=>$customers]); exit;
}

if ($action === 'get') {
    $id = $_GET['id'] ?? '';
    if (!$id) jsonError('Missing id');
    $stmt = $conn->prepare("SELECT * FROM customer WHERE customer_id = ?");
    $stmt->bind_param('i',$id);
    $stmt->execute();
    $res = $stmt->get_result();
    $c = $res->fetch_assoc();
    if (!$c) jsonError('Not found');
    echo json_encode(['success'=>true,'customer'=>$c]); exit;
}

if ($action === 'create' || $action === 'update') {
    $id = $_POST['customer_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '') jsonError('Name and email required');

    if ($action === 'create') {
        // Password is required for new customers
        if ($password === '') jsonError('Password is required for new customers');
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO customer (name, email, phone, address, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('sssss', $name, $email, $phone, $address, $hashedPassword);
        if ($stmt->execute()) echo json_encode(['success'=>true,'id'=>$conn->insert_id]);
        else jsonError('Insert failed');
        exit;
    } else {
        if (!$id) jsonError('Missing id');
        
        // If password is provided, update it too
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE customer SET name = ?, email = ?, phone = ?, address = ?, password = ? WHERE customer_id = ?");
            $stmt->bind_param('sssssi', $name, $email, $phone, $address, $hashedPassword, $id);
        } else {
            $stmt = $conn->prepare("UPDATE customer SET name = ?, email = ?, phone = ?, address = ? WHERE customer_id = ?");
            $stmt->bind_param('ssssi', $name, $email, $phone, $address, $id);
        }
        
        if ($stmt->execute()) echo json_encode(['success'=>true]);
        else jsonError('Update failed');
        exit;
    }
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? $_GET['id'] ?? '';
    if (!$id) jsonError('Missing id');
    $stmt = $conn->prepare("DELETE FROM customer WHERE customer_id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) echo json_encode(['success'=>true]);
    else jsonError('Delete failed');
    exit;
}

jsonError('Invalid action');