<?php
require_once 'db_connection.php';
require_once 'inc/store_settings.php';

$email = '';
$error = '';
$success = '';

// Handle form submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $location = trim($_POST['location'] ?? ''); // Added location field
    
    // Validate input
    if (empty($email) || empty($password) || empty($confirm_password) || empty($fullname) || empty($location)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if user already exists
        $query = "SELECT customer_id FROM customer WHERE email = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email already registered. Please sign in instead.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $insert_query = "INSERT INTO customer (email, password, name, address) VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                
                if ($insert_stmt) {
                    $insert_stmt->bind_param('ssss', $email, $hashed_password, $fullname, $location);
                    
                    if ($insert_stmt->execute()) {
                        // Auto-login after successful signup
                        session_start();
                        $_SESSION['customer_id'] = $insert_stmt->insert_id;
                        $_SESSION['customer_name'] = $fullname;
                        $_SESSION['customer_email'] = $email;
                        
                        // Redirect to user interface (logged in homepage)
                        header('Location: user-interface.php');
                        exit();
                    } else {
                        $error = 'Error creating account. Please try again.';
                    }
                    $insert_stmt->close();
                } else {
                    $error = 'Database error. Please try again later.';
                }
            }
            $stmt->close();
        } else {
            $error = 'Database error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - <?php echo htmlspecialchars($store_settings['store_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="asset/style/animations.css">
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

        .signup-wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Left Side - Branding */
        .signup-branding {
            flex: 0 0 45%;
            background: linear-gradient(135deg, var(--primary) 0%, #2d2d2d 50%, var(--primary) 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .signup-branding::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(212, 165, 116, 0.1) 0%, transparent 50%);
            animation: pulse 15s ease-in-out infinite;
        }

        .signup-branding::after {
            content: '';
            position: absolute;
            bottom: -20%;
            right: -20%;
            width: 60%;
            height: 60%;
            background: radial-gradient(circle, rgba(212, 165, 116, 0.08) 0%, transparent 60%);
            animation: pulse 20s ease-in-out infinite reverse;
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
            max-width: 400px;
        }

        .branding-logo {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--white) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .branding-tagline {
            font-size: 1.25rem;
            font-weight: 300;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .branding-benefits {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-align: left;
            padding: 1rem 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .benefit-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .benefit-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: var(--white);
            flex-shrink: 0;
        }

        .benefit-text h4 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .benefit-text p {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        /* Right Side - Form */
        .signup-form-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem 3rem;
            background: var(--white);
            overflow-y: auto;
        }

        .form-container {
            width: 100%;
            max-width: 480px;
        }

        .form-header {
            margin-bottom: 2rem;
        }

        .form-header .mobile-logo {
            display: none;
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1.5rem;
            text-decoration: none;
        }

        .form-header h1 {
            font-size: 1.875rem;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            margin-bottom: 1.25rem;
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

        .input-wrapper i.input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 1rem;
            transition: color 0.3s ease;
            pointer-events: none;
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

        .form-group input:focus + i.input-icon,
        .input-wrapper:focus-within i.input-icon {
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

        .password-hint {
            font-size: 0.75rem;
            color: var(--gray-400);
            margin-top: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .password-hint i {
            font-size: 0.7rem;
        }

        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin: 1.5rem 0;
        }

        .terms-checkbox input {
            width: 20px;
            height: 20px;
            accent-color: var(--accent);
            cursor: pointer;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .terms-checkbox label {
            font-size: 0.875rem;
            color: var(--gray-600);
            line-height: 1.5;
            cursor: pointer;
        }

        .terms-checkbox a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .terms-checkbox a:hover {
            color: var(--accent-dark);
            text-decoration: underline;
        }

        .btn-signup {
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

        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-signup:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.75rem 0;
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

        .signin-prompt {
            text-align: center;
            margin-top: 1.75rem;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .signin-prompt a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signin-prompt a:hover {
            color: var(--accent-dark);
            text-decoration: underline;
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.35rem;
        }

        .strength-fill {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: all 0.3s ease;
        }

        .strength-fill.weak { width: 33%; background: #ef4444; }
        .strength-fill.medium { width: 66%; background: #f59e0b; }
        .strength-fill.strong { width: 100%; background: #10b981; }

        .strength-text {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .signup-branding {
                display: none;
            }

            .signup-form-section {
                padding: 2rem;
            }

            .form-header .mobile-logo {
                display: block;
            }
        }

        @media (max-width: 600px) {
            .signup-form-section {
                padding: 1.5rem;
            }

            .form-container {
                max-width: 100%;
            }

            .form-header h1 {
                font-size: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .social-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="signup-wrapper">
        <!-- Left Branding Section -->
        <div class="signup-branding">
            <div class="branding-content">
                <div class="branding-logo"><?php echo htmlspecialchars($store_settings['store_name']); ?></div>
                <p class="branding-tagline">Join thousands of shoe lovers and discover your perfect style</p>
                
                <div class="branding-benefits">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Welcome Discount</h4>
                            <p>Get 15% off your first order</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Exclusive Access</h4>
                            <p>Early access to new arrivals & sales</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Save Favorites</h4>
                            <p>Create wishlists & track orders</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Rewards Program</h4>
                            <p>Earn points on every purchase</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Form Section -->
        <div class="signup-form-section">
            <div class="form-container">
                <div class="form-header">
                    <a href="index.php" class="mobile-logo"><?php echo htmlspecialchars($store_settings['store_name']); ?></a>
                    <h1>Create your account</h1>
                    <p>Start your shoe shopping journey today</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="signup.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <div class="input-wrapper">
                                <input type="text" id="fullname" name="fullname" placeholder="John Doe" required value="<?php echo htmlspecialchars($fullname ?? ''); ?>">
                                <i class="fas fa-user input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-wrapper">
                                <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($email); ?>">
                                <i class="fas fa-envelope input-icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-row full">
                        <div class="form-group">
                            <label for="location">Location / Address</label>
                            <div class="input-wrapper">
                                <input type="text" id="location" name="location" placeholder="Your city or full address" required value="<?php echo htmlspecialchars($location ?? ''); ?>">
                                <i class="fas fa-map-marker-alt input-icon"></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-wrapper">
                                <input type="password" id="password" name="password" placeholder="Create a password" required onkeyup="checkPasswordStrength()">
                                <i class="fas fa-lock input-icon"></i>
                                <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                    <i class="fas fa-eye" id="toggleIcon1"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <span class="strength-text" id="strengthText">Use 8+ characters with mix of letters & numbers</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="input-wrapper">
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                                <i class="fas fa-lock input-icon"></i>
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                    <i class="fas fa-eye" id="toggleIcon2"></i>
                                </button>
                            </div>
                            <p class="password-hint" id="matchHint"><i class="fas fa-info-circle"></i> Must match your password</p>
                        </div>
                    </div>

                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="btn-signup">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                </form>

                <div class="divider">
                    <span>or sign up with</span>
                </div>

                <div class="social-buttons">
                    <button type="button" class="social-btn google" onclick="alert('Google Sign Up - Coming Soon')">
                        <i class="fab fa-google"></i>
                        Google
                    </button>
                    <button type="button" class="social-btn facebook" onclick="alert('Facebook Sign Up - Coming Soon')">
                        <i class="fab fa-facebook-f"></i>
                        Facebook
                    </button>
                </div>

                <p class="signin-prompt">
                    Already have an account? <a href="login.php">Sign in</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            strengthFill.className = 'strength-fill';
            
            if (password.length === 0) {
                strengthFill.style.width = '0';
                strengthText.textContent = 'Use 8+ characters with mix of letters & numbers';
            } else if (strength <= 1) {
                strengthFill.classList.add('weak');
                strengthText.textContent = 'Weak - Add more characters and numbers';
            } else if (strength <= 2) {
                strengthFill.classList.add('medium');
                strengthText.textContent = 'Medium - Try adding special characters';
            } else {
                strengthFill.classList.add('strong');
                strengthText.textContent = 'Strong password!';
            }
        }

        // Check password match
        document.getElementById('confirm_password').addEventListener('keyup', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const matchHint = document.getElementById('matchHint');
            
            if (confirmPassword.length === 0) {
                matchHint.innerHTML = '<i class="fas fa-info-circle"></i> Must match your password';
                matchHint.style.color = '#9ca3af';
            } else if (password === confirmPassword) {
                matchHint.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match!';
                matchHint.style.color = '#10b981';
            } else {
                matchHint.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
                matchHint.style.color = '#ef4444';
            }
        });
    </script>
</body>
</html>
