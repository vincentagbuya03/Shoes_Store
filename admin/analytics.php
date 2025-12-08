<?php
require_once 'db_connection.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Fetch analytics data
// Revenue by month (last 6 months)
$revenueByMonth = [];
$stmt = $conn->prepare("
    SELECT DATE_FORMAT(order_date, '%Y-%m') as month, 
           SUM(total_amount) as revenue,
           COUNT(*) as order_count
    FROM orders 
    WHERE status != 'cancelled' 
    AND order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(order_date, '%Y-%m')
    ORDER BY month ASC
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $revenueByMonth[] = $row;
    }
    $stmt->close();
}

// Top selling products
$topProducts = [];
$stmt = $conn->prepare("
    SELECT p.product_id, p.name, b.brand_name, 
           SUM(oi.quantity) as total_sold,
           SUM(oi.price * oi.quantity) as total_revenue
    FROM order_items oi
    JOIN product_variant pv ON oi.variant_id = pv.variant_id
    JOIN product p ON pv.product_id = p.product_id
    LEFT JOIN brand b ON p.brand_id = b.brand_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.status != 'cancelled'
    GROUP BY p.product_id, p.name, b.brand_name
    ORDER BY total_sold DESC
    LIMIT 10
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $topProducts[] = $row;
    }
    $stmt->close();
}

// Orders by status
$ordersByStatus = [];
$stmt = $conn->prepare("
    SELECT status, COUNT(*) as count
    FROM orders
    GROUP BY status
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $ordersByStatus[$row['status']] = $row['count'];
    }
    $stmt->close();
}

// Revenue by brand
$revenueByBrand = [];
$stmt = $conn->prepare("
    SELECT b.brand_name, SUM(oi.price * oi.quantity) as revenue
    FROM order_items oi
    JOIN product_variant pv ON oi.variant_id = pv.variant_id
    JOIN product p ON pv.product_id = p.product_id
    LEFT JOIN brand b ON p.brand_id = b.brand_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.status != 'cancelled'
    GROUP BY b.brand_id, b.brand_name
    ORDER BY revenue DESC
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $revenueByBrand[] = $row;
    }
    $stmt->close();
}

