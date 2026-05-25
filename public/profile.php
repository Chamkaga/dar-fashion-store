<?php
$pageTitle = 'My Account | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';

require_customer();

$user = current_user();
$database = new Database();
$conn = $database->connect();

// Get active tab
$tab = $_GET['tab'] ?? 'dashboard';
$allowed_tabs = ['dashboard', 'orders', 'wishlist', 'addresses', 'coupons', 'returns', 'recently-viewed', 'settings'];
if (!in_array($tab, $allowed_tabs)) {
    $tab = 'dashboard';
}

// Fetch user data
$userOrders = [];
$totalSpent = 0;
$wishlistItems = [];
$userAddresses = [];
$userCoupons = [];
$userReturns = [];
$recentlyViewed = [];

$profileDbWarning = '';

if ($conn) {
    $missingTables = [];

    try {
        $stmt = $conn->prepare("
            SELECT o.*, p.payment_status, p.method
            FROM orders o
            LEFT JOIN payments p ON p.order_id = o.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$user['id']]);
        $userOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) AS total_spent FROM orders WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $totalSpent = $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'];
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') {
            $missingTables[] = 'orders';
        } else {
            throw $e;
        }
    }

    try {
        $stmt = $conn->prepare("
            SELECT p.*, w.created_at AS added_at
            FROM user_wishlist w
            JOIN products p ON p.id = w.product_id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') {
            $missingTables[] = 'wishlist';
        } else {
            throw $e;
        }
    }

    try {
        $stmt = $conn->prepare("
            SELECT * FROM user_addresses
            WHERE user_id = ?
            ORDER BY is_default DESC, created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $userAddresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') {
            $missingTables[] = 'addresses';
        } else {
            throw $e;
        }
    }

    try {
        $stmt = $conn->prepare("
            SELECT * FROM user_coupons
            WHERE user_id = ?
            ORDER BY valid_until DESC
        ");
        $stmt->execute([$user['id']]);
        $userCoupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') {
            $missingTables[] = 'coupons';
        } else {
            throw $e;
        }
    }

    try {
        $stmt = $conn->prepare("
            SELECT ur.*, o.order_number, o.total
            FROM user_returns ur
            JOIN orders o ON o.id = ur.order_id
            WHERE ur.user_id = ?
            ORDER BY ur.requested_at DESC
        ");
        $stmt->execute([$user['id']]);
        $userReturns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') {
            $missingTables[] = 'returns';
        } else {
            throw $e;
        }
    }

    try {
        $stmt = $conn->prepare("
            SELECT DISTINCT p.id, p.name, p.slug, p.price, p.sale_price, p.image, ua.created_at
            FROM products p
            JOIN user_activities ua ON ua.product_id = p.id
            WHERE ua.user_id = ? AND ua.activity_type = 'view_product'
            ORDER BY ua.created_at DESC
            LIMIT 8
        ");
        $stmt->execute([$user['id']]);
        $recentlyViewed = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if ($e->getCode() === '42S02') {
            $missingTables[] = 'recently-viewed';
        } else {
            throw $e;
        }
    }

    if ($missingTables) {
        $profileDbWarning = 'Some profile features need a database update. Run: php repair-database.php';
    }
}

$customerInitial = strtoupper(substr($user['fullname'] ?? 'C', 0, 1));

