<?php
// checkout.php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get cart items
$cart_items = [];
$total = 0;

$stmt = $pdo->prepare("
    SELECT 
        c.*,
        p.name as product_name,
        p.slug,
        p.price,
        p.discount_price,
        p.vendor_id,
        p.quantity as stock_quantity,
        pi.image_url,
        u.full_name as vendor_name
    FROM cart_items c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN users u ON p.vendor_id = u.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// Calculate total
foreach ($cart_items as $item) {
    $item_price = $item['discount_price'] ?? $item['price'];
    $total += $item_price * $item['quantity'];
}

$shipping = $total > 2000 ? 0 : 200;
$grand_total = $total + $shipping;

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $billing_address = trim($_POST['billing_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $order_notes = trim($_POST['order_notes'] ?? '');

    if (empty($shipping_address)) {
        $error = 'Please enter shipping address';
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Create orders for each vendor
            $vendor_orders = [];
            foreach ($cart_items as $item) {
                $vendor_id = $item['vendor_id'];
                if (!isset($vendor_orders[$vendor_id])) {
                    $vendor_orders[$vendor_id] = [];
                }
                $vendor_orders[$vendor_id][] = $item;
            }

            foreach ($vendor_orders as $vendor_id => $items) {
                $vendor_total = 0;
                foreach ($items as $item) {
                    $item_price = $item['discount_price'] ?? $item['price'];
                    $vendor_total += $item_price * $item['quantity'];
                }

                $vendor_shipping = $vendor_total > 2000 ? 0 : 200;
                $vendor_grand_total = $vendor_total + $vendor_shipping;

                // Generate order number
                $order_number = 'ORD-' . date('Ymd') . '-' . rand(10000, 99999);

                // Insert order
                $stmt = $pdo->prepare("
                    INSERT INTO orders (
                        user_id, vendor_id, order_number, total_amount, 
                        discount_amount, shipping_charge, net_amount,
                        status, payment_method, shipping_address, billing_address, order_notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id,
                    $vendor_id,
                    $order_number,
                    $vendor_total,
                    0,
                    $vendor_shipping,
                    $vendor_grand_total,
                    $payment_method,
                    $shipping_address,
                    $billing_address ?: $shipping_address,
                    $order_notes
                ]);

                $order_id = $pdo->lastInsertId();

                // Insert order items
                foreach ($items as $item) {
                    $item_price = $item['discount_price'] ?? $item['price'];
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, price, total_amount)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $order_id,
                        $item['product_id'],
                        $item['quantity'],
                        $item_price,
                        $item_price * $item['quantity']
                    ]);

                    // Update product stock
                    $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }

                // Calculate commission (10%)
                $commission = $vendor_grand_total * 0.10;
                $payout = $vendor_grand_total - $commission;

                // Insert vendor payout
                $stmt = $pdo->prepare("
                    INSERT INTO vendor_payouts (vendor_id, order_id, commission_amount, payout_amount, status)
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$vendor_id, $order_id, $commission, $payout]);
            }

            // Clear cart
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $pdo->commit();
            $success = true;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Order failed: ' . $e->getMessage();
            error_log("Checkout error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MarketPK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 80px;
        }
        .checkout-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .checkout-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .checkout-card h5 {
            color: #2c2b2b;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .order-summary-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .order-summary-item:last-child {
            border-bottom: none;
        }
        .order-summary-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        .order-summary-item .details {
            flex: 1;
            padding: 0 15px;
        }
        .order-summary-item .details .name {
            font-weight: 600;
            color: #2c2b2b;
        }
        .order-summary-item .details .vendor {
            font-size: 0.85rem;
            color: #888;
        }
        .order-summary-item .price {
            font-weight: 700;
            color: #FF636B;
        }
        .summary-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: #2c2b2b;
        }
        .btn-checkout {
            background: #FF636B;
            color: #fff;
            width: 100%;
            padding: 14px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
        }
        .btn-checkout:hover {
            background: #0066CB;
            color: #fff;
        }
        .btn-checkout:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .form-control:focus, .form-select:focus {
            border-color: #FF636B;
            box-shadow: 0 0 0 0.2rem rgba(255, 99, 107, 0.25);
        }
        .payment-option {
            padding: 12px 15px;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 8px;
        }
        .payment-option:hover {
            border-color: #FF636B;
        }
        .payment-option.selected {
            border-color: #FF636B;
            background: rgba(255, 99, 107, 0.05);
        }
        .payment-option input[type="radio"] {
            margin-right: 10px;
        }
        .success-page {
            text-align: center;
            padding: 40px 20px;
        }
        .success-page i {
            font-size: 4rem;
            color: #28a745;
        }
        .success-page h3 {
            margin-top: 20px;
            color: #2c2b2b;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container checkout-container">

        <?php if ($success): ?>
            <div class="checkout-card text-center">
                <div class="success-page">
                    <i class="fas fa-check-circle"></i>
                    <h3>Order Placed Successfully!</h3>
                    <p class="text-muted">Thank you for your order. You will receive a confirmation email shortly.</p>
                    <div class="mt-3">
                        <a href="orders.php" class="btn btn-primary">View My Orders</a>
                        <a href="index.php" class="btn btn-outline-secondary">Continue Shopping</a>
                    </div>
                </div>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>

            <div class="row g-4">
                <!-- Order Summary -->
                <div class="col-lg-7">
                    <div class="checkout-card">
                        <h5><i class="fas fa-shopping-bag"></i> Order Summary</h5>
                        <?php foreach ($cart_items as $item): 
                            $item_price = $item['discount_price'] ?? $item['price'];
                        ?>
                            <div class="order-summary-item">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://via.placeholder.com/60'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                <div class="details">
                                    <div class="name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="vendor"><i class="fas fa-store"></i> <?php echo htmlspecialchars($item['vendor_name'] ?? 'Unknown Vendor'); ?></div>
                                    <div class="text-muted small">Qty: <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="price">PKR <?php echo number_format($item_price * $item['quantity']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Shipping Address -->
                    <div class="checkout-card">
                        <h5><i class="fas fa-map-marker-alt"></i> Shipping Address</h5>
                        <form method="POST" id="checkoutForm">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Shipping Address *</label>
                                <textarea class="form-control" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Billing Address (Optional)</label>
                                <textarea class="form-control" name="billing_address" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Order Notes (Optional)</label>
                                <textarea class="form-control" name="order_notes" rows="2" placeholder="Any special instructions for the vendor..."></textarea>
                            </div>
                    </div>
                </div>

                <!-- Checkout Summary -->
                <div class="col-lg-5">
                    <div class="checkout-card">
                        <h5><i class="fas fa-credit-card"></i> Payment Method</h5>
                        
                        <div class="payment-option selected">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <label class="fw-bold">Cash on Delivery</label>
                            <p class="small text-muted mb-0">Pay when you receive your order</p>
                        </div>
                        
                        <div class="payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer">
                            <label class="fw-bold">Bank Transfer</label>
                            <p class="small text-muted mb-0">Pay via bank transfer</p>
                        </div>
                        
                        <div class="payment-option">
                            <input type="radio" name="payment_method" value="mobile_wallet">
                            <label class="fw-bold">Mobile Wallet</label>
                            <p class="small text-muted mb-0">JazzCash, EasyPaisa</p>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>PKR <?php echo number_format($total); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span><?php echo $shipping > 0 ? 'PKR ' . number_format($shipping) : 'Free'; ?></span>
                        </div>
                        <?php if ($total > 2000): ?>
                            <div class="text-success small mb-2">
                                <i class="fas fa-truck"></i> Free shipping applied!
                            </div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-bold">Total</span>
                            <span class="summary-total">PKR <?php echo number_format($grand_total); ?></span>
                        </div>

                        <button type="submit" class="btn-checkout" id="placeOrderBtn">
                            <i class="fas fa-lock"></i> Place Order
                        </button>

                        <div class="text-center mt-3">
                            <small class="text-muted">By placing an order, you agree to our Terms & Conditions</small>
                        </div>
                    </div>
                </div>
            </div>

            </form>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Payment method selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                const radio = this.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            });
        });

        // Prevent double submission
        document.getElementById('checkoutForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('placeOrderBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        });
    </script>
</body>
</html>
