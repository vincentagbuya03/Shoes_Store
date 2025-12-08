<?php
// API for orders management
// Suppress PHP errors/warnings from corrupting JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Ensure JSON content-type and protect against unexpected PHP errors
header('Content-Type: application/json; charset=utf-8');

// Start output buffering so we can return a clean JSON response on fatal errors
if (!ob_get_level()) ob_start();

// Custom error handler to catch non-fatal errors and log them (don't output to client)
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("orders_api.php warning [$errno]: $errstr in $errfile:$errline");
    return true;
});

// Shutdown handler: if a fatal error occurred, ensure a valid JSON response is returned
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err !== null) {
        error_log('orders_api.php fatal: ' . json_encode($err));
        // Clear any buffered output so we only return JSON
        while (ob_get_level()) ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Internal server error']);
    }
});

require_once __DIR__ . '/../db_connection.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

function jsonError($m){ echo json_encode(['success'=>false,'message'=>$m]); exit; }

// Helper: fetch a single associative row from a prepared statement
function fetch_stmt_assoc($stmt) {
    // prefer get_result when available (mysqlnd)
    if (method_exists($stmt, 'get_result')) {
        $res = $stmt->get_result();
        if ($res === false) return null;
        return $res->fetch_assoc();
    }
    // fallback: bind_result into an associative array
    $meta = $stmt->result_metadata();
    if (!$meta) return null;
    $fields = [];
    $row = [];
    while ($field = $meta->fetch_field()) {
        $fields[] = &$row[$field->name];
    }
    $meta->free();
    if (!$fields) return null;
    call_user_func_array([$stmt, 'bind_result'], $fields);
    if ($stmt->fetch()) {
        // copy values (they are references)
        $out = [];
        foreach ($row as $k => $v) $out[$k] = $v;
        return $out;
    }
    return null;
}

if ($action === 'list') {
    $q = $_GET['q'] ?? '';
    $sql = "SELECT o.order_id, o.total_amount, o.status, o.order_date, o.rider_id, o.delivery_proof, o.payment_method, o.payment_proof, c.name AS customer_name, c.email AS customer_email, r.name AS rider_name
            FROM orders o
            LEFT JOIN customer c ON o.customer_id = c.customer_id
            LEFT JOIN rider r ON o.rider_id = r.rider_id
            WHERE 1=1 ";
    $params = [];
    if ($q !== '') {
        $sql .= " AND (o.order_id = ? OR c.name LIKE ? OR o.status = ?)";
        $like = "%$q%";
        $params = [$q, $like, $q];
    }
    $sql .= " ORDER BY o.order_date DESC LIMIT 200";
    $stmt = $conn->prepare($sql);
    if (!$stmt) jsonError('Query error');
    if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $orders = $res->fetch_all(MYSQLI_ASSOC);
    // Attach refund request info for each order (if table exists)
    foreach ($orders as &$ord) {
        $ord['refund_status'] = null;
        $ord['refund_id'] = null;
        // safely check and query refund_requests if table exists
        try {
            $tbl = $conn->query("SHOW TABLES LIKE 'refund_requests'");
            if ($tbl && $tbl->num_rows > 0) {
                $rq = $conn->prepare("SELECT id, status FROM refund_requests WHERE order_id = ? ORDER BY id DESC LIMIT 1");
                if ($rq) {
                    $rq->bind_param('i', $ord['order_id']);
                    $rq->execute();
                    $rr = $rq->get_result();
                    if ($rr && ($rrow = $rr->fetch_assoc())) {
                        $ord['refund_id'] = (int)$rrow['id'];
                        $ord['refund_status'] = $rrow['status'];
                    }
                    $rq->close();
                }
            }
        } catch (Exception $e) {
            // ignore - leave refund fields null
        }
    }
    unset($ord);
    echo json_encode(['success'=>true,'orders'=>$orders]); exit;
}

