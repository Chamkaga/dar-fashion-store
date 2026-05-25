<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/models/ActivityLog.php';

$conn = (new Database())->connect();
$users = $conn ? $conn->query("SELECT id, fullname, email, phone, role, status, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
$activityLog = $conn ? new ActivityLog($conn) : null;

// Get all activities for each user
$userActivities = [];
$onlineUsers = [];
if ($activityLog) {
    $onlineUsers = $activityLog->getOnlineUsers();
    $onlineUserIds = array_column($onlineUsers, 'id');
    
    foreach ($users as $user) {
        $userActivities[$user['id']] = $activityLog->getUserActivities($user['id'], 5);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Admin Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <style>
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .user-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s;
        }
        .user-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .user-card.online::before {
            content: '●';
            color: #00cc44;
            font-size: 1.2rem;
            margin-right: 0.5rem;
        }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        .user-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #333;
        }
        .user-role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .user-role-badge.customer {
            background: #e3f2fd;
            color: #1976d2;
        }
        .user-role-badge.admin {
            background: #fff3e0;
            color: #e65100;
        }
        .user-details {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin: 1rem 0;
            font-size: 0.9rem;
            color: #666;
        }
        .user-detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .user-detail-label {
            font-weight: 500;
            color: #999;
            min-width: 60px;
        }
        .user-activities {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        .user-activities-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: #333;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .activity-item {
            padding: 0.5rem;
            background: #fafafa;
            border-radius: 4px;
            font-size: 0.8rem;
            color: #666;
        }
        .activity-item-type {
            font-weight: 500;
            color: #333;
        }
        .online-status {
            color: #00cc44;
            font-weight: 600;
        }
    </style>
</head>
<body>
<main class="section">
    <div class="container">
        <div class="section-heading">
            <p class="section-kicker">Admin</p>
            <h1>Users & Customers</h1>
            <p>Manage all registered users and track their activities</p>
        </div>

        <div style="margin-bottom: 2rem; padding: 1rem; background: #e3f2fd; border-radius: 6px; border-left: 4px solid #1976d2;">
            <p><strong>Total Users:</strong> <?php echo count($users); ?> | 
            <strong>Online Now:</strong> <span class="online-status"><?php echo count($onlineUsers); ?></span></p>
        </div>

        <div class="users-grid">
            <?php foreach ($users as $user): 
                $isOnline = in_array($user['id'], array_column($onlineUsers, 'id'));
                $activities = $userActivities[$user['id']] ?? [];
            ?>
                <div class="user-card <?php echo $isOnline ? 'online' : ''; ?>">
                    <div class="user-header">
                        <div>
                            <div class="user-name"><?php echo htmlspecialchars($user['fullname']); ?></div>
                            <div class="user-role-badge <?php echo $user['role']; ?>">
                                <?php echo htmlspecialchars($user['role']); ?>
                            </div>
                        </div>
                        <?php if ($isOnline): ?>
                            <span class="online-status">● Active</span>
                        <?php endif; ?>
                    </div>

                    <div class="user-details">
                        <div class="user-detail-item">
                            <span class="user-detail-label">Email:</span>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <?php if ($user['phone']): ?>
                            <div class="user-detail-item">
                                <span class="user-detail-label">Phone:</span>
                                <span><?php echo htmlspecialchars($user['phone']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="user-detail-item">
                            <span class="user-detail-label">Joined:</span>
                            <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div class="user-detail-item">
                            <span class="user-detail-label">Status:</span>
                            <span style="color: <?php echo $user['status'] === 'active' ? '#00cc44' : '#ff6600'; ?>; font-weight: 600;">
                                <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($activities): ?>
                        <div class="user-activities">
                            <div class="user-activities-title">Recent Activities</div>
                            <div class="activity-list">
                                <?php foreach (array_slice($activities, 0, 3) as $activity): ?>
                                    <div class="activity-item">
                                        <span class="activity-item-type">
                                            <?php 
                                            $labels = [
                                                'login' => '🔓 Login',
                                                'logout' => '🔒 Logout',
                                                'view_product' => '👁️ Product View',
                                                'add_to_cart' => '🛒 Add to Cart',
                                                'remove_from_cart' => '✕ Remove',
                                                'checkout' => '💳 Checkout',
                                                'order_placed' => '✓ Order',
                                                'payment_attempted' => '💰 Payment',
                                            ];
                                            echo $labels[$activity['activity_type']] ?? htmlspecialchars($activity['activity_type']);
                                            ?>
                                        </span>
                                        <br>
                                        <small><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <p style="margin-top: 2rem;"><a href="../index.php">Back to dashboard</a></p>
    </div>
</main>
</body>
</html>
