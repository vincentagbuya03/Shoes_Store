<?php

 session_start();
if (!isset($_SESSION['rider_id'])) {
    header('Location: /login.php');
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

// fallback to rider.current_lat/current_lng if no history table or no rows
if (is_null($riderLocation)) {
    // Ensure the columns exist before querying to avoid SQL errors
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
    <title>Route Map | Rider - ShoeTakels</title>
    <link rel="icon" type="image/x-icon" href="../upload/picture/logo.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- App styles (reuses rider.css) -->
    <link rel="stylesheet" href="assets/css/rider.css">

    <!-- Leaflet (Map) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>

    <style>
      /* small override for map container */
      #map {
        width: 100%;
        height: 70vh;
        border-radius: 12px;
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 6px 18px rgba(16,24,40,0.06);
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

        <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
            <header class="sidebar-header">
                <div class="rider-avatar" aria-hidden="true">
                    <?php
                    // Inline the SVG asset to avoid caching/path issues. Fallback to initials if file missing.
                        echo '<div class="rider-initials">' . htmlspecialchars($initials) . '</div>';
                    ?>
                </div>
                <div class="rider-info">
                    <h2><?php echo htmlspecialchars($rider['name']); ?></h2>
                    <p>Rider #<?php echo (int)$rider_id; ?></p>
                    <div class="rider-status">
                        <span class="status-dot" aria-hidden="true"></span>
                        <span><?php echo ucfirst(htmlspecialchars($rider['status'])); ?></span>
                    </div>
                </div>
            </header>

            <section class="quick-stats" aria-label="Quick statistics">
                <div class="stat-item">
                    <span class="stat-label">Active Deliveries</span>
                    <span class="stat-value highlight"><?php // dynamic value not available here ?></span>
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

            <nav class="sidebar-nav">
                <ul class="nav-list" role="menubar">
                    <li class="nav-item" role="none">
                        <a href="dashboard.php" class="nav-link" role="menuitem">
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
                        <a href="route_map.php" class="nav-link active" role="menuitem" aria-current="page">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
                                <line x1="9" y1="3" x2="9" y2="18"/>
                                <line x1="15" y1="6" x2="15" y2="21"/>
                            </svg>
                            Route Map
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content" role="main">
            <header class="page-header">
                <h1>Route Map</h1>
                                <p>Live route and navigation overview for your assigned deliveries.</p>
                                <div style="margin-top:0.75rem;">
                                    <button id="startTrackingBtn" class="action-btn" title="Start/Stop location tracking">Track Location</button>
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
                                    <div id="map" role="application" aria-label="Route map"></div>
                                    <aside class="route-panel" aria-label="Deliveries list">
                                        <h3 style="margin-top:0;margin-bottom:0.5rem;">Assigned Deliveries</h3>
                                        <div class="deliveries-list" id="routeDeliveriesList">
                                            <?php if (count($deliveries) === 0): ?>
                                                <div class="empty">No active deliveries assigned.</div>
                                            <?php else: ?>
                                                <?php foreach ($deliveries as $d): 
                                                    // Prepare coord info for display (if present)
                                                    $lat = isset($d['delivery_lat']) ? (float)$d['delivery_lat'] : null;
                                                    $lng = isset($d['delivery_lng']) ? (float)$d['delivery_lng'] : null;
                                                    $coordInfo = '';
                                                    $coordWarning = false;
                                                    if ($lat !== null && $lng !== null) {
                                                        $coordInfo = sprintf('lat: %s, lng: %s', $lat, $lng);
                                                        // simple heuristic: lat must be between -90 and 90, lng between -180 and 180
                                                        if (!($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180)) {
                                                            $coordWarning = true;
                                                        }
                                                    }
                                                ?>
                                                    <button class="route-item" data-order="<?php echo htmlspecialchars($d['order_id']); ?>">
                                                        <div style="display:flex;justify-content:space-between;align-items:center;">
                                                          <div style="font-weight:600;">#ORD-<?php echo htmlspecialchars($d['order_id']); ?></div>
                                                          <?php if ($coordWarning): ?>
                                                            <span style="background:#fecaca;color:#7f1d1d;padding:2px 6px;border-radius:6px;font-size:12px;">coords suspicious</span>
                                                          <?php endif; ?>
                                                        </div>
                                                        <div style="font-size:0.9rem;color:var(--text-secondary);"><?php echo htmlspecialchars($d['customer_name'] ?? 'Guest'); ?></div>
                                                        <div style="font-size:0.8rem;color:var(--text-muted);max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($d['address'] ?? ''); ?>"><?php echo htmlspecialchars($d['address'] ?? ''); ?></div>
                                                        <?php if ($coordInfo): ?>
                                                          <div style="font-size:0.75rem;color:#334155;margin-top:6px;"><?php echo htmlspecialchars($coordInfo); ?></div>
                                                        <?php endif; ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div style="margin-top:0.75rem;">
                                            <button onclick="window.location.href='dashboard.php'" class="action-btn">Back to Dashboard</button>
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
</body>
</html>
