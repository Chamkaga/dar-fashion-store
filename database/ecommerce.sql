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
DROP TABLE IF EXISTS user_returns;
DROP TABLE IF EXISTS user_coupons;
DROP TABLE IF EXISTS user_addresses;
DROP TABLE IF EXISTS user_wishlist;
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
-- USER WISHLIST TABLE
-- Stores saved/favorited products for each user.
-- =========================================
CREATE TABLE user_wishlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_wishlist_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_wishlist_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, product_id),
    INDEX idx_wishlist_user (user_id)
) ENGINE=InnoDB;

-- =========================================
-- USER ADDRESSES TABLE
-- Stores multiple shipping addresses for users.
-- =========================================
CREATE TABLE user_addresses (
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
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_addresses_user (user_id)
) ENGINE=InnoDB;

-- =========================================
-- USER COUPONS TABLE
-- Stores coupons/vouchers for users.
-- =========================================
CREATE TABLE user_coupons (
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
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_coupons_user (user_id),
    INDEX idx_coupons_code (code)
) ENGINE=InnoDB;

-- =========================================
-- USER RETURNS TABLE
-- Stores return and refund requests.
-- =========================================
CREATE TABLE user_returns (
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
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_returns_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    INDEX idx_returns_user (user_id),
    INDEX idx_returns_order (order_id)
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
-- USER ACTIVITIES TABLE
-- Logs all user actions for admin monitoring and analytics.
-- Must be created after products and orders (foreign keys).
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
('Amina Hassan', 'amina@example.com', '+255711111111', '$2y$12$o.UsA72zUv5UeSgIAWR9/.G2Nd2ZgAVowFLbqsYgucpzAw/mp0cUC', 'customer'),
('Ignas Kipchoge', 'ignas@example.com', '+255722333444', '$2y$12$o.UsA72zUv5UeSgIAWR9/.G2Nd2ZgAVowFLbqsYgucpzAw/mp0cUC', 'customer'),
('Fatima Mohamed', 'fatima@example.com', '+255733444555', '$2y$12$o.UsA72zUv5UeSgIAWR9/.G2Nd2ZgAVowFLbqsYgucpzAw/mp0cUC', 'customer'),
('James Wilson', 'james@example.com', '+255744555666', '$2y$12$o.UsA72zUv5UeSgIAWR9/.G2Nd2ZgAVowFLbqsYgucpzAw/mp0cUC', 'customer'),
('Zainab Ali', 'zainab@example.com', '+255755666777', '$2y$12$o.UsA72zUv5UeSgIAWR9/.G2Nd2ZgAVowFLbqsYgucpzAw/mp0cUC', 'customer'),
('Mwangi Njoroge', 'mwangi@example.com', '+255766777888', '$2y$12$o.UsA72zUv5UeSgIAWR9/.G2Nd2ZgAVowFLbqsYgucpzAw/mp0cUC', 'customer'),
('Naomi Okafor', 'naomi@example.com', '+255777888999', '$2y$12$o.UsA72zUv5UeSgIAWR9/.G2Nd2ZgAVowFLbqsYgucpzAw/mp0cUC', 'customer');

INSERT INTO categories (name, slug, description, image) VALUES
('Men', 'men', 'Men shirts, jackets, and smart casual fashion.', 'https://images.unsplash.com/photo-1516257984-b1b4d707412e?auto=format&fit=crop&w=600&q=80'),
('Women', 'women', 'Women dresses, outfits, and everyday fashion.', 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=600&q=80'),
('Shoes', 'shoes', 'Sneakers, sandals, and formal shoes.', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80'),
('Bags', 'bags', 'Handbags, tote bags, and backpacks.', 'https://images.unsplash.com/photo-1594223274512-ad4803739b7c?auto=format&fit=crop&w=600&q=80'),
('Accessories', 'accessories', 'Watches, earrings, and style accessories.', 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?auto=format&fit=crop&w=600&q=80');

INSERT INTO products
(category_id, name, slug, sku, price, sale_price, image, description, size_options, color_options, stock_quantity, is_featured, is_trending)
VALUES
-- WOMEN (Category 2) - 15 products
(2, 'Linen Summer Dress', 'linen-summer-dress', 'DFS-WOM-001', 48000.00, 42000.00, 'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=600&q=80', 'Light and stylish dress for warm Dar es Salaam weather.', 'S,M,L,XL', 'White,Beige,Orange', 24, 1, 1),
(2, 'Printed Kitenge Set', 'printed-kitenge-set', 'DFS-WOM-002', 58000.00, 52000.00, 'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?auto=format&fit=crop&w=600&q=80', 'Colorful fashion set inspired by local style.', 'S,M,L,XL', 'Mixed Print', 20, 0, 1),
(2, 'Casual Blouse', 'casual-blouse', 'DFS-WOM-003', 35000.00, NULL, 'https://images.unsplash.com/photo-1506629082847-11d3e392e467?auto=format&fit=crop&w=600&q=80', 'Perfect everyday blouse for the office or casual outings.', 'S,M,L,XL,XXL', 'Pink,Blue,White,Black', 28, 1, 0),
(2, 'Evening Gown', 'evening-gown', 'DFS-WOM-004', 95000.00, 85000.00, 'https://images.unsplash.com/photo-1566126842435-f0dc07b30cfb?auto=format&fit=crop&w=600&q=80', 'Elegant evening gown for special occasions.', 'S,M,L,XL', 'Black,Red,Navy', 10, 1, 1),
(2, 'Denim Jacket', 'denim-jacket', 'DFS-WOM-005', 62000.00, 55000.00, 'https://images.unsplash.com/photo-1551028719-00167b16ebc5?auto=format&fit=crop&w=600&q=80', 'Classic denim jacket for layering and style.', 'S,M,L,XL,XXL', 'Blue,Black,Light Blue', 35, 1, 0),
(2, 'Maxi Skirt', 'maxi-skirt', 'DFS-WOM-006', 42000.00, NULL, 'https://images.unsplash.com/photo-1606252297535-5fd5dac78c5e?auto=format&fit=crop&w=600&q=80', 'Comfortable and stylish maxi skirt.', 'S,M,L,XL', 'Purple,Green,Orange', 22, 0, 1),
(2, 'Yoga Pants', 'yoga-pants', 'DFS-WOM-007', 38000.00, 32000.00, 'https://images.unsplash.com/photo-1506629082847-11d3e392e467?auto=format&fit=crop&w=600&q=80', 'Stretchy and comfortable yoga pants.', 'XS,S,M,L,XL', 'Black,Gray,Purple,Blue', 40, 1, 1),
(2, 'Summer Shorts', 'summer-shorts', 'DFS-WOM-008', 28000.00, NULL, 'https://images.unsplash.com/photo-1565814008395-8e6de5f09a67?auto=format&fit=crop&w=600&q=80', 'Perfect summer shorts for hot weather.', 'S,M,L,XL', 'Khaki,Black,White', 30, 0, 0),
(2, 'Cardigan Sweater', 'cardigan-sweater', 'DFS-WOM-009', 45000.00, 40000.00, 'https://images.unsplash.com/photo-1551986782-d244ca0f914b?auto=format&fit=crop&w=600&q=80', 'Cozy cardigan sweater for layering.', 'S,M,L,XL,XXL', 'Brown,Cream,Gray,Navy', 25, 1, 0),
(2, 'Crop Top', 'crop-top', 'DFS-WOM-010', 22000.00, 18000.00, 'https://images.unsplash.com/photo-1506629082847-11d3e392e467?auto=format&fit=crop&w=600&q=80', 'Trendy crop top for casual wear.', 'S,M,L,XL', 'White,Black,Pink,Yellow', 35, 0, 1),
(2, 'Blazer Jacket', 'blazer-jacket', 'DFS-WOM-011', 72000.00, 64000.00, 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?auto=format&fit=crop&w=600&q=80', 'Professional blazer for work and formal occasions.', 'S,M,L,XL,XXL', 'Black,Navy,Gray', 18, 1, 0),
(2, 'Tunic Dress', 'tunic-dress', 'DFS-WOM-012', 36000.00, 30000.00, 'https://images.unsplash.com/photo-1595536371857-6cfc17bb9b52?auto=format&fit=crop&w=600&q=80', 'Versatile tunic dress for any occasion.', 'S,M,L,XL,XXL', 'Beige,Olive,Navy', 26, 0, 1),
(2, 'Fit & Flare Dress', 'fit-flare-dress', 'DFS-WOM-013', 52000.00, 46000.00, 'https://images.unsplash.com/photo-1595836369708-d1d6cc8b524c?auto=format&fit=crop&w=600&q=80', 'Flattering fit and flare dress.', 'S,M,L,XL', 'Floral,Navy,Red', 19, 1, 1),
(2, 'Linen Pants', 'linen-pants', 'DFS-WOM-014', 42000.00, 37000.00, 'https://images.unsplash.com/photo-1597588174514-d89a44e94923?auto=format&fit=crop&w=600&q=80', 'Comfortable linen pants for hot weather.', 'S,M,L,XL', 'White,Beige,Olive', 24, 0, 0),
(2, 'Silk Blouse', 'silk-blouse', 'DFS-WOM-015', 68000.00, 61000.00, 'https://images.unsplash.com/photo-1506629082847-11d3e392e467?auto=format&fit=crop&w=600&q=80', 'Elegant silk blouse for formal occasions.', 'S,M,L,XL', 'Champagne,Blush,Navy', 15, 1, 0),

-- MEN (Category 1) - 15 products
(1, 'Classic Men Shirt', 'classic-men-shirt', 'DFS-MEN-001', 35000.00, 31000.00, 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=600&q=80', 'Clean smart casual shirt for work and events.', 'M,L,XL,XXL', 'Blue,White,Black', 30, 1, 0),
(1, 'Formal Dress Shirt', 'formal-dress-shirt', 'DFS-MEN-002', 45000.00, 40000.00, 'https://images.unsplash.com/photo-1596455056769-12226f253b1e?auto=format&fit=crop&w=600&q=80', 'Premium formal dress shirt for business occasions.', 'S,M,L,XL,XXL', 'White,Light Blue,Navy', 22, 1, 0),
(1, 'Polo Shirt', 'polo-shirt', 'DFS-MEN-003', 32000.00, 28000.00, 'https://images.unsplash.com/photo-1586308409274-75d440642117?auto=format&fit=crop&w=600&q=80', 'Classic polo shirt for casual elegance.', 'M,L,XL,XXL', 'Red,Blue,White,Black', 35, 1, 1),
(1, 'T-Shirt Basic', 't-shirt-basic', 'DFS-MEN-004', 18000.00, 15000.00, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=600&q=80', 'Basic comfortable t-shirt for everyday wear.', 'S,M,L,XL,XXL', 'White,Black,Gray,Navy', 50, 0, 1),
(1, 'Denim Jeans', 'denim-jeans', 'DFS-MEN-005', 55000.00, 49000.00, 'https://images.unsplash.com/photo-1542272604-787c62d465d1?auto=format&fit=crop&w=600&q=80', 'Classic denim jeans for everyday wear.', '28,30,32,34,36,38', 'Dark Blue,Light Blue,Black', 45, 1, 1),
(1, 'Chino Pants', 'chino-pants', 'DFS-MEN-006', 42000.00, 37000.00, 'https://images.unsplash.com/photo-1473080169176-2a84a40d96e2?auto=format&fit=crop&w=600&q=80', 'Versatile chino pants for casual and formal wear.', '28,30,32,34,36,38,40', 'Khaki,Navy,Gray,Olive', 40, 1, 0),
(1, 'Blazer Suit', 'blazer-suit', 'DFS-MEN-007', 85000.00, 76000.00, 'https://images.unsplash.com/photo-1591195853830-cd0b32787203?auto=format&fit=crop&w=600&q=80', 'Professional blazer for formal occasions.', 'S,M,L,XL,XXL', 'Black,Navy,Gray,Brown', 12, 1, 0),
(1, 'Casual Shorts', 'casual-shorts', 'DFS-MEN-008', 28000.00, 24000.00, 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=600&q=80', 'Comfortable shorts for hot weather.', 'S,M,L,XL,XXL', 'Khaki,Black,Navy,Olive', 32, 0, 1),
(1, 'Hoodie Sweatshirt', 'hoodie-sweatshirt', 'DFS-MEN-009', 48000.00, 42000.00, 'https://images.unsplash.com/photo-1556821552-5b6e6a4e5e1a?auto=format&fit=crop&w=600&q=80', 'Cozy hoodie for casual comfort.', 'S,M,L,XL,XXL', 'Black,Gray,Navy,White', 38, 1, 1),
(1, 'Sweater Crew Neck', 'sweater-crew', 'DFS-MEN-010', 52000.00, 46000.00, 'https://images.unsplash.com/photo-1591195853826-4b80f2653b38?auto=format&fit=crop&w=600&q=80', 'Classic crew neck sweater.', 'S,M,L,XL,XXL', 'Navy,Gray,Burgundy,Black', 28, 1, 0),
(1, 'Jacket Bomber', 'jacket-bomber', 'DFS-MEN-011', 65000.00, 58000.00, 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?auto=format&fit=crop&w=600&q=80', 'Trendy bomber jacket for stylish look.', 'S,M,L,XL,XXL', 'Black,Navy,Olive,Brown', 20, 1, 1),
(1, 'Oxford Shirt', 'oxford-shirt', 'DFS-MEN-012', 42000.00, 37000.00, 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=600&q=80', 'Classic oxford shirt for smart casual.', 'M,L,XL,XXL', 'White,Blue,Burgundy', 25, 0, 0),
(1, 'Cargo Pants', 'cargo-pants', 'DFS-MEN-013', 48000.00, 42000.00, 'https://images.unsplash.com/photo-1612873088269-ea34f5f9e468?auto=format&fit=crop&w=600&q=80', 'Practical cargo pants with multiple pockets.', '28,30,32,34,36,38,40', 'Khaki,Black,Navy', 22, 0, 1),
(1, 'Thermal Innerwear', 'thermal-innerwear', 'DFS-MEN-014', 25000.00, 20000.00, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=600&q=80', 'Warm thermal innerwear for cold weather.', 'S,M,L,XL,XXL', 'Black,White,Gray', 40, 0, 0),
(1, 'Dress Pants', 'dress-pants', 'DFS-MEN-015', 58000.00, 52000.00, 'https://images.unsplash.com/photo-1621295422519-c1e2a8ee8ded?auto=format&fit=crop&w=600&q=80', 'Premium dress pants for formal occasions.', '28,30,32,34,36,38,40', 'Black,Navy,Gray,Brown', 18, 1, 0),

-- SHOES (Category 3) - 15 products
(3, 'Street Sneakers', 'street-sneakers', 'DFS-SHO-001', 72000.00, 65000.00, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=600&q=80', 'Comfortable sneakers for everyday movement.', '39,40,41,42,43,44', 'White,Black,Red', 18, 1, 1),
(3, 'Running Shoes', 'running-shoes', 'DFS-SHO-002', 85000.00, 76000.00, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80', 'Professional running shoes for athletes.', '38,39,40,41,42,43,44,45', 'Black,Blue,Yellow', 14, 1, 0),
(3, 'Formal Shoes', 'formal-shoes', 'DFS-SHO-003', 68000.00, 61000.00, 'https://images.unsplash.com/photo-1592078615290-033ee584e267?auto=format&fit=crop&w=600&q=80', 'Elegant formal shoes for business occasions.', '38,39,40,41,42,43,44,45', 'Black,Brown,Tan', 16, 1, 0),
(3, 'Casual Loafers', 'casual-loafers', 'DFS-SHO-004', 55000.00, 49000.00, 'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?auto=format&fit=crop&w=600&q=80', 'Comfortable loafers for casual outings.', '38,39,40,41,42,43,44,45', 'Brown,Black,Navy', 20, 0, 1),
(3, 'Sandals Flip Flop', 'sandals-flip-flop', 'DFS-SHO-005', 18000.00, 15000.00, 'https://images.unsplash.com/photo-1565148666747-640ef573c4d3?auto=format&fit=crop&w=600&q=80', 'Perfect sandals for beach and casual wear.', '35,36,37,38,39,40,41,42,43,44,45', 'Black,Blue,Brown', 35, 0, 1),
(3, 'Boots Leather', 'boots-leather', 'DFS-SHO-006', 95000.00, 85000.00, 'https://images.unsplash.com/photo-1544126268-92ec8879eb15?auto=format&fit=crop&w=600&q=80', 'Premium leather boots for style and durability.', '38,39,40,41,42,43,44,45', 'Black,Brown', 10, 1, 0),
(3, 'Athletic Trainers', 'athletic-trainers', 'DFS-SHO-007', 78000.00, 70000.00, 'https://images.unsplash.com/photo-1514306688989-48e2b1ef3e3d?auto=format&fit=crop&w=600&q=80', 'Trainer shoes for sports and gym.', '38,39,40,41,42,43,44,45', 'Gray,Black,Blue', 22, 1, 1),
(3, 'Canvas Shoes', 'canvas-shoes', 'DFS-SHO-008', 42000.00, 37000.00, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80', 'Classic canvas shoes for everyday wear.', '38,39,40,41,42,43,44,45', 'White,Black,Gray,Blue', 28, 0, 0),
(3, 'Heels Formal', 'heels-formal', 'DFS-SHO-009', 65000.00, 58000.00, 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&w=600&q=80', 'Elegant heels for formal events.', '35,36,37,38,39,40,41,42', 'Black,Red,Gold', 12, 1, 1),
(3, 'Slip-On Shoes', 'slip-on-shoes', 'DFS-SHO-010', 48000.00, 42000.00, 'https://images.unsplash.com/photo-1542291017-d5a11d56e8c5?auto=format&fit=crop&w=600&q=80', 'Easy slip-on shoes for quick wear.', '38,39,40,41,42,43,44,45', 'Black,Navy,Brown', 24, 0, 1),
(3, 'Sports Cleats', 'sports-cleats', 'DFS-SHO-011', 88000.00, 79000.00, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80', 'Professional sports cleats for football.', '39,40,41,42,43,44', 'Black,White,Red', 15, 1, 0),
(3, 'Boat Shoes', 'boat-shoes', 'DFS-SHO-012', 58000.00, 52000.00, 'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?auto=format&fit=crop&w=600&q=80', 'Comfortable boat shoes for casual outings.', '38,39,40,41,42,43,44,45', 'Brown,Navy,Tan', 18, 0, 0),
(3, 'Platform Shoes', 'platform-shoes', 'DFS-SHO-013', 72000.00, 64000.00, 'https://images.unsplash.com/photo-1513999453826-ec7a0797dd8e?auto=format&fit=crop&w=600&q=80', 'Trendy platform shoes for style.', '35,36,37,38,39,40,41,42', 'Black,White,Pink', 14, 1, 1),
(3, 'Slippers Indoor', 'slippers-indoor', 'DFS-SHO-014', 22000.00, 18000.00, 'https://images.unsplash.com/photo-1565148666747-640ef573c4d3?auto=format&fit=crop&w=600&q=80', 'Cozy indoor slippers for comfort.', '36,37,38,39,40,41,42,43,44,45', 'Gray,Black,White,Blue', 40, 0, 1),
(3, 'Hiking Boots', 'hiking-boots', 'DFS-SHO-015', 98000.00, 88000.00, 'https://images.unsplash.com/photo-1553539292-494e22207b8e?auto=format&fit=crop&w=600&q=80', 'Durable hiking boots for outdoor adventures.', '38,39,40,41,42,43,44,45,46', 'Brown,Black,Gray', 11, 1, 0),

-- BAGS (Category 4) - 10 products
(4, 'Everyday Tote Bag', 'everyday-tote-bag', 'DFS-BAG-001', 42000.00, 37000.00, 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=600&q=80', 'Durable tote bag for shopping, work, and travel.', 'One Size', 'Brown,Black,Tan', 16, 1, 0),
(4, 'Backpack School', 'backpack-school', 'DFS-BAG-002', 38000.00, 34000.00, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80', 'Comfortable backpack for school and work.', 'One Size', 'Blue,Black,Gray,Red', 28, 1, 1),
(4, 'Handbag Leather', 'handbag-leather', 'DFS-BAG-003', 68000.00, 61000.00, 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=600&q=80', 'Premium leather handbag for style.', 'One Size', 'Black,Brown,Red', 12, 1, 0),
(4, 'Crossbody Bag', 'crossbody-bag', 'DFS-BAG-004', 35000.00, 31000.00, 'https://images.unsplash.com/photo-1591522521140-f9bd7f3f149e?auto=format&fit=crop&w=600&q=80', 'Convenient crossbody bag for everyday use.', 'One Size', 'Black,White,Tan', 22, 0, 1),
(4, 'Shoulder Bag', 'shoulder-bag', 'DFS-BAG-005', 45000.00, 40000.00, 'https://images.unsplash.com/photo-1594223274512-ad4803739b7c?auto=format&fit=crop&w=600&q=80', 'Stylish shoulder bag for work and leisure.', 'One Size', 'Navy,Black,Burgundy', 18, 1, 0),
(4, 'Clutch Evening', 'clutch-evening', 'DFS-BAG-006', 32000.00, 28000.00, 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?auto=format&fit=crop&w=600&q=80', 'Elegant clutch for evening events.', 'One Size', 'Gold,Silver,Black', 15, 1, 1),
(4, 'Duffel Bag Travel', 'duffel-travel', 'DFS-BAG-007', 58000.00, 52000.00, 'https://images.unsplash.com/photo-1547996309-4898e12ce9d9?auto=format&fit=crop&w=600&q=80', 'Spacious duffel bag for travel.', 'One Size', 'Black,Gray,Navy', 14, 0, 1),
(4, 'Sling Bag', 'sling-bag', 'DFS-BAG-008', 28000.00, 24000.00, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80', 'Compact sling bag for on-the-go.', 'One Size', 'Black,Brown,Navy', 26, 0, 0),
(4, 'Laptop Backpack', 'laptop-backpack', 'DFS-BAG-009', 68000.00, 61000.00, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80', 'Professional laptop backpack with padding.', 'One Size', 'Black,Gray,Navy', 16, 1, 0),
(4, 'Wallet Coin Purse', 'wallet-purse', 'DFS-BAG-010', 15000.00, 12000.00, 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=600&q=80', 'Compact wallet for coins and cards.', 'One Size', 'Black,Brown,Red,Blue', 45, 0, 1),

-- ACCESSORIES (Category 5) - 10 products
(5, 'Minimal Wrist Watch', 'minimal-wrist-watch', 'DFS-ACC-001', 44000.00, 39000.00, 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?auto=format&fit=crop&w=600&q=80', 'Simple watch that works with formal and casual outfits.', 'One Size', 'Gold,Black,Silver', 12, 0, 1),
(5, 'Digital Smartwatch', 'digital-smartwatch', 'DFS-ACC-002', 95000.00, 85000.00, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=600&q=80', 'Modern smartwatch with health tracking.', 'One Size', 'Black,Silver,Blue', 10, 1, 0),
(5, 'Sunglasses UV', 'sunglasses-uv', 'DFS-ACC-003', 45000.00, 40000.00, 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?auto=format&fit=crop&w=600&q=80', 'Stylish sunglasses with UV protection.', 'One Size', 'Black,Brown,Gold', 24, 1, 1),
(5, 'Earrings Stud', 'earrings-stud', 'DFS-ACC-004', 18000.00, 15000.00, 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=600&q=80', 'Classic stud earrings for everyday wear.', 'One Size', 'Gold,Silver,Rose Gold', 32, 0, 1),
(5, 'Necklace Pendant', 'necklace-pendant', 'DFS-ACC-005', 35000.00, 31000.00, 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=600&q=80', 'Elegant pendant necklace.', 'One Size', 'Gold,Silver,Rose Gold', 20, 1, 0),
(5, 'Bracelet Bangle', 'bracelet-bangle', 'DFS-ACC-006', 25000.00, 22000.00, 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=600&q=80', 'Stylish bangle bracelet.', 'One Size', 'Gold,Silver,Copper', 28, 0, 1),
(5, 'Scarf Silk', 'scarf-silk', 'DFS-ACC-007', 32000.00, 28000.00, 'https://images.unsplash.com/photo-1503341455253-b2e723bb12dd?auto=format&fit=crop&w=600&q=80', 'Premium silk scarf for elegant look.', 'One Size', 'Multicolor,Solid Colors', 18, 1, 0),
(5, 'Belt Leather', 'belt-leather', 'DFS-ACC-008', 28000.00, 24000.00, 'https://images.unsplash.com/photo-1533631813352-82f8c663149f?auto=format&fit=crop&w=600&q=80', 'Quality leather belt for any outfit.', 'M,L,XL', 'Brown,Black,Tan', 26, 0, 1),
(5, 'Hat Baseball', 'hat-baseball', 'DFS-ACC-009', 20000.00, 17000.00, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=600&q=80', 'Classic baseball cap for casual wear.', 'One Size', 'Black,Navy,White,Red', 35, 0, 0),
(5, 'Hair Clip Set', 'hair-clip-set', 'DFS-ACC-010', 15000.00, 12000.00, 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=600&q=80', 'Set of stylish hair clips.', 'One Size', 'Black,Gold,Silver,Rose Gold', 42, 0, 1);

INSERT INTO product_images (product_id, image_url, alt_text, sort_order) VALUES
(1, 'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=900&q=80', 'Linen summer dress main view', 1),
(1, 'https://images.unsplash.com/photo-1485968579580-b6d095142e6e?auto=format&fit=crop&w=600&q=80', 'Linen summer dress styled view', 2),
(3, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=900&q=80', 'Street sneakers main view', 1),
(4, 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=900&q=80', 'Everyday tote bag main view', 1);

INSERT INTO orders
(order_number, user_id, customer_name, customer_email, customer_phone, shipping_address, subtotal, delivery_fee, total, status)
VALUES
('DFS-1001', 2, 'Amina Hassan', 'amina@example.com', '+255711111111', 'Mikocheni, Dar es Salaam', 120000.00, 5000.00, 125000.00, 'delivered'),
('DFS-1002', 3, 'Ignas Kipchoge', 'ignas@example.com', '+255722333444', 'Upanga, Dar es Salaam', 135000.00, 5000.00, 140000.00, 'in_transit'),
('DFS-1003', 4, 'Fatima Mohamed', 'fatima@example.com', '+255733444555', 'Mbeya, Tanzania', 95000.00, 12000.00, 107000.00, 'shipped'),
('DFS-1004', 5, 'James Wilson', 'james@example.com', '+255744555666', 'Morogoro, Tanzania', 180000.00, 8000.00, 188000.00, 'processing'),
('DFS-1005', 6, 'Zainab Ali', 'zainab@example.com', '+255755666777', 'Ilala, Dar es Salaam', 72000.00, 5000.00, 77000.00, 'confirmed'),
('DFS-1006', 7, 'Mwangi Njoroge', 'mwangi@example.com', '+255766777888', 'Mwanza, Tanzania', 165000.00, 12000.00, 177000.00, 'delivered'),
('DFS-1007', 8, 'Naomi Okafor', 'naomi@example.com', '+255777888999', 'Kinondoni, Dar es Salaam', 220000.00, 5000.00, 225000.00, 'out_for_delivery'),
('DFS-1008', 2, 'Amina Hassan', 'amina@example.com', '+255711111111', 'Mikocheni, Dar es Salaam', 102000.00, 5000.00, 107000.00, 'pending'),
('DFS-1009', 3, 'Ignas Kipchoge', 'ignas@example.com', '+255722333444', 'Upanga, Dar es Salaam', 85000.00, 5000.00, 90000.00, 'delivered'),
('DFS-1010', 4, 'Fatima Mohamed', 'fatima@example.com', '+255733444555', 'Mbeya, Tanzania', 156000.00, 12000.00, 168000.00, 'processing');

INSERT INTO order_items (order_id, product_id, product_name, quantity, price, line_total) VALUES
(1, 1, 'Linen Summer Dress', 1, 48000.00, 48000.00),
(1, 3, 'Street Sneakers', 1, 72000.00, 72000.00),
(2, 2, 'Formal Dress Shirt', 1, 45000.00, 45000.00),
(2, 5, 'Denim Jeans', 1, 55000.00, 55000.00),
(2, 16, 'Running Shoes', 1, 85000.00, 85000.00),
(3, 9, 'Evening Gown', 1, 95000.00, 95000.00),
(4, 4, 'Polo Shirt', 2, 32000.00, 64000.00),
(4, 22, 'Handbag Leather', 1, 68000.00, 68000.00),
(4, 28, 'Digital Smartwatch', 1, 95000.00, 95000.00),
(5, 6, 'Denim Jacket', 1, 62000.00, 62000.00),
(5, 20, 'Sandals Flip Flop', 1, 18000.00, 18000.00),
(6, 11, 'Hoodie Sweatshirt', 1, 48000.00, 48000.00),
(6, 17, 'Casual Loafers', 1, 55000.00, 55000.00),
(6, 31, 'Sunglasses UV', 1, 45000.00, 45000.00),
(6, 40, 'Scarf Silk', 1, 32000.00, 32000.00),
(7, 7, 'Yoga Pants', 1, 38000.00, 38000.00),
(7, 15, 'Oxford Shirt', 1, 42000.00, 42000.00),
(7, 23, 'Crossbody Bag', 1, 35000.00, 35000.00),
(7, 28, 'Digital Smartwatch', 1, 95000.00, 95000.00),
(7, 36, 'Necklace Pendant', 1, 35000.00, 35000.00),
(8, 8, 'Summer Shorts', 1, 28000.00, 28000.00),
(8, 19, 'Athletic Trainers', 1, 78000.00, 78000.00),
(9, 2, 'Formal Dress Shirt', 1, 45000.00, 45000.00),
(9, 18, 'Formal Shoes', 1, 68000.00, 68000.00),
(9, 32, 'Earrings Stud', 1, 18000.00, 18000.00),
(10, 12, 'Sweater Crew Neck', 1, 52000.00, 52000.00),
(10, 25, 'Boots Leather', 1, 95000.00, 95000.00),
(10, 34, 'Clutch Evening', 1, 32000.00, 32000.00);

INSERT INTO payments (order_id, method, amount, payment_status, transaction_ref, provider_response, paid_at) VALUES
(1, 'mobile_money', 125000.00, 'verified', 'DEMO-MPESA-1001', 'Demo payment approved for class presentation.', NOW()),
(2, 'mobile_money', 140000.00, 'verified', 'DEMO-MPESA-1002', 'Payment verified.', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'card_demo', 107000.00, 'verified', 'DEMO-CARD-1003', 'Card payment approved.', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 'mobile_money', 188000.00, 'pending', 'DEMO-MPESA-1004', 'Payment pending verification.', NULL),
(5, 'cash_on_delivery', 77000.00, 'pending', 'COD-1005', 'Cash on delivery - pending collection.', NULL),
(6, 'mobile_money', 177000.00, 'verified', 'DEMO-MPESA-1006', 'Payment verified.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(7, 'card_demo', 225000.00, 'verified', 'DEMO-CARD-1007', 'Card payment approved.', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(8, 'mobile_money', 107000.00, 'pending', 'DEMO-MPESA-1008', 'Payment pending verification.', NULL),
(9, 'mobile_money', 90000.00, 'verified', 'DEMO-MPESA-1009', 'Payment verified.', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(10, 'card_demo', 168000.00, 'verified', 'DEMO-CARD-1010', 'Card payment approved.', DATE_SUB(NOW(), INTERVAL 4 DAY));

INSERT INTO order_confirmations (order_id, channel, recipient, subject, message, status, sent_at) VALUES
(1, 'email', 'amina@example.com', 'Dar Fashion Store Order Confirmation', 'Thank you for your order DFS-1001. Your payment has been verified and your items have been delivered.', 'sent', NOW()),
(2, 'email', 'ignas@example.com', 'Dar Fashion Store Order Confirmation', 'Thank you for your order DFS-1002. Your payment has been verified and your items are in transit.', 'sent', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'email', 'fatima@example.com', 'Dar Fashion Store Order Confirmation', 'Thank you for your order DFS-1003. Your payment has been verified and your items have been shipped.', 'sent', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, 'email', 'james@example.com', 'Dar Fashion Store Order Confirmation', 'Thank you for your order DFS-1004. Your payment is being processed.', 'sent', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 'email', 'zainab@example.com', 'Dar Fashion Store Order Confirmation', 'Thank you for your order DFS-1005. Payment confirmed for cash on delivery.', 'sent', NOW()),
(6, 'email', 'mwangi@example.com', 'Dar Fashion Store Order Confirmation', 'Thank you for your order DFS-1006. Your payment has been verified and your items have been delivered.', 'sent', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(7, 'email', 'naomi@example.com', 'Dar Fashion Store Order Confirmation', 'Thank you for your order DFS-1007. Your payment has been verified and your items are out for delivery.', 'sent', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(8, 'email', 'amina@example.com', 'Dar Fashion Store Order Confirmation', 'Thank you for your order DFS-1008. Your payment is being verified.', 'sent', NOW()),
(9, 'email', 'ignas@example.com', 'Dar Fashion Store Order Confirmation', 'Thank you for your order DFS-1009. Your payment has been verified and your items have been delivered.', 'sent', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(10, 'email', 'fatima@example.com', 'Dar Fashion Store Order Confirmation', 'Thank you for your order DFS-1010. Your payment has been verified and your items are being processed.', 'sent', DATE_SUB(NOW(), INTERVAL 4 DAY));

UPDATE orders SET delivery_company = 'Dar Express Courier', delivery_notes = 'Delivered successfully.', estimated_delivery_days = 1 WHERE order_number = 'DFS-1001';
UPDATE orders SET delivery_company = 'DPL Logistics', delivery_notes = 'In transit from warehouse to destination.', estimated_delivery_days = 1 WHERE order_number = 'DFS-1002';
UPDATE orders SET delivery_company = 'Tanzania Courier', delivery_notes = 'Package shipped and on the way.', estimated_delivery_days = 3 WHERE order_number = 'DFS-1003';
UPDATE orders SET delivery_company = 'Dar Express Courier', delivery_notes = 'Items being packed at warehouse.', estimated_delivery_days = 1 WHERE order_number = 'DFS-1004';
UPDATE orders SET delivery_company = 'Local Courier', delivery_notes = 'Order confirmed, waiting for payment at delivery.', estimated_delivery_days = 1 WHERE order_number = 'DFS-1005';
UPDATE orders SET delivery_company = 'DPL Logistics', delivery_notes = 'Delivered successfully.', estimated_delivery_days = 3 WHERE order_number = 'DFS-1006';
UPDATE orders SET delivery_company = 'Tanzania Courier', delivery_notes = 'Out for delivery today.', estimated_delivery_days = 1 WHERE order_number = 'DFS-1007';
UPDATE orders SET delivery_company = 'Dar Express Courier', delivery_notes = 'Order placed, awaiting processing.', estimated_delivery_days = 1 WHERE order_number = 'DFS-1008';
UPDATE orders SET delivery_company = 'Local Courier', delivery_notes = 'Delivered successfully.', estimated_delivery_days = 1 WHERE order_number = 'DFS-1009';
UPDATE orders SET delivery_company = 'DPL Logistics', delivery_notes = 'Items being prepared and packed.', estimated_delivery_days = 2 WHERE order_number = 'DFS-1010';

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
(4, NULL, 'Neema', 4, 'The bag is strong and looks professional.'),
(2, 3, 'Ignas Kipchoge', 4, 'Great quality shirt for formal wear.'),
(5, 4, 'Fatima Mohamed', 5, 'Perfect jeans, very comfortable fit.'),
(6, 5, 'James Wilson', 5, 'Excellent jacket, great value for money.'),
(9, 6, 'Zainab Ali', 4, 'Love this hoodie, very cozy and warm.'),
(11, 7, 'Mwangi Njoroge', 5, 'Amazing quality, exceeded my expectations.'),
(17, 8, 'Naomi Okafor', 4, 'Comfortable loafers, perfect for work.'),
(1, NULL, 'Customer One', 5, 'Beautiful dress, arrived as described.'),
(2, NULL, 'Customer Two', 4, 'Good quality, a bit loose fit.'),
(7, NULL, 'Customer Three', 5, 'Very comfortable yoga pants.'),
(10, NULL, 'Customer Four', 3, 'Nice hoodie but runs small.'),
(18, NULL, 'Customer Five', 5, 'Great shoes, very professional look.'),
(28, NULL, 'Customer Six', 4, 'Excellent smartwatch, love the features.');

-- Sample wishlist data
INSERT INTO user_wishlist (user_id, product_id) VALUES
(2, 8),
(2, 11),
(2, 28),
(3, 9),
(3, 12),
(4, 16),
(4, 20),
(5, 22),
(5, 25),
(6, 31),
(6, 32),
(7, 6),
(7, 15),
(8, 1),
(8, 5);

-- Sample user addresses
INSERT INTO user_addresses (user_id, label, fullname, phone, region, city, address, postal_code, is_default) VALUES
(2, 'Home', 'Amina Hassan', '+255711111111', 'Dar es Salaam', 'Dar es Salaam', 'Mikocheni St, House 45', '12345', 1),
(2, 'Work', 'Amina Hassan', '+255711111111', 'Dar es Salaam', 'Dar es Salaam', 'City Centre, Office 201', '12346', 0),
(3, 'Home', 'Ignas Kipchoge', '+255722333444', 'Dar es Salaam', 'Dar es Salaam', 'Upanga Rd, Apt 12', '12347', 1),
(4, 'Home', 'Fatima Mohamed', '+255733444555', 'Mbeya', 'Mbeya', 'Mpiana Mpiana, House 22', '12348', 1),
(4, 'Family', 'Fatima Mohamed', '+255733444555', 'Mbeya', 'Mbeya', 'Rungwe Rd, House 88', '12349', 0),
(5, 'Home', 'James Wilson', '+255744555666', 'Dar es Salaam', 'Dar es Salaam', 'Oysterbay, Villa 5', '12350', 1),
(6, 'Home', 'Zainab Ali', '+255755666777', 'Dar es Salaam', 'Dar es Salaam', 'Ilala District, House 33', '12351', 1),
(7, 'Home', 'Mwangi Njoroge', '+255766777888', 'Mwanza', 'Mwanza', 'Ilemela, House 15', '12352', 1),
(8, 'Home', 'Naomi Okafor', '+255777888999', 'Dar es Salaam', 'Dar es Salaam', 'Kinondoni, Apt 8', '12353', 1);

-- Sample coupons for users
INSERT INTO user_coupons (user_id, code, discount_percentage, description, valid_from, valid_until, min_purchase, status) VALUES
(2, 'AMINA20', 20.00, 'Welcome coupon - 20% off', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 50000.00, 'active'),
(2, 'SAVE10', 10.00, 'Summer sale - 10% off', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 25 DAY), 0.00, 'active'),
(3, 'IGNAS15', 15.00, 'First time buyer - 15% off', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 75000.00, 'active'),
(4, 'FATIMA25', 25.00, 'VIP discount - 25% off', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 100000.00, 'active'),
(5, 'WELCOME30', 30.00, 'New customer - 30% off', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0.00, 'active'),
(6, 'ZAINAB20', 20.00, 'Loyalty reward - 20% off', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 50000.00, 'active'),
(7, 'MWANGI10', 10.00, 'Regular customer - 10% off', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 50000.00, 'active'),
(8, 'NAOMI15', 15.00, 'Special offer - 15% off', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 75000.00, 'active');
