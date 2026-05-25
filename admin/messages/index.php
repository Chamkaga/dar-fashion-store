<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$messages = $conn ? $conn->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Messages</title><link rel="stylesheet" href="../../assets/css/style.css"><link rel="stylesheet" href="../../assets/css/responsive.css"></head>
<body><main class="section"><div class="container"><div class="section-heading"><p class="section-kicker">Admin</p><h1>Messages / Contact Requests</h1></div><div class="cart-table"><div class="cart-row cart-row--head"><span>Name</span><span>Email</span><span>Phone</span><span>Subject</span><span>Status</span></div><?php foreach ($messages as $message): ?><div class="cart-row"><span><?php echo htmlspecialchars($message['name']); ?></span><span><?php echo htmlspecialchars($message['email']); ?></span><span><?php echo htmlspecialchars($message['phone'] ?? ''); ?></span><span><?php echo htmlspecialchars($message['subject'] ?? 'Message'); ?></span><span><?php echo htmlspecialchars($message['status']); ?></span></div><?php endforeach; ?></div><p><a href="../dashboard.php">Back to dashboard</a></p></div></main></body></html>
