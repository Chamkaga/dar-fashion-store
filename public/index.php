<?php
$pageTitle = 'Dar Fashion Store | Modern Fashion Marketplace';
$pageDescription = 'Shop clothing, shoes, bags, and accessories from Dar Fashion Store.';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/Category.php';
include __DIR__ . '/../app/includes/header.php';

$categories = [
    ['name' => 'Men', 'image' => 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Women', 'image' => 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Shoes', 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Bags', 'image' => 'https://images.unsplash.com/photo-1594223274512-ad4803739b7c?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Accessories', 'image' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=600&q=80'],
];

$featuredProducts = [
    ['name' => 'Linen Summer Dress', 'price' => 'TZS 48,000', 'rating' => '4.8', 'image' => 'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Classic Men Shirt', 'price' => 'TZS 35,000', 'rating' => '4.7', 'image' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Street Sneakers', 'price' => 'TZS 72,000', 'rating' => '4.9', 'image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Everyday Tote Bag', 'price' => 'TZS 42,000', 'rating' => '4.6', 'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=600&q=80'],
];

$trendingProducts = [
    ['name' => 'Printed Kitenge Set', 'price' => 'TZS 58,000', 'rating' => '4.9', 'image' => 'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Leather Sandals', 'price' => 'TZS 39,000', 'rating' => '4.5', 'image' => 'https://images.unsplash.com/photo-1562273138-f46be4ebdf33?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Gold Hoop Earrings', 'price' => 'TZS 18,000', 'rating' => '4.6', 'image' => 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Weekend Backpack', 'price' => 'TZS 55,000', 'rating' => '4.8', 'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Relaxed Denim Jacket', 'price' => 'TZS 66,000', 'rating' => '4.7', 'image' => 'https://images.unsplash.com/photo-1543076447-215ad9ba6923?auto=format&fit=crop&w=600&q=80'],
    ['name' => 'Minimal Wrist Watch', 'price' => 'TZS 44,000', 'rating' => '4.4', 'image' => 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?auto=format&fit=crop&w=600&q=80'],
];

$conn = (new Database())->connect();
if ($conn) {
    $productModel = new Product($conn);
    $categoryModel = new Category($conn);
    $dbCategories = $categoryModel->getAll();
    $dbFeatured = $productModel->getFeatured(4);
    $dbTrending = $productModel->getTrending(6);

    if ($dbCategories) {
        $categories = $dbCategories;
    }
    if ($dbFeatured) {
        $featuredProducts = $dbFeatured;
    }
    if ($dbTrending) {
        $trendingProducts = $dbTrending;
    }
}
?>

<main>
    <section class="hero-section section">
        <div class="container hero-layout">
            <aside class="hero-sidebar">
                <h2>Categories</h2>
                <a href="shop.php?category=men">Men Fashion</a>
                <a href="shop.php?category=women">Women Fashion</a>
                <a href="shop.php?category=shoes">Shoes</a>
                <a href="shop.php?category=bags">Bags</a>
                <a href="shop.php?category=accessories">Accessories</a>
            </aside>

            <section class="hero-banner">
                <div>
                    <p class="section-kicker">New arrivals</p>
                    <h1>Dar fashion picks for every day, event, and season.</h1>
                    <p>Browse a marketplace-style collection of clothing, shoes, bags, and accessories ready for fast local delivery.</p>
                    <a class="button button--primary" href="shop.php">Shop Now</a>
                </div>
            </section>

            <aside class="hero-panel">
                <h2>Welcome back</h2>
                <p>Sign in for faster checkout, order tracking, and wishlist saves.</p>
                <a class="button button--dark" href="login.php">Login</a>
                <div class="deal-box">
                    <strong>Today Deals</strong>
                    <span>Up to 35% off selected styles</span>
                </div>
            </aside>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-heading">
                <p class="section-kicker">Shop by category</p>
                <h2>Find your next look faster</h2>
            </div>
            <div class="category-grid">
                <?php foreach ($categories as $category): ?>
                    <a class="category-card" href="shop.php?category=<?php echo urlencode($category['slug'] ?? strtolower($category['name'])); ?>">
                        <img src="<?php echo $category['image']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?> fashion category">
                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section--soft">
        <div class="container">
            <div class="section-heading section-heading--row">
                <div>
                    <p class="section-kicker">Featured products</p>
                    <h2>Popular this week</h2>
                </div>
                <a href="shop.php">View all</a>
            </div>
            <div class="product-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php include __DIR__ . '/../app/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container promo-banner">
            <div>
                <p class="section-kicker">Flash sale</p>
                <h2>Weekend wardrobe refresh</h2>
                <p>Countdown offer: selected outfits, sneakers, and handbags at limited-time prices.</p>
            </div>
            <div class="countdown" aria-label="Countdown timer">
                <span>02 Days</span>
                <span>14 Hours</span>
                <span>38 Min</span>
            </div>
            <a class="button button--accent" href="shop.php?sale=1">Shop Sale</a>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-heading">
                <p class="section-kicker">Trending products</p>
                <h2>Marketplace favorites</h2>
            </div>
            <div class="product-grid product-grid--wide">
                <?php foreach ($trendingProducts as $product): ?>
                    <?php include __DIR__ . '/../app/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section section--soft">
        <div class="container benefit-grid">
            <article class="benefit-card"><strong>Free Delivery</strong><span>On selected Dar es Salaam orders.</span></article>
            <article class="benefit-card"><strong>Secure Payments</strong><span>Checkout designed for trust and clarity.</span></article>
            <article class="benefit-card"><strong>24/7 Support</strong><span>Customer help by phone, email, and WhatsApp.</span></article>
            <article class="benefit-card"><strong>Easy Returns</strong><span>Simple return steps for eligible items.</span></article>
        </div>
    </section>

    <section class="section">
        <div class="container testimonial-grid">
            <article class="testimonial-card">"Very good products and the quality matches the photos."<strong>- Amina</strong></article>
            <article class="testimonial-card">"Fast delivery and helpful support during checkout."<strong>- Kelvin</strong></article>
            <article class="testimonial-card">"Affordable prices for outfits I can wear often."<strong>- Neema</strong></article>
        </div>
    </section>

    <section class="newsletter-section section" id="newsletter">
        <div class="container newsletter-box">
            <div>
                <p class="section-kicker">Newsletter</p>
                <h2>Subscribe for new drops and promotions</h2>
            </div>
            <form class="newsletter-form" method="post">
                <label class="sr-only" for="newsletter-email">Email address</label>
                <input id="newsletter-email" type="email" placeholder="Email address">
                <button type="submit">Subscribe</button>
            </form>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
