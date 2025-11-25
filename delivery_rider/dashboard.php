<?php
/* 
 * Delivery Rider Dashboard
 * Session/auth placeholder - implement authentication as needed
 */
// session_start();
// if (!isset($_SESSION['rider_id'])) {
//     header('Location: /login.php');
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Delivery Rider Dashboard - Manage your deliveries efficiently">
    <title>Rider Dashboard | ShoeTakels</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../upload/picture/logo.png">
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Dashboard CSS -->
    <link rel="stylesheet" href="assets/css/rider.css">
</head>
<body>
    <!-- Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <!-- Sidebar Overlay (Mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar" role="navigation" aria-label="Main navigation">
            <!-- Rider Profile Header -->
            <header class="sidebar-header">
                <div class="rider-avatar" aria-hidden="true">JD</div>
                <div class="rider-info">
                    <h2>Juan Dela Cruz</h2>
                    <p>Rider #1042</p>
                    <div class="rider-status">
                        <span class="status-dot" aria-hidden="true"></span>
                        <span>Online</span>
                    </div>
                </div>
            </header>

            <!-- Quick Stats -->
            <section class="quick-stats" aria-label="Quick statistics">
                <div class="stat-item">
                    <span class="stat-label">Active Deliveries</span>
                    <span class="stat-value highlight">5</span>
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

            <!-- Navigation -->
            <nav class="sidebar-nav">
                <ul class="nav-list" role="menubar">
                    <li class="nav-item" role="none">
                        <a href="#" class="nav-link active" role="menuitem" aria-current="page">
                            <!-- Dashboard Icon -->
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
                        <a href="#" class="nav-link" role="menuitem">
                            <!-- Package Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/>
                                <path d="m3.3 7 8.7 5 8.7-5"/>
                                <path d="M12 22V12"/>
                            </svg>
                            My Deliveries
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="#" class="nav-link" role="menuitem">
                            <!-- Map Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/>
                                <line x1="9" y1="3" x2="9" y2="18"/>
                                <line x1="15" y1="6" x2="15" y2="21"/>
                            </svg>
                            Route Map
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="#" class="nav-link" role="menuitem">
                            <!-- History Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            History
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="#" class="nav-link" role="menuitem">
                            <!-- Earnings Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="12" y1="1" x2="12" y2="23"/>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                            </svg>
                            Earnings
                        </a>
                    </li>
                    <li class="nav-item" role="none">
                        <a href="#" class="nav-link" role="menuitem">
                            <!-- Settings Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                            Settings
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content" role="main">
            <!-- Page Header -->
            <header class="page-header">
                <h1>Dashboard</h1>
                <p>Welcome back, Juan! Here's your delivery overview.</p>
            </header>

            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <div class="search-box">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <input 
                        type="search" 
                        id="deliverySearch" 
                        placeholder="Search deliveries..." 
                        aria-label="Search deliveries"
                    >
                </div>
                <div class="filter-dropdown">
                    <select id="statusFilter" aria-label="Filter by status">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="transit">In Transit</option>
                        <option value="delivered">Delivered</option>
                    </select>
                </div>
            </div>

            <!-- Summary Cards -->
            <section class="summary-cards" aria-label="Delivery summary">
                <article class="summary-card">
                    <div class="summary-card-header">
                        <div class="summary-card-icon pending" aria-hidden="true">
                            <!-- Clock Icon -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <span class="summary-card-badge">Today</span>
                    </div>
                    <div class="summary-card-value">3</div>
                    <div class="summary-card-label">Pending Pickups</div>
                </article>

                <article class="summary-card">
                    <div class="summary-card-header">
                        <div class="summary-card-icon transit" aria-hidden="true">
                            <!-- Bike Icon inline -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-bike">
                                <path d="M12 19V13l-4-2"/>
                                <path d="M8 11l4 2 5-3"/>
                                <path d="M17 8v3"/>
                                <circle class="wheel" cx="5" cy="19" r="3"/>
                                <circle class="wheel" cx="19" cy="19" r="3"/>
                                <path d="M17 8h2"/>
                            </svg>
                        </div>
                        <span class="summary-card-badge">Active</span>
                    </div>
                    <div class="summary-card-value">2</div>
                    <div class="summary-card-label">In Transit</div>
                </article>

                <article class="summary-card">
                    <div class="summary-card-header">
                        <div class="summary-card-icon delivered" aria-hidden="true">
                            <!-- Check Icon inline -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-check">
                                <path d="M20 6L9 17l-5-5"/>
                            </svg>
                        </div>
                        <span class="summary-card-badge">Today</span>
                    </div>
                    <div class="summary-card-value">12</div>
                    <div class="summary-card-label">Delivered</div>
                </article>
            </section>

            <!-- Chart Section -->
            <section class="chart-section" aria-label="Deliveries chart">
                <div class="chart-header">
                    <h3>Deliveries This Week</h3>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-dot deliveries" aria-hidden="true"></span>
                            Deliveries
                        </span>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="deliveriesChart" aria-label="Bar chart showing deliveries per day for the last 7 days"></canvas>
                </div>
            </section>

            <!-- Deliveries Table -->
            <section class="deliveries-section" aria-label="Deliveries list">
                <div class="deliveries-header">
                    <h3>Recent Deliveries</h3>
                    <span class="deliveries-count">8 of 8 deliveries</span>
                </div>
                <div class="deliveries-table-wrapper">
                    <table class="deliveries-table" role="grid">
                        <thead>
                            <tr>
                                <th scope="col">Order ID</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Status</th>
                                <th scope="col">Time</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="deliveriesTableBody">
                            <tr>
                                <td><span class="order-id">#ORD-7823</span></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name">Maria Santos</span>
                                        <span class="customer-address">123 Rizal St, Makati</span>
                                    </div>
                                </td>
                                <td><span class="status-badge pending">Pending</span></td>
                                <td>10:30 AM</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn start-btn" aria-label="Start delivery" title="Start Delivery">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-bike" aria-hidden="true">
                                                <path d="M12 19V13l-4-2"/>
                                                <path d="M8 11l4 2 5-3"/>
                                                <path d="M17 8v3"/>
                                                <circle class="wheel" cx="5" cy="19" r="3"/>
                                                <circle class="wheel" cx="19" cy="19" r="3"/>
                                                <path d="M17 8h2"/>
                                            </svg>
                                        </button>
                                        <button class="action-btn contact-btn" aria-label="Contact customer" title="Contact Customer">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="order-id">#ORD-7824</span></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name">Jose Garcia</span>
                                        <span class="customer-address">456 EDSA, Quezon City</span>
                                    </div>
                                </td>
                                <td><span class="status-badge transit">In Transit</span></td>
                                <td>11:15 AM</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn deliver-btn" aria-label="Mark as delivered" title="Mark Delivered">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-check" aria-hidden="true">
                                                <path d="M20 6L9 17l-5-5"/>
                                            </svg>
                                        </button>
                                        <button class="action-btn contact-btn" aria-label="Contact customer" title="Contact Customer">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="order-id">#ORD-7825</span></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name">Ana Reyes</span>
                                        <span class="customer-address">789 Ayala Ave, BGC</span>
                                    </div>
                                </td>
                                <td><span class="status-badge transit">In Transit</span></td>
                                <td>11:45 AM</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn deliver-btn" aria-label="Mark as delivered" title="Mark Delivered">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-check" aria-hidden="true">
                                                <path d="M20 6L9 17l-5-5"/>
                                            </svg>
                                        </button>
                                        <button class="action-btn contact-btn" aria-label="Contact customer" title="Contact Customer">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="order-id">#ORD-7826</span></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name">Pedro Cruz</span>
                                        <span class="customer-address">321 Shaw Blvd, Mandaluyong</span>
                                    </div>
                                </td>
                                <td><span class="status-badge pending">Pending</span></td>
                                <td>12:00 PM</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn start-btn" aria-label="Start delivery" title="Start Delivery">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-bike" aria-hidden="true">
                                                <path d="M12 19V13l-4-2"/>
                                                <path d="M8 11l4 2 5-3"/>
                                                <path d="M17 8v3"/>
                                                <circle class="wheel" cx="5" cy="19" r="3"/>
                                                <circle class="wheel" cx="19" cy="19" r="3"/>
                                                <path d="M17 8h2"/>
                                            </svg>
                                        </button>
                                        <button class="action-btn contact-btn" aria-label="Contact customer" title="Contact Customer">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="order-id">#ORD-7827</span></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name">Carmen Luna</span>
                                        <span class="customer-address">654 Jupiter St, Makati</span>
                                    </div>
                                </td>
                                <td><span class="status-badge pending">Pending</span></td>
                                <td>12:30 PM</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn start-btn" aria-label="Start delivery" title="Start Delivery">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon-bike" aria-hidden="true">
                                                <path d="M12 19V13l-4-2"/>
                                                <path d="M8 11l4 2 5-3"/>
                                                <path d="M17 8v3"/>
                                                <circle class="wheel" cx="5" cy="19" r="3"/>
                                                <circle class="wheel" cx="19" cy="19" r="3"/>
                                                <path d="M17 8h2"/>
                                            </svg>
                                        </button>
                                        <button class="action-btn contact-btn" aria-label="Contact customer" title="Contact Customer">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="order-id">#ORD-7820</span></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name">Roberto Tan</span>
                                        <span class="customer-address">987 Ortigas Ave, Pasig</span>
                                    </div>
                                </td>
                                <td><span class="status-badge delivered">Delivered</span></td>
                                <td>9:15 AM</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn contact-btn" aria-label="Contact customer" title="Contact Customer">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="order-id">#ORD-7819</span></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name">Elena Bautista</span>
                                        <span class="customer-address">159 Taft Ave, Manila</span>
                                    </div>
                                </td>
                                <td><span class="status-badge delivered">Delivered</span></td>
                                <td>8:45 AM</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn contact-btn" aria-label="Contact customer" title="Contact Customer">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td><span class="order-id">#ORD-7818</span></td>
                                <td>
                                    <div class="customer-info">
                                        <span class="customer-name">Miguel Ramos</span>
                                        <span class="customer-address">753 Katipunan Ave, QC</span>
                                    </div>
                                </td>
                                <td><span class="status-badge delivered">Delivered</span></td>
                                <td>8:00 AM</td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn contact-btn" aria-label="Contact customer" title="Contact Customer">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- Dashboard JavaScript -->
    <script src="assets/js/rider.js"></script>
</body>
</html>
