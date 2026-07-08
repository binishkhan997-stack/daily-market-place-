<?php
// category.php
session_start();
require_once 'config/database.php';

$slug = $_GET['slug'] ?? 'all';
$page = intval($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;
$sort = $_GET['sort'] ?? 'newest';

// Get category
$category = null;
if ($slug !== 'all') {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    $category = $stmt->fetch();
}

// Build query
$sql = "SELECT p.*, pi.image_url, u.full_name as vendor_name 
        FROM products p
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN users u ON p.vendor_id = u.id
        WHERE p.is_active = 1";

$params = [];

if ($category) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category['id'];
}

// Search filter
if (!empty($_GET['search'])) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Price filter
if (!empty($_GET['min_price']) && !empty($_GET['max_price'])) {
    $sql .= " AND (p.discount_price IS NOT NULL ? p.discount_price : p.price) BETWEEN ? AND ?";
    $params[] = floatval($_GET['min_price']);
    $params[] = floatval($_GET['max_price']);
}

// Sort
switch ($sort) {
    case 'price_low':
        $sql .= " ORDER BY COALESCE(p.discount_price, p.price) ASC";
        break;
    case 'price_high':
        $sql .= " ORDER BY COALESCE(p.discount_price, p.price) DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY p.total_orders DESC, p.views DESC";
        break;
    case 'rating':
        $sql .= " ORDER BY p.rating DESC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
}

$sql .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get total count for pagination
$countSql = "SELECT COUNT(*) FROM products p WHERE p.is_active = 1";
$countParams = [];
if ($category) {
    $countSql .= " AND p.category_id = ?";
    $countParams[] = $category['id'];
}
if (!empty($_GET['search'])) {
    $countSql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $countParams[] = $searchTerm;
    $countParams[] = $searchTerm;
}
$stmt = $pdo->prepare($countSql);
$stmt->execute($countParams);
$total_products = $stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

