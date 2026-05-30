<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/models/ActivityLog.php';

$conn = (new Database())->connect();
$activityLog = $conn ? new ActivityLog($conn) : null;

// Get filter parameter
$filter = $_GET['filter'] ?? 'all';
$limit = 100;

// Get activities based on filter
$activities = [];
if ($activityLog) {
    $activities = $activityLog->getRecentActivities($limit);
}

// Filter activities by type if requested
if ($filter !== 'all' && $activities) {
    $activities = array_filter($activities, function($activity) use ($filter) {
        return $activity['activity_type'] === $filter;
    });
}

// Group activities by date for better display
$groupedActivities = [];
foreach ($activities as $activity) {
    $date = date('M d, Y', strtotime($activity['created_at']));
    if (!isset($groupedActivities[$date])) {
        $groupedActivities[$date] = [];
    }
    $groupedActivities[$date][] = $activity;
}

$adminPage = 'activities';
$adminRoot = '../';

function getActivityBadgeColor($activityType) {
    $colors = [
        'login' => '#0066ff',
        'logout' => '#999999',
        'view_product' => '#0099ff',
        'add_to_cart' => '#00cc44',
        'remove_from_cart' => '#ff6600',
        'checkout' => '#ff9900',
        'order_placed' => '#00aa00',
        'payment_attempted' => '#ff9900',
        'order_status_update' => '#0066ff'
    ];
    return $colors[$activityType] ?? '#666666';
}

function getActivityLabel($activityType) {
    $labels = [
        'login' => 'Login',
        'logout' => 'Logout',
        'view_product' => 'Viewed Product',
        'add_to_cart' => 'Added to Cart',
        'remove_from_cart' => 'Removed from Cart',
        'checkout' => 'Started Checkout',
        'order_placed' => 'Placed Order',
        'payment_attempted' => 'Payment Attempt',
        'order_status_update' => 'Order Updated'
    ];
    return $labels[$activityType] ?? ucfirst(str_replace('_', ' ', $activityType));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activities | Dar Fashion Store Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <style>
        .activity-timeline {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        .activity-date-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .activity-date-header {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .activity-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1rem;
            align-items: center;
            padding: 0.75rem 1rem;
            background: #fafafa;
            border-radius: 6px;
            border-left: 4px solid #0066ff;
            font-size: 0.95rem;
        }
        .activity-item:hover {
            background: #f5f5f5;
        }
        .activity-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f0f0f0;
            color: white;
            font-weight: 600;
            font-size: 0.8rem;
            flex-shrink: 0;
        }
        .activity-content {
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .activity-main {
            font-weight: 500;
            color: #333;
        }
        .activity-user {
            font-size: 0.85rem;
            color: #999;
        }
        .activity-time {
            font-size: 0.85rem;
            color: #999;
            text-align: right;
        }
        .activity-filters {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .activity-filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .activity-filter-btn.active {
            background: #0066ff;
            color: white;
            border-color: #0066ff;
        }
        .activity-filter-btn:hover {
            border-color: #0066ff;
        }
        .empty-state {
            padding: 2rem;
            text-align: center;
            color: #999;
        }
    </style>
</head>
<body>
<main class="admin-shell">
    <?php include __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>
    <section class="admin-content">
        <header class="admin-page-header">
            <div>
                <p class="section-kicker">System Monitoring</p>
                <h1>User Activities</h1>
                <p>Real-time tracking of all customer actions and order progress</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--primary" href="../dashboard.php">Dashboard</a>
            </div>
        </header>

        <div class="activity-filters">
            <a href="index.php?filter=all" class="activity-filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All Activities</a>
            <a href="index.php?filter=login" class="activity-filter-btn <?php echo $filter === 'login' ? 'active' : ''; ?>">Logins</a>
            <a href="index.php?filter=view_product" class="activity-filter-btn <?php echo $filter === 'view_product' ? 'active' : ''; ?>">Product Views</a>
            <a href="index.php?filter=add_to_cart" class="activity-filter-btn <?php echo $filter === 'add_to_cart' ? 'active' : ''; ?>">Cart Additions</a>
            <a href="index.php?filter=order_placed" class="activity-filter-btn <?php echo $filter === 'order_placed' ? 'active' : ''; ?>">Orders</a>
            <a href="index.php?filter=payment_attempted" class="activity-filter-btn <?php echo $filter === 'payment_attempted' ? 'active' : ''; ?>">Payments</a>
        </div>

        <div class="activity-timeline">
            <?php if (empty($groupedActivities)): ?>
                <div class="empty-state">
                    <p>No activities found.</p>
                </div>
            <?php else: ?>
                <?php foreach ($groupedActivities as $date => $dayActivities): ?>
                    <div class="activity-date-group">
                        <div class="activity-date-header"><?php echo htmlspecialchars($date); ?></div>
                        <?php foreach ($dayActivities as $activity): ?>
                            <div class="activity-item" style="border-left-color: <?php echo getActivityBadgeColor($activity['activity_type']); ?>;">
                                <div class="activity-badge" style="background-color: <?php echo getActivityBadgeColor($activity['activity_type']); ?>;">
                                    <?php echo substr($activity['activity_type'], 0, 1); ?>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-main">
                                        <strong><?php echo htmlspecialchars($activity['fullname']); ?></strong>
                                        <?php echo getActivityLabel($activity['activity_type']); ?>
                                        <?php if ($activity['product_name']): ?>
                                            - <em><?php echo htmlspecialchars($activity['product_name']); ?></em>
                                        <?php endif; ?>
                                        <?php if ($activity['order_number']): ?>
                                            - <a href="../orders/view.php?id=<?php echo (int) $activity['order_id']; ?>"><?php echo htmlspecialchars($activity['order_number']); ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-user">
                                        <?php echo htmlspecialchars($activity['email']); ?>
                                    </div>
                                </div>
                                <div class="activity-time">
                                    <?php echo date('H:i', strtotime($activity['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
</body>
</html>
