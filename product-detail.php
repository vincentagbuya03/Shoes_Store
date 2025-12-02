<?php
// product-details.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "vincentagbuya123";
$database   = "shoestore";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Get product ID
$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($product_id <= 0) {
    die("Product not found.");
}

// Fetch product info
$product_stmt = $conn->prepare("
    SELECT p.product_id, p.name, p.description, p.category, b.brand_name
    FROM product p
    LEFT JOIN brand b ON p.brand_id = b.brand_id
    WHERE p.product_id = ?
");
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
    $cart_conn = new mysqli($servername, $username, $password, $database);
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
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?> - Product Details</title>

    <link rel="stylesheet" href="asset/style/index.css">
    <link rel="stylesheet" href="asset/style/product-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary: #007bff;
            --primary-hover: #0056b3;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --border: #dee2e6;
        }

        .selector-container {
            background: var(--light);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .selector-group {
            margin-bottom: 16px;
        }

        .selector-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .selector-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .size-btn {
            padding: 10px 16px;
            border: 2px solid var(--border);
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            min-width: 60px;
            text-align: center;
        }

        .size-btn:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary);
        }

        .size-btn.selected {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .size-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f0f0f0;
            color: #999;
        }

        .color-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid var(--border);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background-size: cover;
            background-position: center;
        }

        .color-btn:hover:not(:disabled) {
            transform: scale(1.1);
            border-color: var(--primary);
        }

        .color-btn.selected::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 24px;
            color: white;
            font-weight: bold;
            text-shadow: 0 0 3px rgba(0, 0, 0, 0.5);
        }

        .color-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .color-label {
            font-size: 12px;
            text-align: center;
            margin-top: 4px;
            color: #666;
        }

        .color-option-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: opacity 0.3s ease;
        }

        .color-option-wrapper.hidden {
            display: none;
        }

        .variant-info {
            background: white;
            border: 1px solid var(--border);
            padding: 12px;
            border-radius: 6px;
            margin-top: 16px;
            display: none;
        }

        .variant-info.show {
            display: block;
        }

        .variant-price {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .variant-stock {
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            font-size: 12px;
            font-weight: 600;
        }

        .stock-available {
            background: var(--success);
            color: white;
        }

        .stock-low {
            background: var(--warning);
            color: white;
        }

        .stock-unavailable {
            background: var(--danger);
            color: white;
        }

        .btn-add-to-cart {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.3s ease;
        }

        .btn-add-to-cart:hover:not(:disabled) {
            background: var(--primary-hover);
        }

        .btn-add-to-cart:disabled {
            background: #999;
            cursor: not-allowed;
        }
    </style>
</head>

<body>

    <!-- NAVIGATION -->
    <nav>
        <div class="logo">
            <a href="index.php">ShoeTakels</a>
        </div>

        <ul class="nav-links" id="navLinks">
            <li><a href="index.php">Home</a></li>
            <li><a href="shop.php">Shop</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>

        <button class="menu-toggle" id="menuToggle">☰</button>

        <div class="nav-right">
            <div class="cart-icon" id="cartIcon">
                🛒
                <?php
                $cart_product_count = 0;
                if (isset($_SESSION['customer_id'])) {
                    $cart_conn = new mysqli($servername, $username, $password, $database);
                    if (!$cart_conn->connect_error) {
                        $cart_stmt = $cart_conn->prepare("SELECT SUM(c.quantity) as total FROM cart c INNER JOIN product_variant v ON c.variant_id = v.variant_id WHERE c.customer_id = ? AND v.product_id = ?");
                        $cart_stmt->bind_param('ii', $_SESSION['customer_id'], $product_id);
                        $cart_stmt->execute();
                        $cart_result = $cart_stmt->get_result();
                        if ($cart_result && ($row = $cart_result->fetch_assoc())) {
                            $cart_product_count = (int)($row['total'] ?? 0);
                        }
                        $cart_stmt->close();
                    }
                    $cart_conn->close();
                }
                if ($cart_product_count > 0) {
                    echo '<span class="cart-badge" id="cartBadge">' . $cart_product_count . '</span>';
                }
                ?>
            </div>
        </div>
    </nav>

    <!-- PRODUCT DETAIL AREA -->
    <main>
        <div class="product-detail-container" style="display:flex; gap:24px; padding:24px;">

            <!-- GALLERY -->
            <div class="product-gallery" style="flex:1; max-width:520px;">
                <?php
                    $firstColor = isset($_GET['color']) && isset($colorToImages[$_GET['color']]) ? $_GET['color'] : array_key_first($colorToImages);
                    $defaultImages = $firstColor ? $colorToImages[$firstColor] : [];
                ?>
                <div class="main-image" style="border:1px solid #eee; padding:12px;">
                    <img id="mainImage"
                         src="<?php echo htmlspecialchars($defaultImages[0] ?? 'upload/product-image/placeholder.png'); ?>"
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         style="width:100%; height:auto;">
                </div>

                <div class="thumbnail-list" id="thumbnailList" style="margin-top:12px;">
                    <?php foreach ($defaultImages as $index => $img): ?>
                        <div class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                             data-image="<?php echo htmlspecialchars($img); ?>">
                            <img src="<?php echo htmlspecialchars($img); ?>"
                                 style="width:64px; height:64px; object-fit:cover; border-radius:4px;">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- PRODUCT INFO -->
            <div class="product-info" style="flex:1;">

                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <?php if ($cart_count > 0): ?>
                    <div style="margin: 8px 0; color: #007bff; font-weight: 600;">
                        You have added <?php echo $cart_count; ?> of this product to your cart.
                    </div>
                <?php endif; ?>

                <div>
                    Brand: <strong><?php echo htmlspecialchars($product['brand_name'] ?? 'Unknown'); ?></strong>
                </div>

                <div style="margin:8px 0; font-size:20px; font-weight:700;">
                    <?php
                    if ($min_price == $max_price) {
                        echo "₱" . number_format($min_price, 2);
                    } else {
                        echo "₱" . number_format($min_price, 2) . " - ₱" . number_format($max_price, 2);
                    }
                    ?>
                </div>

                <p><?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description.')); ?></p>

                <?php if (count($variants) > 0): ?>
                    <div class="selector-container">

                        <?php
                        $sizes = [];
                        $colors = [];

                        foreach ($variants as $v) {
                            $sizes[$v['size']]  = true;
                            $colors[$v['color']] = true;
                        }

                        $sizes  = array_keys($sizes);
                        $colors = array_keys($colors);
                        ?>

                        <!-- SIZE SELECTOR -->
                        <div class="selector-group">
                            <label>Select Size:</label>

                            <div class="selector-options" id="sizeOptions">
                                <?php foreach ($sizes as $size): ?>
                                    <button class="size-btn" data-size="<?php echo htmlspecialchars($size); ?>">
                                        <?php echo htmlspecialchars($size); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- COLOR SELECTOR -->
                        <div class="selector-group">
                            <label>Select Color:</label>

                            <div class="selector-options" id="colorOptions">
                                <?php foreach ($colors as $color): ?>
                                    <div class="color-option-wrapper">
                                        <button class="color-btn"
                                                data-color="<?php echo htmlspecialchars($color); ?>"
                                                title="<?php echo htmlspecialchars($color); ?>"
                                                style="background-color: <?php echo htmlspecialchars($color); ?>;">
                                        </button>

                                        <div class="color-label"><?php echo htmlspecialchars($color); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                      <div class="variant-info" id="variantInfo">
                            <div class="variant-price" id="variantPrice">₱0.00</div>
                            <div class="variant-stock" id="variantStock">Select size and color</div>

                            <div style="margin-top:8px;">
                                <label for="quantity">Quantity:</label>
                                <input type="number"
                                       id="quantity"
                                       value="1"
                                       min="1"
                                       max="10"
                                       style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
                            </div>

                            <button class="btn-add-to-cart" id="addToCartBtn" data-variant-id="">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- BENEFITS -->
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

    <!-- NEWSLETTER -->
    <section class="newsletter">
        <h2>Stay Updated on New Releases</h2>
        <p>Subscribe to get exclusive offers and first access to new collections</p>

        <form class="newsletter-form" onsubmit="return handleSubscribe(event)">
            <input type="email" placeholder="Enter your email" required>
            <button type="submit" class="btn-primary">Subscribe</button>
        </form>
    </section>

    <!-- FOOTER -->
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

    <!-- JAVASCRIPT -->
    <script>
        const variantMap = <?php echo json_encode($variantMap); ?>;
        const colorToImages = <?php echo json_encode($colorToImages); ?>; // Changed to store all images per color
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
            document.getElementById('variantPrice').textContent =
                '₱' + parseFloat(variant.price).toFixed(2);

            // Stock state
            const stock = parseInt(variant.stock) || 0;
            const stockEl = document.getElementById('variantStock');

            if (stock > 10) {
                stockEl.textContent = "In Stock";
                stockEl.className = "variant-stock stock-available";
            } else if (stock > 0) {
                stockEl.textContent = "Low Stock (" + stock + ")";
                stockEl.className = "variant-stock stock-low";
            } else {
                stockEl.textContent = "Out of Stock";
                stockEl.className = "variant-stock stock-unavailable";
            }

            const addBtn = document.getElementById('addToCartBtn');
            addBtn.dataset.variantId = variant.variant_id;
            addBtn.disabled = stock === 0;

            document.getElementById('variantInfo').classList.add('show');
        }

        // Size selector
        document.querySelectorAll('.size-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                selectedSize = this.dataset.size;

                document.querySelectorAll('.size-btn')
                    .forEach(b => b.classList.remove('selected'));

                this.classList.add('selected');

                document.querySelectorAll('.color-btn').forEach(b => {
                    const color = b.dataset.color;
                    b.disabled = !(selectedSize + "|" + color in variantMap);
                });

                updateVariantInfo();
            });
        });

        document.querySelectorAll('.color-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                selectedColor = this.dataset.color;

                document.querySelectorAll('.color-btn')
                    .forEach(b => b.classList.remove('selected'));

                this.classList.add('selected');

                // Update thumbnails to only show selected color's images
                const galleryImages = colorToImages[selectedColor] || [];
                const mainImage = document.getElementById('mainImage');
                const thumbnailList = document.getElementById('thumbnailList');

                // Update main image
                if (galleryImages.length > 0) {
                    mainImage.src = galleryImages[0];
                } else {
                    mainImage.src = 'upload/product-image/placeholder.png';
                }

                // Update thumbnails to only show selected color's images
                let thumbsHtml = '';
                galleryImages.forEach((img, idx) => {
                    thumbsHtml += `<div class=\"thumbnail ${idx === 0 ? 'active' : ''}\" data-image=\"${img}\">` +
                        `<img src=\"${img}\" style=\"width:64px; height:64px; object-fit:cover; border-radius:4px;\">` +
                        `</div>`;
                });
                thumbnailList.innerHTML = thumbsHtml;

                // Add click event to new thumbnails
                thumbnailList.querySelectorAll('.thumbnail').forEach(thumb => {
                    thumb.addEventListener('click', function () {
                        thumbnailList.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                        mainImage.src = this.dataset.image;
                    });
                });

                updateVariantInfo();
            });
        });

        // Add to cart
        document.getElementById('addToCartBtn').addEventListener('click', async function (e) {
            e.preventDefault();

            const variantId = this.dataset.variantId;
            const quantity = parseInt(document.getElementById('quantity').value) || 1;

            if (!variantId) {
                alert("Select size and color");
                return;
            }

            try {
                const res = await fetch("add-to-cart.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body:
                        "variant_id=" + encodeURIComponent(variantId) +
                        "&quantity=" + encodeURIComponent(quantity)
                });

                const data = await res.json();

                if (data.success) {
                    alert("Added to cart!");

                    if (data.cart_count !== undefined) {
                        const badge = document.getElementById("cartBadge");
                        badge.textContent = data.cart_count;
                        badge.style.display = "inline-block";
                    }

                } else {
                    alert("Error: " + (data.message || "Unknown error"));
                }
            } catch (err) {
                console.error(err);
                alert("Failed to add to cart.");
            }
        });

        // Thumbnail click
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.addEventListener('click', function () {
                document.querySelectorAll('.thumbnail')
                    .forEach(t => t.classList.remove('active'));

                this.classList.add('active');

                document.getElementById('mainImage').src = this.dataset.image;
            });
        });

        // Mobile menu
        document.getElementById('menuToggle')?.addEventListener("click", function () {
            document.getElementById("navLinks").classList.toggle("show");
        });

        document.addEventListener('DOMContentLoaded', () => {
            if (initialColor) {
                const colorButton = document.querySelector(
                    `.color-btn[data-color="${CSS.escape(initialColor)}"]`
                );
                if (colorButton && !colorButton.disabled) {
                    colorButton.click();
                    return;
                }
            }
            
            const firstColorButton = document.querySelector('.color-btn:not(:disabled)');
            if (firstColorButton) {
                firstColorButton.click();
            }
        });
    </script>

</body>
</html>
