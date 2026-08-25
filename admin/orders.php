<?php
/**
 * Admin Order Management & Status Updating
 */

$page_title = "Manage Customer Orders";
require_once __DIR__ . '/includes/admin_header.php';

$success = '';
$error = '';

// Handle Status Update (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');

    $allowedStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

    if ($orderId > 0 && in_array($newStatus, $allowedStatuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->execute([
                'status' => $newStatus,
                'id'     => $orderId
            ]);
            $success = "Order #ORD-" . str_pad($orderId, 4, '0', STR_PAD_LEFT) . " status updated to '{$newStatus}'.";
        } catch (PDOException $e) {
            $error = "Failed to update order status: " . $e->getMessage();
        }
    }
}

// Single Order Detail View (if ?order_id=X is present)
$selectedOrderId = (int)($_GET['order_id'] ?? 0);

if ($selectedOrderId > 0) {
    try {
        // Fetch order with user info
        $orderStmt = $pdo->prepare("
            SELECT o.*, u.email AS customer_email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = :id 
            LIMIT 1
        ");
        $orderStmt->execute(['id' => $selectedOrderId]);
        $order = $orderStmt->fetch();

        if ($order) {
            // Fetch items
            $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :id");
            $itemStmt->execute(['id' => $selectedOrderId]);
            $orderItems = $itemStmt->fetchAll();
        }
    } catch (PDOException $e) {
        $order = null;
    }
}

// Otherwise, fetch all orders with optional filter
$statusFilter = trim($_GET['status'] ?? '');
$sql = "
    SELECT o.*, u.name AS customer_name, u.email AS customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE 1=1
";
$params = [];

if (!empty($statusFilter)) {
    $sql .= " AND o.status = :status";
    $params['status'] = $statusFilter;
}

$sql .= " ORDER BY o.id DESC";

try {
    $listStmt = $pdo->prepare($sql);
    $listStmt->execute($params);
    $orders = $listStmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}
?>

<div class="page-header">
    <h1>📦 Customer Orders Management</h1>
    <?php if ($selectedOrderId > 0): ?>
        <a href="orders.php" class="btn btn-outline">← Back to All Orders</a>
    <?php endif; ?>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?= sanitize($success); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= sanitize($error); ?></div>
<?php endif; ?>

<?php if ($selectedOrderId > 0 && !empty($order)): ?>
    <!-- Single Order Management Card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Order Details: #ORD-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></h2>
            <span class="badge badge-<?= strtolower($order['status']); ?>" style="font-size: 0.9rem; padding: 6px 14px;">
                <?= sanitize($order['status']); ?>
            </span>
        </div>

        <div style="padding: 25px;">
            <!-- Grid: Customer Info & Status Update -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <!-- Customer Details -->
                <div style="background: #f8fafc; padding: 20px; border-radius: var(--radius); border: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.1rem; margin-bottom: 12px; color: var(--secondary);">👤 Customer Information</h3>
                    <div style="line-height: 1.8; font-size: 0.92rem;">
                        <strong>Name:</strong> <?= sanitize($order['shipping_name']); ?><br>
                        <strong>Email:</strong> <?= sanitize($order['customer_email']); ?><br>
                        <strong>Phone:</strong> <?= sanitize($order['shipping_phone']); ?><br>
                        <strong>Address:</strong> <?= nl2br(sanitize($order['shipping_address'])); ?><br>
                        <strong>Payment:</strong> <?= sanitize($order['payment_method']); ?><br>
                        <strong>Order Date:</strong> <?= date('F d, Y h:i A', strtotime($order['created_at'])); ?>
                    </div>
                </div>

                <!-- Update Status Form -->
                <div style="background: #f8fafc; padding: 20px; border-radius: var(--radius); border: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.1rem; margin-bottom: 12px; color: var(--secondary);">⚙️ Update Order Status</h3>
                    <form action="orders.php?order_id=<?= (int)$order['id']; ?>" method="POST">
                        <input type="hidden" name="order_id" value="<?= (int)$order['id']; ?>">
                        
                        <div class="form-group">
                            <label class="form-label" for="status">Change Status</label>
                            <select name="status" id="status" class="form-control" style="font-weight: bold;">
                                <option value="Pending" <?= $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending (Order Placed)</option>
                                <option value="Processing" <?= $order['status'] === 'Processing' ? 'selected' : ''; ?>>Processing (Packaging Parts)</option>
                                <option value="Shipped" <?= $order['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped (In Transit)</option>
                                <option value="Delivered" <?= $order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered (Completed)</option>
                                <option value="Cancelled" <?= $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">
                            💾 Update Status
                        </button>
                    </form>
                </div>
            </div>

            <!-- Ordered Spare Parts Items Table -->
            <h3 style="font-size: 1.15rem; margin-bottom: 15px;">Purchased Spare Parts</h3>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Part Name</th>
                            <th>Unit Price</th>
                            <th style="text-align: center;">Quantity</th>
                            <th style="text-align: right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td><strong><?= sanitize($item['product_name']); ?></strong></td>
                                <td><?= formatPrice($item['price']); ?></td>
                                <td style="text-align: center;"><?= (int)$item['quantity']; ?></td>
                                <td style="text-align: right; font-weight: bold; color: var(--primary);">
                                    <?= formatPrice($item['subtotal']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; font-weight: bold; font-size: 1.1rem; padding-top: 20px;">
                                Total Order Amount:
                            </td>
                            <td style="text-align: right; font-weight: 800; font-size: 1.3rem; color: var(--secondary); padding-top: 20px;">
                                <?= formatPrice($order['total_amount']); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Orders Filter Tabs -->
    <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="orders.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-outline'; ?>">All Orders (<?= count($orders); ?>)</a>
        <a href="orders.php?status=Pending" class="btn btn-sm <?= $statusFilter === 'Pending' ? 'btn-primary' : 'btn-outline'; ?>">Pending</a>
        <a href="orders.php?status=Processing" class="btn btn-sm <?= $statusFilter === 'Processing' ? 'btn-primary' : 'btn-outline'; ?>">Processing</a>
        <a href="orders.php?status=Shipped" class="btn btn-sm <?= $statusFilter === 'Shipped' ? 'btn-primary' : 'btn-outline'; ?>">Shipped</a>
        <a href="orders.php?status=Delivered" class="btn btn-sm <?= $statusFilter === 'Delivered' ? 'btn-primary' : 'btn-outline'; ?>">Delivered</a>
        <a href="orders.php?status=Cancelled" class="btn btn-sm <?= $statusFilter === 'Cancelled' ? 'btn-primary' : 'btn-outline'; ?>">Cancelled</a>
    </div>

    <!-- Orders Master Table -->
    <div class="admin-card">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Placed Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 35px;">
                                No orders found with the selected status filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): 
                            $badgeClass = 'badge-' . strtolower($o['status']);
                        ?>
                            <tr>
                                <td><strong>#ORD-<?= str_pad($o['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                <td>
                                    <strong><?= sanitize($o['customer_name']); ?></strong><br>
                                    <small style="color: var(--text-muted);"><?= sanitize($o['customer_email']); ?></small>
                                </td>
                                <td><?= sanitize($o['shipping_phone']); ?></td>
                                <td><strong><?= formatPrice($o['total_amount']); ?></strong></td>
                                <td><?= sanitize($o['payment_method']); ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($o['created_at'])); ?></td>
                                <td>
                                    <span class="badge <?= $badgeClass; ?>">
                                        <?= sanitize($o['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="orders.php?order_id=<?= (int)$o['id']; ?>" class="btn btn-sm btn-primary">
                                        Manage
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
