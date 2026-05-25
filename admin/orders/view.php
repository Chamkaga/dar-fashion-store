<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$id = (int) ($_GET['id'] ?? 0);
$statusMessage = '';

if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $allowedStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    $status = $_POST['status'] ?? '';
    if (in_array($status, $allowedStatuses, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $statusMessage = 'Order status updated.';
    }
}

$stmt = $conn->prepare("SELECT * FROM orders WHERE id=?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
$items = [];
$payment = null;
if ($order) {
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id=?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id=?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Order Details</title><link rel="stylesheet" href="../../assets/css/style.css"></head>
<body><main class="section"><div class="container">
<?php if ($order): ?>
<div class="section-heading"><p class="section-kicker">Order details</p><h1><?php echo htmlspecialchars($order['order_number']); ?></h1></div>
<?php if ($statusMessage): ?><p class="alert alert--success"><?php echo htmlspecialchars($statusMessage); ?></p><?php endif; ?>
<section class="summary-panel">
    <p><span>Customer</span><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
    <p><span>Email</span><strong><?php echo htmlspecialchars($order['customer_email']); ?></strong></p>
    <p><span>Payment</span><strong><?php echo htmlspecialchars($payment['method'] ?? 'pending'); ?></strong></p>
    <p class="summary-total"><span>Total</span><strong>TZS <?php echo number_format((float) $order['total'], 0); ?></strong></p>
</section>
<form class="admin-status-form" method="post">
    <label>Order Status
        <select name="status">
            <?php foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                <option value="<?php echo $status; ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button class="button button--primary" type="submit">Update Status</button>
</form>
<div class="cart-table">
    <div class="cart-row cart-row--head"><span>Product</span><span>Qty</span><span>Price</span><span>Total</span><span>Status</span></div>
    <?php foreach ($items as $item): ?>
        <div class="cart-row"><span><?php echo htmlspecialchars($item['product_name']); ?></span><span><?php echo (int) $item['quantity']; ?></span><span>TZS <?php echo number_format((float) $item['price'], 0); ?></span><span>TZS <?php echo number_format((float) $item['line_total'], 0); ?></span><span><?php echo htmlspecialchars($order['status']); ?></span></div>
    <?php endforeach; ?>
</div>
<?php else: ?><p class="empty-state">Order not found.</p><?php endif; ?>
<p><a href="index.php">Back to orders</a></p>
</div></main></body></html>
