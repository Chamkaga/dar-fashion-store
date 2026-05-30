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
$priceRange = trim($_GET['price'] ?? '');
$minRating = isset($_GET['rating']) && $_GET['rating'] === '4' ? 4 : 0;
$products = [];
$categories = [];

if ($conn) {
    $productModel = new Product($conn);
    $categoryModel = new Category($conn);
    $products = $productModel->search($search, $activeCategory, $sort, $priceRange, $minRating);
    $categories = $categoryModel->getAll();
}
?>
<main class="section">
    <div class="container shop-layout">
        <aside class="filter-panel">
            <h1>Filters</h1>
            <form id="filter-form" method="get">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                
                <fieldset>
                    <legend>Categories</legend>
                    <a class="<?php echo $activeCategory === '' ? 'filter-link is-active' : 'filter-link'; ?>" href="shop.php?q=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>">All Products</a>
                    <?php foreach ($categories as $category): ?>
                        <a class="<?php echo $activeCategory === $category['slug'] ? 'filter-link is-active' : 'filter-link'; ?>" href="shop.php?q=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category['slug']); ?>&sort=<?php echo urlencode($sort); ?>&price=<?php echo urlencode($priceRange); ?>&rating=<?php echo $minRating; ?>">
                            <?php echo htmlspecialchars($category['name']); ?> (<?php echo (int) $category['product_count']; ?>)
                        </a>
                    <?php endforeach; ?>
                </fieldset>
                
                <fieldset>
                    <legend>Price</legend>
                    <label>
                        <input type="radio" name="price" value="under-30000" <?php echo $priceRange === 'under-30000' ? 'checked' : ''; ?> onchange="document.getElementById('filter-form').submit()">
                        Under TZS 30,000
                    </label>
                    <label>
                        <input type="radio" name="price" value="30000-70000" <?php echo $priceRange === '30000-70000' ? 'checked' : ''; ?> onchange="document.getElementById('filter-form').submit()">
                        TZS 30,000 - 70,000
                    </label>
                    <label>
                        <input type="radio" name="price" value="above-70000" <?php echo $priceRange === 'above-70000' ? 'checked' : ''; ?> onchange="document.getElementById('filter-form').submit()">
                        Above TZS 70,000
                    </label>
                    <button type="button" style="margin-top: 8px; display: block; width: 100%; padding: 8px; background: none; border: none; color: var(--primary); cursor: pointer; text-align: left; font-size: 0.85rem;" onclick="document.querySelector('input[name=price]:checked').checked = false; document.getElementById('filter-form').submit();">Clear price filter</button>
                </fieldset>
                
                <fieldset>
                    <legend>Rating</legend>
                    <label>
                        <input type="checkbox" name="rating" value="4" <?php echo $minRating === 4 ? 'checked' : ''; ?> onchange="document.getElementById('filter-form').submit()">
                        4 stars and above
                    </label>
                </fieldset>
                
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($activeCategory); ?>">
            </form>
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
                    <input type="hidden" name="price" value="<?php echo htmlspecialchars($priceRange); ?>">
                    <input type="hidden" name="rating" value="<?php echo $minRating; ?>">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle quick wishlist buttons on product cards
    const wishlistBtns = document.querySelectorAll('.wishlist-quick-btn');
    
    wishlistBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Check if user is logged in (if button has no product id, user is not logged in)
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            
            if (!productId) {
                window.location.href = 'login.php';
                return;
            }
            
            // Show loading state
            const originalText = this.textContent;
            this.disabled = true;
            this.textContent = '⌛';
            
            // Call API
            fetch(`../api/wishlist.php?action=toggle&product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.added) {
                            this.textContent = '♥';
                            this.style.color = '#e74c3c';
                            
                            // Show feedback
                            const feedback = document.createElement('div');
                            feedback.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #4caf50; color: white; padding: 12px 20px; border-radius: 4px; z-index: 9999; font-weight: 600;';
                            feedback.textContent = '♥ Added to Wishlist!';
                            document.body.appendChild(feedback);
                            setTimeout(() => feedback.remove(), 2000);
                        } else {
                            this.textContent = '♡';
                            this.style.color = 'inherit';
                            
                            // Show feedback
                            const feedback = document.createElement('div');
                            feedback.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #ff9800; color: white; padding: 12px 20px; border-radius: 4px; z-index: 9999; font-weight: 600;';
                            feedback.textContent = 'Removed from Wishlist';
                            document.body.appendChild(feedback);
                            setTimeout(() => feedback.remove(), 2000);
                        }
                    } else {
                        if (data.message && data.message.includes('login')) {
                            window.location.href = 'login.php';
                        } else {
                            alert('Error: ' + data.message);
                            this.textContent = originalText;
                        }
                    }
                    
                    this.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.textContent = originalText;
                    this.disabled = false;
                });
        });
    });
});
</script>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
