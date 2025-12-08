<?php
session_start();
if (!isset($_SESSION['rider_id'])) {
    header('Location: ../login.php');
    exit;
}
require_once __DIR__ . '/../db_connection.php';

$rider_id = $_SESSION['rider_id'];
$success_message = '';
$error_message = '';

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        if (empty($name)) {
            $error_message = 'Name is required.';
        } else {
            $stmt = $conn->prepare("UPDATE rider SET name = ?, phone = ? WHERE rider_id = ?");
            $stmt->bind_param('ssi', $name, $phone, $rider_id);
            if ($stmt->execute()) {
                $success_message = 'Profile updated successfully!';
                $rider['name'] = $name;
                $rider['phone'] = $phone;
                $_SESSION['rider_name'] = $name;
            } else {
                $error_message = 'Failed to update profile.';
            }
            $stmt->close();
        }
    }
    
    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'Password must be at least 6 characters.';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM rider WHERE rider_id = ?");
            $stmt->bind_param('i', $rider_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $rider_data = $res->fetch_assoc();
            $stmt->close();
            
            if ($rider_data && password_verify($current_password, $rider_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE rider SET password = ? WHERE rider_id = ?");
                $stmt->bind_param('si', $hashed_password, $rider_id);
                if ($stmt->execute()) {
                    $success_message = 'Password changed successfully!';
                } else {
                    $error_message = 'Failed to change password.';
                }
                $stmt->close();
            } else {
                $error_message = 'Current password is incorrect.';
            }
        }
    }
    
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? 'available';
        $valid_statuses = ['available', 'busy', 'offline'];
        if (in_array($status, $valid_statuses)) {
            $stmt = $conn->prepare("UPDATE rider SET status = ? WHERE rider_id = ?");
            $stmt->bind_param('si', $status, $rider_id);
            if ($stmt->execute()) {
                $success_message = 'Status updated!';
                $rider['status'] = $status;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Settings - ShoeTakels Rider">
    <title>Settings | ShoeTakels Rider</title>
    
    <link rel="icon" type="image/x-icon" href="../upload/picture/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/rider.css">
    <style>
        .settings-container {
            display: grid;
            gap: 24px;
            max-width: 800px;
        }
        
        .settings-card {
            background: var(--bg-card);
            backdrop-filter: var(--glass-blur);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 24px;
        }
        .settings-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-light);
        }
        .settings-card-header .icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-indigo), var(--primary-indigo-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .settings-card-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .settings-card-header p {
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-top: 2px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-light);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            color: var(--text-primary);
            transition: all var(--transition-fast);
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-indigo);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }
        .form-group input::placeholder {
            color: var(--text-muted);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-indigo), var(--primary-indigo-dark));
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        }
        
        .status-options {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .status-option {
            flex: 1;
            min-width: 120px;
            padding: 16px;
            background: var(--bg-light);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-md);
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .status-option:hover {
            border-color: var(--primary-indigo);
        }
        .status-option.active {
            border-color: var(--primary-indigo);
            background: rgba(99, 102, 241, 0.1);
        }
        .status-option input {
            display: none;
        }
        .status-option .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin: 0 auto 8px;
        }
        .status-option.available .status-indicator { background: #22c55e; }
        .status-option.busy .status-indicator { background: #f59e0b; }
        .status-option.offline .status-indicator { background: #94a3b8; }
        .status-option span {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #16a34a;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 24px;
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-indigo), var(--primary-indigo-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .profile-info h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .profile-info p {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <?php include 'partials/sidebar.php'; ?>

    <div class="dashboard-wrapper">
        <main class="main-content" role="main">
            <header class="page-header">
                <h1>Settings</h1>
                <p>Manage your account and preferences</p>
            </header>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>

            <div class="settings-container">
                <!-- Availability Status -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 6v6l4 2"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Availability Status</h3>
                            <p>Set your current availability for deliveries</p>
                        </div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <div class="status-options">
                            <label class="status-option available <?php echo $rider['status'] === 'available' ? 'active' : ''; ?>">
                                <input type="radio" name="status" value="available" <?php echo $rider['status'] === 'available' ? 'checked' : ''; ?>>
                                <div class="status-indicator"></div>
                                <span>Available</span>
                            </label>
                            <label class="status-option busy <?php echo $rider['status'] === 'busy' ? 'active' : ''; ?>">
                                <input type="radio" name="status" value="busy" <?php echo $rider['status'] === 'busy' ? 'checked' : ''; ?>>
                                <div class="status-indicator"></div>
                                <span>Busy</span>
                            </label>
                            <label class="status-option offline <?php echo $rider['status'] === 'offline' ? 'active' : ''; ?>">
                                <input type="radio" name="status" value="offline" <?php echo $rider['status'] === 'offline' ? 'checked' : ''; ?>>
                                <div class="status-indicator"></div>
                                <span>Offline</span>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 16px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6L9 17l-5-5"/>
                            </svg>
                            Update Status
                        </button>
                    </form>
                </div>

                <!-- Profile Information -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Profile Information</h3>
                            <p>Update your personal details</p>
                        </div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($rider['name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" value="<?php echo htmlspecialchars($rider['email']); ?>" disabled>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($rider['phone'] ?? ''); ?>" placeholder="09XX XXX XXXX">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                                <polyline points="17 21 17 13 7 13 7 21"/>
                                <polyline points="7 3 7 8 15 8"/>
                            </svg>
                            Save Changes
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="icon" style="background: linear-gradient(135deg, var(--accent-orange), #ea580c);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Change Password</h3>
                            <p>Update your account password</p>
                        </div>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            Change Password
                        </button>
                    </form>
                </div>

                <!-- Logout -->
                <div class="settings-card">
                    <div class="settings-card-header">
                        <div class="icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                        </div>
                        <div>
                            <h3>Logout</h3>
                            <p>Sign out of your account</p>
                        </div>
                    </div>
                    <a href="logout.php" class="btn btn-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <polyline points="16 17 21 12 16 7"/>
                            <line x1="21" y1="12" x2="9" y2="12"/>
                        </svg>
                        Logout
                    </a>
                </div>
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

        // Status option selection
        document.querySelectorAll('.status-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.status-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
    
    <!-- Notifications JavaScript -->
    <script src="assets/js/notifications.js"></script>
</body>
</html>
