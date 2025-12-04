<?php
require_once 'db_connection.php';
session_start();

// Load store settings
require_once 'inc/store_settings.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($store_settings['customer_theme'] ?? 'default'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/index.css">
    <link rel="stylesheet" href="asset/style/product.css">
    <link rel="stylesheet" href="asset/style/carousel.css">
    <link rel="stylesheet" href="asset/style/brand-menu.css">
    <link rel="stylesheet" href="asset/style/beautiful-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <script src="asset/script/script.js"></script>
    <?php echo getStoreThemeCSS(); ?>
</head>
<body>
    <?php if ($store_settings['announcement_enabled'] && !empty($store_settings['announcement_bar'])): ?>
    <div class="announcement-bar announcement-modern">
        <div class="announcement-content">
            <i class="fas fa-bullhorn announcement-icon"></i>
            <p><?php echo htmlspecialchars($store_settings['announcement_bar']); ?></p>
            <i class="fas fa-gift announcement-icon"></i>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="toast-container" id="toast-container"></div>
    <nav class="nav-modern">
        <div class="logo">
            <a href="index.php" class="logo-modern">
                <span class="gradient-text-accent"><?php echo htmlspecialchars($store_settings['store_name']); ?></span>
                <span class="logo-sparkle">✨</span>
            </a>
        </div>
        
        <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation" tabindex="0">
            <i class="fas fa-bars"></i>
        </button>
        <ul class="nav-links" id="nav-links">
            <li><a href="12_12.php" class="nav-link-enhanced sale-link"><i class="fas fa-fire nav-icon"></i> 12.12 Sale</a></li>
            <li><a href="index.php" class="nav-link-enhanced"><i class="fas fa-home nav-icon"></i> Home</a></li>
            <li><a href="index.php" class="nav-link-enhanced"><i class="fas fa-star nav-icon"></i> Best Seller</a></li>
            <li><a href="shoes.php" class="nav-link-enhanced"><i class="fas fa-shoe-prints nav-icon"></i> Shoes</a></li>
            <?php if ($store_settings['show_brands_menu']): ?>
            <li class="brand-dropdown">
                <a href="brand.php" class="brand-link">Brand</a>
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
            <?php endif; ?>

        </ul>
        <div class="nav-right">
            <form class="nav-search search-modern" action="shoes.php" method="get" role="search" aria-label="Site search">
                <i class="fas fa-search search-icon"></i>
                <input type="search" name="q" placeholder="Search shoes, brands, categories..." aria-label="Search" />
            </form>            
            <div class="cart-icon cart-icon-modern" id="cart-icon" title="View Cart">
                <i class="fas fa-shopping-bag"></i>
                <span class="cart-badge cart-badge-modern" id="cart-badge" style="display: none;">0</span>
                <span class="cart-pulse"></span>
            </div>
            <a href="login.php" class="user-icon user-icon-modern" title="Sign In" aria-label="Sign in">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
                <span class="user-tooltip">Sign In</span>
            </a>
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
            <h1 class="hero-title-modern"><?php echo htmlspecialchars($store_settings['hero_title']); ?></h1>
            <p class="hero-subtitle-modern"><?php echo htmlspecialchars($store_settings['hero_subtitle']); ?></p>
            <div class="cta-buttons cta-buttons-modern">
                <a href="shoes.php" class="btn-primary btn-modern btn-glow">
                    <i class="fas fa-shopping-bag"></i> Shop Now
                </a>
                <a href="#shop" class="btn-secondary btn-modern">
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
                    (
                        SELECT pci2.image_url
                        FROM product_color_image pci2
                        WHERE pci2.product_id = s.product_id
                        ORDER BY pci2.sort_order ASC
                        LIMIT 1
                    ) AS image_url
                FROM Product s
                LEFT JOIN Brand b ON s.brand_id = b.brand_id
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
                    echo "
                    <div class='product-card'>
                        <div class='product-image'>
                            <a href='product-detail.php?id={$pid}' title='View $name'>
                                <img src='$img' alt='$name' class='product-thumb' loading='lazy'>
                            </a>
                        </div>
                        <h3 class='product-name'><a href='product.php?id={$pid}'>$name</a></h3>
                        <div class='product-brand'>$brand</div>
                        <button class='btn-primary add-to-cart-btn' onclick=\"window.location.href='login.php'\" style='width: 100%;'>Add to Cart</button>
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
                    (
                        SELECT MIN(v.price) FROM product_variant v WHERE v.product_id = s.product_id AND v.color_id = (
                            SELECT pci4.color_id FROM product_color_image pci4 WHERE pci4.product_id = s.product_id ORDER BY pci4.sort_order ASC LIMIT 1
                        )
                    ) AS min_price,
                    (
                        SELECT MAX(v.price) FROM product_variant v WHERE v.product_id = s.product_id AND v.color_id = (
                            SELECT pci5.color_id FROM product_color_image pci5 WHERE pci5.product_id = s.product_id ORDER BY pci5.sort_order ASC LIMIT 1
                        )
                    ) AS max_price
                FROM Product s
                LEFT JOIN Brand b ON s.brand_id = b.brand_id
                ORDER BY s.created_at ASC
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
                            $color_swatches .= "<a href='product-detail.php?id={$pid}&color=$color_param' class='color-swatch' title='$color_label' style='$color_style'></a> ";
                        }
                        $color_swatches .= '</div>';
                    }

                    echo "
                    <div class='product-card'>
                        <div class='product-image'>
                            <a href='product-detail.php?id={$pid}&color=" . urlencode($raw_primary_color) . "' title='View $name'>
                                <img src='$img' alt='$name' class='product-thumb' loading='lazy'>
                            </a>
                        </div>
                        <h3 class='product-name'><a href='product-detail.php?id={$pid}&color=" . urlencode($raw_primary_color) . "'>$name</a></h3>
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

    <?php if ($store_settings['show_newsletter']): ?>
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
    <?php endif; ?>
    
    <footer class="footer-modern">
        <div class="footer-decoration">
            <div class="footer-shape footer-shape-1"></div>
            <div class="footer-shape footer-shape-2"></div>
        </div>
        <div class="footer-container">
            <div class="footer-main">
                <div class="footer-brand">
                    <a href="index.php" class="footer-logo footer-logo-modern">
                        <span class="gradient-text-accent"><?php echo htmlspecialchars($store_settings['store_name']); ?></span>
                        <span class="logo-sparkle">✨</span>
                    </a>
                    <p class="footer-tagline"><?php echo htmlspecialchars($store_settings['footer_about'] ?: 'Step into style and comfort with our premium footwear collection.'); ?></p>
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
                        <?php if (!empty($store_settings['social_tiktok'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_tiktok']); ?>" aria-label="TikTok" target="_blank" class="social-link-modern">
                            <i class="fab fa-tiktok"></i>
                            <span class="social-tooltip">TikTok</span>
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
                            <li><a href="mailto:<?php echo htmlspecialchars($store_settings['store_email']); ?>">Contact Us</a></li>
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
                        <h4>Contact</h4>
                        <ul>
                            <li><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($store_settings['store_email']); ?></li>
                            <li><i class="fas fa-phone"></i> <?php echo htmlspecialchars($store_settings['store_phone']); ?></li>
                            <li><i class="fas fa-clock"></i> <?php echo htmlspecialchars($store_settings['contact_hours']); ?></li>
                            <li><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($store_settings['store_address']); ?></li>
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

    <script>
        function handleNavToggle() {
            const menuToggle = document.getElementById('menu-toggle');
            const navLinks = document.getElementById('nav-links');
            if (menuToggle && navLinks) {
                menuToggle.addEventListener('click', () => {
                    navLinks.classList.toggle('show');
                });
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
        function updateCartBadge(count) {
            let badge = document.getElementById('cart-badge');
            
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
                const productId = button.dataset.productId;
                const originalText = button.textContent;

                button.classList.add('loading');
                button.disabled = true;

                try {
                    const response = await fetch('partials/add-to-cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `product_id=${productId}`
                    });

                    const text = await response.text();
                    let data = null;
                    if (text) {
                        try { data = JSON.parse(text); } catch(e) { console.warn('add-to-cart index: invalid JSON', text); data = null; }
                    }

                    if (data && data.success) {
                        updateCartBadge(data.cart_count);
                        showToast(
                            'Added to Cart',
                            `${data.product.name} has been added (Total items: ${data.cart_count})`
                        );
                    } else {
                        showToast('Error', (data && data.message) ? data.message : 'Failed to add item', true);
                    }
                } catch (error) {
                    showToast('Error', 'Failed to add item to cart', true);
                    console.error('Cart error:', error);
                } finally {
                    button.classList.remove('loading');
                    button.disabled = false;
                }
            });
        });

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
</body>
</html>
