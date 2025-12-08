<?php
require_once 'db_connection.php';

$debug_log = $_SERVER['DOCUMENT_ROOT'] . '/debug_log.txt';

ob_start();
echo "=== Tables in Database ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$result = $conn->query("SHOW TABLES");
if ($result) {
    echo "Tables found:\n";
    while ($row = $result->fetch_array()) {
        echo "  - " . $row[0] . "\n";
    }
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

$output = ob_get_clean();
file_put_contents($debug_log, $output, FILE_APPEND);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        pre { background: white; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>Database Tables</h2>
    <pre><?php
    if (file_exists($debug_log)) {
        echo htmlspecialchars(file_get_contents($debug_log));
    }
    ?></pre>
</body>
</html>
