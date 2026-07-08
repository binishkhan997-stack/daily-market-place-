<?php
// admin/settings.php
session_start();
require_once '../config/database.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$error = '';

// Load settings (from a settings table or hardcoded)
$settings = [
    'site_name' => 'MarketPK',
    'site_tagline' => 'Pakistan\'s Best Online Shopping Marketplace',
    'commission_rate' => 10,
    'free_shipping_threshold' => 2000,
    'shipping_charge' => 200,
    'currency' => 'PKR',
    'enable_vendor_registration' => 1,
    'enable_customer_registration' => 1,
    'maintenance_mode' => 0,
    'contact_email' => 'info@marketpk.com',
    'contact_phone' => '+92 300 1234567',
    'address' => 'Karachi, Pakistan'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real implementation, save to a settings table
    $message = 'Settings updated successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 250px;
            background: #2c2b2b;
            color: #fff;
            padding: 20px 0;
            overflow-y: auto;
        }
        .sidebar .brand {
            padding: 0 20px 20px;
            border-bottom: 1px solid #444;
            text-align: center;
        }
        .sidebar .brand h3 {
            color: #FF636B;
        }
        .sidebar .nav-item {
            padding: 12px 20px;
            cursor: pointer;
            transition: 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar .nav-item:hover,
        .sidebar .nav-item.active {
            background: rgba(255, 99, 107, 0.1);
            border-left-color: #FF636B;
            color: #FF636B;
        }
        .sidebar .nav-item i {
            width: 25px;
            margin-right: 10px;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        .settings-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .settings-card h5 {
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        .settings-card .form-control:focus,
        .settings-card .form-select:focus {
            border-color: #FF636B;
            box-shadow: 0 0 0 0.2rem rgba(255, 99, 107, 0.25);
        }
        .settings-card .btn-primary {
            background: #FF636B;
            border: none;
            padding: 10px 30px;
        }
        .settings-card .btn-primary:hover {
            background: #0066CB;
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="brand">
            <h3><i class="fas fa-crown"></i> Admin</h3>
            <small>Multivendor Marketplace</small>
        </div>
        <nav class="nav flex-column mt-3">
            <a class="nav-item" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a class="nav-item" href="vendors.php"><i class="fas fa-store"></i> Vendors</a>
            <a class="nav-item" href="products.php"><i class="fas fa-box"></i> Products</a>
            <a class="nav-item" href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a class="nav-item" href="users.php"><i class="fas fa-users"></i> Users</a>
            <a class="nav-item" href="payouts.php"><i class="fas fa-wallet"></i> Payouts</a>
            <a class="nav-item active" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h4 class="mb-4"><i class="fas fa-cog"></i> Platform Settings</h4>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST">

            <!-- General Settings -->
            <div class="settings-card">
                <h5><i class="fas fa-globe"></i> General Settings</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Site Name</label>
                        <input type="text" class="form-control" name="site_name" value="<?php echo $settings['site_name']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Site Tagline</label>
                        <input type="text" class="form-control" name="site_tagline" value="<?php echo $settings['site_tagline']; ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Email</label>
                        <input type="email" class="form-control" name="contact_email" value="<?php echo $settings['contact_email']; ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" class="form-control" name="contact_phone" value="<?php echo $settings['contact_phone']; ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-control" name="address" value="<?php echo $settings['address']; ?>">
                </div>
            </div>

            <!-- Commission & Shipping -->
            <div class="settings-card">
                <h5><i class="fas fa-dollar-sign"></i> Commission & Shipping</h5>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Commission Rate (%)</label>
                        <input type="number" class="form-control" name="commission_rate" value="<?php echo $settings['commission_rate']; ?>" min="0" max="100">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Free Shipping Threshold (PKR)</label>
                        <input type="number" class="form-control" name="free_shipping_threshold" value="<?php echo $settings['free_shipping_threshold']; ?>" min="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Shipping Charge (PKR)</label>
                        <input type="number" class="form-control" name="shipping_charge" value="<?php echo $settings['shipping_charge']; ?>" min="0">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Currency</label>
                        <select class="form-select" name="currency">
                            <option value="PKR" <?php echo $settings['currency'] === 'PKR' ? 'selected' : ''; ?>>PKR - Pakistani Rupee</option>
                            <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>USD - US Dollar</option>
                            <option value="EUR" <?php echo $settings['currency'] === 'EUR' ? 'selected' : ''; ?>>EUR - Euro</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Registration & Maintenance -->
            <div class="settings-card">
                <h5><i class="fas fa-toggle-on"></i> Registration & Maintenance</h5>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="enable_vendor_registration" <?php echo $settings['enable_vendor_registration'] ? 'checked' : ''; ?>>
                            <label class="form-check-label">Enable Vendor Registration</label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="enable_customer_registration" <?php echo $settings['enable_customer_registration'] ? 'checked' : ''; ?>>
                            <label class="form-check-label">Enable Customer Registration</label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                            <label class="form-check-label">Maintenance Mode</label>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
