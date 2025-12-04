<?php
require_once 'db_connection.php';
require_once 'inc/store_settings.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}
$customer_id = (int)$_SESSION['customer_id'];

function resolve_to_web_url($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return '';

    if (preg_match('#^data:#i', $raw) || preg_match('#^https?://#i', $raw)) {
        return $raw;
    }

    $normalized = str_replace('\\', '/', $raw);

        if (preg_match('#(upload/[^\s"\']+)#i', $normalized, $m)) {
            $candidate = ltrim($m[1], '/');
        } elseif (preg_match('#(admin/[^\s"\']+)#i', $normalized, $m)) {
            $candidate = ltrim($m[1], '/');
    } else {
        $tmp = preg_replace('#^[A-Za-z]:/#', '', $normalized);
        $tmp = preg_replace('#^/+','#', $tmp);
            $candidate = ltrim($tmp, '/');
    }

    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], '/') : '';
    if ($docRoot) {
            $filePath = $docRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
            if (is_file($filePath)) {
            return $candidate;
        }

            $tryRaw = ltrim($normalized, '/');
            $tryFile = $docRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $tryRaw);
            if (is_file($tryFile)) {
            return $tryRaw;
        }
    }

    return htmlspecialchars($normalized, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_address') {
    $resp = ['success' => false];
    $addr = trim((string)($_POST['delivery_address'] ?? ''));
    $latRaw = isset($_POST['latitude']) ? trim((string)$_POST['latitude']) : '';
    $lngRaw = isset($_POST['longitude']) ? trim((string)$_POST['longitude']) : '';
    $lat = $latRaw === '' ? null : (is_numeric($latRaw) ? (float)$latRaw : null);
    $lng = $lngRaw === '' ? null : (is_numeric($lngRaw) ? (float)$lngRaw : null);
    if ($addr === '') {
        $resp['error'] = 'Empty address';
        header('Content-Type: application/json'); echo json_encode($resp); exit();
    }
    $columnsToCheck = ['latitude','longitude','lat','lng'];
    $availableCols = [];
    $colRes = $conn->query("SHOW COLUMNS FROM customer");
    if ($colRes) {
        while ($crow = $colRes->fetch_assoc()) {
            $availableCols[] = strtolower($crow['Field']);
        }
        $colRes->close();
    }

    $fields = ['address'];
    $types = 's';
    $values = [$addr];

    if ($lat !== null) {
        if (in_array('latitude', $availableCols)) { $fields[] = 'latitude'; $types .= 'd'; $values[] = $lat; }
        elseif (in_array('lat', $availableCols)) { $fields[] = 'lat'; $types .= 'd'; $values[] = $lat; }
    }
    if ($lng !== null) {
        if (in_array('longitude', $availableCols)) { $fields[] = 'longitude'; $types .= 'd'; $values[] = $lng; }
        elseif (in_array('lng', $availableCols)) { $fields[] = 'lng'; $types .= 'd'; $values[] = $lng; }
    }

    $setParts = array_map(function($f){ return "$f = ?"; }, $fields);
    $sql = "UPDATE customer SET " . implode(', ', $setParts) . " WHERE customer_id = ?";
    $types .= 'i';
    $values[] = $customer_id;

    $up = $conn->prepare($sql);
    if ($up) {
        $bindNames = [];
        $bindNames[] = $types;
        foreach ($values as $k => $v) $bindNames[] = &$values[$k];
        call_user_func_array([$up, 'bind_param'], $bindNames);
        if ($up->execute()) { $resp['success'] = true; $resp['address'] = $addr; if ($lat !== null) $resp['latitude'] = $lat; if ($lng !== null) $resp['longitude'] = $lng; }
        else $resp['error'] = 'DB execute failed';
        $up->close();
    } else {
        $resp['error'] = 'DB prepare failed';
    }
    header('Content-Type: application/json'); echo json_encode($resp); exit();
}

$selected = $_POST['selected_items'] ?? [];
if (!is_array($selected)) $selected = [$selected];
$selected = array_values(array_filter(array_map('intval', $selected)));

if (empty($selected)) {
    $stmt = $conn->prepare("SELECT variant_id, quantity FROM cart WHERE customer_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $r = $stmt->get_result();
        while ($row = $r->fetch_assoc()) {
            $selected[] = (int)$row['variant_id'];
        }
        $stmt->close();
    }
}

$products = [];
$subtotal = 0.0;
if (!empty($selected)) {
    $placeholders = implode(',', array_fill(0, count($selected), '?'));
    $types = str_repeat('i', count($selected));
    $sql = "SELECT pv.variant_id, pv.product_id, p.name AS product_name, pv.price, pv.stock, co.color_name, sz.size_name, pci.image_url
        FROM product_variant pv
        JOIN product p ON pv.product_id = p.product_id
        LEFT JOIN color co ON pv.color_id = co.color_id
        LEFT JOIN size sz ON pv.size_id = sz.size_id
        LEFT JOIN product_color_image pci ON pv.product_id = pci.product_id AND pv.color_id = pci.color_id AND pci.sort_order = 1
        WHERE pv.variant_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $params = array_merge([$types], $selected);
        $refs = [];
        foreach ($params as $k => $v) $refs[$k] = &$params[$k];
        call_user_func_array([$stmt, 'bind_param'], $refs);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $row['price'] = (float)$row['price'];
            $row['variant_id'] = (int)$row['variant_id'];
            $products[$row['variant_id']] = $row;
            $subtotal += $row['price'];
        }
        $stmt->close();
    }
}

$delivery_address = '';
$customer_name = '';
$stmt = $conn->prepare("SELECT address, name FROM customer WHERE customer_id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($rr = $r->fetch_assoc()) { $delivery_address = (string)($rr['address'] ?? ''); $customer_name = (string)($rr['name'] ?? ''); }
    $stmt->close();
}

$store_row = [];
$sq = $conn->prepare("SELECT * FROM store LIMIT 1");
if ($sq) {
    $sq->execute();
    $sr = $sq->get_result();
    if ($srr = $sr->fetch_assoc()) { $store_row = $srr; }
    $sq->close();
}

$store_gcash = '';
$store_phone = '';
$store_gcash_no = '';

$gcashKeys = ['gcash_no','gcash','mobile_no','mobile_number','store_gcash_no','phone','contact_number','contact'];
foreach ($gcashKeys as $k) {
    if (isset($store_row[$k]) && trim((string)$store_row[$k]) !== '') {
        $val = trim((string)$store_row[$k]);
        if (preg_match('#(upload/|admin/|\\\|\.png|\.jpe?g|\.svg|\.gif)#i', $val)) {
        } else {
            $store_gcash = $val;
            break;
        }
    }
}

