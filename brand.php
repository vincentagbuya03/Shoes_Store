<?php
    include 'db_connection.php';
    require_once 'inc/store_settings.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) session_start();
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

// Get selected brand ID if any
$selected_brand_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function getBrandProducts($conn, $brand_id) {
    $query = "
        SELECT
            p.product_id,
            p.name AS product_name,
            p.product_badge,
            MIN(pv.price) AS price,
            MIN(pv.stock) AS stock,
            MIN(pci.image_url) AS image_url
        FROM product p
        LEFT JOIN product_variant pv ON p.product_id = pv.product_id
        LEFT JOIN product_color_image pci ON p.product_id = pci.product_id AND pci.sort_order = 1
        WHERE p.brand_id = ?
        GROUP BY p.product_id, p.name, p.product_badge
        ORDER BY p.product_id
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $brand_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Fetch all brands
$brands_query = "SELECT * FROM brand ORDER BY brand_name ASC";
$brands_result = $conn->query($brands_query);

// Get selected brand info
$selected_brand = null;
if ($selected_brand_id > 0) {
    $brand_info_query = $conn->prepare("SELECT * FROM brand WHERE brand_id = ?");
    $brand_info_query->bind_param("i", $selected_brand_id);
    $brand_info_query->execute();
    $brand_info_result = $brand_info_query->get_result();
    $selected_brand = $brand_info_result->fetch_assoc();
    $brand_info_query->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $selected_brand ? htmlspecialchars($selected_brand['brand_name']) . ' | ' : 'All Brands | '; ?><?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="asset/style/beautiful-ui.css">
    <link rel="stylesheet" href="asset/style/brand-modern.css">
    <link rel="stylesheet" href="asset/style/animations.css">
    <?php echo getStoreThemeCSS(); ?>
</head>
<body>
    <!-- Announcement Bar -->
    <?php if (!empty($store_settings['announcement_enabled']) && !empty($store_settings['announcement_text'])): ?>
    <div class="announcement-bar" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($store_settings['announcement_bg_color'] ?? '#1a1a1a'); ?>, <?php echo htmlspecialchars($store_settings['announcement_bg_color'] ?? '#333'); ?>); color: <?php echo htmlspecialchars($store_settings['announcement_text_color'] ?? '#fff'); ?>;">
        <div class="announcement-content">
            <i class="fas fa-bullhorn announcement-icon"></i>
            <p><?php echo htmlspecialchars($store_settings['announcement_text']); ?></p>
            <button class="announcement-close" aria-label="Close announcement"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <?php endif; ?>
    
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
                <a href="brand.php" class="brand-link nav-link-enhanced active"><i class="fas fa-tags nav-icon"></i> Brand</a>
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
                <input type="search" name="q" placeholder="Search shoes, brands..." aria-label="Search" />
            </form>
            <a href="cart.php" class="cart-icon cart-icon-modern" id="cart-icon" title="Shopping Cart">
                <i class="fas fa-shopping-bag"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge cart-badge-modern" id="cart-badge"><?php echo $cart_count; ?></span>
                <?php else: ?>
                    <span class="cart-badge cart-badge-modern" id="cart-badge" style="display: none;">0</span>
                <?php endif; ?>
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
                    <a href="#" class="dropdown-item" id="wishlist-link"><i class="fas fa-heart"></i> Wishlist</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item logout-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <?php if ($selected_brand): ?>
    <section class="brand-hero">
        <div class="brand-hero-bg">
            <div class="brand-hero-pattern"></div>
        </div>
        <div class="brand-hero-content">
            <div class="brand-hero-logo">
                <img src="<?php echo htmlspecialchars($selected_brand['brand_logo'] ?: 'upload/brand/default_logo.png'); ?>" alt="<?php echo htmlspecialchars($selected_brand['brand_name']); ?>">
            </div>
            <h1 class="brand-hero-title"><?php echo htmlspecialchars($selected_brand['brand_name']); ?></h1>
            <p class="brand-hero-subtitle">Discover the latest collection from <?php echo htmlspecialchars($selected_brand['brand_name']); ?></p>
            <div class="brand-hero-stats">
                <?php
                $product_count_query = $conn->prepare("SELECT COUNT(*) as count FROM product WHERE brand_id = ?");
                $product_count_query->bind_param("i", $selected_brand_id);
                $product_count_query->execute();
                $product_count = $product_count_query->get_result()->fetch_assoc()['count'];
                $product_count_query->close();
                ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo $product_count; ?></span>
                    <span class="stat-label">Products</span>
                </div>
            </div>
            <a href="shoes.php?brand=<?php echo $selected_brand_id; ?>" class="brand-hero-btn">
                <i class="fas fa-shopping-bag"></i> Shop All <?php echo htmlspecialchars($selected_brand['brand_name']); ?>
            </a>
        </div>
    </section>
    <?php else: ?>
    <section class="brands-hero">
        <div class="brands-hero-bg">
            <div class="brands-hero-pattern"></div>
        </div>
        <div class="brands-hero-content">
            <span class="brands-hero-badge"><i class="fas fa-star"></i> Premium Brands</span>
            <h1 class="brands-hero-title">Explore Our Brands</h1>
            <p class="brands-hero-subtitle">Discover authentic footwear from the world's most iconic brands</p>
        </div>
    </section>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="brand-container">
        <?php if (!$selected_brand): ?>
        <!-- All Brands Grid -->
        <section class="brands-showcase">
            <div class="section-header-modern">
                <div class="section-title-wrapper">
                    <h2 class="section-title-modern"><i class="fas fa-tags"></i> All Brands</h2>
                    <p class="section-subtitle-modern">Choose from our curated selection of premium shoe brands</p>
                </div>
            </div>
            <div class="brands-grid">
                <?php
                $all_brands = $conn->query("SELECT brand_id, brand_name, brand_logo FROM brand ORDER BY brand_name ASC");
                while ($b = $all_brands->fetch_assoc()):
                    $bid = (int)$b['brand_id'];
                    $bname = htmlspecialchars($b['brand_name']);
                    $blogo = !empty($b['brand_logo']) ? htmlspecialchars($b['brand_logo']) : 'upload/brand/default_logo.png';
                    
                    // Get product count for this brand
                    $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM product WHERE brand_id = ?");
                    $count_stmt->bind_param("i", $bid);
                    $count_stmt->execute();
                    $pcount = $count_stmt->get_result()->fetch_assoc()['count'];
                    $count_stmt->close();
                ?>
                <a href="brand.php?id=<?php echo $bid; ?>" class="brand-card">
                    <div class="brand-card-glow"></div>
                    <div class="brand-card-inner">
                        <div class="brand-card-logo">
                            <img src="<?php echo $blogo; ?>" alt="<?php echo $bname; ?>" loading="lazy">
                        </div>
                        <div class="brand-card-info">
                            <h3 class="brand-card-name"><?php echo $bname; ?></h3>
                            <span class="brand-card-count"><?php echo $pcount; ?> Products</span>
                        </div>
                        <div class="brand-card-action">
                            <span class="explore-btn">Explore <i class="fas fa-arrow-right"></i></span>
                        </div>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($selected_brand): ?>
        <!-- Selected Brand Products -->
        <section class="brand-products-section">
            <div class="section-header-modern">
                <div class="section-title-wrapper">
                    <h2 class="section-title-modern"><i class="fas fa-shoe-prints"></i> <?php echo htmlspecialchars($selected_brand['brand_name']); ?> Collection</h2>
                    <p class="section-subtitle-modern">Browse all products from this brand</p>
                </div>
                <a href="shoes.php?brand=<?php echo $selected_brand_id; ?>" class="view-all-modern">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="products-grid-modern">
                <?php
                $products = getBrandProducts($conn, $selected_brand_id);
                if ($products->num_rows > 0):
                    while ($product = $products->fetch_assoc()):
                        $pid = $product['product_id'];
                        $pname = htmlspecialchars($product['product_name']);
                        $badge = htmlspecialchars($product['product_badge'] ?? '');
                        $price = $product['price'] ? number_format((float)$product['price'], 2) : 'N/A';
                        $image = $product['image_url'] ? htmlspecialchars($product['image_url']) : 'upload/product-image/placeholder.png';
                ?>
                <a href="product-detail.php?id=<?php echo $pid; ?>" class="product-card-modern">
                    <div class="product-image-wrapper">
                        <img src="<?php echo $image; ?>" alt="<?php echo $pname; ?>" loading="lazy">
                        <?php if ($badge): ?>
                        <span class="product-badge-modern <?php echo strpos(strtolower($badge), 'new') !== false ? 'badge-new' : 'badge-sale'; ?>">
                            <?php echo $badge; ?>
                        </span>
                        <?php endif; ?>
                        <div class="product-overlay">
                            <button class="quick-view-btn" data-id="<?php echo $pid; ?>">
                                <i class="fas fa-eye"></i> Quick View
                            </button>
                        </div>
                    </div>
                    <div class="product-info-modern">
                        <h3 class="product-name-modern"><?php echo $pname; ?></h3>
                        <div class="product-price-modern">₱<?php echo $price; ?></div>
                    </div>
                </a>
                <?php
                    endwhile;
                else:
                ?>
                <div class="empty-state-modern">
                    <i class="fas fa-box-open"></i>
                    <h3>No products available</h3>
                    <p>This brand currently has no products in stock.</p>
                    <a href="brand.php" class="back-btn">Browse All Brands</a>
                </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Back to All Brands -->
        <div class="back-to-brands">
            <a href="brand.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to All Brands
            </a>
        </div>
        <?php endif; ?>

        <?php if (!$selected_brand): ?>
        <!-- Featured Brands Carousel -->
        <?php
        $brands_result = $conn->query("SELECT * FROM brand ORDER BY brand_name ASC");
        if ($brands_result && $brands_result->num_rows > 0):
            while ($brand = $brands_result->fetch_assoc()):
                $brand_id = $brand['brand_id'];
                $brand_name = htmlspecialchars($brand['brand_name']);
                $brand_logo = htmlspecialchars($brand['brand_logo']);
                
                $products = getBrandProducts($conn, $brand_id);
                if ($products->num_rows === 0) continue;
        ?>
        <section class="brand-section-modern" id="brand-<?php echo $brand_id; ?>">
            <div class="section-header-modern">
                <div class="section-title-wrapper">
                    <div class="brand-section-logo">
                        <img src="<?php echo $brand_logo ?: 'upload/brand/default_logo.png'; ?>" alt="<?php echo $brand_name; ?>">
                    </div>
                    <div>
                        <h2 class="section-title-modern"><?php echo $brand_name; ?></h2>
                        <p class="section-subtitle-modern">Latest arrivals from <?php echo $brand_name; ?></p>
                    </div>
                </div>
                <a href="brand.php?id=<?php echo $brand_id; ?>" class="view-all-modern">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="products-carousel-modern">
                <div class="carousel-track" id="carousel-<?php echo $brand_id; ?>">
                    <?php while ($product = $products->fetch_assoc()):
                        $pid = $product['product_id'];
                        $pname = htmlspecialchars($product['product_name']);
                        $badge = htmlspecialchars($product['product_badge'] ?? '');
                        $price = $product['price'] ? number_format((float)$product['price'], 2) : 'N/A';
                        $image = $product['image_url'] ? htmlspecialchars($product['image_url']) : 'upload/product-image/placeholder.png';
                    ?>
                    <a href="product-detail.php?id=<?php echo $pid; ?>" class="carousel-product-card">
                        <div class="carousel-product-image">
                            <img src="<?php echo $image; ?>" alt="<?php echo $pname; ?>" loading="lazy">
                            <?php if ($badge): ?>
                            <span class="product-badge-modern <?php echo strpos(strtolower($badge), 'new') !== false ? 'badge-new' : 'badge-sale'; ?>">
                                <?php echo $badge; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="carousel-product-info">
                            <h3><?php echo $pname; ?></h3>
                            <span class="carousel-price">₱<?php echo $price; ?></span>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>
                <button class="carousel-btn-modern prev" data-target="carousel-<?php echo $brand_id; ?>">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="carousel-btn-modern next" data-target="carousel-<?php echo $brand_id; ?>">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </section>
        <?php
            endwhile;
        endif;
        ?>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer-modern">
        <div class="footer-wave">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 100" preserveAspectRatio="none">
                <path fill="currentColor" d="M0,50 C360,100 1080,0 1440,50 L1440,100 L0,100 Z"></path>
            </svg>
        </div>
        <div class="footer-content-modern">
            <div class="footer-grid">
                <div class="footer-section">
                    <h3 class="footer-title"><?php echo htmlspecialchars($store_settings['store_name']); ?></h3>
                    <p class="footer-desc">Your destination for premium footwear from the world's top brands.</p>
                    <div class="footer-social">
                        <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <a href="user-interface.php">Home</a>
                    <a href="shoes.php">Shop</a>
                    <a href="brand.php">Brands</a>
                    <a href="best-seller.php">Best Sellers</a>
                </div>
                <div class="footer-section">
                    <h4>Customer Care</h4>
                    <a href="#">Help Center</a>
                    <a href="#">Contact Us</a>
                    <a href="#">Returns</a>
                    <a href="#">Shipping Info</a>
                </div>
                <div class="footer-section">
                    <h4>My Account</h4>
                    <a href="user-profile.php">Profile</a>
                    <a href="orders.php">Orders</a>
                    <a href="cart.php">Cart</a>
                    <a href="#">Wishlist</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom-modern">
            <p>&copy; 2025 <?php echo htmlspecialchars($store_settings['store_name']); ?>. All rights reserved.</p>
        </div>
    </footer>
    
    <?php include __DIR__ . '/partials/chatbot.php'; ?>
    
    <script>
        // Mobile menu toggle
        document.getElementById('menu-toggle')?.addEventListener('click', function() {
            document.getElementById('nav-links')?.classList.toggle('show');
        });

        // User menu toggle
        (function() {
            const userMenu = document.getElementById('user-menu');
            const userMenuToggle = userMenu?.querySelector('.user-menu-toggle');
            if (!userMenu || !userMenuToggle) return;

            userMenuToggle.addEventListener('click', (evt) => {
                userMenu.classList.toggle('active');
                evt.stopPropagation();
            });

            document.addEventListener('click', (e) => {
                if (!userMenu.contains(e.target)) {
                    userMenu.classList.remove('active');
                }
            });
        })();

        // Carousel controls
        document.querySelectorAll('.carousel-btn-modern').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const track = document.getElementById(targetId);
                if (!track) return;

                const card = track.querySelector('.carousel-product-card');
                const cardWidth = card ? card.offsetWidth + 16 : 280;
                const scrollAmount = cardWidth * 2;

                if (btn.classList.contains('next')) {
                    track.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                } else {
                    track.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
                }
            });
        });

        // Announcement close button
        document.querySelector('.announcement-close')?.addEventListener('click', function() {
            this.closest('.announcement-bar')?.remove();
        });

        // Smooth scroll for brand section anchors
        document.querySelectorAll('a[href^="#brand-"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>