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
                <p class="section-kicker">Business Overview</p>
                <h1>Welcome back, <?php echo htmlspecialchars($adminUser['fullname'] ?? 'Admin'); ?></h1>
                <p>Real-time business insights for Dar Fashion Store — <?php echo date('l, F j, Y'); ?>. Manage your store operations efficiently.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--secondary" href="profile.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    My Account
                </a>
                <a class="button button--primary" href="../public/index.php">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    View Store
                </a>
            </div>
        </header>

        <nav class="admin-quick-actions" aria-label="Quick actions">
            <a href="users/index.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Users & Accounts
            </a>
            <a href="orders/index.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                Orders
            </a>
            <a href="products/index.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <path d="M16 10a4 4 0 0 1-8 0"></path>
                </svg>
                Products
            </a>
            <a href="inventory/index.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                Inventory
            </a>
            <a href="messages/index.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                Messages
            </a>
            <a href="reports/index.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                Reports
            </a>
        </nav>

        <p class="admin-section-title">Key Performance Metrics</p>
        <div class="admin-kpi-grid">
            <?php foreach ($primaryStats as $stat): ?>
                <article class="admin-kpi-card <?php echo htmlspecialchars($stat['class']); ?>">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span><?php echo htmlspecialchars($stat['label']); ?></span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary); opacity: 0.3;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </div>
                    <strong><?php echo htmlspecialchars($stat['value']); ?></strong>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="admin-kpi-grid" style="margin-top: 18px;">
            <?php foreach ($secondaryStats as $stat): ?>
                <article class="admin-kpi-card">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span><?php echo htmlspecialchars($stat['label']); ?></span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--muted); opacity: 0.4;">
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                    </div>
                    <strong><?php echo htmlspecialchars($stat['value']); ?></strong>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="admin-section-title">Analytics & Insights</p>
        <section class="admin-chart-grid">
            <article class="admin-panel">
                <div class="section-heading section-heading--row">
                    <h2>Sales Overview</h2>
                    <span style="font-size: 0.85rem; color: var(--muted);">Monthly Performance</span>
                </div>
                <canvas id="salesByMonth" height="120"></canvas>
            </article>
            <article class="admin-panel">
                <div class="section-heading section-heading--row">
                    <h2>Payment Methods</h2>
                    <span style="font-size: 0.85rem; color: var(--muted);">Distribution</span>
                </div>
                <canvas id="paymentMethods" height="120"></canvas>
            </article>
            <article class="admin-panel admin-panel--wide">
                <div class="section-heading section-heading--row">
                    <h2>Daily Sales Trend</h2>
                    <span style="font-size: 0.85rem; color: var(--muted);">Last 14 Days</span>
                </div>
                <canvas id="salesTrend" height="100"></canvas>
            </article>
            <article class="admin-panel">
                <div class="section-heading section-heading--row">
                    <h2>Top Products</h2>
                    <span style="font-size: 0.85rem; color: var(--muted);">Best Sellers</span>
                </div>
                <canvas id="topProducts" height="120"></canvas>
            </article>
            <article class="admin-panel">
                <div class="section-heading section-heading--row">
                    <h2>Revenue by Category</h2>
                    <span style="font-size: 0.85rem; color: var(--muted);">Performance</span>
                </div>
                <canvas id="revenueByCategory" height="120"></canvas>
            </article>
        </section>

        <p class="admin-section-title">Operations Management</p>
        <section class="admin-grid-two">
            <article class="admin-panel" style="margin-top: 0;">
                <div class="section-heading section-heading--row">
                    <div>
                        <h2>Recent Orders</h2>
                        <span style="font-size: 0.85rem; color: var(--muted);">Latest transactions</span>
                    </div>
                    <a href="orders/index.php" class="button button--secondary" style="padding: 8px 16px; min-height: 36px; font-size: 0.85rem;">View All</a>
                </div>
                <?php if ($recentOrders): ?>
                    <div class="cart-table">
                        <div class="cart-row cart-row--head"><span>Order</span><span>Customer</span><span>Status</span><span>Total</span></div>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="cart-row">
                                <span><a href="orders/view.php?id=<?php echo (int) $order['id']; ?>" style="color: var(--primary); font-weight: 600;"><?php echo htmlspecialchars($order['order_number']); ?></a></span>
                                <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                <span><span class="status-pill status-pill--<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span></span>
                                <span style="font-weight: 600;">TZS <?php echo number_format((float) $order['total'], 0); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No orders yet.</p>
                <?php endif; ?>
            </article>

            <article class="admin-panel" style="margin-top: 0;">
                <div class="section-heading section-heading--row">
                    <div>
                        <h2>Low Stock Alerts</h2>
                        <span style="font-size: 0.85rem; color: var(--muted);">Items needing attention</span>
                    </div>
                    <a href="inventory/index.php" class="button button--secondary" style="padding: 8px 16px; min-height: 36px; font-size: 0.85rem;">Manage</a>
                </div>
                <?php if ($lowStock): ?>
                    <div class="admin-alert-list">
                        <?php foreach ($lowStock as $product): ?>
                            <p style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin: 0; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                                <span style="font-weight: 500;"><?php echo htmlspecialchars($product['name']); ?></span>
                                <strong style="color: var(--error); background: var(--error-light); padding: 4px 12px; border-radius: 999px; font-size: 0.85rem;"><?php echo (int) $product['stock_quantity']; ?> left</strong>
                            </p>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state" style="color: var(--success); background: var(--success-light); border-color: var(--success);">✓ Stock levels healthy</p>
                <?php endif; ?>
            </article>
        </section>

        <section class="admin-grid-two">
            <article class="admin-panel" style="margin-top: 0;">
                <div class="section-heading section-heading--row">
                    <div>
                        <h2>Online Users</h2>
                        <span style="font-size: 0.85rem; color: var(--muted);">Active sessions</span>
                    </div>
                    <a href="users/index.php" class="button button--secondary" style="padding: 8px 16px; min-height: 36px; font-size: 0.85rem;">Manage</a>
                </div>
                <?php if ($onlineUsers): ?>
                    <div class="admin-alert-list">
                        <?php foreach ($onlineUsers as $user): ?>
                            <p style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin: 0; border-bottom: 1px solid var(--border); padding-bottom: 12px;">
                                <span style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success);"></div>
                                    <strong><?php echo htmlspecialchars($user['fullname']); ?></strong>
                                    <span class="admin-role-badge <?php echo htmlspecialchars($user['role'] ?? 'customer'); ?>" style="margin-left: 6px;"><?php echo htmlspecialchars($user['role'] ?? 'customer'); ?></span>
                                </span>
                                <a href="users/index.php?id=<?php echo (int) $user['id']; ?>" style="color: var(--primary); font-weight: 600; font-size: 0.85rem;">View</a>
                            </p>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="empty-state">No active sessions right now.</p>
                <?php endif; ?>
            </article>

            <article class="admin-panel" style="margin-top: 0;">
                <div class="section-heading section-heading--row">
                    <div>
                        <h2>Live Activity Feed</h2>
                        <span style="font-size: 0.85rem; color: var(--muted);">Real-time actions</span>
                    </div>
                    <a href="activities/index.php" class="button button--secondary" style="padding: 8px 16px; min-height: 36px; font-size: 0.85rem;">Full Log</a>
                </div>
                <?php if ($recentActivities): ?>
                    <div class="admin-activity-feed">
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="admin-activity-item" style="display: flex; justify-content: space-between; gap: 12px; padding: 12px; border: 1px solid var(--border-light); border-radius: var(--radius); background: var(--background); font-size: 0.88rem;">
                                <span style="display: flex; align-items: center; gap: 10px;">
                                    <strong style="color: var(--secondary);"><?php echo htmlspecialchars($activity['fullname']); ?></strong>
                                    <span class="admin-activity-tag" style="display: inline-block; margin-right: 6px; padding: 3px 10px; border-radius: 4px; background: var(--primary-light); color: var(--primary); font-size: 0.75rem; font-weight: 700;"><?php echo htmlspecialchars($activityLabels[$activity['activity_type']] ?? $activity['activity_type']); ?></span>
                                </span>
                                <small style="color: var(--muted); font-size: 0.8rem;"><?php echo date('H:i', strtotime($activity['created_at'])); ?></small>
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
