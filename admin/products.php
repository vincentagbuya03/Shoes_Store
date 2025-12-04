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
    <title>Products - ShoeTakels Admin</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/admin-dashboard.css">
    <link rel="stylesheet" href="asset/style/products.css">
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
        <!-- If partial is not available, fallback to simple sidebar -->
        <aside class="sidebar" id="sidebar" style="display:none;"></aside>

        <main class="main-content">
            <!-- Header (reused) -->
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

            <div class="products-panel">
            <div class="dashboard-content">
                <div class="page-title" style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <h1>Products</h1>
                    </div>
                    <div class="page-actions">
                        <input id="searchInput" class="page-search" type="search" placeholder="Search products...">
                        <button class="btn" id="addProductBtn">Add Product</button>
                    </div>
                </div>

                <div id="productsRegion">
                    <table class="table" id="productsTable" aria-live="polite">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Brand</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Category</th>
                                <th style="width:150px;text-align:center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productsTbody">
                            <tr><td colspan="6" class="no-data">Loading products…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Add / Edit -->
    <div class="modal-backdrop" id="productModalBackdrop" role="dialog" aria-modal="true">
        <div class="modal" role="document" id="productModal">
            <h3 id="productModalTitle">Add Product</h3>
            <div id="productMeta" class="product-meta" aria-hidden="true" style="display:none">
                <div id="metaId">ID: <span id="metaIdVal"></span></div>
                <div id="metaCreated">Created: <span id="metaCreatedVal"></span></div>
            </div>
            <form id="productForm">
                <input type="hidden" name="product_id" id="product_id" value="">
                <div class="form-row">
                    <input type="text" name="name" id="name" placeholder="Product name" required>
                    <input type="text" name="brand_name" id="brand_name" placeholder="Brand name">
                </div>
                <div class="form-row">
                    <input type="number" name="price" id="price" placeholder="Price" step="0.01" required>
                    <input type="number" name="stock" id="stock" placeholder="Stock" required>
                </div>
                <div class="form-row">
                    <input type="text" name="category" id="category" placeholder="Category">
                    <div style="display:flex;gap:.5rem;flex-direction:column;">
                            <label style="font-size:.8rem;color:var(--text-secondary);">Product images (up to 5)</label>
                            <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-start;">
                                <div style="display:flex;flex-direction:column;gap:.5rem;align-items:center;">
                                    <input type="file" name="image_1" id="image_1" accept="image/*">
                                    <img id="preview_1" src="" alt="" style="width:68px;height:68px;border-radius:8px;display:none;object-fit:cover;border:1px solid rgba(255,255,255,0.03);">
                                </div>
                                <div style="display:flex;flex-direction:column;gap:.5rem;align-items:center;">
                                    <input type="file" name="image_2" id="image_2" accept="image/*">
                                    <img id="preview_2" src="" alt="" style="width:68px;height:68px;border-radius:8px;display:none;object-fit:cover;border:1px solid rgba(255,255,255,0.03);">
                                </div>
                                <div style="display:flex;flex-direction:column;gap:.5rem;align-items:center;">
                                    <input type="file" name="image_3" id="image_3" accept="image/*">
                                    <img id="preview_3" src="" alt="" style="width:68px;height:68px;border-radius:8px;display:none;object-fit:cover;border:1px solid rgba(255,255,255,0.03);">
                                </div>
                                <div style="display:flex;flex-direction:column;gap:.5rem;align-items:center;">
                                    <input type="file" name="image_4" id="image_4" accept="image/*">
                                    <img id="preview_4" src="" alt="" style="width:68px;height:68px;border-radius:8px;display:none;object-fit:cover;border:1px solid rgba(255,255,255,0.03);">
                                </div>
                                <div style="display:flex;flex-direction:column;gap:.5rem;align-items:center;">
                                    <input type="file" name="image_5" id="image_5" accept="image/*">
                                    <img id="preview_5" src="" alt="" style="width:68px;height:68px;border-radius:8px;display:none;object-fit:cover;border:1px solid rgba(255,255,255,0.03);">
                                </div>
                            </div>
                        </div>
                </div>
                <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:.5rem;">
                    <button type="button" class="btn ghost" id="cancelProduct">Cancel</button>
                    <button class="btn" id="saveProduct">Save</button>
                </div>
            </form>
        </div>
    </div>

    </div>
    </div>

