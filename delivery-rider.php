<?php
session_start();

// Authentication check - redirect to login if not authenticated as rider
// Note: In production, add proper rider authentication check
// if (!isset($_SESSION['rider_id'])) {
//     header('Location: login.php');
//     exit();
// }

// Dynamic greeting based on time of day
$hour = (int)date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = "Good Morning";
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = "Good Afternoon";
} elseif ($hour >= 17 && $hour < 21) {
    $greeting = "Good Evening";
} else {
    $greeting = "Hello";
}

// Demo data for the delivery rider dashboard
$rider_name = $_SESSION['rider_name'] ?? "John Rider";
$today_deliveries = 12;
$pending_deliveries = 5;
$completed_deliveries = 7;
$total_earnings = 2450.00;

// Sample active deliveries data
$active_deliveries = [
    [
        'order_id' => 'ORD-2024-001',
        'customer' => 'Maria Santos',
        'address' => '123 Rizal Street, Manila',
        'items' => 2,
        'status' => 'in_transit',
        'distance' => '3.2 km',
        'time_estimate' => '15 min'
    ],
    [
        'order_id' => 'ORD-2024-002',
        'customer' => 'Juan Dela Cruz',
        'address' => '456 Bonifacio Ave, Quezon City',
        'items' => 1,
        'status' => 'pickup',
        'distance' => '5.1 km',
        'time_estimate' => '25 min'
    ],
    [
        'order_id' => 'ORD-2024-003',
        'customer' => 'Ana Reyes',
        'address' => '789 Mabini Street, Makati',
        'items' => 3,
        'status' => 'pending',
        'distance' => '2.8 km',
        'time_estimate' => '12 min'
    ]
];

// Weekly delivery stats for chart
$weekly_stats = [
    ['day' => 'Mon', 'deliveries' => 8],
    ['day' => 'Tue', 'deliveries' => 12],
    ['day' => 'Wed', 'deliveries' => 10],
    ['day' => 'Thu', 'deliveries' => 15],
    ['day' => 'Fri', 'deliveries' => 18],
    ['day' => 'Sat', 'deliveries' => 20],
    ['day' => 'Sun', 'deliveries' => 14]
];

