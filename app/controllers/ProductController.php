<?php

require_once __DIR__ . "/../models/Product.php";

class ProductController {
    private $product;

    public function __construct($db) {
        $this->product = new Product($db);
    }

    public function getAllProducts() {
        return $this->product->getAll();
    }

    public function getProduct($id) {
        return $this->product->getById($id);
    }
}
?>