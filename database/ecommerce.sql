-- =========================================
-- DAR FASHION STORE DATABASE EXPORT
-- University Assignment 1 - Group 1
-- Project: Online Store for a Local Fashion Business in Dar es Salaam
-- =========================================

CREATE DATABASE IF NOT EXISTS fashion_storedb
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE fashion_storedb;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS order_confirmations;
DROP TABLE IF EXISTS user_activities;
DROP TABLE IF EXISTS delivery_regions;
DROP TABLE IF EXISTS store_settings;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS newsletter_subscribers;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS cart;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================
-- USERS TABLE
-- Stores customer/admin accounts.
-- Passwords must be saved with PHP password_hash().
-- =========================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    phone VARCHAR(30),
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    status ENUM('active', 'blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- USER ACTIVITIES TABLE
-- Logs all user actions for admin monitoring and analytics.
-- Tracks: login, logout, product views, cart actions, orders, payments.
-- =========================================
CREATE TABLE user_activities (
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
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_user_activities_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT fk_user_activities_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    INDEX idx_user_activities_user (user_id),
    INDEX idx_user_activities_created_at (created_at),
    INDEX idx_user_activities_type (activity_type),
    INDEX idx_user_activities_order (order_id)
) ENGINE=InnoDB;

-- =========================================
-- CATEGORIES TABLE
-- Supports category navigation and filtering.
-- =========================================
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(500),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- PRODUCTS TABLE
-- Product catalog with prices, images, stock, and category links.
-- =========================================
CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    sku VARCHAR(80) NOT NULL UNIQUE,
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2),
    image VARCHAR(500),
    description TEXT,
    size_options VARCHAR(120),
    color_options VARCHAR(160),
    stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_trending TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    INDEX idx_products_category (category_id),
    INDEX idx_products_status (status),
    INDEX idx_products_featured (is_featured),
    INDEX idx_products_trending (is_trending)
) ENGINE=InnoDB;

-- =========================================
-- PRODUCT IMAGES TABLE
-- Allows multiple images on the product details page.
-- =========================================
CREATE TABLE product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    image_url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(180),
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_images_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_product_images_product (product_id)
) ENGINE=InnoDB;

-- =========================================
-- ORDERS TABLE
-- Stores checkout, shipping, totals, and order status.
-- user_id is nullable so demo guest checkout can still work.
-- =========================================
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(40) NOT NULL UNIQUE,
    user_id INT UNSIGNED,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(160) NOT NULL,
    customer_phone VARCHAR(30) NOT NULL,
    shipping_address TEXT NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'confirmed', 'processing', 'shipped', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    delivery_company VARCHAR(120),
    delivery_notes TEXT,
    estimated_delivery_days INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    INDEX idx_orders_user (user_id),
    INDEX idx_orders_status (status),
    INDEX idx_orders_created_at (created_at)
) ENGINE=InnoDB;

-- =========================================
-- ORDER ITEMS TABLE
-- Stores products attached to each order.
-- =========================================
CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED,
    product_name VARCHAR(180) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_product (product_id)
) ENGINE=InnoDB;

-- =========================================
-- PAYMENTS TABLE
-- Supports secure payment simulation/demo.
-- No card numbers are stored.
-- =========================================
CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    method ENUM('mobile_money', 'card_demo', 'cash_on_delivery') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'verified', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    transaction_ref VARCHAR(120),
    provider_response TEXT,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_payments_order (order_id),
    INDEX idx_payments_status (payment_status)
) ENGINE=InnoDB;

-- =========================================
-- CART TABLE
-- Optional database cart for logged-in customers.
-- Session cart can still be used in PHP for quick demo.
-- =========================================
CREATE TABLE cart (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_cart_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
) ENGINE=InnoDB;

-- =========================================
-- ORDER CONFIRMATIONS TABLE
-- Stores demo confirmation email/message records.
-- =========================================
CREATE TABLE order_confirmations (
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
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_order_confirmations_order (order_id)
) ENGINE=InnoDB;

