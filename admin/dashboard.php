<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_admin('login.php');
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';

$conn = (new Database())->connect();
$adminUser = current_user();

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

$primaryStats = [
    ['label' => 'Verified Revenue', 'value' => 'TZS ' . number_format((float) admin_fetch_value($conn, "SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'verified'"), 0), 'class' => 'admin-kpi-card--revenue'],
    ['label' => 'Total Orders', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM orders")), 'class' => 'admin-kpi-card--primary'],
    ['label' => 'Customers', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM users WHERE role = 'customer'")), 'class' => 'admin-kpi-card--primary'],
    ['label' => 'Pending Orders', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'confirmed', 'processing')")), 'class' => 'admin-kpi-card--primary'],
];

$secondaryStats = [
    ['label' => 'Products', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM products"))],
    ['label' => 'Delivered', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM orders WHERE status = 'delivered'"))],
    ['label' => 'Payments', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM payments"))],
    ['label' => 'Out of Stock', 'value' => number_format((int) admin_fetch_value($conn, "SELECT COUNT(*) FROM products WHERE stock_quantity = 0"))],
];

$salesByMonth = admin_fetch_all($conn, "
    SELECT DATE_FORMAT(created_at, '%b') AS label, COALESCE(SUM(total), 0) AS value
    FROM orders GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY YEAR(created_at), MONTH(created_at) LIMIT 6
");
$paymentMethods = admin_fetch_all($conn, "
    SELECT REPLACE(method, '_', ' ') AS label, COUNT(*) AS value FROM payments GROUP BY method
");
$salesTrend = admin_fetch_all($conn, "
    SELECT DATE_FORMAT(created_at, '%d %b') AS label, COALESCE(SUM(total), 0) AS value
    FROM orders GROUP BY DATE(created_at) ORDER BY DATE(created_at) LIMIT 14
");
$topProducts = admin_fetch_all($conn, "
    SELECT product_name AS label, SUM(quantity) AS value FROM order_items
    GROUP BY product_name ORDER BY value DESC LIMIT 6
");
$revenueByCategory = admin_fetch_all($conn, "
    SELECT COALESCE(categories.name, 'Uncategorized') AS label, COALESCE(SUM(order_items.line_total), 0) AS value
    FROM order_items
    LEFT JOIN products ON products.id = order_items.product_id
    LEFT JOIN categories ON categories.id = products.category_id
    GROUP BY categories.name ORDER BY value DESC LIMIT 6
");
$recentOrders = admin_fetch_all($conn, "
    SELECT orders.*, payments.payment_status, payments.method
    FROM orders LEFT JOIN payments ON payments.order_id = orders.id
    ORDER BY orders.created_at DESC LIMIT 6
");
$lowStock = admin_fetch_all($conn, "
    SELECT name, stock_quantity FROM products WHERE stock_quantity <= 5
    ORDER BY stock_quantity ASC, name ASC LIMIT 5
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

$activityLog = $conn ? new ActivityLog($conn) : null;
$recentActivities = $activityLog ? $activityLog->getRecentActivities(8) : [];
$onlineUsers = $activityLog ? $activityLog->getOnlineUsers() : [];

$activityLabels = [
    'login' => 'Login',
    'logout' => 'Logout',
    'view_product' => 'Viewed product',
    'add_to_cart' => 'Added to cart',
    'remove_from_cart' => 'Removed from cart',
    'checkout' => 'Checkout',
    'order_placed' => 'Order placed',
    'payment_attempted' => 'Payment',
];

$chartData = [
    'salesByMonth' => $salesByMonth,
    'paymentMethods' => $paymentMethods,
    'salesTrend' => $salesTrend,
    'topProducts' => $topProducts,
    'revenueByCategory' => $revenueByCategory,
];

$adminPage = 'dashboard';
$adminRoot = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Dar Fashion Store Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<main class="admin-shell">
    <?php include __DIR__ . '/../app/includes/admin-sidebar.php'; ?>

    <section class="admin-content">
        <header class="admin-page-header">
            <div>
                <p class="section-kicker">Overview</p>
                <h1>Welcome, <?php echo htmlspecialchars($adminUser['fullname'] ?? 'Admin'); ?></h1>
                <p>Business snapshot for Dar Fashion Store — <?php echo date('l, F j, Y'); ?>. Admin tools are separate from customer self-service accounts.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--secondary" href="profile.php">My Admin Account</a>
                <a class="button button--primary" href="../public/index.php">View Storefront</a>
            </div>
        </header>

        <nav class="admin-quick-actions" aria-label="Quick actions">
            <a href="users/index.php">Users & Accounts</a>
            <a href="orders/index.php">Orders</a>
            <a href="products/index.php">Products</a>
            <a href="inventory/index.php">Inventory</a>
            <a href="messages/index.php">Messages</a>
            <a href="reports/index.php">Reports</a>
        </nav>

        <p class="admin-section-title">Key metrics</p>
        <div class="admin-kpi-grid">
            <?php foreach ($primaryStats as $stat): ?>
                <article class="admin-kpi-card <?php echo htmlspecialchars($stat['class']); ?>">
                    <span><?php echo htmlspecialchars($stat['label']); ?></span>
                    <strong><?php echo htmlspecialchars($stat['value']); ?></strong>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="admin-kpi-grid" style="margin-top: 14px;">
            <?php foreach ($secondaryStats as $stat): ?>
                <article class="admin-kpi-card">
                    <span><?php echo htmlspecialchars($stat['label']); ?></span>
                    <strong><?php echo htmlspecialchars($stat['value']); ?></strong>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="admin-section-title">Analytics</p>
        <section class="admin-chart-grid">
            <article class="admin-panel"><h2>Sales per Month</h2><canvas id="salesByMonth" height="120"></canvas></article>
            <article class="admin-panel"><h2>Payment Methods</h2><canvas id="paymentMethods" height="120"></canvas></article>
            <article class="admin-panel admin-panel--wide"><h2>Daily Sales Trend</h2><canvas id="salesTrend" height="100"></canvas></article>
            <article class="admin-panel"><h2>Top Products</h2><canvas id="topProducts" height="120"></canvas></article>
            <article class="admin-panel"><h2>Revenue by Category</h2><canvas id="revenueByCategory" height="120"></canvas></article>
        </section>

        <p class="admin-section-title">Operations</p>
        <section class="admin-grid-two">
            <article class="admin-panel" style="margin-top: 0;">
                <div class="section-heading section-heading--row">
                    <h2>Recent Orders</h2>
                    <a href="orders/index.php">View all</a>
                </div>
                <?php if ($recentOrders): ?>
                    <div class="cart-table">
                        <div class="cart-row cart-row--head"><span>Order</span><span>Customer</span><span>Status</span><span>Total</span></div>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="cart-row">
                                <span><a href="orders/view.php?id=<?php echo (int) $order['id']; ?>"><?php echo htmlspecialchars($order['order_number']); ?></a></span>
                                <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                <span><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                                <span>TZS <?php echo number_format((float) $order['total'], 0); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No orders yet.</p>
                <?php endif; ?>
            </article>

            <article class="admin-panel" style="margin-top: 0;">
                <h2>Low Stock Alerts</h2>
                <?php if ($lowStock): ?>
                    <div class="admin-alert-list">
                        <?php foreach ($lowStock as $product): ?>
                            <p><span><?php echo htmlspecialchars($product['name']); ?></span><strong style="color: var(--primary);"><?php echo (int) $product['stock_quantity']; ?> left</strong></p>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Stock levels look healthy.</p>
                <?php endif; ?>
                <p style="margin-top: 14px;"><a href="inventory/index.php">Open inventory →</a></p>
            </article>
        </section>

        <section class="admin-grid-two">
            <article class="admin-panel" style="margin-top: 0;">
                <div class="section-heading section-heading--row">
                    <h2>Online Now</h2>
                    <a href="users/index.php">Manage users</a>
                </div>
                <?php if ($onlineUsers): ?>
                    <div class="admin-alert-list">
                        <?php foreach ($onlineUsers as $user): ?>
                            <p>
                                <span>
                                    <strong><?php echo htmlspecialchars($user['fullname']); ?></strong>
                                    <span class="admin-role-badge <?php echo htmlspecialchars($user['role'] ?? 'customer'); ?>" style="margin-left: 6px;"><?php echo htmlspecialchars($user['role'] ?? 'customer'); ?></span>
                                </span>
                                <a href="users/index.php?id=<?php echo (int) $user['id']; ?>">View</a>
                            </p>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No active sessions right now.</p>
                <?php endif; ?>
            </article>

            <article class="admin-panel" style="margin-top: 0;">
                <div class="section-heading section-heading--row">
                    <h2>Live Activity</h2>
                    <a href="activities/index.php">Full log</a>
                </div>
                <?php if ($recentActivities): ?>
                    <div class="admin-activity-feed">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="admin-activity-item">
                                <span>
                                    <strong><?php echo htmlspecialchars($activity['fullname']); ?></strong>
                                    <span class="admin-activity-tag"><?php echo htmlspecialchars($activityLabels[$activity['activity_type']] ?? $activity['activity_type']); ?></span>
                                </span>
                                <small style="color: var(--muted);"><?php echo date('H:i', strtotime($activity['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No recent activity.</p>
                <?php endif; ?>
            </article>
        </section>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartData = <?php echo json_encode($chartData, JSON_NUMERIC_CHECK); ?>;
const brandPrimary = '#ff6a00';
const brandSecondary = '#232f3e';
const brandAccent = '#ffd814';
const brandSuccess = '#13795b';
const palette = [brandPrimary, brandSecondary, brandAccent, brandSuccess, '#667085', '#c2410c'];
const labels = (key) => (chartData[key] || []).map((row) => row.label);
const values = (key) => (chartData[key] || []).map((row) => Number(row.value));

const chartDefaults = {
    plugins: { legend: { labels: { color: brandSecondary, font: { weight: '600' } } } },
    scales: {
        x: { ticks: { color: '#667085' }, grid: { color: '#eef2f6' } },
        y: { ticks: { color: '#667085' }, grid: { color: '#eef2f6' } }
    }
};

if (document.getElementById('salesByMonth')) {
    new Chart(document.getElementById('salesByMonth'), {
        type: 'bar',
        data: { labels: labels('salesByMonth'), datasets: [{ label: 'TZS', data: values('salesByMonth'), backgroundColor: brandPrimary, borderRadius: 6 }] },
        options: chartDefaults
    });
}
if (document.getElementById('paymentMethods')) {
    new Chart(document.getElementById('paymentMethods'), {
        type: 'doughnut',
        data: { labels: labels('paymentMethods'), datasets: [{ data: values('paymentMethods'), backgroundColor: palette }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
}
if (document.getElementById('salesTrend')) {
    new Chart(document.getElementById('salesTrend'), {
        type: 'line',
        data: {
            labels: labels('salesTrend'),
            datasets: [{ label: 'TZS', data: values('salesTrend'), borderColor: brandPrimary, backgroundColor: 'rgba(255,106,0,.12)', fill: true, tension: 0.35 }]
        },
        options: chartDefaults
    });
}
if (document.getElementById('topProducts')) {
    new Chart(document.getElementById('topProducts'), {
        type: 'bar',
        data: { labels: labels('topProducts'), datasets: [{ label: 'Units', data: values('topProducts'), backgroundColor: brandSecondary, borderRadius: 6 }] },
        options: { ...chartDefaults, indexAxis: 'y' }
    });
}
if (document.getElementById('revenueByCategory')) {
    new Chart(document.getElementById('revenueByCategory'), {
        type: 'pie',
        data: { labels: labels('revenueByCategory'), datasets: [{ data: values('revenueByCategory'), backgroundColor: palette }] },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
}
</script>
</body>
</html>
