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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="asset/style/animations.css">
    <?php echo getStoreThemeCSS(); ?>
    <link rel="stylesheet" href="asset/style/cart.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="cart-nav">
        <div class="cart-nav-container">
            <a href="user-interface.php" class="cart-nav-brand">
                <img src="upload/logo/<?php echo $store_settings['store_logo'] ?? 'logo.png'; ?>" alt="<?php echo htmlspecialchars($store_settings['store_name']); ?>">
                <span><?php echo htmlspecialchars($store_settings['store_name']); ?></span>
            </a>
            <div class="cart-nav-actions">
                <a href="user-interface.php" class="nav-action-btn" title="Continue Shopping">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <a href="user-interface.php" class="nav-action-btn" title="Home">
                    <i class="fas fa-home"></i>
                </a>
            </div>
        </div>
    </nav>

    <main class="cart-main">
        <div class="cart-container">
            <!-- Page Header -->
            <header class="cart-page-header">
                <h1>
                    <i class="fas fa-shopping-bag"></i>
                    Shopping Cart
                </h1>
                <nav class="cart-breadcrumb">
                    <a href="user-interface.php">Home</a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Cart</span>
                </nav>
            </header>

            <?php if (empty($cart_items)): ?>
            <!-- Empty Cart State -->
            <div class="cart-empty-state">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet. Start exploring our collection!</p>
                <a href="user-interface.php" class="btn-shop-now">
                    <span>Start Shopping</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php else: ?>
            <!-- Cart Content -->
            <div class="cart-wrapper">
                <!-- Cart Items Section -->
                <section class="cart-items-section">
                    <div class="cart-items-header">
                        <div class="select-all-wrapper">
                            <label>
                                <div class="cart-checkbox">
                                    <input type="checkbox" id="select-all-items" checked>
                                    <span class="checkmark"></span>
                                </div>
                                <span>Select all</span>
                            </label>
                            <span class="item-count">(<?php echo count($cart_items); ?> items)</span>
                        </div>
                    </div>
                    
                    <form id="cart-form" method="POST" action="checkout.php">
                        <div class="cart-items-list">
                            <?php foreach ($cart_items as $index => $item): ?>
                            <article class="cart-item" data-variant-id="<?php echo $item['variant_id']; ?>">
                                <!-- Checkbox -->
                                <div class="cart-item-select">
                                    <div class="cart-checkbox">
                                        <input type="checkbox" class="item-checkbox" name="selected_items[]" value="<?php echo $item['variant_id']; ?>" checked>
                                        <span class="checkmark"></span>
                                    </div>
                                </div>
                                
                                <!-- Product Image -->
                                <div class="cart-item-image">
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'upload/product-image/placeholder.png'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                
                                <!-- Product Details -->
                                <div class="cart-item-details">
                                    <span class="item-brand"><?php echo htmlspecialchars($item['brand_name'] ?? 'Brand'); ?></span>
                                    <a href="product-detail.php?id=<?php echo $item['product_id']; ?>" class="item-name">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                    <div class="item-variants">
                                        <span class="variant-tag">
                                            <i class="fas fa-palette"></i>
                                            <?php echo htmlspecialchars($item['color_name']); ?>
                                        </span>
                                        <span class="variant-tag">
                                            <i class="fas fa-ruler"></i>
                                            Size <?php echo htmlspecialchars($item['size_name']); ?>
                                        </span>
                                    </div>
                                    <?php if ($item['stock'] <= 5 && $item['stock'] > 0): ?>
                                    <div class="item-stock low-stock">
                                        <i class="fas fa-circle"></i>
                                        Only <?php echo $item['stock']; ?> left
                                    </div>
                                    <?php elseif ($item['stock'] > 5): ?>
                                    <div class="item-stock in-stock">
                                        <i class="fas fa-circle-check"></i>
                                        In Stock
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Actions -->
                                <div class="cart-item-actions">
                                    <div class="quantity-control">
                                        <button type="button" class="qty-decrease" <?php echo $item['quantity'] <= 1 ? 'disabled' : ''; ?>>
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="99">
                                        <button type="button" class="qty-increase">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div class="item-price-wrapper">
                                        <span class="item-price"><?php echo $store_settings['currency_symbol']; ?><?php echo number_format((float)($item['price'] * $item['quantity']), 2); ?></span>
                                        <span class="item-unit-price"><?php echo $store_settings['currency_symbol']; ?><?php echo number_format((float)$item['price'], 2); ?> each</span>
                                    </div>
                                    <button type="button" class="remove-btn" title="Remove item">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        </div>
                    </form>
                </section>

                <!-- Order Summary Sidebar -->
                <aside class="order-summary">
                    <div class="summary-header">
                        <div class="summary-header-content">
                            <i class="fas fa-receipt"></i>
                            <span class="summary-title">Order Summary</span>
                        </div>
                    </div>
                    
                    <div class="summary-body">
                        <div class="summary-row">
                            <span class="summary-label">Subtotal</span>
                            <span class="summary-value" id="subtotal"><?php echo $store_settings['currency_symbol']; ?><?php echo number_format((float)$cart_total, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">
                                Shipping
                                <i class="fas fa-info-circle info-icon" title="Free shipping on all orders"></i>
                            </span>
                            <span class="summary-value free" id="shipping">FREE</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Tax (12% VAT)</span>
                            <span class="summary-value" id="tax"><?php echo $store_settings['currency_symbol']; ?><?php echo number_format((float)($cart_total * 0.12), 2); ?></span>
                        </div>
                        
                        <div class="summary-divider"></div>
                        
                        <div class="summary-row total">
                            <span class="summary-label">Total</span>
                            <span class="summary-value" id="total-price"><?php echo $store_settings['currency_symbol']; ?><?php echo number_format((float)($cart_total * 1.12), 2); ?></span>
                        </div>
                        
                        <button type="button" id="btn-checkout" class="btn-checkout">
                            <i class="fas fa-lock"></i>
                            <span>Proceed to Checkout</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                        
                        <!-- Promo Code -->
                        <div class="promo-section">
                            <div class="promo-header">
                                <i class="fas fa-ticket"></i>
                                <span>Have a promo code?</span>
                            </div>
                            <div class="promo-input-group">
                                <input type="text" class="promo-input" placeholder="Enter code">
                                <button type="button" class="promo-btn">Apply</button>
                            </div>
                        </div>
                        
                        <!-- Guarantees -->
                        <div class="guarantees">
                            <div class="guarantee">
                                <i class="fas fa-shield-halved"></i>
                                <span>Secure</span>
                            </div>
                            <div class="guarantee">
                                <i class="fas fa-rotate-left"></i>
                                <span>Returns</span>
                            </div>
                            <div class="guarantee">
                                <i class="fas fa-truck-fast"></i>
                                <span>Fast Ship</span>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/partials/chatbot.php'; ?>

    <script>
        // Select All functionality
        const selectAllCheckbox = document.getElementById('select-all-items');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');
        
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                itemCheckboxes.forEach(cb => cb.checked = this.checked);
            });
            
            itemCheckboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    selectAllCheckbox.checked = Array.from(itemCheckboxes).every(c => c.checked);
                });
            });
        }

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
                        // Update decrease button state
                        if (qtyDecrease) qtyDecrease.disabled = newQty <= 1;
                        const priceEl = item.querySelector('.item-price');
                        if (priceEl) priceEl.textContent = '₱' + data.item_total;
                        updateSummary(data);
                    }
                }).catch(err => console.error('Update failed', err));
            }

            function removeItem() {
                item.style.transform = 'translateX(100%)';
                item.style.opacity = '0';
                item.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
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

                            // Update item count display
                            const countEl = document.querySelector('.item-count');
                            if (countEl) {
                                countEl.textContent = `(${data.cart_count} items)`;
                            }

                            if (data.cart_count === 0) {
                                setTimeout(() => location.reload(), 300);
                            }
                        }
                    }).catch(err => console.error('Remove failed', err));
                }, 300);
            }

            if (qtyDecrease) qtyDecrease.addEventListener('click', (e) => {
                e.preventDefault();
                updateQuantity(parseInt(qtyInput.value) - 1);
            });
            if (qtyIncrease) qtyIncrease.addEventListener('click', (e) => {
                e.preventDefault();
                updateQuantity(parseInt(qtyInput.value) + 1);
            });
            if (qtyInput) qtyInput.addEventListener('change', () => updateQuantity(parseInt(qtyInput.value)));
            if (removeBtn) removeBtn.addEventListener('click', removeItem);
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
        const checkoutBtn = document.getElementById('btn-checkout');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', function() {
                if (this.disabled) return;
                const checked = Array.from(document.querySelectorAll('.item-checkbox:checked'));
                if (checked.length === 0) {
                    alert('Please select at least one item to check out.');
                    return;
                }
                const cartForm = document.getElementById('cart-form');
                if (!cartForm) { alert('Form not found.'); return; }
                cartForm.submit();
            });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
