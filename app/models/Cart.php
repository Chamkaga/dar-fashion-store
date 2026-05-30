<?php

class Cart {
    // Cart items stored as: $_SESSION['cart']['v{variant_id}'] = [product_id, variant_id, qty, unit_price, color, size]
    public function add($product_id, $variant_id, $qty, $unit_price = null, $color = null, $size = null) {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        $key = 'v' . (int)$variant_id;
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['qty'] += (int)$qty;
        } else {
            $_SESSION['cart'][$key] = [
                'product_id' => (int)$product_id,
                'variant_id' => (int)$variant_id,
                'qty' => (int)$qty,
                'unit_price' => $unit_price,
                'color' => $color,
                'size' => $size
            ];
        }
    }

    public function remove($variant_id) {
        $key = 'v' . (int)$variant_id;
        if (isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
        }
    }

    public function updateQuantity($variant_id, $qty) {
        $key = 'v' . (int)$variant_id;
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['qty'] = (int)$qty;
        }
    }

    public function getCart() {
        return array_values($_SESSION['cart'] ?? []);
    }

    public function clear() {
        unset($_SESSION['cart']);
    }

    // Persist session cart to DB for logged-in user
    public function syncSessionToDb($conn, $user_id) {
        if (!$conn || !$user_id) return false;
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) return true;

        $upsert = $conn->prepare("
            INSERT INTO user_cart_items (user_id, product_id, variant_id, quantity, unit_price)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), unit_price = VALUES(unit_price), updated_at = NOW()
        ");

        foreach ($_SESSION['cart'] as $key => $item) {
            if (is_array($item) && isset($item['variant_id'])) {
                $upsert->execute([$user_id, $item['product_id'], $item['variant_id'], $item['qty'], $item['unit_price']]);
            } else {
                // legacy product key
                $product_id = (int)$key;
                $qty = (int)$item;
                $upsert->execute([$user_id, $product_id, null, $qty, null]);
            }
        }

        return true;
    }

    // Load DB cart for logged-in user into session (merge)
    public function loadDbToSession($conn, $user_id) {
        if (!$conn || !$user_id) return false;
        $stmt = $conn->prepare("SELECT * FROM user_cart_items WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return true;
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

        foreach ($rows as $r) {
            $key = $r['variant_id'] ? 'v' . $r['variant_id'] : $r['product_id'];
            if (isset($_SESSION['cart'][$key])) {
                // merge quantities
                if (is_array($_SESSION['cart'][$key])) {
                    $_SESSION['cart'][$key]['qty'] += (int)$r['quantity'];
                } else {
                    $_SESSION['cart'][$key] += (int)$r['quantity'];
                }
            } else {
                if ($r['variant_id']) {
                    $_SESSION['cart'][$key] = [
                        'product_id' => (int)$r['product_id'],
                        'variant_id' => (int)$r['variant_id'],
                        'qty' => (int)$r['quantity'],
                        'unit_price' => $r['unit_price']
                    ];
                } else {
                    $_SESSION['cart'][$key] = (int)$r['quantity'];
                }
            }
        }
        return true;
    }
}
?>