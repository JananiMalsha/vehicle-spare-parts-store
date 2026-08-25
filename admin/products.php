<?php
/**
 * Product Management (List & Delete) - Admin Panel
 */

$page_title = "Manage Spare Parts";
require_once __DIR__ . '/includes/admin_header.php';

$success = '';
$error = '';

// Handle Delete Product
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        // Fetch product image to remove from uploads if exists
        $imgStmt = $pdo->prepare("SELECT image FROM products WHERE id = :id");
        $imgStmt->execute(['id' => $deleteId]);
        $prod = $imgStmt->fetch();

        if ($prod && !empty($prod['image']) && $prod['image'] !== 'default_part.jpg') {
            $imgPath = __DIR__ . '/../assets/uploads/' . $prod['image'];
            if (file_exists($imgPath)) {
                @unlink($imgPath);
            }
        }

        // Delete from database
        $delStmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $delStmt->execute(['id' => $deleteId]);
        $success = "Spare part deleted successfully from inventory.";

    } catch (PDOException $e) {
        $error = "Error deleting product: " . $e->getMessage();
    }
}

// Fetch all products with category names
try {
    $stmt = $pdo->query("
        SELECT p.*, c.name AS category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        ORDER BY p.id DESC
    ");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $error = "Failed to load products: " . $e->getMessage();
}
?>

<div class="page-header">
    <h1>🔧 Spare Parts Inventory (<?= count($products); ?> Items)</h1>
    <a href="product-add.php" class="btn btn-primary">➕ Add New Spare Part</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= sanitize($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= sanitize($error); ?></div>
<?php endif; ?>

<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Part Name & SKU</th>
                    <th>Category</th>
                    <th>Vehicle Compatibility</th>
                    <th>Price</th>
                    <th>Stock Quantity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 35px;">
                            No spare parts found in the catalog. Click "Add New Spare Part" to add one!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): 
                        $imageSrc = (!empty($p['image']) && file_exists(__DIR__ . '/../assets/uploads/' . $p['image']))
                            ? '../assets/uploads/' . $p['image']
                            : '../assets/images/default-part.svg';
                    ?>
                        <tr>
                            <td>
                                <img src="<?= $imageSrc; ?>" alt="<?= sanitize($p['name']); ?>" style="width: 50px; height: 50px; object-fit: contain; background: #f8fafc; border-radius: 4px; border: 1px solid var(--border-color);">
                            </td>
                            <td>
                                <strong><?= sanitize($p['name']); ?></strong><br>
                                <small style="color: var(--text-muted);">SKU: <code><?= sanitize($p['part_number']); ?></code></small>
                            </td>
                            <td><?= sanitize($p['category_name']); ?></td>
                            <td>
                                <span style="font-size: 0.85rem;">
                                    <?= sanitize($p['vehicle_make']); ?> <?= sanitize($p['vehicle_model']); ?><br>
                                    <small style="color: var(--text-muted);">(<?= sanitize($p['year_compatibility']); ?>)</small>
                                </span>
                            </td>
                            <td><strong><?= formatPrice($p['price']); ?></strong></td>
                            <td>
                                <?php if ($p['stock_quantity'] <= 0): ?>
                                    <span class="badge badge-outstock">Out of Stock (0)</span>
                                <?php elseif ($p['stock_quantity'] <= 10): ?>
                                    <span class="badge badge-lowstock">Low Stock (<?= (int)$p['stock_quantity']; ?>)</span>
                                <?php else: ?>
                                    <span class="badge badge-instock"><?= (int)$p['stock_quantity']; ?> units</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <a href="product-edit.php?id=<?= (int)$p['id']; ?>" class="btn btn-sm btn-outline">
                                        ✏️ Edit
                                    </a>
                                    <a href="products.php?delete=<?= (int)$p['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this spare part?');">
                                        🗑️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