if ($action === 'get') {
    $id = $_GET['id'] ?? '';
    if (!$id) jsonError('Missing id');
    $stmt = $conn->prepare("SELECT o.*, c.name AS customer_name, c.email, c.phone, r.rider_id, r.name AS rider_name FROM orders o LEFT JOIN customer c ON o.customer_id = c.customer_id LEFT JOIN rider r ON o.rider_id = r.rider_id WHERE o.order_id = ?");
    $stmt->bind_param('i',$id);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    if (!$order) jsonError('Order not found');

    // fetch items (assumes order_items table)
    $items = [];
    $it = $conn->prepare("SELECT oi.*, p.name FROM order_items oi LEFT JOIN product p ON oi.product_id = p.product_id WHERE oi.order_id = ?");
    $it->bind_param('i',$id);
    $it->execute();
    $ir = $it->get_result();
    while ($r = $ir->fetch_assoc()) $items[] = $r;

    $order['items'] = $items;
    // include refund request details if present
    try {
        $tbl = $conn->query("SHOW TABLES LIKE 'refund_requests'");
        if ($tbl && $tbl->num_rows > 0) {
            $fr = $conn->prepare("SELECT id, status, reason, created_at FROM refund_requests WHERE order_id = ? ORDER BY id DESC LIMIT 1");
            if ($fr) {
                $fr->bind_param('i', $id);
                $fr->execute();
                $fres = $fr->get_result();
                if ($fres && ($frow = $fres->fetch_assoc())) {
                    $order['refund_request'] = $frow;
                }
                $fr->close();
            }
        }
    } catch (Exception $e) {
        // ignore
    }
    echo json_encode(['success'=>true,'order'=>$order]); exit;
}

// Admin: process refund requests (approve/complete/reject)
if ($action === 'refund') {
    // only POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Invalid method');
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0; // order id
    $op = $_POST['op'] ?? 'approve'; // approve|complete|reject
    if (!$id) jsonError('Missing id');

    // find latest refund request for this order
    $tbl = $conn->query("SHOW TABLES LIKE 'refund_requests'");
    if (!($tbl && $tbl->num_rows > 0)) jsonError('No refund requests table');
    $fr = $conn->prepare("SELECT id, status FROM refund_requests WHERE order_id = ? ORDER BY id DESC LIMIT 1");
    if (!$fr) jsonError('Prepare failed');
    $fr->bind_param('i', $id);
    $fr->execute();
    $fres = $fr->get_result();
    $frow = $fres ? $fres->fetch_assoc() : null;
    $fr->close();
    if (!$frow) jsonError('No refund request for this order');
    $refundId = (int)$frow['id'];

    $newStatus = null;
    if ($op === 'approve') $newStatus = 'processing';
    elseif ($op === 'complete') $newStatus = 'resolved';
    elseif ($op === 'reject') $newStatus = 'rejected';
    else jsonError('Invalid op');

    $u = $conn->prepare('UPDATE refund_requests SET status = ? WHERE id = ?');
    if (!$u) jsonError('Prepare failed');
    $u->bind_param('si', $newStatus, $refundId);
    if (!$u->execute()) jsonError('Update failed');

    // notify admins for audit trail (wrapped in try-catch to prevent breaking JSON response)
    try {
        @require_once __DIR__ . '/../../inc/admin_notifications.php';
        $title = 'Refund ' . $newStatus . ' for Order #' . $id;
        $body = 'Admin set refund request ' . $refundId . ' to ' . $newStatus;
        @create_admin_notification($conn, 'refund_' . $newStatus, $title, $body, 'high', ['order_id'=>$id,'refund_id'=>$refundId], '/admin/orders.php?id=' . $id);
    } catch (Exception $e) {
        // Log but don't fail the response
        error_log('Refund notification error: ' . $e->getMessage());
    }

    echo json_encode(['success'=>true,'refund_id'=>$refundId,'status'=>$newStatus]); exit;
}

