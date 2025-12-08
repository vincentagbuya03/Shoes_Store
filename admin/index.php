<?php
require_once 'db_connection.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Fetch dashboard statistics
$total_products = 0;
$total_customers = 0;
$total_orders = 0;
$total_revenue = 0;
$total_brands = 0;

// Get total products
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM product");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $total_products = $row['count'];
    }
    $stmt->close();
}

// Get total customers
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM customer");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $total_customers = $row['count'];
    }
    $stmt->close();
}

// Get total orders
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $total_orders = $row['count'];
    }
    $stmt->close();
}

// Get total revenue
$stmt = $conn->prepare("SELECT SUM(total_amount) as revenue FROM orders WHERE status != 'cancelled'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $total_revenue = $row['revenue'] ?? 0;
    }
    $stmt->close();
}

// Get total brands
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM brand");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $total_brands = $row['count'];
    }
    $stmt->close();
}

// Get recent orders
$recent_orders = [];
$stmt = $conn->prepare("
    SELECT o.order_id, o.total_amount, o.status, o.order_date, c.name as customer_name
    FROM orders o
    LEFT JOIN customer c ON o.customer_id = c.customer_id
    ORDER BY o.order_date DESC
    LIMIT 5
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
    $stmt->close();
}

// Get top products
$top_products = [];
$stmt = $conn->prepare(
    "SELECT 
        p.product_id,
        p.name,
        COALESCE(MIN(pv.price), 0) AS price,
        b.brand_name,
        COALESCE(NULLIF(MIN(pi.image_url), ''), MIN(pv.image_url), 'upload/product-image/placeholder.png') AS image_url
    FROM product p
    LEFT JOIN brand b ON p.brand_id = b.brand_id
    LEFT JOIN product_variant pv ON p.product_id = pv.product_id
    LEFT JOIN product_color_image pi ON p.product_id = pi.product_id AND pi.sort_order = 1
    GROUP BY p.product_id, p.name, b.brand_name
    ORDER BY p.created_at DESC
    LIMIT 5"
);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $top_products[] = $row;
    }
    $stmt->close();
}
$monthly_sales = [
    'Jan' => 12500,
    'Feb' => 18200,
    'Mar' => 15800,
    'Apr' => 22100,
    'May' => 19500,
    'Jun' => 25300,
    'Jul' => 28900,
    'Aug' => 24600,
    'Sep' => 31200,
    'Oct' => 27800,
    'Nov' => 35400,
    'Dec' => 42000
];

