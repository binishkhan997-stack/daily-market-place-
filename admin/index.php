<?php
// admin/index.php
session_start();
require_once '../config/database.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get statistics
$stats = [];

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $stmt->fetch()['count'];

// Total vendors
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor'");
$stats['total_vendors'] = $stmt->fetch()['count'];

// Pending vendors
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND vendor_status = 'pending'");
$stats['pending_vendors'] = $stmt->fetch()['count'];

// Total products
$stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
$stats['total_products'] = $stmt->fetch()['count'];

// Total orders
$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
$stats['total_orders'] = $stmt->fetch()['count'];

// Total revenue
$stmt = $pdo->query("SELECT COALESCE(SUM(net_amount), 0) as revenue FROM orders WHERE status = 'delivered'");
$stats['total_revenue'] = $stmt->fetch()['revenue'];

// Pending orders
$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $stmt->fetch()['count'];

// Recent orders
$stmt = $pdo->query("
    SELECT o.*, u.full_name as customer_name, v.full_name as vendor_name 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN users v ON o.vendor_id = v.id
    ORDER BY o.created_at DESC LIMIT 10
");
$recent_orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MarketPK</title>
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
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-card .number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c2b2b;
        }
        .stat-card .label {
            color: #888;
        }
        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.3;
        }
        .table-container {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-badge.approved { background: #d4edda; color: #155724; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.suspended { background: #f8d7da; color: #721c24; }
        .status-badge.delivered { background: #d4edda; color: #155724; }
        .status-badge.shipped { background: #cce5ff; color: #004085; }
        .status-badge.cancelled { background: #f8d7da; color: #721c24; }
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
            <a class="nav-item active" href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a class="nav-item" href="vendors.php"><i class="fas fa-store"></i> Vendors</a>
            <a class="nav-item" href="products.php"><i class="fas fa-box"></i> Products</a>
            <a class="nav-item" href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a class="nav-item" href="users.php"><i class="fas fa-users"></i> Users</a>
            <a class="nav-item" href="payouts.php"><i class="fas fa-wallet"></i> Payouts</a>
            <a class="nav-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Dashboard</h4>
            <div>
                <span class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number"><?php echo number_format($stats['total_users']); ?></div>
                            <div class="label">Total Users</div>
                        </div>
                        <div class="icon"><i class="fas fa-users text-primary"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number"><?php echo number_format($stats['total_vendors']); ?></div>
                            <div class="label">Total Vendors</div>
                        </div>
                        <div class="icon"><i class="fas fa-store text-success"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number"><?php echo number_format($stats['total_orders']); ?></div>
                            <div class="label">Total Orders</div>
                        </div>
                        <div class="icon"><i class="fas fa-shopping-cart text-warning"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number">PKR <?php echo number_format($stats['total_revenue']); ?></div>
                            <div class="label">Total Revenue</div>
                        </div>
                        <div class="icon"><i class="fas fa-dollar-sign text-success"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" style="color: #FF636B;"><?php echo $stats['pending_vendors']; ?></div>
                            <div class="label">Pending Vendor Approvals</div>
                        </div>
                        <div class="icon"><i class="fas fa-user-clock text-warning"></i></div>
                    </div>
                    <a href="vendors.php?status=pending" class="btn btn-sm btn-outline-primary mt-2">Review Vendors</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number" style="color: #FF636B;"><?php echo $stats['pending_orders']; ?></div>
                            <div class="label">Pending Orders</div>
                        </div>
                        <div class="icon"><i class="fas fa-clock text-info"></i></div>
                    </div>
                    <a href="orders.php?status=pending" class="btn btn-sm btn-outline-primary mt-2">View Orders</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number"><?php echo number_format($stats['total_products']); ?></div>
                            <div class="label">Total Products</div>
                        </div>
                        <div class="icon"><i class="fas fa-box text-primary"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="number">5</div>
                            <div class="label">Active Categories</div>
                        </div>
                        <div class="icon"><i class="fas fa-tags text-success"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="table-container">
            <h5 class="mb-3">Recent Orders</h5>
            <?php if (!empty($recent_orders)): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Vendor</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                                    <td><?php echo htmlspecialchars($order['vendor_name'] ?? 'N/A'); ?></td>
                                    <td>PKR <?php echo number_format($order['net_amount']); ?></td>
                                    <td><span class="status-badge <?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                    <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center">No recent orders</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
