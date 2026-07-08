<?php
// orders.php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get orders
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name as vendor_name 
    FROM orders o
    LEFT JOIN users u ON o.vendor_id = u.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Get order items for each order
foreach ($orders as &$order) {
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, pi.image_url
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $order['items'] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - MarketPK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 80px;
        }
        .order-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .order-number {
            font-weight: 700;
            color: #2c2b2b;
        }
        .order-date {
            color: #888;
            font-size: 0.9rem;
        }
        .order-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .order-status.pending { background: #fff3cd; color: #856404; }
        .order-status.processing { background: #cce5ff; color: #004085; }
        .order-status.shipped { background: #cce5ff; color: #004085; }
        .order-status.delivered { background: #d4edda; color: #155724; }
        .order-status.cancelled { background: #f8d7da; color: #721c24; }
        .order-status.returned { background: #f8d7da; color: #721c24; }
        .order-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .order-item .details {
            flex: 1;
            padding: 0 15px;
        }
        .order-item .details .name {
            font-weight: 600;
            color: #2c2b2b;
        }
        .order-item .details .qty {
            font-size: 0.85rem;
            color: #888;
        }
        .order-item .price {
            font-weight: 600;
            color: #FF636B;
        }
        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            margin-top: 15px;
        }
        .order-total {
            font-weight: 700;
            font-size: 1.1rem;
            color: #2c2b2b;
        }
        .order-total span {
            color: #FF636B;
        }
        .btn-track {
            background: #0066CB;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            font-weight: 600;
        }
        .btn-track:hover {
            background: #0044aa;
            color: #fff;
        }
        .empty-orders {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-orders i {
            font-size: 4rem;
            color: #ddd;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">
        <h4 class="mb-4">My Orders</h4>

        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <i class="fas fa-shopping-bag"></i>
                <h3>No orders yet</h3>
                <p class="text-muted">Start shopping to see your orders here</p>
                <a href="index.php" class="btn btn-primary mt-3">Start Shopping</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <!-- Order Header -->
                    <div class="order-header">
                        <div>
                            <div class="order-number">Order #<?php echo htmlspecialchars($order['order_number']); ?></div>
                            <div class="order-date">
                                <i class="far fa-calendar-alt"></i> 
                                <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <div>
                            <span class="order-status <?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="mt-3">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://via.placeholder.com/50'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                <div class="details">
                                    <div class="name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="qty">Qty: <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="price">PKR <?php echo number_format($item['total_amount']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Footer -->
                    <div class="order-footer">
                        <div>
                            <div class="text-muted small">Vendor: <?php echo htmlspecialchars($order['vendor_name'] ?? 'Unknown'); ?></div>
                            <div class="text-muted small">Payment: <?php echo strtoupper($order['payment_method']); ?></div>
                        </div>
                        <div>
                            <div class="order-total">Total: <span>PKR <?php echo number_format($order['net_amount']); ?></span></div>
                            <?php if ($order['status'] === 'shipped' || $order['status'] === 'processing'): ?>
                                <button class="btn-track mt-2">Track Order</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
