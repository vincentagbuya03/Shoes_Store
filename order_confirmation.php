<?php
require_once 'db_connection.php';
require_once __DIR__ . '/inc/store_settings.php';
session_start();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    echo 'Invalid order id'; exit();
}

// fetch order
$stmt = $conn->prepare('SELECT * FROM orders WHERE order_id = ? LIMIT 1');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$res = $stmt->get_result();
$order = $res->fetch_assoc();
$stmt->close();
if (!$order) { echo 'Order not found'; exit(); }

// fetch items
$stmt = $conn->prepare('SELECT oi.*, pv.price AS pv_price, p.name AS product_name FROM order_items oi LEFT JOIN product_variant pv ON oi.variant_id = pv.variant_id LEFT JOIN product p ON oi.product_id = p.product_id WHERE oi.order_id = ?');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// fetch payment (latest)
$payment = null;
$pstmt = $conn->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1');
if ($pstmt) {
    $pstmt->bind_param('i', $order_id);
    $pstmt->execute();
    $pres = $pstmt->get_result();
    $payment = $pres->fetch_assoc();
    $pstmt->close();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="asset/style/animations.css">
    <?php echo getStoreThemeCSS(); ?>
    <style>
        body { font-family: var(--store-font, Arial, Helvetica, sans-serif); padding:24px; background: var(--store-bg); color: var(--store-text); }
        .card { background: var(--store-card-bg); padding:20px;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,0.06);max-width:900px;margin:0 auto; border: 1px solid var(--store-border); }
        .items { margin-top:12px; }
        .item { display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--store-border);align-items:center }
        .item img { width:64px;height:64px;object-fit:cover;border-radius:8px }
        .meta { flex:1 }
        h1 { background: var(--store-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Order Confirmation</h1>
        <p>Order #<?php echo intval($order['order_id']); ?> placed at <?php echo htmlspecialchars($order['created_at']); ?></p>
        <p><strong>Total:</strong> ₱<?php echo number_format((float)$order['total_amount'],2); ?></p>
        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['payment_method']); ?></p>
        <?php if ($payment): ?>
            <div style="margin-top:12px;padding:12px;background:#f9fafb;border-radius:8px;border:1px solid #eee;">
                <h3 style="margin:0 0 8px 0;">Payment Details</h3>
                <div><strong>Status:</strong> <?php echo htmlspecialchars($payment['status'] ?? 'pending'); ?></div>
                <div><strong>Method:</strong> <?php echo htmlspecialchars($payment['method'] ?? ''); ?></div>
                <div><strong>Reference:</strong> <?php echo htmlspecialchars($payment['reference'] ?? ''); ?></div>
                <div><strong>Amount:</strong> ₱<?php echo number_format((float)($payment['amount'] ?? 0),2); ?></div>
                <?php if (!empty($payment['proof_path'])): ?>
                    <div style="margin-top:8px;">
                        <strong>Payment Proof:</strong><br>
                        <a href="<?php echo htmlspecialchars($payment['proof_path']); ?>" target="_blank">
                            <img src="<?php echo htmlspecialchars($payment['proof_path']); ?>" alt="Payment proof" style="max-width:240px;border-radius:8px;border:1px solid #e5e7eb;">
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="margin-top:12px;color:#6b7280;">No payment record found yet.</div>
        <?php endif; ?>
        <p><strong>Delivery Address:</strong><br><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>

        <div class="items">
            <?php foreach ($items as $it): ?>
                <div class="item">
                    <img src="<?php echo htmlspecialchars($it['image_url'] ?? 'upload/product-image/placeholder.png'); ?>" alt="">
                    <div class="meta">
                        <div style="font-weight:700"><?php echo htmlspecialchars($it['product_name'] ?? 'Product'); ?></div>
                        <div style="color:#666">Variant: <?php echo intval($it['variant_id']); ?> &times; <?php echo intval($it['qty']); ?></div>
                    </div>
                    <div style="font-weight:700">₱<?php echo number_format((float)$it['price'],2); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:16px;">
            <a href="user-interface.php">Back to shop</a>
        </div>
    </div>
</body>
</html>