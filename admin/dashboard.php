<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_admin('login.php');
require_once __DIR__ . '/../app/config/db.php';

$conn = (new Database())->connect();
$stats = [
    'revenue' => 0,
    'orders' => 0,
    'customers' => 0,
    'products' => 0,
    'pending' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'low_stock' => 0
];
$recentOrders = [];
$statusCounts = [];
$topProducts = [];
$monthlySales = [];
$lowStockProducts = [];

if ($conn) {
    $stats['revenue'] = (float) $conn->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status <> 'cancelled'")->fetchColumn();
    $stats['orders'] = (int) $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats['customers'] = (int) $conn->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
    $stats['products'] = (int) $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
    $stats['pending'] = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'confirmed', 'processing')")->fetchColumn();
    $stats['shipped'] = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn();
    $stats['delivered'] = (int) $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'")->fetchColumn();
    $stats['low_stock'] = (int) $conn->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= 5")->fetchColumn();

    $recentOrders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    $statusCounts = $conn->query("SELECT status, COUNT(*) AS total FROM orders GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
    $topProducts = $conn->query("
        SELECT product_name, SUM(quantity) AS units, SUM(line_total) AS sales
        FROM order_items
        GROUP BY product_name
        ORDER BY sales DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    $monthlySales = $conn->query("
        SELECT DATE_FORMAT(created_at, '%b') AS month_name, SUM(total) AS sales
        FROM orders
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at), DATE_FORMAT(created_at, '%b')
        ORDER BY YEAR(created_at), MONTH(created_at)
    ")->fetchAll(PDO::FETCH_ASSOC);
    $lowStockProducts = $conn->query("
        SELECT name, sku, stock_quantity
        FROM products
        WHERE stock_quantity <= 10
        ORDER BY stock_quantity ASC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
}

$maxMonthly = 1;
foreach ($monthlySales as $month) {
    $maxMonthly = max($maxMonthly, (float) $month['sales']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Dar Fashion Store</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<main class="admin-shell">
    <aside class="admin-sidebar">
        <a class="logo" href="dashboard.php"><span class="logo__mark">DF</span><span class="logo__text">Admin</span></a>
        <nav>
            <a class="is-active" href="dashboard.php">Dashboard</a>
            <a href="products/index.php">Products</a>
            <a href="orders/index.php">Orders</a>
            <a href="users/index.php">Users</a>
            <a href="../public/index.php">View Store</a>
            <a href="logout.php">Logout</a>
        </nav>
    </aside>

    <section class="admin-content">
        <div class="admin-topline">
            <div class="section-heading">
                <p class="section-kicker">Admin dashboard</p>
                <h1>E-commerce management dashboard</h1>
            </div>
            <a class="button button--primary" href="orders/index.php">Manage Orders</a>
        </div>

        <?php if (!$conn): ?>
            <p class="alert alert--error">Database connection failed. Start MySQL and confirm database settings.</p>
        <?php endif; ?>

        <section class="dashboard-kpis">
            <article><span>Total Revenue</span><strong>TZS <?php echo number_format($stats['revenue'], 0); ?></strong></article>
            <article><span>Total Orders</span><strong><?php echo number_format($stats['orders']); ?></strong></article>
            <article><span>Customers</span><strong><?php echo number_format($stats['customers']); ?></strong></article>
            <article><span>Products</span><strong><?php echo number_format($stats['products']); ?></strong></article>
        </section>

        <section class="admin-grid">
            <article class="admin-panel">
                <h2>Order Tracking Control</h2>
                <div class="status-grid">
                    <span><strong><?php echo (int) $stats['pending']; ?></strong>Pending/Processing</span>
                    <span><strong><?php echo (int) $stats['shipped']; ?></strong>In Transit</span>
                    <span><strong><?php echo (int) $stats['delivered']; ?></strong>Delivered</span>
                    <span><strong><?php echo (int) ($statusCounts['cancelled'] ?? 0); ?></strong>Cancelled</span>
                </div>
            </article>

            <article class="admin-panel">
                <h2>Sales Analytics</h2>
                <div class="chart-placeholder chart-placeholder--labeled">
                    <?php if ($monthlySales): ?>
                        <?php foreach ($monthlySales as $month): ?>
                            <?php $height = max(12, ((float) $month['sales'] / $maxMonthly) * 100); ?>
                            <span style="height: <?php echo $height; ?>%"><em><?php echo htmlspecialchars($month['month_name']); ?></em></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="height: 35%"><em>Sales</em></span>
                        <span style="height: 55%"><em>Orders</em></span>
                        <span style="height: 72%"><em>Growth</em></span>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <section class="admin-grid admin-grid--wide">
            <article class="admin-panel">
                <h2>Recent Orders</h2>
                <div class="responsive-table">
                    <table>
                        <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><span class="status-pill status-pill--<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></td>
                                <td>TZS <?php echo number_format((float) $order['total'], 0); ?></td>
                                <td><a href="orders/view.php?id=<?php echo (int) $order['id']; ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="admin-panel">
                <h2>Inventory Alerts</h2>
                <?php if ($lowStockProducts): ?>
                    <div class="inventory-list">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <p><span><?php echo htmlspecialchars($product['name']); ?><small><?php echo htmlspecialchars($product['sku']); ?></small></span><strong><?php echo (int) $product['stock_quantity']; ?> left</strong></p>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No low-stock products right now.</p>
                <?php endif; ?>
            </article>
        </section>

        <section class="admin-grid">
            <article class="admin-panel">
                <h2>Top Selling Products</h2>
                <div class="inventory-list">
                    <?php foreach ($topProducts as $product): ?>
                        <p><span><?php echo htmlspecialchars($product['product_name']); ?><small><?php echo (int) $product['units']; ?> units</small></span><strong>TZS <?php echo number_format((float) $product['sales'], 0); ?></strong></p>
                    <?php endforeach; ?>
                    <?php if (!$topProducts): ?><p class="empty-state">Top products appear after orders are placed.</p><?php endif; ?>
                </div>
            </article>

            <article class="admin-panel">
                <h2>Business Controls</h2>
                <div class="quick-actions">
                    <a href="products/add.php">Add Product</a>
                    <a href="orders/index.php">Update Order Status</a>
                    <a href="users/index.php">Manage Customers</a>
                    <a href="../analytics/insights.txt">View Analytics Notes</a>
                </div>
            </article>
        </section>
    </section>
</main>
</body>
</html>
