-- Migration: production prelaunch architecture hardening
START TRANSACTION;

-- Add audit log table for admin actions, payment/fraud events, and stock changes
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    target_type VARCHAR(80) NULL,
    target_id INT UNSIGNED NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Add a backend job queue for emails, analytics, inventory cleanup, and notifications
CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_type VARCHAR(80) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_error TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_jobs_status_available (status, available_at),
    INDEX idx_jobs_type (job_type)
) ENGINE=InnoDB;

-- Add idempotency support to orders and payment reference tracking
ALTER TABLE orders
    ADD COLUMN idempotency_key VARCHAR(120) NULL AFTER order_number,
    ADD UNIQUE KEY ux_orders_idempotency (idempotency_key);

ALTER TABLE payments
    ADD COLUMN payment_reference VARCHAR(120) NULL AFTER transaction_ref,
    ADD COLUMN payment_provider VARCHAR(80) NOT NULL DEFAULT 'internal',
    ADD UNIQUE KEY ux_payments_reference (payment_reference);

-- Harden inventory and cart query performance
ALTER TABLE inventory_reservations
    ADD INDEX idx_reservations_status_expires (status, expires_at),
    ADD INDEX idx_reservations_order (order_id);

ALTER TABLE user_cart_items
    ADD INDEX idx_user_cart_items_user (user_id);

ALTER TABLE payments
    ADD INDEX idx_payments_provider (payment_provider);

ALTER TABLE product_variants
    ADD INDEX idx_variants_stock (stock_quantity);

COMMIT;
