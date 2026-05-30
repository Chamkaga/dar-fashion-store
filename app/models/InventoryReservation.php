<?php

class InventoryReservation {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($order_id, $variant_id, $quantity, $expiresAt, $user_id = null) {
        $stmt = $this->conn->prepare("INSERT INTO inventory_reservations (user_id, order_id, variant_id, quantity, expires_at) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $order_id, $variant_id, $quantity, $expiresAt]);
    }

    public function getExpiredActive() {
        $stmt = $this->conn->prepare("SELECT * FROM inventory_reservations WHERE status = 'active' AND expires_at <= NOW() ORDER BY expires_at ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function releaseExpired() {
        $expired = $this->getExpiredActive();
        if (!$expired) {
            return 0;
        }
        $restored = 0;
        foreach ($expired as $reservation) {
            try {
                $this->conn->beginTransaction();
                $variantId = $reservation['variant_id'];
                $quantity = (int)$reservation['quantity'];

                if ($variantId) {
                    $updateStock = $this->conn->prepare("UPDATE product_variants SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    $updateStock->execute([$quantity, $variantId]);
                }

                $stmt = $this->conn->prepare("UPDATE inventory_reservations SET status = 'released' WHERE id = ?");
                $stmt->execute([$reservation['id']]);

                if (!empty($reservation['order_id'])) {
                    $orderStmt = $this->conn->prepare("SELECT status FROM orders WHERE id = ?");
                    $orderStmt->execute([$reservation['order_id']]);
                    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
                    if ($order && in_array($order['status'], ['pending', 'confirmed'], true)) {
                        $cancelStmt = $this->conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
                        $cancelStmt->execute([$reservation['order_id']]);
                    }
                }

                $this->conn->commit();
                $restored++;
            } catch (Throwable $e) {
                $this->conn->rollBack();
                Logger::error('Failed to release expired reservation', ['reservation_id' => $reservation['id'], 'error' => $e->getMessage()]);
            }
        }
        return $restored;
    }

    public function consumeForOrder($order_id) {
        $stmt = $this->conn->prepare("UPDATE inventory_reservations SET status = 'consumed' WHERE order_id = ? AND status = 'active'");
        return $stmt->execute([$order_id]);
    }

    public function restoreForOrder($order_id) {
        $stmt = $this->conn->prepare("SELECT * FROM inventory_reservations WHERE order_id = ? AND status = 'active'");
        $stmt->execute([$order_id]);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$reservations) {
            return 0;
        }

        $restored = 0;
        foreach ($reservations as $reservation) {
            try {
                $this->conn->beginTransaction();
                if ($reservation['variant_id']) {
                    $updateStock = $this->conn->prepare("UPDATE product_variants SET stock_quantity = stock_quantity + ? WHERE id = ?");
                    $updateStock->execute([(int)$reservation['quantity'], $reservation['variant_id']]);
                }
                $stmt = $this->conn->prepare("UPDATE inventory_reservations SET status = 'released' WHERE id = ?");
                $stmt->execute([$reservation['id']]);
                $this->conn->commit();
                $restored++;
            } catch (Throwable $e) {
                $this->conn->rollBack();
                Logger::error('Failed to restore reservation stock', ['reservation_id' => $reservation['id'], 'error' => $e->getMessage()]);
            }
        }
        return $restored;
    }
}
