-- Adds tables missing from older/partial installs (safe to re-run)

CREATE TABLE IF NOT EXISTS user_wishlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wishlist_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_wishlist_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, product_id),
    INDEX idx_wishlist_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    label VARCHAR(50) NOT NULL,
    fullname VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    region VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    postal_code VARCHAR(20),
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_addresses_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_addresses_user (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    discount_percentage DECIMAL(5,2),
    discount_amount DECIMAL(10,2),
    description TEXT,
    valid_from DATE NOT NULL,
    valid_until DATE NOT NULL,
    min_purchase DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    max_uses INT UNSIGNED,
    times_used INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active', 'used', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_coupons_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_coupons_user (user_id),
    INDEX idx_coupons_code (code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_returns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    return_number VARCHAR(40) NOT NULL UNIQUE,
    reason VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'shipped_back', 'completed', 'refunded') NOT NULL DEFAULT 'pending',
    refund_amount DECIMAL(10,2),
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_returns_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_returns_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_returns_user (user_id),
    INDEX idx_returns_order (order_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_confirmations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    channel ENUM('email', 'sms', 'whatsapp') NOT NULL DEFAULT 'email',
    recipient VARCHAR(160) NOT NULL,
    subject VARCHAR(180),
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') NOT NULL DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_confirmations_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_order_confirmations_order (order_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    activity_type ENUM('login', 'logout', 'view_product', 'add_to_cart', 'remove_from_cart', 'checkout', 'order_placed', 'payment_attempted', 'order_status_update') NOT NULL,
    product_id INT UNSIGNED,
    order_id INT UNSIGNED,
    details JSON,
    ip_address VARCHAR(45),
    user_agent VARCHAR(500),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_activities_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_user_activities_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_user_activities_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_user_activities_user (user_id),
    INDEX idx_user_activities_created_at (created_at),
    INDEX idx_user_activities_type (activity_type),
    INDEX idx_user_activities_order (order_id)
) ENGINE=InnoDB;
