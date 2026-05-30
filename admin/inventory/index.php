<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$products = $conn ? $conn->query("SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON categories.id = products.category_id ORDER BY stock_quantity ASC, products.name ASC")->fetchAll(PDO::FETCH_ASSOC) : [];

$adminPage = 'inventory';
$adminRoot = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management | Dar Fashion Store Admin</title>
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
                <h1>Inventory / Stock Management</h1>
                <p>Monitor stock levels and manage product inventory.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--primary" href="../dashboard.php">Dashboard</a>
            </div>
        </header>

        <div class="admin-panel" style="margin-top: 0;">
            <p style="color: var(--muted); margin-bottom: 16px;">Monitor stock levels and manage product inventory. Items with stock ≤ 5 are flagged as low stock.</p>
            <div class="cart-table">
                <div class="cart-row cart-row--head">
                    <span>Product</span>
                    <span>Category</span>
                    <span>SKU</span>
                    <span>Stock</span>
                    <span>Alert</span>
                </div>
                <?php foreach ($products as $product): ?>
                    <div class="cart-row">
                        <span><strong><?php echo htmlspecialchars($product['name']); ?></strong></span>
                        <span><?php echo htmlspecialchars($product['category_name'] ?? 'None'); ?></span>
                        <span><?php echo htmlspecialchars($product['sku']); ?></span>
                        <span style="color: <?php echo ((int) $product['stock_quantity'] <= 5) ? '#c2410c' : 'var(--secondary)'; ?>; font-weight: 600;"><?php echo (int) $product['stock_quantity']; ?></span>
                        <span><?php if ((int) $product['stock_quantity'] <= 5): ?><span style="color: #c2410c; font-weight: 600; background: #fff3e8; padding: 2px 8px; border-radius: 4px;">Low stock</span><?php else: ?><span style="color: #13795b; font-weight: 600;">OK</span><?php endif; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>
