<?php
// Geocode orders and store delivery_lat/delivery_lng using Nominatim
// Usage: run from CLI: php delivery_rider/tools/geocode_orders.php

require_once __DIR__ . '/../../db_connection.php';

// Config
$rateLimitSeconds = 1; // keep >=1s for Nominatim usage policy
$maxToProcess = 200; // safety cap per run

// Find orders lacking coords but with a non-empty customer address
$sql = "SELECT o.order_id, c.address FROM orders o JOIN customer c ON o.customer_id = c.customer_id WHERE (o.delivery_lat IS NULL OR o.delivery_lng IS NULL) AND c.address IS NOT NULL AND TRIM(c.address) != '' LIMIT ?";
if (!($stmt = $conn->prepare($sql))) {
    echo "Prepare failed: " . $conn->error . PHP_EOL;
    exit(1);
}
$stmt->bind_param('i', $maxToProcess);
$stmt->execute();
$res = $stmt->get_result();
$orders = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

if (count($orders) === 0) {
    echo "No orders found that need geocoding.\n";
    exit(0);
}

echo "Found " . count($orders) . " orders to geocode (max $maxToProcess).\n";

foreach ($orders as $o) {
    $orderId = (int)$o['order_id'];
    $address = $o['address'];
    echo "Geocoding order #$orderId -> address: $address\n";

    // Build Nominatim query
    $q = http_build_query([
        'q' => $address,
        'format' => 'json',
        'limit' => 1,
        'addressdetails' => 0,
    ]);
    $url = 'https://nominatim.openstreetmap.org/search?' . $q;

    // cURL with User-Agent per Nominatim policy
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ShoeTakels-Geocoder/1.0 (+https://example.com)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $body = curl_exec($ch);
    $err = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err || $http !== 200 || !$body) {
        echo "  Request failed (HTTP $http) or empty response. Error: $err\n";
        // respect rate limit and continue
        sleep($rateLimitSeconds);
        continue;
    }

    $json = json_decode($body, true);
    if (!is_array($json) || count($json) === 0) {
        echo "  No results from geocoder.\n";
        sleep($rateLimitSeconds);
        continue;
    }

    $place = $json[0];
    if (!isset($place['lat']) || !isset($place['lon'])) {
        echo "  Result missing lat/lon.\n";
        sleep($rateLimitSeconds);
        continue;
    }

    $lat = floatval($place['lat']);
    $lng = floatval($place['lon']);

    // Update the order row with the coordinates
    $uSql = "UPDATE orders SET delivery_lat = ?, delivery_lng = ? WHERE order_id = ?";
    if (!($uStmt = $conn->prepare($uSql))) {
        echo "  Prepare update failed: " . $conn->error . "\n";
        sleep($rateLimitSeconds);
        continue;
    }
    $uStmt->bind_param('ddi', $lat, $lng, $orderId);
    if ($uStmt->execute()) {
        echo "  Updated order $orderId -> lat=$lat lng=$lng\n";
    } else {
        echo "  Update failed: " . $uStmt->error . "\n";
    }
    $uStmt->close();

    // Rate limit
    sleep($rateLimitSeconds);
}

echo "Done.\n";

?>