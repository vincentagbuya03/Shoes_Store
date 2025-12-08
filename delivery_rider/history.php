<?php
session_start();
if (!isset($_SESSION['rider_id'])) {
    header('Location: ../login.php');
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

// Get delivery counts
$delivering_count = 0;
if ($stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM orders WHERE rider_id = ? AND status = 'delivering'")) {
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    $stmt->bind_result($cnt);
    if ($stmt->fetch()) { $delivering_count = (int)$cnt; }
    $stmt->close();
}

// Date filter
$date_filter = $_GET['period'] ?? 'all';
$date_condition = '';
switch ($date_filter) {
    case 'today':
        $date_condition = "AND DATE(o.order_date) = CURDATE()";
        break;
    case 'week':
        $date_condition = "AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = "AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
    default:
        $date_condition = '';
}

// Fetch completed deliveries (history)
$history = [];
$sql = "SELECT o.order_id, o.status, o.order_date, o.total_amount, 
               c.name AS customer_name, c.address, c.phone AS customer_phone
        FROM orders o 
        LEFT JOIN customer c ON o.customer_id = c.customer_id 
        WHERE o.rider_id = ? AND o.status = 'completed' $date_condition
        ORDER BY o.order_date DESC 
        LIMIT 100";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
}

// Stats
$total_deliveries = count($history);
$total_earnings = 0;
foreach ($history as $h) {
    $total_earnings += ($h['total_amount'] ?? 0) * 0.1; // 10% commission
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Delivery History - ShoeTakels Rider">
    <title>History | ShoeTakels Rider</title>
    
    <link rel="icon" type="image/x-icon" href="../upload/picture/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/rider.css">
    <style>
        .period-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .period-tab {
            padding: 10px 20px;
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
        }
        .period-tab:hover {
            background: var(--bg-light);
            color: var(--text-primary);
        }
        .period-tab.active {
            background: linear-gradient(135deg, var(--primary-indigo), var(--primary-indigo-dark));
            color: white;
            border-color: transparent;
        }
        
        .history-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .history-stat-card {
            background: var(--bg-card);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 20px;
            text-align: center;
        }
        .history-stat-card .icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 12px;
            background: linear-gradient(135deg, var(--accent-teal), #0d9488);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .history-stat-card .value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .history-stat-card .label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        
        .history-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .history-item {
            background: var(--bg-card);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all var(--transition-fast);
        }
        .history-item:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
        }
        .history-item-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .history-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .history-details h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .history-details p {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }
        .history-item-right {
            text-align: right;
        }
        .history-amount {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .history-date {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 4px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        .empty-state h3 {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <?php include 'partials/sidebar.php'; ?>

    <div class="dashboard-wrapper">
        <main class="main-content" role="main">
            <header class="page-header">
                <h1>Delivery History</h1>
                <p>View your past completed deliveries</p>
            </header>

            <!-- Period Tabs -->
            <div class="period-tabs">
                <a href="?period=all" class="period-tab <?php echo $date_filter === 'all' ? 'active' : ''; ?>">All Time</a>
                <a href="?period=today" class="period-tab <?php echo $date_filter === 'today' ? 'active' : ''; ?>">Today</a>
                <a href="?period=week" class="period-tab <?php echo $date_filter === 'week' ? 'active' : ''; ?>">This Week</a>
                <a href="?period=month" class="period-tab <?php echo $date_filter === 'month' ? 'active' : ''; ?>">This Month</a>
            </div>

            <!-- Stats -->
            <div class="history-stats">
                <div class="history-stat-card">
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                    </div>
                    <div class="value"><?php echo $total_deliveries; ?></div>
                    <div class="label">Completed Deliveries</div>
                </div>
                <div class="history-stat-card">
                    <div class="icon" style="background: linear-gradient(135deg, var(--accent-orange), #ea580c);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="value">₱<?php echo number_format($total_earnings, 2); ?></div>
                    <div class="label">Estimated Earnings</div>
                </div>
                <div class="history-stat-card">
                    <div class="icon" style="background: linear-gradient(135deg, var(--primary-indigo), var(--primary-indigo-dark));">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <div class="value">4.9</div>
                    <div class="label">Average Rating</div>
                </div>
            </div>

            <!-- History List -->
            <div class="history-list">
                <?php if (empty($history)): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <h3>No delivery history</h3>
                    <p>Your completed deliveries will appear here.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($history as $h): ?>
                    <div class="history-item">
                        <div class="history-item-left">
                            <div class="history-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 6L9 17l-5-5"/>
                                </svg>
                            </div>
                            <div class="history-details">
                                <h4>#ORD-<?php echo htmlspecialchars($h['order_id']); ?> - <?php echo htmlspecialchars($h['customer_name'] ?? 'Guest'); ?></h4>
                                <p><?php echo htmlspecialchars($h['address'] ?? 'No address'); ?></p>
                            </div>
                        </div>
                        <div class="history-item-right">
                            <div class="history-amount">₱<?php echo number_format($h['total_amount'] ?? 0, 2); ?></div>
                            <div class="history-date"><?php echo date('M d, Y - h:i A', strtotime($h['order_date'])); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Sidebar toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                sidebarOverlay.classList.toggle('active');
            });
        }
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        }
    </script>
    
    <!-- Notifications JavaScript -->
    <script src="assets/js/notifications.js"></script>
</body>
</html>
