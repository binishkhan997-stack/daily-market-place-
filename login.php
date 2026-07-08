<?php
// login.php
session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if user is banned
                if ($user['user_type'] === 'vendor' && $user['vendor_status'] === 'suspended') {
                    $error = 'Your account has been suspended. Please contact support.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // Remember me (30 days)
                    if ($remember) {
                        setcookie('user_id', $user['id'], time() + (86400 * 30), '/');
                        setcookie('user_type', $user['user_type'], time() + (86400 * 30), '/');
                    }
                    
                    // Redirect based on user type
                    if ($user['user_type'] === 'vendor') {
                        header('Location: vendor_dashboard.php');
                    } else {
                        header('Location: index.php');
                    }
                    exit;
                }
            } else {
                $error = 'Invalid email/username or password';
            }
        } catch (PDOException $e) {
            $error = 'Login failed. Please try again.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MarketPK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 80px;
        }
        .login-container {
            max-width: 450px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .login-container h2 {
            color: #FF636B;
            text-align: center;
            margin-bottom: 30px;
        }
        .login-container .btn-primary {
            background: #FF636B;
            border: none;
            width: 100%;
            padding: 12px;
            font-weight: 600;
        }
        .login-container .btn-primary:hover {
            background: #0066CB;
        }
        .login-container a {
            color: #FF636B;
            text-decoration: none;
        }
        .login-container a:hover {
            color: #0066CB;
        }
        .form-control:focus {
            border-color: #FF636B;
            box-shadow: 0 0 0 0.2rem rgba(255, 99, 107, 0.25);
        }
    </style>
</head>
<body>

    <!-- Header -->
    <nav class="navbar navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <h2 style="color: #FF636B; font-weight: 700;">Market<span style="color: #0066CB;">PK</span></h2>
            </a>
            <a href="register.php" class="btn btn-outline-primary">Register</a>
        </div>
    </nav>

    <div class="container">
        <div class="login-container">
            <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Email or Username</label>
                    <input type="text" class="form-control" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" name="remember" id="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <div class="text-center mt-3">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="forgot_password.php">Forgot Password?</a></p>
            </div>
            
            <div class="text-center mt-3">
                <hr>
                <p class="text-muted">Are you a vendor? <a href="vendor_login.php">Login to Vendor Dashboard</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
