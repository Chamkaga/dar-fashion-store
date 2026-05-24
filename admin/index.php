<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_admin('login.php');

$stats = [
    ['label' => 'Total Revenue', 'value' => 'TZS 8.4M'],
    ['label' => 'Total Orders', 'value' => '312'],
    ['label' => 'Customers', 'value' => '1,248'],
    ['label' => 'Products', 'value' => '96'],
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
        <a class="logo" href="index.php"><span class="logo__mark">DF</span><span class="logo__text">Admin</span></a>
        <nav>
            <a href="index.php">Dashboard</a>
            <a href="products/index.php">Products</a>
            <a href="orders/index.php">Orders</a>
            <a href="users/index.php">Users</a>
            <a href="../analytics/insights.txt">Analytics</a>
            <a href="../public/index.php">View Store</a>
        </nav>
    </aside>
    <section class="admin-content">
        <div class="section-heading">
            <p class="section-kicker">Admin dashboard</p>
            <h1>Store overview</h1>
        </div>
        <div class="benefit-grid">
            <?php foreach ($stats as $stat): ?>
                <article class="benefit-card"><strong><?php echo $stat['value']; ?></strong><span><?php echo $stat['label']; ?></span></article>
            <?php endforeach; ?>
        </div>
        <section class="admin-panel">
            <h2>Sales Analytics</h2>
            <div class="chart-placeholder">
                <span style="height: 42%"></span>
                <span style="height: 65%"></span>
                <span style="height: 50%"></span>
                <span style="height: 82%"></span>
                <span style="height: 70%"></span>
                <span style="height: 92%"></span>
            </div>
        </section>
        <section class="admin-panel">
            <h2>Recent Orders</h2>
            <div class="cart-table">
                <div class="cart-row cart-row--head"><span>Order</span><span>Qty</span><span>Status</span><span>Total</span><span>Action</span></div>
                <div class="cart-row"><span>#DFS-1024</span><span>2</span><span>Paid</span><span>TZS 125,000</span><a href="orders/view.php">View</a></div>
                <div class="cart-row"><span>#DFS-1025</span><span>1</span><span>Pending</span><span>TZS 48,000</span><a href="orders/view.php">View</a></div>
            </div>
        </section>
    </section>
</main>
</body>
</html>
