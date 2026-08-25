<!-- Top Notification Bar -->
<div class="top-bar">
    <div class="container">
        <div>
            <span>📞 Customer Support: +1 (800) 555-PART &nbsp;|&nbsp; 🚚 Fast Nationwide Shipping on All Parts</span>
        </div>
        <div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Welcome, <strong><?= sanitize($_SESSION['user_name']); ?></strong> (<?= ucfirst($_SESSION['user_role']); ?>)</span>
            <?php else: ?>
                <span>Need help? <a href="login.php" style="color: #38bdf8;">Sign In to Track Orders</a></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Navigation Bar -->
<header class="navbar">
    <div class="container">
        <!-- Logo -->
        <a href="index.php" class="brand-logo">
            ⚙️ <span>Gear</span>Parts
        </a>

        <!-- Main Links -->
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="products.php">Browse Parts</a></li>
            <li><a href="products.php?category=1">Braking</a></li>
            <li><a href="products.php?category=2">Engine</a></li>
            <li><a href="products.php?category=3">Suspension</a></li>
        </ul>

        <!-- Right Actions: User Account & Shopping Cart -->
        <div class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin/index.php" class="btn btn-sm btn-outline">🛠️ Admin Panel</a>
                <?php endif; ?>
                <a href="my-orders.php" class="btn btn-sm btn-outline">📦 My Orders</a>
                <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-sm btn-outline">Login</a>
                <a href="register.php" class="btn btn-sm btn-primary">Register</a>
            <?php endif; ?>

            <!-- Cart Badge -->
            <a href="cart.php" class="cart-badge-link">
                🛒 Cart
                <span class="cart-count" id="navCartCount"><?= $cart_total_items; ?></span>
            </a>
        </div>
    </div>
</header>
<main>