-- =========================================
-- DELIVERY REGIONS TABLE
-- Supports Tanzania delivery fee and ETA rules.
-- =========================================
CREATE TABLE delivery_regions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    region_name VARCHAR(100) NOT NULL UNIQUE,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estimated_days VARCHAR(40) NOT NULL,
    delivery_method VARCHAR(120) NOT NULL DEFAULT 'Courier',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- STORE SETTINGS TABLE
-- Simple key/value settings for store identity and payment configuration.
-- =========================================
CREATE TABLE store_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- CONTACT MESSAGES TABLE
-- Stores contact/about page messages.
-- =========================================
CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL,
    phone VARCHAR(30),
    subject VARCHAR(180),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- NEWSLETTER SUBSCRIBERS TABLE
-- Supports marketing/email feature.
-- =========================================
CREATE TABLE newsletter_subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(160) NOT NULL UNIQUE,
    status ENUM('subscribed', 'unsubscribed') NOT NULL DEFAULT 'subscribed',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================
-- REVIEWS TABLE
-- Supports customer trust with product ratings.
-- =========================================
CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED,
    customer_name VARCHAR(120) NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    INDEX idx_reviews_product (product_id)
) ENGINE=InnoDB;

-- =========================================
-- SAMPLE DATA
-- Password for demo accounts should be explained as hashed.
-- Hash shown is for a demo password and must be changed in production.
-- =========================================

INSERT INTO users (fullname, email, phone, password, role) VALUES
('Admin User', 'admin@darfashion.store', '+255700000000', '$2y$12$o.UsA72zUv5UeSgIAWR9/.G2Nd2ZgAVowFLbqsYgucpzAw/mp0cUC', 'admin'),
('Amina Hassan', 'amina@example.com', '+255711111111', '$2y$12$o.UsA72zUv5UeSgIAWR9/.G2Nd2ZgAVowFLbqsYgucpzAw/mp0cUC', 'customer');

