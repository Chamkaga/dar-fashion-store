-- Backup recommendation: export your current DB before running this migration.
-- Migration: add product_variants, product_variant_images, variant stock handling, and user profile fields.

START TRANSACTION;

-- 1) Backup existing tables by creating copies (schema-only for safety)
CREATE TABLE IF NOT EXISTS products_backup AS SELECT * FROM products WHERE 0;
CREATE TABLE IF NOT EXISTS order_items_backup AS SELECT * FROM order_items WHERE 0;
CREATE TABLE IF NOT EXISTS users_backup AS SELECT * FROM users WHERE 0;

-- 2) Create product_variants table
CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    sku VARCHAR(100) DEFAULT NULL,
    color VARCHAR(100) DEFAULT NULL,
    size VARCHAR(100) DEFAULT NULL,
    price DECIMAL(12,2) DEFAULT NULL,
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_variant_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3) Create product_variant_images table for additional images per variant
CREATE TABLE IF NOT EXISTS product_variant_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    variant_id INT NOT NULL,
    image_url TEXT,
    sort_order INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_variant_image FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4) Alter products table: keep size_options/color_options for now but mark deprecated
ALTER TABLE products 
    ADD COLUMN deprecated_size_options VARCHAR(255) NULL,
    ADD COLUMN deprecated_color_options VARCHAR(255) NULL;

-- Move existing data into product_variants for compatibility
-- Note: This migration will create one default variant per existing product preserving stock and price.
INSERT INTO product_variants (product_id, sku, color, size, price, stock_quantity, created_at)
SELECT id AS product_id, sku, NULL AS color, NULL AS size, price, stock_quantity, NOW()
FROM products;

-- 5) Update order_items to reference variant data and keep legacy product_id
ALTER TABLE order_items 
    ADD COLUMN variant_id INT NULL AFTER product_id,
    ADD COLUMN unit_price DECIMAL(12,2) NULL AFTER price,
    ADD COLUMN sku VARCHAR(120) NULL AFTER unit_price,
    ADD COLUMN selected_color VARCHAR(100) NULL AFTER sku,
    ADD COLUMN selected_size VARCHAR(100) NULL AFTER selected_color;

-- If order_items had a price column, preserve it into unit_price
UPDATE order_items SET unit_price = price WHERE price IS NOT NULL;

-- 6) Add user profile fields
ALTER TABLE users
    ADD COLUMN shipping_address TEXT NULL,
    ADD COLUMN billing_address TEXT NULL,
    ADD COLUMN profile_picture VARCHAR(255) NULL;

COMMIT;

-- Notes:
-- - After migration, admin UI should allow creating more variants per product and assigning stock to each variant.
-- - Existing products will have one variant created to preserve current stock and SKU.
-- - Review and remove deprecated_size_options / deprecated_color_options after migration and thorough testing.
