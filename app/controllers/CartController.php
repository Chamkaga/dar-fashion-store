<?php

require_once __DIR__ . "/../models/Cart.php";

class CartController {

    private $cart;

    public function __construct() {
        $this->cart = new Cart();
    }

    public function addToCart($product_id, $variant_id, $qty, $unit_price = null, $color = null, $size = null) {
        $this->cart->add($product_id, $variant_id, $qty, $unit_price, $color, $size);
    }

    public function removeFromCart($product_id) {
        $this->cart->remove($product_id);
    }

    public function getCartItems() {
        return $this->cart->getCart();
    }

    public function clearCart() {
        $this->cart->clear();
    }
}
?>