INSERT INTO categories (name, slug, description, image) VALUES
('Men', 'men', 'Men shirts, jackets, and smart casual fashion.', 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?auto=format&fit=crop&w=600&q=80'),
('Women', 'women', 'Women dresses, outfits, and everyday fashion.', 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=600&q=80'),
('Shoes', 'shoes', 'Sneakers, sandals, and formal shoes.', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80'),
('Bags', 'bags', 'Handbags, tote bags, and backpacks.', 'https://images.unsplash.com/photo-1594223274512-ad4803739b7c?auto=format&fit=crop&w=600&q=80'),
('Accessories', 'accessories', 'Watches, earrings, and style accessories.', 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=600&q=80');

INSERT INTO products
(category_id, name, slug, sku, price, sale_price, image, description, size_options, color_options, stock_quantity, is_featured, is_trending)
VALUES
(2, 'Linen Summer Dress', 'linen-summer-dress', 'DFS-WOM-001', 48000.00, 42000.00, 'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=600&q=80', 'Light and stylish dress for warm Dar es Salaam weather.', 'S,M,L,XL', 'White,Beige,Orange', 24, 1, 1),
(1, 'Classic Men Shirt', 'classic-men-shirt', 'DFS-MEN-001', 35000.00, NULL, 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=600&q=80', 'Clean smart casual shirt for work and events.', 'M,L,XL,XXL', 'Blue,White,Black', 30, 1, 0),
(3, 'Street Sneakers', 'street-sneakers', 'DFS-SHO-001', 72000.00, 65000.00, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=600&q=80', 'Comfortable sneakers for everyday movement.', '39,40,41,42,43,44', 'White,Black,Red', 18, 1, 1),
(4, 'Everyday Tote Bag', 'everyday-tote-bag', 'DFS-BAG-001', 42000.00, NULL, 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=600&q=80', 'Durable tote bag for shopping, work, and travel.', 'One Size', 'Brown,Black,Tan', 16, 1, 0),
(2, 'Printed Kitenge Set', 'printed-kitenge-set', 'DFS-WOM-002', 58000.00, NULL, 'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?auto=format&fit=crop&w=600&q=80', 'Colorful fashion set inspired by local style.', 'S,M,L,XL', 'Mixed Print', 20, 0, 1),
(5, 'Minimal Wrist Watch', 'minimal-wrist-watch', 'DFS-ACC-001', 44000.00, 39000.00, 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?auto=format&fit=crop&w=600&q=80', 'Simple watch that works with formal and casual outfits.', 'One Size', 'Gold,Black,Silver', 12, 0, 1);

INSERT INTO product_images (product_id, image_url, alt_text, sort_order) VALUES
(1, 'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=900&q=80', 'Linen summer dress main view', 1),
(1, 'https://images.unsplash.com/photo-1485968579580-b6d095142e6e?auto=format&fit=crop&w=600&q=80', 'Linen summer dress styled view', 2),
(3, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=900&q=80', 'Street sneakers main view', 1),
(4, 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=900&q=80', 'Everyday tote bag main view', 1);

INSERT INTO orders
(order_number, user_id, customer_name, customer_email, customer_phone, shipping_address, subtotal, delivery_fee, total, status)
VALUES
('DFS-1001', 2, 'Amina Hassan', 'amina@example.com', '+255711111111', 'Mikocheni, Dar es Salaam', 120000.00, 5000.00, 125000.00, 'processing');

INSERT INTO order_items (order_id, product_id, product_name, quantity, price, line_total) VALUES
(1, 1, 'Linen Summer Dress', 1, 48000.00, 48000.00),
(1, 3, 'Street Sneakers', 1, 72000.00, 72000.00);

INSERT INTO payments (order_id, method, amount, payment_status, transaction_ref, provider_response, paid_at) VALUES
(1, 'mobile_money', 125000.00, 'verified', 'DEMO-MPESA-1001', 'Demo payment approved for class presentation.', NOW());

INSERT INTO order_confirmations (order_id, channel, recipient, subject, message, status, sent_at) VALUES
(1, 'email', 'amina@example.com', 'Dar Fashion Store Order Confirmation', 'Thank you for your order DFS-1001. Your payment has been verified and your items are being prepared.', 'sent', NOW());

UPDATE orders
SET delivery_company = 'Dar Express Courier',
    delivery_notes = 'Payment verified. Items are being prepared at the Dar es Salaam warehouse.',
    estimated_delivery_days = 1
WHERE order_number = 'DFS-1001';

INSERT INTO delivery_regions (region_name, delivery_fee, estimated_days, delivery_method) VALUES
('Dar es Salaam', 5000.00, '1 day', 'Local courier'),
('Morogoro', 8000.00, '2 days', 'Road courier'),
('Mwanza', 12000.00, '3-4 days', 'Road courier'),
('Zanzibar', 10000.00, '2 days', 'Sea delivery');

INSERT INTO store_settings (setting_key, setting_value) VALUES
('store_name', 'Dar Fashion Store'),
('contact_phone', '+255700000000'),
('contact_email', 'info@darfashion.store'),
('payment_methods', 'M-Pesa, Card Demo, Cash on Delivery');

INSERT INTO messages (name, email, phone, subject, message) VALUES
('Neema John', 'neema@example.com', '+255722222222', 'Product size question', 'Do you have the Linen Summer Dress in medium size?');

INSERT INTO newsletter_subscribers (email) VALUES
('customer@example.com'),
('fashionfan@example.com');

INSERT INTO reviews (product_id, user_id, customer_name, rating, comment) VALUES
(1, 2, 'Amina Hassan', 5, 'Very good quality and fast delivery.'),
(3, NULL, 'Kelvin', 5, 'Comfortable sneakers and fair price.'),
(4, NULL, 'Neema', 4, 'The bag is strong and looks professional.');
