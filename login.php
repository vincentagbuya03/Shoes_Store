<?php
require_once 'db_connection.php';

$email = '';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {

        $query = "SELECT customer_id, name, email, password FROM customer WHERE email = ?";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $customer = $result->fetch_assoc();
                if (password_verify($password, $customer['password'])) {
                    session_start();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - ShoeTakels</title>
    <link rel="icon" type="image/x-icon" href="upload/picture/logo.png">
    <link rel="stylesheet" href="asset/style/index.css">
    <style> 
        :root {
            --primary-color: #000;
            --accent-color: #d4af37;
            --background-light: #f5f5f5;
            --border-color: #ddd;
            --error-color: #dc3545;
            --success-color: #28a745;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #e9e9e9 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 550px; /* Increased width for two-column layout */
            padding: 2.5rem;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .login-header p {
            color: #666;
            font-size: 0.95rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-row.full {
            grid-template-columns: 1fr;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.7rem;
            font-size: 0.97rem;
            gap: 0.5rem;
        }
        .remember-forgot label {
            font-weight: 500;
            color: #444;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .remember-forgot input[type="checkbox"] {
            accent-color: var(--accent-color);
            margin-right: 0.3rem;
        }
        .remember-forgot a {
            color: var(--accent-color);
            text-decoration: none;
            font-size: 0.97rem;
            transition: color 0.3s;
        }
        .remember-forgot a:hover {
            text-decoration: underline;
            color: #bfa12a;
        }
        .signup-link {
            text-align: center;
            margin-top: 1.2rem;
            color: #666;
            font-size: 0.97rem;
        }
        .signup-link a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .signup-link a:hover {
            text-decoration: underline;
            color: #bfa12a;
        }

        .social-login {
            display: flex;
            gap: 0.7rem;
            margin-bottom: 1.1rem;
        }
        .social-btn {
            flex: 1;
            padding: 0.7rem 0;
            border: 2px solid var(--border-color);
            background: #fafafa;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.97rem;
            font-weight: 600;
            color: #333;
            transition: border-color 0.3s, background 0.3s, color 0.3s;
        }
        .social-btn:hover {
            border-color: var(--accent-color);
            background: #fffbe6;
            color: var(--accent-color);
        }

        label {
            display: block;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .alert-error {
            background-color: #f8d7da;
            color: var(--error-color);
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background-color: #d4edda;
            color: var(--success-color);
            border: 1px solid #c3e6cb;
        }

        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            margin-top: 1rem;
        }

        .btn-primary:hover {
            background-color: #333;
            transform: translateY(-2px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 1.5rem 0;
            color: #999;
            font-size: 0.9rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background-color: var(--border-color);
        }

        .divider span {
            margin: 0 1rem;
        }

        .social-login {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .social-btn {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            background-color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: border-color 0.3s, background-color 0.3s;
        }

        .social-btn:hover {
            border-color: var(--accent-color);
            background-color: #fafafa;
        }

        .signin-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .signin-link a {
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .signin-link a:hover {
            text-decoration: underline;
        }

        .password-hint {
            font-size: 0.8rem;
            color: #999;
            margin-top: 0.25rem;
        }

        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            margin-bottom: 1rem;
            margin-top: 1rem;
        }

        .terms-checkbox input {
            margin-top: 0.25rem;
            cursor: pointer;
        }

        .terms-checkbox label {
            margin-bottom: 0;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .terms-checkbox a {
            color: var(--accent-color);
            text-decoration: none;
        }

        .terms-checkbox a:hover {
            text-decoration: underline;
        }

        footer {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: auto;
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 1.5rem;
                max-width: 100%;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            nav {
                flex-wrap: wrap;
                gap: 1rem;
            }

            .nav-links {
                gap: 1rem;
                flex-wrap: wrap;
            }
        }

        .promo-section {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 60px 20px;
            margin: 40px 0;
            border-radius: 12px;
            color: white;
            text-align: center;
        }

        .promo-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .promo-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }

        .promo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .promo-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .promo-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .promo-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .promo-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .promo-card p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.5;
        }

        .cta-button {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 25px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .cta-button:hover {
            background: #c0392b;
        }

        .featured-offer {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }

        .featured-offer h2 {
            font-size: 24px;
            margin-bottom: 10px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        .discount-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 14px;
        }

        footer {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 40px 20px 20px;
            margin-top: 60px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-section h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #ecf0f1;
        }

        .footer-section a {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            margin-bottom: 8px;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .footer-section a:hover {
            color: #e74c3c;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
            text-align: center;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        @media (max-width: 768px) {
            .promo-title {
                font-size: 22px;
            }

            .featured-offer {
                padding: 25px;
            }

            .featured-offer h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">
            <a href="index.php">ShoeTakels</a>
        </div>
        <div class="nav-right">
            <div class="cart-icon">🛒</div>
        </div>
    </nav>

    <main>
        <div class="login-container">
            <div class="login-header">
                <h1>Sign In</h1>
                <p>Welcome back to ShoeTakels</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com" value="<?php echo htmlspecialchars($email); ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>

                <div class="remember-forgot">
                    <label style="margin-bottom: 0; font-weight: 500;">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="forgot-password.php">Forgot password?</a>
                </div>

                <button type="submit" class="btn-primary">Sign In</button>
            </form>

            <div class="divider"><span>OR</span></div>

            <div class="social-login">
                <button class="social-btn" onclick="alert('Google Sign In - Coming Soon')">Google</button>
                <button class="social-btn" onclick="alert('Facebook Sign In - Coming Soon')">Facebook</button>
            </div>

            <div class="signup-link">
                Don't have an account? <a href="signup.php">Create one now</a>
            </div>
        </div>
    </main>
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>About ShoeTakels</h4>
                <div>
                    <a href="#about">About Us</a>
                    <a href="#careers">Careers</a>
                    <a href="#press">Press</a>
                    <a href="#blog">Blog</a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Customer Service</h4>
                <div>
                    <a href="#contact">Contact Us</a>
                    <a href="#faq">FAQ</a>
                    <a href="#shipping">Shipping Info</a>
                    <a href="#track">Track Order</a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Shop</h4>
                <div>
                    <a href="#men">Men's Shoes</a>
                    <a href="#women">Women's Shoes</a>
                    <a href="#kids">Kids' Shoes</a>
                    <a href="#sale">Sale</a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Connect</h4>
                <div>
                    <a href="#facebook">Facebook</a>
                    <a href="#instagram">Instagram</a>
                    <a href="#twitter">Twitter</a>
                    <a href="#newsletter">Newsletter</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 ShoeTakels. All rights reserved. | <a href="#privacy">Privacy Policy</a> | <a href="#terms">Terms of Service</a></p>
        </div>
    </footer>
</body>
</html>
