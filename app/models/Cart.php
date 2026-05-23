<?php

class Cart {

    public function add($product_id, $qty) {
        $_SESSION['cart'][$product_id] = $qty;
    }

    public function remove($product_id) {
        unset($_SESSION['cart'][$product_id]);
    }

    public function getCart() {
        return $_SESSION['cart'] ?? [];
    }

    public function clear() {
        unset($_SESSION['cart']);
    }
}
?>