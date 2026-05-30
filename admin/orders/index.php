<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$orders = $conn ? $conn->query("
    SELECT orders.*, payments.payment_status, payments.method,
           GROUP_CONCAT(CONCAT(order_items.product_name, ' x', order_items.quantity) SEPARATOR ', ') AS products_ordered
    FROM orders
    LEFT JOIN payments ON payments.order_id = orders.id
    LEFT JOIN order_items ON order_items.order_id = orders.id
    GROUP BY orders.id, payments.id
    ORDER BY orders.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC) : [];

$adminPage = 'orders';
$adminRoot = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management | Dar Fashion Store Admin</title>
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
                <p class="section-kicker">Order Management</p>
                <h1>Orders</h1>
                <p>View and manage all customer orders.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--primary" href="../dashboard.php">Dashboard</a>
            </div>
        </header>

        <div class="admin-panel" style="margin-top: 0;">
            <div class="cart-table">
                <div class="cart-row admin-orders-row cart-row--head">
                    <span>Order ID</span>
                    <span>Customer</span>
                    <span>Phone</span>
                    <span>Location</span>
                    <span>Products</span>
                    <span>Total</span>
                    <span>Payment</span>
                    <span>Delivery</span>
                    <span>Date</span>
                    <span>Action</span>
                </div>
                <?php foreach ($orders as $order): ?>
                    <div class="cart-row admin-orders-row">
                        <span><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></span>
                        <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                        <span><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                        <span><?php echo htmlspecialchars($order['shipping_address']); ?></span>
                        <span><?php echo htmlspecialchars($order['products_ordered'] ?? 'No items'); ?></span>
                        <span style="color: var(--primary); font-weight: 600;">TZS <?php echo number_format((float) $order['total'], 0); ?></span>
                        <span><?php echo htmlspecialchars($order['payment_status'] ?? 'pending'); ?></span>
                        <span><?php echo htmlspecialchars($order['status']); ?></span>
                        <span><?php echo htmlspecialchars(date('M d, Y', strtotime($order['created_at']))); ?></span>
                        <a class="button button--small button--primary" href="view.php?id=<?php echo (int) $order['id']; ?>">View</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>
