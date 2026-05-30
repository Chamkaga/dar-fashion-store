<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/models/ActivityLog.php';

$conn = (new Database())->connect();
$activityLog = $conn ? new ActivityLog($conn) : null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle_status') {
        $id = (int) ($_POST['user_id'] ?? 0);
        $newStatus = $_POST['status'] ?? 'active';
        if (in_array($newStatus, ['active', 'blocked'])) {
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'customer'");
            $stmt->execute([$newStatus, $id]);
            $message = 'User status updated successfully.';
        }
    }
}

$roleFilter = $_GET['role'] ?? 'all';
if (!in_array($roleFilter, ['all', 'customer', 'admin'], true)) {
    $roleFilter = 'all';
}
$search = trim($_GET['q'] ?? '');
$selectedId = (int) ($_GET['id'] ?? 0);

$users = [];
$onlineUserIds = [];

if ($conn) {
    $sql = "SELECT id, fullname, email, phone, role, status, created_at FROM users WHERE 1=1";
    $params = [];

    if ($roleFilter !== 'all') {
        $sql .= " AND role = ?";
        $params[] = $roleFilter;
    }

    if ($search !== '') {
        $sql .= " AND (fullname LIKE ? OR email LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($activityLog) {
        $onlineUserIds = array_column($activityLog->getOnlineUsers(), 'id');
    }
}

$selectedUser = null;
$selectedStats = ['orders' => 0, 'spent' => 0];
$selectedOrders = [];
$selectedActivities = [];

if ($conn && $selectedId > 0) {
    $stmt = $conn->prepare("SELECT id, fullname, email, phone, role, status, created_at FROM users WHERE id = ?");
    $stmt->execute([$selectedId]);
    $selectedUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($selectedUser) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS orders, COALESCE(SUM(total), 0) AS spent
            FROM orders WHERE user_id = ?
        ");
        $stmt->execute([$selectedId]);
        $selectedStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: $selectedStats;

        $stmt = $conn->prepare("
            SELECT order_number, total, status, created_at
            FROM orders WHERE user_id = ?
            ORDER BY created_at DESC LIMIT 5
        ");
        $stmt->execute([$selectedId]);
        $selectedOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($activityLog) {
            $selectedActivities = $activityLog->getUserActivities($selectedId, 10);
        }
    }
}

$activityLabels = [
    'login' => 'Login',
    'logout' => 'Logout',
    'view_product' => 'Product view',
    'add_to_cart' => 'Add to cart',
    'remove_from_cart' => 'Remove from cart',
    'checkout' => 'Checkout',
    'order_placed' => 'Order placed',
    'payment_attempted' => 'Payment',
    'order_status_update' => 'Status update',
];

function users_query_params($role, $search, $id = null) {
    $params = [];
    if ($role !== 'all') {
        $params['role'] = $role;
    }
    if ($search !== '') {
        $params['q'] = $search;
    }
    if ($id) {
        $params['id'] = $id;
    }
    return $params ? '?' . http_build_query($params) : '';
}

