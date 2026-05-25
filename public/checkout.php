<?php
$pageTitle = 'Checkout | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/controllers/OrderController.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';
include __DIR__ . '/../app/includes/header.php';
$database = new Database();
$conn = $database->connect();
$productModel = $conn ? new Product($conn) : null;
$error = '';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_GET['buy']) && $productModel) {
    $productId = (int) $_GET['buy'];
    if ($productModel->getById($productId)) {
        $_SESSION['cart'][$productId] = max(1, $_SESSION['cart'][$productId] ?? 1);
    }
}

$cartItems = [];
$subtotal = 0;

if ($productModel) {
    foreach ($_SESSION['cart'] as $productId => $qty) {
        $product = $productModel->getById((int) $productId);
        if ($product) {
            $price = (float) ($product['sale_price'] ?: $product['price']);
            $subtotal += $price * (int) $qty;
            $cartItems[] = ['product' => $product, 'qty' => (int) $qty, 'price' => $price];
        }
    }
}

$deliveryFee = $subtotal > 0 ? 5000 : 0;
$total = $subtotal + $deliveryFee;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security token expired. Please try again.';
    } elseif (!$conn) {
        $error = 'Database connection failed.';
    } elseif (!$cartItems) {
        $error = 'Your cart is empty.';
    } else {
        $customer = [
            'customer_name' => trim($_POST['name'] ?? ''),
            'customer_email' => trim($_POST['email'] ?? ''),
            'customer_phone' => trim($_POST['phone'] ?? ''),
            'shipping_address' => trim($_POST['address'] ?? ''),
            'delivery_fee' => $deliveryFee
        ];

        if ($customer['customer_name'] === '' || $customer['customer_email'] === '' || $customer['customer_phone'] === '' || $customer['shipping_address'] === '') {
            $error = 'Please complete all checkout fields.';
        } else {
            try {
                $controller = new OrderController($conn);
                $user = current_user();
                $orderId = $controller->createOrder($user['id'] ?? null, $_SESSION['cart'], $_POST['payment'] ?? 'mobile_money', $customer);

                if (is_logged_in()) {
                    $activityLog = new ActivityLog($conn);
                    $activityLog->log($user['id'], 'checkout', [
                        'details' => ['items_count' => count($_SESSION['cart']), 'payment_method' => $_POST['payment']]
                    ]);
                    $activityLog->log($user['id'], 'order_placed', [
                        'order_id' => $orderId,
                        'details' => ['items_count' => count($_SESSION['cart']), 'total' => $total]
                    ]);
                    $activityLog->log($user['id'], 'payment_attempted', [
                        'order_id' => $orderId,
                        'details' => ['amount' => $total, 'method' => $_POST['payment']]
                    ]);
                }

                $_SESSION['cart'] = [];
                $_SESSION['last_order_id'] = $orderId;
                header('Location: order-success.php?order_id=' . urlencode($orderId));
                exit;
            } catch (Throwable $e) {
                error_log('Checkout failed: ' . $e->getMessage());
                $error = 'Unable to place your order. Please try again.';
            }
        }
    }
}
?>
<main class="section">
    <div class="container checkout-layout">
        <form class="checkout-form" action="checkout.php" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <div class="section-heading">
                <p class="section-kicker">Checkout</p>
                <h1>Billing and shipping</h1>
            </div>
            <?php if ($error): ?><p class="alert alert--error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
            <label>Full Name<input type="text" name="name" value="<?php echo htmlspecialchars(current_user()['fullname'] ?? ''); ?>" required></label>
            <label>Email<input type="email" name="email" value="<?php echo htmlspecialchars(current_user()['email'] ?? ''); ?>" required></label>
            <label>Phone<input type="tel" name="phone" required></label>
            <label>Shipping Address<textarea name="address" rows="4" required></textarea></label>
            <label>Payment Method
                <select name="payment">
                    <option value="mobile_money">Mobile Money</option>
                    <option value="card_demo">Card Payment Demo</option>
                    <option value="cash_on_delivery">Cash on Delivery</option>
                </select>
            </label>
            <button class="button button--primary" type="submit">Place Order</button>
        </form>
        <aside class="summary-panel">
            <h2>Order Summary</h2>
            <p><span>Products</span><strong><?php echo count($cartItems); ?> items</strong></p>
            <p><span>Subtotal</span><strong>TZS <?php echo number_format($subtotal, 0); ?></strong></p>
            <p><span>Delivery</span><strong>TZS <?php echo number_format($deliveryFee, 0); ?></strong></p>
            <p class="summary-total"><span>Total</span><strong>TZS <?php echo number_format($total, 0); ?></strong></p>
        </aside>
    </div>
</main>
<?php include __DIR__ . '/../app/includes/footer.php'; ?>
