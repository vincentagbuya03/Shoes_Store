<?php
require_once 'db_connection.php';
require_once 'inc/store_settings.php';
session_start();

if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit();
}

$customer_id = (int)$_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'] ?? 'Customer';

// Fetch cart count
$cart_count = 0;
$cid = (int)$_SESSION['customer_id'];
$cart_count_query = $conn->prepare('SELECT COUNT(*) as total FROM cart WHERE customer_id = ?');
$cart_count_query->bind_param('i', $cid);
$cart_count_query->execute();
$cart_count_result = $cart_count_query->get_result();
if ($cart_count_result && ($row = $cart_count_result->fetch_assoc())) {
    $cart_count = (int)($row['total'] ?? 0);
}
$cart_count_query->close();

// Fetch customer data
$stmt = $conn->prepare("SELECT customer_id, name, email, phone, address, created_at FROM customer WHERE customer_id = ?");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$stmt->close();

if (!$customer) {
    header('Location: login.php');
    exit();
}

// Handle profile update
$update_message = '';
$update_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($name) || empty($email)) {
        $update_error = 'Name and email are required.';
    } else {
        $update_stmt = $conn->prepare("UPDATE customer SET customer_name = ?, email = ?, phone = ?, address = ? WHERE customer_id = ?");
        $update_stmt->bind_param("ssssi", $name, $email, $phone, $address, $customer_id);
        
        if ($update_stmt->execute()) {
            $update_message = 'Profile updated successfully!';
            $_SESSION['customer_name'] = $name;
            $customer['customer_name'] = $name;
            $customer['email'] = $email;
            $customer['phone'] = $phone;
            $customer['address'] = $address;
        } else {
            $update_error = 'Failed to update profile.';
        }
        $update_stmt->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $update_error = 'All password fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $update_error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $update_error = 'Password must be at least 6 characters.';
    } else {
        $verify_stmt = $conn->prepare("SELECT password FROM customer WHERE customer_id = ?");
        $verify_stmt->bind_param("i", $customer_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $verify_data = $verify_result->fetch_assoc();
        $verify_stmt->close();
        
        if ($verify_data && password_verify($current_password, $verify_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $password_stmt = $conn->prepare("UPDATE customer SET password = ? WHERE customer_id = ?");
            $password_stmt->bind_param("si", $hashed_password, $customer_id);
            
            if ($password_stmt->execute()) {
                $update_message = 'Password changed successfully!';
            } else {
                $update_error = 'Failed to change password.';
            }
            $password_stmt->close();
        } else {
            $update_error = 'Current password is incorrect.';
        }
    }
}

// Fetch order statistics
$order_stats_query = $conn->prepare("SELECT COUNT(*) as total_orders, COALESCE(SUM(total_amount), 0) as total_spent FROM orders WHERE customer_id = ?");
$order_stats_query->bind_param("i", $customer_id);
$order_stats_query->execute();
$order_stats_result = $order_stats_query->get_result();
$order_stats = $order_stats_result->fetch_assoc();
$order_stats_query->close();

// Fetch recent orders
$recent_orders_query = $conn->prepare("SELECT order_id, total_amount, order_date, status FROM orders WHERE customer_id = ? ORDER BY order_date DESC LIMIT 5");
$recent_orders_query->bind_param("i", $customer_id);
$recent_orders_query->execute();
$recent_orders_result = $recent_orders_query->get_result();
$recent_orders = [];
while ($order = $recent_orders_result->fetch_assoc()) {
    $recent_orders[] = $order;
}
$recent_orders_query->close();

// Get initials for avatar
$name_parts = explode(' ', $customer['name']);
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="asset/style/beautiful-ui.css">
    <link rel="stylesheet" href="asset/style/animations.css">
    <script src="asset/script/script.js" defer></script>
    <?php echo getStoreThemeCSS(); ?>
    <style>
        :root {
            --profile-primary: var(--store-primary, #7c3aed);
            --profile-secondary: var(--store-secondary, #6d28d9);
            --profile-accent: #f59e0b;
            --profile-bg: #f1f5f9;
            --profile-card: #ffffff;
            --profile-text: #0f172a;
            --profile-text-muted: #64748b;
            --profile-border: #e2e8f0;
            --profile-success: #10b981;
            --profile-error: #ef4444;
            --profile-gradient: linear-gradient(135deg, var(--profile-primary), var(--profile-secondary));
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--profile-bg);
            min-height: 100vh;
            color: var(--profile-text);
            line-height: 1.6;
        }

        /* ========== NAVIGATION ========== */
        .profile-nav {
            background: var(--profile-card);
            padding: 0.875rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            border-bottom: 1px solid var(--profile-border);
        }

        .nav-logo {
            font-size: 1.5rem;
            font-weight: 800;
            text-decoration: none;
            background: var(--profile-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-logo-sparkle {
            -webkit-text-fill-color: initial;
            animation: sparkle 2s ease-in-out infinite;
        }

        @keyframes sparkle {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.2) rotate(180deg); }
        }

        .nav-center {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background: var(--profile-bg);
            padding: 0.35rem;
            border-radius: 12px;
        }

        .nav-center a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.1rem;
            text-decoration: none;
            color: var(--profile-text-muted);
            font-weight: 500;
            font-size: 0.875rem;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .nav-center a:hover {
            color: var(--profile-primary);
        }

        .nav-center a.active {
            background: var(--profile-card);
            color: var(--profile-primary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-cart {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            background: var(--profile-bg);
            border-radius: 12px;
            color: var(--profile-text-muted);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .nav-cart:hover {
            background: var(--profile-primary);
            color: white;
        }

        .nav-cart-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--profile-accent);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid var(--profile-card);
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.4rem 0.75rem 0.4rem 0.4rem;
            background: var(--profile-bg);
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .nav-user:hover {
            background: var(--profile-border);
        }

        .nav-user-avatar {
            width: 34px;
            height: 34px;
            background: var(--profile-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .nav-user-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--profile-text);
        }

        /* ========== PAGE LAYOUT ========== */
        .profile-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            align-items: start;
        }

        /* ========== SIDEBAR ========== */
        .profile-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            position: sticky;
            top: 90px;
        }

        .user-card {
            background: var(--profile-card);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(0, 0, 0, 0.03);
        }

        .user-card-header {
            background: var(--profile-gradient);
            padding: 2rem 1.5rem;
            text-align: center;
            position: relative;
        }

        .user-card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .user-avatar-lg {
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            font-weight: 700;
            color: var(--profile-primary);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 1;
        }

        .user-name-lg {
            color: white;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            position: relative;
            z-index: 1;
        }

        .user-email-lg {
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.85rem;
            position: relative;
            z-index: 1;
        }

        .user-card-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            border-top: 1px solid var(--profile-border);
        }

        .stat-item {
            text-align: center;
            padding: 1.25rem 0.5rem;
            border-right: 1px solid var(--profile-border);
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--profile-primary);
            display: block;
        }

        .stat-label {
            font-size: 0.7rem;
            color: var(--profile-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 0.125rem;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            background: var(--profile-card);
            border-radius: 16px;
            padding: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            border-radius: 10px;
            text-decoration: none;
            color: var(--profile-text);
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            font-size: 0.9rem;
            font-family: inherit;
            text-align: left;
        }

        .menu-item:hover {
            background: var(--profile-bg);
            color: var(--profile-primary);
        }

        .menu-item.active {
            background: var(--profile-gradient);
            color: white;
        }

        .menu-item i {
            width: 18px;
            font-size: 0.95rem;
            text-align: center;
        }

        .menu-item-badge {
            margin-left: auto;
            background: var(--profile-accent);
            color: white;
            font-size: 0.65rem;
            padding: 0.15rem 0.45rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .menu-item.active .menu-item-badge {
            background: rgba(255, 255, 255, 0.25);
        }

        .menu-divider {
            height: 1px;
            background: var(--profile-border);
            margin: 0.5rem 0;
        }

        .menu-item.danger {
            color: var(--profile-error);
        }

        .menu-item.danger:hover {
            background: rgba(239, 68, 68, 0.08);
        }

        /* ========== MAIN CONTENT ========== */
        .profile-main {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .content-card {
            background: var(--profile-card);
            border-radius: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 4px 12px rgba(0, 0, 0, 0.03);
            display: none;
        }

        .content-card.active {
            display: block;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--profile-border);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: var(--profile-gradient);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .card-header-text h2 {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--profile-text);
        }

        .card-header-text p {
            font-size: 0.85rem;
            color: var(--profile-text-muted);
            margin-top: 0.125rem;
        }

        .card-body {
            padding: 1.75rem 2rem;
        }

        /* ========== ALERTS ========== */
        .alert {
            padding: 0.875rem 1.125rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* ========== FORM STYLES ========== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--profile-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .form-input {
            padding: 0.8rem 1rem;
            border: 2px solid var(--profile-border);
            border-radius: 10px;
            font-size: 0.95rem;
            font-family: inherit;
            transition: all 0.2s ease;
            background: var(--profile-card);
            color: var(--profile-text);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--profile-primary);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .form-hint {
            font-size: 0.75rem;
            color: var(--profile-text-muted);
        }

        .form-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--profile-border);
        }

        /* ========== BUTTONS ========== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            font-size: 0.9rem;
            font-weight: 600;
            font-family: inherit;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--profile-gradient);
            color: white;
            box-shadow: 0 2px 8px rgba(124, 58, 237, 0.25);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }

        .btn-secondary {
            background: var(--profile-bg);
            color: var(--profile-text-muted);
        }

        .btn-secondary:hover {
            background: var(--profile-border);
            color: var(--profile-text);
        }

        /* ========== ORDERS LIST ========== */
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 1rem 1.25rem;
            background: var(--profile-bg);
            border-radius: 12px;
            gap: 1rem;
            transition: all 0.2s ease;
        }

        .order-item:hover {
            transform: translateX(4px);
            background: rgba(124, 58, 237, 0.04);
        }

        .order-icon {
            width: 42px;
            height: 42px;
            background: var(--profile-card);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .order-icon.pending { color: #f59e0b; }
        .order-icon.processing { color: #3b82f6; }
        .order-icon.shipped { color: #8b5cf6; }
        .order-icon.delivered { color: #10b981; }
        .order-icon.cancelled { color: #ef4444; }

        .order-details { flex: 1; }

        .order-id {
            font-weight: 600;
            color: var(--profile-text);
            font-size: 0.95rem;
        }

        .order-date {
            font-size: 0.8rem;
            color: var(--profile-text-muted);
        }

        .order-status {
            padding: 0.3rem 0.65rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .order-status.pending { background: rgba(245, 158, 11, 0.12); color: #d97706; }
        .order-status.processing { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
        .order-status.shipped { background: rgba(139, 92, 246, 0.12); color: #7c3aed; }
        .order-status.delivered { background: rgba(16, 185, 129, 0.12); color: #059669; }
        .order-status.cancelled { background: rgba(239, 68, 68, 0.12); color: #dc2626; }

        .order-amount {
            font-weight: 700;
            color: var(--profile-primary);
            font-size: 1rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
        }

        .empty-icon {
            width: 70px;
            height: 70px;
            background: var(--profile-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 1.75rem;
            color: var(--profile-text-muted);
        }

        .empty-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--profile-text);
            margin-bottom: 0.35rem;
        }

        .empty-text {
            color: var(--profile-text-muted);
            margin-bottom: 1.25rem;
            font-size: 0.9rem;
        }

        /* ========== SECURITY SECTION ========== */
        .security-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            background: var(--profile-bg);
            border-radius: 12px;
            margin-bottom: 0.75rem;
        }

        .security-info {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }

        .security-icon {
            width: 40px;
            height: 40px;
            background: var(--profile-card);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--profile-primary);
            font-size: 1rem;
        }

        .security-title {
            font-weight: 600;
            color: var(--profile-text);
            font-size: 0.95rem;
        }

        .security-desc {
            font-size: 0.8rem;
            color: var(--profile-text-muted);
        }

        .password-form {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--profile-border);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .profile-wrapper {
                grid-template-columns: 260px 1fr;
                padding: 1.5rem;
            }
        }

        @media (max-width: 900px) {
            .profile-wrapper {
                grid-template-columns: 1fr;
            }

            .profile-sidebar {
                position: static;
            }

            .nav-center {
                display: none;
            }
        }

        @media (max-width: 640px) {
            .profile-nav {
                padding: 0.75rem 1rem;
            }

            .profile-wrapper {
                padding: 1rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .card-body {
                padding: 1.25rem;
            }

            .card-header {
                padding: 1.25rem;
            }

            .user-card-stats {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Navigation -->
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
                <a href="brand.php" class="brand-link">Brand</a>
                <div class="brand-mega-menu">
                    <div class="brand-grid">
                        <?php
                            $brand_query = "SELECT brand_id, brand_name, brand_logo 
                                            FROM brand 
                                            ORDER BY brand_name ASC";
                            $brand_result = $conn->query($brand_query);

                            if ($brand_result && $brand_result->num_rows > 0) {
                                while ($brand = $brand_result->fetch_assoc()) {
                                    $brand_name_nav = htmlspecialchars($brand['brand_name'], ENT_QUOTES, 'UTF-8');
                                    $brand_logo = htmlspecialchars($brand['brand_logo'], ENT_QUOTES, 'UTF-8');
                                    $brand_id   = (int)$brand['brand_id'];

                                    if (empty($brand_logo)) {
                                        $brand_logo = "upload/brand/default_logo.png";
                                    }

                                    echo "
                                    <a href='brand.php?id={$brand_id}' class='brand-item'>
                                        <div class='brand-logo-box'>
                                            <img src='{$brand_logo}' alt='{$brand_name_nav} logo'>
                                        </div>
                                        <h4 class='brand-item-name'>{$brand_name_nav}</h4>
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
                <input type="search" name="q" placeholder="Search shoes, brands, categories..." aria-label="Search" />
            </form>
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
                    <a href="#" class="dropdown-item"><i class="fas fa-heart"></i> Wishlist</a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item logout-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="profile-wrapper">
        <!-- Sidebar -->
        <aside class="profile-sidebar">
            <!-- User Card -->
            <div class="user-card">
                <div class="user-card-header">
                    <div class="user-avatar-lg"><?php echo $initials; ?></div>
                    <h2 class="user-name-lg"><?php echo htmlspecialchars($customer['name']); ?></h2>
                    <p class="user-email-lg"><?php echo htmlspecialchars($customer['email']); ?></p>
                </div>
                <div class="user-card-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $order_stats['total_orders']; ?></span>
                        <span class="stat-label">Orders</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">₱<?php echo number_format($order_stats['total_spent'], 0); ?></span>
                        <span class="stat-label">Spent</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo date('Y', strtotime($customer['created_at'])); ?></span>
                        <span class="stat-label">Since</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <div class="sidebar-menu">
                <button class="menu-item active" data-tab="profile">
                    <i class="fas fa-user-pen"></i>
                    Edit Profile
                </button>
                <button class="menu-item" data-tab="orders">
                    <i class="fas fa-box"></i>
                    My Orders
                    <?php if ($order_stats['total_orders'] > 0): ?>
                    <span class="menu-item-badge"><?php echo $order_stats['total_orders']; ?></span>
                    <?php endif; ?>
                </button>
                <button class="menu-item" data-tab="security">
                    <i class="fas fa-shield-halved"></i>
                    Security
                </button>
                <div class="menu-divider"></div>
                <a href="logout.php" class="menu-item danger">
                    <i class="fas fa-right-from-bracket"></i>
                    Sign Out
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="profile-main">
            <!-- Profile Tab -->
            <div class="content-card active" id="profile-content">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-user-pen"></i>
                    </div>
                    <div class="card-header-text">
                        <h2>Personal Information</h2>
                        <p>Manage your account details and contact information</p>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($update_message && !isset($_POST['change_password'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo htmlspecialchars($update_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($update_error && !isset($_POST['change_password'])): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($update_error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-input" 
                                       value="<?php echo htmlspecialchars($customer['name']); ?>" 
                                       placeholder="Enter your full name" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($customer['email']); ?>" 
                                       placeholder="Enter your email" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-input" 
                                       value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" 
                                       placeholder="Enter your phone number">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <input type="text" name="address" class="form-input" 
                                       value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" 
                                       placeholder="Enter your address">
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-rotate-left"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Orders Tab -->
            <div class="content-card" id="orders-content">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="card-header-text">
                        <h2>Recent Orders</h2>
                        <p>View and track your purchase history</p>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($recent_orders) > 0): ?>
                        <div class="orders-list">
                            <?php foreach ($recent_orders as $order): 
                                $status = strtolower($order['status']);
                                $status_icon = match($status) {
                                    'pending' => 'fa-clock',
                                    'processing' => 'fa-spinner fa-spin',
                                    'shipped' => 'fa-truck',
                                    'delivered' => 'fa-circle-check',
                                    'cancelled' => 'fa-circle-xmark',
                                    default => 'fa-box'
                                };
                            ?>
                                <div class="order-item">
                                    <div class="order-icon <?php echo $status; ?>">
                                        <i class="fas <?php echo $status_icon; ?>"></i>
                                    </div>
                                    <div class="order-details">
                                        <div class="order-id">Order #<?php echo $order['order_id']; ?></div>
                                        <div class="order-date"><?php echo date('M d, Y • h:i A', strtotime($order['order_date'])); ?></div>
                                    </div>
                                    <span class="order-status <?php echo $status; ?>"><?php echo ucfirst($status); ?></span>
                                    <div class="order-amount">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-actions">
                            <a href="orders.php" class="btn btn-primary">
                                <i class="fas fa-list"></i> View All Orders
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-bag-shopping"></i>
                            </div>
                            <h3 class="empty-title">No orders yet</h3>
                            <p class="empty-text">Start shopping to see your orders here</p>
                            <a href="shoes.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> Shop Now
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Security Tab -->
            <div class="content-card" id="security-content">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div class="card-header-text">
                        <h2>Security Settings</h2>
                        <p>Manage your password and account security</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="security-item">
                        <div class="security-info">
                            <div class="security-icon">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div>
                                <div class="security-title">Password</div>
                                <div class="security-desc">Change your account password</div>
                            </div>
                        </div>
                        <button class="btn btn-secondary" onclick="togglePasswordForm()">
                            <i class="fas fa-pen"></i> Change
                        </button>
                    </div>

                    <div class="security-item">
                        <div class="security-info">
                            <div class="security-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <div class="security-title">Email Verified</div>
                                <div class="security-desc"><?php echo htmlspecialchars($customer['email']); ?></div>
                            </div>
                        </div>
                        <span style="color: var(--profile-success); font-weight: 600; font-size: 0.85rem;">
                            <i class="fas fa-circle-check"></i> Verified
                        </span>
                    </div>

                    <div class="password-form" id="password-form" style="display: none;">
                        <?php if ($update_message && isset($_POST['change_password'])): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlspecialchars($update_message); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($update_error && isset($_POST['change_password'])): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($update_error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-input" 
                                           placeholder="Enter your current password" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-input" 
                                           placeholder="Enter new password" required>
                                    <span class="form-hint">Minimum 6 characters</span>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" class="form-input" 
                                           placeholder="Confirm new password" required>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Password
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="togglePasswordForm()">
                                    <i class="fas fa-xmark"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.menu-item[data-tab]').forEach(button => {
            button.addEventListener('click', () => {
                const tabName = button.dataset.tab;
                
                // Remove active from all
                document.querySelectorAll('.menu-item[data-tab]').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.content-card').forEach(card => card.classList.remove('active'));
                
                // Add active to current
                button.classList.add('active');
                document.getElementById(tabName + '-content').classList.add('active');
            });
        });

        // Toggle password form
        function togglePasswordForm() {
            const form = document.getElementById('password-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Show password form if there was an error
        <?php if (isset($_POST['change_password'])): ?>
        document.getElementById('password-form').style.display = 'block';
        document.querySelector('[data-tab="security"]').click();
        <?php endif; ?>
    </script>
    <?php include __DIR__ . '/partials/chatbot.php'; ?>
</body>
</html>
