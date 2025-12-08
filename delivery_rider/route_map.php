<?php

 session_start();
if (!isset($_SESSION['rider_id'])) {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../db_connection.php';

$rider_id = $_SESSION['rider_id'];

// Fetch rider info (for header)
$rider = ['name' => 'Rider', 'status' => 'available'];
if ($stmt = $conn->prepare("SELECT name, status FROM rider WHERE rider_id = ?")) {
    $stmt->bind_param('i', $rider_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $rider = $row;
    }
    $stmt->close();
}

// Fetch rider last-known coords if available.
// Prefer `rider_location_history` latest entry when the table exists, otherwise fall back to `rider.current_lat/current_lng`.
$riderLocation = null;
$tblRes = $conn->query("SHOW TABLES LIKE 'rider_location_history'");
if ($tblRes && $tblRes->num_rows > 0) {
    // discover column names in rider_location_history
    $cols = [];
    $cRes = $conn->query("SHOW COLUMNS FROM rider_location_history");
    if ($cRes) {
        while ($c = $cRes->fetch_assoc()) { $cols[] = $c['Field']; }
    }

    $find = function(array $candidates) use ($cols) {
        foreach ($candidates as $c) { if (in_array($c, $cols, true)) return $c; }
        return null;
    };

    $latCols = ['lat','latitude','latitude_deg','location_lat','lat_deg','coord_lat','lat_value'];
    $lngCols = ['lng','longitude','long','lon','location_lng','lng_deg','coord_lng','lon_value'];
    $timeCols = ['created_at','recorded_at','timestamp','ts','created','time','updated_at'];

    $latCol = $find($latCols);
    $lngCol = $find($lngCols);
    $timeCol = $find($timeCols) ?: 'id';

    if ($latCol && $lngCol) {
        $select = "{$latCol} AS lat, {$lngCol} AS lng";
        // if we found a time column use it to order, otherwise fallback to primary id
        $orderBy = in_array($timeCol, $cols, true) ? $timeCol : 'id';
        $sql = "SELECT {$select} FROM rider_location_history WHERE rider_id = ? ORDER BY {$orderBy} DESC LIMIT 1";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('i', $rider_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if ($row['lat'] !== null && $row['lng'] !== null && $row['lat'] !== '' && $row['lng'] !== '') {
                    $riderLocation = ['lat' => (float)$row['lat'], 'lng' => (float)$row['lng']];
                }
            }
            $stmt->close();
        }
    }
}

