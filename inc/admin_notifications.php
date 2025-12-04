<?php
// Helper to create admin notifications. Creates table on-demand if missing.
// Usage: require_once __DIR__ . '/inc/admin_notifications.php';
function create_admin_notification($conn, $type, $title, $body = '', $level = 'info', $meta = null, $url = null) {
    if (!($conn instanceof mysqli)) return false;

    // Support either table name: legacy singular `admin_notification` or plural `admin_notifications`.
    $tablePlural = 'admin_notifications';
    $tableSingular = 'admin_notification';

    // Table DDL used for both tables
    $createSql = "CREATE TABLE IF NOT EXISTS %s (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(100) NOT NULL,
        title VARCHAR(255) NOT NULL,
        body TEXT,
        level ENUM('critical','high','medium','info') DEFAULT 'info',
        meta JSON NULL,
        url VARCHAR(255) NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Ensure both table names exist (idempotent)
    $conn->query(sprintf($createSql, $tablePlural));
    $conn->query(sprintf($createSql, $tableSingular));

    $metaJson = null;
    if ($meta !== null) {
        if (is_array($meta) || is_object($meta)) $metaJson = json_encode($meta, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        elseif (is_string($meta)) $metaJson = $meta;
    }

    // Use a transaction to insert into both tables so they're kept in sync for visibility
    $started = false;
    if (method_exists($conn, 'begin_transaction')) {
        $conn->begin_transaction();
        $started = true;
    }

    $sql = "INSERT INTO {$tablePlural} (type, title, body, level, meta, url) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('create_admin_notification: prepare failed: ' . $conn->error);
        if ($started) $conn->rollback();
        return false;
    }
    $stmt->bind_param('ssssss', $type, $title, $body, $level, $metaJson, $url);
    if (!$stmt->execute()) {
        error_log('create_admin_notification: execute failed: ' . $stmt->error);
        $stmt->close();
        if ($started) $conn->rollback();
        return false;
    }
    $insertId = $stmt->insert_id;
    $stmt->close();

    // Also write a copy into the singular table so tools expecting that name can find notifications
    $sql2 = "INSERT INTO {$tableSingular} (type, title, body, level, meta, url) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt2 = $conn->prepare($sql2);
    if ($stmt2) {
        $stmt2->bind_param('ssssss', $type, $title, $body, $level, $metaJson, $url);
        if (!$stmt2->execute()) {
            error_log('create_admin_notification: execute singular failed: ' . $stmt2->error);
            // non-fatal: continue
        }
        $stmt2->close();
    } else {
        error_log('create_admin_notification: prepare singular failed: ' . $conn->error);
    }

    if ($started) $conn->commit();
    return $insertId;
}

?>
