<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$dailySales = $conn ? $conn->query("SELECT DATE(created_at) AS report_date, COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS revenue FROM orders GROUP BY DATE(created_at) ORDER BY report_date DESC LIMIT 14")->fetchAll(PDO::FETCH_ASSOC) : [];
$topCustomers = $conn ? $conn->query("SELECT customer_name, customer_email, COUNT(*) AS orders_count, COALESCE(SUM(total), 0) AS revenue FROM orders GROUP BY customer_email, customer_name ORDER BY revenue DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC) : [];

$adminPage = 'reports';
$adminRoot = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | Dar Fashion Store Admin</title>
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
                <p class="section-kicker">Business Intelligence</p>
                <h1>Reports & Analytics</h1>
                <p>Detailed sales reports and customer analytics.</p>
            </div>
            <div class="admin-page-actions">
                <button class="button button--secondary" onclick="window.print()">Export PDF</button>
                <a class="button button--primary" href="../dashboard.php">Dashboard</a>
            </div>
        </header>

        <div class="admin-grid-two">
            <section class="admin-panel" style="margin-top: 0;">
                <h2>Daily Sales Report</h2>
                <p style="color: var(--muted); margin-bottom: 16px;">Sales performance over the last 14 days.</p>
                <div class="cart-table">
                    <div class="cart-row cart-row--head">
                        <span>Date</span>
                        <span>Orders</span>
                        <span>Revenue</span>
                        <span>Report</span>
                        <span>Status</span>
                    </div>
                    <?php foreach ($dailySales as $row): ?>
                        <div class="cart-row">
                            <span><strong><?php echo htmlspecialchars($row['report_date']); ?></strong></span>
                            <span><?php echo (int) $row['orders_count']; ?></span>
                            <span style="color: var(--primary); font-weight: 600;">TZS <?php echo number_format((float) $row['revenue'], 0); ?></span>
                            <span>Daily sales</span>
                            <span><span style="color: #13795b; font-weight: 600;">Ready</span></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="admin-panel" style="margin-top: 0;">
                <h2>Top Customers</h2>
                <p style="color: var(--muted); margin-bottom: 16px;">Highest revenue-generating customers.</p>
                <div class="cart-table">
                    <div class="cart-row cart-row--head">
                        <span>Name</span>
                        <span>Email</span>
                        <span>Orders</span>
                        <span>Revenue</span>
                        <span>Status</span>
                    </div>
                    <?php foreach ($topCustomers as $row): ?>
                        <div class="cart-row">
                            <span><strong><?php echo htmlspecialchars($row['customer_name']); ?></strong></span>
                            <span><?php echo htmlspecialchars($row['customer_email']); ?></span>
                            <span><?php echo (int) $row['orders_count']; ?></span>
                            <span style="color: var(--primary); font-weight: 600;">TZS <?php echo number_format((float) $row['revenue'], 0); ?></span>
                            <span><span style="color: #13795b; font-weight: 600;">Ready</span></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </section>
</main>
</body>
</html>
