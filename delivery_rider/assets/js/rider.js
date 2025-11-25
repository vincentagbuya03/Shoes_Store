/**
 * Delivery Rider Dashboard - Vanilla JavaScript
 * Fully functional dashboard with delivery management, status updates, and data persistence
 */

(function() {
  'use strict';

  // ============================================
  // DATA MANAGEMENT
  // ============================================
  
  // Sample delivery data - stored in localStorage for persistence
  const STORAGE_KEY = 'riderDashboardDeliveries';
  
  const defaultDeliveries = [
    { id: 'ORD-7823', customer: 'Maria Santos', address: '123 Rizal St, Makati', phone: '+63 917 123 4567', status: 'pending', time: '10:30 AM' },
    { id: 'ORD-7824', customer: 'Jose Garcia', address: '456 EDSA, Quezon City', phone: '+63 918 234 5678', status: 'transit', time: '11:15 AM' },
    { id: 'ORD-7825', customer: 'Ana Reyes', address: '789 Ayala Ave, BGC', phone: '+63 919 345 6789', status: 'transit', time: '11:45 AM' },
    { id: 'ORD-7826', customer: 'Pedro Cruz', address: '321 Shaw Blvd, Mandaluyong', phone: '+63 920 456 7890', status: 'pending', time: '12:00 PM' },
    { id: 'ORD-7827', customer: 'Carmen Luna', address: '654 Jupiter St, Makati', phone: '+63 921 567 8901', status: 'pending', time: '12:30 PM' },
    { id: 'ORD-7820', customer: 'Roberto Tan', address: '987 Ortigas Ave, Pasig', phone: '+63 922 678 9012', status: 'delivered', time: '9:15 AM' },
    { id: 'ORD-7819', customer: 'Elena Bautista', address: '159 Taft Ave, Manila', phone: '+63 923 789 0123', status: 'delivered', time: '8:45 AM' },
    { id: 'ORD-7818', customer: 'Miguel Ramos', address: '753 Katipunan Ave, QC', phone: '+63 924 890 1234', status: 'delivered', time: '8:00 AM' }
  ];

  /**
   * Get deliveries from localStorage or return defaults
   */
  function getDeliveries() {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      return stored ? JSON.parse(stored) : [...defaultDeliveries];
    } catch (e) {
      return [...defaultDeliveries];
    }
  }

  /**
   * Save deliveries to localStorage
   */
  function saveDeliveries(deliveries) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(deliveries));
    } catch (e) {
      console.warn('Could not save to localStorage');
    }
  }

  /**
   * Reset deliveries to default state
   */
  function resetDeliveries() {
    localStorage.removeItem(STORAGE_KEY);
    location.reload();
  }

  // ============================================
  // CHART INITIALIZATION
  // ============================================
  
  let deliveriesChart = null;

  /**
   * Initialize the deliveries chart with Chart.js
   */
  function initDeliveriesChart() {
    const ctx = document.getElementById('deliveriesChart');
    if (!ctx) return;

    // Sample 7-day data
    const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const deliveriesData = [12, 19, 8, 15, 22, 18, 25];

    const chartConfig = {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Deliveries',
          data: deliveriesData,
          borderColor: '#6366f1',
          backgroundColor: 'rgba(99, 102, 241, 0.1)',
          borderWidth: 3,
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#6366f1',
          pointBorderColor: '#ffffff',
          pointBorderWidth: 2,
          pointRadius: 5,
          pointHoverRadius: 8,
          pointHoverBackgroundColor: '#6366f1',
          pointHoverBorderColor: '#ffffff',
          pointHoverBorderWidth: 3
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            enabled: true,
            backgroundColor: 'rgba(30, 41, 59, 0.95)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderColor: 'rgba(99, 102, 241, 0.3)',
            borderWidth: 1,
            cornerRadius: 8,
            padding: 12,
            displayColors: false,
            callbacks: {
              title: function(context) {
                return context[0].label;
              },
              label: function(context) {
                return context.parsed.y + ' deliveries';
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false
            },
            ticks: {
              color: '#64748b',
              font: {
                size: 12,
                weight: '500'
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: 'rgba(226, 232, 240, 0.5)',
              drawBorder: false
            },
            ticks: {
              color: '#64748b',
              font: {
                size: 12
              },
              stepSize: 5
            }
          }
        }
      }
    };

    deliveriesChart = new Chart(ctx, chartConfig);
  }

  // ============================================
  // SEARCH FILTER FUNCTIONALITY
  // ============================================

  /**
   * Apply combined search and status filters to the deliveries table
   * Both filters work together: rows must match search term AND status filter
   */
  function applyFilters() {
    const searchInput = document.getElementById('deliverySearch');
    const filterSelect = document.getElementById('statusFilter');
    const tableBody = document.getElementById('deliveriesTableBody');
    
    if (!tableBody) return;

    const searchTerm = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const filterValue = filterSelect ? filterSelect.value.toLowerCase() : 'all';
    const rows = tableBody.querySelectorAll('tr');

    rows.forEach(function(row) {
      const text = row.textContent.toLowerCase();
      const statusBadge = row.querySelector('.status-badge');
      
      // Check if row matches search term
      const matchesSearch = searchTerm === '' || text.includes(searchTerm);
      
      // Check if row matches status filter
      let matchesStatus = true;
      if (filterValue !== 'all' && statusBadge) {
        matchesStatus = statusBadge.classList.contains(filterValue);
      }
      
      // Row is visible only if it matches both filters
      const isVisible = matchesSearch && matchesStatus;
      row.style.display = isVisible ? '' : 'none';
      
      if (isVisible) {
        row.style.opacity = '1';
      }
    });

    // Update visible count
    updateVisibleCount();
  }

  /**
   * Initialize search filter for deliveries table
   */
  function initSearchFilter() {
    const searchInput = document.getElementById('deliverySearch');
    
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
      applyFilters();
    });
  }

  /**
   * Initialize status filter dropdown
   */
  function initStatusFilter() {
    const filterSelect = document.getElementById('statusFilter');
    
    if (!filterSelect) return;

    filterSelect.addEventListener('change', function() {
      applyFilters();
    });
  }

  /**
   * Update the visible deliveries count
   */
  function updateVisibleCount() {
    const tableBody = document.getElementById('deliveriesTableBody');
    const countElement = document.querySelector('.deliveries-count');
    
    if (!tableBody || !countElement) return;

    const visibleRows = tableBody.querySelectorAll('tr:not([style*="display: none"])');
    const totalRows = tableBody.querySelectorAll('tr');
    
    countElement.textContent = visibleRows.length + ' of ' + totalRows.length + ' deliveries';
  }

  // ============================================
  // DELIVERY ACTIONS - FUNCTIONAL
  // ============================================

  /**
   * Start a delivery - change status from Pending to In Transit
   */
  function startDelivery(orderId, row) {
    const deliveries = getDeliveries();
    const delivery = deliveries.find(d => d.id === orderId);
    
    if (!delivery || delivery.status !== 'pending') {
      showToast('Cannot start this delivery', 'error');
      return;
    }

    // Update status
    delivery.status = 'transit';
    saveDeliveries(deliveries);

    // Update UI
    const statusBadge = row.querySelector('.status-badge');
    if (statusBadge) {
      statusBadge.className = 'status-badge transit';
      statusBadge.textContent = 'In Transit';
    }

    // Update action buttons
    updateRowActions(row, 'transit');
    
    // Update summary cards
    updateSummaryCards();

    // Animate row
    row.style.background = 'rgba(99, 102, 241, 0.12)';
    setTimeout(() => { row.style.background = ''; }, 800);

    showToast('🚴 Delivery started for ' + orderId);
  }

  /**
   * Mark delivery as delivered - change status from In Transit to Delivered
   */
  function markDelivered(orderId, row) {
    const deliveries = getDeliveries();
    const delivery = deliveries.find(d => d.id === orderId);
    
    if (!delivery || delivery.status !== 'transit') {
      showToast('Cannot mark this as delivered', 'error');
      return;
    }

    // Update status
    delivery.status = 'delivered';
    saveDeliveries(deliveries);

    // Update UI
    const statusBadge = row.querySelector('.status-badge');
    if (statusBadge) {
      statusBadge.className = 'status-badge delivered';
      statusBadge.textContent = 'Delivered';
    }

    // Update action buttons
    updateRowActions(row, 'delivered');
    
    // Update summary cards
    updateSummaryCards();

    // Animate row
    row.style.background = 'rgba(20, 184, 166, 0.12)';
    setTimeout(() => { row.style.background = ''; }, 800);

    showToast('✅ Delivery completed for ' + orderId);
  }

  /**
   * Contact customer - show phone number and simulate call
   */
  function contactCustomer(orderId, row) {
    const deliveries = getDeliveries();
    const delivery = deliveries.find(d => d.id === orderId);
    
    if (!delivery) {
      showToast('Customer not found', 'error');
      return;
    }

    // Show contact modal
    showContactModal(delivery);
  }

  /**
   * Update row action buttons based on new status
   */
  function updateRowActions(row, newStatus) {
    const actionsCell = row.querySelector('.action-buttons');
    if (!actionsCell) return;

    const orderId = row.querySelector('.order-id').textContent.replace('#', '');

    if (newStatus === 'transit') {
      // Replace start button with deliver button
      actionsCell.innerHTML = `
        <button class="action-btn deliver-btn" aria-label="Mark as delivered" title="Mark Delivered" data-order="${orderId}">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-check" aria-hidden="true">
            <path d="M20 6L9 17l-5-5"/>
          </svg>
        </button>
        <button class="action-btn contact-btn" aria-label="Contact customer" title="Contact Customer" data-order="${orderId}">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
          </svg>
        </button>
      `;
    } else if (newStatus === 'delivered') {
      // Only show contact button for delivered items
      actionsCell.innerHTML = `
        <button class="action-btn contact-btn" aria-label="Contact customer" title="Contact Customer" data-order="${orderId}">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
          </svg>
        </button>
      `;
    }

    // Re-attach event listeners to new buttons
    attachActionListeners(actionsCell);
  }

  /**
   * Update summary cards with current counts
   */
  function updateSummaryCards() {
    const deliveries = getDeliveries();
    
    const pendingCount = deliveries.filter(d => d.status === 'pending').length;
    const transitCount = deliveries.filter(d => d.status === 'transit').length;
    const deliveredCount = deliveries.filter(d => d.status === 'delivered').length;

    // Update summary card values
    const cards = document.querySelectorAll('.summary-card');
    if (cards[0]) {
      cards[0].querySelector('.summary-card-value').textContent = pendingCount;
    }
    if (cards[1]) {
      cards[1].querySelector('.summary-card-value').textContent = transitCount;
    }
    if (cards[2]) {
      cards[2].querySelector('.summary-card-value').textContent = deliveredCount;
    }

    // Update sidebar active deliveries stat
    const activeDeliveriesStat = document.querySelector('.quick-stats .stat-item:first-child .stat-value');
    if (activeDeliveriesStat) {
      activeDeliveriesStat.textContent = pendingCount + transitCount;
    }
  }

  /**
   * Show contact modal with customer details
   */
  function showContactModal(delivery) {
    // Remove existing modal if any
    const existingModal = document.getElementById('contactModal');
    if (existingModal) {
      existingModal.remove();
    }

    const modal = document.createElement('div');
    modal.id = 'contactModal';
    modal.className = 'modal-overlay';
    modal.innerHTML = `
      <div class="modal-content">
        <div class="modal-header">
          <h3>Contact Customer</h3>
          <button class="modal-close" aria-label="Close modal">&times;</button>
        </div>
        <div class="modal-body">
          <div class="contact-info-item">
            <strong>Order:</strong> #${delivery.id}
          </div>
          <div class="contact-info-item">
            <strong>Customer:</strong> ${delivery.customer}
          </div>
          <div class="contact-info-item">
            <strong>Address:</strong> ${delivery.address}
          </div>
          <div class="contact-info-item phone-number">
            <strong>Phone:</strong> 
            <a href="tel:${delivery.phone.replace(/\s/g, '')}" class="phone-link">${delivery.phone}</a>
          </div>
        </div>
        <div class="modal-actions">
          <a href="tel:${delivery.phone.replace(/\s/g, '')}" class="btn-call">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            Call Customer
          </a>
          <button class="btn-sms" onclick="window.open('sms:${delivery.phone.replace(/\s/g, '')}')">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            Send SMS
          </button>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    // Add modal styles if not already present
    addModalStyles();

    // Show modal with animation
    requestAnimationFrame(() => {
      modal.classList.add('active');
    });

    // Close handlers
    modal.querySelector('.modal-close').addEventListener('click', () => closeModal(modal));
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal(modal);
    });
    document.addEventListener('keydown', function closeOnEscape(e) {
      if (e.key === 'Escape') {
        closeModal(modal);
        document.removeEventListener('keydown', closeOnEscape);
      }
    });
  }

  function closeModal(modal) {
    modal.classList.remove('active');
    setTimeout(() => modal.remove(), 300);
  }

  function addModalStyles() {
    if (document.getElementById('modalStyles')) return;

    const styles = document.createElement('style');
    styles.id = 'modalStyles';
    styles.textContent = `
      .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
      }
      .modal-overlay.active {
        opacity: 1;
        visibility: visible;
      }
      .modal-content {
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 400px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        transform: scale(0.9) translateY(20px);
        transition: transform 0.3s ease;
      }
      .modal-overlay.active .modal-content {
        transform: scale(1) translateY(0);
      }
      .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
      }
      .modal-header h3 {
        margin: 0;
        font-size: 1.1rem;
        color: #1e293b;
      }
      .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #64748b;
        padding: 0;
        line-height: 1;
      }
      .modal-close:hover {
        color: #1e293b;
      }
      .modal-body {
        padding: 1.5rem;
      }
      .contact-info-item {
        margin-bottom: 0.75rem;
        color: #475569;
        font-size: 0.95rem;
      }
      .contact-info-item strong {
        color: #1e293b;
        display: inline-block;
        min-width: 80px;
      }
      .phone-link {
        color: #6366f1;
        text-decoration: none;
        font-weight: 600;
      }
      .phone-link:hover {
        text-decoration: underline;
      }
      .modal-actions {
        display: flex;
        gap: 0.75rem;
        padding: 1rem 1.5rem 1.5rem;
      }
      .btn-call, .btn-sms {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
      }
      .btn-call {
        background: #6366f1;
        color: white;
        border: none;
      }
      .btn-call:hover {
        background: #4f46e5;
      }
      .btn-sms {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
      }
      .btn-sms:hover {
        background: #e2e8f0;
      }
    `;
    document.head.appendChild(styles);
  }

  /**
   * Attach event listeners to action buttons
   */
  function attachActionListeners(container) {
    const buttons = container ? container.querySelectorAll('.action-btn') : document.querySelectorAll('.action-btn');
    
    buttons.forEach(btn => {
      // Remove existing listeners by cloning
      const newBtn = btn.cloneNode(true);
      btn.parentNode.replaceChild(newBtn, btn);
      
      newBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const row = newBtn.closest('tr');
        const orderId = row.querySelector('.order-id').textContent.replace('#', '');
        
        if (newBtn.classList.contains('start-btn')) {
          startDelivery(orderId, row);
        } else if (newBtn.classList.contains('deliver-btn')) {
          markDelivered(orderId, row);
        } else if (newBtn.classList.contains('contact-btn')) {
          contactCustomer(orderId, row);
        }

        // SVG animation
        const svg = newBtn.querySelector('svg');
        if (svg) {
          svg.classList.add('animated');
          setTimeout(() => svg.classList.remove('animated'), 500);
        }
      });
    });
  }

  // ============================================
  // NAVIGATION FUNCTIONALITY
  // ============================================

  function initNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active from all
        navLinks.forEach(l => l.classList.remove('active'));
        navLinks.forEach(l => l.removeAttribute('aria-current'));
        
        // Add active to clicked
        this.classList.add('active');
        this.setAttribute('aria-current', 'page');

        // Get nav item text
        const navText = this.textContent.trim();
        
        // Show appropriate content based on nav
        handleNavigation(navText);
      });
    });
  }

  function handleNavigation(navItem) {
    switch(navItem) {
      case 'Dashboard':
        // Already on dashboard - show all sections
        document.querySelector('.summary-cards').style.display = '';
        document.querySelector('.chart-section').style.display = '';
        document.querySelector('.deliveries-section').style.display = '';
        showToast('📊 Dashboard view');
        break;
      case 'My Deliveries':
        // Focus on deliveries table
        document.querySelector('.deliveries-section').scrollIntoView({ behavior: 'smooth' });
        showToast('📦 Viewing your deliveries');
        break;
      case 'Route Map':
        showToast('🗺️ Route map coming soon');
        break;
      case 'History':
        // Filter to show only delivered
        document.getElementById('statusFilter').value = 'delivered';
        applyFilters();
        showToast('📜 Viewing delivery history');
        break;
      case 'Earnings':
        showEarningsModal();
        break;
      case 'Settings':
        showSettingsModal();
        break;
    }
  }

  function showEarningsModal() {
    const deliveries = getDeliveries();
    const deliveredCount = deliveries.filter(d => d.status === 'delivered').length;
    const earningsPerDelivery = 50; // Sample earnings in PHP
    const totalEarnings = deliveredCount * earningsPerDelivery;

    const existingModal = document.getElementById('earningsModal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'earningsModal';
    modal.className = 'modal-overlay';
    modal.innerHTML = `
      <div class="modal-content">
        <div class="modal-header">
          <h3>💰 Today's Earnings</h3>
          <button class="modal-close" aria-label="Close modal">&times;</button>
        </div>
        <div class="modal-body" style="text-align: center; padding: 2rem;">
          <div style="font-size: 3rem; font-weight: 700; color: #10b981; margin-bottom: 0.5rem;">
            ₱${totalEarnings.toLocaleString()}
          </div>
          <div style="color: #64748b; margin-bottom: 1.5rem;">
            ${deliveredCount} deliveries × ₱${earningsPerDelivery} each
          </div>
          <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; text-align: left;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
              <span>Base Pay:</span>
              <span>₱${(deliveredCount * 40).toLocaleString()}</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
              <span>Incentive Bonus:</span>
              <span>₱${(deliveredCount * 10).toLocaleString()}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: 600; border-top: 1px solid #e2e8f0; padding-top: 0.5rem; margin-top: 0.5rem;">
              <span>Total:</span>
              <span style="color: #10b981;">₱${totalEarnings.toLocaleString()}</span>
            </div>
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(modal);
    addModalStyles();
    requestAnimationFrame(() => modal.classList.add('active'));

    modal.querySelector('.modal-close').addEventListener('click', () => closeModal(modal));
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal(modal);
    });
  }

  function showSettingsModal() {
    const existingModal = document.getElementById('settingsModal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'settingsModal';
    modal.className = 'modal-overlay';
    modal.innerHTML = `
      <div class="modal-content">
        <div class="modal-header">
          <h3>⚙️ Settings</h3>
          <button class="modal-close" aria-label="Close modal">&times;</button>
        </div>
        <div class="modal-body">
          <div style="margin-bottom: 1rem;">
            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
              <input type="checkbox" id="notificationsToggle" checked style="width: 18px; height: 18px;">
              <span>Enable Notifications</span>
            </label>
          </div>
          <div style="margin-bottom: 1rem;">
            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
              <input type="checkbox" id="soundToggle" checked style="width: 18px; height: 18px;">
              <span>Sound Alerts</span>
            </label>
          </div>
          <div style="margin-bottom: 1.5rem;">
            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer;">
              <input type="checkbox" id="darkModeToggle" style="width: 18px; height: 18px;">
              <span>Dark Mode (Coming Soon)</span>
            </label>
          </div>
          <div style="border-top: 1px solid #e2e8f0; padding-top: 1rem;">
            <button onclick="resetDeliveries()" class="btn-reset" style="
              width: 100%;
              padding: 0.75rem;
              background: #fef2f2;
              color: #dc2626;
              border: 1px solid #fecaca;
              border-radius: 8px;
              cursor: pointer;
              font-weight: 500;
            ">
              🔄 Reset Demo Data
            </button>
            <p style="font-size: 0.8rem; color: #64748b; margin-top: 0.5rem; text-align: center;">
              This will restore all deliveries to their original state
            </p>
          </div>
        </div>
      </div>
    `;

    document.body.appendChild(modal);
    addModalStyles();
    requestAnimationFrame(() => modal.classList.add('active'));

    modal.querySelector('.modal-close').addEventListener('click', () => closeModal(modal));
    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal(modal);
    });

    // Make resetDeliveries available globally for the onclick
    window.resetDeliveries = resetDeliveries;
  }

  // ============================================
  // SIDEBAR TOGGLE (Mobile)
  // ============================================

  /**
   * Initialize sidebar toggle for mobile screens
   */
  function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (!toggleBtn || !sidebar) return;

    // Toggle sidebar
    toggleBtn.addEventListener('click', function() {
      sidebar.classList.toggle('open');
      if (overlay) {
        overlay.classList.toggle('active');
      }
      
      // Update aria-expanded
      const isOpen = sidebar.classList.contains('open');
      toggleBtn.setAttribute('aria-expanded', isOpen);
    });

    // Close sidebar when clicking overlay
    if (overlay) {
      overlay.addEventListener('click', function() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        toggleBtn.setAttribute('aria-expanded', 'false');
      });
    }

    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        if (overlay) {
          overlay.classList.remove('active');
        }
        toggleBtn.setAttribute('aria-expanded', 'false');
      }
    });

    // Close sidebar when window is resized to desktop
    window.addEventListener('resize', function() {
      if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        if (overlay) {
          overlay.classList.remove('active');
        }
        toggleBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // ============================================
  // TOAST NOTIFICATION
  // ============================================

  /**
   * Show a simple toast notification
   * @param {string} message - The message to display
   * @param {string} type - Type of toast: 'success' or 'error'
   */
  function showToast(message, type) {
    // Check if toast container exists, create if not
    let toastContainer = document.getElementById('toastContainer');
    
    if (!toastContainer) {
      toastContainer = document.createElement('div');
      toastContainer.id = 'toastContainer';
      toastContainer.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 1000;';
      document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toast = document.createElement('div');
    toast.textContent = message;
    const bgColor = type === 'error' ? '#ef4444' : '#1e293b';
    toast.style.cssText = [
      'background: ' + bgColor,
      'color: #ffffff',
      'padding: 12px 20px',
      'border-radius: 8px',
      'margin-top: 8px',
      'font-size: 14px',
      'box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15)',
      'opacity: 0',
      'transform: translateX(20px)',
      'transition: all 0.3s ease'
    ].join(';');

    toastContainer.appendChild(toast);

    // Animate in
    requestAnimationFrame(function() {
      toast.style.opacity = '1';
      toast.style.transform = 'translateX(0)';
    });

    // Remove after delay
    setTimeout(function() {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(20px)';
      
      setTimeout(function() {
        if (toast.parentNode) {
          toast.parentNode.removeChild(toast);
        }
      }, 300);
    }, 2500);
  }

  // ============================================
  // KEYBOARD NAVIGATION
  // ============================================

  /**
   * Initialize keyboard navigation for accessibility
   */
  function initKeyboardNav() {
    // Make table rows focusable and handle keyboard interaction
    const tableRows = document.querySelectorAll('.deliveries-table tbody tr');
    
    tableRows.forEach(function(row, index) {
      row.setAttribute('tabindex', '0');
      row.setAttribute('role', 'row');
      
      row.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown' && tableRows[index + 1]) {
          e.preventDefault();
          tableRows[index + 1].focus();
        } else if (e.key === 'ArrowUp' && tableRows[index - 1]) {
          e.preventDefault();
          tableRows[index - 1].focus();
        } else if (e.key === 'Enter') {
          // Focus first action button in row
          const firstBtn = row.querySelector('.action-btn');
          if (firstBtn) {
            firstBtn.focus();
          }
        }
      });
    });
  }

  // ============================================
  // INITIALIZATION
  // ============================================

  /**
   * Initialize all dashboard functionality
   */
  function init() {
    // Wait for Chart.js to be available
    if (typeof Chart !== 'undefined') {
      initDeliveriesChart();
    } else {
      console.warn('Chart.js not loaded. Chart will not be displayed.');
    }

    initSearchFilter();
    initStatusFilter();
    initSidebarToggle();
    initNavigation();
    initKeyboardNav();
    
    // Attach action button listeners
    attachActionListeners();
    
    // Update summary cards with current data
    updateSummaryCards();
    
    // Initialize visible count
    updateVisibleCount();
  }

  // Run initialization when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
