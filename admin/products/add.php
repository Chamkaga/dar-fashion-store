<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/includes/upload.php';

$conn = (new Database())->connect();
$message = '';
$error = '';
$categories = $conn ? $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $imagePath = '';
    
    // Handle image upload: priority to file upload, then URL
    if (!empty($_FILES['image_file']['name'])) {
        // User uploaded a file
        $uploadResult = upload_product_image($_FILES['image_file']);
        if ($uploadResult['success']) {
            $imagePath = $uploadResult['path'];
        } else {
            $error = $uploadResult['error'];
        }
    } elseif (!empty($_POST['image_url'])) {
        // Use external URL if no file uploaded
        $imagePath = $_POST['image_url'];
    } else {
        // No image provided
        $error = 'Please provide either an image file upload or external image URL.';
    }
    
    // Only proceed if we have an image
        if (!$error && $imagePath) {
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
            $imagePath,
            $_POST['description'],
            $_POST['size_options'],
            $_POST['color_options'],
            $_POST['stock_quantity'],
            isset($_POST['is_featured']) ? 1 : 0,
            isset($_POST['is_trending']) ? 1 : 0
        ]);
        $message = 'Product added successfully.';
            $product_id = $conn->lastInsertId();

            // Handle variants if provided as JSON
            if (!empty($_POST['variants_json'])) {
                $variants = json_decode($_POST['variants_json'], true);
                if (is_array($variants)) {
                    $pvStmt = $conn->prepare("INSERT INTO product_variants (product_id, sku, color, size, price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
                    foreach ($variants as $v) {
                        $pvStmt->execute([
                            $product_id,
                            $v['sku'] ?? null,
                            $v['color'] ?? null,
                            $v['size'] ?? null,
                            $v['price'] ?? null,
                            $v['stock'] ?? 0
                        ]);
                    }
                }
            } else {
                // Create a default variant to preserve existing behaviour
                $pvStmt = $conn->prepare("INSERT INTO product_variants (product_id, sku, price, stock_quantity) VALUES (?, ?, ?, ?)");
                $pvStmt->execute([$product_id, $sku, $_POST['price'], $_POST['stock_quantity'] ?? 0]);
            }

            // Handle additional uploaded images (multiple)
            if (!empty($_FILES['image_files']) && is_array($_FILES['image_files']['name'])) {
                $imgStmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, alt_text, sort_order) VALUES (?, ?, ?, ?)");
                $count = count($_FILES['image_files']['name']);
                for ($i = 0; $i < $count; $i++) {
                    if (empty($_FILES['image_files']['name'][$i])) continue;
                    $fileArray = [
                        'name' => $_FILES['image_files']['name'][$i],
                        'type' => $_FILES['image_files']['type'][$i],
                        'tmp_name' => $_FILES['image_files']['tmp_name'][$i],
                        'error' => $_FILES['image_files']['error'][$i],
                        'size' => $_FILES['image_files']['size'][$i]
                    ];
                    $uploadResult = upload_product_image($fileArray);
                    if ($uploadResult['success']) {
                        $imgStmt->execute([$product_id, $uploadResult['path'], $_POST['name'] . ' image', $i]);
                    }
                }
            }
            $message = 'Product added successfully.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | Dar Fashion Store Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>
<body>
<main class="admin-shell">
    <?php include __DIR__ . '/../../app/includes/admin-sidebar.php'; ?>
    <section class="admin-content">
        <header class="admin-page-header">
            <div>
                <p class="section-kicker">Product Management</p>
                <h1>Add New Product</h1>
                <p>Create a new product listing with all required details.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--secondary" href="index.php">Back to Products</a>
            </div>
        </header>

        <?php if ($message): ?><p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
        <?php if ($error): ?><p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>

        <div class="admin-grid-two">
            <article class="admin-panel" style="margin-top: 0;">
                <h2>Product Information</h2>
                <form class="admin-form-grid" method="post" enctype="multipart/form-data">
                    <label class="admin-form-grid__wide">Product Name
                        <input name="name" type="text" placeholder="e.g., Linen Summer Dress" required>
                    </label>
                    <label>Category
                        <select name="category_id">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo (int) $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Price (TZS)
                        <input name="price" type="number" step="0.01" placeholder="50000" required>
                    </label>
                    <label>Sale Price (TZS)
                        <input name="sale_price" type="number" step="0.01" placeholder="Optional">
                    </label>
                    <label class="admin-form-grid__wide">Image - Upload from Device
                        <input name="image_file" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                    </label>
                    <div class="admin-form-grid__wide" style="text-align: center; padding: 8px 0; color: #666;">
                        <strong>OR</strong>
                    </div>
                    <label class="admin-form-grid__wide">Image - External URL
                        <input name="image_url" type="url" placeholder="https://example.com/image.jpg">
                    </label>
                    <p class="admin-form-grid__wide" style="color: #666; font-size: 0.9rem; margin: -8px 0 16px;">
                        <strong>Note:</strong> Please provide either an image file upload OR an external URL. If both are provided, the uploaded file will be used.
                    </p>
                    <label class="admin-form-grid__wide">Description
                        <textarea name="description" rows="4" placeholder="Detailed product description..."></textarea>
                    </label>
                    <label>Size Options
                        <input name="size_options" type="text" placeholder="S,M,L,XL">
                    </label>
                    <label>Color Options
                        <input name="color_options" type="text" placeholder="Black,Gold,Orange">
                    </label>
                    <label>Stock Quantity
                        <input name="stock_quantity" type="number" value="10" min="0">
                    </label>
                    <label class="admin-form-grid__wide" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_featured"> <strong>Featured Product</strong>
                    </label>
                    <label class="admin-form-grid__wide" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_trending"> <strong>Trending Product</strong>
                    </label>
                    <button class="button button--primary admin-form-grid__wide" type="submit">Save Product</button>
                </form>
            </article>

            <article class="admin-panel" style="margin-top: 0;">
                <h2>Product Preview</h2>
                <div class="product-preview-card">
                    <div class="product-preview-image">
                        <img src="https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=400&q=80" alt="Preview">
                    </div>
                    <div class="product-preview-info">
                        <h3>Product Preview</h3>
                        <p class="preview-label">This is how your product will appear on the storefront.</p>
                        <div class="preview-rating">
                            <span class="star star-empty">★</span>
                            <span class="star star-empty">★</span>
                            <span class="star star-empty">★</span>
                            <span class="star star-empty">★</span>
                            <span class="star star-empty">★</span>
                            <span class="rating-number">0.0</span>
                        </div>
                        <div class="preview-price">
                            <strong class="regular-price">TZS 0</strong>
                        </div>
                        <div class="preview-badges">
                            <span class="preview-badge">New</span>
                        </div>
                    </div>
                </div>

                <h3 style="margin-top: 24px;">Tips & Guidelines</h3>
                <ul class="admin-tips-list">
                    <li>Use high-quality images (minimum 800x800px)</li>
                    <li>Write detailed descriptions for better SEO</li>
                    <li>Set competitive prices based on market research</li>
                    <li>Keep stock quantities updated regularly</li>
                    <li>Mark trending items to boost visibility</li>
                    <li>Use clear size and color options</li>
                </ul>

                <h3 style="margin-top: 24px;">Required Fields</h3>
                <div class="required-fields-list">
                    <span class="required-field">✓ Product Name</span>
                    <span class="required-field">✓ Category</span>
                    <span class="required-field">✓ Price</span>
                    <span class="required-field">✓ Image (Upload or URL)</span>
                    <span class="required-field">✓ Description</span>
                </div>
            </article>
        </div>
    </section>
</main>
</body>
</html>
