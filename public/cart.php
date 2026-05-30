<?php
$pageTitle = 'Shopping Cart | Dar Fashion Store';
$basePath = '..';
require_once __DIR__ . '/../app/config/db.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/ProductVariant.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/models/ActivityLog.php';
include __DIR__ . '/../app/includes/header.php';
$database = new Database();
$conn = $database->connect();
$productModel = $conn ? new Product($conn) : null;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Quick product add from cards and listing buttons
if (isset($_GET['add']) && $productModel) {
    $productId = (int) $_GET['add'];
    if ($productId > 0) {
        $product = $productModel->getById($productId);
        if ($product) {
            $variantModel = new ProductVariant($conn);
            $variants = $variantModel->getByProduct($productId);

            if (!empty($variants)) {
                $selectedVariant = null;
                foreach ($variants as $variant) {
                    if ((int) $variant['stock_quantity'] > 0) {
                        $selectedVariant = $variant;
                        break;
                    }
                }

                if ($selectedVariant === null) {
                    $_SESSION['cart_error'] = 'This product has no variants in stock right now.';
                } else {
                    $key = 'v' . (int) $selectedVariant['id'];
                    if (!isset($_SESSION['cart'][$key])) {
                        $_SESSION['cart'][$key] = [
                            'product_id' => $productId,
                            'variant_id' => (int) $selectedVariant['id'],
                            'qty' => 1,
                            'unit_price' => $selectedVariant['price'] ?: $product['price'],
                            'color' => $selectedVariant['color'],
                            'size' => $selectedVariant['size']
                        ];
                    } else {
                        $_SESSION['cart'][$key]['qty'] += 1;
                    }
                    $_SESSION['cart_message'] = 'Product added to cart.';

                    if (is_logged_in()) {
                        $user = current_user();
                        $activityLog = new ActivityLog($conn);
                        $activityLog->log($user['id'], 'add_to_cart', [
                            'product_id' => $productId,
                            'details' => ['quantity' => 1, 'variant_id' => (int) $selectedVariant['id']]
                        ]);
                    }
                }
            } else {
                $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
                $_SESSION['cart_message'] = 'Product added to cart.';

                if (is_logged_in()) {
                    $user = current_user();
                    $activityLog = new ActivityLog($conn);
                    $activityLog->log($user['id'], 'add_to_cart', [
                        'product_id' => $productId,
                        'details' => ['quantity' => 1]
                    ]);
                }
            }
        } else {
            $_SESSION['cart_error'] = 'Could not add the selected product. Please try again.';
        }
    }

    header('Location: cart.php');
    exit;
}

// Handle variant-based POST add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $productModel) {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $variantId = (int) ($_POST['variant_id'] ?? 0);
    $qty = max(1, (int) ($_POST['qty'] ?? 1));

    if ($variantId > 0) {
        $pvModel = new ProductVariant($conn);
        $variant = $pvModel->getById($variantId);
        if ($variant) {
            // Check stock
            if ($qty > (int)$variant['stock_quantity']) {
                $_SESSION['cart_error'] = 'Requested quantity exceeds available stock for selected variant.';
            } else {
                $key = 'v' . $variantId;
                if (!isset($_SESSION['cart'][$key])) {
                    $_SESSION['cart'][$key] = [
                        'product_id' => $productId,
                        'variant_id' => $variantId,
                        'qty' => $qty,
                        'unit_price' => $variant['price'] ?: $productModel->getById($productId)['price'],
                        'color' => $variant['color'],
                        'size' => $variant['size']
                    ];
                } else {
                    $_SESSION['cart'][$key]['qty'] += $qty;
                }
                $_SESSION['cart_message'] = 'Product added to cart.';

                // Log add to cart activity
                if (is_logged_in()) {
                    $user = current_user();
                    $activityLog = new ActivityLog($conn);
                    $activityLog->log($user['id'], 'add_to_cart', [
                        'product_id' => $productId,
                        'details' => ['quantity' => $qty, 'variant_id' => $variantId]
                    ]);
                }
            }
        } else {
            $_SESSION['cart_error'] = 'Selected product variant was not found.';
        }
    } else {
        // Legacy product add via POST
        $product = $productModel->getById($productId);
        if ($product) {
            $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $qty;
            $_SESSION['cart_message'] = 'Product added to cart.';

            if (is_logged_in()) {
                $user = current_user();
                $activityLog = new ActivityLog($conn);
                $activityLog->log($user['id'], 'add_to_cart', [
                    'product_id' => $productId,
                    'details' => ['quantity' => $qty]
                ]);
            }
        }
    }

    header('Location: cart.php');
    exit;
}