if (is_null($riderLocation)) {
    $colLatRes = $conn->query("SHOW COLUMNS FROM rider LIKE 'current_lat'");
    $colLngRes = $conn->query("SHOW COLUMNS FROM rider LIKE 'current_lng'");
    $hasCurrentCols = ($colLatRes && $colLatRes->num_rows > 0) && ($colLngRes && $colLngRes->num_rows > 0);

    if ($hasCurrentCols) {
        if ($rstmt = $conn->prepare("SELECT current_lat, current_lng FROM rider WHERE rider_id = ? LIMIT 1")) {
            $rstmt->bind_param('i', $rider_id);
            $rstmt->execute();
            $rres = $rstmt->get_result();
            if ($rrow = $rres->fetch_assoc()) {
                if (!is_null($rrow['current_lat']) && !is_null($rrow['current_lng']) && $rrow['current_lat'] !== '' && $rrow['current_lng'] !== '') {
                    $riderLocation = ['lat' => (float)$rrow['current_lat'], 'lng' => (float)$rrow['current_lng']];
                }
            }
            $rstmt->close();
        }
    }
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
    <meta name="description" content="Route Map - Live navigation for delivery riders">
    <meta name="theme-color" content="#6366f1">
    <title>Route Map | Rider - ShoeTakels</title>
    <link rel="icon" type="image/x-icon" href="../upload/picture/logo.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- App styles -->
    <link rel="stylesheet" href="assets/css/rider.css">
    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

    <style>
      /* Route Map Page Specific Styles */
      :root {
        --map-radius: 16px;
        --panel-width: 340px;
        --success-green: #10b981;
        --warning-amber: #f59e0b;
        --danger-red: #ef4444;
      }

      /* Page Header Enhancement */
      .page-header {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.08) 0%, rgba(20, 184, 166, 0.06) 100%);
        padding: 1.5rem 2rem;
        border-radius: var(--radius-lg);
        margin-bottom: 1.5rem;
        border: 1px solid rgba(99, 102, 241, 0.1);
      }

      .page-header h1 {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      .page-header h1::before {
        content: '';
        width: 4px;
        height: 28px;
        background: linear-gradient(180deg, var(--primary-indigo), var(--accent-teal));
        border-radius: 4px;
      }

      .header-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 1rem;
        flex-wrap: wrap;
      }

      /* Map Container */
      #map {
        width: 100%;
        height: calc(100vh - 280px);
        min-height: 400px;
        border-radius: var(--map-radius);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 8px 24px rgba(16,24,40,0.08), 0 2px 8px rgba(16,24,40,0.04);
        overflow: hidden;
      }

      /* Route Grid Layout */
      .route-grid {
        display: grid;
        grid-template-columns: 1fr var(--panel-width);
        gap: 1.5rem;
        align-items: start;
      }

      /* Route Panel Styling */
      .route-panel {
        background: var(--bg-card);
        backdrop-filter: var(--glass-blur);
        -webkit-backdrop-filter: var(--glass-blur);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        max-height: calc(100vh - 280px);
        overflow: hidden;
        display: flex;
        flex-direction: column;
      }

      .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-light);
      }

      .panel-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .delivery-count {
        background: linear-gradient(135deg, var(--primary-indigo), var(--primary-indigo-dark));
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.625rem;
        border-radius: 20px;
      }

      /* Deliveries List */
      .deliveries-list {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        padding-right: 0.5rem;
        scrollbar-width: thin;
        scrollbar-color: var(--border-light) transparent;
      }

      .deliveries-list::-webkit-scrollbar {
        width: 6px;
      }

      .deliveries-list::-webkit-scrollbar-track {
        background: transparent;
      }

      .deliveries-list::-webkit-scrollbar-thumb {
        background: var(--border-light);
        border-radius: 3px;
      }

      .deliveries-list .empty {
        text-align: center;
        padding: 2rem 1rem;
        color: var(--text-muted);
        font-size: 0.9rem;
      }

      .deliveries-list .empty::before {
        content: '📦';
        display: block;
        font-size: 2.5rem;
        margin-bottom: 0.75rem;
      }

      /* Route Item Card */
      .route-item {
        width: 100%;
        background: var(--bg-white);
        border: 1px solid var(--border-light);
        border-radius: var(--radius-md);
        padding: 1rem;
        text-align: left;
        cursor: pointer;
        transition: all var(--transition-fast);
        position: relative;
        overflow: hidden;
      }

      .route-item::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--primary-indigo);
        opacity: 0;
        transition: opacity var(--transition-fast);
      }

      .route-item:hover {
        border-color: var(--primary-indigo);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
        transform: translateY(-2px);
      }

      .route-item:hover::before {
        opacity: 1;
      }

      .route-item.active {
        border-color: var(--primary-indigo);
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(99, 102, 241, 0.02) 100%);
      }

      .route-item.active::before {
        opacity: 1;
      }

      .route-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
      }

      .order-id {
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--text-primary);
      }

      .order-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        font-weight: 500;
      }

      .order-badge.warning {
        background: #fef3c7;
        color: #92400e;
      }

      .order-badge.success {
        background: #d1fae5;
        color: #065f46;
      }

      .customer-name {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 0.25rem;
      }

      .customer-address {
        font-size: 0.8rem;
        color: var(--text-muted);
        display: flex;
        align-items: flex-start;
        gap: 0.375rem;
        line-height: 1.4;
      }

      .customer-address svg {
        flex-shrink: 0;
        margin-top: 2px;
      }

      .coord-info {
        font-size: 0.7rem;
        color: var(--text-muted);
        background: var(--bg-light);
        padding: 0.375rem 0.5rem;
        border-radius: 6px;
        margin-top: 0.5rem;
        font-family: 'Monaco', 'Consolas', monospace;
      }

      /* Panel Footer */
      .panel-footer {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-light);
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
      }

      /* Action Buttons */
      .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.25rem;
        border: none;
        border-radius: var(--radius-md);
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all var(--transition-fast);
        text-decoration: none;
      }

      .action-btn.primary {
        background: linear-gradient(135deg, var(--primary-indigo), var(--primary-indigo-dark));
        color: white;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
      }

      .action-btn.primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
      }

      .action-btn.secondary {
        background: var(--bg-white);
        color: var(--text-primary);
        border: 1px solid var(--border-light);
      }

      .action-btn.secondary:hover {
        background: var(--bg-light);
        border-color: var(--primary-indigo);
      }

      .action-btn.success {
        background: linear-gradient(135deg, var(--success-green), #059669);
        color: white;
      }

      .action-btn.success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
      }

      /* Tracking Button States */
      .tracking-active {
        animation: tracking-pulse 2s infinite;
      }

      @keyframes tracking-pulse {
        0%, 100% { box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
        50% { box-shadow: 0 4px 20px rgba(16, 185, 129, 0.5); }
      }

      /* Map Section */
      .chart-section {
        background: transparent;
        padding: 0;
      }

      /* Leaflet Custom Styles */
      .leaflet-container {
        font-family: 'Inter', sans-serif;
      }

      .leaflet-popup-content-wrapper {
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
      }

      .leaflet-popup-content {
        margin: 0.875rem 1rem;
        font-size: 0.875rem;
      }

      /* Responsive Styles */
      @media (max-width: 1024px) {
        .route-grid {
          grid-template-columns: 1fr;
        }

        .route-panel {
          max-height: 350px;
        }

        #map {
          height: 50vh;
          min-height: 350px;
        }
      }

      @media (max-width: 768px) {
        .page-header {
          padding: 1rem 1.25rem;
        }

        .header-actions {
          flex-direction: column;
          align-items: stretch;
        }

        #map {
          height: 45vh;
          min-height: 300px;
          border-radius: var(--radius-md);
        }

        .route-panel {
          padding: 1rem;
        }
      }
    </style>
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

