<?php

require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductVariant.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/InventoryReservation.php';
require_once __DIR__ . '/../models/JobQueue.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../services/PaymentService.php';
require_once __DIR__ . '/../helpers/Logger.php';

class OrderController {
    private $db;
    private $order;
    private $orderItem;
    private $product;
    private $productVariant;
    private $reservation;
    private $queue;
    private $audit;
    private $paymentService;

    public function __construct($db) {
        $this->db = $db;
        $this->order = new Order($db);
        $this->orderItem = new OrderItem($db);
        $this->product = new Product($db);
        $this->productVariant = new ProductVariant($db);
        $this->reservation = new InventoryReservation($db);
        $this->queue = new JobQueue($db);
        $this->audit = new AuditLog($db);
        $this->paymentService = new PaymentService($db);
    }

    public function createOrder($user_id, $cart, $payment_method, $customer = [], $idempotency_key = null) {
        if (empty($cart) || !is_array($cart)) {
            throw new Exception('Cart is empty or invalid.');
        }

        if ($idempotency_key) {
            $existing = $this->getOrderByIdempotency($idempotency_key);
            if ($existing) {
                return $existing['id'];
            }
        }

        $items = $this->normalizeCart($cart);
        if (empty($items)) {
            throw new Exception('No valid cart items found.');
        }

        $customer['delivery_fee'] = $customer['delivery_fee'] ?? 0;
        $subtotal = $this->calculateSubtotal($items);
        $total = $subtotal + $customer['delivery_fee'];
        $orderNumber = $this->generateOrderNumber();
        $customer['order_number'] = $orderNumber;
        $customer['subtotal'] = $subtotal;

        try {
            $this->db->beginTransaction();

            $orderId = $this->order->create($user_id, $total, array_merge($customer, ['idempotency_key' => $idempotency_key]));

            foreach ($items as $item) {
                $this->insertOrderItem($orderId, $item);
                $this->reserveItemStock($orderId, $item, $user_id);
            }

            $paymentMeta = $this->paymentService->createPaymentRequest($orderId, $payment_method, $total, $orderNumber);

            $this->audit->log($user_id, 'order_created', 'order', $orderId, [
                'order_number' => $orderNumber,
                'payment_method' => $payment_method,
                'payment_reference' => $paymentMeta['payment_reference'],
                'idempotency_key' => $idempotency_key,
            ]);
            $this->enqueuePostOrderJobs($orderId, $customer, $paymentMeta, count($items), $total);
            $this->db->commit();
            return $orderId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            Logger::error('Order creation failed', ['error' => $e->getMessage(), 'cart' => $cart, 'user_id' => $user_id]);
            throw $e;
        }

    }

