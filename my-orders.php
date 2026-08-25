<?php
/**
 * Customer Order History & Tracking
 */

$page_title = "My Order History";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/auth.php';

// Require customer to be logged in
requireLogin();

$userId = currentUserId();

// Fetch all orders for current user
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY id DESC");
    $stmt->execute(['user_id' => $userId]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}
?>

<div class="container section">
    <div style="margin-bottom: 25px;">
        <h1 style="font-size: 1.8rem; margin-bottom: 4px;">📦 My Orders & Purchase History</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">Track the status of your vehicle spare parts shipments</p>
    </div>

    <?php if (empty($orders)): ?>
        <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--radius); padding: 50px 20px; text-align: center; box-shadow: var(--shadow-sm);">
            <div style="font-size: 3rem; margin-bottom: 15px;">📦</div>
            <h2 style="font-size: 1.4rem; margin-bottom: 8px;">You Have Not Placed Any Orders Yet</h2>
            <p style="color: var(--text-muted); max-width: 450px; margin: 0 auto 20px;">
                Find genuine replacement parts and maintenance essentials for your vehicle in our catalog.
            </p>
            <a href="products.php" class="btn btn-primary">🚗 Start Shopping</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($orders as $order): 
                $statusClass = 'badge-' . strtolower($order['status']);

                // Fetch items for this order
                $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
                $itemsStmt->execute(['order_id' => $order['id']]);
                $items = $itemsStmt->fetchAll();
            ?>
                <div class="cart-card" style="padding: 24px;">
                    <!-- Order Card Header -->
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Order Number</span>
                            <h3 style="font-size: 1.25rem; color: var(--secondary);">#ORD-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></h3>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">
                                Placed on <?= date('M d, Y \a\t h:i A', strtotime($order['created_at'])); ?>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <span class="badge <?= $statusClass; ?>" style="font-size: 0.85rem; padding: 6px 12px;">
                                <?= sanitize($order['status']); ?>
                            </span>
                            <div style="margin-top: 6px; font-weight: 800; font-size: 1.15rem; color: var(--primary);">
                                <?= formatPrice($order['total_amount']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Purchased Items Preview -->
                    <div style="margin-bottom: 16px;">
                        <strong style="font-size: 0.9rem; color: var(--secondary); display: block; margin-bottom: 8px;">Items in this order:</strong>
                        <ul style="list-style: none; font-size: 0.9rem; color: #475569;">
                            <?php foreach ($items as $it): ?>
                                <li style="padding: 6px 0; border-bottom: 1px dashed #f1f5f9; display: flex; justify-content: space-between;">
                                    <span>
                                        • <strong><?= sanitize($it['product_name']); ?></strong> 
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">(&times; <?= (int)$it['quantity']; ?>)</span>
                                    </span>
                                    <span><?= formatPrice($it['subtotal']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Footer actions & destination -->
                    <div style="display: flex; justify-content: space-between; align-items: center; pt-3; font-size: 0.85rem; color: var(--text-muted); flex-wrap: wrap; gap: 10px;">
                        <div>
                            📍 <strong>Delivering to:</strong> <?= sanitize($order['shipping_name']); ?>, <?= sanitize($order['shipping_address']); ?>
                        </div>
                        <a href="order-success.php?order_id=<?= (int)$order['id']; ?>" class="btn btn-sm btn-outline">
                            📄 View Full Receipt
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