// Today's stats
$todayStats = ['orders' => 0, 'revenue' => 0, 'customers' => 0];
$stmt = $conn->prepare("
    SELECT COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue
    FROM orders
    WHERE DATE(order_date) = CURDATE() AND status != 'cancelled'
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $todayStats['orders'] = $row['orders'];
        $todayStats['revenue'] = $row['revenue'];
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM customer WHERE DATE(created_at) = CURDATE()");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $todayStats['customers'] = $row['count'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Analytics - ShoeTakels Admin</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/admin-dashboard.css">
    <link rel="stylesheet" href="asset/style/products.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            padding: 1.5rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 0.5rem 0;
        }
        
        .page-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin: 0;
        }
        
        /* Stat Cards Grid */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        
        @media (max-width: 1200px) {
            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 600px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .stat-card .stat-icon svg {
            width: 24px;
            height: 24px;
        }
        
        .stat-card .stat-icon.orders {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }
        
        .stat-card .stat-icon.revenue {
            background: rgba(34, 197, 94, 0.15);
            color: #22c55e;
        }
        
        .stat-card .stat-icon.customers {
            background: rgba(168, 85, 247, 0.15);
            color: #a855f7;
        }
        
        .stat-card .stat-icon.pending {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }
        
        .stat-card .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }
        
        .stat-card .stat-change {
            font-size: 0.8rem;
            margin-top: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .stat-change.positive { color: var(--success); }
        .stat-change.negative { color: var(--error); }
        
        /* Analytics Grid */
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        @media (max-width: 1024px) {
            .analytics-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .analytics-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            transition: var(--transition);
        }
        
        .analytics-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.1);
        }
        
        .analytics-card h3 {
            margin: 0 0 1.25rem 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .analytics-card h3 svg {
            width: 20px;
            height: 20px;
            color: var(--primary);
        }
        
        .chart-container {
            position: relative;
            height: 280px;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .full-width .chart-container {
            height: 320px;
        }
        
        /* Top Products List */
        .top-products-list {
            list-style: none;
            padding: 0;
            margin: 0;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .top-products-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .top-products-list::-webkit-scrollbar-track {
            background: var(--bg-dark);
            border-radius: 3px;
        }
        
        .top-products-list::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 3px;
        }
        
        .top-products-list li {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid transparent;
            transition: var(--transition);
        }
        
        .top-products-list li:hover {
            background: rgba(99, 102, 241, 0.08);
            border-color: rgba(99, 102, 241, 0.2);
        }
        
        .top-products-list li:last-child {
            margin-bottom: 0;
        }
        
        .product-rank {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .top-products-list li:nth-child(1) .product-rank {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }
        
        .top-products-list li:nth-child(2) .product-rank {
            background: linear-gradient(135deg, #94a3b8, #64748b);
        }
        
        .top-products-list li:nth-child(3) .product-rank {
            background: linear-gradient(135deg, #cd7f32, #a0522d);
        }
        
        .product-info {
            flex: 1;
            min-width: 0;
        }
        
        .product-info .name {
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.25rem;
        }
        
        .product-info .brand {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        
        .product-stats {
            text-align: right;
            flex-shrink: 0;
            margin-left: 1rem;
        }
        
        .product-stats .sold {
            font-weight: 600;
            color: var(--success);
            font-size: 0.95rem;
        }
        
        .product-stats .revenue {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-secondary);
        }
        
        .empty-state svg {
            width: 48px;
            height: 48px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php
            $current = basename($_SERVER['PHP_SELF'] ?? '');
            function nav_active(array $names, $current) {
                return in_array($current, $names) ? 'nav-item active' : 'nav-item';
            }
        ?>
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
                <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <nav class="sidebar-nav" role="navigation" aria-label="Main navigation">
                <a href="index.php" class="<?php echo nav_active(['index.php','dashboard.php'], $current); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="products.php" class="<?php echo nav_active(['products.php'], $current); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span>Products</span>
                </a>

                <a href="orders.php" class="<?php echo nav_active(['orders.php'], $current); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span>Orders</span>
                </a>

                <a href="customers.php" class="<?php echo nav_active(['customers.php'], $current); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span>Customers</span>
                </a>

                <a href="brands.php" class="<?php echo nav_active(['brands.php'], $current); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span>Brands</span>
                </a>

                <a href="riders.php" class="<?php echo nav_active(['riders.php'], $current); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    <span>Riders</span>
                </a>

                <a href="analytics.php" class="<?php echo nav_active(['analytics.php'], $current); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Analytics</span>
                </a>

                <a href="settings.php" class="<?php echo nav_active(['settings.php'], $current); ?>">
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
            <?php $notification_count = $_SESSION['admin_notifications'] ?? 0; ?>
            <header class="header" role="banner">
                <div class="header-left">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open menu">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 style="margin:0;font-size:1.5rem;">Analytics</h1>
                </div>
                <div class="header-right">
                    <div class="user-menu" id="userMenu">
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

            <div class="products-panel">
                <div class="analytics-container">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h2>Analytics Dashboard</h2>
                        <p>Track your store performance and sales metrics</p>
                    </div>
                    
                    <!-- Today's Stats -->
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-icon orders">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div class="stat-label">Today's Orders</div>
                            <div class="stat-value"><?php echo number_format($todayStats['orders']); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon revenue">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="stat-label">Today's Revenue</div>
                            <div class="stat-value">₱<?php echo number_format($todayStats['revenue'], 2); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon customers">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                </svg>
                            </div>
                            <div class="stat-label">New Customers</div>
                            <div class="stat-value"><?php echo number_format($todayStats['customers']); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon pending">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="stat-label">Pending Orders</div>
                            <div class="stat-value"><?php echo number_format($ordersByStatus['pending'] ?? 0); ?></div>
                        </div>
                    </div>

                    <div class="analytics-grid">
                        <!-- Revenue Chart -->
                        <div class="analytics-card full-width">
                            <h3>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                </svg>
                                Revenue Overview (Last 6 Months)
                            </h3>
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>

                        <!-- Orders by Status -->
                        <div class="analytics-card">
                            <h3>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                </svg>
                                Orders by Status
                            </h3>
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>

                        <!-- Revenue by Brand -->
                        <div class="analytics-card">
                            <h3>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Revenue by Brand
                            </h3>
                            <div class="chart-container">
                                <canvas id="brandChart"></canvas>
                            </div>
                        </div>

                        <!-- Top Selling Products -->
                        <div class="analytics-card full-width">
                            <h3>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                Top Selling Products
                            </h3>
                            <?php if (!empty($topProducts)): ?>
                            <ul class="top-products-list">
                                <?php foreach ($topProducts as $i => $product): ?>
                                <li>
                                    <span class="product-rank"><?php echo $i + 1; ?></span>
                                    <div class="product-info">
                                        <div class="name"><?php echo htmlspecialchars($product['name']); ?></div>
                                        <div class="brand"><?php echo htmlspecialchars($product['brand_name'] ?? 'No Brand'); ?></div>
                                    </div>
                                    <div class="product-stats">
                                        <div class="sold"><?php echo number_format($product['total_sold']); ?> sold</div>
                                        <div class="revenue">₱<?php echo number_format($product['total_revenue'], 2); ?></div>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <div class="empty-state">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p>No sales data available yet</p>
                            </div>
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

        sidebarToggle?.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
        mobileMenuToggle?.addEventListener('click', () => sidebar.classList.toggle('mobile-open'));

        // Chart.js configuration for dark theme
        Chart.defaults.color = '#94a3b8';
        Chart.defaults.borderColor = 'rgba(148, 163, 184, 0.1)';
        Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";

        // Revenue Chart
        const revenueData = <?php echo json_encode($revenueByMonth); ?>;
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: revenueData.map(d => {
                    const [year, month] = d.month.split('-');
                    return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: 'Revenue (₱)',
                    data: revenueData.map(d => d.revenue),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.15)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#1e293b',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }, {
                    label: 'Orders',
                    data: revenueData.map(d => d.order_count),
                    borderColor: '#22c55e',
                    backgroundColor: 'transparent',
                    yAxisID: 'y1',
                    tension: 0.4,
                    borderWidth: 3,
                    pointBackgroundColor: '#22c55e',
                    pointBorderColor: '#1e293b',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: { 
                    legend: { 
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#94a3b8',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            padding: 10
                        }
                    },
                    y: { 
                        beginAtZero: true, 
                        title: { 
                            display: true, 
                            text: 'Revenue (₱)',
                            color: '#94a3b8'
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.08)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    },
                    y1: { 
                        beginAtZero: true, 
                        position: 'right', 
                        grid: { drawOnChartArea: false }, 
                        title: { 
                            display: true, 
                            text: 'Orders',
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });

        // Orders by Status Chart
        const statusData = <?php echo json_encode($ordersByStatus); ?>;
        const statusColors = {
            'pending': '#f59e0b',
            'processing': '#3b82f6',
            'shipped': '#8b5cf6',
            'delivered': '#22c55e',
            'completed': '#10b981',
            'cancelled': '#ef4444'
        };
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: Object.keys(statusData).map(s => statusColors[s] || '#6b7280'),
                    borderColor: '#1e293b',
                    borderWidth: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: { 
                    legend: { 
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            generateLabels: function(chart) {
                                const data = chart.data;
                                if (data.labels.length && data.datasets.length) {
                                    return data.labels.map((label, i) => ({
                                        text: `${label} (${data.datasets[0].data[i]})`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        strokeStyle: data.datasets[0].backgroundColor[i],
                                        lineWidth: 0,
                                        pointStyle: 'circle',
                                        hidden: false,
                                        index: i
                                    }));
                                }
                                return [];
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#94a3b8',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
        });

        // Revenue by Brand Chart
        const brandData = <?php echo json_encode($revenueByBrand); ?>;
        const brandColors = ['#6366f1', '#8b5cf6', '#a855f7', '#d946ef', '#ec4899', '#f43f5e', '#f97316', '#eab308'];
        new Chart(document.getElementById('brandChart'), {
            type: 'bar',
            data: {
                labels: brandData.map(b => b.brand_name || 'Unknown'),
                datasets: [{
                    label: 'Revenue',
                    data: brandData.map(b => b.revenue),
                    backgroundColor: brandData.map((_, i) => brandColors[i % brandColors.length]),
                    borderColor: brandData.map((_, i) => brandColors[i % brandColors.length]),
                    borderWidth: 0,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#94a3b8',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString();
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
                            padding: 10
                        }
                    },
                    y: { 
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.08)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    } 
                }
            }
        });
    </script>
</body>
</html>
