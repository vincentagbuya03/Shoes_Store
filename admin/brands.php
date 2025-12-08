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
    <title>Brands - ShoeTakels Admin</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/admin-dashboard.css">
    <link rel="stylesheet" href="asset/style/products.css">
    <style>
        .brand-logo {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            padding: 4px;
        }
        .brand-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .brand-name {
            font-weight: 600;
        }
        .brand-id {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
        .logo-preview {
            width: 120px;
            height: 120px;
            object-fit: contain;
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            border: 2px dashed rgba(255,255,255,0.1);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .logo-preview:hover {
            border-color: var(--accent);
        }
        .logo-upload-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        .logo-upload-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
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
                            <h1>Brands</h1>
                        </div>
                        <div class="page-actions">
                            <input id="searchInput" class="page-search" type="search" placeholder="Search brands...">
                            <button class="btn" id="addBrandBtn">Add Brand</button>
                        </div>
                    </div>

                    <div id="brandsRegion">
                        <table class="table" id="brandsTable" aria-live="polite">
                            <thead>
                                <tr>
                                    <th>Brand</th>
                                    <th>Products Count</th>
                                    <th>Created At</th>
                                    <th style="width:150px;text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="brandsTbody">
                                <tr><td colspan="4" class="no-data">Loading brands…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal for Add / Edit Brand -->
    <div class="modal-backdrop" id="brandModalBackdrop" role="dialog" aria-modal="true">
        <div class="modal" role="document" id="brandModal">
            <h3 id="brandModalTitle">Add Brand</h3>
            <div id="brandMeta" class="product-meta" aria-hidden="true" style="display:none">
                <div id="metaId">ID: <span id="metaIdVal"></span></div>
                <div id="metaCreated">Created: <span id="metaCreatedVal"></span></div>
            </div>
            <form id="brandForm">
                <input type="hidden" name="brand_id" id="brand_id" value="">
                <div class="form-row">
                    <input type="text" name="brand_name" id="brand_name" placeholder="Brand name" required style="flex:1;">
                </div>
                <div class="form-row">
                    <div class="logo-upload-container">
                        <label class="logo-upload-label">Brand Logo</label>
                        <img id="logoPreview" src="upload/brand-picture/placeholder.png" alt="Brand logo preview" class="logo-preview">
                        <input type="file" name="brand_logo" id="brand_logo" accept="image/*" style="display:none;">
                        <small style="color:var(--text-secondary);font-size:0.75rem;">Click image to upload</small>
                    </div>
                </div>
                <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
                    <button type="button" class="btn ghost" id="cancelBrand">Cancel</button>
                    <button class="btn" id="saveBrand">Save</button>
                </div>
            </form>
        </div>
    </div>

<script>
    const qs = s => document.querySelector(s);
    const qsa = s => Array.from(document.querySelectorAll(s));

    let brands = [];
    let searchTimeout = null;

    // DOM elements
    const brandsTbody = qs('#brandsTbody');
    const addBrandBtn = qs('#addBrandBtn');
    const brandModalBackdrop = qs('#brandModalBackdrop');
    const brandForm = qs('#brandForm');
    const brandModalTitle = qs('#brandModalTitle');
    const searchInput = qs('#searchInput');
    const logoPreview = qs('#logoPreview');
    const logoInput = qs('#brand_logo');

    // Fetch brands from API
    async function fetchBrands(q = '') {
        brandsTbody.innerHTML = '<tr><td colspan="4" class="no-data">Loading brands…</td></tr>';
        try {
            const res = await fetch('api/brands_api.php?action=list' + (q ? '&q=' + encodeURIComponent(q) : ''));
            const txt = await res.text();
            let data = null;
            if (!txt) throw new Error('Empty response from API');
            try {
                data = JSON.parse(txt);
            } catch (e) {
                throw new Error('Invalid JSON response from API:\n' + (txt.length > 1000 ? txt.slice(0, 1000) + '\n…' : txt));
            }
            if (!data || !data.success) throw new Error((data && data.message) ? data.message : 'Failed to fetch');
            brands = data.brands || [];
            renderBrands();
        } catch (err) {
            const msg = err?.message || 'Error loading brands';
            brandsTbody.innerHTML = `<tr><td colspan="4" class="no-data">${escapeHtml(msg)}<div style="margin-top:.6rem"><button id="retryBrands" class="btn">Retry</button></div></td></tr>`;
            console.error(err);
            document.getElementById('retryBrands')?.addEventListener('click', () => fetchBrands(q));
        }
    }

    // Render brands table
    function renderBrands() {
        if (!brands.length) {
            brandsTbody.innerHTML = '<tr><td colspan="4" class="no-data">No brands found</td></tr>';
            return;
        }
        brandsTbody.innerHTML = '';
        brands.forEach(b => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="brand-info">
                        <img src="${escapeHtml(b.brand_logo || 'upload/brand-picture/placeholder.png')}" alt="${escapeHtml(b.brand_name)}" class="brand-logo">
                        <div>
                            <div class="brand-name">${escapeHtml(b.brand_name)}</div>
                            <div class="brand-id">#${b.brand_id}</div>
                        </div>
                    </div>
                </td>
                <td>${b.product_count || 0} products</td>
                <td>${escapeHtml(b.created_at || '—')}</td>
                <td style="text-align:center;" class="actions">
                    <button class="btn ghost" data-action="edit" data-id="${b.brand_id}" aria-label="Edit brand">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                    </button>
                    <button class="btn" data-action="delete" data-id="${b.brand_id}" style="background:#ef4444" aria-label="Delete brand">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6m5 0V4a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v2"/></svg>
                    </button>
                </td>
            `;
            brandsTbody.appendChild(tr);
        });
    }

    function escapeHtml(s) {
        if (s === null || s === undefined) return '';
        return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    // Open modal
    addBrandBtn.addEventListener('click', () => {
        openBrandModal();
    });

    // Handle action buttons (edit/delete)
    document.addEventListener('click', e => {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const action = btn.dataset.action;
        const id = btn.dataset.id;
        if (action === 'edit') editBrand(id);
        if (action === 'delete') deleteBrand(id);
    });

    function openBrandModal(data = null) {
        brandModalTitle.textContent = data ? 'Edit Brand' : 'Add Brand';
        brandForm.reset();
        brandForm.brand_id.value = data ? data.brand_id : '';
        brandForm.brand_name.value = data ? data.brand_name : '';
        
        // Reset logo preview
        logoPreview.src = data && data.brand_logo ? data.brand_logo : 'upload/brand-picture/placeholder.png';
        logoInput.value = '';
        
        // Brand meta
        const metaEl = document.getElementById('brandMeta');
        if (data && data.brand_id) {
            metaEl.style.display = 'flex';
            document.getElementById('metaIdVal').textContent = data.brand_id || '';
            document.getElementById('metaCreatedVal').textContent = data.created_at ? data.created_at : '';
            metaEl.setAttribute('aria-hidden', 'false');
        } else {
            metaEl.style.display = 'none';
            metaEl.setAttribute('aria-hidden', 'true');
        }
        
        brandModalBackdrop.style.display = 'flex';
    }

    // Logo preview click to upload
    logoPreview.addEventListener('click', () => {
        logoInput.click();
    });

    // Logo file input change
    logoInput.addEventListener('change', (e) => {
        const file = logoInput.files && logoInput.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(ev) {
            logoPreview.src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });

    // Cancel button
    qs('#cancelBrand').addEventListener('click', () => {
        brandModalBackdrop.style.display = 'none';
    });

    // Form submit
    brandForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = new FormData(brandForm);
        const id = form.get('brand_id');
        const action = id ? 'update' : 'create';
        try {
            const res = await fetch('api/brands_api.php?action=' + action, {
                method: 'POST',
                body: form
            });
            const txt = await res.text();
            let data = null;
            if (!txt) throw new Error('Empty response from server');
            try { data = JSON.parse(txt); } catch (e) { throw new Error('Invalid JSON response from server:\n' + (txt.length > 1000 ? txt.slice(0,1000) + '\n…' : txt)); }
            if (!data.success) throw new Error(data.message || 'Error saving');
            brandModalBackdrop.style.display = 'none';
            await fetchBrands(searchInput.value.trim());
        } catch (err) {
            alert(err.message || 'Failed');
            console.error(err);
        }
    });

    async function editBrand(id) {
        const b = brands.find(x => x.brand_id == id);
        if (b) {
            openBrandModal(b);
            return;
        }
        // Fallback: fetch specific
        try {
            const res = await fetch('api/brands_api.php?action=get&id=' + encodeURIComponent(id));
            const txt = await res.text();
            if (!txt) throw new Error('Empty response from server');
            let data = null;
            try { data = JSON.parse(txt); } catch(e) { throw new Error('Invalid JSON response from server'); }
            if (!data.success) throw new Error(data.message || 'Failed to load brand');
            openBrandModal(data.brand);
        } catch (err) {
            alert(err.message || 'Failed to load brand');
        }
    }

    async function deleteBrand(id) {
        if (!confirm('Delete this brand? This may affect products associated with it.')) return;
        try {
            const res = await fetch('api/brands_api.php?action=delete', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + encodeURIComponent(id)
            });
            const txt = await res.text();
            if (!txt) throw new Error('Empty response from server');
            let data = null;
            try { data = JSON.parse(txt); } catch(e) { throw new Error('Invalid JSON response from server'); }
            if (!data.success) throw new Error(data.message || 'Delete failed');
            await fetchBrands(searchInput.value.trim());
        } catch (err) {
            alert(err.message || 'Failed to delete');
            console.error(err);
        }
    }

    // Search
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            fetchBrands(searchInput.value.trim());
        }, 350);
    });

    // Initial load
    fetchBrands();
</script>
</body>
</html>
