<?php
// admin/vendors.php
session_start();
require_once '../config/database.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle vendor actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $vendor_id = intval($_POST['vendor_id'] ?? 0);
    
    if ($vendor_id > 0) {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE users SET vendor_status = 'approved' WHERE id = ? AND user_type = 'vendor'");
                $stmt->execute([$vendor_id]);
                $_SESSION['message'] = 'Vendor approved successfully!';
                break;
            case 'suspend':
                $stmt = $pdo->prepare("UPDATE users SET vendor_status = 'suspended' WHERE id = ? AND user_type = 'vendor'");
                $stmt->execute([$vendor_id]);
                $_SESSION['message'] = 'Vendor suspended!';
                break;
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND user_type = 'vendor'");
                $stmt->execute([$vendor_id]);
                $_SESSION['message'] = 'Vendor deleted!';
                break;
        }
    }
    header('Location: vendors.php');
    exit;
}

// Get vendors
$status_filter = $_GET['status'] ?? '';
$sql = "SELECT u.*, vp.store_name, vp.store_description, vp.is_verified, vp.total_sales 
        FROM users u 
        LEFT JOIN vendor_profiles vp ON u.id = vp.user_id 
        WHERE u.user_type = 'vendor'";

if ($status_filter) {
    $sql .= " AND u.vendor_status = ?";
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($status_filter) {
    $stmt->execute([$status_filter]);
} else {
    $stmt->execute();
}
$vendors = $stmt->fetchAll();

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management - Admin</title>
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
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-badge.approved { background: #d4edda; color: #155724; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.suspended { background: #f8d7da; color: #721c24; }
        .table-container {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .btn-approve { background: #28a745; color: #fff; }
        .btn-approve:hover { background: #218838; color: #fff; }
        .btn-suspend { background: #ffc107; color: #fff; }
        .btn-suspend:hover { background: #e0a800; color: #fff; }
        .btn-delete { background: #dc3545; color: #fff; }
        .btn-delete:hover { background: #c82333; color: #fff; }
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
            <a class="nav-item active" href="vendors.php"><i class="fas fa-store"></i> Vendors</a>
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
            <h4>Vendor Management</h4>
            <div>
                <a href="?status=pending" class="btn btn-outline-warning btn-sm">Pending</a>
                <a href="?status=approved" class="btn btn-outline-success btn-sm">Approved</a>
                <a href="?status=suspended" class="btn btn-outline-danger btn-sm">Suspended</a>
                <a href="vendors.php" class="btn btn-outline-secondary btn-sm">All</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (empty($vendors)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-store" style="font-size: 3rem; color: #ddd;"></i>
                    <p class="mt-3">No vendors found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Store</th>
                                <th>Vendor</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Verified</th>
                                <th>Sales</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vendors as $vendor): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($vendor['store_name'] ?? $vendor['username']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($vendor['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($vendor['email']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $vendor['vendor_status']; ?>">
                                            <?php echo ucfirst($vendor['vendor_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($vendor['is_verified']): ?>
                                            <i class="fas fa-check-circle text-success"></i> Verified
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger"></i> Not Verified
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($vendor['total_sales'] ?? 0); ?></td>
                                    <td>
                                        <?php if ($vendor['vendor_status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-approve">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($vendor['vendor_status'] === 'approved'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="suspend">
                                                <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-suspend" onclick="return confirm('Suspend this vendor?')">
                                                    <i class="fas fa-pause"></i> Suspend
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($vendor['vendor_status'] === 'suspended'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-approve">
                                                    <i class="fas fa-undo"></i> Reactivate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-delete" onclick="return confirm('Delete this vendor permanently?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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
