/**
 * Delivery Rider Dashboard - Single Clean Vanilla JavaScript File
 *
 * - Consolidates chart init, proof modal + upload, action delegation,
 *   search & status filters, sidebar toggle, SVG animations and keyboard nav.
 * - Fixed duplicate function names, Chart.js options structure, and multiple
 *   small bugs that caused runtime errors (missing null-checks, wrong option keys).
 *
 * Usage: include this file after the DOM and after Chart.js (optional).
 */

(function () {
  'use strict';

  // -------------------------
  // Utilities
  // -------------------------
  function safeJsonParse(text) {
    try {
      return JSON.parse(text);
    } catch (e) {
      return null;
    }
  }

  function showToast(message) {
    if (!message) return;
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
      toastContainer = document.createElement('div');
      toastContainer.id = 'toastContainer';
      toastContainer.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 10000;';
      document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = [
      'background: #1e293b',
      'color: #fff',
      'padding: 10px 14px',
      'border-radius: 8px',
      'margin-top: 8px',
      'opacity: 0',
      'transform: translateX(20px)',
      'transition: all 0.25s ease',
      'font-size: 14px',
      'box-shadow: 0 4px 12px rgba(0,0,0,0.12)'
    ].join(';');

    toastContainer.appendChild(toast);
    requestAnimationFrame(function () {
      toast.style.opacity = '1';
      toast.style.transform = 'translateX(0)';
    });

    setTimeout(function () {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(20px)';
      setTimeout(function () {
        if (toast.parentNode) toast.parentNode.removeChild(toast);
      }, 300);
    }, 2500);
  }

  // -------------------------
  // CHART INITIALIZATION
  // -------------------------
  function initDeliveriesChart() {
    const ctx = document.getElementById('deliveriesChart');
    if (!ctx || typeof Chart === 'undefined') return;

    // Use dynamic data from PHP if available, otherwise use defaults
    const labels = (window.chartData && window.chartData.labels) ? window.chartData.labels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const deliveriesData = (window.chartData && window.chartData.values) ? window.chartData.values : [0, 0, 0, 0, 0, 0, 0];

    const chartConfig = {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Deliveries',
            data: deliveriesData,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.08)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#6366f1',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: '#64748b', font: { size: 12, weight: '500' } }
          },
          y: {
            beginAtZero: true,
            grid: { color: 'rgba(226,232,240,0.5)', drawBorder: false },
            ticks: { color: '#64748b', font: { size: 12 }, stepSize: 5 }
          }
        },
        plugins: {
          legend: { display: false }
        }
      }
    };

    new Chart(ctx, chartConfig);
  }

  // -------------------------
  // SEARCH & STATUS FILTERS
  // -------------------------
  function updateVisibleCount() {
    const tableBody = document.getElementById('deliveriesTableBody');
    const countElement = document.querySelector('.deliveries-count');
    if (!tableBody || !countElement) return;
    const allRows = Array.from(tableBody.querySelectorAll('tr'));
    const visibleRows = allRows.filter(r => r.offsetParent !== null);
    countElement.textContent = visibleRows.length + ' of ' + allRows.length + ' deliveries';
  }

  function applyFilters() {
    const searchInput = document.getElementById('deliverySearch');
    const filterSelect = document.getElementById('statusFilter');
    const tableBody = document.getElementById('deliveriesTableBody');
    if (!tableBody) return;

    const searchTerm = (searchInput && searchInput.value || '').toLowerCase().trim();
    const filterValue = (filterSelect && filterSelect.value || 'all').toLowerCase();

    const rows = Array.from(tableBody.querySelectorAll('tr'));
    rows.forEach(function (row) {
      const text = row.textContent.toLowerCase();
      const statusBadge = row.querySelector('.status-badge');

      const matchesSearch = searchTerm === '' || text.includes(searchTerm);

      let matchesStatus = true;
      if (filterValue !== 'all' && statusBadge) {
        // badge class names might be 'pending', 'transit', 'delivered'
        matchesStatus = statusBadge.classList.contains(filterValue);
      }

      const isVisible = matchesSearch && matchesStatus;
      row.style.display = isVisible ? '' : 'none';
    });

    updateVisibleCount();
  }

  function initSearchFilter() {
    const searchInput = document.getElementById('deliverySearch');
    if (!searchInput) return;
    searchInput.addEventListener('input', debounce(applyFilters, 150));
  }

  function initStatusFilter() {
    const filterSelect = document.getElementById('statusFilter');
    if (!filterSelect) return;
    filterSelect.addEventListener('change', applyFilters);
  }

  // small debounce helper
  function debounce(fn, wait) {
    let t = null;
    return function () {
      clearTimeout(t);
      const args = arguments;
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  // -------------------------
  // SIDEBAR TOGGLE (Mobile)
  // -------------------------
  function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!toggleBtn || !sidebar) return;

    toggleBtn.addEventListener('click', function () {
      sidebar.classList.toggle('open');
      if (overlay) overlay.classList.toggle('active');
      toggleBtn.setAttribute('aria-expanded', sidebar.classList.contains('open'));
    });

    if (overlay) {
      overlay.addEventListener('click', function () {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        toggleBtn.setAttribute('aria-expanded', 'false');
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        toggleBtn.setAttribute('aria-expanded', 'false');
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        toggleBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // -------------------------
  // PROOF MODAL & UPLOAD
  // -------------------------
  function openProofModal(orderId, row, originatingButton) {
    const modal = document.getElementById('proofModal');
    const orderInput = document.getElementById('proofOrderId');
    const fileInput = document.getElementById('proofFile');
    const previewWrap = document.getElementById('proofPreviewWrap');
    const previewImg = document.getElementById('proofPreview');

    if (!modal || !orderInput || !fileInput) return;

    modal.setAttribute('aria-hidden', 'false');
    orderInput.value = orderId || '';

    // revoke previous preview URL
    try {
      if (fileInput._lastPreviewUrl) {
        URL.revokeObjectURL(fileInput._lastPreviewUrl);
        fileInput._lastPreviewUrl = null;
      }
    } catch (e) { /* ignore */ }

    fileInput.value = '';
    if (previewImg) previewImg.src = '';
    if (previewWrap) previewWrap.style.display = 'none';

    modal._row = row || null;
    modal._originBtn = originatingButton || null;

    // focus file input for quick access
    try { fileInput.focus(); } catch (e) { /* ignore */ }
  }

  function closeProofModal() {
    const modal = document.getElementById('proofModal');
    if (!modal) return;
    modal.setAttribute('aria-hidden', 'true');

    try {
      const fileInput = document.getElementById('proofFile');
      const previewImg = document.getElementById('proofPreview');
      const previewWrap = document.getElementById('proofPreviewWrap');
      if (fileInput) {
        if (fileInput._lastPreviewUrl) {
          try { URL.revokeObjectURL(fileInput._lastPreviewUrl); } catch (e) { /* ignore */ }
          fileInput._lastPreviewUrl = null;
        }
        fileInput.value = '';
      }
      if (previewImg) previewImg.src = '';
      if (previewWrap) previewWrap.style.display = 'none';
      const debugEl = document.getElementById('proofDebug');
      const debugPre = document.getElementById('proofDebugPre');
      if (debugEl) debugEl.style.display = 'none';
      if (debugPre) debugPre.textContent = '';
    } catch (e) { /* ignore */ }

    modal._row = null;
    modal._originBtn = null;
  }

  function waitForUserFile(fileEl, timeoutMs = 10000) {
    return new Promise((resolve) => {
      if (!fileEl) return resolve(null);
      const onChange = () => {
        const nf = fileEl.files && fileEl.files[0];
        if (nf) {
          cleanup();
          resolve(nf);
        }
      };
      const cleanup = () => {
        try { fileEl.removeEventListener('change', onChange); } catch (e) { /* ignore */ }
        clearTimeout(timer);
      };
      fileEl.addEventListener('change', onChange);
      try { fileEl.click(); } catch (e) { /* ignore */ }
      const timer = setTimeout(() => {
        cleanup();
        resolve(null);
      }, timeoutMs);
    });
  }

  function initProofModal() {
    const modal = document.getElementById('proofModal');
    if (!modal) return;
    const backdrop = modal.querySelector('.proof-modal-backdrop');
    const fileInput = document.getElementById('proofFile');
    const previewWrap = document.getElementById('proofPreviewWrap');
    const previewImg = document.getElementById('proofPreview');
    const form = document.getElementById('proofForm');
    const cancelBtn = document.getElementById('proofCancel');
    const proofDebug = document.getElementById('proofDebug');
    const proofDebugPre = document.getElementById('proofDebugPre');
    const proofDebugClose = document.getElementById('proofDebugClose');
    const proofDebugToggle = document.getElementById('proofDebugToggle');

    if (backdrop) backdrop.addEventListener('click', closeProofModal);
    if (cancelBtn) cancelBtn.addEventListener('click', function (e) { e.preventDefault(); closeProofModal(); });

    if (fileInput) {
      fileInput.addEventListener('change', function () {
        const f = fileInput.files && fileInput.files[0];
        if (!f) {
          try {
            if (fileInput._lastPreviewUrl) {
              URL.revokeObjectURL(fileInput._lastPreviewUrl);
              fileInput._lastPreviewUrl = null;
            }
          } catch (e) { /* ignore */ }
          if (previewWrap) previewWrap.style.display = 'none';
          if (previewImg) previewImg.src = '';
          return;
        }
        try {
          if (fileInput._lastPreviewUrl) URL.revokeObjectURL(fileInput._lastPreviewUrl);
        } catch (e) { /* ignore */ }
        const url = URL.createObjectURL(f);
        fileInput._lastPreviewUrl = url;
        if (previewImg) previewImg.src = url;
        if (previewWrap) previewWrap.style.display = '';
      });
    }

    if (proofDebugClose) {
      proofDebugClose.addEventListener('click', function () {
        if (proofDebug) proofDebug.style.display = 'none';
        if (proofDebugPre) proofDebugPre.textContent = '';
      });
    }

    if (proofDebugToggle) {
      proofDebugToggle.addEventListener('click', function () {
        if (!proofDebug || !proofDebugPre) return;
        if (proofDebug.style.display === 'none' || proofDebug.style.display === '') {
          if (!proofDebugPre.textContent || proofDebugPre.textContent.trim() === '') {
            proofDebugPre.textContent = 'No debug available';
          }
          proofDebug.style.display = '';
          proofDebugToggle.textContent = 'Hide debug';
        } else {
          proofDebug.style.display = 'none';
          proofDebugToggle.textContent = 'Show debug';
        }
      });
    }

    if (!form) return;

    async function handleProofSubmit(e) {
      if (e && typeof e.preventDefault === 'function') e.preventDefault();

      const orderIdEl = document.getElementById('proofOrderId');
      const fileEl = document.getElementById('proofFile');
      const submitBtn = document.getElementById('proofSubmit');
      if (!fileEl || !submitBtn || !orderIdEl) {
        showToast('Form elements missing');
        return;
      }

      let fileObj = fileEl.files && fileEl.files[0];
      // If no file selected, try prompting the user
      if (!fileObj) {
        // show debug info
        const previewUrl = (fileEl && fileEl._lastPreviewUrl) || (previewImg && previewImg.src) || '';
        if (proofDebug && proofDebugPre) {
          proofDebugPre.textContent = 'No file selected. Preview URL: ' + (previewUrl || 'none');
          proofDebug.style.display = '';
          const toggle = document.getElementById('proofDebugToggle'); if (toggle) toggle.textContent = 'Hide debug';
        }

        showToast('Please select a photo (file picker opened)');
        fileObj = await waitForUserFile(fileEl, 15000);
      }

      // Attempt to create file from preview blob if still missing and previewUrl is blob/data
      if (!fileObj) {
        const previewUrl = (fileEl && fileEl._lastPreviewUrl) || (previewImg && previewImg.src) || '';
        if (previewUrl && (previewUrl.indexOf('blob:') === 0 || previewUrl.indexOf('data:') === 0)) {
          try {
            const resp = await fetch(previewUrl);
            const blob = await resp.blob();
            try {
              fileObj = new File([blob], 'photo.jpg', { type: blob.type || 'image/jpeg' });
            } catch (err) {
              // fallback for older browsers
              blob.name = 'photo.jpg';
              fileObj = blob;
            }
          } catch (err) {
            console.warn('Failed to fetch preview blob', err);
          }
        }
      }

      if (!fileObj) {
        showToast('Please select a photo');
        return;
      }

      const origText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Uploading...';

      const formData = new FormData();
      formData.append('order_id', orderIdEl.value || '');
      formData.append('proof', fileObj);

      try {
        const resp = await fetch('api/upload_proof.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });

        const contentType = (resp.headers.get('content-type') || '').toLowerCase();
        let data = null;
        if (contentType.includes('application/json')) {
          const text = await resp.text();
          data = safeJsonParse(text);
          if (!data) {
            showToast('Server returned invalid JSON');
            if (proofDebug && proofDebugPre) {
              proofDebugPre.textContent = text;
              proofDebug.style.display = '';
            }
            return;
          }
        } else {
          const text = await resp.text().catch(() => '(unreadable response)');
          if (proofDebug && proofDebugPre) {
            proofDebugPre.textContent = text;
            proofDebug.style.display = '';
            const toggle = document.getElementById('proofDebugToggle'); if (toggle) toggle.textContent = 'Hide debug';
          }
          showToast('Server error (see debug)');
          return;
        }

        // show debug if provided
        if (data && data.debug && proofDebug && proofDebugPre) {
          proofDebugPre.textContent = JSON.stringify(data.debug, null, 2);
          proofDebug.style.display = '';
          const toggle = document.getElementById('proofDebugToggle'); if (toggle) toggle.textContent = 'Hide debug';
        }

        if (data && data.success) {
          showToast(data.message || 'Uploaded');
          const modal = document.getElementById('proofModal');
          const row = modal && modal._row;
          if (row) {
            const badge = row.querySelector('.status-badge');
            if (badge) {
              const newStatus = (data.new_status || 'completed');
              badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
              badge.classList.remove('pending', 'transit', 'delivered');
              badge.classList.add('delivered');
            }
            row.querySelectorAll('.action-btn').forEach(b => { b.disabled = true; });
          }

          try {
            if (fileEl && fileEl._lastPreviewUrl) {
              URL.revokeObjectURL(fileEl._lastPreviewUrl);
              fileEl._lastPreviewUrl = null;
            }
          } catch (e) { /* ignore */ }
          if (fileEl) fileEl.value = '';
          closeProofModal();
          updateVisibleCount();
        } else {
          // Auto-show debug panel on failure
          if (data && data.debug && proofDebug && proofDebugPre) {
            proofDebugPre.textContent = JSON.stringify(data.debug, null, 2);
            proofDebug.style.display = '';
            const toggle = document.getElementById('proofDebugToggle'); if (toggle) toggle.textContent = 'Hide debug';
          }
          const errorMsg = (data && data.message) ? data.message : 'Upload failed';
          showToast(errorMsg);
          console.error('Upload proof failed:', errorMsg, data);
        }
      } catch (err) {
        console.error('upload_proof error', err);
        showToast('Network error: ' + (err.message || 'Unknown error'));
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = origText;
      }
    }

    form.addEventListener('submit', handleProofSubmit);
    const proofSubmitBtn = document.getElementById('proofSubmit');
    if (proofSubmitBtn) proofSubmitBtn.addEventListener('click', handleProofSubmit);
  }

  // -------------------------
  // ACTION HANDLING (delegated)
  // -------------------------
  async function performAction(btn) {
    if (!btn) return;
    if (btn.dataset.actionRunning) return;
    btn.dataset.actionRunning = '1';

    const row = btn.closest('tr');
    if (!row) {
      delete btn.dataset.actionRunning;
      return;
    }

    const orderId = row.dataset.orderId || (row.querySelector('.order-id') && row.querySelector('.order-id').textContent.replace(/[^0-9]/g, '')) || '';
    if (!orderId) {
      delete btn.dataset.actionRunning;
      return;
    }

    let action = '';
    if (btn.classList.contains('start-btn')) action = 'start';
    else if (btn.classList.contains('deliver-btn')) action = 'delivered';
    else if (btn.classList.contains('contact-btn')) action = 'contact';

    // deliver requires proof (open modal)
    if (action === 'delivered') {
      openProofModal(orderId, row, btn);
      delete btn.dataset.actionRunning;
      return;
    }

    // contact can be handled client-side (tel:) if phone present
    if (action === 'contact') {
      const phone = row.dataset.customerPhone || '';
      if (phone) {
        window.location.href = 'tel:' + phone;
        showToast('Opening dialer...');
        delete btn.dataset.actionRunning;
        return;
      }
    }

    const form = new URLSearchParams();
    form.append('order_id', orderId);
    form.append('action', action);

    btn.disabled = true;
    try {
      const resp = await fetch('api/update_order_status.php', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: form,
        credentials: 'same-origin'
      });

      let data = null;
      try {
        data = await resp.json();
      } catch (e) {
        // not JSON or parse failed
        const txt = await resp.text().catch(() => '');
        console.error('update_order_status parse error', e, txt);
        showToast('Server error');
      }

      if (data && data.success) {
        showToast(data.message || 'Updated');

        // update UI: badge
        if (row) {
          const badge = row.querySelector('.status-badge');
          if (badge && data.new_status) {
            const newStatus = (data.new_status || '').toLowerCase();
            const clsMap = { 'pending': 'pending', 'delivering': 'transit', 'completed': 'delivered' };
            const newClass = clsMap[newStatus] || '';
            badge.textContent = data.new_status.charAt(0).toUpperCase() + data.new_status.slice(1);
            badge.classList.remove('pending', 'transit', 'delivered');
            if (newClass) badge.classList.add(newClass);
          }
        }

        // If starting, swap Start -> Done/Deliver button
        if (action === 'start' && row) {
          const startBtn = row.querySelector('.start-btn');
          if (startBtn) startBtn.remove();

          const actionContainer = row.querySelector('.action-buttons');
          if (actionContainer) {
            const doneBtn = document.createElement('button');
            doneBtn.className = 'action-btn deliver-btn';
            doneBtn.textContent = 'Done';
            // Insert before contact button if present, otherwise append
            const contactBtn = actionContainer.querySelector('.contact-btn');
            if (contactBtn) actionContainer.insertBefore(doneBtn, contactBtn);
            else actionContainer.appendChild(doneBtn);
            // no need to add explicit listener: delegated handler will catch it
          }
        }

        if (action === 'delivered' && row) {
          row.querySelectorAll('.action-btn').forEach(b => { b.disabled = true; });
        }
      } else {
        showToast((data && data.message) ? data.message : 'Action failed');
      }
    } catch (err) {
      console.error('performAction network error', err);
      showToast('Network error');
    } finally {
      btn.disabled = false;
      delete btn.dataset.actionRunning;
      updateVisibleCount();
    }
  }

  // -------------------------
  // SVG Animations
  // -------------------------
  function triggerCheckAnimation(svg) {
    if (!svg) return;
    const path = svg.querySelector('path');
    if (!path) return;
    // prepare for CSS-less stroke draw animation
    const length = path.getTotalLength ? path.getTotalLength() : 24;
    path.style.strokeDasharray = length;
    path.style.strokeDashoffset = length;
    // force reflow
    void svg.offsetWidth;
    path.style.transition = 'stroke-dashoffset 380ms ease-in-out';
    path.style.strokeDashoffset = '0';
    setTimeout(() => {
      // cleanup style so future animations can run
      path.style.transition = '';
      path.style.strokeDashoffset = '';
      path.style.strokeDasharray = '';
    }, 500);
  }

  function initSvgAnimations() {
    // CSS animations are optional; only add lightweight behaviors here.
    // We'll trigger check animation for buttons that include an svg.icon-check on click.
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('.action-btn');
      if (!btn) return;
      // If deliver button, modal handles the flow
      if (btn.classList.contains('deliver-btn')) return;
      const svg = btn.querySelector('svg.icon-check');
      if (svg) triggerCheckAnimation(svg);
    }, true);
  }

  // -------------------------
  // KEYBOARD NAVIGATION
  // -------------------------
  function initKeyboardNav() {
    const table = document.querySelector('.deliveries-table tbody');
    if (!table) return;
    const rows = Array.from(table.querySelectorAll('tr'));
    rows.forEach(function (row, idx) {
      row.setAttribute('tabindex', '0');
      row.setAttribute('role', 'row');
      row.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown' && rows[idx + 1]) {
          e.preventDefault();
          rows[idx + 1].focus();
        } else if (e.key === 'ArrowUp' && rows[idx - 1]) {
          e.preventDefault();
          rows[idx - 1].focus();
        } else if (e.key === 'Enter') {
          const firstBtn = row.querySelector('.action-btn');
          if (firstBtn) firstBtn.focus();
        }
      });
    });
  }

  // -------------------------
  // Delegated click handling for action buttons (single place)
  // -------------------------
  function initActionDelegation() {
    document.addEventListener('click', function (evt) {
      const btn = evt.target.closest('.action-btn');
      if (!btn) return;

      // If deliver button, open proof modal (performAction will also guard)
      if (btn.classList.contains('deliver-btn')) {
        const row = btn.closest('tr');
        const orderId = row && (row.dataset.orderId || (row.querySelector('.order-id') && row.querySelector('.order-id').textContent.replace(/[^0-9]/g, '')));
        openProofModal(orderId, row, btn);
        return;
      }

      // otherwise perform action
      performAction(btn).catch(err => {
        console.error('Action failed', err);
        showToast('Action failed');
      });
    }, false);
  }

  // -------------------------
  // Initialization
  // -------------------------
  function init() {
    // Chart
    try {
      if (typeof Chart !== 'undefined') initDeliveriesChart();
      else console.info('Chart.js not available; skipping chart init.');
    } catch (e) {
      console.error('Chart init failed', e);
    }

    initSearchFilter();
    initStatusFilter();
    initSidebarToggle();
    initSvgAnimations();
    initProofModal();
    initActionDelegation();
    initKeyboardNav();

    // initial visible counts
    updateVisibleCount();
  }

  // Run init when DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();