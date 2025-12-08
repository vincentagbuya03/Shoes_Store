<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Get rider initials
$names = array_filter(explode(' ', $rider['name'] ?? 'Rider'));
$initials = '';
if (count($names) > 0) {
    $initials = strtoupper(substr($names[0], 0, 1));
    if (isset($names[1])) {
        $initials .= strtoupper(substr($names[1], 0, 1));
    }
}

// Fetch rider rating from database
$rider_rating = 0;
$rating_count = 0;
if (isset($conn) && isset($rider_id)) {
    // Try the view first
    $ratingQuery = $conn->prepare("SELECT avg_rating, rating_count FROM rider_avg_rating WHERE rider_id = ?");
    if ($ratingQuery) {
        $ratingQuery->bind_param('i', $rider_id);
        $ratingQuery->execute();
        $ratingRes = $ratingQuery->get_result();
        if ($ratingRow = $ratingRes->fetch_assoc()) {
            $rider_rating = round($ratingRow['avg_rating'], 1);
            $rating_count = (int)$ratingRow['rating_count'];
        }
        $ratingQuery->close();
    }
}

// Calculate on-time delivery percentage (completed deliveries / total assigned deliveries * 100)
$ontime_percent = 0;
if (isset($conn) && isset($rider_id)) {
    $totalAssigned = 0;
    $totalCompleted = 0;
    
    // Get total assigned orders
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = ?");
    if ($stmt) {
        $stmt->bind_param('i', $rider_id);
        $stmt->execute();
        $stmt->bind_result($totalAssigned);
        $stmt->fetch();
        $stmt->close();
    }
    
    // Get completed orders
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = ? AND status = 'completed'");
    if ($stmt) {
        $stmt->bind_param('i', $rider_id);
        $stmt->execute();
        $stmt->bind_result($totalCompleted);
        $stmt->fetch();
        $stmt->close();
    }
    
    if ($totalAssigned > 0) {
        $ontime_percent = round(($totalCompleted / $totalAssigned) * 100);
    }
}
?>
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
</button>

<!-- Notification Bell (Fixed Position) -->
<div class="notification-container" id="notificationContainer">
    <button class="notification-btn" id="notificationBtn" aria-haspopup="true" aria-expanded="false" title="Notifications">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
        <span class="notification-badge" id="notificationBadge" style="display:none;">0</span>
    </button>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <div class="notification-header-left">
                <h4>Notifications</h4>
                <span class="notification-count-badge" id="notificationCountBadge">0</span>
            </div>
            <button class="mark-all-read" id="markAllRead">Mark all read</button>
        </div>
        
        <div class="notification-list" id="notificationList">
            <div class="notification-empty">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <p>No notifications yet</p>
            </div>
        </div>
        
        <div class="notification-footer">
            <a href="javascript:void(0)" id="clearAllNotifications">Clear all notifications</a>
        </div>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
    <header class="sidebar-header">
        <div class="rider-avatar" aria-hidden="true"><?php echo htmlspecialchars($initials); ?></div>
        <div class="rider-info">
            <h2><?php echo htmlspecialchars($rider['name'] ?? 'Rider'); ?></h2>
            <p>Rider #<?php echo (int)($rider_id ?? 0); ?></p>
            <div class="rider-status">
                <span class="status-dot" aria-hidden="true"></span>
                <span><?php echo ucfirst(htmlspecialchars($rider['status'] ?? 'available')); ?></span>
            </div>
        </div>
    </header>

    <!-- Quick Stats -->
    <section class="quick-stats" aria-label="Quick statistics">
        <div class="stat-item">
            <span class="stat-label">Active Deliveries</span>
            <span class="stat-value highlight"><?php echo (int)($delivering_count ?? 0); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Rating</span>
            <span class="stat-value"><?php echo $rider_rating > 0 ? number_format($rider_rating, 1) . ' ⭐' : 'N/A'; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Completed %</span>
            <span class="stat-value"><?php echo $ontime_percent; ?>%</span>
        </div>
    </section>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <ul class="nav-list" role="menubar">
            <li class="nav-item" role="none">
                <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" role="menuitem" <?php echo $current_page === 'dashboard.php' ? 'aria-current="page"' : ''; ?>>
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
                <a href="deliveries.php" class="nav-link <?php echo $current_page === 'deliveries.php' ? 'active' : ''; ?>" role="menuitem" <?php echo $current_page === 'deliveries.php' ? 'aria-current="page"' : ''; ?>>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                        <path d="m3.3 7 8.7 5 8.7-5"/>
                        <path d="M12 22V12"/>
                    </svg>
                    My Deliveries
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="route_map.php" class="nav-link <?php echo $current_page === 'route_map.php' ? 'active' : ''; ?>" role="menuitem" <?php echo $current_page === 'route_map.php' ? 'aria-current="page"' : ''; ?>>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
                        <line x1="9" y1="3" x2="9" y2="18"/>
                        <line x1="15" y1="6" x2="15" y2="21"/>
                    </svg>
                    Route Map
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="history.php" class="nav-link <?php echo $current_page === 'history.php' ? 'active' : ''; ?>" role="menuitem" <?php echo $current_page === 'history.php' ? 'aria-current="page"' : ''; ?>>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    History
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="earnings.php" class="nav-link <?php echo $current_page === 'earnings.php' ? 'active' : ''; ?>" role="menuitem" <?php echo $current_page === 'earnings.php' ? 'aria-current="page"' : ''; ?>>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    Earnings
                </a>
            </li>
            <li class="nav-item" role="none">
                <a href="settings.php" class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" role="menuitem" <?php echo $current_page === 'settings.php' ? 'aria-current="page"' : ''; ?>>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Settings
                </a>
            </li>
        </ul>
    </nav>

    <!-- Logout -->
    <div class="sidebar-footer">
        <a href="logout.php" class="nav-link logout-link">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Logout
        </a>
    </div>
</aside>
