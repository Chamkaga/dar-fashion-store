<?php

require_once __DIR__ . "/../models/Order.php";
require_once __DIR__ . "/../models/OrderItem.php";
require_once __DIR__ . "/../models/Product.php";
require_once __DIR__ . "/../models/Payment.php";
require_once __DIR__ . "/../models/OrderTracking.php";

class OrderController {

    private $order;
    private $orderItem;
    private $product;
    private $payment;
    private $tracking;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->order = new Order($db);
        $this->orderItem = new OrderItem($db);
        $this->product = new Product($db);
        $this->payment = new Payment($db);
        $this->tracking = new OrderTracking($db);
    }

    public function createOrder($user_id, $cart, $payment_method, $customer = []) {

        $total = 0;

        // 1. Calculate total
        foreach ($cart as $product_id => $qty) {
            $product = $this->product->getById($product_id);
            if (!$product) {
                continue;
            }
            $price = $product['sale_price'] ?: $product['price'];
            $total += $price * $qty;
        }

        if ($total <= 0) {
            throw new InvalidArgumentException('Cart is empty or contains invalid products.');
        }

        // 2. Create order
        $deliveryFee = $customer['delivery_fee'] ?? 5000;
        $customer['subtotal'] = $total;
        $customer['delivery_fee'] = $deliveryFee;
        $order_id = $this->order->create($user_id, $total + $deliveryFee, $customer);

        // 3. Create order items + update stock
        foreach ($cart as $product_id => $qty) {
            $product = $this->product->getById($product_id);
            if (!$product) {
                continue;
            }
            $price = $product['sale_price'] ?: $product['price'];

            $this->orderItem->addItem($order_id, $product_id, $qty, $price, $product['name']);
            $this->product->updateStock($product_id, $qty);
        }

        // 4. Create payment record
        $this->payment->create($order_id, $payment_method, $total + $deliveryFee);
        $this->createConfirmation($order_id, $customer);

        $customer['created_at'] = date('Y-m-d H:i:s');
        $this->tracking->seedInitialEvents($order_id, $customer);
        $this->tracking->logEvent($order_id, 'confirmed', ['created_by' => 'system']);

        return $order_id;
    }

    private function createConfirmation($order_id, $customer) {
        $stmt = $this->payment->getConnection()->prepare("
            INSERT INTO order_confirmations (order_id, channel, recipient, subject, message, status, sent_at)
            VALUES (?, 'email', ?, 'Dar Fashion Store Order Confirmation', ?, 'sent', NOW())
        ");
        $recipient = $customer['customer_email'] ?? 'customer@example.com';
        $message = 'Thank you for shopping with Dar Fashion Store. Your order has been received and payment is recorded as a secure demo transaction.';
        $stmt->execute([$order_id, $recipient, $message]);
    }

    public function updateStatus($order_id, $status) {
        return $this->order->updateStatus($order_id, $status);
    }
}
?>
