<?php
/**
 * Category Management - Admin Panel
 */

$page_title = "Manage Categories";
require_once __DIR__ . '/includes/admin_header.php';

$success = '';
$error = '';

// Handle Delete Category
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    try {
        $delStmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
        $delStmt->execute(['id' => $deleteId]);
        $success = "Category and associated products deleted successfully.";
    } catch (PDOException $e) {
        $error = "Error deleting category: " . $e->getMessage();
    }
}

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        try {
            $insertStmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
            $insertStmt->execute([
                'name'        => $name,
                'description' => $description
            ]);
            $success = "New category '{$name}' added successfully!";
        } catch (PDOException $e) {
            $error = "Failed to add category: " . $e->getMessage();
        }
    }
}

// Fetch all categories with product counts
try {
    $catStmt = $pdo->query("
        SELECT c.*, COUNT(p.id) AS product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.id ASC
    ");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $error = "Failed to fetch categories: " . $e->getMessage();
}
?>

<div class="page-header">
    <h1>📁 Manage Vehicle System Categories</h1>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= sanitize($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= sanitize($error); ?></div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
    <!-- Add Category Form -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>➕ Add New Category</h2>
        </div>
        <div style="padding: 20px;">
            <form action="categories.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="name">Category Name *</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Exhaust & Emission" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3" placeholder="Brief summary of parts in this category"></textarea>
                </div>

                <button type="submit" name="add_category" class="btn btn-primary" style="width: 100%;">
                    Save Category
                </button>
            </form>
        </div>
    </div>

    <!-- Category List Table -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Existing Categories (<?= count($categories); ?>)</h2>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Total Parts</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 25px;">
                                No categories found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>#<?= (int)$cat['id']; ?></td>
                                <td><strong><?= sanitize($cat['name']); ?></strong></td>
                                <td style="color: var(--text-muted); font-size: 0.85rem; max-width: 250px;">
                                    <?= sanitize($cat['description']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-processing">
                                        <?= (int)$cat['product_count']; ?> parts
                                    </span>
                                </td>
                                <td>
                                    <a href="categories.php?delete=<?= (int)$cat['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this category? All associated products will also be removed!');">
                                        🗑️ Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
