<?php

class Category {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        return $this->conn->query("
            SELECT categories.*, COUNT(products.id) AS product_count
            FROM categories
            LEFT JOIN products ON products.category_id = categories.id AND products.status = 'active'
            WHERE categories.is_active = 1
            GROUP BY categories.id
            ORDER BY categories.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}
