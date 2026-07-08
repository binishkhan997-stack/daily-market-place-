<?php
// admin/orders.php
session_start();
require_once '../config/database.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get orders
$status_filter = $_GET['status'] ?? '';
$sql = "SELECT o.*, u.full_name as customer_name, v.full_name as vendor_name 
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN users v ON o.vendor_id = v.id";

if ($status_filter) {
    $sql .= " WHERE o.status = ?";
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($status_filter) {
    $stmt->execute([$status_filter]);
} else {
    $stmt->execute();
}
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - Admin</title>
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
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.processing { background: #cce5ff; color: #004085; }
        .status-badge.shipped { background: #cce5ff; color: #004085; }
        .status-badge.delivered { background: #d4edda; color: #155724; }
        .status-badge.cancelled { background: #f8d7da; color: #721c24; }
        .status-badge.returned { background: #f8d7da; color: #721c24; }
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
            <a class="nav-item active" href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a class="nav-item" href="users.php"><i class="fas fa-users"></i> Users</a>
            <a class="nav-item" href="payouts.php"><i class="fas fa-wallet"></i> Payouts</a>
            <a class="nav-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Order Management</h4>
            <div>
                <a href="?status=pending" class="btn btn-outline-warning btn-sm">Pending</a>
                <a href="?status=processing" class="btn btn-outline-primary btn-sm">Processing</a>
                <a href="?status=shipped" class="btn btn-outline-info btn-sm">Shipped</a>
                <a href="?status=delivered" class="btn btn-outline-success btn-sm">Delivered</a>
                <a href="?status=cancelled" class="btn btn-outline-danger btn-sm">Cancelled</a>
                <a href="orders.php" class="btn btn-outline-secondary btn-sm">All</a>
            </div>
        </div>

        <div class="table-container">
            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-bag" style="font-size: 3rem; color: #ddd;"></i>
                    <p class="mt-3">No orders found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Vendor</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></td>
                                    <td><?php echo htmlspecialchars($order['vendor_name'] ?? 'N/A'); ?></td>
                                    <td>PKR <?php echo number_format($order['net_amount']); ?></td>
                                    <td><?php echo strtoupper($order['payment_method']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
