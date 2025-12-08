<?php
/**
 * SHOES STORE - HOSTING SETUP CHECKER
 * This file helps diagnose issues when hosting on profreehost/Ezyro
 * 
 * Upload this file and visit: http://shoetakels.unaux.com/hosting_check.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔧 Shoes Store - Hosting Setup Checker</h1>";
echo "<hr>";

// 1. Check PHP version
echo "<h2>1. PHP Version</h2>";
echo "PHP Version: <strong>" . phpversion() . "</strong>";
if (version_compare(phpversion(), '7.0.0') >= 0) {
    echo " ✓<br>";
} else {
    echo " ✗ (Need PHP 7.0 or higher)<br>";
}

// 2. Check if MySQL extension is loaded
echo "<h2>2. MySQL Extension</h2>";
if (extension_loaded('mysqli')) {
    echo "mysqli extension: <strong>✓ Loaded</strong><br>";
} else {
    echo "mysqli extension: <strong>✗ NOT LOADED</strong><br>";
}

// 3. Check database connection
echo "<h2>3. Database Connection</h2>";
require_once 'db_connection.php';

if ($conn->connect_error) {
    echo "<strong style='color:red;'>✗ Connection Failed: " . $conn->connect_error . "</strong><br>";
    echo "Debug Info:<br>";
    echo "- Server: " . htmlspecialchars($servername) . "<br>";
    echo "- Database: " . htmlspecialchars($dbname) . "<br>";
    echo "- Username: " . htmlspecialchars($username) . "<br>";
} else {
    echo "<strong style='color:green;'>✓ Database Connected Successfully!</strong><br>";
    echo "- Server: " . htmlspecialchars($servername) . "<br>";
    echo "- Database: " . htmlspecialchars($dbname) . "<br>";
    
    // 4. Check if tables exist
    echo "<h2>4. Database Tables</h2>";
    $requiredTables = ['Product', 'Brand', 'customer', 'orders', 'cart', 'rider', 'notifications'];
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while($row = $result->fetch_array()) {
        $tables[] = strtolower($row[0]);
    }
    
    echo "Tables found: " . count($tables) . "<br>";
    foreach ($requiredTables as $table) {
        if (in_array(strtolower($table), $tables)) {
            echo "- $table: <strong style='color:green;'>✓</strong><br>";
        } else {
            echo "- $table: <strong style='color:red;'>✗ MISSING</strong><br>";
        }
    }
    
    if (empty($tables)) {
        echo "<strong style='color:red;'>ERROR: No tables found! You need to import your database.</strong><br>";
    }
}

// 5. Check file permissions
echo "<h2>5. File Permissions</h2>";
$uploadDirs = [
    'upload/product-image',
    'upload/brand-picture',
    'upload/carousel-picture',
    'admin/upload/product-image',
    'admin/upload/brand-picture',
];

foreach ($uploadDirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "- $dir: <strong style='color:green;'>✓ Writable</strong><br>";
        } else {
            echo "- $dir: <strong style='color:orange;'>⚠ Not writable</strong><br>";
        }
    } else {
        echo "- $dir: <strong style='color:red;'>✗ Directory not found</strong><br>";
    }
}

// 6. Check if critical files exist
echo "<h2>6. Critical Files</h2>";
$criticalFiles = [
    'db_connection.php',
    'inc/store_settings.php',
    'inc/admin_notifications.php',
    'asset/script/script.js',
    'asset/style/beautiful-ui.css',
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        echo "- $file: <strong style='color:green;'>✓ Found</strong><br>";
    } else {
        echo "- $file: <strong style='color:red;'>✗ NOT FOUND</strong><br>";
    }
}

// 7. Display environment info
echo "<h2>7. Environment Info</h2>";
echo "Server: " . $_SERVER['SERVER_NAME'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Current Script: " . __FILE__ . "<br>";

// 8. Check for common errors in error logs
echo "<h2>8. Error Summary</h2>";
if (defined('PHP_EOL')) {
    echo "Error reporting: Enabled<br>";
}
echo "Current directory: " . getcwd() . "<br>";

$conn->close();
?>

<hr>
<h2>Next Steps:</h2>
<ul>
    <li>If database tables are missing, export your local database and import it on the hosting</li>
    <li>If file permissions show ⚠, contact your hosting support to make directories writable</li>
    <li>After fixing issues, delete this file from your hosting for security</li>
</ul>
