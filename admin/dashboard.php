<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_admin('login.php');
require_once __DIR__ . '/../app/config/db.php';

$conn = (new Database())->connect();
<<<<<<< HEAD

function admin_fetch_value($conn, $sql, $default = 0) {
    if (!$conn) {
        return $default;
    }
    try {
        $value = $conn->query($sql)->fetchColumn();
        return $value === false || $value === null ? $default : $value;
    } catch (Throwable $e) {
        return $default;
    }
}

function admin_fetch_all($conn, $sql) {
    if (!$conn) {
        return [];
    }
    try {
        return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

$stats = [
    ['label' => 'Total Products', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM products"))],
    ['label' => 'Total Orders', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM orders"))],
    ['label' => 'Total Customers', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer'"))],
    ['label' => 'Total Revenue', 'value' => 'TZS ' . number_format((float) admin_fetch_value($conn, "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'verified'"), 0)],
    ['label' => 'Pending Orders', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'confirmed', 'processing')"))],
    ['label' => 'Completed Orders', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM orders WHERE status = 'delivered'"))],
    ['label' => 'Total Payments', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM payments"))],
    ['label' => 'Out of Stock', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM products WHERE stock_quantity = 0"))],
];

$salesByMonth = admin_fetch_all($conn, "
    SELECT DATE_FORMAT(created_at, '%b') AS label, COALESCE(SUM(total), 0) AS value
    FROM orders
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY YEAR(created_at), MONTH(created_at)
    LIMIT 6
");
$paymentMethods = admin_fetch_all($conn, "
    SELECT REPLACE(method, '_', ' ') AS label, COUNT(*) AS value
    FROM payments
    GROUP BY method
");
$salesTrend = admin_fetch_all($conn, "
    SELECT DATE_FORMAT(created_at, '%d %b') AS label, COALESCE(SUM(total), 0) AS value
    FROM orders
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at)
    LIMIT 14
");
$topProducts = admin_fetch_all($conn, "
    SELECT product_name AS label, SUM(quantity) AS value
    FROM order_items
    GROUP BY product_name
    ORDER BY value DESC
    LIMIT 6
");
$revenueByCategory = admin_fetch_all($conn, "
    SELECT COALESCE(categories.name, 'Uncategorized') AS label, COALESCE(SUM(order_items.line_total), 0) AS value
    FROM order_items
    LEFT JOIN products ON products.id = order_items.product_id
    LEFT JOIN categories ON categories.id = products.category_id
    GROUP BY categories.name
    ORDER BY value DESC
    LIMIT 6
");
$recentOrders = admin_fetch_all($conn, "
    SELECT orders.*, payments.payment_status, payments.method
    FROM orders
    LEFT JOIN payments ON payments.order_id = orders.id
    ORDER BY orders.created_at DESC
    LIMIT 6
");
$lowStock = admin_fetch_all($conn, "
    SELECT name, stock_quantity
    FROM products
    WHERE stock_quantity <= 5
    ORDER BY stock_quantity ASC, name ASC
    LIMIT 5
");

if (!$salesByMonth && $recentOrders) {
    $monthly = [];
    foreach ($recentOrders as $order) {
        $label = date('M', strtotime($order['created_at']));
        $monthly[$label] = ($monthly[$label] ?? 0) + (float) $order['total'];
    }
    $salesByMonth = array_map(fn($label, $value) => ['label' => $label, 'value' => $value], array_keys($monthly), $monthly);
}

if (!$salesTrend && $recentOrders) {
    $daily = [];
    foreach ($recentOrders as $order) {
        $label = date('d M', strtotime($order['created_at']));
        $daily[$label] = ($daily[$label] ?? 0) + (float) $order['total'];
    }
    $salesTrend = array_map(fn($label, $value) => ['label' => $label, 'value' => $value], array_keys($daily), $daily);
}

$chartData = [
    'salesByMonth' => $salesByMonth,
    'paymentMethods' => $paymentMethods,
    'salesTrend' => $salesTrend,
    'topProducts' => $topProducts,
    'revenueByCategory' => $revenueByCategory,
];
=======
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
>>>>>>> dffcd92dcb69c10beb0fb71be3046d8012e923b0
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
<<<<<<< HEAD
            <a href="products/index.php">Products Management</a>
            <a href="categories/index.php">Categories Management</a>
            <a href="orders/index.php">Orders Management</a>
            <a href="users/index.php">Users / Customers</a>
            <a href="payments/index.php">Payments</a>
            <a href="orders/index.php">Order Tracking</a>
            <a href="reports/index.php">Reports & Analytics</a>
            <a href="inventory/index.php">Inventory / Stock</a>
            <a href="messages/index.php">Messages</a>
            <a href="settings/index.php">Settings</a>
            <a href="logout.php">Logout</a>
        </nav>
    </aside>
    <section class="admin-content">
        <div class="section-heading section-heading--row">
            <div>
                <p class="section-kicker">Admin dashboard</p>
                <h1>Business control center</h1>
            </div>
            <a class="button button--secondary" href="../public/index.php">View Store</a>
        </div>

        <div class="admin-kpi-grid">
            <?php foreach ($stats as $stat): ?>
                <article class="admin-kpi-card">
                    <span><?php echo htmlspecialchars($stat['label']); ?></span>
                    <strong><?php echo htmlspecialchars($stat['value']); ?></strong>
                </article>
            <?php endforeach; ?>
        </div>

        <section class="admin-chart-grid">
            <article class="admin-panel"><h2>Sales per Month</h2><canvas id="salesByMonth"></canvas></article>
            <article class="admin-panel"><h2>Payment Methods</h2><canvas id="paymentMethods"></canvas></article>
            <article class="admin-panel admin-panel--wide"><h2>Daily Sales Trend</h2><canvas id="salesTrend"></canvas></article>
            <article class="admin-panel"><h2>Top Selling Products</h2><canvas id="topProducts"></canvas></article>
            <article class="admin-panel"><h2>Revenue by Category</h2><canvas id="revenueByCategory"></canvas></article>
        </section>

        <section class="admin-grid-two">
            <article class="admin-panel">
                <h2>Recent Orders</h2>
                <div class="cart-table">
                    <div class="cart-row cart-row--head"><span>Order</span><span>Customer</span><span>Payment</span><span>Status</span><span>Total</span></div>
                    <?php foreach ($recentOrders as $order): ?>
                        <div class="cart-row">
                            <span><a href="orders/view.php?id=<?php echo (int) $order['id']; ?>"><?php echo htmlspecialchars($order['order_number']); ?></a></span>
                            <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                            <span><?php echo htmlspecialchars($order['payment_status'] ?? 'pending'); ?></span>
                            <span><?php echo htmlspecialchars($order['status']); ?></span>
                            <span>TZS <?php echo number_format((float) $order['total'], 0); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
            <article class="admin-panel">
                <h2>Low Stock Alerts</h2>
                <?php if ($lowStock): ?>
                    <div class="admin-alert-list">
                        <?php foreach ($lowStock as $product): ?>
                            <p><span><?php echo htmlspecialchars($product['name']); ?></span><strong><?php echo (int) $product['stock_quantity']; ?> left</strong></p>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No low stock alerts right now.</p>
                <?php endif; ?>
            </article>
        </section>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartData = <?php echo json_encode($chartData, JSON_NUMERIC_CHECK); ?>;
const labels = (key) => chartData[key].map((row) => row.label);
const values = (key) => chartData[key].map((row) => Number(row.value));
const palette = ['#0f766e', '#2563eb', '#f59e0b', '#dc2626', '#7c3aed', '#16a34a'];
new Chart(document.getElementById('salesByMonth'), {type: 'bar', data: {labels: labels('salesByMonth'), datasets: [{label: 'TZS', data: values('salesByMonth'), backgroundColor: '#0f766e'}]}});
new Chart(document.getElementById('paymentMethods'), {type: 'pie', data: {labels: labels('paymentMethods'), datasets: [{data: values('paymentMethods'), backgroundColor: palette}]}});
new Chart(document.getElementById('salesTrend'), {type: 'line', data: {labels: labels('salesTrend'), datasets: [{label: 'TZS', data: values('salesTrend'), borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.12)', fill: true, tension: .35}]}});
new Chart(document.getElementById('topProducts'), {type: 'bar', data: {labels: labels('topProducts'), datasets: [{label: 'Units sold', data: values('topProducts'), backgroundColor: '#f59e0b'}]}, options: {indexAxis: 'y'}});
new Chart(document.getElementById('revenueByCategory'), {type: 'doughnut', data: {labels: labels('revenueByCategory'), datasets: [{data: values('revenueByCategory'), backgroundColor: palette}]}});
</script>
=======
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
>>>>>>> dffcd92dcb69c10beb0fb71be3046d8012e923b0
</body>
</html>
