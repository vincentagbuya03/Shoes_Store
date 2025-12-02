<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ShoeTakels - Welcome</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/index.css">
    <link rel="stylesheet" href="asset/style/product.css">
    <link rel="stylesheet" href="asset/style/carousel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <script src="asset/script/script.js" defer></script>

    <!-- Chat widget & PJAX styles (kept inline for simplicity) -->
    <style>
        /* Modern floating chat widget */
        .ai-chat-widget {
            position: fixed;
            right: 24px;
            bottom: 24px;
            width: 360px;
            max-width: calc(100% - 48px);
            box-shadow: 0 10px 30px rgba(16,24,40,0.2);
            border-radius: 14px;
            overflow: hidden;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            z-index: 9999;
            transform-origin: bottom right;
            transition: transform 180ms ease, opacity 180ms ease;
        }

        .ai-chat-widget.minimized {
            width: 60px;
            height: 60px;
            border-radius: 999px;
            overflow: visible;
        }

        .ai-chat-toggle {
            background: linear-gradient(180deg,#0ea5a3,#0b8380);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 999px;
            cursor: pointer;
            box-shadow: 0 6px 18px rgba(2,6,23,0.24);
            border: none;
        }

        .ai-chat-header {
            display:flex;
            align-items:center;
            gap:12px;
            padding:12px 16px;
            background: linear-gradient(90deg, #ffffff, #f7fdfc);
            border-bottom: 1px solid rgba(8, 15, 20, 0.06);
        }

        .ai-chat-title {
            font-weight: 700;
            font-size: 15px;
            color: #062021;
            line-height:1;
        }
        .ai-chat-sub {
            font-size:12px;
            color:#4b6462;
        }

        .ai-chat-body {
            background: #ffffff;
            max-height: 420px;
            overflow-y: auto;
            padding:12px;
            display:flex;
            flex-direction:column;
            gap:10px;
        }

        .ai-msg {
            max-width: 78%;
            padding:10px 12px;
            border-radius: 10px;
            line-height:1.35;
            font-size: 14px;
            box-shadow: 0 1px 0 rgba(0,0,0,0.03);
        }
        .ai-msg.user {
            align-self: flex-end;
            background: linear-gradient(90deg,#06b6d4,#0891b2);
            color: white;
            border-bottom-right-radius: 4px;
        }
        .ai-msg.assistant {
            align-self: flex-start;
            background: #f3f6f6;
            color: #062021;
            border-bottom-left-radius: 4px;
        }

        .ai-chat-input {
            display:flex;
            gap:8px;
            padding:12px;
            border-top: 1px solid rgba(8,15,20,0.06);
            background: #fff;
        }
        .ai-input-field {
            flex:1;
            background: #f7faf9;
            border-radius: 10px;
            padding:10px 12px;
            border: 1px solid rgba(8,15,20,0.06);
            outline: none;
            font-size:14px;
        }
        .ai-send-btn {
            background: #0ea5a3;
            color: #fff;
            border: none;
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            font-weight:600;
        }

        .ai-chat-minimized-label {
            display:none;
            position: absolute;
            right: 80px;
            bottom: 32px;
            background: rgba(2,6,23,0.9);
            color: #fff;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 13px;
            box-shadow: 0 8px 20px rgba(2,6,23,0.2);
        }

        .ai-chat-widget.minimized .ai-chat-minimized-label {
            display:block;
        }

        .ai-chat-footer {
            font-size: 12px;
            color: #28524b;
            padding: 8px 12px;
            background: linear-gradient(90deg,#f7fdfc,#ffffff);
            border-top:1px solid rgba(8,15,20,0.03);
        }

        .ai-status {
            font-size:12px;
            color:#0b6b66;
        }

        /* Small screen adjustments */
        @media (max-width: 520px) {
            .ai-chat-widget { right: 12px; bottom: 12px; width: 92%; max-width: 420px; border-radius: 12px; }
        }

        /* PJAX fade animation */
        .pjax-fade {
            transition: opacity .18s ease;
        }
        .pjax-loading {
            opacity: .5;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="toast-container" id="toast-container"></div>
    <nav>
        <div class="logo">
            <a href="user-interface.php" class="pjax-link">ShoeTakels</a>
        </div>
        <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation" tabindex="0">☰</button>
        <ul class="nav-links" id="nav-links">
            <li><a href="12_12.php" class="pjax-link">12.12 Sale</a></li>
            <li><a href="user-interface.php" class="pjax-link">Home</a></li>
            <li><a href="index.php" class="pjax-link">Best Seller</a></li>
            <li><a href="shoes.php" class="pjax-link">Shoes</a></li>
            <li class="brand-dropdown">
                <a href="brand.php" class="brand-link pjax-link">Brand</a>
                <div class="brand-mega-menu">
                    <div class="brand-grid">
                        <?php
                            $brand_query = "SELECT brand_id, brand_name, brand_logo 
                                            FROM Brand 
                                            ORDER BY brand_name ASC";
                            $brand_result = $conn->query($brand_query);

                            if ($brand_result && $brand_result->num_rows > 0) {
                                while ($brand = $brand_result->fetch_assoc()) {

                                    $brand_name = htmlspecialchars($brand['brand_name'], ENT_QUOTES, 'UTF-8');
                                    $brand_logo = htmlspecialchars($brand['brand_logo'], ENT_QUOTES, 'UTF-8');
                                    $brand_id   = (int)$brand['brand_id'];

                                    if (empty($brand_logo)) {
                                        $brand_logo = "upload/brand/default_logo.png";
                                    }
                                    echo "
                                    <a href='brand.php?id={$brand_id}' class='brand-item pjax-link'>
                                        <div class='brand-logo-box'>
                                            <img src='{$brand_logo}' alt='{$brand_name} logo'>
                                        </div>
                                        <h4 class='brand-item-name'>{$brand_name}</h4>
                                    </a>
                                    ";
                                }
                            }
                        ?>
                    </div>
                </div>
            </li>
        </ul>
        <div class="nav-right">
            <form class="nav-search" action="shoes.php" method="get" role="search" aria-label="Site search" data-pjax>
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
                    <span class="user-name"><?php echo htmlspecialchars($customer_name); ?></span>
                </div>
                <div class="user-dropdown">
                    <a href="user-profile.php" class="pjax-link">My Profile</a>
                    <a href="orders.php" class="pjax-link">My Orders</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- MAIN PAGE CONTENT (will be replaced via PJAX to keep chat widget alive) -->
    <main id="page-content" class="pjax-fade">
    <section class="hero">
        <div class="hero-content">
            <h1>Step Into Style and Comfort</h1>
            <p>Discover our exclusive collection of premium footwear designed for every occasion. From athletic performance to everyday elegance, find your perfect pair.</p>
            <div class="cta-buttons">
                <button class="btn-primary">Shop Now</button>
                <button class="btn-secondary">Learn More</button>
            </div>
        </div>
        <div class="hero-image">
                <?php include __DIR__ . '/partials/carousel.php'; ?>
        </div>
    </section>
    
    <?php include __DIR__ . '/partials/hot-sales.php'; ?>
                    
    <section class="categories">
        <h2 class="section-title">Shop By Category</h2>
        <p class="section-subtitle">Find the perfect pair for every family member</p>

        <div class="category-grid">
            <a href="shop.php?category=Men" class="category-card pjax-link">
                <div class="category-image">
                    <img src="upload\category\men.jpg" alt="Men's Shoes">
                </div>
                <div class="category-title">Men</div>
            </a>

            <a href="shop.php?category=Women" class="category-card pjax-link">
                <div class="category-image">
                    <img src="upload\category\women.jpg" alt="Women's Shoes">
                </div>
                <div class="category-title">Women</div>
            </a>
            <a href="shop.php?category=Kids" class="category-card pjax-link">
                <div class="category-image">
                    <img src="upload\category\kid.webp" alt="Kids' Shoes">
                </div>
                <div class="category-title">Kids</div>
            </a>
        </div>
    </section>
    
    <section class="new-arrivals" id="new-arrivals">
        <h2 class="section-title">New Arrivals</h2>
        <p class="section-subtitle">Fresh styles just dropped</p>
        
        <div class="products-grid">
            <?php
                $query = "
                    SELECT 
                        s.product_id, 
                        s.name, 
                        b.brand_name, 
                        s.Category AS CategoryName, 
                        pci.image_url,
                        c.color_name AS primary_color,
                        (
                            SELECT MIN(v.price) FROM product_variant v WHERE v.product_id = s.product_id AND v.color_id = pci.color_id
                        ) AS min_price,
                        (
                            SELECT MAX(v.price) FROM product_variant v WHERE v.product_id = s.product_id AND v.color_id = pci.color_id
                        ) AS max_price
                    FROM Product s
                    LEFT JOIN Brand b ON s.brand_id = b.brand_id
                    LEFT JOIN product_color_image pci ON s.product_id = pci.product_id AND pci.sort_order = 1
                    LEFT JOIN color c ON pci.color_id = c.color_id
                    ORDER BY s.created_at DESC
                    LIMIT 4
                ";
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0) {
                    while($product = $result->fetch_assoc()) {
                        $img_path = !empty($product['image_url']) ? $product['image_url'] : 'upload/product-image/placeholder.png';
                        $img = htmlspecialchars($img_path, ENT_QUOTES, 'UTF-8');
                        $name = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
                        $brand = htmlspecialchars($product['brand_name'], ENT_QUOTES, 'UTF-8');
                        $pid = (int)$product['product_id'];
                        $raw_primary_color = $product['primary_color'] ?? '';
                        $primary_color = htmlspecialchars($raw_primary_color !== '' ? $raw_primary_color : 'N/A', ENT_QUOTES, 'UTF-8');
                        $min_price = $product['min_price'] !== null ? number_format((float)$product['min_price'], 2) : 'N/A';
                        $max_price = $product['max_price'] !== null ? number_format((float)$product['max_price'], 2) : 'N/A';
                        $price_display = ($min_price === $max_price) ? "₱$min_price" : "₱$min_price - ₱$max_price";
                        $color_sql = "SELECT DISTINCT c.color_name FROM product_variant v LEFT JOIN color c ON v.color_id = c.color_id WHERE v.product_id = $pid ORDER BY c.color_name ASC";
                        $color_res = $conn->query($color_sql);
                        $color_swatches = '';
                        if ($color_res && $color_res->num_rows > 0) {
                            $color_swatches .= '<div class="product-swatches">';
                            while ($color_row = $color_res->fetch_assoc()) {
                                $color_name = $color_row['color_name'];
                                $color_param = urlencode($color_name);
                                $color_label = htmlspecialchars($color_name, ENT_QUOTES, 'UTF-8');
                                $color_style = "background-color: $color_label;";
                                $color_swatches .= "<a href='product-detail.php?id={$pid}&color=$color_param' class='color-swatch pjax-link' title='$color_label' style='$color_style'></a> ";
                            }
                            $color_swatches .= '</div>';
                        }
                        echo "
                        <div class='product-card'>
                            <div class='product-image'>
                                <a href='product-detail.php?id={$pid}&color=" . urlencode($raw_primary_color) . "' class='pjax-link' title='View $name'>
                                    <img src='$img' alt='$name' class='product-thumb' loading='lazy'>
                                </a>
                            </div>
                            <h3 class='product-name'><a href='product-detail.php?id={$pid}&color=" . urlencode($raw_primary_color) . "' class='pjax-link'>$name</a></h3>
                            <div class='product-brand'>$brand</div>
                            <div class='product-color'>$primary_color</div>
                            $color_swatches
                            <div class='product-price'>$price_display</div>
                            <!-- Add data-product-id to button for AJAX handling -->
                            <button class='btn-primary add-to-cart-btn' data-product-id='$pid' style='width: 100%;'>Add to Cart</button>
                        </div>
                        ";
                    }
                } else {
                    echo "<p>No new arrivals available</p>";
                }
            ?>
        </div>
    </section>

    <section class="video-section">
        <div class="video-container">
            <video autoplay muted loop class="background-video">
                <source src="upload/video/ads.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="video-overlay"></div>
            <button class="video-button">Shop Now</button>
        </div>
    </section>

    <section class="logo-carousel-section">
        <div class="logo-carousel-wrapper">
            <div class="logo-carousel-container" id="logoCarousel">
                <?php
                    $carousel_brand_query = "SELECT brand_id, brand_name, brand_logo 
                                             FROM Brand 
                                             WHERE brand_logo IS NOT NULL AND brand_logo != ''
                                             ORDER BY brand_name ASC";
                    $carousel_brand_result = $conn->query($carousel_brand_query);
                    $carousel_brands = [];

                    if ($carousel_brand_result && $carousel_brand_result->num_rows > 0) {
                        while ($brand = $carousel_brand_result->fetch_assoc()) {
                            $carousel_brands[] = $brand;
                        }
                    }
                    $repeated_brands = array_merge($carousel_brands, $carousel_brands);

                    foreach ($repeated_brands as $brand) {
                        $brand_logo = htmlspecialchars($brand['brand_logo'], ENT_QUOTES, 'UTF-8');
                        $brand_name = htmlspecialchars($brand['brand_name'], ENT_QUOTES, 'UTF-8');
                        $brand_id = (int)$brand['brand_id'];

                        echo "
                        <a href='brand.php?id={$brand_id}' class='logo-carousel-item pjax-link' title='{$brand_name}'>
                            <img src='{$brand_logo}' alt='{$brand_name}' loading='lazy'>
                        </a>
                        ";
                    }
                ?>
            </div>
        </div>
    </section>

    <section class="featured" id="shop">
        <h2 class="section-title">Featured Collection</h2>
        <p class="section-subtitle">Handpicked styles for the season</p>
        
        <div class="products-grid">
            <?php
                $query = "
                    SELECT 
                        s.product_id, 
                        s.name, 
                        b.brand_name, 
                        s.Category AS CategoryName, 
                        pci.image_url,
                        c.color_name AS primary_color,
                        (
                            SELECT MIN(v.price) FROM product_variant v WHERE v.product_id = s.product_id AND v.color_id = pci.color_id
                        ) AS min_price,
                        (
                            SELECT MAX(v.price) FROM product_variant v WHERE v.product_id = s.product_id AND v.color_id = pci.color_id
                        ) AS max_price
                    FROM Product s
                    LEFT JOIN Brand b ON s.brand_id = b.brand_id
                    LEFT JOIN product_color_image pci ON s.product_id = pci.product_id AND pci.sort_order = 1
                    LEFT JOIN color c ON pci.color_id = c.color_id
                    LIMIT 4
                ";
                $result = $conn->query($query);
                
                if ($result && $result->num_rows > 0) {
                    while($product = $result->fetch_assoc()) {
                        $img_path = !empty($product['image_url']) ? $product['image_url'] : 'upload/product-image/placeholder.png';
                        $img = htmlspecialchars($img_path, ENT_QUOTES, 'UTF-8');
                        $name = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
                        $brand = htmlspecialchars($product['brand_name'], ENT_QUOTES, 'UTF-8');
                        $pid = (int)$product['product_id'];
                        $raw_primary_color = $product['primary_color'] ?? '';
                        $primary_color = htmlspecialchars($raw_primary_color !== '' ? $raw_primary_color : 'N/A', ENT_QUOTES, 'UTF-8');
                        $min_price = $product['min_price'] !== null ? number_format((float)$product['min_price'], 2) : 'N/A';
                        $max_price = $product['max_price'] !== null ? number_format((float)$product['max_price'], 2) : 'N/A';
                        $price_display = ($min_price === $max_price) ? "₱$min_price" : "₱$min_price - ₱$max_price";

                        // Fetch all available colors for this product
                        $color_sql = "SELECT DISTINCT c.color_name FROM product_variant v LEFT JOIN color c ON v.color_id = c.color_id WHERE v.product_id = $pid ORDER BY c.color_name ASC";
                        $color_res = $conn->query($color_sql);
                        $color_swatches = '';
                        if ($color_res && $color_res->num_rows > 0) {
                            $color_swatches .= '<div class="product-swatches">';
                            while ($color_row = $color_res->fetch_assoc()) {
                                $color_name = $color_row['color_name'];
                                $color_param = urlencode($color_name);
                                $color_label = htmlspecialchars($color_name, ENT_QUOTES, 'UTF-8');
                                $color_style = "background-color: $color_label;";
                                $color_swatches .= "<a href='product-detail.php?id={$pid}&color=$color_param' class='color-swatch pjax-link' title='$color_label' style='$color_style'></a> ";
                            }
                            $color_swatches .= '</div>';
                        }

                        echo "
                        <div class='product-card'>
                            <div class='product-image'>
                                <a href='product-detail.php?id={$pid}&color=" . urlencode($raw_primary_color) . "' class='pjax-link' title='View $name'>
                                    <img src='$img' alt='$name' class='product-thumb' loading='lazy'>
                                </a>
                            </div>
                            <h3 class='product-name'><a href='product-detail.php?id={$pid}&color=" . urlencode($raw_primary_color) . "' class='pjax-link'>$name</a></h3>
                            <div class='product-brand'>$brand</div>
                            <div class='product-color'>$primary_color</div>
                            $color_swatches
                            <div class='product-price'>$price_display</div>
                            <!-- Add data-product-id to button for AJAX handling -->
                            <button class='btn-primary add-to-cart-btn' data-product-id='$pid' style='width: 100%;'>Add to Cart</button>
                        </div>
                        ";
                    }
                } else {
                    echo "<p>No featured products available</p>";
                }
            // End featured section PHP
            ?>
        </div>
    </section>

    <section class="benefits">
        <div class="benefit-item">
            <h3>Free Shipping</h3>
            <p>On orders over 50. Fast and reliable delivery to your doorstep.</p>
        </div>
        <div class="benefit-item">
            <h3>Easy Returns</h3>
            <p>30-day hassle-free returns if you're not completely satisfied.</p>
        </div>
        <div class="benefit-item">
            <h3>Quality Guaranteed</h3>
            <p>100% authentic premium footwear with lifetime warranty on defects.</p>
        </div>
        <div class="benefit-item">
            <h3>Expert Support</h3>
            <p>24/7 customer service ready to help with sizing and style advice.</p>
        </div>
    </section>

    <section class="newsletter">
        <h2>Stay Updated on New Releases</h2>
        <p>Subscribe to get exclusive offers and first access to new collections</p>
        <form class="newsletter-form" onsubmit="return handleSubscribe(event)">
            <input type="email" placeholder="Enter your email" required>
            <button type="submit" class="btn-primary">Subscribe</button>
        </form>
    </section>
    </main>
    
    <footer>
        <div>
            <h4>About Us</h4>
            <ul>
                <li><a href="#">Our Story</a></li>
                <li><a href="#">Sustainability</a></li>
                <li><a href="#">Careers</a></li>
                <li><a href="#">Press</a></li>
            </ul>
        </div>
        <div>
            <h4>Shop</h4>
            <ul>
                <li><a href="#">Men's Shoes</a></li>
                <li><a href="#">Women's Shoes</a></li>
                <li><a href="#">Accessories</a></li>
                <li><a href="#">New Arrivals</a></li>
            </ul>
        </div>
        <div>
            <h4>Support</h4>
            <ul>
                <li><a href="#">Contact Us</a></li>
                <li><a href="#">FAQ</a></li>
                <li><a href="#">Shipping Info</a></li>
                <li><a href="#">Size Guide</a></li>
            </ul>
        </div>
        <div>
            <h4>Legal</h4>
            <ul>
                <li><a href="#">Privacy Policy</a></li>
                <li><a href="#">Terms of Service</a></li>
                <li><a href="#">Cookie Policy</a></li>
            </ul>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 ShoesTakels. All rights reserved.</p>
        </div>
    </footer>

    <!-- AI Chat widget (persistent while PJAX replaces #page-content) -->
    <div id="aiChat" class="ai-chat-widget minimized" aria-live="polite" aria-atomic="true">
        <div class="ai-chat-minimized-label">Chat with ShoeBot</div>
        <div style="display:flex; align-items:center; justify-content:flex-end; padding:10px;">
            <button id="aiToggle" class="ai-chat-toggle" aria-expanded="false" aria-controls="aiPanel" title="Open chat">
                <i class="fa-solid fa-robot" aria-hidden="true"></i>
            </button>
        </div>

        <div id="aiPanel" style="display:none; background:#fff;">
            <div class="ai-chat-header">
                <div>
                    <div class="ai-chat-title">ShoeBot</div>
                    <div class="ai-chat-sub">Ask about products, sizes, shipping & more</div>
                </div>
                <div style="margin-left:auto; display:flex; gap:8px; align-items:center;">
                    <button id="aiMinimize" title="Minimize" style="background:none; border:none; font-size:18px; cursor:pointer;">−</button>
                    <button id="aiClose" title="Close" style="background:none; border:none; font-size:18px; cursor:pointer;">×</button>
                </div>
            </div>
            <div id="aiMessages" class="ai-chat-body" role="log" aria-live="polite"></div>
            <div id="aiOfflineBanner" style="display:none;padding:8px 12px;background:#fff3f2;color:#7a2a2a;border-top:1px solid #ffd7d0;font-size:13px;">AI offline — using a local fallback response.</div>
            <div class="ai-chat-input">
                <input id="aiInput" class="ai-input-field" placeholder="Type a message..." aria-label="Type a message"/>
                <button id="aiSend" class="ai-send-btn">Send</button>
            </div>
            <div class="ai-chat-footer">
                <span class="ai-status" id="aiStatus">Powered by an AI assistant — your data is not stored on third-party pages.</span>
            </div>
        </div>
    </div>

    <script>
        // ---------------------------
        // PJAX (simple) - intercept clicks on .pjax-link and form submissions that have data-pjax
        // Keeps #aiChat widget alive when navigating the site
        // ---------------------------
        (function () {
            const container = document.getElementById('page-content');

            async function loadUrl(url, addToHistory = true) {
                if (!url) return;
                container.classList.add('pjax-loading');
                try {
                    const res = await fetch(url, {credentials: 'same-origin'});
                    if (!res.ok) {
                        window.location.href = url; // fall back
                        return;
                    }
                    const text = await res.text();
                    // Extract content between <main id="page-content">...</main>
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(text, 'text/html');
                    const newMain = doc.getElementById('page-content');
                    const newTitle = doc.querySelector('title')?.innerText || document.title;
                    if (newMain) {
                        container.innerHTML = newMain.innerHTML;
                        document.title = newTitle;
                        if (addToHistory) history.pushState({url: url}, '', url);
                        // re-run client-side initializers for dynamic features on the loaded content
                        reinitializePage();
                    } else {
                        // If no #page-content in response, navigate normally
                        window.location.href = url;
                    }
                } catch (err) {
                    console.error('PJAX error', err);
                    window.location.href = url;
                } finally {
                    setTimeout(() => container.classList.remove('pjax-loading'), 200);
                }
            }

            // Attach click handler for links
            document.addEventListener('click', function (e) {
                const link = e.target.closest('a.pjax-link');
                if (!link) return;
                const url = link.href;
                // Only pjax same-origin navigation without modifiers
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || link.target === '_blank') return;
                if (link.origin !== location.origin) return;
                e.preventDefault();
                loadUrl(url, true);
            });

            // Attach for forms with data-pjax attribute
            document.addEventListener('submit', function (e) {
                const form = e.target.closest('form[data-pjax]');
                if (!form) return;
                e.preventDefault();
                const url = form.action || location.href;
                const params = new URLSearchParams(new FormData(form));
                const method = (form.method || 'GET').toUpperCase();
                let fetchUrl = url;
                if (method === 'GET') {
                    fetchUrl = url.split('?')[0] + '?' + params.toString();
                    loadUrl(fetchUrl, true);
                } else {
                    // For simplicity POST fallback to full navigate
                    fetch(url, { method: 'POST', body: params, credentials: 'same-origin' })
                        .then(()=> loadUrl(url, true))
                        .catch(()=> window.location.href = url);
                }
            });

            // Handle back/forward
            window.addEventListener('popstate', function (e) {
                const url = (e.state && e.state.url) || location.href;
                loadUrl(url, false);
            });

            // Reinitialize page after PJAX load (re-attach event listeners, etc.)
            window.reinitializePage = function () {
                // Re-attach add-to-cart click handler
                document.querySelectorAll('.add-to-cart-btn').forEach(button => {
                    // avoid double-binding: remove old listeners by cloning
                    const newBtn = button.cloneNode(true);
                    button.parentNode.replaceChild(newBtn, button);
                    newBtn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        const pid = newBtn.dataset.productId;
                        // simple handler - you might integrate with your existing add-to-cart endpoint
                        const fd = new FormData();
                        fd.append('product_id', pid);
                        fd.append('quantity', 1);
                        try {
                            const r = await fetch('partials/add-to-cart.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                            const text = await r.text();
                            let data = null;
                            try { data = JSON.parse(text);} catch (err) { console.warn('add-to-cart: invalid json', text); }
                            if (data && data.success) {
                                showToast('Added', data.message || 'Item added to cart');
                                updateCartBadge();
                            } else {
                                showToast('Error', (data && data.message) ? data.message : 'Could not add to cart', true);
                            }
                        } catch (err) {
                            showToast('Error', 'Could not add to cart', true);
                        }
                    });
                });

                // Re-attach pjax links inside loaded content by adding pjax-link class where needed
                // (Most internal links were already given pjax-link in server templates above)
            };

            // initial call
            reinitializePage();
        })();

        // ---------------------------
        // AI Chat Widget (client)
        // - stores conversation in localStorage so the visual state is restored across full page reloads
        // - works via a server-side proxy (partials/ai-chat.php) that keeps your API key on the server
        // ---------------------------
        (function () {
            const widget = document.getElementById('aiChat');
            const toggleBtn = document.getElementById('aiToggle');
            const panel = document.getElementById('aiPanel');
            const closeBtn = document.getElementById('aiClose');
            const minimizeBtn = document.getElementById('aiMinimize');
            const messagesEl = document.getElementById('aiMessages');
            const inputEl = document.getElementById('aiInput');
            const sendBtn = document.getElementById('aiSend');

            const STORAGE_KEY = 'shoe_bot_conversation_v1';

            let conversation = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');

            function renderMessages() {
                messagesEl.innerHTML = '';
                conversation.forEach(m => {
                    const div = document.createElement('div');
                    div.className = 'ai-msg ' + (m.role === 'user' ? 'user' : 'assistant');
                    div.textContent = m.content;
                    messagesEl.appendChild(div);
                });
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }

            function saveConversation() {
                try {
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(conversation));
                } catch (e) {
                    console.warn('Could not save conversation', e);
                }
            }

            function addMessage(role, text) {
                conversation.push({ role, content: text, timestamp: Date.now() });
                // limit conversation length to keep payload small
                if (conversation.length > 40) conversation = conversation.slice(-40);
                saveConversation();
                renderMessages();
            }

            async function sendMessageToServer(userText) {
                const payload = { message: userText, conversation: conversation };
                try {
                    const res = await fetch('partials/ai-chat.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload),
                        credentials: 'same-origin'
                    });
                    const text = await res.text();
                    if (!text) throw new Error('Empty response');
                    let data = null;
                    try { data = JSON.parse(text); } catch(e) { console.warn('ai-chat: invalid json', text); throw e; }

                    // If HTTP returned a non-2xx status, treat as provider error
                    if (!res.ok) {
                        const errMsg = (data && data.error) ? data.error : (res.statusText || 'AI provider error');
                        // Surface provider error to UI via thrown Error with additional payload
                        const err = new Error(errMsg);
                        err.provider = data || null;
                        throw err;
                    }

                    // Successful response
                    if (data && data.success) {
                        // If server indicated offline fallback, show banner
                        if (data.offline) showOfflineBanner(true);
                        else showOfflineBanner(false);
                        return data.assistant ?? data.message ?? '';
                    } else {
                        const errMsg = data && data.error ? data.error : 'AI error';
                        const err = new Error(errMsg);
                        err.provider = data || null;
                        throw err;
                    }
                } catch (err) {
                    console.error('sendMessageToServer error', err);
                    // ensure banner shown when offline
                    if (err && err.message && /offline|quota|limit|429|exceed/i.test(err.message)) showOfflineBanner(true);
                    throw err;
                }
            }

            function showOfflineBanner(show) {
                const b = document.getElementById('aiOfflineBanner');
                if (!b) return;
                b.style.display = show ? 'block' : 'none';
            }

            toggleBtn.addEventListener('click', () => {
                const isMin = widget.classList.toggle('minimized');
                if (!isMin) {
                    panel.style.display = 'block';
                    toggleBtn.setAttribute('aria-expanded', 'true');
                } else {
                    panel.style.display = 'none';
                    toggleBtn.setAttribute('aria-expanded', 'false');
                }
            });

            minimizeBtn.addEventListener('click', () => {
                widget.classList.add('minimized');
                panel.style.display = 'none';
            });

            closeBtn.addEventListener('click', () => {
                widget.classList.add('minimized');
                panel.style.display = 'none';
            });

            sendBtn.addEventListener('click', async () => {
                const text = inputEl.value.trim();
                if (!text) return;
                addMessage('user', text);
                inputEl.value = '';
                addMessage('assistant', '...'); // placeholder while waiting for reply
                try {
                    const assistantText = await sendMessageToServer(text);
                    // replace last assistant placeholder
                    if (conversation.length && conversation[conversation.length - 1].content === '...') {
                        conversation[conversation.length - 1].content = assistantText;
                    } else {
                        addMessage('assistant', assistantText);
                    }
                    saveConversation();
                    renderMessages();
                } catch (err) {
                    // Show provider or network error inside the conversation and add a Retry helper
                    const errMsg = (err && err.message) ? err.message : 'Sorry, I could not reach the AI service right now.';
                    const display = `AI error: ${errMsg}`;
                    if (conversation.length && conversation[conversation.length - 1].content === '...') {
                        conversation[conversation.length - 1].content = display;
                    } else {
                        addMessage('assistant', display);
                    }
                    // append a retry hint element into the UI (not stored in conversation)
                    renderMessages();
                    // attach a small retry button under the messages
                    const retryId = 'ai-retry-btn';
                    // remove existing retry if any
                    const existing = document.getElementById(retryId);
                    if (existing) existing.remove();
                    const retry = document.createElement('button');
                    retry.id = retryId;
                    retry.textContent = 'Retry';
                    retry.className = 'ai-send-btn';
                    retry.style.margin = '8px';
                    retry.addEventListener('click', () => {
                        inputEl.value = text;
                        inputEl.focus();
                    });
                    messagesEl.appendChild(retry);
                    messagesEl.scrollTop = messagesEl.scrollHeight;
                    saveConversation();
                }
            });

            inputEl.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendBtn.click();
                }
            });

            // restore UI
            renderMessages();
            // if there are no messages, show a starter message
            if (conversation.length === 0) {
                conversation.push({ role: 'assistant', content: 'Hi! I am ShoeBot — ask me about products, sizes, shipping, or recommendations.'});
                saveConversation();
                renderMessages();
            }
        })();

        // Reusable toast and cart badge functions (kept from your original script)
        const cartIcon = document.getElementById('cart-icon');
        const toastContainer = document.getElementById('toast-container');

        function showToast(title, message, isError = false) {
            const toast = document.createElement('div');
            toast.className = `toast ${isError ? 'error' : ''}`;
            toast.innerHTML = `
                <span class="toast-icon">${isError ? '✕' : '✓'}</span>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" aria-label="Close notification">×</button>
            `;

            toastContainer.appendChild(toast);

            const closeBtn = toast.querySelector('.toast-close');
            closeBtn.addEventListener('click', () => {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 300);
            });

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.classList.add('removing');
                    setTimeout(() => toast.remove(), 300);
                }
            }, 4000);
        }

        async function updateCartBadge(count = null) {
            let badge = document.getElementById('cart-badge');
            // If count is not provided, fetch from server
            if (count === null) {
                try {
                    const res = await fetch('cart-count.php', { credentials: 'same-origin' });
                    const text = await res.text();
                    if (text) {
                        try { const data = JSON.parse(text); count = data.cart_count || 0; } catch (e) { console.warn('updateCartBadge: invalid JSON', text); count = 0; }
                    } else { count = 0; }
                } catch (e) {
                    count = 0;
                }
            }
            if (!badge && count > 0 && cartIcon) {
                badge = document.createElement('span');
                badge.id = 'cart-badge';
                badge.className = 'cart-badge';
                cartIcon.appendChild(badge);
            }
            if (badge) {
                badge.textContent = count;
                badge.classList.remove('updated');
                void badge.offsetWidth;
                badge.classList.add('updated');
            }
        }
    </script>
</body>
</html>