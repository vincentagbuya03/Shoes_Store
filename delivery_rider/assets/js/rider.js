/**
 * Delivery Rider Dashboard - Vanilla JavaScript
 * Handles Chart.js integration, search filtering, sidebar toggle, and SVG animations
 */

(function() {
  'use strict';

  // ============================================
  // CHART INITIALIZATION
  // ============================================
  
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

    new Chart(ctx, chartConfig);
  }

  // ============================================
  // SEARCH FILTER FUNCTIONALITY
  // ============================================

  /**
   * Initialize search filter for deliveries table
   */
  function initSearchFilter() {
    const searchInput = document.getElementById('deliverySearch');
    const tableBody = document.getElementById('deliveriesTableBody');
    
    if (!searchInput || !tableBody) return;

    searchInput.addEventListener('input', function(e) {
      const searchTerm = e.target.value.toLowerCase().trim();
      const rows = tableBody.querySelectorAll('tr');

      rows.forEach(function(row) {
        const text = row.textContent.toLowerCase();
        const isVisible = text.includes(searchTerm);
        
        row.style.display = isVisible ? '' : 'none';
        
        // Add/remove animation class
        if (isVisible) {
          row.style.opacity = '1';
        }
      });

      // Update visible count
      updateVisibleCount();
    });
  }

  /**
   * Initialize status filter dropdown
   */
  function initStatusFilter() {
    const filterSelect = document.getElementById('statusFilter');
    const tableBody = document.getElementById('deliveriesTableBody');
    
    if (!filterSelect || !tableBody) return;

    filterSelect.addEventListener('change', function(e) {
      const filterValue = e.target.value.toLowerCase();
      const rows = tableBody.querySelectorAll('tr');

      rows.forEach(function(row) {
        if (filterValue === 'all') {
          row.style.display = '';
        } else {
          const statusBadge = row.querySelector('.status-badge');
          if (statusBadge) {
            const status = statusBadge.classList.contains(filterValue);
            row.style.display = status ? '' : 'none';
          }
        }
      });

      // Update visible count
      updateVisibleCount();
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
      }
    });
  }

  // ============================================
  // SVG ANIMATION TRIGGERS
  // ============================================

  /**
   * Initialize SVG animation triggers on action buttons
   */
  function initSvgAnimations() {
    const actionButtons = document.querySelectorAll('.action-btn');

    actionButtons.forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        const svg = btn.querySelector('svg');
        if (!svg) return;

        // Add animated class for CSS animations
        svg.classList.add('animated');

        // For checkmark animation - reset and trigger
        if (svg.classList.contains('icon-check')) {
          triggerCheckAnimation(svg);
        }

        // Remove animated class after animation completes
        setTimeout(function() {
          svg.classList.remove('animated');
        }, 500);

        // Show feedback message
        showActionFeedback(btn);
      });
    });
  }

  /**
   * Trigger checkmark draw animation
   * @param {SVGElement} svg - The checkmark SVG element
   */
  function triggerCheckAnimation(svg) {
    const path = svg.querySelector('path');
    if (!path) return;

    // Reset the animation
    path.style.strokeDashoffset = '24';
    
    // Trigger reflow
    void svg.offsetWidth;
    
    // Animate
    path.style.strokeDashoffset = '0';
  }

  /**
   * Show visual feedback when action button is clicked
   * @param {HTMLElement} btn - The clicked button
   */
  function showActionFeedback(btn) {
    const row = btn.closest('tr');
    if (!row) return;

    // Add highlight effect to row
    row.style.background = 'rgba(99, 102, 241, 0.08)';
    
    setTimeout(function() {
      row.style.background = '';
    }, 600);

    // Get action type and show toast notification
    let actionType = 'Action completed';
    if (btn.classList.contains('start-btn')) {
      actionType = 'Delivery started';
    } else if (btn.classList.contains('deliver-btn')) {
      actionType = 'Marked as delivered';
    } else if (btn.classList.contains('contact-btn')) {
      actionType = 'Contacting customer...';
    }

    showToast(actionType);
  }

  // ============================================
  // TOAST NOTIFICATION
  // ============================================

  /**
   * Show a simple toast notification
   * @param {string} message - The message to display
   */
  function showToast(message) {
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
    toast.style.cssText = [
      'background: #1e293b',
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
    initSvgAnimations();
    initKeyboardNav();
    
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
