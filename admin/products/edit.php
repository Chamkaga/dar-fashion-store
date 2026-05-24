<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$id = (int) ($_GET['id'] ?? 0);
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $stmt = $conn->prepare("UPDATE products SET name=?, price=?, sale_price=?, stock_quantity=?, status=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['price'], $_POST['sale_price'] ?: null, $_POST['stock_quantity'], $_POST['status'], $id]);
    $message = 'Product updated.';
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Edit Product</title><link rel="stylesheet" href="../../assets/css/style.css"></head>
<body><main class="section"><div class="container auth-page">
<?php if ($product): ?>
<form class="auth-card" method="post">
    <p class="section-kicker">Admin</p><h1>Edit Product</h1>
    <?php if ($message): ?><p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    <label>Name<input name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required></label>
    <label>Price<input name="price" type="number" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required></label>
    <label>Sale Price<input name="sale_price" type="number" step="0.01" value="<?php echo htmlspecialchars($product['sale_price']); ?>"></label>
    <label>Stock<input name="stock_quantity" type="number" value="<?php echo (int) $product['stock_quantity']; ?>"></label>
    <label>Status<select name="status"><option value="active">Active</option><option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option></select></label>
    <button class="button button--primary" type="submit">Update</button>
    <a href="index.php">Back to products</a>
</form>
<?php else: ?><p class="empty-state">Product not found.</p><?php endif; ?>
</div></main></body></html>
