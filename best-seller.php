<?php
require_once 'db_connection.php';
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['customer_id']);
$customer_name = $_SESSION['customer_name'] ?? 'Customer';
$cart_count = 0;

if ($is_logged_in) {
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

// Fetch best selling products based on order_items count
$best_sellers_query = "
    SELECT 
        p.product_id,
        p.name,
        p.product_badge,
        b.brand_name,
        p.Category,
        COALESCE(pci.image_url, 'upload/product-image/placeholder.png') AS image_url,
        c.color_name AS primary_color,
        MIN(pv.price) AS min_price,
        MAX(pv.price) AS max_price,
        COUNT(DISTINCT oi.order_id) AS total_orders,
        SUM(oi.quantity) AS total_sold
    FROM product p
    LEFT JOIN brand b ON p.brand_id = b.brand_id
    LEFT JOIN product_color_image pci ON p.product_id = pci.product_id AND pci.sort_order = 1
    LEFT JOIN color c ON pci.color_id = c.color_id
    LEFT JOIN product_variant pv ON p.product_id = pv.product_id
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    GROUP BY p.product_id, p.name, p.product_badge, b.brand_name, p.Category, pci.image_url, c.color_name
    ORDER BY total_sold DESC, total_orders DESC, p.created_at DESC
    LIMIT 12
";

$best_sellers_result = $conn->query($best_sellers_query);

// Fetch trending products (most viewed/recent orders)
$trending_query = "
    SELECT 
        p.product_id,
        p.name,
        p.product_badge,
        b.brand_name,
        COALESCE(pci.image_url, 'upload/product-image/placeholder.png') AS image_url,
        c.color_name AS primary_color,
        MIN(pv.price) AS min_price,
        MAX(pv.price) AS max_price
    FROM product p
    LEFT JOIN brand b ON p.brand_id = b.brand_id
    LEFT JOIN product_color_image pci ON p.product_id = pci.product_id AND pci.sort_order = 1
    LEFT JOIN color c ON pci.color_id = c.color_id
    LEFT JOIN product_variant pv ON p.product_id = pv.product_id
    LEFT JOIN order_items oi ON p.product_id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.order_id AND o.order_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY p.product_id, p.name, p.product_badge, b.brand_name, pci.image_url, c.color_name
    ORDER BY COUNT(oi.id) DESC, p.created_at DESC
    LIMIT 4
";
$trending_result = $conn->query($trending_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Sellers - ShoeTakels</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/product.css">
    <link rel="stylesheet" href="asset/style/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        :root {
            --bs-primary: #1a1a1a;
            --bs-accent: #d4a574;
            --bs-accent-dark: #c49660;
            --bs-accent-light: rgba(212, 165, 116, 0.1);
            --bs-white: #ffffff;
            --bs-light: #f8f9fa;
            --bs-gray: #6b7280;
            --bs-border: #e5e7eb;
            --bs-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --bs-shadow-lg: 0 10px 40px rgba(0, 0, 0, 0.12);
            --bs-radius: 16px;
            --bs-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Hero Section */
        .bestseller-hero {
            background: linear-gradient(135deg, var(--bs-primary) 0%, #2d2d2d 100%);
            padding: 5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .bestseller-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .bestseller-hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .bestseller-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--bs-accent);
            color: var(--bs-white);
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .bestseller-hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--bs-white);
            margin-bottom: 1rem;
            line-height: 1.1;
        }

        .bestseller-hero h1 span {
            color: var(--bs-accent);
        }

        .bestseller-hero p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.7);
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2.5rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            display: block;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--bs-accent);
        }

        .stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Main Container */
        .bestseller-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 3rem 1.5rem 5rem;
        }

        /* Section Styles */
        .bestseller-section {
            margin-bottom: 4rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--bs-light);
        }

        .section-header-content h2 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--bs-primary);
            margin-bottom: 0.375rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-header-content h2::before {
            content: '';
            width: 4px;
            height: 32px;
            background: linear-gradient(180deg, var(--bs-accent) 0%, var(--bs-accent-dark) 100%);
            border-radius: 2px;
        }

        .section-header-content p {
            color: var(--bs-gray);
            margin-left: calc(4px + 0.75rem);
        }

        .view-all-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--bs-accent);
            font-weight: 600;
            text-decoration: none;
            transition: var(--bs-transition);
        }

        .view-all-link:hover {
            color: var(--bs-accent-dark);
            gap: 0.75rem;
        }

        /* Trending Grid (4 columns) */
        .trending-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }

        /* Best Sellers Grid */
        .bestseller-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        /* Product Card Enhancements */
        .product-card {
            background: var(--bs-white);
            border: 1px solid var(--bs-border);
            border-radius: var(--bs-radius);
            overflow: hidden;
            transition: var(--bs-transition);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--bs-shadow-lg);
            border-color: rgba(212, 165, 116, 0.3);
        }

        .product-card .rank-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            width: 36px;
            height: 36px;
            background: var(--bs-accent);
            color: var(--bs-white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(212, 165, 116, 0.4);
        }

        .product-card .rank-badge.gold {
            background: linear-gradient(135deg, #ffd700 0%, #ffb800 100%);
            color: var(--bs-primary);
        }

        .product-card .rank-badge.silver {
            background: linear-gradient(135deg, #c0c0c0 0%, #a8a8a8 100%);
            color: var(--bs-primary);
        }

        .product-card .rank-badge.bronze {
            background: linear-gradient(135deg, #cd7f32 0%, #b87333 100%);
            color: var(--bs-white);
        }

        .product-card .sold-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: var(--bs-white);
            padding: 0.375rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .product-card .sold-badge i {
            color: var(--bs-accent);
        }

        .product-image {
            position: relative;
            height: 280px;
            background: var(--bs-light);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            transition: var(--bs-transition);
        }

        .product-card:hover .product-image img {
            transform: scale(1.08);
        }

        .product-info {
            padding: 1.25rem;
        }

        .product-brand {
            font-size: 0.8rem;
            color: var(--bs-gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.375rem;
        }

        .product-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--bs-primary);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .product-name a {
            color: inherit;
            text-decoration: none;
        }

        .product-name a:hover {
            color: var(--bs-accent);
        }

        .product-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--bs-primary);
            margin-bottom: 1rem;
        }

        .product-swatches {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .color-swatch {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid var(--bs-border);
            transition: var(--bs-transition);
        }

        .color-swatch:hover {
            transform: scale(1.15);
            border-color: var(--bs-accent);
        }

        .btn-primary {
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, var(--bs-accent) 0%, var(--bs-accent-dark) 100%);
            color: var(--bs-white);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--bs-transition);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(212, 165, 116, 0.4);
        }

        /* Categories Filter */
        .category-filters {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.625rem 1.25rem;
            background: var(--bs-white);
            border: 2px solid var(--bs-border);
            border-radius: 50px;
            font-weight: 500;
            color: var(--bs-gray);
            cursor: pointer;
            transition: var(--bs-transition);
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--bs-primary);
            border-color: var(--bs-primary);
            color: var(--bs-white);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bs-light);
            border-radius: var(--bs-radius);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--bs-gray);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--bs-primary);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--bs-gray);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .trending-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .bestseller-hero h1 {
                font-size: 2.5rem;
            }

            .hero-stats {
                gap: 2rem;
            }
        }

        @media (max-width: 768px) {
            .bestseller-hero {
                padding: 3rem 1.5rem;
            }

            .bestseller-hero h1 {
                font-size: 2rem;
            }

            .bestseller-hero p {
                font-size: 1rem;
            }

            .hero-stats {
                flex-wrap: wrap;
                gap: 1.5rem;
            }

            .stat-number {
                font-size: 2rem;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .trending-grid {
                grid-template-columns: 1fr;
            }

            .bestseller-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }

        @media (max-width: 480px) {
            .bestseller-hero h1 {
                font-size: 1.75rem;
            }

            .category-filters {
                justify-content: center;
            }

            .filter-btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="toast-container" id="toast-container"></div>
    
    <!-- Navigation -->
    <nav>
        <div class="logo">
            <a href="user-interface.php">ShoeTakels</a>
        </div>
        <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation" tabindex="0">☰</button>
        <ul class="nav-links" id="nav-links">
            <li><a href="12_12.php">12.12 Sale</a></li>
            <li><a href="user-interface.php">Home</a></li>
            <li><a href="best-seller.php" class="active">Best Seller</a></li>
            <li><a href="shoes.php">Shoes</a></li>
            <li class="brand-dropdown">
                <a href="brand.php" class="brand-link">Brand</a>
                <div class="brand-mega-menu">
                    <div class="brand-grid">
                        <?php
                            $brand_query = "SELECT brand_id, brand_name, brand_logo FROM brand ORDER BY brand_name ASC";
                            $brand_result = $conn->query($brand_query);
                            if ($brand_result && $brand_result->num_rows > 0) {
                                while ($brand = $brand_result->fetch_assoc()) {
                                    $brand_name = htmlspecialchars($brand['brand_name'], ENT_QUOTES, 'UTF-8');
                                    $brand_logo = htmlspecialchars($brand['brand_logo'], ENT_QUOTES, 'UTF-8');
                                    $brand_id = (int)$brand['brand_id'];
                                    if (empty($brand_logo)) $brand_logo = "upload/brand/default_logo.png";
                                    echo "<a href='brand.php?id={$brand_id}' class='brand-item'>
                                        <div class='brand-logo-box'><img src='{$brand_logo}' alt='{$brand_name} logo'></div>
                                        <h4 class='brand-item-name'>{$brand_name}</h4>
                                    </a>";
                                }
                            }
                        ?>
                    </div>
                </div>
            </li>
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
            <?php if ($is_logged_in): ?>
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
            <?php else: ?>
            <a href="login.php" class="user-icon" title="Sign In" aria-label="Sign in">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bestseller-hero">
        <div class="bestseller-hero-content">
            <div class="bestseller-badge">
                <i class="fa-solid fa-fire"></i>
                Customer Favorites
            </div>
            <h1>Our <span>Best Sellers</span></h1>
            <p>Discover the shoes everyone's talking about. These top-rated styles are loved by thousands of happy customers.</p>
            <div class="hero-stats">
                <div class="stat-item">
                    <span class="stat-number">10K+</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">5-Star Reviews</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">50+</span>
                    <span class="stat-label">Premium Brands</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="bestseller-container">
        
        <!-- Trending This Week -->
        <section class="bestseller-section">
            <div class="section-header">
                <div class="section-header-content">
                    <h2><i class="fa-solid fa-bolt" style="color: var(--bs-accent);"></i> Trending This Week</h2>
                    <p>Hot picks based on recent purchases</p>
                </div>
                <a href="shoes.php" class="view-all-link">
                    View All <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="trending-grid">
                <?php
                if ($trending_result && $trending_result->num_rows > 0) {
                    while ($product = $trending_result->fetch_assoc()) {
                        $pid = (int)$product['product_id'];
                        $name = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
                        $brand = htmlspecialchars($product['brand_name'] ?? '', ENT_QUOTES, 'UTF-8');
                        $img = htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8');
                        $primary_color = $product['primary_color'] ?? '';
                        $min_price = $product['min_price'] !== null ? number_format((float)$product['min_price'], 2) : 'N/A';
                        $max_price = $product['max_price'] !== null ? number_format((float)$product['max_price'], 2) : 'N/A';
                        $price_display = ($min_price === $max_price) ? "₱$min_price" : "₱$min_price - ₱$max_price";
                        
                        echo "<div class='product-card'>
                            <div class='product-image'>
                                <a href='product-detail.php?id={$pid}'>
                                    <img src='{$img}' alt='{$name}' loading='lazy'>
                                </a>
                            </div>
                            <div class='product-info'>
                                <div class='product-brand'>{$brand}</div>
                                <h3 class='product-name'><a href='product-detail.php?id={$pid}'>{$name}</a></h3>
                                <div class='product-price'>{$price_display}</div>
                                <button class='btn-primary add-to-cart-btn' data-product-id='{$pid}'>Add to Cart</button>
                            </div>
                        </div>";
                    }
                } else {
                    echo "<div class='empty-state' style='grid-column: 1/-1;'>
                        <i class='fa-solid fa-box-open'></i>
                        <h3>No trending products yet</h3>
                        <p>Check back soon for trending items!</p>
                    </div>";
                }
                ?>
            </div>
        </section>

        <!-- Top Best Sellers -->
        <section class="bestseller-section">
            <div class="section-header">
                <div class="section-header-content">
                    <h2><i class="fa-solid fa-crown" style="color: var(--bs-accent);"></i> Top Best Sellers</h2>
                    <p>Our most popular shoes based on customer purchases</p>
                </div>
            </div>

            <!-- Category Filters -->
            <div class="category-filters">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="Men">Men</button>
                <button class="filter-btn" data-filter="Women">Women</button>
                <button class="filter-btn" data-filter="Kids">Kids</button>
            </div>

            <div class="bestseller-grid" id="bestseller-grid">
                <?php
                if ($best_sellers_result && $best_sellers_result->num_rows > 0) {
                    $rank = 1;
                    while ($product = $best_sellers_result->fetch_assoc()) {
                        $pid = (int)$product['product_id'];
                        $name = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
                        $brand = htmlspecialchars($product['brand_name'] ?? '', ENT_QUOTES, 'UTF-8');
                        $category = htmlspecialchars($product['Category'] ?? '', ENT_QUOTES, 'UTF-8');
                        $img = htmlspecialchars($product['image_url'], ENT_QUOTES, 'UTF-8');
                        $primary_color = $product['primary_color'] ?? '';
                        $min_price = $product['min_price'] !== null ? number_format((float)$product['min_price'], 2) : 'N/A';
                        $max_price = $product['max_price'] !== null ? number_format((float)$product['max_price'], 2) : 'N/A';
                        $price_display = ($min_price === $max_price) ? "₱$min_price" : "₱$min_price - ₱$max_price";
                        $total_sold = (int)($product['total_sold'] ?? 0);
                        
                        // Rank badge class
                        $rank_class = '';
                        if ($rank === 1) $rank_class = 'gold';
                        elseif ($rank === 2) $rank_class = 'silver';
                        elseif ($rank === 3) $rank_class = 'bronze';

                        // Fetch color swatches
                        $color_sql = "SELECT DISTINCT c.color_name FROM product_variant v LEFT JOIN color c ON v.color_id = c.color_id WHERE v.product_id = $pid ORDER BY c.color_name ASC LIMIT 5";
                        $color_res = $conn->query($color_sql);
                        $swatches_html = '';
                        if ($color_res && $color_res->num_rows > 0) {
                            $swatches_html = '<div class="product-swatches">';
                            while ($c = $color_res->fetch_assoc()) {
                                $cn = htmlspecialchars($c['color_name'], ENT_QUOTES, 'UTF-8');
                                $swatches_html .= "<a href='product-detail.php?id={$pid}&color=" . urlencode($c['color_name']) . "' class='color-swatch' title='{$cn}' style='background-color: {$cn};'></a>";
                            }
                            $swatches_html .= '</div>';
                        }

                        echo "<div class='product-card' data-category='{$category}'>
                            <span class='rank-badge {$rank_class}'>#{$rank}</span>";
                        
                        if ($total_sold > 0) {
                            echo "<span class='sold-badge'><i class='fa-solid fa-fire'></i> {$total_sold} sold</span>";
                        }
                        
                        echo "<div class='product-image'>
                                <a href='product-detail.php?id={$pid}'>
                                    <img src='{$img}' alt='{$name}' loading='lazy'>
                                </a>
                            </div>
                            <div class='product-info'>
                                <div class='product-brand'>{$brand}</div>
                                <h3 class='product-name'><a href='product-detail.php?id={$pid}'>{$name}</a></h3>
                                {$swatches_html}
                                <div class='product-price'>{$price_display}</div>
                                <button class='btn-primary add-to-cart-btn' data-product-id='{$pid}'>Add to Cart</button>
                            </div>
                        </div>";
                        
                        $rank++;
                    }
                } else {
                    echo "<div class='empty-state' style='grid-column: 1/-1;'>
                        <i class='fa-solid fa-trophy'></i>
                        <h3>No best sellers yet</h3>
                        <p>Products will appear here once customers start purchasing!</p>
                    </div>";
                }
                ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-main">
                <div class="footer-brand">
                    <a href="user-interface.php" class="footer-logo">ShoeTakels</a>
                    <p class="footer-tagline">Step into style and comfort with our premium footwear collection.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
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
                            <li><a href="best-seller.php">Best Sellers</a></li>
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
                <p>&copy; 2025 ShoeTakels. All rights reserved.</p>
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

    <?php if ($is_logged_in): ?>
        <?php include __DIR__ . '/partials/chatbot.php'; ?>
    <?php endif; ?>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menu-toggle');
        const navLinks = document.getElementById('nav-links');
        if (menuToggle && navLinks) {
            menuToggle.addEventListener('click', () => navLinks.classList.toggle('show'));
        }

        // User menu toggle
        const userMenu = document.getElementById('user-menu');
        const userMenuToggle = userMenu?.querySelector('.user-menu-toggle');
        if (userMenuToggle) {
            userMenuToggle.addEventListener('click', (evt) => {
                userMenu.classList.toggle('active');
                evt.stopPropagation();
            });
            document.addEventListener('click', (e) => {
                if (!userMenu.contains(e.target)) userMenu.classList.remove('active');
            });
        }

        // Brand dropdown
        const brandDropdown = document.querySelector('.brand-dropdown');
        const brandMegaMenu = document.querySelector('.brand-mega-menu');
        if (brandDropdown && brandMegaMenu) {
            brandDropdown.addEventListener('mouseenter', () => brandMegaMenu.style.display = 'block');
            brandDropdown.addEventListener('mouseleave', () => brandMegaMenu.style.display = 'none');
        }

        // Toast notifications
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
            
            toast.querySelector('.toast-close').addEventListener('click', () => {
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

        // Update cart badge
        async function updateCartBadge() {
            try {
                const res = await fetch('cart-count.php');
                const data = await res.json();
                let badge = document.getElementById('cart-badge');
                const count = data.cart_count || 0;
                
                if (!badge && count > 0) {
                    badge = document.createElement('span');
                    badge.id = 'cart-badge';
                    badge.className = 'cart-badge';
                    document.getElementById('cart-icon').appendChild(badge);
                }
                
                if (badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'flex' : 'none';
                }
            } catch (e) {
                console.error('Failed to update cart badge', e);
            }
        }

        // Add to cart functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.preventDefault();
                const productId = button.dataset.productId;
                
                <?php if (!$is_logged_in): ?>
                window.location.href = 'login.php';
                return;
                <?php endif; ?>
                
                button.disabled = true;
                button.textContent = 'Adding...';
                
                try {
                    const response = await fetch('partials/add-to-cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `product_id=${productId}`
                    });
                    
                    const text = await response.text();
                    let data = null;
                    if (text) {
                        try { data = JSON.parse(text); } catch(e) { data = null; }
                    }
                    
                    if (data && data.success) {
                        showToast('Added to Cart', data.message || 'Item added successfully!');
                        updateCartBadge();
                    } else {
                        showToast('Error', data?.message || 'Failed to add item', true);
                    }
                } catch (error) {
                    showToast('Error', 'Failed to add item to cart', true);
                } finally {
                    button.disabled = false;
                    button.textContent = 'Add to Cart';
                }
            });
        });

        // Category filter functionality
        const filterBtns = document.querySelectorAll('.filter-btn');
        const productCards = document.querySelectorAll('#bestseller-grid .product-card');
        
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Update active state
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                const filter = btn.dataset.filter;
                
                productCards.forEach(card => {
                    if (filter === 'all' || card.dataset.category === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
