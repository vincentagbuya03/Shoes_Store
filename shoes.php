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
    $res = $conn->query("SHOW COLUMNS FROM Product LIKE '" . $conn->real_escape_string($col) . "'");
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
    FROM Product s
    LEFT JOIN Brand b ON s.brand_id = b.brand_id

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
                $badge_class = 'hot';
            } elseif (strpos($badge, '%') !== false) {
                $badge_class = 'sale';
            }

            $badge_html = (strcasecmp($badge, 'Hot') === 0)
                ? '<i class="fa-solid fa-fire" aria-hidden="true"></i>'
                : htmlspecialchars($badge, ENT_QUOTES, 'UTF-8');

            $html .= "
            <div class='product-card'>
                <span class='product-badge $badge_class'>{$badge_html}</span>
                <div class='product-image'>
                    <a href='product-detail.php?id={$pid}&color=" . urlencode($color) . "' title='View $name'>
                        <img src='$img' alt='$name' loading='lazy'>
                    </a>
                </div>
                <div class='product-info'>
                    <h3 class='product-name'>
                        <a href='product-detail.php?id={$pid}&color=" . urlencode($color) . "'>$name</a>
                    </h3>
                    <div class='product-brand'>$brand</div>
                    <div class='product-color'>$color</div>
                    <div class='product-price'>$price</div>
                    <a class='add-to-cart-btn' href='product-detail.php?id={$pid}'>
                        View Product
                    </a>
                </div>
            </div>
            ";
        }
    } else {
        $html .= "
        <div class='empty-state'>
            <i class='fas fa-inbox'></i>
            <h3>No products found</h3>
            <p>Try adjusting your filters</p>
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shoes Collection - <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/index.css">
    <link rel="stylesheet" href="asset/style/product.css">
    <link rel="stylesheet" href="asset/style/shoes.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <?php echo getStoreThemeCSS(); ?>
</head>
<body>
    <!-- Announcement Bar -->
    <?php if (!empty($store_settings['announcement_enabled']) && !empty($store_settings['announcement_text'])): ?>
    <div class="announcement-bar" style="background-color: <?php echo htmlspecialchars($store_settings['announcement_bg_color'] ?? '#000'); ?>; color: <?php echo htmlspecialchars($store_settings['announcement_text_color'] ?? '#fff'); ?>;">
        <p><?php echo htmlspecialchars($store_settings['announcement_text']); ?></p>
    </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav>
        <div class="logo">
            <a href="user-interface.php"><?php echo htmlspecialchars($store_settings['store_name']); ?></a>
        </div>
        <ul class="nav-links" id="nav-links">
            <li><a href="user-interface.php">Home</a></li>
            <li><a href="shoes.php" style="font-weight: 600; color: #000;">Shoes</a></li>
            <li><a href="brand.php">Brand</a></li>

            <!-- Profile links moved into mobile menu (hidden on desktop) -->
            <li class="nav-user-mobile" id="nav-user-mobile" style="display:none; padding-top:12px;">
                <a href="user-profile.php">My Profile</a>
                <a href="orders.php">My Orders</a>
                <a href="logout.php">Logout</a>
            </li>
        </ul>
        <div class="nav-right">
            <a href="cart.php" class="cart-icon" id="cart-icon">
                🛒
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>

            <!-- Desktop user icon only (name intentionally hidden) -->
            <div class="user-menu-desktop" aria-hidden="true" style="display:flex;align-items:center;gap:8px;margin-left:8px;">
                <i class="fa-solid fa-user"></i>
            </div>

            <!-- Toggle moved to the right for mobile/zoomed views -->
            <button class="menu-toggle" id="menu-toggle" aria-label="Toggle navigation" tabindex="0">☰</button>
        </div>
    </nav>

    <!-- Promo Banner -->
    <div class="promo-banner">
        <div class="promo-content">
            <h3>Black Friday Sale</h3>
            <p>2 Pairs 20% OFF, 3 Pairs 25% OFF, 4 Pairs 30% OFF</p>
        </div>
        <button class="promo-code">
            Code: BFCM25 <span>📋</span>
        </button>
    </div>

    <!-- Main Collection Section -->
    <div class="collection-container">
        <!-- Left Sidebar Filter -->
        <aside class="filter-sidebar" id="filter-sidebar">
            <div class="filter-header">
                <h2 class="filter-title">Filter</h2>
            </div>

            <!-- Size Filter -->
            <div class="filter-group" data-filter="size">
                <div class="filter-group-title">
                    <span>Size</span>
                    <button class="group-toggle" aria-expanded="true" data-filter="size" title="Hide Size">−</button>
                </div>
                <div class="filter-body">
                    <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                        <label for="size-unit-select" style="font-size:0.85rem;color:#6b7280;">Unit:</label>
                        <?php
                            // Dynamically populate unit selector if `size` table has a unit column
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
                                    // if EU present, default to EU
                                    if (in_array('EU', $unitOptions)) $defaultUnit = 'EU';
                                    else $defaultUnit = $unitOptions[0];
                                }
                            }

                            echo "<select id=\"size-unit-select\" style=\"padding:6px;border:1px solid #e5e7eb;border-radius:6px;font-size:0.8rem;\" data-default-unit=\"$defaultUnit\">";
                            foreach ($unitOptions as $u) {
                                $sel = ($u === $defaultUnit) ? ' selected' : '';
                                echo "<option value=\"" . htmlspecialchars($u,ENT_QUOTES,'UTF-8') . "\"$sel>" . htmlspecialchars($u,ENT_QUOTES,'UTF-8') . "</option>";
                            }
                            echo "</select>";
                        ?>
                    </div>
                    <div class="size-grid">
                        <?php
                            $sizes_query = "SELECT DISTINCT sz.size_name AS size FROM product_variant pv LEFT JOIN size sz ON pv.size_id = sz.size_id WHERE sz.size_name IS NOT NULL ORDER BY CAST(sz.size_name AS DECIMAL(5,1)) ASC";
                            $sizes_result = $conn->query($sizes_query);
                            $available_sizes = [];
                            if ($sizes_result && $sizes_result->num_rows > 0) {
                                while ($size_row = $sizes_result->fetch_assoc()) {
                                    $size = $size_row['size'];
                                    $size_html = htmlspecialchars($size, ENT_QUOTES, 'UTF-8');
                                    $available_sizes[] = $size_html;
                                    $is_active = in_array($size, $selected_sizes) ? 'active' : '';
                                    // keep data-size (original DB value) and store data-eu-size for client-side conversions (your DB uses EU/UE sizes)
                                    // single-label button; JS will relabel based on selected unit
                                    echo "\n                                <button class='size-btn $is_active' data-size='$size_html' data-eu-size='$size_html' onclick='toggleSize(\"$size_html\")'>$size_html</button>\n                                ";
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>

                <!-- Brand Filter -->
                <div class="filter-group" data-filter="brand">
                    <div class="filter-group-title">
                        <span>Brand</span>
                        <button class="group-toggle" aria-expanded="true" data-filter="brand" title="Hide Brand">−</button>
                    </div>
                    <div class="filter-body">
                        <div class="brand-list" style="display:flex;flex-direction:column;gap:6px;">
                            <?php
                                $brand_q = "SELECT brand_id, brand_name FROM brand ORDER BY brand_name ASC";
                                $brand_res = $conn->query($brand_q);
                                if ($brand_res && $brand_res->num_rows > 0) {
                                    while ($b = $brand_res->fetch_assoc()) {
                                        $bid = (int)$b['brand_id'];
                                        $bname = htmlspecialchars($b['brand_name'], ENT_QUOTES, 'UTF-8');
                                        $active = ($selected_brand !== null && (string)$selected_brand === (string)$bid) ? 'active' : '';
                                        echo "<button class='brand-btn $active' data-brand-id='$bid' onclick=\"toggleBrand('$bid')\">$bname</button>";
                                    }
                                } else {
                                    echo "<div style='color:#6b7280;font-size:0.9rem;'>No brands available</div>";
                                }
                            ?>
                        </div>
                    </div>
                </div>

            <div class="filter-group" data-filter="color">
                <div class="filter-group-title">
                    <span>Color</span>
                    <button class="group-toggle" aria-expanded="true" data-filter="color" title="Hide Color">−</button>
                </div>
                <div class="filter-body">
                    <div class="color-swatches">
                    <?php
                        $colors_query = "SELECT DISTINCT color_name FROM color ORDER BY color_name ASC";
                        $colors_result = $conn->query($colors_query);
                        $color_map = [
                            'Black' => '#000000',
                            'White' => '#ffffff',
                            'Red' => '#ef4444',
                            'Blue' => '#3b82f6',
                            'Green' => '#10b981',
                            'Yellow' => '#fbbf24',
                            'Gray' => '#9ca3af',
                            'Brown' => '#92400e',
                            'Nude' => '#dcc7b0',
                            'Navy' => '#001f3f',
                            'Espresso' => '#6f4e37',
                            'Leopard' => '#a0826d'
                        ];
                        
                        if ($colors_result && $colors_result->num_rows > 0) {
                            while ($color_row = $colors_result->fetch_assoc()) {
                                $color = htmlspecialchars($color_row['color_name'], ENT_QUOTES, 'UTF-8');
                                $hex_color = $color_map[$color] ?? '#cccccc';
                                $is_active = in_array($color, $selected_colors) ? 'active' : '';
                                echo "
                                <div style='text-align: center;'>
                                    <div class='color-swatch $is_active' style='background-color: $hex_color;' data-color='$color' onclick='toggleColor(\"$color\")'></div>
                                    <div class='color-label'>$color</div>
                                </div>
                                ";
                            }
                        }
                    ?>
                    </div>
                </div>
            </div>
            
            <!-- Category Filter -->
            <div class="filter-group" data-filter="category">
                <div class="filter-group-title">
                    <span>Category</span>
                    <button class=  "group-toggle" aria-expanded="true" data-filter="category" title="Hide Category">−</button>
                </div>
                <div class="filter-body">
                    <div class="category-grid">
                        <?php
                            $cats = ['Male','Female','Kids','Unisex'];
                            foreach ($cats as $cat) {
                                $c = htmlspecialchars($cat, ENT_QUOTES, 'UTF-8');
                                $is_active = ($selected_category === $cat) ? 'active' : '';
                                echo "<button class='category-btn $is_active' data-cat='".htmlspecialchars($cat,ENT_QUOTES,'UTF-8')."' onclick=\"toggleCategory('".htmlspecialchars($cat,ENT_QUOTES,'UTF-8')."')\">$c</button>";
                            }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Price range filter as selectable options -->
            <div class="filter-group" data-filter="price">
                <div class="filter-group-title">
                    <span>Price Range</span>
                    <button class="group-toggle" aria-expanded="true" data-filter="price" title="Hide Price">−</button>
                </div>
                <div class="filter-body">
                    <div class="price-range-container">
                        <div class="price-options">
                            <button type="button" class="price-option" data-min="0" data-max="999" onclick="togglePriceOption(this)">Under ₱1,000</button>
                            <button type="button" class="price-option" data-min="1000" data-max="2499" onclick="togglePriceOption(this)">₱1,000 - ₱2,499</button>
                            <button type="button" class="price-option" data-min="2500" data-max="4999" onclick="togglePriceOption(this)">₱2,500 - ₱4,999</button>
                            <button type="button" class="price-option" data-min="5000" data-max="9999" onclick="togglePriceOption(this)">₱5,000 - ₱9,999</button>
                            <button type="button" class="price-option" data-min="10000" data-max="999999" onclick="togglePriceOption(this)">₱10,000+</button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Clear Filters -->
            <button class="clear-all-btn" onclick="clearFilters()">
                Clear All
            </button>
        </aside>

        <!-- Right Content Area -->
        <div class="collection-header">
            <!-- Collection Header -->
            <div class="collection-title">
                <h1>Collection</h1>
                <div class="sort-container">
                    <label for="sort-select" style="font-size: 0.9rem; font-weight: 600;">Sort By</label>
                    <select id="sort-select" class="sort-select" onchange="updateSort(this.value)">
                        <option value="popular" <?php echo $sort_by === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest</option>
                        <option value="price-low" <?php echo $sort_by === 'price-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price-high" <?php echo $sort_by === 'price-high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="products-grid">
                <?php echo render_products_html($products_result); ?>
            </div>
        </div>
    </div>

    <script>
        // Fetch filtered products via AJAX and update the products grid without a full page reload
        async function fetchProducts() {
            const sizes = Array.from(document.querySelectorAll('.size-btn.active')).map(el => el.dataset.size).filter(Boolean).join(',');
            const colors = Array.from(document.querySelectorAll('.color-swatch.active')).map(el => el.dataset.color).filter(Boolean).join(',');
            // Read selected price option if any (new pill-style options)
            const activePrice = document.querySelector('.price-option.active');
            let minPrice = '';
            let maxPrice = '';
            if (activePrice) {
                minPrice = activePrice.dataset.min || '';
                maxPrice = activePrice.dataset.max || '';
            } else {
                // legacy inputs (if present) -- defensive
                const minEl = document.getElementById('min-price');
                const maxEl = document.getElementById('max-price');
                minPrice = minEl ? (minEl.value || '') : '';
                maxPrice = maxEl ? (maxEl.value || '') : '';
            }
            const sort = document.getElementById('sort-select') ? document.getElementById('sort-select').value : 'popular';

            const categoryBtn = document.querySelector('.category-btn.active');
            const category = categoryBtn ? categoryBtn.dataset.cat : '';
            const brandBtn = document.querySelector('.brand-btn.active');
            const brand = brandBtn ? brandBtn.dataset.brandId : '';

            const params = new URLSearchParams();
            if (sizes) params.set('sizes', sizes);
            if (colors) params.set('colors', colors);
            if (minPrice) params.set('min_price', minPrice);
            if (maxPrice) params.set('max_price', maxPrice);
            if (sort) params.set('sort', sort);
            if (category) params.set('category', category);
            if (brand) params.set('brand', brand);
            params.set('ajax', '1');

            const grid = document.querySelector('.products-grid');
            if (!grid) return;

            // optional: show a loading state
            const old = grid.innerHTML;
            grid.innerHTML = '<div class="loading-placeholder">Loading...</div>';

            try {
                const res = await fetch('shoes.php?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Network error');
                const html = await res.text();
                grid.innerHTML = html;

                // update browser URL (without ajax param)
                params.delete('ajax');
                const newUrl = 'shoes.php' + (params.toString() ? ('?' + params.toString()) : '');
                history.replaceState(null, '', newUrl);
            } catch (e) {
                console.error('Failed to load products:', e);
                grid.innerHTML = old; // restore
            }
        }

        function toggleSize(size) {
            const btn = document.querySelector(`.size-btn[data-size="${size}"]`);
            if (!btn) return;
            btn.classList.toggle('active');
            fetchProducts();
        }

        // Size unit conversion helpers. Treat stored sizes as EU (UE) in `data-eu-size`.
        (function(){
            // precise lookup table for EU -> UK/US (common adult ranges). Values are strings to preserve .5
            const EU_MAP = {
                35: {uk: '2.5', us: '3.5'},
                36: {uk: '3.5', us: '4.5'},
                37: {uk: '4', us: '5'},
                38: {uk: '5', us: '6'},
                39: {uk: '6', us: '7'},
                40: {uk: '6.5', us: '7.5'},
                41: {uk: '7', us: '8'},
                42: {uk: '8', us: '9'},
                43: {uk: '9', us: '10'},
                44: {uk: '9.5', us: '10.5'},
                45: {uk: '10', us: '11'},
                46: {uk: '11', us: '12'},
                47: {uk: '12', us: '13'},
                48: {uk: '13', us: '14'}
            };

            function roundHalf(num){ return Math.round(num*2)/2; }
            function euToUkFallback(v){ return roundHalf((v - 32) / 1.27); }
            function ukToUs(v){ return roundHalf(v + 1); }

            function format(val){ if (Math.abs(val - Math.round(val)) < 0.001) return String(Math.round(val)); return String(val); }

            window.getSizeConversions = function(euVal){
                const eu = Number(euVal);
                if (isNaN(eu)) return null;
                const key = Math.round(eu);
                if (EU_MAP[key]) return {eu: String(key), uk: EU_MAP[key].uk, us: EU_MAP[key].us};
                // fallback: use approximate formulas
                const ukApprox = euToUkFallback(eu);
                const usApprox = ukToUs(ukApprox);
                return {eu: String(Math.round(eu)), uk: format(ukApprox), us: format(usApprox)};
            };

            window.updateSizeLabels = function(unit){
                document.querySelectorAll('.size-btn').forEach(btn => {
                    const euRaw = parseFloat(btn.dataset.euSize);
                    if (isNaN(euRaw)) return;
                    const conv = window.getSizeConversions(euRaw);
                    if (!conv) return;
                    let label = conv.eu;
                    if (unit === 'EU') label = conv.eu;
                    else if (unit === 'UK') label = conv.uk;
                    else if (unit === 'US') label = conv.us;
                    btn.textContent = label;
                });
            };
        })();

        function toggleColor(color) {
            const sw = document.querySelector(`.color-swatch[data-color="${color}"]`);
            if (!sw) return;
            sw.classList.toggle('active');
            fetchProducts();
        }

        function toggleCategory(cat) {
            const btn = document.querySelector(`.category-btn[data-cat="${cat}"]`);
            if (!btn) return;
            // single-select behavior: deactivate others
            document.querySelectorAll('.category-btn.active').forEach(b => b.classList.remove('active'));
            if (btn.classList.contains('active')) {
                btn.classList.remove('active');
            } else {
                btn.classList.add('active');
            }
            fetchProducts();
        }

        function toggleBrand(brandId) {
            const btn = document.querySelector(`.brand-btn[data-brand-id="${brandId}"]`);
            if (!btn) return;
            // single-select behavior
            document.querySelectorAll('.brand-btn.active').forEach(b => b.classList.remove('active'));
            if (btn.classList.contains('active')) {
                btn.classList.remove('active');
            } else {
                btn.classList.add('active');
            }
            fetchProducts();
        }

        function applyPriceFilter() {
            fetchProducts();
        }

        // Price option toggle (single-select). Pass the button element.
        function togglePriceOption(el) {
            if (!el) return;
            const currently = document.querySelector('.price-option.active');
            // if clicking the already-active option -> clear selection
            if (currently === el) {
                el.classList.remove('active');
            } else {
                if (currently) currently.classList.remove('active');
                el.classList.add('active');
            }
            fetchProducts();
        }

        function updateSort(value) {
            const sel = document.getElementById('sort-select');
            if (sel) sel.value = value;
            fetchProducts();
        }

        function clearFilters() {
            document.querySelectorAll('.size-btn.active').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.color-swatch.active').forEach(el => el.classList.remove('active'));
            // Clear category selection as well
            document.querySelectorAll('.category-btn.active').forEach(el => el.classList.remove('active'));
            // Clear price option pills (new UI) as well as legacy inputs
            document.querySelectorAll('.price-option.active').forEach(el => el.classList.remove('active'));
            const min = document.getElementById('min-price');
            const max = document.getElementById('max-price');
            if (min) min.value = '';
            if (max) max.value = '';
            const sort = document.getElementById('sort-select');
            if (sort) sort.value = 'popular';
            fetchProducts();
        }

        function addToCart(event, productId) {
            event.preventDefault();
            const button = event.target;
            button.classList.add('loading');
            button.disabled = true;

            // Add to cart logic here
            setTimeout(() => {
                button.classList.remove('loading');
                button.disabled = false;
            }, 1000);
        }

        // Initialize active states on page load based on URL parameters
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const currentSizes = urlParams.get('sizes')?.split(',') || [];
            const currentColors = urlParams.get('colors')?.split(',') || [];
            const minPrice = urlParams.get('min_price') || '0';
            const maxPrice = urlParams.get('max_price') || '999999';

            // Activate size buttons
            document.querySelectorAll('.size-btn').forEach(button => {
                if (currentSizes.includes(button.dataset.size)) {
                    button.classList.add('active');
                }
            });

            // Activate color swatches
            document.querySelectorAll('.color-swatch').forEach(swatch => {
                if (currentColors.includes(swatch.dataset.color)) {
                    swatch.classList.add('active');
                }
            });

            // Activate category button
            document.querySelectorAll('.category-btn').forEach(btn => {
                if (btn.dataset.cat && btn.dataset.cat === urlParams.get('category')) btn.classList.add('active');
            });

            // Activate brand button (by id)
            const brandParam = urlParams.get('brand');
            if (brandParam) {
                document.querySelectorAll('.brand-btn').forEach(b => {
                    if (b.dataset.brandId && b.dataset.brandId === brandParam) b.classList.add('active');
                });
            }

            // Set price input values
            const minPriceInput = document.getElementById('min-price');
            const maxPriceInput = document.getElementById('max-price');
            if (minPrice !== '0' && maxPrice !== '999999') {
                // try to match a price-option button and activate it
                const options = document.querySelectorAll('.price-option');
                options.forEach(o => {
                    if (o.dataset.min == minPrice && o.dataset.max == maxPrice) {
                        o.classList.add('active');
                    }
                });

                // fallback: populate legacy inputs if they exist
                if (minPriceInput) minPriceInput.value = minPrice !== '0' ? minPrice : '';
                if (maxPriceInput) maxPriceInput.value = maxPrice !== '999999' ? maxPrice : '';
            }

            // Initialize size unit selector and labels (default to EU/UE because sizes are stored as EU)
            const unitSelect = document.getElementById('size-unit-select');
            const savedUnit = localStorage.getItem('shoes_size_unit') || 'EU';
            if (unitSelect) {
                unitSelect.value = savedUnit;
                updateSizeLabels(unitSelect.value);
                unitSelect.addEventListener('change', function() {
                    updateSizeLabels(this.value);
                    try { localStorage.setItem('shoes_size_unit', this.value); } catch(e) {}
                });
            }
        });
        </script>

        <!-- Per-group collapse script -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Per-group toggles (Size, Color, Price)
                document.querySelectorAll('.group-toggle').forEach(btn => {
                    const key = 'shoes_filter_group_' + (btn.dataset.filter || btn.getAttribute('data-filter')) + '_collapsed';
                    const parent = btn.closest('.filter-group');
                    if (!parent) return;
                    const savedGroup = localStorage.getItem(key) === 'true';
                    if (savedGroup) {
                        parent.classList.add('collapsed');
                        btn.textContent = '+';
                        btn.setAttribute('aria-expanded', 'false');
                    }

                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        const collapsed = parent.classList.toggle('collapsed');
                        btn.textContent = collapsed ? '+' : '−';
                        btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                        try { localStorage.setItem(key, collapsed); } catch (err) { /* ignore */ }
                    });

                    // Also allow clicking the title area to toggle
                    const title = parent.querySelector('.filter-group-title');
                    if (title) {
                        title.addEventListener('click', function(ev) {
                            if (ev.target === btn) return; // already handled
                            const collapsed = parent.classList.toggle('collapsed');
                            btn.textContent = collapsed ? '+' : '−';
                            btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                            try { localStorage.setItem(key, collapsed); } catch (err) { /* ignore */ }
                        });
                    }
                });
            });
        </script>

        <script>
            // Nav toggle for shoes.php (mobile / zoomed view)
            document.addEventListener('DOMContentLoaded', function() {
                const menuToggle = document.getElementById('menu-toggle');
                const navLinks = document.getElementById('nav-links');
                const userMobile = document.getElementById('nav-user-mobile');

            function setUserMobileVisibility() {
                if (!userMobile) return;
                // Show only when nav-links has 'show' class (mobile open)
                userMobile.style.display = navLinks && navLinks.classList.contains('show') ? 'block' : 'none';
            }

            if (menuToggle && navLinks) {
                menuToggle.addEventListener('click', function() {
                    navLinks.classList.toggle('show');
                    setUserMobileVisibility();
                });

                menuToggle.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        navLinks.classList.toggle('show');
                        setUserMobileVisibility();
                    }
                });
            }

            // Close mobile nav when clicking outside (optional)
            document.addEventListener('click', function(e) {
                if (!navLinks || !menuToggle) return;
                if (!navLinks.contains(e.target) && !menuToggle.contains(e.target)) {
                    navLinks.classList.remove('show');
                    setUserMobileVisibility();
                }
            });
        });
    </script>
</body>
</html>