if (isset($_GET['remove'])) {
    $removeKey = $_GET['remove'];
    // If key starts with v it's a variant key like v123
    if (is_string($removeKey) && strlen($removeKey) > 1 && $removeKey[0] === 'v') {
        $key = $removeKey;
        if (isset($_SESSION['cart'][$key])) {
            // Log remove if possible
            if (is_logged_in() && $productModel) {
                $prodId = $_SESSION['cart'][$key]['product_id'] ?? null;
                if ($prodId) {
                    $product = $productModel->getById($prodId);
                    if ($product) {
                        $user = current_user();
                        $activityLog = new ActivityLog($conn);
                        $activityLog->log($user['id'], 'remove_from_cart', [
                            'product_id' => $prodId,
                            'details' => ['product_name' => $product['name']]
                        ]);
                    }
                }
            }
            unset($_SESSION['cart'][$key]);
        }
    } else {
        $productId = (int) $removeKey;
        if (isset($_SESSION['cart'][$productId])) {
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
            unset($_SESSION['cart'][$productId]);
        }
    }
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
    foreach ($_SESSION['cart'] as $key => $item) {
        if (is_array($item) && isset($item['variant_id'])) {
            $product = $productModel->getById((int) $item['product_id']);
            $price = (float) ($item['unit_price'] ?: $product['sale_price'] ?: $product['price']);
            $lineTotal = $price * (int) $item['qty'];
            $subtotal += $lineTotal;
            $cartItems[] = [
                'product' => $product,
                'qty' => (int) $item['qty'],
                'price' => $price,
                'line_total' => $lineTotal,
                'variant' => $item
            ];
        } else {
            $productId = (int) $key;
            $product = $productModel->getById($productId);
            if (!$product) continue;
            $qty = (int) $item;
            $price = (float) ($product['sale_price'] ?: $product['price']);
            $lineTotal = $price * $qty;
            $subtotal += $lineTotal;
            $cartItems[] = [
                'product' => $product,
                'qty' => $qty,
                'price' => $price,
                'line_total' => $lineTotal
            ];
        }
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
            <?php if (!empty($_SESSION['cart_error'])): ?>
                <p class="alert alert--error"><?php echo htmlspecialchars($_SESSION['cart_error']); ?></p>
                <?php unset($_SESSION['cart_error']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['cart_message'])): ?>
                <p class="alert alert--success"><?php echo htmlspecialchars($_SESSION['cart_message']); ?></p>
                <?php unset($_SESSION['cart_message']); ?>
            <?php endif; ?>
            <div class="cart-table">
                <div class="cart-row cart-row--head"><span>Product</span><span>Qty</span><span>Price</span><span>Total</span><span>Remove</span></div>
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-row">
                        <span>
                            <?php echo htmlspecialchars($item['product']['name']); ?>
                            <?php if (!empty($item['variant'])): ?>
                                <div style="font-size:0.9rem;color:#666;">Variant: <?php echo htmlspecialchars($item['variant']['color'] ?? ''); ?> <?php echo htmlspecialchars($item['variant']['size'] ?? ''); ?>
                                (SKU: <?php echo htmlspecialchars($item['variant']['variant_id'] ?? $item['variant']['variant_id'] ?? ''); ?>)
                                </div>
                            <?php endif; ?>
                        </span>
                        <span><?php echo (int) $item['qty']; ?></span>
                        <span>TZS <?php echo number_format($item['price'], 0); ?></span>
                        <span>TZS <?php echo number_format($item['line_total'], 0); ?></span>
                        <?php if (!empty($item['variant'])): ?>
                            <a href="cart.php?remove=<?php echo 'v'.(int)$item['variant']['variant_id']; ?>">Remove</a>
                        <?php else: ?>
                            <a href="cart.php?remove=<?php echo (int) $item['product']['id']; ?>">Remove</a>
                        <?php endif; ?>
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
