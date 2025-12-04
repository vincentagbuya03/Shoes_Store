<?php
/**
 * One-time migration: add `delivery_proof` column to `orders` table.
 * Usage (browser): http://localhost/Shoes_Store/scripts/add_delivery_proof.php
 * Usage (CLI): php scripts\\add_delivery_proof.php
 */
require_once __DIR__ . '/../db_connection.php';

header('Content-Type: text/plain; charset=utf-8');

// Verify DB connection
if (!isset($conn) || !$conn) {
    echo "Database connection not available. Check db_connection.php\n";
    exit(1);
}

try {
    $colCheck = $conn->prepare("SHOW COLUMNS FROM `orders` LIKE 'delivery_proof'");
    if ($colCheck) {
        $colCheck->execute();
        $res = $colCheck->get_result();
        if ($res && $res->num_rows > 0) {
            echo "Column `delivery_proof` already exists on `orders`. Nothing to do.\n";
            exit(0);
        }
        $colCheck->close();
    }

    $sql = "ALTER TABLE `orders` ADD COLUMN `delivery_proof` VARCHAR(255) DEFAULT NULL AFTER `status`";
    if ($conn->query($sql) === TRUE) {
        echo "Added column `delivery_proof` to `orders` successfully.\n";
        exit(0);
    } else {
        echo "Failed to add column: " . ($conn->error ?? 'unknown error') . "\n";
        exit(2);
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    exit(3);
}

?>
