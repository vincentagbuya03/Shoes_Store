<?php
require_once 'db_connection.php';
require_once 'inc/store_settings.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];
$customer_name = htmlspecialchars($_SESSION['customer_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8');

// Compute cart count for header badge
$cart_count = 0;
$cq = $conn->prepare('SELECT COUNT(*) AS total FROM cart WHERE customer_id = ?');
if ($cq) {
    $cq->bind_param('i', $customer_id);
    $cq->execute();
    $cres = $cq->get_result();
    if ($cres && ($crow = $cres->fetch_assoc())) { $cart_count = (int)($crow['total'] ?? 0); }
    $cq->close();
}

// Determine which timestamp column exists in the `orders` table and fallback if missing
$created_col = 'created_at';
$check = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'created_at'");
if (!($check && $check->num_rows > 0)) {
    $found = false;
    $alts = ['created','created_on','order_date','date_created','timestamp'];
    foreach ($alts as $c) {
        $r = $conn->query("SHOW COLUMNS FROM `orders` LIKE '" . $conn->real_escape_string($c) . "'");
        if ($r && $r->num_rows > 0) { $created_col = $c; $found = true; break; }
    }
    if (!$found) {
        // As a last resort use order_id so the query still runs
        $created_col = 'order_id';
    }
}

// Determine which order-number-like column exists and alias it as order_number
$order_number_col = 'order_number';
$chk2 = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'order_number'");
if (!($chk2 && $chk2->num_rows > 0)) {
    $found2 = false;
    $alts2 = ['order_no','order_ref','invoice_number','reference','external_id','ref_number'];
    foreach ($alts2 as $c) {
        $r2 = $conn->query("SHOW COLUMNS FROM `orders` LIKE '" . $conn->real_escape_string($c) . "'");
        if ($r2 && $r2->num_rows > 0) { $order_number_col = $c; $found2 = true; break; }
    }
    if (!$found2) {
        // Fallback to order_id so UI still has a stable identifier
        $order_number_col = 'order_id';
    }
}

// Fetch orders for the logged-in customer
$orders = [];

// Detect optional columns and safely build SELECT
$shipping_col = null;
$payment_col = null;
$chk_ship = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'shipping_address'");
if ($chk_ship && $chk_ship->num_rows > 0) { $shipping_col = 'shipping_address'; }
else {
    $alts_ship = ['shipping','address','delivery_address','delivery_addr','shipping_addr'];
    foreach ($alts_ship as $c) {
        $r = $conn->query("SHOW COLUMNS FROM `orders` LIKE '" . $conn->real_escape_string($c) . "'");
        if ($r && $r->num_rows > 0) { $shipping_col = $c; break; }
    }
}

$chk_pay = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'payment_method'");
if ($chk_pay && $chk_pay->num_rows > 0) { $payment_col = 'payment_method'; }
else {
    $alts_pay = ['payment','payment_type','payment_method_name','pay_method'];
    foreach ($alts_pay as $c) {
        $r = $conn->query("SHOW COLUMNS FROM `orders` LIKE '" . $conn->real_escape_string($c) . "'");
        if ($r && $r->num_rows > 0) { $payment_col = $c; break; }
    }
}

$select_cols = "o.order_id,\n        o.`{$order_number_col}` AS order_number,\n        o.total_amount,\n        o.status,\n        o.`{$created_col}` AS created_at";

if ($shipping_col) {
    $select_cols .= ",\n        COALESCE(o.`{$shipping_col}`, '') AS shipping_address";
} else {
    $select_cols .= ",\n        '' AS shipping_address";
}

if ($payment_col) {
    $select_cols .= ",\n        COALESCE(o.`{$payment_col}`, '') AS payment_method";
} else {
    $select_cols .= ",\n        '' AS payment_method";
}

$order_sql = "SELECT \n    {$select_cols}\n    FROM orders o\n    WHERE o.customer_id = ?\n    ORDER BY o.`{$created_col}` DESC";
$order_stmt = $conn->prepare($order_sql);
if ($order_stmt) {
    $order_stmt->bind_param('i', $customer_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    while ($row = $order_result->fetch_assoc()) {
        // Cast/normalize
        $row['order_id'] = (int)$row['order_id'];
        $row['total_amount'] = number_format((float)$row['total_amount'], 2);
        $row['created_at'] = $row['created_at'];
        $row['status'] = $row['status'];
        
        // Check for existing refund request
        $row['refund_status'] = null;
        try {
            $refund_check = $conn->prepare("SELECT status FROM refund_requests WHERE order_id = ? ORDER BY id DESC LIMIT 1");
            if ($refund_check) {
                $refund_check->bind_param('i', $row['order_id']);
                $refund_check->execute();
                $refund_result = $refund_check->get_result();
                if ($refund_row = $refund_result->fetch_assoc()) {
                    $row['refund_status'] = $refund_row['status'];
                }
                $refund_check->close();
            }
        } catch (Exception $e) { /* ignore */ }
        
        $orders[] = $row;
    }
    $order_stmt->close();
}

// Helper to fetch items for an order (returns array)
function get_order_items($conn, $order_id) {
    $items = [];
        // Inspect columns in order_items so we can safely reference them
        $colsRes = $conn->query("SHOW COLUMNS FROM `order_items`");
        $cols = [];
        if ($colsRes) {
            while ($c = $colsRes->fetch_assoc()) { $cols[] = $c['Field']; }
        }

        // If order_items doesn't have an order_id column, we cannot reliably fetch items
        if (!in_array('order_id', $cols, true)) {
            return $items;
        }

        $has = function($name) use ($cols) { return in_array($name, $cols, true); };

        $select = [];
        // order_item_id
        if ($has('order_item_id')) { $select[] = 'oi.order_item_id AS order_item_id'; }
        else { $select[] = 'NULL AS order_item_id'; }

        // product_id
        if ($has('product_id')) { $select[] = 'oi.product_id AS product_id'; }
        else { $select[] = 'NULL AS product_id'; }

        // product_name (prefer oi.product_name, else product.name when product_id exists)
        if ($has('product_name') && $has('product_id')) {
            $select[] = 'COALESCE(oi.product_name, p.name, "") AS product_name';
        } elseif ($has('product_name')) {
            $select[] = 'COALESCE(oi.product_name, "") AS product_name';
        } elseif ($has('product_id')) {
            $select[] = 'COALESCE(p.name, "") AS product_name';
        } else {
            $select[] = '"" AS product_name';
        }

        // quantity
        if ($has('quantity')) { $select[] = 'oi.quantity AS quantity'; }
        else { $select[] = '1 AS quantity'; }

        // price (stored as total: unit_price * quantity)
        if ($has('price')) { $select[] = 'oi.price AS unit_price'; }
        else { $select[] = '0.00 AS unit_price'; }

        // image_url via product_color_image when product_id and color_id exist
        $joinProduct = $has('product_id');
        $joinPci = ($has('product_id') && $has('color_id'));
        if ($joinPci) {
            $select[] = "COALESCE(pci.image_url, 'upload/product-image/placeholder.png') AS image_url";
        } else {
            $select[] = "'upload/product-image/placeholder.png' AS image_url";
        }

        // color_name
        if ($has('color_id')) {
            $select[] = "COALESCE(c.color_name, '') AS color_name";
        } else {
            $select[] = "'' AS color_name";
        }

        // size
        if ($has('size')) { $select[] = "COALESCE(oi.size, '') AS size"; }
        else { $select[] = "'' AS size"; }

        $sql = "SELECT \n            " . implode(",\n            ", $select) . "\n        FROM order_items oi\n    ";

        if ($joinProduct) {
            $sql .= " LEFT JOIN product p ON oi.product_id = p.product_id\n    ";
        }
        if ($joinPci) {
            $sql .= " LEFT JOIN product_color_image pci ON oi.product_id = pci.product_id AND pci.color_id = oi.color_id AND pci.sort_order = 1\n    ";
        }
        if ($has('color_id')) {
            $sql .= " LEFT JOIN color c ON oi.color_id = c.color_id\n    ";
        }

        $sql .= " WHERE oi.order_id = ?";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                // Normalize types safely
                $r['order_item_id'] = isset($r['order_item_id']) ? (int)$r['order_item_id'] : 0;
                $r['product_id'] = isset($r['product_id']) && $r['product_id'] !== null ? (int)$r['product_id'] : 0;
                $r['quantity'] = isset($r['quantity']) ? (int)$r['quantity'] : 1;
                $r['unit_price'] = isset($r['unit_price']) ? number_format((float)$r['unit_price'], 2) : number_format(0,2);
                $items[] = $r;
            }
            $stmt->close();
        }
    return $items;
}

