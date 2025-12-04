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

            <div class="dashboard-content">
                <div class="page-title" style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <h1>Orders</h1>
                        <div class="muted" style="margin-top:.25rem;">Manage orders, view payment proofs and update statuses.</div>
                    </div>
 
                </div>

                <div class="customers-wrapper" role="region" aria-label="Orders">
                    <div class="table-toolbar">
                        <div class="table-controls">
                            <div class="search-input" title="Search orders">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <!-- id changed to orderSearch to match the script below -->
                                <input id="orderSearch" type="search" placeholder="Search order id, customer..." aria-label="Search orders">
                            </div>
                            <div class="muted" style="margin-left:.5rem;">Showing <span id="ordersCount">—</span></div>
                        </div>
                        <div class="pagination">
                            <button class="page-btn" id="prevOrderPage" aria-label="Previous page">&larr;</button>
                            <div class="muted" id="orderPageInfo">1 / 1</div>
                            <button class="page-btn" id="nextOrderPage" aria-label="Next page">&rarr;</button>
                        </div>
                    </div>

                    <div style="overflow:auto;">
                        <table class="table orders-table" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Rider</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th style="width:160px;text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTbody">
                                <tr><td colspan="7" class="no-data">Loading orders…</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="muted" style="margin-top:.6rem">You can search, filter and view order proofs from this panel.</div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal-backdrop" id="orderModalBackdrop" aria-hidden="true" style="display:none; align-items:center; justify-content:center; padding:1.25rem;">
        <div class="modal" id="orderModal" role="dialog" aria-modal="true" aria-labelledby="orderModalTitle" tabindex="-1">
            <div class="modal-header">
                <div>
                    <h3 id="orderModalTitle" class="modal-title">Order Details</h3>
                    <div id="orderModalSub" class="modal-sub">View order information, payment proof and update status.</div>
                </div>

                <div style="display:flex;gap:.5rem;">
                    <button type="button" class="modal-close btn ghost" id="closeOrderModal" aria-label="Close dialog">
                        <svg viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>
            </div>

            <div class="modal-body">
                <div class="modal-main">
                    <div id="orderDetails" class="form-card">
                        <p>Loading…</p>
                    </div>
                    <div class="modal-actions" style="margin-top:.6rem;">
                        <button class="btn ghost" id="closeOrderModalBottom">Close</button>
                    </div>
                </div>

                <aside class="modal-side">
                    <div id="orderSideCard" class="form-card">
                        <strong>Order Info</strong>
                        <div class="muted">Contact, payment method and proof will appear here.</div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

