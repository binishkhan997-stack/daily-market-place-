<?php
// header.php
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <h2 style="color: #FF636B; font-weight: 700; margin: 0;">Market<span style="color: #0066CB;">PK</span></h2>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Categories</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="category.php?slug=electronics">Electronics</a></li>
                        <li><a class="dropdown-item" href="category.php?slug=clothing">Clothing</a></li>
                        <li><a class="dropdown-item" href="category.php?slug=books">Books</a></li>
                        <li><a class="dropdown-item" href="category.php?slug=home-kitchen">Home & Kitchen</a></li>
                        <li><a class="dropdown-item" href="category.php?slug=beauty">Beauty</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="index.php?filter=featured">Best Sellers</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php?filter=new">New Arrivals</a></li>
            </ul>
            <div class="d-flex align-items-center">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i>
                            <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="fas fa-list"></i> My Orders</a></li>
                            <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a></li>
                            <?php if ($_SESSION['user_type'] === 'vendor'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="vendor_dashboard.php"><i class="fas fa-store"></i> Vendor Dashboard</a></li>
                            <?php endif; ?>
                            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="admin/index.php"><i class="fas fa-crown"></i> Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                    <a href="cart.php" class="btn btn-link text-dark position-relative ms-2">
                        <i class="fas fa-shopping-cart" style="font-size: 1.2rem;"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php 
                            $cart_count = 0;
                            if (isset($_SESSION['user_id'])) {
                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                $cart_count = $stmt->fetch()['count'];
                            }
                            echo $cart_count;
                            ?>
                        </span>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                    <a href="register.php" class="btn btn-primary">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
