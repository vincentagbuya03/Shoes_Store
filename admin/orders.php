<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$admin_name = $_SESSION['admin_name'] ?? 'Admin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <title>Orders - ShoeTakels Admin</title>
    <link rel="stylesheet" href="asset/style/admin-dashboard.css">
    <link rel="stylesheet" href="asset/style/admin-orders.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            <?php
                $notification_count = $_SESSION['admin_notifications'] ?? 3;
            ?>
            <header class="header" role="banner">
                <div class="header-left">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open menu" title="Open menu">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                <div class="header-right">
                    <button class="icon-btn notification-btn" id="notificationBtn" aria-haspopup="true" aria-expanded="false" title="Notifications">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <?php if (!empty($notification_count) && (int)$notification_count > 0): ?>
                            <span class="badge" id="notificationBadge"><?php echo (int)$notification_count; ?></span>
                        <?php endif; ?>
                    </button>

                    <div class="user-menu" id="userMenu" aria-haspopup="true" aria-expanded="false">
                        <div class="user-avatar" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="user-info" id="userMenuButton" role="button" tabindex="0" aria-label="User menu">
                            <span class="user-name"><?php echo htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="user-role">Administrator</span>
                        </div>

                        <div class="user-dropdown" id="userDropdown" role="menu" aria-hidden="true" style="display:none; position:absolute; right:2rem; margin-top:.5rem; min-width:200px;">
                            <a href="profile.php" class="nav-item" role="menuitem" style="display:block; padding:.5rem 1rem;">Profile</a>
                            <a href="settings.php" class="nav-item" role="menuitem" style="display:block; padding:.5rem 1rem;">Settings</a>
                            <div style="height:1px; background:var(--border); margin: .25rem 0;"></div>
                            <a href="admin-logout.php" class="nav-item" role="menuitem" style="display:block; padding:.5rem 1rem; color:var(--error);">Logout</a>
                        </div>
                    </div>
                </div>

                <script>
                    (function(){
                        const sidebar = document.getElementById('sidebar');
                        const mobileToggle = document.getElementById('mobileMenuToggle');
                        const sidebarToggle = document.getElementById('sidebarToggle');

                        mobileToggle?.addEventListener('click', () => {
                            if (!sidebar) return;
                            sidebar.classList.toggle('mobile-open');
                        });

                        sidebarToggle?.addEventListener('click', () => {
                            if (!sidebar) return;
                            sidebar.classList.toggle('collapsed');
                        });

                        const notificationBtn = document.getElementById('notificationBtn');
                        notificationBtn?.addEventListener('click', (e) => {
                            const badge = document.getElementById('notificationBadge');
                            if (badge) {
                                badge.style.transform = 'scale(0.9)';
                                setTimeout(()=> badge.style.transform = '', 150);
                            }
                        });

                        const userMenuButton = document.getElementById('userMenuButton');
                        const userDropdown = document.getElementById('userDropdown');
                        const userMenu = document.getElementById('userMenu');

                        function closeUserDropdown() {
                            userDropdown.style.display = 'none';
                            userMenu.setAttribute('aria-expanded', 'false');
                            userDropdown.setAttribute('aria-hidden', 'true');
                        }
                        function openUserDropdown() {
                            userDropdown.style.display = 'block';
                            userMenu.setAttribute('aria-expanded', 'true');
                            userDropdown.setAttribute('aria-hidden', 'false');
                        }

                        userMenuButton?.addEventListener('click', (e) => {
                            e.stopPropagation();
                            if (userDropdown.style.display === 'block') closeUserDropdown();
                            else openUserDropdown();
                        });

                        userMenuButton?.addEventListener('keydown', (e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                                e.preventDefault();
                                userMenuButton.click();
                            } else if (e.key === 'Escape') {
                                closeUserDropdown();
                            }
                        });

                        document.addEventListener('click', (e) => {
                            const target = e.target;
                            if (!userMenu.contains(target)) {
                                closeUserDropdown();
                            }
                        });

                        document.addEventListener('keydown', (e) => {
                            if (e.key === 'Escape') {
                                closeUserDropdown();
                                sidebar?.classList.remove('mobile-open');
                            }
                        });

                        const globalSearch = document.getElementById('globalSearchInput');
                        if (globalSearch) {
                            let searchTimeout;
                            globalSearch.addEventListener('input', (e) => {
                                clearTimeout(searchTimeout);
                                searchTimeout = setTimeout(() => {
                                    const ev = new CustomEvent('admin:search', { detail: { q: globalSearch.value } });
                                    document.dispatchEvent(ev);
                                }, 300);
                            });
                        }
                    })();
                </script>
            </header>

            <!-- Redesigned Orders Page Content -->
            <div class="orders-page">
                <!-- Page Header with Stats -->
                <div class="orders-header">
                    <div class="orders-header-content">
                        <div class="orders-title-section">
                            <div class="orders-icon-wrapper">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="orders-page-title">Order Management</h1>
                                <p class="orders-page-subtitle">Track, manage and fulfill customer orders efficiently</p>
                            </div>
                        </div>
                        <div class="orders-quick-stats">
                            <div class="quick-stat" id="statPending">
                                <div class="quick-stat-icon pending">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="quick-stat-info">
                                    <span class="quick-stat-value" id="pendingCount">0</span>
                                    <span class="quick-stat-label">Pending</span>
                                </div>
                            </div>
                            <div class="quick-stat" id="statDelivering">
                                <div class="quick-stat-icon delivering">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                    </svg>
                                </div>
                                <div class="quick-stat-info">
                                    <span class="quick-stat-value" id="deliveringCount">0</span>
                                    <span class="quick-stat-label">Delivering</span>
                                </div>
                            </div>
                            <div class="quick-stat" id="statCompleted">
                                <div class="quick-stat-icon completed">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="quick-stat-info">
                                    <span class="quick-stat-value" id="completedCount">0</span>
                                    <span class="quick-stat-label">Completed</span>
                                </div>
                            </div>
                            <div class="quick-stat" id="statRefunds">
                                <div class="quick-stat-icon refunds">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                    </svg>
                                </div>
                                <div class="quick-stat-info">
                                    <span class="quick-stat-value" id="refundsCount">0</span>
                                    <span class="quick-stat-label">Refunds</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs & Search -->
                <div class="orders-controls">
                    <div class="orders-filter-tabs">
                        <button class="filter-tab active" data-filter="all">
                            <span class="filter-tab-text">All Orders</span>
                            <span class="filter-tab-count" id="allCount">0</span>
                        </button>
                        <button class="filter-tab" data-filter="pending">
                            <span class="filter-tab-text">Pending</span>
                            <span class="filter-tab-count filter-pending" id="filterPendingCount">0</span>
                        </button>
                        <button class="filter-tab" data-filter="confirmed">
                            <span class="filter-tab-text">Confirmed</span>
                            <span class="filter-tab-count filter-confirmed" id="filterConfirmedCount">0</span>
                        </button>
                        <button class="filter-tab" data-filter="delivering">
                            <span class="filter-tab-text">Delivering</span>
                            <span class="filter-tab-count filter-delivering" id="filterDeliveringCount">0</span>
                        </button>
                        <button class="filter-tab" data-filter="completed">
                            <span class="filter-tab-text">Completed</span>
                            <span class="filter-tab-count filter-completed" id="filterCompletedCount">0</span>
                        </button>
                        <button class="filter-tab" data-filter="cancelled">
                            <span class="filter-tab-text">Cancelled</span>
                            <span class="filter-tab-count filter-cancelled" id="filterCancelledCount">0</span>
                        </button>
                        <button class="filter-tab" data-filter="refunds">
                            <span class="filter-tab-text">Refunds</span>
                            <span class="filter-tab-count filter-refunds" id="filterRefundsCount">0</span>
                        </button>
                    </div>
                    <div class="orders-search-wrap">
                        <div class="orders-search-box">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <input type="search" id="orderSearch" placeholder="Search by order ID, customer name..." aria-label="Search orders">
                            <kbd>⌘K</kbd>
                        </div>
                    </div>
                </div>

                <!-- Orders Grid/List -->
                <div class="orders-container">
                    <div class="orders-list-header">
                        <div class="orders-list-info">
                            <span id="ordersCount">0</span> orders found
                        </div>
                        <div class="orders-view-toggle">
                            <button class="view-btn active" id="listViewBtn" title="List View">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                            </button>
                            <button class="view-btn" id="gridViewBtn" title="Grid View">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Orders List View -->
                    <div class="orders-list" id="ordersList">
                        <div class="orders-loading">
                            <div class="loading-spinner"></div>
                            <span>Loading orders...</span>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="orders-pagination">
                        <button class="pagination-btn" id="prevOrderPage" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                            </svg>
                            Previous
                        </button>
                        <div class="pagination-info">
                            <span id="orderPageInfo">Page 1 of 1</span>
                        </div>
                        <button class="pagination-btn" id="nextOrderPage" disabled>
                            Next
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Redesigned Order Modal -->
    <div class="order-modal-overlay" id="orderModalBackdrop" aria-hidden="true">
        <div class="order-modal" id="orderModal" role="dialog" aria-modal="true" aria-labelledby="orderModalTitle" tabindex="-1">
            <div class="order-modal-header">
                <div class="order-modal-title-wrap">
                    <div class="order-modal-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h2 id="orderModalTitle" class="order-modal-title">Order Details</h2>
                        <p id="orderModalSub" class="order-modal-subtitle">View complete order information</p>
                    </div>
                </div>
                <button type="button" class="order-modal-close" id="closeOrderModal" aria-label="Close dialog">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="order-modal-body">
                <div class="order-modal-main" id="orderDetails">
                    <div class="order-loading-state">
                        <div class="loading-spinner"></div>
                        <span>Loading order details...</span>
                    </div>
                </div>

                <aside class="order-modal-sidebar" id="orderSideCard">
                    <div class="sidebar-section">
                        <h4 class="sidebar-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Customer Info
                        </h4>
                        <p class="sidebar-placeholder">Loading customer details...</p>
                    </div>
                </aside>
            </div>

            <div class="order-modal-footer">
                <button class="modal-btn secondary" id="closeOrderModalBottom">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Close
                </button>
                <button class="modal-btn primary" id="printOrderBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print Order
                </button>
            </div>
        </div>
    </div>

    <!-- Refund Confirmation Modal -->
    <div class="refund-confirm-overlay" id="refundConfirmModal" aria-hidden="true">
        <div class="refund-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="refundConfirmTitle">
            <div class="refund-confirm-header">
                <div class="refund-confirm-icon" id="refundConfirmIcon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 id="refundConfirmTitle" class="refund-confirm-title">Confirm Refund Action</h3>
                <p id="refundConfirmMessage" class="refund-confirm-message">Are you sure you want to approve this refund request?</p>
            </div>
            <div class="refund-confirm-body">
                <div class="refund-confirm-details">
                    <div class="refund-detail-row">
                        <span class="refund-detail-label">Order ID</span>
                        <span class="refund-detail-value" id="refundOrderIdDisplay">#—</span>
                    </div>
                    <div class="refund-detail-row">
                        <span class="refund-detail-label">Action</span>
                        <span class="refund-detail-value refund-action-badge" id="refundActionDisplay">Approve</span>
                    </div>
                </div>
                <div class="refund-confirm-warning" id="refundWarning">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>This action will notify the customer and cannot be easily undone.</span>
                </div>
            </div>
            <div class="refund-confirm-footer">
                <button type="button" class="refund-confirm-btn cancel" id="refundConfirmCancel">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Cancel
                </button>
                <button type="button" class="refund-confirm-btn confirm" id="refundConfirmSubmit">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span id="refundConfirmBtnText">Confirm</span>
                </button>
            </div>
        </div>
    </div>

