<?php
/**
 * Handle Refund API
 * Allows riders to accept pickup assignments and mark items as picked up
 */
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['rider_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../db_connection.php';
// Admin notification helper
require_once __DIR__ . '/../../inc/admin_notifications.php';

$rider_id = $_SESSION['rider_id'];

// Helper to insert into notifications table safely depending on available columns
function safeInsertNotification($conn, $user_type, $user_id, $title, $body, $type) {
    $cols = [];
    $colRes = $conn->query("SHOW COLUMNS FROM notifications");
    if ($colRes) {
        $existing = [];
        while ($c = $colRes->fetch_assoc()) $existing[] = $c['Field'];
        $hasTitle = in_array('title', $existing);
        $hasBody = in_array('body', $existing);
        $hasType = in_array('type', $existing);
    } else {
        // If table doesn't exist or SHOW failed, try to create basic notifications table
        $conn->query("CREATE TABLE IF NOT EXISTS `notifications` (
            `id` int NOT NULL AUTO_INCREMENT,
            `user_type` enum('customer','rider','admin') NOT NULL,
            `user_id` int NOT NULL,
            `type` varchar(80) NOT NULL,
            `title` varchar(255) DEFAULT NULL,
            `body` text,
            `payload` json DEFAULT NULL,
            `url` varchar(255) DEFAULT NULL,
            `read_at` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $colRes = $conn->query("SHOW COLUMNS FROM notifications");
        $existing = [];
        while ($c = $colRes->fetch_assoc()) $existing[] = $c['Field'];
        $hasTitle = in_array('title', $existing);
        $hasBody = in_array('body', $existing);
        $hasType = in_array('type', $existing);
    }

    // Build insert based on available columns
    if ($hasTitle && $hasBody && $hasType) {
        $stmt = $conn->prepare("INSERT INTO notifications (`user_type`,`user_id`,`title`,`body`,`type`) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sisss', $user_type, $user_id, $title, $body, $type);
    } elseif ($hasBody && $hasType) {
        $stmt = $conn->prepare("INSERT INTO notifications (`user_type`,`user_id`,`body`,`type`) VALUES (?,?,?,?)");
        $stmt->bind_param('siss', $user_type, $user_id, $body, $type);
    } elseif ($hasType) {
        $stmt = $conn->prepare("INSERT INTO notifications (`user_type`,`user_id`,`type`) VALUES (?,?,?)");
        $stmt->bind_param('sis', $user_type, $user_id, $type);
    } else {
        // Last resort: try a minimal insert without type
        $stmt = $conn->prepare("INSERT INTO notifications (`user_type`,`user_id`) VALUES (?,?)");
        $stmt->bind_param('si', $user_type, $user_id);
    }

    if (!$stmt) {
        error_log(date('[Y-m-d H:i:s] ') . "prepare notifications insert failed: " . $conn->error . "\n", 3, __DIR__ . '/../../storage/refund_errors.log');
        return false;
    }

    $ok = $stmt->execute();
    if (!$ok) {
        error_log(date('[Y-m-d H:i:s] ') . "notifications insert failed: " . $stmt->error . "\n", 3, __DIR__ . '/../../storage/refund_errors.log');
    }
    $stmt->close();
    return $ok;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request body']);
    exit;
}

$refund_id = isset($input['refund_id']) ? (int)$input['refund_id'] : 0;
$action = isset($input['action']) ? $input['action'] : '';

if ($refund_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid refund ID']);
    exit;
}

// Validate action
$valid_actions = ['accept_pickup', 'mark_picked_up', 'cancel_pickup'];
if (!in_array($action, $valid_actions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    // Check if refund exists
    $stmt = $conn->prepare("SELECT r.*, o.rider_id as order_rider_id 
                            FROM refund_requests r 
                            JOIN orders o ON r.order_id = o.order_id 
                            WHERE r.id = ?");
    $stmt->bind_param('i', $refund_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $refund = $result->fetch_assoc();
    $stmt->close();

    if (!$refund) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Refund request not found']);
        exit;
    }

    switch ($action) {
        case 'accept_pickup':
            // Rider accepts the pickup assignment
            if (!in_array($refund['status'], ['requested', 'processing'])) {
                echo json_encode(['success' => false, 'message' => 'This refund cannot be accepted']);
                exit;
            }
            
            // Check if rider is authorized (must be the original order rider)
            if ($refund['order_rider_id'] != $rider_id && $refund['rider_pickup_id'] != $rider_id) {
                // Allow any rider to accept if not yet assigned
                if ($refund['rider_pickup_id'] !== null && $refund['rider_pickup_id'] != $rider_id) {
                    echo json_encode(['success' => false, 'message' => 'This pickup is already assigned to another rider']);
                    exit;
                }
            }
            
            $stmt = $conn->prepare("UPDATE refund_requests 
                                    SET status = 'pickup_scheduled', 
                                        rider_pickup_id = ?,
                                        updated_at = NOW() 
                                    WHERE id = ?");
            $stmt->bind_param('ii', $rider_id, $refund_id);
            
            if ($stmt->execute()) {
                // Insert customer notification (safe)
                safeInsertNotification($conn, 'customer', $refund['customer_id'], 'Pickup Scheduled', 'A rider has been assigned to pick up your return item.', 'refund');
                // Create admin notification (so admins can see pickup assignments)
                $adminTitle = "Refund pickup accepted for Order #{$refund['order_id']}";
                $adminBody = "Rider #{$rider_id} accepted pickup for refund request #{$refund_id}.";
                $adminRes = create_admin_notification($conn, 'refund_pickup_accepted', $adminTitle, $adminBody, 'high', ['order_id'=>$refund['order_id'],'refund_id'=>$refund_id], '/admin/orders.php?id=' . $refund['order_id']);
                if ($adminRes === false) {
                    error_log(date('[Y-m-d H:i:s] ') . "create_admin_notification failed (accept_pickup) for refund_id={$refund_id}, order_id={$refund['order_id']}\n", 3, __DIR__ . '/../../storage/refund_errors.log');
                }
                // Also update orders table refund tracking (best-effort)
                try {
                    $u = $conn->prepare("UPDATE orders SET refund_status = 'pickup_scheduled', refund_id = ? WHERE order_id = ?");
                    if ($u) {
                        $u->bind_param('ii', $refund_id, $refund['order_id']);
                        $u->execute();
                        $u->close();
                    }
                } catch (Exception $e) { /* ignore order update failures */ }
                
                echo json_encode(['success' => true, 'message' => 'Pickup accepted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update refund status']);
            }
            $stmt->close();
            break;

        case 'mark_picked_up':
            // Rider marks the item as picked up
            if ($refund['status'] !== 'pickup_scheduled') {
                echo json_encode(['success' => false, 'message' => 'Invalid status for this action']);
                exit;
            }
            
            // Verify this rider is assigned
            if ($refund['rider_pickup_id'] != $rider_id) {
                echo json_encode(['success' => false, 'message' => 'You are not assigned to this pickup']);
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE refund_requests 
                                    SET status = 'picked_up', 
                                        pickup_date = NOW(),
                                        updated_at = NOW() 
                                    WHERE id = ?");
            $stmt->bind_param('i', $refund_id);
            
            if ($stmt->execute()) {
                // Create notification for customer
                // Insert customer notification (safe)
                safeInsertNotification($conn, 'customer', $refund['customer_id'], 'Item Picked Up', 'Your return item has been picked up. Your refund will be processed soon.', 'refund');
                
                // Create notification for admin
                // Insert admin notification (safe). Build body text locally because CONCAT with placeholder can fail on column mismatch.
                $adminBodyShort = 'Refund request #' . $refund_id . ' item has been picked up by rider.';
                safeInsertNotification($conn, 'admin', 0, 'Refund Item Picked Up', $adminBodyShort, 'refund');
                // Also create admin notification entry for admin_notifications table
                $adminTitle2 = "Refund picked up for Order #{$refund['order_id']}";
                $adminBody2 = "Refund request #{$refund_id} for order #{$refund['order_id']} has been picked up by rider #{$rider_id}.";
                $adminRes2 = create_admin_notification($conn, 'refund_picked_up', $adminTitle2, $adminBody2, 'high', ['order_id'=>$refund['order_id'],'refund_id'=>$refund_id], '/admin/orders.php?id=' . $refund['order_id']);
                if ($adminRes2 === false) {
                    error_log(date('[Y-m-d H:i:s] ') . "create_admin_notification failed (mark_picked_up) for refund_id={$refund_id}, order_id={$refund['order_id']}\n", 3, __DIR__ . '/../../storage/refund_errors.log');
                }
                // Also update orders table refund tracking (best-effort)
                try {
                    $u = $conn->prepare("UPDATE orders SET refund_status = 'picked_up', refund_id = ? WHERE order_id = ?");
                    if ($u) {
                        $u->bind_param('ii', $refund_id, $refund['order_id']);
                        $u->execute();
                        $u->close();
                    }
                } catch (Exception $e) { /* ignore order update failures */ }
                
                echo json_encode(['success' => true, 'message' => 'Item marked as picked up']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
            $stmt->close();
            break;

        case 'cancel_pickup':
            // Rider cancels their pickup assignment
            if ($refund['rider_pickup_id'] != $rider_id) {
                echo json_encode(['success' => false, 'message' => 'You are not assigned to this pickup']);
                exit;
            }
            
            if ($refund['status'] === 'picked_up') {
                echo json_encode(['success' => false, 'message' => 'Cannot cancel after pickup']);
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE refund_requests 
                                    SET status = 'processing', 
                                        rider_pickup_id = NULL,
                                        updated_at = NOW() 
                                    WHERE id = ?");
            $stmt->bind_param('i', $refund_id);
            
            if ($stmt->execute()) {
                // Update orders table refund tracking (best-effort)
                try {
                    $u = $conn->prepare("UPDATE orders SET refund_status = 'processing', refund_id = ? WHERE order_id = ?");
                    if ($u) {
                        $u->bind_param('ii', $refund_id, $refund['order_id']);
                        $u->execute();
                        $u->close();
                    }
                } catch (Exception $e) { /* ignore */ }
                echo json_encode(['success' => true, 'message' => 'Pickup cancelled']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to cancel pickup']);
            }
            $stmt->close();
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
