<?php
/**
 * Admin Dashboard - Overview & Key Metrics
 */

$page_title = "Dashboard";
require_once __DIR__ . '/includes/admin_header.php';

// Fetch statistics
try {
    // 1. Total Products
    $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

    // 2. Total Categories
    $totalCategories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

    // 3. Total Orders
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

    // 4. Total Sales Revenue
    $totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'Cancelled'")->fetchColumn();

    // 5. Low stock alerts (<= 10 units)
    $lowStockStmt = $pdo->query("
        SELECT p.*, c.name AS category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.stock_quantity <= 10 
        ORDER BY p.stock_quantity ASC 
        LIMIT 5
    ");
    $lowStockProducts = $lowStockStmt->fetchAll();

    // 6. Recent Orders
    $recentOrdersStmt = $pdo->query("
        SELECT o.*, u.name as customer_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.id DESC 
        LIMIT 5
    ");
    $recentOrders = $recentOrdersStmt->fetchAll();

} catch (PDOException $e) {
    $totalProducts = 0;
    $totalCategories = 0;
    $totalOrders = 0;
    $totalRevenue = 0;
    $lowStockProducts = [];
    $recentOrders = [];
}
?>

<div class="page-header">
    <h1>Dashboard Overview</h1>
    <a href="product-add.php" class="btn btn-primary">➕ Add New Spare Part</a>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">🔧</div>
        <div class="stat-info">
            <h3><?= (int)$totalProducts; ?></h3>
            <p>Total Spare Parts</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📁</div>
        <div class="stat-info">
            <h3><?= (int)$totalCategories; ?></h3>
            <p>Part Categories</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-info">
            <h3><?= (int)$totalOrders; ?></h3>
            <p>Customer Orders</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <h3><?= formatPrice($totalRevenue); ?></h3>
            <p>Total Sales Revenue</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr; gap: 25px;">
    <!-- Low Stock Alert Card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>⚠️ Low Stock Inventory Alert (<= 10 Units)</h2>
            <a href="products.php" class="btn btn-sm btn-outline">View All Inventory</a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Part Name</th>
                        <th>SKU / Part #</th>
                        <th>Category</th>
                        <th>Compatibility</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStockProducts)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 25px;">
                                 All spare parts are sufficiently stocked!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lowStockProducts as $item): ?>
                            <tr>
                                <td><strong><?= sanitize($item['name']); ?></strong></td>
                                <td><code><?= sanitize($item['part_number']); ?></code></td>
                                <td><?= sanitize($item['category_name']); ?></td>
                                <td><?= sanitize($item['vehicle_make']); ?> <?= sanitize($item['vehicle_model']); ?></td>
                                <td><strong><?= formatPrice($item['price']); ?></strong></td>
                                <td>
                                    <span class="badge <?= $item['stock_quantity'] === 0 ? 'badge-outstock' : 'badge-lowstock'; ?>">
                                        <?= (int)$item['stock_quantity']; ?> units remaining
                                    </span>
                                </td>
                                <td>
                                    <a href="product-edit.php?id=<?= (int)$item['id']; ?>" class="btn btn-sm btn-outline">
                                        Restock / Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Orders Card -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>📦 Recent Customer Orders</h2>
            <a href="orders.php" class="btn btn-sm btn-outline">View All Orders</a>
        </div>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Order Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 25px;">
                                No orders placed yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): 
                            $statusClass = 'badge-' . strtolower($order['status']);
                        ?>
                            <tr>
                                <td><strong>#ORD-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                <td><?= sanitize($order['customer_name']); ?></td>
                                <td><strong><?= formatPrice($order['total_amount']); ?></strong></td>
                                <td><?= sanitize($order['payment_method']); ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <span class="badge <?= $statusClass; ?>">
                                        <?= sanitize($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="orders.php?order_id=<?= (int)$order['id']; ?>" class="btn btn-sm btn-primary">
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
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
