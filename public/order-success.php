<?php
$pageTitle = 'Order Success | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
include __DIR__ . '/../app/includes/header.php';
$database = new Database();
$conn = $database->connect();
$order = null;
$payment = null;
$orderId = (int) ($_GET['order_id'] ?? ($_SESSION['last_order_id'] ?? 0));

if ($conn && $orderId > 0) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<main class="section">
    <div class="container success-panel">
        <p class="section-kicker">Order placed</p>
        <h1>Thank you for shopping with Dar Fashion Store.</h1>
        <?php if ($order): ?>
            <p>Your order <strong><?php echo htmlspecialchars($order['order_number']); ?></strong> has been received.</p>
            <p>Total: <strong>TZS <?php echo number_format((float) $order['total'], 0); ?></strong></p>
            <p>Payment: <strong><?php echo htmlspecialchars($payment['method'] ?? 'demo'); ?></strong> / <strong><?php echo htmlspecialchars($payment['payment_status'] ?? 'pending'); ?></strong></p>
            <p>Estimated delivery: <strong><?php echo (int) ($order['estimated_delivery_days'] ?? 3); ?>–<?php echo (int) ($order['estimated_delivery_days'] ?? 3) + 2; ?> days</strong> (Dar es Salaam → your address).</p>
            <p>
                <a class="button button--primary" href="track-order.php?order_number=<?php echo urlencode($order['order_number']); ?>&email=<?php echo urlencode($order['customer_email']); ?>">Track your parcel</a>
            </p>
            <p><a href="profile.php?tab=orders">View in my account</a></p>
        <?php else: ?>
            <p>Your order has been received. We will send confirmation and delivery updates to your contact details.</p>
        <?php endif; ?>
        <a class="button button--primary" href="shop.php">Continue Shopping</a>
    </div>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
