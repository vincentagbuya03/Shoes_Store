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
$period = $_GET['period'] ?? 'week';

// Calculate earnings
$earnings_data = [];
$total_earnings = 0;
$total_deliveries = 0;
$commission_rate = 0.10; // 10% commission

switch ($period) {
    case 'today':
        $date_condition = "DATE(o.order_date) = CURDATE()";
        break;
    case 'week':
        $date_condition = "o.order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = "o.order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        break;
    case 'year':
        $date_condition = "o.order_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
        break;
    default:
        $date_condition = "o.order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $period = 'week';
}

// Get daily earnings for chart
$daily_earnings = [];
$sql = "SELECT DATE(o.order_date) as date, COUNT(*) as deliveries, SUM(o.total_amount) as total
        FROM orders o
        WHERE o.rider_id = ? AND o.status = 'completed' AND $date_condition
        GROUP BY DATE(o.order_date)
        ORDER BY date ASC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $daily_earnings[] = $row;
        $total_deliveries += $row['deliveries'];
        $total_earnings += $row['total'] * $commission_rate;
    }
    $stmt->close();
}

// Get recent transactions
$transactions = [];
$sql = "SELECT o.order_id, o.order_date, o.total_amount, c.name AS customer_name
        FROM orders o
        LEFT JOIN customer c ON o.customer_id = c.customer_id
        WHERE o.rider_id = ? AND o.status = 'completed' AND $date_condition
        ORDER BY o.order_date DESC
        LIMIT 20";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $transactions[] = $row;
    }
    $stmt->close();
}

// Prepare chart data
$chart_labels = [];
$chart_values = [];
foreach ($daily_earnings as $day) {
    $chart_labels[] = date('M d', strtotime($day['date']));
    $chart_values[] = round($day['total'] * $commission_rate, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Earnings - ShoeTakels Rider">
    <title>Earnings | ShoeTakels Rider</title>
    
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
            background: linear-gradient(135deg, var(--accent-orange), #ea580c);
            color: white;
            border-color: transparent;
        }
        
        .earnings-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .earnings-card {
            background: var(--bg-card);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 24px;
            position: relative;
            overflow: hidden;
        }
        .earnings-card.highlight {
            background: linear-gradient(135deg, var(--accent-orange), #ea580c);
            border: none;
            color: white;
        }
        .earnings-card.highlight .earnings-label {
            color: rgba(255,255,255,0.8);
        }
        .earnings-card.highlight .earnings-value {
            color: white;
        }
        .earnings-card .icon {
            width: 48px;
            height: 48px;
            background: rgba(249, 115, 22, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent-orange);
            margin-bottom: 16px;
        }
        .earnings-card.highlight .icon {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .earnings-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        .earnings-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .earnings-change {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.8rem;
            color: #22c55e;
            margin-top: 8px;
        }
        .earnings-change.negative {
            color: #ef4444;
        }
        
        .earnings-chart-section {
            background: var(--bg-card);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 32px;
        }
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .chart-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .chart-container {
            height: 280px;
        }
        
        .transactions-section {
            background: var(--bg-card);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 24px;
        }
        .transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .transactions-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .transaction-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            background: var(--bg-light);
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
        }
        .transaction-item:hover {
            background: var(--bg-white);
            box-shadow: var(--shadow-sm);
        }
        .transaction-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .transaction-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .transaction-details h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .transaction-details p {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        .transaction-amount {
            font-size: 1rem;
            font-weight: 700;
            color: #22c55e;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <?php include 'partials/sidebar.php'; ?>

    <div class="dashboard-wrapper">
        <main class="main-content" role="main">
            <header class="page-header">
                <h1>Earnings</h1>
                <p>Track your delivery earnings and commissions</p>
            </header>

            <!-- Period Tabs -->
            <div class="period-tabs">
                <a href="?period=today" class="period-tab <?php echo $period === 'today' ? 'active' : ''; ?>">Today</a>
                <a href="?period=week" class="period-tab <?php echo $period === 'week' ? 'active' : ''; ?>">This Week</a>
                <a href="?period=month" class="period-tab <?php echo $period === 'month' ? 'active' : ''; ?>">This Month</a>
                <a href="?period=year" class="period-tab <?php echo $period === 'year' ? 'active' : ''; ?>">This Year</a>
            </div>

            <!-- Earnings Summary -->
            <div class="earnings-summary">
                <div class="earnings-card highlight">
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                    <div class="earnings-label">Total Earnings</div>
                    <div class="earnings-value">₱<?php echo number_format($total_earnings, 2); ?></div>
                    <div class="earnings-change">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                            <polyline points="17 6 23 6 23 12"/>
                        </svg>
                        +12.5% from last period
                    </div>
                </div>
                <div class="earnings-card">
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                        </svg>
                    </div>
                    <div class="earnings-label">Deliveries</div>
                    <div class="earnings-value"><?php echo $total_deliveries; ?></div>
                </div>
                <div class="earnings-card">
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                    <div class="earnings-label">Avg. Per Delivery</div>
                    <div class="earnings-value">₱<?php echo $total_deliveries > 0 ? number_format($total_earnings / $total_deliveries, 2) : '0.00'; ?></div>
                </div>
            </div>

            <!-- Earnings Chart -->
            <div class="earnings-chart-section">
                <div class="chart-header">
                    <h3>Earnings Overview</h3>
                </div>
                <div class="chart-container">
                    <canvas id="earningsChart"></canvas>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="transactions-section">
                <div class="transactions-header">
                    <h3>Recent Transactions</h3>
                </div>
                <div class="transaction-list">
                    <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <p>No transactions found for this period.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($transactions as $t): ?>
                        <div class="transaction-item">
                            <div class="transaction-info">
                                <div class="transaction-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>
                                </div>
                                <div class="transaction-details">
                                    <h4>#ORD-<?php echo htmlspecialchars($t['order_id']); ?></h4>
                                    <p><?php echo htmlspecialchars($t['customer_name'] ?? 'Guest'); ?> • <?php echo date('M d, h:i A', strtotime($t['order_date'])); ?></p>
                                </div>
                            </div>
                            <div class="transaction-amount">+₱<?php echo number_format(($t['total_amount'] ?? 0) * $commission_rate, 2); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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

        // Earnings Chart
        const ctx = document.getElementById('earningsChart').getContext('2d');
        const chartLabels = <?php echo json_encode($chart_labels); ?>;
        const chartValues = <?php echo json_encode($chart_values); ?>;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels.length > 0 ? chartLabels : ['No data'],
                datasets: [{
                    label: 'Earnings (₱)',
                    data: chartValues.length > 0 ? chartValues : [0],
                    backgroundColor: 'rgba(249, 115, 22, 0.8)',
                    borderColor: 'rgba(249, 115, 22, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { 
                            callback: function(value) { return '₱' + value; }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    </script>
    
    <!-- Notifications JavaScript -->
    <script src="assets/js/notifications.js"></script>
</body>
</html>
