<?php
require_once 'db_connection.php';
require_once 'inc/store_settings.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'] ?? 'User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $variant_id = (int)($_POST['variant_id'] ?? 0);

    if ($action === 'remove' && $variant_id > 0) {
        $delete_query = "DELETE FROM cart WHERE customer_id = ? AND variant_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('ii', $customer_id, $variant_id);
        $stmt->execute();
        $stmt->close();

        $count_query = "SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?";
        $stmt = $conn->prepare($count_query);
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $cart_count = (int)($count_row['total'] ?? 0);
        $stmt->close();

        $total_query = "
            SELECT SUM(pv.price * c.quantity) as total 
            FROM cart c
            JOIN product_variant pv ON c.variant_id = pv.variant_id
            WHERE c.customer_id = ?
        ";
        $stmt = $conn->prepare($total_query);
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $total_result = $stmt->get_result();
        $total_row = $total_result->fetch_assoc();
        $new_total = (float)($total_row['total'] ?? 0);
        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Item removed',
            'cart_count' => $cart_count,
            'cart_total' => number_format((float)$new_total, 2)
        ]);
        exit();
    }

    if ($action === 'update' && $variant_id > 0) {
        $quantity = (int)($_POST['quantity'] ?? 1);
        if ($quantity < 1) $quantity = 1;
        if ($quantity > 999) $quantity = 999;

        $update_query = "UPDATE cart SET quantity = ? WHERE customer_id = ? AND variant_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('iii', $quantity, $customer_id, $variant_id);
        $stmt->execute();
        $stmt->close();

        // Get variant price for updated item total
        $item_query = "SELECT pv.price FROM product_variant pv WHERE pv.variant_id = ?";
        $stmt = $conn->prepare($item_query);
        $stmt->bind_param('i', $variant_id);
        $stmt->execute();
        $item_result = $stmt->get_result();
        $item_row = $item_result->fetch_assoc();
        $item_total = number_format((float)(($item_row['price'] ?? 0) * $quantity), 2);
        $stmt->close();

        $total_query = "
            SELECT SUM(pv.price * c.quantity) as total 
            FROM cart c
            JOIN product_variant pv ON c.variant_id = pv.variant_id
            WHERE c.customer_id = ?
        ";
        $stmt = $conn->prepare($total_query);
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $total_result = $stmt->get_result();
        $total_row = $total_result->fetch_assoc();
        $new_total = (float)($total_row['total'] ?? 0);
        $stmt->close();

        $count_query = "SELECT SUM(quantity) as total FROM cart WHERE customer_id = ?";
        $stmt = $conn->prepare($count_query);
        $stmt->bind_param('i', $customer_id);
        $stmt->execute();
        $count_result = $stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $cart_count = (int)($count_row['total'] ?? 0);
        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Quantity updated',
            'item_total' => $item_total,
            'cart_count' => $cart_count,
            'cart_total' => number_format((float)$new_total, 2)
        ]);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}


$cart_query = "
    SELECT 
        c.cart_id,
        c.variant_id,
        c.quantity,
        pv.product_id,
        p.name,
        pv.price,
        pv.stock,
        b.brand_name,
        co.color_name,
        sz.size_name,
        pci.image_url
    FROM cart c
    INNER JOIN product_variant pv ON c.variant_id = pv.variant_id
    INNER JOIN product p ON pv.product_id = p.product_id
    INNER JOIN brand b ON p.brand_id = b.brand_id
    INNER JOIN color co ON pv.color_id = co.color_id
    INNER JOIN size sz ON pv.size_id = sz.size_id
    LEFT JOIN product_color_image pci ON pv.product_id = pci.product_id AND pv.color_id = pci.color_id AND pci.sort_order = 1
    WHERE c.customer_id = ?
    ORDER BY c.updated_at DESC
";

$stmt = $conn->prepare($cart_query);
$stmt->bind_param('i', $customer_id);
$stmt->execute();
$cart_result = $stmt->get_result();

$cart_total = 0;
$cart_items = [];

