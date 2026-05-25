<?php
$pageTitle = 'Track Order | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/OrderTracking.php';
include __DIR__ . '/../app/includes/header.php';

$database = new Database();
$conn = $database->connect();
$orderNumber = strtoupper(trim($_GET['order_number'] ?? ''));
$email = trim($_GET['email'] ?? '');
$order = null;
$items = [];
$payment = null;
$confirmation = null;
$timeline = ['steps' => [], 'progress' => 0, 'cancelled' => false, 'events' => []];
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

        $tracking = new OrderTracking($conn);
        $timeline = $tracking->buildTimeline($order);
    } else {
        $error = 'No order found. Check the order number and email address.';
    }
}

$etaDays = $order ? (int) ($order['estimated_delivery_days'] ?? 3) : 3;
$etaLabel = $etaDays <= 1 ? '1 day' : ($etaDays . '–' . ($etaDays + 2) . ' days');
?>
<main class="section">
    <div class="container checkout-layout">
        <form class="checkout-form" method="get">
            <p class="section-kicker">Track order</p>
            <h1>Follow your parcel step by step</h1>
            <p>See the full journey from Kariakoo (Dar es Salaam) to your city — like Amazon, eBay, or local couriers.</p>
            <p class="form-hint">Demo: order <strong>DFS-1002</strong> + email <strong>ignas@example.com</strong> (in transit to Mwanza)</p>
            <?php if ($error): ?><p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
            <label>Tracking number (order #)<input name="order_number" value="<?php echo htmlspecialchars($orderNumber); ?>" placeholder="DFS-1002"></label>
            <label>Email address<input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="you@example.com"></label>
            <button class="button button--primary" type="submit">Track parcel</button>
        </form>

        <aside class="summary-panel">
            <h2>Tracking summary</h2>
            <?php if ($order): ?>
                <p><span>Tracking #</span><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></p>
                <p><span>Route</span><strong>Dar es Salaam → <?php echo htmlspecialchars($order['shipping_address']); ?></strong></p>
                <p><span>Courier</span><strong><?php echo htmlspecialchars($order['delivery_company'] ?? 'Assigning courier…'); ?></strong></p>
                <p><span>Est. delivery</span><strong><?php echo htmlspecialchars($etaLabel); ?></strong></p>
                <p><span>Payment</span><strong><?php echo htmlspecialchars($payment['payment_status'] ?? 'pending'); ?></strong></p>
                <p class="summary-total"><span>Order total</span><strong>TZS <?php echo number_format((float) $order['total'], 0); ?></strong></p>
            <?php else: ?>
                <p>Enter your tracking number and email to see live progress.</p>
            <?php endif; ?>
        </aside>
    </div>

    <?php if ($order && !$timeline['cancelled']): ?>
        <section class="container section">
            <div class="tracking-hero">
                <div>
                    <p class="section-kicker">Delivery progress</p>
                    <h2><?php echo (int) $timeline['progress']; ?>% complete</h2>
                    <p>Each step below reflects real logistics activity — packing, pickup, transit hubs, and final delivery.</p>
                </div>
                <div class="tracking-progress-bar" aria-hidden="true">
                    <span style="width: <?php echo (int) $timeline['progress']; ?>%;"></span>
                </div>
            </div>

            <ol class="delivery-timeline">
                <?php foreach ($timeline['steps'] as $item):
                    $step = $item['step'];
                    $event = $item['event'];
                    $state = $item['state'];
                ?>
                    <li class="delivery-timeline__item delivery-timeline__item--<?php echo htmlspecialchars($state); ?>">
                        <div class="delivery-timeline__marker"><?php echo htmlspecialchars($step['icon']); ?></div>
                        <article class="delivery-timeline__card">
                            <div class="delivery-timeline__head">
                                <h3><?php echo htmlspecialchars($step['label']); ?></h3>
                                <span class="delivery-timeline__badge"><?php echo htmlspecialchars(ucfirst($state)); ?></span>
                            </div>
                            <p class="delivery-timeline__summary"><?php echo htmlspecialchars($step['summary']); ?></p>
                            <p><strong>What you see:</strong> <?php echo htmlspecialchars($event['description'] ?? $step['customer']); ?></p>
                            <p class="delivery-timeline__activity"><strong>Behind the scenes:</strong> <?php echo htmlspecialchars($event['activity_note'] ?? $step['activity']); ?></p>
                            <?php if ($event && !empty($event['location'])): ?>
                                <p class="delivery-timeline__location">Location: <?php echo htmlspecialchars($event['location']); ?></p>
                            <?php endif; ?>
                            <?php if ($event): ?>
                                <time class="delivery-timeline__time"><?php echo date('M d, Y · H:i', strtotime($event['event_at'])); ?></time>
                            <?php elseif ($state === 'upcoming'): ?>
                                <time class="delivery-timeline__time">Waiting for next scan</time>
                            <?php endif; ?>
                        </article>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <?php if (!empty($timeline['events'])): ?>
            <section class="container section section--soft">
                <div class="admin-panel" style="margin-top: 0;">
                    <h2>Scan history</h2>
                    <p>Every update is triggered by warehouse scans, hub sorting, or courier actions.</p>
                    <div class="scan-history">
                        <?php foreach ($timeline['events'] as $event): ?>
                            <div class="scan-history__row">
                                <span><?php echo date('M d, H:i', strtotime($event['event_at'])); ?></span>
                                <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                <span><?php echo htmlspecialchars($event['location'] ?? ''); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="container cart-table">
            <div class="cart-row cart-row--head"><span>Product</span><span>Qty</span><span>Price</span><span>Total</span></div>
            <?php foreach ($items as $item): ?>
                <div class="cart-row">
                    <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                    <span><?php echo (int) $item['quantity']; ?></span>
                    <span>TZS <?php echo number_format((float) $item['price'], 0); ?></span>
                    <span>TZS <?php echo number_format((float) $item['line_total'], 0); ?></span>
                </div>
            <?php endforeach; ?>
        </section>

        <?php if (!empty($order['delivery_notes'])): ?>
            <section class="container section">
                <div class="summary-panel">
                    <h2>Latest courier note</h2>
                    <p><?php echo htmlspecialchars($order['delivery_notes']); ?></p>
                </div>
            </section>
        <?php endif; ?>
    <?php elseif ($order && $timeline['cancelled']): ?>
        <section class="container section">
            <p class="alert alert--error">This order was cancelled.</p>
        </section>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
