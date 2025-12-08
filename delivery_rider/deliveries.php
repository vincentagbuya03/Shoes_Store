<?php
session_start();
if (!isset($_SESSION['rider_id'])) {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../db_connection.php';

$rider_id = $_SESSION['rider_id'];

// Create refund_requests table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS refund_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    reason TEXT,
    status ENUM('requested', 'processing', 'pickup_scheduled', 'picked_up', 'resolved', 'rejected') DEFAULT 'requested',
    rider_pickup_id INT DEFAULT NULL,
    pickup_date DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");


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

// Get current tab
$current_tab = $_GET['tab'] ?? 'deliveries';
$valid_tabs = ['deliveries', 'refunds'];
if (!in_array($current_tab, $valid_tabs)) {
    $current_tab = 'deliveries';
}

$status_filter = $_GET['status'] ?? 'all';
$valid_statuses = ['all', 'pending', 'confirmed', 'delivering', 'completed', 'cancelled'];
if (!in_array($status_filter, $valid_statuses)) {
    $status_filter = 'all';
}

// Fetch deliveries based on filter
$deliveries = [];
if ($status_filter === 'all') {
    $sql = "SELECT o.order_id, o.status, o.order_date, o.total_amount, 
                   c.name AS customer_name, c.address, c.phone AS customer_phone,
                   (SELECT COUNT(*) FROM refund_requests WHERE order_id = o.order_id) as has_refund
            FROM orders o 
            LEFT JOIN customer c ON o.customer_id = c.customer_id 
            WHERE o.rider_id = ? 
            ORDER BY 
                CASE o.status 
                    WHEN 'delivering' THEN 1 
                    WHEN 'confirmed' THEN 2 
                    WHEN 'pending' THEN 3 
                    ELSE 4 
                END, 
                o.order_date DESC 
            LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $rider_id);
} else {
    $sql = "SELECT o.order_id, o.status, o.order_date, o.total_amount, 
                   c.name AS customer_name, c.address, c.phone AS customer_phone,
                   (SELECT COUNT(*) FROM refund_requests WHERE order_id = o.order_id) as has_refund
            FROM orders o 
            LEFT JOIN customer c ON o.customer_id = c.customer_id 
            WHERE o.rider_id = ? AND o.status = ?
            ORDER BY o.order_date DESC 
            LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $rider_id, $status_filter);
}

if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $deliveries[] = $row;
    }
    $stmt->close();
}

// Count by status
$status_counts = ['pending' => 0, 'confirmed' => 0, 'delivering' => 0, 'completed' => 0];
if ($stmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM orders WHERE rider_id = ? GROUP BY status")) {
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (isset($status_counts[$row['status']])) {
            $status_counts[$row['status']] = (int)$row['cnt'];
        }
    }
    $stmt->close();
}

// Fetch refund requests (assigned to this rider or pending pickup)
$refund_requests = [];
$sql = "SELECT r.*, o.order_date, o.total_amount, o.status as order_status,
               c.name AS customer_name, c.address, c.phone AS customer_phone
        FROM refund_requests r
        JOIN orders o ON r.order_id = o.order_id
        LEFT JOIN customer c ON r.customer_id = c.customer_id
        WHERE (r.rider_pickup_id = ? OR (r.status IN ('requested', 'processing') AND o.rider_id = ?))
        ORDER BY 
            CASE r.status 
                WHEN 'pickup_scheduled' THEN 1 
                WHEN 'processing' THEN 2 
                WHEN 'requested' THEN 3 
                ELSE 4 
            END, 
            r.created_at DESC
        LIMIT 50";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('ii', $rider_id, $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $refund_requests[] = $row;
    }
    $stmt->close();
}