$phoneKeys = ['phone','store_phone','contact_number','tel'];
foreach ($phoneKeys as $k) {
    if (isset($store_row[$k]) && trim((string)$store_row[$k]) !== '') { $store_phone = trim((string)$store_row[$k]); break; }
}
$store_image_raw = '';
$store_gcash_qr_raw = '';

$imgKeys = ['image','store_image','logo','store_logo','image_url','picture','store_image_url','store_logo_url','logo_url'];
foreach ($imgKeys as $k) {
    if (isset($store_row[$k]) && trim((string)$store_row[$k]) !== '') { $store_image_raw = trim((string)$store_row[$k]); break; }
}

$qrKeys = ['gcash_qr','gcash_qr_path','gcash_qr_image','gcash_qr_url','qr_image','qr','gcash_qr_img'];
foreach ($qrKeys as $k) {
    if (isset($store_row[$k]) && trim((string)$store_row[$k]) !== '') { $store_gcash_qr_raw = trim((string)$store_row[$k]); break; }
}

if ($store_image_raw === '') {
    foreach ($gcashKeys as $k) {
        if (isset($store_row[$k]) && trim((string)$store_row[$k]) !== '') {
            $v = trim((string)$store_row[$k]);
            if (preg_match('#(upload/|admin/|\\\|\.png|\.jpe?g|\.svg|\.gif)#i', $v)) {
                $store_image_raw = $v;
                break;
            }
        }
    }
}

$store_image = $store_image_raw ? resolve_to_web_url($store_image_raw) : '';
$store_gcash_qr = $store_gcash_qr_raw ? resolve_to_web_url($store_gcash_qr_raw) : '';

$debug_html = '';
if (isset($_GET['debug_store']) && $_GET['debug_store']) {
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) : '';
    $resolved_img_path = $store_image ? $store_image : '(empty)';
    $resolved_qr_path = $store_gcash_qr ? $store_gcash_qr : '(empty)';
    $img_exists = false; $qr_exists = false;
    if ($docRoot) {
        $img_file = $store_image ? $docRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($store_image, '/')) : '';
        $qr_file = $store_gcash_qr ? $docRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($store_gcash_qr, '/')) : '';
        $img_exists = $img_file && is_file($img_file);
        $qr_exists = $qr_file && is_file($qr_file);
    }
    ob_start();
    echo "<div style=\"background:#fff;border:2px solid #eee;padding:12px;margin:12px 0;font-family:monospace;\">";
    echo "<strong>DEBUG: store table raw row</strong>\n";
    echo "<pre>" . htmlspecialchars(print_r($store_row, true), ENT_QUOTES, 'UTF-8') . "</pre>";
    echo "<strong>Resolved values</strong><br>";
    echo "store_image_raw: " . htmlspecialchars($store_image_raw ?? '(null)', ENT_QUOTES, 'UTF-8') . "<br>";
    echo "store_image_resolved: " . htmlspecialchars($resolved_img_path, ENT_QUOTES, 'UTF-8') . "<br>";
    echo "store_image_exists_on_disk: " . ($img_exists ? 'YES' : 'NO') . "<br>";
    echo "gcash_qr_raw: " . htmlspecialchars($store_gcash_qr_raw ?? '(null)', ENT_QUOTES, 'UTF-8') . "<br>";
    echo "gcash_qr_resolved: " . htmlspecialchars($resolved_qr_path, ENT_QUOTES, 'UTF-8') . "<br>";
    echo "gcash_qr_exists_on_disk: " . ($qr_exists ? 'YES' : 'NO') . "<br>";
    if ($docRoot) {
        echo "docRoot checked: " . htmlspecialchars($docRoot, ENT_QUOTES, 'UTF-8') . "<br>";
        if ($store_image) echo "store_image_file_checked: " . htmlspecialchars($img_file ?? '', ENT_QUOTES, 'UTF-8') . "<br>";
        if ($store_gcash_qr) echo "gcash_qr_file_checked: " . htmlspecialchars($qr_file ?? '', ENT_QUOTES, 'UTF-8') . "<br>";
    }
    echo "</div>";
    $debug_html = ob_get_clean();
}

// Load store bank accounts into PHP array (optional table: store_bank_account)
$bank_accounts = [];
$sb = $conn->prepare("SELECT bank_code, bank_name, account_number, account_name, branch FROM store_bank_account");
if ($sb) {
    $sb->execute();
    $bres = $sb->get_result();
    while ($brow = $bres->fetch_assoc()) {
        $code = isset($brow['bank_code']) && $brow['bank_code'] !== '' ? $brow['bank_code'] : preg_replace('/[^a-z0-9]+/','',strtolower($brow['bank_name'] ?? ''));
        $bank_accounts[$code] = [
            'bank_name' => $brow['bank_name'] ?? '',
            'account_number' => $brow['account_number'] ?? '',
            'account_name' => $brow['account_name'] ?? '',
            'branch' => $brow['branch'] ?? ''
        ];
    }
    $sb->close();
}

// Load owner/store bank from `store` table as fallback (try several common column names)
$owner_bank = ['bank_name' => '', 'account_number' => '', 'account_name' => ''];
if (!empty($store_row)) {
    $bankKeys = ['bank_name','bank','store_bank','bankName','bankname'];
    $acctNumKeys = ['bank_account_number','account_number','account_no','account','acct_number','acct_num'];
    $acctNameKeys = ['bank_account_name','account_name','account_holder','acct_name','accountName'];

    foreach ($bankKeys as $k) {
        if (array_key_exists($k, $store_row) && trim((string)($store_row[$k] ?? '')) !== '') { $owner_bank['bank_name'] = trim((string)$store_row[$k]); break; }
    }
    foreach ($acctNumKeys as $k) {
        if (array_key_exists($k, $store_row) && trim((string)($store_row[$k] ?? '')) !== '') { $owner_bank['account_number'] = trim((string)$store_row[$k]); break; }
    }
    foreach ($acctNameKeys as $k) {
        if (array_key_exists($k, $store_row) && trim((string)($store_row[$k] ?? '')) !== '') { $owner_bank['account_name'] = trim((string)$store_row[$k]); break; }
    }
}

