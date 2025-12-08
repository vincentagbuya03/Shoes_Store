<?php
/**
 * SHOES STORE - Export Database Without Views
 * This exports your database WITHOUT the problematic VIEW definitions
 * Perfect for Ezyro hosting
 */

require_once 'db_connection.php';

// Only run this on local (not on hosting)
if ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
    die('This script can only run on localhost');
}

$dbname = 'shoestore';
$output = "-- Shoes Store Database Export (Compatible with Ezyro)\n";
$output .= "-- Exported: " . date('Y-m-d H:i:s') . "\n";
$output .= "-- Views have been removed for Ezyro compatibility\n\n";

// Get all tables
$tables_result = $conn->query("SHOW TABLES FROM $dbname");
$tables = [];
while ($row = $tables_result->fetch_array()) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    // Skip view tables
    if ($table === 'rider_avg_rating') {
        continue;
    }
    
    $output .= "\n-- ========== TABLE: $table ==========\n\n";
    
    // Get CREATE TABLE
    $create_result = $conn->query("SHOW CREATE TABLE `$dbname`.`$table`");
    $create_row = $create_result->fetch_assoc();
    $output .= $create_row['Create Table'] . ";\n\n";
    
    // Get INSERT data
    $data_result = $conn->query("SELECT * FROM `$dbname`.`$table`");
    if ($data_result->num_rows > 0) {
        // Get column names
        $fields = $data_result->fetch_fields();
        $columns = array_map(function($f) { return "`" . $f->name . "`"; }, $fields);
        $columns_str = implode(", ", $columns);
        
        while ($row = $data_result->fetch_assoc()) {
            $values = array_map(function($v) {
                if ($v === null) return 'NULL';
                return "'" . addslashes($v) . "'";
            }, $row);
            $values_str = implode(", ", $values);
            $output .= "INSERT INTO `$table` ($columns_str) VALUES ($values_str);\n";
        }
        $output .= "\n";
    }
}

// Force download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="shoestore_ezyro.sql"');
header('Content-Length: ' . strlen($output));
echo $output;
exit;
?>
