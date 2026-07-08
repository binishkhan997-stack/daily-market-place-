<?php
// admin/products.php
session_start();
require_once '../config/database.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    
    switch ($action) {
        case 'toggle_active':
            if ($product_id > 0) {
                $stmt = $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$product_id]);
                $_SESSION['message'] = 'Product status updated!';
            }
            break;
        case 'delete':
            if ($product_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $_SESSION['message'] = 'Product deleted!';
            }
            break;
    }
    header('Location: products.php');
    exit;
}

// Get products
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name, u.full_name as vendor_name,
           (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.vendor_id = u.id
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll();

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin</title>
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
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-badge.active { background: #d4edda; color: #155724; }
        .status-badge.inactive { background: #f8d7da; color: #721c24; }
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
            <a class="nav-item active" href="products.php"><i class="fas fa-box"></i> Products</a>
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
            <h4>Product Management</h4>
            <a href="#" class="btn btn-primary" onclick="alert('Product add form would go here')">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box" style="font-size: 3rem; color: #ddd;"></i>
                    <p class="mt-3">No products found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Vendor</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/50'); ?>" class="product-img">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['vendor_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td>PKR <?php echo number_format($product['price']); ?></td>
                                    <td><?php echo $product['quantity']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $product['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-<?php echo $product['is_active'] ? 'warning' : 'success'; ?>">
                                                <i class="fas <?php echo $product['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')">
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
