<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$categories = $conn ? $conn->query("SELECT categories.*, COUNT(products.id) AS product_count FROM categories LEFT JOIN products ON products.category_id = categories.id GROUP BY categories.id ORDER BY categories.name")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Categories Management</title><link rel="stylesheet" href="../../assets/css/style.css"><link rel="stylesheet" href="../../assets/css/responsive.css"></head>
<body><main class="section"><div class="container"><div class="section-heading"><p class="section-kicker">Admin</p><h1>Categories Management</h1></div><div class="cart-table"><div class="cart-row cart-row--head"><span>Name</span><span>Slug</span><span>Products</span><span>Status</span><span>Created</span></div><?php foreach ($categories as $category): ?><div class="cart-row"><span><?php echo htmlspecialchars($category['name']); ?></span><span><?php echo htmlspecialchars($category['slug']); ?></span><span><?php echo (int) $category['product_count']; ?></span><span><?php echo ((int) $category['is_active'] === 1) ? 'Active' : 'Inactive'; ?></span><span><?php echo htmlspecialchars(date('M d, Y', strtotime($category['created_at']))); ?></span></div><?php endforeach; ?></div><p><a href="../dashboard.php">Back to dashboard</a></p></div></main></body></html>
