<?php
$cartCount = $cartCount ?? count($_SESSION['cart'] ?? []);
$navBase = $navBase ?? $basePath ?? '..';
$loggedInUser = current_user();
?>
<header class="site-header">
    <section class="topbar">
        <div class="container topbar__inner">
            <div class="topbar__group">
                <span>Language: English</span>
                <span>Currency: TZS</span>
            </div>
            <nav class="topbar__links" aria-label="Utility navigation">
                <a href="<?php echo $navBase; ?>/public/contact.php">Help</a>
                <a href="<?php echo $navBase; ?>/public/track-order.php">Track Order</a>
                <?php if ($loggedInUser): ?>
                    <a href="<?php echo $navBase; ?>/public/logout.php">Logout</a>
                <?php else: ?>
                    <a href="<?php echo $navBase; ?>/public/login.php">Login</a>
                    <a href="<?php echo $navBase; ?>/public/register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </section>

    <section class="main-nav">
        <div class="container main-nav__inner">
            <a class="logo" href="<?php echo $navBase; ?>/public/index.php" aria-label="Dar Fashion Store home">
                <span class="logo__mark">DF</span>
                <span class="logo__text">Dar Fashion Store</span>
            </a>

            <form class="search-form" action="<?php echo $navBase; ?>/public/shop.php" method="get">
                <label class="sr-only" for="site-search">Search products</label>
                <input id="site-search" name="q" type="search" placeholder="Search dresses, shoes, bags, accessories">
                <button type="submit">Search</button>
            </form>

            <nav class="action-nav" aria-label="Account actions">
                <a href="<?php echo $navBase; ?>/public/cart.php">Cart <strong><?php echo (int) $cartCount; ?></strong></a>
                <a href="<?php echo $navBase; ?>/public/shop.php?wishlist=1">Wishlist</a>
                <a href="<?php echo $navBase; ?>/public/profile.php"><?php echo $loggedInUser ? htmlspecialchars($loggedInUser['fullname']) : 'Profile'; ?></a>
            </nav>

            <button class="mobile-menu-button" type="button" data-menu-toggle aria-expanded="false" aria-controls="category-menu">
                Menu
            </button>
        </div>
    </section>

    <nav class="category-nav" id="category-menu" aria-label="Product categories">
        <div class="container category-nav__inner">
            <a href="<?php echo $navBase; ?>/public/shop.php?category=men">Men</a>
            <a href="<?php echo $navBase; ?>/public/shop.php?category=women">Women</a>
            <a href="<?php echo $navBase; ?>/public/shop.php?category=shoes">Shoes</a>
            <a href="<?php echo $navBase; ?>/public/shop.php?category=bags">Bags</a>
            <a href="<?php echo $navBase; ?>/public/shop.php?category=accessories">Accessories</a>
            <a href="<?php echo $navBase; ?>/public/shop.php?category=new-arrivals">New Arrivals</a>
        </div>
    </nav>
</header>
