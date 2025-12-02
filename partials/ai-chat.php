<?php


header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Only POST allowed']);
    exit;
}

$raw = file_get_contents('php://input');
if (!$raw) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty request body']);
    exit;
}

$data = json_decode($raw, true);
if ($data === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$message = trim($data['message'] ?? '');
$conversation = $data['conversation'] ?? [];

if ($message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message is required']);
    exit;
}
$OPENAI_API_KEY = getenv('OPENAI_API_KEY') ?: null;
$OPENAI_MODEL = getenv('OPENAI_MODEL') ?: 'gpt-3.5-turbo';
$DEBUG_MODE = getenv('AI_CHAT_DEBUG') === '1';

function local_fallback_reply($message) {
    $msg = strtolower($message);
    if (strpos($msg, 'shipping') !== false || strpos($msg, 'delivery') !== false) {
        return "Shipping typically takes 3-7 business days. Expedited options available at checkout.";
    }
    if (strpos($msg, 'size') !== false || strpos($msg, 'fit') !== false) {
        return "Sizes run true to size for most models. Check the product page's size guide for exact measurements.";
    }
    if (strpos($msg, 'return') !== false || strpos($msg, 'refund') !== false) {
        return "You can request a return within 14 days of delivery. See our refund policy on the orders page.";
    }
    if (strpos($msg, 'recommend') !== false || strpos($msg, 'suggest') !== false) {
        return "Tell me your preferred style and budget and I can suggest some popular options.";
    }
    if (strpos($msg, 'price') !== false || strpos($msg, 'cost') !== false) {
        return "Prices vary by product and promotions. Open the product page to see the current price and offers.";
    }
    return "Sorry, I couldn't reach the AI service right now. Please try again in a few moments or ask a simple question like 'shipping' or 'size'.";
}

$messages = [];
$messages[] = [
    'role' => 'system',
    'content' => 'You are ShoeBot, a friendly assistant that helps customers find shoes, sizes, shipping information and product recommendations. Keep answers concise and helpful.'
];

$conv = array_values(array_filter($conversation, function($m){
    return isset($m['role']) && isset($m['content']);
}));
$conv = array_slice($conv, -20);
foreach ($conv as $m) {
    $role = ($m['role'] === 'user') ? 'user' : 'assistant';
    $messages[] = ['role' => $role, 'content' => $m['content']];
}
$messages[] = ['role' => 'user', 'content' => $message];

if (!$OPENAI_API_KEY) {
    error_log('[ai-chat] OPENAI_API_KEY not set; using local fallback responder.');
    $assistant = local_fallback_reply($message);
    echo json_encode(['success' => true, 'assistant' => $assistant, 'offline' => true]);
    exit;
}

$postData = [
    'model' => $OPENAI_MODEL,
    'messages' => $messages,
    'temperature' => 0.7,
    'max_tokens' => 800,
    'top_p' => 1.0,
    'n' => 1,
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $OPENAI_API_KEY,
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $err || $httpCode >= 500) {
    error_log('[ai-chat] AI provider request failed: ' . ($err ?: 'HTTP ' . $httpCode));
    $assistant = local_fallback_reply($message);
    $payload = ['success' => true, 'assistant' => $assistant, 'offline' => true];
    if ($DEBUG_MODE) $payload['error'] = ($err ?: 'HTTP ' . $httpCode);
    echo json_encode($payload);
    exit;
}

$respJson = json_decode($response, true);
if ($respJson === null) {
    error_log('[ai-chat] Invalid JSON from AI provider: ' . $response);
    $assistant = local_fallback_reply($message);
    $payload = ['success' => true, 'assistant' => $assistant, 'offline' => true];
    if ($DEBUG_MODE) $payload['raw'] = $response;
    echo json_encode($payload);
    exit;
}
if (isset($respJson['choices'][0]['message']['content'])) {
    $assistant = trim($respJson['choices'][0]['message']['content']);
    $payload = ['success' => true, 'assistant' => $assistant];
    if ($DEBUG_MODE) $payload['raw'] = $respJson;
    echo json_encode($payload);
    exit;
}
if (isset($respJson['error'])) {
    // Provider returned an error object. If it's a client error (4xx) return it to caller,
    // so the frontend can surface it. For server errors (5xx) fall back to local reply.
    error_log('[ai-chat] AI provider error: ' . json_encode($respJson['error']));
    $providerMessage = is_array($respJson['error']) ? ($respJson['error']['message'] ?? json_encode($respJson['error'])) : (string)$respJson['error'];
    // If we got an HTTP code from cURL, use it when returning to client
    $statusCode = $httpCode >= 400 && $httpCode < 600 ? $httpCode : 502;
    // For client errors (4xx) forward the error to client
    if ($statusCode >= 400 && $statusCode < 500) {
        http_response_code($statusCode);
        echo json_encode(['success' => false, 'error' => $providerMessage, 'provider' => $respJson['error']]);
        exit;
    }

    // For server-side errors (5xx) fall back to local reply to keep UX friendly
    $assistant = local_fallback_reply($message);
    $payload = ['success' => true, 'assistant' => $assistant, 'offline' => true];
    if ($DEBUG_MODE) $payload['error'] = $respJson['error'];
    echo json_encode($payload);
    exit;
}

error_log('[ai-chat] Unknown response structure from AI provider.');
$assistant = local_fallback_reply($message);
echo json_encode(['success' => true, 'assistant' => $assistant, 'offline' => true]);
exit;