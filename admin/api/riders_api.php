<?php

header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', '0');
ini_set('log_errors', '1');

$isLocalRequest = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']);
set_exception_handler(function($ex) use ($isLocalRequest){
    http_response_code(500);
    error_log('Uncaught exception in riders_api: ' . $ex->getMessage());
    $msg = $isLocalRequest ? ('Internal server error: ' . $ex->getMessage()) : 'Internal server error';
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
});

register_shutdown_function(function() use ($isLocalRequest){
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        error_log('Shutdown fatal error in riders_api: ' . ($err['message'] ?? ''));
        $msg = $isLocalRequest ? ('Internal server error (fatal): ' . ($err['message'] ?? '')) : 'Internal server error (fatal)';
        echo json_encode(['success' => false, 'message' => $msg]);
    }
});

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

// Get rider statistics
function getRiderStats($conn) {
    $stats = ['total' => 0, 'available' => 0, 'busy' => 0, 'inactive' => 0];
    
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM rider GROUP BY status");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $stats[$row['status']] = $row['count'];
            $stats['total'] += $row['count'];
        }
        $stmt->close();
    }
    
    return $stats;
}

// List all riders
if ($action === 'list') {
    $q = $_GET['q'] ?? '';
    
    $sql = "SELECT r.rider_id, r.name, r.email, r.phone, r.status, r.created_at,
                   r.current_lat, r.current_lng,
                   (SELECT COUNT(*) FROM orders o WHERE o.rider_id = r.rider_id AND o.status = 'completed') AS delivery_count
            FROM rider r
            WHERE 1=1";
    
    if ($q !== '') {
        $sql .= " AND (r.name LIKE ? OR r.email LIKE ? OR r.phone LIKE ? OR r.rider_id = ?)";
    }
    $sql .= " ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);
    
    if ($q !== '') {
        $like = "%$q%";
        if (!$stmt->bind_param('ssss', $like, $like, $like, $q)) {
            jsonError('Bind failed: ' . $stmt->error);
        }
    }
    
    if (!$stmt->execute()) jsonError('Execute failed: ' . $stmt->error);
    $res = $stmt->get_result();
    $riders = $res->fetch_all(MYSQLI_ASSOC);
    
    $stats = getRiderStats($conn);
    
    jsonSuccess(['riders' => $riders, 'stats' => $stats]);
}

// Get single rider
if ($action === 'get') {
    $id = $_GET['id'] ?? '';
    if (!$id) jsonError('Missing id');
    
    $stmt = $conn->prepare("SELECT rider_id, name, email, phone, status, created_at, current_lat, current_lng FROM rider WHERE rider_id = ?");
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    
    if (!$row) jsonError('Rider not found');
    
    jsonSuccess(['rider' => $row]);
}

// Create new rider
if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $status = $_POST['status'] ?? 'available';
    
    if (!$name) jsonError('Name is required');
    if (!$email) jsonError('Email is required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email format');
    if (!$password) jsonError('Password is required');
    if (strlen($password) < 6) jsonError('Password must be at least 6 characters');
    
    // Validate status
    $validStatuses = ['available', 'busy', 'inactive'];
    if (!in_array($status, $validStatuses)) {
        $status = 'available';
    }
    
    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT rider_id FROM rider WHERE email = ?");
    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
        jsonError('A rider with this email already exists');
    }
    $checkStmt->close();
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO rider (name, email, phone, password, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);
    
    $stmt->bind_param('sssss', $name, $email, $phone, $hashedPassword, $status);
    
    if (!$stmt->execute()) {
        jsonError('Failed to create rider: ' . $stmt->error);
    }
    
    $newId = $conn->insert_id;
    jsonSuccess(['message' => 'Rider created successfully', 'rider_id' => $newId]);
}

// Update rider
if ($action === 'update') {
    $rider_id = $_POST['rider_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $status = $_POST['status'] ?? 'available';
    
    if (!$rider_id) jsonError('Rider ID is required');
    if (!$name) jsonError('Name is required');
    if (!$email) jsonError('Email is required');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email format');
    
    // Validate status
    $validStatuses = ['available', 'busy', 'inactive'];
    if (!in_array($status, $validStatuses)) {
        $status = 'available';
    }
    
    // Check if another rider with same email exists
    $checkStmt = $conn->prepare("SELECT rider_id FROM rider WHERE email = ? AND rider_id != ?");
    $checkStmt->bind_param('si', $email, $rider_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
        jsonError('Another rider with this email already exists');
    }
    $checkStmt->close();
    
    // Check if rider exists
    $existsStmt = $conn->prepare("SELECT rider_id FROM rider WHERE rider_id = ?");
    $existsStmt->bind_param('i', $rider_id);
    $existsStmt->execute();
    if ($existsStmt->get_result()->num_rows === 0) {
        jsonError('Rider not found');
    }
    $existsStmt->close();
    
    // Update with or without password
    if ($password && strlen($password) >= 6) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE rider SET name = ?, email = ?, phone = ?, password = ?, status = ? WHERE rider_id = ?");
        $stmt->bind_param('sssssi', $name, $email, $phone, $hashedPassword, $status, $rider_id);
    } else {
        $stmt = $conn->prepare("UPDATE rider SET name = ?, email = ?, phone = ?, status = ? WHERE rider_id = ?");
        $stmt->bind_param('ssssi', $name, $email, $phone, $status, $rider_id);
    }
    
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);
    
    if (!$stmt->execute()) {
        jsonError('Failed to update rider: ' . $stmt->error);
    }
    
    jsonSuccess(['message' => 'Rider updated successfully']);
}

// Delete rider
if ($action === 'delete') {
    $id = $_POST['id'] ?? '';
    if (!$id) jsonError('Rider ID is required');
    
    // Check if rider has active orders
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE rider_id = ? AND status IN ('processing', 'shipped', 'out_for_delivery')");
    $checkStmt->bind_param('i', $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $activeOrders = $checkResult->fetch_assoc()['count'];
    $checkStmt->close();
    
    if ($activeOrders > 0) {
        jsonError("Cannot delete rider: $activeOrders active order(s) are assigned to this rider");
    }
    
    // Set rider_id to NULL for completed orders
    $updateStmt = $conn->prepare("UPDATE orders SET rider_id = NULL WHERE rider_id = ?");
    $updateStmt->bind_param('i', $id);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Delete rider
    $stmt = $conn->prepare("DELETE FROM rider WHERE rider_id = ?");
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);
    
    $stmt->bind_param('i', $id);
    
    if (!$stmt->execute()) {
        jsonError('Failed to delete rider: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        jsonError('Rider not found');
    }
    
    jsonSuccess(['message' => 'Rider deleted successfully']);
}

// Update rider status
if ($action === 'update_status') {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (!$id) jsonError('Rider ID is required');
    
    $validStatuses = ['available', 'busy', 'inactive'];
    if (!in_array($status, $validStatuses)) {
        jsonError('Invalid status');
    }
    
    $stmt = $conn->prepare("UPDATE rider SET status = ? WHERE rider_id = ?");
    if (!$stmt) jsonError('Prepare failed: ' . $conn->error);
    
    $stmt->bind_param('si', $status, $id);
    
    if (!$stmt->execute()) {
        jsonError('Failed to update status: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        jsonError('Rider not found');
    }
    
    jsonSuccess(['message' => 'Status updated successfully']);
}

jsonError('Unknown action: ' . $action);
