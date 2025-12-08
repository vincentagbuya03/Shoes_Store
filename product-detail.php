<?php

require_once 'db_connection.php';
require_once 'inc/store_settings.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

$customer_name = $_SESSION['customer_name'] ?? 'Customer';
$cart_count = 0;
if (isset($_SESSION['customer_id'])) {
    $cid = (int)$_SESSION['customer_id'];
    $cart_count_query = $conn->prepare('SELECT COUNT(*) as total FROM cart WHERE customer_id = ?');
    $cart_count_query->bind_param('i', $cid);
    $cart_count_query->execute();
    $cart_count_result = $cart_count_query->get_result();
    if ($cart_count_result && ($row = $cart_count_result->fetch_assoc())) {
        $cart_count = (int)($row['total'] ?? 0);
    }
    $cart_count_query->close();
}

// Load the Gemini description helper from `inc/` if present; fall back to project root.
$helper_included = false;
if (file_exists(__DIR__ . '/inc/gemini_description.php')) {
    include_once __DIR__ . '/inc/gemini_description.php';
    $helper_included = true;
} elseif (file_exists(__DIR__ . '/gemini_description.php')) {
    include_once __DIR__ . '/gemini_description.php';
    $helper_included = true;
}
if (!$helper_included && function_exists('error_log')) {
    error_log('[gemini] helper not loaded: looked in /inc and project root');
}

// Get product ID
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($product_id <= 0) {
    die("Product not found.");
}

