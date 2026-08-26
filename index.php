<?php
/**
 * Homepage - Online Vehicle Spare Parts Store
 */

$page_title = "Quality Auto & Vehicle Parts";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// 1. Fetch all categories from database
try {
    $categoryStmt = $pdo->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $categoryStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// 2. Fetch featured/latest products (Joined with categories for category name)
try {
    $productStmt = $pdo->query("
        SELECT p.*, c.name AS category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id DESC 
        LIMIT 6
    ");
    $featuredProducts = $productStmt->fetchAll();
} catch (PDOException $e) {
    $featuredProducts = [];
}
?>

<!-- Hero Banner Section -->
<section class="hero">
    <div class="container">
        <h1>Find Genuine Vehicle Spare Parts</h1>
        <p>Premium OEM & Aftermarket parts for your car, truck, or SUV. Search by make, model, or part number.</p>

        <!-- Quick Search Bar -->
        <form action="products.php" method="GET" class="hero-search-box" id="heroSearchForm">
            <input type="text" name="q" placeholder="Enter Part Name, Part Number (e.g. BRK-BRM-001), or Vehicle Make..." required>
            <button type="submit" class="btn btn-accent">🔍 Search Parts</button>
        </form>
    </div>
</section>

<!-- Trust & Feature Badges -->
<section class="container">
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">🛡️</div>
            <h3>100% Genuine Quality</h3>
            <p>Certified OEM and trusted performance aftermarket replacement parts.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">⚡</div>
            <h3>Fast Express Shipping</h3>
            <p>Reliable nationwide delivery straight to your doorstep or garage.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">🔧</div>
            <h3>Guaranteed Fitment</h3>
            <p>Exact compatibility matching for your vehicle's make, model, and year.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">💬</div>
            <h3>Mechanic Support</h3>
            <p>Dedicated automotive technical support for all part installations.</p>
        </div>
    </div>
</section>

<!-- Vehicle Categories Section -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2>Shop Parts by System</h2>
            <p>Select a vehicle system category to browse compatible components</p>
        </div>

        <div class="category-grid">
            <?php 
            $icons = ['1' => '🛑', '2' => '⚙️', '3' => '🚙', '4' => '💡', '5' => '🛢️'];
            foreach ($categories as $cat): 
                $icon = $icons[$cat['id']] ?? '🚗';
            ?>
                <a href="products.php?category=<?= (int)$cat['id']; ?>" class="category-card">
                    <div class="category-icon-circle"><?= $icon; ?></div>
                    <h3><?= sanitize($cat['name']); ?></h3>
                    <p><?= sanitize($cat['description']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Spare Parts Section -->
<section class="section" style="background-color: #f1f5f9;">
    <div class="container">
        <div class="section-header">
            <h2>Featured Spare Parts</h2>
            <p>Top-rated replacement parts and maintenance essentials</p>
        </div>

        <div class="product-grid">
            <?php if (empty($featuredProducts)): ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 8px;">
                    <p>No products found in the database. Please make sure you have imported <code>database/schema.sql</code>.</p>
                </div>
            <?php else: ?>
                <?php foreach ($featuredProducts as $part): 
                    $imageSrc = (!empty($part['image']) && file_exists(__DIR__ . '/assets/uploads/' . $part['image']))
                        ? 'assets/uploads/' . $part['image']
                        : 'assets/images/default-part.svg';
                ?>
                    <div class="product-card">
                        <div class="product-img-wrapper">
                            <span class="badge-compatibility">
                                <?= sanitize($part['vehicle_make']); ?> <?= sanitize($part['vehicle_model']); ?> (<?= sanitize($part['year_compatibility']); ?>)
                            </span>
                            <img src="<?= $imageSrc; ?>" alt="<?= sanitize($part['name']); ?>">
                        </div>

                        <div class="product-body">
                            <span class="product-part-num">SKU: <?= sanitize($part['part_number']); ?></span>
                            <h3 class="product-title"><?= sanitize($part['name']); ?></h3>
                            <p class="product-compatibility-text">
                                <strong>System:</strong> <?= sanitize($part['category_name']); ?>
                            </p>

                            <div class="product-footer">
                                <span class="product-price"><?= formatPrice($part['price']); ?></span>
                                <a href="product-details.php?id=<?= (int)$part['id']; ?>" class="btn btn-sm btn-primary">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="products.php" class="btn btn-accent" style="padding: 12px 30px; font-size: 1.05rem;">
                Browse All Spare Parts Catalog &rarr;
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
