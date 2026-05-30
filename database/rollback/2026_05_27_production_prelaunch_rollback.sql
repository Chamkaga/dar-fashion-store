-- Rollback for production prelaunch changes
START TRANSACTION;

ALTER TABLE payments
    DROP INDEX ux_payments_reference,
    DROP INDEX idx_payments_provider,
    DROP COLUMN payment_reference,
    DROP COLUMN payment_provider;

ALTER TABLE orders
    DROP INDEX ux_orders_idempotency,
    DROP COLUMN idempotency_key;

ALTER TABLE inventory_reservations
    DROP INDEX idx_reservations_status_expires,
    DROP INDEX idx_reservations_order;

ALTER TABLE user_cart_items
    DROP INDEX idx_user_cart_items_user;

ALTER TABLE product_variants
    DROP INDEX idx_variants_stock;

DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS audit_logs;

COMMIT;
