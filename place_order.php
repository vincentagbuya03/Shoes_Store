<?php
require_once 'db_connection.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}
$customer_id = (int)$_SESSION['customer_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Helper to respond with error
function resp_error($msg) {
    echo json_encode(['success' => false, 'error' => $msg]);
    exit();
}

// Collect input
$payment = isset($_POST['payment']) ? trim((string)$_POST['payment']) : 'gcash';
$delivery_address = isset($_POST['delivery_address']) ? trim((string)$_POST['delivery_address']) : '';
$gcash_number = isset($_POST['gcash_number']) ? trim((string)$_POST['gcash_number']) : '';
$gcash_ref = isset($_POST['gcash_ref']) ? trim((string)$_POST['gcash_ref']) : '';
$bank_name = isset($_POST['bank_name']) ? trim((string)$_POST['bank_name']) : '';
$bank_account_name = isset($_POST['bank_account_name']) ? trim((string)$_POST['bank_account_name']) : '';
$bank_account_number = isset($_POST['bank_account_number']) ? trim((string)$_POST['bank_account_number']) : '';
$selected = $_POST['selected_items'] ?? [];
if (!is_array($selected)) $selected = [$selected];
$selected = array_values(array_filter(array_map('intval', $selected)));

try {
    // If none selected, fetch from cart for this user
    if (empty($selected)) {
        $stmt = $conn->prepare("SELECT variant_id FROM cart WHERE customer_id = ?");
        if (!$stmt) throw new Exception('DB prepare failed (cart load)');
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) {
            $selected[] = (int)$row['variant_id'];
        }
        $stmt->close();
    }

    if (empty($selected)) {
        resp_error('No items selected');
    }

    // Get quantities from cart
    $placeholders = implode(',', array_fill(0, count($selected), '?'));
    $types = str_repeat('i', count($selected));
    $params = array_merge([$types, $customer_id], $selected); // we'll use a prepared statement with dynamic bind
    // Build query for cart quantities
    $sql = "SELECT variant_id, quantity FROM cart WHERE customer_id = ? AND variant_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('DB prepare failed (cart quantities)');
    // bind params: first param is types string that matches all (first is i then many i) - we need to build correct types and bind values
    // For mysqli bind_param we must supply types string followed by variables. We'll create array of references.
    $bind_params = [];
    $types_full = 'i' . $types; // account_id + variant ids
    $bind_params[] = & $types_full;
    $bind_params[] = & $customer_id;
    foreach ($selected as $k => $v) {
        $bind_params[] = & $selected[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_params);
    $stmt->execute();
    $res = $stmt->get_result();
    $quantities = [];
    while ($r = $res->fetch_assoc()) {
        $quantities[(int)$r['variant_id']] = (int)$r['quantity'];
    }
    $stmt->close();

    // As a safety, if any selected variant not found in cart, set quantity 1
    foreach ($selected as $vid) {
        if (!isset($quantities[$vid]) || $quantities[$vid] < 1) $quantities[$vid] = 1;
    }

    // Fetch product_variant info (price, product_id) and richer product snapshot
    $placeholders = implode(',', array_fill(0, count($selected), '?'));
    $types = str_repeat('i', count($selected));
    $sql = "SELECT pv.variant_id, pv.product_id, pv.price, p.name AS product_name, co.color_name, sz.size_name, pci.image_url
        FROM product_variant pv
        LEFT JOIN product p ON pv.product_id = p.product_id
        LEFT JOIN color co ON pv.color_id = co.color_id
        LEFT JOIN size sz ON pv.size_id = sz.size_id
        LEFT JOIN product_color_image pci ON pv.product_id = pci.product_id AND pv.color_id = pci.color_id AND pci.sort_order = 1
        WHERE pv.variant_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('DB prepare failed (variant fetch)');
    // bind
    $bind_params = [];
    $bind_params[] = & $types;
    foreach ($selected as $k => $v) $bind_params[] = & $selected[$k];
    call_user_func_array([$stmt, 'bind_param'], $bind_params);
    $stmt->execute();
    $res = $stmt->get_result();
    $variants = [];
    while ($r = $res->fetch_assoc()) {
        $variants[(int)$r['variant_id']] = [
            'product_id' => (int)$r['product_id'],
            'price' => (float)$r['price'],
            'product_name' => isset($r['product_name']) ? $r['product_name'] : '',
            'color_name' => isset($r['color_name']) ? $r['color_name'] : '',
            'size_name' => isset($r['size_name']) ? $r['size_name'] : '',
            'image_url' => isset($r['image_url']) ? $r['image_url'] : ''
        ];
    }
    $stmt->close();

    if (empty($variants)) resp_error('Unable to fetch product data');

    // Compute totals
    $subtotal = 0.0;
    foreach ($selected as $vid) {
        $price = isset($variants[$vid]['price']) ? (float)$variants[$vid]['price'] : 0.0;
        $qty = isset($quantities[$vid]) ? (int)$quantities[$vid] : 1;
        $subtotal += $price * $qty;
    }
    
    // Add 12% VAT to get the final total
    $vat_rate = 0.12;
    $vat_amount = $subtotal * $vat_rate;
    $total_amount = $subtotal + $vat_amount;

    // Begin transaction (track started state for compatibility)
    $startedTx = false;
    if (method_exists($conn, 'begin_transaction')) {
        $conn->begin_transaction();
        $startedTx = true;
    } else {
        // older mysqli may not expose begin_transaction; attempt to use autocommit=false as fallback
        if (method_exists($conn, 'autocommit')) {
            $conn->autocommit(false);
            $startedTx = true;
        }
    }

    // Insert order
    $payment_details = json_encode([
        'payment' => $payment,
        'gcash_number' => $gcash_number,
        'gcash_ref' => $gcash_ref,
        'bank_name' => $bank_name,
        'bank_account_name' => $bank_account_name,
        'bank_account_number' => $bank_account_number
    ]);
    // Detect which payment column exists in the orders table and build INSERT accordingly
    function find_first_existing_column($conn, $candidates) {
        foreach ($candidates as $c) {
            $q = $conn->query("SHOW COLUMNS FROM `orders` LIKE '" . $conn->real_escape_string($c) . "'");
            if ($q && $q->num_rows) return $c;
        }
        return null;
    }

    $payment_col = find_first_existing_column($conn, ['payment_details', 'payment_meta', 'payment_json', 'payment']);
    $proof_col = find_first_existing_column($conn, ['payment_proof','payment_proof_path','proof_path','payment_receipt']);
    $delivery_col = find_first_existing_column($conn, ['delivery_address','address','shipping_address','ship_address']);

    // detect common timestamp column names (created_at / order_date etc.) and include only if present
    $time_col = find_first_existing_column($conn, ['created_at', 'order_date', 'date_created', 'created_on', 'order_created_at', 'created']);
    $time_col_sql = $time_col ? (", {$time_col}") : '';
    $time_val_sql = $time_col ? (", NOW()") : '';

    // Build insert columns dynamically
    $cols = ['customer_id','total_amount','status','payment_method','delivery_address','created_at'];
    $placeholders = ['?','?','\'pending\'','?','?','NOW()'];
    // We'll construct a parameterized query; if payment_col exists we include it
    $types = 'id'; // customer_id (i), total_amount (d)
    $bindValues = [];
    // base values
    $bindValues[] = &$customer_id;
    $bindValues[] = &$total_amount;

    if ($payment_col) {
        // insert payment column between payment_method and delivery_address for readability
        if ($delivery_col) {
            $order_insert_sql = "INSERT INTO orders (customer_id, total_amount, status, payment_method, {$payment_col}, {$delivery_col}{$time_col_sql}) VALUES (?, ?, 'pending', ?, ?, ?{$time_val_sql})";
            $stmt = $conn->prepare($order_insert_sql);
            if (!$stmt) throw new Exception('DB prepare failed (order insert with payment+delivery)');
            $stmt->bind_param('idsss', $customer_id, $total_amount, $payment, $payment_details, $delivery_address);
            if (!$stmt->execute()) { $err = $conn->error; $stmt->close(); throw new Exception('DB execute failed (order insert with payment+delivery): ' . $err); }
            $order_id = $stmt->insert_id;
            $stmt->close();
        } else {
            // delivery column missing: insert without delivery
            $order_insert_sql = "INSERT INTO orders (customer_id, total_amount, status, payment_method, {$payment_col}{$time_col_sql}) VALUES (?, ?, 'pending', ?, ?{$time_val_sql})";
            $stmt = $conn->prepare($order_insert_sql);
            if (!$stmt) throw new Exception('DB prepare failed (order insert with payment only)');
            $stmt->bind_param('idss', $customer_id, $total_amount, $payment, $payment_details);
            if (!$stmt->execute()) { $err = $conn->error; $stmt->close(); throw new Exception('DB execute failed (order insert with payment only): ' . $err); }
            $order_id = $stmt->insert_id;
            $stmt->close();
        }
    } else {
        // no payment column found — insert without it
        if ($delivery_col) {
            $order_insert_sql = "INSERT INTO orders (customer_id, total_amount, status, payment_method, {$delivery_col}{$time_col_sql}) VALUES (?, ?, 'pending', ?, ?{$time_val_sql})";
            $stmt = $conn->prepare($order_insert_sql);
            if (!$stmt) throw new Exception('DB prepare failed (order insert no payment col with delivery)');
            $stmt->bind_param('ids', $customer_id, $total_amount, $payment, $delivery_address);
            if (!$stmt->execute()) { $stmt->close(); throw new Exception('DB execute failed (order insert no payment col with delivery): ' . $conn->error); }
            $order_id = $stmt->insert_id;
            $stmt->close();
        } else {
            // neither payment nor delivery columns exist
            $order_insert_sql = "INSERT INTO orders (customer_id, total_amount, status, payment_method{$time_col_sql}) VALUES (?, ?, 'pending', ?{$time_val_sql})";
            $stmt = $conn->prepare($order_insert_sql);
            if (!$stmt) throw new Exception('DB prepare failed (order insert minimal)');
            $stmt->bind_param('ids', $customer_id, $total_amount, $payment);
            if (!$stmt->execute()) { $stmt->close(); throw new Exception('DB execute failed (order insert minimal): ' . $conn->error); }
            $order_id = $stmt->insert_id;
            $stmt->close();
        }
    }

    // Insert order items into order_items (or order_item) - try both table names
    $order_items_table = null;
    // detect which table exists by attempting a harmless prepare
    $tryTables = ['order_items', 'order_item', 'orders_item'];
    foreach ($tryTables as $t) {
        $ptest = $conn->prepare("INSERT INTO {$t} (order_id, variant_id, product_id, quantity, price) VALUES (?, ?, ?, ?, ?)");
        if ($ptest) { $order_items_table = $t; $ptest->close(); break; }
    }
    if ($order_items_table === null) {
        // No dedicated items table: skip item insertion but continue (order record exists)
    } else {
        // helper to detect additional columns in the order items table
        function find_first_existing_column_in_table($conn, $table, $candidates) {
            foreach ($candidates as $c) {
                $q = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "` LIKE '" . $conn->real_escape_string($c) . "'");
                if ($q && $q->num_rows) return $c;
            }
            return null;
        }

        // detect optional columns to store product snapshot
        $item_meta_col = find_first_existing_column_in_table($conn, $order_items_table, ['item_meta','product_snapshot','product_details','details','meta']);
        $prod_name_col = find_first_existing_column_in_table($conn, $order_items_table, ['product_name','item_name','name']);
        $image_col = find_first_existing_column_in_table($conn, $order_items_table, ['image_url','product_image','image','img']);
        $color_col = find_first_existing_column_in_table($conn, $order_items_table, ['color_name','color','variant_color']);
        $size_col = find_first_existing_column_in_table($conn, $order_items_table, ['size','size_name','variant_size']);

        // Build dynamic insert columns
        $baseCols = ['order_id','variant_id','product_id','quantity','price'];
        $extraCols = [];
        if ($prod_name_col) $extraCols[] = $prod_name_col;
        if ($image_col) $extraCols[] = $image_col;
        if ($color_col) $extraCols[] = $color_col;
        if ($size_col) $extraCols[] = $size_col;
        if ($item_meta_col) $extraCols[] = $item_meta_col;

        $allCols = array_merge($baseCols, $extraCols);
        $colList = implode(', ', $allCols);
        $placeholders = implode(', ', array_fill(0, count($allCols), '?'));
        $insert_sql = "INSERT INTO {$order_items_table} ({$colList}) VALUES ({$placeholders})";
        $stmt = $conn->prepare($insert_sql);
        if (!$stmt) throw new Exception('DB prepare failed (order items insert dynamic) - ' . $conn->error);

        foreach ($selected as $vid) {
            $pid = isset($variants[$vid]['product_id']) ? $variants[$vid]['product_id'] : 0;
            $unit_price = isset($variants[$vid]['price']) ? (float)$variants[$vid]['price'] : 0.0;
            $qty = isset($quantities[$vid]) ? (int)$quantities[$vid] : 1;
            $item_total = $unit_price * $qty; // Store total price (price × quantity) instead of unit price

            // prepare values in order
            $vals = [];
            $vals[] = $order_id;
            $vals[] = $vid;
            $vals[] = $pid;
            $vals[] = $qty;
            $vals[] = $item_total;
            // extras
            $snapshot = [];
            if ($prod_name_col) { $vals[] = (string)($variants[$vid]['product_name'] ?? ''); $snapshot['product_name'] = $variants[$vid]['product_name'] ?? ''; }
            if ($image_col) { $vals[] = (string)($variants[$vid]['image_url'] ?? ''); $snapshot['image_url'] = $variants[$vid]['image_url'] ?? ''; }
            if ($color_col) { $vals[] = (string)($variants[$vid]['color_name'] ?? ''); $snapshot['color_name'] = $variants[$vid]['color_name'] ?? ''; }
            if ($size_col) { $vals[] = (string)($variants[$vid]['size_name'] ?? ''); $snapshot['size_name'] = $variants[$vid]['size_name'] ?? ''; }
            if ($item_meta_col) { $metaJson = json_encode(array_filter([
                'product_id' => $pid,
                'variant_id' => $vid,
                'product_name' => $variants[$vid]['product_name'] ?? '',
                'color_name' => $variants[$vid]['color_name'] ?? '',
                'size_name' => $variants[$vid]['size_name'] ?? '',
                'image_url' => $variants[$vid]['image_url'] ?? '',
                'unit_price' => $unit_price,
                'quantity' => $qty,
                'total_price' => $item_total
            ]));
                $vals[] = $metaJson;
            }

            // Build types string
            $types = '';
            foreach ($allCols as $c) {
                if (in_array($c, ['order_id','variant_id','product_id','quantity'])) $types .= 'i';
                elseif ($c === 'price') $types .= 'd';
                else $types .= 's';
            }

            // bind params by reference
            $bind_params = [];
            $bind_params[] = & $types;
            $refs = [];
            foreach ($vals as $k => $v) {
                $refs[$k] = & $vals[$k];
                $bind_params[] = & $refs[$k];
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_params);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('DB execute failed (order items dynamic): ' . $conn->error);
            }
        }
        $stmt->close();
    }

    // Handle file upload (payment_proof)
    $uploaded_path = '';
    if (!empty($_FILES['payment_proof']) && is_uploaded_file($_FILES['payment_proof']['tmp_name'])) {
        $file = $_FILES['payment_proof'];
        // Basic checks
        $allowed_types = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            // Non-fatal: skip storing but continue
        } else {
            // Save payment proof into admin upload directory so admin UI can serve it
            $upload_dir = __DIR__ . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'payment-proof';
            if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe_ext = preg_replace('/[^a-z0-9]/i','', $ext) ?: 'jpg';
            $basename = 'proof_' . $order_id . '_' . time() . '.' . $safe_ext;
            $dest = $upload_dir . DIRECTORY_SEPARATOR . $basename;
            if (@move_uploaded_file($file['tmp_name'], $dest)) {
                $uploaded_path = 'upload/payment-proof/' . $basename;
                $updated = false;
                if (!empty($proof_col)) {
                    $col = $proof_col;
                    $ustmt = $conn->prepare("UPDATE `orders` SET `{$col}` = ? WHERE order_id = ?");
                    if ($ustmt) {
                        $ustmt->bind_param('si', $uploaded_path, $order_id);
                        $ustmt->execute();
                        $ustmt->close();
                        $updated = true;
                    }
                } else {
                    $tryCols = ['payment_proof','payment_proof_path','proof_path','payment_receipt'];
                    foreach ($tryCols as $col) {
                        // check column existence explicitly to avoid triggering SQL errors
                        $qc = $conn->query("SHOW COLUMNS FROM `orders` LIKE '" . $conn->real_escape_string($col) . "'");
                        if ($qc && $qc->num_rows) {
                            $ustmt = $conn->prepare("UPDATE `orders` SET `" . $conn->real_escape_string($col) . "` = ? WHERE order_id = ?");
                            if ($ustmt) {
                                $ustmt->bind_param('si', $uploaded_path, $order_id);
                                $ustmt->execute();
                                $ustmt->close();
                                $updated = true;
                                break;
                            }
                        }
                    }
                }

                // Persist delivery latitude/longitude into orders table if provided and columns exist
                $provided_lat = isset($_POST['delivery_lat']) && is_numeric($_POST['delivery_lat']) ? (float)$_POST['delivery_lat'] : null;
                $provided_lng = isset($_POST['delivery_lng']) && is_numeric($_POST['delivery_lng']) ? (float)$_POST['delivery_lng'] : null;
                if ($provided_lat !== null || $provided_lng !== null) {
                    $colsRes = $conn->query("SHOW COLUMNS FROM `orders`");
                    $orderCols = [];
                    if ($colsRes) {
                        while ($cr = $colsRes->fetch_assoc()) $orderCols[] = strtolower($cr['Field']);
                        $colsRes->close();
                    }
                    $updates = [];
                    $params = [];
                    $types = '';
                    if ($provided_lat !== null) {
                        if (in_array('delivery_lat', $orderCols)) { $updates[] = '`delivery_lat` = ?'; $types .= 'd'; $params[] = $provided_lat; }
                        elseif (in_array('latitude', $orderCols)) { $updates[] = '`latitude` = ?'; $types .= 'd'; $params[] = $provided_lat; }
                        elseif (in_array('lat', $orderCols)) { $updates[] = '`lat` = ?'; $types .= 'd'; $params[] = $provided_lat; }
                    }
                    if ($provided_lng !== null) {
                        if (in_array('delivery_lng', $orderCols)) { $updates[] = '`delivery_lng` = ?'; $types .= 'd'; $params[] = $provided_lng; }
                        elseif (in_array('longitude', $orderCols)) { $updates[] = '`longitude` = ?'; $types .= 'd'; $params[] = $provided_lng; }
                        elseif (in_array('lng', $orderCols)) { $updates[] = '`lng` = ?'; $types .= 'd'; $params[] = $provided_lng; }
                    }
                    if (!empty($updates)) {
                        $sql = 'UPDATE `orders` SET ' . implode(', ', $updates) . ' WHERE order_id = ?';
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            // bind params
                            $types_full = $types . 'i';
                            $bindArr = [];
                            $bindArr[] = & $types_full;
                            foreach ($params as $k => $v) $bindArr[] = & $params[$k];
                            $bindArr[] = & $order_id;
                            call_user_func_array([$stmt, 'bind_param'], $bindArr);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
                // ignore update result
            }
        }
    }

    // Remove items from cart
    if (!empty($selected)) {
        $placeholders = implode(',', array_fill(0, count($selected), '?'));
        $types = str_repeat('i', count($selected));
        $sql = "DELETE FROM cart WHERE customer_id = ? AND variant_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $bind_params = [];
            $types_full = 'i' . $types;
            $bind_params[] = & $types_full;
            $bind_params[] = & $customer_id;
            foreach ($selected as $k => $v) $bind_params[] = & $selected[$k];
            call_user_func_array([$stmt, 'bind_param'], $bind_params);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->commit();

    // Create admin notification for new order
    try {
        require_once __DIR__ . '/inc/admin_notifications.php';
        $title = "Order #{$order_id} placed";
        $body = '₱' . number_format($total_amount,2) . " — " . count($selected) . " item(s)";
        $meta = ['order_id' => $order_id, 'customer_id' => $customer_id, 'amount' => $total_amount];
        $url = '/admin/orders.php?id=' . $order_id;
        @create_admin_notification($conn, 'new_order', $title, $body, 'high', $meta, $url);
    } catch (Exception $e) {
        error_log('Failed to create new order notification: ' . $e->getMessage());
    }

    echo json_encode(['success' => true, 'order_id' => $order_id, 'redirect' => 'order.php?order_id=' . $order_id]);
    exit();

} catch (Exception $ex) {
    // Rollback if we started a transaction (avoid using undefined mysqli::$in_transaction)
    if (!empty($startedTx)) {
        if (method_exists($conn, 'rollback')) {
            $conn->rollback();
        } elseif (method_exists($conn, 'autocommit')) {
            // attempt to re-enable autocommit as a fallback
            $conn->autocommit(true);
        }
    }
    resp_error($ex->getMessage());
}
?>