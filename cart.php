<?php
require_once 'db_connection.php';
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
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    if ($action === 'remove' && $product_id > 0) {
        $delete_query = "DELETE FROM cart WHERE customer_id = ? AND product_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('ii', $customer_id, $product_id);
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
            SELECT SUM(p.price * c.quantity) as total 
            FROM cart c
            JOIN product p ON c.product_id = p.product_id
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
            'cart_total' => number_format($new_total, 2)
        ]);
        exit();
    }
    
    if ($action === 'update' && $product_id > 0) {
        $quantity = (int)($_POST['quantity'] ?? 1);
        if ($quantity < 1) $quantity = 1;
        if ($quantity > 999) $quantity = 999;
        
        $update_query = "UPDATE cart SET quantity = ? WHERE customer_id = ? AND product_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('iii', $quantity, $customer_id, $product_id);
        $stmt->execute();
        $stmt->close();
        

        $item_query = "SELECT p.price FROM product p WHERE p.product_id = ?";
        $stmt = $conn->prepare($item_query);
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $item_result = $stmt->get_result();
        $item_row = $item_result->fetch_assoc();
        $item_total = number_format($item_row['price'] * $quantity, 2);
        $stmt->close();
        
        $total_query = "
            SELECT SUM(p.price * c.quantity) as total 
            FROM cart c
            JOIN product p ON c.product_id = p.product_id
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
            'cart_total' => number_format($new_total, 2)
        ]);
        exit();
    }
    
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}


