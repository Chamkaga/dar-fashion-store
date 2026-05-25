<?php
/**
 * Database Setup Script
 * Imports schema and demo data from database/ecommerce.sql
 */

require_once __DIR__ . '/app/includes/env.php';
load_env_file(__DIR__ . '/.env');

$dbHost = app_env('DB_HOST', 'localhost');
$dbName = app_env('DB_NAME', 'fashion_storedb');
$dbUser = app_env('DB_USER', 'root');
$dbPassword = app_env('DB_PASSWORD', '');

echo "==== Dar Fashion Store - Database Setup ====\n\n";
echo "Configuration:\n";
echo "- Host: $dbHost\n";
echo "- Database: $dbName\n";
echo "- User: $dbUser\n\n";

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ Connected to MySQL server\n\n";
} catch (PDOException $e) {
    echo "✗ Failed to connect to MySQL: " . $e->getMessage() . "\n";
    echo "Copy .env.example to .env and set DB_PASSWORD to your MySQL password.\n";
    exit(1);
}

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$dbName' ready\n";
    $pdo->exec("USE `$dbName`");
    echo "✓ Selected database '$dbName'\n\n";
} catch (PDOException $e) {
    echo "✗ Database setup failed: " . $e->getMessage() . "\n";
    exit(1);
}

$sqlFile = __DIR__ . '/database/ecommerce.sql';
if (!file_exists($sqlFile)) {
    echo "✗ SQL file not found: $sqlFile\n";
    exit(1);
}

echo "Importing database schema...\n";

$sql = file_get_contents($sqlFile);
$sql = preg_replace('/^--.*$/m', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

$statements = preg_split('/;\s*\n/', $sql);
$count = 0;
$errors = 0;

foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement === '') {
        continue;
    }

    try {
        $pdo->exec($statement);
        $count++;
    } catch (PDOException $e) {
        $errors++;
        echo "✗ Error: " . $e->getMessage() . "\n";
        echo "  Statement: " . substr(str_replace("\n", ' ', $statement), 0, 120) . "...\n";
    }
}

echo "✓ Executed $count SQL statements";
if ($errors > 0) {
    echo " ($errors failed)";
}
echo "\n";

$tables = [
    'users',
    'categories',
    'products',
    'orders',
    'order_items',
    'payments',
    'reviews',
    'user_wishlist',
    'user_addresses',
    'user_coupons',
    'user_returns',
    'user_activities',
    'order_confirmations',
];

$result = $pdo->query('SHOW TABLES');
$existingTables = $result->fetchAll(PDO::FETCH_COLUMN);

echo "\nVerifying tables:\n";
$allPresent = true;
foreach ($tables as $table) {
    if (in_array($table, $existingTables, true)) {
        echo "✓ Table: $table\n";
    } else {
        echo "✗ Table: $table (MISSING)\n";
        $allPresent = false;
    }
}

echo "\nVerifying demo data:\n";
$checks = [
    'users' => 'SELECT COUNT(*) FROM users',
    'products' => 'SELECT COUNT(*) FROM products',
    'orders' => 'SELECT COUNT(*) FROM orders',
];

foreach ($checks as $table => $query) {
    try {
        $count = (int) $pdo->query($query)->fetchColumn();
        echo "✓ $table: $count records\n";
    } catch (Exception $e) {
        echo "✗ $table: " . $e->getMessage() . "\n";
        $allPresent = false;
    }
}

if ($allPresent) {
    echo "\n✅ Database setup completed successfully!\n";
    echo "\nDemo credentials (password: password123):\n";
    echo "- amina@example.com\n";
    echo "- admin@darfashion.store (admin)\n";
    exit(0);
}

echo "\n⚠️  Setup incomplete. Check MySQL credentials in .env and re-run.\n";
exit(1);
