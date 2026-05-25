<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$payments = $conn ? $conn->query("SELECT payments.*, orders.order_number, orders.customer_name FROM payments LEFT JOIN orders ON orders.id = payments.order_id ORDER BY payments.created_at DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Payments</title><link rel="stylesheet" href="../../assets/css/style.css"><link rel="stylesheet" href="../../assets/css/responsive.css"></head>
<body><main class="section"><div class="container"><div class="section-heading"><p class="section-kicker">Admin</p><h1>Payments</h1></div><div class="cart-table"><div class="cart-row cart-row--head"><span>Order</span><span>Customer</span><span>Method</span><span>Status</span><span>Transaction ID</span><span>Amount</span></div><?php foreach ($payments as $payment): ?><div class="cart-row"><span><?php echo htmlspecialchars($payment['order_number'] ?? 'Order'); ?></span><span><?php echo htmlspecialchars($payment['customer_name'] ?? 'Customer'); ?></span><span><?php echo htmlspecialchars($payment['method']); ?></span><span><?php echo htmlspecialchars($payment['payment_status']); ?></span><span><?php echo htmlspecialchars($payment['transaction_ref'] ?? 'Pending'); ?></span><span>TZS <?php echo number_format((float) $payment['amount'], 0); ?></span></div><?php endforeach; ?></div><p><a href="../dashboard.php">Back to dashboard</a></p></div></main></body></html>
