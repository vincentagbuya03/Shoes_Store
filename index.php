<?php
require_once 'db_connection.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShoeTakels</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/index.css">
    <link rel="stylesheet" href="asset/style/product.css">
    <link rel="stylesheet" href="asset/style/carousel.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <script src="asset/script/script.js"></script>
    
</head>
<body>
    <div class="toast-container" id="toast-container"></div>
    <nav>
        <div class="logo">
            <a href="index.php">ShoeTakels</a>
        </div>
        
        <button class="menu-toggle" id="menu-toggle">☰</button>
        <ul class="nav-links" id="nav-links">
            <li><a href="12_12.php">12.12 Sale</a></li>
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php">Best Seller</a></li>
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
            <li><a href="index.php">Find Our Store</a></li>
        </ul>
        <div class="nav-right">
            <div class="cart-icon" id="cart-icon" title="View Cart">
                🛒
                <span class="cart-badge" id="cart-badge" style="display: none;">0</span>
            </div>
            <a href="login.php" class="user-icon" title="Sign In" aria-label="Sign in">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
            </a>
        </div>
    </nav>
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
            <div class="carousel-container">
                <div class="carousel-wrapper">
                    <?php
                        $carousel_query = "
                            SELECT carousel_id, image_url, title
                            FROM hero_carousel
                            WHERE is_active = 1
                            ORDER BY sort_order ASC
                            LIMIT 5
                        ";
                        
                        $carousel_result = $conn->query($carousel_query);
                        $carousel_images = [];
                        
                        if ($carousel_result && $carousel_result->num_rows > 0) {
                            while ($img = $carousel_result->fetch_assoc()) {
                                $carousel_images[] = $img;
                            }
                        }
                        
                        if (empty($carousel_images)) {
                            $carousel_images[] = [
                                'image_url' => 'upload/picture/575966072_1558927741910787_5270800588714805217_n.png',
                                'carousel_id' => 0,
                                'title' => 'Featured'
                            ];
                        }
                        
                        foreach ($carousel_images as $index => $slide) {
                            $img_url = htmlspecialchars($slide['image_url'], ENT_QUOTES, 'UTF-8');
                            $title = htmlspecialchars($slide['title'] ?? 'Carousel Image', ENT_QUOTES, 'UTF-8');
                            $active_class = $index === 0 ? 'active' : '';
                            
                            echo "
                            <div class='carousel-slide {$active_class}' data-index='{$index}'>
                                <img src='{$img_url}' alt='{$title}' class='hero-img'>
                            </div>
                            ";
                        }
                    ?>
                </div>
            </div>
        </div>
    </section>

    <section class="categories">
        <h2 class="section-title">Shop By Category</h2>
        <p class="section-subtitle">Find the perfect pair for every family member</p>

        <div class="category-grid">
            <a href="shop.php?category=Men" class="category-card">
                <div class="category-image">
                    <img src="upload\category\men.jpg" alt="Men's Shoes">
                </div>
                <div class="category-title">Men</div>
            </a>

            <a href="shop.php?category=Women" class="category-card">
                <div class="category-image">
                    <img src="upload\category\women.jpg" alt="Women's Shoes">
                </div>
                <div class="category-title">Women</div>
            </a>
            <a href="shop.php?category=Kids" class="category-card">
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
                    si.image_url
                FROM Product s
                LEFT JOIN Brand b ON s.brand_id = b.brand_id
                LEFT JOIN product_image si ON s.product_id = si.product_id AND si.sort_order = 1
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

                        <!-- Added data-product-id and onclick handler for AJAX -->
                        <button class='btn-primary add-to-cart-btn' data-product-id='{$pid}' data-product-name='{$name}' onclick='addToCartAjax(event, this)' style='width: 100%;'>Add to Cart</button>
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
                    si.image_url
                FROM Product s
                LEFT JOIN Brand b ON s.brand_id = b.brand_id
                LEFT JOIN product_image si ON s.product_id = si.product_id AND si.sort_order = 1
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
                    
                    echo "
                    <div class='product-card'>
                        <div class='product-image'>
                            <a href='product.php?id={$pid}' title='View $name'>
                                <img src='$img' alt='$name' class='product-thumb' loading='lazy'>
                            </a>
                        </div>
                        <h3 class='product-name'><a href='product.php?id={$pid}'>$name</a></h3>
                        <div class='product-brand'>$brand</div>

                        <!-- Added data-product-id and onclick handler for AJAX -->
                        <button class='btn-primary add-to-cart-btn' data-product-id='{$pid}' data-product-name='{$name}' onclick='addToCartAjax(event, this)' style='width: 100%;'>Add to Cart</button>
                    </div>
                    ";
                }
            } else {
                echo "<p>No featured products available</p>";
            }
        ?>
    </div>
</section>
    <section class="benefits">
        <div class="benefit-item">
            <h3>Free Shipping</h3>
            <p>On orders over ₱50. Fast and reliable delivery to your doorstep.</p>
        </div>
        <div class="benefit-item">
            <h3>Easy Returns</h3>
            <p>30-day hassle-free returns if you'rade not completely satisfied.</p>
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

    <script>
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
                void badge.offsetWidth; // Trigger reflow
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
                    const response = await fetch('add-to-cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `product_id=${productId}`
                    });

                    const data = await response.json();

                    if (data.success) {
                        updateCartBadge(data.cart_count);
                        showToast(
                            'Added to Cart',
                            `${data.product.name} has been added (Total items: ${data.cart_count})`
                        );
                    } else {
                        showToast('Error', data.message, true);
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

        let currentSlide = 0;
        let carousel_slides = document.querySelectorAll('.carousel-slide');
        const AUTO_SLIDE_INTERVAL = 4000;
        let autoSlideTimer;

        function showSlide(n) {
            if (carousel_slides.length === 0) return;
            
            if (n >= carousel_slides.length) {
                currentSlide = 0;
            } else if (n < 0) {
                currentSlide = carousel_slides.length - 1;
            } else {
                currentSlide = n;
            }

            carousel_slides.forEach(slide => slide.classList.remove('active'));
            carousel_slides[currentSlide].classList.add('active');

            clearTimeout(autoSlideTimer);
            autoSlideTimer = setTimeout(() => {
                showSlide(currentSlide + 1);
            }, AUTO_SLIDE_INTERVAL);
        }

        if (carousel_slides.length > 0) {
            autoSlideTimer = setTimeout(() => {
                showSlide(currentSlide + 1);
            }, AUTO_SLIDE_INTERVAL);
        }

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
