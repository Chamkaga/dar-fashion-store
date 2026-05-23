<?php

require_once __DIR__ . "/../models/Order.php";
require_once __DIR__ . "/../models/OrderItem.php";
require_once __DIR__ . "/../models/Product.php";
require_once __DIR__ . "/../models/Payment.php";

class OrderController {

    private $order;
    private $orderItem;
    private $product;
    private $payment;

    public function __construct($db) {
        $this->order = new Order($db);
        $this->orderItem = new OrderItem($db);
        $this->product = new Product($db);
        $this->payment = new Payment($db);
    }

    public function createOrder($user_id, $cart, $payment_method) {

        $total = 0;

        // 1. Calculate total
        foreach ($cart as $product_id => $qty) {
            $product = $this->product->getById($product_id);
            $total += $product['price'] * $qty;
        }

        // 2. Create order
        $order_id = $this->order->create($user_id, $total);

        // 3. Create order items + update stock
        foreach ($cart as $product_id => $qty) {
            $product = $this->product->getById($product_id);

            $this->orderItem->addItem(
                $order_id,
                $product_id,
                $qty,
                $product['price']
            );

            // reduce stock
            $this->product->updateStock($product_id, $qty);
        }

        // 4. Create payment record
        $this->payment->create($order_id, $payment_method, $total);

        return $order_id;
    }

    public function updateStatus($order_id, $status) {
        return $this->order->updateStatus($order_id, $status);
    }
}
?>