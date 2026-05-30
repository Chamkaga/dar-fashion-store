<?php
/**
 * Database Setup Script for Dar Fashion Store
 * Run this file to set up the database and populate it with sample data
 */

$host = 'localhost';
$dbname = 'fashion_storedb';
$username = 'root';
$password = 'Chamkaga@2025';

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.<br>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created or already exists.<br>";
    
    // Select the database
    $pdo->exec("USE `$dbname`");
    echo "Database selected.<br>";

    function executeSqlFile(PDO $pdo, string $filePath) {
        if (!file_exists($filePath)) {
            echo "SQL file not found: $filePath<br>";
            return;
        }

        $sql = file_get_contents($filePath);
        $lines = explode("\n", $sql);
        $statement = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || substr($line, 0, 2) === '--') {
                continue;
            }

            $statement .= ' ' . $line;
            if (substr($line, -1) === ';') {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        echo "Warning: " . $e->getMessage() . "<br>";
                    }
                }
                $statement = '';
            }
        }
    }

    // Read and execute the main schema SQL file
    $sqlFile = __DIR__ . '/database/ecommerce.sql';
    executeSqlFile($pdo, $sqlFile);
    echo "Database structure created successfully.<br>";

    // Apply database migrations to ensure all tables exist
    $migrationFiles = glob(__DIR__ . '/database/migrations/*.sql');
    sort($migrationFiles);
    foreach ($migrationFiles as $migrationFile) {
        echo "Applying migration: " . basename($migrationFile) . "<br>";
        executeSqlFile($pdo, $migrationFile);
    }
    echo "Database migrations applied successfully.<br>\n";

    // Insert sample data
    echo "<br>Inserting sample data...<br>";
    
    // Insert admin user
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (fullname, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
    $stmt->execute(['Admin User', 'admin@fashionstore.com', $adminPassword]);
    echo "Admin user created (email: admin@fashionstore.com, password: admin123)<br>";
    
    // Insert sample categories
    $categories = [
        ['Men\'s Clothing', 'mens-clothing', 'Clothing for men including shirts, pants, and jackets'],
        ['Women\'s Clothing', 'womens-clothing', 'Clothing for women including dresses, tops, and skirts'],
        ['Accessories', 'accessories', 'Fashion accessories including bags, jewelry, and watches'],
        ['Footwear', 'footwear', 'Shoes and sandals for all occasions'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)");
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
    echo "Sample categories inserted.<br>";
    
    // Insert sample products
    $products = [
        [1, 'Men\'s Casual Shirt', 'mens-casual-shirt', 'SKU001', 45000, 38000, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=400', 'Comfortable casual shirt for everyday wear', 'S,M,L,XL', 'Blue,Black,White', 50, 1, 0],
        [1, 'Men\'s Formal Shirt', 'mens-formal-shirt', 'SKU002', 55000, 0, 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=400', 'Formal shirt for office and special occasions', 'S,M,L,XL', 'White,Light Blue', 30, 0, 1],
        [2, 'Women\'s Summer Dress', 'womens-summer-dress', 'SKU003', 65000, 55000, 'https://images.unsplash.com/photo-1572804013309-59a88b7e92f1?w=400', 'Beautiful summer dress for warm weather', 'S,M,L', 'Red,Blue,Green', 25, 1, 1],
        [2, 'Women\'s Blouse', 'womens-blouse', 'SKU004', 35000, 0, 'https://images.unsplash.com/photo-1564257631407-4deb1f99d992?w=400', 'Elegant blouse for casual and formal wear', 'S,M,L,XL', 'White,Pink,Yellow', 40, 0, 0],
        [3, 'Leather Handbag', 'leather-handbag', 'SKU005', 85000, 75000, 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=400', 'Premium leather handbag with multiple compartments', 'One Size', 'Black,Brown,Tan', 15, 1, 1],
        [3, 'Fashion Watch', 'fashion-watch', 'SKU006', 120000, 0, 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?w=400', 'Stylish fashion watch for everyday wear', 'One Size', 'Silver,Gold,Rose Gold', 20, 0, 0],
        [4, 'Men\'s Sneakers', 'mens-sneakers', 'SKU007', 75000, 65000, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400', 'Comfortable sneakers for casual wear', '40,41,42,43,44', 'Black,White,Red', 35, 1, 1],
        [4, 'Women\'s Sandals', 'womens-sandals', 'SKU008', 45000, 0, 'https://images.unsplash.com/photo-1515347619252-60a6bf4fffce?w=400', 'Elegant sandals for summer and formal occasions', '36,37,38,39,40', 'Black,Gold,Silver', 45, 0, 0],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO products (category_id, name, slug, sku, price, sale_price, image, description, size_options, color_options, stock_quantity, is_featured, is_trending) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($products as $prod) {
        $stmt->execute($prod);
    }
    echo "Sample products inserted.<br>";
    
    // Insert sample delivery regions
    $regions = [
        ['Dar es Salaam', 5000, '1 day', 'Local courier'],
        ['Morogoro', 8000, '2 days', 'Road courier'],
        ['Mwanza', 12000, '3-4 days', 'Road courier'],
        ['Zanzibar', 10000, '2 days', 'Sea delivery'],
        ['Arusha', 15000, '3-5 days', 'Road courier'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO delivery_regions (region_name, delivery_fee, estimated_days, delivery_method) VALUES (?, ?, ?, ?)");
    foreach ($regions as $region) {
        $stmt->execute($region);
    }
    echo "Delivery regions inserted.<br>";
    
    // Insert sample store settings
    $settings = [
        ['store_name', 'Dar Fashion Store'],
        ['contact_phone', '+255700000000'],
        ['contact_email', 'info@fashionstore.com'],
        ['payment_methods', 'M-Pesa, Card Demo, Cash on Delivery'],
        ['currency', 'TZS'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO store_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    echo "Store settings inserted.<br>";
    
    echo "<br><strong>Database setup completed successfully!</strong><br>";
    echo "<br>You can now login to the admin panel:<br>";
    echo "Email: admin@fashionstore.com<br>";
    echo "Password: admin123<br>";
    echo "<br><a href='admin/login.php'>Go to Admin Login</a>";
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}
