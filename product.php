<?php
// product.php
session_start();
require_once 'config/database.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Get product details
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, u.full_name as vendor_name,
           u.id as vendor_id, vp.store_name, vp.is_verified
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN users u ON p.vendor_id = u.id
    LEFT JOIN vendor_profiles vp ON u.id = vp.user_id
    WHERE p.slug = ? AND p.is_active = 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit;
}

// Get product images
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC");
$stmt->execute([$product['id']]);
$images = $stmt->fetchAll();

if (empty($images)) {
    $images = [['image_url' => 'https://via.placeholder.com/600x600/eee/333?text=' . urlencode($product['name'])]];
}

// Get product attributes
$stmt = $pdo->prepare("SELECT * FROM product_attributes WHERE product_id = ?");
$stmt->execute([$product['id']]);
$attributes = $stmt->fetchAll();

// Get related products
$stmt = $pdo->prepare("
    SELECT p.*, pi.image_url
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
    LIMIT 8
");
$stmt->execute([$product['category_id'], $product['id']]);
$related_products = $stmt->fetchAll();

// Get reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.full_name 
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ? AND r.is_approved = 1
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$product['id']]);
$reviews = $stmt->fetchAll();

// Calculate rating
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(AVG(rating), 0) as avg_rating,
        COUNT(*) as total_reviews
    FROM reviews
    WHERE product_id = ? AND is_approved = 1
");
$stmt->execute([$product['id']]);
$rating_data = $stmt->fetch();

// Add to cart
$cart_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php?redirect=product&slug=' . $slug);
        exit;
    }
    
    $quantity = intval($_POST['quantity'] ?? 1);
    $user_id = $_SESSION['user_id'];
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO cart_items (user_id, product_id, quantity) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + ?
        ");
        $stmt->execute([$user_id, $product['id'], $quantity, $quantity]);
        $cart_message = 'Product added to cart!';
    } catch (Exception $e) {
        $cart_message = 'Failed to add to cart';
    }
}

