<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$id = (int) ($_GET['id'] ?? 0);
<<<<<<< HEAD
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Security token expired. Please try again.';
    } else {
        $status = $_POST['status'] ?? 'pending';
        $allowed = ['pending', 'confirmed', 'processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }
        try {
            $stmt = $conn->prepare("UPDATE orders SET status = ?, delivery_company = ?, delivery_notes = ?, estimated_delivery_days = ? WHERE id = ?");
            $stmt->execute([$status, trim($_POST['delivery_company'] ?? ''), trim($_POST['delivery_notes'] ?? ''), (int) ($_POST['estimated_delivery_days'] ?? 1), $id]);
            $message = 'Tracking updated successfully.';
        } catch (Throwable $e) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $message = 'Status updated. Add tracking columns from database/ecommerce.sql for delivery notes and courier fields.';
        }
    }
}
=======
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

>>>>>>> dffcd92dcb69c10beb0fb71be3046d8012e923b0
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
<<<<<<< HEAD
<?php if ($message): ?><p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
=======
<?php if ($statusMessage): ?><p class="alert alert--success"><?php echo htmlspecialchars($statusMessage); ?></p><?php endif; ?>
>>>>>>> dffcd92dcb69c10beb0fb71be3046d8012e923b0
<section class="summary-panel">
    <p><span>Customer</span><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
    <p><span>Email</span><strong><?php echo htmlspecialchars($order['customer_email']); ?></strong></p>
    <p><span>Payment</span><strong><?php echo htmlspecialchars($payment['method'] ?? 'pending'); ?></strong></p>
    <p><span>Phone</span><strong><?php echo htmlspecialchars($order['customer_phone']); ?></strong></p>
    <p><span>Location</span><strong><?php echo htmlspecialchars($order['shipping_address']); ?></strong></p>
    <p class="summary-total"><span>Total</span><strong>TZS <?php echo number_format((float) $order['total'], 0); ?></strong></p>
</section>
<<<<<<< HEAD
<section class="admin-panel">
    <h2>Update Tracking</h2>
    <form class="admin-form-grid" method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <label>Delivery Status
            <select name="status">
                <?php foreach (['pending' => 'Order Placed', 'confirmed' => 'Payment Confirmed', 'processing' => 'Processing Order', 'shipped' => 'Shipped', 'in_transit' => 'In Transit', 'out_for_delivery' => 'Out for Delivery', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo ($order['status'] === $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Delivery Company<input name="delivery_company" value="<?php echo htmlspecialchars($order['delivery_company'] ?? ''); ?>" placeholder="DHL, EMS, local courier"></label>
        <label>Estimated Delivery Days<input type="number" min="1" name="estimated_delivery_days" value="<?php echo (int) ($order['estimated_delivery_days'] ?? 1); ?>"></label>
        <label class="admin-form-grid__wide">Delivery Notes<textarea name="delivery_notes" rows="3" placeholder="Package left Dar es Salaam warehouse."><?php echo htmlspecialchars($order['delivery_notes'] ?? ''); ?></textarea></label>
        <button class="button button--primary" type="submit">Save Tracking Update</button>
    </form>
</section>
=======
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
>>>>>>> dffcd92dcb69c10beb0fb71be3046d8012e923b0
<div class="cart-table">
    <div class="cart-row cart-row--head"><span>Product</span><span>Qty</span><span>Price</span><span>Total</span><span>Status</span></div>
    <?php foreach ($items as $item): ?>
        <div class="cart-row"><span><?php echo htmlspecialchars($item['product_name']); ?></span><span><?php echo (int) $item['quantity']; ?></span><span>TZS <?php echo number_format((float) $item['price'], 0); ?></span><span>TZS <?php echo number_format((float) $item['line_total'], 0); ?></span><span><?php echo htmlspecialchars($order['status']); ?></span></div>
    <?php endforeach; ?>
</div>
<?php else: ?><p class="empty-state">Order not found.</p><?php endif; ?>
<p><a href="index.php">Back to orders</a></p>
</div></main></body></html>
