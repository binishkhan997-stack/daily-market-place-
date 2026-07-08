<?php
// register.php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->rowCount() > 0) {
                $error = 'Email or username already exists';
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, full_name, phone, address, user_type) 
                    VALUES (?, ?, ?, ?, ?, ?, 'customer')
                ");
                $stmt->execute([$username, $email, $password_hash, $full_name, $phone, $address]);
                
                $user_id = $pdo->lastInsertId();
                
                // Auto login
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_type'] = 'customer';
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $full_name;
                
                $success = 'Registration successful! Redirecting...';
                header('refresh:2;url=index.php');
            }
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MarketPK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 80px;
        }
        .register-container {
            max-width: 500px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .register-container h2 {
            color: #FF636B;
            text-align: center;
            margin-bottom: 30px;
        }
        .register-container .btn-primary {
            background: #FF636B;
            border: none;
            width: 100%;
            padding: 12px;
            font-weight: 600;
        }
        .register-container .btn-primary:hover {
            background: #0066CB;
        }
        .register-container a {
            color: #FF636B;
            text-decoration: none;
        }
        .register-container a:hover {
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
            <a href="login.php" class="btn btn-outline-primary">Login</a>
        </div>
    </nav>

    <div class="container">
        <div class="register-container">
            <h2><i class="fas fa-user-plus"></i> Create Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name *</label>
                    <input type="text" class="form-control" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Username *</label>
                    <input type="text" class="form-control" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password *</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirm Password *</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
            
            <div class="text-center mt-3">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
