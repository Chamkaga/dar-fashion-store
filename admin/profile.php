<?php
require_once __DIR__ . '/../app/includes/auth.php';
require_admin('login.php');
require_once __DIR__ . '/../app/config/db.php';

$admin = current_user();
$adminPhone = '';
$adminSince = null;

$conn = (new Database())->connect();
if ($conn) {
    $stmt = $conn->prepare("SELECT phone, created_at FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$admin['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $adminPhone = $row['phone'] ?? '';
    $adminSince = $row['created_at'] ?? null;
}

$adminInitial = strtoupper(substr($admin['fullname'] ?? 'A', 0, 1));
$adminPage = 'profile';
$adminRoot = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Admin Account | Dar Fashion Store</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
<main class="admin-shell">
    <?php include __DIR__ . '/../app/includes/admin-sidebar.php'; ?>

    <section class="admin-content">
        <header class="admin-page-header">
            <div>
                <p class="section-kicker">Administrator only</p>
                <h1>My Admin Account</h1>
                <p>Your store management login. Customer shopping profiles live separately under <a href="users/index.php">Users & Accounts</a>.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--primary" href="dashboard.php">Dashboard</a>
            </div>
        </header>

        <article class="admin-panel profile-hero" style="display: grid; grid-template-columns: auto 1fr; gap: 20px; align-items: center;">
            <div class="profile-avatar" style="background: var(--secondary); color: #fff; width: 80px; height: 80px; font-size: 2rem;"><?php echo htmlspecialchars($adminInitial); ?></div>
            <div>
                <h2 style="margin: 0 0 8px; color: var(--secondary);"><?php echo htmlspecialchars($admin['fullname']); ?></h2>
                <span class="admin-role-badge admin">Administrator</span>
                <p style="margin: 12px 0 0; color: var(--muted);"><?php echo htmlspecialchars($admin['email']); ?></p>
                <?php if ($adminPhone): ?>
                    <p style="margin: 6px 0 0; color: var(--muted);">Phone: <?php echo htmlspecialchars($adminPhone); ?></p>
                <?php endif; ?>
                <?php if ($adminSince): ?>
                    <p style="margin: 6px 0 0; color: var(--muted);">Admin since <?php echo date('M d, Y', strtotime($adminSince)); ?></p>
                <?php endif; ?>
            </div>
        </article>

        <section class="admin-grid-two">
            <article class="admin-panel" style="margin-top: 18px;">
                <h2>Admin workspace</h2>
                <p>Manage products, orders, payments, inventory, and customer accounts from the dashboard.</p>
                <p><a class="button button--primary" href="dashboard.php">Open Dashboard</a></p>
            </article>
            <article class="admin-panel" style="margin-top: 18px;">
                <h2>Customer storefront</h2>
                <p>Preview the public shop and customer experience. Do not use the customer profile page while logged in as admin.</p>
                <p><a class="button button--secondary" href="../public/index.php">View Storefront</a></p>
            </article>
        </section>

        <p style="margin-top: 20px;"><a href="logout.php" class="button button--dark">Log Out</a></p>
    </section>
</main>
</body>
</html>
