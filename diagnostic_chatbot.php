<?php
/**
 * Chatbot Diagnostic Page
 * Use this to debug CSS/JS loading issues on hosting
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the path detection function from chatbot.php
function getChatbotBasePath() {
    $subdirectory = 'Shoes_Store';
    
    $doc_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $script_filename = $_SERVER['SCRIPT_FILENAME'] ?? '';
    
    if ($doc_root && $script_filename && strpos($script_filename, $doc_root) === 0) {
        $relative_path = substr($script_filename, strlen($doc_root));
        $path_parts = explode('/', trim($relative_path, '/'));
        if (!empty($path_parts[0]) && $path_parts[0] === $subdirectory) {
            return '/' . $subdirectory . '/';
        }
    }
    
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script_name, '/' . $subdirectory . '/') !== false) {
        return '/' . $subdirectory . '/';
    }
    
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($request_uri, '/' . $subdirectory . '/') !== false) {
        return '/' . $subdirectory . '/';
    }
    
    return '/';
}

$chatbot_base = getChatbotBasePath();
$css_url = $chatbot_base . 'asset/style/chatbot.css';
$js_url = $chatbot_base . 'asset/script/chatbot.js';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .box { background: #f5f5f5; border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .good { background: #d4edda; border-color: #28a745; }
        .warning { background: #fff3cd; border-color: #ffc107; }
        .error { background: #f8d7da; border-color: #dc3545; }
        code { background: #222; color: #0f0; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        h2 { color: #333; }
        .test-button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 10px 0; }
        .test-button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🔧 Chatbot Asset Diagnostic</h1>
    
    <div class="box">
        <h2>Server Environment Detection</h2>
        <p><strong>DOCUMENT_ROOT:</strong> <code><?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET'); ?></code></p>
        <p><strong>SCRIPT_FILENAME:</strong> <code><?php echo htmlspecialchars($_SERVER['SCRIPT_FILENAME'] ?? 'NOT SET'); ?></code></p>
        <p><strong>SCRIPT_NAME:</strong> <code><?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'NOT SET'); ?></code></p>
        <p><strong>REQUEST_URI:</strong> <code><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'NOT SET'); ?></code></p>
    </div>

    <div class="box <?php echo ($chatbot_base === '/Shoes_Store/' || $chatbot_base === '/') ? 'good' : 'warning'; ?>">
        <h2>Detected Base Path</h2>
        <p><strong>Chatbot Base Path:</strong> <code><?php echo htmlspecialchars($chatbot_base); ?></code></p>
        <p>This will be set as <code>window.CHATBOT_BASE_PATH</code> in JavaScript</p>
    </div>

    <div class="box">
        <h2>CSS File URL</h2>
        <p><strong>Expected URL:</strong></p>
        <p><code><?php echo htmlspecialchars($css_url); ?></code></p>
        <p><strong>Full URL:</strong></p>
        <p><code><?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'unknown') . $css_url); ?></code></p>
        <p>
            <button class="test-button" onclick="testCSSLoad()">Test CSS Load</button>
            <span id="css-result"></span>
        </p>
    </div>

    <div class="box">
        <h2>JavaScript File URL</h2>
        <p><strong>Expected URL:</strong></p>
        <p><code><?php echo htmlspecialchars($js_url); ?></code></p>
        <p><strong>Full URL:</strong></p>
        <p><code><?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'unknown') . $js_url); ?></code></p>
        <p>
            <button class="test-button" onclick="testJSLoad()">Test JS Load</button>
            <span id="js-result"></span>
        </p>
    </div>

    <div class="box">
        <h2>Browser Console Test</h2>
        <p>Open your browser's Developer Tools (F12) and run these commands in the Console tab:</p>
        <pre><code>// Check if global base path is set
console.log('CHATBOT_BASE_PATH:', window.CHATBOT_BASE_PATH);

// Check if chatbot CSS loaded
var chatbotCSS = Array.from(document.styleSheets).find(s => s.href && s.href.includes('chatbot.css'));
console.log('Chatbot CSS loaded:', !!chatbotCSS);

// Check if chatbot container exists
console.log('Chatbot container exists:', !!document.getElementById('ai-chatbot-container'));

// Check actual CSS link tag
var cssLink = document.querySelector('link[href*="chatbot.css"]');
console.log('CSS link tag:', cssLink);
console.log('CSS link href:', cssLink ? cssLink.href : 'NOT FOUND');</code></pre>
    </div>

    <script>
        function testCSSLoad() {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '<?php echo htmlspecialchars($css_url); ?>?test=' + Date.now();
            
            link.onload = function() {
                document.getElementById('css-result').innerHTML = '✅ CSS loaded successfully!';
                document.getElementById('css-result').style.color = 'green';
            };
            
            link.onerror = function() {
                document.getElementById('css-result').innerHTML = '❌ CSS failed to load (404 or permission denied)';
                document.getElementById('css-result').style.color = 'red';
            };
            
            document.head.appendChild(link);
        }

        function testJSLoad() {
            var script = document.createElement('script');
            script.src = '<?php echo htmlspecialchars($js_url); ?>?test=' + Date.now();
            
            script.onload = function() {
                document.getElementById('js-result').innerHTML = '✅ JS loaded successfully!';
                document.getElementById('js-result').style.color = 'green';
            };
            
            script.onerror = function() {
                document.getElementById('js-result').innerHTML = '❌ JS failed to load (404 or permission denied)';
                document.getElementById('js-result').style.color = 'red';
            };
            
            document.head.appendChild(script);
        }

        // Show server environment info
        window.onload = function() {
            console.log('=== Chatbot Diagnostic Info ===');
            console.log('Chatbot Base Path:', '<?php echo htmlspecialchars($chatbot_base); ?>');
            console.log('CSS URL:', '<?php echo htmlspecialchars($css_url); ?>');
            console.log('JS URL:', '<?php echo htmlspecialchars($js_url); ?>');
            console.log('Full CSS URL:', '<?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'unknown') . $css_url); ?>');
            console.log('Full JS URL:', '<?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'unknown') . $js_url); ?>');
        };
    </script>
</body>
</html>