<script>
    // small helper
    const qs = s => document.querySelector(s);
    const qsa = s => Array.from(document.querySelectorAll(s));


    let products = [];
    let searchTimeout = null;

    // DOM
    const productsTbody = qs('#productsTbody');
    const addProductBtn = qs('#addProductBtn');
    const productModalBackdrop = qs('#productModalBackdrop');
    const productForm = qs('#productForm');
    const productModalTitle = qs('#productModalTitle');
    const searchInput = qs('#searchInput');

    async function fetchProducts(q = '') {
        productsTbody.innerHTML = '<tr><td colspan="6" class="no-data">Loading products…</td></tr>';
        try {
                const res = await fetch('api/products_api.php?action=list' + (q ? '&q=' + encodeURIComponent(q) : ''));
                const txt = await res.text();
                let data = null;
                if (!txt) throw new Error('Empty response from API');
                try {
                    data = JSON.parse(txt);
                } catch (e) {
                    // show first chunk to avoid flooding the UI
                    throw new Error('Invalid JSON response from API:\n' + (txt.length > 1000 ? txt.slice(0, 1000) + '\n…' : txt));
                }
                if (!data || !data.success) throw new Error((data && data.message) ? data.message : 'Failed to fetch');
                products = data.products || [];
                renderProducts();
        } catch (err) {
            const msg = err?.message || 'Error loading products';
            productsTbody.innerHTML = `<tr><td colspan="6" class="no-data">${escapeHtml(msg)}<div style="margin-top:.6rem"><button id="retryProducts" class="btn">Retry</button></div></td></tr>`;
            console.error(err);
            document.getElementById('retryProducts')?.addEventListener('click', () => fetchProducts(q));
        }
    }

    function renderProducts() {
        if (!products.length) {
            productsTbody.innerHTML = '<tr><td colspan="6" class="no-data">No products found</td></tr>';
            return;
        }
        productsTbody.innerHTML = '';
        products.forEach(p => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="display:flex;align-items:center;gap:.75rem;">
                    <img src="${escapeHtml(p.image_url || 'upload/picture/no-image.png')}" alt="${escapeHtml(p.name)}" class="product-thumb">
                    <div>
                        <div style="font-weight:600">${escapeHtml(p.name)}</div>
                        <div style="color:var(--text-secondary);font-size:.85rem;">#${p.product_id}</div>
                    </div>
                </td>
                <td>${escapeHtml(p.brand_name || 'No Brand')}</td>
                <td>₱${Number(p.price).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                <td>${escapeHtml(p.stock)}</td>
                <td>${escapeHtml(p.category || '—')}</td>
                <td style="text-align:center;" class="actions">
                    <button class="btn ghost" data-action="edit" data-id="${p.product_id}" aria-label="Edit product">
                        <!-- Pencil / edit icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                    </button>
                    <button class="btn" data-action="delete" data-id="${p.product_id}" style="background:#ef4444" aria-label="Delete product">
                        <!-- Trash / delete icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m5 0V4a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </td>
            `;
            productsTbody.appendChild(tr);
        });
    }

    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // open modal
    addProductBtn.addEventListener('click', () => {
        openProductModal();
    });

    document.addEventListener('click', e => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;
        const id = btn.dataset.id;
        if (action === 'edit') editProduct(id);
        if (action === 'delete') deleteProduct(id);
    });

    function openProductModal(data = null) {
        productModalTitle.textContent = data ? 'Edit Product' : 'Add Product';
        productForm.reset();
        productForm.product_id.value = data ? data.product_id : '';
        productForm.name.value = data ? data.name : '';
        productForm.brand_name.value = data ? data.brand_name : '';
        productForm.price.value = data ? data.price : '';
        productForm.stock.value = data ? data.stock : '';
        productForm.category.value = data ? data.category : '';
        // clear previews
        for (var i=1;i<=5;i++){
            try { const pv = document.getElementById('preview_'+i); pv.style.display='none'; pv.src=''; document.getElementById('image_'+i).value = ''; } catch(e){}
        }
        // product meta
        const metaEl = document.getElementById('productMeta');
        if (data && data.product_id) {
            metaEl.style.display = 'flex';
            document.getElementById('metaIdVal').textContent = data.product_id || '';
            document.getElementById('metaCreatedVal').textContent = data.created_at ? data.created_at : '';
            metaEl.setAttribute('aria-hidden','false');
        } else {
            metaEl.style.display = 'none'; metaEl.setAttribute('aria-hidden','true');
        }
        // If editing and there are images from the API, populate previews
        if (data && Array.isArray(data.images) && data.images.length) {
            data.images.forEach((img, idx) => {
                const i = idx + 1;
                try {
                    const pv = document.getElementById('preview_'+i);
                    pv.src = img.image_url;
                    pv.style.display = 'block';
                } catch (e){}
            });
        } else if (data && data.image_url) {
            try { document.getElementById('preview_1').src = data.image_url; document.getElementById('preview_1').style.display='block'; } catch(e){}
        }
        productModalBackdrop.style.display = 'flex';
    }

    // wire file inputs to show live previews and allow clicking preview to pick file
    function bindImageInputs() {
        for (let i=1;i<=5;i++) {
            const input = document.getElementById('image_'+i);
            const preview = document.getElementById('preview_'+i);
            if (!input || !preview) continue;
            // when file selected, show preview
            input.addEventListener('change', (e) => {
                const f = input.files && input.files[0];
                if (!f) return;
                const reader = new FileReader();
                reader.onload = function(ev) {
                    preview.src = ev.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(f);
            });
            // clicking preview opens file input
            preview.addEventListener('click', () => {
                input.click();
            });
            // make preview focusable
            preview.style.cursor = 'pointer';
            preview.setAttribute('tabindex','0');
            preview.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') input.click(); });
        }
    }

    // bind once
    bindImageInputs();

    qs('#cancelProduct').addEventListener('click', () => {
        productModalBackdrop.style.display = 'none';
    });

    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = new FormData(productForm);
        const id = form.get('product_id');
        const action = id ? 'update' : 'create';
        try {
            const res = await fetch('api/products_api.php?action=' + action, {
                method: 'POST',
                body: form
            });
            const txt = await res.text();
            let data = null;
            if (!txt) throw new Error('Empty response from server');
            try { data = JSON.parse(txt); } catch (e) { throw new Error('Invalid JSON response from server:\n' + (txt.length > 1000 ? txt.slice(0,1000) + '\n…' : txt)); }
            if (!data.success) throw new Error(data.message || 'Error saving');
            productModalBackdrop.style.display = 'none';
            await fetchProducts(searchInput.value.trim());
        } catch (err) {
            alert(err.message || 'Failed');
            console.error(err);
        }
    });

    async function editProduct(id) {
        const p = products.find(x => x.product_id == id);
        if (p) {
            openProductModal(p);
            return;
        }
        // fallback: fetch specific
        try {
            const res = await fetch('api/products_api.php?action=get&id=' + encodeURIComponent(id));
            const txt = await res.text();
            if (!txt) throw new Error('Empty response from server');
            let data = null;
            try { data = JSON.parse(txt); } catch(e) { throw new Error('Invalid JSON response from server'); }
            if (!data.success) throw new Error(data.message || 'Failed to load product');
            openProductModal(data.product);
        } catch (err) {
            alert(err.message || 'Failed to load product');
        }
    }

    async function deleteProduct(id) {
        if (!confirm('Delete this product?')) return;
        try {
            const res = await fetch('api/products_api.php?action=delete', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(id)
            });
            const txt = await res.text();
            if (!txt) throw new Error('Empty response from server');
            let data = null;
            try { data = JSON.parse(txt); } catch(e) { throw new Error('Invalid JSON response from server'); }
            if (!data.success) throw new Error(data.message || 'Delete failed');
            await fetchProducts(searchInput.value.trim());
        } catch (err) {
            alert(err.message || 'Failed to delete');
            console.error(err);
        }
    }

    // search
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchProducts(searchInput.value.trim());
        }, 350);
    });

    // initial load
    fetchProducts();
</script>
</body>
</html>