$adminPage = 'users';
$adminRoot = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users & Accounts | Dar Fashion Store Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>
<body>
<main class="admin-shell">
    <?php include __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>

    <section class="admin-content">
        <header class="admin-page-header">
            <div>
                <p class="section-kicker">User management</p>
                <h1>Users & Accounts</h1>
                <p>Browse all accounts on the left. Select <strong>View</strong> to open full details on the right — customer data stays hidden until you choose a user.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--secondary" href="../profile.php">My Admin Account</a>
                <a class="button button--primary" href="../dashboard.php">Dashboard</a>
            </div>
        </header>

        <?php if ($message): ?><p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>

        <div class="admin-users-summary">
            <p>
                <strong><?php echo count($users); ?></strong> accounts shown
                <?php if ($roleFilter !== 'all'): ?> (<?php echo htmlspecialchars($roleFilter); ?> only)<?php endif; ?>
                · <strong><?php echo count($onlineUserIds); ?></strong> online now
            </p>
        </div>

        <div class="admin-users-layout">
            <div class="admin-user-list-panel">
                <div class="admin-user-list-panel__head">
                    <h2>All users</h2>
                    <div class="admin-user-filters">
                        <a href="index.php<?php echo users_query_params('all', $search); ?>" class="<?php echo $roleFilter === 'all' ? 'is-active' : ''; ?>">All</a>
                        <a href="index.php<?php echo users_query_params('customer', $search); ?>" class="<?php echo $roleFilter === 'customer' ? 'is-active' : ''; ?>">Customers</a>
                        <a href="index.php<?php echo users_query_params('admin', $search); ?>" class="<?php echo $roleFilter === 'admin' ? 'is-active' : ''; ?>">Admins</a>
                    </div>
                    <form method="get" action="index.php">
                        <?php if ($roleFilter !== 'all'): ?>
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($roleFilter); ?>">
                        <?php endif; ?>
                        <?php if ($selectedId): ?>
                            <input type="hidden" name="id" value="<?php echo $selectedId; ?>">
                        <?php endif; ?>
                        <input class="admin-user-search" type="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or email">
                    </form>
                </div>

                <div class="admin-user-list">
                    <?php if (!$users): ?>
                        <p class="empty-state" style="padding: 24px;">No users match your filters.</p>
                    <?php endif; ?>
                    <?php foreach ($users as $user):
                        $isOnline = in_array($user['id'], $onlineUserIds, true);
                        $isSelected = $selectedId === (int) $user['id'];
                        $initial = strtoupper(substr($user['fullname'], 0, 1));
                        $viewHref = 'index.php' . users_query_params($roleFilter, $search, $user['id']);
                    ?>
                        <a class="admin-user-list-item <?php echo $isSelected ? 'is-selected' : ''; ?>" href="<?php echo htmlspecialchars($viewHref); ?>">
                            <span class="admin-user-list-item__avatar"><?php echo htmlspecialchars($initial); ?></span>
                            <span class="admin-user-list-item__meta">
                                <strong><?php echo htmlspecialchars($user['fullname']); ?></strong>
                                <span>
                                    <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                                    <?php if ($isOnline): ?> · Online<?php endif; ?>
                                </span>
                            </span>
                            <span class="admin-user-list-item__view">View</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-user-detail-panel">
                <div class="admin-user-detail-panel__head">
                    <h2>Account details</h2>
                </div>

                <?php if (!$selectedUser): ?>
                    <div class="admin-user-detail-empty">
                        <div>
                            <strong>Select a user to view details</strong>
                            <p>Email, phone, orders, and activity history appear here only after you choose an account from the list.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="admin-user-detail-panel__body">
                        <?php if ($selectedUser['role'] === 'admin'): ?>
                            <p class="admin-account-note">This is an <strong>administrator</strong> account for store management — not a customer shopping profile. Customer self-service is at <code>/public/profile.php</code>.</p>
                        <?php else: ?>
                            <p class="admin-account-note">Customer account overview. Personal shopping data is not mixed with your admin login.</p>
                        <?php endif; ?>

                        <div class="admin-user-detail-hero">
                            <div class="profile-avatar" style="background: <?php echo $selectedUser['role'] === 'admin' ? 'var(--secondary)' : 'var(--primary)'; ?>; color: #fff;">
                                <?php echo strtoupper(substr($selectedUser['fullname'], 0, 1)); ?>
                            </div>
                            <div>
                                <h2 style="margin: 0 0 6px; color: var(--secondary);"><?php echo htmlspecialchars($selectedUser['fullname']); ?></h2>
                                <span class="admin-role-badge <?php echo htmlspecialchars($selectedUser['role']); ?>">
                                    <?php echo htmlspecialchars($selectedUser['role']); ?>
                                </span>
                                <?php if (in_array($selectedUser['id'], $onlineUserIds, true)): ?>
                                    <span style="margin-left: 8px; color: var(--success); font-weight: 700;">Online</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="admin-detail-grid">
                            <div class="admin-detail-stat">
                                <span>Total orders</span>
                                <strong><?php echo (int) $selectedStats['orders']; ?></strong>
                            </div>
                            <div class="admin-detail-stat">
                                <span>Total spent</span>
                                <strong>TZS <?php echo number_format((float) $selectedStats['spent'], 0); ?></strong>
                            </div>
                        </div>

                        <div class="admin-detail-fields">
                            <p><strong>Email</strong><?php echo htmlspecialchars($selectedUser['email']); ?></p>
                            <p><strong>Phone</strong><?php echo htmlspecialchars($selectedUser['phone'] ?: 'Not provided'); ?></p>
                            <p><strong>Status</strong>
                                <?php if ($selectedUser['role'] === 'customer'): ?>
                                    <span class="admin-role-badge <?php echo htmlspecialchars($selectedUser['status']); ?>">
                                        <?php echo htmlspecialchars(ucfirst($selectedUser['status'])); ?>
                                    </span>
                                    <form method="post" style="display:inline; margin-left:8px;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?php echo (int) $selectedUser['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $selectedUser['status'] === 'active' ? 'blocked' : 'active'; ?>">
                                        <button class="button button--small button--secondary" type="submit">
                                            <?php echo $selectedUser['status'] === 'active' ? 'Block' : 'Activate'; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <?php echo htmlspecialchars(ucfirst($selectedUser['status'])); ?>
                                <?php endif; ?>
                            </p>
                            <p><strong>Member since</strong><?php echo date('M d, Y', strtotime($selectedUser['created_at'])); ?></p>
                        </div>

                        <?php if ($selectedOrders): ?>
                            <h3 style="margin: 0 0 12px; color: var(--secondary); font-size: 1rem;">Recent orders</h3>
                            <div class="cart-table" style="margin-bottom: 22px;">
                                <div class="cart-row cart-row--head">
                                    <span>Order</span><span>Date</span><span>Status</span><span>Total</span>
                                </div>
                                <?php foreach ($selectedOrders as $order): ?>
                                    <div class="cart-row">
                                        <span><?php echo htmlspecialchars($order['order_number']); ?></span>
                                        <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                        <span><?php echo htmlspecialchars(ucfirst($order['status'])); ?></span>
                                        <span>TZS <?php echo number_format((float) $order['total'], 0); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <h3 style="margin: 0 0 12px; color: var(--secondary); font-size: 1rem;">Recent activity</h3>
                        <?php if ($selectedActivities): ?>
                            <div class="admin-activity-feed">
                                <?php foreach ($selectedActivities as $activity): ?>
                                    <div class="admin-activity-item">
                                        <span>
                                            <span class="admin-activity-tag"><?php echo htmlspecialchars($activityLabels[$activity['activity_type']] ?? $activity['activity_type']); ?></span>
                                            <?php if (!empty($activity['product_name'])): ?>
                                                <?php echo htmlspecialchars($activity['product_name']); ?>
                                            <?php endif; ?>
                                        </span>
                                        <small style="color: var(--muted);"><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="empty-state">No recorded activity for this user yet.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>
