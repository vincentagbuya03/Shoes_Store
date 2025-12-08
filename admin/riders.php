<?php
require_once 'db_connection.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Riders - ShoeTakels Admin</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/admin-dashboard.css">
    <link rel="stylesheet" href="asset/style/products.css">
    <style>
        .rider-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent, #6366f1) 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
        }
        .rider-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .rider-details {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }
        .rider-name {
            font-weight: 600;
            color: var(--text-primary);
        }
        .rider-id {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }
        .rider-contact {
            display: flex;
            flex-direction: column;
            gap: 0.15rem;
        }
        .rider-email {
            color: var(--text-primary);
            font-size: 0.9rem;
        }
        .rider-phone {
            color: var(--text-secondary);
            font-size: 0.8rem;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }
        .status-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }
        .status-available {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }
        .status-busy {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }
        .status-inactive {
            background: rgba(107, 114, 128, 0.15);
            color: #6b7280;
        }
        .rider-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 900px) {
            .rider-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 500px) {
            .rider-stats {
                grid-template-columns: 1fr;
            }
        }
        .rider-stat-card {
            background: var(--card, var(--bg-card));
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid var(--border);
            text-align: center;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .rider-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .rider-stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }
        .rider-stat-card .stat-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 0.35rem;
        }
        .rider-stat-card:nth-child(1) .stat-value { color: var(--accent, #6366f1); }
        .rider-stat-card:nth-child(2) .stat-value { color: #10b981; }
        .rider-stat-card:nth-child(3) .stat-value { color: #f59e0b; }
        .rider-stat-card:nth-child(4) .stat-value { color: #6b7280; }
        
        .deliveries-count {
            font-weight: 600;
            color: var(--text-primary);
        }
        .deliveries-label {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        .joined-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        /* Modal Improvements */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            z-index: 2000;
            padding: 1rem;
        }
        .modal {
            width: 500px;
            max-width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            background: var(--bg-card, #1e1e2e);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.2s ease-out;
        }
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        .modal h3 {
            margin: 0 0 1.25rem 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .modal .product-meta,
        .modal .rider-meta {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.25rem;
            padding: 0.75rem 1rem;
            background: rgba(99, 102, 241, 0.1);
            border-radius: 8px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        .modal .form-row {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        .modal .form-row > * {
            flex: 1;
        }
        .modal input,
        .modal select,
        .modal textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg-dark, rgba(0, 0, 0, 0.2));
            border: 1px solid var(--border);
            color: var(--text-primary);
            border-radius: 10px;
            outline: none;
            font-size: 0.95rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .modal input:focus,
        .modal select:focus,
        .modal textarea:focus {
            border-color: var(--accent, #6366f1);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .modal input::placeholder {
            color: var(--text-muted, #6b7280);
        }
        .modal select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.25rem;
            padding-right: 2.5rem;
        }
        .modal .form-hint {
            display: block;
            margin-top: 0.35rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .modal .form-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
        .modal .btn {
            padding: 0.65rem 1.25rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .modal .btn:not(.ghost) {
            background: var(--accent, #6366f1);
            color: white;
            border: none;
        }
        .modal .btn:not(.ghost):hover {
            background: #4f46e5;
            transform: translateY(-1px);
        }
        .modal .btn.ghost {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }
        .modal .btn.ghost:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        /* Form Label */
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
        }
        .form-group {
            margin-bottom: 1rem;
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
                    <div class="search-box" role="search">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input id="globalSearchInput" type="text" placeholder="Search..." aria-label="Search">
                    </div>
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
                <div class="dashboard-content">
                    <div class="page-title" style="display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <h1>Delivery Riders</h1>
                        </div>
                        <div class="page-actions">
                            <input id="searchInput" class="page-search" type="search" placeholder="Search riders...">
                            <button class="btn" id="addRiderBtn">Add Rider</button>
                        </div>
                    </div>

                    <!-- Rider Stats -->
                    <div class="rider-stats" id="riderStats">
                        <div class="rider-stat-card">
                            <div class="stat-value" id="totalRiders">0</div>
                            <div class="stat-label">Total Riders</div>
                        </div>
                        <div class="rider-stat-card">
                            <div class="stat-value" id="availableRiders">0</div>
                            <div class="stat-label">Available</div>
                        </div>
                        <div class="rider-stat-card">
                            <div class="stat-value" id="busyRiders">0</div>
                            <div class="stat-label">Busy</div>
                        </div>
                        <div class="rider-stat-card">
                            <div class="stat-value" id="inactiveRiders">0</div>
                            <div class="stat-label">Inactive</div>
                        </div>
                    </div>

                    <div id="ridersRegion">
                        <table class="table" id="ridersTable" aria-live="polite">
                            <thead>
                                <tr>
                                    <th>Rider</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                    <th>Deliveries</th>
                                    <th>Joined</th>
                                    <th style="width:150px;text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ridersTbody">
                                <tr><td colspan="6" class="no-data">Loading riders…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Add / Edit Rider -->
    <div class="modal-backdrop" id="riderModalBackdrop" role="dialog" aria-modal="true">
        <div class="modal" role="document" id="riderModal">
            <h3 id="riderModalTitle">Add Rider</h3>
            <div id="riderMeta" class="rider-meta" aria-hidden="true" style="display:none">
                <div id="metaId">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    ID: <span id="metaIdVal"></span>
                </div>
                <div id="metaCreated">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Joined: <span id="metaCreatedVal"></span>
                </div>
            </div>
            <form id="riderForm">
                <input type="hidden" name="rider_id" id="rider_id" value="">
                
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" name="name" id="name" placeholder="Enter full name" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" name="email" id="email" placeholder="email@example.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="text" name="phone" id="phone" placeholder="+63 XXX XXX XXXX">
                    </div>
                </div>
                
                <div class="form-group" id="passwordRow">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter password">
                    <span class="form-hint">Leave blank to keep existing password (edit mode only)</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status">
                        <option value="available">Available</option>
                        <option value="busy">Busy</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn ghost" id="cancelRider">Cancel</button>
                    <button class="btn" id="saveRider">Save Rider</button>
                </div>
            </form>
        </div>
    </div>

<script>
    const qs = s => document.querySelector(s);
    const qsa = s => Array.from(document.querySelectorAll(s));

    let riders = [];
    let searchTimeout = null;

    // DOM elements
    const ridersTbody = qs('#ridersTbody');
    const addRiderBtn = qs('#addRiderBtn');
    const riderModalBackdrop = qs('#riderModalBackdrop');
    const riderForm = qs('#riderForm');
    const riderModalTitle = qs('#riderModalTitle');
    const searchInput = qs('#searchInput');

    // Fetch riders from API
    async function fetchRiders(q = '') {
        ridersTbody.innerHTML = '<tr><td colspan="6" class="no-data">Loading riders…</td></tr>';
        try {
            const res = await fetch('api/riders_api.php?action=list' + (q ? '&q=' + encodeURIComponent(q) : ''));
            const txt = await res.text();
            let data = null;
            if (!txt) throw new Error('Empty response from API');
            try {
                data = JSON.parse(txt);
            } catch (e) {
                throw new Error('Invalid JSON response from API');
            }
            if (!data || !data.success) throw new Error((data && data.message) ? data.message : 'Failed to fetch');
            riders = data.riders || [];
            updateStats(data.stats || {});
            renderRiders();
        } catch (err) {
            const msg = err?.message || 'Error loading riders';
            ridersTbody.innerHTML = `<tr><td colspan="6" class="no-data">${escapeHtml(msg)}<div style="margin-top:.6rem"><button id="retryRiders" class="btn">Retry</button></div></td></tr>`;
            console.error(err);
            document.getElementById('retryRiders')?.addEventListener('click', () => fetchRiders(q));
        }
    }

    function updateStats(stats) {
        qs('#totalRiders').textContent = stats.total || 0;
        qs('#availableRiders').textContent = stats.available || 0;
        qs('#busyRiders').textContent = stats.busy || 0;
        qs('#inactiveRiders').textContent = stats.inactive || 0;
    }

    // Render riders table
    function renderRiders() {
        if (!riders.length) {
            ridersTbody.innerHTML = '<tr><td colspan="6" class="no-data">No riders found</td></tr>';
            return;
        }
        ridersTbody.innerHTML = '';
        riders.forEach(r => {
            const initials = r.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="rider-info">
                        <div class="rider-avatar">${escapeHtml(initials)}</div>
                        <div class="rider-details">
                            <div class="rider-name">${escapeHtml(r.name)}</div>
                            <div class="rider-id">#${r.rider_id}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="rider-contact">
                        <span class="rider-email">${escapeHtml(r.email)}</span>
                        <span class="rider-phone">${escapeHtml(r.phone || '—')}</span>
                    </div>
                </td>
                <td><span class="status-badge status-${r.status}">${escapeHtml(r.status)}</span></td>
                <td>
                    <span class="deliveries-count">${r.delivery_count || 0}</span>
                    <span class="deliveries-label"> deliveries</span>
                </td>
                <td><span class="joined-date">${escapeHtml(r.created_at ? new Date(r.created_at).toLocaleDateString() : '—')}</span></td>
                <td style="text-align:center;" class="actions">
                    <button class="btn ghost" data-action="edit" data-id="${r.rider_id}" aria-label="Edit rider">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                    </button>
                    <button class="btn" data-action="delete" data-id="${r.rider_id}" style="background:#ef4444" aria-label="Delete rider">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m5 0V4a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </td>
            `;
            ridersTbody.appendChild(tr);
        });
    }

    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // Open modal
    addRiderBtn.addEventListener('click', () => {
        openRiderModal();
    });

    // Handle action buttons (edit/delete)
    document.addEventListener('click', e => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;
        const id = btn.dataset.id;
        if (action === 'edit') editRider(id);
        if (action === 'delete') deleteRider(id);
    });

    function openRiderModal(data = null) {
        riderModalTitle.textContent = data ? 'Edit Rider' : 'Add Rider';
        riderForm.reset();
        riderForm.rider_id.value = data ? data.rider_id : '';
        riderForm.name.value = data ? data.name : '';
        riderForm.email.value = data ? data.email : '';
        riderForm.phone.value = data ? data.phone : '';
        riderForm.status.value = data ? data.status : 'available';
        riderForm.password.value = '';
        
        // Show password hint for edit mode
        const passwordHint = qs('#passwordRow .form-hint');
        if (data) {
            passwordHint.style.display = 'block';
            riderForm.password.removeAttribute('required');
        } else {
            passwordHint.style.display = 'none';
            riderForm.password.setAttribute('required', 'required');
        }
        
        // Rider meta
        const metaEl = document.getElementById('riderMeta');
        if (data && data.rider_id) {
            metaEl.style.display = 'flex';
            document.getElementById('metaIdVal').textContent = data.rider_id || '';
            document.getElementById('metaCreatedVal').textContent = data.created_at ? new Date(data.created_at).toLocaleDateString() : '';
            metaEl.setAttribute('aria-hidden', 'false');
        } else {
            metaEl.style.display = 'none';
            metaEl.setAttribute('aria-hidden', 'true');
        }
        
        riderModalBackdrop.style.display = 'flex';
    }

    // Cancel button
    qs('#cancelRider').addEventListener('click', () => {
        riderModalBackdrop.style.display = 'none';
    });

    // Form submit
    riderForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = new FormData(riderForm);
        const id = form.get('rider_id');
        const action = id ? 'update' : 'create';
        try {
            const res = await fetch('api/riders_api.php?action=' + action, {
                method: 'POST',
                body: form
            });
            const txt = await res.text();
            let data = null;
            if (!txt) throw new Error('Empty response from server');
            try { data = JSON.parse(txt); } catch (e) { throw new Error('Invalid JSON response from server'); }
            if (!data.success) throw new Error(data.message || 'Error saving');
            riderModalBackdrop.style.display = 'none';
            await fetchRiders(searchInput.value.trim());
        } catch (err) {
            alert(err.message || 'Failed');
            console.error(err);
        }
    });

    async function editRider(id) {
        const r = riders.find(x => x.rider_id == id);
        if (r) {
            openRiderModal(r);
            return;
        }
        // Fallback: fetch specific
        try {
            const res = await fetch('api/riders_api.php?action=get&id=' + encodeURIComponent(id));
            const txt = await res.text();
            if (!txt) throw new Error('Empty response from server');
            let data = null;
            try { data = JSON.parse(txt); } catch(e) { throw new Error('Invalid JSON response from server'); }
            if (!data.success) throw new Error(data.message || 'Failed to load rider');
            openRiderModal(data.rider);
        } catch (err) {
            alert(err.message || 'Failed to load rider');
        }
    }

    async function deleteRider(id) {
        if (!confirm('Delete this rider? This action cannot be undone.')) return;
        try {
            const res = await fetch('api/riders_api.php?action=delete', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(id)
            });
            const txt = await res.text();
            if (!txt) throw new Error('Empty response from server');
            let data = null;
            try { data = JSON.parse(txt); } catch(e) { throw new Error('Invalid JSON response from server'); }
            if (!data.success) throw new Error(data.message || 'Delete failed');
            await fetchRiders(searchInput.value.trim());
        } catch (err) {
            alert(err.message || 'Failed to delete');
            console.error(err);
        }
    }

    // Search
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchRiders(searchInput.value.trim());
        }, 350);
    });

    // Close modal on backdrop click
    riderModalBackdrop.addEventListener('click', (e) => {
        if (e.target === riderModalBackdrop) {
            riderModalBackdrop.style.display = 'none';
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && riderModalBackdrop.style.display === 'flex') {
            riderModalBackdrop.style.display = 'none';
        }
    });

    // Sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');

    sidebarToggle?.addEventListener('click', () => sidebar.classList.toggle('collapsed'));
    mobileMenuToggle?.addEventListener('click', () => sidebar.classList.toggle('mobile-open'));

    // Initial load
    fetchRiders();
</script>
</body>
</html>
