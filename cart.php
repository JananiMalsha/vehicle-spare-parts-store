<?php
/**
 * Shopping Cart Management
 */

$page_title = "Your Shopping Cart";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Initialize cart array in session if not set
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// -------------------------------------------------------------
// Handle Cart Actions (Add, Update, Remove, Clear)
// -------------------------------------------------------------

// 1. Action: ADD TO CART
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity  = max(1, (int)($_POST['quantity'] ?? 1));

    if ($productId > 0) {
        // Query product data from database to ensure up-to-date price and stock
        try {
            $stmt = $pdo->prepare("SELECT id, name, part_number, price, stock_quantity, image, vehicle_make, vehicle_model FROM products WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $productId]);
            $product = $stmt->fetch();

            if ($product) {
                if ($product['stock_quantity'] <= 0) {
                    $_SESSION['flash_error'] = "Sorry, '{$product['name']}' is currently out of stock.";
                } else {
                    // Check if already in cart
                    $currentQtyInCart = $_SESSION['cart'][$productId]['quantity'] ?? 0;
                    $newQty = $currentQtyInCart + $quantity;

                    // Ensure not exceeding available stock
                    if ($newQty > $product['stock_quantity']) {
                        $newQty = $product['stock_quantity'];
                        $_SESSION['flash_error'] = "Only {$product['stock_quantity']} units of '{$product['name']}' are available in stock.";
                    } else {
                        $_SESSION['flash_success'] = "Added '{$product['name']}' to your shopping cart!";
                    }

                    $_SESSION['cart'][$productId] = [
                        'id'           => (int)$product['id'],
                        'name'         => $product['name'],
                        'part_number'  => $product['part_number'],
                        'price'        => (float)$product['price'],
                        'image'        => $product['image'],
                        'vehicle_info' => $product['vehicle_make'] . ' ' . $product['vehicle_model'],
                        'quantity'     => $newQty,
                        'max_stock'    => (int)$product['stock_quantity']
                    ];
                }
            }
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error adding product to cart.";
        }
    }
    header("Location: cart.php");
    exit;
}

// 2. Action: UPDATE QUANTITIES
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $id => $qty) {
            $id = (int)$id;
            $qty = (int)$qty;

            if (isset($_SESSION['cart'][$id])) {
                if ($qty <= 0) {
                    unset($_SESSION['cart'][$id]);
                } else {
                    $maxStock = $_SESSION['cart'][$id]['max_stock'];
                    $_SESSION['cart'][$id]['quantity'] = min($qty, $maxStock);
                }
            }
        }
        $_SESSION['flash_success'] = "Cart quantities updated successfully.";
    }
    header("Location: cart.php");
    exit;
}

// 3. Action: REMOVE SINGLE ITEM
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$removeId])) {
        $removedName = $_SESSION['cart'][$removeId]['name'];
        unset($_SESSION['cart'][$removeId]);
        $_SESSION['flash_success'] = "Removed '{$removedName}' from your cart.";
    }
    header("Location: cart.php");
    exit;
}

// 4. Action: CLEAR ENTIRE CART
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    $_SESSION['cart'] = [];
    $_SESSION['flash_success'] = "Your shopping cart has been cleared.";
    header("Location: cart.php");
    exit;
}

// Calculate totals
$subtotal = 0;
$totalItems = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $totalItems += $item['quantity'];
}

// Shipping rules: Free shipping if subtotal >= $75, otherwise $9.99
$shipping = ($subtotal >= 75 || $subtotal == 0) ? 0.00 : 9.99;
$grandTotal = $subtotal + $shipping;

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>

