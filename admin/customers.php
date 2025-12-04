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
    <title>Customers - ShoeTakels Admin</title>
    <link rel="stylesheet" href="asset/style/admin-dashboard.css">
    <link rel="stylesheet" href="asset/style/customer.css">
</head>
<body>
    <div class="dashboard-container">
        <?php
            // Inlined admin sidebar (previously in partials/admin-sidebar.php)
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
                // Inlined admin header (previously in partials/admin-header.php)
                $notification_count = $_SESSION['admin_notifications'] ?? 3;
            ?>
            <header class="header" role="banner">
                <div class="header-left">
                    <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open menu" title="Open menu">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="search-box" role="search" aria-label="Site search">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input id="globalSearchInput" type="text" placeholder="Search..." aria-label="Search">
                    </div>
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

            <div class="dashboard-content">
                <div class="page-title" style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <h1>Customers</h1>
                        <div class="muted" style="margin-top:.25rem;">Manage customers, view contact details and account creation date.</div>
                    </div>
                    <div>
                        <button id="addCustomerBtn" class="btn" aria-haspopup="dialog">Add Customer</button>
                    </div>
                </div>

                <div class="customers-wrapper" role="region" aria-label="Customers">
                    <div class="table-toolbar" aria-hidden="false">
                        <div class="table-controls">
                            <div class="search-input" title="Search customers">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input id="customersSearch" type="search" placeholder="Search name, email, phone..." aria-label="Search customers">
                            </div>
                            <div class="muted" style="margin-left:.5rem;">Showing <span id="customersCount">—</span></div>
                        </div>

                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <div class="pagination" role="navigation" aria-label="Customers pagination">
                                <button class="page-btn" id="prevPage" aria-label="Previous page">&larr;</button>
                                <div class="muted" id="pageInfo">1 / 1</div>
                                <button class="page-btn" id="nextPage" aria-label="Next page">&rarr;</button>
                            </div>
                        </div>
                    </div>

                    <div style="overflow:auto;">
                        <table class="table customers-table" id="customersTable" aria-describedby="customersDescription">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Joined</th>
                                    <th style="width:140px;text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="customersTbody">
                                <tr><td colspan="5" class="no-data">Loading customers…</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="customersDescription" class="muted" style="margin-top:.6rem">You can search, edit or delete customers. Data loads from the server.</div>
                </div>
            </div>
        </main>
    </div>

    <!-- BEAUTIFIED MODAL -->
    <div class="modal-backdrop" id="customerModalBackdrop" aria-hidden="true" style="display:none; align-items:center; justify-content:center; padding:1.25rem;">
        <div class="modal" id="customerModal" role="dialog" aria-modal="true" aria-labelledby="customerModalTitle" tabindex="-1">
            <div class="modal-header">
                <div>
                    <h3 id="customerModalTitle" class="modal-title">Add Customer</h3>
                    <div class="modal-sub" id="customerModalSub">Create or edit customer information — email is required.</div>
                </div>

                <div style="display:flex;gap:.5rem;">
                    <button type="button" class="modal-close btn ghost" id="closeCustomerModal" aria-label="Close dialog">
                        <svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>

            <form id="customerForm" class="modal-body" novalidate>
                <div class="form-card" aria-hidden="false">
                    <div class="input-field">
                        <label for="c_name">Full name</label>
                        <input type="text" name="name" id="c_name" placeholder="Full name" required>
                    </div>

                    <div class="input-field">
                        <label for="c_email">Email</label>
                        <input type="email" name="email" id="c_email" placeholder="Email" required>
                    </div>

                    <div class="input-field">
                        <label for="c_phone">Phone</label>
                        <input type="text" name="phone" id="c_phone" placeholder="Phone">
                    </div>

                    <div class="input-field">
                        <label for="c_address">Address</label>
                        <input type="text" name="address" id="c_address" placeholder="Address">
                    </div>

                    <div class="input-field">
                        <label for="c_password">Password <span id="passwordNote" class="muted" style="font-size:.8rem;">(required for new customers)</span></label>
                        <input type="password" name="password" id="c_password" placeholder="Password">
                    </div>

                    <input type="hidden" name="customer_id" id="customer_id" value="">

                    <div class="modal-actions">
                        <button type="button" class="btn ghost" id="cancelCustomer" aria-label="Cancel">Cancel</button>
                        <button class="btn save" id="saveCustomer" type="submit" aria-live="polite">
                            <span class="save-text">Save</span>
                        </button>
                    </div>
                </div>

                <aside style="padding: .25rem .6rem; max-width:320px;">
                    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;">
                        <div class="avatar" id="modalAvatar" aria-hidden="true">A</div>
                        <div>
                            <div style="font-weight:700" id="modalNamePreview">New customer</div>
                            <div class="muted" style="font-size:.85rem" id="modalEmailPreview">will appear here</div>
                        </div>
                    </div>

                    <div class="muted" style="font-size:.9rem">Tip: Email must be unique. Use the save button to persist changes.</div>
                </aside>
            </form>
        </div>
    </div>

