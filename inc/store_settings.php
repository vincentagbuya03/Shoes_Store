<?php
/**
 * Store Settings Helper
 * Loads store settings from admin configuration
 * Include this file in customer-facing pages to apply admin settings
 */

// Prevent multiple inclusions
if (defined('STORE_SETTINGS_LOADED')) {
    return;
}
define('STORE_SETTINGS_LOADED', true);

// Default store settings
$store_settings = [
    'store_name' => 'ShoeTakels',
    'store_email' => 'admin@shoetakels.com',
    'store_phone' => '+63 912 345 6789',
    'store_address' => 'Manila, Philippines',
    'currency' => 'PHP',
    'currency_symbol' => '₱',
    'customer_theme' => 'default',
    'customer_primary_color' => '#6366f1',
    'customer_secondary_color' => '#8b5cf6',
    'customer_accent_color' => '#c084fc',
    'customer_font' => 'Inter',
    'maintenance_mode' => false,
    'low_stock_threshold' => 10,
    // Customer page specific settings
    'hero_title' => 'Step Into Style and Comfort',
    'hero_subtitle' => 'Discover our exclusive collection of premium footwear designed for every occasion. From athletic performance to everyday elegance, find your perfect pair.',
    'footer_about' => 'Premium footwear for every occasion. Quality meets style in every step you take.',
    'social_facebook' => 'https://facebook.com',
    'social_instagram' => 'https://instagram.com',
    'social_twitter' => 'https://twitter.com',
    'social_tiktok' => 'https://tiktok.com',
    'show_newsletter' => true,
    'announcement_bar' => '',
    'announcement_enabled' => false,
    'free_shipping_min' => 2000,
    'show_brands_menu' => true,
    'show_sale_badge' => true,
    'contact_hours' => 'Mon-Fri: 9AM - 6PM'
];

// Load settings from database if connection exists
if (isset($conn) && $conn) {
    // Get settings from any admin (use admin_id = 1 as primary, or first available)
    $settings_query = "SELECT setting_key, setting_value FROM admin_settings WHERE admin_id = 1 OR admin_id = (SELECT MIN(admin_id) FROM admin_settings) LIMIT 100";
    $result = $conn->query($settings_query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $key = $row['setting_key'];
            $value = $row['setting_value'];
            
            // Convert boolean strings
            if ($value === 'true') $value = true;
            elseif ($value === 'false') $value = false;
            elseif (is_numeric($value) && strpos($value, '.') === false) $value = (int)$value;
            
            $store_settings[$key] = $value;
        }
    }
}

