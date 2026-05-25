<?php

class Order {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $total, $customer = []) {
        $orderNumber = $customer['order_number'] ?? ('DFS-' . date('YmdHis'));
        $customerName = $customer['customer_name'] ?? 'Registered Customer';
        $customerEmail = $customer['customer_email'] ?? 'customer@example.com';
        $customerPhone = $customer['customer_phone'] ?? '+255700000000';
        $shippingAddress = $customer['shipping_address'] ?? 'Dar es Salaam';
        $deliveryFee = $customer['delivery_fee'] ?? 0;
        $subtotal = $customer['subtotal'] ?? $total;

        $stmt = $this->conn->prepare("
            INSERT INTO orders (
                order_number,
                user_id,
                customer_name,
                customer_email,
                customer_phone,
                shipping_address,
                subtotal,
                delivery_fee,
                total,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $orderNumber,
            $user_id,
            $customerName,
            $customerEmail,
            $customerPhone,
            $shippingAddress,
            $subtotal,
            $deliveryFee,
            $total
        ]);
        return $this->conn->lastInsertId();
    }

    public function getByUser($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getItems($order_id) {
        $stmt = $this->conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($order_id, $status) {
        $stmt = $this->conn->prepare("
            UPDATE orders SET status = ? WHERE id = ?
        ");
        return $stmt->execute([$status, $order_id]);
    }
}
?>
