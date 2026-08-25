<?php
/**
 * Single Spare Part Details Page
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    header("Location: products.php");
    exit;
}

// Fetch product details joined with category
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.id = :id 
        LIMIT 1
    ");
    $stmt->execute(['id' => $productId]);
    $part = $stmt->fetch();

    if (!$part) {
        echo "<div class='container section'><div class='alert alert-danger'>Spare part not found. <a href='products.php'>Return to Catalog</a></div></div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }

    // Fetch related parts in the same category (excluding current part)
    $relStmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.category_id = :cat_id AND p.id != :curr_id 
        LIMIT 3
    ");
    $relStmt->execute([
        'cat_id'  => $part['category_id'],
        'curr_id' => $productId
    ]);
    $relatedParts = $relStmt->fetchAll();

} catch (PDOException $e) {
    echo "<div class='container section'><div class='alert alert-danger'>Error loading product details: " . sanitize($e->getMessage()) . "</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$imageSrc = (!empty($part['image']) && file_exists(__DIR__ . '/assets/uploads/' . $part['image']))
    ? 'assets/uploads/' . $part['image']
    : 'assets/images/default-part.svg';
?>

<div class="container section">
    <!-- Breadcrumb navigation -->
    <div style="margin-bottom: 20px; font-size: 0.9rem; color: var(--text-muted);">
        <a href="index.php">Home</a> &nbsp;/&nbsp; 
        <a href="products.php">Catalog</a> &nbsp;/&nbsp; 
        <a href="products.php?category=<?= (int)$part['category_id']; ?>"><?= sanitize($part['category_name']); ?></a> &nbsp;/&nbsp; 
        <span style="color: var(--secondary); font-weight: 600;"><?= sanitize($part['name']); ?></span>
    </div>

    <!-- Main Product Detail Card -->
    <div class="product-detail-grid">
        <!-- Left: Product Photo -->
        <div class="detail-img-box">
            <img src="<?= $imageSrc; ?>" alt="<?= sanitize($part['name']); ?>">
        </div>

        <!-- Right: Information & Actions -->
        <div class="detail-info">
            <div style="display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap;">
                <span class="badge" style="background: #e0f2fe; color: #0369a1;"><?= sanitize($part['category_name']); ?></span>
                <span class="badge" style="background: #f1f5f9; color: #475569;">SKU: <?= sanitize($part['part_number']); ?></span>
            </div>

            <h1><?= sanitize($part['name']); ?></h1>

            <div style="display: flex; align-items: baseline; gap: 15px; margin: 15px 0;">
                <span style="font-size: 2rem; font-weight: 800; color: var(--primary);">
                    <?= formatPrice($part['price']); ?>
                </span>
                
                <?php if ($part['stock_quantity'] > 0): ?>
                    <span style="color: var(--success); font-weight: 600; font-size: 0.95rem;">
                        ✅ In Stock (<?= (int)$part['stock_quantity']; ?> units available)
                    </span>
                <?php else: ?>
                    <span style="color: var(--danger); font-weight: 600; font-size: 0.95rem;">
                        ❌ Temporarily Out of Stock
                    </span>
                <?php endif; ?>
            </div>

            <!-- Vehicle Fitment Callout -->
            <div class="compatibility-box">
                <h4>🚗 Guaranteed Vehicle Compatibility</h4>
                <p>
                    <strong>Make:</strong> <?= sanitize($part['vehicle_make']); ?> &nbsp;|&nbsp; 
                    <strong>Model:</strong> <?= sanitize($part['vehicle_model']); ?> &nbsp;|&nbsp; 
                    <strong>Years:</strong> <?= sanitize($part['year_compatibility']); ?>
                </p>
            </div>

            <!-- Description -->
            <div style="margin: 20px 0;">
                <h4 style="margin-bottom: 8px; color: var(--secondary);">Description & Specifications:</h4>
                <p style="color: #475569; font-size: 0.95rem; white-space: pre-line;">
                    <?= sanitize($part['description'] ?: 'High quality OEM specification replacement part.'); ?>
                </p>
            </div>

            <!-- Add to Cart Form -->
            <?php if ($part['stock_quantity'] > 0): ?>
                <form action="cart.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$part['id']; ?>">

                    <div class="qty-control-wrapper">
                        <label for="quantity" style="font-weight: 600;">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" class="qty-input" min="1" max="<?= (int)$part['stock_quantity']; ?>" value="1">
                        
                        <button type="submit" class="btn btn-accent" style="padding: 12px 28px; font-size: 1.05rem;">
                            🛒 Add to Cart
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning" style="margin-top: 20px;">
                    This item is currently out of stock. Please check back later or contact customer support.
                </div>
            <?php endif; ?>

            <!-- Trust Guarantees -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-muted);">
                <div>📦 <strong>Fast Shipping</strong> (Dispatches in 24h)</div>
                <div>🛡️ <strong>1-Year Warranty</strong> on all parts</div>
                <div>🔄 <strong>30-Day Easy Returns</strong></div>
                <div>🔧 <strong>100% Fitment Guarantee</strong></div>
            </div>
        </div>
    </div>

    <!-- Related Spare Parts -->
    <?php if (!empty($relatedParts)): ?>
        <div style="margin-top: 60px;">
            <div class="section-header" style="text-align: left; margin-bottom: 25px;">
                <h2>More in <?= sanitize($part['category_name']); ?></h2>
                <p>Other compatible parts you might need for your maintenance</p>
            </div>

            <div class="product-grid">
                <?php foreach ($relatedParts as $rel): 
                    $relImg = (!empty($rel['image']) && file_exists(__DIR__ . '/assets/uploads/' . $rel['image']))
                        ? 'assets/uploads/' . $rel['image']
                        : 'assets/images/default-part.svg';
                ?>
                    <div class="product-card">
                        <div class="product-img-wrapper">
                            <img src="<?= $relImg; ?>" alt="<?= sanitize($rel['name']); ?>">
                        </div>
                        <div class="product-body">
                            <span class="product-part-num">SKU: <?= sanitize($rel['part_number']); ?></span>
                            <h3 class="product-title"><?= sanitize($rel['name']); ?></h3>
                            <div class="product-footer">
                                <span class="product-price"><?= formatPrice($rel['price']); ?></span>
                                <a href="product-details.php?id=<?= (int)$rel['id']; ?>" class="btn btn-sm btn-primary">View</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