/* rider assignment endpoints removed — assign-rider UI was removed from admin pages */

if ($action === 'update_status') {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';
    $allowed = ['pending','confirmed','delivering','completed','cancelled'];
    if (!$id || !$status || !in_array($status,$allowed)) jsonError('Invalid input');
    // If confirming, attempt to assign an available rider automatically
    if ($status === 'confirmed') {
        // start transaction
        $conn->begin_transaction();
        try {
            // update status first
            $ust = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            if (!$ust) throw new Exception('Prepare failed');
            $ust->bind_param('si',$status,$id);
            if (!$ust->execute()) throw new Exception('Update failed');

            // find an available rider. If the schema has `is_active`, require it; otherwise fall back
            $rsel = null;
            $hasIsActive = false;
            try {
                $colCheck = $conn->query("SHOW COLUMNS FROM `rider` LIKE 'is_active'");
                if ($colCheck && $colCheck->num_rows > 0) $hasIsActive = true;
            } catch (Exception $e) {
                // ignore - we'll fall back to a simpler query
            }
            // Prefer riders who have fewer than 10 active assignments (pending/delivering)
            // This lets a rider have up to 10 concurrent deliveries even if already busy.
            if ($hasIsActive) {
                $rsel = $conn->prepare(
                    "SELECT r.rider_id, r.name, COALESCE(o.cnt,0) AS active_count\n" .
                    "FROM rider r\n" .
                    "LEFT JOIN (SELECT rider_id, COUNT(*) AS cnt FROM orders WHERE status IN ('pending','delivering') GROUP BY rider_id) o ON o.rider_id = r.rider_id\n" .
                    "WHERE r.is_active = 1 AND COALESCE(o.cnt,0) < 10\n" .
                    "ORDER BY COALESCE(o.cnt,0) ASC LIMIT 1"
                );
            } else {
                // older schema: no is_active column; pick any rider with < 10 active assignments
                $rsel = $conn->prepare(
                    "SELECT r.rider_id, r.name, COALESCE(o.cnt,0) AS active_count\n" .
                    "FROM rider r\n" .
                    "LEFT JOIN (SELECT rider_id, COUNT(*) AS cnt FROM orders WHERE status IN ('pending','delivering') GROUP BY rider_id) o ON o.rider_id = r.rider_id\n" .
                    "WHERE COALESCE(o.cnt,0) < 10\n" .
                    "ORDER BY COALESCE(o.cnt,0) ASC LIMIT 1"
                );
            }
            if (!$rsel) throw new Exception('Rider select failed');
            $rsel->execute();
            $rr = fetch_stmt_assoc($rsel);
            if ($rr && isset($rr['rider_id'])) {
                $rider_id = (int)$rr['rider_id'];
                // assign rider to order
                $aart = $conn->prepare("UPDATE orders SET rider_id = ? WHERE order_id = ?");
                if (!$aart) throw new Exception('Assign prepare failed');
                $aart->bind_param('ii', $rider_id, $id);
                if (!$aart->execute()) throw new Exception('Assign failed');

                // mark rider as busy
                $rupdate = $conn->prepare("UPDATE rider SET status = 'busy' WHERE rider_id = ?");
                if (!$rupdate) throw new Exception('Rider update failed');
                $rupdate->bind_param('i', $rider_id);
                if (!$rupdate->execute()) throw new Exception('Rider update failed');

                $conn->commit();
                echo json_encode(['success'=>true, 'assigned_rider' => ['rider_id'=>$rider_id, 'name'=>$rr['name']]]);
                exit;
            } else {
                // no available rider, just commit status change
                $conn->commit();
                echo json_encode(['success'=>true, 'assigned_rider' => null, 'message' => 'No available riders']);
                exit;
            }
        } catch (Exception $e) {
            $conn->rollback();
            jsonError('Assignment failed: ' . $e->getMessage());
        }
    }

    // If admin sets status back to 'pending', clear assigned rider and free them
    if ($status === 'pending') {
        $conn->begin_transaction();
        try {
            // fetch current assigned rider
            $s = $conn->prepare('SELECT rider_id FROM orders WHERE order_id = ? FOR UPDATE');
            if (!$s) throw new Exception('Prepare failed');
            $s->bind_param('i', $id);
            $s->execute();
            $r = $s->get_result()->fetch_assoc();
            $s->close();

            $assigned = isset($r['rider_id']) ? (int)$r['rider_id'] : 0;

            // clear rider assignment on order
            $u = $conn->prepare('UPDATE orders SET status = ?, rider_id = NULL WHERE order_id = ?');
            if (!$u) throw new Exception('Prepare failed');
            $u->bind_param('si', $status, $id);
            if (!$u->execute()) throw new Exception('Update failed');

            // if there was an assigned rider, only set them back to available
            // if they no longer have any active assignments (pending/delivering)
            if ($assigned > 0) {
                $cntStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE rider_id = ? AND status IN ('pending','delivering')");
                if ($cntStmt) {
                    $cntStmt->bind_param('i', $assigned);
                    $cntStmt->execute();
                    $cres = $cntStmt->get_result();
                    $crow = $cres ? $cres->fetch_assoc() : null;
                    $remaining = isset($crow['cnt']) ? (int)$crow['cnt'] : 0;
                    $cntStmt->close();
                } else {
                    // fallback: assume none (safe default)
                    $remaining = 0;
                }
                if ($remaining === 0) {
                    $ru = $conn->prepare("UPDATE rider SET status = 'available' WHERE rider_id = ?");
                    if (!$ru) throw new Exception('Rider update prepare failed');
                    $ru->bind_param('i', $assigned);
                    if (!$ru->execute()) throw new Exception('Rider update failed');
                    $ru->close();
                }
            }

            $conn->commit();
            echo json_encode(['success' => true, 'freed_rider' => $assigned > 0 ? $assigned : null]);
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            jsonError('Reverting to pending failed: ' . $e->getMessage());
        }
    }

    // default non-confirm status update (other statuses)
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    if (!$stmt) jsonError('Prepare failed: ' . ($conn->error ?? 'unknown'));
    $stmt->bind_param('si',$status,$id);
        if ($stmt->execute()) {
            // If the order was marked completed, only make the assigned rider available
            // if they have no other active assignments remaining.
            if ($status === 'completed') {
                try {
                    $s = $conn->prepare('SELECT rider_id FROM orders WHERE order_id = ?');
                    if ($s) {
                        $s->bind_param('i', $id);
                        $s->execute();
                        $rres = $s->get_result();
                        $row = $rres ? $rres->fetch_assoc() : null;
                        $s->close();
                        $assigned = isset($row['rider_id']) ? (int)$row['rider_id'] : 0;
                        if ($assigned > 0) {
                            $cntStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE rider_id = ? AND status IN ('pending','delivering')");
                            if ($cntStmt) {
                                $cntStmt->bind_param('i', $assigned);
                                $cntStmt->execute();
                                $cres = $cntStmt->get_result();
                                $crow = $cres ? $cres->fetch_assoc() : null;
                                $remaining = isset($crow['cnt']) ? (int)$crow['cnt'] : 0;
                                $cntStmt->close();
                            } else {
                                $remaining = 0;
                            }
                            if ($remaining === 0) {
                                $ru = $conn->prepare("UPDATE rider SET status = 'available' WHERE rider_id = ?");
                                if ($ru) {
                                    $ru->bind_param('i', $assigned);
                                    $ru->execute();
                                    $ru->close();
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    // non-fatal: log for debugging
                    error_log('Failed to mark rider available after completion: ' . $e->getMessage());
                }
            }
            echo json_encode(['success'=>true]);
        } else jsonError('Update failed: ' . ($stmt->error ?? $conn->error ?? 'unknown'));
    exit;
}

jsonError('Invalid action');