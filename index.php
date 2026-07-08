<?php
// index.php
session_start();
require_once 'config/database.php';

// Get featured products
$stmt = $pdo->query("
    SELECT p.*, pi.image_url 
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE p.is_featured = 1 AND p.is_active = 1
    LIMIT 8
");
$featured_products = $stmt->fetchAll();

// Get best selling products
$stmt = $pdo->query("
    SELECT p.*, pi.image_url 
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE p.is_active = 1
    ORDER BY p.total_orders DESC, p.views DESC
    LIMIT 12
");
$best_sellers = $stmt->fetchAll();

// Get new arrivals
$stmt = $pdo->query("
    SELECT p.*, pi.image_url 
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE p.is_active = 1
    ORDER BY p.created_at DESC
    LIMIT 12
");
$new_arrivals = $stmt->fetchAll();

// Get categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name LIMIT 8");
$categories = $stmt->fetchAll();

// Get featured vendors
$stmt = $pdo->query("
    SELECT u.id, u.full_name, vp.store_name, vp.store_logo, vp.is_verified, vp.rating
    FROM users u
    JOIN vendor_profiles vp ON u.id = vp.user_id
    WHERE u.vendor_status = 'approved'
    ORDER BY vp.rating DESC
    LIMIT 8
");
$vendors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketPK - Pakistan's Best Online Shopping Marketplace</title>
    <meta name="description" content="Shop online at MarketPK for the best deals on electronics, fashion, home & kitchen, and more. Free delivery on orders over PKR 2000.">
    <meta property="og:title" content="MarketPK - Pakistan's Best Online Shopping Marketplace">
    <meta property="og:description" content="Shop online at MarketPK for the best deals on electronics, fashion, home & kitchen, and more.">
    <meta property="og:image" content="https://marketpk.com/og-image.jpg">
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
        :root {
            --daraz-orange: #ff6a00;
            --daraz-blue: #0f1463;
            --daraz-gray: #75757a;
            --daraz-border: #e2e2e2;
            --daraz-green: #00a651;
        }

        /* Hero Slider */
        .hero-slider {
            background: linear-gradient(135deg, var(--daraz-blue), #1a237e);
            border-radius: 12px;
            padding: 40px 50px;
            margin-bottom: 25px;
            color: #fff;
            position: relative;
            overflow: hidden;
            min-height: 320px;
        }
        .hero-slider::before {
            content: '';
            position: absolute;
            right: -50px;
            top: -50px;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }
        .hero-slider::after {
            content: '';
            position: absolute;
            right: 100px;
            bottom: -80px;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
        }
        .hero-slider h1 {
            font-size: 2.8rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .hero-slider h1 span {
            color: var(--daraz-orange);
        }
        .hero-slider p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 15px 0 25px;
            max-width: 500px;
        }
        .hero-slider .btn-shop {
            background: var(--daraz-orange);
            color: #fff;
            padding: 14px 40px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-block;
        }
        .hero-slider .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 106, 0, 0.4);
            color: #fff;
        }
        .hero-slider .hero-image {
            position: absolute;
            right: 60px;
            top: 50%;
            transform: translateY(-50%);
            max-width: 300px;
        }
        @media (max-width: 768px) {
            .hero-slider {
                padding: 25px;
                min-height: auto;
                border-radius: 0;
            }
            .hero-slider h1 {
                font-size: 1.8rem;
            }
            .hero-slider p {
                font-size: 0.95rem;
            }
            .hero-slider .hero-image {
                display: none;
            }
        }

        /* Flash Deals */
        .flash-deals {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .flash-deals .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .flash-deals .header h5 {
            font-weight: 700;
            color: var(--daraz-orange);
            margin: 0;
        }
        .flash-deals .header h5 i {
            margin-right: 8px;
        }
        .flash-deals .timer {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .flash-deals .timer .time-box {
            background: #333;
            color: #fff;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .flash-deals .timer .time-box span {
            font-weight: 300;
            font-size: 0.7rem;
        }

        /* Category Cards */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 10px;
            margin-bottom: 25px;
        }
        .category-card {
            background: #fff;
            border-radius: 8px;
            padding: 15px 10px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .category-card i {
            font-size: 2rem;
            color: var(--daraz-orange);
        }
        .category-card .name {
            font-size: 0.75rem;
            margin-top: 6px;
            display: block;
        }
        @media (max-width: 768px) {
            .category-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 8px;
            }
            .category-card {
                padding: 10px 5px;
            }
            .category-card i {
                font-size: 1.5rem;
            }
            .category-card .name {
                font-size: 0.65rem;
            }
        }
        @media (max-width: 480px) {
            .category-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 0 5px;
        }
        .section-header h5 {
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        .section-header a {
            color: var(--daraz-orange);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .section-header a:hover {
            text-decoration: underline;
        }

        /* Product Cards */
        .product-card {
            background: #fff;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            height: 100%;
            position: relative;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            border-color: var(--daraz-orange);
        }
        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 4px;
        }
        .product-card .name {
            font-size: 0.85rem;
            color: #333;
            margin: 8px 0 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 40px;
        }
        .product-card .name a {
            color: #333;
            text-decoration: none;
        }
        .product-card .name a:hover {
            color: var(--daraz-orange);
        }
        .product-card .price {
            font-weight: 700;
            color: var(--daraz-orange);
            font-size: 1rem;
        }
        .product-card .original-price {
            font-size: 0.8rem;
            color: var(--daraz-gray);
            text-decoration: line-through;
            margin-left: 5px;
        }
        .product-card .discount-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #fce4d6;
            color: var(--daraz-orange);
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .product-card .rating {
            font-size: 0.75rem;
            color: #f5a623;
        }
        .product-card .sold {
            font-size: 0.7rem;
            color: var(--daraz-gray);
        }

        @media (max-width: 768px) {
            .product-card img {
                height: 140px;
            }
            .product-card .name {
                font-size: 0.75rem;
                height: 35px;
            }
            .product-card .price {
                font-size: 0.85rem;
            }
        }

        /* Vendor Showcase */
        .vendor-showcase {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .vendor-card {
            text-align: center;
            padding: 15px;
            transition: all 0.3s ease;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
        }
        .vendor-card:hover {
            background: #f8f9fa;
        }
        .vendor-card .avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: var(--daraz-orange);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0 auto 10px;
        }
        .vendor-card .store-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .vendor-card .verified {
            color: var(--daraz-green);
            font-size: 0.7rem;
        }
        .vendor-card .rating {
            font-size: 0.8rem;
            color: #f5a623;
        }

        /* App Download Banner */
        .app-banner {
            background: linear-gradient(135deg, #0f1463, #1a237e);
            border-radius: 12px;
            padding: 30px 40px;
            color: #fff;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .app-banner h5 {
            font-weight: 700;
        }
        .app-banner p {
            opacity: 0.8;
            margin: 0;
        }
        .app-banner .btn-download {
            background: var(--daraz-orange);
            color: #fff;
            padding: 10px 30px;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .app-banner .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 106, 0, 0.4);
            color: #fff;
        }
        @media (max-width: 768px) {
            .app-banner {
                flex-direction: column;
                text-align: center;
                gap: 15px;
                border-radius: 0;
                padding: 20px;
            }
            .app-banner .btn-download {
                width: 100%;
                text-align: center;
            }
        }

        /* Responsive container */
        @media (max-width: 768px) {
            .container {
                padding-left: 0;
                padding-right: 0;
            }
            .flash-deals,
            .vendor-showcase {
                border-radius: 0;
            }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container">

        <!-- Hero Slider -->
        <div class="hero-slider">
            <div>
                <h1>Shop to Get <br><span>What You Love</span></h1>
                <p>A revolution in recognition. Get the best deals from top vendors across Pakistan.</p>
                <a href="category.php?slug=all" class="btn-shop">
                    Shop Now <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="hero-image">
                <img src="https://via.placeholder.com/300x250/fff/ff6a00?text=MarketPK" alt="Shopping" class="img-fluid">
            </div>
        </div>

        <!-- Categories -->
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="category.php?slug=<?php echo $cat['slug']; ?>" class="category-card">
                    <i class="fas fa-<?php echo $cat['icon_class'] ?? 'tag'; ?>"></i>
                    <span class="name"><?php echo htmlspecialchars($cat['name']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Flash Deals -->
        <div class="flash-deals">
            <div class="header">
                <h5><i class="fas fa-bolt"></i> Flash Deals</h5>
                <div class="timer">
                    <span class="time-box">02 <span>Hours</span></span>
                    <span class="time-box">45 <span>Mins</span></span>
                    <span class="time-box">30 <span>Secs</span></span>
                </div>
            </div>
            <div class="row g-3">
                <?php 
                $flash_products = array_slice($best_sellers, 0, 6);
                foreach ($flash_products as $product): 
                    $price = $product['discount_price'] ?? $product['price'];
                    $discount = $product['discount_price'] ? round((($product['price'] - $product['discount_price']) / $product['price']) * 100) : 0;
                ?>
                    <div class="col-6 col-md-2">
                        <div class="product-card">
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                            <a href="product.php?slug=<?php echo $product['slug']; ?>">
                                <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/200x180/eee/333?text=No+Image'); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     loading="lazy">
                            </a>
                            <div class="name">
                                <a href="product.php?slug=<?php echo $product['slug']; ?>">
                                    <?php echo htmlspecialchars(substr($product['name'], 0, 40)); ?>
                                </a>
                            </div>
                            <div class="price">PKR <?php echo number_format($price); ?></div>
                            <?php if ($product['discount_price']): ?>
                                <div class="original-price">PKR <?php echo number_format($product['price']); ?></div>
                            <?php endif; ?>
                            <div class="sold">🔥 <?php echo number_format($product['total_orders'] ?? 0); ?> sold</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Featured Products -->
        <?php if (!empty($featured_products)): ?>
            <div class="section-header">
                <h5><i class="fas fa-star text-warning"></i> Featured Products</h5>
                <a href="category.php?slug=all">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="row g-3 mb-4">
                <?php foreach ($featured_products as $product): 
                    $price = $product['discount_price'] ?? $product['price'];
                    $discount = $product['discount_price'] ? round((($product['price'] - $product['discount_price']) / $product['price']) * 100) : 0;
                ?>
                    <div class="col-6 col-md-3">
                        <div class="product-card">
                            <?php if ($discount > 0): ?>
                                <span class="discount-badge">-<?php echo $discount; ?>%</span>
                            <?php endif; ?>
                            <a href="product.php?slug=<?php echo $product['slug']; ?>">
                                <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/200x180/eee/333?text=No+Image'); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     loading="lazy">
                            </a>
                            <div class="name">
                                <a href="product.php?slug=<?php echo $product['slug']; ?>">
                                    <?php echo htmlspecialchars(substr($product['name'], 0, 40)); ?>
                                </a>
                            </div>
                            <div class="price">PKR <?php echo number_format($price); ?></div>
                            <?php if ($product['discount_price']): ?>
                                <div class="original-price">PKR <?php echo number_format($product['price']); ?></div>
                            <?php endif; ?>
                            <div class="rating">
                                <i class="fas fa-star"></i> <?php echo number_format($product['rating'] ?? 0, 1); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Best Sellers -->
        <div class="section-header">
            <h5><i class="fas fa-trophy text-warning"></i> Best Selling</h5>
            <a href="category.php?slug=all&sort=popular">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="row g-3 mb-4">
            <?php foreach (array_slice($best_sellers, 0, 8) as $product): 
                $price = $product['discount_price'] ?? $product['price'];
                $discount = $product['discount_price'] ? round((($product['price'] - $product['discount_price']) / $product['price']) * 100) : 0;
            ?>
                <div class="col-6 col-md-3">
                    <div class="product-card">
                        <?php if ($discount > 0): ?>
                            <span class="discount-badge">-<?php echo $discount; ?>%</span>
                        <?php endif; ?>
                        <a href="product.php?slug=<?php echo $product['slug']; ?>">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/200x180/eee/333?text=No+Image'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 loading="lazy">
                        </a>
                        <div class="name">
                            <a href="product.php?slug=<?php echo $product['slug']; ?>">
                                <?php echo htmlspecialchars(substr($product['name'], 0, 40)); ?>
                            </a>
                        </div>
                        <div class="price">PKR <?php echo number_format($price); ?></div>
                        <div class="sold">🔥 <?php echo number_format($product['total_orders'] ?? 0); ?> sold</div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- New Arrivals -->
        <div class="section-header">
            <h5><i class="fas fa-clock text-primary"></i> New Arrivals</h5>
            <a href="category.php?slug=all&sort=newest">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="row g-3 mb-4">
            <?php foreach (array_slice($new_arrivals, 0, 8) as $product): 
                $price = $product['discount_price'] ?? $product['price'];
                $discount = $product['discount_price'] ? round((($product['price'] - $product['discount_price']) / $product['price']) * 100) : 0;
            ?>
                <div class="col-6 col-md-3">
                    <div class="product-card">
                        <?php if ($discount > 0): ?>
                            <span class="discount-badge">New</span>
                        <?php endif; ?>
                        <a href="product.php?slug=<?php echo $product['slug']; ?>">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/200x180/eee/333?text=No+Image'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 loading="lazy">
                        </a>
                        <div class="name">
                            <a href="product.php?slug=<?php echo $product['slug']; ?>">
                                <?php echo htmlspecialchars(substr($product['name'], 0, 40)); ?>
                            </a>
                        </div>
                        <div class="price">PKR <?php echo number_format($price); ?></div>
                        <?php if ($product['discount_price']): ?>
                            <div class="original-price">PKR <?php echo number_format($product['price']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Featured Vendors -->
        <?php if (!empty($vendors)): ?>
            <div class="vendor-showcase">
                <h5 class="mb-3"><i class="fas fa-store-alt text-success"></i> Featured Vendors</h5>
                <div class="row g-3">
                    <?php foreach ($vendors as $vendor): ?>
                        <div class="col-4 col-md-3 col-lg-2">
                            <a href="vendor_products.php?vendor=<?php echo $vendor['id']; ?>" class="vendor-card">
                                <div class="avatar">
                                    <?php echo strtoupper(substr($vendor['store_name'] ?? $vendor['full_name'], 0, 1)); ?>
                                </div>
                                <div class="store-name"><?php echo htmlspecialchars($vendor['store_name'] ?? $vendor['full_name']); ?></div>
                                <?php if ($vendor['is_verified']): ?>
                                    <div class="verified"><i class="fas fa-check-circle"></i> Verified</div>
                                <?php endif; ?>
                                <div class="rating">
                                    <i class="fas fa-star"></i> <?php echo number_format($vendor['rating'] ?? 0, 1); ?>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- App Download Banner -->
        <div class="app-banner">
            <div>
                <h5><i class="fas fa-mobile-alt me-2"></i> Download the MarketPK App</h5>
                <p>Get the best deals on the go. Shop anytime, anywhere.</p>
            </div>
            <a href="#" class="btn-download">
                <i class="fas fa-download"></i> Download Now
            </a>
        </div>

    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
