-- Migration: add user_cart_items, inventory_reservations, indexes, and extend orders statuses

START TRANSACTION;

-- 1) Backup tables (schema-only)
CREATE TABLE IF NOT EXISTS product_variants_backup AS SELECT * FROM product_variants WHERE 0;
CREATE TABLE IF NOT EXISTS orders_backup AS SELECT * FROM orders WHERE 0;

-- 2) Create user_cart_items (persistent cart for logged-in users)
CREATE TABLE IF NOT EXISTS user_cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    variant_id INT UNSIGNED DEFAULT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_cart_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL,
    UNIQUE KEY ux_user_variant (user_id, variant_id)
) ENGINE=InnoDB;

-- 3) Create inventory_reservations table
CREATE TABLE IF NOT EXISTS inventory_reservations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    order_id INT UNSIGNED NULL,
    variant_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    status ENUM('active','consumed','released') NOT NULL DEFAULT 'active',
    expires_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservation_variant FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    CONSTRAINT fk_reservation_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4) Add useful indexes for performance
ALTER TABLE product_variants
    ADD INDEX idx_variants_product (product_id),
    ADD INDEX idx_variants_sku (sku);

ALTER TABLE order_items
    ADD INDEX idx_order_items_variant (variant_id);

-- 5) Alter orders table to include new statuses 'paid' and 'refunded' (if not present)
-- Note: ALTER TABLE MODIFY to redefine enum safely
ALTER TABLE orders MODIFY COLUMN status ENUM('pending','paid','confirmed','processing','shipped','in_transit','out_for_delivery','delivered','cancelled','refunded') NOT NULL DEFAULT 'pending';

COMMIT;

-- Notes:
-- - After applying, implement cron or background job to release reservations when expired.
-- - The application will use SELECT ... FOR UPDATE during checkout to prevent overselling.
