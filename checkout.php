<?php
/**
 * Checkout & Order Placement Page
 * 
 * Uses PDO Database Transactions (ACID) to guarantee data integrity.
 */

$page_title = "Checkout & Order Details";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/auth.php';

// 1. Require user to be logged in to checkout
if (!isLoggedIn()) {
    $_SESSION['flash_error'] = "Please sign in or create an account to complete your order.";
    header("Location: login.php");
    exit;
}

// 2. Redirect if cart is empty
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$userId = currentUserId();
$errors = [];

// 3. Fetch user details to pre-populate shipping form
try {
    $userStmt = $pdo->prepare("SELECT name, email, phone, address FROM users WHERE id = :id LIMIT 1");
    $userStmt->execute(['id' => $userId]);
    $user = $userStmt->fetch();
} catch (PDOException $e) {
    $user = ['name' => '', 'email' => '', 'phone' => '', 'address' => ''];
}

$shippingName    = $user['name'] ?? '';
$shippingPhone   = $user['phone'] ?? '';
$shippingAddress = $user['address'] ?? '';
$paymentMethod   = 'Cash on Delivery';

// Calculate order amounts
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = ($subtotal >= 75) ? 0.00 : 9.99;
$grandTotal = $subtotal + $shipping;

// 4. Handle Order Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shippingName    = trim($_POST['shipping_name'] ?? '');
    $shippingPhone   = trim($_POST['shipping_phone'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $paymentMethod   = trim($_POST['payment_method'] ?? 'Cash on Delivery');

    // Validation
    if (empty($shippingName)) {
        $errors[] = "Shipping recipient name is required.";
    }
    if (empty($shippingPhone)) {
        $errors[] = "Contact phone number is required for shipping delivery.";
    }
    if (empty($shippingAddress)) {
        $errors[] = "Complete delivery address is required.";
    }

    // 5. Process Order inside a PDO Database Transaction
    if (empty($errors)) {
        try {
            // Begin Transaction
            $pdo->beginTransaction();

            // Step A: Re-verify product stock availability to prevent race conditions
            foreach ($_SESSION['cart'] as $pId => $item) {
                $stockCheck = $pdo->prepare("SELECT stock_quantity, name FROM products WHERE id = :id FOR UPDATE");
                $stockCheck->execute(['id' => $pId]);
                $dbProd = $stockCheck->fetch();

                if (!$dbProd || $dbProd['stock_quantity'] < $item['quantity']) {
                    $avail = $dbProd ? $dbProd['stock_quantity'] : 0;
                    throw new Exception("Insufficient stock for '{$item['name']}'. Only {$avail} units remaining.");
                }
            }

            // Step B: Insert into `orders` table
            $orderStmt = $pdo->prepare("
                INSERT INTO orders (
                    user_id, total_amount, shipping_name, shipping_phone, 
                    shipping_address, payment_method, status
                ) VALUES (
                    :user_id, :total, :s_name, :s_phone, 
                    :s_addr, :pay_method, 'Pending'
                )
            ");

            $orderStmt->execute([
                'user_id'    => $userId,
                'total'      => $grandTotal,
                's_name'     => $shippingName,
                's_phone'    => $shippingPhone,
                's_addr'     => $shippingAddress,
                'pay_method' => $paymentMethod
            ]);

            $orderId = (int)$pdo->lastInsertId();

            // Step C: Insert each item into `order_items` and deduct stock from `products`
            $itemStmt = $pdo->prepare("
                INSERT INTO order_items (
                    order_id, product_id, product_name, price, quantity, subtotal
                ) VALUES (
                    :order_id, :prod_id, :prod_name, :price, :qty, :subtotal
                )
            ");

            $stockDeductStmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - :qty 
                WHERE id = :id
            ");

            foreach ($_SESSION['cart'] as $pId => $item) {
                $itemSubtotal = $item['price'] * $item['quantity'];

                // Insert order item row
                $itemStmt->execute([
                    'order_id'  => $orderId,
                    'prod_id'   => $item['id'],
                    'prod_name' => $item['name'],
                    'price'     => $item['price'],
                    'qty'       => $item['quantity'],
                    'subtotal'  => $itemSubtotal
                ]);

                // Decrement inventory stock
                $stockDeductStmt->execute([
                    'qty' => $item['quantity'],
                    'id'  => $item['id']
                ]);
            }

            // Commit all operations atomically
            $pdo->commit();

            // Clear the shopping cart
            $_SESSION['cart'] = [];

            // Redirect to Order Success Confirmation page
            header("Location: order-success.php?order_id=" . $orderId);
            exit;

        } catch (Exception $e) {
            // Roll back any database modifications if an error occurred
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "Order could not be processed: " . $e->getMessage();
        }
    }
}
?>

<div class="container section">
    <div style="margin-bottom: 25px;">
        <h1 style="font-size: 1.8rem; margin-bottom: 4px;">💳 Checkout & Shipping Details</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Provide your delivery address and choose your preferred payment method</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul style="margin-left: 20px; line-height: 1.5;">
                <?php foreach ($errors as $err): ?>
                    <li><?= sanitize($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="cart-layout">
        <!-- Left: Shipping & Payment Form -->
        <div class="cart-card" style="padding: 30px;">
            <form action="checkout.php" method="POST">
                <h3 style="font-size: 1.25rem; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                    📍 1. Delivery & Contact Details
                </h3>

                <div class="form-group">
                    <label class="form-label" for="shipping_name">Recipient Full Name *</label>
                    <input type="text" id="shipping_name" name="shipping_name" class="form-control" placeholder="e.g. John Doe" value="<?= sanitize($shippingName); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="shipping_phone">Contact Phone Number (For Courier) *</label>
                    <input type="tel" id="shipping_phone" name="shipping_phone" class="form-control" placeholder="e.g. +1 (555) 019-2834" value="<?= sanitize($shippingPhone); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="shipping_address">Complete Delivery Address *</label>
                    <textarea id="shipping_address" name="shipping_address" class="form-control" rows="3" placeholder="Street Address, Apartment/Suite, City, State, Postal Code" required><?= sanitize($shippingAddress); ?></textarea>
                </div>

                <h3 style="font-size: 1.25rem; margin: 30px 0 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">
                    💳 2. Payment Method
                </h3>

                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 25px;">
                    <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--border-color); border-radius: var(--radius); cursor: pointer; background: #f8fafc;">
                        <input type="radio" name="payment_method" value="Cash on Delivery" checked>
                        <div>
                            <strong>💵 Cash on Delivery (COD)</strong>
                            <div style="font-size: 0.82rem; color: var(--text-muted);">Pay securely in cash or card when your spare parts are delivered.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--border-color); border-radius: var(--radius); cursor: pointer;">
                        <input type="radio" name="payment_method" value="Credit / Debit Card (Mock)">
                        <div>
                            <strong>💳 Credit / Debit Card (Online Payment)</strong>
                            <div style="font-size: 0.82rem; color: var(--text-muted);">Instant mock checkout with Visa / MasterCard / Amex.</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--border-color); border-radius: var(--radius); cursor: pointer;">
                        <input type="radio" name="payment_method" value="Direct Bank Wire Transfer">
                        <div>
                            <strong>🏦 Direct Bank Transfer</strong>
                            <div style="font-size: 0.82rem; color: var(--text-muted);">Make your payment directly into our garage bank account.</div>
                        </div>
                    </label>
                </div>

                <button type="submit" class="btn btn-accent btn-block" style="padding: 15px; font-size: 1.1rem;">
                    🚀 Place Order (<?= formatPrice($grandTotal); ?>)
                </button>
            </form>
        </div>

        <!-- Right: Order Items Summary Sidebar -->
        <div class="cart-summary-card">
            <h3>Items in Your Order</h3>

            <div style="max-height: 280px; overflow-y: auto; margin-bottom: 20px; padding-right: 5px;">
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 0.9rem; padding-bottom: 10px; border-bottom: 1px solid #f1f5f9;">
                        <div>
                            <strong><?= sanitize($item['name']); ?></strong><br>
                            <span style="color: var(--text-muted); font-size: 0.8rem;">Qty: <?= (int)$item['quantity']; ?> &times; <?= formatPrice($item['price']); ?></span>
                        </div>
                        <span style="font-weight: 700; color: var(--primary);">
                            <?= formatPrice($item['price'] * $item['quantity']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-row">
                <span>Parts Subtotal:</span>
                <span><strong><?= formatPrice($subtotal); ?></strong></span>
            </div>

            <div class="summary-row">
                <span>Shipping Fee:</span>
                <span>
                    <?php if ($shipping == 0): ?>
                        <strong style="color: var(--success);">FREE</strong>
                    <?php else: ?>
                        <strong><?= formatPrice($shipping); ?></strong>
                    <?php endif; ?>
                </span>
            </div>

            <div class="summary-total">
                <span>Grand Total:</span>
                <span style="color: var(--primary);"><?= formatPrice($grandTotal); ?></span>
            </div>

            <div style="margin-top: 20px;">
                <a href="cart.php" class="btn btn-outline btn-sm btn-block">
                    ✏️ Edit Cart Items
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
