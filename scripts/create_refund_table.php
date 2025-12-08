<?php
// Run this from project root: php scripts\create_refund_table.php
require_once __DIR__ . '/../db_connection.php';

$queries = [];
// Create refund_requests table if it doesn't exist
$queries[] = "CREATE TABLE IF NOT EXISTS `refund_requests` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `customer_id` INT NULL,
    `reason` TEXT,
    `status` ENUM('requested','processing','pickup_scheduled','picked_up','resolved','rejected') DEFAULT 'requested',
    `rider_pickup_id` INT DEFAULT NULL,
    `pickup_date` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// Ensure refund_requests columns for older installs
$queries[] = "ALTER TABLE `refund_requests` MODIFY COLUMN status ENUM('requested','processing','pickup_scheduled','picked_up','resolved','rejected') DEFAULT 'requested'";

// Ensure orders has refund tracking columns
$queries[] = "ALTER TABLE `orders` MODIFY COLUMN status ENUM('pending','confirmed','delivering','completed','cancelled','refund_requested') NULL DEFAULT 'pending'";
$queries[] = "-- add refund_status if missing (handled below via SHOW COLUMNS)";
$queries[] = "-- add refund_id if missing (handled below via SHOW COLUMNS)";

$errors = [];
foreach ($queries as $q) {
    // skip placeholder comments
    if (strpos(trim($q), '--') === 0) continue;
    if ($conn->query($q) === false) {
        $errors[] = "Query failed: " . $conn->error . "\nSQL: $q";
    }
}

// Add columns refund_status and refund_id if they don't exist
try {
    $col = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'refund_status'");
    if (!($col && $col->num_rows > 0)) {
        if ($conn->query("ALTER TABLE `orders` ADD COLUMN `refund_status` ENUM('requested','processing','resolved','rejected') DEFAULT NULL AFTER `status`") === false) {
            $errors[] = "Failed adding refund_status: " . $conn->error;
        }
    }
} catch (Exception $e) {
    $errors[] = "Exception checking/adding refund_status: " . $e->getMessage();
}

try {
    $col2 = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'refund_id'");
    if (!($col2 && $col2->num_rows > 0)) {
        if ($conn->query("ALTER TABLE `orders` ADD COLUMN `refund_id` INT DEFAULT NULL AFTER `refund_status`") === false) {
            $errors[] = "Failed adding refund_id: " . $conn->error;
        }
    }
} catch (Exception $e) {
    $errors[] = "Exception checking/adding refund_id: " . $e->getMessage();
}

// Optionally add index for refund_status
if ($conn->query("SHOW INDEX FROM `orders` WHERE Key_name = 'idx_refund_status'") === false) {
    // Some MySQL connectors return false for SHOW INDEX when index doesn't exist; we try to create index safely
    $conn->query("ALTER TABLE `orders` ADD INDEX idx_refund_status (refund_status)");
}

if ($errors) {
    echo "Finished with errors:\n";
    foreach ($errors as $e) echo "- $e\n";
    exit(1);
}

echo "refund_requests table and orders refund columns created/verified successfully.\n";
exit(0);
