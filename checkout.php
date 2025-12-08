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
    <meta name="description" content="Complete your purchase securely">
    <meta name="theme-color" content="#6366f1">
    <title>Checkout - <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <!-- Leaflet CSS for map picker -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="asset/style/animations.css">
    <?php echo getStoreThemeCSS(); ?>
    <style>
/* ============================================
   CHECKOUT PAGE - MODERN REDESIGN
   ============================================ */

:root {
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --primary-light: #818cf8;
    --accent: #14b8a6;
    --accent-dark: #0d9488;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;
    --bg-body: #f1f5f9;
    --bg-white: #ffffff;
    --bg-card: rgba(255, 255, 255, 0.95);
    --bg-subtle: #f8fafc;
    --border-light: #e2e8f0;
    --border-medium: #cbd5e1;
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 24px;
    --transition-fast: 150ms ease;
    --transition-normal: 250ms ease;
    --transition-smooth: 300ms cubic-bezier(0.4, 0, 0.2, 1);
}

*, *::before, *::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: linear-gradient(135deg, #f0f4ff 0%, #e0f2fe 50%, #f0fdf4 100%);
    background-attachment: fixed;
    min-height: 100vh;
    color: var(--text-primary);
    line-height: 1.6;
}

/* Focus States */
*:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

/* ============================================
   LAYOUT
   ============================================ */
.checkout-wrapper {
    min-height: 100vh;
    padding: 2rem 1rem;
}

.checkout-container {
    max-width: 1280px;
    margin: 0 auto;
}

/* Header */
.checkout-header {
    text-align: center;
    margin-bottom: 2.5rem;
    padding: 2rem;
    background: var(--bg-card);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-light);
}

.checkout-header h1 {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.checkout-header p {
    color: var(--text-secondary);
    font-size: 1rem;
}

.checkout-steps {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 1.5rem;
}

.step {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-muted);
}

.step.active {
    color: var(--primary);
    font-weight: 600;
}

.step-number {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--border-light);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.75rem;
}

.step.active .step-number {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
}

.step.completed .step-number {
    background: var(--success);
    color: white;
}

/* Content Grid */
.checkout-content {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 2rem;
    align-items: start;
}

.checkout-main {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* ============================================
   CARD COMPONENTS
   ============================================ */
.card {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-light);
    overflow: hidden;
    transition: box-shadow var(--transition-normal);
}

.card:hover {
    box-shadow: var(--shadow-lg);
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(20, 184, 166, 0.03) 100%);
    border-bottom: 1px solid var(--border-light);
}

.card-title {
    font-size: 1.125rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--text-primary);
}

.card-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.card-body {
    padding: 1.5rem;
}

/* ============================================
   ADDRESS SECTION
   ============================================ */
.address-display {
    padding: 1rem 1.25rem;
    background: var(--bg-subtle);
    border-radius: var(--radius-md);
    border-left: 4px solid var(--primary);
    white-space: pre-wrap;
    word-break: break-word;
    color: var(--text-primary);
    font-size: 0.95rem;
    line-height: 1.6;
}

.address-display.empty {
    color: var(--text-muted);
    font-style: italic;
}

.address-editor {
    width: 100%;
    min-height: 100px;
    padding: 1rem;
    border: 2px solid var(--border-light);
    border-radius: var(--radius-md);
    font-family: inherit;
    font-size: 0.95rem;
    resize: vertical;
    display: none;
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.address-editor:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.address-msg {
    padding: 0.75rem 1rem;
    border-radius: var(--radius-sm);
    margin-top: 1rem;
    font-size: 0.875rem;
    display: none;
}

.address-msg.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.address-msg.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.address-actions {
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

/* ============================================
   PRODUCTS SECTION
   ============================================ */
.products-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.product-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-subtle);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-light);
    transition: transform var(--transition-fast), box-shadow var(--transition-fast);
}