// Preload items for each order to avoid multiple DB roundtrips in JS later
foreach ($orders as &$order) {
    $order['items'] = get_order_items($conn, $order['order_id']);
}
unset($order);

// Count orders by status for stats
$stats = [
    'total' => count($orders),
    'pending' => 0,
    'delivering' => 0,
    'completed' => 0,
    'cancelled' => 0
];
foreach ($orders as $o) {
    $s = strtolower($o['status'] ?? 'pending');
    if ($s === 'pending' || $s === 'confirmed') $stats['pending']++;
    elseif (in_array($s, ['shipped','delivering','out_for_delivery','in_transit'])) $stats['delivering']++;
    elseif ($s === 'completed' || $s === 'delivered') $stats['completed']++;
    elseif ($s === 'cancelled') $stats['cancelled']++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>My Orders — <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="asset/style/beautiful-ui.css">
    <link rel="stylesheet" href="asset/style/animations.css">
    <?php echo getStoreThemeCSS(); ?>
    <style>
        :root {
            /* Use store theme colors */
            --primary: var(--store-primary, #6366f1);
            --primary-dark: var(--store-secondary, #4f46e5);
            --primary-light: var(--store-accent, #818cf8);
            --primary-bg: var(--store-primary-light, rgba(99, 102, 241, 0.08));
            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.1);
            --warning: #f59e0b;
            --warning-bg: rgba(245, 158, 11, 0.1);
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.1);
            --info: #3b82f6;
            --info-bg: rgba(59, 130, 246, 0.1);
            --text-primary: var(--store-text, #1f2937);
            --text-secondary: var(--store-text-secondary, #6b7280);
            --text-muted: var(--store-text-secondary, #9ca3af);
            --bg-primary: var(--store-card-bg, #ffffff);
            --bg-secondary: var(--store-bg, #f9fafb);
            --bg-tertiary: var(--store-bg-secondary, #f3f4f6);
            --border-color: var(--store-border, #e5e7eb);
            --border-light: var(--store-border, #f3f4f6);
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -1px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --transition: all 0.2s ease;
        }

        /* ===== PAGE ADJUSTMENTS FOR NAV-MODERN ===== */
        body {
            padding-top: 0;
        }
        
        .announcement-bar {
            text-align: center;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .announcement-bar p {
            margin: 0;
        }

        * { box-sizing: border-box; }

        body {
            font-family: var(--store-font, 'Inter', -apple-system, BlinkMacSystemFont, sans-serif);
            background: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* ===== MAIN CONTAINER ===== */
        .orders-page {
            max-width: 1280px;
            margin: 0 auto;
            padding: 2rem 1.5rem 4rem;
        }

        /* ===== HERO HEADER ===== */
        .orders-hero {
            background: var(--store-gradient, linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%));
            border-radius: var(--radius-xl);
            padding: 3rem 2.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 40px -10px var(--primary);
        }

        .orders-hero::before {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 60%);
            pointer-events: none;
            animation: float 6s ease-in-out infinite;
        }

        .orders-hero::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -50px;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
            pointer-events: none;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .hero-text {
            flex: 1;
            min-width: 250px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 1rem;
        }

        .hero-badge i {
            font-size: 0.9rem;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
            margin: 0 0 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .hero-title i {
            font-size: 2rem;
            opacity: 0.9;
        }

        .hero-title svg {
            width: 36px;
            height: 36px;
            opacity: 0.9;
        }

        .hero-subtitle {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            margin: 0;
            max-width: 400px;
            line-height: 1.6;
        }

        .hero-illustration {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .hero-icon-box {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: bounce 2s ease-in-out infinite;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .hero-icon-box i {
            font-size: 2.5rem;
            color: #fff;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .orders-hero {
                padding: 2rem 1.5rem;
            }
            
            .hero-title {
                font-size: 1.75rem;
            }
            
            .hero-illustration {
                display: none;
            }
        }

        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .stat-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
        }

        .stat-icon svg { width: 20px; height: 20px; }

        .stat-icon.total { background: var(--primary-bg); color: var(--primary); }
        .stat-icon.pending { background: var(--warning-bg); color: var(--warning); }
        .stat-icon.delivering { background: var(--info-bg); color: var(--info); }
        .stat-icon.completed { background: var(--success-bg); color: var(--success); }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* ===== CONTROLS BAR ===== */
        .controls-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 280px;
            position: relative;
        }

        .search-box svg {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: var(--text-muted);
            pointer-events: none;
        }

        .search-box input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-family: inherit;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-bg);
        }

        .search-box input::placeholder {
            color: var(--text-muted);
        }

        .filter-select {
            padding: 0.875rem 2.5rem 0.875rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-family: inherit;
            background: var(--bg-primary) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") no-repeat right 1rem center;
            color: var(--text-primary);
            cursor: pointer;
            transition: var(--transition);
            appearance: none;
            min-width: 160px;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-bg);
        }

        /* ===== ORDERS LIST ===== */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        /* ===== ORDER CARD ===== */
        .order-card {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: var(--transition);
        }

        .order-card:hover {
            box-shadow: var(--shadow-lg);
            border-color: var(--border-light);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-light);
            background: var(--bg-tertiary);
        }

        .order-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .order-number {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .order-number svg {
            width: 16px;
            height: 16px;
            color: var(--primary);
        }

        .order-date {
            font-size: 0.85rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .order-date svg {
            width: 14px;
            height: 14px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge svg {
            width: 14px;
            height: 14px;
        }

        .status-pending {
            background: var(--warning-bg);
            color: var(--warning);
        }

        .status-confirmed {
            background: var(--info-bg);
            color: var(--info);
        }

        .status-shipped, .status-delivering, .status-in_transit, .status-out_for_delivery {
            background: var(--info-bg);
            color: var(--info);
        }

        .status-delivered, .status-completed {
            background: var(--success-bg);
            color: var(--success);
        }

        .status-cancelled {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .status-refund_requested {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.15) 0%, rgba(139, 92, 246, 0.1) 100%);
            color: #7c3aed;
        }

        /* Order Content */
        .order-content {
            padding: 1.5rem;
        }

        .order-items-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .order-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            align-items: center;
        }

        .item-image {
            width: 72px;
            height: 72px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background: var(--bg-tertiary);
            flex-shrink: 0;
        }

        .item-details {
            flex: 1;
            min-width: 0;
        }

        .item-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .item-variant {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.35rem;
        }

        .item-price {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Order Footer */
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border-light);
            flex-wrap: wrap;
            gap: 1rem;
        }

        .order-shipping {
            font-size: 0.9rem;
            color: var(--text-secondary);
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            max-width: 300px;
        }

        .order-shipping svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            margin-top: 2px;
            color: var(--text-muted);
        }

        .order-total {
            text-align: right;
        }

        .total-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        .total-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Order Actions */
        .order-actions {
            display: flex;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: var(--bg-tertiary);
            border-top: 1px solid var(--border-light);
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.15rem;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: none;
            transition: var(--transition);
        }

        .btn svg {
            width: 16px;
            height: 16px;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--bg-primary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--bg-secondary);
            border-color: var(--text-muted);
        }

        .btn-success {
            background: var(--success);
            color: #fff;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger);
            color: #fff;
        }

        .btn-warning {
            background: var(--warning-bg);
            color: #b45309;
        }

        .btn-warning:hover {
            background: var(--warning);
            color: #fff;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            background: var(--bg-tertiary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .empty-icon svg {
            width: 36px;
            height: 36px;
            color: var(--text-muted);
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 0.5rem;
        }

        .empty-text {
            color: var(--text-secondary);
            margin: 0 0 1.5rem;
        }

        /* ===== MODALS ===== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            padding: 1rem;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-content {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            max-width: 600px;
            width: 100%;
            max-height: 85vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transform: scale(0.95) translateY(10px);
            transition: var(--transition);
        }

        .modal-overlay.active .modal-content {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .modal-title svg {
            width: 20px;
            height: 20px;
            color: var(--primary);
        }

        .modal-close {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            border: none;
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .modal-close:hover {
            background: var(--danger-bg);
            color: var(--danger);
        }

        .modal-close svg {
            width: 18px;
            height: 18px;
        }

        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* Track Map */
        #trackMap {
            width: 100%;
            height: 350px;
            border-radius: var(--radius-md);
            background: var(--bg-tertiary);
        }

        .track-info {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        /* Details Modal Content */
        .details-section {
            margin-bottom: 1.5rem;
        }

        .details-section:last-child {
            margin-bottom: 0;
        }

        .details-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .details-value {
            font-size: 0.95rem;
            color: var(--text-primary);
        }

        .details-items {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .details-item {
            display: flex;
            gap: 1rem;
            padding: 0.75rem;
            background: var(--bg-secondary);
            border-radius: var(--radius-sm);
        }

        .details-item img {
            width: 56px;
            height: 56px;
            border-radius: 6px;
            object-fit: cover;
        }

        .details-item-info {
            flex: 1;
        }

        .details-item-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }

        .details-item-meta {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .details-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            margin-top: 1rem;
        }

        .details-total-label {
            font-weight: 600;
            color: var(--text-secondary);
        }

        .details-total-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* ===== TOAST NOTIFICATIONS ===== */
        .toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .toast {
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            background: var(--text-primary);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 500;
            box-shadow: var(--shadow-lg);
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .toast.success { background: var(--success); }
        .toast.error { background: var(--danger); }

        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .orders-page {
                padding: 1rem;
            }

            .orders-hero {
                padding: 1.5rem;
            }

            .hero-title {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .stat-value {
                font-size: 1.35rem;
            }

            .controls-bar {
                flex-direction: column;
            }

            .search-box {
                min-width: auto;
            }

            .filter-select {
                width: 100%;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .order-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .item-image {
                width: 100%;
                height: 120px;
            }

            .order-footer {
                flex-direction: column;
                align-items: flex-start;
            }

            .order-total {
                text-align: left;
                width: 100%;
            }

            .order-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* ===== REFUND MODAL SPECIFIC STYLES ===== */
        .refund-modal-content {
            max-width: 480px;
        }

        .refund-modal-content .modal-header {
            background: linear-gradient(135deg, var(--primary-bg) 0%, var(--bg-tertiary) 100%);
            border-bottom: none;
        }

        .refund-modal-content .modal-title {
            color: var(--primary-dark);
        }

        .refund-modal-content .modal-title svg {
            color: var(--primary);
            width: 20px;
            height: 20px;
        }

        .refund-modal-content .modal-close {
            color: var(--primary-dark);
        }

        .refund-modal-content .modal-close:hover {
            background: var(--primary-bg);
        }

        .refund-order-info {
            font-size: 1rem;
            color: var(--text-secondary);
            margin: 0 0 1.25rem;
            padding: 0.875rem 1rem;
            background: linear-gradient(135deg, var(--primary-bg) 0%, transparent 100%);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .refund-order-info strong {
            color: var(--primary);
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.9rem;
        }

        .form-group .text-muted {
            color: var(--text-muted);
            font-weight: 400;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s ease;
            resize: vertical;
            background: var(--bg-primary);
            color: var(--text-primary);
            box-sizing: border-box;
        }

        .form-control:hover {
            border-color: var(--primary-light);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-bg);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        .refund-notice {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-bg) 0%, transparent 100%);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            position: relative;
        }

        .refund-notice::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 4px 0 0 4px;
        }

        .refund-notice svg {
            width: 20px;
            height: 20px;
            min-width: 20px;
            max-width: 20px;
            flex-shrink: 0;
            color: var(--primary);
            margin-top: 2px;
        }

        .refund-notice span {
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }

        .refund-modal-content .modal-footer {
            background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--bg-secondary) 100%);
            border-top: 1px solid var(--border-color);
        }

        .refund-modal-content .modal-footer .btn-warning {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            font-weight: 600;
            border: none;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }

        .refund-modal-content .modal-footer .btn-warning:hover {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        /* Request Refund Button */
        .request-refund {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }

        .request-refund:hover {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        /* Refund Status Button */
        .refund-status-btn {
            cursor: not-allowed !important;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            border: none !important;
        }

        .refund-status-btn[data-refund-status="requested"],
        .refund-status-btn[data-refund-status="processing"] {
            background: linear-gradient(135deg, var(--warning-bg) 0%, rgba(245, 158, 11, 0.15) 100%) !important;
            color: #92400e !important;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
        }

        .refund-status-btn[data-refund-status="pickup_scheduled"],
        .refund-status-btn[data-refund-status="picked_up"] {
            background: linear-gradient(135deg, var(--info-bg) 0%, rgba(59, 130, 246, 0.15) 100%) !important;
            color: #1e40af !important;
        }

        .refund-status-btn[data-refund-status="resolved"] {
            background: linear-gradient(135deg, var(--success-bg) 0%, rgba(16, 185, 129, 0.15) 100%) !important;
            color: #065f46 !important;
        }

        .refund-status-btn[data-refund-status="rejected"] {
            background: linear-gradient(135deg, var(--danger-bg) 0%, rgba(239, 68, 68, 0.15) 100%) !important;
            color: #991b1b !important;
        }

        .spinner-sm {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            vertical-align: middle;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Announcement Bar -->
    <?php if (!empty($store_settings['announcement_enabled']) && !empty($store_settings['announcement_text'])): ?>
    <div class="announcement-bar" style="background-color: <?php echo htmlspecialchars($store_settings['announcement_bg_color'] ?? '#000'); ?>; color: <?php echo htmlspecialchars($store_settings['announcement_text_color'] ?? '#fff'); ?>;">
        <p><?php echo htmlspecialchars($store_settings['announcement_text']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Site navigation -->
    <nav class="nav-modern">
        <div class="logo">
            <a href="user-interface.php" class="logo-modern">
                <span class="gradient-text-accent"><?php echo htmlspecialchars($store_settings['store_name']); ?></span>
                <span class="logo-sparkle">✨</span>
            </a>
        </div>
        <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation" tabindex="0">
            <i class="fas fa-bars"></i>
        </button>
        <ul class="nav-links" id="nav-links">
            <li><a href="user-interface.php" class="nav-link-enhanced"><i class="fas fa-home nav-icon"></i> Home</a></li>
            <li><a href="best-seller.php" class="nav-link-enhanced"><i class="fas fa-star nav-icon"></i> Best Seller</a></li>
            <li><a href="shoes.php" class="nav-link-enhanced"><i class="fas fa-shoe-prints nav-icon"></i> Shoes</a></li>
            <li><a href="brand.php" class="nav-link-enhanced"><i class="fas fa-tags nav-icon"></i> Brand</a></li>
        </ul>
        <div class="nav-right">
            <form class="nav-search search-modern" action="shoes.php" method="get" role="search" aria-label="Site search">
                <i class="fas fa-search search-icon"></i>
                <input type="search" name="q" placeholder="Search shoes, brands, categories..." aria-label="Search" />
            </form>
            <a href="cart.php" class="cart-icon cart-icon-modern" id="cart-icon" title="Shopping Cart">
                <i class="fas fa-shopping-bag"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge cart-badge-modern" id="cart-badge"><?php echo $cart_count; ?></span>
                <?php else: ?>
                    <span class="cart-badge cart-badge-modern" id="cart-badge" style="display: none;">0</span>
                <?php endif; ?>
                <span class="cart-pulse"></span>
            </a>
            <div class="user-menu" id="user-menu">
                <div class="user-menu-toggle">
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-greeting">Hello,</span>
                        <span class="user-name"><?php echo htmlspecialchars($customer_name); ?></span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </div>
                <div class="user-dropdown">
                    <div class="dropdown-header">
                        <div class="dropdown-avatar"><i class="fas fa-user-circle"></i></div>
                        <div class="dropdown-user-info">
                            <span class="dropdown-name"><?php echo htmlspecialchars($customer_name); ?></span>
                            <span class="dropdown-email">Manage your account</span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="user-profile.php" class="dropdown-item"><i class="fas fa-user-cog"></i> My Profile</a>
                    <a href="orders.php" class="dropdown-item"><i class="fas fa-box"></i> My Orders</a>
                    <a href="#" class="dropdown-item"><i class="fas fa-heart"></i> Wishlist</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item logout-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Main Content -->
    <main class="orders-page">
        <!-- Hero Header -->
        <header class="orders-hero">
            <div class="hero-content">
                <div class="hero-text">
                    <div class="hero-badge">
                        <i class="fas fa-star"></i>
                        Order Management
                    </div>
                    <h1 class="hero-title">
                        <i class="fas fa-boxes-stacked"></i>
                        My Orders
                    </h1>
                    <p class="hero-subtitle">Track your purchases, view order history, and manage deliveries all in one place.</p>
                </div>
                <div class="hero-illustration">
                    <div class="hero-icon-box">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Stats Cards -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon delivering">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $stats['delivering']; ?></div>
                <div class="stat-label">In Transit</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </section>

        <!-- Controls Bar -->
        <div class="controls-bar">
            <div class="search-box">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input id="searchBox" type="search" placeholder="Search orders, products, order #..." aria-label="Search orders">
            </div>
            <select id="statusFilter" class="filter-select" aria-label="Filter by status">
                <option value="all">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="shipped">Shipped</option>
                <option value="delivering">Delivering</option>
                <option value="delivered">Delivered</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>

        <!-- Orders List -->
        <section class="orders-list" id="ordersList">
            <?php if (count($orders) === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        </svg>
                    </div>
                    <h3 class="empty-title">No orders yet</h3>
                    <p class="empty-text">Looks like you haven't placed any orders. Start shopping to see them here.</p>
                    <a class="btn btn-primary" href="shoes.php">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                        Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $ord): 
                    $status = strtolower($ord['status'] ?? 'pending');
                    $status_class = 'status-' . $status;

                    // Safe created_at formatting
                    $raw_created = $ord['created_at'] ?? '';
                    if (!empty($raw_created) && strtotime($raw_created) !== false) {
                        $created_display = date('M j, Y \a\t g:ia', strtotime($raw_created));
                    } else {
                        $created_display = '—';
                    }

                    $display_order_number = $ord['order_number'] ?? $ord['order_id'];
                    $raw_payment = trim((string)($ord['payment_method'] ?? ''));
                    $payment_display = $raw_payment !== '' ? htmlspecialchars($raw_payment, ENT_QUOTES, 'UTF-8') : '—';

                    // Status icon
                    $status_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
                    $status_display = ucfirst(str_replace('_', ' ', $status));
                    
                    if ($status === 'completed' || $status === 'delivered') {
                        $status_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
                    } elseif ($status === 'cancelled') {
                        $status_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
                    } elseif ($status === 'refund_requested') {
                        $status_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>';
                        $status_display = 'Refund Requested';
                    } elseif (in_array($status, ['shipped','delivering','in_transit','out_for_delivery'])) {
                        $status_icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>';
                    }
                ?>
                    <article class="order-card" data-order-id="<?php echo $ord['order_id']; ?>" data-status="<?php echo htmlspecialchars($status); ?>">
                        <!-- Order Header -->
                        <div class="order-header">
                            <div class="order-info">
                                <div class="order-number">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                                    </svg>
                                    Order #<?php echo htmlspecialchars($display_order_number, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="order-date">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/>
                                        <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                                    </svg>
                                    <?php echo $created_display; ?>
                                </div>
                            </div>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo $status_icon; ?>
                                <?php echo $status_display; ?>
                            </span>
                        </div>

                        <!-- Order Content -->
                        <div class="order-content">
                            <div class="order-items-grid">
                                <?php if (!empty($ord['items'])): ?>
                                    <?php foreach ($ord['items'] as $it): 
                                        $raw_img = $it['image_url'] ?? 'upload/product-image/placeholder.png';
                                        $raw_img = str_replace('\\','/', $raw_img);
                                        $img = htmlspecialchars($raw_img, ENT_QUOTES, 'UTF-8');
                                        $raw_name = trim((string)($it['product_name'] ?? ''));
                                        $pname = $raw_name !== '' ? htmlspecialchars($raw_name, ENT_QUOTES, 'UTF-8') : 'Unknown product';
                                        $color = htmlspecialchars($it['color_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $size = htmlspecialchars($it['size'] ?? '', ENT_QUOTES, 'UTF-8');
                                        $qty = (int)($it['quantity'] ?? 1);
                                        $total_price = (float)str_replace(',', '', $it['unit_price'] ?? '0.00');
                                        // unit_price column actually stores total (unit × qty), so calculate per-unit
                                        $per_unit = $qty > 0 ? $total_price / $qty : $total_price;
                                        $per_unit_formatted = number_format($per_unit, 2);
                                    ?>
                                        <div class="order-item">
                                            <img class="item-image" src="<?php echo $img; ?>" alt="<?php echo $pname; ?>">
                                            <div class="item-details">
                                                <div class="item-name"><?php echo $pname; ?></div>
                                                <div class="item-variant"><?php echo $color; ?><?php echo $size ? " · Size $size" : ''; ?></div>
                                                <div class="item-price">₱<?php echo $per_unit_formatted; ?> × <?php echo $qty; ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="order-item">
                                        <div class="item-details">
                                            <div class="item-name">No items found</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="order-footer">
                                <div class="order-shipping">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span><?php $ship = trim((string)($ord['shipping_address'] ?? '')); echo $ship !== '' ? nl2br(htmlspecialchars($ship, ENT_QUOTES, 'UTF-8')) : '—'; ?></span>
                                </div>
                                <div class="order-total">
                                    <div class="total-label">Total • <?php echo $payment_display; ?></div>
                                    <div class="total-value">₱<?php echo htmlspecialchars($ord['total_amount']); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Actions -->
                        <div class="order-actions">
                            <button type="button" class="btn btn-secondary view-details" data-order-id="<?php echo $ord['order_id']; ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                View Details
                            </button>

                            <?php
                                $trackable_statuses = ['shipped','out_for_delivery','delivering','in_transit','on_the_way'];
                                if (in_array($status, $trackable_statuses, true)): ?>
                                <button type="button" class="btn btn-primary track-order" data-order-id="<?php echo $ord['order_id']; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    Track Order
                                </button>
                            <?php endif; ?>

                            <?php if ($status === 'pending'): ?>
                                <button type="button" class="btn btn-danger cancel-order" data-order-id="<?php echo $ord['order_id']; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                    </svg>
                                    Cancel Order
                                </button>
                            <?php elseif (in_array($status, ['shipped','out_for_delivery','delivering','in_transit'])): ?>
                                <button type="button" class="btn btn-success confirm-received" data-order-id="<?php echo $ord['order_id']; ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Mark Received
                                </button>
                            <?php elseif ($status === 'completed'): ?>
                                <?php if (!empty($ord['refund_status'])): ?>
                                    <button type="button" class="btn btn-ghost refund-status-btn" disabled data-refund-status="<?php echo htmlspecialchars($ord['refund_status']); ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <?php if ($ord['refund_status'] === 'resolved'): ?>
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                            <?php elseif ($ord['refund_status'] === 'rejected'): ?>
                                                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                            <?php else: ?>
                                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                            <?php endif; ?>
                                        </svg>
                                        <?php 
                                            $refund_labels = [
                                                'requested' => 'Refund Pending',
                                                'processing' => 'Refund Processing',
                                                'pickup_scheduled' => 'Pickup Scheduled',
                                                'picked_up' => 'Item Picked Up',
                                                'resolved' => 'Refund Completed',
                                                'rejected' => 'Refund Rejected'
                                            ];
                                            echo $refund_labels[$ord['refund_status']] ?? 'Refund ' . ucfirst($ord['refund_status']);
                                        ?>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-warning request-refund" data-order-id="<?php echo $ord['order_id']; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                                        </svg>
                                        Request Refund
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <!-- Details Modal -->
    <div id="detailsModal" class="modal-overlay" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    Order Details
                </div>
                <button id="closeDetails" class="modal-close" aria-label="Close details">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div id="detailsBody"></div>
            </div>
            <div class="modal-footer">
                <button id="closeDetails2" class="btn btn-primary">Close</button>
            </div>
        </div>
    </div>

    <!-- Refund Modal -->
    <div id="refundModal" class="modal-overlay" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="modal-content refund-modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    Request Refund
                </div>
                <button id="closeRefund" class="modal-close" aria-label="Close refund modal">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <p class="refund-order-info">Order <strong id="refundOrderId">#</strong></p>
                <div class="form-group">
                    <label for="refundReason">Reason for refund <span class="text-muted">(optional)</span></label>
                    <textarea id="refundReason" class="form-control" rows="4" placeholder="Please tell us why you want a refund..."></textarea>
                </div>
                <div class="refund-notice">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                    </svg>
                    <span>Refund requests are typically processed within 3-5 business days. You will be notified once your request is reviewed.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button id="cancelRefund" class="btn btn-secondary">Cancel</button>
                <button id="submitRefund" class="btn btn-warning">Submit Refund Request</button>
            </div>
        </div>
    </div>

    <!-- Track Modal -->
    <div id="trackModal" class="modal-overlay" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                    </svg>
                    Track Order
                </div>
                <button id="closeTrack" class="modal-close" aria-label="Close map">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <div id="trackMap"></div>
                <div id="trackFallback" class="track-info" style="margin-top: 1rem;"></div>
            </div>
            <div class="modal-footer">
                <button id="closeTrack2" class="btn btn-primary">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Orders data for client-side details modal
        const ORDERS_DATA = <?php echo json_encode($orders, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    </script>
    <script>
        // Nav toggle and user-menu behaviour (small, non-conflicting helpers)
        (function(){
            const menuToggle = document.getElementById('menu-toggle');
            const navLinks = document.getElementById('nav-links');
            if (menuToggle && navLinks) {
                menuToggle.addEventListener('click', () => navLinks.classList.toggle('show'));
                menuToggle.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') navLinks.classList.toggle('show'); });
            }

            const userMenu = document.getElementById('user-menu');
            const userMenuToggle = userMenu?.querySelector('.user-menu-toggle');
            if (userMenuToggle) {
                userMenuToggle.addEventListener('click', (evt) => { userMenu.classList.toggle('active'); evt.stopPropagation(); });
                document.addEventListener('click', (e) => { if (!userMenu.contains(e.target)) userMenu.classList.remove('active'); });
            }
        })();

        // Client-side filtering and actions
        (function(){
            const ordersList = document.getElementById('ordersList');
            const searchBox = document.getElementById('searchBox');
            const statusFilter = document.getElementById('statusFilter');
            const toastContainer = document.getElementById('toast-container');

            function showToast(message, isError=false){
                const t = document.createElement('div');
                t.className = 'toast ' + (isError ? 'error' : 'success');
                t.textContent = message;
                toastContainer.appendChild(t);
                setTimeout(()=> {
                    t.style.opacity = 0;
                    setTimeout(()=> t.remove(), 300);
                }, 3500);
            }

            function filterOrders(){
                const q = searchBox.value.trim().toLowerCase();
                const status = statusFilter.value;
                const cards = ordersList.querySelectorAll('.order-card');
                cards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    const cardStatus = card.getAttribute('data-status') || '';
                    const matchesQuery = q === '' || text.includes(q);
                    const matchesStatus = status === 'all' || cardStatus === status;
                    if(matchesQuery && matchesStatus){
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            searchBox.addEventListener('input', filterOrders);
            statusFilter.addEventListener('change', filterOrders);

            // DETAILS: open a modal with full order details (client-side data from ORDERS_DATA)
            function escapeHtml(str){
                if (str === null || typeof str === 'undefined') return '';
                return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            }

            const detailsModalEl = document.getElementById('detailsModal');
            const detailsBodyEl = document.getElementById('detailsBody');
            const closeDetailsBtn = document.getElementById('closeDetails');
            const closeDetailsBtn2 = document.getElementById('closeDetails2');

            function closeDetailsModal(){
                detailsModalEl.classList.remove('active');
                detailsModalEl.setAttribute('aria-hidden','true');
                detailsBodyEl.innerHTML = '';
            }

            closeDetailsBtn.addEventListener('click', closeDetailsModal);
            closeDetailsBtn2.addEventListener('click', closeDetailsModal);
            detailsModalEl.addEventListener('click', (e) => { if (e.target === detailsModalEl) closeDetailsModal(); });

            function buildDetailsHtml(order){
                const parts = [];
                
                // Order Info Section
                parts.push('<div class="details-section">');
                parts.push('<div class="details-label">Order Information</div>');
                parts.push('<div class="details-value"><strong>Order #' + escapeHtml(order.order_number || order.order_id) + '</strong></div>');
                parts.push('<div class="details-value" style="color: var(--text-secondary); font-size: 0.9rem;">' + escapeHtml(order.created_at || '') + '</div>');
                parts.push('<div class="details-value" style="margin-top: 0.5rem;"><span style="text-transform: capitalize; padding: 0.25rem 0.75rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; background: var(--primary-bg); color: var(--primary);">' + escapeHtml(order.status || '') + '</span></div>');
                parts.push('</div>');

                // Payment Section
                parts.push('<div class="details-section">');
                parts.push('<div class="details-label">Payment Method</div>');
                parts.push('<div class="details-value">' + escapeHtml(order.payment_method || '—') + '</div>');
                parts.push('</div>');

                // Shipping Section
                parts.push('<div class="details-section">');
                parts.push('<div class="details-label">Shipping Address</div>');
                parts.push('<div class="details-value">' + (order.shipping_address ? escapeHtml(order.shipping_address).replace(/\n/g,'<br>') : '—') + '</div>');
                parts.push('</div>');

                // Items Section
                parts.push('<div class="details-section">');
                parts.push('<div class="details-label">Items</div>');
                parts.push('<div class="details-items">');
                if (Array.isArray(order.items) && order.items.length){
                    order.items.forEach(it => {
                        const img = escapeHtml((it.image_url || 'upload/product-image/placeholder.png').replace(/\\/g,'/'));
                        const name = escapeHtml(it.product_name || 'Unknown product');
                        const qty = escapeHtml(it.quantity || '1');
                        const unit = escapeHtml(it.unit_price || '0.00');
                        const color = escapeHtml(it.color_name || '');
                        const size = escapeHtml(it.size || '');
                        parts.push('<div class="details-item">');
                        parts.push('<img src="' + img + '" alt="' + name + '">');
                        parts.push('<div class="details-item-info">');
                        parts.push('<div class="details-item-name">' + name + '</div>');
                        parts.push('<div class="details-item-meta">' + (color ? color + (size ? ' · Size ' + size : '') : (size ? 'Size ' + size : '—')) + '</div>');
                        parts.push('<div class="details-item-meta">₱' + unit + ' × ' + qty + '</div>');
                        parts.push('</div>');
                        parts.push('</div>');
                    });
                } else {
                    parts.push('<div class="details-item"><div class="details-item-info"><div class="details-item-name">No items</div></div></div>');
                }
                parts.push('</div>');
                parts.push('</div>');

                // Total
                parts.push('<div class="details-total">');
                parts.push('<span class="details-total-label">Total</span>');
                parts.push('<span class="details-total-value">₱' + escapeHtml(order.total_amount || '0.00') + '</span>');
                parts.push('</div>');
                return parts.join('');
            }

            function openDetailsModal(orderId){
                const id = parseInt(orderId, 10);
                const order = (Array.isArray(ORDERS_DATA) && ORDERS_DATA.find(o => parseInt(o.order_id,10) === id)) || null;
                if (!order){ showToast('Order not found', true); return; }
                detailsBodyEl.innerHTML = buildDetailsHtml(order);
                detailsModalEl.classList.add('active');
                detailsModalEl.setAttribute('aria-hidden','false');
            }

            document.querySelectorAll('.view-details').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const orderId = btn.dataset.orderId;
                    if (!orderId) return;
                    openDetailsModal(orderId);
                });
            });

            // Cancel order and Mark Received actions
            async function postAction(orderId, action) {
                try {
                        const resp = await fetch('orders_action.php', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: new URLSearchParams({ order_id: orderId, action: action })
                        });
                        const txt = await resp.text();
                        let data = null;
                        if (txt) {
                            try { data = JSON.parse(txt); } catch(e) { console.warn('orders_action: invalid JSON', txt); data = null; }
                        }
                    if (data && data.success) {
                        showToast(data.message || 'Updated');
                        // update UI badge and disable buttons accordingly
                        const card = document.querySelector(`.order-card[data-order-id="${orderId}"]`);
                        if (card) {
                            card.setAttribute('data-status', (data.new_status || action).toLowerCase());
                            const badge = card.querySelector('.status-badge');
                            if (badge) {
                                badge.textContent = (data.new_status || action).charAt(0).toUpperCase() + (data.new_status || action).slice(1);
                            }
                            // Re-run filter to hide/show per current filter
                            filterOrders();
                            // Optionally disable action buttons to prevent repeat
                            card.querySelectorAll('button').forEach(b => {
                                if (b.classList.contains('cancel-order') || b.classList.contains('confirm-received')) {
                                    b.disabled = true;
                                    b.classList.add('btn-ghost');
                                }
                            });
                        }
                    } else {
                        showToast((data && data.message) ? data.message : 'Action failed', true);
                    }
                } catch (err) {
                    showToast('Network error', true);
                }
            }

            document.querySelectorAll('.cancel-order').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const orderId = btn.dataset.orderId;
                    if (!confirm('Are you sure you want to cancel this order?')) return;
                    postAction(orderId, 'cancelled');
                });
            });

            document.querySelectorAll('.confirm-received').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const orderId = btn.dataset.orderId;
                    if (!confirm('Confirm you have received this order?')) return;
                    postAction(orderId, 'delivered');
                });
            });

            // Refund Modal functionality
            const refundModal = document.getElementById('refundModal');
            const refundOrderIdEl = document.getElementById('refundOrderId');
            const refundReasonEl = document.getElementById('refundReason');
            const closeRefundBtn = document.getElementById('closeRefund');
            const cancelRefundBtn = document.getElementById('cancelRefund');
            const submitRefundBtn = document.getElementById('submitRefund');
            let currentRefundOrderId = null;
            let currentRefundButton = null;

            function openRefundModal(orderId, btn) {
                currentRefundOrderId = orderId;
                currentRefundButton = btn;
                refundOrderIdEl.textContent = '#' + orderId;
                refundReasonEl.value = '';
                refundModal.classList.add('active');
                refundModal.setAttribute('aria-hidden', 'false');
                refundReasonEl.focus();
            }

            function closeRefundModal() {
                refundModal.classList.remove('active');
                refundModal.setAttribute('aria-hidden', 'true');
                currentRefundOrderId = null;
                currentRefundButton = null;
            }

            closeRefundBtn?.addEventListener('click', closeRefundModal);
            cancelRefundBtn?.addEventListener('click', closeRefundModal);
            refundModal?.addEventListener('click', (e) => {
                if (e.target === refundModal) closeRefundModal();
            });

            // Request refund action (opens modal)
            document.querySelectorAll('.request-refund').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const orderId = btn.dataset.orderId;
                    if (!orderId) return;
                    openRefundModal(orderId, btn);
                });
            });

            // Submit refund from modal
            submitRefundBtn?.addEventListener('click', async () => {
                if (!currentRefundOrderId) return;
                
                submitRefundBtn.disabled = true;
                submitRefundBtn.innerHTML = '<span class="spinner-sm"></span> Submitting...';
                
                try {
                    const resp = await fetch('request_refund.php', {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: new URLSearchParams({ 
                            order_id: currentRefundOrderId,
                            reason: refundReasonEl.value.trim()
                        })
                    });
                    const txt = await resp.text();
                    let data = null;
                    if (txt) {
                        try { data = JSON.parse(txt); } catch (err) { console.warn('request_refund: invalid JSON', txt); }
                    }
                    if (data && data.success) {
                        showToast(data.message || 'Refund request submitted successfully!');
                        // Update the button to show pending status
                        if (currentRefundButton) {
                            currentRefundButton.disabled = true;
                            currentRefundButton.classList.remove('btn-warning');
                            currentRefundButton.classList.add('btn-ghost', 'refund-status-btn');
                            currentRefundButton.innerHTML = `
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                </svg>
                                Refund Pending
                            `;
                        }
                        closeRefundModal();
                    } else {
                        showToast((data && data.message) ? data.message : 'Refund request failed', true);
                    }
                } catch (err) {
                    showToast('Network error. Please try again.', true);
                } finally {
                    submitRefundBtn.disabled = false;
                    submitRefundBtn.innerHTML = 'Submit Refund Request';
                }
            });

            // TRACKING: fetch location or tracking link and show modal map / fallback
            let mapInstance = null;
            let currentMarker = null;
            const modal = document.getElementById('trackModal');
            const closeTrack = document.getElementById('closeTrack');
            const closeTrack2 = document.getElementById('closeTrack2');
            const trackFallback = document.getElementById('trackFallback');

            function openModal() {
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
            }
            function closeModal() {
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                // destroy map to free memory
                if (mapInstance) {
                    try { mapInstance.remove(); } catch (e) {}
                    mapInstance = null;
                    currentMarker = null;
                    document.getElementById('trackMap').innerHTML = '';
                }
                trackFallback.textContent = '';
            }

            closeTrack.addEventListener('click', closeModal);
            closeTrack2.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

            async function fetchTracking(orderId) {
                try {
                    const resp = await fetch('orders_track.php?order_id=' + encodeURIComponent(orderId), {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!resp.ok) throw new Error('Network response not ok');
                    const txt = await resp.text();
                    if (!txt) return { success: false, message: 'Empty response' };
                    try { const data = JSON.parse(txt); return data; } catch (e) { console.warn('fetchTracking: invalid JSON', txt); return { success: false, message: 'Invalid tracking response' }; }
                } catch (err) {
                    return { success: false, message: 'Network error' };
                }
            }

            document.querySelectorAll('.track-order').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const orderId = btn.dataset.orderId;
                    btn.disabled = true;
                    const res = await fetchTracking(orderId);
                    btn.disabled = false;
                    if (!res || !res.success) {
                        showToast(res && res.message ? res.message : 'Could not fetch tracking info', true);
                        return;
                    }

                    // If we have coordinates (legacy `lat/lng` or `customer_lat`/`rider_lat`), show map
                    if ((typeof res.lat !== 'undefined' && typeof res.lng !== 'undefined') || (typeof res.customer_lat !== 'undefined' && typeof res.customer_lng !== 'undefined') || (typeof res.rider_lat !== 'undefined' && typeof res.rider_lng !== 'undefined')) {
                        openModal();
                        trackFallback.textContent = res.provider ? ('Provider: ' + res.provider) : '';
                        // init leaflet map lazily
                        try {
                            // load leaflet if not loaded yet
                            if (typeof L === 'undefined') {
                                // leaflet JS should be in the page below, but guard
                                showToast('Map library not available', true);
                                return;
                            }
                            // create map — support both single-point responses and dual customer/rider points
                            const centerLat = (typeof res.lat !== 'undefined') ? res.lat : (res.customer_lat || res.rider_lat);
                            const centerLng = (typeof res.lng !== 'undefined') ? res.lng : (res.customer_lng || res.rider_lng);
                            mapInstance = L.map('trackMap').setView([centerLat, centerLng], 13);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '&copy; OpenStreetMap contributors'
                            }).addTo(mapInstance);

                            // If both customer and rider coordinates are present, draw markers for both and request a driving route (OSRM) for accuracy
                            if (typeof res.customer_lat !== 'undefined' && typeof res.customer_lng !== 'undefined' && typeof res.rider_lat !== 'undefined' && typeof res.rider_lng !== 'undefined') {
                                const custMarker = L.marker([res.customer_lat, res.customer_lng]).addTo(mapInstance);
                                custMarker.bindPopup('Your Location').openPopup();
                                const riderMarker = L.marker([res.rider_lat, res.rider_lng]).addTo(mapInstance);
                                riderMarker.bindPopup('Rider location');

                                (async function(){
                                    try {
                                        // Build OSRM coordinates as lon,lat;lon,lat
                                        const coords = [
                                            [res.rider_lng, res.rider_lat],
                                            [res.customer_lng, res.customer_lat]
                                        ];
                                        const parts = coords.map(c => c.join(',')).join(';');
                                        const url = 'https://router.project-osrm.org/route/v1/driving/' + parts + '?overview=full&geometries=geojson';
                                        const r = await fetch(url);
                                        if (r.ok) {
                                            const body = await r.json();
                                            if (body && body.routes && body.routes.length) {
                                                const geom = body.routes[0].geometry;
                                                if (geom && Array.isArray(geom.coordinates) && geom.coordinates.length > 1) {
                                                    const routePts = geom.coordinates.map(c => [c[1], c[0]]);
                                                    const routeLayer = L.polyline(routePts, { color: 'blue', weight: 4, opacity: 0.9 }).addTo(mapInstance);
                                                    mapInstance.fitBounds(routeLayer.getBounds().pad(0.25));
                                                    currentMarker = riderMarker;
                                                    return;
                                                }
                                            }
                                        }
                                    } catch (e) {
                                        console.warn('OSRM route error', e);
                                    }
                                    // Fallback: straight line if routing failed
                                    const fallbackLine = L.polyline([[res.rider_lat, res.rider_lng],[res.customer_lat, res.customer_lng]], {color: 'blue', weight: 4, opacity: 0.7}).addTo(mapInstance);
                                    mapInstance.fitBounds(fallbackLine.getBounds().pad(0.25));
                                    currentMarker = riderMarker;
                                })();
                            } else {
                                // single point legacy behaviour
                                const lat = (typeof res.lat !== 'undefined') ? res.lat : res.customer_lat || res.rider_lat;
                                const lng = (typeof res.lng !== 'undefined') ? res.lng : res.customer_lng || res.rider_lng;
                                currentMarker = L.marker([lat, lng]).addTo(mapInstance);
                                if (res.label) currentMarker.bindPopup(res.label).openPopup();
                            }
                        } catch (err) {
                            showToast('Could not initialize map', true);
                        }
                        return;
                    }

                    // Otherwise show fallback tracking details or link
                    if (res.tracking_url) {
                        openModal();
                        trackFallback.innerHTML = 'Open tracking: <a target="_blank" rel="noopener noreferrer" href="' + encodeURIComponent(res.tracking_url).replace(/%3A/g, ':').replace(/%2F/g, '/') + '">Tracking link</a>';
                    } else if (res.tracking_number) {
                        openModal();
                        trackFallback.textContent = 'Tracking number: ' + res.tracking_number;
                    } else {
                        showToast(res.message || 'No tracking information available', true);
                    }
                });
            });

            // initial filter pass
            filterOrders();
        })();
    </script>

    <!-- Leaflet JS (placed near bottom to load after DOM) -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <?php include __DIR__ . '/partials/chatbot.php'; ?>
</body>
</html>