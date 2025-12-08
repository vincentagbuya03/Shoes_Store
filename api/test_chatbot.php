<?php
/**
 * Chatbot API Test
 * Upload this to your hosting and visit it to test the chatbot API
 */

header('Content-Type: text/html; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Chatbot API Test</h1>";

// Test 1: Check if gemini.local.php exists
echo "<h2>1. Config File Check</h2>";
$configPath = __DIR__ . '/../config/gemini.local.php';
if (file_exists($configPath)) {
    echo "<p style='color:green'>✓ gemini.local.php exists</p>";
    $localConfig = require $configPath;
    echo "<p>API Key: " . (empty($localConfig['api_key']) ? '<span style="color:red">EMPTY</span>' : substr($localConfig['api_key'], 0, 10) . '...') . "</p>";
} else {
    echo "<p style='color:red'>✗ gemini.local.php NOT FOUND at: $configPath</p>";
}

// Test 2: Check if cURL is enabled
echo "<h2>2. cURL Check</h2>";
if (function_exists('curl_init')) {
    echo "<p style='color:green'>✓ cURL is enabled</p>";
} else {
    echo "<p style='color:red'>✗ cURL is NOT enabled - chatbot won't work!</p>";
}

// Test 3: Check gemini.php main config
echo "<h2>3. Main Config Check</h2>";
$mainConfigPath = __DIR__ . '/../config/gemini.php';
if (file_exists($mainConfigPath)) {
    echo "<p style='color:green'>✓ gemini.php exists</p>";
    $config = require $mainConfigPath;
    echo "<p>Loaded API Key: " . (empty($config['api_key']) ? '<span style="color:red">EMPTY</span>' : substr($config['api_key'], 0, 10) . '...') . "</p>";
} else {
    echo "<p style='color:red'>✗ gemini.php NOT FOUND</p>";
}

// Test 4: Try a test API call
echo "<h2>4. API Connection Test</h2>";
if (function_exists('curl_init') && !empty($config['api_key'])) {
    $apiKey = $config['api_key'];
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;
    
    $data = [
        'contents' => [
            ['parts' => [['text' => 'Say hello in one word']]]
        ],
        'generationConfig' => [
            'maxOutputTokens' => 50
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30
    ]);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "<p>HTTP Code: $httpCode</p>";
    
    if ($error) {
        echo "<p style='color:red'>cURL Error: $error</p>";
    } elseif ($httpCode === 200) {
        $response = json_decode($result, true);
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            echo "<p style='color:green'>✓ API Response: " . htmlspecialchars($response['candidates'][0]['content']['parts'][0]['text']) . "</p>";
        } else {
            echo "<p style='color:orange'>Unexpected response structure:</p><pre>" . htmlspecialchars($result) . "</pre>";
        }
    } else {
        echo "<p style='color:red'>API Error ($httpCode):</p><pre>" . htmlspecialchars($result) . "</pre>";
    }
} else {
    echo "<p style='color:orange'>Skipped - cURL not available or API key empty</p>";
}

// Test 5: Check chatbot.php
echo "<h2>5. Chatbot API File Check</h2>";
$chatbotPath = __DIR__ . '/chatbot.php';
if (file_exists($chatbotPath)) {
    echo "<p style='color:green'>✓ api/chatbot.php exists</p>";
} else {
    echo "<p style='color:red'>✗ api/chatbot.php NOT FOUND</p>";
}

echo "<hr><p><strong>Instructions:</strong> If all checks pass, the chatbot should work. If any fail, fix that issue first.</p>";
echo "<p>Delete this file after testing for security!</p>";
?>
