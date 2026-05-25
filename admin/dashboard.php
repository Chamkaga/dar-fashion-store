<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_admin('login.php');
require_once __DIR__ . '/../app/config/db.php';

$conn = (new Database())->connect();

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
</body>
</html>
