<article class="product-card">
    <?php
    $productPrice = $product['sale_price'] ?? $product['price'];
    $formattedPrice = is_numeric($productPrice) ? 'TZS ' . number_format((float) $productPrice, 0) : $productPrice;
    $rating = !empty($product['avg_rating']) ? number_format((float) $product['avg_rating'], 1) : ($product['rating'] ?? '4.8');
    $productUrl = isset($product['slug']) ? 'product.php?slug=' . urlencode($product['slug']) : 'product.php?name=' . urlencode($product['name']);
    ?>
    <a href="<?php echo htmlspecialchars($productUrl); ?>">
        <img src="<?php echo htmlspecialchars($product['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    </a>
    <div class="product-card__body">
        <div class="product-card__title-row">
            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
            <button class="icon-button" type="button" aria-label="Add <?php echo htmlspecialchars($product['name']); ?> to wishlist">♡</button>
        </div>
        <p class="rating">Rating <?php echo htmlspecialchars($rating); ?>/5</p>
        <strong><?php echo htmlspecialchars($formattedPrice); ?></strong>
        <a class="button button--small" href="cart.php?add=<?php echo urlencode($product['id'] ?? $product['name']); ?>">Add to Cart</a>
    </div>
</article>
