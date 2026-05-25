<?php
<<<<<<< HEAD
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
=======
$pageTitle = 'My Account | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Order.php';
require_login('login.php');

$database = new Database();
$conn = $database->connect();
$sessionUser = current_user();
$message = '';
$error = '';
$user = $sessionUser;
$orders = [];
$orderItems = [];

$trackingSteps = [
    'pending' => ['label' => 'Pending Payment', 'icon' => '&#8987;'],
    'confirmed' => ['label' => 'Confirmed', 'icon' => '&#10003;'],
    'processing' => ['label' => 'Processing', 'icon' => '&#9638;'],
    'shipped' => ['label' => 'In Transit', 'icon' => '&#9635;'],
    'delivered' => ['label' => 'Delivered', 'icon' => '&#10003;'],
];

if ($conn) {
    $userModel = new User($conn);
    $orderModel = new Order($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fullname = trim($_POST['fullname'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($fullname === '') {
            $error = 'Full name is required.';
        } elseif ($userModel->updateProfile((int) $sessionUser['id'], $fullname, $phone)) {
            $_SESSION['user']['fullname'] = $fullname;
            $message = 'Profile updated successfully.';
        } else {
            $error = 'Profile could not be updated. Please try again.';
        }
    }

    $freshUser = $userModel->findById((int) $sessionUser['id']);
    if ($freshUser) {
        $user = $freshUser;
    }

    $orders = $orderModel->getByUser((int) $sessionUser['id']);
    foreach ($orders as $order) {
        $orderItems[$order['id']] = $orderModel->getItems((int) $order['id']);
    }
}

function order_step_index($status) {
    $map = [
        'pending' => 0,
        'confirmed' => 1,
        'processing' => 2,
        'shipped' => 3,
        'delivered' => 4,
        'cancelled' => -1
    ];
    return $map[$status] ?? 0;
}

include __DIR__ . '/../app/includes/header.php';
?>
<main class="account-page">
    <div class="container account-layout">
        <aside class="account-menu">
            <div class="account-menu__title">&#9776; Menu</div>
            <a class="is-active" href="#overview">My Dar Fashion</a>
            <a href="#orders">My Orders</a>
            <a href="#tracking">Track Order</a>
            <a href="#profile">Edit Profile</a>
            <a href="#address">Shipping Address</a>
            <a href="logout.php">Log Out</a>
        </aside>

        <section class="account-content">
            <section class="profile-hero" id="overview">
                <div class="profile-avatar"><?php echo strtoupper(substr($user['fullname'] ?? 'C', 0, 1)); ?></div>
                <div>
                    <p class="section-kicker">Customer profile</p>
                    <h1><?php echo htmlspecialchars($user['fullname'] ?? 'Customer'); ?></h1>
                    <p><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                </div>
                <a class="profile-action" href="#profile">&#9998; Edit My Profile</a>
                <a class="profile-action" href="#orders">Orders(<?php echo count($orders); ?>)</a>
            </section>

            <section class="status-shortcuts" id="tracking" aria-label="Order tracking shortcuts">
                <a href="#orders"><span>&#8987;</span>Pending Payment</a>
                <a href="#orders"><span>&#9635;</span>In Transit</a>
                <a href="#orders"><span>&#9633;</span>Pending Feedback</a>
                <a href="#orders"><span>&#8617;</span>Return & Refund</a>
            </section>

            <section class="account-panel" id="orders">
                <div class="section-heading section-heading--row">
                    <div>
                        <p class="section-kicker">My orders</p>
                        <h2>Track your order progress</h2>
                    </div>
                    <a href="shop.php">Continue Shopping</a>
                </div>

                <?php if (!$conn): ?>
                    <p class="alert alert--error">Database connection failed. Order tracking needs MySQL to be running.</p>
                <?php elseif (!$orders): ?>
                    <p class="empty-state">You do not have orders yet. Your active orders will appear here after checkout.</p>
                <?php else: ?>
                    <div class="order-list">
                        <?php foreach ($orders as $order): ?>
                            <?php $activeStep = order_step_index($order['status']); ?>
                            <article class="order-card">
                                <div class="order-card__head">
                                    <div>
                                        <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                        <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                    </div>
                                    <span class="status-pill status-pill--<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                                </div>

                                <div class="tracking-line">
                                    <?php $stepNumber = 0; ?>
                                    <?php foreach ($trackingSteps as $key => $step): ?>
                                        <div class="tracking-step <?php echo $activeStep >= $stepNumber ? 'is-complete' : ''; ?>">
                                            <span><?php echo $step['icon']; ?></span>
                                            <strong><?php echo htmlspecialchars($step['label']); ?></strong>
                                        </div>
                                        <?php $stepNumber++; ?>
                                    <?php endforeach; ?>
                                </div>

                                <div class="order-items">
                                    <?php foreach ($orderItems[$order['id']] ?? [] as $item): ?>
                                        <p><span><?php echo htmlspecialchars($item['product_name']); ?> x <?php echo (int) $item['quantity']; ?></span><strong>TZS <?php echo number_format((float) $item['line_total'], 0); ?></strong></p>
                                    <?php endforeach; ?>
                                    <p class="summary-total"><span>Total</span><strong>TZS <?php echo number_format((float) $order['total'], 0); ?></strong></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="account-panel" id="profile">
                <p class="section-kicker">Profile details</p>
                <h2>Edit My Profile</h2>
                <?php if ($message): ?><p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
                <?php if ($error): ?><p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
                <form class="profile-form" method="post">
                    <label>Full name<input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required></label>
                    <label>Email<input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" disabled></label>
                    <label>Phone<input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></label>
                    <button class="button button--primary" type="submit">Save Profile</button>
                </form>
            </section>

            <section class="account-panel" id="address">
                <p class="section-kicker">Shipping address</p>
                <h2>Saved delivery details</h2>
                <?php $latestOrder = $orders[0] ?? null; ?>
                <p><?php echo $latestOrder ? htmlspecialchars($latestOrder['shipping_address']) : 'Your checkout address will appear here after your first order.'; ?></p>
            </section>
>>>>>>> dffcd92dcb69c10beb0fb71be3046d8012e923b0
        </section>
    </div>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