$category_data = [];
$stmt = $conn->prepare("SELECT category, COUNT(*) as count FROM product GROUP BY category");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $category_data[$row['category']] = $row['count'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ShoeTakels</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/admin-dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <span>ShoeTakels</span>
                </div>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item active">
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
                <a href="settings.php" class="nav-item">
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
                        <div class="header-time" id="headerTime" aria-live="polite" title="Current time"></div>
                    <div class="notification-container">
                        <button class="notification-btn" id="notificationBtn" aria-haspopup="true" aria-expanded="false" title="Notifications">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="badge" id="notificationBadge" style="display:none">0</span>
                        </button>

                        <div class="notification-dropdown" id="notificationDropdown">
                            <!-- Header -->
                            <div class="notification-header">
                                <div class="notification-header-left">
                                    <h4>Notifications</h4>
                                    <span class="notification-count-badge" id="notificationCountBadge">0</span>
                                </div>
                                <button class="mark-all-btn" id="markAllRead">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Mark all read
                                </button>
                            </div>
                            
                            <!-- Notification List -->
                            <div class="notification-list" id="notificationList">
                                <div class="notification-loading">
                                    <div class="notification-loader"></div>
                                    <span>Loading notifications...</span>
                                </div>
                            </div>
                            
                            <!-- Footer -->
                            <div class="notification-footer">
                                <a href="/admin/refunds.php">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Manage Refunds & Settings
                                </a>
                            </div>
                        </div>
                    </div>
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

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <div class="page-title">
                    <h1>Dashboard Overview</h1>
                    <p>Welcome back! Here's what's happening with your store today.</p>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon products">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Products</span>
                            <span class="stat-value"><?php echo number_format($total_products); ?></span>
                            <span class="stat-change positive">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                +12%
                            </span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon orders">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Orders</span>
                            <span class="stat-value"><?php echo number_format($total_orders); ?></span>
                            <span class="stat-change positive">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                +8%
                            </span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon customers">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Customers</span>
                            <span class="stat-value"><?php echo number_format($total_customers); ?></span>
                            <span class="stat-change positive">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                +24%
                            </span>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon revenue">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-label">Total Revenue</span>
                            <span class="stat-value">₱<?php echo number_format($total_revenue, 2); ?></span>
                            <span class="stat-change positive">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                +18%
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-grid">
                    <div class="chart-card large">
                        <div class="card-header">
                            <h3>Sales Overview</h3>
                            <div class="card-actions">
                                <select class="period-select">
                                    <option value="yearly">This Year</option>
                                    <option value="monthly">This Month</option>
                                    <option value="weekly">This Week</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="card-header">
                            <h3>Category Distribution</h3>
                        </div>
                        <div class="chart-container doughnut">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Bottom Section -->
                <div class="bottom-grid">
                    <!-- Recent Orders -->
                    <div class="card orders-card">
                        <div class="card-header">
                            <h3>Recent Orders</h3>
                            <a href="#" class="view-all">View All</a>
                        </div>
                        <div class="orders-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_orders)): ?>
                                        <tr>
                                            <td colspan="4" class="no-data">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                                </svg>
                                                No orders yet
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                                                <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo htmlspecialchars($order['status']); ?>">
                                                        <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top Products -->
                    <div class="card products-card">
                        <div class="card-header">
                            <h3>Top Products</h3>
                            <a href="#" class="view-all">View All</a>
                        </div>
                        <div class="products-list">
                            <?php if (empty($top_products)): ?>
                                <div class="no-data">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                    No products yet
                                </div>
                            <?php else: ?>
                                <?php foreach ($top_products as $product): ?>
                                    <div class="product-item">
                                        <div class="product-image">
                                            <?php
                                                // Normalize image path: replace backslashes, ensure correct admin-relative path
                                                $rawImg = $product['image_url'] ?? '';
                                                $rawImg = str_replace('\\', '/', $rawImg);
                                                if ($rawImg === '') {
                                                $rawImg = 'upload/product-image/placeholder.png';
                                                }
                                                // If it's not an absolute URL or root-relative, prefix ../ because we're in /admin/
                                                if (!preg_match('#^(https?://|/)#i', $rawImg)) {
                                                $rawImg = '../' . ltrim($rawImg, '/');
                                                }
                                                $imgEsc = htmlspecialchars($rawImg, ENT_QUOTES, 'UTF-8');
                                                ?>
                                                <img src="<?php echo $imgEsc; ?>" alt="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                        <div class="product-info">
                                            <span class="product-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                            <span class="product-brand"><?php echo htmlspecialchars($product['brand_name'] ?? 'No Brand'); ?></span>
                                        </div>
                                        <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');

        sidebarToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });

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

        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($monthly_sales)); ?>,
                datasets: [{
                    label: 'Sales (₱)',
                    data: <?php echo json_encode(array_values($monthly_sales)); ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#94a3b8',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(148, 163, 184, 0.1)'
                        },
                        ticks: {
                            color: '#94a3b8',
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryData = <?php echo json_encode($category_data); ?>;
        const categoryLabels = Object.keys(categoryData).length > 0 ? Object.keys(categoryData) : ['Male', 'Female', 'Unisex', 'Kids'];
        const categoryValues = Object.keys(categoryData).length > 0 ? Object.values(categoryData) : [40, 30, 20, 10];

        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    backgroundColor: [
                        '#6366f1',
                        '#22c55e',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6'
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#94a3b8',
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#94a3b8',
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
        });

        // Header clock — updates every second
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
        
        // ===== MODERN NOTIFICATION SYSTEM =====
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationBadge = document.getElementById('notificationBadge');
        const notificationCountBadge = document.getElementById('notificationCountBadge');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationList = document.getElementById('notificationList');
        const markAllBtn = document.getElementById('markAllRead');

        // Escape HTML helper
        function escapeHtml(s) { 
            if (s === null || s === undefined) return ''; 
            return String(s).replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','\'':'&#39;','"':'&quot;'}[c])); 
        }

        // Format relative time
        function formatRelativeTime(dateStr) {
            const date = new Date(dateStr);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            
            if (diff < 60) return 'Just now';
            if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
            if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }

        // Get icon for notification type
        function getNotificationIcon(type) {
            const icons = {
                order: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>`,
                refund: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>`,
                customer: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>`,
                alert: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>`,
                message: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>`,
                system: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>`
            };
            return icons[type] || icons.system;
        }

        // Fetch notification count
        async function fetchNotificationCount() {
            try {
                const res = await fetch('api/notifications_api.php?action=count');
                const data = await res.json();
                if (!data.success) return;
                const n = parseInt(data.unread || 0, 10);
                
                // Update badge on button
                if (n > 0) { 
                    notificationBadge.style.display = 'flex'; 
                    notificationBadge.textContent = n > 99 ? '99+' : n; 
                } else { 
                    notificationBadge.style.display = 'none'; 
                }
                
                // Update count badge in header
                if (notificationCountBadge) {
                    notificationCountBadge.textContent = n;
                    notificationCountBadge.style.display = n > 0 ? 'inline-block' : 'none';
                }
            } catch (err) {
                console.error('Failed to fetch notification count', err);
            }
        }

        // Show skeleton loading
        function showSkeletonLoading() {
            const skeletonHTML = Array(3).fill().map(() => `
                <div class="notification-skeleton">
                    <div class="skeleton skeleton-icon"></div>
                    <div class="skeleton-content">
                        <div class="skeleton skeleton-title"></div>
                        <div class="skeleton skeleton-body"></div>
                        <div class="skeleton skeleton-time"></div>
                    </div>
                </div>
            `).join('');
            notificationList.innerHTML = skeletonHTML;
        }

        // Fetch notification list
        async function fetchNotificationList() {
            try {
                showSkeletonLoading();
                const res = await fetch('api/notifications_api.php?action=list&limit=50');
                const data = await res.json();
                if (!data.success) { 
                    notificationList.innerHTML = `
                        <div class="notification-empty">
                            <div class="notification-empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h5>Failed to load</h5>
                            <p>Please try again later</p>
                        </div>
                    `;
                    return; 
                }
                renderNotifications(data.notifications || []);
            } catch (err) {
                console.error('Failed to fetch notifications', err);
                notificationList.innerHTML = `
                    <div class="notification-empty">
                        <div class="notification-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h5>Connection Error</h5>
                        <p>Unable to load notifications</p>
                    </div>
                `;
            }
        }

        // Render notifications
        function renderNotifications(items) {
            if (!items.length) {
                notificationList.innerHTML = `
                    <div class="notification-empty">
                        <div class="notification-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <h5>All caught up!</h5>
                        <p>No new notifications at the moment</p>
                    </div>
                `;
                return;
            }

            const html = items.map(it => {
                const isUnread = it.is_read != 1;
                const notifType = it.type || 'system';
                const level = it.level || 'info';
                
                return `
                    <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${it.id}" data-url="${escapeHtml(it.url || '#')}">
                        <div class="notification-icon ${notifType}">
                            ${getNotificationIcon(notifType)}
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">${escapeHtml(it.title)}</div>
                            <div class="notification-body">${escapeHtml(it.body || '')}</div>
                            <div class="notification-meta">
                                <span class="notification-time">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    ${formatRelativeTime(it.created_at)}
                                </span>
                                ${level !== 'info' ? `<span class="notification-level ${level}">${level}</span>` : ''}
                            </div>
                        </div>
                        <div class="notification-actions">
                            <button class="notification-action-btn mark-read-btn" data-id="${it.id}" title="Mark as read">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
            
            notificationList.innerHTML = html;

            // Attach click handlers for notification items
            notificationList.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    if (!e.target.closest('.notification-action-btn')) {
                        const url = item.dataset.url;
                        if (url && url !== '#') {
                            window.location.href = url;
                        }
                    }
                });
            });

            // Attach handlers for mark read buttons
            notificationList.querySelectorAll('.mark-read-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const id = btn.dataset.id;
                    const item = btn.closest('.notification-item');
                    
                    try {
                        await fetch('api/notifications_api.php?action=mark_read', { 
                            method: 'POST', 
                            headers: {'Content-Type':'application/x-www-form-urlencoded'}, 
                            body: 'id=' + encodeURIComponent(id) 
                        });
                        
                        // Animate removal
                        item.style.transition = 'all 0.3s ease';
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(20px)';
                        
                        setTimeout(() => {
                            item.classList.remove('unread');
                            item.style.opacity = '1';
                            item.style.transform = 'translateX(0)';
                        }, 300);
                        
                        fetchNotificationCount();
                    } catch (err) { 
                        console.error(err); 
                    }
                });
            });
        }

        // Toggle dropdown
        notificationBtn?.addEventListener('click', async (e) => {
            e.stopPropagation();
            const isOpen = notificationDropdown.classList.contains('active');
            
            if (isOpen) {
                notificationDropdown.classList.remove('active');
                notificationBtn.setAttribute('aria-expanded', 'false');
            } else {
                notificationDropdown.classList.add('active');
                notificationBtn.setAttribute('aria-expanded', 'true');
                await fetchNotificationList();
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!notificationDropdown?.contains(e.target) && !notificationBtn?.contains(e.target)) {
                notificationDropdown?.classList.remove('active');
                notificationBtn?.setAttribute('aria-expanded', 'false');
            }
        });

        // Mark all as read
        markAllBtn?.addEventListener('click', async () => {
            try {
                markAllBtn.disabled = true;
                markAllBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="animation: notificationSpin 0.8s linear infinite;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Processing...
                `;
                
                await fetch('api/notifications_api.php?action=mark_read', { method: 'POST' });
                fetchNotificationCount();
                await fetchNotificationList();
                
                markAllBtn.disabled = false;
                markAllBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Mark all read
                `;
            } catch (err) { 
                console.error(err);
                markAllBtn.disabled = false;
                markAllBtn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Mark all read
                `;
            }
        });

        // Fetch initial count and poll every 30s
        fetchNotificationCount();
        setInterval(fetchNotificationCount, 30000);
        
    </script>
    <!-- Chat widget removed -->
</body>
</html>
