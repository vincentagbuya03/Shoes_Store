<?php
require_once 'db_connection.php';
require_once 'inc/store_settings.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];
$customer_name = htmlspecialchars($_SESSION['customer_name'] ?? 'Customer', ENT_QUOTES, 'UTF-8');

// Compute cart count for header badge
$cart_count = 0;
$cq = $conn->prepare('SELECT COUNT(*) AS total FROM cart WHERE customer_id = ?');
if ($cq) {
    $cq->bind_param('i', $customer_id);
    $cq->execute();
    $cres = $cq->get_result();
    if ($cres && ($crow = $cres->fetch_assoc())) { $cart_count = (int)($crow['total'] ?? 0); }
    $cq->close();
}

// Determine which timestamp column exists in the `orders` table and fallback if missing
$created_col = 'created_at';
$check = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'created_at'");
if (!($check && $check->num_rows > 0)) {
    $found = false;
    $alts = ['created','created_on','order_date','date_created','timestamp'];
    foreach ($alts as $c) {
        $r = $conn->query("SHOW COLUMNS FROM `orders` LIKE '" . $conn->real_escape_string($c) . "'");
        if ($r && $r->num_rows > 0) { $created_col = $c; $found = true; break; }
    }
    if (!$found) {
        // As a last resort use order_id so the query still runs
        $created_col = 'order_id';
    }
}

// Determine which order-number-like column exists and alias it as order_number
$order_number_col = 'order_number';
$chk2 = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'order_number'");
if (!($chk2 && $chk2->num_rows > 0)) {
    $found2 = false;
    $alts2 = ['order_no','order_ref','invoice_number','reference','external_id','ref_number'];
    foreach ($alts2 as $c) {
        $r2 = $conn->query("SHOW COLUMNS FROM `orders` LIKE '" . $conn->real_escape_string($c) . "'");
        if ($r2 && $r2->num_rows > 0) { $order_number_col = $c; $found2 = true; break; }
    }
    if (!$found2) {
        // Fallback to order_id so UI still has a stable identifier
        $order_number_col = 'order_id';
    }
}

// Fetch orders for the logged-in customer
$orders = [];

// Detect optional columns and safely build SELECT
$shipping_col = null;
$payment_col = null;
$chk_ship = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'shipping_address'");
if ($chk_ship && $chk_ship->num_rows > 0) { $shipping_col = 'shipping_address'; }
else {
    $alts_ship = ['shipping','address','delivery_address','delivery_addr','shipping_addr'];
    foreach ($alts_ship as $c) {
        $r = $conn->query("SHOW COLUMNS FROM `orders` LIKE '" . $conn->real_escape_string($c) . "'");
        if ($r && $r->num_rows > 0) { $shipping_col = $c; break; }
    }
}

$chk_pay = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'payment_method'");
if ($chk_pay && $chk_pay->num_rows > 0) { $payment_col = 'payment_method'; }
else {
    $alts_pay = ['payment','payment_type','payment_method_name','pay_method'];
    foreach ($alts_pay as $c) {
        $r = $conn->query("SHOW COLUMNS FROM `orders` LIKE '" . $conn->real_escape_string($c) . "'");
        if ($r && $r->num_rows > 0) { $payment_col = $c; break; }
    }
}

$select_cols = "o.order_id,\n        o.`{$order_number_col}` AS order_number,\n        o.total_amount,\n        o.status,\n        o.`{$created_col}` AS created_at";

if ($shipping_col) {
    $select_cols .= ",\n        COALESCE(o.`{$shipping_col}`, '') AS shipping_address";
} else {
    $select_cols .= ",\n        '' AS shipping_address";
}

if ($payment_col) {
    $select_cols .= ",\n        COALESCE(o.`{$payment_col}`, '') AS payment_method";
} else {
    $select_cols .= ",\n        '' AS payment_method";
}

