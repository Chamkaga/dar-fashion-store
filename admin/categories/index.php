<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$categories = $conn ? $conn->query("SELECT categories.*, COUNT(products.id) AS product_count FROM categories LEFT JOIN products ON products.category_id = categories.id GROUP BY categories.id ORDER BY categories.name")->fetchAll(PDO::FETCH_ASSOC) : [];

$adminPage = 'categories';
$adminRoot = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management | Dar Fashion Store Admin</title>
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
                <p class="section-kicker">Product Management</p>
                <h1>Categories</h1>
                <p>Manage product categories and organize your inventory.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--primary" href="../dashboard.php">Dashboard</a>
            </div>
        </header>

        <div class="admin-panel" style="margin-top: 0;">
            <div class="cart-table">
                <div class="cart-row cart-row--head">
                    <span>Name</span>
                    <span>Slug</span>
                    <span>Products</span>
                    <span>Status</span>
                    <span>Created</span>
                </div>
                <?php foreach ($categories as $category): ?>
                    <div class="cart-row">
                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                        <span><?php echo htmlspecialchars($category['slug']); ?></span>
                        <span><?php echo (int) $category['product_count']; ?></span>
                        <span><?php echo ((int) $category['is_active'] === 1) ? 'Active' : 'Inactive'; ?></span>
                        <span><?php echo htmlspecialchars(date('M d, Y', strtotime($category['created_at']))); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>
