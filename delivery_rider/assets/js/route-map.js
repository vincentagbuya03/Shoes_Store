(function(){
  'use strict';

  // Enhanced Leaflet route demo - draws a sample route and maps assigned deliveries
  function initRouteMap() {
    if (typeof L === 'undefined') {
      console.warn('Leaflet not loaded');
      return;
    }

    // Sample base route coordinates (fallback)
    const routeCoords = [
      [14.5995, 120.9842],
      [14.6010, 120.9900],
      [14.6035, 120.9950],
      [14.6060, 120.9980]
    ];

    const map = L.map('map', { zoomControl: true }).setView(routeCoords[0], 14);

    const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // We'll draw a route polyline. Default fallback uses sample routeCoords.
    let routeLine = L.polyline(routeCoords, { color: '#6366f1', weight: 5, opacity: 0.9, smoothFactor: 1 }).addTo(map);

    // Helper: request driving route geometry from OSRM (coords array of [lat,lng])
    async function fetchRouteViaOSRM(latLngArray) {
      if (!Array.isArray(latLngArray) || latLngArray.length < 2) return null;
      try {
        // Build coordinate list as lon,lat;lon,lat
        const parts = latLngArray.map(p => {
          const lat = Number(p[0]);
          const lng = Number(p[1]);
          return (isNaN(lng) || isNaN(lat)) ? null : (lng + ',' + lat);
        }).filter(Boolean);
        if (parts.length < 2) return null;
        const url = 'https://router.project-osrm.org/route/v1/driving/' + parts.join(';') + '?overview=full&geometries=geojson';
        const resp = await fetch(url);
        if (!resp.ok) return null;
        const body = await resp.json();
        if (!body || !body.routes || !body.routes.length) return null;
        const geom = body.routes[0].geometry; // GeoJSON LineString
        if (!geom || !geom.coordinates) return null;
        // convert [lng,lat] to [lat,lng]
        const pts = geom.coordinates.map(c => [c[1], c[0]]);
        return pts;
      } catch (err) {
        console.warn('OSRM route fetch failed', err);
        return null;
      }
    }

    // If a store start coordinate is provided by the server, center there and add a marker
    const storeStart = (typeof window.storeStart !== 'undefined') ? window.storeStart : null;
    if (storeStart && storeStart.lat && storeStart.lng) {
      try {
        const sLat = parseFloat(storeStart.lat);
        const sLng = parseFloat(storeStart.lng);
        if (!isNaN(sLat) && !isNaN(sLng)) {
          map.setView([sLat, sLng], 13);
          const storeMarker = L.circleMarker([sLat, sLng], { radius: 8, color: '#14b8a6', fillColor: '#14b8a6', fillOpacity: 0.9 }).addTo(map);
          storeMarker.bindPopup('<strong>' + (storeStart.name ? escapeHtml(storeStart.name) : 'Store') + '</strong><br>Start location');
        }
      } catch (e) {
        console.warn('Invalid storeStart coordinates', e);
      }
    } else {
      map.fitBounds(polyline.getBounds(), { padding: [40, 40] });
    }

    // Layer control (can be extended)
    L.control.layers({ 'OpenStreetMap': osm }, null, { position: 'topright' }).addTo(map);

    // Rider icon (use the SVG we added). Path is relative to this page.
    const riderIcon = L.icon({
      iconUrl: 'assets/img/rider-logo.svg',
      iconSize: [56, 56],
      iconAnchor: [28, 28],
      popupAnchor: [0, -18],
      className: 'rider-marker-svg'
    });

    // Read deliveries injected by server
    const deliveries = Array.isArray(window.assignedDeliveries) ? window.assignedDeliveries : [];
    const hasCoords = !!window.assignedDeliveriesHasCoords;

    const markers = {};

    if (deliveries.length === 0) {
      // nothing assigned — keep sample markers
    } else {
      // If deliveries have coords, use them; otherwise distribute along the base route
      deliveries.forEach(function(d, idx) {
        let lat = null, lng = null;
        if (hasCoords && d.delivery_lat && d.delivery_lng) {
          lat = parseFloat(d.delivery_lat);
          lng = parseFloat(d.delivery_lng);
        } else {
          // pick a point along the sample route
          const i = Math.floor(idx * (routeCoords.length - 1) / Math.max(1, deliveries.length - 1));
          const p = routeCoords[i] || routeCoords[routeCoords.length - 1];
          lat = p[0]; lng = p[1];
        }

        // Normalize coordinates: detect swapped values where lat is outside [-90,90]
        // but lng is within [-90,90] — likely they were stored as (lng,lat).
        if ((lat < -90 || lat > 90) && (lng >= -90 && lng <= 90)) {
          console.warn('Detected suspicious coords for order', d.order_id, 'swapping lat/lng');
          const tmp = lat; lat = lng; lng = tmp;
        }

        // Final sanity clamp: if still invalid, fall back to sample point
        if (!(lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180)) {
          const fallback = routeCoords[Math.min(idx, routeCoords.length - 1)];
          lat = fallback[0]; lng = fallback[1];
        }

        const marker = L.marker([lat, lng]).addTo(map);
        const popupHtml = '<strong>#ORD-' + escapeHtml(d.order_id) + '</strong><br>' + escapeHtml(d.customer_name || 'Guest') + '<br>' + escapeHtml(d.address || '');
        marker.bindPopup(popupHtml);
        markers[String(d.order_id)] = marker;
      });
      // If we have rider location and deliveries with coords, attempt to draw a road-following route from rider to deliveries
      (async function drawRoadRouteIfPossible(){
        try {
          // Use rider location as starting point (prefer over store)
          const riderLoc = (typeof window.riderLocation !== 'undefined') ? window.riderLocation : null;
          let startLat = null, startLng = null;
          
          if (riderLoc && riderLoc.lat && riderLoc.lng) {
            startLat = parseFloat(riderLoc.lat);
            startLng = parseFloat(riderLoc.lng);
          } else if (storeStart && storeStart.lat && storeStart.lng) {
            // Fallback to store if no rider location
            startLat = parseFloat(storeStart.lat);
            startLng = parseFloat(storeStart.lng);
          }
          
          if (startLat === null || startLng === null || isNaN(startLat) || isNaN(startLng)) return;
          
          // collect coords for those deliveries that have valid numeric coords
          const seq = [];
          seq.push([startLat, startLng]);
          deliveries.forEach(function(d){
            if (d.delivery_lat && d.delivery_lng) {
              const la = parseFloat(d.delivery_lat); const ln = parseFloat(d.delivery_lng);
              if (!isNaN(la) && !isNaN(ln)) seq.push([la, ln]);
            }
          });
          if (seq.length < 2) return;
          const osrmPts = await fetchRouteViaOSRM(seq);
          if (osrmPts && osrmPts.length > 1) {
            try { map.removeLayer(routeLine); } catch (e) {}
            routeLine = L.polyline(osrmPts, { color: '#6366f1', weight: 5, opacity: 0.9, smoothFactor: 1 }).addTo(map);
            map.fitBounds(routeLine.getBounds(), { padding: [40,40] });
          }
        } catch (e) { /* ignore */ }
      })();
    }

    // Rider marker variable (declared here so it's accessible in click handlers)
    let riderMarker = null;

    // Wire up list clicks to fly to markers and draw rider->customer route
    const list = document.getElementById('routeDeliveriesList');
    let selectedRouteLayer = null;
    if (list) {
      list.addEventListener('click', function(e) {
        const btn = e.target.closest('.route-item');
        if (!btn) return;
        const orderId = btn.getAttribute('data-order');
        if (!orderId) return;
        const m = markers[String(orderId)];
        if (m) {
          map.flyTo(m.getLatLng(), 16, { duration: 0.8 });
          m.openPopup();

          // Draw route from rider location to this customer
          // Get current rider location (use marker if exists, otherwise fallback to initial riderLocation)
          let startLat = null, startLng = null;
          
          if (riderMarker) {
            const riderPos = riderMarker.getLatLng();
            startLat = riderPos.lat;
            startLng = riderPos.lng;
          } else {
            const riderLoc = (typeof window.riderLocation !== 'undefined') ? window.riderLocation : null;
            if (riderLoc && riderLoc.lat && riderLoc.lng) {
              startLat = parseFloat(riderLoc.lat);
              startLng = parseFloat(riderLoc.lng);
            } else if (storeStart && storeStart.lat && storeStart.lng) {
              // Fallback to store if no rider location available
              startLat = parseFloat(storeStart.lat);
              startLng = parseFloat(storeStart.lng);
            }
          }
          
          if (startLat !== null && startLng !== null && !isNaN(startLat) && !isNaN(startLng)) {
            // remove previous selected route
            if (selectedRouteLayer) {
              try { map.removeLayer(selectedRouteLayer); } catch (err) {}
              selectedRouteLayer = null;
            }
            const dest = m.getLatLng();
            if (dest) {
              (async function(){
                // try OSRM driving route for rider->dest
                const pts = await fetchRouteViaOSRM([[startLat, startLng], [dest.lat, dest.lng]]);
                if (pts && pts.length > 1) {
                  selectedRouteLayer = L.polyline(pts, { color: '#f97316', weight: 5, opacity: 0.95 }).addTo(map);
                  map.fitBounds(selectedRouteLayer.getBounds().pad(0.25));
                } else {
                  // fallback to straight line
                  selectedRouteLayer = L.polyline([[startLat, startLng], [dest.lat, dest.lng]], { color: '#f97316', weight: 5, opacity: 0.95 }).addTo(map);
                  const bounds = L.latLngBounds([[startLat, startLng], [dest.lat, dest.lng]]);
                  map.fitBounds(bounds.pad(0.25));
                }
              })();
            }
          }
        }
      });
    }

    // Expose map for debugging/integration
    window.routeMap = map;

    // Show rider location if available and start polling for updates every 15s
    const riderLoc = (typeof window.riderLocation !== 'undefined') ? window.riderLocation : null;
    function createOrUpdateRiderMarker(lat, lng) {
      if (lat === null || lng === null || typeof lat === 'undefined' || typeof lng === 'undefined') return;
      const latNum = parseFloat(lat);
      const lngNum = parseFloat(lng);
      if (isNaN(latNum) || isNaN(lngNum)) return;

      const latlng = L.latLng(latNum, lngNum);
      if (!riderMarker) {
        // Use custom SVG icon for rider
        try {
          riderMarker = L.marker(latlng, { icon: riderIcon }).addTo(map);
        } catch (e) {
          // fallback
          riderMarker = L.circleMarker(latlng, { radius: 7, color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.95 }).addTo(map);
        }
        riderMarker.bindPopup('<strong>Your location</strong>');
      } else {
        try { riderMarker.setLatLng(latlng); } catch (err) { console.warn('Failed to update rider marker', err); }
      }

      // If the rider is outside the current bounds, pan the map slightly to keep them visible
      try {
        if (!map.getBounds().contains(latlng)) {
          map.panTo(latlng, { animate: true, duration: 0.6 });
        }
      } catch (err) { /* ignore */ }
    }

    if (riderLoc && riderLoc.lat && riderLoc.lng) {
      try {
        createOrUpdateRiderMarker(parseFloat(riderLoc.lat), parseFloat(riderLoc.lng));
      } catch (e) {
        console.warn('Invalid riderLocation', e);
      }
    }

    // Poll for rider location regularly (every 15s)
    const RIDER_POLL_INTERVAL_MS = 15000;
    // If the server injected debug vars, use the debug API with token so you can
    // fetch any rider's latest location without being logged in as that rider.
    let RIDER_LOCATION_API = 'api/get_rider_location.php';
    const DEBUG_RIDER_ID = (typeof window.DEBUG_RIDER_ID !== 'undefined') ? window.DEBUG_RIDER_ID : null;
    const DEBUG_RIDER_TOKEN = (typeof window.DEBUG_RIDER_TOKEN !== 'undefined') ? window.DEBUG_RIDER_TOKEN : null;
    const IS_DEBUG_API = DEBUG_RIDER_ID && DEBUG_RIDER_TOKEN;
    if (IS_DEBUG_API) {
      RIDER_LOCATION_API = 'api/debug_get_rider_location.php?rider_id=' + encodeURIComponent(DEBUG_RIDER_ID) + '&token=' + encodeURIComponent(DEBUG_RIDER_TOKEN);
    }

    let lastRiderTs = null;
    async function fetchRiderLocation() {
      try {
        // For debug API calls we don't send credentials; for production session API we include same-origin credentials
        const fetchOpts = IS_DEBUG_API ? { method: 'GET' } : { method: 'GET', credentials: 'same-origin' };
        const res = await fetch(RIDER_LOCATION_API, fetchOpts);
        if (!res.ok) {
          // 401/404 etc. ignore silently
          return;
        }
        const data = await res.json();
        if (data && data.success && typeof data.lat !== 'undefined' && typeof data.lng !== 'undefined') {
          // Use returned timestamp (if present) to avoid redundant updates
          if (data.ts) {
            // normalize numeric timestamp if possible
            const ts = isNaN(Number(data.ts)) ? String(data.ts) : Number(data.ts);
            if (lastRiderTs !== null && lastRiderTs === ts) return;
            lastRiderTs = ts;
          }
          createOrUpdateRiderMarker(parseFloat(data.lat), parseFloat(data.lng));
        }
      } catch (err) {
        console.warn('Failed to fetch rider location', err);
      }
    }

    // Start polling after small delay to avoid race with initial page load
    setTimeout(function(){
      fetchRiderLocation();
      setInterval(fetchRiderLocation, RIDER_POLL_INTERVAL_MS);
    }, 800);

    // ----- Geolocation tracking (save to server) -----
    // Use a polling interval to save location every 5s (reduced from 1s to avoid excessive DB writes).
    let trackingIntervalId = null;
    const SAVE_LOCATION_API = 'api/save_rider_location.php';

    async function sendLocationToServer(lat, lng, accuracy, heading) {
      if (!lat || !lng) return;
      // Do not attempt to save when viewing another rider via debug API
      if (IS_DEBUG_API) {
        console.warn('Debug view active — not saving location to server.');
        return;
      }
      try {
        const body = { lat: Number(lat), lng: Number(lng) };
        if (typeof accuracy !== 'undefined') body.accuracy = Number(accuracy);
        if (typeof heading !== 'undefined') body.heading = Number(heading);
        const res = await fetch(SAVE_LOCATION_API, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(body)
        });
        if (!res.ok) {
          // try to surface server response body for easier debugging
          try {
            const text = await res.text();
            console.warn('Failed to save location, status', res.status, 'response:', text);
          } catch (e) {
            console.warn('Failed to save location, status', res.status);
          }
          return;
        }
        const data = await res.json();
        if (data && data.success) {
          // update marker immediately with authoritative saved coords
          createOrUpdateRiderMarker(parseFloat(data.lat), parseFloat(data.lng));
        }
      } catch (err) {
        console.warn('Error saving location', err);
      }
    }

    function onGeoSuccess(pos) {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      const accuracy = pos.coords.accuracy;
      const heading = pos.coords.heading || null;
      // update visual marker immediately
      createOrUpdateRiderMarker(lat, lng);
      // persist to server
      sendLocationToServer(lat, lng, accuracy, heading);
    }

    function onGeoError(err) {
      console.warn('Geolocation error', err);
    }

    function startTracking() {
      if (!('geolocation' in navigator)) {
        alert('Geolocation is not available in this browser.');
        return;
      }
      if (IS_DEBUG_API) {
        alert('Debug view is active — tracking (save) is disabled. Remove debug params to enable.');
        return;
      }
      if (trackingIntervalId !== null) return;

      // Poll every 5000ms using getCurrentPosition to save location periodically.
      function pollOnce() {
        navigator.geolocation.getCurrentPosition(function(pos){
          onGeoSuccess(pos);
        }, function(err){
          onGeoError(err);
        }, { enableHighAccuracy: true, maximumAge: 500, timeout: 5000 });
      }

      // Immediately perform one poll, then set interval
      pollOnce();
      trackingIntervalId = setInterval(pollOnce, 5000);

      const btn = document.getElementById('startTrackingBtn');
      if (btn) { btn.textContent = 'Stop Tracking'; btn.classList.add('active'); }
    }

    function stopTracking() {
      if (trackingIntervalId === null) return;
      try { clearInterval(trackingIntervalId); } catch (e) { /* ignore */ }
      trackingIntervalId = null;
      const btn = document.getElementById('startTrackingBtn');
      if (btn) { btn.textContent = 'Track Location'; btn.classList.remove('active'); }
    }

    // Attach to UI button if present
    const trackBtn = document.getElementById('startTrackingBtn');
    if (trackBtn) {
      trackBtn.addEventListener('click', function(){
        if (trackingIntervalId === null) startTracking(); else stopTracking();
      });
      // small UX: set initial label
      trackBtn.textContent = 'Track Location';
    }

    // Auto-start tracking every 1s if not in debug mode
    try {
      if (!IS_DEBUG_API) {
        // give the page a moment to finish setup
        setTimeout(function(){ startTracking(); }, 1200);
      }
    } catch (e) { /* ignore */ }
  }

  function escapeHtml(input) {
    if (!input && input !== 0) return '';
    return String(input)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRouteMap);
  } else {
    initRouteMap();
  }
})();