$order_sql = "SELECT \n    {$select_cols}\n    FROM orders o\n    WHERE o.customer_id = ?\n    ORDER BY o.`{$created_col}` DESC";
$order_stmt = $conn->prepare($order_sql);
if ($order_stmt) {
    $order_stmt->bind_param('i', $customer_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    while ($row = $order_result->fetch_assoc()) {
        // Cast/normalize
        $row['order_id'] = (int)$row['order_id'];
        $row['total_amount'] = number_format((float)$row['total_amount'], 2);
        $row['created_at'] = $row['created_at'];
        $row['status'] = $row['status'];
        $orders[] = $row;
    }
    $order_stmt->close();
}

// Helper to fetch items for an order (returns array)
function get_order_items($conn, $order_id) {
    $items = [];
        // Inspect columns in order_items so we can safely reference them
        $colsRes = $conn->query("SHOW COLUMNS FROM `order_items`");
        $cols = [];
        if ($colsRes) {
            while ($c = $colsRes->fetch_assoc()) { $cols[] = $c['Field']; }
        }

        // If order_items doesn't have an order_id column, we cannot reliably fetch items
        if (!in_array('order_id', $cols, true)) {
            return $items;
        }

        $has = function($name) use ($cols) { return in_array($name, $cols, true); };

        $select = [];
        // order_item_id
        if ($has('order_item_id')) { $select[] = 'oi.order_item_id AS order_item_id'; }
        else { $select[] = 'NULL AS order_item_id'; }

        // product_id
        if ($has('product_id')) { $select[] = 'oi.product_id AS product_id'; }
        else { $select[] = 'NULL AS product_id'; }

        // product_name (prefer oi.product_name, else product.name when product_id exists)
        if ($has('product_name') && $has('product_id')) {
            $select[] = 'COALESCE(oi.product_name, p.name, "") AS product_name';
        } elseif ($has('product_name')) {
            $select[] = 'COALESCE(oi.product_name, "") AS product_name';
        } elseif ($has('product_id')) {
            $select[] = 'COALESCE(p.name, "") AS product_name';
        } else {
            $select[] = '"" AS product_name';
        }

        // quantity
        if ($has('quantity')) { $select[] = 'oi.quantity AS quantity'; }
        else { $select[] = '1 AS quantity'; }

        // unit_price
        if ($has('unit_price')) { $select[] = 'oi.unit_price AS unit_price'; }
        else { $select[] = '0.00 AS unit_price'; }

        // image_url via product_color_image when product_id and color_id exist
        $joinProduct = $has('product_id');
        $joinPci = ($has('product_id') && $has('color_id'));
        if ($joinPci) {
            $select[] = "COALESCE(pci.image_url, 'upload/product-image/placeholder.png') AS image_url";
        } else {
            $select[] = "'upload/product-image/placeholder.png' AS image_url";
        }

        // color_name
        if ($has('color_id')) {
            $select[] = "COALESCE(c.color_name, '') AS color_name";
        } else {
            $select[] = "'' AS color_name";
        }

        // size
        if ($has('size')) { $select[] = "COALESCE(oi.size, '') AS size"; }
        else { $select[] = "'' AS size"; }

        $sql = "SELECT \n            " . implode(",\n            ", $select) . "\n        FROM order_items oi\n    ";

        if ($joinProduct) {
            $sql .= " LEFT JOIN product p ON oi.product_id = p.product_id\n    ";
        }
        if ($joinPci) {
            $sql .= " LEFT JOIN product_color_image pci ON oi.product_id = pci.product_id AND pci.color_id = oi.color_id AND pci.sort_order = 1\n    ";
        }
        if ($has('color_id')) {
            $sql .= " LEFT JOIN color c ON oi.color_id = c.color_id\n    ";
        }

        $sql .= " WHERE oi.order_id = ?";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                // Normalize types safely
                $r['order_item_id'] = isset($r['order_item_id']) ? (int)$r['order_item_id'] : 0;
                $r['product_id'] = isset($r['product_id']) && $r['product_id'] !== null ? (int)$r['product_id'] : 0;
                $r['quantity'] = isset($r['quantity']) ? (int)$r['quantity'] : 1;
                $r['unit_price'] = isset($r['unit_price']) ? number_format((float)$r['unit_price'], 2) : number_format(0,2);
                $items[] = $r;
            }
            $stmt->close();
        }
    return $items;
}

// Preload items for each order to avoid multiple DB roundtrips in JS later
foreach ($orders as &$order) {
    $order['items'] = get_order_items($conn, $order['order_id']);
}
unset($order);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>My Orders — <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="styles.css" />
        <link rel="stylesheet" href="asset/style/orders.css" />
    <link rel="stylesheet" href="asset/style/index.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <!-- Leaflet CSS for map tracking -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <?php echo getStoreThemeCSS(); ?>
