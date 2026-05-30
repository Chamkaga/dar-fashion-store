<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$products = $conn ? $conn->query("
    SELECT products.*, categories.name AS category_name
    FROM products
    LEFT JOIN categories ON categories.id = products.category_id
    ORDER BY products.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC) : [];

$adminPage = 'products';
$adminRoot = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management | Dar Fashion Store Admin</title>
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
                <h1>Products</h1>
                <p>Manage your product catalog, inventory, and pricing.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--primary" href="add.php">Add Product</a>
                <a class="button button--secondary" href="../dashboard.php">Dashboard</a>
            </div>
        </header>

        <div class="admin-panel" style="margin-top: 0;">
            <div class="cart-table">
                <div class="cart-row cart-row--head">
                    <span>Product</span>
                    <span>Category</span>
                    <span>Price</span>
                    <span>Stock</span>
                    <span>Action</span>
                </div>
                <?php foreach ($products as $product): ?>
                    <div class="cart-row">
                        <span><?php echo htmlspecialchars($product['name']); ?></span>
                        <span><?php echo htmlspecialchars($product['category_name'] ?? 'None'); ?></span>
                        <span>TZS <?php echo number_format((float) $product['price'], 0); ?></span>
                        <span><?php echo (int) $product['stock_quantity']; ?></span>
                        <span><a class="button button--small" href="edit.php?id=<?php echo (int) $product['id']; ?>">Edit</a></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
</body>
</html>