// Check maintenance mode
if ($store_settings['maintenance_mode'] && !isset($_SESSION['admin_id'])) {
    // Allow admin to bypass maintenance mode
    if (!defined('SKIP_MAINTENANCE_CHECK')) {
        http_response_code(503);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Maintenance Mode - <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #f8fafc;
                }
                .maintenance-container {
                    text-align: center;
                    padding: 2rem;
                    max-width: 500px;
                }
                .maintenance-icon {
                    width: 80px;
                    height: 80px;
                    background: rgba(99, 102, 241, 0.1);
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto 1.5rem;
                }
                .maintenance-icon svg {
                    width: 40px;
                    height: 40px;
                    color: #6366f1;
                }
                h1 { font-size: 2rem; margin-bottom: 1rem; }
                p { color: #94a3b8; line-height: 1.6; margin-bottom: 1.5rem; }
                .contact { font-size: 0.9rem; color: #64748b; }
                .contact a { color: #6366f1; text-decoration: none; }
            </style>
        </head>
        <body>
            <div class="maintenance-container">
                <div class="maintenance-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h1>We'll Be Back Soon!</h1>
                <p>We're currently performing scheduled maintenance to improve your shopping experience. Please check back shortly.</p>
                <p class="contact">Questions? Contact us at <a href="mailto:<?php echo htmlspecialchars($store_settings['store_email']); ?>"><?php echo htmlspecialchars($store_settings['store_email']); ?></a></p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/**
 * Helper function to format currency
 */
function format_currency($amount) {
    global $store_settings;
    $symbol = $store_settings['currency_symbol'] ?? '₱';
    return $symbol . number_format((float)$amount, 2);
}

/**
 * Helper function to get store setting
 */
function get_store_setting($key, $default = '') {
    global $store_settings;
    return $store_settings[$key] ?? $default;
}

/**
 * Helper function to get store name
 */
function get_store_name() {
    global $store_settings;
    return $store_settings['store_name'] ?? 'ShoeTakels';
}

/**
 * Generate CSS variables for theme customization
 */
function get_theme_css_vars() {
    global $store_settings;
    
    $primary = $store_settings['accent_color'] ?? $store_settings['primary_color'] ?? '#6366f1';
    $theme = $store_settings['theme'] ?? 'default';
    
    // Define theme color schemes
    $themes = [
        'default' => [
            'bg' => '#ffffff',
            'bg-secondary' => '#f8fafc',
            'text' => '#0f172a',
            'text-secondary' => '#64748b',
            'border' => '#e2e8f0'
        ],
        'dark' => [
            'bg' => '#0f172a',
            'bg-secondary' => '#1e293b',
            'text' => '#f8fafc',
            'text-secondary' => '#94a3b8',
            'border' => '#334155'
        ],
        'light' => [
            'bg' => '#ffffff',
            'bg-secondary' => '#f1f5f9',
            'text' => '#0f172a',
            'text-secondary' => '#475569',
            'border' => '#e2e8f0'
        ],
        'midnight' => [
            'bg' => '#020617',
            'bg-secondary' => '#0f172a',
            'text' => '#f8fafc',
            'text-secondary' => '#94a3b8',
            'border' => '#1e293b'
        ],
        'ocean' => [
            'bg' => '#042f2e',
            'bg-secondary' => '#134e4a',
            'text' => '#f0fdfa',
            'text-secondary' => '#99f6e4',
            'border' => '#115e59'
        ],
        'purple' => [
            'bg' => '#1e1b4b',
            'bg-secondary' => '#312e81',
            'text' => '#f5f3ff',
            'text-secondary' => '#c4b5fd',
            'border' => '#3730a3'
        ]
    ];
    
    $colors = $themes[$theme] ?? $themes['default'];
    
    $css = "<style id=\"store-theme-vars\">
    :root {
        --store-primary: {$primary};
        --store-primary-dark: {$primary};
        --store-bg: {$colors['bg']};
        --store-bg-secondary: {$colors['bg-secondary']};
        --store-text: {$colors['text']};
        --store-text-secondary: {$colors['text-secondary']};
        --store-border: {$colors['border']};
    }
    </style>";
    
    return $css;
}

/**
 * Alias function for getStoreThemeCSS (camelCase version)
 * Generates CSS for customer page theming
 */
function getStoreThemeCSS() {
    global $store_settings;
    
    // Get customer-specific theme settings from admin
    $primary = $store_settings['customer_primary_color'] ?? '#6366f1';
    $secondary = $store_settings['customer_secondary_color'] ?? '#8b5cf6';
    $accent = $store_settings['customer_accent_color'] ?? '#c084fc';
    $theme = $store_settings['customer_theme'] ?? 'default';
    $font = $store_settings['customer_font'] ?? 'Inter';
    
    // Define theme color schemes matching admin settings
    $themes = [
        'default' => [
            'bg' => '#ffffff',
            'bg-secondary' => '#f8fafc',
            'text' => '#0f172a',
            'text-secondary' => '#64748b',
            'border' => '#e2e8f0',
            'nav-bg' => '#ffffff',
            'card-bg' => '#ffffff'
        ],
        'dark' => [
            'bg' => '#0f172a',
            'bg-secondary' => '#1e293b',
            'text' => '#f8fafc',
            'text-secondary' => '#94a3b8',
            'border' => '#334155',
            'nav-bg' => '#0f172a',
            'card-bg' => '#1e293b'
        ],
        'midnight' => [
            'bg' => '#020617',
            'bg-secondary' => '#0f172a',
            'text' => '#f8fafc',
            'text-secondary' => '#94a3b8',
            'border' => '#1e293b',
            'nav-bg' => '#020617',
            'card-bg' => '#0f172a'
        ],
        'ocean' => [
            'bg' => '#042f2e',
            'bg-secondary' => '#134e4a',
            'text' => '#f0fdfa',
            'text-secondary' => '#99f6e4',
            'border' => '#115e59',
            'nav-bg' => '#042f2e',
            'card-bg' => '#134e4a'
        ],
        'purple' => [
            'bg' => '#1e1b4b',
            'bg-secondary' => '#312e81',
            'text' => '#f5f3ff',
            'text-secondary' => '#c4b5fd',
            'border' => '#3730a3',
            'nav-bg' => '#1e1b4b',
            'card-bg' => '#312e81'
        ],
        'forest' => [
            'bg' => '#14532d',
            'bg-secondary' => '#166534',
            'text' => '#f0fdf4',
            'text-secondary' => '#bbf7d0',
            'border' => '#22c55e',
            'nav-bg' => '#14532d',
            'card-bg' => '#166534'
        ],
        'sunset' => [
            'bg' => '#7c2d12',
            'bg-secondary' => '#9a3412',
            'text' => '#fff7ed',
            'text-secondary' => '#fed7aa',
            'border' => '#ea580c',
            'nav-bg' => '#7c2d12',
            'card-bg' => '#9a3412'
        ],
        'coffee' => [
            'bg' => '#292524',
            'bg-secondary' => '#44403c',
            'text' => '#fafaf9',
            'text-secondary' => '#d6d3d1',
            'border' => '#78716c',
            'nav-bg' => '#292524',
            'card-bg' => '#44403c'
        ]
    ];
    
    $colors = $themes[$theme] ?? $themes['default'];
    
    // Font import URLs
    $fontImports = [
        'Inter' => "@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');",
        'Poppins' => "@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');",
        'Roboto' => "@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');",
        'Open Sans' => "@import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap');",
        'Montserrat' => "@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap');",
        'Lato' => "@import url('https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap');",
        'Playfair Display' => "@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&display=swap');",
        'Nunito' => "@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;500;600;700;800&display=swap');",
    ];
    
    $fontImport = $fontImports[$font] ?? $fontImports['Inter'];
    $fontFamily = $font . ", -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
    
    // Calculate primary with transparency for hover effects
    $primaryRgb = hexToRgb($primary);
    $secondaryRgb = hexToRgb($secondary);
    $accentRgb = hexToRgb($accent);
    $primaryLight = "rgba({$primaryRgb['r']}, {$primaryRgb['g']}, {$primaryRgb['b']}, 0.1)";
    $primaryMedium = "rgba({$primaryRgb['r']}, {$primaryRgb['g']}, {$primaryRgb['b']}, 0.2)";
    $secondaryLight = "rgba({$secondaryRgb['r']}, {$secondaryRgb['g']}, {$secondaryRgb['b']}, 0.1)";
    $accentLight = "rgba({$accentRgb['r']}, {$accentRgb['g']}, {$accentRgb['b']}, 0.15)";
    
    $css = "<style id=\"store-customer-theme\">
    {$fontImport}
    :root {
        --store-primary: {$primary};
        --store-primary-light: {$primaryLight};
        --store-primary-medium: {$primaryMedium};
        --store-secondary: {$secondary};
        --store-secondary-light: {$secondaryLight};
        --store-accent: {$accent};
        --store-accent-light: {$accentLight};
        --store-bg: {$colors['bg']};
        --store-bg-secondary: {$colors['bg-secondary']};
        --store-text: {$colors['text']};
        --store-text-secondary: {$colors['text-secondary']};
        --store-border: {$colors['border']};
        --store-nav-bg: {$colors['nav-bg']};
        --store-card-bg: {$colors['card-bg']};
        --store-font: {$fontFamily};
        --store-gradient: linear-gradient(135deg, {$primary}, {$secondary});
        --store-gradient-accent: linear-gradient(135deg, {$secondary}, {$accent});
    }
    
    /* Base Styles */
    body {
        font-family: var(--store-font);
        background-color: var(--store-bg);
        color: var(--store-text);
        transition: background-color 0.3s ease, color 0.3s ease;
    }
    
    /* Smooth transitions for all themed elements */
    *, *::before, *::after {
        transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }
    a, button {
        transition: all 0.3s ease;
    }
    
    /* Navigation */
    nav, .navbar {
        background-color: var(--store-nav-bg) !important;
        border-bottom: 1px solid var(--store-border);
    }
    nav a, .navbar a, .nav-links a {
        color: var(--store-text) !important;
    }
    nav a:hover, .navbar a:hover, .nav-links a:hover {
        color: var(--store-primary) !important;
    }
    .logo a {
        color: var(--store-text) !important;
        font-weight: 700;
    }
    
    /* Buttons - Primary with Gradient */
    .btn-primary, .add-to-cart-btn, button[type='submit'], 
    .checkout-btn, .primary-btn, .cta-btn {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        border: none !important;
        border-radius: 8px;
        padding: 12px 24px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        box-shadow: 0 2px 8px var(--store-primary-medium);
    }
    .btn-primary:hover, .add-to-cart-btn:hover, button[type='submit']:hover,
    .checkout-btn:hover, .primary-btn:hover, .cta-btn:hover {
        background: var(--store-gradient-accent) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px var(--store-primary-medium);
    }
    
    /* Buttons - Secondary/Outline */
    .btn-secondary, .btn-outline, .secondary-btn {
        background: transparent !important;
        color: var(--store-primary) !important;
        border: 2px solid var(--store-primary) !important;
        border-radius: 8px;
        padding: 10px 22px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-secondary:hover, .btn-outline:hover, .secondary-btn:hover {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        border-color: transparent !important;
    }
    
    /* Links */
    a {
        color: var(--store-primary);
        text-decoration: none;
        transition: color 0.2s ease;
    }
    a:hover {
        color: var(--store-secondary);
    }
    
    /* Product Cards - Enhanced */
    .product-card, .card {
        background-color: var(--store-card-bg);
        border: 1px solid var(--store-border);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
    }
    .product-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: var(--store-gradient);
        opacity: 0;
        transition: opacity 0.3s ease;
        z-index: 0;
        border-radius: 16px;
    }
    .product-card:hover, .card:hover {
        border-color: transparent;
        box-shadow: 0 20px 40px var(--store-primary-medium);
        transform: translateY(-8px);
    }
    .product-card:hover::before {
        opacity: 0.05;
    }
    .product-card > *, .card > * {
        position: relative;
        z-index: 1;
    }
    .product-name a, .product-card h3 a {
        color: var(--store-text) !important;
        transition: color 0.2s ease;
    }
    .product-name a:hover, .product-card h3 a:hover {
        color: var(--store-primary) !important;
    }
    .product-price, .price {
        background: var(--store-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        font-size: 1.25rem;
    }
    .old-price, .original-price {
        color: var(--store-text-secondary) !important;
        text-decoration: line-through;
        -webkit-text-fill-color: var(--store-text-secondary);
    }
    
    /* Badges - Enhanced with Gradients */
    .product-badge, .badge {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        box-shadow: 0 2px 8px var(--store-primary-medium);
    }
    .product-badge.hot, .badge-hot {
        background: linear-gradient(135deg, #ef4444, #f97316) !important;
        animation: pulse 2s infinite;
    }
    .product-badge.sale, .badge-sale {
        background: linear-gradient(135deg, var(--store-secondary), var(--store-accent)) !important;
    }
    .product-badge.new, .badge-new {
        background: linear-gradient(135deg, var(--store-accent), var(--store-primary)) !important;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    /* Form Elements */
    input[type='text'], input[type='email'], input[type='password'], 
    input[type='search'], input[type='number'], input[type='tel'],
    textarea, select {
        background: var(--store-bg);
        color: var(--store-text);
        border: 1px solid var(--store-border);
        border-radius: 8px;
        padding: 12px 16px;
        transition: all 0.2s ease;
    }
    input:focus, textarea:focus, select:focus {
        outline: none;
        border-color: var(--store-primary) !important;
        box-shadow: 0 0 0 3px var(--store-primary-light);
    }
    
    /* Cart Icon - Enhanced */
    .cart-icon, .cart-btn {
        color: var(--store-text);
        position: relative;
        transition: color 0.2s ease;
    }
    .cart-icon:hover, .cart-btn:hover {
        color: var(--store-primary);
    }
    .cart-badge {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        font-weight: 600;
        min-width: 20px;
        height: 20px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        box-shadow: 0 2px 8px var(--store-primary-medium);
    }
    
    /* Sections */
    section, .section {
        background-color: var(--store-bg);
    }
    .section-title, h1, h2, h3 {
        color: var(--store-text);
    }
    
    /* Footer - Enhanced */
    footer {
        background: linear-gradient(180deg, var(--store-bg-secondary) 0%, var(--store-bg) 100%) !important;
        color: var(--store-text);
        border-top: 1px solid var(--store-border);
        position: relative;
    }
    footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--store-gradient);
    }
    footer a {
        color: var(--store-text-secondary) !important;
        transition: color 0.2s ease;
    }
    footer a:hover {
        color: var(--store-primary) !important;
    }
    .footer-title, footer h3, footer h4 {
        color: var(--store-text) !important;
        position: relative;
        display: inline-block;
    }
    .footer-title::after, footer h3::after, footer h4::after {
        content: '';
        position: absolute;
        bottom: -6px;
        left: 0;
        width: 30px;
        height: 3px;
        background: var(--store-gradient);
        border-radius: 2px;
    }
    .footer-social a {
        background: var(--store-primary-light);
        color: var(--store-primary) !important;
        border-radius: 50%;
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }
    .footer-social a:hover {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        transform: translateY(-4px) rotate(5deg);
        box-shadow: 0 8px 20px var(--store-primary-medium);
    }
    .footer-bottom {
        border-top: 1px solid var(--store-border);
    }
    
    /* Filters & Sidebar - Enhanced */
    .filter-sidebar, .sidebar {
        background: var(--store-card-bg);
        border: 1px solid var(--store-border);
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    .filter-group-title, .filter-title {
        color: var(--store-text);
        font-weight: 600;
        position: relative;
        padding-left: 12px;
    }
    .filter-group-title::before, .filter-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 20px;
        background: var(--store-gradient);
        border-radius: 2px;
    }
    .size-btn, .filter-btn {
        background: var(--store-bg);
        color: var(--store-text);
        border: 1px solid var(--store-border);
        border-radius: 8px;
        padding: 10px 16px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    .size-btn:hover, .filter-btn:hover {
        border-color: var(--store-primary);
        color: var(--store-primary);
        background: var(--store-primary-light);
    }
    .size-btn.active, .filter-btn.active {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        border-color: transparent !important;
        box-shadow: 0 4px 12px var(--store-primary-medium);
    }
    
    /* Custom Checkbox Styling */
    input[type='checkbox'] {
        appearance: none;
        width: 20px;
        height: 20px;
        border: 2px solid var(--store-border);
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }
    input[type='checkbox']:checked {
        background: var(--store-gradient);
        border-color: transparent;
    }
    input[type='checkbox']:checked::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #ffffff;
        font-size: 12px;
        font-weight: bold;
    }
    
    /* Custom Radio Styling */
    input[type='radio'] {
        appearance: none;
        width: 20px;
        height: 20px;
        border: 2px solid var(--store-border);
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }
    input[type='radio']:checked {
        border-color: var(--store-primary);
    }
    input[type='radio']:checked::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 10px;
        height: 10px;
        background: var(--store-gradient);
        border-radius: 50%;
    }
    
    /* Promo Banner - Enhanced */
    .promo-banner, .announcement-bar {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        position: relative;
        overflow: hidden;
    }
    .promo-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        animation: shine 3s infinite;
    }
    @keyframes shine {
        0% { left: -100%; }
        100% { left: 100%; }
    }
    .promo-banner a, .announcement-bar a {
        color: #ffffff !important;
        text-decoration: underline;
        font-weight: 600;
    }
    .promo-banner strong, .announcement-bar strong {
        color: var(--store-accent) !important;
    }
    
    /* Breadcrumbs */
    .breadcrumb {
        color: var(--store-text-secondary);
    }
    .breadcrumb a {
        color: var(--store-text-secondary);
    }
    .breadcrumb a:hover {
        color: var(--store-primary);
    }
    
    /* Pagination - Enhanced */
    .pagination a, .page-link {
        color: var(--store-text);
        border: 1px solid var(--store-border);
        border-radius: 10px;
        padding: 10px 16px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    .pagination a:hover, .page-link:hover {
        background: var(--store-primary-light);
        border-color: var(--store-primary);
        color: var(--store-primary);
        transform: translateY(-2px);
    }
    .pagination .active, .page-link.active {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        border-color: transparent !important;
        box-shadow: 0 4px 12px var(--store-primary-medium);
    }
    .pagination .prev, .pagination .next {
        font-weight: 600;
    }
    }
    
    /* Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    ::-webkit-scrollbar-track {
        background: var(--store-bg-secondary);
    }
    ::-webkit-scrollbar-thumb {
        background: var(--store-border);
        border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: var(--store-primary);
    }
    
    /* Selection */
    ::selection {
        background: var(--store-primary);
        color: #ffffff;
    }
    
    /* ========== HERO SECTION ========== */
    .hero, .hero-section, .hero-content {
        background: linear-gradient(135deg, var(--store-bg) 0%, var(--store-bg-secondary) 100%) !important;
        position: relative;
    }
    .hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at top right, var(--store-primary-light) 0%, transparent 60%);
        pointer-events: none;
    }
    .hero h1, .hero-title, .hero-section h1 {
        color: var(--store-text) !important;
        background: var(--store-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .hero p, .hero-subtitle, .hero-section p, .hero-description {
        color: var(--store-text-secondary) !important;
    }
    .hero-buttons .btn, .hero .btn, .hero-cta {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 15px var(--store-primary-medium);
    }
    .hero-buttons .btn:hover, .hero .btn:hover {
        background: var(--store-gradient-accent) !important;
        transform: translateY(-3px);
        box-shadow: 0 8px 25px var(--store-primary-medium);
    }
    .hero-buttons .btn-outline, .hero .btn-secondary {
        background: transparent !important;
        border: 2px solid var(--store-primary) !important;
        color: var(--store-primary) !important;
    }
    .hero-buttons .btn-outline:hover, .hero .btn-secondary:hover {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        border-color: transparent !important;
    }
    
    /* ========== SECTION TITLES ========== */
    .section-header, .section-title-wrapper {
        color: var(--store-text);
    }
    .section-header h2, .section-title, .hot-sales h2, 
    .featured-title, .categories-title {
        color: var(--store-text) !important;
        position: relative;
        display: inline-block;
    }
    .section-header h2::after, .section-title::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: var(--store-gradient);
        border-radius: 2px;
    }
    .section-header p, .section-subtitle, .section-description {
        color: var(--store-text-secondary) !important;
    }
    
    /* ========== PRODUCT INFO ========== */
    .product-info, .product-details {
        background: var(--store-card-bg);
        color: var(--store-text);
    }
    .product-info h3, .product-title, .product-name {
        color: var(--store-text) !important;
    }
    .product-brand, .product-category {
        color: var(--store-text-secondary) !important;
    }
    .product-color {
        color: var(--store-text-secondary) !important;
    }
    
    /* ========== HOT SALES / FEATURED ========== */
    .hot-sales, .featured-products, .best-sellers {
        background: var(--store-bg) !important;
    }
    .hot-sales .section-title, .featured-products h2 {
        color: var(--store-text) !important;
    }
    
    /* ========== NEWSLETTER - Enhanced ========== */
    .newsletter, .newsletter-section {
        background: linear-gradient(135deg, var(--store-bg-secondary) 0%, var(--store-bg) 100%) !important;
        border: 1px solid var(--store-border);
        border-radius: 20px;
        position: relative;
        overflow: hidden;
    }
    .newsletter::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle, var(--store-primary-light) 0%, transparent 60%);
        pointer-events: none;
    }
    .newsletter h2, .newsletter h3, .newsletter-title {
        color: var(--store-text) !important;
        position: relative;
    }
    .newsletter p, .newsletter-description {
        color: var(--store-text-secondary) !important;
        position: relative;
    }
    .newsletter input {
        background: var(--store-bg) !important;
        color: var(--store-text) !important;
        border: 2px solid var(--store-border) !important;
        border-radius: 12px;
        padding: 14px 20px;
    }
    .newsletter input:focus {
        border-color: var(--store-primary) !important;
        box-shadow: 0 0 0 4px var(--store-primary-light) !important;
    }
    .newsletter button, .newsletter-btn {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        border-radius: 12px;
        padding: 14px 28px;
        font-weight: 600;
        box-shadow: 0 4px 15px var(--store-primary-medium);
        transition: all 0.3s ease;
    }
    .newsletter button:hover, .newsletter-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px var(--store-primary-medium);
    }
    
    /* ========== CATEGORIES - Enhanced ========== */
    .categories, .category-section {
        background: var(--store-bg) !important;
    }
    .category-card, .category-item {
        background: var(--store-card-bg) !important;
        border: 1px solid var(--store-border);
        border-radius: 16px;
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
    }
    .category-card::before, .category-item::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--store-gradient);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    .category-card:hover, .category-item:hover {
        border-color: var(--store-primary);
        transform: translateY(-6px);
        box-shadow: 0 15px 30px var(--store-primary-light);
    }
    .category-card:hover::before, .category-item:hover::before {
        transform: scaleX(1);
    }
    .category-name, .category-title {
        color: var(--store-text) !important;
        font-weight: 600;
    }
    
    /* ========== BRANDS - Enhanced ========== */
    .brands-section, .brand-list {
        background: var(--store-bg) !important;
    }
    .brand-item, .brand-card {
        background: var(--store-card-bg) !important;
        border: 1px solid var(--store-border);
        border-radius: 16px;
        transition: all 0.4s ease;
        position: relative;
        overflow: hidden;
    }
    .brand-item::after, .brand-card::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: var(--store-gradient);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    .brand-item:hover, .brand-card:hover {
        border-color: var(--store-primary);
        transform: translateY(-6px);
        box-shadow: 0 12px 30px var(--store-primary-light);
    }
    .brand-item:hover::after, .brand-card:hover::after {
        transform: scaleX(1);
    }
    .brand-name, .brand-item-name {
        color: var(--store-text) !important;
        font-weight: 600;
    }
    .brand-mega-menu {
        background: var(--store-card-bg) !important;
        border: 1px solid var(--store-border);
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    }
    .brand-mega-menu .brand-item {
        color: var(--store-text) !important;
    }
    
    /* ========== DROPDOWN MENUS ========== */
    .dropdown-menu, .mega-menu, .brand-dropdown .brand-mega-menu {
        background: var(--store-card-bg) !important;
        border: 1px solid var(--store-border) !important;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    }
    .dropdown-item, .menu-item {
        color: var(--store-text) !important;
    }
    .dropdown-item:hover, .menu-item:hover {
        background: var(--store-primary-light) !important;
        color: var(--store-primary) !important;
    }
    
    /* ========== USER MENU ========== */
    .user-menu, .user-dropdown {
        color: var(--store-text);
    }
    .user-dropdown {
        background: var(--store-card-bg) !important;
        border: 1px solid var(--store-border) !important;
    }
    .user-dropdown a {
        color: var(--store-text) !important;
    }
    .user-dropdown a:hover {
        background: var(--store-primary-light) !important;
        color: var(--store-primary) !important;
    }
    .user-name {
        color: var(--store-text) !important;
    }
    
    /* ========== SEARCH ========== */
    .nav-search input, .search-input {
        background: var(--store-bg-secondary) !important;
        color: var(--store-text) !important;
        border: 1px solid var(--store-border) !important;
    }
    .nav-search input::placeholder, .search-input::placeholder {
        color: var(--store-text-secondary) !important;
    }
    .nav-search input:focus, .search-input:focus {
        border-color: var(--store-primary) !important;
        box-shadow: 0 0 0 3px var(--store-primary-light) !important;
    }
    
    /* ========== TOAST NOTIFICATIONS ========== */
    .toast, .notification {
        background: var(--store-card-bg) !important;
        color: var(--store-text) !important;
        border: 1px solid var(--store-border);
    }
    .toast-success {
        border-left: 4px solid #22c55e !important;
    }
    .toast-error {
        border-left: 4px solid #ef4444 !important;
    }
    
    /* ========== MODALS ========== */
    .modal, .modal-content {
        background: var(--store-card-bg) !important;
        color: var(--store-text) !important;
    }
    .modal-header, .modal-footer {
        border-color: var(--store-border) !important;
    }
    .modal-title {
        color: var(--store-text) !important;
    }
    
    /* ========== TABLES ========== */
    table, .table {
        color: var(--store-text);
    }
    th {
        background: var(--store-bg-secondary) !important;
        color: var(--store-text) !important;
        border-color: var(--store-border) !important;
    }
    td {
        border-color: var(--store-border) !important;
    }
    tr:hover {
        background: var(--store-primary-light) !important;
    }
    
    /* ========== EMPTY STATES ========== */
    .empty-state, .no-products, .empty-cart {
        color: var(--store-text-secondary);
    }
    .empty-state i, .empty-state svg {
        color: var(--store-border);
    }
    
    /* ========== LOADING STATES ========== */
    .loading, .spinner {
        border-color: var(--store-border);
        border-top-color: var(--store-primary);
    }
    
    /* ========== CAROUSEL ========== */
    .carousel-controls button, .carousel-nav button {
        background: var(--store-card-bg) !important;
        color: var(--store-text) !important;
        border: 1px solid var(--store-border);
    }
    .carousel-controls button:hover {
        background: var(--store-primary) !important;
        color: #ffffff !important;
    }
    .carousel-dots .dot {
        background: var(--store-border);
    }
    .carousel-dots .dot.active {
        background: var(--store-primary);
    }
    
    /* ========== ICONS ========== */
    .fa, .fas, .far, .fab, i {
        color: inherit;
    }
    
    /* ========== MISC OVERRIDES ========== */
    hr {
        border-color: var(--store-border);
    }
    blockquote {
        border-left-color: var(--store-primary);
        color: var(--store-text-secondary);
    }
    code, pre {
        background: var(--store-bg-secondary);
        color: var(--store-text);
    }
    
    /* ========== ACCENT HIGHLIGHTS ========== */
    .accent-border {
        border-left: 4px solid var(--store-accent) !important;
    }
    .accent-text {
        color: var(--store-accent) !important;
    }
    .accent-bg {
        background: var(--store-accent) !important;
    }
    
    /* ========== SPECIAL PROMO CARDS ========== */
    .promo-card, .special-offer {
        background: var(--store-gradient) !important;
        color: #ffffff !important;
        border-radius: 20px;
        position: relative;
        overflow: hidden;
    }
    .promo-card::before, .special-offer::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 50%);
    }
    
    /* ========== FEATURE ICONS ========== */
    .feature-icon, .benefit-icon {
        width: 60px;
        height: 60px;
        background: var(--store-gradient) !important;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1.5rem;
        box-shadow: 0 8px 20px var(--store-primary-medium);
    }
    
    /* ========== PRICE TAGS ========== */
    .discount-tag, .sale-tag {
        background: linear-gradient(135deg, var(--store-secondary), var(--store-accent)) !important;
        color: #ffffff !important;
        padding: 4px 12px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    /* ========== DIVIDERS ========== */
    .gradient-divider {
        height: 4px;
        background: var(--store-gradient);
        border-radius: 2px;
        border: none;
    }
    
    /* ========== GLOW EFFECTS ========== */
    .glow-primary {
        box-shadow: 0 0 30px var(--store-primary-medium);
    }
    .glow-accent {
        box-shadow: 0 0 30px rgba(201, 168, 124, 0.4);
    }
    
    /* ========== ANIMATED GRADIENT TEXT ========== */
    .gradient-text, .highlight-text {
        background: var(--store-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    /* ========== SKELETON LOADING ========== */
    .skeleton {
        background: linear-gradient(90deg, 
            var(--store-border) 25%, 
            var(--store-bg-secondary) 50%, 
            var(--store-border) 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
    }
    @keyframes skeleton-loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    /* ========== FLOATING ANIMATION ========== */
    .floating {
        animation: float 6s ease-in-out infinite;
    }
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-20px); }
    }
    
    /* ========== RESPONSIVE NAV ========== */
    @media (max-width: 768px) {
        .nav-links {
            background: var(--store-nav-bg) !important;
            border-top: 1px solid var(--store-border);
        }
        .nav-links.show {
            background: var(--store-card-bg) !important;
        }
    }
    </style>";
    
    return $css;
}

/**
 * Helper function to convert hex to RGB
 */
function hexToRgb($hex) {
    $hex = ltrim($hex, '#');
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2))
    ];
}

/**
 * Helper function to adjust color brightness
 */
function adjustBrightness($hex, $percent) {
    // Remove # if present
    $hex = ltrim($hex, '#');
    
    // Convert to RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Adjust brightness
    $r = max(0, min(255, $r + ($r * $percent / 100)));
    $g = max(0, min(255, $g + ($g * $percent / 100)));
    $b = max(0, min(255, $b + ($b * $percent / 100)));
    
    // Convert back to hex
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

/**
 * Get a single store setting value
 */
function getStoreSetting($key, $default = '') {
    global $store_settings;
    return $store_settings[$key] ?? $default;
}
?>