include __DIR__ . '/../app/includes/header.php';
?>
<main class="section account-page">
    <div class="container account-layout">
        <aside class="account-menu">
            <div class="account-menu__title">My Account</div>
            <div class="customer-menu-user">
                <div class="profile-avatar"><?php echo htmlspecialchars($customerInitial); ?></div>
                <strong><?php echo htmlspecialchars($user['fullname']); ?></strong>
                <span><?php echo htmlspecialchars($user['email']); ?></span>
            </div>
            <a href="?tab=dashboard" class="<?php echo $tab === 'dashboard' ? 'is-active' : ''; ?>">Dashboard</a>
            <a href="?tab=orders" class="<?php echo $tab === 'orders' ? 'is-active' : ''; ?>">My Orders</a>
            <a href="?tab=wishlist" class="<?php echo $tab === 'wishlist' ? 'is-active' : ''; ?>">Wishlist</a>
            <a href="?tab=addresses" class="<?php echo $tab === 'addresses' ? 'is-active' : ''; ?>">Shipping Addresses</a>
            <a href="?tab=coupons" class="<?php echo $tab === 'coupons' ? 'is-active' : ''; ?>">My Coupons</a>
            <a href="?tab=returns" class="<?php echo $tab === 'returns' ? 'is-active' : ''; ?>">Returns & Refunds</a>
            <a href="?tab=recently-viewed" class="<?php echo $tab === 'recently-viewed' ? 'is-active' : ''; ?>">Recently Viewed</a>
            <a href="?tab=settings" class="<?php echo $tab === 'settings' ? 'is-active' : ''; ?>">Account Settings</a>
        </aside>

        <section class="account-content">
            <?php if (!$conn): ?>
                <p class="alert alert--error">Database connection failed. Copy <code>.env.example</code> to <code>.env</code>, set your MySQL password, then run <code>php setup-database.php</code>.</p>
            <?php elseif ($profileDbWarning): ?>
                <p class="alert alert--error"><?php echo htmlspecialchars($profileDbWarning); ?></p>
            <?php endif; ?>
            <?php if ($tab === 'dashboard'): ?>
                <article class="account-panel">
                    <p class="section-kicker">Customer dashboard</p>
                    <h1>Welcome back, <?php echo htmlspecialchars($user['fullname']); ?>!</h1>

                    <div class="customer-stat-grid">
                        <div class="customer-stat">
                            <strong><?php echo count($userOrders); ?></strong>
                            <span>Total Orders</span>
                        </div>
                        <div class="customer-stat">
                            <strong>TZS <?php echo number_format($totalSpent, 0); ?></strong>
                            <span>Total Spent</span>
                        </div>
                        <div class="customer-stat">
                            <strong><?php echo count($wishlistItems); ?></strong>
                            <span>Wishlist Items</span>
                        </div>
                        <div class="customer-stat">
                            <strong><?php echo count($userCoupons); ?></strong>
                            <span>Available Coupons</span>
                        </div>
                    </div>

                    <h2>Recent Orders</h2>
                    <?php if ($userOrders): ?>
                        <div class="cart-table">
                            <div class="cart-row cart-row--head">
                                <span>Order</span><span>Date</span><span>Total</span><span>Payment</span><span>Status</span><span>Action</span>
                            </div>
                            <?php foreach (array_slice($userOrders, 0, 3) as $order): ?>
                                <div class="cart-row">
                                    <span><?php echo htmlspecialchars($order['order_number']); ?></span>
                                    <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                    <span>TZS <?php echo number_format($order['total'], 0); ?></span>
                                    <span><?php echo htmlspecialchars($order['payment_status'] ?? 'pending'); ?></span>
                                    <span><strong><?php echo htmlspecialchars(ucfirst($order['status'])); ?></strong></span>
                                    <a href="track-order.php?order_number=<?php echo urlencode($order['order_number']); ?>">Track</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <p><a href="?tab=orders">View all orders →</a></p>
                    <?php else: ?>
                        <p class="empty-state">No orders yet. <a href="shop.php">Start shopping!</a></p>
                    <?php endif; ?>
                </article>

            <?php elseif ($tab === 'orders'): ?>
                <article class="account-panel">
                    <p class="section-kicker">Orders</p>
                    <h1>My Orders</h1>
                    <?php if ($userOrders): ?>
                        <div class="cart-table">
                            <div class="cart-row cart-row--head">
                                <span>Order</span><span>Date</span><span>Total</span><span>Payment</span><span>Status</span><span>Action</span>
                            </div>
                            <?php foreach ($userOrders as $order): ?>
                                <div class="cart-row">
                                    <span><?php echo htmlspecialchars($order['order_number']); ?></span>
                                    <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                    <span>TZS <?php echo number_format($order['total'], 0); ?></span>
                                    <span><?php echo htmlspecialchars($order['payment_status'] ?? 'pending'); ?></span>
                                    <span><strong><?php echo htmlspecialchars(ucfirst($order['status'])); ?></strong></span>
                                    <a href="track-order.php?order_number=<?php echo urlencode($order['order_number']); ?>">Track</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">No orders yet. <a href="shop.php">Start shopping!</a></p>
                    <?php endif; ?>
                </article>

            <?php elseif ($tab === 'wishlist'): ?>
                <article class="account-panel">
                    <p class="section-kicker">Wishlist</p>
                    <h1>My Wishlist</h1>
                    <?php if ($wishlistItems): ?>
                        <div class="customer-product-grid">
                            <?php foreach ($wishlistItems as $item):
                                $displayPrice = $item['sale_price'] ?: $item['price'];
                            ?>
                                <article class="customer-product-card">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <div class="customer-product-card__body">
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p>TZS <?php echo number_format($displayPrice, 0); ?></p>
                                        <a href="product.php?slug=<?php echo urlencode($item['slug']); ?>" class="button button--primary">View Product</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">Your wishlist is empty. <a href="shop.php">Add items to your wishlist!</a></p>
                    <?php endif; ?>
                </article>

            <?php elseif ($tab === 'addresses'): ?>
                <article class="account-panel">
                    <p class="section-kicker">Delivery</p>
                    <h1>Shipping Addresses</h1>
                    <?php if ($userAddresses): ?>
                        <div class="customer-product-grid">
                            <?php foreach ($userAddresses as $address): ?>
                                <article class="address-card <?php echo $address['is_default'] ? 'address-card--default' : ''; ?>">
                                    <h3><?php echo htmlspecialchars($address['label']); ?></h3>
                                    <?php if ($address['is_default']): ?>
                                        <span class="address-badge">Default</span>
                                    <?php endif; ?>
                                    <p>
                                        <strong><?php echo htmlspecialchars($address['fullname']); ?></strong><br>
                                        <?php echo htmlspecialchars($address['address']); ?><br>
                                        <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['region']); ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                        <?php echo htmlspecialchars($address['phone']); ?>
                                    </p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">No addresses saved yet.</p>
                    <?php endif; ?>
                </article>

            <?php elseif ($tab === 'coupons'): ?>
                <article class="account-panel">
                    <p class="section-kicker">Savings</p>
                    <h1>My Coupons & Vouchers</h1>
                    <?php if ($userCoupons): ?>
                        <div class="customer-product-grid">
                            <?php foreach ($userCoupons as $coupon): ?>
                                <article class="coupon-card">
                                    <h3><?php echo htmlspecialchars($coupon['code']); ?></h3>
                                    <p><?php echo htmlspecialchars($coupon['description']); ?></p>
                                    <p class="coupon-discount">
                                        <?php echo $coupon['discount_percentage'] ? $coupon['discount_percentage'] . '%' : 'TZS ' . number_format($coupon['discount_amount'], 0); ?>
                                    </p>
                                    <p>
                                        Valid: <?php echo date('M d, Y', strtotime($coupon['valid_from'])); ?> -
                                        <?php echo date('M d, Y', strtotime($coupon['valid_until'])); ?>
                                    </p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">No coupons available yet.</p>
                    <?php endif; ?>
                </article>

            <?php elseif ($tab === 'returns'): ?>
                <article class="account-panel">
                    <p class="section-kicker">Support</p>
                    <h1>Returns & Refunds</h1>
                    <?php if ($userReturns): ?>
                        <div class="cart-table">
                            <div class="cart-row cart-row--head">
                                <span>Return #</span><span>Order</span><span>Reason</span><span>Status</span><span>Refund</span>
                            </div>
                            <?php foreach ($userReturns as $return): ?>
                                <div class="cart-row">
                                    <span><?php echo htmlspecialchars($return['return_number']); ?></span>
                                    <span><?php echo htmlspecialchars($return['order_number']); ?></span>
                                    <span><?php echo htmlspecialchars($return['reason']); ?></span>
                                    <span><strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $return['status']))); ?></strong></span>
                                    <span><?php echo $return['refund_amount'] ? 'TZS ' . number_format($return['refund_amount'], 0) : 'Pending'; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">No returns or refunds. </p>
                    <?php endif; ?>
                </article>

            <?php elseif ($tab === 'recently-viewed'): ?>
                <article class="account-panel">
                    <p class="section-kicker">Browsing history</p>
                    <h1>Recently Viewed Products</h1>
                    <?php if ($recentlyViewed): ?>
                        <div class="customer-product-grid">
                            <?php foreach ($recentlyViewed as $product):
                                $displayPrice = $product['sale_price'] ?: $product['price'];
                            ?>
                                <article class="customer-product-card">
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="customer-product-card__body">
                                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p>TZS <?php echo number_format($displayPrice, 0); ?></p>
                                        <a href="product.php?slug=<?php echo urlencode($product['slug']); ?>" class="button button--primary">View Product</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">You haven't viewed any products yet.</p>
                    <?php endif; ?>
                </article>

            <?php elseif ($tab === 'settings'): ?>
                <?php
                $customerPhone = '';
                if ($conn) {
                    $stmt = $conn->prepare("SELECT phone FROM users WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    $customerPhone = $stmt->fetchColumn() ?: '';
                }
                ?>
                <article class="account-panel">
                    <p class="section-kicker">Account</p>
                    <h1>Account Settings</h1>

                    <div class="settings-panel">
                        <div class="settings-box">
                            <h2>Personal Information</h2>
                            <p><strong>Full Name:</strong><br><?php echo htmlspecialchars($user['fullname']); ?></p>
                            <p><strong>Email:</strong><br><?php echo htmlspecialchars($user['email']); ?></p>
                            <?php if ($customerPhone): ?>
                                <p><strong>Phone:</strong><br><?php echo htmlspecialchars($customerPhone); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="settings-box">
                            <h2>Security</h2>
                            <p>Manage your password and account security settings.</p>
                            <button class="button button--secondary" type="button" disabled>Change Password (Coming Soon)</button>
                        </div>

                        <a href="logout.php" class="button button--dark">Log Out</a>
                    </div>
                </article>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