.product-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.product-image {
    width: 100px;
    height: 100px;
    background: var(--border-light);
    border-radius: var(--radius-md);
    flex-shrink: 0;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.product-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.product-variant {
    font-size: 0.8rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.product-variant span {
    background: var(--border-light);
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
}

.product-price {
    font-weight: 700;
    font-size: 1.125rem;
    color: var(--primary);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    min-width: 100px;
}

.empty-state {
    text-align: center;
    padding: 3rem 1.5rem;
    color: var(--text-muted);
}

.empty-state-icon {
    font-size: 3.5rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state p {
    font-size: 1rem;
}

/* ============================================
   PAYMENT SECTION
   ============================================ */
.payment-methods {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.payment-option {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    border: 2px solid var(--border-light);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition-fast);
    background: var(--bg-white);
}

.payment-option:hover {
    border-color: var(--primary-light);
    background: rgba(99, 102, 241, 0.02);
}

.payment-option.selected {
    border-color: var(--primary);
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(99, 102, 241, 0.02) 100%);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.payment-option input[type="radio"] {
    display: none;
}

.payment-radio {
    width: 22px;
    height: 22px;
    border: 2px solid var(--border-medium);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all var(--transition-fast);
}

.payment-option.selected .payment-radio {
    border-color: var(--primary);
}

.payment-radio::after {
    content: '';
    width: 10px;
    height: 10px;
    background: var(--primary);
    border-radius: 50%;
    opacity: 0;
    transform: scale(0);
    transition: all var(--transition-fast);
}

.payment-option.selected .payment-radio::after {
    opacity: 1;
    transform: scale(1);
}

.payment-icon {
    width: 44px;
    height: 44px;
    background: var(--bg-subtle);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.payment-info {
    flex: 1;
}

.payment-label {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
}

.payment-desc {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 0.125rem;
}

/* Payment Fields */
.payment-fields {
    margin-top: 1.5rem;
    padding: 1.25rem;
    background: var(--bg-subtle);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-light);
    display: none;
}

.payment-fields.visible {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Form Fields */
.form-group {
    margin-bottom: 1.25rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.form-label .required {
    color: var(--danger);
}

.form-input,
.form-select {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--border-light);
    border-radius: var(--radius-md);
    font-size: 0.95rem;
    font-family: inherit;
    transition: all var(--transition-fast);
    background: var(--bg-white);
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.form-input::placeholder {
    color: var(--text-muted);
}

/* GCash Info Section */
.gcash-payment-info {
    margin-top: 1.5rem;
    padding: 1.25rem;
    background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
    border-radius: var(--radius-md);
    border: 1px solid #bae6fd;
    display: none;
}

.gcash-payment-info.visible {
    display: block;
}

.gcash-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.gcash-header h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #0369a1;
}

.gcash-qr-container {
    text-align: center;
    padding: 1rem;
    background: var(--bg-white);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-light);
}

.gcash-qr-thumb {
    max-width: 280px;
    width: 100%;
    height: auto;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: transform var(--transition-fast);
}

.gcash-qr-thumb:hover {
    transform: scale(1.02);
}

.gcash-instruction {
    margin-top: 1rem;
    padding: 0.75rem;
    background: #fef3c7;
    border-radius: var(--radius-sm);
    border: 1px solid #fcd34d;
    font-size: 0.85rem;
    color: #92400e;
    text-align: center;
}

/* Payment Proof */
.proof-upload {
    margin-top: 1.5rem;
    padding: 1.25rem;
    background: var(--bg-subtle);
    border-radius: var(--radius-md);
    border: 2px dashed var(--border-medium);
    text-align: center;
    display: none;
}

.proof-upload.visible {
    display: block;
}

.proof-upload-label {
    display: block;
    cursor: pointer;
    padding: 1rem;
}

.proof-upload-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.proof-upload-text {
    font-size: 0.95rem;
    color: var(--text-secondary);
}

.proof-upload-hint {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.proof-preview {
    max-width: 200px;
    max-height: 150px;
    border-radius: var(--radius-sm);
    display: none;
    margin: 1rem auto 0;
    border: 2px solid var(--border-light);
}

/* ============================================
   ORDER SUMMARY SIDEBAR
   ============================================ */
.order-summary {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-light);
    position: sticky;
    top: 2rem;
    overflow: hidden;
}

.summary-header {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
}

.summary-header h2 {
    font-size: 1.125rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.summary-body {
    padding: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.875rem 0;
    border-bottom: 1px solid var(--border-light);
    font-size: 0.95rem;
}

.summary-row:last-of-type {
    border-bottom: none;
}

.summary-row.total {
    margin-top: 0.5rem;
    padding-top: 1rem;
    border-top: 2px solid var(--border-light);
    border-bottom: none;
    font-size: 1.25rem;
    font-weight: 700;
}

.summary-row .label {
    color: var(--text-secondary);
}

.summary-row .value {
    font-weight: 600;
    color: var(--text-primary);
}

.summary-row.total .value {
    color: var(--primary);
    font-size: 1.5rem;
}

/* Summary Footer */
.summary-footer {
    padding: 1.5rem;
    background: var(--bg-subtle);
    border-top: 1px solid var(--border-light);
}

.summary-actions {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* Shoes Animation */
.shoes-animation {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 1.5rem;
    border-top: 1px solid var(--border-light);
}

/* ============================================
   BUTTONS
   ============================================ */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: var(--radius-md);
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-fast);
    font-family: inherit;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.btn-secondary {
    background: var(--bg-white);
    color: var(--text-primary);
    border: 2px solid var(--border-light);
}

.btn-secondary:hover {
    background: var(--bg-subtle);
    border-color: var(--primary);
    color: var(--primary);
}

.btn-sm {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
}

.btn-block {
    width: 100%;
}

.btn-icon {
    padding: 0.625rem;
}

/* ============================================
   MODALS
   ============================================ */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 6000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.modal-card {
    background: var(--bg-white);
    border-radius: var(--radius-lg);
    padding: 2rem;
    max-width: 480px;
    width: 100%;
    text-align: center;
    box-shadow: var(--shadow-xl);
    animation: modalIn 0.3s ease;
}

@keyframes modalIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Order Modal */
.verifying-animation {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin: 1.5rem 0;
}

.verifying-dot {
    width: 12px;
    height: 12px;
    background: var(--primary);
    border-radius: 50%;
    animation: bounce 1.4s infinite ease-in-out both;
}

.verifying-dot:nth-child(1) { animation-delay: -0.32s; }
.verifying-dot:nth-child(2) { animation-delay: -0.16s; }
.verifying-dot:nth-child(3) { animation-delay: 0; }

@keyframes bounce {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.5; }
    40% { transform: scale(1); opacity: 1; }
}

.check-container {
    width: 100px;
    height: 100px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--success), #059669);
    display: flex;
    align-items: center;
    justify-content: center;
    transform: scale(0);
    opacity: 0;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 8px 30px rgba(16, 185, 129, 0.3);
}

.check-container.visible {
    transform: scale(1);
    opacity: 1;
}

.check-container svg {
    width: 50px;
    height: 50px;
    color: white;
}

/* Map Modal */
#map-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    z-index: 6500;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

#map-modal .map-card {
    width: 95%;
    max-width: 1000px;
    height: 85vh;
    background: var(--bg-white);
    border-radius: var(--radius-lg);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: var(--shadow-xl);
}

#map-modal .map-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    background: var(--bg-subtle);
    border-bottom: 1px solid var(--border-light);
}

