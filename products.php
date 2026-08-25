<?php
/**
 * Customer Spare Parts Catalog & Search
 */

$page_title = "Browse Spare Parts Catalog";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// 1. Get filter parameters
$searchQuery = trim($_GET['q'] ?? '');
$categoryId  = (int)($_GET['category'] ?? 0);
$vehicleMake = trim($_GET['make'] ?? '');
$sortBy      = trim($_GET['sort'] ?? 'newest');

// 2. Fetch all categories for filter sidebar
try {
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// 3. Fetch distinct vehicle makes for filter sidebar
try {
    $makeStmt = $pdo->query("SELECT DISTINCT vehicle_make FROM products WHERE vehicle_make != '' ORDER BY vehicle_make ASC");
    $vehicleMakes = $makeStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $vehicleMakes = [];
}

// 4. Build Dynamic SQL Query with PDO Parameters
$sql = "
    SELECT p.*, c.name AS category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE 1=1
";
$params = [];

if (!empty($searchQuery)) {
    $sql .= " AND (p.name LIKE :q OR p.part_number LIKE :q OR p.vehicle_make LIKE :q OR p.vehicle_model LIKE :q OR p.description LIKE :q)";
    $params['q'] = '%' . $searchQuery . '%';
}

if ($categoryId > 0) {
    $sql .= " AND p.category_id = :category_id";
    $params['category_id'] = $categoryId;
}

if (!empty($vehicleMake)) {
    $sql .= " AND p.vehicle_make = :vehicle_make";
    $params['vehicle_make'] = $vehicleMake;
}

// Sorting Whitelist
switch ($sortBy) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY p.name ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY p.id DESC";
        break;
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}
?>

<div class="container section">
    <!-- Breadcrumb & Results Header -->
    <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <h1 style="font-size: 1.8rem; margin-bottom: 4px;">Vehicle Spare Parts Catalog</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Showing <strong><?= count($products); ?></strong> compatible spare parts
                <?php if (!empty($searchQuery)): ?> for search "<strong><?= sanitize($searchQuery); ?></strong>"<?php endif; ?>
            </p>
        </div>

        <!-- Reset Button -->
        <?php if (!empty($searchQuery) || $categoryId > 0 || !empty($vehicleMake) || $sortBy !== 'newest'): ?>
            <a href="products.php" class="btn btn-sm btn-outline">🔄 Clear All Filters</a>
        <?php endif; ?>
    </div>

    <div class="catalog-layout">
        <!-- Left Filter Sidebar -->
        <aside class="filter-sidebar">
            <h3>🔍 Filter Parts</h3>

            <form action="products.php" method="GET">
                <!-- Search by keyword -->
                <div class="filter-group">
                    <label for="q">Search Keyword</label>
                    <input type="text" id="q" name="q" class="form-control" placeholder="Part name, SKU, Make..." value="<?= sanitize($searchQuery); ?>">
                </div>

                <!-- Filter by System Category -->
                <div class="filter-group">
                    <label for="category">Vehicle System</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">-- All Systems --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id']; ?>" <?= ($categoryId === (int)$cat['id']) ? 'selected' : ''; ?>>
                                <?= sanitize($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter by Vehicle Make -->
                <div class="filter-group">
                    <label for="make">Vehicle Make</label>
                    <select id="make" name="make" class="form-control">
                        <option value="">-- All Makes --</option>
                        <?php foreach ($vehicleMakes as $make): ?>
                            <option value="<?= sanitize($make); ?>" <?= ($vehicleMake === $make) ? 'selected' : ''; ?>>
                                <?= sanitize($make); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Sort By -->
                <div class="filter-group">
                    <label for="sort">Sort By</label>
                    <select id="sort" name="sort" class="form-control">
                        <option value="newest" <?= ($sortBy === 'newest') ? 'selected' : ''; ?>>Newest Arrivals</option>
                        <option value="price_asc" <?= ($sortBy === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?= ($sortBy === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?= ($sortBy === 'name_asc') ? 'selected' : ''; ?>>Part Name (A-Z)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 10px;">
                    Apply Filters
                </button>
            </form>
        </aside>

        <!-- Right Product Grid -->
        <main>
            <?php if (empty($products)): ?>
                <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius); padding: 50px 20px; text-align: center;">
                    <div style="font-size: 3rem; margin-bottom: 15px;">🔍</div>
                    <h2 style="font-size: 1.4rem; margin-bottom: 8px;">No Matching Spare Parts Found</h2>
                    <p style="color: var(--text-muted); max-width: 450px; margin: 0 auto 20px;">
                        We couldn't find any parts matching your selected filters. Try clearing your search or choosing a different vehicle make.
                    </p>
                    <a href="products.php" class="btn btn-primary">View All Spare Parts</a>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $part): 
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
                                <h3 class="product-title">
                                    <a href="product-details.php?id=<?= (int)$part['id']; ?>" style="color: inherit;">
                                        <?= sanitize($part['name']); ?>
                                    </a>
                                </h3>
                                <p class="product-compatibility-text">
                                    <strong>Category:</strong> <?= sanitize($part['category_name']); ?>
                                </p>

                                <div class="product-footer">
                                    <div>
                                        <span class="product-price"><?= formatPrice($part['price']); ?></span><br>
                                        <?php if ($part['stock_quantity'] <= 0): ?>
                                            <span style="color: var(--danger); font-size: 0.75rem; font-weight: bold;">Out of Stock</span>
                                        <?php elseif ($part['stock_quantity'] <= 5): ?>
                                            <span style="color: var(--warning); font-size: 0.75rem; font-weight: bold;">Only <?= (int)$part['stock_quantity']; ?> left</span>
                                        <?php else: ?>
                                            <span style="color: var(--success); font-size: 0.75rem; font-weight: bold;">In Stock</span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="product-details.php?id=<?= (int)$part['id']; ?>" class="btn btn-sm btn-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
