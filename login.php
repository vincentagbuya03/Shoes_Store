<?php
require_once 'db_connection.php';
require_once 'inc/store_settings.php';

$email = '';
$error = '';
$success = '';

if (session_status() === PHP_SESSION_NONE) session_start();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $adminQuery = "SELECT admin_id, name, email, password FROM admin WHERE email = ? LIMIT 1";
        if ($stmt = $conn->prepare($adminQuery)) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows === 1) {
                $admin = $res->fetch_assoc();
                if (!empty($admin['password']) && password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['admin_id'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_user'] = $admin['name'];
                    $_SESSION['is_admin'] = true;

                    header('Location: admin/index.php');
                    exit();
                } else {
                    $error = 'Invalid email or password.';
                }
            }
            $stmt->close();
        }
        if (empty($error)) {
            $riderQuery = "SELECT rider_id, name, email, password FROM rider WHERE email = ? LIMIT 1";
            if ($rstmt = $conn->prepare($riderQuery)) {
                $rstmt->bind_param('s', $email);
                $rstmt->execute();
                $rres = $rstmt->get_result();
                if ($rres && $rres->num_rows === 1) {
                    $rider = $rres->fetch_assoc();
                    if (!empty($rider['password']) && password_verify($password, $rider['password'])) {
                        $_SESSION['rider_id'] = $rider['rider_id'];
                        $_SESSION['rider_name'] = $rider['name'];
                        $_SESSION['rider_email'] = $rider['email'];

                        header('Location: delivery_rider/dashboard.php');
                            exit();
                    } else {
                        $error = 'Invalid email or password.';
                    }
                }
                $rstmt->close();
            }

            $query = "SELECT customer_id, name, email, password FROM customer WHERE email = ? LIMIT 1";
            $stmt = $conn->prepare($query);

            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows === 1) {
                    $customer = $result->fetch_assoc();
                    if (!empty($customer['password']) && password_verify($password, $customer['password'])) {
                        $_SESSION['customer_id'] = $customer['customer_id'];
                        $_SESSION['customer_name'] = $customer['name'];
                        $_SESSION['customer_email'] = $customer['email'];

                        header('Location: user-interface.php');
                        exit();
                    } else {
                        $error = 'Invalid email or password.';
                    }
                } else {
                    $error = 'Invalid email or password.';
                }
                $stmt->close();
            } else {
                $error = 'Database error. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <?php echo getStoreThemeCSS(); ?>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #1a1a1a;
            --accent: #d4a574;
            --accent-dark: #b8956a;
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --error: #ef4444;
            --success: #10b981;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: var(--gray-100);
            min-height: 100vh;
            display: flex;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left Side - Branding */
        .login-branding {
            flex: 1;
            background: linear-gradient(135deg, var(--primary) 0%, #2d2d2d 50%, var(--primary) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .login-branding::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 165, 116, 0.1) 0%, transparent 50%);
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .branding-content {
            position: relative;
            z-index: 1;
            text-align: center;
            color: var(--white);
            max-width: 480px;
        }

        .branding-logo {
            font-size: 3rem;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--white) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .branding-tagline {
            font-size: 1.5rem;
            font-weight: 300;
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .branding-features {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            margin-top: 3rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-align: left;
            padding: 1rem 1.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--white);
            flex-shrink: 0;
        }

        .feature-text h4 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .feature-text p {
            font-size: 0.875rem;
            opacity: 0.7;
        }

        /* Right Side - Form */
        .login-form-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            background: var(--white);
        }

        .form-container {
            width: 100%;
            max-width: 420px;
        }

        .form-header {
            margin-bottom: 2.5rem;
        }

        .form-header .mobile-logo {
            display: none;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .form-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .form-header p {
            color: var(--gray-500);
            font-size: 1rem;
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background: #fef2f2;
            color: var(--error);
            border: 1px solid #fecaca;
        }

        .alert-error::before {
            content: '\f06a';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }

        .alert-success {
            background: #ecfdf5;
            color: var(--success);
            border: 1px solid #a7f3d0;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1rem;
            transition: color 0.3s ease;
        }

        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.75rem;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            font-size: 1rem;
            color: var(--gray-900);
            background: var(--white);
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(212, 165, 116, 0.15);
        }

        .form-group input:focus + i,
        .input-wrapper:focus-within i {
            color: var(--accent);
        }

        .form-group input::placeholder {
            color: var(--gray-400);
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-400);
            cursor: pointer;
            padding: 0.25rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--gray-600);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .remember-me input {
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
            cursor: pointer;
        }

        .remember-me span {
            font-size: 0.875rem;
            color: var(--gray-600);
        }

        .forgot-link {
            font-size: 0.875rem;
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: var(--accent-dark);
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--gray-800) 100%);
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            color: var(--gray-400);
            font-size: 0.875rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }

        .divider span {
            padding: 0 1rem;
        }

        .social-buttons {
            display: flex;
            gap: 1rem;
        }

        .social-btn {
            flex: 1;
            padding: 0.875rem;
            border: 2px solid var(--gray-200);
            background: var(--white);
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-700);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .social-btn:hover {
            border-color: var(--gray-300);
            background: var(--gray-50);
        }

        .social-btn.google:hover {
            border-color: #ea4335;
            color: #ea4335;
        }

        .social-btn.facebook:hover {
            border-color: #1877f2;
            color: #1877f2;
        }

        .social-btn i {
            font-size: 1.1rem;
        }

        .signup-prompt {
            text-align: center;
            margin-top: 2rem;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .signup-prompt a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-prompt a:hover {
            color: var(--accent-dark);
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .login-branding {
                display: none;
            }

            .login-form-section {
                padding: 2rem;
            }

            .form-header .mobile-logo {
                display: block;
            }
        }

        @media (max-width: 480px) {
            .login-form-section {
                padding: 1.5rem;
            }

            .form-container {
                max-width: 100%;
            }

            .form-header h1 {
                font-size: 1.75rem;
            }

            .social-buttons {
                flex-direction: column;
            }

            .form-options {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Branding Section -->
        <div class="login-branding">
            <div class="branding-content">
                <div class="branding-logo"><?php echo htmlspecialchars($store_settings['store_name']); ?></div>
                <p class="branding-tagline">Step into style and comfort with our premium footwear collection</p>
                
                <div class="branding-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Free Shipping</h4>
                            <p>On orders over ₱50</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-undo-alt"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Easy Returns</h4>
                            <p>30-day hassle-free returns</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="feature-text">
                            <h4>Secure Shopping</h4>
                            <p>100% secure payment</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Form Section -->
        <div class="login-form-section">
            <div class="form-container">
                <div class="form-header">
                    <a href="index.php" class="mobile-logo"><?php echo htmlspecialchars($store_settings['store_name']); ?></a>
                    <h1>Welcome back</h1>
                    <p>Sign in to continue shopping</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" placeholder="Enter your email" required value="<?php echo htmlspecialchars($email); ?>">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <i class="fas fa-lock"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>

                <div class="divider">
                    <span>or continue with</span>
                </div>

                <div class="social-buttons">
                    <button type="button" class="social-btn google" onclick="alert('Google Sign In - Coming Soon')">
                        <i class="fab fa-google"></i>
                        Google
                    </button>
                    <button type="button" class="social-btn facebook" onclick="alert('Facebook Sign In - Coming Soon')">
                        <i class="fab fa-facebook-f"></i>
                        Facebook
                    </button>
                </div>

                <p class="signup-prompt">
                    Don't have an account? <a href="signup.php">Create one now</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