<script>
    // DOM Elements
    const ordersList = document.getElementById('ordersList');
    const orderModalBackdrop = document.getElementById('orderModalBackdrop');
    const orderDetails = document.getElementById('orderDetails');
    const orderModal = document.getElementById('orderModal');
    const orderSearch = document.getElementById('orderSearch');
    const filterTabs = document.querySelectorAll('.filter-tab');
    const listViewBtn = document.getElementById('listViewBtn');
    const gridViewBtn = document.getElementById('gridViewBtn');

    let orders = [];
    let filteredOrders = [];
    let currentFilter = 'all';
    let currentPage = 1;
    const itemsPerPage = 10;
    let searchTimeout;

    // Fetch orders from API
    async function fetchOrders(q = '') {
        showLoading();
        try {
            const res = await fetch('api/orders_api.php?action=list' + (q ? '&q=' + encodeURIComponent(q) : ''));
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed to load');
            orders = data.orders;
            applyFilter();
            updateStats();
        } catch (err) {
            ordersList.innerHTML = `
                <div class="orders-empty">
                    <div class="empty-icon error">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3>Error Loading Orders</h3>
                    <p>Something went wrong while fetching orders.</p>
                    <button class="modal-btn primary" onclick="fetchOrders()">Try Again</button>
                </div>
            `;
            console.error(err);
        }
    }

    // Show loading state
    function showLoading() {
        ordersList.innerHTML = `
            <div class="orders-loading">
                <div class="loading-spinner"></div>
                <span>Loading orders...</span>
            </div>
        `;
    }

    // Update statistics
    function updateStats() {
        const stats = {
            pending: orders.filter(o => o.status === 'pending').length,
            confirmed: orders.filter(o => o.status === 'confirmed').length,
            delivering: orders.filter(o => o.status === 'delivering').length,
            completed: orders.filter(o => o.status === 'completed').length,
            cancelled: orders.filter(o => o.status === 'cancelled').length,
            refunds: orders.filter(o => o.refund_status && o.refund_status !== null).length
        };

        document.getElementById('pendingCount').textContent = stats.pending;
        document.getElementById('deliveringCount').textContent = stats.delivering;
        document.getElementById('completedCount').textContent = stats.completed;
        document.getElementById('refundsCount').textContent = stats.refunds;
        
        document.getElementById('allCount').textContent = orders.length;
        document.getElementById('filterPendingCount').textContent = stats.pending;
        document.getElementById('filterConfirmedCount').textContent = stats.confirmed;
        document.getElementById('filterDeliveringCount').textContent = stats.delivering;
        document.getElementById('filterCompletedCount').textContent = stats.completed;
        document.getElementById('filterCancelledCount').textContent = stats.cancelled;
        document.getElementById('filterRefundsCount').textContent = stats.refunds;
    }

    // Apply filter
    function applyFilter() {
        if (currentFilter === 'all') {
            filteredOrders = [...orders];
        } else if (currentFilter === 'refunds') {
            filteredOrders = orders.filter(o => o.refund_status && o.refund_status !== null);
        } else {
            filteredOrders = orders.filter(o => o.status === currentFilter);
        }
        currentPage = 1;
        renderOrders();
    }

    // Render orders list
    function renderOrders() {
        const startIdx = (currentPage - 1) * itemsPerPage;
        const endIdx = startIdx + itemsPerPage;
        const pageOrders = filteredOrders.slice(startIdx, endIdx);
        const totalPages = Math.ceil(filteredOrders.length / itemsPerPage) || 1;

        document.getElementById('ordersCount').textContent = filteredOrders.length;
        document.getElementById('orderPageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
        
        document.getElementById('prevOrderPage').disabled = currentPage <= 1;
        document.getElementById('nextOrderPage').disabled = currentPage >= totalPages;

        if (!filteredOrders.length) {
            ordersList.innerHTML = `
                <div class="orders-empty">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                    </div>
                    <h3>No Orders Found</h3>
                    <p>There are no orders matching your current filter.</p>
                </div>
            `;
            return;
        }

        ordersList.innerHTML = pageOrders.map(o => `
            <div class="order-card" data-order-id="${o.order_id}">
                <div class="order-card-header">
                    <div class="order-id-wrap">
                        <span class="order-id">#${o.order_id}</span>
                        <span class="order-date">${escapeHtml(formatDate(o.order_date))}</span>
                    </div>
                    <div class="order-badges">
                        <div class="order-status-badge status-${o.status || 'pending'}">
                            ${getStatusIcon(o.status)}
                            <span>${capitalizeFirst(o.status || 'pending')}</span>
                        </div>
                        ${o.refund_status ? `
                        <div class="refund-status-badge refund-${o.refund_status}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            <span>${capitalizeFirst(o.refund_status.replace('_', ' '))}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
                
                <div class="order-card-body">
                    <div class="order-customer">
                        <div class="customer-avatar">
                            ${getInitials(o.customer_name || 'Guest')}
                        </div>
                        <div class="customer-info">
                            <span class="customer-name">${escapeHtml(o.customer_name || 'Guest')}</span>
                            <span class="customer-email">${escapeHtml(o.customer_email || 'No email')}</span>
                        </div>
                    </div>
                    
                    <div class="order-details-row">
                        <div class="order-detail">
                            <span class="detail-label">Amount</span>
                            <span class="detail-value amount">₱${Number(o.total_amount).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</span>
                        </div>
                        <div class="order-detail">
                            <span class="detail-label">Rider</span>
                            <span class="detail-value">${escapeHtml(o.rider_name || 'Not assigned')}</span>
                        </div>
                        <div class="order-detail">
                            <span class="detail-label">Payment</span>
                            <span class="detail-value">${escapeHtml(o.payment_method || 'N/A')}</span>
                        </div>
                    </div>
                </div>
                
                <div class="order-card-footer">
                    <div class="order-status-select-wrap">
                        <label for="status-${o.order_id}">Status:</label>
                        <select class="order-status-select status-${o.status || 'pending'}" id="status-${o.order_id}" data-id="${o.order_id}" data-current="${o.status || ''}">
                            <option value="pending" ${o.status=='pending'?'selected':''}>Pending</option>
                            <option value="confirmed" ${o.status=='confirmed'?'selected':''}>Confirmed</option>
                            <option value="delivering" ${o.status=='delivering'?'selected':''}>Delivering</option>
                            <option value="completed" ${o.status=='completed'?'selected':''}>Completed</option>
                            <option value="cancelled" ${o.status=='cancelled'?'selected':''}>Cancelled</option>
                            <option value="refund_requested" ${o.status=='refund_requested'?'selected':''}>Refund Requested</option>
                        </select>
                    </div>
                    <div class="order-actions">
                        ${o.refund_status === 'requested' ? `
                            <button class="order-btn refund" data-action="refund" data-id="${o.order_id}" data-refund-id="${o.refund_id}">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                Process Refund
                            </button>
                        ` : ''}
                        <button class="order-btn view" data-action="view" data-id="${o.order_id}">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    // Helper functions
    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function getInitials(name) {
        return name.split(' ').map(n => n[0]).slice(0, 2).join('').toUpperCase();
    }

    function formatDate(dateStr) {
        try {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        } catch {
            return dateStr;
        }
    }

    function getStatusIcon(status) {
        const icons = {
            pending: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            confirmed: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            delivering: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" /></svg>',
            completed: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>',
            cancelled: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>'
        };
        return icons[status] || icons.pending;
    }

    function getRefundStatusIcon(status) {
        const icons = {
            requested: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            processing: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>',
            resolved: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
            rejected: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>'
        };
        return icons[status] || icons.requested;
    }

    // Modal for status confirmation
    function showConfirmStatusModal(title, message) {
        return new Promise(resolve => {
            const modal = document.createElement('div');
            modal.className = 'confirm-modal-overlay';
            modal.innerHTML = `
                <div class="confirm-modal" role="dialog" aria-modal="true">
                    <h3>${escapeHtml(title)}</h3>
                    <p>${escapeHtml(message)}</p>
                    <div class="confirm-modal-actions">
                        <button class="modal-btn secondary" id="confirmCancel">Cancel</button>
                        <button class="modal-btn primary" id="confirmYes">Confirm</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            
            const yesBtn = modal.querySelector('#confirmYes');
            const cancelBtn = modal.querySelector('#confirmCancel');
            
            function cleanup(val) {
                modal.remove();
                resolve(val);
            }
            
            yesBtn?.addEventListener('click', () => cleanup(true));
            cancelBtn?.addEventListener('click', () => cleanup(false));
            modal.addEventListener('click', (e) => {
                if (e.target === modal) cleanup(false);
            });
        });
    }

    // Filter tab click handler
    filterTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            filterTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentFilter = tab.dataset.filter;
            applyFilter();
        });
    });

    // View toggle handlers
    listViewBtn?.addEventListener('click', () => {
        listViewBtn.classList.add('active');
        gridViewBtn?.classList.remove('active');
        ordersList.classList.remove('grid-view');
    });

    gridViewBtn?.addEventListener('click', () => {
        gridViewBtn.classList.add('active');
        listViewBtn?.classList.remove('active');
        ordersList.classList.add('grid-view');
    });

    // Pagination handlers
    document.getElementById('prevOrderPage')?.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            renderOrders();
        }
    });

    document.getElementById('nextOrderPage')?.addEventListener('click', () => {
        const totalPages = Math.ceil(filteredOrders.length / itemsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            renderOrders();
        }
    });

    // Status change handler
    document.addEventListener('change', async (e) => {
        const sel = e.target.closest('.order-status-select');
        if (!sel) return;
        
        const id = sel.dataset.id;
        const newStatus = sel.value;
        const prevStatus = sel.dataset.current || '';

        async function doUpdate(statusToSet) {
            try {
                const res = await fetch('api/orders_api.php?action=update_status', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(statusToSet)
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Update failed');
                
                const statuses = ['pending','confirmed','delivering','completed','cancelled'];
                statuses.forEach(s => sel.classList.remove('status-' + s));
                sel.classList.add('status-' + statusToSet);
                sel.dataset.current = statusToSet;
                
                // Update the badge in the card
                const card = sel.closest('.order-card');
                const badge = card?.querySelector('.order-status-badge');
                if (badge) {
                    statuses.forEach(s => badge.classList.remove('status-' + s));
                    badge.classList.add('status-' + statusToSet);
                    badge.innerHTML = `${getStatusIcon(statusToSet)}<span>${capitalizeFirst(statusToSet)}</span>`;
                }
                
                return true;
            } catch (err) {
                alert('Failed to update status: ' + (err.message || String(err)));
                console.error(err);
                return false;
            }
        }

        if (newStatus === 'confirmed') {
            // Show modal confirmation for confirmed status
            const confirmed = await showConfirmStatusModal('Confirm Order', `Are you sure you want to confirm order #${id}?`);
            if (!confirmed) {
                sel.value = prevStatus;
                return;
            }
        }

        const okStatus = await doUpdate(newStatus);
        if (!okStatus) {
            sel.value = prevStatus;
        } else {
            await fetchOrders(orderSearch?.value?.trim() || '');
        }
    });

    // Click handlers for view and refund buttons
    document.addEventListener('click', async (e) => {
        const viewBtn = e.target.closest('[data-action="view"]');
        if (viewBtn) {
            const id = viewBtn.dataset.id;
            try {
                const u = new URL(window.location.href);
                u.searchParams.set('id', id);
                history.pushState({orderModalId: id}, '', u.pathname + '?' + u.searchParams.toString());
            } catch (err) {
                console.warn('Could not push state', err);
            }
            openOrderModal(id);
            return;
        }

        const refundBtn = e.target.closest('[data-action="refund"]');
        if (refundBtn) {
            const id = refundBtn.dataset.id;
            openRefundConfirmModal(id, 'approve');
            return;
        }

        // Refund action buttons in modal
        const refundActionBtn = e.target.closest('.refund-action-btn');
        if (refundActionBtn) {
            const orderId = refundActionBtn.dataset.orderId;
            const op = refundActionBtn.dataset.op;
            openRefundConfirmModal(orderId, op);
            return;
        }
    });

    // Modal functions
    async function openOrderModal(id) {
        if (!orderModalBackdrop) return;
        
        orderModalBackdrop.classList.add('open');
        orderModal?.classList.add('open');
        orderModalBackdrop.setAttribute('aria-hidden', 'false');
        
        orderDetails.innerHTML = `
            <div class="order-loading-state">
                <div class="loading-spinner"></div>
                <span>Loading order details...</span>
            </div>
        `;

        try {
            const res = await fetch('api/orders_api.php?action=get&id=' + encodeURIComponent(id));
            const text = await res.text();
            let data;
            
            try {
                data = JSON.parse(text);
            } catch (parseErr) {
                orderDetails.innerHTML = `
                    <div class="order-error-state">
                        <div class="error-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3>Error Loading Order</h3>
                        <p>Server returned invalid response.</p>
                        <button class="modal-btn primary" id="retryOrderFetch">Try Again</button>
                    </div>
                `;
                document.getElementById('retryOrderFetch')?.addEventListener('click', () => openOrderModal(id));
                return;
            }

            if (!data.success) {
                orderDetails.innerHTML = `
                    <div class="order-error-state">
                        <div class="error-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h3>Error Loading Order</h3>
                        <p>${escapeHtml(data.message || 'Unknown error')}</p>
                        <button class="modal-btn primary" id="retryOrderFetch">Try Again</button>
                    </div>
                `;
                document.getElementById('retryOrderFetch')?.addEventListener('click', () => openOrderModal(id));
                return;
            }

            const o = data.order;
            
            // Main order details
            const itemsHtml = (o.items || []).map(i => {
                const qty = i.qty ?? i.quantity ?? i.qty_order ?? 1;
                const priceVal = i.price ?? i.unit_price ?? i.amount ?? 0;
                const name = i.name ?? i.product_name ?? 'Item';
                return `
                    <div class="order-item">
                        <div class="order-item-info">
                            <span class="order-item-name">${escapeHtml(name)}</span>
                            <span class="order-item-qty">Qty: ${escapeHtml(String(qty))}</span>
                        </div>
                        <span class="order-item-price">₱${Number(priceVal).toFixed(2)}</span>
                    </div>
                `;
            }).join('');

            orderDetails.innerHTML = `
                <div class="order-detail-header">
                    <div class="order-detail-id">
                        <span class="label">Order ID</span>
                        <span class="value">#${escapeHtml(String(o.order_id))}</span>
                    </div>
                    <div class="order-detail-status status-${o.status || 'pending'}">
                        ${getStatusIcon(o.status)}
                        <span>${capitalizeFirst(o.status || 'pending')}</span>
                    </div>
                </div>
                
                <div class="order-detail-section">
                    <h4 class="section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Order Items
                    </h4>
                    <div class="order-items-list">
                        ${itemsHtml || '<p class="no-items">No items found</p>'}
                    </div>
                </div>
                
                <div class="order-detail-section">
                    <h4 class="section-title">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Order Summary
                    </h4>
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Order Date</span>
                            <span>${escapeHtml(formatDate(o.order_date))}</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total Amount</span>
                            <span>₱${Number(o.total_amount).toLocaleString(undefined,{minimumFractionDigits:2})}</span>
                        </div>
                    </div>
                </div>
            `;

            // Sidebar with customer info and proofs
            const side = document.getElementById('orderSideCard');
            if (side) {
                const phone = o.customer_phone || o.phone || o.contact || '—';
                const addressCandidates = [o.shipping_address, o.delivery_address, o.address, o.customer_address];
                const address = addressCandidates.find(a => a && String(a).trim()) || '—';
                const paymentMethod = o.payment_method || o.payment || '—';

                const proofCandidates = [o.payment_proof, o.payment_proof_url, o.proof];
                const proof = proofCandidates.find(p => p && String(p).trim());
                
                const deliveryProofCandidates = [o.delivery_proof, o.delivery_proof_url];
                const deliveryProof = deliveryProofCandidates.find(p => p && String(p).trim());

                side.innerHTML = `
                    <div class="sidebar-section">
                        <h4 class="sidebar-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Customer
                        </h4>
                        <div class="sidebar-customer">
                            <div class="customer-avatar large">${getInitials(o.customer_name || 'Guest')}</div>
                            <div class="customer-details">
                                <span class="name">${escapeHtml(o.customer_name || 'Guest')}</span>
                                <span class="phone">${escapeHtml(phone)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sidebar-section">
                        <h4 class="sidebar-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Shipping Address
                        </h4>
                        <p class="sidebar-text">${escapeHtml(address)}</p>
                    </div>
                    
                    <div class="sidebar-section">
                        <h4 class="sidebar-section-title">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            Payment Method
                        </h4>
                        <p class="sidebar-text payment-badge">${escapeHtml(paymentMethod)}</p>
                    </div>
                    
                    ${proof ? `
                        <div class="sidebar-section">
                            <h4 class="sidebar-section-title">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Payment Proof
                            </h4>
                            <a href="${escapeHtml(proof)}" target="_blank" class="proof-image-link">
                                <img src="${escapeHtml(proof)}" alt="Payment proof" class="proof-image">
                                <span class="proof-overlay">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                    </svg>
                                    View Full Size
                                </span>
                            </a>
                        </div>
                    ` : ''}
                    
                    ${deliveryProof ? `
                        <div class="sidebar-section">
                            <h4 class="sidebar-section-title">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Delivery Proof
                            </h4>
                            <a href="${escapeHtml(deliveryProof)}" target="_blank" class="proof-image-link">
                                <img src="${escapeHtml(deliveryProof)}" alt="Delivery proof" class="proof-image">
                                <span class="proof-overlay">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                    </svg>
                                    View Full Size
                                </span>
                            </a>
                        </div>
                    ` : ''}
                    
                    ${o.refund_request ? `
                        <div class="sidebar-section refund-section">
                            <h4 class="sidebar-section-title">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                Refund Request
                            </h4>
                            <div class="refund-info">
                                <div class="refund-status-badge refund-${o.refund_request.status || 'requested'}">
                                    ${getRefundStatusIcon(o.refund_request.status)}
                                    ${capitalizeFirst(o.refund_request.status || 'requested')}
                                </div>
                                <p class="refund-reason">${escapeHtml(o.refund_request.reason || 'No reason provided')}</p>
                                <p class="refund-date">Requested: ${escapeHtml(formatDate(o.refund_request.created_at))}</p>
                                ${o.refund_request.status === 'requested' ? `
                                    <div class="refund-actions">
                                        <button class="refund-action-btn approve" data-order-id="${o.order_id}" data-op="approve">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Approve
                                        </button>
                                        <button class="refund-action-btn reject" data-order-id="${o.order_id}" data-op="reject">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                            Reject
                                        </button>
                                    </div>
                                ` : ''}
                                ${o.refund_request.status === 'processing' ? `
                                    <div class="refund-actions">
                                        <button class="refund-action-btn complete" data-order-id="${o.order_id}" data-op="complete">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Mark Completed
                                        </button>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    ` : ''}
                `;
            }

            orderModal?.focus();
            trapFocus(orderModal);
        } catch (err) {
            console.error(err);
            orderDetails.innerHTML = `
                <div class="order-error-state">
                    <div class="error-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3>Error Loading Order</h3>
                    <p>${escapeHtml(err.message || String(err))}</p>
                    <button class="modal-btn primary" id="retryOrderFetch">Try Again</button>
                </div>
            `;
            document.getElementById('retryOrderFetch')?.addEventListener('click', () => openOrderModal(id));
        }
    }

    function closeOrderModal() {
        releaseTrap(orderModal);
        orderModal?.classList.remove('open');
        orderModalBackdrop?.classList.remove('open');
        orderModalBackdrop?.setAttribute('aria-hidden', 'true');
        
        try {
            const u = new URL(window.location.href);
            u.searchParams.delete('id');
            const qs = u.searchParams.toString();
            history.replaceState({}, '', u.pathname + (qs ? '?' + qs : ''));
        } catch (err) {}
    }

    // Modal event listeners
    orderModalBackdrop?.addEventListener('click', (e) => {
        if (e.target === orderModalBackdrop) closeOrderModal();
    });

    document.getElementById('closeOrderModal')?.addEventListener('click', closeOrderModal);
    document.getElementById('closeOrderModalBottom')?.addEventListener('click', closeOrderModal);
    
    document.getElementById('printOrderBtn')?.addEventListener('click', () => {
        window.print();
    });

    // Focus trap functions
    let _focusableElems = [];
    let _firstFocusable = null;
    let _lastFocusable = null;

    function trapFocus(container) {
        if (!container) return;
        _focusableElems = Array.from(container.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'))
            .filter(el => el.offsetParent !== null);
        _firstFocusable = _focusableElems[0] || null;
        _lastFocusable = _focusableElems[_focusableElems.length - 1] || null;
        container.addEventListener('keydown', _handleTrap);
    }

    function _handleTrap(e) {
        if (e.key !== 'Tab') return;
        if (!_firstFocusable || !_lastFocusable) {
            e.preventDefault();
            return;
        }
        if (e.shiftKey) {
            if (document.activeElement === _firstFocusable) {
                e.preventDefault();
                _lastFocusable.focus();
            }
        } else {
            if (document.activeElement === _lastFocusable) {
                e.preventDefault();
                _firstFocusable.focus();
            }
        }
    }

    function releaseTrap(container) {
        if (!container) return;
        container.removeEventListener('keydown', _handleTrap);
    }

    // Search handler
    if (orderSearch) {
        orderSearch.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchOrders(orderSearch.value.trim());
            }, 300);
        });
    }

    // Keyboard shortcut for search
    document.addEventListener('keydown', (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            orderSearch?.focus();
        }
        if (e.key === 'Escape') {
            if (orderModalBackdrop?.classList.contains('open')) {
                closeOrderModal();
            }
        }
    });

    // Handle browser back/forward
    window.addEventListener('popstate', () => {
        try {
            const params = new URLSearchParams(window.location.search);
            const id = params.get('id');
            if (id) {
                openOrderModal(id);
            } else if (orderModalBackdrop?.classList.contains('open')) {
                closeOrderModal();
            }
        } catch (err) {
            console.error('popstate handler error', err);
        }
    });

    // Initialize
    fetchOrders().then(() => {
        try {
            const params = new URLSearchParams(window.location.search);
            const id = params.get('id');
            if (id) {
                setTimeout(() => openOrderModal(id), 150);
            }
        } catch (e) {
            console.error('Failed to open order from URL:', e);
        }
    });

    // ===== REFUND CONFIRMATION MODAL =====
    const refundConfirmModal = document.getElementById('refundConfirmModal');
    const refundConfirmIcon = document.getElementById('refundConfirmIcon');
    const refundConfirmTitle = document.getElementById('refundConfirmTitle');
    const refundConfirmMessage = document.getElementById('refundConfirmMessage');
    const refundOrderIdDisplay = document.getElementById('refundOrderIdDisplay');
    const refundActionDisplay = document.getElementById('refundActionDisplay');
    const refundWarning = document.getElementById('refundWarning');
    const refundConfirmCancel = document.getElementById('refundConfirmCancel');
    const refundConfirmSubmit = document.getElementById('refundConfirmSubmit');
    const refundConfirmBtnText = document.getElementById('refundConfirmBtnText');

    let pendingRefundOrderId = null;
    let pendingRefundOp = null;

    const refundOpConfig = {
        approve: {
            title: 'Approve Refund Request',
            message: 'Are you sure you want to approve this refund request? The refund will be marked as processing.',
            btnText: 'Approve Refund',
            iconClass: 'approve',
            badgeClass: 'approve',
            warning: 'This will notify the customer that their refund has been approved.'
        },
        reject: {
            title: 'Reject Refund Request',
            message: 'Are you sure you want to reject this refund request? This action cannot be easily undone.',
            btnText: 'Reject Refund',
            iconClass: 'reject',
            badgeClass: 'reject',
            warning: 'The customer will be notified that their refund request was rejected.'
        },
        complete: {
            title: 'Complete Refund',
            message: 'Are you sure you want to mark this refund as completed?',
            btnText: 'Mark Completed',
            iconClass: 'complete',
            badgeClass: 'complete',
            warning: 'This confirms the refund has been processed and the customer has been compensated.'
        }
    };

    function openRefundConfirmModal(orderId, op) {
        pendingRefundOrderId = orderId;
        pendingRefundOp = op;

        const config = refundOpConfig[op] || refundOpConfig.approve;
        
        refundConfirmTitle.textContent = config.title;
        refundConfirmMessage.textContent = config.message;
        refundOrderIdDisplay.textContent = '#' + orderId;
        refundActionDisplay.textContent = op.charAt(0).toUpperCase() + op.slice(1);
        refundConfirmBtnText.textContent = config.btnText;
        refundWarning.querySelector('span').textContent = config.warning;

        refundConfirmIcon.className = 'refund-confirm-icon ' + config.iconClass;
        refundActionDisplay.className = 'refund-detail-value refund-action-badge ' + config.badgeClass;
        refundConfirmSubmit.className = 'refund-confirm-btn confirm ' + config.iconClass;

        refundConfirmModal.classList.add('open');
        refundConfirmModal.setAttribute('aria-hidden', 'false');
        refundConfirmSubmit.focus();
    }

    function closeRefundConfirmModal() {
        refundConfirmModal.classList.remove('open');
        refundConfirmModal.setAttribute('aria-hidden', 'true');
        pendingRefundOrderId = null;
        pendingRefundOp = null;
    }

    async function executeRefundAction() {
        if (!pendingRefundOrderId || !pendingRefundOp) return;

        const orderId = pendingRefundOrderId;
        const op = pendingRefundOp;

        refundConfirmSubmit.disabled = true;
        refundConfirmCancel.disabled = true;
        refundConfirmBtnText.innerHTML = '<span class="btn-spinner"></span> Processing...';

        try {
            const form = new URLSearchParams();
            form.append('id', orderId);
            form.append('op', op);
            const res = await fetch('api/orders_api.php?action=refund', {
                method: 'POST',
                body: form
            });

            const text = await res.text();
            if (!res.ok) {
                let msg = text || res.statusText || 'Server error';
                try {
                    const parsed = JSON.parse(text || '{}');
                    if (parsed && parsed.message) msg = parsed.message;
                } catch (e) {}
                throw new Error(msg);
            }

            let data = null;
            try {
                data = text ? JSON.parse(text) : null;
            } catch (e) {
                throw new Error('Invalid JSON response from server: ' + (text ? text.substring(0,200) : '[empty]'));
            }

            if (!data || !data.success) throw new Error((data && data.message) ? data.message : 'Refund API error');

            closeRefundConfirmModal();
            showSuccessToast('Refund ' + (op === 'complete' ? 'completed' : op + 'd') + ' successfully!');

            if (orderModalBackdrop?.classList.contains('open')) {
                openOrderModal(orderId);
            }
            fetchOrders(orderSearch?.value?.trim() || '');
        } catch (err) {
            showErrorToast('Failed: ' + (err.message || String(err)));
            console.error(err);
        } finally {
            refundConfirmSubmit.disabled = false;
            refundConfirmCancel.disabled = false;
            const config = refundOpConfig[op] || refundOpConfig.approve;
            refundConfirmBtnText.textContent = config.btnText;
        }
    }

    function showSuccessToast(message) {
        showToast(message, 'success');
    }

    function showErrorToast(message) {
        showToast(message, 'error');
    }

    function showToast(message, type = 'success') {
        const existing = document.querySelector('.admin-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'admin-toast ' + type;
        toast.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                ${type === 'success' 
                    ? '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />'
                    : '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'}
            </svg>
            <span>${escapeHtml(message)}</span>
        `;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Event listeners for refund confirm modal
    refundConfirmCancel?.addEventListener('click', closeRefundConfirmModal);
    refundConfirmSubmit?.addEventListener('click', executeRefundAction);
    refundConfirmModal?.addEventListener('click', (e) => {
        if (e.target === refundConfirmModal) closeRefundConfirmModal();
    });

    // Add Escape key handler for refund modal
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && refundConfirmModal?.classList.contains('open')) {
            closeRefundConfirmModal();
        }
    });
</script>
</body>
</html>