// Count refunds
$refund_counts = ['pending' => 0, 'scheduled' => 0, 'picked_up' => 0];
$sql = "SELECT r.status, COUNT(*) as cnt 
        FROM refund_requests r
        JOIN orders o ON r.order_id = o.order_id
        WHERE r.rider_pickup_id = ? OR (r.status IN ('requested', 'processing') AND o.rider_id = ?)
        GROUP BY r.status";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('ii', $rider_id, $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        if (in_array($row['status'], ['requested', 'processing'])) {
            $refund_counts['pending'] += (int)$row['cnt'];
        } elseif ($row['status'] === 'pickup_scheduled') {
            $refund_counts['scheduled'] = (int)$row['cnt'];
        } elseif ($row['status'] === 'picked_up') {
            $refund_counts['picked_up'] = (int)$row['cnt'];
        }
    }
    $stmt->close();
}
$total_refunds = array_sum($refund_counts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your deliveries - ShoeTakels Rider">
    <title>My Deliveries | ShoeTakels Rider</title>
    
    <link rel="icon" type="image/x-icon" href="../upload/picture/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/rider.css">
    <style>
        /* Tab Navigation */
        .main-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 24px;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: 6px;
            border: 1px solid var(--border-light);
        }
        .main-tab {
            flex: 1;
            padding: 14px 24px;
            background: transparent;
            border: none;
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .main-tab:hover {
            color: var(--text-primary);
            background: var(--bg-light);
        }
        .main-tab.active {
            background: linear-gradient(135deg, var(--primary-indigo), var(--primary-indigo-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        .main-tab .badge {
            background: rgba(255,255,255,0.25);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .main-tab:not(.active) .badge {
            background: var(--bg-light);
            color: var(--text-muted);
        }
        .main-tab.refund-tab.active {
            background: linear-gradient(135deg, var(--accent-orange), #ea580c);
        }
        
        /* Filter Pills */
        .filter-pills {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .filter-pill {
            padding: 8px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
        }
        .filter-pill:hover {
            background: var(--bg-light);
            border-color: var(--primary-indigo);
            color: var(--primary-indigo);
        }
        .filter-pill.active {
            background: var(--primary-indigo);
            color: white;
            border-color: var(--primary-indigo);
        }
        .filter-pill .count {
            margin-left: 6px;
            opacity: 0.8;
        }
        
        /* Cards Grid */
        .cards-grid {
            display: grid;
            gap: 16px;
        }
        @media (min-width: 768px) {
            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1200px) {
            .cards-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        /* Delivery Card - Redesigned */
        .delivery-card {
            background: var(--bg-card);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all var(--transition-fast);
        }
        .delivery-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-indigo);
        }
        .delivery-card.has-refund {
            border-color: var(--accent-orange);
        }
        .delivery-card.has-refund:hover {
            border-color: var(--accent-orange);
        }
        
        .card-status-bar {
            height: 4px;
            background: var(--border-light);
        }
        .card-status-bar.pending { background: linear-gradient(90deg, var(--accent-orange), #fbbf24); }
        .card-status-bar.confirmed { background: linear-gradient(90deg, var(--accent-blue), #38bdf8); }
        .card-status-bar.delivering { background: linear-gradient(90deg, var(--primary-indigo), #818cf8); }
        .card-status-bar.completed { background: linear-gradient(90deg, var(--accent-teal), #2dd4bf); }
        
        .card-content {
            padding: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .order-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .order-id {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-indigo);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .refund-indicator {
            background: var(--accent-orange);
            color: white;
            font-size: 0.65rem;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }
        .order-date {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .status-chip {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-chip.pending { background: rgba(245, 158, 11, 0.15); color: var(--accent-orange); }
        .status-chip.confirmed { background: rgba(59, 130, 246, 0.15); color: var(--accent-blue); }
        .status-chip.delivering { background: rgba(99, 102, 241, 0.15); color: var(--primary-indigo); }
        .status-chip.completed { background: rgba(20, 184, 166, 0.15); color: var(--accent-teal); }
        
        .customer-section {
            display: flex;
            gap: 14px;
            padding: 16px;
            background: var(--bg-light);
            border-radius: var(--radius-md);
            margin-bottom: 16px;
        }
        .customer-avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent-teal), #0d9488);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .customer-info {
            flex: 1;
            min-width: 0;
        }
        .customer-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
            font-size: 0.95rem;
        }
        .customer-address {
            font-size: 0.8rem;
            color: var(--text-secondary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .customer-phone {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 4px;
        }
        
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 16px;
            border-top: 1px solid var(--border-light);
        }
        .order-amount {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
        }
        .order-amount small {
            font-size: 0.7rem;
            color: var(--text-muted);
            font-weight: 400;
            display: block;
        }
        
        .card-actions {
            display: flex;
            gap: 8px;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 18px;
            border-radius: var(--radius-md);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-indigo), var(--primary-indigo-dark));
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4);
        }
        .btn-success {
            background: linear-gradient(135deg, var(--accent-teal), #0d9488);
            color: white;
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(20, 184, 166, 0.4);
        }
        .btn-warning {
            background: linear-gradient(135deg, var(--accent-orange), #ea580c);
            color: white;
        }
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }
        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            padding: 10px 14px;
        }
        .btn-ghost:hover {
            background: var(--bg-light);
            color: var(--text-primary);
        }
        .btn-icon {
            padding: 10px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 2px dashed var(--border-light);
        }
        .empty-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: var(--bg-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .empty-icon svg {
            width: 40px;
            height: 40px;
            color: var(--text-muted);
        }
        .empty-state h3 {
            font-size: 1.2rem;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        .empty-state p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        /* Refund Card Specific */
        .refund-card {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            overflow: hidden;
            border-left: 4px solid var(--accent-orange);
        }
        .refund-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .refund-status-chip {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .refund-status-chip.requested { background: rgba(245, 158, 11, 0.15); color: var(--accent-orange); }
        .refund-status-chip.processing { background: rgba(59, 130, 246, 0.15); color: var(--accent-blue); }
        .refund-status-chip.pickup_scheduled { background: rgba(99, 102, 241, 0.15); color: var(--primary-indigo); }
        .refund-status-chip.picked_up { background: rgba(20, 184, 166, 0.15); color: var(--accent-teal); }
        
        .refund-reason {
            background: rgba(245, 158, 11, 0.08);
            padding: 12px;
            border-radius: var(--radius-md);
            margin-bottom: 16px;
            border: 1px solid rgba(245, 158, 11, 0.2);
        }
        .refund-reason-label {
            font-size: 0.7rem;
            color: var(--accent-orange);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .refund-reason-text {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        
        /* Proof Modal Styles */
        .proof-modal {
            position: fixed;
            inset: 0;
            display: none;
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }
        .proof-modal[aria-hidden="false"] {
            display: flex !important;
        }
        .proof-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.45);
            backdrop-filter: blur(2px);
        }
        .proof-modal-panel {
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            width: 100%;
            max-width: 520px;
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            z-index: 10000 !important;
        }
        .proof-modal-panel h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            color: var(--text-primary);
            font-weight: 600;
        }
        .proof-modal-panel .muted {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .file-label {
            display: block;
            border: 2px dashed var(--border-light);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--bg-light);
        }
        .file-label:hover {
            border-color: var(--primary-indigo);
            background: rgba(99, 102, 241, 0.05);
        }
        .file-label input[type="file"] {
            display: none;
        }
        .file-hint {
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 500;
        }
        .modal-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }
        .modal-actions .btn {
            min-width: 120px;
        }
        /* Confirmation Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: var(--bg-card);
            border-radius: 12px;
            width: 100%;
            max-width: 520px;
            box-shadow: var(--shadow-lg);
            padding: 20px;
            border: 1px solid var(--border-light);
        }
        .modal h3 { margin: 0 0 8px 0; font-size: 1.05rem; }
        .modal p { margin: 0 0 16px 0; color: var(--text-secondary); }
        .modal .modal-actions { display:flex; gap:8px; justify-content:flex-end; }
        .modal .btn { min-width: 96px; }
        .btn.loading { opacity: 0.9; pointer-events: none; }
    </style>
</head>
<body>
    <?php include 'partials/sidebar.php'; ?>

    <div class="dashboard-wrapper">
        <main class="main-content" role="main">
            <!-- Page Header -->
            <header class="page-header">
                <h1>Deliveries & Returns</h1>
                <p>Manage orders, track deliveries, and handle customer refunds</p>
            </header>

            <!-- Main Tabs -->
            <div class="main-tabs">
                <a href="?tab=deliveries&status=<?php echo $status_filter; ?>" class="main-tab <?php echo $current_tab === 'deliveries' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    Deliveries
                    <span class="badge"><?php echo array_sum($status_counts); ?></span>
                </a>
                <a href="?tab=refunds" class="main-tab refund-tab <?php echo $current_tab === 'refunds' ? 'active' : ''; ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Refund Pickups
                    <span class="badge"><?php echo $total_refunds; ?></span>
                </a>
            </div>

            <!-- Deliveries Tab Content -->
            <div class="tab-content <?php echo $current_tab === 'deliveries' ? 'active' : ''; ?>" id="deliveries-tab">
                <!-- Filter Pills -->
                <div class="filter-pills">
                    <a href="?tab=deliveries&status=all" class="filter-pill <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                        All <span class="count">(<?php echo array_sum($status_counts); ?>)</span>
                    </a>
                    <a href="?tab=deliveries&status=pending" class="filter-pill <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                        Pending <span class="count">(<?php echo $status_counts['pending']; ?>)</span>
                    </a>
                    <a href="?tab=deliveries&status=confirmed" class="filter-pill <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">
                        Confirmed <span class="count">(<?php echo $status_counts['confirmed']; ?>)</span>
                    </a>
                    <a href="?tab=deliveries&status=delivering" class="filter-pill <?php echo $status_filter === 'delivering' ? 'active' : ''; ?>">
                        In Transit <span class="count">(<?php echo $status_counts['delivering']; ?>)</span>
                    </a>
                    <a href="?tab=deliveries&status=completed" class="filter-pill <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                        Completed <span class="count">(<?php echo $status_counts['completed']; ?>)</span>
                    </a>
                </div>

                <!-- Delivery Cards -->
                <?php if (empty($deliveries)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="1" y="3" width="15" height="13"/>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                            <circle cx="5.5" cy="18.5" r="2.5"/>
                            <circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                    </div>
                    <h3>No deliveries found</h3>
                    <p>There are no deliveries matching this filter.</p>
                </div>
                <?php else: ?>
                <div class="cards-grid">
                    <?php foreach ($deliveries as $d): 
                        $customer_initials = '';
                        $name_parts = explode(' ', $d['customer_name'] ?? 'G');
                        $customer_initials = strtoupper(substr($name_parts[0], 0, 1));
                        if (isset($name_parts[1])) {
                            $customer_initials .= strtoupper(substr($name_parts[1], 0, 1));
                        }
                        $status_class = strtolower($d['status']);
                        $has_refund = (int)($d['has_refund'] ?? 0) > 0;
                    ?>
                    <article class="delivery-card <?php echo $has_refund ? 'has-refund' : ''; ?>" data-order-id="<?php echo (int)$d['order_id']; ?>">
                        <div class="card-status-bar <?php echo $status_class; ?>"></div>
                        <div class="card-content">
                            <div class="card-header">
                                <div class="order-info">
                                    <div class="order-id">
                                        #ORD-<?php echo htmlspecialchars($d['order_id']); ?>
                                        <?php if ($has_refund): ?>
                                        <span class="refund-indicator">REFUND</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="order-date"><?php echo date('M d, Y • h:i A', strtotime($d['order_date'])); ?></div>
                                </div>
                                <span class="status-chip <?php echo $status_class; ?>"><?php echo ucfirst($d['status']); ?></span>
                            </div>
                            
                            <div class="customer-section">
                                <div class="customer-avatar"><?php echo $customer_initials; ?></div>
                                <div class="customer-info">
                                    <div class="customer-name"><?php echo htmlspecialchars($d['customer_name'] ?? 'Guest Customer'); ?></div>
                                    <div class="customer-address"><?php echo htmlspecialchars($d['address'] ?? 'No address provided'); ?></div>
                                    <?php if (!empty($d['customer_phone'])): ?>
                                    <div class="customer-phone">📞 <?php echo htmlspecialchars($d['customer_phone']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <div class="order-amount">
                                    ₱<?php echo number_format($d['total_amount'] ?? 0, 2); ?>
                                    <small>Total Amount</small>
                                </div>
                                <div class="card-actions">
                                    <?php if ($d['status'] === 'pending' || $d['status'] === 'confirmed'): ?>
                                    <button class="btn btn-primary start-delivery-btn" data-order-id="<?php echo (int)$d['order_id']; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                        Start
                                    </button>
                                    <?php elseif ($d['status'] === 'delivering'): ?>
                                    <button class="btn btn-success complete-delivery-btn" data-order-id="<?php echo (int)$d['order_id']; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                        Complete
                                    </button>
                                    <?php endif; ?>
                                    <a href="route_map.php?order=<?php echo (int)$d['order_id']; ?>" class="btn btn-ghost btn-icon" title="View Map">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Refunds Tab Content -->
            <div class="tab-content <?php echo $current_tab === 'refunds' ? 'active' : ''; ?>" id="refunds-tab">
                <!-- Refund Filter Pills -->
                <div class="filter-pills">
                    <span class="filter-pill active">All Requests <span class="count">(<?php echo $total_refunds; ?>)</span></span>
                </div>
                
                <?php if (empty($refund_requests)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </div>
                    <h3>No refund requests</h3>
                    <p>There are no pending refund pickups assigned to you.</p>
                </div>
                <?php else: ?>
                <div class="cards-grid">
                    <?php foreach ($refund_requests as $r): 
                        $customer_initials = '';
                        $name_parts = explode(' ', $r['customer_name'] ?? 'G');
                        $customer_initials = strtoupper(substr($name_parts[0], 0, 1));
                        if (isset($name_parts[1])) {
                            $customer_initials .= strtoupper(substr($name_parts[1], 0, 1));
                        }
                        $refund_status = $r['status'];
                    ?>
                    <article class="refund-card" data-refund-id="<?php echo (int)$r['id']; ?>">
                        <div class="card-content">
                            <div class="card-header">
                                <div class="order-info">
                                    <div class="order-id">#ORD-<?php echo htmlspecialchars($r['order_id']); ?></div>
                                    <div class="order-date">Requested: <?php echo date('M d, Y', strtotime($r['created_at'])); ?></div>
                                </div>
                                <span class="refund-status-chip <?php echo $refund_status; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $refund_status)); ?>
                                </span>
                            </div>
                            
                            <?php if (!empty($r['reason'])): ?>
                            <div class="refund-reason">
                                <div class="refund-reason-label">Return Reason</div>
                                <div class="refund-reason-text"><?php echo htmlspecialchars($r['reason']); ?></div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="customer-section">
                                <div class="customer-avatar"><?php echo $customer_initials; ?></div>
                                <div class="customer-info">
                                    <div class="customer-name"><?php echo htmlspecialchars($r['customer_name'] ?? 'Guest Customer'); ?></div>
                                    <div class="customer-address"><?php echo htmlspecialchars($r['address'] ?? 'No address provided'); ?></div>
                                    <?php if (!empty($r['customer_phone'])): ?>
                                    <div class="customer-phone">📞 <?php echo htmlspecialchars($r['customer_phone']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="order-amount">
                                    ₱<?php echo number_format($r['total_amount'] ?? 0, 2); ?>
                                    <small>Refund Amount</small>
                                </div>
                                <div class="card-actions">
                                    <?php if ($r['status'] === 'requested' || $r['status'] === 'processing'): ?>
                                    <button class="btn btn-warning accept-refund-btn" data-refund-id="<?php echo (int)$r['id']; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                        Accept Pickup
                                    </button>
                                    <?php elseif ($r['status'] === 'pickup_scheduled'): ?>
                                    <button class="btn btn-success complete-refund-btn" data-refund-id="<?php echo (int)$r['id']; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                        Mark Picked Up
                                    </button>
                                    <?php endif; ?>
                                    <a href="route_map.php?order=<?php echo (int)$r['order_id']; ?>&refund=1" class="btn btn-ghost btn-icon" title="View Map">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
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
        function showConfirmationModal(title, message, confirmText = 'Confirm') {
            return new Promise(resolve => {
                let overlay = document.getElementById('confirmOverlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.id = 'confirmOverlay';
                    overlay.className = 'modal-overlay';
                    overlay.innerHTML = `
                        <div class="modal" role="dialog" aria-modal="true">
                            <h3 id="confirmTitle"></h3>
                            <p id="confirmMessage"></p>
                            <div class="modal-actions">
                                <button class="btn btn-ghost" id="confirmCancel">Cancel</button>
                                <button class="btn btn-warning" id="confirmOk">${confirmText}</button>
                            </div>
                        </div>`;
                    document.body.appendChild(overlay);
                }
                const titleEl = overlay.querySelector('#confirmTitle');
                const messageEl = overlay.querySelector('#confirmMessage');
                const okBtn = overlay.querySelector('#confirmOk');
                const cancelBtn = overlay.querySelector('#confirmCancel');

                titleEl.textContent = title;
                messageEl.textContent = message;
                okBtn.textContent = confirmText;

                function cleanup(val) {
                    overlay.classList.remove('active');
                    okBtn.removeEventListener('click', onOk);
                    cancelBtn.removeEventListener('click', onCancel);
                    resolve(val);
                }
                function onOk() { cleanup(true); }
                function onCancel() { cleanup(false); }
                okBtn.addEventListener('click', onOk);
                cancelBtn.addEventListener('click', onCancel);
                overlay.classList.add('active');
            });
        }
        async function safePostJson(url, body) {
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(body)
                });
                const text = await res.text();
                if (!text) return { success: false, message: 'Empty server response' };
                try { return JSON.parse(text); } catch (e) { return { success: false, message: text || 'Invalid JSON' }; }
            } catch (e) {
                return { success: false, message: e.message || 'Network error' };
            }
        }
        document.querySelectorAll('.start-delivery-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const orderId = this.dataset.orderId;
                const ok = await showConfirmationModal('Start Delivery', 'Start delivery for order #ORD-' + orderId + '?', 'Start');
                if (!ok) return;
                btn.classList.add('loading');
                const data = await safePostJson('api/update_order_status.php', {order_id: orderId, action: 'start'});
                btn.classList.remove('loading');
                if (data.success) location.reload();
                else alert(data.message || 'Failed to update status');
            });
        });

        // Complete delivery handled by proof modal below

        document.querySelectorAll('.accept-refund-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const refundId = this.dataset.refundId;
                const ok = await showConfirmationModal('Accept Pickup', 'Accept this refund pickup assignment?', 'Accept');
                if (!ok) return;
                btn.classList.add('loading');
                const data = await safePostJson('api/handle_refund.php', {refund_id: refundId, action: 'accept_pickup'});
                btn.classList.remove('loading');
                if (data.success) location.reload();
                else alert(data.message || 'Failed to accept pickup');
            });
        });

        // Complete refund pickup
        document.querySelectorAll('.complete-refund-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const refundId = this.dataset.refundId;
                const ok = await showConfirmationModal('Picked Up', 'Confirm that you have picked up the returned item?', 'Confirm');
                if (!ok) return;
                btn.classList.add('loading');
                const data = await safePostJson('api/handle_refund.php', {refund_id: refundId, action: 'mark_picked_up'});
                btn.classList.remove('loading');
                if (data.success) location.reload();
                else alert(data.message || 'Failed to update status');
            });
        });
    </script>
    
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
                    <span class="file-hint">Choose photo or take a picture</span>
                </label>
                <div id="proofPreviewWrap" style="display:none; margin-top:0.5rem;">
                    <img id="proofPreview" alt="Photo preview" style="max-width:100%; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.12);">
                </div>
                <div class="modal-actions" style="margin-top:0.75rem; display:flex; gap:0.5rem; justify-content:flex-end;">
                    <button type="button" class="btn btn-ghost" id="proofCancel">Cancel</button>
                    <button type="button" class="btn btn-success" id="proofSubmit">Upload & Mark Delivered</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Notifications JavaScript -->
    <script src="assets/js/notifications.js"></script>
    
    <script>
        // Proof of Delivery Modal Handler
        const proofModal = document.getElementById('proofModal');
        const proofForm = document.getElementById('proofForm');
        const proofFile = document.getElementById('proofFile');
        const proofPreview = document.getElementById('proofPreview');
        const proofPreviewWrap = document.getElementById('proofPreviewWrap');
        const proofOrderId = document.getElementById('proofOrderId');
        const proofSubmit = document.getElementById('proofSubmit');
        const proofCancel = document.getElementById('proofCancel');
        const proofBackdrop = proofModal?.querySelector('.proof-modal-backdrop');

        function openProofModal(orderId) {
            if (!proofModal) return;
            proofOrderId.value = orderId;
            proofForm.reset();
            proofPreviewWrap.style.display = 'none';
            proofModal.setAttribute('aria-hidden', 'false');
        }

        function closeProofModal() {
            if (!proofModal) return;
            proofModal.setAttribute('aria-hidden', 'true');
            proofForm.reset();
            proofPreviewWrap.style.display = 'none';
        }

        // File preview handler
        proofFile?.addEventListener('change', function(e) {
            const file = e.target.files?.[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (ev) => {
                    proofPreview.src = ev.target?.result;
                    proofPreviewWrap.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });

        // Modal close handlers
        proofCancel?.addEventListener('click', closeProofModal);
        proofBackdrop?.addEventListener('click', (e) => {
            if (e.target === proofBackdrop) closeProofModal();
        });

        // Form submission
        proofSubmit?.addEventListener('click', async (e) => {
            e.preventDefault();
            if (!proofFile.files?.[0]) {
                alert('Please select a photo');
                return;
            }
            
            proofSubmit.disabled = true;
            proofSubmit.textContent = 'Uploading...';
            
            const formData = new FormData(proofForm);
            try {
                const res = await fetch('api/upload_proof.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    alert('Proof uploaded successfully!');
                    closeProofModal();
                    location.reload();
                } else {
                    alert(data.message || 'Upload failed');
                }
            } catch (err) {
                alert('Upload error: ' + err.message);
            } finally {
                proofSubmit.disabled = false;
                proofSubmit.textContent = 'Upload & Mark Delivered';
            }
        });

        // Add proof upload button to complete delivery action
        document.querySelectorAll('.complete-delivery-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                // Change to open proof modal instead of just marking complete
                const orderId = btn.dataset.orderId;
                const ok = await showConfirmationModal('Deliver Order', 'Ready to deliver order #ORD-' + orderId + '? You\'ll upload proof next.', 'Continue');
                if (!ok) return;
                openProofModal(orderId);
            });
        });
    </script>
</body>
</html>