<?php include 'partials/sidebar.php'; ?>

        <main class="main-content" role="main">
            <header class="page-header">
                <h1>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
                        <line x1="9" y1="3" x2="9" y2="18"/>
                        <line x1="15" y1="6" x2="15" y2="21"/>
                    </svg>
                    Route Map
                </h1>
                <p>Live route and navigation overview for your assigned deliveries.</p>
                <div class="header-actions">
                    <button id="startTrackingBtn" class="action-btn success" title="Start/Stop location tracking">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        Track Location
                    </button>
                    <button id="refreshMapBtn" class="action-btn secondary" title="Refresh map data">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 2v6h-6"/>
                            <path d="M3 12a9 9 0 0 1 15-6.7L21 8"/>
                            <path d="M3 22v-6h6"/>
                            <path d="M21 12a9 9 0 0 1-15 6.7L3 16"/>
                        </svg>
                        Refresh
                    </button>
                </div>
            </header>

                        <?php
                        // Load store start coordinates (if present) to center the map
                        $storeCoords = null;
                        if ($sStmt = $conn->prepare("SELECT store_name, start_lat, start_lng FROM store LIMIT 1")) {
                            $sStmt->execute();
                            $sRes = $sStmt->get_result();
                            if ($sRow = $sRes->fetch_assoc()) {
                                if (!is_null($sRow['start_lat']) && !is_null($sRow['start_lng'])) {
                                    $storeCoords = [
                                        'name' => $sRow['store_name'],
                                        'lat' => (float)$sRow['start_lat'],
                                        'lng' => (float)$sRow['start_lng']
                                    ];
                                }
                            }
                            $sStmt->close();
                        }

                        $deliveries = [];
                        $has_coords = false;
                        $colsRes = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_lat'");
                        if ($colsRes && $colsRes->num_rows > 0) {
                                $has_coords = true;
                        }

                        if ($has_coords) {
                                $sql = "SELECT o.order_id, c.name AS customer_name, c.address, o.delivery_lat, o.delivery_lng, o.order_date FROM orders o LEFT JOIN customer c ON o.customer_id = c.customer_id WHERE o.rider_id = ? AND o.status IN ('pending','delivering') ORDER BY o.order_date ASC";
                        } else {
                                $sql = "SELECT o.order_id, c.name AS customer_name, c.address, o.order_date FROM orders o LEFT JOIN customer c ON o.customer_id = c.customer_id WHERE o.rider_id = ? AND o.status IN ('pending','delivering') ORDER BY o.order_date ASC";
                        }

                        if ($stmt = $conn->prepare($sql)) {
                                $stmt->bind_param('i', $rider_id);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                while ($row = $res->fetch_assoc()) {
                                        $deliveries[] = $row;
                                }
                                $stmt->close();
                        }
                        ?>

                        <section class="chart-section" aria-label="Route map">
                <div class="route-grid">
                    <div id="map" role="application" aria-label="Interactive route map"></div>
                    
                    <aside class="route-panel" aria-label="Deliveries list">
                        <div class="panel-header">
                            <h3>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="1" y="3" width="15" height="13"/>
                                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                    <circle cx="5.5" cy="18.5" r="2.5"/>
                                    <circle cx="18.5" cy="18.5" r="2.5"/>
                                </svg>
                                Assigned Deliveries
                            </h3>
                            <span class="delivery-count"><?php echo count($deliveries); ?></span>
                        </div>
                        
                        <div class="deliveries-list" id="routeDeliveriesList">
                            <?php if (count($deliveries) === 0): ?>
                                <div class="empty">No active deliveries assigned.</div>
                            <?php else: ?>
                                <?php foreach ($deliveries as $index => $d): 
                                    // Prepare coord info for display (if present)
                                    $lat = isset($d['delivery_lat']) ? (float)$d['delivery_lat'] : null;
                                    $lng = isset($d['delivery_lng']) ? (float)$d['delivery_lng'] : null;
                                    $coordInfo = '';
                                    $coordWarning = false;
                                    if ($lat !== null && $lng !== null) {
                                        $coordInfo = sprintf('%.6f, %.6f', $lat, $lng);
                                        // simple heuristic: lat must be between -90 and 90, lng between -180 and 180
                                        if (!($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180)) {
                                            $coordWarning = true;
                                        }
                                    }
                                ?>
                                    <button class="route-item<?php echo $index === 0 ? ' active' : ''; ?>" data-order="<?php echo htmlspecialchars($d['order_id']); ?>" data-lat="<?php echo $lat; ?>" data-lng="<?php echo $lng; ?>">
                                        <div class="route-item-header">
                                            <span class="order-id">#ORD-<?php echo htmlspecialchars($d['order_id']); ?></span>
                                            <?php if ($coordWarning): ?>
                                                <span class="order-badge warning">⚠ Invalid coords</span>
                                            <?php elseif ($lat && $lng): ?>
                                                <span class="order-badge success">📍 Located</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="customer-name">
                                            <strong><?php echo htmlspecialchars($d['customer_name'] ?? 'Guest'); ?></strong>
                                        </div>
                                        <div class="customer-address" title="<?php echo htmlspecialchars($d['address'] ?? ''); ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <span><?php echo htmlspecialchars($d['address'] ?? 'No address provided'); ?></span>
                                        </div>
                                        <?php if ($coordInfo): ?>
                                            <div class="coord-info">📌 <?php echo htmlspecialchars($coordInfo); ?></div>
                                        <?php endif; ?>
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="panel-footer">
                            <button onclick="window.location.href='dashboard.php'" class="action-btn secondary" style="width:100%;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 12H5"/>
                                    <polyline points="12 19 5 12 12 5"/>
                                </svg>
                                Back to Dashboard
                            </button>
                        </div>
                    </aside>
                </div>
            </section>

                        <script>
                            // Inject deliveries data and store start coords for the map script
                            window.assignedDeliveries = <?php echo json_encode($deliveries, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || [];
                            window.assignedDeliveriesHasCoords = <?php echo $has_coords ? 'true' : 'false'; ?>;
                            window.storeStart = <?php echo json_encode($storeCoords, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || null;
                            window.riderLocation = <?php echo json_encode($riderLocation, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || null;

                            // Optional debug: allow loading a specific rider's location without session
                            // Usage: add `?debug_rider_id=2&debug_token=localdebug123` to the URL.
                            <?php if (isset($_GET['debug_rider_id'])): ?>
                                window.DEBUG_RIDER_ID = <?php echo (int)$_GET['debug_rider_id']; ?>;
                                window.DEBUG_RIDER_TOKEN = <?php echo json_encode($_GET['debug_token'] ?? 'localdebug123'); ?>;
                            <?php else: ?>
                                window.DEBUG_RIDER_ID = null;
                                window.DEBUG_RIDER_TOKEN = null;
                            <?php endif; ?>
                        </script>
        </main>
    </div>

    <!-- Leaflet JS -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
        <!-- Leaflet PolylineDecorator for directional arrows -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-polylinedecorator/1.7.0/leaflet.polylineDecorator.min.js" integrity="" crossorigin=""></script>

        <script>
            // Friendly fallback: if Leaflet doesn't initialize, show a message in the map container
            (function(){
                function showMapError() {
                    var mapEl = document.getElementById('map');
                    if (!mapEl) return;
                    mapEl.innerHTML = '<div style="padding:24px;font-size:16px;color:#7f1d1d;background:#fff7f7;border:1px solid #fecaca;border-radius:8px;">Map failed to load. Check your network or open developer console for errors.</div>';
                }

                // Wait a short time for Leaflet to load; if not present, show error
                setTimeout(function(){
                    if (typeof L === 'undefined') {
                        console.warn('Leaflet appears not to have loaded.');
                        showMapError();
                    }
                }, 800);
            })();
        </script>

    <!-- Route map initializer -->
    <script src="assets/js/route-map.js"></script>

    <script>
      // Optionally provide route coordinates from server in future
      // For now route-map.js will use sample coordinates
    </script>
    <script>
      // Debug: fetch rider's current location from API
      fetch('api/debug_get_rider_location.php?rider_id=1&token=localdebug123', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => console.log('debug API ->', data))
        .catch(err => console.error(err));
    </script>
    <script>
      // Replace with the lat/lng returned by the debug API if different
      const testLat = 15.9247992;
      const testLng = 120.3488955;

      if (!window.routeMap) { console.error('routeMap not found on page'); } else {
        const m = L.circleMarker([testLat, testLng], { radius: 9, color: '#00cc66', fillColor: '#00cc66', fillOpacity: 0.95 }).addTo(window.routeMap);
        m.bindPopup('<strong>Debug rider #1</strong><br>' + testLat + ', ' + testLng).openPopup();
        window.routeMap.setView([testLat, testLng], 15);
        console.log('Added debug marker at', testLat, testLng);
      }
    </script>
    
    <!-- Notifications JavaScript -->
    <script src="assets/js/notifications.js"></script>
</body>
</html>
