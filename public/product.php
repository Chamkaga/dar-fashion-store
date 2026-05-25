<?php
$pageTitle = 'Product Details | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';
include __DIR__ . '/../app/includes/header.php';
$database = new Database();
$conn = $database->connect();
$productModel = $conn ? new Product($conn) : null;
$product = null;

if ($productModel) {
    if (!empty($_GET['slug'])) {
        $product = $productModel->getBySlug($_GET['slug']);
    } elseif (!empty($_GET['id'])) {
        $product = $productModel->getById((int) $_GET['id']);
    }
}

if (!$product) {
    http_response_code(404);
} else {
    // Log product view activity
    if (is_logged_in()) {
        $user = current_user();
        $activityLog = new ActivityLog($conn);
        $activityLog->log($user['id'], 'view_product', [
            'product_id' => $product['id'],
            'details' => ['product_name' => $product['name']]
        ]);
    }
}

$images = $product && $productModel ? $productModel->getImages($product['id']) : [];
$mainImage = $product['image'] ?? 'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=900&q=80';
$displayPrice = $product ? ($product['sale_price'] ?: $product['price']) : 0;
?>
<main class="section">
    <?php if (!$product): ?>
        <div class="container success-panel">
            <p class="section-kicker">Product not found</p>
            <h1>This product is not available.</h1>
            <a class="button button--primary" href="shop.php">Back to Shop</a>
        </div>
    <?php else: ?>
    <div class="container product-detail">
        <section class="product-gallery">
            <img class="product-gallery__main" src="<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            <div class="thumbnail-row">
                <?php foreach ($images ?: [['image_url' => $mainImage, 'alt_text' => $product['name']]] as $image): ?>
                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="<?php echo htmlspecialchars($image['alt_text'] ?? $product['name']); ?>">
                <?php endforeach; ?>
            </div>
        </section>
        <section class="product-info">
            <p class="section-kicker">Product details</p>
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="rating"><?php echo htmlspecialchars($product['category_name'] ?? 'Fashion'); ?> | Stock: <?php echo (int) $product['stock_quantity']; ?></p>
            <strong class="product-price">TZS <?php echo number_format((float) $displayPrice, 0); ?></strong>
            <p><?php echo htmlspecialchars($product['description']); ?></p>
            <p><strong>Sizes:</strong> <?php echo htmlspecialchars($product['size_options'] ?? 'One Size'); ?></p>
            <p><strong>Colors:</strong> <?php echo htmlspecialchars($product['color_options'] ?? 'Available colors'); ?></p>
            <form class="purchase-box" action="cart.php" method="get">
                <label for="quantity">Quantity</label>
                <input id="quantity" name="qty" type="number" value="1" min="1">
                <input type="hidden" name="add" value="<?php echo (int) $product['id']; ?>">
                <button class="button button--primary" type="submit">Add to Cart</button>
                <a class="button button--dark" href="checkout.php?buy=<?php echo (int) $product['id']; ?>">Buy Now</a>
            </form>
        </section>
    </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
