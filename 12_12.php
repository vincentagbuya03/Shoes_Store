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

// Calculate countdown to December 12, 2025 end of day
$sale_end = strtotime('2025-12-12 23:59:59');
$now = time();
$time_remaining = max(0, $sale_end - $now);

// Fetch sale products (you can modify this query based on your sale criteria)
$sale_query = "
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
    ORDER BY RAND()
    LIMIT 12
";
$sale_result = $conn->query($sale_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>12.12 MEGA SALE - <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/index.css">
    <link rel="stylesheet" href="asset/style/12-12-sale.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <script src="asset/script/script.js"></script>
    <?php echo getStoreThemeCSS(); ?>
</head>
<body class="sale-page">
    <!-- Floating Sale Particles -->
    <div class="sale-particles" id="sale-particles"></div>

    <!-- Announcement Bar -->
    <div class="sale-announcement-bar">
        <div class="announcement-content">
            <span class="announcement-icon">🎉</span>
            <span class="announcement-text">12.12 MEGA SALE IS HERE! Up to 50% OFF on Selected Items!</span>
            <span class="announcement-icon">🎉</span>
        </div>
    </div>

    <div class="toast-container" id="toast-container"></div>
    
    <nav>
        <div class="logo">
            <a href="user-interface.php"><?php echo htmlspecialchars($store_settings['store_name']); ?></a>
        </div>
        <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation" tabindex="0">☰</button>
        <ul class="nav-links" id="nav-links">
            <li><a href="12_12.php" class="active-sale-link">12.12 Sale</a></li>
            <li><a href="user-interface.php">Home</a></li>
            <li><a href="best-seller.php">Best Seller</a></li>
            <li><a href="shoes.php">Shoes</a></li>
            <li class="brand-dropdown">
                <a href="brand.php" class="brand-link">Brand</a>
            </li>
        </ul>
        <div class="nav-right">
            <form class="nav-search" action="shoes.php" method="get" role="search" aria-label="Site search">
                <input type="search" name="q" placeholder="Search shoes, brands" aria-label="Search" />
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
                    <a href="user-profile.php">My Profile</a>
                    <a href="orders.php">My Orders</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section with 12.12 Theme -->
    <section class="sale-hero">
        <div class="hero-background">
            <div class="bg-circle circle-1"></div>
            <div class="bg-circle circle-2"></div>
            <div class="bg-circle circle-3"></div>
        </div>
        
        <div class="sale-hero-content">
            <div class="sale-badge-container">
                <span class="flash-badge">⚡ FLASH SALE ⚡</span>
            </div>
            
            <h1 class="sale-title">
                <span class="title-line">
                    <span class="number-animate">12</span>
                    <span class="dot-animate">.</span>
                    <span class="number-animate">12</span>
                </span>
                <span class="title-sub">MEGA SALE</span>
            </h1>
            
            <p class="sale-tagline">The Biggest Shopping Festival of the Year!</p>
            
            <div class="discount-showcase">
                <div class="discount-card">
                    <span class="discount-value">50%</span>
                    <span class="discount-label">OFF</span>
                </div>
                <div class="discount-divider">+</div>
                <div class="discount-card secondary">
                    <span class="discount-value">FREE</span>
                    <span class="discount-label">SHIPPING</span>
                </div>
            </div>

            <!-- Countdown Timer -->
            <div class="countdown-section">
                <h3 class="countdown-title">🔥 Sale Ends In:</h3>
                <div class="countdown-timer" id="countdown-timer" data-end="<?php echo $sale_end; ?>">
                    <div class="countdown-item">
                        <span class="countdown-value" id="days">00</span>
                        <span class="countdown-label">DAYS</span>
                    </div>
                    <div class="countdown-separator">:</div>
                    <div class="countdown-item">
                        <span class="countdown-value" id="hours">00</span>
                        <span class="countdown-label">HOURS</span>
                    </div>
                    <div class="countdown-separator">:</div>
                    <div class="countdown-item">
                        <span class="countdown-value" id="minutes">00</span>
                        <span class="countdown-label">MINS</span>
                    </div>
                    <div class="countdown-separator">:</div>
                    <div class="countdown-item">
                        <span class="countdown-value" id="seconds">00</span>
                        <span class="countdown-label">SECS</span>
                    </div>
                </div>
            </div>

            <a href="#sale-products" class="cta-sale-btn">
                <span>Shop Now</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="floating-elements">
            <div class="floating-shoe shoe-1">👟</div>
            <div class="floating-shoe shoe-2">👠</div>
            <div class="floating-shoe shoe-3">👢</div>
            <div class="floating-discount disc-1">-50%</div>
            <div class="floating-discount disc-2">-30%</div>
        </div>
    </section>

    <!-- Sale Categories Quick Nav -->
    <section class="sale-categories">
        <div class="category-pills">
            <a href="#all" class="pill active" data-filter="all">
                <i class="fa-solid fa-fire"></i> All Deals
            </a>
            <a href="#men" class="pill" data-filter="Men">
                <i class="fa-solid fa-person"></i> Men
            </a>
            <a href="#women" class="pill" data-filter="Women">
                <i class="fa-solid fa-person-dress"></i> Women
            </a>
            <a href="#kids" class="pill" data-filter="Kids">
                <i class="fa-solid fa-child"></i> Kids
            </a>
        </div>
    </section>

    <!-- Flash Deals Section -->
    <section class="flash-deals" id="sale-products">
        <div class="section-header-sale">
            <div class="header-left">
                <span class="flash-icon">⚡</span>
                <h2>Flash Deals</h2>
                <span class="flash-icon">⚡</span>
            </div>
            <div class="header-right">
                <span class="deals-count"><?php echo $sale_result ? $sale_result->num_rows : 0; ?> Amazing Deals</span>
            </div>
        </div>

        <div class="sale-products-grid">
            <?php
            if ($sale_result && $sale_result->num_rows > 0) {
                $delay = 0;
                while($product = $sale_result->fetch_assoc()) {
                    $img_path = !empty($product['image_url']) ? $product['image_url'] : 'upload/product-image/placeholder.png';
                    $img = htmlspecialchars($img_path, ENT_QUOTES, 'UTF-8');
                    $name = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
                    $brand = htmlspecialchars($product['brand_name'], ENT_QUOTES, 'UTF-8');
                    $pid = (int)$product['product_id'];
                    $category = htmlspecialchars($product['CategoryName'] ?? '', ENT_QUOTES, 'UTF-8');
                    $raw_primary_color = $product['primary_color'] ?? '';
                    $min_price = $product['min_price'] !== null ? (float)$product['min_price'] : 0;
                    
                    // Calculate sale price (12% discount for 12.12)
                    $discount_percent = rand(12, 50); // Random discount between 12% and 50%
                    $sale_price = $min_price * (1 - $discount_percent / 100);
                    
                    $original_display = '₱' . number_format($min_price, 2);
                    $sale_display = '₱' . number_format($sale_price, 2);
                    
                    // Random sold count for urgency
                    $sold_count = rand(50, 500);
                    $stock_left = rand(3, 20);

                    echo "
                    <div class='sale-product-card' data-category='$category' style='animation-delay: {$delay}ms'>
                        <div class='sale-badge-wrapper'>
                            <span class='sale-discount-badge'>-{$discount_percent}%</span>
                            <span class='flash-deal-badge'>⚡ Flash</span>
                        </div>
                        
                        <div class='product-image-wrapper'>
                            <a href='product-detail.php?id={$pid}&color=" . urlencode($raw_primary_color) . "' title='View $name'>
                                <img src='$img' alt='$name' class='sale-product-thumb' loading='lazy'>
                            </a>
                            <div class='quick-actions'>
                                <button class='quick-action-btn wishlist' title='Add to Wishlist'>
                                    <i class='fa-regular fa-heart'></i>
                                </button>
                                <button class='quick-action-btn quick-view' title='Quick View'>
                                    <i class='fa-regular fa-eye'></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class='sale-product-info'>
                            <span class='product-brand-tag'>$brand</span>
                            <h3 class='sale-product-name'>
                                <a href='product-detail.php?id={$pid}&color=" . urlencode($raw_primary_color) . "'>$name</a>
                            </h3>
                            
                            <div class='price-section'>
                                <span class='sale-price'>$sale_display</span>
                                <span class='original-price'>$original_display</span>
                            </div>
                            
                            <div class='urgency-section'>
                                <div class='stock-bar'>
                                    <div class='stock-fill' style='width: " . (100 - ($stock_left * 5)) . "%'></div>
                                </div>
                                <div class='urgency-text'>
                                    <span class='sold-text'>🔥 {$sold_count} sold</span>
                                    <span class='stock-text'>Only {$stock_left} left!</span>
                                </div>
                            </div>
                            
                            <button class='add-to-cart-sale-btn' data-product-id='$pid'>
                                <i class='fa-solid fa-cart-plus'></i>
                                <span>Add to Cart</span>
                            </button>
                        </div>
                    </div>
                    ";
                    $delay += 50;
                }
            } else {
                echo "<div class='no-deals'>
                    <i class='fa-solid fa-box-open'></i>
                    <p>No deals available at the moment. Check back soon!</p>
                </div>";
            }
            ?>
        </div>
    </section>

    <!-- Special Offers Banner -->
    <section class="special-offers-banner">
        <div class="offer-card offer-1">
            <div class="offer-content">
                <span class="offer-tag">EXTRA</span>
                <span class="offer-value">12%</span>
                <span class="offer-desc">OFF on Orders Above ₱5,000</span>
                <span class="offer-code">Code: MEGA1212</span>
            </div>
            <div class="offer-glow"></div>
        </div>
        
        <div class="offer-card offer-2">
            <div class="offer-content">
                <span class="offer-tag">FREE</span>
                <span class="offer-value">SHIPPING</span>
                <span class="offer-desc">On All Orders Today!</span>
                <span class="offer-code">No Code Needed</span>
            </div>
            <div class="offer-glow"></div>
        </div>
        
        <div class="offer-card offer-3">
            <div class="offer-content">
                <span class="offer-tag">BUY 2</span>
                <span class="offer-value">GET 1</span>
                <span class="offer-desc">Free on Selected Items</span>
                <span class="offer-code">Auto-Applied</span>
            </div>
            <div class="offer-glow"></div>
        </div>
    </section>

    <!-- Newsletter with Sale Theme -->
    <section class="sale-newsletter">
        <div class="newsletter-content">
            <div class="newsletter-icon">📧</div>
            <h2>Don't Miss Out on Future Sales!</h2>
            <p>Subscribe to get exclusive early access to our biggest deals</p>
            <form class="sale-newsletter-form" onsubmit="return handleSaleSubscribe(event)">
                <input type="email" placeholder="Enter your email address" required>
                <button type="submit">
                    <span>Subscribe</span>
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </section>

    <footer>
        <div class="footer-container">
            <div class="footer-main">
                <div class="footer-brand">
                    <a href="user-interface.php" class="footer-logo"><?php echo htmlspecialchars($store_settings['store_name']); ?></a>
                    <p class="footer-tagline">Step into style and comfort with our premium footwear collection.</p>
                    <div class="footer-social">
                        <?php if (!empty($store_settings['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_facebook']); ?>" aria-label="Facebook" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($store_settings['social_instagram'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_instagram']); ?>" aria-label="Instagram" target="_blank"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($store_settings['social_twitter'])): ?>
                        <a href="<?php echo htmlspecialchars($store_settings['social_twitter']); ?>" aria-label="Twitter" target="_blank"><i class="fab fa-twitter"></i></a>
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
                        </ul>
                    </div>
                    <div class="footer-column">
                        <h4>Support</h4>
                        <ul>
                            <li><a href="#">Contact Us</a></li>
                            <li><a href="#">FAQs</a></li>
                            <li><a href="#">Shipping Info</a></li>
                            <li><a href="#">Returns</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($store_settings['store_name']); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <?php include __DIR__ . '/partials/chatbot.php'; ?>

    <script>
        // Countdown Timer
        function initCountdown() {
            const timer = document.getElementById('countdown-timer');
            if (!timer) return;
            
            const endTime = parseInt(timer.dataset.end) * 1000;
            
            function updateTimer() {
                const now = Date.now();
                const remaining = Math.max(0, endTime - now);
                
                const days = Math.floor(remaining / (1000 * 60 * 60 * 24));
                const hours = Math.floor((remaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((remaining % (1000 * 60)) / 1000);
                
                document.getElementById('days').textContent = String(days).padStart(2, '0');
                document.getElementById('hours').textContent = String(hours).padStart(2, '0');
                document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
                document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
                
                if (remaining > 0) {
                    requestAnimationFrame(() => setTimeout(updateTimer, 1000));
                }
            }
            
            updateTimer();
        }
        
        // Sale Particles Animation
        function createParticles() {
            const container = document.getElementById('sale-particles');
            if (!container) return;
            
            const particles = ['✨', '🎉', '🔥', '⭐', '💫', '🎊'];
            
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('span');
                particle.className = 'particle';
                particle.textContent = particles[Math.floor(Math.random() * particles.length)];
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDuration = (Math.random() * 3 + 2) + 's';
                particle.style.animationDelay = Math.random() * 2 + 's';
                container.appendChild(particle);
            }
        }

        // Category Filter
        function initCategoryFilter() {
            const pills = document.querySelectorAll('.category-pills .pill');
            const cards = document.querySelectorAll('.sale-product-card');
            
            pills.forEach(pill => {
                pill.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    pills.forEach(p => p.classList.remove('active'));
                    pill.classList.add('active');
                    
                    const filter = pill.dataset.filter;
                    
                    cards.forEach(card => {
                        if (filter === 'all' || card.dataset.category === filter) {
                            card.style.display = 'block';
                            card.style.animation = 'fadeInUp 0.5s ease forwards';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        }

        // Toast Notifications
        function showToast(title, message, isError = false) {
            const toastContainer = document.getElementById('toast-container');
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

        // Add to Cart functionality
        document.querySelectorAll('.add-to-cart-sale-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                const productId = button.dataset.productId;
                if (!productId) return;
                
                button.disabled = true;
                button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Adding...';

                // Show success after brief delay (simulating add to cart)
                setTimeout(() => {
                    showToast('Added to Cart!', 'Item has been added to your cart');
                    button.disabled = false;
                    button.innerHTML = '<i class="fa-solid fa-cart-plus"></i><span>Add to Cart</span>';
                }, 800);
            });
        });

        // Newsletter Subscribe
        function handleSaleSubscribe(event) {
            event.preventDefault();
            const emailInput = event.target.querySelector('input[type="email"]');
            showToast('Subscribed!', 'You\'ll be first to know about our sales!');
            emailInput.value = '';
            return false;
        }

        // User Menu Toggle
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

        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menu-toggle');
        const navLinks = document.getElementById('nav-links');
        if (menuToggle && navLinks) {
            menuToggle.addEventListener('click', () => {
                navLinks.classList.toggle('show');
            });
        }

        // Initialize all
        document.addEventListener('DOMContentLoaded', () => {
            initCountdown();
            createParticles();
            initCategoryFilter();
        });
    </script>
</body>
</html>
