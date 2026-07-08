<?php
// cart.php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    $variation_id = intval($_POST['variation_id'] ?? 0);

    switch ($action) {
        case 'add':
            if ($product_id > 0 && $quantity > 0) {
                // Check if item already in cart
                $stmt = $pdo->prepare("
                    SELECT id, quantity FROM cart_items 
                    WHERE user_id = ? AND product_id = ? AND (variation_id = ? OR (variation_id IS NULL AND ? IS NULL))
                ");
                $stmt->execute([$user_id, $product_id, $variation_id, $variation_id]);
                $existing = $stmt->fetch();

                if ($existing) {
                    // Update quantity
                    $new_qty = $existing['quantity'] + $quantity;
                    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
                    $stmt->execute([$new_qty, $existing['id']]);
                } else {
                    // Add new item
                    $stmt = $pdo->prepare("
                        INSERT INTO cart_items (user_id, product_id, variation_id, quantity) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$user_id, $product_id, $variation_id, $quantity]);
                }
                
                $_SESSION['cart_message'] = 'Product added to cart!';
            }
            break;

        case 'update':
            $cart_id = intval($_POST['cart_id'] ?? 0);
            if ($cart_id > 0 && $quantity > 0) {
                $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $cart_id, $user_id]);
            }
            break;

        case 'remove':
            $cart_id = intval($_POST['cart_id'] ?? 0);
            if ($cart_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
                $stmt->execute([$cart_id, $user_id]);
            }
            break;

        case 'clear':
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([$user_id]);
            break;
    }

    // Redirect to prevent form resubmission
    header('Location: cart.php');
    exit;
}

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

// Calculate total
foreach ($cart_items as $item) {
    $item_price = $item['discount_price'] ?? $item['price'];
    $total += $item_price * $item['quantity'];
}

// Count cart items
$cart_count = count($cart_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - MarketPK</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 80px;
        }
        .cart-item {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .cart-item .product-title {
            font-weight: 600;
            color: #2c2b2b;
        }
        .cart-item .product-title:hover {
            color: #FF636B;
        }
        .cart-item .vendor-name {
            color: #888;
            font-size: 0.9rem;
        }
        .cart-item .price {
            font-weight: 700;
            color: #FF636B;
            font-size: 1.1rem;
        }
        .cart-item .quantity-input {
            width: 60px;
            text-align: center;
        }
        .cart-summary {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 100px;
        }
        .cart-summary .total {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c2b2b;
        }
        .cart-summary .btn-checkout {
            background: #FF636B;
            color: #fff;
            width: 100%;
            padding: 12px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
        }
        .cart-summary .btn-checkout:hover {
            background: #0066CB;
        }
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
        }
        .empty-cart h3 {
            margin-top: 20px;
            color: #2c2b2b;
        }
        .qty-btn {
            background: #f0f0f0;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .qty-btn:hover {
            background: #FF636B;
            color: #fff;
        }
    </style>
</head>
<body>

    <!-- Include Header -->
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="row g-4">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <h4 class="mb-3">Shopping Cart (<?php echo $cart_count; ?> items)</h4>
                
                <?php if (isset($_SESSION['cart_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                        echo htmlspecialchars($_SESSION['cart_message']);
                        unset($_SESSION['cart_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($cart_items)): ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Your cart is empty</h3>
                        <p class="text-muted">Browse our products and add items to your cart</p>
                        <a href="index.php" class="btn btn-primary mt-3">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($cart_items as $item): 
                        $item_price = $item['discount_price'] ?? $item['price'];
                        $subtotal = $item_price * $item['quantity'];
                    ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://via.placeholder.com/100'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <a href="product.php?slug=<?php echo $item['slug']; ?>" class="product-title">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </a>
                                    <div class="vendor-name">
                                        <i class="fas fa-store"></i> By <?php echo htmlspecialchars($item['vendor_name'] ?? 'Unknown Vendor'); ?>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="price">PKR <?php echo number_format($item_price); ?></div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex align-items-center">
                                        <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                                        <input type="number" class="form-control form-control-sm quantity-input" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               id="qty_<?php echo $item['id']; ?>"
                                               onchange="updateQuantity(<?php echo $item['id']; ?>, 0, this.value)">
                                        <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                                    </div>
                                </div>
                                <div class="col-md-2 text-end">
                                    <div class="fw-bold">PKR <?php echo number_format($subtotal); ?></div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-link text-danger p-0" onclick="return confirm('Remove this item?')">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-3">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Clear all items from cart?')">
                                <i class="fas fa-trash-alt"></i> Clear Cart
                            </button>
                        </form>
                        <a href="index.php" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cart Summary -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h5 class="mb-3">Order Summary</h5>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal (<?php echo $cart_count; ?> items)</span>
                        <span>PKR <?php echo number_format($total); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span>PKR <?php echo $total > 2000 ? '0' : '200'; ?></span>
                    </div>
                    
                    <?php if ($total > 2000): ?>
                        <div class="text-success small mb-2">
                            <i class="fas fa-truck"></i> Free shipping on orders over PKR 2000
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold">Total</span>
                        <span class="total">PKR <?php echo number_format($total + ($total > 2000 ? 0 : 200)); ?></span>
                    </div>
                    
                    <?php if (!empty($cart_items)): ?>
                        <a href="checkout.php" class="btn btn-checkout">
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </a>
                    <?php else: ?>
                        <button class="btn btn-checkout" disabled>Cart is empty</button>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">Secure checkout. 100% protected.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateQuantity(cartId, change, newValue) {
            let quantity;
            if (newValue !== undefined) {
                quantity = parseInt(newValue);
            } else {
                const input = document.getElementById('qty_' + cartId);
                quantity = parseInt(input.value) + change;
            }
            
            if (quantity < 1) {
                if (confirm('Remove this item from cart?')) {
                    removeItem(cartId);
                }
                return;
            }
            
            // Update via AJAX
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('cart_id', cartId);
            formData.append('quantity', quantity);
            
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                location.reload();
            })
            .catch(error => console.error('Error:', error));
        }
        
        function removeItem(cartId) {
            const formData = new FormData();
            formData.append('action', 'remove');
            formData.append('cart_id', cartId);
            
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(() => location.reload())
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
