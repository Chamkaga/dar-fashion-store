<?php
$pageTitle = 'My Account | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';

require_login();

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

if ($conn) {
    // User Orders
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
    
    // Total Spent
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as total_spent FROM orders WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $totalSpent = $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'];
    
    // Wishlist
    $stmt = $conn->prepare("
        SELECT p.*, w.created_at as added_at
        FROM user_wishlist w
        JOIN products p ON p.id = w.product_id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $wishlistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Addresses
    $stmt = $conn->prepare("
        SELECT * FROM user_addresses
        WHERE user_id = ?
        ORDER BY is_default DESC, created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $userAddresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Coupons
    $stmt = $conn->prepare("
        SELECT * FROM user_coupons
        WHERE user_id = ?
        ORDER BY valid_until DESC
    ");
    $stmt->execute([$user['id']]);
    $userCoupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Returns
    $stmt = $conn->prepare("
        SELECT ur.*, o.order_number, o.total
        FROM user_returns ur
        JOIN orders o ON o.id = ur.order_id
        WHERE ur.user_id = ?
        ORDER BY ur.requested_at DESC
    ");
    $stmt->execute([$user['id']]);
    $userReturns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recently Viewed
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
}

include __DIR__ . '/../app/includes/header.php';
?>
<main class="section">
    <div class="container profile-layout">
        <!-- Sidebar Navigation -->
        <aside class="profile-sidebar">
            <div class="profile-header">
                <h2><?php echo htmlspecialchars($user['fullname']); ?></h2>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            
            <nav class="profile-nav">
                <a href="?tab=dashboard" class="profile-nav-item <?php echo $tab === 'dashboard' ? 'is-active' : ''; ?>">
                    📊 Dashboard
                </a>
                <a href="?tab=orders" class="profile-nav-item <?php echo $tab === 'orders' ? 'is-active' : ''; ?>">
                    📦 My Orders
                </a>
                <a href="?tab=wishlist" class="profile-nav-item <?php echo $tab === 'wishlist' ? 'is-active' : ''; ?>">
                    ❤️ My KiKUU (Wishlist)
                </a>
                <a href="?tab=addresses" class="profile-nav-item <?php echo $tab === 'addresses' ? 'is-active' : ''; ?>">
                    📍 Shipping Addresses
                </a>
                <a href="?tab=coupons" class="profile-nav-item <?php echo $tab === 'coupons' ? 'is-active' : ''; ?>">
                    🎟️ My Coupons
                </a>
                <a href="?tab=returns" class="profile-nav-item <?php echo $tab === 'returns' ? 'is-active' : ''; ?>">
                    ↩️ Returns & Refunds
                </a>
                <a href="?tab=recently-viewed" class="profile-nav-item <?php echo $tab === 'recently-viewed' ? 'is-active' : ''; ?>">
                    👁️ Recently Viewed
                </a>
                <a href="?tab=settings" class="profile-nav-item <?php echo $tab === 'settings' ? 'is-active' : ''; ?>">
                    ⚙️ Settings
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <section class="profile-content">
            <?php if ($tab === 'dashboard'): ?>
                <div class="profile-section">
                    <h1>Welcome back, <?php echo htmlspecialchars($user['fullname']); ?>!</h1>
                    
                    <div class="profile-stats">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($userOrders); ?></div>
                            <div class="stat-label">Total Orders</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">TZS <?php echo number_format($totalSpent, 0); ?></div>
                            <div class="stat-label">Total Spent</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($wishlistItems); ?></div>
                            <div class="stat-label">Wishlist Items</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($userCoupons); ?></div>
                            <div class="stat-label">Available Coupons</div>
                        </div>
                    </div>

                    <h2 style="margin-top: 3rem;">Recent Orders</h2>
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
                </div>

            <?php elseif ($tab === 'orders'): ?>
                <div class="profile-section">
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
                </div>

            <?php elseif ($tab === 'wishlist'): ?>
                <div class="profile-section">
                    <h1>My KiKUU (Wishlist)</h1>
                    <?php if ($wishlistItems): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($wishlistItems as $item): 
                                $displayPrice = $item['sale_price'] ?: $item['price'];
                            ?>
                                <div style="border: 1px solid #eee; border-radius: 8px; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 100%; height: 200px; object-fit: cover;">
                                    <div style="padding: 1rem;">
                                        <h3 style="font-size: 0.95rem; margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p style="color: #666; font-size: 0.9rem; margin: 0;">TZS <?php echo number_format($displayPrice, 0); ?></p>
                                        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                                            <a href="product.php?slug=<?php echo urlencode($item['slug']); ?>" class="button button--primary" style="flex: 1; text-align: center; padding: 0.5rem;">View</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">Your wishlist is empty. <a href="shop.php">Add items to your wishlist!</a></p>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'addresses'): ?>
                <div class="profile-section">
                    <h1>Shipping Addresses</h1>
                    <?php if ($userAddresses): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($userAddresses as $address): ?>
                                <div style="border: 1px solid #eee; border-radius: 8px; padding: 1.5rem; background: <?php echo $address['is_default'] ? '#f0f8ff' : '#fafafa'; ?>;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                        <div>
                                            <h3 style="margin: 0;"><?php echo htmlspecialchars($address['label']); ?></h3>
                                            <?php if ($address['is_default']): ?>
                                                <span style="display: inline-block; margin-top: 0.5rem; padding: 0.25rem 0.75rem; background: #0066ff; color: white; font-size: 0.75rem; border-radius: 4px; font-weight: 600;">Default</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                        <strong><?php echo htmlspecialchars($address['fullname']); ?></strong><br>
                                        <?php echo htmlspecialchars($address['address']); ?><br>
                                        <?php echo htmlspecialchars($address['city']); ?>, <?php echo htmlspecialchars($address['region']); ?> <?php echo htmlspecialchars($address['postal_code']); ?><br>
                                        <?php echo htmlspecialchars($address['phone']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">No addresses saved yet.</p>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'coupons'): ?>
                <div class="profile-section">
                    <h1>My Coupons & Vouchers</h1>
                    <?php if ($userCoupons): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($userCoupons as $coupon): ?>
                                <div style="border: 2px dashed #0066ff; border-radius: 8px; padding: 1.5rem;">
                                    <h3 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($coupon['code']); ?></h3>
                                    <p style="margin: 0 0 1rem 0; color: #666; font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($coupon['description']); ?>
                                    </p>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 1rem 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">
                                        <div style="font-size: 1.5rem; font-weight: 600; color: #0066ff;">
                                            <?php echo $coupon['discount_percentage'] ? $coupon['discount_percentage'] . '%' : 'TZS ' . number_format($coupon['discount_amount'], 0); ?>
                                        </div>
                                    </div>
                                    <p style="margin: 0; font-size: 0.85rem; color: #999;">
                                        Valid: <?php echo date('M d, Y', strtotime($coupon['valid_from'])); ?> - <?php echo date('M d, Y', strtotime($coupon['valid_until'])); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">No coupons available yet.</p>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'returns'): ?>
                <div class="profile-section">
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
                </div>

            <?php elseif ($tab === 'recently-viewed'): ?>
                <div class="profile-section">
                    <h1>Recently Viewed Products</h1>
                    <?php if ($recentlyViewed): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($recentlyViewed as $product): 
                                $displayPrice = $product['sale_price'] ?: $product['price'];
                            ?>
                                <div style="border: 1px solid #eee; border-radius: 8px; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: 200px; object-fit: cover;">
                                    <div style="padding: 1rem;">
                                        <h3 style="font-size: 0.95rem; margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <p style="color: #666; font-size: 0.9rem; margin: 0;">TZS <?php echo number_format($displayPrice, 0); ?></p>
                                        <a href="product.php?slug=<?php echo urlencode($product['slug']); ?>" class="button button--primary" style="width: 100%; text-align: center; padding: 0.5rem; margin-top: 1rem; display: block;">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">You haven't viewed any products yet.</p>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'settings'): ?>
                <div class="profile-section">
                    <h1>Account Settings</h1>
                    
                    <div style="max-width: 500px;">
                        <h2 style="font-size: 1.2rem; margin-top: 2rem;">Account Information</h2>
                        <div style="background: #fafafa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                            <p><strong>Full Name:</strong><br><?php echo htmlspecialchars($user['fullname']); ?></p>
                            <p><strong>Email:</strong><br><?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong>Role:</strong><br><?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
                        </div>

                        <h2 style="font-size: 1.2rem; margin-top: 2rem;">Security</h2>
                        <div style="background: #fafafa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                            <p>Manage your password and account security settings.</p>
                            <button class="button button--secondary" disabled>Change Password (Coming Soon)</button>
                        </div>

                        <div style="margin-top: 3rem;">
                            <a href="logout.php" class="button button--secondary">Log Out</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<style>
.profile-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 3rem;
    align-items: start;
    margin-top: 2rem;
}

.profile-sidebar {
    background: #fafafa;
    border-radius: 8px;
    padding: 1.5rem;
    position: sticky;
    top: 100px;
    height: fit-content;
}

.profile-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}

.profile-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
}

.profile-email {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}

.profile-nav {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.profile-nav-item {
    padding: 0.75rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
    font-size: 0.95rem;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}

.profile-nav-item:hover {
    background: #eee;
}

.profile-nav-item.is-active {
    background: #0066ff;
    color: white;
    border-left-color: #0066ff;
}

.profile-content {
    min-height: 400px;
}

.profile-section {
    background: white;
    border-radius: 8px;
    padding: 2rem;
}

.profile-section h1 {
    margin-top: 0;
    margin-bottom: 2rem;
}

.profile-section h2 {
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.profile-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #999;
}

@media (max-width: 768px) {
    .profile-layout {
        grid-template-columns: 1fr;
    }
    
    .profile-sidebar {
        position: relative;
        top: auto;
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
        </section>
    </div>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
