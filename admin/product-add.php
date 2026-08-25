<?php
/**
 * Add New Spare Part - Admin Panel
 */

$page_title = "Add Spare Part";
require_once __DIR__ . '/includes/admin_header.php';

// Fetch categories for select dropdown
$catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll();

$errors = [];
$name = '';
$part_number = '';
$category_id = '';
$vehicle_make = '';
$vehicle_model = '';
$year_compatibility = '';
$price = '';
$stock_quantity = '10';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name               = trim($_POST['name'] ?? '');
    $part_number        = trim($_POST['part_number'] ?? '');
    $category_id        = (int)($_POST['category_id'] ?? 0);
    $vehicle_make       = trim($_POST['vehicle_make'] ?? '');
    $vehicle_model      = trim($_POST['vehicle_model'] ?? '');
    $year_compatibility = trim($_POST['year_compatibility'] ?? '');
    $price              = (float)($_POST['price'] ?? 0);
    $stock_quantity     = (int)($_POST['stock_quantity'] ?? 0);
    $description        = trim($_POST['description'] ?? '');

    // Validation
    if (empty($name)) $errors[] = "Part name is required.";
    if (empty($part_number)) $errors[] = "Part Number (SKU) is required.";
    if ($category_id <= 0) $errors[] = "Please select a valid category.";
    if (empty($vehicle_make)) $errors[] = "Vehicle make is required (e.g. Toyota, Universal).";
    if (empty($vehicle_model)) $errors[] = "Vehicle model is required (e.g. Corolla, All Models).";
    if ($price <= 0) $errors[] = "Price must be greater than $0.00.";
    if ($stock_quantity < 0) $errors[] = "Stock quantity cannot be negative.";

    // Check unique SKU
    if (empty($errors)) {
        $skuCheck = $pdo->prepare("SELECT id FROM products WHERE part_number = :sku LIMIT 1");
        $skuCheck->execute(['sku' => $part_number]);
        if ($skuCheck->fetch()) {
            $errors[] = "A product with SKU '{$part_number}' already exists.";
        }
    }

    // Handle Image Upload
    $imageFilename = 'default_part.jpg';
    if (empty($errors) && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName    = $_FILES['image']['name'];
        $fileSize    = $_FILES['image']['size'];
        $fileExt     = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

        if (!in_array($fileExt, $allowedExts)) {
            $errors[] = "Invalid image format. Allowed formats: JPG, PNG, WEBP, SVG.";
        } elseif ($fileSize > 5 * 1024 * 1024) { // 5MB limit
            $errors[] = "Image size exceeds 5MB limit.";
        } else {
            // Generate unique secure filename
            $newFileName = 'part_' . uniqid() . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $imageFilename = $newFileName;
            } else {
                $errors[] = "Failed to upload image file. Check folder permissions.";
            }
        }
    }

    // Insert Product into Database
    if (empty($errors)) {
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO products (
                    category_id, name, part_number, vehicle_make, vehicle_model, 
                    year_compatibility, price, stock_quantity, description, image
                ) VALUES (
                    :cat_id, :name, :part_num, :make, :model, 
                    :years, :price, :stock, :description, :image
                )
            ");

            $insertStmt->execute([
                'cat_id'      => $category_id,
                'name'        => $name,
                'part_num'    => $part_number,
                'make'        => $vehicle_make,
                'model'       => $vehicle_model,
                'years'       => $year_compatibility ?: 'All Years',
                'price'       => $price,
                'stock'       => $stock_quantity,
                'description' => $description,
                'image'       => $imageFilename
            ]);

            header("Location: products.php");
            exit;

        } catch (PDOException $e) {
            $errors[] = "Database insert failed: " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <h1>➕ Add New Vehicle Spare Part</h1>
    <a href="products.php" class="btn btn-outline">← Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul style="margin-left: 20px;">
            <?php foreach ($errors as $err): ?>
                <li><?= sanitize($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-card">
    <div style="padding: 25px;">
        <form action="product-add.php" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="name">Part Name *</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Ceramic Front Brake Pads" value="<?= sanitize($name); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="part_number">SKU / Part Number *</label>
                    <input type="text" id="part_number" name="part_number" class="form-control" placeholder="e.g. BRK-TOY-001" value="<?= sanitize($part_number); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="category_id">Vehicle System Category *</label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id']; ?>" <?= ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                                <?= sanitize($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="vehicle_make">Compatible Vehicle Make *</label>
                    <input type="text" id="vehicle_make" name="vehicle_make" class="form-control" placeholder="e.g. Toyota, Honda, Universal" value="<?= sanitize($vehicle_make); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="vehicle_model">Compatible Model *</label>
                    <input type="text" id="vehicle_model" name="vehicle_model" class="form-control" placeholder="e.g. Corolla, Civic, All Models" value="<?= sanitize($vehicle_model); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="year_compatibility">Year Range</label>
                    <input type="text" id="year_compatibility" name="year_compatibility" class="form-control" placeholder="e.g. 2014-2022 or All Years" value="<?= sanitize($year_compatibility); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="price">Price ($ USD) *</label>
                    <input type="number" step="0.01" min="0" id="price" name="price" class="form-control" placeholder="49.99" value="<?= sanitize($price); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="stock_quantity">Initial Stock Quantity *</label>
                    <input type="number" min="0" id="stock_quantity" name="stock_quantity" class="form-control" placeholder="25" value="<?= sanitize($stock_quantity); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Detailed Description & Specifications</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Mention OEM part numbers, materials, dimensions, warranty, and installation notes..."><?= sanitize($description); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="image">Part Photo / Diagram</label>
                <input type="file" id="image" name="image" class="form-control" accept="image/*">
                <small style="color: var(--text-muted);">Accepted formats: JPG, PNG, WEBP, SVG (Max 5MB)</small>
            </div>

            <div style="margin-top: 25px;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 25px;">
                    💾 Save & Publish Spare Part
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
