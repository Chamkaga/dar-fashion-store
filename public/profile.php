<?php
$pageTitle = 'Customer Profile | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_login('login.php');
include __DIR__ . '/../app/includes/header.php';

$user = current_user();
$database = new Database();
$conn = $database->connect();
$orders = [];
$stats = ['orders' => 0, 'spent' => 0, 'cart' => count($_SESSION['cart'] ?? [])];

if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stats['orders'] = count($orders);
    foreach ($orders as $order) {
        $stats['spent'] += (float) $order['total'];
    }
}
?>
<main class="section">
    <div class="container profile-layout">
        <aside class="profile-card">
            <div class="profile-avatar"><?php echo strtoupper(substr($user['fullname'], 0, 1)); ?></div>
            <h1><?php echo htmlspecialchars($user['fullname']); ?></h1>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
            <a class="button button--dark" href="logout.php">Logout</a>
        </aside>

        <section>
            <div class="benefit-grid">
                <article class="benefit-card"><strong><?php echo (int) $stats['orders']; ?></strong><span>Total Orders</span></article>
                <article class="benefit-card"><strong>TZS <?php echo number_format($stats['spent'], 0); ?></strong><span>Total Spent</span></article>
                <article class="benefit-card"><strong><?php echo (int) $stats['cart']; ?></strong><span>Items in Cart</span></article>
            </div>

            <div class="section-heading">
                <p class="section-kicker">My orders</p>
                <h2>Recent shopping activity</h2>
            </div>

            <div class="cart-table">
                <div class="cart-row cart-row--head"><span>Order</span><span>Date</span><span>Status</span><span>Total</span><span>Track</span></div>
                <?php foreach ($orders as $order): ?>
                    <div class="cart-row">
                        <span><?php echo htmlspecialchars($order['order_number']); ?></span>
                        <span><?php echo htmlspecialchars(date('M d, Y', strtotime($order['created_at']))); ?></span>
                        <span><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                        <span>TZS <?php echo number_format((float) $order['total'], 0); ?></span>
                        <a href="track-order.php?order_number=<?php echo urlencode($order['order_number']); ?>&email=<?php echo urlencode($order['customer_email']); ?>">Track</a>
                    </div>
                <?php endforeach; ?>
                <?php if (!$orders): ?>
                    <p class="empty-state">No orders yet. Start shopping to see your history here.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