<script>
    const ordersTbody = document.getElementById('ordersTbody');
    const orderModalBackdrop = document.getElementById('orderModalBackdrop');
    const orderDetails = document.getElementById('orderDetails');
    const orderModal = document.getElementById('orderModal');
    // support both IDs just in case: orderSearch (new) or orderSearchInline (old)
    const orderSearch = document.getElementById('orderSearch') || document.getElementById('orderSearchInline');

    let orders = [];
    let searchTimeout;

    async function fetchOrders(q = '') {
        ordersTbody.innerHTML = '<tr><td colspan="7" class="no-data">Loading orders…</td></tr>';
        try {
            const res = await fetch('api/orders_api.php?action=list' + (q ? '&q=' + encodeURIComponent(q) : ''));
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed to load');
            orders = data.orders;
            renderOrders();
        } catch (err) {
            ordersTbody.innerHTML = '<tr><td colspan="7" class="no-data">Error loading orders</td></tr>';
            console.error(err);
        }
    }

    function renderOrders() {
        if (!orders.length) {
            ordersTbody.innerHTML = '<tr><td colspan="7" class="no-data">No orders found</td></tr>';
            return;
        }
        ordersTbody.innerHTML = '';
        orders.forEach(o => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${o.order_id}</td>
                <td>${escapeHtml(o.customer_name || 'Guest')}</td>
                <td>${escapeHtml(o.rider_name || '—')}</td>
                <td>₱${Number(o.total_amount).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td>
                <td>${escapeHtml(o.order_date)}</td>
                <td>
                    <select class="status-select status-${o.status || 'pending'}" data-id="${o.order_id}" data-current="${o.status || ''}" aria-label="Order status for ${escapeHtml(o.order_id)}">
                        <option value="pending" ${o.status=='pending'?'selected':''}>Pending</option>
                        <option value="confirmed" ${o.status=='confirmed'?'selected':''}>Confirmed</option>
                        <option value="delivering" ${o.status=='delivering'?'selected':''}>Delivering</option>
                        <option value="completed" ${o.status=='completed'?'selected':''}>Completed</option>
                        <option value="cancelled" ${o.status=='cancelled'?'selected':''}>Cancelled</option>
                    </select>
                </td>
                <td style="text-align:center;">
                    <button class="btn ghost" data-action="view" data-id="${o.order_id}">View</button>
                    ${o.refund_status ? (o.refund_status === 'requested' ? `<button class="btn" data-action="refund" data-id="${o.order_id}" data-refund-id="${o.refund_id}">Process Refund</button>` : `<button class="btn ghost" disabled>Refund: ${escapeHtml(o.refund_status)}</button>`) : ''}
                </td>
            `;
            ordersTbody.appendChild(tr);
        });
    }

    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // delegate events
    document.addEventListener('change', async (e) => {
        const sel = e.target.closest('.status-select');
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
                // update select class to reflect new status
                try {
                    const statuses = ['pending','confirmed','delivering','completed','cancelled'];
                    statuses.forEach(s => sel.classList.remove('status-' + s));
                    sel.classList.add('status-' + statusToSet);
                    sel.dataset.current = statusToSet;
                } catch (err) {
                    // non-critical
                }
                return true;
            } catch (err) {
                    // show more helpful error information to the admin
                    const msg = (err && err.message) ? err.message : String(err);
                    alert('Failed to update status: ' + msg);
                    console.error(err);
                    return false;
                }
        }

        if (newStatus === 'confirmed') {
            const ok = confirm('Confirm this order?');
            if (!ok) {
                sel.value = prevStatus;
                return;
            }
            const okStatus = await doUpdate('confirmed');
            if (!okStatus) {
                sel.value = prevStatus;
                return;
            }
            // refresh list after confirming (do not auto-open modal)
            await fetchOrders();
            return;
        }

        // Non-confirmed status change: just update
        const okNormal = await doUpdate(newStatus);
        if (!okNormal) sel.value = prevStatus;
    });

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('button[data-action="view"]');
        if (btn) {
            const id = btn.dataset.id;
                // push id into URL so modal is linkable
                try {
                    const u = new URL(window.location.href);
                    u.searchParams.set('id', id);
                    const qs = u.searchParams.toString();
                    history.pushState({orderModalId: id}, '', u.pathname + (qs ? ('?' + qs) : ''));
                } catch (err) {
                    console.warn('Could not push state', err);
                }
                openOrderModal(id);
            return;
        }

        const rbtn = e.target.closest('button[data-action="refund"]');
        if (rbtn) {
            const id = rbtn.dataset.id;
            const refundId = rbtn.dataset.refundId;
            if (!confirm('Approve and process refund for order #' + id + '?')) return;
            try {
                const form = new URLSearchParams();
                form.append('id', id);
                form.append('op', 'approve');
                const res = await fetch('api/orders_api.php?action=refund', {
                    method: 'POST',
                    body: form
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Refund API error');
                alert('Refund marked as ' + data.status);
                // refresh orders list
                fetchOrders(orderSearch ? orderSearch.value.trim() : '');
            } catch (err) {
                alert('Failed to process refund: ' + (err.message || String(err)));
                console.error(err);
            }
            return;
        }
    });

    // click backdrop to close modal (with transition)
    orderModalBackdrop?.addEventListener('click', (e) => {
        if (e.target === orderModalBackdrop) {
            // remove open classes to animate out
            orderModal?.classList.remove('open');
            orderModalBackdrop.classList.remove('open');
            orderModalBackdrop.setAttribute('aria-hidden', 'true');
            setTimeout(() => {
                orderModalBackdrop.style.display = 'none';
            }, 220);
        }
    });

    async function openOrderModal(id) {
        if (!orderModalBackdrop) {
            console.error('orderModalBackdrop element not found');
            return;
        }
        // show backdrop first so user sees loading state
        orderModalBackdrop.style.display = 'flex';
        // add open classes to trigger blur & modal animation
        orderModalBackdrop.classList.add('open');
        orderModal?.classList.add('open');
        orderModalBackdrop.setAttribute('aria-hidden', 'false');
        orderDetails.innerHTML = '<p>Loading order details…</p>';

        try {
            console.debug('Fetching order details for id=', id);
            const res = await fetch('api/orders_api.php?action=get&id=' + encodeURIComponent(id));
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseErr) {
                // non-JSON response
                console.error('Non-JSON response for order get:', text);
                const errHtml = `
                    <div class="no-data">
                        <div style="font-size:28px;color:var(--error)"><i class="fa fa-circle-exclamation"></i></div>
                        <div style="margin-top:.5rem;">Error loading order: server returned invalid JSON.</div>
                        <pre style="white-space:pre-wrap;max-height:240px;overflow:auto">${escapeHtml(text)}</pre>
                        <div style="margin-top:.6rem;"><button class="btn" id="retryOrderFetch">Retry</button></div>
                    </div>
                `;
                orderDetails.innerHTML = errHtml;
                document.getElementById('retryOrderFetch')?.addEventListener('click', () => openOrderModal(id));
                return;
            }
            if (!res.ok) {
                console.error('Network error when fetching order:', res.status, data);
                const errHtml = `
                    <div class="no-data">
                        <div style="font-size:28px;color:var(--error)"><i class="fa fa-circle-exclamation"></i></div>
                        <div style="margin-top:.5rem;">Error loading order: ${escapeHtml(data.message || ('HTTP ' + res.status))}</div>
                        <div style="margin-top:.6rem;"><button class="btn" id="retryOrderFetch">Retry</button></div>
                    </div>
                `;
                orderDetails.innerHTML = errHtml;
                document.getElementById('retryOrderFetch')?.addEventListener('click', () => openOrderModal(id));
                return;
            }
            if (!data.success) {
                console.error('API error when fetching order:', data);
                const errHtml = `
                    <div class="no-data">
                        <div style="font-size:28px;color:var(--error)"><i class="fa fa-circle-exclamation"></i></div>
                        <div style="margin-top:.5rem;">Error loading order: ${escapeHtml(data.message || 'Unknown error')}</div>
                        <div style="margin-top:.6rem;"><button class="btn" id="retryOrderFetch">Retry</button></div>
                    </div>
                `;
                orderDetails.innerHTML = errHtml;
                document.getElementById('retryOrderFetch')?.addEventListener('click', () => openOrderModal(id));
                return;
            }
            const o = data.order;
            const itemsHtml = (o.items || []).map(i => {
                const qty = (i.qty ?? i.quantity ?? i.qty_order ?? 1);
                const priceVal = (i.price ?? i.unit_price ?? i.amount ?? 0);
                const name = i.name ?? i.product_name ?? 'Item';
                return `<li>${escapeHtml(name)} — ₱${Number(priceVal).toFixed(2)} x ${escapeHtml(String(qty))}</li>`;
            }).join('');
            const proofCandidates = [o.payment_proof, o.payment_proof_url, o.payment_proof_path, o.proof, o.proof_url, o.proof_path];
            const proof = proofCandidates.find(p => p && String(p).trim());
            let proofLargeHtml = '';


            orderDetails.innerHTML = proofLargeHtml + `
                <div><strong>Order #${escapeHtml(String(o.order_id))}</strong></div>
                <div>Customer: ${escapeHtml(o.customer_name || 'Guest')}</div>
                <div>Amount: ₱${Number(o.total_amount).toLocaleString(undefined,{minimumFractionDigits:2})}</div>
                <div>Date: ${escapeHtml(o.order_date)}</div>
                <div style="margin-top:.5rem"><strong>Items:</strong>
                    <ul class="order-items">${itemsHtml}</ul>
                </div>
            `;

            // populate side card with contact/status/proof thumbnail
            const side = document.getElementById('orderSideCard');
            if (side) {
                const phone = escapeHtml(o.customer_phone || o.phone || o.contact || '—');
                // Try a list of common address column names returned by different APIs/schemas
                const addressCandidates = [
                    o.shipping_address,
                    o.shipping_addr,
                    o.shipping_address_line,
                    o.delivery_address,
                    o.address,
                    o.ship_address,
                    o.customer_address,
                    o.customer_addr,
                    o.address_line1,
                    o.address_line2
                ];
                const foundAddress = addressCandidates.find(a => a && String(a).trim());
                const address = escapeHtml(foundAddress || '—');
                const paymentMethod = escapeHtml(o.payment_method || o.payment || o.method || '—');
                let sideHtml = `
                    <strong>Contact</strong>
                    <div class="muted">${phone}</div>
                    <div style="margin-top:.5rem"><strong>Address</strong>
                        <div class="muted">${address}</div>
                    </div>
                    <div style="margin-top:.6rem"><strong>Payment</strong>
                        <div class="muted">${paymentMethod}</div>
                    </div>
                `;
                
                if (proof) {
                    const src = String(proof).replace(/\\/g, '/').replace(/%20/g, '').trim();
                    sideHtml += `
                        <div style="margin-top:.6rem"><strong>Payment proof</strong>
                            <a href="${escapeHtml(src)}" target="_blank" rel="noopener noreferrer">
                                <img src="${escapeHtml(src)}" alt="proof" style="width:100%;height:auto;border-radius:8px;margin-top:.45rem;">
                            </a>
                        </div>
                    `;
                }

                const deliveryCandidates = [o.delivery_proof, o.delivery_proof_url, o.delivery_proof_path, o.delivery_proof_link, o.delivery_proof_img, o.delivery_photo, o.delivery_proof_file, o.deliver_proof];
                const deliveryProof = deliveryCandidates.find(p => p && String(p).trim());
                if (deliveryProof) {
                    const src2 = String(deliveryProof).replace(/\\/g, '/').replace(/%20/g, '').trim();
                    sideHtml += `
                        <div style="margin-top:.6rem"><strong>Delivery proof</strong>
                            <a href="${escapeHtml(src2)}" target="_blank" rel="noopener noreferrer">
                                <img src="${escapeHtml(src2)}" alt="delivery proof" style="width:100%;height:auto;border-radius:8px;margin-top:.45rem;">
                            </a>
                        </div>
                    `;
                }
                side.innerHTML = sideHtml;
            }

            const modal = document.getElementById('orderModal');
            modal?.focus();
            trapFocus(modal);
        } catch (err) {
            console.error(err);
            const errHtml = `
                <div class="no-data">
                    <div style="font-size:28px;color:var(--error)"><i class="fa fa-circle-exclamation"></i></div>
                    <div style="margin-top:.5rem;">Error loading order: ${escapeHtml(err.message || String(err))}</div>
                    <div style="margin-top:.6rem;"><button class="btn" id="retryOrderFetch">Retry</button></div>
                </div>
            `;
            orderDetails.innerHTML = errHtml;
            document.getElementById('retryOrderFetch')?.addEventListener('click', () => openOrderModal(id));
        }
    }

    function closeOrderModal() {
        const modal = document.getElementById('orderModal');
        releaseTrap(modal);
        orderModal?.classList.remove('open');
        orderModalBackdrop.classList.remove('open');
        orderModalBackdrop.setAttribute('aria-hidden', 'true');
        setTimeout(() => {
            orderModalBackdrop.style.display = 'none';
                try {
                    // remove id from URL without adding history entry
                    const u = new URL(window.location.href);
                    u.searchParams.delete('id');
                    const qs = u.searchParams.toString();
                    history.replaceState({}, '', u.pathname + (qs ? ('?' + qs) : ''));
                } catch (err) {
                    // ignore
                }
        }, 220);
    }

        // Handle back/forward navigation: open or close modal based on ?id param
        window.addEventListener('popstate', (ev) => {
            try {
                const params = new URLSearchParams(window.location.search);
                const id = params.get('id');
                if (id) {
                    openOrderModal(id);
                } else {
                    // if modal is open, close it
                    const backdrop = document.getElementById('orderModalBackdrop');
                    if (backdrop && backdrop.style.display !== 'none') closeOrderModal();
                }
            } catch (err) {
                console.error('popstate handler error', err);
            }
        });
    document.getElementById('closeOrderModal')?.addEventListener('click', closeOrderModal);
    document.getElementById('closeOrderModalBottom')?.addEventListener('click', closeOrderModal);

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

    if (orderSearch) {
        orderSearch.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchOrders(orderSearch.value.trim());
            }, 300);
        });
    } else {
        console.warn('Order search input not found; search disabled.');
    }

    // Fetch orders then open modal if URL has ?id=...
    fetchOrders().then(() => {
        try {
            const params = new URLSearchParams(window.location.search);
            const id = params.get('id');
            if (id) {
                // slight delay to ensure UI is ready
                setTimeout(() => openOrderModal(id), 150);
            }
        } catch (e) {
            console.error('Failed to open order from URL:', e);
        }
    });

</script>
</body>
</html>