</head>
<body>
    <!-- Announcement Bar -->
    <?php if (!empty($store_settings['announcement_enabled']) && !empty($store_settings['announcement_text'])): ?>
    <div class="announcement-bar" style="background-color: <?php echo htmlspecialchars($store_settings['announcement_bg_color'] ?? '#000'); ?>; color: <?php echo htmlspecialchars($store_settings['announcement_text_color'] ?? '#fff'); ?>;">
        <p><?php echo htmlspecialchars($store_settings['announcement_text']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Site navigation (matching user-interface.php) -->
    <div class="toast-container" id="toast-container"></div>
    <nav>
        <div class="logo">
            <a href="user-interface.php"><?php echo htmlspecialchars($store_settings['store_name']); ?></a>
        </div>
        <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation" tabindex="0">☰</button>
        <ul class="nav-links" id="nav-links">
            <li><a href="user-interface.php">Home</a></li>
            <li><a href="index.php">Best Seller</a></li>
            <li><a href="shoes.php">Shoes</a></li>
            <li><a href="brand.php">Brand</a></li>
        </ul>
        <div class="nav-right">
            <form class="nav-search" action="shoes.php" method="get" role="search" aria-label="Site search">
                <input type="search" name="q" placeholder="Search shoes, brands, categories" aria-label="Search" />
            </form>
            <a href="cart.php" class="cart-icon" id="cart-icon">
                <span>🛒</span>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge" id="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>
            <div class="user-menu" id="user-menu">
                <div class="user-menu-toggle">
                    <i class="fa-solid fa-user"></i>
                    <span class="user-name"><?php echo $customer_name; ?></span>
                </div>
                <div class="user-dropdown">
                    <a href="user-profile.php">My Profile</a>
                    <a href="orders.php">My Orders</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container orders-container" role="main">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fa-solid fa-box"></i> My Orders</h1>
            <p>Track and manage all your orders in one place</p>
        </div>

        <header class="topbar" aria-label="Top bar">
            <div class="controls" role="region" aria-label="Order controls">
                <div class="search" role="search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="searchBox" type="search" placeholder="Search orders, products, order #" aria-label="Search orders">
                </div>
                <select id="statusFilter" class="filter" title="Filter by status" aria-label="Filter orders by status">
                    <option value="all">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </header>

        <main>
            <section class="orders" aria-label="Orders list" id="ordersList">
                <?php if (count($orders) === 0): ?>
                    <div class="empty" role="status">
                        <h3>No orders yet</h3>
                        <p>Looks like you haven't placed any orders. Start shopping to see them here.</p>
                        <p><a class="btn btn-primary" href="shoes.php">Shop Shoes</a></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $ord): 
                        $status = strtolower($ord['status'] ?? 'pending');
                        $status_class = 'status-pending';
                        if ($status === 'shipped') $status_class = 'status-shipped';
                        if ($status === 'delivered') $status_class = 'status-delivered';
                        if ($status === 'cancelled') $status_class = 'status-cancelled';

                        // Safe created_at formatting: if the column contains a valid datetime use it, otherwise show a styled fallback
                        $raw_created = $ord['created_at'] ?? '';
                        if (!empty($raw_created) && strtotime($raw_created) !== false) {
                            $created = '<i class="fa-regular fa-calendar"></i> ' . date('M j, Y \a\t g:ia', strtotime($raw_created));
                        } else {
                            $created = '<span class="fallback">—</span>';
                        }

                        // Order number fallback: use order_number if present, otherwise order_id
                        $display_order_number = $ord['order_number'] ?? $ord['order_id'];

                        // Payment method fallback for UI (use fallback class when empty)
                        $raw_payment = trim((string)($ord['payment_method'] ?? ''));
                        if ($raw_payment !== '') {
                            $payment_method_display = htmlspecialchars($raw_payment, ENT_QUOTES, 'UTF-8');
                        } else {
                            $payment_method_display = '<span class="fallback">—</span>';
                        }
                    ?>
                        <article class="order-card" data-order-id="<?php echo $ord['order_id']; ?>" data-status="<?php echo htmlspecialchars($status); ?>">
                            <div class="order-meta">
                                <div class="order-left">
                                    <div>
                                        <div class="order-number">Order #<?php echo htmlspecialchars($display_order_number, ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="order-date"><?php echo $created; ?></div>
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span>
                                </div>
                            </div>

                            <div class="order-body">
                                <div class="order-items" aria-hidden="false">
                                    <?php if (!empty($ord['items'])): ?>
                                        <?php foreach ($ord['items'] as $it): 
                                            // Normalize image paths (convert backslashes to slashes) to avoid broken images on Windows-stored paths
                                            $raw_img = $it['image_url'] ?? 'upload/product-image/placeholder.png';
                                            $raw_img = str_replace('\\','/', $raw_img);
                                            $img = htmlspecialchars($raw_img, ENT_QUOTES, 'UTF-8');

                                            // Product name: prefer a real name, otherwise show a styled fallback
                                            $raw_name = trim((string)($it['product_name'] ?? ''));
                                            if ($raw_name !== '') {
                                                $pname_html = htmlspecialchars($raw_name, ENT_QUOTES, 'UTF-8');
                                            } else {
                                                $pname_html = '<span class="fallback">Unknown product</span>';
                                            }
                                            // safe attribute form (no HTML)
                                            $pname_attr = htmlspecialchars($raw_name !== '' ? $raw_name : 'Unknown product', ENT_QUOTES, 'UTF-8');

                                            $color = htmlspecialchars($it['color_name'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $size = htmlspecialchars($it['size'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $qty = (int)($it['quantity'] ?? 1);
                                            $unit = htmlspecialchars($it['unit_price'] ?? '0.00', ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <div class="item" title="<?php echo $pname_attr; ?>">
                                                <img src="<?php echo $img; ?>" alt="<?php echo $pname_attr; ?>">
                                                <div class="meta">
                                                    <strong><?php echo $pname_html; ?></strong>
                                                    <span><?php echo $color; ?> <?php echo $size ? "· Size $size" : ''; ?></span>
                                                    <span style="display:block;margin-top:6px;color:var(--muted)"><?php echo "₱{$unit} × {$qty}"; ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="item">
                                            <div class="meta">
                                                <strong>No items found</strong>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="order-summary" aria-label="Order summary">
                                    <div class="total-label">Total</div>
                                    <div class="total-value">₱<?php echo htmlspecialchars($ord['total_amount']); ?></div>
                                    <div style="margin-top:8px;color:var(--orders-muted);font-size:13px;"><?php echo $payment_method_display; ?></div>

                                    <div class="order-actions" role="group" aria-label="Order actions">
                                        <button type="button" class="btn btn-ghost view-details" data-order-id="<?php echo $ord['order_id']; ?>" title="View details" aria-label="View order details">
                                            <i class="fa-regular fa-eye"></i> Details
                                        </button>

                                        <?php
                                            // Show a Track button when order is in transit/delivering
                                            $trackable_statuses = ['shipped','out_for_delivery','delivering','in_transit','on_the_way'];
                                            if (in_array($status, $trackable_statuses, true)): ?>
                                            <button type="button" class="btn btn-outline track-order" data-order-id="<?php echo $ord['order_id']; ?>" title="Track order" aria-label="Track this order">
                                                <i class="fa-solid fa-location-dot"></i> Track
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($status === 'pending'): ?>
                                            <button type="button" class="btn btn-danger cancel-order" data-order-id="<?php echo $ord['order_id']; ?>" title="Cancel order" aria-label="Cancel this order">
                                                <i class="fa-solid fa-xmark"></i> Cancel
                                            </button>
                                        <?php elseif ($status === 'shipped' || $status === 'out_for_delivery' || $status === 'delivering' || $status === 'in_transit'): ?>
                                            <button type="button" class="btn btn-primary confirm-received" data-order-id="<?php echo $ord['order_id']; ?>" title="Mark as received" aria-label="Mark order as received">
                                                <i class="fa-solid fa-check"></i> Mark Received
                                            </button>
                                        <?php elseif ($status === 'completed'): ?>
                                            <button type="button" class="btn btn-warning request-refund" data-order-id="<?php echo $ord['order_id']; ?>" title="Request refund" aria-label="Request a refund">
                                                <i class="fa-solid fa-arrow-rotate-left"></i> Request Refund
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-ghost" disabled><?php echo ucfirst($status); ?></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="order-shipping">
                                <strong>Shipping:</strong>
                                <div><?php $ship = trim((string)($ord['shipping_address'] ?? '')); if ($ship !== '') { echo nl2br(htmlspecialchars($ship, ENT_QUOTES, 'UTF-8')); } else { echo '<span class="fallback">—</span>'; } ?></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

        </main>
    </div>

    <!-- Details modal -->
    <div id="detailsModal" class="details-modal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="dialog" role="document" aria-labelledby="detailsTitle">
            <div class="header">
                <div id="detailsTitle"><strong><i class="fa-solid fa-receipt" style="color: var(--orders-accent, #d4a574); margin-right: 8px;"></i>Order Details</strong></div>
                <button id="closeDetails" class="btn btn-ghost" aria-label="Close details"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="body">
                <div id="detailsBody"></div>
            </div>
            <div class="footer">
                <button id="closeDetails2" class="btn btn-primary">Close</button>
            </div>
        </div>
    </div>

    <div class="toast-container" id="toast-container" aria-live="polite" aria-atomic="true"></div>

    <!-- Track modal -->
    <div id="trackModal" class="track-modal" role="dialog" aria-modal="true" aria-hidden="true">
        <div class="dialog" role="document" aria-labelledby="trackTitle">
            <div class="header">
                <div id="trackTitle"><strong><i class="fa-solid fa-location-dot" style="color: var(--orders-accent, #d4a574); margin-right: 8px;"></i>Track Order</strong></div>
                <button id="closeTrack" class="btn btn-ghost" aria-label="Close map"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="body">
                <div id="trackMap"></div>
            </div>
            <div class="footer">
                <div id="trackFallback" class="track-info" style="float:left"></div>
                <button id="closeTrack2" class="btn btn-primary">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Orders data for client-side details modal
        const ORDERS_DATA = <?php echo json_encode($orders, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    </script>
    <script>
        // Nav toggle and user-menu behaviour (small, non-conflicting helpers)
        (function(){
            const menuToggle = document.getElementById('menu-toggle');
            const navLinks = document.getElementById('nav-links');
            if (menuToggle && navLinks) {
                menuToggle.addEventListener('click', () => navLinks.classList.toggle('show'));
                menuToggle.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') navLinks.classList.toggle('show'); });
            }

            const userMenu = document.getElementById('user-menu');
            const userMenuToggle = userMenu?.querySelector('.user-menu-toggle');
            if (userMenuToggle) {
                userMenuToggle.addEventListener('click', (evt) => { userMenu.classList.toggle('active'); evt.stopPropagation(); });
                document.addEventListener('click', (e) => { if (!userMenu.contains(e.target)) userMenu.classList.remove('active'); });
            }
        })();
        // Simple client-side filtering, actions via fetch to server endpoint (orders_action.php)
        (function(){
            const ordersList = document.getElementById('ordersList');
            const searchBox = document.getElementById('searchBox');
            const statusFilter = document.getElementById('statusFilter');
            const chips = document.querySelectorAll('.chip');
            const toastContainer = document.getElementById('toast-container');

            function showToast(message, isError=false){
                const t = document.createElement('div');
                t.className = 'toast ' + (isError ? 'error' : 'success');
                t.textContent = message;
                toastContainer.appendChild(t);
                setTimeout(()=> {
                    t.style.opacity = 0;
                    setTimeout(()=> t.remove(), 300);
                }, 3500);
            }

            function filterOrders(){
                const q = searchBox.value.trim().toLowerCase();
                const status = statusFilter.value;
                const cards = ordersList.querySelectorAll('.order-card');
                cards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    const cardStatus = card.getAttribute('data-status') || '';
                    const matchesQuery = q === '' || text.includes(q);
                    const matchesStatus = status === 'all' || cardStatus === status;
                    if(matchesQuery && matchesStatus){
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            searchBox.addEventListener('input', filterOrders);
            statusFilter.addEventListener('change', filterOrders);

            chips.forEach(c => {
                c.addEventListener('click', () => {
                    chips.forEach(x => x.classList.remove('active'));
                    c.classList.add('active');
                    const f = c.getAttribute('data-filter');
                    statusFilter.value = f === 'all' ? 'all' : f;
                    filterOrders();
                });
            });

            // DETAILS: open a modal with full order details (client-side data from ORDERS_DATA)
            function escapeHtml(str){
                if (str === null || typeof str === 'undefined') return '';
                return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
            }

            const detailsModalEl = document.getElementById('detailsModal');
            const detailsBodyEl = document.getElementById('detailsBody');
            const closeDetailsBtn = document.getElementById('closeDetails');
            const closeDetailsBtn2 = document.getElementById('closeDetails2');

            function closeDetailsModal(){
                detailsModalEl.classList.remove('active');
                detailsModalEl.setAttribute('aria-hidden','true');
                detailsBodyEl.innerHTML = '';
            }

            closeDetailsBtn.addEventListener('click', closeDetailsModal);
            closeDetailsBtn2.addEventListener('click', closeDetailsModal);
            detailsModalEl.addEventListener('click', (e) => { if (e.target === detailsModalEl) closeDetailsModal(); });

            function buildDetailsHtml(order){
                const parts = [];
                parts.push('<div class="details-meta" style="margin-bottom:10px">');
                parts.push('<div><strong>Order #</strong> ' + escapeHtml(order.order_number || order.order_id) + '</div>');
                parts.push('<div style="color:var(--muted);font-size:0.95rem">' + escapeHtml(order.created_at || '') + '</div>');
                parts.push('<div style="margin-top:6px"><strong>Status:</strong> ' + escapeHtml(order.status || '') + '</div>');
                parts.push('</div>');

                parts.push('<div style="margin-bottom:10px"><strong>Payment:</strong> ' + escapeHtml(order.payment_method || '—') + '</div>');
                parts.push('<div style="margin-bottom:12px"><strong>Shipping address:</strong><div style="margin-top:6px;color:#333">' + (order.shipping_address ? escapeHtml(order.shipping_address).replace(/\n/g,'<br>') : '—') + '</div></div>');

                parts.push('<div class="details-list">');
                if (Array.isArray(order.items) && order.items.length){
                    order.items.forEach(it => {
                        const img = escapeHtml((it.image_url || 'upload/product-image/placeholder.png').replace('\\','/'));
                        const name = escapeHtml(it.product_name || 'Unknown product');
                        const qty = escapeHtml(it.quantity || '1');
                        const unit = escapeHtml(it.unit_price || '0.00');
                        const color = escapeHtml(it.color_name || '');
                        const size = escapeHtml(it.size || '');
                        parts.push('<div class="details-item">');
                        parts.push('<img src="' + img + '" alt="' + name + '">');
                        parts.push('<div style="flex:1">');
                        parts.push('<div style="font-weight:600">' + name + '</div>');
                        parts.push('<div style="color:var(--muted);font-size:0.95rem">' + (color ? color + (size ? ' · Size ' + size : '') : (size ? 'Size ' + size : '')) + '</div>');
                        parts.push('<div style="margin-top:6px;color:var(--muted)">₱' + unit + ' × ' + qty + '</div>');
                        parts.push('</div>');
                        parts.push('</div>');
                    });
                } else {
                    parts.push('<div class="details-item"><div style="flex:1"><strong>No items</strong></div></div>');
                }
                parts.push('</div>');

                parts.push('<div style="margin-top:12px;text-align:right;font-weight:700">Total: ₱' + escapeHtml(order.total_amount || '0.00') + '</div>');
                return parts.join('');
            }

            function openDetailsModal(orderId){
                const id = parseInt(orderId, 10);
                const order = (Array.isArray(ORDERS_DATA) && ORDERS_DATA.find(o => parseInt(o.order_id,10) === id)) || null;
                if (!order){ showToast('Order not found', true); return; }
                detailsBodyEl.innerHTML = buildDetailsHtml(order);
                detailsModalEl.classList.add('active');
                detailsModalEl.setAttribute('aria-hidden','false');
            }

            document.querySelectorAll('.view-details').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const orderId = btn.dataset.orderId;
                    if (!orderId) return;
                    openDetailsModal(orderId);
                });
            });

            // Cancel order and Mark Received actions
            async function postAction(orderId, action) {
                try {
                        const resp = await fetch('orders_action.php', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: new URLSearchParams({ order_id: orderId, action: action })
                        });
                        const txt = await resp.text();
                        let data = null;
                        if (txt) {
                            try { data = JSON.parse(txt); } catch(e) { console.warn('orders_action: invalid JSON', txt); data = null; }
                        }
                    if (data && data.success) {
                        showToast(data.message || 'Updated');
                        // update UI badge and disable buttons accordingly
                        const card = document.querySelector(`.order-card[data-order-id="${orderId}"]`);
                        if (card) {
                            card.setAttribute('data-status', (data.new_status || action).toLowerCase());
                            const badge = card.querySelector('.status-badge');
                            if (badge) {
                                badge.textContent = (data.new_status || action).charAt(0).toUpperCase() + (data.new_status || action).slice(1);
                            }
                            // Re-run filter to hide/show per current filter
                            filterOrders();
                            // Optionally disable action buttons to prevent repeat
                            card.querySelectorAll('button').forEach(b => {
                                if (b.classList.contains('cancel-order') || b.classList.contains('confirm-received')) {
                                    b.disabled = true;
                                    b.classList.add('btn-ghost');
                                }
                            });
                        }
                    } else {
                        showToast((data && data.message) ? data.message : 'Action failed', true);
                    }
                } catch (err) {
                    showToast('Network error', true);
                }
            }

            document.querySelectorAll('.cancel-order').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const orderId = btn.dataset.orderId;
                    if (!confirm('Are you sure you want to cancel this order?')) return;
                    postAction(orderId, 'cancelled');
                });
            });

            document.querySelectorAll('.confirm-received').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const orderId = btn.dataset.orderId;
                    if (!confirm('Confirm you have received this order?')) return;
                    postAction(orderId, 'delivered');
                });
            });

            // Request refund action (sends to request_refund.php)
            document.querySelectorAll('.request-refund').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const orderId = btn.dataset.orderId;
                    if (!orderId) return;
                    if (!confirm('Request a refund for this order?')) return;
                    btn.disabled = true;
                    try {
                        const resp = await fetch('request_refund.php', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: new URLSearchParams({ order_id: orderId })
                        });
                        const txt = await resp.text();
                        let data = null;
                        if (txt) {
                            try { data = JSON.parse(txt); } catch (err) { console.warn('request_refund: invalid JSON', txt); }
                        }
                        if (data && data.success) {
                            showToast(data.message || 'Refund requested');
                            // disable the refund button to avoid duplicate requests
                            btn.disabled = true;
                            btn.classList.add('btn-ghost');
                        } else {
                            showToast((data && data.message) ? data.message : 'Refund request failed', true);
                            btn.disabled = false;
                        }
                    } catch (err) {
                        showToast('Network error', true);
                        btn.disabled = false;
                    }
                });
            });

            // TRACKING: fetch location or tracking link and show modal map / fallback
            let mapInstance = null;
            let currentMarker = null;
            const modal = document.getElementById('trackModal');
            const closeTrack = document.getElementById('closeTrack');
            const closeTrack2 = document.getElementById('closeTrack2');
            const trackFallback = document.getElementById('trackFallback');

            function openModal() {
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
            }
            function closeModal() {
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                // destroy map to free memory
                if (mapInstance) {
                    try { mapInstance.remove(); } catch (e) {}
                    mapInstance = null;
                    currentMarker = null;
                    document.getElementById('trackMap').innerHTML = '';
                }
                trackFallback.textContent = '';
            }

            closeTrack.addEventListener('click', closeModal);
            closeTrack2.addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

            async function fetchTracking(orderId) {
                try {
                    const resp = await fetch('orders_track.php?order_id=' + encodeURIComponent(orderId), {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!resp.ok) throw new Error('Network response not ok');
                    const txt = await resp.text();
                    if (!txt) return { success: false, message: 'Empty response' };
                    try { const data = JSON.parse(txt); return data; } catch (e) { console.warn('fetchTracking: invalid JSON', txt); return { success: false, message: 'Invalid tracking response' }; }
                } catch (err) {
                    return { success: false, message: 'Network error' };
                }
            }

            document.querySelectorAll('.track-order').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    const orderId = btn.dataset.orderId;
                    btn.disabled = true;
                    const res = await fetchTracking(orderId);
                    btn.disabled = false;
                    if (!res || !res.success) {
                        showToast(res && res.message ? res.message : 'Could not fetch tracking info', true);
                        return;
                    }

                    // If we have coordinates (legacy `lat/lng` or `customer_lat`/`rider_lat`), show map
                    if ((typeof res.lat !== 'undefined' && typeof res.lng !== 'undefined') || (typeof res.customer_lat !== 'undefined' && typeof res.customer_lng !== 'undefined') || (typeof res.rider_lat !== 'undefined' && typeof res.rider_lng !== 'undefined')) {
                        openModal();
                        trackFallback.textContent = res.provider ? ('Provider: ' + res.provider) : '';
                        // init leaflet map lazily
                        try {
                            // load leaflet if not loaded yet
                            if (typeof L === 'undefined') {
                                // leaflet JS should be in the page below, but guard
                                showToast('Map library not available', true);
                                return;
                            }
                            // create map — support both single-point responses and dual customer/rider points
                            const centerLat = (typeof res.lat !== 'undefined') ? res.lat : (res.customer_lat || res.rider_lat);
                            const centerLng = (typeof res.lng !== 'undefined') ? res.lng : (res.customer_lng || res.rider_lng);
                            mapInstance = L.map('trackMap').setView([centerLat, centerLng], 13);
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                maxZoom: 19,
                                attribution: '&copy; OpenStreetMap contributors'
                            }).addTo(mapInstance);

                            // If both customer and rider coordinates are present, draw markers for both and request a driving route (OSRM) for accuracy
                            if (typeof res.customer_lat !== 'undefined' && typeof res.customer_lng !== 'undefined' && typeof res.rider_lat !== 'undefined' && typeof res.rider_lng !== 'undefined') {
                                const custMarker = L.marker([res.customer_lat, res.customer_lng]).addTo(mapInstance);
                                custMarker.bindPopup('Your Location').openPopup();
                                const riderMarker = L.marker([res.rider_lat, res.rider_lng]).addTo(mapInstance);
                                riderMarker.bindPopup('Rider location');

                                (async function(){
                                    try {
                                        // Build OSRM coordinates as lon,lat;lon,lat
                                        const coords = [
                                            [res.rider_lng, res.rider_lat],
                                            [res.customer_lng, res.customer_lat]
                                        ];
                                        const parts = coords.map(c => c.join(',')).join(';');
                                        const url = 'https://router.project-osrm.org/route/v1/driving/' + parts + '?overview=full&geometries=geojson';
                                        const r = await fetch(url);
                                        if (r.ok) {
                                            const body = await r.json();
                                            if (body && body.routes && body.routes.length) {
                                                const geom = body.routes[0].geometry;
                                                if (geom && Array.isArray(geom.coordinates) && geom.coordinates.length > 1) {
                                                    const routePts = geom.coordinates.map(c => [c[1], c[0]]);
                                                    const routeLayer = L.polyline(routePts, { color: 'blue', weight: 4, opacity: 0.9 }).addTo(mapInstance);
                                                    mapInstance.fitBounds(routeLayer.getBounds().pad(0.25));
                                                    currentMarker = riderMarker;
                                                    return;
                                                }
                                            }
                                        }
                                    } catch (e) {
                                        console.warn('OSRM route error', e);
                                    }
                                    // Fallback: straight line if routing failed
                                    const fallbackLine = L.polyline([[res.rider_lat, res.rider_lng],[res.customer_lat, res.customer_lng]], {color: 'blue', weight: 4, opacity: 0.7}).addTo(mapInstance);
                                    mapInstance.fitBounds(fallbackLine.getBounds().pad(0.25));
                                    currentMarker = riderMarker;
                                })();
                            } else {
                                // single point legacy behaviour
                                const lat = (typeof res.lat !== 'undefined') ? res.lat : res.customer_lat || res.rider_lat;
                                const lng = (typeof res.lng !== 'undefined') ? res.lng : res.customer_lng || res.rider_lng;
                                currentMarker = L.marker([lat, lng]).addTo(mapInstance);
                                if (res.label) currentMarker.bindPopup(res.label).openPopup();
                            }
                        } catch (err) {
                            showToast('Could not initialize map', true);
                        }
                        return;
                    }

                    // Otherwise show fallback tracking details or link
                    if (res.tracking_url) {
                        openModal();
                        trackFallback.innerHTML = 'Open tracking: <a target="_blank" rel="noopener noreferrer" href="' + encodeURIComponent(res.tracking_url).replace(/%3A/g, ':').replace(/%2F/g, '/') + '">Tracking link</a>';
                    } else if (res.tracking_number) {
                        openModal();
                        trackFallback.textContent = 'Tracking number: ' + res.tracking_number;
                    } else {
                        showToast(res.message || 'No tracking information available', true);
                    }
                });
            });

            // initial filter pass
            filterOrders();
        })();
    </script>

    <!-- Leaflet JS (placed near bottom to load after DOM) -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>