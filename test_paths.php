<?php
echo "<h2>Path Debug Info</h2>";
echo "<p>SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "</p>";
echo "<p>SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'NOT SET') . "</p>";
echo "<p>REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "</p>";
echo "<p>PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'NOT SET') . "</p>";

$script_path = $_SERVER['SCRIPT_NAME'] ?? '';
$is_subdirectory = (strpos($script_path, '/Shoes_Store/') !== false);

echo "<hr>";
echo "<p>is_subdirectory: " . ($is_subdirectory ? 'YES' : 'NO') . "</p>";

if ($is_subdirectory) {
    echo "<p>Chatbot base: /Shoes_Store/</p>";
    echo "<p>CSS Path: /Shoes_Store/asset/style/chatbot.css</p>";
    echo "<p>JS Path: /Shoes_Store/asset/script/chatbot.js</p>";
} else {
    echo "<p>Chatbot base: /</p>";
    echo "<p>CSS Path: /asset/style/chatbot.css</p>";
    echo "<p>JS Path: /asset/script/chatbot.js</p>";
}
?>
