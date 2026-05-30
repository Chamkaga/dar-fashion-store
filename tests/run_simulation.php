<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Test harness: run simulated checkout flows in-memory using SQLite
require_once __DIR__ . '/../app/helpers/Logger.php';
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/ProductVariant.php';
require_once __DIR__ . '/../app/models/Order.php';
require_once __DIR__ . '/../app/models/OrderItem.php';
require_once __DIR__ . '/../app/models/Payment.php';
require_once __DIR__ . '/../app/models/InventoryReservation.php';
require_once __DIR__ . '/../app/models/JobQueue.php';
require_once __DIR__ . '/../app/models/AuditLog.php';
require_once __DIR__ . '/../app/services/PaymentService.php';
require_once __DIR__ . '/../app/controllers/OrderController.php';
require_once __DIR__ . '/../app/models/Cart.php';

// Minimal OrderItem model stub if missing
if (!class_exists('OrderItem')) {
    class OrderItem {
        private $conn;
        public function __construct($db) { $this->conn = $db; }
        public function addItem($orderId, $product_id, $qty, $price, $name = null, $variant_id = null, $sku = null, $color = null, $size = null) {
            $stmt = $this->conn->prepare("INSERT INTO order_items (order_id, product_id, variant_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$orderId, $product_id, $variant_id, $qty, $price, $price * $qty]);
        }
    }
}

// Setup in-memory SQLite
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function execSql($pdo, $sql) {
    foreach (explode(";", $sql) as $stmt) {
        $s = trim($stmt);
        if ($s) $pdo->exec($s);
    }
}

// Create minimal tables
$schema = <<<SQL
CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, fullname TEXT, email TEXT);
CREATE TABLE products (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, price REAL, sale_price REAL, stock_quantity INTEGER DEFAULT 0);
CREATE TABLE product_variants (id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER, sku TEXT, color TEXT, size TEXT, price REAL, stock_quantity INTEGER DEFAULT 0);
CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT, order_number TEXT, idempotency_key TEXT, user_id INTEGER, customer_name TEXT, customer_email TEXT, customer_phone TEXT, shipping_address TEXT, subtotal REAL, delivery_fee REAL, total REAL, status TEXT DEFAULT 'pending');
CREATE TABLE order_items (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, product_id INTEGER, product_name TEXT, quantity INTEGER, price REAL, line_total REAL, variant_id INTEGER, unit_price REAL, sku TEXT, selected_color TEXT, selected_size TEXT);
CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT, order_id INTEGER, method TEXT, amount REAL, payment_status TEXT, transaction_ref TEXT, payment_reference TEXT, payment_provider TEXT, provider_response TEXT, created_at DATETIME, paid_at DATETIME);
CREATE TABLE inventory_reservations (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, order_id INTEGER, variant_id INTEGER, quantity INTEGER, status TEXT DEFAULT 'active', expires_at DATETIME);
CREATE TABLE audit_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, action TEXT, target_type TEXT, target_id INTEGER, details TEXT, ip_address TEXT, user_agent TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE jobs (id INTEGER PRIMARY KEY AUTOINCREMENT, job_type TEXT, payload TEXT, status TEXT DEFAULT 'pending', attempts INTEGER DEFAULT 0, available_at DATETIME DEFAULT CURRENT_TIMESTAMP, last_error TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE user_cart_items (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, product_id INTEGER, variant_id INTEGER, quantity INTEGER, unit_price REAL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP);
SQL;
execSql($pdo, $schema);

// Seed data
$pdo->exec("INSERT INTO users (fullname, email) VALUES ('Amina Test', 'amina@test.local')");
$pdo->exec("INSERT INTO products (name, price, sale_price, stock_quantity) VALUES ('Test Shirt', 10000, 9000, 1000)");
$pdo->exec("INSERT INTO product_variants (product_id, sku, color, size, price, stock_quantity) VALUES (1, 'SKU-TS-01', 'red', 'M', 9000, 200)");

// Test helper subclass to avoid FOR UPDATE (not supported in SQLite)
class TestOrderController extends OrderController {
    protected function reserveItemStock($orderId, array $item, $user_id) {
        if (!empty($item['variant_id'])) {
            $stmt = $this->db->prepare('SELECT stock_quantity FROM product_variants WHERE id = ?');
            $stmt->execute([$item['variant_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['stock_quantity'] < $item['qty']) {
                throw new Exception('Insufficient stock for selected variant.');
            }
            $upd = $this->db->prepare('UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE id = ?');
            $upd->execute([$item['qty'], $item['variant_id']]);
            $expiresAt = date('Y-m-d H:i:s', time() + 2); // short expiration for tests
            $this->reservation->create($orderId, $item['variant_id'], $item['qty'], $expiresAt, $user_id);
        } else {
            $stmt = $this->db->prepare('SELECT stock_quantity FROM products WHERE id = ?');
            $stmt->execute([$item['product_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || $row['stock_quantity'] < $item['qty']) {
                throw new Exception('Insufficient stock for selected product.');
            }
            $upd = $this->db->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?');
            $upd->execute([$item['qty'], $item['product_id']]);
        }
    }
}

// Instantiate service objects but inject our $pdo
$product = new Product($pdo);
$variantModel = new ProductVariant($pdo);
$orderModel = new Order($pdo);
$orderItem = new OrderItem($pdo);
$paymentModel = new Payment($pdo);
$reservation = null; // will use TestInventoryReservation below
$queue = new JobQueue($pdo);
$audit = new AuditLog($pdo);
$paymentService = new PaymentService($pdo);

// Replace underlying Payment implementation with TestPayment to avoid NOW() (SQLite)
class TestPayment {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }
    public function create($order_id, $method, $amount, $payment_reference, $payment_provider, $transaction_ref) {
        $stmt = $this->pdo->prepare("INSERT INTO payments (order_id, method, amount, payment_status, transaction_ref, payment_reference, payment_provider, created_at) VALUES (?, ?, ?, 'pending', ?, ?, ?, datetime('now'))");
        return $stmt->execute([$order_id, $method, $amount, $transaction_ref, $payment_reference, $payment_provider]);
    }
    public function verifyPayment($order_id, $transaction_ref, $provider_response = null) {
        $stmt = $this->pdo->prepare("UPDATE payments SET payment_status = 'verified', transaction_ref = ?, provider_response = ?, paid_at = datetime('now') WHERE order_id = ?");
        return $stmt->execute([$transaction_ref, json_encode($provider_response), $order_id]);
    }
    public function markFailed($order_id, $reason = null) {
        $stmt = $this->pdo->prepare("UPDATE payments SET payment_status = 'failed', provider_response = ?, paid_at = NULL WHERE order_id = ?");
        return $stmt->execute([json_encode(['error' => $reason]), $order_id]);
    }
    public function refund($order_id, $amount, $reason = null) {
        $stmt = $this->pdo->prepare("UPDATE payments SET payment_status = 'refunded', provider_response = ?, paid_at = datetime('now') WHERE order_id = ?");
        return $stmt->execute([json_encode(['refund_amount' => $amount, 'reason' => $reason]), $order_id]);
    }
    public function getByOrder($order_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE order_id = ?");
        $stmt->execute([$order_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
$rp = new ReflectionClass($paymentService);
$prop = $rp->getProperty('payment');
$prop->setAccessible(true);
$prop->setValue($paymentService, new TestPayment($pdo));

$reservation = new class($pdo) {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }
    public function create($order_id, $variant_id, $quantity, $expires_at, $user_id = null) {
        $stmt = $this->pdo->prepare("INSERT INTO inventory_reservations (user_id, order_id, variant_id, quantity, status, expires_at) VALUES (?, ?, ?, ?, 'active', ?)");
        $stmt->execute([$user_id, $order_id, $variant_id, $quantity, $expires_at]);
        return $this->pdo->lastInsertId();
    }
    public function getExpiredActive() {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_reservations WHERE status = 'active' AND expires_at <= datetime('now') ORDER BY expires_at ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function releaseExpired() {
        $expired = $this->getExpiredActive();
        if (!$expired) return 0;
        $restored = 0;
        foreach ($expired as $reservation) {
            try {
                $this->pdo->beginTransaction();
                $variantId = $reservation['variant_id'];
                $quantity = (int)$reservation['quantity'];
                if ($variantId) {
                    $updateStock = $this->pdo->prepare("UPDATE product_variants SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    $updateStock->execute([$quantity, $variantId]);
                }
                $stmt = $this->pdo->prepare("UPDATE inventory_reservations SET status = 'released' WHERE id = ?");
                $stmt->execute([$reservation['id']]);
                $this->pdo->commit();
                $restored++;
            } catch (Throwable $e) {
                $this->pdo->rollBack();
                Logger::error('Failed to release expired reservation', ['reservation_id' => $reservation['id'], 'error' => $e->getMessage()]);
            }
        }
        return $restored;
    }
    public function restoreForOrder($order_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_reservations WHERE order_id = ? AND status = 'active'");
        $stmt->execute([$order_id]);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $restored = 0;
        foreach ($reservations as $reservation) {
            try {
                $this->pdo->beginTransaction();
                if ($reservation['variant_id']) {
                    $updateStock = $this->pdo->prepare("UPDATE product_variants SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    $updateStock->execute([(int)$reservation['quantity'], $reservation['variant_id']]);
                }
                $stmt = $this->pdo->prepare("UPDATE inventory_reservations SET status = 'released' WHERE id = ?");
                $stmt->execute([$reservation['id']]);
                $this->pdo->commit();
                $restored++;
            } catch (Throwable $e) {
                $this->pdo->rollBack();
                Logger::error('Failed to restore reservation stock', ['reservation_id' => $reservation['id'], 'error' => $e->getMessage()]);
            }
        }
        return $restored;
    }
};
$controller = new TestOrderController($pdo);

// Inject TestPayment into the controller's internal PaymentService instance
$parentClass = get_parent_class($controller);
if ($parentClass) {
    $pRef = new ReflectionClass($parentClass);
    if ($pRef->hasProperty('paymentService')) {
        $pProp = $pRef->getProperty('paymentService');
        $pProp->setAccessible(true);
        $internalPaymentService = $pProp->getValue($controller);
        if ($internalPaymentService) {
            $rp = new ReflectionClass($internalPaymentService);
            $prop = $rp->getProperty('payment');
            $prop->setAccessible(true);
            $prop->setValue($internalPaymentService, new TestPayment($pdo));
            $pProp->setValue($controller, $internalPaymentService);
        }
    }
}

// Inject our test reservation instance into controller's private property
$pRef = new ReflectionClass(get_parent_class($controller));
if ($pRef->hasProperty('reservation')) {
    $resProp = $pRef->getProperty('reservation');
    $resProp->setAccessible(true);
    $resProp->setValue($controller, $reservation);
}

function printStatus($label, $ok) { echo ($ok ? "[PASS] " : "[FAIL] ") . $label . PHP_EOL; }

// Scenario 1: Normal Checkout
try {
    $cart = [['product_id'=>1,'variant_id'=>1,'qty'=>1,'unit_price'=>9000]];
    $orderId = $controller->createOrder(1, $cart, 'mobile_money', ['customer_name'=>'Amina','customer_email'=>'amina@test.local'], bin2hex(random_bytes(8)));
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?'); $stmt->execute([$orderId]); $order = $stmt->fetch();
    printStatus('Normal checkout created order', (bool)$order);
} catch (Throwable $e) {
    printStatus('Normal checkout', false);
    echo $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}

// Scenario 2: Large Quantity Checkout (100 and 500)
try {
    // reset variant stock for test
    $pdo->exec("UPDATE product_variants SET stock_quantity = 1000 WHERE id = 1");
    $cart100 = [['product_id'=>1,'variant_id'=>1,'qty'=>100,'unit_price'=>9000]];
    $orderId100 = $controller->createOrder(1, $cart100, 'mobile_money', ['customer_name'=>'Amina','customer_email'=>'amina@test.local'], bin2hex(random_bytes(8)));
    $stockAfter100 = $pdo->query('SELECT stock_quantity FROM product_variants WHERE id = 1')->fetchColumn();
    printStatus('Large quantity 100 deducted', $stockAfter100 == 900);

    $cart500 = [['product_id'=>1,'variant_id'=>1,'qty'=>500,'unit_price'=>9000]];
    $orderId500 = $controller->createOrder(1, $cart500, 'mobile_money', ['customer_name'=>'Amina','customer_email'=>'amina@test.local'], bin2hex(random_bytes(8)));
    $stockAfter500 = $pdo->query('SELECT stock_quantity FROM product_variants WHERE id = 1')->fetchColumn();
    printStatus('Large quantity 500 deducted', $stockAfter500 == 400);
} catch (Throwable $e) {
    printStatus('Large quantity checkout', false);
    echo $e->getMessage() . PHP_EOL;
}

// Scenario 3: Concurrent Checkout simulation
try {
    // set stock to 10
    $pdo->exec("UPDATE product_variants SET stock_quantity = 10 WHERE id = 1");
    $cartA = [['product_id'=>1,'variant_id'=>1,'qty'=>8,'unit_price'=>9000]];
    $cartB = [['product_id'=>1,'variant_id'=>1,'qty'=>5,'unit_price'=>9000]];
    // Simulate two actors trying to reserve nearly simultaneously
    $okA = $okB = false;
    try { $orderA = $controller->createOrder(1, $cartA, 'mobile_money', ['customer_name'=>'A','customer_email'=>'a@test'], bin2hex(random_bytes(8))); $okA = true; } catch (Throwable $e) { $okA = false; }
    try { $orderB = $controller->createOrder(2, $cartB, 'mobile_money', ['customer_name'=>'B','customer_email'=>'b@test'], bin2hex(random_bytes(8))); $okB = true; } catch (Throwable $e) { $okB = false; }
    $finalStock = (int)$pdo->query('SELECT stock_quantity FROM product_variants WHERE id = 1')->fetchColumn();
    $noOversell = $finalStock >= 0 && $finalStock <= 10;
    printStatus('Concurrent checkout no oversell', $noOversell && !($okA && $okB && $finalStock < 0));
} catch (Throwable $e) {
    printStatus('Concurrent checkout', false);
    echo $e->getMessage() . PHP_EOL;
}

// Scenario 4: Reservation expiration
try {
    // create a reservation that already expired
    $pdo->exec("UPDATE product_variants SET stock_quantity = 50 WHERE id = 1");
    $pdo->exec("INSERT INTO inventory_reservations (user_id, order_id, variant_id, quantity, status, expires_at) VALUES (1, NULL, 1, 5, 'active', datetime('now','-1 minute'))");
    $restored = $reservation->releaseExpired();
    $stockAfter = (int)$pdo->query('SELECT stock_quantity FROM product_variants WHERE id = 1')->fetchColumn();
    printStatus('Reservation expiration restores stock', $restored > 0 && $stockAfter >= 55);
} catch (Throwable $e) {
    printStatus('Reservation expiration', false);
    echo $e->getMessage() . PHP_EOL;
}

// Scenario 5: Failed payment flow
try {
    // create order with reservation
    $pdo->exec("UPDATE product_variants SET stock_quantity = 20 WHERE id = 1");
    $cart = [['product_id'=>1,'variant_id'=>1,'qty'=>2,'unit_price'=>9000]];
    $idemp = bin2hex(random_bytes(8));
    $orderId = $controller->createOrder(1, $cart, 'mobile_money', ['customer_name'=>'Amina'], $idemp);
    // simulate payment fail
    $res = $paymentService->failPayment($orderId, 'simulated decline');
    $stockAfter = (int)$pdo->query('SELECT stock_quantity FROM product_variants WHERE id = 1')->fetchColumn();
    printStatus('Failed payment restores stock', $res && $stockAfter >= 20);
} catch (Throwable $e) {
    printStatus('Failed payment flow', false);
    echo $e->getMessage() . PHP_EOL;
}

// Scenario 6: Cancelled order flow
try {
    $pdo->exec("UPDATE product_variants SET stock_quantity = 30 WHERE id = 1");
    $cart = [['product_id'=>1,'variant_id'=>1,'qty'=>3,'unit_price'=>9000]];
    $orderId = $controller->createOrder(1, $cart, 'mobile_money', ['customer_name'=>'Amina'], bin2hex(random_bytes(8)));
    $cancel = $controller->cancelOrder($orderId, 'customer request');
    $stockAfter = (int)$pdo->query('SELECT stock_quantity FROM product_variants WHERE id = 1')->fetchColumn();
    $stmt = $pdo->prepare('SELECT * FROM inventory_reservations WHERE order_id = ?'); $stmt->execute([$orderId]); $resRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Reservations for order {$orderId}: " . json_encode($resRows) . PHP_EOL;
    printStatus('Cancelled order restores stock', $cancel && $stockAfter >= 30);
} catch (Throwable $e) {
    printStatus('Cancelled order flow', false);
    echo $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}

// Scenario 7: Duplicate request protection
try {
    $pdo->exec("UPDATE product_variants SET stock_quantity = 50 WHERE id = 1");
    $idemp = 'dup-test-key';
    $cart = [['product_id'=>1,'variant_id'=>1,'qty'=>1,'unit_price'=>9000]];
    $o1 = $controller->createOrder(1, $cart, 'mobile_money', ['customer_name'=>'Amina'], $idemp);
    $o2 = $controller->createOrder(1, $cart, 'mobile_money', ['customer_name'=>'Amina'], $idemp);
    printStatus('Duplicate idempotent request returns same order', $o1 == $o2);
} catch (Throwable $e) {
    printStatus('Duplicate protection', false);
    echo $e->getMessage() . PHP_EOL;
}

// Scenario 8: Cart persistence (basic merge simulation)
try {
    session_start();
    // start with session cart
    $_SESSION['cart'] = ['v1' => ['product_id'=>1,'variant_id'=>1,'qty'=>2,'unit_price'=>9000]];
    // insert DB cart for user
    $pdo->exec("INSERT INTO user_cart_items (user_id, product_id, variant_id, quantity, unit_price) VALUES (1,1,1,3,9000)");
    $cartModel = new Cart();
    $cartModel->loadDbToSession($pdo, 1);
    $merged = $_SESSION['cart']['v1']['qty'] ?? 0;
    printStatus('Cart persistence merges quantities', $merged == 5);
    session_destroy();
} catch (Throwable $e) {
    printStatus('Cart persistence', false);
    echo $e->getMessage() . PHP_EOL;
}

// Summary
echo PHP_EOL . "Simulation complete. Check logs for details." . PHP_EOL;

?>