<script>
    const customersTbody = document.getElementById('customersTbody');
    const addCustomerBtn = document.getElementById('addCustomerBtn');
    const customerModalBackdrop = document.getElementById('customerModalBackdrop');
    const customerModal = document.getElementById('customerModal');
    const customerForm = document.getElementById('customerForm');
    const cancelCustomer = document.getElementById('cancelCustomer');
    const closeCustomerModal = document.getElementById('closeCustomerModal');
    const saveCustomerBtn = document.getElementById('saveCustomer');

    const customersSearch = document.getElementById('customersSearch');
    const customersCount = document.getElementById('customersCount');
    const prevPage = document.getElementById('prevPage');
    const nextPage = document.getElementById('nextPage');
    const pageInfo = document.getElementById('pageInfo');

    const modalAvatar = document.getElementById('modalAvatar');
    const modalNamePreview = document.getElementById('modalNamePreview');
    const modalEmailPreview = document.getElementById('modalEmailPreview');

    let customers = [];
    let filtered = [];
    let page = 1;
    const perPage = 8;

    function formatDateTime(s) {
        if (!s) return '—';
        try {
            const d = new Date(s);
            return d.toLocaleDateString() + ' • ' + d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        } catch {
            return s;
        }
    }

    async function fetchCustomers() {
        customersTbody.innerHTML = '<tr><td colspan="5" class="no-data">Loading customers…</td></tr>';
        try {
            const res = await fetch('api/customers_api.php?action=list');
            const data = await res.json();
            if (!data.success) {
                const msg = data.message || 'Failed to load customers';
                throw Object.assign(new Error(msg), { code: res.status, apiMessage: msg });
            }
            customers = Array.isArray(data.customers) ? data.customers : [];
            filtered = customers.slice();
            page = 1;
            renderCustomers();
        } catch (err) {
            console.error(err);
            const msg = err.apiMessage || err.message || 'Error loading customers';
            let extra = '';
            if (msg.toLowerCase().includes('unauthorized') || msg.toLowerCase().includes('login')) {
                extra = ` <a href="login.php" style="color:var(--primary); font-weight:600;">Login</a>`;
            }
            customersTbody.innerHTML = `
                <tr>
                    <td colspan="5" class="no-data">
                        <div>${escapeHtml(msg)}${extra}</div>
                        <div style="margin-top:.75rem"><button id="retryFetch" class="btn">Retry</button></div>
                    </td>
                </tr>
            `;
            document.getElementById('retryFetch')?.addEventListener('click', fetchCustomers);
        }
    }

    function renderCustomers() {
        customersCount.textContent = filtered.length;
        const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
        page = Math.min(Math.max(1, page), totalPages);
        const start = (page - 1) * perPage;
        const pageItems = filtered.slice(start, start + perPage);

        pageInfo.textContent = `${page} / ${totalPages}`;

        if (!pageItems.length) {
            customersTbody.innerHTML = '<tr><td colspan="5" class="no-data">No customers</td></tr>';
            return;
        }
        customersTbody.innerHTML = '';
        pageItems.forEach(c => {
            const tr = document.createElement('tr');

            const initials = getInitials(c.name || c.email || '');
            const nameCell = document.createElement('td');
            nameCell.innerHTML = `
                <div class="customer-name-cell">
                    <div class="avatar" aria-hidden="true">${escapeHtml(initials)}</div>
                    <div>
                        <div style="font-weight:700;">${escapeHtml(c.name || 'Unnamed')}</div>
                        <div class="muted" style="margin-top:.15rem;">${escapeHtml(c.address || '')}</div>
                    </div>
                </div>
            `;

            const emailCell = document.createElement('td');
            emailCell.textContent = c.email || '';

            const phoneCell = document.createElement('td');
            phoneCell.textContent = c.phone || '—';

            const joinedCell = document.createElement('td');
            joinedCell.textContent = formatDateTime(c.created_at || c.joined || '');

            const actionsCell = document.createElement('td');
            actionsCell.style.textAlign = 'center';
            actionsCell.innerHTML = `
                <button class="action-btn ghost" data-action="edit" data-id="${escapeHtml(c.customer_id)}" title="Edit">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M16.5 3.5l4 4L8 20H4v-4L16.5 3.5z"/></svg>
                </button>
                <button class="action-btn danger" data-action="delete" data-id="${escapeHtml(c.customer_id)}" title="Delete">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M10 11v6M14 11v6M9 7l1-3h4l1 3M5 7h14v12a2 2 0 01-2 2H7a2 2 0 01-2-2z"/></svg>
                </button>
            `;

            tr.appendChild(nameCell);
            tr.appendChild(emailCell);
            tr.appendChild(phoneCell);
            tr.appendChild(joinedCell);
            tr.appendChild(actionsCell);
            customersTbody.appendChild(tr);
        });
    }

    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function getInitials(name) {
        if (!name) return 'U';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) return parts[0].slice(0,2).toUpperCase();
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }

    addCustomerBtn.addEventListener('click', () => {
        openCustomerModal();
    });

    cancelCustomer.addEventListener('click', () => {
        closeModal();
    });

    closeCustomerModal?.addEventListener('click', () => closeModal());

    // Handle keyboard: Esc to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && customerModalBackdrop.classList.contains('open')) {
            closeModal();
        }
    });

    // Delegate edit/delete actions
    document.addEventListener('click', e => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const act = btn.dataset.action;
        const id = btn.dataset.id;
        if (act === 'edit') editCustomer(id);
        if (act === 'delete') openDeleteConfirm(id);
    });

    // Confirmation modal elements
    const confirmBackdrop = document.createElement('div');
    confirmBackdrop.id = 'confirmBackdrop';
    confirmBackdrop.className = 'confirm-backdrop';
    confirmBackdrop.innerHTML = `
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
            <div class="confirm-title" id="confirmTitle">Confirm deletion</div>
            <div class="confirm-desc" id="confirmDesc">Are you sure you want to delete this item? This action cannot be undone.</div>
            <div class="confirm-actions">
                <button class="btn ghost" id="cancelConfirm">Cancel</button>
                <button class="btn danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    `;
    document.body.appendChild(confirmBackdrop);

    let pendingDeleteId = null;
    function openDeleteConfirm(id) {
        pendingDeleteId = id;
        const c = customers.find(x => String(x.customer_id) === String(id));
        const name = c ? (c.name || c.email || ('#' + id)) : ('#' + id);
        document.getElementById('confirmDesc').textContent = `Delete customer ${name}? This cannot be undone.`;
        confirmBackdrop.classList.add('open');
        document.getElementById('confirmDelete').focus();
    }

    function closeDeleteConfirm() {
        pendingDeleteId = null;
        confirmBackdrop.classList.remove('open');
    }

    document.getElementById('cancelConfirm')?.addEventListener('click', closeDeleteConfirm);
    document.getElementById('confirmDelete')?.addEventListener('click', async () => {
        if (!pendingDeleteId) return closeDeleteConfirm();
        // perform delete
        try {
            const res = await fetch('api/customers_api.php?action=delete', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(pendingDeleteId)
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Delete failed');
            closeDeleteConfirm();
            await fetchCustomers();
        } catch (err) {
            alert(err.message || 'Delete failed');
            console.error(err);
        }
    });

    // close confirm on backdrop click or Escape
    confirmBackdrop.addEventListener('click', (e) => { if (e.target === confirmBackdrop) closeDeleteConfirm(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeDeleteConfirm(); });

    function openCustomerModal(data = null) {
        document.getElementById('customerModalTitle').textContent = data ? 'Edit Customer' : 'Add Customer';
        document.getElementById('customerModalSub').textContent = data ? 'Modify customer information.' : 'Create a new customer. Email is required.';

        customerForm.customer_id.value = data ? data.customer_id : '';
        customerForm.c_name.value = data ? data.name : '';
        customerForm.c_email.value = data ? data.email : '';
        customerForm.c_phone.value = data ? data.phone : '';
        customerForm.c_address.value = data ? data.address : '';
        customerForm.c_password.value = '';
        
        // Update password field hint based on add/edit mode
        const passwordNote = document.getElementById('passwordNote');
        if (passwordNote) {
            passwordNote.textContent = data ? '(leave blank to keep current)' : '(required for new customers)';
        }

        // update preview
        modalAvatar.textContent = getInitials(data ? data.name || data.email : '');
        modalNamePreview.textContent = data ? (data.name || 'Unnamed') : 'New customer';
        modalEmailPreview.textContent = data ? (data.email || '') : 'will appear here';

        // show backdrop and animate
        customerModalBackdrop.style.display = 'flex';
        requestAnimationFrame(() => {
            customerModalBackdrop.classList.add('open');
            customerModal.classList.add('open');
            customerModalBackdrop.setAttribute('aria-hidden', 'false');

            // focus management: move focus to first input
            const first = customerForm.querySelector('input:not([type=hidden])');
            if (first) {
                first.focus();
            }

            // trap focus
            trapFocus(customerModal);
        });
    }

    function closeModal() {
        customerModalBackdrop.classList.remove('open');
        customerModal.classList.remove('open');
        customerModalBackdrop.setAttribute('aria-hidden', 'true');
        setTimeout(() => {
            customerModalBackdrop.style.display = 'none';
            customerForm.reset();
            saveCustomerBtn.classList.remove('loading');
            // release focus trap by focusing the Add button
            addCustomerBtn.focus();
        }, 260);
    }

    // Focus trap utility (simple)
    let focusableElements = [];
    let firstFocusable = null;
    let lastFocusable = null;
    function trapFocus(container) {
        focusableElements = Array.from(container.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'))
            .filter(el => el.offsetParent !== null);
        firstFocusable = focusableElements[0];
        lastFocusable = focusableElements[focusableElements.length - 1];
        container.addEventListener('keydown', handleTrap);
    }
    function handleTrap(e) {
        if (e.key !== 'Tab') return;
        if (focusableElements.length === 0) {
            e.preventDefault();
            return;
        }
        if (e.shiftKey) {
            if (document.activeElement === firstFocusable) {
                e.preventDefault();
                lastFocusable.focus();
            }
        } else {
            if (document.activeElement === lastFocusable) {
                e.preventDefault();
                firstFocusable.focus();
            }
        }
    }
    function releaseTrap(container) {
        container.removeEventListener('keydown', handleTrap);
    }

    customerForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(customerForm);
        const id = fd.get('customer_id');
        const action = id ? 'update' : 'create';

        // client-side validation
        const email = (fd.get('email') || '').trim();
        if (!email) {
            alert('Email is required.');
            return;
        }
        
        // Password required for new customers
        const password = (fd.get('password') || '').trim();
        const customerId = fd.get('customer_id');
        if (!customerId && !password) {
            alert('Password is required for new customers.');
            return;
        }

        // show small loading state on save button
        saveCustomerBtn.classList.add('loading');
        const originalLabel = saveCustomerBtn.querySelector('.save-text')?.textContent || '';
        saveCustomerBtn.querySelector('.save-text').textContent = 'Saving...';

        try {
            const res = await fetch('api/customers_api.php?action=' + action, { method: 'POST', body: fd });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed');
            // subtle success animation: briefly tint one of the avatars
            setTimeout(() => {}, 150);

            closeModal();
            // after change, refresh list
            await fetchCustomers();
        } catch (err) {
            alert(err.message || 'Failed');
            console.error(err);
        } finally {
            saveCustomerBtn.classList.remove('loading');
            saveCustomerBtn.querySelector('.save-text').textContent = originalLabel || 'Save';
        }
    });

    async function editCustomer(id) {
        // try to find in memory first
        const c = customers.find(x => String(x.customer_id) === String(id));
        if (c) {
            openCustomerModal(c);
            return;
        }
        try {
            const res = await fetch('api/customers_api.php?action=get&id=' + encodeURIComponent(id));
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed');
            openCustomerModal(data.customer);
        } catch (err) {
            alert(err.message || 'Failed to load');
        }
    }

    async function deleteCustomer(id) {
        if (!confirm('Delete this customer?')) return;
        try {
            const res = await fetch('api/customers_api.php?action=delete', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(id)
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Delete failed');
            await fetchCustomers();
        } catch (err) {
            alert(err.message || 'Delete failed');
            console.error(err);
        }
    }

    customerModalBackdrop.addEventListener('click', (e) => {
        if (e.target === customerModalBackdrop) {
            // release trap
            releaseTrap(customerModal);
            closeModal();
        }
    });

    // Search input: filter client-side
    function applyFilter(q) {
        const term = String(q || '').trim().toLowerCase();
        if (!term) {
            filtered = customers.slice();
        } else {
            filtered = customers.filter(c => {
                return (c.name && c.name.toLowerCase().includes(term))
                    || (c.email && c.email.toLowerCase().includes(term))
                    || (c.phone && c.phone.toLowerCase().includes(term))
                    || (c.address && c.address.toLowerCase().includes(term));
            });
        }
        page = 1;
        renderCustomers();
    }

    customersSearch?.addEventListener('input', (e) => {
        applyFilter(e.target.value);
    });

    // Connect to header global search if used
    document.addEventListener('admin:search', (e) => {
        const q = e.detail?.q;
        if (typeof q === 'string') {
            customersSearch.value = q;
            applyFilter(q);
        }
    });

    prevPage.addEventListener('click', () => {
        page = Math.max(1, page - 1);
        renderCustomers();
    });
    nextPage.addEventListener('click', () => {
        const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
        page = Math.min(totalPages, page + 1);
        renderCustomers();
    });

    // Simple live preview update
    ['c_name', 'c_email'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', () => {
            modalAvatar.textContent = getInitials(document.getElementById('c_name').value || document.getElementById('c_email').value);
            modalNamePreview.textContent = document.getElementById('c_name').value || 'New customer';
            modalEmailPreview.textContent = document.getElementById('c_email').value || '';
        });
    });

    // initial load
    fetchCustomers();
</script>
</body>
</html>