<?php
    include 'db_connection.php';
    require_once 'inc/store_settings.php';
    // Description helper (fallback-only)



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
        -- prefer the primary image (sort_order = 1) for each product
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
$brands_query = "SELECT * FROM brand ORDER BY brand_id";
$brands_result = $conn->query($brands_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brands | <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="stylesheet" href="asset/style/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="asset/style/brand.css">
    <?php echo getStoreThemeCSS(); ?>
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
        <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation" tabindex="0">☰</button>
        <ul class="nav-links" id="nav-links">
            <li><a href="12_12.php">12.12 Sale</a></li>
            <li><a href="user-interface.php">Home</a></li>
            <li><a href="index.php">Best Seller</a></li>
            <li><a href="shoes.php">Shoes</a></li>
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
        </ul>
        <div class="nav-right">
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
                    <a href="user-profile.php">My Profile</a>
                    <a href="orders.php">My Orders</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="container">
        <?php
            // Show all brands as a grid at the top
            $all_brands_sql = "SELECT brand_id, brand_name, brand_logo FROM brand ORDER BY brand_name ASC";
            $all_brands_res = $conn->query($all_brands_sql);
            if ($all_brands_res && $all_brands_res->num_rows > 0):
        ?>
        <section class="brand-list">
            <h2 class="section-title">All Brands</h2>
            <p class="section-subtitle">Browse all brands available in the store</p>
            <div class="brand-list-grid">
                <?php while ($b = $all_brands_res->fetch_assoc()):
                    $bid = (int)$b['brand_id'];
                    $bname = htmlspecialchars($b['brand_name']);
                    $blogo = !empty($b['brand_logo']) ? htmlspecialchars($b['brand_logo']) : 'upload/brand/default_logo.png';
                ?>
                <a href="brand.php?id=<?php echo $bid; ?>" class="brand-tile">
                    <div class="brand-logo-box"><img src="<?php echo $blogo; ?>" alt="<?php echo $bname; ?> logo"></div>
                    <div class="brand-item-name"><?php echo $bname; ?></div>
                    <div class="brand-overlay">
                        <div class="brand-overlay-text"><?php echo $bname; ?></div>
                        <button type="button" class="shop-now" data-brand="<?php echo $bid; ?>">Shop Now</button>
                    </div>
                </a>
                <?php endwhile; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php
        if ($brands_result && $brands_result->num_rows > 0) {
            $brand_counter = 0;
            
            while ($brand = $brands_result->fetch_assoc()) {
                $brand_id = $brand['brand_id'];
                $brand_name = htmlspecialchars($brand['brand_name']);
                $brand_logo = htmlspecialchars($brand['brand_logo']);
                
                $products = getBrandProducts($conn, $brand_id);
                
                if ($products->num_rows === 0) {
                    continue;
                }
                
                $brand_counter++;
        ?>
        
        <section class="brand-section" id="brand-<?php echo $brand_id; ?>">
                <div class="section-header">
                <div>
                    <h2 class="section-title"><?php echo $brand_name; ?></h2>
                    <p class="section-subtitle">Fresh New Arrivals from <?php echo $brand_name; ?></p>
                </div>
                <a href="shoes.php?brand=<?php echo $brand_id; ?>" class="view-all-btn">VIEW ALL</a>
            </div>

            <!-- Products Grid -->
            <div class="products-carousel-wrapper">
                <div id="products-<?php echo $brand_id; ?>" class="products-carousel">
                <?php
                // Use the brand logo as the image for the first product card when available
                $is_first_product = true;
                while ($product = $products->fetch_assoc()) {
                    $product_id = $product['product_id'];
                    $product_name = htmlspecialchars($product['product_name']);
                    $product_badge = htmlspecialchars($product['product_badge'] ?? '');
                    $price = $product['price'] ? number_format((float)$product['price'], 2) : 'N/A';

                    if ($is_first_product && !empty($brand_logo)) {
                        // First card: show full-brand image (do not skip the current product)
                        $image_url = $brand_logo;
                        ?>
                        <div class="product-card brand-first-card">
                            <a href="brand.php?id=<?php echo $brand_id; ?>" title="<?php echo $brand_name; ?>">
                                <div class="product-image-container">
                                    <img src="<?php echo htmlspecialchars($image_url); ?>" alt="<?php echo $brand_name; ?>" class="product-image" onerror="this.src='/placeholder.svg?height=380&width=560'">
                                        <div class="brand-overlay brand-overlay-hero">
                                            <div class="brand-overlay-text"><?php echo $brand_name; ?></div>
                                            <button type="button" class="shop-now" data-brand="<?php echo $brand_id; ?>">Shop Now</button>
                                        </div>
                                </div>
                            </a>
                        </div>
                        <?php
                        $is_first_product = false;
                    }

                    // Normal product card (use product image or fallback)
                    $image_url = $product['image_url'] ? htmlspecialchars($product['image_url']) : '/placeholder.svg?height=280&width=280';
                    ?>

                    <div class="product-card">
                        <div class="product-image-container">
                            <img src="<?php echo $image_url; ?>" alt="<?php echo $product_name; ?>" class="product-image" onerror="this.src='/placeholder.svg?height=280&width=280'">
                            
                            <?php if (!empty($product_badge)): ?>
                                <div class="badge <?php echo strpos(strtolower($product_badge), 'new') !== false ? 'badge-new' : 'badge-sale'; ?>">
                                    <?php 
                                    // Format badge text
                                    if (strpos(strtolower($product_badge), 'save') !== false || strpos(strtolower($product_badge), '%') !== false) {
                                        echo 'SAVE UP TO ' . $product_badge;
                                    } else {
                                        echo $product_badge;
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="product-info">
                            <h3 class="product-name"><?php echo $product_name; ?></h3>
                            <div class="product-price">
                                <span class="price-current">₱<?php echo $price; ?></span>
                            </div>
                        </div>
                    </div>

                <?php } ?>
                </div>
                <div class="carousel-controls-centered" role="group" aria-label="Brand carousel controls">
                    <button class="carousel-btn prev" data-target="products-<?php echo $brand_id; ?>" aria-label="Previous">‹</button>
                    <button class="carousel-btn next" data-target="products-<?php echo $brand_id; ?>" aria-label="Next">›</button>
                </div>
            </div>
        </section>

        <?php
            }
            
            if ($brand_counter === 0) {
        ?>
        <div class="empty-state">
            <h2>No brands found</h2>
            <p>Currently, there are no brands with products available.</p>
        </div>
        <?php } ?>

        <?php
        } else {
        ?>
        <div class="empty-state">
            <h2>Unable to load brands</h2>
            <p>Please try again later.</p>
        </div>
        <?php } ?>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About urbanAthletics</h3>
                <a href="#about">About Us</a>
                <a href="#careers">Careers</a>
                <a href="#press">Press</a>
                <a href="#blog">Blog</a>
            </div>
            <div class="footer-section">
                <h3>Shop</h3>
                <a href="#new-arrivals">New Arrivals</a>
                <a href="#bestsellers">Best Sellers</a>
                <a href="#sale">On Sale</a>
                <a href="#collections">Collections</a>
            </div>
            <div class="footer-section">
                <h3>Customer Care</h3>
                <a href="#help">Help Center</a>
                <a href="#contact">Contact Us</a>
                <a href="#returns">Returns</a>
                <a href="#shipping">Shipping Info</a>
            </div>
            <div class="footer-section">
                <h3>Connect</h3>
                <a href="#facebook">Facebook</a>
                <a href="#instagram">Instagram</a>
                <a href="#twitter">Twitter</a>
                <a href="#newsletter">Newsletter</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 urbanAthletics. All rights reserved.</p>
        </div>
    </footer>
            <?php include __DIR__ . '/partials/chatbot.php'; ?>
    <script>
        // Mobile menu toggle
        document.querySelectorAll('.icon-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                console.log('Icon clicked');
            });
        });

        // Per-brand carousel controls
        document.querySelectorAll('.carousel-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const targetId = btn.getAttribute('data-target');
                const wrapper = document.getElementById(targetId);
                if (!wrapper) return;

                const card = wrapper.querySelector('.product-card');
                const cardWidth = card ? card.getBoundingClientRect().width + parseFloat(getComputedStyle(wrapper).gap || 16) : wrapper.clientWidth * 0.9;

                if (btn.classList.contains('next')) {
                    wrapper.scrollBy({ left: cardWidth * 2, behavior: 'smooth' });
                } else {
                    wrapper.scrollBy({ left: -cardWidth * 2, behavior: 'smooth' });
                }
            });
        });

        // User menu toggle (make user icon dropdown interactive)
        (function() {
            const userMenu = document.getElementById('user-menu');
            const userMenuToggle = userMenu ? userMenu.querySelector('.user-menu-toggle') : null;
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

        // Shop Now buttons: navigate to filtered shoes page and stop propagation
        document.addEventListener('click', function(e) {
            var el = e.target;
            if (!el) return;
            if (el.classList && el.classList.contains('shop-now')) {
                e.preventDefault();
                e.stopPropagation();
                var bid = el.getAttribute('data-brand');
                if (bid) {
                    window.location.href = 'shoes.php?brand=' + encodeURIComponent(bid);
                }
            }
        });
    </script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>