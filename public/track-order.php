<?php
$pageTitle = 'Track Order | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
include __DIR__ . '/../app/includes/header.php';

$database = new Database();
$conn = $database->connect();
$orderNumber = strtoupper(trim($_GET['order_number'] ?? ''));
$email = trim($_GET['email'] ?? '');
$order = null;
$items = [];
$payment = null;
$confirmation = null;
$error = '';

if (($orderNumber !== '' || $email !== '') && $conn) {
    $sql = "SELECT * FROM orders WHERE 1=1";
    $params = [];

    if ($orderNumber !== '') {
        $sql .= " AND order_number = ?";
        $params[] = $orderNumber;
    }

    if ($email !== '') {
        $sql .= " AND customer_email = ?";
        $params[] = $email;
    }

    $sql .= " ORDER BY created_at DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ? LIMIT 1");
        $stmt->execute([$order['id']]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT * FROM order_confirmations WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$order['id']]);
        $confirmation = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = 'No order found. Check the order number and email address.';
    }
}

$steps = [
    'pending' => 'Order Placed',
    'confirmed' => 'Payment Confirmed',
    'processing' => 'Processing Order',
    'shipped' => 'Shipped',
    'in_transit' => 'In Transit',
    'out_for_delivery' => 'Out for Delivery',
    'delivered' => 'Delivered'
];
$currentStatus = $order['status'] ?? '';
$stepKeys = array_keys($steps);
$currentIndex = array_search($currentStatus, $stepKeys, true);
if ($currentStatus === 'cancelled') {
    $currentIndex = -1;
}
?>
<main class="section">
    <div class="container checkout-layout">
        <form class="checkout-form" method="get">
            <p class="section-kicker">Track order</p>
            <h1>Check your delivery progress</h1>
            <p class="form-hint">Use demo order <strong>DFS-1001</strong> with <strong>amina@example.com</strong>.</p>
            <?php if ($error): ?><p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
            <label>Order Number<input name="order_number" value="<?php echo htmlspecialchars($orderNumber); ?>" placeholder="DFS-1001"></label>
            <label>Email Address<input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="amina@example.com"></label>
            <button class="button button--primary" type="submit">Track Order</button>
        </form>

        <aside class="summary-panel">
            <h2>Tracking Details</h2>
            <?php if ($order): ?>
                <p><span>Order</span><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></p>
                <p><span>Status</span><strong><?php echo htmlspecialchars(ucfirst($order['status'])); ?></strong></p>
                <p><span>Payment</span><strong><?php echo htmlspecialchars($payment['payment_status'] ?? 'pending'); ?></strong></p>
                <p><span>Estimated Delivery</span><strong><?php echo (int) ($order['estimated_delivery_days'] ?? 1); ?> day(s)</strong></p>
                <p><span>Total</span><strong>TZS <?php echo number_format((float) $order['total'], 0); ?></strong></p>
            <?php else: ?>
                <p>Enter your order details to see tracking information.</p>
            <?php endif; ?>
        </aside>
    </div>

    <?php if ($order): ?>
        <section class="container section">
            <div class="tracking-steps">
                <?php foreach ($steps as $key => $label): ?>
                    <?php $isDone = $currentIndex !== false && $currentIndex >= array_search($key, $stepKeys, true); ?>
                    <article class="<?php echo $isDone ? 'tracking-step is-done' : 'tracking-step'; ?>">
                        <strong><?php echo htmlspecialchars($label); ?></strong>
                        <span><?php echo $isDone ? 'Completed' : 'Pending'; ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="container cart-table">
            <div class="cart-row cart-row--head"><span>Product</span><span>Qty</span><span>Price</span><span>Total</span><span>Status</span></div>
            <?php foreach ($items as $item): ?>
                <div class="cart-row">
                    <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                    <span><?php echo (int) $item['quantity']; ?></span>
                    <span>TZS <?php echo number_format((float) $item['price'], 0); ?></span>
                    <span>TZS <?php echo number_format((float) $item['line_total'], 0); ?></span>
                    <span><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="container section">
            <div class="summary-panel">
                <h2>Order Process</h2>
                <p><span>Shipping Address</span><strong><?php echo htmlspecialchars($order['shipping_address']); ?></strong></p>
                <p><span>Delivery Company</span><strong><?php echo htmlspecialchars($order['delivery_company'] ?? 'To be assigned'); ?></strong></p>
                <p><span>Delivery Updates</span><strong><?php echo htmlspecialchars($order['delivery_notes'] ?? 'Your order update will appear here after the admin processes it.'); ?></strong></p>
                <p><span>Confirmation</span><strong><?php echo htmlspecialchars($confirmation['status'] ?? 'pending'); ?></strong></p>
                <p><span>Message</span><strong><?php echo htmlspecialchars($confirmation['message'] ?? 'Confirmation will be sent after checkout.'); ?></strong></p>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