$cart_query = "
    SELECT 
        c.cart_id,
        c.product_id,
        c.quantity,
        p.name,
        p.price,
        p.stock,
        b.brand_name,
        pi.image_url
    FROM cart c
    LEFT JOIN product p ON c.product_id = p.product_id
    LEFT JOIN brand b ON p.brand_id = b.brand_id
    LEFT JOIN product_image pi ON p.product_id = pi.product_id AND pi.sort_order = 1
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
    <title>Shopping Cart - ShoeTakels</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1a1a1a;
            --accent: #d4a574;
            --light: #f5f5f5;
            --white: #ffffff;
            --text: #333333;
            --text-light: #666666;
            --success: #10b981;
            --error: #ef4444;
            --border: #e5e7eb;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background-color: var(--light);
        }

        nav {
            background: var(--white);
            padding: 1rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        nav .logo a {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
        }

        nav .nav-back {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        nav a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        nav a:hover {
            color: var(--primary);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            color: var(--primary);
        }

        .breadcrumb {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        .breadcrumb a {
            color: var(--accent);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .cart-wrapper {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }

        .cart-items-section {
            background: var(--white);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .cart-item {
            display: grid;
            grid-template-columns: 120px 1fr 120px;
            gap: 1.5rem;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            align-items: center;
            transition: background 0.2s;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item:hover {
            background: var(--light);
            border-radius: 8px;
        }

        .cart-item-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .cart-item-brand {
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .cart-item-price {
            font-size: 1.1rem;
            color: var(--accent);
            font-weight: 700;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0.25rem;
            width: fit-content;
            background: var(--white);
        }

        .quantity-btn {
            background: none;
            border: none;
            width: 32px;
            height: 32px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.1rem;
            transition: background 0.2s;
            border-radius: 4px;
        }

        .quantity-btn:hover {
            background: var(--light);
        }

        .quantity-input {
            width: 45px;
            border: none;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
            background: none;
            color: var(--primary);
        }

        .quantity-input:focus {
            outline: none;
        }

        .cart-item-total {
            text-align: right;
        }

        .item-total-label {
            font-size: 0.85rem;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .item-total-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--accent);
        }

        .remove-btn {
            background: none;
            border: none;
            color: var(--error);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0.5rem;
            transition: transform 0.2s;
        }

        .remove-btn:hover {
            transform: scale(1.1);
        }

        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }

        .empty-cart-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-cart h2 {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .empty-cart p {
            margin-bottom: 2rem;
        }

        .btn-continue-shopping {
            display: inline-block;
            background: var(--primary);
            color: var(--white);
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-continue-shopping:hover {
            background: var(--accent);
        }

        .order-summary {
            background: var(--white);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 2rem;
            height: fit-content;
        }

        .summary-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: var(--text-light);
        }

        .summary-row.total {
            border-top: 2px solid var(--border);
            padding-top: 1rem;
            margin-top: 1rem;
            color: var(--primary);
            font-weight: 700;
            font-size: 1.2rem;
        }

        .btn-checkout {
            width: 100%;
            background: var(--accent);
            color: var(--primary);
            padding: 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            margin-top: 1.5rem;
        }

        .btn-checkout:hover {
            background: #c29460;
            transform: translateY(-2px);
        }

        .btn-checkout:active {
            transform: translateY(0);
        }

        .btn-checkout.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .promos {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }

        .promos-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }

        .promo-input-group {
            display: flex;
            gap: 0.5rem;
        }

        .promo-input {
            flex: 1;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .promo-btn {
            background: var(--primary);
            color: var(--white);
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .promo-btn:hover {
            background: var(--accent);
        }

        @media (max-width: 768px) {
            nav {
                padding: 1rem 1.5rem;
                flex-direction: column;
                gap: 1rem;
            }

            .page-header {
                flex-direction: column;
                text-align: center;
            }

            .cart-wrapper {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }

            .cart-item {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .cart-item-image {
                width: 100%;
                height: 200px;
            }

            .cart-item-total {
                text-align: left;
            }

            .quantity-controls {
                width: 100%;
                justify-content: space-around;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">
            <a href="user-interface.php">ShoeTakels</a>
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
                <span id="cart-count"><?php echo count($cart_items); ?></span> item(s) in cart
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
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <div class="cart-item-image">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'upload/product-image/placeholder.png', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="cart-item-details">
                                <div class="cart-item-name"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="cart-item-brand"><?php echo htmlspecialchars($item['brand_name'] ?? 'Unknown Brand', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="cart-item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                                <div style="margin-top: 0.75rem;">
                                    <div class="quantity-controls">
                                        <button class="quantity-btn qty-decrease" aria-label="Decrease quantity">−</button>
                                        <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="999">
                                        <button class="quantity-btn qty-increase" aria-label="Increase quantity">+</button>
                                    </div>
                                </div>
                            </div>
                            <div class="cart-item-total">
                                <div class="item-total-label">Subtotal</div>
                                <div class="item-total-price item-price">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                <button class="remove-btn" aria-label="Remove item" title="Remove from cart">×</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="order-summary">
                <div class="summary-title">Order Summary</div>
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span id="subtotal">₱<?php echo number_format($cart_total, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span id="shipping">₱0.00</span>
                </div>
                <div class="summary-row">
                    <span>Tax:</span>
                    <span id="tax">₱<?php echo number_format($cart_total * 0.12, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total:</span>
                    <span id="total-price">₱<?php echo number_format($cart_total * 1.12, 2); ?></span>
                </div>

                <button class="btn-checkout <?php echo empty($cart_items) ? 'disabled' : ''; ?>" <?php echo empty($cart_items) ? 'disabled' : ''; ?>>Proceed to Checkout</button>

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

    <script>
        document.querySelectorAll('.cart-item').forEach(item => {
            const productId = item.dataset.productId;
            const qtyInput = item.querySelector('.quantity-input');
            const qtyDecrease = item.querySelector('.qty-decrease');
            const qtyIncrease = item.querySelector('.qty-increase');
            const removeBtn = item.querySelector('.remove-btn');

            function updateQuantity(newQty) {
                if (newQty < 1) newQty = 1;
                if (newQty > 999) newQty = 999;

                fetch('cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=update&product_id=${productId}&quantity=${newQty}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        qtyInput.value = newQty;
                        item.querySelector('.item-price').textContent = '₱' + data.item_total;
                        updateSummary(data);
                    }
                });
            }

            function removeItem() {
                fetch('cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=remove&product_id=${productId}`
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
                });
            }

            qtyDecrease.addEventListener('click', () => updateQuantity(parseInt(qtyInput.value) - 1));
            qtyIncrease.addEventListener('click', () => updateQuantity(parseInt(qtyInput.value) + 1));
            qtyInput.addEventListener('change', () => updateQuantity(parseInt(qtyInput.value)));
            removeBtn.addEventListener('click', removeItem);
        });

        function updateSummary(data) {
            const cartTotal = parseFloat(data.cart_total);
            const tax = cartTotal * 0.12;
            const total = cartTotal + tax;
            
            document.getElementById('subtotal').textContent = '₱' + parseFloat(data.cart_total).toFixed(2);
            document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
            document.getElementById('total-price').textContent = '₱' + total.toFixed(2);
        }

        document.querySelector('.btn-checkout').addEventListener('click', function() {
            if (!this.disabled) {
                window.location.href = 'checkout.php';
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
