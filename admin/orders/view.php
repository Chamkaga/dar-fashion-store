<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/models/OrderTracking.php';

$conn = (new Database())->connect();
$tracking = $conn ? new OrderTracking($conn) : null;
$id = (int) ($_GET['id'] ?? 0);
$message = '';

$statusLabels = [
    'pending' => '1. Order Placed',
    'confirmed' => '2. Order Confirmed',
    'processing' => '3. Processing & Packing',
    'shipped' => '4. Picked Up by Courier',
    'in_transit' => '5. In Transit (Dar → destination)',
    'out_for_delivery' => '7. Out for Delivery',
    'delivered' => '8. Delivered',
    'cancelled' => 'Cancelled',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Security token expired. Please try again.';
    } else {
        $status = $_POST['status'] ?? 'pending';
        $allowed = array_keys($statusLabels);
        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $before = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("
            UPDATE orders SET status = ?, delivery_company = ?, delivery_notes = ?, estimated_delivery_days = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $status,
            trim($_POST['delivery_company'] ?? ''),
            trim($_POST['delivery_notes'] ?? ''),
            (int) ($_POST['estimated_delivery_days'] ?? 3),
            $id,
        ]);

        $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $after = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($tracking && $after) {
            $tracking->logStatusChange($id, $status, $after, 'admin');
            if (!empty($_POST['log_hub_arrival'])) {
                $tracking->logEvent($id, 'arrived_at_hub', [
                    'location' => trim($_POST['hub_location'] ?? '') ?: 'Destination sorting facility',
                    'created_by' => 'admin',
                ]);
            }
        }

        $message = 'Tracking updated. Customer will see the new step on Track Order.';
    }
}

$order = null;
$items = [];
$payment = null;
$timeline = ['steps' => [], 'progress' => 0];

if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id=?");
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($order && $conn) {
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id=?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id=?");
    $stmt->execute([$id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($tracking) {
        $timeline = $tracking->buildTimeline($order);
    }
}

$adminPage = 'orders';
$adminRoot = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order <?php echo htmlspecialchars($order['order_number'] ?? ''); ?> | Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>
<body>
<main class="admin-shell">
    <?php include __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>
    <section class="admin-content">
        <?php if (!$order): ?>
            <p class="empty-state">Order not found.</p>
            <p><a href="index.php">Back to orders</a></p>
        <?php else: ?>
            <header class="admin-page-header">
                <div>
                    <p class="section-kicker">Order management</p>
                    <h1><?php echo htmlspecialchars($order['order_number']); ?></h1>
                    <p>Update courier scans — customers see the same timeline on Track Order.</p>
                </div>
                <div class="admin-page-actions">
                    <a class="button button--secondary" href="../../public/track-order.php?order_number=<?php echo urlencode($order['order_number']); ?>&email=<?php echo urlencode($order['customer_email']); ?>" target="_blank" rel="noopener">Preview tracking</a>
                    <a class="button button--primary" href="index.php">All orders</a>
                </div>
            </header>

            <?php if ($message): ?><p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>

            <div class="admin-kpi-grid" style="margin-bottom: 20px;">
                <article class="admin-kpi-card admin-kpi-card--primary">
                    <span>Progress</span>
                    <strong><?php echo (int) $timeline['progress']; ?>%</strong>
                </article>
                <article class="admin-kpi-card">
                    <span>Customer</span>
                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                </article>
                <article class="admin-kpi-card">
                    <span>Est. days</span>
                    <strong><?php echo (int) ($order['estimated_delivery_days'] ?? 3); ?></strong>
                </article>
                <article class="admin-kpi-card admin-kpi-card--revenue">
                    <span>Total</span>
                    <strong>TZS <?php echo number_format((float) $order['total'], 0); ?></strong>
                </article>
            </div>

            <section class="admin-grid-two">
                <article class="admin-panel" style="margin-top: 0;">
                    <h2>Update delivery status</h2>
                    <form class="admin-form-grid" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <label class="admin-form-grid__wide">Parcel status
                            <select name="status">
                                <?php foreach ($statusLabels as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $order['status'] === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Courier company<input name="delivery_company" value="<?php echo htmlspecialchars($order['delivery_company'] ?? ''); ?>" placeholder="Dar Express, DPL Logistics"></label>
                        <label>Estimated days<input type="number" min="1" max="14" name="estimated_delivery_days" value="<?php echo (int) ($order['estimated_delivery_days'] ?? 3); ?>"></label>
                        <label class="admin-form-grid__wide">Courier note (visible to customer)<textarea name="delivery_notes" rows="2" placeholder="Parcel left Dar warehouse, heading to Mwanza hub."><?php echo htmlspecialchars($order['delivery_notes'] ?? ''); ?></textarea></label>
                        <label class="admin-form-grid__wide" style="display: flex; align-items: center; gap: 8px; font-weight: 600;">
                            <input type="checkbox" name="log_hub_arrival" value="1"> Log step: <strong>Arrived at destination hub</strong> (e.g. Mwanza sorting facility)
                        </label>
                        <label>Hub location<input name="hub_location" placeholder="Mwanza distribution hub"></label>
                        <button class="button button--primary admin-form-grid__wide" type="submit">Save & notify tracking</button>
                    </form>
                </article>

                <article class="admin-panel" style="margin-top: 0;">
                    <h2>Customer & payment</h2>
                    <p><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br><?php echo htmlspecialchars($order['customer_email']); ?><br><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    <p style="margin-top: 12px;"><strong>Ship to:</strong><br><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    <p style="margin-top: 12px;"><strong>Payment:</strong> <?php echo htmlspecialchars($payment['method'] ?? '—'); ?> / <?php echo htmlspecialchars($payment['payment_status'] ?? 'pending'); ?></p>
                </article>
            </section>

            <article class="admin-panel">
                <h2>Live tracking timeline (customer view)</h2>
                <ol class="delivery-timeline">
                    <?php foreach ($timeline['steps'] as $item): ?>
                        <li class="delivery-timeline__item delivery-timeline__item--<?php echo htmlspecialchars($item['state']); ?>">
                            <div class="delivery-timeline__marker"><?php echo htmlspecialchars($item['step']['icon']); ?></div>
                            <article class="delivery-timeline__card">
                                <div class="delivery-timeline__head">
                                    <h3><?php echo htmlspecialchars($item['step']['label']); ?></h3>
                                    <span class="delivery-timeline__badge"><?php echo htmlspecialchars($item['state']); ?></span>
                                </div>
                                <?php if ($item['event']): ?>
                                    <time class="delivery-timeline__time"><?php echo date('M d, H:i', strtotime($item['event']['event_at'])); ?> · <?php echo htmlspecialchars($item['event']['location'] ?? ''); ?></time>
                                <?php endif; ?>
                            </article>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </article>

            <article class="admin-panel">
                <h2>Order items</h2>
                <div class="cart-table">
                    <div class="cart-row cart-row--head"><span>Product</span><span>Qty</span><span>Price</span><span>Total</span></div>
                    <?php foreach ($items as $item): ?>
                        <div class="cart-row">
                            <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                            <span><?php echo (int) $item['quantity']; ?></span>
                            <span>TZS <?php echo number_format((float) $item['price'], 0); ?></span>
                            <span>TZS <?php echo number_format((float) $item['line_total'], 0); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