<div class="container section">
    <div style="margin-bottom: 25px;">
        <h1 style="font-size: 1.8rem; margin-bottom: 4px;">Shopping Cart (<?= $totalItems; ?> Items)</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Review your selected vehicle spare parts before checkout</p>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-auto-dismiss">
            <?= sanitize($flashSuccess); ?>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert alert-warning">
            <?= sanitize($flashError); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($_SESSION['cart'])): ?>
        <!-- Empty Cart State -->
        <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius); padding: 60px 20px; text-align: center; box-shadow: var(--shadow-sm);">
            <div style="font-size: 3.5rem; margin-bottom: 15px;">🛒</div>
            <h2 style="font-size: 1.5rem; margin-bottom: 10px;">Your Cart is Currently Empty</h2>
            <p style="color: var(--text-muted); max-width: 450px; margin: 0 auto 25px;">
                Looks like you haven't added any vehicle spare parts to your cart yet.
            </p>
            <a href="products.php" class="btn btn-primary" style="padding: 12px 28px;">
                🚗 Browse Spare Parts Catalog
            </a>
        </div>
    <?php else: ?>
        <!-- Active Cart Layout -->
        <div class="cart-layout">
            <!-- Left: Items Table Form -->
            <div class="cart-card">
                <form action="cart.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    
                    <div class="table-responsive">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Part Details</th>
                                    <th>Unit Price</th>
                                    <th style="text-align: center;">Quantity</th>
                                    <th>Subtotal</th>
                                    <th style="text-align: center;">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $id => $item): 
                                    $itemSubtotal = $item['price'] * $item['quantity'];
                                    $imgSrc = (!empty($item['image']) && file_exists(__DIR__ . '/assets/uploads/' . $item['image']))
                                        ? 'assets/uploads/' . $item['image']
                                        : 'assets/images/default-part.svg';
                                ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; gap: 15px; align-items: center;">
                                                <img src="<?= $imgSrc; ?>" alt="<?= sanitize($item['name']); ?>" style="width: 60px; height: 60px; object-fit: contain; background: #f8fafc; border-radius: 6px; border: 1px solid var(--border-color);">
                                                <div>
                                                    <a href="product-details.php?id=<?= (int)$id; ?>" style="font-weight: 700; color: var(--secondary);">
                                                        <?= sanitize($item['name']); ?>
                                                    </a>
                                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;">
                                                        SKU: <code><?= sanitize($item['part_number']); ?></code> &nbsp;|&nbsp; 🚗 <?= sanitize($item['vehicle_info']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><strong><?= formatPrice($item['price']); ?></strong></td>
                                        <td style="text-align: center;">
                                            <input type="number" name="quantities[<?= (int)$id; ?>]" class="form-control" style="width: 70px; text-align: center; display: inline-block; padding: 6px;" min="1" max="<?= (int)$item['max_stock']; ?>" value="<?= (int)$item['quantity']; ?>">
                                            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                                                Max: <?= (int)$item['max_stock']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong style="color: var(--primary); font-size: 1.05rem;">
                                                <?= formatPrice($itemSubtotal); ?>
                                            </strong>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="cart.php?remove=<?= (int)$id; ?>" class="btn btn-sm btn-outline" style="color: var(--danger); border-color: #fecaca;" title="Remove Item" onclick="return confirm('Remove this part from cart?');">
                                                🗑️
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="padding: 18px 24px; background-color: #f8fafc; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color);">
                        <a href="products.php" class="btn btn-outline btn-sm">
                            ← Continue Shopping
                        </a>
                        <div style="display: flex; gap: 10px;">
                            <a href="cart.php?clear=1" class="btn btn-outline btn-sm" style="color: var(--danger);" onclick="return confirm('Are you sure you want to clear your entire cart?');">
                                Clear Cart
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm">
                                🔄 Update Cart
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right: Order Summary Card -->
            <div class="cart-summary-card">
                <h3>Order Summary</h3>

                <div class="summary-row">
                    <span>Parts Subtotal:</span>
                    <span><strong><?= formatPrice($subtotal); ?></strong></span>
                </div>

                <div class="summary-row">
                    <span>Estimated Shipping:</span>
                    <span>
                        <?php if ($shipping == 0): ?>
                            <strong style="color: var(--success);">FREE</strong>
                        <?php else: ?>
                            <strong><?= formatPrice($shipping); ?></strong>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ($subtotal < 75): ?>
                    <div style="background-color: #eff6ff; border: 1px dashed #93c5fd; padding: 8px 12px; border-radius: 6px; font-size: 0.8rem; color: #1e40af; margin-bottom: 15px;">
                        💡 Add <strong><?= formatPrice(75 - $subtotal); ?></strong> more to qualify for <strong>FREE Shipping</strong>!
                    </div>
                <?php endif; ?>

                <div class="summary-total">
                    <span>Estimated Total:</span>
                    <span style="color: var(--primary);"><?= formatPrice($grandTotal); ?></span>
                </div>

                <div style="margin-top: 25px;">
                    <a href="checkout.php" class="btn btn-accent btn-block" style="padding: 14px; font-size: 1.05rem;">
                        💳 Proceed to Checkout &rarr;
                    </a>
                </div>

                <div style="margin-top: 20px; text-align: center; font-size: 0.8rem; color: var(--text-muted); line-height: 1.5;">
                    🔒 Safe & Secure Checkout<br>
                    Guaranteed OEM fitment & 30-day warranty
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
