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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Products | Dar Fashion Store</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>
<body>
<main class="section">
    <div class="container">
        <div class="section-heading section-heading--row">
            <div><p class="section-kicker">Admin</p><h1>Products</h1></div>
            <a class="button button--primary" href="add.php">Add Product</a>
        </div>
        <div class="cart-table">
            <div class="cart-row cart-row--head"><span>Product</span><span>Category</span><span>Price</span><span>Stock</span><span>Action</span></div>
            <?php foreach ($products as $product): ?>
                <div class="cart-row">
                    <span><?php echo htmlspecialchars($product['name']); ?></span>
                    <span><?php echo htmlspecialchars($product['category_name'] ?? 'None'); ?></span>
                    <span>TZS <?php echo number_format((float) $product['price'], 0); ?></span>
                    <span><?php echo (int) $product['stock_quantity']; ?></span>
                    <span><a href="edit.php?id=<?php echo (int) $product['id']; ?>">Edit</a></span>
                </div>
            <?php endforeach; ?>
        </div>
        <p><a href="../index.php">Back to dashboard</a></p>
    </div>
</main>
</body>
</html>
