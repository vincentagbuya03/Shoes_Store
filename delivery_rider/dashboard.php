<?php

 session_start();
if (!isset($_SESSION['rider_id'])) {
    header('Location: \login.php');
    exit;
}
require_once __DIR__ . '/../db_connection.php';

$rider_id = $_SESSION['rider_id'];

// Fetch rider info
$rider = ['name' => 'Rider', 'email' => '', 'phone' => '', 'status' => 'available'];
if ($stmt = $conn->prepare("SELECT name, email, phone, status FROM rider WHERE rider_id = ?")) {
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $rider = $row;
    }
    $stmt->close();
}

// Counts: pending (includes confirmed), delivering, completed
$pending_count = 0; $delivering_count = 0; $completed_count = 0;
// Count orders that are pending pickup for the rider. Include both 'pending' and 'confirmed' as pickup-ready.
if ($stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE rider_id = ? AND (status = ? OR status = ?)")) {
    $status1 = 'pending';
    $status2 = 'confirmed';
    $stmt->bind_param('iss', $rider_id, $status1, $status2);
    $stmt->execute();
    $stmt->bind_result($cnt);
    if ($stmt->fetch()) { $pending_count = (int)$cnt; }
    $stmt->close();
}
if ($stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE rider_id = ? AND status = ?")) {
    $status = 'delivering';
    $stmt->bind_param('is', $rider_id, $status);
    $stmt->execute();
    $stmt->bind_result($cnt);
    if ($stmt->fetch()) { $delivering_count = (int)$cnt; }
    $stmt->close();
}
if ($stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE rider_id = ? AND status = ?")) {
    $status = 'completed';
    $stmt->bind_param('is', $rider_id, $status);
    $stmt->execute();
    $stmt->bind_result($cnt);
    if ($stmt->fetch()) { $completed_count = (int)$cnt; }
    $stmt->close();
}

$recent_deliveries = [];
if ($stmt = $conn->prepare("SELECT o.order_id, c.name AS customer_name, c.address, c.phone AS customer_phone, o.status, o.order_date FROM orders o LEFT JOIN customer c ON o.customer_id = c.customer_id WHERE o.rider_id = ? ORDER BY o.order_date DESC LIMIT 8")) {
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $recent_deliveries[] = $row;
    }
    $stmt->close();
}