// Get categories for sidebar
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get price range for filter
$stmt = $pdo->query("
    SELECT 
        MIN(COALESCE(discount_price, price)) as min_price,
        MAX(COALESCE(discount_price, price)) as max_price
    FROM products
    WHERE is_active = 1
");
$price_range = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $category ? htmlspecialchars($category['name']) : 'All Products'; ?> - MarketPK</title>
    <meta name="description" content="Shop <?php echo $category ? htmlspecialchars($category['name']) : 'all products'; ?> online at best prices in Pakistan.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
        }

        /* Category Header */
        .category-header {
            background: #fff;
            padding: 20px 25px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .category-header h4 {
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .category-header .count {
            color: var(--daraz-gray);
            font-size: 0.9rem;
        }

        /* Sidebar */
        .filter-sidebar {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            position: sticky;
            top: 100px;
        }
        .filter-sidebar h6 {
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }
        .filter-sidebar .filter-group {
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .filter-sidebar .filter-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .filter-sidebar .category-item {
            display: block;
            padding: 5px 0;
            color: #555;
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 0.9rem;
        }
        .filter-sidebar .category-item:hover,
        .filter-sidebar .category-item.active {
            color: var(--daraz-orange);
        }
        .filter-sidebar .category-item .badge {
            background: #f0f0f0;
            color: #555;
            font-weight: 400;
            float: right;
        }
        .filter-sidebar .price-input {
            width: 100%;
            padding: 6px 10px;
            border: 1px solid var(--daraz-border);
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .filter-sidebar .price-input:focus {
            outline: none;
            border-color: var(--daraz-orange);
        }
        .filter-sidebar .btn-filter {
            background: var(--daraz-orange);
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            font-weight: 500;
            width: 100%;
            transition: background 0.3s ease;
        }
        .filter-sidebar .btn-filter:hover {
            background: #e55d00;
        }

        /* Product Grid */
        .product-grid {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .product-grid-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .product-grid-header .sort-select {
            padding: 6px 12px;
            border: 1px solid var(--daraz-border);
            border-radius: 4px;
            font-size: 0.9rem;
            background: #fff;
        }
        .product-grid-header .sort-select:focus {
            outline: none;
            border-color: var(--daraz-orange);
        }

        .product-card {
            background: #fff;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            height: 100%;
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border-color: var(--daraz-orange);
        }
        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 4px;
        }
        .product-card .name {
            font-size: 0.9rem;
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
            font-size: 1.1rem;
        }
        .product-card .original-price {
            font-size: 0.85rem;
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
            font-size: 0.75rem;
            font-weight: 600;
        }
        .product-card .vendor-name {
            font-size: 0.8rem;
            color: var(--daraz-gray);
        }
        .product-card .rating {
            font-size: 0.8rem;
            color: #f5a623;
        }

        /* Pagination */
        .pagination-custom {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .pagination-custom a,
        .pagination-custom span {
            padding: 8px 14px;
            border: 1px solid var(--daraz-border);
            border-radius: 4px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            min-width: 40px;
            text-align: center;
        }
        .pagination-custom a:hover {
            background: var(--daraz-orange);
            color: #fff;
            border-color: var(--daraz-orange);
        }
        .pagination-custom .active {
            background: var(--daraz-orange);
            color: #fff;
            border-color: var(--daraz-orange);
        }
        .pagination-custom .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .category-header {
                padding: 15px;
                border-radius: 0;
            }
            .filter-sidebar {
                position: static;
                margin-bottom: 15px;
            }
            .product-grid {
                padding: 12px;
                border-radius: 0;
            }
            .product-card img {
                height: 140px;
            }
            .product-card .name {
                font-size: 0.8rem;
                height: 35px;
            }
            .product-card .price {
                font-size: 0.95rem;
            }
        }

        @media (max-width: 480px) {
            .product-grid-header {
                flex-direction: column;
                align-items: stretch;
            }
            .product-grid-header .sort-select {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="container py-3">

        <!-- Category Header -->
        <div class="category-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h4><?php echo $category ? htmlspecialchars($category['name']) : 'All Products'; ?></h4>
                    <?php if ($category && $category['description']): ?>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($category['description']); ?></p>
                    <?php endif; ?>
                </div>
                <span class="count"><i class="fas fa-box"></i> <?php echo number_format($total_products); ?> Products</span>
            </div>
        </div>

        <div class="row g-4">

            <!-- Sidebar Filters -->
            <div class="col-lg-3">
                <div class="filter-sidebar">
                    <h6><i class="fas fa-list"></i> Categories</h6>
                    <div class="filter-group">
                        <a href="category.php?slug=all" class="category-item <?php echo !$category ? 'active' : ''; ?>">
                            All Categories <span class="badge">All</span>
                        </a>
                        <?php foreach ($categories as $cat): ?>
                            <a href="category.php?slug=<?php echo $cat['slug']; ?>" class="category-item <?php echo ($category && $category['id'] == $cat['id']) ? 'active' : ''; ?>">
                                <i class="fas fa-angle-right"></i> <?php echo htmlspecialchars($cat['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <h6><i class="fas fa-dollar-sign"></i> Price Range</h6>
                    <div class="filter-group">
                        <form method="GET" id="priceFilter">
                            <input type="hidden" name="slug" value="<?php echo $slug; ?>">
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="price-input" name="min_price" placeholder="Min" value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="price-input" name="max_price" placeholder="Max" value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn-filter mt-2">Apply Filter</button>
                        </form>
                    </div>

                    <h6><i class="fas fa-tag"></i> Brands</h6>
                    <div class="filter-group">
                        <!-- Dynamic brands would go here -->
                        <label class="d-block"><input type="checkbox"> Brand 1</label>
                        <label class="d-block"><input type="checkbox"> Brand 2</label>
                        <label class="d-block"><input type="checkbox"> Brand 3</label>
                    </div>

                    <h6><i class="fas fa-star"></i> Rating</h6>
                    <div class="filter-group">
                        <label class="d-block"><input type="checkbox"> ⭐ 4 & above</label>
                        <label class="d-block"><input type="checkbox"> ⭐ 3 & above</label>
                        <label class="d-block"><input type="checkbox"> ⭐ 2 & above</label>
                    </div>
                </div>
            </div>

            <!-- Products -->
            <div class="col-lg-9">
                <div class="product-grid">
                    <div class="product-grid-header">
                        <span><?php echo number_format($total_products); ?> products found</span>
                        <div>
                            <label class="me-2 small">Sort by:</label>
                            <select class="sort-select" onchange="window.location.href=this.value">
                                <option value="?slug=<?php echo $slug; ?>&sort=newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                                <option value="?slug=<?php echo $slug; ?>&sort=popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Popular</option>
                                <option value="?slug=<?php echo $slug; ?>&sort=price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="?slug=<?php echo $slug; ?>&sort=price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="?slug=<?php echo $slug; ?>&sort=rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Rating</option>
                            </select>
                        </div>
                    </div>

                    <?php if (empty($products)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box-open" style="font-size: 3rem; color: #ddd;"></i>
                            <h5 class="mt-3">No products found</h5>
                            <p class="text-muted">Try adjusting your filters or search terms</p>
                            <a href="category.php?slug=<?php echo $slug; ?>" class="btn btn-outline-primary">Clear Filters</a>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($products as $product): 
                                $price = $product['discount_price'] ?? $product['price'];
                                $discount = $product['discount_price'] ? round((($product['price'] - $product['discount_price']) / $product['price']) * 100) : 0;
                            ?>
                                <div class="col-6 col-md-4 col-lg-3">
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
                                                <?php echo htmlspecialchars(substr($product['name'], 0, 50)); ?>
                                            </a>
                                        </div>
                                        <div class="price">
                                            PKR <?php echo number_format($price); ?>
                                            <?php if ($product['discount_price']): ?>
                                                <span class="original-price">PKR <?php echo number_format($product['price']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="vendor-name"><?php echo htmlspecialchars($product['vendor_name'] ?? 'Vendor'); ?></div>
                                        <div class="rating">
                                            <i class="fas fa-star"></i> <?php echo number_format($product['rating'] ?? 0, 1); ?>
                                            (<?php echo number_format($product['total_orders'] ?? 0); ?>)
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination-custom">
                                <?php if ($page > 1): ?>
                                    <a href="?slug=<?php echo $slug; ?>&page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>"><i class="fas fa-chevron-left"></i></a>
                                <?php else: ?>
                                    <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="active"><?php echo $i; ?></span>
                                    <?php elseif ($i <= 2 || $i > $total_pages - 2 || abs($i - $page) <= 1): ?>
                                        <a href="?slug=<?php echo $slug; ?>&page=<?php echo $i; ?>&sort=<?php echo $sort; ?>"><?php echo $i; ?></a>
                                    <?php elseif ($i == 3 && $page > 4): ?>
                                        <span>…</span>
                                    <?php elseif ($i == $total_pages - 2 && $page < $total_pages - 3): ?>
                                        <span>…</span>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?slug=<?php echo $slug; ?>&page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>"><i class="fas fa-chevron-right"></i></a>
                                <?php else: ?>
                                    <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
