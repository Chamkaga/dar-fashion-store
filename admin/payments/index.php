<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$payments = $conn ? $conn->query("SELECT payments.*, orders.order_number, orders.customer_name FROM payments LEFT JOIN orders ON orders.id = payments.order_id ORDER BY payments.created_at DESC")->fetchAll(PDO::FETCH_ASSOC) : [];

$adminPage = 'payments';
$adminRoot = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments Management | Dar Fashion Store Admin</title>
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
                <p class="section-kicker">Payment Management</p>
                <h1>Payments</h1>
                <p>View and manage all payment transactions.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--primary" href="../dashboard.php">Dashboard</a>
            </div>
        </header>

        <div class="admin-panel" style="margin-top: 0;">
            <div class="cart-table">
                <div class="cart-row cart-row--head">
                    <span>Order</span>
                    <span>Customer</span>
                    <span>Method</span>
                    <span>Status</span>
                    <span>Transaction ID</span>
                    <span>Amount</span>
                </div>
                <?php foreach ($payments as $payment): ?>
                    <div class="cart-row">
                        <span><strong><?php echo htmlspecialchars($payment['order_number'] ?? 'Order'); ?></strong></span>
                        <span><?php echo htmlspecialchars($payment['customer_name'] ?? 'Customer'); ?></span>
                        <span><?php echo htmlspecialchars($payment['method']); ?></span>
                        <span><?php echo htmlspecialchars($payment['payment_status']); ?></span>
                        <span><?php echo htmlspecialchars($payment['transaction_ref'] ?? 'Pending'); ?></span>
                        <span style="color: var(--primary); font-weight: 600;">TZS <?php echo number_format((float) $payment['amount'], 0); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>
