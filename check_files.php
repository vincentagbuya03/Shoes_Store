<?php
/**
 * File existence checker
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get document root
$doc_root = $_SERVER['DOCUMENT_ROOT'];

// Check various paths
$paths_to_check = [
    'asset/style/chatbot.css',
    'asset/script/chatbot.js',
    'asset/style/animations.css',
    '/asset/style/chatbot.css',
    __DIR__ . '/asset/style/chatbot.css',
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>File Checker</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .exists { color: green; }
        .missing { color: red; }
    </style>
</head>
<body>
    <h1>File Existence Check</h1>
    <p><strong>DOCUMENT_ROOT:</strong> <?php echo htmlspecialchars($doc_root); ?></p>
    <p><strong>Current File:</strong> <?php echo htmlspecialchars(__FILE__); ?></p>
    <p><strong>Current Dir:</strong> <?php echo htmlspecialchars(__DIR__); ?></p>
    
    <h2>Files to Check:</h2>
    <ul>
    <?php
    foreach ($paths_to_check as $path) {
        $full_path = $doc_root . '/' . ltrim($path, '/');
        $exists = file_exists($full_path);
        $status = $exists ? 'exists' : 'missing';
        echo '<li class="' . $status . '">';
        echo htmlspecialchars($path) . ' - ';
        echo ($exists ? '✅ EXISTS' : '❌ MISSING');
        echo '</li>';
        echo '<small>Full path: ' . htmlspecialchars($full_path) . '</small><br>';
    }
    ?>
    </ul>
</body>
</html>
