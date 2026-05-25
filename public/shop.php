<?php
$pageTitle = 'Shop | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/Category.php';
include __DIR__ . '/../app/includes/header.php';
$database = new Database();
$conn = $database->connect();
$search = trim($_GET['q'] ?? '');
$activeCategory = trim($_GET['category'] ?? '');
$sort = trim($_GET['sort'] ?? 'featured');
$products = [];
$categories = [];

if ($conn) {
    $productModel = new Product($conn);
    $categoryModel = new Category($conn);
    $products = $productModel->search($search, $activeCategory, $sort);
    $categories = $categoryModel->getAll();
}
?>
<main class="section">
    <div class="container shop-layout">
        <aside class="filter-panel">
            <h1>Filters</h1>
            <fieldset>
                <legend>Categories</legend>
                <a class="<?php echo $activeCategory === '' ? 'filter-link is-active' : 'filter-link'; ?>" href="shop.php">All Products</a>
                <?php foreach ($categories as $category): ?>
                    <a class="<?php echo $activeCategory === $category['slug'] ? 'filter-link is-active' : 'filter-link'; ?>" href="shop.php?category=<?php echo urlencode($category['slug']); ?>">
                        <?php echo htmlspecialchars($category['name']); ?> (<?php echo (int) $category['product_count']; ?>)
                    </a>
                <?php endforeach; ?>
            </fieldset>
            <fieldset>
                <legend>Price</legend>
                <label><input type="radio" name="price"> Under TZS 30,000</label>
                <label><input type="radio" name="price"> TZS 30,000 - 70,000</label>
                <label><input type="radio" name="price"> Above TZS 70,000</label>
            </fieldset>
            <fieldset>
                <legend>Rating</legend>
                <label><input type="checkbox"> 4 stars and above</label>
            </fieldset>
        </aside>
        <section>
            <div class="section-heading section-heading--row">
                <div>
                    <p class="section-kicker">Products grid</p>
                    <h2><?php echo $search ? 'Search results for "' . htmlspecialchars($search) . '"' : 'Browse fashion products'; ?></h2>
                </div>
                <form method="get" class="sort-form">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="category" value="<?php echo htmlspecialchars($activeCategory); ?>">
                    <select name="sort" aria-label="Sort products" onchange="this.form.submit()">
                        <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>Sort: Featured</option>
                        <option value="price-low" <?php echo $sort === 'price-low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                    </select>
                </form>
            </div>
            <div class="product-grid product-grid--wide">
                <?php foreach ($products as $product): ?>
                    <?php include __DIR__ . '/../app/includes/product-card.php'; ?>
                <?php endforeach; ?>
                <?php if (!$products): ?>
                    <p class="empty-state">No products found. Try another search or category.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
