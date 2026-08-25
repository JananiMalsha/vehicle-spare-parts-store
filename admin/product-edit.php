<?php
/**
 * Edit Spare Part - Admin Panel
 */

$page_title = "Edit Spare Part";
require_once __DIR__ . '/includes/admin_header.php';

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    header("Location: products.php");
    exit;
}

// Fetch existing product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='alert alert-danger'>Spare part not found. <a href='products.php'>Return to catalog</a></div>";
    require_once __DIR__ . '/includes/admin_footer.php';
    exit;
}

// Fetch categories
$catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll();

$errors = [];
$name               = $product['name'];
$part_number        = $product['part_number'];
$category_id        = $product['category_id'];
$vehicle_make       = $product['vehicle_make'];
$vehicle_model      = $product['vehicle_model'];
$year_compatibility = $product['year_compatibility'];
$price              = $product['price'];
$stock_quantity     = $product['stock_quantity'];
$description        = $product['description'];
$currentImage       = $product['image'];

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
    if (empty($vehicle_make)) $errors[] = "Vehicle make is required.";
    if (empty($vehicle_model)) $errors[] = "Vehicle model is required.";
    if ($price <= 0) $errors[] = "Price must be greater than $0.00.";
    if ($stock_quantity < 0) $errors[] = "Stock quantity cannot be negative.";

    // Check unique SKU against other products
    if (empty($errors)) {
        $skuCheck = $pdo->prepare("SELECT id FROM products WHERE part_number = :sku AND id != :id LIMIT 1");
        $skuCheck->execute(['sku' => $part_number, 'id' => $productId]);
        if ($skuCheck->fetch()) {
            $errors[] = "Another product with SKU '{$part_number}' already exists.";
        }
    }

    // Handle Image Replacement
    $imageFilename = $currentImage;
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
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $errors[] = "Image size exceeds 5MB limit.";
        } else {
            $newFileName = 'part_' . uniqid() . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Delete old image if it's not default
                if ($currentImage !== 'default_part.jpg' && file_exists($uploadDir . $currentImage)) {
                    @unlink($uploadDir . $currentImage);
                }
                $imageFilename = $newFileName;
            } else {
                $errors[] = "Failed to upload new image file.";
            }
        }
    }

    // Update Product in Database
    if (empty($errors)) {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE products SET
                    category_id = :cat_id,
                    name = :name,
                    part_number = :part_num,
                    vehicle_make = :make,
                    vehicle_model = :model,
                    year_compatibility = :years,
                    price = :price,
                    stock_quantity = :stock,
                    description = :description,
                    image = :image
                WHERE id = :id
            ");

            $updateStmt->execute([
                'cat_id'      => $category_id,
                'name'        => $name,
                'part_num'    => $part_number,
                'make'        => $vehicle_make,
                'model'       => $vehicle_model,
                'years'       => $year_compatibility ?: 'All Years',
                'price'       => $price,
                'stock'       => $stock_quantity,
                'description' => $description,
                'image'       => $imageFilename,
                'id'          => $productId
            ]);

            header("Location: products.php");
            exit;

        } catch (PDOException $e) {
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <h1>✏️ Edit Spare Part: <?= sanitize($product['name']); ?></h1>
    <a href="products.php" class="btn btn-outline">← Back to Catalog</a>
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
        <form action="product-edit.php?id=<?= $productId; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="name">Part Name *</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= sanitize($name); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="part_number">SKU / Part Number *</label>
                    <input type="text" id="part_number" name="part_number" class="form-control" value="<?= sanitize($part_number); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="category_id">Vehicle System Category *</label>
                    <select id="category_id" name="category_id" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id']; ?>" <?= ($category_id == $cat['id']) ? 'selected' : ''; ?>>
                                <?= sanitize($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="vehicle_make">Compatible Vehicle Make *</label>
                    <input type="text" id="vehicle_make" name="vehicle_make" class="form-control" value="<?= sanitize($vehicle_make); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="vehicle_model">Compatible Model *</label>
                    <input type="text" id="vehicle_model" name="vehicle_model" class="form-control" value="<?= sanitize($vehicle_model); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="year_compatibility">Year Range</label>
                    <input type="text" id="year_compatibility" name="year_compatibility" class="form-control" value="<?= sanitize($year_compatibility); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="price">Price ($ USD) *</label>
                    <input type="number" step="0.01" min="0" id="price" name="price" class="form-control" value="<?= sanitize($price); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="stock_quantity">Stock Quantity *</label>
                    <input type="number" min="0" id="stock_quantity" name="stock_quantity" class="form-control" value="<?= sanitize($stock_quantity); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Detailed Description & Specifications</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?= sanitize($description); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label" for="image">Replace Product Image (Optional)</label>
                <input type="file" id="image" name="image" class="form-control" accept="image/*">
                <small style="color: var(--text-muted);">Leave empty to keep the current image.</small>
            </div>

            <div style="margin-top: 25px;">
                <button type="submit" class="btn btn-primary" style="padding: 12px 25px;">
                    💾 Update Spare Part
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