$price = $product['discount_price'] ?? $product['price'];
$discount = $product['discount_price'] ? round((($product['price'] - $product['discount_price']) / $product['price']) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - MarketPK</title>
    <meta name="description" content="<?php echo htmlspecialchars(strip_tags($product['short_description'] ?? $product['description'])); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($product['name']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(strip_tags($product['short_description'] ?? $product['description'])); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($images[0]['image_url'] ?? ''); ?>">
    <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta name="twitter:card" content="summary_large_image">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f1f3f6;
            padding-top: 80px;
        }
        
        /* Daraz Style Colors */
        :root {
            --daraz-orange: #ff6a00;
            --daraz-blue: #0f1463;
            --daraz-light: #f5f5f5;
            --daraz-gray: #75757a;
            --daraz-border: #e2e2e2;
            --daraz-green: #00a651;
        }

        /* Breadcrumb */
        .breadcrumb-custom {
            background: transparent;
            padding: 10px 0;
            margin: 0;
            font-size: 0.85rem;
        }
        .breadcrumb-custom a {
            color: var(--daraz-gray);
            text-decoration: none;
        }
        .breadcrumb-custom a:hover {
            color: var(--daraz-orange);
        }
        .breadcrumb-custom .active {
            color: #333;
            font-weight: 500;
        }

        /* Product Main */
        .product-main {
            background: #fff;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }

        /* Product Gallery */
        .gallery-main {
            position: relative;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        .gallery-main img {
            width: 100%;
            height: 450px;
            object-fit: contain;
            transition: transform 0.3s ease;
            cursor: zoom-in;
        }
        .gallery-main img:hover {
            transform: scale(1.05);
        }
        .gallery-thumbs {
            display: flex;
            gap: 8px;
            margin-top: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
        }
        .gallery-thumbs img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 4px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .gallery-thumbs img:hover,
        .gallery-thumbs img.active {
            border-color: var(--daraz-orange);
        }

        /* Product Info */
        .product-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .product-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .product-rating .stars {
            color: #f5a623;
        }
        .product-rating .count {
            color: var(--daraz-gray);
            font-size: 0.9rem;
        }
        .product-rating .verified {
            color: var(--daraz-green);
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .product-price {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0 15px;
        }
        .product-price .current {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--daraz-orange);
        }
        .product-price .original {
            font-size: 1.1rem;
            color: var(--daraz-gray);
            text-decoration: line-through;
            margin-left: 12px;
        }
        .product-price .discount {
            background: #fce4d6;
            color: var(--daraz-orange);
            padding: 2px 10px;
            border-radius: 4px;
            font-weight: 600;
            margin-left: 10px;
        }

        .product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 10px 0;
            font-size: 0.9rem;
            color: var(--daraz-gray);
        }
        .product-meta i {
            color: var(--daraz-orange);
            margin-right: 5px;
        }

        /* Vendor Info */
        .vendor-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        .vendor-card .store-name {
            font-weight: 600;
            color: #333;
        }
        .vendor-card .verified {
            color: var(--daraz-green);
            font-size: 0.8rem;
        }
        .vendor-card .rating {
            color: #f5a623;
        }

        /* Add to Cart */
        .add-to-cart-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .add-to-cart-section .qty-selector {
            display: flex;
            align-items: center;
            border: 1px solid var(--daraz-border);
            border-radius: 4px;
            overflow: hidden;
        }
        .add-to-cart-section .qty-selector button {
            background: #f5f5f5;
            border: none;
            padding: 8px 16px;
            font-size: 1.2rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .add-to-cart-section .qty-selector button:hover {
            background: #e0e0e0;
        }
        .add-to-cart-section .qty-selector input {
            width: 50px;
            text-align: center;
            border: none;
            border-left: 1px solid var(--daraz-border);
            border-right: 1px solid var(--daraz-border);
            padding: 8px 0;
            font-size: 1rem;
        }
        .add-to-cart-section .qty-selector input:focus {
            outline: none;
        }
        .btn-add-cart {
            background: var(--daraz-orange);
            color: #fff;
            border: none;
            padding: 12px 40px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            flex: 1;
            max-width: 250px;
        }
        .btn-add-cart:hover {
            background: #e55d00;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 106, 0, 0.3);
        }
        .btn-add-cart i {
            margin-right: 8px;
        }
        .btn-wishlist {
            background: transparent;
            border: 1px solid var(--daraz-border);
            padding: 12px 18px;
            border-radius: 4px;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            color: var(--daraz-gray);
        }
        .btn-wishlist:hover {
            border-color: var(--daraz-orange);
            color: var(--daraz-orange);
        }
        .btn-wishlist.active {
            color: #e74c3c;
            border-color: #e74c3c;
        }

        /* Product Description */
        .product-description {
            padding: 15px 0;
        }
        .product-description h5 {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .product-description p {
            color: #555;
            line-height: 1.7;
        }

        /* Product Attributes */
        .attributes-table {
            width: 100%;
            font-size: 0.9rem;
        }
        .attributes-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        .attributes-table td:first-child {
            font-weight: 500;
            color: #555;
            width: 30%;
        }

        /* Reviews */
        .reviews-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .review-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 15px 0;
        }
        .review-item:last-child {
            border-bottom: none;
        }
        .review-item .user {
            font-weight: 600;
            color: #333;
        }
        .review-item .date {
            color: var(--daraz-gray);
            font-size: 0.8rem;
            margin-left: 10px;
        }
        .review-item .comment {
            color: #555;
            margin-top: 5px;
        }

        /* Related Products */
        .related-products {
            margin-top: 25px;
        }
        .related-products h5 {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        .related-product-card {
            background: #fff;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid transparent;
        }
        .related-product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: var(--daraz-orange);
        }
        .related-product-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
        }
        .related-product-card .name {
            font-size: 0.9rem;
            color: #333;
            margin: 8px 0 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 40px;
        }
        .related-product-card .price {
            font-weight: 700;
            color: var(--daraz-orange);
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            .product-main {
                padding: 15px;
                border-radius: 0;
            }
            .gallery-main img {
                height: 300px;
            }
            .gallery-thumbs img {
                width: 55px;
                height: 55px;
            }
            .product-title {
                font-size: 1.1rem;
            }
            .product-price .current {
                font-size: 1.4rem;
            }
            .btn-add-cart {
                max-width: 100%;
                padding: 12px 20px;
            }
            .add-to-cart-section {
                flex-direction: column;
                align-items: stretch;
            }
            .add-to-cart-section .qty-selector {
                width: 100%;
                justify-content: center;
            }
            .vendor-card {
                padding: 12px;
            }
            .related-product-card img {
                height: 120px;
            }
        }

        @media (max-width: 480px) {
            .gallery-main img {
                height: 250px;
            }
            .product-price .current {
                font-size: 1.2rem;
            }
            .product-meta {
                font-size: 0.8rem;
                gap: 10px;
            }
        }

        /* Toast Notification */
        .toast-custom {
            position: fixed;
            top: 90px;
            right: 20px;
            background: #fff;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 9999;
            border-left: 4px solid var(--daraz-orange);
            transform: translateX(120%);
            transition: transform 0.4s ease;
            max-width: 350px;
        }
        .toast-custom.show {
            transform: translateX(0);
        }
        .toast-custom .toast-body {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .toast-custom .toast-body i {
            color: var(--daraz-green);
            font-size: 1.5rem;
        }
        .toast-custom .toast-body span {
            font-size: 0.95rem;
            color: #333;
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <!-- Toast Notification -->
    <?php if ($cart_message): ?>
    <div class="toast-custom show" id="cartToast">
        <div class="toast-body">
            <i class="fas fa-check-circle"></i>
            <span><?php echo htmlspecialchars($cart_message); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="container py-3">

        <!-- Breadcrumb -->
        <nav class="breadcrumb-custom">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <span class="mx-2">/</span>
            <a href="category.php?slug=<?php echo $product['category_slug'] ?? 'all'; ?>"><?php echo htmlspecialchars($product['category_name'] ?? 'Products'); ?></a>
            <span class="mx-2">/</span>
            <span class="active"><?php echo htmlspecialchars(substr($product['name'], 0, 50)); ?></span>
        </nav>

        <!-- Product Main -->
        <div class="product-main row g-4">

            <!-- Gallery -->
            <div class="col-md-5">
                <div class="gallery-main">
                    <img src="<?php echo htmlspecialchars($images[0]['image_url'] ?? 'https://via.placeholder.com/600x600/eee/333?text=' . urlencode($product['name'])); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         id="mainImage">
                </div>
                <div class="gallery-thumbs">
                    <?php foreach ($images as $index => $img): ?>
                        <img src="<?php echo htmlspecialchars($img['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?> Image <?php echo $index + 1; ?>"
                             class="<?php echo $index === 0 ? 'active' : ''; ?>"
                             onclick="changeImage(this.src, this)">
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Info -->
            <div class="col-md-7">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

                <!-- Rating -->
                <div class="product-rating">
                    <span class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= floor($rating_data['avg_rating']) ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                    </span>
                    <span class="count"><?php echo number_format($rating_data['total_reviews']); ?> Reviews</span>
                    <span class="verified"><i class="fas fa-check-circle"></i> Verified</span>
                </div>

                <!-- Price -->
                <div class="product-price">
                    <span class="current">PKR <?php echo number_format($price); ?></span>
                    <?php if ($product['discount_price']): ?>
                        <span class="original">PKR <?php echo number_format($product['price']); ?></span>
                        <span class="discount">-<?php echo $discount; ?>%</span>
                    <?php endif; ?>
                </div>

                <!-- Meta -->
                <div class="product-meta">
                    <span><i class="fas fa-box"></i> In Stock: <?php echo $product['quantity']; ?></span>
                    <span><i class="fas fa-tag"></i> SKU: <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></span>
                    <span><i class="fas fa-store"></i> Category: <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                </div>

                <!-- Vendor -->
                <div class="vendor-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="store-name">
                                <i class="fas fa-store-alt"></i> <?php echo htmlspecialchars($product['store_name'] ?? $product['vendor_name']); ?>
                                <?php if ($product['is_verified']): ?>
                                    <span class="verified"><i class="fas fa-check-circle"></i> Verified Seller</span>
                                <?php endif; ?>
                            </div>
                            <div class="rating">
                                <i class="fas fa-star text-warning"></i> 
                                <?php echo number_format($rating_data['avg_rating'], 1); ?>
                                (<?php echo number_format($rating_data['total_reviews']); ?> reviews)
                            </div>
                        </div>
                        <a href="vendor_products.php?vendor=<?php echo $product['vendor_id']; ?>" class="btn btn-sm btn-outline-primary">
                            View Store
                        </a>
                    </div>
                </div>

                <!-- Add to Cart -->
                <form method="POST" class="add-to-cart-section">
                    <div class="qty-selector">
                        <button type="button" onclick="changeQty(-1)">−</button>
                        <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                        <button type="button" onclick="changeQty(1)">+</button>
                    </div>
                    <button type="submit" name="add_to_cart" class="btn-add-cart">
                        <i class="fas fa-cart-plus"></i> Add to Cart
                    </button>
                    <button type="button" class="btn-wishlist" onclick="toggleWishlist(<?php echo $product['id']; ?>, this)">
                        <i class="far fa-heart"></i>
                    </button>
                </form>

                <!-- Secure Delivery -->
                <div class="d-flex gap-4 mt-3 text-muted small flex-wrap">
                    <span><i class="fas fa-truck text-success"></i> Free Delivery</span>
                    <span><i class="fas fa-undo-alt text-success"></i> 7 Days Return</span>
                    <span><i class="fas fa-shield-alt text-success"></i> Secure Payment</span>
                </div>
            </div>
        </div>

        <!-- Description & Attributes -->
        <div class="row g-4 mt-2">
            <div class="col-lg-8">
                <div class="product-description bg-white p-4 rounded-3 shadow-sm">
                    <h5><i class="fas fa-file-alt text-primary"></i> Product Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
            </div>
            <div class="col-lg-4">
                <?php if (!empty($attributes)): ?>
                    <div class="bg-white p-4 rounded-3 shadow-sm">
                        <h5><i class="fas fa-tags text-primary"></i> Product Details</h5>
                        <table class="attributes-table">
                            <?php foreach ($attributes as $attr): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($attr['attribute_name']); ?></td>
                                    <td><?php echo htmlspecialchars($attr['attribute_value']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reviews -->
        <div class="reviews-section">
            <h5 class="mb-3"><i class="fas fa-star text-warning"></i> Customer Reviews</h5>
            <?php if (empty($reviews)): ?>
                <p class="text-muted">No reviews yet. Be the first to review this product!</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div>
                            <span class="user"><?php echo htmlspecialchars($review['full_name'] ?? 'Anonymous'); ?></span>
                            <span class="date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                            <span class="ms-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?> text-warning" style="font-size: 0.8rem;"></i>
                                <?php endfor; ?>
                            </span>
                        </div>
                        <div class="comment"><?php echo htmlspecialchars($review['comment']); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
            <div class="related-products">
                <h5 class="mb-3"><i class="fas fa-th-large text-primary"></i> Related Products</h5>
                <div class="row g-3">
                    <?php foreach ($related_products as $related): ?>
                        <div class="col-6 col-md-3">
                            <a href="product.php?slug=<?php echo $related['slug']; ?>" class="text-decoration-none">
                                <div class="related-product-card">
                                    <img src="<?php echo htmlspecialchars($related['image_url'] ?? 'https://via.placeholder.com/150x150/eee/333?text=No+Image'); ?>" 
                                         alt="<?php echo htmlspecialchars($related['name']); ?>" 
                                         loading="lazy">
                                    <div class="name"><?php echo htmlspecialchars(substr($related['name'], 0, 40)); ?></div>
                                    <div class="price">PKR <?php echo number_format($related['discount_price'] ?? $related['price']); ?></div>
                                    <?php if ($related['discount_price']): ?>
                                        <small class="text-muted text-decoration-line-through">PKR <?php echo number_format($related['price']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <?php include 'footer.php'; ?>

    <script>
        // Change main image
        function changeImage(src, el) {
            document.getElementById('mainImage').src = src;
            document.querySelectorAll('.gallery-thumbs img').forEach(img => img.classList.remove('active'));
            el.classList.add('active');
        }

        // Quantity selector
        function changeQty(delta) {
            const input = document.getElementById('qtyInput');
            let val = parseInt(input.value) + delta;
            const max = parseInt(input.max) || 999;
            if (val < 1) val = 1;
            if (val > max) val = max;
            input.value = val;
        }

        // Wishlist toggle
        function toggleWishlist(productId, btn) {
            const icon = btn.querySelector('i');
            if (icon.classList.contains('far')) {
                icon.classList.remove('far');
                icon.classList.add('fas');
                btn.classList.add('active');
                // Add to wishlist via AJAX
                fetch('wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=add&product_id=' + productId
                });
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
                btn.classList.remove('active');
                fetch('wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=remove&product_id=' + productId
                });
            }
        }

        // Hide toast after 3 seconds
        setTimeout(() => {
            document.querySelector('.toast-custom')?.classList.remove('show');
        }, 3000);
    </script>

</body>
</html>
