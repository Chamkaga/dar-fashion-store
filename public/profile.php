<?php
$pageTitle = 'My Account | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';
require_once __DIR__ . '/../app/models/OrderTracking.php';

require_customer();

$user = current_user();
$database = new Database();
$conn = $database->connect();

require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/includes/upload.php';
// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $uc = new UserController($conn);
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $fullname = trim($_POST['fullname'] ?? $user['fullname']);
        $email = trim($_POST['email'] ?? $user['email']);
        $phone = trim($_POST['phone'] ?? $user['phone']);
        $shipping = trim($_POST['shipping_address'] ?? $user['shipping_address']);
        $billing = trim($_POST['billing_address'] ?? $user['billing_address']);

        $uc->updateProfile($user['id'], $fullname, $email, $phone, $shipping, $billing);

        // Handle profile picture upload
        if (!empty($_FILES['profile_picture']['name'])) {
            $res = upload_product_image($_FILES['profile_picture'], '../../assets/images/users/');
            if ($res['success']) {
                $uc->uploadProfilePicture($user['id'], $res['path']);
            }
        }

        // refresh user
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$user['id']]); $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        if ($new && password_verify($current, $user['password'])) {
            $uc->changePassword($user['id'], $new);
        }
    }
}

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
            <a href="?tab=dashboard" class="<?php echo $tab === 'dashboard' ? 'is-active' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 10px;">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                Dashboard
            </a>
            <a href="?tab=orders" class="<?php echo $tab === 'orders' ? 'is-active' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 10px;">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                My Orders
            </a>
            <a href="?tab=wishlist" class="<?php echo $tab === 'wishlist' ? 'is-active' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 10px;">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
                Wishlist
            </a>
            <a href="?tab=addresses" class="<?php echo $tab === 'addresses' ? 'is-active' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 10px;">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                Shipping Addresses
            </a>
            <a href="?tab=coupons" class="<?php echo $tab === 'coupons' ? 'is-active' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 10px;">
                    <path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"></path>
                    <path d="M4 6v12c0 1.1.9 2 2 2h14v-4"></path>
                    <path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"></path>
                </svg>
                My Coupons
            </a>
            <a href="?tab=returns" class="<?php echo $tab === 'returns' ? 'is-active' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 10px;">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                </svg>
                Returns & Refunds
            </a>
            <a href="?tab=recently-viewed" class="<?php echo $tab === 'recently-viewed' ? 'is-active' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 10px;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Recently Viewed
            </a>
            <a href="?tab=settings" class="<?php echo $tab === 'settings' ? 'is-active' : ''; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 10px;">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                Account Settings
            </a>
        </aside>

        <section class="account-content">
            <?php if (!$conn): ?>
                <p class="alert alert--error">Database connection failed. Copy <code>.env.example</code> to <code>.env</code>, set your MySQL password, then run <code>php setup-database.php</code>.</p>
            <?php elseif ($profileDbWarning): ?>
                <p class="alert alert--error"><?php echo htmlspecialchars($profileDbWarning); ?></p>
            <?php endif; ?>
            <?php if ($tab === 'dashboard'): ?>
                <article class="account-panel">
                    <div class="section-heading section-heading--row">
                        <div>
                            <p class="section-kicker">Customer Dashboard</p>
                            <h1>Welcome back, <?php echo htmlspecialchars($user['fullname']); ?>!</h1>
                        </div>
                        <a href="shop.php" class="button button--primary">Start Shopping</a>
                    </div>

                    <div class="customer-stat-grid">
                        <div class="customer-stat">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary);">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                            </div>
                            <strong><?php echo count($userOrders); ?></strong>
                            <span>Total Orders</span>
                        </div>
                        <div class="customer-stat">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary);">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <strong>TZS <?php echo number_format($totalSpent, 0); ?></strong>
                            <span>Total Spent</span>
                        </div>
                        <div class="customer-stat">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary);">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                            </div>
                            <strong><?php echo count($wishlistItems); ?></strong>
                            <span>Wishlist Items</span>
                        </div>
                        <div class="customer-stat">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary);">
                                    <path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"></path>
                                    <path d="M4 6v12c0 1.1.9 2 2 2h14v-4"></path>
                                    <path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"></path>
                                </svg>
                            </div>
                            <strong><?php echo count($userCoupons); ?></strong>
                            <span>Available Coupons</span>
                        </div>
                    </div>

                    <div class="section-heading section-heading--row">
                        <h2>Recent Orders</h2>
                        <a href="?tab=orders" style="color: var(--primary); font-weight: 600;">View all →</a>
                    </div>
                    <?php if ($userOrders): ?>
                        <?php 
                        $tracking = $conn ? new OrderTracking($conn) : null;
                        foreach (array_slice($userOrders, 0, 3) as $order): 
                            $timeline = $tracking ? $tracking->buildTimeline($order) : ['steps' => [], 'progress' => 0];
                        ?>
                            <div style="border: 1px solid var(--border); border-radius: 8px; padding: 12px; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <p style="font-weight: 600; color: var(--primary); margin: 0;"><?php echo htmlspecialchars($order['order_number']); ?></p>
                                    <span class="status-pill status-pill--<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                                </div>
                                <p style="font-size: 0.85rem; color: var(--muted); margin: 0 0 6px 0;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                                <div style="width: 100%; height: 4px; background: var(--border); border-radius: 2px; overflow: hidden; margin-bottom: 6px;">
                                    <div style="width: <?php echo (int) $timeline['progress']; ?>%; height: 100%; background: var(--primary);"></div>
                                </div>
                                <p style="font-size: 0.75rem; margin: 0; color: var(--primary); font-weight: 600;"><?php echo (int) $timeline['progress']; ?>% delivered</p>
                                <a href="track-order.php?order_number=<?php echo urlencode($order['order_number']); ?>&email=<?php echo urlencode($user['email']); ?>" class="button button--secondary" style="width: 100%; margin-top: 8px; padding: 6px 12px; font-size: 0.85rem;">Track Order</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-state">No orders yet. <a href="shop.php" style="color: var(--primary); font-weight: 600;">Start shopping!</a></p>
                    <?php endif; ?>
                </article>

            <?php elseif ($tab === 'orders'): ?>
                <article class="account-panel">
                    <div class="section-heading section-heading--row">
                        <div>
                            <p class="section-kicker">Order History</p>
                            <h1>My Orders</h1>
                        </div>
                        <span style="font-size: 0.85rem; color: var(--muted);"><?php echo count($userOrders); ?> total orders</span>
                    </div>
                    <?php if ($userOrders): ?>
                        <?php 
                        $tracking = $conn ? new OrderTracking($conn) : null;
                        foreach ($userOrders as $order): 
                            $timeline = $tracking ? $tracking->buildTimeline($order) : ['steps' => [], 'progress' => 0];
                        ?>
                            <div style="border: 1px solid var(--border); border-radius: 8px; padding: 16px; margin-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                    <div>
                                        <p style="font-weight: 600; color: var(--primary); margin: 0 0 4px 0;"><?php echo htmlspecialchars($order['order_number']); ?></p>
                                        <p style="font-size: 0.85rem; color: var(--muted); margin: 0;">Placed on <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <div style="text-align: right;">
                                        <p style="font-weight: 600; margin: 0 0 4px 0;">TZS <?php echo number_format($order['total'], 0); ?></p>
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <span class="status-pill status-pill--<?php echo htmlspecialchars($order['payment_status'] ?? 'pending'); ?>"><?php echo htmlspecialchars($order['payment_status'] ?? 'pending'); ?></span>
                                            <span class="status-pill status-pill--<?php echo htmlspecialchars($order['status']); ?>"><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tracking Progress Bar -->
                                <div style="margin-bottom: 12px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                        <span style="font-size: 0.85rem; font-weight: 600;">Delivery Progress</span>
                                        <span style="font-size: 0.85rem; color: var(--primary);"><?php echo (int) $timeline['progress']; ?>%</span>
                                    </div>
                                    <div style="width: 100%; height: 6px; background: var(--border); border-radius: 3px; overflow: hidden;">
                                        <div style="width: <?php echo (int) $timeline['progress']; ?>%; height: 100%; background: var(--primary); transition: width 0.3s ease;"></div>
                                    </div>
                                </div>
                                
                                <!-- Current Status -->
                                <?php 
                                $currentStep = null;
                                foreach ($timeline['steps'] as $item) {
                                    if ($item['state'] === 'completed' || $item['state'] === 'current') {
                                        $currentStep = $item['step']['label'];
                                    }
                                }
                                ?>
                                <?php if ($currentStep): ?>
                                    <p style="font-size: 0.85rem; margin: 8px 0; color: var(--muted);">Current: <strong><?php echo htmlspecialchars($currentStep); ?></strong></p>
                                <?php endif; ?>
                                
                                <a href="track-order.php?order_number=<?php echo urlencode($order['order_number']); ?>&email=<?php echo urlencode($user['email']); ?>" class="button button--secondary" style="width: 100%; margin-top: 8px;">View Full Tracking Details</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-state">No orders yet. <a href="shop.php" style="color: var(--primary); font-weight: 600;">Start shopping!</a></p>
                    <?php endif; ?>
                </article>

            <?php elseif ($tab === 'wishlist'): ?>
                <article class="account-panel">
                    <div class="section-heading section-heading--row">
                        <div>
                            <p class="section-kicker">Saved Items</p>
                            <h1>My Wishlist</h1>
                        </div>
                        <span style="font-size: 0.85rem; color: var(--muted);"><?php echo count($wishlistItems); ?> items saved</span>
                    </div>
                    <?php if ($wishlistItems): ?>
                        <div class="customer-product-grid">
                            <?php foreach ($wishlistItems as $item):
                                $displayPrice = $item['sale_price'] ?: $item['price'];
                            ?>
                                <article class="customer-product-card">
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                    <div class="customer-product-card__body">
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p style="color: var(--primary); font-weight: 600; font-size: 1.1rem;">TZS <?php echo number_format($displayPrice, 0); ?></p>
                                        <a href="product.php?slug=<?php echo urlencode($item['slug']); ?>" class="button button--primary" style="width: 100%; margin-top: 12px;">View Product</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">Your wishlist is empty. <a href="shop.php" style="color: var(--primary); font-weight: 600;">Add items to your wishlist!</a></p>
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
                    <div class="section-heading section-heading--row">
                        <div>
                            <p class="section-kicker">Account Management</p>
                            <h1>Account Settings</h1>
                        </div>
                    </div>

                    <div class="settings-panel">
                        <div class="settings-box">
                            <form method="post" action="" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_profile">
                                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                                    <div class="profile-avatar" style="width: 64px; height: 64px; font-size: 1.5rem; background: #eee; display:flex;align-items:center;justify-content:center;">
                                        <?php if (!empty($user['profile_picture'])): ?>
                                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="avatar" style="width:64px;height:64px;border-radius:50%;object-fit:cover;" />
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($customerInitial); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex:1;">
                                        <label>Full Name<input type="text" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required></label>
                                        <label>Email<input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required></label>
                                    </div>
                                </div>
                                <label>Phone<input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"></label>
                                <label>Shipping Address<textarea name="shipping_address" rows="3"><?php echo htmlspecialchars($user['shipping_address'] ?? ''); ?></textarea></label>
                                <label>Billing Address<textarea name="billing_address" rows="3"><?php echo htmlspecialchars($user['billing_address'] ?? ''); ?></textarea></label>
                                <label>Profile Picture<input type="file" name="profile_picture" accept="image/*"></label>
                                <div style="display:flex;gap:8px;margin-top:8px;"><button class="button button--primary" type="submit">Save Profile</button><a href="?tab=settings" class="button button--dark">Cancel</a></div>
                            </form>
                        </div>

                        <div class="settings-box">
                            <h3 style="margin-top:0;">Security</h3>
                            <form method="post" action="">
                                <input type="hidden" name="action" value="change_password">
                                <label>Current Password<input type="password" name="current_password" required></label>
                                <label>New Password<input type="password" name="new_password" required></label>
                                <div style="display:flex;gap:8px;margin-top:8px;"><button class="button button--secondary" type="submit">Change Password</button></div>
                            </form>
                        </div>

                        <div style="display: flex; gap: 12px; margin-top: 8px;">
                            <a href="logout.php" class="button button--dark" style="flex: 1;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                Log Out
                            </a>
                        </div>
                    </div>
                </article>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
