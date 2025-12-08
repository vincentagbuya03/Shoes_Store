<?php
require_once 'db_connection.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: /login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_id = $_SESSION['admin_id'];

// Load current settings from database or use defaults
$settings = [
    // Admin dashboard settings
    'theme' => 'dark',
    'accent_color' => '#6366f1',
    'sidebar_collapsed' => false,
    'notifications_enabled' => true,
    'email_notifications' => true,
    'sound_notifications' => false,
    
    // Store information
    'store_name' => 'ShoeTakels',
    'store_email' => 'admin@shoetakels.com',
    'store_phone' => '+63 912 345 6789',
    'store_address' => 'Manila, Philippines',
    'currency' => 'PHP',
    'currency_symbol' => '₱',
    'items_per_page' => 10,
    'low_stock_threshold' => 10,
    'auto_confirm_orders' => false,
    'maintenance_mode' => false,
    
    // Customer page settings
    'customer_theme' => 'default',
    'customer_primary_color' => '#6366f1',
    'customer_secondary_color' => '#8b5cf6',
    'customer_accent_color' => '#c084fc',
    'customer_font' => 'Inter',
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

// Try to load settings from database
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM admin_settings WHERE admin_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $key = $row['setting_key'];
        $value = $row['setting_value'];
        // Convert boolean strings
        if ($value === 'true') $value = true;
        elseif ($value === 'false') $value = false;
        elseif (is_numeric($value)) $value = (int)$value;
        $settings[$key] = $value;
    }
    $stmt->close();
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_settings') {
        // Collect all settings from POST
        $newSettings = [
            // Admin dashboard settings
            'theme' => $_POST['theme'] ?? 'dark',
            'accent_color' => $_POST['accent_color'] ?? '#6366f1',
            'sidebar_collapsed' => isset($_POST['sidebar_collapsed']) ? 'true' : 'false',
            'notifications_enabled' => isset($_POST['notifications_enabled']) ? 'true' : 'false',
            'email_notifications' => isset($_POST['email_notifications']) ? 'true' : 'false',
            'sound_notifications' => isset($_POST['sound_notifications']) ? 'true' : 'false',
            
            // Store information
            'store_name' => $_POST['store_name'] ?? 'ShoeTakels',
            'store_email' => $_POST['store_email'] ?? '',
            'store_phone' => $_POST['store_phone'] ?? '',
            'store_address' => $_POST['store_address'] ?? '',
            'currency' => $_POST['currency'] ?? 'PHP',
            'currency_symbol' => $_POST['currency_symbol'] ?? '₱',
            'items_per_page' => (int)($_POST['items_per_page'] ?? 10),
            'low_stock_threshold' => (int)($_POST['low_stock_threshold'] ?? 10),
            'auto_confirm_orders' => isset($_POST['auto_confirm_orders']) ? 'true' : 'false',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 'true' : 'false',
            
            // Customer page settings
            'customer_theme' => $_POST['customer_theme'] ?? 'default',
            'customer_primary_color' => $_POST['customer_primary_color'] ?? '#6366f1',
            'customer_secondary_color' => $_POST['customer_secondary_color'] ?? '#8b5cf6',
            'customer_accent_color' => $_POST['customer_accent_color'] ?? '#c084fc',
            'customer_font' => $_POST['customer_font'] ?? 'Inter',
            'hero_title' => $_POST['hero_title'] ?? 'Step Into Style and Comfort',
            'hero_subtitle' => $_POST['hero_subtitle'] ?? '',
            'footer_about' => $_POST['footer_about'] ?? '',
            'social_facebook' => $_POST['social_facebook'] ?? '',
            'social_instagram' => $_POST['social_instagram'] ?? '',
            'social_twitter' => $_POST['social_twitter'] ?? '',
            'social_tiktok' => $_POST['social_tiktok'] ?? '',
            'show_newsletter' => isset($_POST['show_newsletter']) ? 'true' : 'false',
            'announcement_bar' => $_POST['announcement_bar'] ?? '',
            'announcement_enabled' => isset($_POST['announcement_enabled']) ? 'true' : 'false',
            'free_shipping_min' => (int)($_POST['free_shipping_min'] ?? 2000),
            'show_brands_menu' => isset($_POST['show_brands_menu']) ? 'true' : 'false',
            'show_sale_badge' => isset($_POST['show_sale_badge']) ? 'true' : 'false',
            'contact_hours' => $_POST['contact_hours'] ?? 'Mon-Fri: 9AM - 6PM'
        ];
        
        // Save settings to database
        $saveSuccess = true;
        foreach ($newSettings as $key => $value) {
            $stmt = $conn->prepare("
                INSERT INTO admin_settings (admin_id, setting_key, setting_value) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ");
            if ($stmt) {
                $stmt->bind_param("iss", $admin_id, $key, $value);
                if (!$stmt->execute()) {
                    $saveSuccess = false;
                }
                $stmt->close();
            }
        }
        
        if ($saveSuccess) {
            $message = 'Settings saved successfully!';
            $messageType = 'success';
            // Reload settings
            header("Location: settings.php?saved=1");
            exit();
        } else {
            $message = 'Failed to save settings. Please try again.';
            $messageType = 'error';
        }
    }
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($new_password !== $confirm_password) {
            $message = 'New passwords do not match!';
            $messageType = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = 'Password must be at least 6 characters!';
            $messageType = 'error';
        } else {
            // Verify current password and update
            $stmt = $conn->prepare("SELECT password FROM admin WHERE admin_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $admin = $result->fetch_assoc();
                $stmt->close();
                
                if ($admin && password_verify($current_password, $admin['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE admin_id = ?");
                    $stmt->bind_param("si", $hashed_password, $admin_id);
                    if ($stmt->execute()) {
                        $message = 'Password changed successfully!';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to update password!';
                        $messageType = 'error';
                    }
                    $stmt->close();
                } else {
                    $message = 'Current password is incorrect!';
                    $messageType = 'error';
                }
            }
        }
    }
}

