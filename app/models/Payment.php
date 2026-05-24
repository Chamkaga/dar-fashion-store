<?php

class Payment {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function create($order_id, $method, $amount) {
        $allowedMethods = [
            'Mobile Money' => 'mobile_money',
            'Card Payment' => 'card_demo',
            'Cash on Delivery' => 'cash_on_delivery',
            'mobile_money' => 'mobile_money',
            'card_demo' => 'card_demo',
            'cash_on_delivery' => 'cash_on_delivery'
        ];
        $method = $allowedMethods[$method] ?? 'mobile_money';

        $stmt = $this->conn->prepare("
            INSERT INTO payments (order_id, method, amount, payment_status)
            VALUES (?, ?, ?, 'pending')
        ");

        return $stmt->execute([$order_id, $method, $amount]);
    }

    public function verifyPayment($order_id, $transaction_ref) {
        $stmt = $this->conn->prepare("
            UPDATE payments 
            SET payment_status = 'verified',
                transaction_ref = ?,
                paid_at = NOW()
            WHERE order_id = ?
        ");

        return $stmt->execute([$transaction_ref, $order_id]);
    }

    public function getByOrder($order_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM payments WHERE order_id = ?
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