// Get total quantity in cart for header display
$total_quantity = 0;
$q = $conn->prepare("SELECT SUM(quantity) AS total FROM cart WHERE customer_id = ?");
if ($q) {
    $q->bind_param('i', $customer_id);
    $q->execute();
    $qr = $q->get_result();
    if ($rtemp = $qr->fetch_assoc()) { $total_quantity = (int)($rtemp['total'] ?? 0); }
    $q->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <!-- Leaflet CSS for map picker -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <?php echo getStoreThemeCSS(); ?>
    <style>
/* (existing CSS unchanged) */
* {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
            color: #1a1a1a;
            line-height: 1.6;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }

        .checkout-header {
            margin-bottom: 32px;
        }

        .checkout-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .checkout-header p {
            color: #666;
            font-size: 14px;
        }

        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 32px;
        }

        .checkout-main {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Card Styles */
        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title-icon {
            width: 24px;
            height: 24px;
            background: #1a1a1a;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 14px;
        }

        /* Address Section */
        .address-section {
            display: flex;
            gap: 10px;
        }

        .address-display {
            flex = 1;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #1a1a1a;
            white-space: pre-wrap;
            word-break: break-word;

        }

        .address-editor {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            display: none;
        }

        .address-editor:focus {
            outline: none;
            border-color: #1a1a1a;
            box-shadow: 0 0 0 3px rgba(26, 26, 26, 0.1);
        }

        .address-msg {
            padding: 12px;
            border-radius: 8px;
            margin-top: 12px;
            font-size: 14px;
            display: none;
        }

        .address-buttons {
            display: flex;
            gap: 12px;
            margin-top: 16px;
        }

        /* Products Section */
        .products-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .product-item {
            display: flex;
            gap: 14px;
            padding: 14px;
            background: #f8f9fa;
            border-radius: 8px;

        }

        .product-image {
            width: 150px;
            height: 150px;
            background: #e5e7eb;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
        }

        .product-qty {
            font-size: 13px;
            color: #6b7280;
            margin-top: 6px;
        }

        .product-price {
            font-weight: 700;
            font-size: 16px;
        }

        /* Payment Section */
        .payment-methods {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }

        .payment-option {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .payment-option:hover {
            border-color: #1a1a1a;
            background: #f8f9fa;
        }

        .payment-option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #1a1a1a;
        }

        .payment-option input[type="radio"]:checked ~ .payment-label {
            font-weight: 600;
        }

        .payment-label {
            flex: 1;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
        }

        /* Form Fields */
        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #1a1a1a;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1a1a1a;
            box-shadow: 0 0 0 3px rgba(26, 26, 26, 0.1);
        }

        .payment-fields {
            padding: 16px;
            background: #f8f9fa;
            border-radius: 8px;
            display: none;
            margin-top: 12px;
        }

        /* Send payment / GCash design */
        .gcash-info {
            margin-top: 12px;
            display: none;
        }

        .gcash-row {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .gcash-pill {
            display: inline-flex;
            align-items: center;
            gap: 12px;

            color: #fff;
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 4px 10px rgba(17,24,39,0.08);

            max-width: 100%;
        }

        .gcash-pill img, .owner-bank-icon {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 6px;
            background: #fff;
            padding: 4px;
        }

        /* When pill has a large QR, stack content vertically and center it */
        .gcash-pill.has-qr {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
            border-radius: 12px;
        }

        .gcash-qr-thumb {
            width: 100%;
            max-width: 420px;
            height: auto;
            border-radius: 12px;
            object-fit: contain;
            cursor: pointer;
            background: #fff;
            padding: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 8px 26px rgba(0,0,0,0.12);
            display: block;
        }

        @media (max-width: 1400px) {
            .gcash-qr-thumb { max-width: 340px; }
        }

        @media (max-width: 1024px) {
            .gcash-qr-thumb { max-width: 260px; }
        }

        @media (max-width: 560px) {
            .gcash-qr-thumb { max-width: 160px; }
        }

        .gcash-instruction {
            color: #374151;
            font-size: 13px;
            margin-top: 6px;
        }

        .copy-btn {
            background: #e5e7eb;
            border: none;
            padding: 8px 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #111827;
            transition: background .15s ease, transform .08s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }

        .copy-btn:active { transform: translateY(1px); }

        .payment-proof-row {
            margin-top: 12px;
            display: none;
        }

        .proof-preview {
            max-width: 220px;
            max-height: 120px;
            border-radius: 8px;
            display: none;
            border: 1px solid #e5e7eb;
            padding: 6px;
            background: #fff;
        }

        @media (max-width: 560px) {
            .gcash-pill { font-size: 14px; padding: 7px 10px; }
            .copy-btn { padding: 7px 8px; }
        }

        .payment-fields.visible {
            display: block;
        }

        /* Order Summary */
        .order-summary {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            height: fit-content;
            position: sticky;
            top: 24px;
        }

        .summary-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .summary-row.total {
            border-bottom: none;
            font-size: 18px;
            font-weight: 600;
            padding-top: 16px;
            padding-bottom: 0;
        }

        .summary-amount {
            font-weight: 600;
            font-size: 15px;
        }

        /* Added animation styles for shoes SVG */
        @keyframes bobbing {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-12px);
            }
        }

        @keyframes rotation {
            0% {
                transform: rotateZ(0deg);
            }
            100% {
                transform: rotateZ(360deg);
            }
        }

        @keyframes fadeInScale {
            0% {
                opacity: 0;
                transform: scale(0.5);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .shoes-animation-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            min-height: 140px;
        }

        .shoes-svg {
            animation: fadeInScale 0.8s ease-out;
        }

        /* Buttons */
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }

        .btn-primary {
            background: #1a1a1a;
            color: white;
            flex: 1;
        }

        .btn-primary:hover:not(:disabled) {
            background: #0d0d0d;
            box-shadow: 0 4px 12px rgba(26, 26, 26, 0.2);
        }

        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #1a1a1a;
            flex: 1;
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .btn-sm {
            padding: 8px 16px;
            flex: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
                top: auto;
            }

            .button-group {
                flex-direction: column;
            }
        }

        .empty-state {
            text-align: center;
            padding: 32px 16px;
            color: #666;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        /* Order modal + verification/check animations */
        #order-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 6000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        #order-modal .modal-card {
            background: #fff;
            border-radius: 12px;
            padding: 28px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 10px 40px rgba(2,6,23,0.2);
        }
        .verifying-dot {
            display:inline-block;
            width: 10px; height: 10px;
            background: #1a1a1a;
            border-radius:50%;
            margin: 0 6px;
            opacity: 0.2;
            transform: translateY(0);
            animation: verifying 1s infinite;
        }
        .verifying-dot:nth-child(2){ animation-delay: .15s; }
        .verifying-dot:nth-child(3){ animation-delay: .3s; }
        @keyframes verifying {
            0% { opacity:0.2; transform: translateY(0); }
            50% { opacity:1; transform: translateY(-8px); }
            100% { opacity:0.2; transform: translateY(0); }
        }

        /* check animation */
        .check-container {
            width: 120px; height: 120px; margin: 0 auto 8px;
            display:flex; align-items:center; justify-content:center;
            border-radius: 999px;
            background: linear-gradient(180deg,#10b981,#059669);
            box-shadow: 0 8px 30px rgba(16,185,129,0.18);
            transform: scale(0.6);
            opacity: 0;
            transition: transform .36s cubic-bezier(.2,.9,.2,1), opacity .2s ease;
        }
        .check-container.visible { transform: scale(1); opacity: 1; }
        .check-svg {
            width: 64px; height:64px; color: #fff;
        }

    </style>
    <style>
        /* Map modal styles */
        #map-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:6500; align-items:center; justify-content:center; }
        #map-modal .map-card { width:92%; max-width:980px; height:80vh; background:#fff; border-radius:10px; overflow:hidden; display:flex; flex-direction:column; }
        #map-container { flex:1; }
        #map-modal .map-header { display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-bottom:1px solid #eee; }
        #map-modal .map-actions { display:flex; gap:8px; }
    </style>
</head>
<body>
    <div class="checkout-container">
        <?php if (!empty($debug_html)) { echo $debug_html; } ?>
        <div class="checkout-header">
            <h1>Checkout</h1>
            <p>Review your order and complete payment</p>
        </div>

        <div class="checkout-content">
            <div class="checkout-main">
                <form id="checkout-form" method="POST" action="place_order.php" enctype="multipart/form-data">
                <!-- Delivery Address -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-title-icon">📍</div>
                            Delivery Address
                        </div>
                    </div>
                    <div id="address-display" class="address-display">
                        <?php echo htmlspecialchars($delivery_address ?? 'No address saved yet.', ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <textarea id="address-editor" class="address-editor"><?php echo htmlspecialchars($delivery_address ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <input type="hidden" id="delivery_address_hidden" name="delivery_address" value="<?php echo htmlspecialchars($delivery_address ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div id="address-msg" class="address-msg"></div>
                    <div class="button-group" style="margin-top: 16px;">
                        <button id="address-change-btn" class="btn-secondary btn-sm" type="button">Change</button>
                        <button id="address-pick-map" class="btn-secondary btn-sm" type="button" title="Pick location on map">Pick on map</button>
                        <button id="address-save-btn" class="btn-primary btn-sm" type="button" style="display: none;">Save</button>
                        <button id="address-cancel-btn" class="btn-secondary btn-sm" type="button" style="display: none;">Cancel</button>
                    </div>
                    <!-- Hidden fields to hold chosen lat/lng for order submission -->
                    <input type="hidden" id="delivery_lat" name="delivery_lat" value="">
                    <input type="hidden" id="delivery_lng" name="delivery_lng" value="">
                </div>

                <!-- Products -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-title-icon">📦</div>
                            Products
                        </div>
                    </div>
                    <div class="products-list" id="products-list">
                        <?php if (empty($products)): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">👟</div>
                                No items selected.
                            </div>
                        <?php else: ?>
                            <?php foreach ($products as $p): ?>
                                <div class="product-item">
                                    <div class="product-image">
                                        <img src="<?php echo htmlspecialchars($p['image_url'] ?? 'upload/product-image/placeholder.png', ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">
                                    </div>
                                    <div class="product-info">
                                        <div class="product-name"><?php echo htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="product-qty">Variant: <?php echo intval($p['variant_id']); ?> • <?php echo htmlspecialchars(($p['color_name'] ?? '') . ' • ' . ($p['size_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </div>
                                    <div class="product-price">₱ <?php echo number_format($p['price'],2); ?></div>
                                </div>
                                <input type="hidden" name="selected_items[]" value="<?php echo intval($p['variant_id']); ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <div class="card-title-icon">💳</div>
                            Payment Method
                        </div>
                    </div>
                    <div class="payment-methods">
                        <label class="payment-option">
                            <input type="radio" name="payment" value="gcash" checked>
                            <span class="payment-label">GCash (Mobile Wallet)</span>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="payment" value="bank">
                            <span class="payment-label">Online Bank Transfer</span>
                        </label>
                    </div>

                    <!-- GCash Fields -->
                    <div id="payment-gcash" class="payment-fields visible">
                        <div class="form-group">
                            <label for="gcash_number">GCash Mobile Number</label>
                            <input type="tel" id="gcash_number" name="gcash_number" placeholder="09XX XXX XXXX">
                        </div>
                        <div class="form-group">
                            <label for="gcash_ref">Transaction Reference (optional)</label>
                            <input type="text" id="gcash_ref" name="gcash_ref" placeholder="Enter reference number">
                        </div>
                    </div>

                    <!-- Bank Transfer Fields -->
                    <div id="payment-bank" class="payment-fields">
                        <div class="form-group">
                            <label for="bank_name">Select Bank</label>
                            <select id="bank_name" name="bank_name">
                                <option value="">Choose a bank...</option>
                                <?php
                                if (!empty($bank_accounts)) {
                                    foreach ($bank_accounts as $bcode => $binfo) {
                                        $label = htmlspecialchars($binfo['bank_name'] ?: $bcode, ENT_QUOTES, 'UTF-8');
                                        echo "<option value=\"" . htmlspecialchars($bcode, ENT_QUOTES, 'UTF-8') . "\">$label</option>\n";
                                    }
                                } else {
                                    // fallback static options
                                    echo "<option value=\"bdo\">BDO</option>\n";
                                    echo "<option value=\"bpi\">BPI</option>\n";
                                    echo "<option value=\"metrobank\">Metrobank</option>\n";
                                    echo "<option value=\"unionbank\">UnionBank</option>\n";
                                    echo "<option value=\"other\">Other Bank</option>\n";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bank_account_name">Account Name</label>
                            <input type="text" id="bank_account_name" name="bank_account_name" placeholder="Enter account holder name">
                        </div>
                        <div class="form-group">
                            <label for="bank_account_number">Account Number</label>
                            <input type="text" id="bank_account_number" name="bank_account_number" placeholder="Enter account number">
                        </div>
                    </div>

                    <!-- GCash info & Payment proof -->
                    <div id="gcash-info" class="gcash-info">
                        <div class="form-group">
                            <label>Send payment to</label>
                            <div class="gcash-row">
                                <div id="store-gcash-number" class="gcash-pill <?php echo !empty($store_gcash_qr) ? 'has-qr' : ''; ?>">
                                   
                                    
                                    <?php if (!empty($store_gcash_qr)): ?>
                                        <a id="store-gcash-qr-link" href="<?php echo htmlspecialchars($store_gcash_qr, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" style="margin-left:8px;">
                                            <img id="store-gcash-qr-thumb" class="gcash-qr-thumb" src="<?php echo htmlspecialchars($store_gcash_qr, ENT_QUOTES, 'UTF-8'); ?>" alt="GCash QR">
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                            </div>
                            <div class="gcash-instruction">After sending payment, attach your receipt below and include the reference number.</div>
                        </div>
                    </div>

                    <div id="payment-proof-row" class="payment-proof-row">
                        <div class="form-group">
                                <label for="payment_proof">Attach payment proof (screenshot) <span style="color:#dc2626">*</span></label>
                                <input type="file" id="payment_proof" name="payment_proof" accept="image/*" />
                            </div>
                        <div style="text-align:center;margin-top:8px;">
                            <img id="proof-preview" class="proof-preview" src="" alt="Proof preview" />
                        </div>
                    </div>

                    <!-- GCash QR view modal (hidden) -->
                    <div id="gcash-qr-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;z-index:4500;">
                        <div style="background:#fff;padding:12px;border-radius:10px;max-width:860px;width:92%;text-align:center;">
                            <div style="text-align:right;"><button id="gcash-qr-close" class="copy-btn" style="background:#f3f4f6;">Close</button></div>
                            <img id="gcash-qr-large" src="" alt="GCash QR" style="max-width:100%;width:auto;max-height:80vh;border-radius:8px;margin-top:8px;" />
                            <div style="margin-top:8px;color:#374151;font-size:13px;">Right click image & choose "Open image in new tab" to save.</div>
                        </div>
                    </div>
                </div>
                        <!-- Map modal for choosing delivery location -->
                        <div id="map-modal" aria-hidden="true">
                            <div class="map-card" role="dialog" aria-modal="true" aria-labelledby="map-modal-title">
                                <div class="map-header">
                                    <div style="font-weight:700;">Choose delivery location</div>
                                    <div class="map-actions">
                                        <button type="button" id="map-center-btn" class="copy-btn">Center</button>
                                        <button type="button" id="map-save-btn" class="btn-primary btn-sm">Save selection</button>
                                        <button type="button" id="map-close-btn" class="btn-secondary btn-sm">Cancel</button>
                                    </div>
                                </div>
                                <div id="map-container"></div>
                            </div>
                        </div>

                <div class="button-group">
                    <button id="place-order-btn" type="submit" class="btn-primary">Place Order</button>
                    <button type="button" class="btn-secondary" onclick="location.href='cart.php'">Edit Cart</button>
                </div>
                </form>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="order-summary">
                <div class="summary-title">Order Summary</div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="summary-amount">₱ <?php echo number_format((float)$subtotal,2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span class="summary-amount">₱ 0.00</span>
                </div>
                <div class="summary-row">
                    <span>Tax (12%)</span>
                    <span class="summary-amount">₱ <?php echo number_format((float)($subtotal * 0.12),2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total</span>
                    <span class="summary-amount">₱ <?php echo number_format((float)($subtotal * 1.12),2); ?></span>
                </div>

                <div class="shoes-animation-container">
                    <!-- SVG animation kept the same as before -->
                    <svg class="shoes-svg" width="130" height="130" viewBox="0 0 130 130" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <style>
                                @keyframes leftShoeFloat {
                                    0%, 100% { transform: translateY(0px) rotate(-8deg); }
                                    50% { transform: translateY(-15px) rotate(-8deg); }
                                }
                                @keyframes rightShoeFloat {
                                    0%, 100% { transform: translateY(0px) rotate(8deg); }
                                    50% { transform: translateY(-15px) rotate(8deg); }
                                }
                                .left-shoe {
                                    animation: leftShoeFloat 3s ease-in-out infinite;
                                    transform-origin: center;
                                }
                                .right-shoe {
                                    animation: rightShoeFloat 3s ease-in-out infinite;
                                    transform-origin: center;
                                }
                            </style>
                        </defs>
                        <!-- Left Shoe -->
                        <g class="left-shoe">
                            <ellipse cx="35" cy="85" rx="22" ry="8" fill="#1a1a1a" opacity="0.3"/>
                            <path d="M 18 70 Q 15 60 20 50 Q 25 45 32 48 Q 38 42 45 48 Q 48 52 45 65 Q 42 75 35 82 Q 28 85 18 70 Z" fill="#1a1a1a" stroke="#0d0d0d" stroke-width="1.5"/>
                            <path d="M 25 55 Q 28 52 35 54" fill="none" stroke="#e5e7eb" stroke-width="1.5" stroke-linecap="round"/>
                            <circle cx="32" cy="62" r="2.5" fill="#e5e7eb"/>
                        </g>
                        <!-- Right Shoe -->
                        <g class="right-shoe">
                            <ellipse cx="95" cy="85" rx="22" ry="8" fill="#1a1a1a" opacity="0.3"/>
                            <path d="M 112 70 Q 115 60 110 50 Q 105 45 98 48 Q 92 42 85 48 Q 82 52 85 65 Q 88 75 95 82 Q 102 85 112 70 Z" fill="#1a1a1a" stroke="#0d0d0d" stroke-width="1.5"/>
                            <path d="M 105 55 Q 102 52 95 54" fill="none" stroke="#e5e7eb" stroke-width="1.5" stroke-linecap="round"/>
                            <circle cx="98" cy="62" r="2.5" fill="#e5e7eb"/>
                        </g>
                        <circle cx="65" cy="65" r="55" fill="none" stroke="#1a1a1a" stroke-width="1" opacity="0.1"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Order modal (verifying / thank you) -->
    <div id="order-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="order-modal-title">
            <div id="modal-verifying" style="display:block;">
                <div style="font-size:14px;color:#6b7280;margin-bottom:14px;">Verifying payment</div>
                <div>
                    <span class="verifying-dot"></span>
                    <span class="verifying-dot"></span>
                    <span class="verifying-dot"></span>
                </div>
                <div id="modal-message" style="margin-top:16px;color:#374151;font-size:15px;">Please wait while we process your order...</div>
            </div>

            <div id="modal-success" style="display:none;">
                <div class="check-container" id="check-container" role="img" aria-hidden="true">
                    <svg class="check-svg" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 24l6 6 18-18" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div style="font-size:20px;font-weight:700;margin-top:8px;">Thank you for purchasing!</div>
                <div id="modal-order-id" style="margin-top:8px;color:#6b7280;">Order #...</div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        var STORE_GCASH = <?php echo json_encode($store_gcash); ?>;
        var STORE_GCASH_IMG = <?php echo json_encode($store_image ?? ''); ?>;
        var BANK_ACCOUNTS = <?php echo json_encode($bank_accounts); ?>;
        var OWNER_BANK = <?php echo json_encode($owner_bank); ?>;

        function updatePaymentFields() {
            var val = document.querySelector('input[name="payment"]:checked')?.value || 'gcash';
            var g = document.getElementById('payment-gcash');
            var b = document.getElementById('payment-bank');
            if (g) g.style.display = (val === 'gcash') ? 'block' : 'none';
            if (b) b.style.display = (val === 'bank') ? 'block' : 'none';
            var gcashInfo = document.getElementById('gcash-info');
            var proofRow = document.getElementById('payment-proof-row');
            if (gcashInfo) gcashInfo.style.display = (val === 'gcash') ? 'block' : 'none';
            if (proofRow) proofRow.style.display = (val === 'gcash' || val === 'bank') ? 'block' : 'none';
            var gcashTextEl = document.getElementById('store-gcash-text');
            if (gcashTextEl && (!gcashTextEl.textContent || gcashTextEl.textContent.trim() === '')) gcashTextEl.textContent = STORE_GCASH || '';
        }

        document.addEventListener('DOMContentLoaded', function(){
            updatePaymentFields();
            document.querySelectorAll('input[name="payment"]').forEach(function(r){
                r.addEventListener('change', updatePaymentFields);
            });

            // Bank select logic
            var bankSelect = document.getElementById('bank_name');
            var bankAccountInput = document.getElementById('bank_account_number');
            var bankAccountNameInput = document.getElementById('bank_account_name');
            var selectedBankDisplay = document.getElementById('selected-bank-display');

            function onBankChange() {
                if (!bankSelect) return;
                var val = bankSelect.value || '';
                if (val && BANK_ACCOUNTS && BANK_ACCOUNTS[val]) {
                    var b = BANK_ACCOUNTS[val];
                    if (bankAccountInput) bankAccountInput.value = b.account_number || '';
                    if (bankAccountNameInput) bankAccountNameInput.value = b.account_name || '';
                }
            }

            if (bankSelect) {
                bankSelect.addEventListener('change', onBankChange);
                onBankChange();
            }

            // Address editor wiring
            var changeBtn = document.getElementById('address-change-btn');
            var saveBtn = document.getElementById('address-save-btn');
            var cancelBtn = document.getElementById('address-cancel-btn');
            var editor = document.getElementById('address-editor');
            var display = document.getElementById('address-display');
            var msg = document.getElementById('address-msg');
            var hidden = document.getElementById('delivery_address_hidden');

            function showEditor() {
                if (!editor || !display) return;
                editor.style.display = 'block';
                display.style.display = 'none';
                changeBtn.style.display = 'none';
                saveBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';
                editor.focus();
            }

            function hideEditor() {
                if (!editor || !display) return;
                editor.style.display = 'none';
                display.style.display = 'block';
                changeBtn.style.display = 'inline-block';
                saveBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
                msg.style.display = 'none';
                try {
                    var current = (document.getElementById('delivery_address_hidden')?.value || '').trim();
                    if (!current) display.classList.add('empty'); else display.classList.remove('empty');
                } catch(e){}
            }

            if (changeBtn) changeBtn.addEventListener('click', function(){
                if (hidden && editor) editor.value = hidden.value || editor.value;
                showEditor();
            });

            if (cancelBtn) cancelBtn.addEventListener('click', function(){
                hideEditor();
            });

            if (saveBtn) saveBtn.addEventListener('click', function(){
                if (!editor) return;
                var val = editor.value.trim();
                if (val === '') {
                    msg.style.color = '#dc2626';
                    msg.textContent = 'Address cannot be empty';
                    msg.style.display = 'block';
                    return;
                }
                saveBtn.disabled = true;
                var fd = new FormData();
                fd.append('action', 'save_address');
                fd.append('delivery_address', val);
                // include lat/lng if chosen via map picker
                fd.append('latitude', document.getElementById('delivery_lat')?.value || '');
                fd.append('longitude', document.getElementById('delivery_lng')?.value || '');
                fetch('checkout.php', { method: 'POST', body: fd })
                    .then(function(resp){
                        return resp.text().then(function(text){
                            if (!text) return null;
                            try { return JSON.parse(text); }
                            catch (e) { console.warn('save_address: invalid JSON response', text); return { success: false, error: (text || 'Invalid JSON response') }; }
                        });
                    })
                    .then(function(json){
                        saveBtn.disabled = false;
                        if (json && json.success) {
                            var esc = (json.address || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                            if (display) {
                                display.classList.remove('empty');
                                display.innerHTML = (esc || '').replace(/\r?\n/g,'<br>');
                            }
                            if (hidden) hidden.value = json.address || '';
                            // Update lat/lng hidden fields if server returned them
                            if (json.latitude !== undefined) document.getElementById('delivery_lat').value = json.latitude;
                            if (json.longitude !== undefined) document.getElementById('delivery_lng').value = json.longitude;
                            hideEditor();
                            msg.style.color = '#16a34a';
                            msg.textContent = 'Address saved successfully!';
                            msg.style.display = 'block';
                            setTimeout(function(){ msg.style.display = 'none'; }, 3000);
                        } else {
                            msg.style.color = '#dc2626';
                            msg.textContent = (json && json.error) ? json.error : 'Save failed';
                            msg.style.display = 'block';
                        }
                    }).catch(function(err){
                        console.error('save_address network error', err);
                        saveBtn.disabled = false;
                        msg.style.color = '#dc2626';
                        msg.textContent = 'Network error';
                        msg.style.display = 'block';
                    });
            });

            // Map picker wiring (Leaflet + Nominatim reverse geocoding)
            var mapModal = document.getElementById('map-modal');
            var mapContainer = document.getElementById('map-container');
            var mapClose = document.getElementById('map-close-btn');
            var mapSave = document.getElementById('map-save-btn');
            var mapCenterBtn = document.getElementById('map-center-btn');
            var pickMapBtn = document.getElementById('address-pick-map');
            var deliveryLatEl = document.getElementById('delivery_lat');
            var deliveryLngEl = document.getElementById('delivery_lng');
            var _map = null;
            var _marker = null;

            function initMap(lat, lng) {
                try {
                    if (!_map) {
                        _map = L.map('map-container');
                        // ensure the container accepts pointer events
                        try { document.getElementById('map-container').style.pointerEvents = 'auto'; } catch(e){}
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(_map);
                    }
                    // Invalidate size so Leaflet knows the container dimensions (call multiple times defensively)
                    _map.invalidateSize();
                    _map.whenReady(function(){ setTimeout(function(){ try{ _map.invalidateSize(); }catch(e){} }, 120); });
                    if (lat != null && lng != null) {
                        _map.setView([lat, lng], 16);
                        placeMarker(lat, lng);
                    } else {
                        _map.setView([14.5995, 120.9842], 12); // fallback Manila
                    }
                } catch (e) { console.error('Map init error', e); }
                if (_map) {
                    // attach click handler (use both namespaced and plain listener for robustness)
                    _map.off('click.mapPick');
                    _map.on('click', function(ev){
                        var p = ev.latlng;
                        console.debug('map click at', p.lat, p.lng);
                        placeMarker(p.lat, p.lng);
                        reverseGeocodeAndFill(p.lat, p.lng);
                    });
                    _map.on('click.mapPick', function(ev){ /* noop placeholder for namespaced removal */ });
                }
            }

            function placeMarker(lat, lng) {
                if (!_map) return;
                if (_marker) _map.removeLayer(_marker);
                _marker = L.marker([lat, lng], { draggable: true }).addTo(_map);
                _marker.on('dragend', function(ev){
                    var ll = ev.target.getLatLng();
                    console.debug('marker dragged to', ll.lat, ll.lng);
                    deliveryLatEl.value = ll.lat.toFixed(6);
                    deliveryLngEl.value = ll.lng.toFixed(6);
                    reverseGeocodeAndFill(ll.lat, ll.lng);
                });
                deliveryLatEl.value = parseFloat(lat).toFixed(6);
                deliveryLngEl.value = parseFloat(lng).toFixed(6);
            }

            function reverseGeocodeAndFill(lat, lng) {
                var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);
                console.debug('reverse geocode url', url);
                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function(r){ return r.json(); })
                    .then(function(j){
                        console.debug('reverse geocode result', j);
                        if (j && j.display_name) {
                            var txt = j.display_name;
                            editor.value = txt;
                            if (document.getElementById('delivery_address_hidden')) document.getElementById('delivery_address_hidden').value = txt;
                        }
                    }).catch(function(e){ console.warn('Reverse geocode failed', e); });
            }

            var mapModalOpen = false;
            var _prevFocusEl = null;
            if (pickMapBtn) pickMapBtn.addEventListener('click', function(e){
                e.preventDefault();
                // set aria-hidden to false before showing visually so assistive tech knows modal is active
                mapModal.setAttribute('aria-hidden','false');
                try { _prevFocusEl = document.activeElement; } catch (ex) { _prevFocusEl = null; }
                mapModal.style.display = 'flex';
                mapModalOpen = true;
                setTimeout(function(){
                    // focus a logical control inside the modal for keyboard users
                    try { (document.getElementById('map-save-btn') || document.getElementById('map-close-btn')).focus(); } catch(e){}
                    var lat = parseFloat(deliveryLatEl.value) || null;
                    var lng = parseFloat(deliveryLngEl.value) || null;
                    if (!isFinite(lat) || !isFinite(lng)) { lat = null; lng = null; }
                    initMap(lat, lng);
                    if (lat === null && navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(function(pos){
                            try { _map.setView([pos.coords.latitude, pos.coords.longitude], 14); } catch(e){}
                        }, function(){}, { enableHighAccuracy:false, timeout:5000 });
                    }
                }, 160);
            });

            if (mapClose) mapClose.addEventListener('click', function(){
                try { if (document.activeElement) document.activeElement.blur(); } catch(e){}
                mapModal.style.display = 'none';
                mapModal.setAttribute('aria-hidden','true');
                mapModalOpen = false;
                try { if (_prevFocusEl && typeof _prevFocusEl.focus === 'function') _prevFocusEl.focus(); } catch(e){}
            });
            if (mapCenterBtn) mapCenterBtn.addEventListener('click', function(){ if (_map && _marker) _map.setView(_marker.getLatLng(), 16); else if (_map) _map.invalidateSize(); });
            if (mapSave) mapSave.addEventListener('click', function(){
                if (_marker) {
                    var ll = _marker.getLatLng();
                    deliveryLatEl.value = ll.lat.toFixed(6);
                    deliveryLngEl.value = ll.lng.toFixed(6);
                    reverseGeocodeAndFill(ll.lat, ll.lng);
                }
                try { if (document.activeElement) document.activeElement.blur(); } catch(e){}
                mapModal.style.display = 'none';
                mapModal.setAttribute('aria-hidden','true');
                mapModalOpen = false;
                try { if (_prevFocusEl && typeof _prevFocusEl.focus === 'function') _prevFocusEl.focus(); } catch(e){}
            });

            // Preview payment proof image
            var proofInput = document.getElementById('payment_proof');
            var proofPreview = document.getElementById('proof-preview');
            if (proofInput && proofPreview) {
                proofInput.addEventListener('change', function(e){
                    var f = proofInput.files && proofInput.files[0];
                    if (!f) { proofPreview.style.display = 'none'; proofPreview.src = ''; return; }
                    if (!f.type.startsWith('image/')) { proofPreview.style.display = 'none'; proofPreview.src = ''; return; }
                    var reader = new FileReader();
                    reader.onload = function(ev){ proofPreview.src = ev.target.result; proofPreview.style.display = 'inline-block'; };
                    reader.readAsDataURL(f);
                });
            }

            // copy store gcash number
            var copyBtn = document.getElementById('copy-gcash');
            if (copyBtn) {
                copyBtn.addEventListener('click', function(){
                    var gcashTextFallback = document.getElementById('store-gcash-text')?.textContent || '';
                    var txt = STORE_GCASH || gcashTextFallback || '';
                    if (!txt) return;
                    navigator.clipboard?.writeText(txt).then(function(){
                        var old = copyBtn.textContent;
                        copyBtn.textContent = 'Copied';
                        setTimeout(function(){ copyBtn.textContent = old; }, 1400);
                    }).catch(function(){
                        try { window.prompt('Copy GCash number:', txt); } catch(e){}
                    });
                });
            }

            // GCash QR thumbnail click -> open modal viewer
            var gcashQrThumb = document.getElementById('store-gcash-qr-thumb');
            var gcashQrLink = document.getElementById('store-gcash-qr-link');
            var gcashQrModal = document.getElementById('gcash-qr-modal');
            var gcashQrLarge = document.getElementById('gcash-qr-large');
            var gcashQrClose = document.getElementById('gcash-qr-close');
            if (gcashQrThumb && gcashQrModal && gcashQrLarge) {
                var src = (gcashQrLink && gcashQrLink.href) ? gcashQrLink.href : gcashQrThumb.src;
                gcashQrThumb.addEventListener('click', function(e){
                    e.preventDefault();
                    gcashQrLarge.src = src;
                    gcashQrModal.style.display = 'flex';
                });
            }
            if (gcashQrClose && gcashQrModal) {
                gcashQrClose.addEventListener('click', function(){ gcashQrModal.style.display = 'none'; gcashQrLarge.src = ''; });
            }
            if (gcashQrModal) {
                gcashQrModal.addEventListener('click', function(e){ if (e.target === gcashQrModal) { gcashQrModal.style.display = 'none'; gcashQrLarge.src = ''; } });
            }

            // Intercept form submit to call place_order.php via fetch (supports file upload)
            var checkoutForm = document.getElementById('checkout-form');
            var placeOrderBtn = document.getElementById('place-order-btn');
            var orderModal = document.getElementById('order-modal');
            var modalVerifying = document.getElementById('modal-verifying');
            var modalSuccess = document.getElementById('modal-success');
            var modalMessage = document.getElementById('modal-message');
            var checkContainer = document.getElementById('check-container');
            var modalOrderId = document.getElementById('modal-order-id');

            if (checkoutForm) {
                checkoutForm.addEventListener('submit', function(e){
                    // if the map modal is open, ignore submit to avoid accidental order placement
                    if (typeof mapModalOpen !== 'undefined' && mapModalOpen) {
                        e.preventDefault();
                        console.debug('Submit ignored because map modal is open');
                        return;
                    }
                    e.preventDefault();
                    if (!placeOrderBtn) return;
                    // client-side validation for selected payment
                    var selectedPayment = document.querySelector('input[name="payment"]:checked')?.value || 'gcash';
                    if (selectedPayment === 'gcash') {
                        var gnum = (document.getElementById('gcash_number')?.value || '').trim();
                        if (!gnum) {
                            // show inline modal message for missing gcash number
                            orderModal.style.display = 'flex';
                            orderModal.setAttribute('aria-hidden','false');
                            modalVerifying.style.display = 'block';
                            modalSuccess.style.display = 'none';
                            modalMessage.textContent = 'Please enter your GCash mobile number.';
                            setTimeout(function(){ orderModal.style.display = 'none'; }, 3000);
                            return;
                        }
                        // require payment proof for GCash
                        var proofEl = document.getElementById('payment_proof');
                        if (proofEl && !(proofEl.files && proofEl.files.length)) {
                            orderModal.style.display = 'flex';
                            orderModal.setAttribute('aria-hidden','false');
                            modalVerifying.style.display = 'block';
                            modalSuccess.style.display = 'none';
                            modalMessage.textContent = 'Please attach a payment proof (screenshot).';
                            setTimeout(function(){ orderModal.style.display = 'none'; }, 2200);
                            return;
                        }
                    } else if (selectedPayment === 'bank') {
                        var bnum = (document.getElementById('bank_account_number')?.value || '').trim();
                        var bname = (document.getElementById('bank_account_name')?.value || '').trim();
                        var bsel = (document.getElementById('bank_name')?.value || '').trim();
                        if (!bsel || !bnum || !bname) {
                            orderModal.style.display = 'flex';
                            orderModal.setAttribute('aria-hidden','false');
                            modalVerifying.style.display = 'block';
                            modalSuccess.style.display = 'none';
                            modalMessage.textContent = 'Please select a bank and provide account name and number.';
                            setTimeout(function(){ orderModal.style.display = 'none'; }, 3000);
                            return;
                        }
                        // require payment proof for bank transfer as well
                        var proofElB = document.getElementById('payment_proof');
                        if (proofElB && !(proofElB.files && proofElB.files.length)) {
                            orderModal.style.display = 'flex';
                            orderModal.setAttribute('aria-hidden','false');
                            modalVerifying.style.display = 'block';
                            modalSuccess.style.display = 'none';
                            modalMessage.textContent = 'Please attach a payment proof (screenshot).';
                            setTimeout(function(){ orderModal.style.display = 'none'; }, 3000);
                            return;
                        }
                    }
                    placeOrderBtn.disabled = true;
                    modalOrderId.textContent = '';
                    modalMessage.textContent = 'Please wait while we process your order...';
                    modalVerifying.style.display = 'block';
                    modalSuccess.style.display = 'none';
                    orderModal.style.display = 'flex';
                    orderModal.setAttribute('aria-hidden','false');

                    // mark verify start time so we can ensure a minimum "verifying" duration
                    var verifyStart = Date.now();

                    var formData = new FormData(checkoutForm);

                    fetch('place_order.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(function(resp){
                        return resp.text().then(function(text){
                            var parsed = null;
                            try {
                                parsed = text ? JSON.parse(text) : null;
                            } catch(e) {
                                // not JSON — keep raw text as error message
                                parsed = { success: false, error: text || ('HTTP ' + resp.status) };
                            }
                            return { ok: resp.ok, status: resp.status, body: parsed, raw: text };
                        });
                    })
                    .then(function(result){
                        var json = result.body;
                        // ensure verifying is shown for at least 5000ms
                        var elapsed = Date.now() - verifyStart;
                        var wait = Math.max(0, 5000 - elapsed);
                        setTimeout(function(){
                            if (json && json.success) {
                                var oid = json.order_id || '';
                                modalVerifying.style.display = 'none';
                                modalSuccess.style.display = 'block';
                                // animate check
                                setTimeout(function(){ checkContainer.classList.add('visible'); }, 60);
                                modalOrderId.textContent = oid ? ('Order #' + oid) : '';
                                setTimeout(function(){
                                    orderModal.style.display = 'none';
                                    placeOrderBtn.disabled = false;
                                    var redirect = (json && json.redirect) ? json.redirect : ('order.php?order_id=' + oid);
                                    window.location.href = redirect;
                                }, 5000);
                            } else {
                                var errMsg = (json && json.error) ? json.error : ('Server error (HTTP ' + result.status + ')');
                                modalVerifying.style.display = 'block';
                                modalSuccess.style.display = 'none';
                                modalMessage.textContent = errMsg;
                                placeOrderBtn.disabled = false;
                                setTimeout(function(){ orderModal.style.display = 'none'; }, 4000);
                            }
                        }, wait);
                    })
                    .catch(function(err){
                        var msg = 'Network error. Please try again.' + (err && err.message ? ' (' + err.message + ')' : '');
                        modalMessage.textContent = msg;
                        placeOrderBtn.disabled = false;
                        setTimeout(function(){ orderModal.style.display = 'none'; }, 3000);
                    });
                });
            }

        });
    </script>
</body>
</html>
<?php
?>