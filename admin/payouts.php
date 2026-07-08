<?php
// admin/payouts.php
session_start();
require_once '../config/database.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle payout actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $payout_id = intval($_POST['payout_id'] ?? 0);
    
    if ($action === 'mark_paid' && $payout_id > 0) {
        $stmt = $pdo->prepare("UPDATE vendor_payouts SET status = 'paid', paid_at = NOW() WHERE id = ?");
        $stmt->execute([$payout_id]);
        $_SESSION['message'] = 'Payout marked as paid!';
    }
    header('Location: payouts.php');
    exit;
}

// Get payouts
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT vp.*, u.full_name as vendor_name, o.order_number 
        FROM vendor_payouts vp
        LEFT JOIN users u ON vp.vendor_id = u.id
        LEFT JOIN orders o ON vp.order_id = o.id";

if ($status_filter) {
    $sql .= " WHERE vp.status = ?";
}

$sql .= " ORDER BY vp.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($status_filter) {
    $stmt->execute([$status_filter]);
} else {
    $stmt->execute();
}
$payouts = $stmt->fetchAll();

// Get summary stats
$stmt = $pdo->query("
    SELECT 
        COALESCE(SUM(CASE WHEN status = 'pending' THEN payout_amount ELSE 0 END), 0) as pending_total,
        COALESCE(SUM(CASE WHEN status = 'paid' THEN payout_amount ELSE 0 END), 0) as paid_total,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count
    FROM vendor_payouts
");
$summary = $stmt->fetch();

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout Management - Admin</title>
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
            text-align: center;
        }
        .stat-card .number {
            font-size: 1.8rem;
            font-weight: 700;
        }
        .stat-card .label {
            color: #888;
            font-size: 0.9rem;
        }
        .table-container {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.paid { background: #d4edda; color: #155724; }
        .status-badge.failed { background: #f8d7da; color: #721c24; }
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
            <a class="nav-item active" href="payouts.php"><i class="fas fa-wallet"></i> Payouts</a>
            <a class="nav-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h4 class="mb-4">Payout Management</h4>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="number text-warning">PKR <?php echo number_format($summary['pending_total']); ?></div>
                    <div class="label">Pending Payouts (<?php echo $summary['pending_count']; ?>)</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="number text-success">PKR <?php echo number_format($summary['paid_total']); ?></div>
                    <div class="label">Paid Payouts</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="number text-primary">PKR <?php echo number_format($summary['pending_total'] + $summary['paid_total']); ?></div>
                    <div class="label">Total Payouts</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="?status=pending" class="btn btn-sm btn-outline-warning">Pending</a>
                <a href="?status=paid" class="btn btn-sm btn-outline-success">Paid</a>
                <a href="payouts.php" class="btn btn-sm btn-outline-secondary">All</a>
            </div>
        </div>

        <div class="table-container">
            <?php if (empty($payouts)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-wallet" style="font-size: 3rem; color: #ddd;"></i>
                    <p class="mt-3">No payouts found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Payout ID</th>
                                <th>Vendor</th>
                                <th>Order #</th>
                                <th>Commission</th>
                                <th>Payout Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payouts as $payout): ?>
                                <tr>
                                    <td>#<?php echo $payout['id']; ?></td>
                                    <td><?php echo htmlspecialchars($payout['vendor_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($payout['order_number'] ?? 'N/A'); ?></td>
                                    <td>PKR <?php echo number_format($payout['commission_amount']); ?></td>
                                    <td><strong>PKR <?php echo number_format($payout['payout_amount']); ?></strong></td>
                                    <td>
                                        <span class="status-badge <?php echo $payout['status']; ?>">
                                            <?php echo ucfirst($payout['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($payout['created_at'])); ?></td>
                                    <td>
                                        <?php if ($payout['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="mark_paid">
                                                <input type="hidden" name="payout_id" value="<?php echo $payout['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Mark this payout as paid?')">
                                                    <i class="fas fa-check"></i> Pay
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-check-circle text-success"></i> Completed</span>
                                        <?php endif; ?>
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
