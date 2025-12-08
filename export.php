<?php
/**
 * SIMPLE DATABASE EXPORT
 * Visit this file in browser to download your database as SQL
 */

require_once 'db_connection.php';

// Only allow on localhost
if ($_SERVER['SERVER_NAME'] !== 'localhost' && $_SERVER['SERVER_NAME'] !== '127.0.0.1') {
    die('This script only works on localhost');
}

$database = 'shoestore';

// Get all tables
$tables = array();
$result = $conn->query("SHOW TABLES FROM `$database`");

while ($row = $result->fetch_row()) {
    $tables[] = $row[0];
}

// Start output
$output = "-- Database Export for Ezyro\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$output .= "-- NOTE: Views have been removed\n\n";

// Export each table
foreach ($tables as $table) {
    // Skip views
    if ($table === 'rider_avg_rating') continue;
    
    $output .= "\n-- ============================================\n";
    $output .= "-- TABLE: `$table`\n";
    $output .= "-- ============================================\n\n";
    
    // Get CREATE TABLE
    $create = $conn->query("SHOW CREATE TABLE `$database`.`$table`");
    $create_row = $create->fetch_assoc();
    $output .= $create_row['Create Table'] . ";\n\n";
    
    // Get data
    $data = $conn->query("SELECT * FROM `$database`.`$table`");
    
    if ($data->num_rows > 0) {
        $output .= "-- Data for table `$table`\n";
        
        // Get column names
        $fields = $data->fetch_fields();
        $columns = array();
        foreach ($fields as $field) {
            $columns[] = '`' . $field->name . '`';
        }
        $col_string = implode(', ', $columns);
        
        // Insert data
        $data = $conn->query("SELECT * FROM `$database`.`$table`");
        while ($row = $data->fetch_assoc()) {
            $values = array();
            foreach ($row as $value) {
                if ($value === NULL) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . $conn->real_escape_string($value) . "'";
                }
            }
            $val_string = implode(', ', $values);
            $output .= "INSERT INTO `$table` ($col_string) VALUES ($val_string);\n";
        }
        $output .= "\n";
    }
}

// Download file
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="shoestore_ezyro_' . date('Ymd_His') . '.sql"');
header('Content-Length: ' . strlen($output));
header('Pragma: no-cache');
header('Expires: 0');

echo $output;
exit;
?>
