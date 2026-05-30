<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'new';
        if (in_array($status, ['new', 'read', 'replied'])) {
            $stmt = $conn->prepare("UPDATE messages SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            $message = 'Message status updated.';
        }
    }
}

$messages = $conn ? $conn->query("SELECT * FROM messages ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
$adminPage = 'messages';
$adminRoot = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Dar Fashion Store Admin</title>
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
                <p class="section-kicker">Communication</p>
                <h1>Messages / Contact Requests</h1>
                <p>View and respond to customer inquiries from the contact form.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--primary" href="../dashboard.php">Dashboard</a>
            </div>
        </header>
        <?php if ($message): ?><p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <div class="admin-panel" style="margin-top: 0;">
            <div class="cart-table">
                <div class="cart-row cart-row--head">
                    <span>Name</span>
                    <span>Email</span>
                    <span>Phone</span>
                    <span>Subject</span>
                    <span>Message</span>
                    <span>Status</span>
                    <span>Action</span>
                </div>
                <?php foreach ($messages as $msg): ?>
                    <div class="cart-row">
                        <span><strong><?php echo htmlspecialchars($msg['name']); ?></strong></span>
                        <span><?php echo htmlspecialchars($msg['email']); ?></span>
                        <span><?php echo htmlspecialchars($msg['phone'] ?? ''); ?></span>
                        <span><?php echo htmlspecialchars($msg['subject'] ?? 'Message'); ?></span>
                        <span><?php echo htmlspecialchars(substr($msg['message'], 0, 50)) . '...'; ?></span>
                        <span><?php echo htmlspecialchars($msg['status']); ?></span>
                        <span>
                            <form method="post" style="display:inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="id" value="<?php echo (int) $msg['id']; ?>">
                                <select name="status" onchange="this.form.submit()" style="padding:4px">
                                    <option value="new" <?php echo $msg['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="read" <?php echo $msg['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                    <option value="replied" <?php echo $msg['status'] === 'replied' ? 'selected' : ''; ?>>Replied</option>
                                </select>
                            </form>
                            <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" class="button button--small button--primary" style="margin-left:8px">Reply</a>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>
