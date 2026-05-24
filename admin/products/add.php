<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$message = '';
$categories = $conn ? $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $_POST['name']), '-'));
    $sku = 'DFS-' . strtoupper(substr(md5($_POST['name'] . time()), 0, 8));
    $stmt = $conn->prepare("
        INSERT INTO products (category_id, name, slug, sku, price, sale_price, image, description, size_options, color_options, stock_quantity, is_featured, is_trending)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_POST['category_id'] ?: null,
        $_POST['name'],
        $slug,
        $sku,
        $_POST['price'],
        $_POST['sale_price'] ?: null,
        $_POST['image'],
        $_POST['description'],
        $_POST['size_options'],
        $_POST['color_options'],
        $_POST['stock_quantity'],
        isset($_POST['is_featured']) ? 1 : 0,
        isset($_POST['is_trending']) ? 1 : 0
    ]);
    $message = 'Product added successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Add Product</title><link rel="stylesheet" href="../../assets/css/style.css"><link rel="stylesheet" href="../../assets/css/responsive.css"></head>
<body>
<main class="section"><div class="container auth-page">
    <form class="auth-card" method="post">
        <p class="section-kicker">Admin</p><h1>Add Product</h1>
        <?php if ($message): ?><p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <label>Category<select name="category_id"><?php foreach ($categories as $category): ?><option value="<?php echo (int) $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option><?php endforeach; ?></select></label>
        <label>Name<input name="name" required></label>
        <label>Price<input name="price" type="number" step="0.01" required></label>
        <label>Sale Price<input name="sale_price" type="number" step="0.01"></label>
        <label>Image URL<input name="image" required></label>
        <label>Description<textarea name="description" rows="4"></textarea></label>
        <label>Sizes<input name="size_options" placeholder="S,M,L,XL"></label>
        <label>Colors<input name="color_options" placeholder="Black,Gold,Orange"></label>
        <label>Stock<input name="stock_quantity" type="number" value="1" min="0"></label>
        <label><input type="checkbox" name="is_featured"> Featured</label>
        <label><input type="checkbox" name="is_trending"> Trending</label>
        <button class="button button--primary" type="submit">Save Product</button>
        <a href="index.php">Back to products</a>
    </form>
</div></main>
</body></html>