if (isset($_GET['saved'])) {
    $message = 'Settings saved successfully!';
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($settings['theme']); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Dashboard</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/admin-dashboard.css">
    <style>
        /* Settings Page Specific Styles */
        :root {
            --accent-color: <?php echo htmlspecialchars($settings['accent_color']); ?>;
        }
        
        /* Theme Variations */
        [data-theme="light"] {
            --bg-dark: #f1f5f9;
            --bg-card: #ffffff;
            --bg-hover: #e2e8f0;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border: #e2e8f0;
        }
        
        [data-theme="midnight"] {
            --bg-dark: #020617;
            --bg-card: #0f172a;
            --bg-hover: #1e293b;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border: #1e293b;
            --primary: #818cf8;
        }
        
        [data-theme="ocean"] {
            --bg-dark: #042f2e;
            --bg-card: #134e4a;
            --bg-hover: #115e59;
            --text-primary: #f0fdfa;
            --text-secondary: #99f6e4;
            --text-muted: #5eead4;
            --border: #115e59;
            --primary: #2dd4bf;
        }
        
        [data-theme="purple"] {
            --bg-dark: #1e1b4b;
            --bg-card: #312e81;
            --bg-hover: #3730a3;
            --text-primary: #f5f3ff;
            --text-secondary: #c4b5fd;
            --text-muted: #a78bfa;
            --border: #3730a3;
            --primary: #a78bfa;
        }
        
        .settings-content {
            padding: 2rem;
            max-width: 1200px;
        }
        
        .settings-header {
            margin-bottom: 2rem;
        }
        
        .settings-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .settings-header p {
            color: var(--text-secondary);
        }
        
        /* Alert Messages */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert.success {
            background: var(--success-light);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }
        
        .alert.error {
            background: var(--error-light);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .alert svg {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        /* Settings Grid */
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
        }
        
        /* Settings Card */
        .settings-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }
        
        .settings-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .settings-card-header .icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }
        
        .settings-card-header .icon svg {
            width: 20px;
            height: 20px;
        }
        
        .settings-card-header h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .settings-card-body {
            padding: 1.5rem;
        }
        
        /* Form Group */
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group:last-child {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="number"],
        .form-group input[type="password"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: all 0.2s ease;
            outline: none;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group small {
            display: block;
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }
        
        /* Theme Selector */
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
        
        .theme-option {
            position: relative;
            cursor: pointer;
        }
        
        .theme-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        
        .theme-preview {
            padding: 1rem;
            border-radius: 12px;
            border: 2px solid var(--border);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .theme-option input:checked + .theme-preview {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .theme-option:hover .theme-preview {
            border-color: var(--text-muted);
        }
        
        .theme-colors {
            display: flex;
            gap: 0.375rem;
        }
        
        .theme-colors span {
            width: 24px;
            height: 24px;
            border-radius: 6px;
        }
        
        .theme-preview .theme-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        /* Dark theme preview colors */
        .theme-dark .theme-colors span:nth-child(1) { background: #0f172a; }
        .theme-dark .theme-colors span:nth-child(2) { background: #1e293b; }
        .theme-dark .theme-colors span:nth-child(3) { background: #6366f1; }
        
        /* Light theme preview colors */
        .theme-light .theme-colors span:nth-child(1) { background: #f1f5f9; }
        .theme-light .theme-colors span:nth-child(2) { background: #ffffff; }
        .theme-light .theme-colors span:nth-child(3) { background: #6366f1; }
        
        /* Midnight theme preview colors */
        .theme-midnight .theme-colors span:nth-child(1) { background: #020617; }
        .theme-midnight .theme-colors span:nth-child(2) { background: #0f172a; }
        .theme-midnight .theme-colors span:nth-child(3) { background: #818cf8; }
        
        /* Ocean theme preview colors */
        .theme-ocean .theme-colors span:nth-child(1) { background: #042f2e; }
        .theme-ocean .theme-colors span:nth-child(2) { background: #134e4a; }
        .theme-ocean .theme-colors span:nth-child(3) { background: #2dd4bf; }
        
        /* Purple theme preview colors */
        .theme-purple .theme-colors span:nth-child(1) { background: #1e1b4b; }
        .theme-purple .theme-colors span:nth-child(2) { background: #312e81; }
        .theme-purple .theme-colors span:nth-child(3) { background: #a78bfa; }
        
        /* Accent Color Picker */
        .color-picker-wrapper {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        
        .color-picker-wrapper input[type="color"] {
            width: 50px;
            height: 40px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            background: transparent;
            padding: 0;
        }
        
        .color-picker-wrapper input[type="color"]::-webkit-color-swatch-wrapper {
            padding: 0;
        }
        
        .color-picker-wrapper input[type="color"]::-webkit-color-swatch {
            border-radius: 6px;
            border: 2px solid var(--border);
        }
        
        .color-presets {
            display: flex;
            gap: 0.5rem;
        }
        
        .color-preset {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .color-preset:hover {
            transform: scale(1.1);
        }
        
        .color-preset.active {
            border-color: var(--text-primary);
            box-shadow: 0 0 0 2px var(--bg-dark);
        }
        
        /* Color Value Display */
        .color-value {
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.85rem;
            color: var(--text-secondary);
            background: var(--bg-dark);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        
        /* Color Schemes Grid */
        .color-schemes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .color-scheme-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: var(--bg-dark);
            border: 2px solid var(--border);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .color-scheme-btn:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .color-scheme-btn.active {
            border-color: var(--accent);
            background: rgba(99, 102, 241, 0.1);
        }
        
        .scheme-preview {
            display: flex;
            gap: 4px;
            width: 100%;
        }
        
        .scheme-preview span {
            flex: 1;
            height: 24px;
            border-radius: 4px;
        }
        
        .scheme-preview span:first-child {
            border-radius: 4px 0 0 4px;
        }
        
        .scheme-preview span:last-child {
            border-radius: 0 4px 4px 0;
        }
        
        .scheme-name {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        /* Color Preview Box */
        .color-preview-box {
            background: var(--bg-dark);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
        }
        
        .preview-logo {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-primary);
        }
        
        .preview-nav {
            display: flex;
            gap: 1rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        .preview-content {
            padding: 1.5rem;
            display: flex;
            justify-content: center;
        }
        
        .preview-card {
            width: 160px;
            background: var(--bg-card);
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--border);
            position: relative;
        }
        
        .preview-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.65rem;
            font-weight: 600;
            color: #fff;
            background: var(--accent);
        }
        
        .preview-product {
            height: 100px;
            background: linear-gradient(135deg, var(--bg-dark), var(--border));
        }
        
        .preview-card .preview-title {
            display: block;
            padding: 0.5rem 0.75rem 0;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .preview-card .preview-price {
            display: block;
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
            font-weight: 700;
        }
        
        .preview-card .preview-btn {
            display: block;
            width: calc(100% - 1.5rem);
            margin: 0.5rem 0.75rem 0.75rem;
            padding: 0.5rem;
            border: none;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        /* Toggle Switch */
        .toggle-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }
        
        .toggle-group:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .toggle-group:first-child {
            padding-top: 0;
        }
        
        .toggle-info {
            flex: 1;
        }
        
        .toggle-info .toggle-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .toggle-info .toggle-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .toggle-switch {
            position: relative;
            width: 48px;
            height: 26px;
            flex-shrink: 0;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: var(--bg-hover);
            border-radius: 26px;
            transition: 0.3s;
        }
        
        .toggle-slider::before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        
        .toggle-switch input:checked + .toggle-slider {
            background: var(--primary);
        }
        
        .toggle-switch input:checked + .toggle-slider::before {
            transform: translateX(22px);
        }
        
        /* Form Row */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        /* Save Button */
        .save-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            font-size: 0.9rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 1.5rem;
        }
        
        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }
        
        .save-btn svg {
            width: 18px;
            height: 18px;
        }
        
        /* Full Width Card */
        .settings-card.full-width {
            grid-column: 1 / -1;
        }
        
        /* Danger Zone */
        .danger-zone {
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .danger-zone .settings-card-header {
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .danger-zone .settings-card-header .icon {
            background: var(--error-light);
            color: var(--error);
        }
        
        .danger-btn {
            background: var(--error);
            color: white;
            border: none;
            padding: 0.625rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .danger-btn:hover {
            background: #dc2626;
        }
        
        /* Responsive */
        @media (max-width: 900px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .theme-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 640px) {
            .settings-content {
                padding: 1rem;
            }
            
            .settings-card-body {
                padding: 1rem;
            }
            
            .color-picker-wrapper {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.644 1.59a.75.75 0 01.712 0l9.75 5.25a.75.75 0 010 1.32l-9.75 5.25a.75.75 0 01-.712 0l-9.75-5.25a.75.75 0 010-1.32l9.75-5.25z" />
                        <path d="M3.265 10.602l7.668 4.129a2.25 2.25 0 002.134 0l7.668-4.13 1.37.739a.75.75 0 010 1.32l-9.75 5.25a.75.75 0 01-.71 0l-9.75-5.25a.75.75 0 010-1.32l1.37-.738z" />
                        <path d="M10.933 19.231l-7.668-4.13-1.37.739a.75.75 0 000 1.32l9.75 5.25c.221.12.489.12.71 0l9.75-5.25a.75.75 0 000-1.32l-1.37-.738-7.668 4.13a2.25 2.25 0 01-2.134-.001z" />
                    </svg>
                    <span><?php echo htmlspecialchars($settings['store_name']); ?></span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="products.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>Products</span>
                </a>
                <a href="orders.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span>Orders</span>
                </a>
                <a href="customers.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Customers</span>
                </a>
                <a href="brands.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span>Brands</span>
                </a>
                <a href="riders.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span>Riders</span>
                </a>
                <a href="analytics.php" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Analytics</span>
                </a>
                <a href="settings.php" class="nav-item active">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Settings</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="admin-logout.php" class="nav-item logout">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
                <div class="header-right">
                    <div class="header-time" id="headerTime"></div>
                    <div class="user-menu">
                        <div class="user-avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                            <span class="user-role">Administrator</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="settings-content">
                <div class="settings-header">
                    <h1>Settings</h1>
                    <p>Customize your admin dashboard and store preferences</p>
                </div>
                
                <?php if ($message): ?>
                    <div class="alert <?php echo $messageType; ?>">
                        <?php if ($messageType === 'success'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="settingsForm">
                    <input type="hidden" name="action" value="save_settings">
                    
                    <div class="settings-grid">
                        <!-- Appearance Settings -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <div class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                    </svg>
                                </div>
                                <h3>Appearance</h3>
                            </div>
                            <div class="settings-card-body">
                                <div class="form-group">
                                    <label>Theme</label>
                                    <div class="theme-grid">
                                        <label class="theme-option">
                                            <input type="radio" name="theme" value="dark" <?php echo $settings['theme'] === 'dark' ? 'checked' : ''; ?>>
                                            <div class="theme-preview theme-dark">
                                                <div class="theme-colors">
                                                    <span></span>
                                                    <span></span>
                                                    <span></span>
                                                </div>
                                                <span class="theme-name">Dark (Default)</span>
                                            </div>
                                        </label>
                                        <label class="theme-option">
                                            <input type="radio" name="theme" value="light" <?php echo $settings['theme'] === 'light' ? 'checked' : ''; ?>>
                                            <div class="theme-preview theme-light">
                                                <div class="theme-colors">
                                                    <span></span>
                                                    <span></span>
                                                    <span></span>
                                                </div>
                                                <span class="theme-name">Light</span>
                                            </div>
                                        </label>
                                        <label class="theme-option">
                                            <input type="radio" name="theme" value="midnight" <?php echo $settings['theme'] === 'midnight' ? 'checked' : ''; ?>>
                                            <div class="theme-preview theme-midnight">
                                                <div class="theme-colors">
                                                    <span></span>
                                                    <span></span>
                                                    <span></span>
                                                </div>
                                                <span class="theme-name">Midnight</span>
                                            </div>
                                        </label>
                                        <label class="theme-option">
                                            <input type="radio" name="theme" value="ocean" <?php echo $settings['theme'] === 'ocean' ? 'checked' : ''; ?>>
                                            <div class="theme-preview theme-ocean">
                                                <div class="theme-colors">
                                                    <span></span>
                                                    <span></span>
                                                    <span></span>
                                                </div>
                                                <span class="theme-name">Ocean</span>
                                            </div>
                                        </label>
                                        <label class="theme-option">
                                            <input type="radio" name="theme" value="purple" <?php echo $settings['theme'] === 'purple' ? 'checked' : ''; ?>>
                                            <div class="theme-preview theme-purple">
                                                <div class="theme-colors">
                                                    <span></span>
                                                    <span></span>
                                                    <span></span>
                                                </div>
                                                <span class="theme-name">Purple</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Accent Color</label>
                                    <div class="color-picker-wrapper">
                                        <input type="color" name="accent_color" id="accentColor" value="<?php echo htmlspecialchars($settings['accent_color']); ?>">
                                        <div class="color-presets">
                                            <button type="button" class="color-preset" style="background: #6366f1;" data-color="#6366f1" title="Indigo"></button>
                                            <button type="button" class="color-preset" style="background: #22c55e;" data-color="#22c55e" title="Green"></button>
                                            <button type="button" class="color-preset" style="background: #f59e0b;" data-color="#f59e0b" title="Amber"></button>
                                            <button type="button" class="color-preset" style="background: #ef4444;" data-color="#ef4444" title="Red"></button>
                                            <button type="button" class="color-preset" style="background: #8b5cf6;" data-color="#8b5cf6" title="Violet"></button>
                                            <button type="button" class="color-preset" style="background: #ec4899;" data-color="#ec4899" title="Pink"></button>
                                            <button type="button" class="color-preset" style="background: #06b6d4;" data-color="#06b6d4" title="Cyan"></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Notification Settings -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <div class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                </div>
                                <h3>Notifications</h3>
                            </div>
                            <div class="settings-card-body">
                                <div class="toggle-group">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Enable Notifications</div>
                                        <div class="toggle-desc">Receive notifications for new orders and updates</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="notifications_enabled" <?php echo $settings['notifications_enabled'] ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="toggle-group">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Email Notifications</div>
                                        <div class="toggle-desc">Get notifications via email</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="email_notifications" <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="toggle-group">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Sound Notifications</div>
                                        <div class="toggle-desc">Play sound for new notifications</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="sound_notifications" <?php echo $settings['sound_notifications'] ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Store Information -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <div class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <h3>Store Information</h3>
                            </div>
                            <div class="settings-card-body">
                                <div class="form-group">
                                    <label>Store Name</label>
                                    <input type="text" name="store_name" value="<?php echo htmlspecialchars($settings['store_name']); ?>" placeholder="Enter store name">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email" name="store_email" value="<?php echo htmlspecialchars($settings['store_email']); ?>" placeholder="admin@example.com">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="tel" name="store_phone" value="<?php echo htmlspecialchars($settings['store_phone']); ?>" placeholder="+63 912 345 6789">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Store Address</label>
                                    <textarea name="store_address" placeholder="Enter store address"><?php echo htmlspecialchars($settings['store_address']); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Currency & Regional -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <div class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3>Currency & Display</h3>
                            </div>
                            <div class="settings-card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Currency</label>
                                        <select name="currency">
                                            <option value="PHP" <?php echo $settings['currency'] === 'PHP' ? 'selected' : ''; ?>>PHP - Philippine Peso</option>
                                            <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                                            <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                                            <option value="GBP" <?php echo $settings['currency'] === 'GBP' ? 'selected' : ''; ?>>GBP - British Pound</option>
                                            <option value="JPY" <?php echo $settings['currency'] === 'JPY' ? 'selected' : ''; ?>>JPY - Japanese Yen</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Currency Symbol</label>
                                        <input type="text" name="currency_symbol" value="<?php echo htmlspecialchars($settings['currency_symbol']); ?>" placeholder="₱" maxlength="5">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Items Per Page</label>
                                        <select name="items_per_page">
                                            <option value="10" <?php echo $settings['items_per_page'] == 10 ? 'selected' : ''; ?>>10</option>
                                            <option value="25" <?php echo $settings['items_per_page'] == 25 ? 'selected' : ''; ?>>25</option>
                                            <option value="50" <?php echo $settings['items_per_page'] == 50 ? 'selected' : ''; ?>>50</option>
                                            <option value="100" <?php echo $settings['items_per_page'] == 100 ? 'selected' : ''; ?>>100</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Low Stock Threshold</label>
                                        <input type="number" name="low_stock_threshold" value="<?php echo (int)$settings['low_stock_threshold']; ?>" min="1" max="100">
                                        <small>Alert when product stock falls below this number</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Settings -->
                        <div class="settings-card full-width">
                            <div class="settings-card-header">
                                <div class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>
                                <h3>Order Management</h3>
                            </div>
                            <div class="settings-card-body">
                                <div class="toggle-group">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Auto-Confirm Orders</div>
                                        <div class="toggle-desc">Automatically confirm orders when payment is received</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="auto_confirm_orders" <?php echo $settings['auto_confirm_orders'] ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="toggle-group">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Maintenance Mode</div>
                                        <div class="toggle-desc">Put store in maintenance mode (customers cannot place orders)</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Page Theme -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <div class="icon" style="background: rgba(34, 197, 94, 0.1); color: #22c55e;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3>Customer Page Theme</h3>
                            </div>
                            <div class="settings-card-body">
                                <div class="form-group">
                                    <label>Customer Site Theme</label>
                                    <select name="customer_theme" id="customerTheme">
                                        <option value="default" <?php echo ($settings['customer_theme'] ?? 'default') === 'default' ? 'selected' : ''; ?>>Default (Light)</option>
                                        <option value="dark" <?php echo ($settings['customer_theme'] ?? '') === 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
                                        <option value="midnight" <?php echo ($settings['customer_theme'] ?? '') === 'midnight' ? 'selected' : ''; ?>>Midnight</option>
                                        <option value="ocean" <?php echo ($settings['customer_theme'] ?? '') === 'ocean' ? 'selected' : ''; ?>>Ocean</option>
                                        <option value="purple" <?php echo ($settings['customer_theme'] ?? '') === 'purple' ? 'selected' : ''; ?>>Purple</option>
                                        <option value="forest" <?php echo ($settings['customer_theme'] ?? '') === 'forest' ? 'selected' : ''; ?>>Forest</option>
                                        <option value="sunset" <?php echo ($settings['customer_theme'] ?? '') === 'sunset' ? 'selected' : ''; ?>>Sunset</option>
                                        <option value="coffee" <?php echo ($settings['customer_theme'] ?? '') === 'coffee' ? 'selected' : ''; ?>>Coffee</option>
                                    </select>
                                    <small>Theme applied to customer-facing pages</small>
                                </div>
                                
                                <!-- Color Scheme Presets -->
                                <div class="form-group">
                                    <label>Quick Color Schemes</label>
                                    <div class="color-schemes-grid">
                                        <button type="button" class="color-scheme-btn" data-primary="#6366f1" data-secondary="#8b5cf6" data-accent="#c084fc" title="Purple Dream">
                                            <span class="scheme-preview">
                                                <span style="background:#6366f1"></span>
                                                <span style="background:#8b5cf6"></span>
                                                <span style="background:#c084fc"></span>
                                            </span>
                                            <span class="scheme-name">Purple Dream</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#059669" data-secondary="#10b981" data-accent="#34d399" title="Fresh Mint">
                                            <span class="scheme-preview">
                                                <span style="background:#059669"></span>
                                                <span style="background:#10b981"></span>
                                                <span style="background:#34d399"></span>
                                            </span>
                                            <span class="scheme-name">Fresh Mint</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#dc2626" data-secondary="#ef4444" data-accent="#f87171" title="Ruby Red">
                                            <span class="scheme-preview">
                                                <span style="background:#dc2626"></span>
                                                <span style="background:#ef4444"></span>
                                                <span style="background:#f87171"></span>
                                            </span>
                                            <span class="scheme-name">Ruby Red</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#0284c7" data-secondary="#0ea5e9" data-accent="#38bdf8" title="Ocean Blue">
                                            <span class="scheme-preview">
                                                <span style="background:#0284c7"></span>
                                                <span style="background:#0ea5e9"></span>
                                                <span style="background:#38bdf8"></span>
                                            </span>
                                            <span class="scheme-name">Ocean Blue</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#d97706" data-secondary="#f59e0b" data-accent="#fbbf24" title="Golden Hour">
                                            <span class="scheme-preview">
                                                <span style="background:#d97706"></span>
                                                <span style="background:#f59e0b"></span>
                                                <span style="background:#fbbf24"></span>
                                            </span>
                                            <span class="scheme-name">Golden Hour</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#db2777" data-secondary="#ec4899" data-accent="#f472b6" title="Pink Blush">
                                            <span class="scheme-preview">
                                                <span style="background:#db2777"></span>
                                                <span style="background:#ec4899"></span>
                                                <span style="background:#f472b6"></span>
                                            </span>
                                            <span class="scheme-name">Pink Blush</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#7c3aed" data-secondary="#a855f7" data-accent="#c084fc" title="Violet Sky">
                                            <span class="scheme-preview">
                                                <span style="background:#7c3aed"></span>
                                                <span style="background:#a855f7"></span>
                                                <span style="background:#c084fc"></span>
                                            </span>
                                            <span class="scheme-name">Violet Sky</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#0d9488" data-secondary="#14b8a6" data-accent="#2dd4bf" title="Teal Wave">
                                            <span class="scheme-preview">
                                                <span style="background:#0d9488"></span>
                                                <span style="background:#14b8a6"></span>
                                                <span style="background:#2dd4bf"></span>
                                            </span>
                                            <span class="scheme-name">Teal Wave</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#1f2937" data-secondary="#374151" data-accent="#6b7280" title="Elegant Gray">
                                            <span class="scheme-preview">
                                                <span style="background:#1f2937"></span>
                                                <span style="background:#374151"></span>
                                                <span style="background:#6b7280"></span>
                                            </span>
                                            <span class="scheme-name">Elegant Gray</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#b45309" data-secondary="#d97706" data-accent="#f59e0b" title="Warm Coffee">
                                            <span class="scheme-preview">
                                                <span style="background:#b45309"></span>
                                                <span style="background:#d97706"></span>
                                                <span style="background:#f59e0b"></span>
                                            </span>
                                            <span class="scheme-name">Warm Coffee</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#be123c" data-secondary="#e11d48" data-accent="#fb7185" title="Rose Garden">
                                            <span class="scheme-preview">
                                                <span style="background:#be123c"></span>
                                                <span style="background:#e11d48"></span>
                                                <span style="background:#fb7185"></span>
                                            </span>
                                            <span class="scheme-name">Rose Garden</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#4338ca" data-secondary="#6366f1" data-accent="#818cf8" title="Royal Indigo">
                                            <span class="scheme-preview">
                                                <span style="background:#4338ca"></span>
                                                <span style="background:#6366f1"></span>
                                                <span style="background:#818cf8"></span>
                                            </span>
                                            <span class="scheme-name">Royal Indigo</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#ea580c" data-secondary="#f97316" data-accent="#fb923c" title="Sunset Orange">
                                            <span class="scheme-preview">
                                                <span style="background:#ea580c"></span>
                                                <span style="background:#f97316"></span>
                                                <span style="background:#fb923c"></span>
                                            </span>
                                            <span class="scheme-name">Sunset Orange</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#16a34a" data-secondary="#22c55e" data-accent="#4ade80" title="Forest Green">
                                            <span class="scheme-preview">
                                                <span style="background:#16a34a"></span>
                                                <span style="background:#22c55e"></span>
                                                <span style="background:#4ade80"></span>
                                            </span>
                                            <span class="scheme-name">Forest Green</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#9333ea" data-secondary="#a855f7" data-accent="#d946ef" title="Neon Purple">
                                            <span class="scheme-preview">
                                                <span style="background:#9333ea"></span>
                                                <span style="background:#a855f7"></span>
                                                <span style="background:#d946ef"></span>
                                            </span>
                                            <span class="scheme-name">Neon Purple</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#0891b2" data-secondary="#06b6d4" data-accent="#22d3ee" title="Aqua Cyan">
                                            <span class="scheme-preview">
                                                <span style="background:#0891b2"></span>
                                                <span style="background:#06b6d4"></span>
                                                <span style="background:#22d3ee"></span>
                                            </span>
                                            <span class="scheme-name">Aqua Cyan</span>
                                        </button>
                                        <button type="button" class="color-scheme-btn" data-primary="#7e22ce" data-secondary="#9333ea" data-accent="#c026d3" title="Electric Violet">
                                            <span class="scheme-preview">
                                                <span style="background:#7e22ce"></span>
                                                <span style="background:#9333ea"></span>
                                                <span style="background:#c026d3"></span>
                                            </span>
                                            <span class="scheme-name">Electric Violet</span>
                                        </button>
                                    </div>
                                    <small>Click to apply a pre-made color combination</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Primary Brand Color</label>
                                    <div class="color-picker-wrapper">
                                        <input type="color" name="customer_primary_color" id="customerPrimaryColor" value="<?php echo htmlspecialchars($settings['customer_primary_color'] ?? '#6366f1'); ?>">
                                        <span class="color-value" id="primaryColorValue"><?php echo htmlspecialchars($settings['customer_primary_color'] ?? '#6366f1'); ?></span>
                                    </div>
                                    <small>Main color for buttons, links, and key elements</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Secondary Color</label>
                                    <div class="color-picker-wrapper">
                                        <input type="color" name="customer_secondary_color" id="customerSecondaryColor" value="<?php echo htmlspecialchars($settings['customer_secondary_color'] ?? '#8b5cf6'); ?>">
                                        <span class="color-value" id="secondaryColorValue"><?php echo htmlspecialchars($settings['customer_secondary_color'] ?? '#8b5cf6'); ?></span>
                                    </div>
                                    <small>Used for hover states and gradients</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Accent Color</label>
                                    <div class="color-picker-wrapper">
                                        <input type="color" name="customer_accent_color" id="customerAccentColor" value="<?php echo htmlspecialchars($settings['customer_accent_color'] ?? '#c084fc'); ?>">
                                        <span class="color-value" id="accentColorValue"><?php echo htmlspecialchars($settings['customer_accent_color'] ?? '#c084fc'); ?></span>
                                    </div>
                                    <small>Highlights, badges, and special elements</small>
                                </div>
                                
                                <!-- Live Preview -->
                                <div class="form-group">
                                    <label>Live Preview</label>
                                    <div class="color-preview-box" id="colorPreviewBox">
                                        <div class="preview-header">
                                            <span class="preview-logo">Your Store</span>
                                            <div class="preview-nav">
                                                <span>Home</span>
                                                <span>Shop</span>
                                                <span>About</span>
                                            </div>
                                        </div>
                                        <div class="preview-content">
                                            <div class="preview-card">
                                                <div class="preview-badge">New</div>
                                                <div class="preview-product"></div>
                                                <span class="preview-title">Product Name</span>
                                                <span class="preview-price">₱2,999</span>
                                                <button class="preview-btn">Add to Cart</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Store Font</label>
                                    <select name="customer_font">
                                        <option value="Inter" <?php echo ($settings['customer_font'] ?? 'Inter') === 'Inter' ? 'selected' : ''; ?>>Inter (Modern)</option>
                                        <option value="Poppins" <?php echo ($settings['customer_font'] ?? '') === 'Poppins' ? 'selected' : ''; ?>>Poppins (Friendly)</option>
                                        <option value="Roboto" <?php echo ($settings['customer_font'] ?? '') === 'Roboto' ? 'selected' : ''; ?>>Roboto (Clean)</option>
                                        <option value="Open Sans" <?php echo ($settings['customer_font'] ?? '') === 'Open Sans' ? 'selected' : ''; ?>>Open Sans (Readable)</option>
                                        <option value="Montserrat" <?php echo ($settings['customer_font'] ?? '') === 'Montserrat' ? 'selected' : ''; ?>>Montserrat (Bold)</option>
                                        <option value="Lato" <?php echo ($settings['customer_font'] ?? '') === 'Lato' ? 'selected' : ''; ?>>Lato (Professional)</option>
                                        <option value="Playfair Display" <?php echo ($settings['customer_font'] ?? '') === 'Playfair Display' ? 'selected' : ''; ?>>Playfair Display (Elegant)</option>
                                        <option value="Nunito" <?php echo ($settings['customer_font'] ?? '') === 'Nunito' ? 'selected' : ''; ?>>Nunito (Rounded)</option>
                                    </select>
                                    <small>Font used across all customer-facing pages</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hero Section Settings -->
                        <div class="settings-card full-width">
                            <div class="settings-card-header">
                                <div class="icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <h3>Homepage Hero Section</h3>
                            </div>
                            <div class="settings-card-body">
                                <div class="form-group">
                                    <label>Hero Title</label>
                                    <input type="text" name="hero_title" value="<?php echo htmlspecialchars($settings['hero_title'] ?? 'Step Into Style and Comfort'); ?>" placeholder="Main headline text">
                                </div>
                                
                                <div class="form-group">
                                    <label>Hero Subtitle</label>
                                    <textarea name="hero_subtitle" placeholder="Supporting text under the title" rows="3"><?php echo htmlspecialchars($settings['hero_subtitle'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="toggle-group">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Announcement Bar</div>
                                        <div class="toggle-desc">Show announcement banner at top of site</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="announcement_enabled" <?php echo ($settings['announcement_enabled'] ?? false) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="form-group">
                                    <label>Announcement Text</label>
                                    <input type="text" name="announcement_bar" value="<?php echo htmlspecialchars($settings['announcement_bar'] ?? ''); ?>" placeholder="e.g., Free shipping on orders over ₱2,000!">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Social Media & Footer -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <div class="icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                                <h3>Social Media Links</h3>
                            </div>
                            <div class="settings-card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Facebook URL</label>
                                        <input type="url" name="social_facebook" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>" placeholder="https://facebook.com/yourpage">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Instagram URL</label>
                                        <input type="url" name="social_instagram" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>" placeholder="https://instagram.com/yourpage">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Twitter/X URL</label>
                                        <input type="url" name="social_twitter" value="<?php echo htmlspecialchars($settings['social_twitter'] ?? ''); ?>" placeholder="https://twitter.com/yourpage">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>TikTok URL</label>
                                        <input type="url" name="social_tiktok" value="<?php echo htmlspecialchars($settings['social_tiktok'] ?? ''); ?>" placeholder="https://tiktok.com/@yourpage">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer & Display Options -->
                        <div class="settings-card">
                            <div class="settings-card-header">
                                <div class="icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3>Footer & Display</h3>
                            </div>
                            <div class="settings-card-body">
                                <div class="form-group">
                                    <label>Footer About Text</label>
                                    <textarea name="footer_about" placeholder="Brief description about your store" rows="2"><?php echo htmlspecialchars($settings['footer_about'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Contact Hours</label>
                                    <input type="text" name="contact_hours" value="<?php echo htmlspecialchars($settings['contact_hours'] ?? 'Mon-Fri: 9AM - 6PM'); ?>" placeholder="Mon-Fri: 9AM - 6PM">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Free Shipping Minimum (<?php echo htmlspecialchars($settings['currency_symbol']); ?>)</label>
                                        <input type="number" name="free_shipping_min" value="<?php echo (int)($settings['free_shipping_min'] ?? 2000); ?>" min="0" step="100">
                                    </div>
                                </div>
                                
                                <div class="toggle-group">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Show Newsletter Signup</div>
                                        <div class="toggle-desc">Display newsletter subscription form in footer</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="show_newsletter" <?php echo ($settings['show_newsletter'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="toggle-group">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Show Brands Dropdown</div>
                                        <div class="toggle-desc">Display brands mega menu in navigation</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="show_brands_menu" <?php echo ($settings['show_brands_menu'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="toggle-group">
                                    <div class="toggle-info">
                                        <div class="toggle-label">Show Sale Badges</div>
                                        <div class="toggle-desc">Display sale/discount badges on products</div>
                                    </div>
                                    <label class="toggle-switch">
                                        <input type="checkbox" name="show_sale_badge" <?php echo ($settings['show_sale_badge'] ?? true) ? 'checked' : ''; ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="save-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Settings
                    </button>
                </form>
                
                <!-- Security Settings - Separate Form -->
                <div class="settings-grid" style="margin-top: 2rem;">
                    <div class="settings-card">
                        <div class="settings-card-header">
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3>Security</h3>
                        </div>
                        <div class="settings-card-body">
                            <form method="POST" id="passwordForm">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" required minlength="6">
                                    <small>Minimum 6 characters</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" required minlength="6">
                                </div>
                                
                                <button type="submit" class="save-btn" style="margin-top: 1rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                    Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Danger Zone -->
                    <div class="settings-card danger-zone">
                        <div class="settings-card-header">
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3>Danger Zone</h3>
                        </div>
                        <div class="settings-card-body">
                            <div class="toggle-group">
                                <div class="toggle-info">
                                    <div class="toggle-label">Clear Cache</div>
                                    <div class="toggle-desc">Clear all cached data and temporary files</div>
                                </div>
                                <button type="button" class="danger-btn" onclick="clearCache()">Clear Cache</button>
                            </div>
                            
                            <div class="toggle-group">
                                <div class="toggle-info">
                                    <div class="toggle-label">Reset Settings</div>
                                    <div class="toggle-desc">Reset all settings to default values</div>
                                </div>
                                <button type="button" class="danger-btn" onclick="resetSettings()">Reset</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');

        mobileMenuToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('mobile-open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });

        // Header clock
        function updateHeaderTime() {
            const el = document.getElementById('headerTime');
            if (!el) return;
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const dateStr = now.toLocaleDateString([], { year: 'numeric', month: 'short', day: 'numeric' });
            el.textContent = `${timeStr} • ${dateStr}`;
        }
        updateHeaderTime();
        setInterval(updateHeaderTime, 1000);

        // Theme preview - live update
        document.querySelectorAll('input[name="theme"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.documentElement.setAttribute('data-theme', this.value);
            });
        });

        // Color presets for admin theme
        const colorPresets = document.querySelectorAll('.color-preset:not(.customer-preset)');
        const accentColorInput = document.getElementById('accentColor');

        colorPresets.forEach(preset => {
            preset.addEventListener('click', function() {
                const color = this.dataset.color;
                accentColorInput.value = color;
                document.documentElement.style.setProperty('--accent-color', color);
                document.documentElement.style.setProperty('--primary', color);
                
                // Update active state
                colorPresets.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
            });
        });

        accentColorInput.addEventListener('input', function() {
            document.documentElement.style.setProperty('--accent-color', this.value);
            document.documentElement.style.setProperty('--primary', this.value);
            colorPresets.forEach(p => p.classList.remove('active'));
        });

        // Set initial active color preset
        const currentColor = accentColorInput.value;
        colorPresets.forEach(preset => {
            if (preset.dataset.color.toLowerCase() === currentColor.toLowerCase()) {
                preset.classList.add('active');
            }
        });
        
        // Color scheme and preview functionality
        const primaryColorInput = document.getElementById('customerPrimaryColor');
        const secondaryColorInput = document.getElementById('customerSecondaryColor');
        const accentColorInput2 = document.getElementById('customerAccentColor');
        const primaryValueDisplay = document.getElementById('primaryColorValue');
        const secondaryValueDisplay = document.getElementById('secondaryColorValue');
        const accentValueDisplay = document.getElementById('accentColorValue');
        const colorSchemeBtns = document.querySelectorAll('.color-scheme-btn');
        
        // Update preview function
        function updateColorPreview() {
            const primary = primaryColorInput?.value || '#6366f1';
            const secondary = secondaryColorInput?.value || '#8b5cf6';
            const accent = accentColorInput2?.value || '#c084fc';
            
            // Update value displays
            if (primaryValueDisplay) primaryValueDisplay.textContent = primary;
            if (secondaryValueDisplay) secondaryValueDisplay.textContent = secondary;
            if (accentValueDisplay) accentValueDisplay.textContent = accent;
            
            // Update preview box
            const previewBox = document.getElementById('colorPreviewBox');
            if (previewBox) {
                const previewBtn = previewBox.querySelector('.preview-btn');
                const previewPrice = previewBox.querySelector('.preview-price');
                const previewBadge = previewBox.querySelector('.preview-badge');
                
                if (previewBtn) {
                    previewBtn.style.background = `linear-gradient(135deg, ${primary}, ${secondary})`;
                }
                if (previewPrice) {
                    previewPrice.style.color = primary;
                }
                if (previewBadge) {
                    previewBadge.style.background = accent;
                }
            }
        }
        
        // Color scheme buttons
        colorSchemeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const primary = this.dataset.primary;
                const secondary = this.dataset.secondary;
                const accent = this.dataset.accent;
                
                if (primaryColorInput) primaryColorInput.value = primary;
                if (secondaryColorInput) secondaryColorInput.value = secondary;
                if (accentColorInput2) accentColorInput2.value = accent;
                
                // Update active state
                colorSchemeBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                updateColorPreview();
            });
        });
        
        // Listen for color input changes
        if (primaryColorInput) {
            primaryColorInput.addEventListener('input', updateColorPreview);
        }
        if (secondaryColorInput) {
            secondaryColorInput.addEventListener('input', updateColorPreview);
        }
        if (accentColorInput2) {
            accentColorInput2.addEventListener('input', updateColorPreview);
        }
        
        // Initial preview update
        updateColorPreview();
        
        // Set initial active color scheme if matching
        function setActiveColorScheme() {
            const currentPrimary = primaryColorInput?.value?.toLowerCase();
            colorSchemeBtns.forEach(btn => {
                if (btn.dataset.primary.toLowerCase() === currentPrimary) {
                    btn.classList.add('active');
                }
            });
        }
        setActiveColorScheme();

        // Clear cache function
        function clearCache() {
            if (confirm('Are you sure you want to clear all cached data?')) {
                // You can add actual cache clearing logic here
                alert('Cache cleared successfully!');
            }
        }

        // Reset settings function
        function resetSettings() {
            if (confirm('Are you sure you want to reset all settings to default? This cannot be undone.')) {
                // Reset theme to dark
                document.documentElement.setAttribute('data-theme', 'dark');
                document.querySelector('input[name="theme"][value="dark"]').checked = true;
                
                // Reset accent color
                accentColorInput.value = '#6366f1';
                document.documentElement.style.setProperty('--primary', '#6366f1');
                
                // Reset customer colors
                if (primaryColorInput) primaryColorInput.value = '#6366f1';
                if (secondaryColorInput) secondaryColorInput.value = '#8b5cf6';
                if (accentColorInput2) accentColorInput2.value = '#c084fc';
                
                updateColorPreview();
                
                alert('Settings have been reset to defaults. Save to apply changes.');
            }
        }

        // Form validation
        document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
            const newPass = this.querySelector('input[name="new_password"]').value;
            const confirmPass = this.querySelector('input[name="confirm_password"]').value;
            
            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
    </script>
</body>
</html>
