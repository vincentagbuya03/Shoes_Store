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

$selected_sizes = isset($_GET['sizes']) ? explode(',', $_GET['sizes']) : [];
$selected_colors = isset($_GET['colors']) ? explode(',', $_GET['colors']) : [];
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$selected_brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 999999;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'popular';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

$where_conditions = ['1=1'];
$params = [];
$param_types = '';

if (!empty($selected_sizes)) {
    $size_placeholders = implode(',', array_fill(0, count($selected_sizes), '?'));
    $where_conditions[] = "sz.size_name IN ($size_placeholders)";
    $params = array_merge($params, $selected_sizes);
    $param_types .= str_repeat('s', count($selected_sizes));
}

if (!empty($selected_colors)) {
    $color_placeholders = implode(',', array_fill(0, count($selected_colors), '?'));
    $where_conditions[] = "c.color_name IN ($color_placeholders)";
    $params = array_merge($params, $selected_colors);
    $param_types .= str_repeat('s', count($selected_colors));
}

// Category filter: support multiple possible Product columns safely
$category_columns = [];
foreach (['category','gender','audience'] as $col) {
    $res = $conn->query("SHOW COLUMNS FROM product LIKE '" . $conn->real_escape_string($col) . "'");
    if ($res && $res->num_rows > 0) $category_columns[] = 's.' . $col;
}
if ($selected_category !== '' && count($category_columns) > 0) {
    $placeholders = array_map(function($c){ return "$c = ?"; }, $category_columns);
    $where_conditions[] = '(' . implode(' OR ', $placeholders) . ')';
    // bind the same category value for each column
    for ($i=0;$i<count($category_columns);$i++) { $params[] = $selected_category; $param_types .= 's'; }
}

// Brand filter (by brand_id)
if ($selected_brand !== '') {
    // accept numeric brand id or fallback to name if non-numeric
    if (ctype_digit((string)$selected_brand)) {
        $where_conditions[] = 'b.brand_id = ?';
        $params[] = (int)$selected_brand;
        $param_types .= 'i';
    } else {
        $where_conditions[] = 'b.brand_name = ?';
        $params[] = $selected_brand;
        $param_types .= 's';
    }
}

if ($min_price > 0 || $max_price < 999999) {
    $where_conditions[] = "pv.price BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
    $param_types .= 'dd';
}

// Search query filter
if ($search_query !== '') {
    $where_conditions[] = "(s.name LIKE ? OR b.brand_name LIKE ? OR c.color_name LIKE ?)";
    $search_like = '%' . $search_query . '%';
    $params[] = $search_like;
    $params[] = $search_like;
    $params[] = $search_like;
    $param_types .= 'sss';
}

$where_sql = implode(' AND ', $where_conditions);
$order_sql = match($sort_by) {
    'price-low' => 'ORDER BY pv.price ASC',
    'price-high' => 'ORDER BY pv.price DESC',
    'newest' => 'ORDER BY s.created_at DESC',
    default => 'ORDER BY s.product_id DESC'
};

$query = "
    SELECT 
        s.product_id, 
        s.name, 
        b.brand_name, 
        pci.image_url,
        s.product_badge,
        c.color_name,
        pv.price,
        sz.size_name AS size
    FROM product s
    LEFT JOIN brand b ON s.brand_id = b.brand_id

    /* Get first image only */
    LEFT JOIN product_color_image pci 
        ON pci.product_id = s.product_id 
        AND pci.sort_order = 1

    LEFT JOIN color c 
        ON c.color_id = pci.color_id

    /* Get variant linked to the same color */
    LEFT JOIN product_variant pv 
        ON pv.product_id = s.product_id 
        AND pv.color_id = pci.color_id

    /* Join size names for filtering and display */
    LEFT JOIN size sz ON pv.size_id = sz.size_id

    WHERE $where_sql
    $order_sql
";


