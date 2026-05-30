<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
require_once __DIR__ . '/../../app/includes/upload.php';

$conn = (new Database())->connect();
$id = (int) ($_GET['id'] ?? 0);
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $imagePath = $_POST['image'] ?? ''; // Current image path
    
    // Handle image upload: priority to file upload, then URL
    if (!empty($_FILES['image_file']['name'])) {
        // User uploaded a file
        $uploadResult = upload_product_image($_FILES['image_file']);
        if ($uploadResult['success']) {
            // Delete old image if it's a local file
            if (!empty($imagePath) && strpos($imagePath, 'http') !== 0) {
                delete_product_image($imagePath);
            }
            $imagePath = $uploadResult['path'];
        } else {
            $error = $uploadResult['error'];
        }
    } elseif (!empty($_POST['image_url'])) {
        // User provided external URL
        if (!empty($imagePath) && strpos($imagePath, 'http') !== 0) {
            // Delete old local image if URL is being used
            delete_product_image($imagePath);
        }
        $imagePath = $_POST['image_url'];
    }
    
    if (!$error) {
        $stmt = $conn->prepare("UPDATE products SET name=?, price=?, sale_price=?, stock_quantity=?, image=?, description=?, size_options=?, color_options=?, is_featured=?, is_trending=?, status=? WHERE id=?");
        $stmt->execute([
            $_POST['name'],
            $_POST['price'],
            $_POST['sale_price'] ?: null,
            $_POST['stock_quantity'],
            $imagePath,
            $_POST['description'] ?? '',
            $_POST['size_options'] ?? '',
            $_POST['color_options'] ?? '',
            isset($_POST['is_featured']) ? 1 : 0,
            isset($_POST['is_trending']) ? 1 : 0,
            $_POST['status'],
            $id
        ]);
        // Update variants if provided (replace existing variants for this product)
        if (!empty($_POST['variants_json'])) {
            $variants = json_decode($_POST['variants_json'], true);
            if (is_array($variants)) {
                $conn->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$id]);
                $pvStmt = $conn->prepare("INSERT INTO product_variants (product_id, sku, color, size, price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($variants as $v) {
                    $pvStmt->execute([
                        $id,
                        $v['sku'] ?? null,
                        $v['color'] ?? null,
                        $v['size'] ?? null,
                        $v['price'] ?? null,
                        $v['stock'] ?? 0
                    ]);
                }
            }
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
                    $imgStmt->execute([$id, $uploadResult['path'], $_POST['name'] . ' image', $i]);
                }
            }
        }
        $message = 'Product updated successfully.';
    }
}

$product = null;
if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
    $stmt->execute([$id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

$adminPage = 'products';
$adminRoot = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | Dar Fashion Store Admin</title>
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
                <h1>Edit Product</h1>
                <p>Update product details and inventory.</p>
            </div>
            <div class="admin-page-actions">
                <a class="button button--secondary" href="index.php">Back to Products</a>
                <a class="button button--primary" href="../dashboard.php">Dashboard</a>
            </div>
        </header>

        <?php if (!$conn): ?>
            <p class="alert alert--error">Database connection failed. Check your .env file and run php setup-database.php.</p>
        <?php elseif ($product): ?>
            <div class="admin-panel" style="margin-top: 0;">
                <?php if ($message): ?><p class="alert alert--success"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
                <?php if ($error): ?><p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
                <form class="admin-form-grid" method="post" enctype="multipart/form-data">
                    <label>Product Name<input name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required></label>
                    <label>Price (TZS)<input name="price" type="number" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required></label>
                    <label>Sale Price (TZS)<input name="sale_price" type="number" step="0.01" value="<?php echo htmlspecialchars($product['sale_price'] ?? ''); ?>"></label>
                    <label>Stock Quantity<input name="stock_quantity" type="number" value="<?php echo (int) $product['stock_quantity']; ?>"></label>
                    <label>Status<select name="status">
                        <option value="active" <?php echo ($product['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($product['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select></label>
                    <label class="admin-form-grid__wide">Description
                        <textarea name="description" rows="4" placeholder="Detailed product description..."><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </label>
                    <label>Size Options
                        <input name="size_options" type="text" placeholder="S,M,L,XL" value="<?php echo htmlspecialchars($product['size_options'] ?? ''); ?>">
                    </label>
                    <label>Color Options
                        <input name="color_options" type="text" placeholder="Black,Gold,Orange" value="<?php echo htmlspecialchars($product['color_options'] ?? ''); ?>">
                    </label>
                    <label class="admin-form-grid__wide" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_featured" <?php echo isset($product['is_featured']) && $product['is_featured'] ? 'checked' : ''; ?>> <strong>Featured Product</strong>
                    </label>
                    <label class="admin-form-grid__wide" style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" name="is_trending" <?php echo isset($product['is_trending']) && $product['is_trending'] ? 'checked' : ''; ?>> <strong>Trending Product</strong>
                    </label>
                    
                    <div class="admin-form-grid__wide" style="border-top: 1px solid #ddd; padding-top: 16px; margin-top: 16px;">
                        <h3>Current Image</h3>
                        <?php if (!empty($product['image'])): ?>
                            <div style="margin-bottom: 16px;">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 200px; max-height: 200px; border-radius: 8px;">
                            </div>
                        <?php else: ?>
                            <p style="color: #666; margin-bottom: 16px;">No image set</p>
                        <?php endif; ?>
                    </div>
                    
                    <label class="admin-form-grid__wide">Update Image - Upload from Device
                        <input name="image_file" type="file" accept="image/jpeg,image/png,image/gif,image/webp">
                    </label>
                    <div class="admin-form-grid__wide" style="text-align: center; padding: 8px 0; color: #666;">
                        <strong>OR</strong>
                    </div>
                    <label class="admin-form-grid__wide">Update Image - External URL
                        <input name="image_url" type="url" placeholder="https://example.com/image.jpg" value="">
                    </label>
                    <input type="hidden" name="image" value="<?php echo htmlspecialchars($product['image'] ?? ''); ?>">
                    <p class="admin-form-grid__wide" style="color: #666; font-size: 0.9rem; margin: -8px 0 16px;">
                        <strong>Note:</strong> Leave empty to keep the current image. Provide a new file or URL to update it.
                    </p>
                    <div style="grid-column: 1 / -1;">
                        <button class="button button--primary" type="submit">Update Product</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <p class="empty-state">Product not found.</p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
