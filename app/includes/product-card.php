<article class="product-card">
    <?php
    $productPrice = $product['sale_price'] ?? $product['price'];
    $formattedPrice = is_numeric($productPrice) ? 'TZS ' . number_format((float) $productPrice, 0) : $productPrice;
    $rating = !empty($product['avg_rating']) ? number_format((float) $product['avg_rating'], 1) : ($product['rating'] ?? '4.8');
    $productUrl = isset($product['slug']) ? 'product.php?slug=' . urlencode($product['slug']) : 'product.php?name=' . urlencode($product['name']);
    $hasDiscount = isset($product['sale_price']) && $product['sale_price'] < $product['price'];
    ?>
    <a href="<?php echo htmlspecialchars($productUrl); ?>">
        <img src="<?php echo htmlspecialchars($product['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    </a>
    <div class="product-card__body">
        <div class="product-card__title-row">
            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
            <button class="icon-button wishlist-quick-btn" type="button" aria-label="Add <?php echo htmlspecialchars($product['name']); ?> to wishlist" data-product-id="<?php echo (int) ($product['id'] ?? 0); ?>" data-product-name="<?php echo htmlspecialchars($product['name']); ?>">♡</button>
        </div>
        
        <!-- Rating Stars -->
        <div class="product-card-rating">
            <?php 
            $ratingValue = floatval($rating);
            for ($i = 1; $i <= 5; $i++): 
                $starClass = $i <= $ratingValue ? 'star-filled' : 'star-empty';
            ?>
                <span class="star <?php echo $starClass; ?>">★</span>
            <?php endfor; ?>
            <span class="rating-number"><?php echo htmlspecialchars($rating); ?></span>
        </div>
        
        <!-- Price with Discount Badge -->
        <div class="product-card-price">
            <?php if ($hasDiscount): ?>
                <span class="original-price"><?php echo 'TZS ' . number_format((float) $product['price'], 0); ?></span>
                <strong class="sale-price"><?php echo htmlspecialchars($formattedPrice); ?></strong>
                <span class="discount-badge-mini">Save <?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%</span>
            <?php else: ?>
                <strong class="regular-price"><?php echo htmlspecialchars($formattedPrice); ?></strong>
            <?php endif; ?>
        </div>
        
        <a class="button button--small" href="cart.php?add=<?php echo urlencode($product['id'] ?? $product['name']); ?>">Add to Cart</a>
    </div>
</article>
