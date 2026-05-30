<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();

// Demo visitor statistics (simulated Google Analytics data)
$visitorStats = [
    'total_visitors' => 15420,
    'unique_visitors' => 8750,
    'page_views' => 45230,
    'avg_session_duration' => '4:32',
    'bounce_rate' => '42%',
    'returning_visitors' => 35
];

// Most viewed products (sample data based on activity logs)
$mostViewedProducts = [];
if ($conn) {
    $mostViewedProducts = $conn->query("
        SELECT p.id, p.name, p.image, p.price, COUNT(ua.id) as view_count
        FROM products p
        LEFT JOIN user_activities ua ON ua.product_id = p.id AND ua.activity_type = 'view_product'
        GROUP BY p.id, p.name, p.image, p.price
        ORDER BY view_count DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // If no activity data, use sample data
    if (empty($mostViewedProducts)) {
        $mostViewedProducts = $conn->query("
            SELECT id, name, image, price, 
                   FLOOR(RAND() * 500) + 100 as view_count
            FROM products
            ORDER BY view_count DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Sales summary
$salesSummary = [];
if ($conn) {
    $salesSummary = [
        'today_sales' => $conn->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE(created_at) = CURDATE() AND payment_status = 'verified'")->fetchColumn(),
        'week_sales' => $conn->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND payment_status = 'verified'")->fetchColumn(),
        'month_sales' => $conn->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND payment_status = 'verified'")->fetchColumn(),
        'total_sales' => $conn->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'verified'")->fetchColumn(),
        'today_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        'week_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn(),
        'month_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
        'total_orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    ];
}

// Top selling products
$topSellingProducts = [];
if ($conn) {
    $topSellingProducts = $conn->query("
        SELECT p.id, p.name, p.image, p.price, 
               SUM(oi.quantity) as total_sold,
               SUM(oi.line_total) as total_revenue
        FROM products p
        LEFT JOIN order_items oi ON oi.product_id = p.id
        LEFT JOIN orders o ON o.id = oi.order_id
        GROUP BY p.id, p.name, p.image, p.price
        HAVING total_sold > 0
        ORDER BY total_sold DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// Low stock variants (alert)
$lowStockVariants = [];
if ($conn) {
    $lowStockVariants = $conn->query(
        "SELECT v.id as variant_id, v.sku, v.color, v.size, v.stock_quantity, p.id as product_id, p.name as product_name
         FROM product_variants v
         JOIN products p ON p.id = v.product_id
         WHERE v.stock_quantity <= 5
         ORDER BY v.stock_quantity ASC
         LIMIT 20"
    )->fetchAll(PDO::FETCH_ASSOC);
}

$adminPage = 'analytics';
$adminRoot = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | Dar Fashion Store Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>
<body>
<main class="admin-shell">
    <?php include __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>
    
    <section class="admin-content">
        <header class="admin-page-header">
            <div>
                <p class="section-kicker">Analytics & Insights</p>
                <h1>Website Analytics</h1>
                <p>Track visitor behavior, product performance, and sales metrics. Data simulated for demonstration.</p>
            </div>
            <div class="admin-page-actions">
                <span class="admin-badge admin-badge--info">Google Analytics Integration</span>
                <span class="admin-badge admin-badge--info">Power BI Ready</span>
            </div>
        </header>

        <!-- Visitor Statistics -->
        <section class="admin-panel" style="margin-top: 0;">
            <h2>📊 Visitor Statistics (Google Analytics Simulation)</h2>
            <div class="admin-kpi-grid">
                <article class="admin-kpi-card admin-kpi-card--primary">
                    <span>Total Visitors</span>
                    <strong><?php echo number_format($visitorStats['total_visitors']); ?></strong>
                    <small>All time</small>
                </article>
                <article class="admin-kpi-card">
                    <span>Unique Visitors</span>
                    <strong><?php echo number_format($visitorStats['unique_visitors']); ?></strong>
                    <small>Distinct users</small>
                </article>
                <article class="admin-kpi-card">
                    <span>Page Views</span>
                    <strong><?php echo number_format($visitorStats['page_views']); ?></strong>
                    <small>Total views</small>
                </article>
                <article class="admin-kpi-card">
                    <span>Avg Session</span>
                    <strong><?php echo htmlspecialchars($visitorStats['avg_session_duration']); ?></strong>
                    <small>Duration</small>
                </article>
                <article class="admin-kpi-card">
                    <span>Bounce Rate</span>
                    <strong><?php echo htmlspecialchars($visitorStats['bounce_rate']); ?></strong>
                    <small>Single page visits</small>
                </article>
                <article class="admin-kpi-card admin-kpi-card--revenue">
                    <span>Returning Visitors</span>
                    <strong><?php echo $visitorStats['returning_visitors']; ?>%</strong>
                    <small>Loyalty rate</small>
                </article>
            </div>
        </section>

        <!-- Sales Summary -->
        <section class="admin-panel">
            <h2>💰 Sales Summary</h2>
            <div class="admin-kpi-grid">
                <article class="admin-kpi-card admin-kpi-card--revenue">
                    <span>Today's Sales</span>
                    <strong>TZS <?php echo number_format((float) $salesSummary['today_sales'], 0); ?></strong>
                    <small><?php echo (int) $salesSummary['today_orders']; ?> orders</small>
                </article>
                <article class="admin-kpi-card">
                    <span>Week Sales</span>
                    <strong>TZS <?php echo number_format((float) $salesSummary['week_sales'], 0); ?></strong>
                    <small><?php echo (int) $salesSummary['week_orders']; ?> orders</small>
                </article>
                <article class="admin-kpi-card">
                    <span>Month Sales</span>
                    <strong>TZS <?php echo number_format((float) $salesSummary['month_sales'], 0); ?></strong>
                    <small><?php echo (int) $salesSummary['month_orders']; ?> orders</small>
                </article>
                <article class="admin-kpi-card admin-kpi-card--revenue">
                    <span>Total Sales</span>
                    <strong>TZS <?php echo number_format((float) $salesSummary['total_sales'], 0); ?></strong>
                    <small><?php echo (int) $salesSummary['total_orders']; ?> orders</small>
                </article>
            </div>
        </section>

        <!-- Most Viewed Products -->
        <section class="admin-panel">
            <h2>👁️ Most Viewed Products</h2>
            <p class="admin-panel__subtitle">Products with highest view count based on user activity</p>
            <div class="cart-table">
                <div class="cart-row cart-row--head">
                    <span>Product</span><span>Price</span><span>Views</span><span>Trend</span>
                </div>
                <?php foreach ($mostViewedProducts as $product): ?>
                    <div class="cart-row">
                        <span>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px;">
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            </div>
                        </span>
                        <span>TZS <?php echo number_format((float) $product['price'], 0); ?></span>
                        <span><?php echo number_format((int) $product['view_count']); ?></span>
                        <span>
                            <?php if ($product['view_count'] > 300): ?>
                                <span style="color: var(--success);">🔥 Hot</span>
                            <?php elseif ($product['view_count'] > 200): ?>
                                <span style="color: var(--primary);">📈 Rising</span>
                            <?php else: ?>
                                <span style="color: var(--muted);">📊 Stable</span>
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Top Selling Products -->
        <section class="admin-panel">
            <h2>🏆 Top Selling Products</h2>
            <p class="admin-panel__subtitle">Products with highest sales volume and revenue</p>
            <div class="cart-table">
                <div class="cart-row cart-row--head">
                    <span>Product</span><span>Price</span><span>Units Sold</span><span>Revenue</span>
                </div>
                <?php foreach ($topSellingProducts as $product): ?>
                    <div class="cart-row">
                        <span>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="" style="width: 48px; height: 48px; object-fit: cover; border-radius: 4px;">
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            </div>
                        </span>
                        <span>TZS <?php echo number_format((float) $product['price'], 0); ?></span>
                        <span><?php echo number_format((int) $product['total_sold']); ?></span>
                        <span>TZS <?php echo number_format((float) $product['total_revenue'], 0); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Low Stock Alerts -->
        <section class="admin-panel">
            <h2>⚠️ Low Stock Variants</h2>
            <p class="admin-panel__subtitle">Variants with low inventory (<= 5 units)</p>
            <?php if ($lowStockVariants): ?>
                <div class="cart-table">
                    <div class="cart-row cart-row--head">
                        <span>Product</span><span>Variant</span><span>SKU</span><span>Stock</span>
                    </div>
                    <?php foreach ($lowStockVariants as $v): ?>
                        <div class="cart-row">
                            <span>
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <strong><?php echo htmlspecialchars($v['product_name']); ?></strong>
                                </div>
                            </span>
                            <span><?php echo htmlspecialchars(trim(($v['color'] ? $v['color'] . ' ' : '') . ($v['size'] ? $v['size'] : ''))); ?></span>
                            <span><?php echo htmlspecialchars($v['sku']); ?></span>
                            <span style="color: <?php echo ((int)$v['stock_quantity'] <= 2) ? 'var(--danger)' : 'var(--muted)'; ?>;"><?php echo (int)$v['stock_quantity']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No low-stock variants detected.</p>
            <?php endif; ?>
        </section>

        <!-- Integration Notes -->
        <section class="admin-panel">
            <h2>🔗 Integration Notes</h2>
            <div class="admin-grid-two">
                <div>
                    <h3>Google Analytics Integration</h3>
                    <p>To enable real Google Analytics tracking:</p>
                    <ol style="margin-left: 20px;">
                        <li>Create a Google Analytics account and property</li>
                        <li>Add the tracking code to the website header</li>
                        <li>Configure goals for conversions (orders, signups)</li>
                        <li>Set up e-commerce tracking for product views and purchases</li>
                    </ol>
                    <p><strong>Current Status:</strong> Simulated data for demonstration purposes</p>
                </div>
                <div>
                    <h3>Microsoft Power BI Integration</h3>
                    <p>To enable Power BI reporting:</p>
                    <ol style="margin-left: 20px;">
                        <li>Set up Power BI service account</li>
                        <li>Connect to MySQL database via Power BI Gateway</li>
                        <li>Create data models for sales, visitors, and products</li>
                        <li>Build dashboards and reports for business insights</li>
                    </ol>
                    <p><strong>Current Status:</strong> Database schema ready for Power BI connection</p>
                </div>
            </div>
        </section>
    </section>
</main>
</body>
</html>
