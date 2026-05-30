<?php

class OrderItem {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function addItem($order_id, $product_id, $quantity, $price, $product_name = null, $variant_id = null, $sku = null, $selected_color = null, $selected_size = null) {
        if ($product_name === null) {
            $productStmt = $this->conn->prepare("SELECT name FROM products WHERE id = ?");
            $productStmt->execute([$product_id]);
            $product = $productStmt->fetch(PDO::FETCH_ASSOC);
            $product_name = $product['name'] ?? 'Product';
        }

        $line_total = $quantity * $price;

        $stmt = $this->conn->prepare(
            "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, line_total, variant_id, unit_price, sku, selected_color, selected_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        return $stmt->execute([$order_id, $product_id, $product_name, $quantity, $price, $line_total, $variant_id, $price, $sku, $selected_color, $selected_size]);
    }

    public function getItemsByOrder($order_id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM order_items WHERE order_id = ?"
        );
        $stmt->execute([$order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