$max_deliveries = max(array_column($weekly_stats, 'deliveries'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShoeTakels - Delivery Rider Dashboard</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/delivery-rider.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                    <span class="logo-text">ShoeTakels</span>
                </div>
                <span class="rider-badge">Rider</span>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"/>
                        <rect x="14" y="3" width="7" height="7"/>
                        <rect x="14" y="14" width="7" height="7"/>
                        <rect x="3" y="14" width="7" height="7"/>
                    </svg>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="1" y="3" width="15" height="13"/>
                        <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                        <circle cx="5.5" cy="18.5" r="2.5"/>
                        <circle cx="18.5" cy="18.5" r="2.5"/>
                    </svg>
                    <span>Active Deliveries</span>
                </a>
                <a href="#" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <span>History</span>
                </a>
                <a href="#" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    <span>Earnings</span>
                </a>
                <a href="#" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    <span>Map View</span>
                </a>
                <a href="#" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <span>Notifications</span>
                    <span class="notification-badge">3</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <a href="#" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    <span>Settings</span>
                </a>
                <a href="logout.php" class="nav-item logout">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                    </button>
                    <div class="greeting">
                        <h1><?php echo htmlspecialchars($greeting); ?>, <?php echo htmlspecialchars($rider_name); ?>! 👋</h1>
                        <p>Here's your delivery overview for today</p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="search-box">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input type="text" placeholder="Search orders...">
                    </div>
                    <div class="header-actions">
                        <button class="icon-btn notification-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                            <span class="badge">3</span>
                        </button>
                        <div class="user-profile">
                            <div class="avatar">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($rider_name); ?></span>
                                <span class="user-status online">Online</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Stats Cards -->
            <section class="stats-section">
                <div class="stat-card">
                    <div class="stat-icon deliveries-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="3" width="15" height="13"/>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                            <circle cx="5.5" cy="18.5" r="2.5"/>
                            <circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Today's Deliveries</span>
                        <span class="stat-value"><?php echo $today_deliveries; ?></span>
                        <span class="stat-change positive">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            </svg>
                            +15% from yesterday
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Pending</span>
                        <span class="stat-value"><?php echo $pending_deliveries; ?></span>
                        <span class="stat-change neutral">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Same as usual
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon completed-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Completed</span>
                        <span class="stat-value"><?php echo $completed_deliveries; ?></span>
                        <span class="stat-change positive">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            </svg>
                            +8% from yesterday
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon earnings-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">Today's Earnings</span>
                        <span class="stat-value">₱<?php echo number_format($total_earnings, 2); ?></span>
                        <span class="stat-change positive">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            </svg>
                            +22% from yesterday
                        </span>
                    </div>
                </div>
            </section>

            <!-- Chart and Active Deliveries -->
            <section class="content-grid">
                <!-- Weekly Chart -->
                <div class="chart-card">
                    <div class="card-header">
                        <h2>Weekly Delivery Overview</h2>
                        <div class="chart-legend">
                            <span class="legend-item">
                                <span class="legend-dot"></span>
                                Deliveries
                            </span>
                        </div>
                    </div>
                    <div class="chart-container">
                        <div class="bar-chart">
                            <?php foreach ($weekly_stats as $stat): 
                                $height_percent = ($stat['deliveries'] / $max_deliveries) * 100;
                            ?>
                            <div class="bar-wrapper">
                                <div class="bar-tooltip"><?php echo $stat['deliveries']; ?> deliveries</div>
                                <div class="bar" style="--bar-height: <?php echo $height_percent; ?>%;">
                                    <div class="bar-fill" data-value="<?php echo $stat['deliveries']; ?>"></div>
                                </div>
                                <span class="bar-label"><?php echo $stat['day']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="chart-y-axis">
                            <span><?php echo $max_deliveries; ?></span>
                            <span><?php echo round($max_deliveries * 0.75); ?></span>
                            <span><?php echo round($max_deliveries * 0.5); ?></span>
                            <span><?php echo round($max_deliveries * 0.25); ?></span>
                            <span>0</span>
                        </div>
                    </div>
                </div>

                <!-- Active Deliveries -->
                <div class="deliveries-card">
                    <div class="card-header">
                        <h2>Active Deliveries</h2>
                        <a href="#" class="view-all-link">View All</a>
                    </div>
                    <div class="deliveries-list">
                        <?php foreach ($active_deliveries as $delivery): ?>
                        <div class="delivery-item" data-status="<?php echo $delivery['status']; ?>">
                            <div class="delivery-status-indicator"></div>
                            <div class="delivery-info">
                                <div class="delivery-header">
                                    <span class="order-id"><?php echo htmlspecialchars($delivery['order_id']); ?></span>
                                    <span class="delivery-status <?php echo $delivery['status']; ?>">
                                        <?php 
                                        $status_labels = [
                                            'pending' => 'Pending',
                                            'pickup' => 'Ready for Pickup',
                                            'in_transit' => 'In Transit'
                                        ];
                                        echo $status_labels[$delivery['status']] ?? $delivery['status'];
                                        ?>
                                    </span>
                                </div>
                                <div class="customer-name"><?php echo htmlspecialchars($delivery['customer']); ?></div>
                                <div class="delivery-address">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                        <circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <?php echo htmlspecialchars($delivery['address']); ?>
                                </div>
                                <div class="delivery-meta">
                                    <span class="meta-item">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                            <line x1="3" y1="6" x2="21" y2="6"/>
                                            <path d="M16 10a4 4 0 0 1-8 0"/>
                                        </svg>
                                        <?php echo $delivery['items']; ?> items
                                    </span>
                                    <span class="meta-item">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <polyline points="12 6 12 12 16 14"/>
                                        </svg>
                                        <?php echo $delivery['time_estimate']; ?>
                                    </span>
                                    <span class="meta-item">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polygon points="3 11 22 2 13 21 11 13 3 11"/>
                                        </svg>
                                        <?php echo $delivery['distance']; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="delivery-actions">
                                <?php if ($delivery['status'] === 'pending'): ?>
                                <button class="action-btn accept-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Accept
                                </button>
                                <?php elseif ($delivery['status'] === 'pickup'): ?>
                                <button class="action-btn pickup-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                        <line x1="3" y1="6" x2="21" y2="6"/>
                                        <path d="M16 10a4 4 0 0 1-8 0"/>
                                    </svg>
                                    Picked Up
                                </button>
                                <?php else: ?>
                                <button class="action-btn deliver-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                        <polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                    Delivered
                                </button>
                                <?php endif; ?>
                                <button class="action-btn details-btn">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="1"/>
                                        <circle cx="19" cy="12" r="1"/>
                                        <circle cx="5" cy="12" r="1"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- Quick Stats Row -->
            <section class="quick-stats">
                <div class="quick-stat-card">
                    <div class="circular-progress" data-value="85">
                        <svg viewBox="0 0 100 100">
                            <circle class="bg-circle" cx="50" cy="50" r="45"/>
                            <circle class="progress-circle" cx="50" cy="50" r="45" style="--progress: 85"/>
                        </svg>
                        <span class="progress-value">85%</span>
                    </div>
                    <div class="quick-stat-info">
                        <span class="quick-stat-label">On-Time Rate</span>
                        <span class="quick-stat-desc">This week's performance</span>
                    </div>
                </div>
                
                <div class="quick-stat-card">
                    <div class="circular-progress" data-value="92">
                        <svg viewBox="0 0 100 100">
                            <circle class="bg-circle" cx="50" cy="50" r="45"/>
                            <circle class="progress-circle success" cx="50" cy="50" r="45" style="--progress: 92"/>
                        </svg>
                        <span class="progress-value">92%</span>
                    </div>
                    <div class="quick-stat-info">
                        <span class="quick-stat-label">Customer Rating</span>
                        <span class="quick-stat-desc">Based on 127 reviews</span>
                    </div>
                </div>
                
                <div class="quick-stat-card">
                    <div class="circular-progress" data-value="78">
                        <svg viewBox="0 0 100 100">
                            <circle class="bg-circle" cx="50" cy="50" r="45"/>
                            <circle class="progress-circle warning" cx="50" cy="50" r="45" style="--progress: 78"/>
                        </svg>
                        <span class="progress-value">78%</span>
                    </div>
                    <div class="quick-stat-info">
                        <span class="quick-stat-label">Acceptance Rate</span>
                        <span class="quick-stat-desc">Orders accepted</span>
                    </div>
                </div>
                
                <div class="quick-stat-card achievement-card">
                    <div class="achievement-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="8" r="7"/>
                            <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
                        </svg>
                    </div>
                    <div class="quick-stat-info">
                        <span class="quick-stat-label">Star Rider</span>
                        <span class="quick-stat-desc">3 weeks streak!</span>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });

        // Animate stats on load
        document.addEventListener('DOMContentLoaded', () => {
            // Animate stat values
            const statValues = document.querySelectorAll('.stat-value');
            statValues.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    el.style.transition = 'all 0.5s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, 100);
            });

            // Animate bars
            const bars = document.querySelectorAll('.bar-fill');
            bars.forEach((bar, index) => {
                setTimeout(() => {
                    const barHeight = bar.parentElement.style.getPropertyValue('--bar-height') || '0%';
                    bar.style.height = barHeight;
                }, index * 100);
            });

            // Animate circular progress
            const progressCircles = document.querySelectorAll('.progress-circle');
            progressCircles.forEach(circle => {
                const progress = circle.style.getPropertyValue('--progress');
                const circumference = 2 * Math.PI * 45;
                const offset = circumference - (progress / 100) * circumference;
                circle.style.strokeDasharray = circumference;
                circle.style.strokeDashoffset = circumference;
                setTimeout(() => {
                    circle.style.transition = 'stroke-dashoffset 1s ease-out';
                    circle.style.strokeDashoffset = offset;
                }, 500);
            });
        });

        // Button click animations
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.classList.add('ripple');
                this.appendChild(ripple);
                
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = e.clientX - rect.left - size / 2 + 'px';
                ripple.style.top = e.clientY - rect.top - size / 2 + 'px';
                
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Tooltip for chart bars
        document.querySelectorAll('.bar-wrapper').forEach(wrapper => {
            wrapper.addEventListener('mouseenter', function() {
                const tooltip = this.querySelector('.bar-tooltip');
                tooltip.style.opacity = '1';
                tooltip.style.transform = 'translateY(0)';
            });
            
            wrapper.addEventListener('mouseleave', function() {
                const tooltip = this.querySelector('.bar-tooltip');
                tooltip.style.opacity = '0';
                tooltip.style.transform = 'translateY(10px)';
            });
        });
    </script>
</body>
</html>