$product_stmt = $conn->prepare(
    "SELECT p.product_id, p.name, p.category, b.brand_name FROM product p LEFT JOIN brand b ON p.brand_id = b.brand_id WHERE p.product_id = ?"
);
$product_stmt->bind_param("i", $product_id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();
$product = $product_result->fetch_assoc();
if (!$product) {
    die("Product not found.");
}

// This now correctly fetches all 5 images per color
$images_stmt = $conn->prepare("
    SELECT pci.image_url, c.color_name
    FROM product_color_image pci
    LEFT JOIN color c ON pci.color_id = c.color_id
    WHERE pci.product_id = ?
    ORDER BY c.color_name ASC, pci.color_id ASC
");
$images_stmt->bind_param("i", $product_id);
$images_stmt->execute();
$images_result = $images_stmt->get_result();

$images = [];
$colorToImages = []; // Store all images per color (up to 5 per color)
while ($row = $images_result->fetch_assoc()) {
    $images[] = $row['image_url'];
    
    if (!empty($row['color_name'])) {
        if (!isset($colorToImages[$row['color_name']])) {
            $colorToImages[$row['color_name']] = [];
        }
        $colorToImages[$row['color_name']][] = $row['image_url'];
    }
}

// Fetch variants
$variants_stmt = $conn->prepare("
    SELECT pv.variant_id, pv.price, pv.stock, s.size_name AS size, c.color_name AS color
    FROM product_variant pv
    LEFT JOIN size s ON pv.size_id = s.size_id
    LEFT JOIN color c ON pv.color_id = c.color_id
    WHERE pv.product_id = ?
    ORDER BY s.size_name ASC, c.color_name ASC
");
$variants_stmt->bind_param("i", $product_id);
$variants_stmt->execute();
$variants_result = $variants_stmt->get_result();

$variants = [];
$min_price = PHP_INT_MAX;
$max_price = 0;

while ($row = $variants_result->fetch_assoc()) {
    $variants[] = $row;

    if ($row['price'] < $min_price) $min_price = $row['price'];
    if ($row['price'] > $max_price) $max_price = $row['price'];
}

// Build variant map for JS
$variantMap = [];
foreach ($variants as $v) {
    $key = $v['size'] . '|' . $v['color'];
    $variantMap[$key] = $v;
}

// Count how many of this product are in the cart for this user
$cart_count = 0;
if (isset($_SESSION['customer_id'])) {
    $cart_conn = new mysqli($servername, $username, $password, $dbname);
    if (!$cart_conn->connect_error) {
        $cart_stmt = $cart_conn->prepare("SELECT SUM(c.quantity) as total FROM cart c INNER JOIN product_variant v ON c.variant_id = v.variant_id WHERE c.customer_id = ? AND v.product_id = ?");
        $cart_stmt->bind_param('ii', $_SESSION['customer_id'], $product_id);
        $cart_stmt->execute();
        $cart_result = $cart_stmt->get_result();
        if ($cart_result && ($row = $cart_result->fetch_assoc())) {
            $cart_count = (int)($row['total'] ?? 0);
        }
        $cart_stmt->close();
    }
    $cart_conn->close();
}

$final_description = '';
// Ensure product array contains a price for better prompts
if (!isset($product['price']) && isset($min_price) && $min_price !== PHP_INT_MAX) {
    $product['price'] = $min_price;
}

// Allow forcing regeneration from the UI for debugging: ?regen=1
$cache_file_path = __DIR__ . '/upload/product-descriptions/' . ($product['product_id'] ?? 'unknown') . '-long-en.txt';
if (isset($_GET['regen']) && $_GET['regen']) {
    @unlink($cache_file_path);
    if (function_exists('error_log')) error_log("[gemini_test] regen requested, deleted cache: {$cache_file_path}");
}

if (function_exists('generate_product_description')) {
    $final_description = (string) generate_product_description($product, 'en');
}

// If the LLM helper returned nothing or empty, use fallback (if available)
if ((trim($final_description) === '') && function_exists('generate_fallback_description')) {
    $final_description = generate_fallback_description($product, 'en');
}

// Admin debug: infer whether gemini or fallback was used (best-effort)
$display_used = 'unknown';
if (function_exists('generate_fallback_description')) {
    $fb = generate_fallback_description($product, 'en');
    if (trim((string)$final_description) === trim((string)$fb)) {
        $display_used = 'fallback';
    } else {
        $display_used = 'gemini';
    }
}

// Don't close $conn here - it's needed for the brand dropdown in navigation
// $conn->close(); // Moved to end of file

// Get store logo
$store_logo = '';
if (!empty($store_settings['store_logo'])) {
    $store_logo = $store_settings['store_logo'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?> - <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="asset/style/beautiful-ui.css">
    <link rel="stylesheet" href="asset/style/animations.css">
    <?php echo getStoreThemeCSS(); ?>
    <link rel="stylesheet" href="asset/style/product-details.css">
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
</head>

<body>

    <div class="toast-container" id="toast-container"></div>
    
    <!-- Navigation (Same as other pages) -->
    <nav class="nav-modern">
        <div class="logo">
            <a href="user-interface.php" class="logo-modern">
                <span class="gradient-text-accent"><?php echo htmlspecialchars($store_settings['store_name']); ?></span>
                <span class="logo-sparkle">✨</span>
            </a>
        </div>
        <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation" tabindex="0">
            <i class="fas fa-bars"></i>
        </button>
        <ul class="nav-links" id="nav-links">
            <li><a href="12_12.php" class="nav-link-enhanced sale-link"><i class="fas fa-fire nav-icon"></i> 12.12 Sale</a></li>
            <li><a href="user-interface.php" class="nav-link-enhanced"><i class="fas fa-home nav-icon"></i> Home</a></li>
            <li><a href="best-seller.php" class="nav-link-enhanced"><i class="fas fa-star nav-icon"></i> Best Seller</a></li>
            <li><a href="shoes.php" class="nav-link-enhanced"><i class="fas fa-shoe-prints nav-icon"></i> Shoes</a></li>
            <li class="brand-dropdown">
                <a href="brand.php" class="brand-link">Brand</a>
                <div class="brand-mega-menu">
                    <div class="brand-grid">
                        <?php
                            $brand_nav_query = "SELECT brand_id, brand_name, brand_logo FROM brand ORDER BY brand_name ASC";
                            $brand_nav_result = $conn->query($brand_nav_query);
                            if ($brand_nav_result && $brand_nav_result->num_rows > 0) {
                                while ($brand_nav = $brand_nav_result->fetch_assoc()) {
                                    $bn_name = htmlspecialchars($brand_nav['brand_name'], ENT_QUOTES, 'UTF-8');
                                    $bn_logo = htmlspecialchars($brand_nav['brand_logo'] ?? 'upload/brand/default_logo.png', ENT_QUOTES, 'UTF-8');
                                    $bn_id = (int)$brand_nav['brand_id'];
                                    echo "<a href='brand.php?id={$bn_id}' class='brand-item'>
                                            <div class='brand-logo-box'><img src='{$bn_logo}' alt='{$bn_name} logo'></div>
                                            <h4 class='brand-item-name'>{$bn_name}</h4>
                                          </a>";
                                }
                            }
                        ?>
                    </div>
                </div>
            </li>
        </ul>
        <div class="nav-right">
            <a href="cart.php" class="cart-icon cart-icon-modern" id="cart-icon" title="Shopping Cart">
                <i class="fas fa-shopping-bag"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge cart-badge-modern" id="cart-badge"><?php echo $cart_count; ?></span>
                <?php else: ?>
                    <span class="cart-badge cart-badge-modern" id="cart-badge" style="display: none;">0</span>
                <?php endif; ?>
                <span class="cart-pulse"></span>
            </a>
            <div class="user-menu" id="user-menu">
                <div class="user-menu-toggle">
                    <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                    <div class="user-info">
                        <span class="user-greeting">Hello,</span>
                        <span class="user-name"><?php echo htmlspecialchars($customer_name); ?></span>
                    </div>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </div>
                <div class="user-dropdown">
                    <div class="dropdown-header">
                        <div class="dropdown-avatar"><i class="fas fa-user-circle"></i></div>
                        <div class="dropdown-user-info">
                            <span class="dropdown-name"><?php echo htmlspecialchars($customer_name); ?></span>
                            <span class="dropdown-email">Manage your account</span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="user-profile.php" class="dropdown-item"><i class="fas fa-user-cog"></i> My Profile</a>
                    <a href="orders.php" class="dropdown-item"><i class="fas fa-box"></i> My Orders</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item logout-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="pd-main">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-modern">
            <div class="breadcrumb-container">
                <a href="user-interface.php" class="breadcrumb-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <i class="fas fa-chevron-right breadcrumb-separator"></i>
                <a href="shoes.php" class="breadcrumb-link">
                    <span>Shop</span>
                </a>
                <i class="fas fa-chevron-right breadcrumb-separator"></i>
                <span class="breadcrumb-current"><?php echo htmlspecialchars($product['name']); ?></span>
            </div>
        </nav>

        <!-- Product Grid -->
        <div class="pd-product-grid">
            <!-- Gallery -->
            <div class="pd-gallery">
                <?php
                    $firstColor = isset($_GET['color']) && isset($colorToImages[$_GET['color']]) ? $_GET['color'] : array_key_first($colorToImages);
                    $defaultImages = $firstColor ? $colorToImages[$firstColor] : [];
                ?>
                <div class="pd-gallery-main">
                    <span class="pd-gallery-badge">New Arrival</span>
                    <img id="mainImage" 
                         src="<?php echo htmlspecialchars($defaultImages[0] ?? 'upload/product-image/placeholder.png'); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="pd-gallery-actions">
                        <button class="pd-gallery-btn" title="Zoom">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                <div class="pd-gallery-thumbs" id="thumbnailList">
                    <?php foreach ($defaultImages as $index => $img): ?>
                        <div class="pd-thumb <?php echo $index === 0 ? 'active' : ''; ?>"
                             data-image="<?php echo htmlspecialchars($img); ?>">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="pd-info">
                <span class="pd-brand">
                    <i class="fas fa-award"></i>
                    <?php echo htmlspecialchars($product['brand_name'] ?? 'Premium Brand'); ?>
                </span>

                <h1 class="pd-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                <div class="pd-rating">
                    <div class="pd-rating-stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <span class="pd-rating-text">4.5 (128 reviews)</span>
                </div>

                <div class="pd-price-box">
                    <span class="pd-price-current">
                        ₱<?php echo number_format((float)$min_price, 2); ?>
                    </span>
                    <?php if ($min_price != $max_price): ?>
                        <span class="pd-price-original">₱<?php echo number_format((float)$max_price + 500, 2); ?></span>
                        <span class="pd-price-badge">Save 15%</span>
                    <?php endif; ?>
                </div>

                <?php if ($cart_count > 0): ?>
                    <div class="pd-cart-notice">
                        <i class="fas fa-shopping-cart"></i>
                        <span><?php echo $cart_count; ?> item(s) already in your cart</span>
                    </div>
                <?php endif; ?>

                <!-- AI Description -->
                <div class="pd-description">
                    <div class="pd-description-header">
                        <span class="pd-description-title">
                            <i class="fas fa-align-left"></i>
                            Product Description
                        </span>
                        <button class="pd-ai-btn" id="generateDescBtn" title="Generate description with AI">
                            <i class="fas fa-wand-magic-sparkles"></i>
                            AI Generate
                        </button>
                    </div>
                    <p class="pd-description-content" id="productDesc">
                        <?php 
                            $display_desc = is_string($final_description) ? trim($final_description) : '';
                            $display_desc = str_ireplace('N/A', '', $display_desc);
                            $display_desc = trim($display_desc);
                            if ($display_desc === '') {
                                if (function_exists('generate_fallback_description')) {
                                    $display_desc = generate_fallback_description($product, 'en');
                                } else {
                                    $display_desc = 'Experience unparalleled comfort and style with these premium shoes. Crafted with attention to detail, featuring high-quality materials and expert craftsmanship for lasting durability.';
                                }
                            }
                            echo nl2br(htmlspecialchars($display_desc));
                        ?>
                    </p>
                </div>

                <?php if (count($variants) > 0): ?>
                    <!-- Variant Selectors -->
                    <div class="pd-variants">
                        <?php
                        $sizes = [];
                        $colors = [];
                        foreach ($variants as $v) {
                            $sizes[$v['size']] = true;
                            $colors[$v['color']] = true;
                        }
                        $sizes = array_keys($sizes);
                        $colors = array_keys($colors);
                        ?>

                        <!-- Size Selector -->
                        <div class="pd-variant-group">
                            <label class="pd-variant-label">
                                <i class="fas fa-ruler-horizontal"></i>
                                Select Size
                                <span class="hint">— Size Guide</span>
                            </label>
                            <div class="pd-sizes" id="sizeOptions">
                                <?php foreach ($sizes as $size): ?>
                                    <button class="pd-size-btn" data-size="<?php echo htmlspecialchars($size); ?>">
                                        <?php echo htmlspecialchars($size); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Color Selector -->
                        <div class="pd-variant-group">
                            <label class="pd-variant-label">
                                <i class="fas fa-palette"></i>
                                Select Color
                            </label>
                            <div class="pd-colors" id="colorOptions">
                                <?php foreach ($colors as $color): ?>
                                    <div class="pd-color-option">
                                        <button class="pd-color-btn"
                                                data-color="<?php echo htmlspecialchars($color); ?>"
                                                title="<?php echo htmlspecialchars($color); ?>"
                                                style="background-color: <?php echo htmlspecialchars($color); ?>;">
                                        </button>
                                        <span class="pd-color-name"><?php echo htmlspecialchars($color); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Box -->
                    <div class="pd-purchase" id="variantInfo">
                        <div class="pd-purchase-price" id="variantPrice">₱0.00</div>
                        <div class="pd-purchase-stock" id="variantStock">
                            <i class="fas fa-circle"></i>
                            Select size and color
                        </div>

                        <div class="pd-quantity-row">
                            <span class="pd-quantity-label">Quantity:</span>
                            <div class="pd-quantity-control">
                                <button type="button" id="qty-decrease" class="pd-qty-btn">−</button>
                                <input type="number" id="quantity" class="pd-qty-input" value="1" min="1" max="10">
                                <button type="button" id="qty-increase" class="pd-qty-btn">+</button>
                            </div>
                        </div>

                        <button class="pd-add-cart-btn" id="addToCartBtn" data-variant-id="">
                            <i class="fas fa-shopping-bag"></i>
                            Add to Cart
                        </button>

                        <div class="pd-purchase-secondary">
                            <button class="pd-secondary-btn">
                                <i class="far fa-heart"></i>
                                Wishlist
                            </button>
                            <button class="pd-secondary-btn">
                                <i class="fas fa-share-alt"></i>
                                Share
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Features -->
        <section class="pd-features">
            <div class="pd-feature">
                <div class="pd-feature-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3 class="pd-feature-title">Free Shipping</h3>
                <p class="pd-feature-text">Free delivery on orders over ₱2,500</p>
            </div>
            <div class="pd-feature">
                <div class="pd-feature-icon">
                    <i class="fas fa-arrow-rotate-left"></i>
                </div>
                <h3 class="pd-feature-title">Easy Returns</h3>
                <p class="pd-feature-text">30-day hassle-free return policy</p>
            </div>
            <div class="pd-feature">
                <div class="pd-feature-icon">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <h3 class="pd-feature-title">Secure Payment</h3>
                <p class="pd-feature-text">100% secure payment methods</p>
            </div>
            <div class="pd-feature">
                <div class="pd-feature-icon">
                    <i class="fas fa-headphones"></i>
                </div>
                <h3 class="pd-feature-title">24/7 Support</h3>
                <p class="pd-feature-text">Dedicated customer service team</p>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo">
                        <i class="fas fa-shoe-prints"></i>
                        <?php echo htmlspecialchars($store_settings['store_name']); ?>
                    </div>
                    <p class="footer-tagline">Premium footwear for the modern lifestyle. Quality, comfort, and style combined.</p>
                    <div class="footer-social">
                        <?php if (!empty($store_settings['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_facebook']); ?>" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($store_settings['social_instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_instagram']); ?>" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($store_settings['social_twitter'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_twitter']); ?>" target="_blank"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($store_settings['social_tiktok'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_tiktok']); ?>" target="_blank"><i class="fab fa-tiktok"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="footer-column">
                    <h4>Shop</h4>
                    <ul>
                        <li><a href="shoes.php">All Shoes</a></li>
                        <li><a href="brand.php">Brands</a></li>
                        <li><a href="best-seller.php">Best Sellers</a></li>
                        <li><a href="#">New Arrivals</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">FAQs</a></li>
                        <li><a href="#">Shipping Info</a></li>
                        <li><a href="#">Size Guide</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Press</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">Returns Policy</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="footer-copyright">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($store_settings['store_name']); ?>. All rights reserved.</p>
                <div class="footer-payments">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-paypal"></i>
                    <i class="fab fa-google-pay"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- JAVASCRIPT -->
    <script>
        const variantMap = <?php echo json_encode($variantMap); ?>;
        const colorToImages = <?php echo json_encode($colorToImages); ?>;
        let selectedSize = null;
        let selectedColor = null;

        const urlParams = new URLSearchParams(window.location.search);
        const initialColor = urlParams.get('color');

        function updateVariantInfo() {
            if (!selectedSize || !selectedColor) {
                document.getElementById('variantInfo').classList.remove('show');
                return;
            }

            const key = selectedSize + "|" + selectedColor;
            const variant = variantMap[key];

            if (!variant) {
                document.getElementById('variantInfo').classList.remove('show');
                return;
            }

            // Price
            document.getElementById('variantPrice').textContent = '₱' + parseFloat(variant.price).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Stock state
            const stock = parseInt(variant.stock) || 0;
            const stockEl = document.getElementById('variantStock');

            if (stock > 10) {
                stockEl.innerHTML = '<i class="fas fa-check-circle"></i> In Stock';
                stockEl.className = "pd-purchase-stock in-stock";
            } else if (stock > 0) {
                stockEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Only ' + stock + ' left';
                stockEl.className = "pd-purchase-stock low-stock";
            } else {
                stockEl.innerHTML = '<i class="fas fa-times-circle"></i> Out of Stock';
                stockEl.className = "pd-purchase-stock out-of-stock";
            }

            const addBtn = document.getElementById('addToCartBtn');
            addBtn.dataset.variantId = variant.variant_id;
            addBtn.disabled = stock === 0;

            document.getElementById('variantInfo').classList.add('show');
        }

        // Size selector
        document.querySelectorAll('.pd-size-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                selectedSize = this.dataset.size;
                document.querySelectorAll('.pd-size-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');

                document.querySelectorAll('.pd-color-btn').forEach(b => {
                    const color = b.dataset.color;
                    b.disabled = !(selectedSize + "|" + color in variantMap);
                });

                updateVariantInfo();
            });
        });

        // Color selector
        document.querySelectorAll('.pd-color-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                selectedColor = this.dataset.color;
                document.querySelectorAll('.pd-color-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');

                // Update gallery images
                const galleryImages = colorToImages[selectedColor] || [];
                const mainImage = document.getElementById('mainImage');
                const thumbnailList = document.getElementById('thumbnailList');

                if (galleryImages.length > 0) {
                    mainImage.src = galleryImages[0];
                } else {
                    mainImage.src = 'upload/product-image/placeholder.png';
                }

                // Update thumbnails
                let thumbsHtml = '';
                galleryImages.forEach((img, idx) => {
                    thumbsHtml += `<div class="pd-thumb ${idx === 0 ? 'active' : ''}" data-image="${img}">
                        <img src="${img}" alt="Thumbnail ${idx + 1}">
                    </div>`;
                });
                thumbnailList.innerHTML = thumbsHtml;

                // Re-attach click events to thumbnails
                thumbnailList.querySelectorAll('.pd-thumb').forEach(thumb => {
                    thumb.addEventListener('click', function () {
                        thumbnailList.querySelectorAll('.pd-thumb').forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                        mainImage.src = this.dataset.image;
                    });
                });

                updateVariantInfo();
            });
        });

        // Thumbnail click
        document.querySelectorAll('.pd-thumb').forEach(thumb => {
            thumb.addEventListener('click', function () {
                document.querySelectorAll('.pd-thumb').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('mainImage').src = this.dataset.image;
            });
        });

        // Quantity controls
        const qtyInput = document.getElementById('quantity');
        const qtyDec = document.getElementById('qty-decrease');
        const qtyInc = document.getElementById('qty-increase');

        function clampQuantity() {
            if (!qtyInput) return 1;
            let v = parseInt(qtyInput.value) || 1;
            const min = parseInt(qtyInput.getAttribute('min')) || 1;
            const max = parseInt(qtyInput.getAttribute('max')) || 10;
            if (v < min) v = min;
            if (v > max) v = max;
            qtyInput.value = v;
            return v;
        }

        qtyDec?.addEventListener('click', () => {
            const cur = clampQuantity();
            qtyInput.value = Math.max(cur - 1, 1);
        });

        qtyInc?.addEventListener('click', () => {
            const cur = clampQuantity();
            qtyInput.value = Math.min(cur + 1, 10);
        });

        qtyInput?.addEventListener('change', clampQuantity);

        // Add to cart
        document.getElementById('addToCartBtn')?.addEventListener('click', async function (e) {
            e.preventDefault();
            const variantId = this.dataset.variantId;
            const quantity = parseInt(document.getElementById('quantity').value) || 1;

            if (!variantId) {
                alert('Please select size and color');
                return;
            }

            const btn = this;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

            try {
                const res = await fetch("partials/add-to-cart.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `variant_id=${encodeURIComponent(variantId)}&quantity=${encodeURIComponent(quantity)}`
                });

                const data = await res.json();

                if (data.success) {
                    btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                    setTimeout(() => { btn.innerHTML = originalText; }, 2000);
                    
                    // Update cart badge (modern nav)
                    if (data.cart_count !== undefined) {
                        const badge = document.getElementById('cart-badge');
                        if (badge) {
                            badge.textContent = data.cart_count;
                            badge.style.display = data.cart_count > 0 ? 'flex' : 'none';
                        }
                    }
                } else {
                    alert(data.message || 'Failed to add to cart');
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                console.error(err);
                alert('Failed to add to cart');
                btn.innerHTML = originalText;
            } finally {
                btn.disabled = false;
            }
        });

        // AI Description Generator
        document.getElementById('generateDescBtn')?.addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.innerHTML;
            const descEl = document.getElementById('productDesc');
            
            btn.disabled = true;
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner"></i> Generating...';
            
            try {
                const response = await fetch('api/gemini_generate_description.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        product_id: <?php echo $product_id; ?>,
                        product_name: <?php echo json_encode($product['name']); ?>,
                        brand_name: <?php echo json_encode($product['brand_name'] ?? ''); ?>,
                        category: <?php echo json_encode($product['category'] ?? ''); ?>
                    })
                });
                
                const data = await response.json();
                
                if (data.success && data.description) {
                    descEl.innerHTML = data.description.replace(/\n/g, '<br>');
                    btn.innerHTML = '<i class="fas fa-check"></i> Done!';
                    setTimeout(() => { btn.innerHTML = originalText; }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to generate description');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error: ' + error.message);
                btn.innerHTML = originalText;
            } finally {
                btn.disabled = false;
                btn.classList.remove('loading');
            }
        });

        // Modern Navigation Menu Toggle
        const menuToggle = document.getElementById('menu-toggle');
        const navLinks = document.getElementById('nav-links');
        
        if (menuToggle && navLinks) {
            menuToggle.addEventListener('click', function() {
                navLinks.classList.toggle('active');
                this.classList.toggle('active');
            });
        }

        // User Menu Dropdown
        const userMenu = document.getElementById('user-menu');
        if (userMenu) {
            const userMenuToggle = userMenu.querySelector('.user-menu-toggle');
            const userDropdown = userMenu.querySelector('.user-dropdown');
            
            if (userMenuToggle && userDropdown) {
                userMenuToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('active');
                });
                
                document.addEventListener('click', function(e) {
                    if (!userMenu.contains(e.target)) {
                        userMenu.classList.remove('active');
                    }
                });
            }
        }

        // Cart Badge Update
        function updateCartBadge(count) {
            const badge = document.getElementById('cart-badge');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
        }

        // Initialize: click first color on load
        document.addEventListener('DOMContentLoaded', () => {
            if (initialColor) {
                const colorButton = document.querySelector(`.pd-color-btn[data-color="${CSS.escape(initialColor)}"]`);
                if (colorButton && !colorButton.disabled) {
                    colorButton.click();
                    return;
                }
            }
            const firstColorButton = document.querySelector('.pd-color-btn:not(:disabled)');
            if (firstColorButton) {
                firstColorButton.click();
            }
        });
    </script>
    <?php include __DIR__ . '/partials/chatbot.php'; ?>
<?php 
// Close database connection at the end
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
</body>
</html>