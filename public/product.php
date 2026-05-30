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
$variants = $product && $productModel ? $productModel->getVariants($product['id']) : [];
$variantImages = $product && $productModel ? $productModel->getVariantImages($product['id']) : [];
$defaultImage = 'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=900&q=80';
$mainImage = $product ? ($product['image'] ?: $defaultImage) : $defaultImage;
$displayPrice = $product ? ($product['sale_price'] ?: $product['price']) : 0;

// Check if product is in user's wishlist
$inWishlist = false;
if ($conn && $product && is_logged_in()) {
    $user = current_user();
    $stmt = $conn->prepare("SELECT id FROM user_wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user['id'], $product['id']]);
    $inWishlist = (bool) $stmt->fetch();
}

// Get product reviews
$reviews = [];
$averageRating = 0;
$totalRatings = 0;
if ($conn && $product) {
    $stmt = $conn->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
    $stmt->execute([$product['id']]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($reviews) {
        $totalRatings = count($reviews);
        $sumRatings = array_sum(array_column($reviews, 'rating'));
        $averageRating = $totalRatings > 0 ? round($sumRatings / $totalRatings, 1) : 0;
    }
}

// Handle review submission
$reviewMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn && $product) {
    if (!is_logged_in()) {
        $reviewMessage = 'Please login to submit a review.';
    } else {
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $user = current_user();
        
        if ($rating < 1 || $rating > 5) {
            $reviewMessage = 'Please select a rating between 1 and 5 stars.';
        } elseif (empty($comment)) {
            $reviewMessage = 'Please write a review comment.';
        } else {
            // Check if user already reviewed this product
            $stmt = $conn->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
            $stmt->execute([$product['id'], $user['id']]);
            $existingReview = $stmt->fetch();
            
            if ($existingReview) {
                $reviewMessage = 'You have already reviewed this product.';
            } else {
                $stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, customer_name, rating, comment) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$product['id'], $user['id'], $user['fullname'], $rating, $comment]);
                $reviewMessage = 'Thank you for your review!';
                
                // Refresh reviews
                $stmt = $conn->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
                $stmt->execute([$product['id']]);
                $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $totalRatings = count($reviews);
                $sumRatings = array_sum(array_column($reviews, 'rating'));
                $averageRating = $totalRatings > 0 ? round($sumRatings / $totalRatings, 1) : 0;
            }
        }
    }
}
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
            
            <!-- Rating Display -->
            <div class="product-rating-card">
                <?php if ($totalRatings > 0): ?>
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo $i <= $averageRating ? 'star-filled' : 'star-empty'; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-info">
                        <strong><?php echo $averageRating; ?></strong>
                        <span>out of 5</span>
                        <span>(<?php echo $totalRatings; ?> reviews)</span>
                    </div>
                <?php else: ?>
                    <div class="rating-stars">
                        <span class="star star-empty">★</span>
                        <span class="star star-empty">★</span>
                        <span class="star star-empty">★</span>
                        <span class="star star-empty">★</span>
                        <span class="star star-empty">★</span>
                    </div>
                    <div class="rating-info">
                        <span>No reviews yet</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Price Card -->
            <div class="product-price-card">
                <div id="price_block">
                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                    <span class="original-price" id="original_price">TZS <?php echo number_format((float) $product['price'], 0); ?></span>
                    <strong class="sale-price" id="product_price_display">TZS <?php echo number_format((float) $displayPrice, 0); ?></strong>
                    <span class="discount-badge" id="discount_badge">Save <?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>%</span>
                <?php else: ?>
                    <strong class="regular-price" id="product_price_display">TZS <?php echo number_format((float) $displayPrice, 0); ?></strong>
                <?php endif; ?>
                </div>
            </div>

            <p class="product-meta"><?php echo htmlspecialchars($product['category_name'] ?? 'Fashion'); ?> | Stock: <span id="product_stock_display"><?php echo (int) $product['stock_quantity']; ?></span></p>
            <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
            <p><strong>Sizes:</strong> <?php echo htmlspecialchars($product['size_options'] ?? 'One Size'); ?></p>
            <p><strong>Colors:</strong> <?php echo htmlspecialchars($product['color_options'] ?? 'Available colors'); ?></p>
            
            <form class="purchase-box" action="cart.php" method="post">
                <?php if ($variants): ?>
                    <label for="color_select">Color</label>
                    <select id="color_select"></select>
                    <label for="size_select">Size</label>
                    <select id="size_select"></select>
                <?php endif; ?>
                <label for="quantity">Quantity</label>
                <input id="quantity" name="qty" type="number" value="1" min="1">
                <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                <input type="hidden" name="variant_id" id="variant_id" value="<?php echo $variants[0]['id'] ?? ''; ?>">
                <button class="button button--primary" type="submit">Add to Cart</button>
                <a class="button button--dark" href="checkout.php?buy=<?php echo (int) $product['id']; ?>">Buy Now</a>
            </form>
            
            <div style="margin-top: 16px;">
                <button id="wishlist-btn" class="button button--secondary" style="width: 100%;" <?php echo !is_logged_in() ? 'data-login-required="true"' : ''; ?> data-product-id="<?php echo (int) $product['id']; ?>" data-added="<?php echo $inWishlist ? 'true' : 'false'; ?>">
                    <span id="wishlist-icon"><?php echo $inWishlist ? '♥' : '♡'; ?></span>
                    <span id="wishlist-text"><?php echo $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?></span>
                </button>
            </div>
        </section>
    </div>
    
    <!-- Reviews Section -->
    <div class="container">
        <section class="reviews-section">
            <h2>Customer Reviews</h2>
            
            <?php if ($reviewMessage): ?>
                <p class="alert <?php echo strpos($reviewMessage, 'Thank you') !== false ? 'alert--success' : 'alert--error'; ?>">
                    <?php echo htmlspecialchars($reviewMessage); ?>
                </p>
            <?php endif; ?>
            
            <!-- Review Form -->
            <?php if (is_logged_in()): ?>
                <div class="review-form-card">
                    <h3>Write a Review</h3>
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                        <label>Rating</label>
                        <div class="rating-input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                <label for="star<?php echo $i; ?>" class="star-label">★</label>
                            <?php endfor; ?>
                        </div>
                        <label>Your Review</label>
                        <textarea name="comment" rows="4" placeholder="Share your experience with this product..." required></textarea>
                        <button class="button button--primary" type="submit">Submit Review</button>
                    </form>
                </div>
            <?php else: ?>
                <p class="login-prompt">Please <a href="login.php">login</a> to write a review.</p>
            <?php endif; ?>
            
            <!-- Reviews List -->
            <div class="reviews-list">
                <?php if ($reviews): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                                <div class="review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $review['rating'] ? 'star-filled' : 'star-empty'; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                            <small class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-reviews">No reviews yet. Be the first to review this product!</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variant handling: build UI from server-side data
    const variants = <?php echo json_encode($variants ?: []); ?>;
    const variantImages = <?php echo json_encode($variantImages ?: []); ?>;
    const colorSelect = document.getElementById('color_select');
    const sizeSelect = document.getElementById('size_select');
    const variantInput = document.getElementById('variant_id');
    const mainImageEl = document.querySelector('.product-gallery__main');

    function populateOptions() {
        if (!variants || variants.length === 0) return;
        const colors = Array.from(new Set(variants.map(v => v.color).filter(Boolean)));
        const sizes = Array.from(new Set(variants.map(v => v.size).filter(Boolean)));

        if (colorSelect && colors.length) {
            colorSelect.innerHTML = '<option value="">Choose color</option>';
            colors.forEach(c => {
                const opt = document.createElement('option'); opt.value = c; opt.textContent = c; colorSelect.appendChild(opt);
            });
        }
        if (sizeSelect && sizes.length) {
            sizeSelect.innerHTML = '<option value="">Choose size</option>';
            sizes.forEach(s => {
                const opt = document.createElement('option'); opt.value = s; opt.textContent = s; sizeSelect.appendChild(opt);
            });
        }
    }

    function updateAvailability() {
        const selColor = colorSelect ? colorSelect.value : '';
        const selSize = sizeSelect ? sizeSelect.value : '';

        // Determine allowed combinations
        const allowed = variants.filter(v => {
            if (selColor && selSize) return v.color === selColor && v.size === selSize && v.stock_quantity > 0;
            if (selColor) return v.color === selColor && v.stock_quantity > 0;
            if (selSize) return v.size === selSize && v.stock_quantity > 0;
            return v.stock_quantity > 0;
        });

        // Disable sizes/colors not present
        if (colorSelect) {
            const opts = Array.from(colorSelect.options);
            opts.forEach(o => {
                if (!o.value) return; // keep placeholder
                const has = variants.some(v => v.color === o.value && (!selSize || v.size === selSize) && v.stock_quantity > 0);
                o.disabled = !has;
            });
        }
        if (sizeSelect) {
            const opts = Array.from(sizeSelect.options);
            opts.forEach(o => {
                if (!o.value) return;
                const has = variants.some(v => v.size === o.value && (!selColor || v.color === selColor) && v.stock_quantity > 0);
                o.disabled = !has;
            });
        }

        // Pick a matching variant
        let found = null;
        if (selColor && selSize) {
            found = variants.find(v => v.color === selColor && v.size === selSize);
        }
        if (!found) {
            found = allowed[0] || variants[0];
        }

        if (found) {
            if (variantInput) variantInput.value = found.id;
            const priceEl = document.getElementById('product_price_display');
            const stockEl = document.getElementById('product_stock_display');
            if (priceEl && (found.price !== null && found.price !== '')) priceEl.textContent = 'TZS ' + Math.round(found.price).toLocaleString();
            if (stockEl) stockEl.textContent = (found.stock_quantity !== null ? parseInt(found.stock_quantity) : 0);

            // switch image if available
            const vImgs = variantImages[found.id] || variantImages[0] || [];
            if (vImgs.length && mainImageEl) {
                mainImageEl.src = vImgs[0];
            }
        }
    }

    populateOptions();
    if (colorSelect) colorSelect.addEventListener('change', updateAvailability);
    if (sizeSelect) sizeSelect.addEventListener('change', updateAvailability);
    updateAvailability();

    // Wishlist and rating code (unchanged)
    const wishlistBtn = document.getElementById('wishlist-btn');
    if (!wishlistBtn) return;
    
    wishlistBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Check if login is required
        if (this.dataset.loginRequired === 'true') {
            window.location.href = 'login.php';
            return;
        }
        
        const productId = this.dataset.productId;
        const isAdded = this.dataset.added === 'true';
        const action = isAdded ? 'remove' : 'add';
        
        // Show loading state
        this.disabled = true;
        const originalText = document.getElementById('wishlist-text').textContent;
        document.getElementById('wishlist-text').textContent = 'Loading...';
        
        // Call API
        fetch(`../api/wishlist.php?action=${action}&product_id=${productId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const icon = document.getElementById('wishlist-icon');
                    const text = document.getElementById('wishlist-text');
                    
                    if (data.added) {
                        icon.textContent = '♥';
                        text.textContent = 'Remove from Wishlist';
                        wishlistBtn.dataset.added = 'true';
                        wishlistBtn.classList.add('button--added');
                        
                        // Show feedback
                        const feedback = document.createElement('div');
                        feedback.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #4caf50; color: white; padding: 12px 20px; border-radius: 4px; z-index: 9999;';
                        feedback.textContent = '♥ Added to wishlist!';
                        document.body.appendChild(feedback);
                        setTimeout(() => feedback.remove(), 3000);
                    } else {
                        icon.textContent = '♡';
                        text.textContent = 'Add to Wishlist';
                        wishlistBtn.dataset.added = 'false';
                        wishlistBtn.classList.remove('button--added');
                        
                        // Show feedback
                        const feedback = document.createElement('div');
                        feedback.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #ff9800; color: white; padding: 12px 20px; border-radius: 4px; z-index: 9999;';
                        feedback.textContent = '♡ Removed from wishlist';
                        document.body.appendChild(feedback);
                        setTimeout(() => feedback.remove(), 3000);
                    }
                } else {
                    alert('Error: ' + data.message);
                    document.getElementById('wishlist-text').textContent = originalText;
                }
                
                // Re-enable button
                wishlistBtn.disabled = false;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating wishlist');
                document.getElementById('wishlist-text').textContent = originalText;
                wishlistBtn.disabled = false;
            });
    });
    
    // Add star rating functionality
    const ratingInput = document.querySelectorAll('input[name="rating"]');
    const starLabels = document.querySelectorAll('.star-label');
    
    starLabels.forEach((label, index) => {
        label.style.cursor = 'pointer';
        label.style.fontSize = '2rem';
        label.style.transition = 'color 0.2s ease';
        label.style.color = '#ddd';
        
        label.addEventListener('mouseover', function() {
            for (let i = 0; i <= index; i++) {
                starLabels[i].style.color = '#ffc107';
            }
        });
        
        label.addEventListener('mouseout', function() {
            // Check if a rating is already selected
            const selectedRating = document.querySelector('input[name="rating"]:checked');
            if (selectedRating) {
                const selectedIndex = parseInt(selectedRating.id.replace('star', '')) - 1;
                for (let i = 0; i < starLabels.length; i++) {
                    starLabels[i].style.color = i <= selectedIndex ? '#ffc107' : '#ddd';
                }
            } else {
                for (let i = 0; i < starLabels.length; i++) {
                    starLabels[i].style.color = '#ddd';
                }
            }
        });
        
        label.addEventListener('click', function() {
            for (let i = 0; i < starLabels.length; i++) {
                starLabels[i].style.color = i < index + 1 ? '#ffc107' : '#ddd';
            }
        });
    });
    
    // Initialize star colors based on selected rating
    const selectedRating = document.querySelector('input[name="rating"]:checked');
    if (selectedRating && starLabels.length > 0) {
        const selectedIndex = parseInt(selectedRating.id.replace('star', '')) - 1;
        for (let i = 0; i < starLabels.length; i++) {
            starLabels[i].style.color = i <= selectedIndex ? '#ffc107' : '#ddd';
        }
    }
});
</script>

<?php include __DIR__ . '/../app/includes/footer.php'; ?>
