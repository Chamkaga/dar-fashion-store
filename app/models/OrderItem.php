<?php

class OrderItem {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function addItem($order_id, $product_id, $quantity, $price) {
        $stmt = $this->conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ");

        return $stmt->execute([$order_id, $product_id, $quantity, $price]);
    }

    public function getItemsByOrder($order_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM order_items WHERE order_id = ?
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>