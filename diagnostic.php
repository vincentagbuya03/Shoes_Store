<?php
echo "<h2>Server Diagnostic</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current File: " . __FILE__ . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Server Name: " . $_SERVER['SERVER_NAME'] . "<br><br>";

echo "<h3>Files in public_html:</h3>";
$files = glob($_SERVER['DOCUMENT_ROOT'] . '/*.php');
foreach ($files as $file) {
    echo basename($file) . " (" . filesize($file) . " bytes)<br>";
}

echo "<h3>Checking index.php content:</h3>";
$index = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/index.php', false, null, 0, 500);
if (strpos($index, 'low-power-mode') !== false) {
    echo "<p style='color:red;'>❌ index.php HAS low-power-mode class</p>";
} else {
    echo "<p style='color:green;'>✓ index.php does NOT have low-power-mode</p>";
}

echo "<h3>Checking user-interface.php content:</h3>";
$ui = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/user-interface.php', false, null, 0, 500);
if (strpos($ui, 'low-power-mode') !== false) {
    echo "<p style='color:red;'>❌ user-interface.php HAS low-power-mode class</p>";
} else {
    echo "<p style='color:green;'>✓ user-interface.php does NOT have low-power-mode</p>";
}
?>