    public function cancelOrder($orderId, $reason = null) {
        try {
            $this->db->beginTransaction();
            $restored = $this->restoreStockForOrder($orderId);
            $this->order->updateStatus($orderId, 'cancelled');
            $this->audit->log(null, 'order_cancelled', 'order', $orderId, ['reason' => $reason, 'restored' => $restored]);
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            Logger::error('Order cancellation failed', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function updateStatus($order_id, $status) {
        return $this->order->updateStatus($order_id, $status);
    }

    private function getOrderByIdempotency($key) {
        if (!$key) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE idempotency_key = ? LIMIT 1');
        $stmt->execute([$key]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function calculateSubtotal(array $items) {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['unit_price'] * $item['qty'];
        }
        return $subtotal;
    }

    private function normalizeCart(array $cart) {
        $items = [];
        foreach ($cart as $key => $entry) {
            if (is_array($entry) && isset($entry['variant_id'])) {
                $items[] = [
                    'product_id' => (int)$entry['product_id'],
                    'variant_id' => (int)$entry['variant_id'],
                    'qty' => max(1, (int)$entry['qty']),
                    'unit_price' => (float)($entry['unit_price'] ?? 0),
                    'sku' => $entry['sku'] ?? null,
                    'color' => $entry['color'] ?? null,
                    'size' => $entry['size'] ?? null,
                ];
            } elseif (is_numeric($key)) {
                $items[] = [
                    'product_id' => (int)$key,
                    'variant_id' => null,
                    'qty' => max(1, (int)$entry),
                    'unit_price' => null,
                ];
            }
        }
        return $items;
    }

    private function insertOrderItem($orderId, array $item) {
        $product = $this->product->getById($item['product_id']);
        if (!$product) {
            throw new Exception('Product not found during order creation');
        }
        $price = $item['unit_price'] ?: ($product['sale_price'] ?: $product['price']);
        $variant = null;
        if ($item['variant_id']) {
            $variant = $this->productVariant->getById($item['variant_id']);
            if (!$variant) {
                throw new Exception('Variant not found during order creation');
            }
        }
        return $this->orderItem->addItem(
            $orderId,
            $item['product_id'],
            $item['qty'],
            $price,
            $product['name'],
            $item['variant_id'],
            $item['sku'] ?? ($variant['sku'] ?? null),
            $item['color'] ?? ($variant['color'] ?? null),
            $item['size'] ?? ($variant['size'] ?? null)
        );
    }

    private function reserveItemStock($orderId, array $item, $user_id) {
        $isMysql = strtolower($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) ?? '') === 'mysql';
        if (!empty($item['variant_id'])) {
            $sql = 'SELECT stock_quantity FROM product_variants WHERE id = ?' . ($isMysql ? ' FOR UPDATE' : '');
            $this->db->prepare($sql)->execute([$item['variant_id']]);
            $variant = $this->productVariant->getById($item['variant_id']);
            if (!$variant || $variant['stock_quantity'] < $item['qty']) {
                throw new Exception('Insufficient stock for selected variant.');
            }
            $this->productVariant->updateStock($item['variant_id'], $item['qty']);
            $expiresAt = date('Y-m-d H:i:s', time() + 900);
            $this->reservation->create($orderId, $item['variant_id'], $item['qty'], $expiresAt, $user_id);
        } else {
            $sql = 'SELECT stock_quantity FROM products WHERE id = ?' . ($isMysql ? ' FOR UPDATE' : '');
            $this->db->prepare($sql)->execute([$item['product_id']]);
            $product = $this->product->getById($item['product_id']);
            if (!$product || $product['stock_quantity'] < $item['qty']) {
                throw new Exception('Insufficient stock for selected product.');
            }
            $this->product->updateStock($item['product_id'], $item['qty']);
        }
    }

    private function enqueuePostOrderJobs($orderId, array $customer, array $paymentMeta, int $itemsCount, float $total) {
        $this->queue->enqueue('send_order_confirmation', [
            'order_id' => $orderId,
            'recipient' => $customer['customer_email'],
            'customer_name' => $customer['customer_name'],
            'payment_reference' => $paymentMeta['payment_reference'],
        ]);
        $this->queue->enqueue('analytics_event', [
            'event' => 'order_placed',
            'order_id' => $orderId,
            'amount' => $total,
            'items_count' => $itemsCount,
            'user_id' => $customer['user_id'] ?? null,
        ]);
        $this->queue->enqueue('notification', [
            'type' => 'order_created',
            'order_id' => $orderId,
            'user_id' => $customer['user_id'] ?? null,
            'email' => $customer['customer_email'],
        ]);
    }

    private function restoreStockForOrder($orderId) {
        if (!$orderId) return 0;
        try {
            // Use reservation model to restore any active reservations for this order
            if (method_exists($this->reservation, 'restoreForOrder')) {
                return $this->reservation->restoreForOrder($orderId);
            }
            return 0;
        } catch (Throwable $e) {
            Logger::error('Failed to restore stock for order', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    private function generateOrderNumber() {
        return 'DFS-' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
    }
}
?>
