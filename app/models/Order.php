<?php

class Order {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $total) {
        $stmt = $this->conn->prepare("
            INSERT INTO orders (user_id, total, status) 
            VALUES (?, ?, 'pending')
        ");
        $stmt->execute([$user_id, $total]);
        return $this->conn->lastInsertId();
    }

    public function getByUser($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE user_id = ?");
        $stmt->execute([$user_id]);
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