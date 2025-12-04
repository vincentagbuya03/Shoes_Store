<?php
require_once 'db_connection.php';
require_once 'inc/admin_notifications.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $res = create_admin_notification($conn, 'test', 'Test notification from script', 'If you see this, the helper works', 'info', ['x'=>'y'], '/admin');
    if ($res) {
        echo "Inserted notification id: " . (int)$res . PHP_EOL;
    } else {
        echo "Failed to insert notification. Check PHP error log." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}

?>