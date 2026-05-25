<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$products = $conn ? $conn->query("SELECT products.*, categories.name AS category_name FROM products LEFT JOIN categories ON categories.id = products.category_id ORDER BY stock_quantity ASC, products.name ASC")->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Inventory</title><link rel="stylesheet" href="../../assets/css/style.css"><link rel="stylesheet" href="../../assets/css/responsive.css"></head>
<body><main class="section"><div class="container"><div class="section-heading"><p class="section-kicker">Admin</p><h1>Inventory / Stock Management</h1></div><div class="cart-table"><div class="cart-row cart-row--head"><span>Product</span><span>Category</span><span>SKU</span><span>Stock</span><span>Alert</span></div><?php foreach ($products as $product): ?><div class="cart-row"><span><?php echo htmlspecialchars($product['name']); ?></span><span><?php echo htmlspecialchars($product['category_name'] ?? 'None'); ?></span><span><?php echo htmlspecialchars($product['sku']); ?></span><span><?php echo (int) $product['stock_quantity']; ?></span><span><?php echo ((int) $product['stock_quantity'] <= 5) ? 'Low stock' : 'OK'; ?></span></div><?php endforeach; ?></div><p><a href="../dashboard.php">Back to dashboard</a></p></div></main></body></html>
