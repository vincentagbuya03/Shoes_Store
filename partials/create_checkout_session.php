<?php
// Create a Stripe Checkout Session and return the URL (JSON).
// Expects POST: amount (in cents), currency (optional), name (optional)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/stripe.php';

$cfg = require __DIR__ . '/../config/stripe.php';
$secret = $cfg['secret'] ?? '';
$baseUrl = rtrim($cfg['base_url'] ?? '', '/');

if (!$secret || strpos($secret, 'REPLACE_ME') !== false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Stripe secret key not configured. Edit config/stripe.php']);
    exit;
}

$amount = intval($_POST['amount'] ?? $_GET['amount'] ?? 0);
if ($amount <= 0) { $amount = 1000; } // default 10.00
$currency = strtolower($_POST['currency'] ?? $_GET['currency'] ?? 'usd');
$name = trim($_POST['name'] ?? $_GET['name'] ?? 'Order from Shoes_Store');

$success_url = $baseUrl . '/checkout_success.php?session_id={CHECKOUT_SESSION_ID}';
$cancel_url = $baseUrl . '/checkout_cancel.php';

$post = [
    'payment_method_types[]' => 'card',
    'mode' => 'payment',
    'success_url' => $success_url,
    'cancel_url' => $cancel_url,
    'line_items[0][price_data][currency]' => $currency,
    'line_items[0][price_data][product_data][name]' => $name,
    'line_items[0][price_data][unit_amount]' => $amount,
    'line_items[0][quantity]' => 1
];

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $secret . ':');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);
// Attempt to find or obtain a CA bundle so cURL can verify Stripe's TLS on Windows.
$preferred = 'D:/Projects/Laragon-installer/8.0-W64/etc/ssl/cacert.pem';
$storageDir = __DIR__ . '/../storage';
if (!is_dir($storageDir)) { @mkdir($storageDir, 0755, true); }
$localPem = $storageDir . '/cacert.pem';
$caBundle = null;

// 1) prefer the reported Laragon path
if (file_exists($preferred) && is_readable($preferred)) {
    $caBundle = $preferred;
}

// 2) otherwise use a local storage copy if present
if (!$caBundle && file_exists($localPem) && is_readable($localPem)) {
    $caBundle = $localPem;
}

// 3) try to download a fresh cacert.pem into storage (best-effort)
if (!$caBundle) {
    $pemUrl = 'https://curl.se/ca/cacert.pem';
    $download = @file_get_contents($pemUrl);
    if ($download && strlen($download) > 2000) {
        @file_put_contents($localPem, $download);
        if (file_exists($localPem) && is_readable($localPem)) {
            $caBundle = $localPem;
        }
    }
}

if ($caBundle) {
    curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
} else {
    // No CA bundle available — enable verification (system defaults) and warn in response if negotiation fails.
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
}

$resp = curl_exec($ch);
if ($resp === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Curl error: ' . curl_error($ch)]);
    exit;
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($resp, true);
if ($httpCode >= 200 && $httpCode < 300 && !empty($data['url'])) {
    echo json_encode(['success' => true, 'url' => $data['url']]);
    exit;
}

http_response_code(500);
echo json_encode(['success' => false, 'message' => $data ?? 'Stripe API error', 'raw' => $resp]);
exit;
