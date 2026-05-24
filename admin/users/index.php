<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$users = $conn ? $conn->query("SELECT id, fullname, email, phone, role, status, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Users</title><link rel="stylesheet" href="../../assets/css/style.css"><link rel="stylesheet" href="../../assets/css/responsive.css"></head>
<body><main class="section"><div class="container">
<div class="section-heading"><p class="section-kicker">Admin</p><h1>Users</h1></div>
<div class="cart-table">
    <div class="cart-row cart-row--head"><span>Name</span><span>Email</span><span>Role</span><span>Status</span><span>Joined</span></div>
    <?php foreach ($users as $user): ?>
        <div class="cart-row"><span><?php echo htmlspecialchars($user['fullname']); ?></span><span><?php echo htmlspecialchars($user['email']); ?></span><span><?php echo htmlspecialchars($user['role']); ?></span><span><?php echo htmlspecialchars($user['status']); ?></span><span><?php echo htmlspecialchars(date('M d, Y', strtotime($user['created_at']))); ?></span></div>
    <?php endforeach; ?>
</div>
<p><a href="../index.php">Back to dashboard</a></p>
</div></main></body></html>