$names = array_filter(explode(' ', $rider['name']));
$initials = '';
if (count($names) > 0) {
    $initials = strtoupper(substr($names[0], 0, 1));
    if (isset($names[1])) {
        $initials .= strtoupper(substr($names[1], 0, 1));
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Delivery Rider Dashboard - Manage your deliveries efficiently">
    <title>Rider Dashboard | ShoeTakels</title>
    
    <link rel="icon" type="image/x-icon" href="../upload/picture/logo.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/rider.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-wrapper">
        <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
            <header class="sidebar-header">
                <div class="rider-avatar" aria-hidden="true"><?php echo htmlspecialchars($initials); ?></div>
                <div class="rider-info">
                    <h2><?php echo htmlspecialchars($rider['name']); ?></h2>
                    <p>Rider #<?php echo (int)$rider_id; ?></p>
                    <div class="rider-status">
                        <span class="status-dot" aria-hidden="true"></span>
                        <span><?php echo ucfirst(htmlspecialchars($rider['status'])); ?></span>
                    </div>
                </div>
            </header>

            <!-- Quick Stats -->
            <section class="quick-stats" aria-label="Quick statistics">
                <div class="stat-item">
                    <span class="stat-label">Active Deliveries</span>
                    <span class="stat-value highlight"><?php echo (int)$delivering_count; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Rating</span>
                    <span class="stat-value">4.9 ⭐</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">On-Time %</span>
                    <span class="stat-value">98%</span>
                </div>
            </section>

            <!-- Navigation -->
            <nav class="sidebar-nav">
                <ul class="nav-list" role="menubar">
                    <li class="nav-item" role="none">
                        <a href="#" class="nav-link active" role="menuitem" aria-current="page">
                            <!-- Dashboard Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="7" height="7"/>
                                <rect x="14" y="3" width="7" height="7"/>
                                <rect x="14" y="14" width="7" height="7"/>
                                <rect x="3" y="14" width="7" height="7"/>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="#" class="nav-link" role="menuitem">
                            <!-- Package Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                <path d="m3.3 7 8.7 5 8.7-5"/>
                                <path d="M12 22V12"/>
                            </svg>
                            My Deliveries
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="route_map.php" class="nav-link" role="menuitem">
                            <!-- Map Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
                                <line x1="9" y1="3" x2="9" y2="18"/>
                                <line x1="15" y1="6" x2="15" y2="21"/>
                            </svg>
                            Route Map
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="#" class="nav-link" role="menuitem">
                            <!-- History Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            History
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="#" class="nav-link" role="menuitem">
                            <!-- Earnings Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="1" x2="12" y2="23"/>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                            Earnings
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="#" class="nav-link" role="menuitem">
                            <!-- Settings Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            Settings
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" role="main">
            <!-- Page Header -->
            <header class="page-header">
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($rider['name']); ?>! Here's your delivery overview.</p>
            </header>

            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input 
                        type="search" 
                        id="deliverySearch" 
                        placeholder="Search deliveries..." 
                        aria-label="Search deliveries"
                    >
                </div>
                <div class="filter-dropdown">
                    <select id="statusFilter" aria-label="Filter by status">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="transit">In Transit</option>
                        <option value="delivered">Delivered</option>
                    </select>
                </div>
            </div>

            <!-- Summary Cards -->
            <section class="summary-cards" aria-label="Delivery summary">
                <article class="summary-card">
                    <div class="summary-card-header">
                        <div class="summary-card-icon pending" aria-hidden="true">
                            <!-- Clock Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <span class="summary-card-badge">Today</span>
                    </div>
                    <div class="summary-card-value"><?php echo (int)$pending_count; ?></div>
                    <div class="summary-card-label">Pending Pickups</div>
                </article>

                <article class="summary-card">
                    <div class="summary-card-header">
                        <div class="summary-card-icon transit" aria-hidden="true">
                            <!-- Bike Icon inline -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-bike">
                                <path d="M12 19V13l-4-2"/>
                                <path d="M8 11l4 2 5-3"/>
                                <path d="M17 8v3"/>
                                <circle class="wheel" cx="5" cy="19" r="3"/>
                                <circle class="wheel" cx="19" cy="19" r="3"/>
                                <path d="M17 8h2"/>
                            </svg>
                        </div>
                        <span class="summary-card-badge">Active</span>
                    </div>
                    <div class="summary-card-value"><?php echo (int)$delivering_count; ?></div>
                    <div class="summary-card-label">In Transit</div>
                </article>

                <article class="summary-card">
                    <div class="summary-card-header">
                        <div class="summary-card-icon delivered" aria-hidden="true">
                            <!-- Check Icon inline -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-check">
                                <path d="M20 6L9 17l-5-5"/>
                            </svg>
                        </div>
                        <span class="summary-card-badge">Today</span>
                    </div>
                    <div class="summary-card-value"><?php echo (int)$completed_count; ?></div>
                    <div class="summary-card-label">Delivered</div>
                </article>
            </section>

            <!-- Chart Section -->
            <section class="chart-section" aria-label="Deliveries chart">
                <div class="chart-header">
                    <h3>Deliveries This Week</h3>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-dot deliveries" aria-hidden="true"></span>
                            Deliveries
                        </span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="deliveriesChart" aria-label="Bar chart showing deliveries per day for the last 7 days"></canvas>
                </div>
            </section>

            <!-- Deliveries Table -->
            <section class="deliveries-section" aria-label="Deliveries list">
                <div class="deliveries-header">
                    <h3>Recent Deliveries</h3>
                    <span class="deliveries-count"><?php echo count($recent_deliveries); ?> recent deliveries</span>
                </div>
                <div class="deliveries-table-wrapper">
                    <table class="deliveries-table" role="grid">
                        <thead>
                            <tr>
                                <th scope="col">Order ID</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Status</th>
                                <th scope="col">Time</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="deliveriesTableBody">
<?php if (count($recent_deliveries) > 0): ?>
    <?php foreach ($recent_deliveries as $d): ?>
        <?php
            $status_label = ucfirst($d['status']);
            $status_class = 'pending';
            if ($d['status'] === 'delivering') { $status_class = 'transit'; }
            if ($d['status'] === 'completed') { $status_class = 'delivered'; }
        ?>
        <tr data-order-id="<?php echo (int)$d['order_id']; ?>" data-customer-phone="<?php echo htmlspecialchars($d['customer_phone'] ?? ''); ?>">
            <td><span class="order-id">#ORD-<?php echo htmlspecialchars($d['order_id']); ?></span></td>
            <td>
                <div class="customer-info">
                    <span class="customer-name"><?php echo htmlspecialchars($d['customer_name'] ?? 'Guest'); ?></span>
                    <span class="customer-address"><?php echo htmlspecialchars($d['address'] ?? ''); ?></span>
                </div>
            </td>
            <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_label); ?></span></td>
            <td><?php echo date('h:i A', strtotime($d['order_date'])); ?></td>
            <td>
                <div class="action-buttons">
                    <?php if ($d['status'] === 'pending' || $d['status'] === 'confirmed'): ?>
                        <?php $startLabel = $d['status'] === 'confirmed' ? 'Pick Up' : 'Start'; ?>
                        <button class="action-btn start-btn" aria-label="Start delivery" title="<?php echo $startLabel === 'Pick Up' ? 'Pick up order' : 'Start Delivery'; ?>"><?php echo $startLabel; ?></button>
                    <?php elseif ($d['status'] === 'delivering'): ?>
                        <button class="action-btn deliver-btn" aria-label="Mark as delivered" title="Mark Delivered">Done</button>
                    <?php endif; ?>
                    <button class="action-btn contact-btn" aria-label="Contact customer" title="Contact Customer">Contact</button>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="5">No deliveries found.</td></tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Proof of Delivery Modal -->
    <div id="proofModal" class="proof-modal" aria-hidden="true">
        <div class="proof-modal-backdrop" data-close="true"></div>
        <div class="proof-modal-panel" role="dialog" aria-modal="true" aria-labelledby="proofModalTitle">
            <h2 id="proofModalTitle">Upload Proof of Delivery</h2>
            <p class="muted">Please take a clear photo of the delivered package as proof.</p>
            <form id="proofForm" method="post" enctype="multipart/form-data" action="api/upload_proof.php">
                <input type="hidden" name="order_id" id="proofOrderId" value="">
                <label class="file-label">
                    <input type="file" id="proofFile" name="proof" accept="image/*" required>
                    <span class="file-hint">Choose photo or take a picture (click preview to reselect)</span>
                </label>
                <div id="proofPreviewWrap" style="display:none; margin-top:0.5rem;">
                    <img id="proofPreview" alt="Photo preview" style="max-width:100%; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.12);">
                </div>
                <div style="display:flex; gap:8px; align-items:center; margin-top:0.5rem;">
                    <button type="button" id="proofDebugToggle" style="background:transparent; border:1px solid rgba(148,163,184,0.12); color:var(--text-secondary); padding:6px 8px; border-radius:6px; cursor:pointer;">Show debug</button>
                    <small style="color:var(--text-muted);">(opens debug panel with server response)</small>
                </div>
                <div id="proofDebug" style="display:none; margin-top:0.5rem; background:#0f172a; color:#e6edf3; padding:8px; border-radius:6px; font-size:13px; max-height:180px; overflow:auto;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:6px;">
                        <strong style="font-size:13px;">Debug</strong>
                        <button type="button" id="proofDebugClose" style="background:transparent; border:none; color:#9ca3af; cursor:pointer;">Close</button>
                    </div>
                    <pre id="proofDebugPre" style="white-space:pre-wrap; word-break:break-word; margin:0; font-family:monospace; font-size:12px;"></pre>
                </div>
                <div class="modal-actions" style="margin-top:0.75rem; display:flex; gap:0.5rem; justify-content:flex-end;">
                    <button type="button" class="action-btn" id="proofCancel">Cancel</button>
                    <button type="button" class="action-btn deliver-btn" id="proofSubmit">Upload & Mark Delivered</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
    <div style="position:fixed; right:16px; bottom:16px; background:rgba(0,0,0,0.6); color:#fff; padding:12px; border-radius:8px; z-index:9999; max-width:320px; font-size:13px;">
        <strong>DEBUG</strong>
        <div style="margin-top:8px;">Session Rider ID: <code><?php echo isset($_SESSION['rider_id']) ? (int)$_SESSION['rider_id'] : 'null'; ?></code></div>
        <form action="api/upload_proof.php" method="post" enctype="multipart/form-data" style="margin-top:8px;">
            <label style="display:block; margin-bottom:6px;">Order ID: <input name="order_id" type="number" required style="width:80px;"></label>
            <label style="display:block; margin-bottom:6px;">Photo: <input name="proof" type="file" accept="image/*" required></label>
            <div style="display:flex; gap:6px; justify-content:flex-end;">
                <button type="submit" style="background:#0ea5a4; color:#042; border:none; padding:6px 8px; border-radius:6px; cursor:pointer;">Upload (auth)</button>
            </div>
        </form>
        <div style="margin-top:6px; font-size:12px; color:#ddd;">After submit, check <code>scripts/upload_proof_debug.log</code> or Network response.</div>
    </div>
    <?php endif; ?>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- Dashboard JavaScript -->
    <script src="assets/js/rider.js"></script>
</body>
</html>