$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$products_result = $stmt->get_result();
// Helper to render products grid HTML (used for full page and AJAX responses)
function render_products_html($products_result) {
    $html = '';
    if ($products_result && $products_result->num_rows > 0) {
        while ($product = $products_result->fetch_assoc()) {
            $img_path = !empty($product['image_url']) ? $product['image_url'] : 'upload/product-image/placeholder.png';
            $img = htmlspecialchars($img_path, ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
            $brand = htmlspecialchars($product['brand_name'], ENT_QUOTES, 'UTF-8');
            $pid = (int)$product['product_id'];
            $color = htmlspecialchars($product['color_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $price = $product['price'] !== null ? '₱' . number_format((float)$product['price'], 2) : 'N/A';

            $db_badge = trim((string)($product['product_badge'] ?? ''));
            if ($db_badge !== '') {
                $badge = $db_badge;
            } else {
                $badges = ['Hot', '-10%'];
                $badge = $badges[array_rand($badges)];
            }

            $badge_class = '';
            if (strcasecmp($badge, 'Hot') === 0) {
                $badge_class = 'badge-hot';
            } elseif (strpos($badge, '%') !== false) {
                $badge_class = 'badge-sale';
            }

            $badge_html = (strcasecmp($badge, 'Hot') === 0)
                ? '<i class="fa-solid fa-fire" aria-hidden="true"></i> Hot'
                : htmlspecialchars($badge, ENT_QUOTES, 'UTF-8');

            $html .= "
            <article class='product-card' data-product-id='$pid' role='listitem'>
                <div class='product-image-wrapper'>
                    <span class='product-badge $badge_class'>{$badge_html}</span>
                    <a href='product-detail.php?id={$pid}&color=" . urlencode($color) . "' title='View $name'>
                        <img src='$img' alt='$name' loading='lazy'>
                    </a>
                </div>
                <div class='product-info'>
                    <p class='product-brand'>$brand</p>
                    <h3 class='product-name'>
                        <a href='product-detail.php?id={$pid}&color=" . urlencode($color) . "'>$name</a>
                    </h3>
                    <div class='product-colors'>
                        <span class='product-color-dot' style='background-color: #6b7280;' title='$color'></span>
                    </div>
                    <div class='product-price'>
                        <span class='price-current'>$price</span>
                    </div>
                    <a class='add-to-cart-btn' href='product-detail.php?id={$pid}'>
                        <span>View Details</span>
                        <svg width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'>
                            <path d='M5 12h14'/>
                            <path d='M12 5l7 7-7 7'/>
                        </svg>
                    </a>
                </div>
            </article>
            ";
        }
    } else {
        $html .= "
        <div class='no-products'>
            <div class='no-products-icon'>📦</div>
            <h3>No products found</h3>
            <p>Try adjusting your filters or search criteria</p>
        </div>
        ";
    }

    return $html;
}

// If this is an AJAX request, return only the products HTML fragment
if (isset($_GET['ajax']) && $_GET['ajax']) {
    echo render_products_html($products_result);
    exit();
}

// Get filter data for sidebar
$sizes_query = "SELECT DISTINCT sz.size_name AS size FROM product_variant pv LEFT JOIN size sz ON pv.size_id = sz.size_id WHERE sz.size_name IS NOT NULL ORDER BY CAST(sz.size_name AS DECIMAL(5,1)) ASC";
$sizes_result = $conn->query($sizes_query);
$available_sizes = [];
if ($sizes_result && $sizes_result->num_rows > 0) {
    while ($size_row = $sizes_result->fetch_assoc()) {
        $available_sizes[] = $size_row['size'];
    }
}

$brand_q = "SELECT brand_id, brand_name FROM brand ORDER BY brand_name ASC";
$brand_res = $conn->query($brand_q);
$brands = [];
if ($brand_res && $brand_res->num_rows > 0) {
    while ($b = $brand_res->fetch_assoc()) {
        $brands[] = $b;
    }
}

$colors_query = "SELECT DISTINCT color_name FROM color ORDER BY color_name ASC";
$colors_result = $conn->query($colors_query);
$colors = [];
if ($colors_result && $colors_result->num_rows > 0) {
    while ($color_row = $colors_result->fetch_assoc()) {
        $colors[] = $color_row['color_name'];
    }
}

$color_map = [
    'Black' => '#000000', 'White' => '#ffffff', 'Red' => '#ef4444',
    'Blue' => '#3b82f6', 'Green' => '#10b981', 'Yellow' => '#fbbf24',
    'Gray' => '#9ca3af', 'Brown' => '#92400e', 'Nude' => '#dcc7b0',
    'Navy' => '#001f3f', 'Espresso' => '#6f4e37', 'Leopard' => '#a0826d',
    'Pink' => '#ec4899', 'Orange' => '#f97316', 'Purple' => '#8b5cf6'
];

$categories = ['Male', 'Female', 'Kids', 'Unisex'];

$price_ranges = [
    ['min' => 0, 'max' => 999, 'label' => 'Under ₱1,000'],
    ['min' => 1000, 'max' => 2499, 'label' => '₱1,000 - ₱2,499'],
    ['min' => 2500, 'max' => 4999, 'label' => '₱2,500 - ₱4,999'],
    ['min' => 5000, 'max' => 9999, 'label' => '₱5,000 - ₱9,999'],
    ['min' => 10000, 'max' => 999999, 'label' => '₱10,000+']
];

// Size unit options
$unitOptions = ['EU','UK','US'];
$defaultUnit = 'EU';
$unitColumn = false;
$colCheck = $conn->query("SHOW COLUMNS FROM size LIKE 'unit'");
if ($colCheck && $colCheck->num_rows > 0) {
    $unitColumn = 'unit';
} else {
    $colCheck2 = $conn->query("SHOW COLUMNS FROM size LIKE 'size_unit'");
    if ($colCheck2 && $colCheck2->num_rows > 0) $unitColumn = 'size_unit';
}
if ($unitColumn) {
    $opts = [];
    $uq = $conn->query("SELECT DISTINCT " . $unitColumn . " AS u FROM size WHERE " . $unitColumn . " IS NOT NULL ORDER BY u ASC");
    if ($uq && $uq->num_rows > 0) {
        while ($r = $uq->fetch_assoc()) { $opts[] = $r['u']; }
    }
    if (!empty($opts)) {
        $unitOptions = $opts;
        if (in_array('EU', $unitOptions)) $defaultUnit = 'EU';
        else $defaultUnit = $unitOptions[0];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="default">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse our premium shoe collection - <?php echo htmlspecialchars($store_settings['store_name']); ?>">
    <title>Shoes Collection | <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="asset/style/beautiful-ui.css">
    <link rel="stylesheet" href="asset/style/shoes.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="asset/style/animations.css">
    <?php echo getStoreThemeCSS(); ?>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
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
            <li><a href="shoes.php" class="nav-link-enhanced active"><i class="fas fa-shoe-prints nav-icon"></i> Shoes</a></li>
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
            <div class="nav-search search-modern" role="search" aria-label="Product search">
                <i class="fas fa-search search-icon"></i>
                <input type="search" id="search-input" name="q" placeholder="Search shoes, brands..." aria-label="Search" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" />
                <button type="button" class="search-clear" id="search-clear" aria-label="Clear search" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
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

    <!-- ============================================
         HERO / PROMO SECTION
         ============================================ -->
    <section class="promo-section" aria-labelledby="promo-title">
        <div class="promo-banner">
            <div class="promo-bg-pattern"></div>
            <div class="promo-glow"></div>
            
            <div class="promo-content">
                <span class="promo-badge">Limited Time</span>
                <h2 class="promo-title" id="promo-title">🎄 Holiday Season Sale</h2>
                <p class="promo-description">Buy 2 Get 20% OFF • Buy 3 Get 25% OFF • Buy 4+ Get 30% OFF</p>
            </div>
            
            <div class="promo-action">
                <button class="promo-code-btn" onclick="copyPromoCode(this)" title="Click to copy code">
                    <span class="promo-code-label">Use Code:</span>
                    <span class="promo-code-value">HOLIDAY25</span>
                    <span class="promo-code-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                        </svg>
                    </span>
                </button>
            </div>
        </div>
    </section>

    <!-- ============================================
         MAIN CONTENT AREA
         ============================================ -->
    <main class="main-content" id="main-content">
        <div class="content-wrapper">
            
            <!-- ========== FILTER SIDEBAR ========== -->
            <aside class="filter-sidebar" id="filter-sidebar" role="complementary" aria-label="Product filters">
                <!-- Filter Header -->
                <header class="filter-header">
                    <div class="filter-header-content">
                        <svg class="filter-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                        </svg>
                        <h2 class="filter-title">Filters</h2>
                    </div>
                    <button class="filter-close" id="filter-close" aria-label="Close filters">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </header>

                <!-- Filter Content -->
                <div class="filter-content">
                    
                    <!-- SIZE FILTER -->
                    <div class="filter-section" data-filter="size">
                        <button class="filter-section-header" aria-expanded="true" data-toggle="size">
                            <span class="filter-section-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                </svg>
                                Size
                            </span>
                            <span class="filter-toggle-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </span>
                        </button>
                        <div class="filter-section-body" id="filter-size">
                            <div class="size-unit-selector">
                                <label for="size-unit-select">Unit:</label>
                                <select id="size-unit-select" data-default-unit="<?php echo $defaultUnit; ?>">
                                    <?php foreach ($unitOptions as $u): ?>
                                    <option value="<?php echo htmlspecialchars($u); ?>" <?php echo $u === $defaultUnit ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($u); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="size-options">
                                <?php foreach ($available_sizes as $size): 
                                    $size_html = htmlspecialchars($size, ENT_QUOTES, 'UTF-8');
                                    $is_active = in_array($size, $selected_sizes) ? 'active' : '';
                                ?>
                                <button class="size-option <?php echo $is_active; ?>" 
                                        data-size="<?php echo $size_html; ?>" 
                                        data-eu-size="<?php echo $size_html; ?>"
                                        onclick="toggleSize('<?php echo $size_html; ?>')">
                                    <?php echo $size_html; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- BRAND FILTER -->
                    <div class="filter-section" data-filter="brand">
                        <button class="filter-section-header" aria-expanded="true" data-toggle="brand">
                            <span class="filter-section-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                                </svg>
                                Brand
                            </span>
                            <span class="filter-toggle-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </span>
                        </button>
                        <div class="filter-section-body" id="filter-brand">
                            <div class="brand-options">
                                <?php foreach ($brands as $brand): 
                                    $bid = (int)$brand['brand_id'];
                                    $bname = htmlspecialchars($brand['brand_name'], ENT_QUOTES, 'UTF-8');
                                    $is_active = ($selected_brand !== '' && (string)$selected_brand === (string)$bid) ? 'active' : '';
                                ?>
                                <button class="brand-option <?php echo $is_active; ?>" 
                                        data-brand-id="<?php echo $bid; ?>"
                                        onclick="toggleBrand('<?php echo $bid; ?>')">
                                    <span class="brand-name"><?php echo $bname; ?></span>
                                    <span class="brand-check">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                    </span>
                                </button>
                                <?php endforeach; ?>
                                <?php if (empty($brands)): ?>
                                <p class="filter-empty">No brands available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- COLOR FILTER -->
                    <div class="filter-section" data-filter="color">
                        <button class="filter-section-header" aria-expanded="true" data-toggle="color">
                            <span class="filter-section-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <circle cx="12" cy="12" r="6"/>
                                    <circle cx="12" cy="12" r="2"/>
                                </svg>
                                Color
                            </span>
                            <span class="filter-toggle-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </span>
                        </button>
                        <div class="filter-section-body" id="filter-color">
                            <div class="color-options">
                                <?php foreach ($colors as $color): 
                                    $color_html = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
                                    $hex_color = $color_map[$color] ?? '#cccccc';
                                    $is_active = in_array($color, $selected_colors) ? 'active' : '';
                                ?>
                                <button class="color-option <?php echo $is_active; ?>" 
                                        data-color="<?php echo $color_html; ?>"
                                        onclick="toggleColor('<?php echo $color_html; ?>')"
                                        title="<?php echo $color_html; ?>">
                                    <span class="color-swatch" style="background-color: <?php echo $hex_color; ?>;">
                                        <svg class="color-check" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                    </span>
                                    <span class="color-name"><?php echo $color_html; ?></span>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- CATEGORY FILTER -->
                    <div class="filter-section" data-filter="category">
                        <button class="filter-section-header" aria-expanded="true" data-toggle="category">
                            <span class="filter-section-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                    <circle cx="9" cy="7" r="4"/>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                                Category
                            </span>
                            <span class="filter-toggle-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </span>
                        </button>
                        <div class="filter-section-body" id="filter-category">
                            <div class="category-options">
                                <?php foreach ($categories as $cat): 
                                    $cat_html = htmlspecialchars($cat, ENT_QUOTES, 'UTF-8');
                                    $is_active = ($selected_category === $cat) ? 'active' : '';
                                ?>
                                <button class="category-option <?php echo $is_active; ?>" 
                                        data-cat="<?php echo $cat_html; ?>"
                                        onclick="toggleCategory('<?php echo $cat_html; ?>')">
                                    <span class="category-icon-svg">
                                        <?php if ($cat === 'Male'): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="5" r="3"/>
                                            <path d="M12 8v8"/>
                                            <path d="M8 21l4-5 4 5"/>
                                        </svg>
                                        <?php elseif ($cat === 'Female'): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="5" r="3"/>
                                            <path d="M12 8v4"/>
                                            <path d="M8 12h8l-4 9-4-9z"/>
                                        </svg>
                                        <?php elseif ($cat === 'Kids'): ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="6" r="3"/>
                                            <path d="M12 9v5"/>
                                            <path d="M9 21l3-7 3 7"/>
                                            <path d="M7 14h10"/>
                                        </svg>
                                        <?php else: ?>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                                            <line x1="12" y1="6" x2="12" y2="12"/>
                                            <line x1="9" y1="9" x2="15" y2="9"/>
                                        </svg>
                                        <?php endif; ?>
                                    </span>
                                    <span class="category-name"><?php echo $cat_html; ?></span>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- PRICE FILTER -->
                    <div class="filter-section" data-filter="price">
                        <button class="filter-section-header" aria-expanded="true" data-toggle="price">
                            <span class="filter-section-title">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                                Price Range
                            </span>
                            <span class="filter-toggle-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </span>
                        </button>
                        <div class="filter-section-body" id="filter-price">
                            <div class="price-options">
                                <?php foreach ($price_ranges as $range): ?>
                                <button class="price-option" 
                                        data-min="<?php echo $range['min']; ?>" 
                                        data-max="<?php echo $range['max']; ?>"
                                        onclick="togglePriceOption(this)">
                                    <span class="price-radio"></span>
                                    <span class="price-label"><?php echo $range['label']; ?></span>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Filter Footer -->
                <footer class="filter-footer">
                    <button class="clear-filters-btn" onclick="clearFilters()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="1 4 1 10 7 10"/>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                        </svg>
                        <span>Clear All Filters</span>
                    </button>
                </footer>
            </aside>

            <!-- ========== PRODUCTS SECTION ========== -->
            <section class="products-section" aria-labelledby="collection-title">
                
                <!-- Products Header -->
                <header class="products-header">
                    <div class="products-header-left">
                        <h1 class="collection-title" id="collection-title">
                            <span class="title-accent"></span>
                            Collection
                        </h1>
                        <p class="products-count" id="products-count">
                            <!-- Product count will be updated via JS -->
                        </p>
                    </div>
                    
                    <div class="products-header-right">
                        <!-- Mobile Filter Toggle -->
                        <button class="filter-toggle-btn" id="filter-toggle-btn" aria-label="Open filters">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                            </svg>
                            <span>Filters</span>
                        </button>
                        
                        <!-- Sort Dropdown -->
                        <div class="sort-wrapper">
                            <label for="sort-select" class="sort-label">Sort by:</label>
                            <div class="sort-select-wrapper">
                                <select id="sort-select" class="sort-select" onchange="updateSort(this.value)">
                                    <option value="popular" <?php echo $sort_by === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                                    <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                                    <option value="price-low" <?php echo $sort_by === 'price-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price-high" <?php echo $sort_by === 'price-high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                </select>
                                <span class="sort-arrow">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"/>
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Active Filters Display -->
                <div class="active-filters" id="active-filters">
                    <!-- Active filter tags will be inserted here via JS -->
                </div>

                <!-- Products Grid -->
                <div class="products-grid" id="products-grid" role="list" aria-label="Products">
                    <?php echo render_products_html($products_result); ?>
                </div>

                <!-- Load More / Pagination (optional) -->
                <div class="products-footer" id="products-footer">
                    <!-- Pagination or Load More button can go here -->
                </div>
                
            </section>
        </div>
    </main>

    <!-- ============================================
         MOBILE FILTER OVERLAY
         ============================================ -->
    <div class="filter-overlay" id="filter-overlay" aria-hidden="true"></div>

    <!-- ============================================
         FLOATING ACTION BUTTON (Mobile)
         ============================================ -->
    <button class="fab-filter" id="fab-filter" aria-label="Open filters">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
        </svg>
    </button>

    <!-- ============================================
         JAVASCRIPT
         ============================================ -->
    <script>
    (function() {
        'use strict';

        // ========================================
        // DOM ELEMENTS
        // ========================================
        const DOM = {
            // Navigation
            navToggle: () => document.getElementById('nav-toggle'),
            navMenu: () => document.getElementById('nav-menu'),
            userDropdown: () => document.getElementById('user-dropdown'),
            userDropdownTrigger: () => document.getElementById('user-dropdown-trigger'),
            mobileUserMenu: () => document.getElementById('mobile-user-menu'),
            
            // Filters
            filterSidebar: () => document.getElementById('filter-sidebar'),
            filterClose: () => document.getElementById('filter-close'),
            filterToggleBtn: () => document.getElementById('filter-toggle-btn'),
            filterOverlay: () => document.getElementById('filter-overlay'),
            fabFilter: () => document.getElementById('fab-filter'),
            activeFilters: () => document.getElementById('active-filters'),
            
            // Products
            productsGrid: () => document.getElementById('products-grid'),
            productsCount: () => document.getElementById('products-count'),
            sortSelect: () => document.getElementById('sort-select'),
            
            // Search
            searchInput: () => document.getElementById('search-input'),
            searchClear: () => document.getElementById('search-clear'),
            
            // Size
            sizeUnitSelect: () => document.getElementById('size-unit-select')
        };

        // ========================================
        // FILTER STATE
        // ========================================
        const filterState = {
            sizes: [],
            colors: [],
            brand: '',
            category: '',
            priceMin: '',
            priceMax: '',
            sort: 'popular',
            search: ''
        };

        // ========================================
        // INITIALIZATION
        // ========================================
        function init() {
            initNavigation();
            initFilters();
            initSearch();
            initUrlState();
            initSizeUnitSelector();
            initIntersectionObserver();
            updateProductCount();
            updateActiveFilterTags();
        }

        // ========================================
        // NAVIGATION
        // ========================================
        function initNavigation() {
            const navToggle = DOM.navToggle();
            const navMenu = DOM.navMenu();
            const userTrigger = DOM.userDropdownTrigger();
            const userDropdown = DOM.userDropdown();
            const mobileUserMenu = DOM.mobileUserMenu();

            // Mobile menu toggle
            if (navToggle && navMenu) {
                navToggle.addEventListener('click', () => {
                    const isOpen = navMenu.classList.toggle('active');
                    navToggle.setAttribute('aria-expanded', isOpen);
                    document.body.classList.toggle('nav-open', isOpen);
                    
                    // Show mobile user menu when nav is open
                    if (mobileUserMenu) {
                        mobileUserMenu.style.display = isOpen ? 'block' : 'none';
                    }
                });
            }

            // User dropdown toggle (desktop)
            if (userTrigger && userDropdown) {
                const userBtn = userTrigger.querySelector('.user-btn');
                if (userBtn) {
                    userBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const isOpen = userDropdown.classList.toggle('active');
                        userBtn.setAttribute('aria-expanded', isOpen);
                    });
                }
            }

            // Close dropdowns on outside click
            document.addEventListener('click', (e) => {
                // Close user dropdown
                if (userDropdown && !userTrigger?.contains(e.target)) {
                    userDropdown.classList.remove('active');
                    userTrigger?.querySelector('.user-btn')?.setAttribute('aria-expanded', 'false');
                }
                
                // Close mobile nav
                if (navMenu && !navMenu.contains(e.target) && !navToggle?.contains(e.target)) {
                    navMenu.classList.remove('active');
                    navToggle?.setAttribute('aria-expanded', 'false');
                    document.body.classList.remove('nav-open');
                    if (mobileUserMenu) mobileUserMenu.style.display = 'none';
                }
            });
        }

        // ========================================
        // FILTERS
        // ========================================
        function initFilters() {
            const sidebar = DOM.filterSidebar();
            const closeBtn = DOM.filterClose();
            const toggleBtn = DOM.filterToggleBtn();
            const overlay = DOM.filterOverlay();
            const fabBtn = DOM.fabFilter();

            // Filter section toggles
            document.querySelectorAll('.filter-section-header').forEach(header => {
                header.addEventListener('click', () => {
                    const section = header.closest('.filter-section');
                    const body = section.querySelector('.filter-section-body');
                    const isExpanded = header.getAttribute('aria-expanded') === 'true';
                    
                    header.setAttribute('aria-expanded', !isExpanded);
                    section.classList.toggle('collapsed', isExpanded);
                    
                    // Animate body
                    if (body) {
                        if (isExpanded) {
                            body.style.maxHeight = '0';
                        } else {
                            body.style.maxHeight = body.scrollHeight + 'px';
                        }
                    }
                    
                    // Save state
                    const filterType = header.getAttribute('data-toggle');
                    if (filterType) {
                        localStorage.setItem(`filter_${filterType}_expanded`, !isExpanded);
                    }
                });
                
                // Restore saved state
                const filterType = header.getAttribute('data-toggle');
                if (filterType) {
                    const savedState = localStorage.getItem(`filter_${filterType}_expanded`);
                    if (savedState === 'false') {
                        header.setAttribute('aria-expanded', 'false');
                        header.closest('.filter-section').classList.add('collapsed');
                        const body = header.closest('.filter-section').querySelector('.filter-section-body');
                        if (body) body.style.maxHeight = '0';
                    }
                }
            });

            // Mobile filter toggle
            function openFilterSidebar() {
                sidebar?.classList.add('active');
                overlay?.classList.add('active');
                document.body.classList.add('filter-open');
            }

            function closeFilterSidebar() {
                sidebar?.classList.remove('active');
                overlay?.classList.remove('active');
                document.body.classList.remove('filter-open');
            }

            toggleBtn?.addEventListener('click', openFilterSidebar);
            fabBtn?.addEventListener('click', openFilterSidebar);
            closeBtn?.addEventListener('click', closeFilterSidebar);
            overlay?.addEventListener('click', closeFilterSidebar);

            // Close on escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeFilterSidebar();
            });
        }

        // ========================================
        // SEARCH FUNCTIONALITY
        // ========================================
        function initSearch() {
            const searchInput = DOM.searchInput();
            const searchClear = DOM.searchClear();
            
            if (!searchInput) return;
            
            let searchTimeout = null;
            
            // Update clear button visibility
            function updateClearButton() {
                if (searchClear) {
                    searchClear.style.display = searchInput.value.trim() ? 'flex' : 'none';
                }
            }
            
            // Initial state
            updateClearButton();
            
            // Debounced search on input
            searchInput.addEventListener('input', () => {
                updateClearButton();
                
                // Debounce search
                if (searchTimeout) clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    fetchProducts();
                }, 400); // 400ms debounce
            });
            
            // Prevent form submit (we handle it with AJAX)
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (searchTimeout) clearTimeout(searchTimeout);
                    fetchProducts();
                }
            });
            
            // Clear button handler
            if (searchClear) {
                searchClear.addEventListener('click', () => {
                    searchInput.value = '';
                    updateClearButton();
                    searchInput.focus();
                    fetchProducts();
                });
            }
            
            // Focus animation
            searchInput.addEventListener('focus', () => {
                searchInput.parentElement.classList.add('focused');
            });
            
            searchInput.addEventListener('blur', () => {
                searchInput.parentElement.classList.remove('focused');
            });
        }

        // ========================================
        // URL STATE SYNC
        // ========================================
        function initUrlState() {
            const params = new URLSearchParams(window.location.search);
            
            // Sizes
            const sizes = params.get('sizes')?.split(',').filter(Boolean) || [];
            sizes.forEach(size => {
                const btn = document.querySelector(`.size-option[data-size="${size}"]`);
                if (btn) btn.classList.add('active');
            });
            filterState.sizes = sizes;

            // Colors
            const colors = params.get('colors')?.split(',').filter(Boolean) || [];
            colors.forEach(color => {
                const btn = document.querySelector(`.color-option[data-color="${color}"]`);
                if (btn) btn.classList.add('active');
            });
            filterState.colors = colors;

            // Brand
            const brand = params.get('brand') || '';
            if (brand) {
                const btn = document.querySelector(`.brand-option[data-brand-id="${brand}"]`);
                if (btn) btn.classList.add('active');
            }
            filterState.brand = brand;

            // Category
            const category = params.get('category') || '';
            if (category) {
                const btn = document.querySelector(`.category-option[data-cat="${category}"]`);
                if (btn) btn.classList.add('active');
            }
            filterState.category = category;

            // Price
            const minPrice = params.get('min_price') || '';
            const maxPrice = params.get('max_price') || '';
            if (minPrice && maxPrice) {
                const btn = document.querySelector(`.price-option[data-min="${minPrice}"][data-max="${maxPrice}"]`);
                if (btn) btn.classList.add('active');
            }
            filterState.priceMin = minPrice;
            filterState.priceMax = maxPrice;

            // Sort
            const sort = params.get('sort') || 'popular';
            const sortSelect = DOM.sortSelect();
            if (sortSelect) sortSelect.value = sort;
            filterState.sort = sort;
            
            // Search
            const searchQuery = params.get('q') || '';
            const searchInput = DOM.searchInput();
            if (searchInput && searchQuery) {
                searchInput.value = searchQuery;
                const searchClear = DOM.searchClear();
                if (searchClear) searchClear.style.display = 'flex';
            }
            filterState.search = searchQuery;
        }

        // ========================================
        // SIZE UNIT CONVERSION
        // ========================================
        const EU_SIZE_MAP = {
            35: {uk: '2.5', us: '3.5'}, 36: {uk: '3.5', us: '4.5'},
            37: {uk: '4', us: '5'}, 38: {uk: '5', us: '6'},
            39: {uk: '6', us: '7'}, 40: {uk: '6.5', us: '7.5'},
            41: {uk: '7', us: '8'}, 42: {uk: '8', us: '9'},
            43: {uk: '9', us: '10'}, 44: {uk: '9.5', us: '10.5'},
            45: {uk: '10', us: '11'}, 46: {uk: '11', us: '12'},
            47: {uk: '12', us: '13'}, 48: {uk: '13', us: '14'}
        };

        function getSizeConversions(euVal) {
            const eu = Number(euVal);
            if (isNaN(eu)) return null;
            const key = Math.round(eu);
            if (EU_SIZE_MAP[key]) {
                return {eu: String(key), uk: EU_SIZE_MAP[key].uk, us: EU_SIZE_MAP[key].us};
            }
            // Fallback formulas
            const uk = Math.round(((eu - 32) / 1.27) * 2) / 2;
            const us = uk + 1;
            return {
                eu: String(Math.round(eu)),
                uk: uk % 1 === 0 ? String(uk) : uk.toFixed(1),
                us: us % 1 === 0 ? String(us) : us.toFixed(1)
            };
        }

        function updateSizeLabels(unit) {
            document.querySelectorAll('.size-option').forEach(btn => {
                const euRaw = parseFloat(btn.dataset.euSize);
                if (isNaN(euRaw)) return;
                const conv = getSizeConversions(euRaw);
                if (!conv) return;
                btn.textContent = unit === 'UK' ? conv.uk : unit === 'US' ? conv.us : conv.eu;
            });
        }

        function initSizeUnitSelector() {
            const select = DOM.sizeUnitSelect();
            if (!select) return;
            
            const savedUnit = localStorage.getItem('shoes_size_unit') || select.dataset.defaultUnit || 'EU';
            select.value = savedUnit;
            updateSizeLabels(savedUnit);
            
            select.addEventListener('change', () => {
                updateSizeLabels(select.value);
                localStorage.setItem('shoes_size_unit', select.value);
            });
        }

        // ========================================
        // FETCH PRODUCTS
        // ========================================
        async function fetchProducts() {
            const grid = DOM.productsGrid();
            if (!grid) return;

            // Build params
            const params = new URLSearchParams();
            
            const sizes = Array.from(document.querySelectorAll('.size-option.active'))
                .map(el => el.dataset.size).filter(Boolean);
            if (sizes.length) params.set('sizes', sizes.join(','));
            
            const colors = Array.from(document.querySelectorAll('.color-option.active'))
                .map(el => el.dataset.color).filter(Boolean);
            if (colors.length) params.set('colors', colors.join(','));
            
            const brandBtn = document.querySelector('.brand-option.active');
            if (brandBtn) params.set('brand', brandBtn.dataset.brandId);
            
            const categoryBtn = document.querySelector('.category-option.active');
            if (categoryBtn) params.set('category', categoryBtn.dataset.cat);
            
            const priceBtn = document.querySelector('.price-option.active');
            if (priceBtn) {
                params.set('min_price', priceBtn.dataset.min);
                params.set('max_price', priceBtn.dataset.max);
            }
            
            const sortSelect = DOM.sortSelect();
            if (sortSelect) params.set('sort', sortSelect.value);
            
            // Search query
            const searchInput = DOM.searchInput();
            if (searchInput && searchInput.value.trim()) {
                params.set('q', searchInput.value.trim());
            }
            
            params.set('ajax', '1');

            // Show loading state
            grid.classList.add('loading');
            grid.innerHTML = `
                <div class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Loading products...</p>
                </div>
            `;

            try {
                const res = await fetch('shoes.php?' + params.toString(), {
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                });
                if (!res.ok) throw new Error('Network error');
                
                const html = await res.text();
                grid.innerHTML = html;
                grid.classList.remove('loading');
                
                // Update URL
                params.delete('ajax');
                const newUrl = 'shoes.php' + (params.toString() ? '?' + params.toString() : '');
                history.replaceState(null, '', newUrl);
                
                // Update count and active filters
                updateProductCount();
                updateActiveFilterTags();
                
                // Re-observe for animations
                initIntersectionObserver();
                
            } catch (e) {
                console.error('Failed to load products:', e);
                grid.innerHTML = `
                    <div class="error-state">
                        <p>Failed to load products. Please try again.</p>
                        <button onclick="location.reload()">Retry</button>
                    </div>
                `;
                grid.classList.remove('loading');
            }
        }

        // ========================================
        // FILTER TOGGLE FUNCTIONS
        // ========================================
        window.toggleSize = function(size) {
            const btn = document.querySelector(`.size-option[data-size="${size}"]`);
            if (!btn) return;
            btn.classList.toggle('active');
            fetchProducts();
        };

        window.toggleColor = function(color) {
            const btn = document.querySelector(`.color-option[data-color="${color}"]`);
            if (!btn) return;
            btn.classList.toggle('active');
            fetchProducts();
        };

        window.toggleBrand = function(brandId) {
            const btn = document.querySelector(`.brand-option[data-brand-id="${brandId}"]`);
            if (!btn) return;
            
            // Single select
            document.querySelectorAll('.brand-option.active').forEach(b => {
                if (b !== btn) b.classList.remove('active');
            });
            btn.classList.toggle('active');
            fetchProducts();
        };

        window.toggleCategory = function(cat) {
            const btn = document.querySelector(`.category-option[data-cat="${cat}"]`);
            if (!btn) return;
            
            // Single select
            document.querySelectorAll('.category-option.active').forEach(b => {
                if (b !== btn) b.classList.remove('active');
            });
            btn.classList.toggle('active');
            fetchProducts();
        };

        window.togglePriceOption = function(el) {
            if (!el) return;
            
            // Single select
            const current = document.querySelector('.price-option.active');
            if (current === el) {
                el.classList.remove('active');
            } else {
                if (current) current.classList.remove('active');
                el.classList.add('active');
            }
            fetchProducts();
        };

        window.updateSort = function(value) {
            const sortSelect = DOM.sortSelect();
            if (sortSelect) sortSelect.value = value;
            fetchProducts();
        };

        window.clearFilters = function() {
            document.querySelectorAll('.size-option.active, .color-option.active, .brand-option.active, .category-option.active, .price-option.active')
                .forEach(el => el.classList.remove('active'));
            
            const sortSelect = DOM.sortSelect();
            if (sortSelect) sortSelect.value = 'popular';
            
            // Clear search
            const searchInput = DOM.searchInput();
            const searchClear = DOM.searchClear();
            if (searchInput) searchInput.value = '';
            if (searchClear) searchClear.style.display = 'none';
            
            fetchProducts();
        };

        // ========================================
        // PROMO CODE COPY
        // ========================================
        window.copyPromoCode = function(btn) {
            const codeEl = btn.querySelector('.promo-code-value');
            if (!codeEl) return;
            
            const code = codeEl.textContent;
            navigator.clipboard.writeText(code).then(() => {
                btn.classList.add('copied');
                codeEl.textContent = 'Copied!';
                
                setTimeout(() => {
                    btn.classList.remove('copied');
                    codeEl.textContent = code;
                }, 2000);
            });
        };

        // ========================================
        // ACTIVE FILTER TAGS
        // ========================================
        function updateActiveFilterTags() {
            const container = DOM.activeFilters();
            if (!container) return;
            
            const tags = [];
            
            // Sizes
            document.querySelectorAll('.size-option.active').forEach(btn => {
                tags.push({
                    type: 'size',
                    value: btn.dataset.size,
                    label: 'Size: ' + btn.textContent
                });
            });
            
            // Colors
            document.querySelectorAll('.color-option.active').forEach(btn => {
                tags.push({
                    type: 'color',
                    value: btn.dataset.color,
                    label: btn.dataset.color
                });
            });
            
            // Brand
            const brandBtn = document.querySelector('.brand-option.active');
            if (brandBtn) {
                tags.push({
                    type: 'brand',
                    value: brandBtn.dataset.brandId,
                    label: brandBtn.querySelector('.brand-name')?.textContent || 'Brand'
                });
            }
            
            // Category
            const categoryBtn = document.querySelector('.category-option.active');
            if (categoryBtn) {
                tags.push({
                    type: 'category',
                    value: categoryBtn.dataset.cat,
                    label: categoryBtn.dataset.cat
                });
            }
            
            // Price
            const priceBtn = document.querySelector('.price-option.active');
            if (priceBtn) {
                tags.push({
                    type: 'price',
                    value: `${priceBtn.dataset.min}-${priceBtn.dataset.max}`,
                    label: priceBtn.querySelector('.price-label')?.textContent || 'Price'
                });
            }
            
            // Search
            const searchInput = DOM.searchInput();
            if (searchInput && searchInput.value.trim()) {
                tags.push({
                    type: 'search',
                    value: searchInput.value.trim(),
                    label: '🔍 "' + searchInput.value.trim() + '"'
                });
            }
            
            // Render tags
            if (tags.length === 0) {
                container.innerHTML = '';
                container.classList.remove('has-filters');
                return;
            }
            
            container.classList.add('has-filters');
            container.innerHTML = tags.map(tag => `
                <span class="filter-tag" data-type="${tag.type}" data-value="${tag.value}">
                    ${tag.label}
                    <button class="filter-tag-remove" onclick="removeFilterTag('${tag.type}', '${tag.value}')" aria-label="Remove filter">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </span>
            `).join('') + `
                <button class="clear-all-tags" onclick="clearFilters()">Clear All</button>
            `;
        }

        window.removeFilterTag = function(type, value) {
            if (type === 'search') {
                const searchInput = DOM.searchInput();
                const searchClear = DOM.searchClear();
                if (searchInput) searchInput.value = '';
                if (searchClear) searchClear.style.display = 'none';
                fetchProducts();
                return;
            }
            
            let selector = '';
            switch (type) {
                case 'size': selector = `.size-option[data-size="${value}"]`; break;
                case 'color': selector = `.color-option[data-color="${value}"]`; break;
                case 'brand': selector = `.brand-option[data-brand-id="${value}"]`; break;
                case 'category': selector = `.category-option[data-cat="${value}"]`; break;
                case 'price': selector = '.price-option.active'; break;
            }
            const el = document.querySelector(selector);
            if (el) el.classList.remove('active');
            fetchProducts();
        };

        // ========================================
        // PRODUCT COUNT
        // ========================================
        function updateProductCount() {
            const countEl = DOM.productsCount();
            const grid = DOM.productsGrid();
            if (!countEl || !grid) return;
            
            const count = grid.querySelectorAll('.product-card').length;
            countEl.textContent = `${count} product${count !== 1 ? 's' : ''}`;
        }

        // ========================================
        // INTERSECTION OBSERVER (Animations)
        // ========================================
        function initIntersectionObserver() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        entry.target.style.animationDelay = `${index * 0.05}s`;
                        entry.target.classList.add('animate-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1, rootMargin: '50px' });
            
            document.querySelectorAll('.product-card:not(.animate-in)').forEach(card => {
                observer.observe(card);
            });
        }

        // ========================================
        // START
        // ========================================
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
    </script>
    <?php include __DIR__ . '/partials/chatbot.php'; ?>
</body>
</html>
