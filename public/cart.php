<?php
$pageTitle = 'Shopping Cart | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';
include __DIR__ . '/../app/includes/header.php';
$database = new Database();
$conn = $database->connect();
$productModel = $conn ? new Product($conn) : null;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_GET['add']) && $productModel) {
    $productId = (int) $_GET['add'];
    $qty = max(1, (int) ($_GET['qty'] ?? 1));
    $product = $productModel->getById($productId);
    if ($product) {
        $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
        
        // Log add to cart activity
        if (is_logged_in()) {
            $user = current_user();
            $activityLog = new ActivityLog($conn);
            $activityLog->log($user['id'], 'add_to_cart', [
                'product_id' => $productId,
                'details' => ['quantity' => $qty, 'product_name' => $product['name']]
            ]);
        }
    }
    header('Location: cart.php');
    exit;
}

if (isset($_GET['remove'])) {
    $productId = (int) $_GET['remove'];
    if (isset($_SESSION['cart'][$productId])) {
        // Log remove from cart activity
        if (is_logged_in() && $productModel) {
            $product = $productModel->getById($productId);
            if ($product) {
                $user = current_user();
                $activityLog = new ActivityLog($conn);
                $activityLog->log($user['id'], 'remove_from_cart', [
                    'product_id' => $productId,
                    'details' => ['product_name' => $product['name']]
                ]);
            }
        }
    }
    unset($_SESSION['cart'][$productId]);
    header('Location: cart.php');
    exit;
}

if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit;
}

$cartItems = [];
$subtotal = 0;

if ($productModel) {
    foreach ($_SESSION['cart'] as $productId => $qty) {
        $product = $productModel->getById((int) $productId);
        if (!$product) {
            continue;
        }
        $price = (float) ($product['sale_price'] ?: $product['price']);
        $lineTotal = $price * (int) $qty;
        $subtotal += $lineTotal;
        $cartItems[] = [
            'product' => $product,
            'qty' => (int) $qty,
            'price' => $price,
            'line_total' => $lineTotal
        ];
    }
}

$deliveryFee = $subtotal > 0 ? 5000 : 0;
$total = $subtotal + $deliveryFee;
?>
<main class="section">
    <div class="container cart-layout">
        <section>
            <div class="section-heading">
                <p class="section-kicker">Shopping cart</p>
                <h1>Your selected items</h1>
            </div>
            <div class="cart-table">
                <div class="cart-row cart-row--head"><span>Product</span><span>Qty</span><span>Price</span><span>Total</span><span>Remove</span></div>
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-row">
                        <span><?php echo htmlspecialchars($item['product']['name']); ?></span>
                        <span><?php echo (int) $item['qty']; ?></span>
                        <span>TZS <?php echo number_format($item['price'], 0); ?></span>
                        <span>TZS <?php echo number_format($item['line_total'], 0); ?></span>
                        <a href="cart.php?remove=<?php echo (int) $item['product']['id']; ?>">Remove</a>
                    </div>
                <?php endforeach; ?>
                <?php if (!$cartItems): ?>
                    <p class="empty-state">Your cart is empty. Add products from the shop page.</p>
                <?php endif; ?>
            </div>
        </section>
        <aside class="summary-panel">
            <h2>Order Summary</h2>
            <p><span>Subtotal</span><strong>TZS <?php echo number_format($subtotal, 0); ?></strong></p>
            <p><span>Delivery</span><strong>TZS <?php echo number_format($deliveryFee, 0); ?></strong></p>
            <p class="summary-total"><span>Total</span><strong>TZS <?php echo number_format($total, 0); ?></strong></p>
            <a class="button button--primary" href="checkout.php">Checkout</a>
            <?php if ($cartItems): ?><a class="button button--dark" href="cart.php?clear=1">Clear Cart</a><?php endif; ?>
        </aside>
    </div>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
