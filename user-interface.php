<?php
require_once 'db_connection.php';
require_once 'inc/store_settings.php';
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
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($store_settings['store_name']); ?> - Premium footwear for every occasion">
    <meta name="theme-color" content="#d4a574">
    <title><?php echo htmlspecialchars($store_settings['store_name']); ?> - Welcome</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="asset/style/product.css">
    <link rel="stylesheet" href="asset/style/carousel.css">
    <link rel="stylesheet" href="asset/style/beautiful-ui.css">
    <link rel="stylesheet" href="asset/style/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <script src="asset/script/script.js" defer></script>
    <?php echo getStoreThemeCSS(); ?>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
</head>
<body>
    
    <!-- Announcement Bar with Animation -->
    <?php if (!empty($store_settings['announcement_enabled']) && !empty($store_settings['announcement_text'])): ?>
    <div class="announcement-bar" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($store_settings['announcement_bg_color'] ?? '#1a1a1a'); ?>, <?php echo htmlspecialchars($store_settings['announcement_bg_color'] ?? '#333'); ?>); color: <?php echo htmlspecialchars($store_settings['announcement_text_color'] ?? '#fff'); ?>;">
        <div class="announcement-content">
            <i class="fas fa-bullhorn announcement-icon"></i>
            <p><?php echo htmlspecialchars($store_settings['announcement_text']); ?></p>
            <button class="announcement-close" aria-label="Close announcement"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <?php endif; ?>

    <div class="toast-container" id="toast-container"></div>
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
                            $brand_query = "SELECT brand_id, brand_name, brand_logo 
                                            FROM brand 
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
                                    <a href='brand.php?id={$brand_id}' class='brand-item'>
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
            <form class="nav-search search-modern" action="shoes.php" method="get" role="search" aria-label="Site search">
                <i class="fas fa-search search-icon"></i>
                <input type="search" name="q" placeholder="Search shoes, brands, categories..." aria-label="Search" />
            </form>
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
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
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
                    <a href="#" class="dropdown-item" id="wishlist-link" onclick="openWishlistModal(event)"><i class="fas fa-heart"></i> Wishlist</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item logout-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <section class="hero hero-modern">
        <div class="hero-floating-element" style="top: 10%; left: 5%;">👟</div>
        <div class="hero-floating-element" style="top: 20%; right: 10%;">⭐</div>
        <div class="hero-floating-element" style="bottom: 30%; left: 8%;">✨</div>
        <div class="hero-floating-element" style="bottom: 15%; right: 5%;">🏃</div>
        <div class="hero-content">
            <span class="hero-badge">
                <i class="fas fa-crown"></i> Premium Collection
            </span>
            <h1 class="hero-title-modern">Step Into Style & Comfort</h1>
            <p class="hero-subtitle-modern">Discover our exclusive collection of premium footwear designed for every occasion. From athletic performance to everyday elegance.</p>
            <div class="cta-buttons cta-buttons-modern">
                <a href="shoes.php" class="btn-primary btn-modern btn-glow">
                    <i class="fas fa-shopping-bag"></i> Shop Now
                </a>
                <a href="#new-arrivals" class="btn-secondary btn-modern">
                    <i class="fas fa-compass"></i> Explore
                </a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">Products</span>
                </div>
                <div class="hero-stat">
                    <span class="stat-number">50+</span>
                    <span class="stat-label">Brands</span>
                </div>
                <div class="hero-stat">
                    <span class="stat-number">10K+</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
            </div>
        </div>
        <div class="hero-image">
            <?php include __DIR__ . '/partials/carousel.php'; ?>
        </div>
    </section>
    
    <?php include __DIR__ . '/partials/hot-sales.php'; ?>
                    
    <section class="categories categories-modern">
        <div class="section-header-modern">
            <span class="section-badge"><i class="fas fa-th-large"></i> Categories</span>
            <h2 class="section-title section-title-modern">Shop By Category</h2>
            <p class="section-subtitle section-subtitle-modern">Find the perfect pair for every family member</p>
        </div>

        <div class="category-grid category-grid-modern">
            <a href="shoes.php?category=Men" class="category-card category-card-modern">
                <div class="category-image">
                    <img src="upload/category/men.jpg" alt="Men's Shoes">
                    <div class="category-overlay">
                        <span class="category-count">150+ Styles</span>
                    </div>
                </div>
                <div class="category-content">
                    <div class="category-title">Men</div>
                    <span class="category-explore">Explore <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>

            <a href="shoes.php?category=Women" class="category-card category-card-modern">
                <div class="category-image">
                    <img src="upload/category/women.jpg" alt="Women's Shoes">
                    <div class="category-overlay">
                        <span class="category-count">200+ Styles</span>
                    </div>
                </div>
                <div class="category-content">
                    <div class="category-title">Women</div>
                    <span class="category-explore">Explore <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>
            <a href="shoes.php?category=Kids" class="category-card category-card-modern">
                <div class="category-image">
                    <img src="upload/category/kid.webp" alt="Kids' Shoes">
                    <div class="category-overlay">
                        <span class="category-count">100+ Styles</span>
                    </div>
                </div>
                <div class="category-content">
                    <div class="category-title">Kids</div>
                    <span class="category-explore">Explore <i class="fas fa-arrow-right"></i></span>
                </div>
            </a>
        </div>
    </section>
    
    <section class="new-arrivals" id="new-arrivals">
        <div class="section-header-modern">
            <span class="section-badge"><i class="fas fa-bolt"></i> Just In</span>
            <h2 class="section-title section-title-modern">New Arrivals</h2>
            <p class="section-subtitle section-subtitle-modern">Fresh styles just dropped</p>
        </div>
        
        <div class="products-grid">
            <?php
                $query = "
                    SELECT 
                        s.product_id, 
                        s.name, 
                        b.brand_name, 
                        s.Category AS CategoryName,
                        (
                            SELECT pci2.image_url
                            FROM product_color_image pci2
                            WHERE pci2.product_id = s.product_id
                            ORDER BY pci2.sort_order ASC
                            LIMIT 1
                        ) AS image_url,
                        (SELECT MIN(v.price) FROM product_variant v WHERE v.product_id = s.product_id) AS min_price
                    FROM product s
                    LEFT JOIN brand b ON s.brand_id = b.brand_id
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
                        $price = $product['min_price'] !== null ? '₱' . number_format((float)$product['min_price'], 2) : '';
                        $priceJs = htmlspecialchars($price, ENT_QUOTES, 'UTF-8');
                        echo "
                        <div class='product-card'>
                            <div class='product-image-container'>
                                <span class='product-badge new-badge'>New</span>
                                <a href='product-detail.php?id={$pid}' title='View $name'>
                                    <img src='$img' alt='$name' class='product-image' loading='lazy'>
                                </a>
                                <div class='product-overlay'></div>
                                <div class='product-actions'>
                                    <a href='product-detail.php?id={$pid}' class='action-btn' title='Quick View'><i class='fas fa-eye'></i></a>
                                    <button class='action-btn wishlist-btn' title='Add to Wishlist' onclick=\"addToWishlist({$pid}, '{$name}', '{$brand}', '{$priceJs}', '{$img}')\"><i class='fas fa-heart'></i></button>
                                </div>
                            </div>
                            <div class='product-info'>
                                <div class='product-brand'>$brand</div>
                                <h3 class='product-name'><a href='product-detail.php?id={$pid}'>$name</a></h3>
                                <div class='product-price'>$price</div>
                                <button class='btn-primary add-to-cart-btn' data-product-id='$pid'><i class='fas fa-shopping-bag'></i> Add to Cart</button>
                            </div>
                        </div>
                        ";
                    }
                } else {
                    echo "<p class='no-products'>No new arrivals available</p>";
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
                                             FROM brand 
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
                        <a href='brand.php?id={$brand_id}' class='logo-carousel-item' title='{$brand_name}'>
                            <img src='{$brand_logo}' alt='{$brand_name}' loading='lazy'>
                        </a>
                        ";
                    }
                ?>
            </div>
        </div>
    </section>

    <section class="featured" id="shop">
        <div class="section-header-modern">
            <span class="section-badge"><i class="fas fa-star"></i> Featured</span>
            <h2 class="section-title section-title-modern">Featured Collection</h2>
            <p class="section-subtitle section-subtitle-modern">Handpicked styles for the season</p>
        </div>
        
        <div class="products-grid">
            <?php
                $query = "
                    SELECT 
                        s.product_id, 
                        s.name, 
                        b.brand_name, 
                        s.Category AS CategoryName, 
                        (
                            SELECT pci2.image_url
                            FROM product_color_image pci2
                            WHERE pci2.product_id = s.product_id
                            ORDER BY pci2.sort_order ASC
                            LIMIT 1
                        ) AS image_url,
                        (
                            SELECT c2.color_name
                            FROM product_color_image pci3
                            LEFT JOIN color c2 ON pci3.color_id = c2.color_id
                            WHERE pci3.product_id = s.product_id
                            ORDER BY pci3.sort_order ASC
                            LIMIT 1
                        ) AS primary_color,
                        (SELECT MIN(v.price) FROM product_variant v WHERE v.product_id = s.product_id) AS min_price,
                        (SELECT MAX(v.price) FROM product_variant v WHERE v.product_id = s.product_id) AS max_price
                    FROM product s
                    LEFT JOIN brand b ON s.brand_id = b.brand_id
                    ORDER BY RAND()
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
                        $min_price = $product['min_price'] !== null ? number_format((float)$product['min_price'], 2) : null;
                        $max_price = $product['max_price'] !== null ? number_format((float)$product['max_price'], 2) : null;
                        $price_display = '';
                        if ($min_price !== null) {
                            $price_display = ($min_price === $max_price) ? "₱$min_price" : "₱$min_price - ₱$max_price";
                        }

                        // Fetch all available colors for this product
                        $color_sql = "SELECT DISTINCT c.color_name FROM product_variant v LEFT JOIN color c ON v.color_id = c.color_id WHERE v.product_id = $pid AND c.color_name IS NOT NULL ORDER BY c.color_name ASC LIMIT 5";
                        $color_res = $conn->query($color_sql);
                        $color_swatches = '';
                        if ($color_res && $color_res->num_rows > 0) {
                            $color_swatches .= '<div class="product-swatches">';
                            while ($color_row = $color_res->fetch_assoc()) {
                                $color_name = $color_row['color_name'];
                                $color_param = urlencode($color_name);
                                $color_label = htmlspecialchars($color_name, ENT_QUOTES, 'UTF-8');
                                $color_style = "background-color: $color_label;";
                                $color_swatches .= "<a href='product-detail.php?id={$pid}&color=$color_param' class='color-swatch' title='$color_label' style='$color_style'></a>";
                            }
                            $color_swatches .= '</div>';
                        }

                        echo "
                        <div class='product-card'>
                            <span class='product-badge featured-badge'><i class='fas fa-star'></i></span>
                            <div class='product-image-container'>
                                <a href='product-detail.php?id={$pid}&color=" . urlencode($raw_primary_color) . "' title='View $name'>
                                    <img src='$img' alt='$name' class='product-image' loading='lazy'>
                                </a>
                                <div class='product-overlay'></div>
                                <div class='product-actions'>
                                    <a href='product-detail.php?id={$pid}&color=" . urlencode($raw_primary_color) . "' class='action-btn' title='Quick View'><i class='fas fa-eye'></i></a>
                                    <button class='action-btn' title='Add to Wishlist'><i class='fas fa-heart'></i></button>
                                </div>
                            </div>
                            <div class='product-info'>
                                <div class='product-brand'>$brand</div>
                                <h3 class='product-name'><a href='product-detail.php?id={$pid}&color=" . urlencode($raw_primary_color) . "'>$name</a></h3>
                                $color_swatches
                                <div class='product-price'>$price_display</div>
                                <button class='btn-primary add-to-cart-btn' data-product-id='$pid'><i class='fas fa-shopping-bag'></i> Add to Cart</button>
                            </div>
                        </div>
                        ";
                    }
                } else {
                    echo "<p class='no-products'>No featured products available</p>";
                }
            // End featured section PHP
            ?>
        </div>
    </section>

    <section class="benefits benefits-modern">
        <div class="benefit-item benefit-item-modern">
            <div class="benefit-icon">
                <i class="fas fa-shipping-fast"></i>
            </div>
            <h3>Free Shipping</h3>
            <p>On orders over ₱50. Fast and reliable delivery to your doorstep.</p>
        </div>
        <div class="benefit-item benefit-item-modern">
            <div class="benefit-icon">
                <i class="fas fa-undo-alt"></i>
            </div>
            <h3>Easy Returns</h3>
            <p>30-day hassle-free returns if you're not completely satisfied.</p>
        </div>
        <div class="benefit-item benefit-item-modern">
            <div class="benefit-icon">
                <i class="fas fa-certificate"></i>
            </div>
            <h3>Quality Guaranteed</h3>
            <p>100% authentic premium footwear with lifetime warranty on defects.</p>
        </div>
        <div class="benefit-item benefit-item-modern">
            <div class="benefit-icon">
                <i class="fas fa-headset"></i>
            </div>
            <h3>Expert Support</h3>
            <p>24/7 customer service ready to help with sizing and style advice.</p>
        </div>
    </section>

    <section class="newsletter newsletter-modern">
        <div class="newsletter-decoration">
            <span class="newsletter-float">📧</span>
            <span class="newsletter-float">🎁</span>
            <span class="newsletter-float">✨</span>
        </div>
        <div class="newsletter-content">
            <span class="newsletter-badge"><i class="fas fa-envelope-open-text"></i> Newsletter</span>
            <h2>Stay Updated on New Releases</h2>
            <p>Subscribe to get exclusive offers and first access to new collections</p>
            <form class="newsletter-form newsletter-form-modern" onsubmit="return handleSubscribe(event)">
                <div class="newsletter-input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" placeholder="Enter your email address" required>
                </div>
                <button type="submit" class="btn-primary btn-modern btn-glow">
                    <i class="fas fa-paper-plane"></i> Subscribe
                </button>
            </form>
            <p class="newsletter-privacy"><i class="fas fa-lock"></i> We respect your privacy. Unsubscribe anytime.</p>
        </div>
    </section>
    
    <footer class="footer-modern">
        <div class="footer-decoration">
            <div class="footer-shape footer-shape-1"></div>
            <div class="footer-shape footer-shape-2"></div>
        </div>
        <div class="footer-container">
            <div class="footer-main">
                <div class="footer-brand">
                    <a href="user-interface.php" class="footer-logo footer-logo-modern">
                        <span class="gradient-text-accent"><?php echo htmlspecialchars($store_settings['store_name']); ?></span>
                        <span class="logo-sparkle">✨</span>
                    </a>
                    <p class="footer-tagline">Step into style and comfort with our premium footwear collection.</p>
                    <div class="footer-social footer-social-modern">
                        <?php if (!empty($store_settings['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_facebook']); ?>" aria-label="Facebook" target="_blank" class="social-link-modern">
                            <i class="fab fa-facebook-f"></i>
                            <span class="social-tooltip">Facebook</span>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($store_settings['social_instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_instagram']); ?>" aria-label="Instagram" target="_blank" class="social-link-modern">
                            <i class="fab fa-instagram"></i>
                            <span class="social-tooltip">Instagram</span>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($store_settings['social_twitter'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_twitter']); ?>" aria-label="Twitter" target="_blank" class="social-link-modern">
                            <i class="fab fa-twitter"></i>
                            <span class="social-tooltip">Twitter</span>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($store_settings['social_youtube'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_youtube']); ?>" aria-label="YouTube" target="_blank" class="social-link-modern">
                            <i class="fab fa-youtube"></i>
                            <span class="social-tooltip">YouTube</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="footer-links">
                    <div class="footer-column">
                        <h4>Shop</h4>
                        <ul>
                            <li><a href="shoes.php">All Shoes</a></li>
                            <li><a href="shoes.php?category=Men">Men's Shoes</a></li>
                            <li><a href="shoes.php?category=Women">Women's Shoes</a></li>
                            <li><a href="shoes.php?category=Kids">Kids' Shoes</a></li>
                            <li><a href="index.php">New Arrivals</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Support</h4>
                        <ul>
                            <li><a href="#">Contact Us</a></li>
                            <li><a href="#">FAQs</a></li>
                            <li><a href="#">Shipping Info</a></li>
                            <li><a href="#">Returns & Exchanges</a></li>
                            <li><a href="#">Size Guide</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Company</h4>
                        <ul>
                            <li><a href="#">About Us</a></li>
                            <li><a href="#">Our Story</a></li>
                            <li><a href="#">Careers</a></li>
                            <li><a href="#">Press</a></li>
                            <li><a href="#">Sustainability</a></li>
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Legal</h4>
                        <ul>
                            <li><a href="#">Privacy Policy</a></li>
                            <li><a href="#">Terms of Service</a></li>
                            <li><a href="#">Cookie Policy</a></li>
                            <li><a href="#">Accessibility</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($store_settings['store_name']); ?>. All rights reserved.</p>
                <div class="footer-payments">
                    <span>We Accept:</span>
                    <i class="fab fa-cc-visa" title="Visa"></i>
                    <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                    <i class="fab fa-cc-paypal" title="PayPal"></i>
                    <i class="fas fa-money-bill-wave" title="GCash"></i>
                </div>
            </div>
        </div>
    </footer>
        <?php include __DIR__ . '/partials/chatbot.php'; ?>
    <script>
        // Responsive nav toggle for zoom/small screens
        function handleNavToggle() {
            const menuToggle = document.getElementById('menu-toggle');
            const navLinks = document.getElementById('nav-links');
            if (menuToggle && navLinks) {
                menuToggle.addEventListener('click', () => {
                    navLinks.classList.toggle('show');
                });
                // Also allow keyboard toggle for accessibility
                menuToggle.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        navLinks.classList.toggle('show');
                    }
                });
            }
        }
        handleNavToggle();
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
                    const res = await fetch('cart-count.php');
                    const text = await res.text();
                    if (text) {
                        try { const data = JSON.parse(text); count = data.cart_count || 0; } catch (e) { console.warn('updateCartBadge: invalid JSON', text); count = 0; }
                    } else { count = 0; }
                } catch (e) {
                    count = 0;
                }
            }
            if (!badge && count > 0) {
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

        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                const variantId = button.dataset.variantId;
                if (!variantId) return;
                button.classList.add('loading');
                button.disabled = true;

                // AJAX call to add-to-cart.php
                const formData = new FormData();
                formData.append('variant_id', variantId);
                formData.append('quantity', 1);

                fetch('partials/add-to-cart.php', {
                method: 'POST',
                body: formData
                })
                .then(response => response.text().then(function(text){ if (!text) return null; try { return JSON.parse(text); } catch(e){ console.warn('add-to-cart: invalid JSON', text); return null; } }))
                .then(data => {
                    if (data && data.success) {
                        showToast('Success', data.message, false);
                        updateCartBadge();
                    } else {
                        showToast('Error', (data && data.message) ? data.message : 'Could not add to cart', true);
                    }
                })
                .catch(error => {
                    showToast('Error', 'Could not add to cart', true);
                })
                .finally(() => {
                    button.classList.remove('loading');
                    button.disabled = false;
                });
            });
        });

        // ...existing code for carousel and other features...

        const userMenu = document.getElementById('user-menu');
        const userMenuToggle = userMenu?.querySelector('.user-menu-toggle');
        
        if (userMenuToggle) {
            userMenuToggle.addEventListener('click', (evt) => {
                userMenu.classList.toggle('active');
                evt.stopPropagation();
            });

            document.addEventListener('click', (e) => {
                if (!userMenu.contains(e.target)) {
                    userMenu.classList.remove('active');
                }
            });
        }


        const menuToggle = document.getElementById('menu-toggle');
        const navLinks = document.getElementById('nav-links');

        if (menuToggle && navLinks) {
            menuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('show');
            });
        }

        function handleSubscribe(event) {
            event.preventDefault();
            const emailInput = event.target.querySelector('input[type="email"]');
            const email = emailInput.value;

            showToast('Subscription Successful', `Thank you for subscribing, ${email}!`);
            emailInput.value = ''; 
            return false;
        }

        const brandDropdown = document.querySelector('.brand-dropdown');
        const brandMegaMenu = document.querySelector('.brand-mega-menu');

        if (brandDropdown && brandMegaMenu) {
            brandDropdown.addEventListener('mouseenter', () => {
                brandMegaMenu.style.display = 'block';
            });

            brandDropdown.addEventListener('mouseleave', () => {
                brandMegaMenu.style.display = 'none';
            });
        }

        const logoCarousel = document.getElementById('logoCarousel');
        if (logoCarousel) {
    
            const items = logoCarousel.querySelectorAll('.logo-carousel-item');
            items.forEach(item => {
                const clone = item.cloneNode(true);
                logoCarousel.appendChild(clone);
            });

            const wrapper = document.querySelector('.logo-carousel-wrapper');
            wrapper.addEventListener('mouseenter', () => {
                logoCarousel.style.animationPlayState = 'paused';
            });

            wrapper.addEventListener('mouseleave', () => {
                logoCarousel.style.animationPlayState = 'running';
            });
        }
    </script>

    <!-- Wishlist Modal -->
    <div class="wishlist-modal-overlay" id="wishlist-modal-overlay">
        <div class="wishlist-modal">
            <div class="wishlist-modal-header">
                <div class="wishlist-modal-title">
                    <i class="fas fa-heart"></i>
                    <h2>My Wishlist</h2>
                </div>
                <button class="wishlist-modal-close" onclick="closeWishlistModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="wishlist-modal-body" id="wishlist-modal-body">
                <div class="wishlist-empty">
                    <div class="wishlist-empty-icon">
                        <i class="fas fa-heart-broken"></i>
                    </div>
                    <h3>Your Wishlist is Empty</h3>
                    <p>Save items you love by clicking the heart icon on any product</p>
                    <a href="shoes.php" class="wishlist-shop-btn" onclick="closeWishlistModal()">
                        <i class="fas fa-shopping-bag"></i> Start Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Wishlist Modal Styles */
        .wishlist-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .wishlist-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .wishlist-modal {
            background: var(--store-card-bg, #ffffff);
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            transform: scale(0.9) translateY(20px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .wishlist-modal-overlay.active .wishlist-modal {
            transform: scale(1) translateY(0);
        }

        .wishlist-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.5rem;
            background: var(--store-primary, #6366f1);
            color: #ffffff;
        }

        .wishlist-modal-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .wishlist-modal-title i {
            font-size: 1.5rem;
        }

        .wishlist-modal-title h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .wishlist-modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: #ffffff;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wishlist-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .wishlist-modal-body {
            padding: 2rem;
            max-height: 60vh;
            overflow-y: auto;
        }

        /* Empty Wishlist State */
        .wishlist-empty {
            text-align: center;
            padding: 2rem 0;
        }

        .wishlist-empty-icon {
            width: 100px;
            height: 100px;
            background: var(--store-primary-light, rgba(99, 102, 241, 0.1));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .wishlist-empty-icon i {
            font-size: 2.5rem;
            color: var(--store-primary, #6366f1);
        }

        .wishlist-empty h3 {
            color: var(--store-text, #1f2937);
            margin: 0 0 0.5rem;
            font-size: 1.25rem;
        }

        .wishlist-empty p {
            color: var(--store-text-secondary, #6b7280);
            margin: 0 0 1.5rem;
            font-size: 0.95rem;
        }

        .wishlist-shop-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            background: var(--store-gradient, linear-gradient(135deg, #6366f1, #8b5cf6));
            color: #ffffff;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .wishlist-shop-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }

        /* Wishlist Items */
        .wishlist-items {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .wishlist-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: var(--store-bg-secondary, #f8fafc);
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .wishlist-item:hover {
            background: var(--store-bg, #f1f5f9);
        }

        .wishlist-item-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .wishlist-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .wishlist-item-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .wishlist-item-name {
            font-weight: 600;
            color: var(--store-text, #1f2937);
            margin: 0 0 0.25rem;
            font-size: 0.95rem;
        }

        .wishlist-item-brand {
            font-size: 0.8rem;
            color: var(--store-text-secondary, #6b7280);
            margin: 0 0 0.5rem;
        }

        .wishlist-item-price {
            font-weight: 700;
            color: var(--store-primary, #6366f1);
            font-size: 1rem;
        }

        .wishlist-item-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            justify-content: center;
        }

        .wishlist-add-cart-btn {
            padding: 0.5rem 1rem;
            background: var(--store-primary, #6366f1);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .wishlist-add-cart-btn:hover {
            background: var(--store-secondary, #4f46e5);
        }

        .wishlist-remove-btn {
            padding: 0.5rem 1rem;
            background: transparent;
            color: #ef4444;
            border: 1px solid #ef4444;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .wishlist-remove-btn:hover {
            background: #ef4444;
            color: #ffffff;
        }

        @media (max-width: 480px) {
            .wishlist-modal {
                width: 95%;
                margin: 1rem;
            }

            .wishlist-modal-body {
                padding: 1.25rem;
            }

            .wishlist-item {
                flex-direction: column;
                text-align: center;
            }

            .wishlist-item-image {
                width: 100%;
                height: 150px;
            }

            .wishlist-item-actions {
                flex-direction: row;
                justify-content: center;
            }
        }
    </style>

    <script>
        // Wishlist Modal Functions
        function openWishlistModal(event) {
            if (event) event.preventDefault();
            const modal = document.getElementById('wishlist-modal-overlay');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Close user menu if open
            const userMenu = document.getElementById('user-menu');
            if (userMenu) userMenu.classList.remove('active');
            
            loadWishlistItems();
        }

        function closeWishlistModal() {
            const modal = document.getElementById('wishlist-modal-overlay');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside
        document.getElementById('wishlist-modal-overlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeWishlistModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeWishlistModal();
            }
        });

        // Load wishlist items from localStorage
        function loadWishlistItems() {
            const wishlistBody = document.getElementById('wishlist-modal-body');
            const wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');

            if (wishlist.length === 0) {
                wishlistBody.innerHTML = `
                    <div class="wishlist-empty">
                        <div class="wishlist-empty-icon">
                            <i class="fas fa-heart-broken"></i>
                        </div>
                        <h3>Your Wishlist is Empty</h3>
                        <p>Save items you love by clicking the heart icon on any product</p>
                        <a href="shoes.php" class="wishlist-shop-btn" onclick="closeWishlistModal()">
                            <i class="fas fa-shopping-bag"></i> Start Shopping
                        </a>
                    </div>
                `;
                return;
            }

            let itemsHtml = '<div class="wishlist-items">';
            wishlist.forEach((item, index) => {
                itemsHtml += `
                    <div class="wishlist-item" data-index="${index}">
                        <div class="wishlist-item-image">
                            <img src="${item.image || 'upload/product-image/placeholder.png'}" alt="${item.name}">
                        </div>
                        <div class="wishlist-item-info">
                            <h4 class="wishlist-item-name">${item.name}</h4>
                            <p class="wishlist-item-brand">${item.brand || ''}</p>
                            <span class="wishlist-item-price">${item.price || ''}</span>
                        </div>
                        <div class="wishlist-item-actions">
                            <a href="product-detail.php?id=${item.id}" class="wishlist-add-cart-btn" onclick="closeWishlistModal()">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <button class="wishlist-remove-btn" onclick="removeFromWishlist(${index})">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                `;
            });
            itemsHtml += '</div>';
            wishlistBody.innerHTML = itemsHtml;
        }

        // Add to wishlist function
        function addToWishlist(productId, productName, productBrand, productPrice, productImage) {
            let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
            
            // Check if already in wishlist
            const exists = wishlist.some(item => item.id === productId);
            if (exists) {
                showToast('Info', 'This item is already in your wishlist', false);
                return;
            }

            wishlist.push({
                id: productId,
                name: productName,
                brand: productBrand,
                price: productPrice,
                image: productImage
            });

            localStorage.setItem('wishlist', JSON.stringify(wishlist));
            showToast('Success', 'Added to wishlist!', false);
        }

        // Remove from wishlist
        function removeFromWishlist(index) {
            let wishlist = JSON.parse(localStorage.getItem('wishlist') || '[]');
            wishlist.splice(index, 1);
            localStorage.setItem('wishlist', JSON.stringify(wishlist));
            loadWishlistItems();
            showToast('Success', 'Removed from wishlist', false);
        }
    </script>

</body>
</html>