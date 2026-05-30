<?php

class Payment {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function create($order_id, $method, $amount, $payment_reference, $payment_provider, $transaction_ref) {
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
            INSERT INTO payments (order_id, method, amount, payment_status, transaction_ref, payment_reference, payment_provider, created_at)
            VALUES (?, ?, ?, 'pending', ?, ?, ?, NOW())
        ");

        return $stmt->execute([$order_id, $method, $amount, $transaction_ref, $payment_reference, $payment_provider]);
    }

    public function verifyPayment($order_id, $transaction_ref, $provider_response = null) {
        $stmt = $this->conn->prepare("
            UPDATE payments 
            SET payment_status = 'verified',
                transaction_ref = ?,
                provider_response = ?,
                paid_at = NOW()
            WHERE order_id = ?
        ");

        return $stmt->execute([$transaction_ref, json_encode($provider_response), $order_id]);
    }

    public function markFailed($order_id, $reason = null) {
        $stmt = $this->conn->prepare("
            UPDATE payments
            SET payment_status = 'failed',
                provider_response = ?,
                paid_at = NULL
            WHERE order_id = ?
        ");

        return $stmt->execute([json_encode(['error' => $reason]), $order_id]);
    }

    public function refund($order_id, $amount, $reason = null) {
        $stmt = $this->conn->prepare("
            UPDATE payments
            SET payment_status = 'refunded',
                provider_response = ?,
                paid_at = NOW()
            WHERE order_id = ?
        ");

        return $stmt->execute([json_encode(['refund_amount' => $amount, 'reason' => $reason]), $order_id]);
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
