<?php
/**
 * Order Confirmation & Invoice Receipt
 */

$page_title = "Order Confirmation";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$orderId = (int)($_GET['order_id'] ?? 0);
$userId = currentUserId();

if ($orderId <= 0) {
    header("Location: index.php");
    exit;
}

// Fetch Order Information
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :order_id AND (user_id = :user_id OR :is_admin = 1) LIMIT 1");
    $stmt->execute([
        'order_id' => $orderId,
        'user_id'  => $userId,
        'is_admin' => isAdmin() ? 1 : 0
    ]);
    $order = $stmt->fetch();

    if (!$order) {
        echo "<div class='container section'><div class='alert alert-danger'>Order not found or unauthorized access.</div></div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }

    // Fetch Order Items
    $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $itemsStmt->execute(['order_id' => $orderId]);
    $orderItems = $itemsStmt->fetchAll();

} catch (PDOException $e) {
    echo "<div class='container section'><div class='alert alert-danger'>Database error: " . sanitize($e->getMessage()) . "</div></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$statusClass = 'badge-' . strtolower($order['status']);
?>

<div class="container section" style="max-width: 800px;">
    <!-- Success Banner -->
    <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: var(--radius); padding: 30px; text-align: center; margin-bottom: 30px;">
        <div style="font-size: 3.5rem; margin-bottom: 10px;">🎉</div>
        <h1 style="color: #065f46; font-size: 2rem; margin-bottom: 8px;">Order Confirmed!</h1>
        <p style="color: #047857; font-size: 1.05rem;">
            Thank you for ordering with GearParts! We are preparing your spare parts for shipment.
        </p>
    </div>

    <!-- Order Receipt Card -->
    <div class="cart-card" style="padding: 30px; margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 20px; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <span style="color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; font-weight: 700;">Order Reference</span>
                <h2 style="font-size: 1.5rem; color: var(--secondary);">#ORD-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></h2>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                    Placed on: <?= date('F d, Y \a\t h:i A', strtotime($order['created_at'])); ?>
                </div>
            </div>

            <div>
                <span class="badge <?= $statusClass; ?>" style="font-size: 0.9rem; padding: 6px 14px;">
                    Status: <?= sanitize($order['status']); ?>
                </span>
            </div>
        </div>

        <!-- Shipping & Payment Breakdown -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; font-size: 0.92rem;">
            <div style="background: #f8fafc; padding: 16px; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <strong style="color: var(--secondary); display: block; margin-bottom: 6px;">📍 Shipping To:</strong>
                <div><strong><?= sanitize($order['shipping_name']); ?></strong></div>
                <div style="color: var(--text-muted); margin-top: 2px;">📞 <?= sanitize($order['shipping_phone']); ?></div>
                <div style="color: var(--text-muted); margin-top: 2px;"><?= nl2br(sanitize($order['shipping_address'])); ?></div>
            </div>

            <div style="background: #f8fafc; padding: 16px; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <strong style="color: var(--secondary); display: block; margin-bottom: 6px;">💳 Payment Details:</strong>
                <div>Method: <strong><?= sanitize($order['payment_method']); ?></strong></div>
                <div style="color: var(--text-muted); margin-top: 4px;">Delivery: Standard Express Courier</div>
            </div>
        </div>

        <!-- Items Table -->
        <h3 style="font-size: 1.15rem; margin-bottom: 15px;">Spare Parts Purchased</h3>
        <div class="table-responsive" style="margin-bottom: 20px;">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th>Price</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><strong><?= sanitize($item['product_name']); ?></strong></td>
                            <td><?= formatPrice($item['price']); ?></td>
                            <td style="text-align: center;"><?= (int)$item['quantity']; ?></td>
                            <td style="text-align: right; font-weight: 700; color: var(--primary);">
                                <?= formatPrice($item['subtotal']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Grand Total -->
        <div style="display: flex; justify-content: flex-end; padding-top: 15px; border-top: 2px solid var(--border-color);">
            <div style="text-align: right;">
                <div style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 4px;">Total Paid / Due on Delivery:</div>
                <div style="font-size: 1.8rem; font-weight: 800; color: var(--secondary);">
                    <?= formatPrice($order['total_amount']); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Actions -->
    <div style="display: flex; justify-content: space-between; gap: 15px; flex-wrap: wrap;">
        <a href="products.php" class="btn btn-outline">
            ← Continue Shopping
        </a>
        <a href="my-orders.php" class="btn btn-primary">
            📦 View All My Orders &rarr;
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