#map-modal .map-header h3 {
    font-size: 1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

#map-modal .map-actions {
    display: flex;
    gap: 0.5rem;
}

#map-container {
    flex: 1;
}

/* GCash QR Modal */
#gcash-qr-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.7);
    backdrop-filter: blur(4px);
    z-index: 4500;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

#gcash-qr-modal .qr-modal-card {
    background: var(--bg-white);
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    max-width: 500px;
    width: 95%;
    text-align: center;
    box-shadow: var(--shadow-xl);
}

#gcash-qr-modal .qr-modal-card img {
    max-width: 100%;
    max-height: 70vh;
    border-radius: var(--radius-md);
}

/* ============================================
   RESPONSIVE DESIGN
   ============================================ */
@media (max-width: 1024px) {
    .checkout-content {
        grid-template-columns: 1fr;
    }
    
    .order-summary {
        position: static;
    }
}

@media (max-width: 768px) {
    .checkout-wrapper {
        padding: 1rem 0.75rem;
    }
    
    .checkout-header {
        padding: 1.5rem 1rem;
        border-radius: var(--radius-lg);
    }
    
    .checkout-header h1 {
        font-size: 1.5rem;
    }
    
    .checkout-steps {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .card-header {
        padding: 1rem 1.25rem;
    }
    
    .card-body {
        padding: 1.25rem;
    }
    
    .product-item {
        flex-direction: column;
        text-align: center;
    }
    
    .product-image {
        width: 80px;
        height: 80px;
        margin: 0 auto;
    }
    
    .product-price {
        justify-content: center;
        margin-top: 0.5rem;
    }
    
    .summary-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .address-actions {
        flex-direction: column;
    }
    
    .address-actions .btn {
        width: 100%;
    }
    
    .gcash-qr-thumb {
        max-width: 200px;
    }
}


@keyframes leftShoeFloat {
    0%, 100% { transform: translateY(0px) rotate(-8deg); }
    50% { transform: translateY(-12px) rotate(-8deg); }
}

@keyframes rightShoeFloat {
    0%, 100% { transform: translateY(0px) rotate(8deg); }
    50% { transform: translateY(-12px) rotate(8deg); }
}

.left-shoe { animation: leftShoeFloat 3s ease-in-out infinite; transform-origin: center; }
.right-shoe { animation: rightShoeFloat 3s ease-in-out infinite; transform-origin: center; animation-delay: 0.3s; }
    </style>
</head>
<body>
    <div class="checkout-wrapper">
        <div class="checkout-container">
            <?php if (!empty($debug_html)) { echo $debug_html; } ?>
            
            <!-- Header -->
            <header class="checkout-header">
                <h1>
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    Checkout
                </h1>
                <p>Complete your order securely</p>
                <div class="checkout-steps">
                    <div class="step completed">
                        <span class="step-number">✓</span>
                        <span>Cart</span>
                    </div>
                    <div class="step active">
                        <span class="step-number">2</span>
                        <span>Checkout</span>
                    </div>
                    <div class="step">
                        <span class="step-number">3</span>
                        <span>Confirmation</span>
                    </div>
                </div>
            </header>

            <div class="checkout-content">
                <div class="checkout-main">
                    <form id="checkout-form" method="POST" action="place_order.php" enctype="multipart/form-data">
                        
                        <!-- Delivery Address Card -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">
                                    <div class="card-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                            <circle cx="12" cy="10" r="3"/>
                                        </svg>
                                    </div>
                                    Delivery Address
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="address-display" class="address-display <?php echo empty($delivery_address) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($delivery_address ?? 'No address saved yet. Please add your delivery address.', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <textarea id="address-editor" class="address-editor form-input"><?php echo htmlspecialchars($delivery_address ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                                <input type="hidden" id="delivery_address_hidden" name="delivery_address" value="<?php echo htmlspecialchars($delivery_address ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <div id="address-msg" class="address-msg"></div>
                                <div class="address-actions">
                                    <button id="address-change-btn" class="btn btn-secondary btn-sm" type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                        Edit Address
                                    </button>
                                    <button id="address-pick-map" class="btn btn-secondary btn-sm" type="button">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
                                            <line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/>
                                        </svg>
                                        Pick on Map
                                    </button>
                                    <button id="address-save-btn" class="btn btn-primary btn-sm" type="button" style="display: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                            <polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                                        </svg>
                                        Save
                                    </button>
                                    <button id="address-cancel-btn" class="btn btn-secondary btn-sm" type="button" style="display: none;">Cancel</button>
                                </div>
                                <input type="hidden" id="delivery_lat" name="delivery_lat" value="">
                                <input type="hidden" id="delivery_lng" name="delivery_lng" value="">
                            </div>
                        </div>

                        <!-- Products Card -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">
                                    <div class="card-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                            <line x1="12" y1="22.08" x2="12" y2="12"/>
                                        </svg>
                                    </div>
                                    Your Items
                                </div>
                                <span style="font-size: 0.875rem; color: var(--text-muted);"><?php echo count($products); ?> item(s)</span>
                            </div>
                            <div class="card-body">
                                <div class="products-list" id="products-list">
                                    <?php if (empty($products)): ?>
                                        <div class="empty-state">
                                            <div class="empty-state-icon">👟</div>
                                            <p>No items selected for checkout</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($products as $p): ?>
                                            <div class="product-item">
                                                <div class="product-image">
                                                    <img src="<?php echo htmlspecialchars($p['image_url'] ?? 'upload/product-image/placeholder.png', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </div>
                                                <div class="product-details">
                                                    <div class="product-name"><?php echo htmlspecialchars($p['product_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                    <div class="product-variant">
                                                        <span><?php echo htmlspecialchars($p['color_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                                                        <span><?php echo htmlspecialchars($p['size_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                                                    </div>
                                                </div>
                                                <div class="product-price">₱<?php echo number_format($p['price'], 2); ?></div>
                                            </div>
                                            <input type="hidden" name="selected_items[]" value="<?php echo intval($p['variant_id']); ?>">
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Card -->
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">
                                    <div class="card-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                            <line x1="1" y1="10" x2="23" y2="10"/>
                                        </svg>
                                    </div>
                                    Payment Method
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="payment-methods">
                                    <label class="payment-option selected">
                                        <input type="radio" name="payment" value="gcash" checked>
                                        <span class="payment-radio"></span>
                                        <span class="payment-icon">📱</span>
                                        <span class="payment-info">
                                            <span class="payment-label">GCash</span>
                                            <span class="payment-desc">Pay via mobile wallet</span>
                                        </span>
                                    </label>
                                    <label class="payment-option">
                                        <input type="radio" name="payment" value="bank">
                                        <span class="payment-radio"></span>
                                        <span class="payment-icon">🏦</span>
                                        <span class="payment-info">
                                            <span class="payment-label">Bank Transfer</span>
                                            <span class="payment-desc">Transfer to our bank account</span>
                                        </span>
                                    </label>
                                </div>

                                <!-- GCash Fields -->
                                <div id="payment-gcash" class="payment-fields visible">
                                    <div class="form-group">
                                        <label class="form-label" for="gcash_number">Your GCash Number <span class="required">*</span></label>
                                        <input type="tel" id="gcash_number" name="gcash_number" class="form-input" placeholder="09XX XXX XXXX">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="gcash_ref">Reference Number (optional)</label>
                                        <input type="text" id="gcash_ref" name="gcash_ref" class="form-input" placeholder="Enter transaction reference">
                                    </div>
                                </div>

                                <!-- Bank Transfer Fields -->
                                <div id="payment-bank" class="payment-fields">
                                    <div class="form-group">
                                        <label class="form-label" for="bank_name">Select Bank <span class="required">*</span></label>
                                        <select id="bank_name" name="bank_name" class="form-select">
                                            <option value="">Choose a bank...</option>
                                            <?php
                                            if (!empty($bank_accounts)) {
                                                foreach ($bank_accounts as $bcode => $binfo) {
                                                    $label = htmlspecialchars($binfo['bank_name'] ?: $bcode, ENT_QUOTES, 'UTF-8');
                                                    echo "<option value=\"" . htmlspecialchars($bcode, ENT_QUOTES, 'UTF-8') . "\">$label</option>\n";
                                                }
                                            } else {
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
                                        <label class="form-label" for="bank_account_name">Account Holder Name <span class="required">*</span></label>
                                        <input type="text" id="bank_account_name" name="bank_account_name" class="form-input" placeholder="Enter account holder name">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="bank_account_number">Account Number <span class="required">*</span></label>
                                        <input type="text" id="bank_account_number" name="bank_account_number" class="form-input" placeholder="Enter account number">
                                    </div>
                                </div>

                                <!-- GCash Payment Info -->
                                <div id="gcash-info" class="gcash-payment-info">
                                    <div class="gcash-header">
                                        <span style="font-size: 1.5rem;">💳</span>
                                        <h4>Send Payment To</h4>
                                    </div>
                                    <?php if (!empty($store_gcash_qr)): ?>
                                        <div class="gcash-qr-container">
                                            <a id="store-gcash-qr-link" href="<?php echo htmlspecialchars($store_gcash_qr, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
                                                <img id="store-gcash-qr-thumb" class="gcash-qr-thumb" src="<?php echo htmlspecialchars($store_gcash_qr, ENT_QUOTES, 'UTF-8'); ?>" alt="GCash QR Code">
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <div class="gcash-instruction">
                                        📋 After sending payment, attach your receipt screenshot below and include the reference number.
                                    </div>
                                </div>

                                <!-- Payment Proof Upload -->
                                <div id="payment-proof-row" class="proof-upload">
                                    <label class="proof-upload-label" for="payment_proof">
                                        <div class="proof-upload-icon">📸</div>
                                        <div class="proof-upload-text">Upload Payment Proof</div>
                                        <div class="proof-upload-hint">Click to select or drag & drop your screenshot</div>
                                    </label>
                                    <input type="file" id="payment_proof" name="payment_proof" accept="image/*" style="display: none;">
                                    <img id="proof-preview" class="proof-preview" src="" alt="Proof preview">
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons (Mobile) -->
                        <div class="card" style="display: none;" id="mobile-actions">
                            <div class="card-body">
                                <div class="summary-actions">
                                    <button id="place-order-btn-mobile" type="submit" class="btn btn-primary btn-block">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M5 12h14"/><path d="M12 5l7 7-7 7"/>
                                        </svg>
                                        Place Order
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-block" onclick="location.href='cart.php'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/>
                                        </svg>
                                        Back to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Order Summary Sidebar -->
                <aside class="order-summary">
                    <div class="summary-header">
                        <h2>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                            </svg>
                            Order Summary
                        </h2>
                    </div>
                    <div class="summary-body">
                        <div class="summary-row">
                            <span class="label">Subtotal</span>
                            <span class="value">₱<?php echo number_format((float)$subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Shipping</span>
                            <span class="value" style="color: var(--success);">FREE</span>
                        </div>
                        <div class="summary-row">
                            <span class="label">Tax (12% VAT)</span>
                            <span class="value">₱<?php echo number_format((float)($subtotal * 0.12), 2); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span class="label">Total</span>
                            <span class="value">₱<?php echo number_format((float)($subtotal * 1.12), 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="summary-footer">
                        <div class="summary-actions">
                            <button id="place-order-btn" type="submit" form="checkout-form" class="btn btn-primary btn-block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                    <line x1="1" y1="10" x2="23" y2="10"/>
                                </svg>
                                Complete Purchase
                            </button>
                            <button type="button" class="btn btn-secondary btn-block" onclick="location.href='cart.php'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 12H5"/><polyline points="12 19 5 12 12 5"/>
                                </svg>
                                Edit Cart
                            </button>
                        </div>
                    </div>
                    
                    <div class="shoes-animation">
                        <svg width="120" height="120" viewBox="0 0 130 130" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g class="left-shoe">
                                <ellipse cx="35" cy="85" rx="22" ry="8" fill="#6366f1" opacity="0.2"/>
                                <path d="M 18 70 Q 15 60 20 50 Q 25 45 32 48 Q 38 42 45 48 Q 48 52 45 65 Q 42 75 35 82 Q 28 85 18 70 Z" fill="#6366f1" stroke="#4f46e5" stroke-width="1.5"/>
                                <path d="M 25 55 Q 28 52 35 54" fill="none" stroke="#e0e7ff" stroke-width="1.5" stroke-linecap="round"/>
                                <circle cx="32" cy="62" r="2.5" fill="#e0e7ff"/>
                            </g>
                            <g class="right-shoe">
                                <ellipse cx="95" cy="85" rx="22" ry="8" fill="#6366f1" opacity="0.2"/>
                                <path d="M 112 70 Q 115 60 110 50 Q 105 45 98 48 Q 92 42 85 48 Q 82 52 85 65 Q 88 75 95 82 Q 102 85 112 70 Z" fill="#6366f1" stroke="#4f46e5" stroke-width="1.5"/>
                                <path d="M 105 55 Q 102 52 95 54" fill="none" stroke="#e0e7ff" stroke-width="1.5" stroke-linecap="round"/>
                                <circle cx="98" cy="62" r="2.5" fill="#e0e7ff"/>
                            </g>
                        </svg>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    <!-- Order Processing Modal -->
    <div id="order-modal" class="modal-overlay" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true">
            <div id="modal-verifying">
                <div style="font-size: 1rem; color: var(--text-secondary); margin-bottom: 0.5rem;">Processing Your Order</div>
                <div class="verifying-animation">
                    <span class="verifying-dot"></span>
                    <span class="verifying-dot"></span>
                    <span class="verifying-dot"></span>
                </div>
                <div id="modal-message" style="color: var(--text-primary); font-size: 0.95rem;">Please wait while we verify your payment...</div>
            </div>
            <div id="modal-success" style="display: none;">
                <div class="check-container" id="check-container">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">Order Placed!</div>
                <div style="color: var(--text-secondary); margin-top: 0.5rem;">Thank you for your purchase</div>
                <div id="modal-order-id" style="margin-top: 1rem; padding: 0.75rem 1.5rem; background: var(--bg-subtle); border-radius: var(--radius-sm); font-weight: 600; color: var(--primary);">Order #...</div>
            </div>
        </div>
    </div>

    <!-- Map Modal -->
    <div id="map-modal" aria-hidden="true">
        <div class="map-card" role="dialog" aria-modal="true">
            <div class="map-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    Select Delivery Location
                </h3>
                <div class="map-actions">
                    <button type="button" id="map-center-btn" class="btn btn-secondary btn-sm">Center</button>
                    <button type="button" id="map-save-btn" class="btn btn-primary btn-sm">Confirm Location</button>
                    <button type="button" id="map-close-btn" class="btn btn-secondary btn-sm">Cancel</button>
                </div>
            </div>
            <div id="map-container"></div>
        </div>
    </div>

    <!-- GCash QR Modal -->
    <div id="gcash-qr-modal">
        <div class="qr-modal-card">
            <div style="text-align: right; margin-bottom: 0.5rem;">
                <button id="gcash-qr-close" class="btn btn-secondary btn-sm">Close</button>
            </div>
            <img id="gcash-qr-large" src="" alt="GCash QR Code">
            <div style="margin-top: 1rem; color: var(--text-muted); font-size: 0.85rem;">
                Scan this QR code with your GCash app to pay
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
            
            // Update payment fields visibility
            if (g) g.classList.toggle('visible', val === 'gcash');
            if (b) b.classList.toggle('visible', val === 'bank');
            
            var gcashInfo = document.getElementById('gcash-info');
            var proofRow = document.getElementById('payment-proof-row');
            if (gcashInfo) gcashInfo.classList.toggle('visible', val === 'gcash');
            if (proofRow) proofRow.classList.toggle('visible', val === 'gcash' || val === 'bank');
            
            // Update payment option selected states
            document.querySelectorAll('.payment-option').forEach(function(opt) {
                var radio = opt.querySelector('input[type="radio"]');
                opt.classList.toggle('selected', radio && radio.checked);
            });
        }

        document.addEventListener('DOMContentLoaded', function(){
            updatePaymentFields();
            
            // Payment method change handlers
            document.querySelectorAll('input[name="payment"]').forEach(function(r){
                r.addEventListener('change', updatePaymentFields);
            });
            
            // Also handle clicks on payment option labels
            document.querySelectorAll('.payment-option').forEach(function(opt) {
                opt.addEventListener('click', function() {
                    var radio = opt.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        updatePaymentFields();
                    }
                });
            });

            // Bank select logic
            var bankSelect = document.getElementById('bank_name');
            var bankAccountInput = document.getElementById('bank_account_number');
            var bankAccountNameInput = document.getElementById('bank_account_name');

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
                saveBtn.style.display = 'inline-flex';
                cancelBtn.style.display = 'inline-flex';
                editor.focus();
            }

            function hideEditor() {
                if (!editor || !display) return;
                editor.style.display = 'none';
                display.style.display = 'block';
                changeBtn.style.display = 'inline-flex';
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
                    msg.className = 'address-msg error';
                    msg.textContent = 'Address cannot be empty';
                    msg.style.display = 'block';
                    return;
                }
                saveBtn.disabled = true;
                var fd = new FormData();
                fd.append('action', 'save_address');
                fd.append('delivery_address', val);
                fd.append('latitude', document.getElementById('delivery_lat')?.value || '');
                fd.append('longitude', document.getElementById('delivery_lng')?.value || '');
                fetch('checkout.php', { method: 'POST', body: fd })
                    .then(function(resp){
                        return resp.text().then(function(text){
                            if (!text) return null;
                            try { return JSON.parse(text); }
                            catch (e) { return { success: false, error: (text || 'Invalid JSON response') }; }
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
                            if (json.latitude !== undefined) document.getElementById('delivery_lat').value = json.latitude;
                            if (json.longitude !== undefined) document.getElementById('delivery_lng').value = json.longitude;
                            hideEditor();
                            msg.className = 'address-msg success';
                            msg.textContent = 'Address saved successfully!';
                            msg.style.display = 'block';
                            setTimeout(function(){ msg.style.display = 'none'; }, 3000);
                        } else {
                            msg.className = 'address-msg error';
                            msg.textContent = (json && json.error) ? json.error : 'Save failed';
                            msg.style.display = 'block';
                        }
                    }).catch(function(err){
                        console.error('save_address network error', err);
                        saveBtn.disabled = false;
                        msg.className = 'address-msg error';
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
    <?php include __DIR__ . '/partials/chatbot.php'; ?>
</body>
</html>
<?php
?>