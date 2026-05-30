<?php

class ProductVariant {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($product_id, $sku = null, $color = null, $size = null, $price = null, $stock = 0) {
        $stmt = $this->conn->prepare("INSERT INTO product_variants (product_id, sku, color, size, price, stock_quantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $sku, $color, $size, $price, $stock]);
        return $this->conn->lastInsertId();
    }

    public function getByProduct($product_id) {
        $stmt = $this->conn->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
        $stmt->execute([$product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($variant_id) {
        $stmt = $this->conn->prepare("SELECT * FROM product_variants WHERE id = ? LIMIT 1");
        $stmt->execute([$variant_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateStock($variant_id, $qty) {
        $stmt = $this->conn->prepare("UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?");
        return $stmt->execute([$qty, $variant_id, $qty]);
    }

    public function setStock($variant_id, $qty) {
        $stmt = $this->conn->prepare("UPDATE product_variants SET stock_quantity = ? WHERE id = ?");
        return $stmt->execute([$qty, $variant_id]);
    }

    public function updatePrice($variant_id, $price) {
        $stmt = $this->conn->prepare("UPDATE product_variants SET price = ? WHERE id = ?");
        return $stmt->execute([$price, $variant_id]);
    }
}

?>
