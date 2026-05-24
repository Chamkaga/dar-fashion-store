<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$orders = $conn ? $conn->query("SELECT * FROM orders ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Orders</title><link rel="stylesheet" href="../../assets/css/style.css"><link rel="stylesheet" href="../../assets/css/responsive.css"></head>
<body><main class="section"><div class="container">
<div class="section-heading"><p class="section-kicker">Admin</p><h1>Orders</h1></div>
<div class="cart-table">
    <div class="cart-row cart-row--head"><span>Order</span><span>Customer</span><span>Status</span><span>Total</span><span>Action</span></div>
    <?php foreach ($orders as $order): ?>
        <div class="cart-row">
            <span><?php echo htmlspecialchars($order['order_number']); ?></span>
            <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
            <span><?php echo htmlspecialchars($order['status']); ?></span>
            <span>TZS <?php echo number_format((float) $order['total'], 0); ?></span>
            <a href="view.php?id=<?php echo (int) $order['id']; ?>">View</a>
        </div>
    <?php endforeach; ?>
</div>
<p><a href="../index.php">Back to dashboard</a></p>
</div></main></body></html>