while ($item = $cart_result->fetch_assoc()) {
    $cart_items[] = $item;
    $cart_total += $item['price'] * $item['quantity'];
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="asset/style/index.css">
    <link rel="stylesheet" href="asset/style/cart.css">
    <?php echo getStoreThemeCSS(); ?>
    <style>

    </style>
</head>
<body>
    <!-- Announcement Bar -->
    <?php if (!empty($store_settings['announcement_enabled']) && !empty($store_settings['announcement_text'])): ?>
    <div class="announcement-bar" style="background-color: <?php echo htmlspecialchars($store_settings['announcement_bg_color'] ?? '#000'); ?>; color: <?php echo htmlspecialchars($store_settings['announcement_text_color'] ?? '#fff'); ?>;">
        <p><?php echo htmlspecialchars($store_settings['announcement_text']); ?></p>
    </div>
    <?php endif; ?>

    <nav>
        <div class="logo">
            <a href="user-interface.php"><?php echo htmlspecialchars($store_settings['store_name']); ?></a>
        </div>
        <div class="nav-back">
            <a href="user-interface.php">Continue Shopping</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div>
                <h1>Shopping Cart</h1>
                <p class="breadcrumb"><a href="user-interface.php">Home</a> / Cart</p>
            </div>
            <div class="breadcrumb">
                <?php
                $total_quantity = 0;
                foreach ($cart_items as $item) {
                    $total_quantity += (int)$item['quantity'];
                }
                ?>
                <span id="cart-count"><?php echo $total_quantity; ?></span> item(s) in cart
            </div>
        </div>

        <div class="cart-wrapper">
            <div class="cart-items-section">
                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <div class="empty-cart-icon">🛒</div>
                        <h2>Your Cart is Empty</h2>
                        <p>Looks like you haven't added any items yet</p>
                        <a href="user-interface.php" class="btn-continue-shopping">Continue Shopping</a>
                    </div>
                <?php else: ?>
                    <form id="cart-form" method="POST" action="checkout.php">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" data-variant-id="<?php echo $item['variant_id']; ?>" style="position:relative; background:#fff; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.04); margin-bottom:18px; display:flex; align-items:stretch; min-height:140px;">
                            <div style="display:flex; flex-direction:column; align-items:flex-start; justify-content:flex-start; min-width:48px;">
                                <input type="checkbox" class="cart-item-checkbox" name="selected_items[]" value="<?php echo $item['variant_id']; ?>" checked style="margin-top:18px; margin-left:10px; width:22px; height:22px; accent-color:#d4a574; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                            </div>
                            <div class="cart-item-image" style="margin:0 18px 0 0; min-width:120px; width:120px; height:120px; display:flex; align-items:center; justify-content:center;">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'upload/product-image/placeholder.png', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" style="border-radius:8px; border:1px solid #eee; background:#fafafa; width:100%; height:100%; object-fit:cover;">
                            </div>
                            <div class="cart-item-details" style="flex:1; padding-left:0; display:flex; flex-direction:column; justify-content:center; min-width:0;">
                                <div class="cart-item-name" style="font-size:1.15rem; font-weight:700; color:#1a1a1a; margin-bottom:2px; letter-spacing:0.01em; line-height:1.2; word-break:break-word; white-space:normal; overflow-wrap:break-word;">
                                    <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="cart-item-brand" style="font-size:0.97rem; color:#b08b4f; margin-bottom:4px; font-weight:600;">
                                    <?php echo htmlspecialchars($item['brand_name'] ?? 'Unknown Brand', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="cart-item-price" style="font-size:1.1rem; color:#d4a574; font-weight:700; margin-bottom:2px;">₱<?php echo number_format((float)$item['price'], 2); ?></div>
                                <div style="display:flex; flex-wrap:wrap; gap:10px 18px;">
                                    <div class="cart-item-color" style="font-size:0.97rem; color:#555;">Color: <span style="font-weight:600; color:#222;"><?php echo htmlspecialchars($item['color_name'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                                    <div class="cart-item-size" style="font-size:0.97rem; color:#555;">Size: <span style="font-weight:600; color:#222;"><?php echo htmlspecialchars($item['size_name'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                                </div>
                                <div style="margin-top: 0.85rem;">
                                    <div class="quantity-controls">
                                        <button class="quantity-btn qty-decrease" aria-label="Decrease quantity">−</button>
                                        <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="999">
                                        <button class="quantity-btn qty-increase" aria-label="Increase quantity">+</button>
                                    </div>
                                </div>
                            </div>
                            <div class="cart-item-total" style="text-align:right; display:flex; flex-direction:column; justify-content:center; align-items:flex-end; min-width:120px;">
                                <div class="item-total-label" style="font-size:0.9rem; color:#b08b4f; margin-bottom:0.25rem;">Subtotal</div>
                                <div class="item-total-price item-price" style="font-size:1.25rem; font-weight:700; color:#d4a574;">₱<?php echo number_format((float)($item['price'] * $item['quantity']), 2); ?></div>
                                <button class="remove-btn" aria-label="Remove item" title="Remove from cart" style="margin-top:10px;">×</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </form>
                <?php endif; ?>
            </div>

            <div class="order-summary">
                <div class="summary-title">Order Summary</div>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">₱<?php echo number_format((float)$cart_total, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span id="shipping">₱0.00</span>
                </div>
                <div class="summary-row">
                    <span>Tax:</span>
                    <span id="tax">₱<?php echo number_format((float)($cart_total * 0.12), 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="total-price">₱<?php echo number_format((float)($cart_total * 1.12), 2); ?></span>
                </div>

                <button type="button" id="btn-checkout" class="btn-checkout <?php echo empty($cart_items) ? 'disabled' : ''; ?>" <?php echo empty($cart_items) ? 'disabled' : ''; ?>>Proceed to Checkout</button>

                <div class="promos">
                    <div class="promos-title">Have a promo code?</div>
                    <div class="promo-input-group">
                        <input type="text" class="promo-input" placeholder="Enter code">
                        <button class="promo-btn">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
            <?php include __DIR__ . '/partials/chatbot.php'; ?>
    <script>
        // Cart item handlers: update quantity and remove item
        document.querySelectorAll('.cart-item').forEach(item => {
            const variantId = item.dataset.variantId;
            const qtyInput = item.querySelector('.quantity-input');
            const qtyDecrease = item.querySelector('.qty-decrease');
            const qtyIncrease = item.querySelector('.qty-increase');
            const removeBtn = item.querySelector('.remove-btn');

            function updateQuantity(newQty) {
                if (isNaN(newQty)) newQty = parseInt(qtyInput.value) || 1;
                if (newQty < 1) newQty = 1;
                if (newQty > 999) newQty = 999;

                fetch('cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=update&variant_id=${encodeURIComponent(variantId)}&quantity=${encodeURIComponent(newQty)}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        qtyInput.value = newQty;
                        const priceEl = item.querySelector('.item-price');
                        if (priceEl) priceEl.textContent = '₱' + data.item_total;
                        updateSummary(data);
                    }
                }).catch(err => console.error('Update failed', err));
            }

            function removeItem() {
                fetch('cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=remove&variant_id=${encodeURIComponent(variantId)}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        item.remove();
                        updateSummary(data);

                        const countElement = document.getElementById('cart-count');
                        if (countElement) {
                            countElement.textContent = data.cart_count;
                        }

                        if (data.cart_count === 0) {
                            setTimeout(() => location.reload(), 300);
                        }
                    }
                }).catch(err => console.error('Remove failed', err));
            }

            qtyDecrease.addEventListener('click', () => updateQuantity(parseInt(qtyInput.value) - 1));
            qtyIncrease.addEventListener('click', () => updateQuantity(parseInt(qtyInput.value) + 1));
            qtyInput.addEventListener('change', () => updateQuantity(parseInt(qtyInput.value)));
            removeBtn.addEventListener('click', removeItem);
        });

        function updateSummary(data) {
            const cartTotal = parseFloat(data.cart_total || '0');
            const tax = cartTotal * 0.12;
            const total = cartTotal + tax;

            document.getElementById('subtotal').textContent = '₱' + cartTotal.toFixed(2);
            document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
            document.getElementById('total-price').textContent = '₱' + total.toFixed(2);
        }

        // Checkout button: submit the existing cart form to checkout.php
        document.getElementById('btn-checkout').addEventListener('click', function() {
            if (this.disabled) return;
            const checked = Array.from(document.querySelectorAll('.cart-item-checkbox:checked'));
            if (checked.length === 0) {
                alert('Please select at least one item to check out.');
                return;
            }
            // Submit the existing cart form (it now has method="POST" action="checkout.php")
            const cartForm = document.getElementById('cart-form');
            if (!cartForm) { alert('Form not found.'); return; }
            cartForm.submit();
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
