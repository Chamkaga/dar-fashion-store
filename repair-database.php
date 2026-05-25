<?php
/**
 * Repairs partial databases by creating missing profile/activity tables.
 * Safe to run multiple times — does not drop existing data.
 */

require_once __DIR__ . '/app/includes/env.php';
load_env_file(__DIR__ . '/.env');

$dbHost = app_env('DB_HOST', 'localhost');
$dbName = app_env('DB_NAME', 'fashion_storedb');
$dbUser = app_env('DB_USER', 'root');
$dbPassword = app_env('DB_PASSWORD', '');

echo "==== Dar Fashion Store - Database Repair ====\n\n";

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

$sqlFile = __DIR__ . '/database/patch_missing_tables.sql';
$sql = file_get_contents($sqlFile);
$statements = preg_split('/;\s*\n/', $sql);

foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement === '') {
        continue;
    }
    $pdo->exec($statement);
    if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/i', $statement, $m)) {
        echo "✓ Table ready: {$m[1]}\n";
    }
}

function seed_if_empty(PDO $pdo, string $table, string $insertSql) {
    $count = (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    if ($count > 0) {
        echo "  (skipped demo data for $table — already has $count rows)\n";
        return;
    }
    try {
        $pdo->exec($insertSql);
        echo "  ✓ Seeded demo data for $table\n";
    } catch (PDOException $e) {
        echo "  ⚠ Could not seed $table: " . $e->getMessage() . "\n";
    }
}

echo "\nSeeding demo profile data (only if tables are empty)...\n";

$productCount = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
if ($productCount > 0) {
    $wishlistCount = (int) $pdo->query("SELECT COUNT(*) FROM user_wishlist")->fetchColumn();
    if ($wishlistCount === 0) {
        try {
            $pdo->exec("
                INSERT INTO user_wishlist (user_id, product_id)
                SELECT u.id, p.id
                FROM users u
                JOIN products p ON p.id <= LEAST(3, $productCount)
                WHERE u.role = 'customer'
                LIMIT 24
            ");
            echo "  ✓ Seeded wishlist from existing products\n";
        } catch (PDOException $e) {
            echo "  ⚠ Could not seed user_wishlist: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  (skipped demo data for user_wishlist — already has $wishlistCount rows)\n";
    }
}

$addressCount = (int) $pdo->query("SELECT COUNT(*) FROM user_addresses")->fetchColumn();
if ($addressCount === 0) {
    try {
        $pdo->exec("
            INSERT INTO user_addresses (user_id, label, fullname, phone, region, city, address, postal_code, is_default)
            SELECT id, 'Home', fullname, COALESCE(phone, '+255700000000'), 'Dar es Salaam', 'Dar es Salaam',
                   CONCAT('Delivery address for ', fullname), '00000', 1
            FROM users
            WHERE role = 'customer'
        ");
        echo "  ✓ Seeded addresses for existing customers\n";
    } catch (PDOException $e) {
        echo "  ⚠ Could not seed user_addresses: " . $e->getMessage() . "\n";
    }
} else {
    echo "  (skipped demo data for user_addresses — already has $addressCount rows)\n";
}

$couponCount = (int) $pdo->query("SELECT COUNT(*) FROM user_coupons")->fetchColumn();
if ($couponCount === 0) {
    try {
        $pdo->exec("
            INSERT INTO user_coupons (user_id, code, discount_percentage, description, valid_from, valid_until, min_purchase, status)
            SELECT id, CONCAT('SAVE', id), 15.00, 'Welcome coupon - 15% off',
                   DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0.00, 'active'
            FROM users
            WHERE role = 'customer'
        ");
        echo "  ✓ Seeded coupons for existing customers\n";
    } catch (PDOException $e) {
        echo "  ⚠ Could not seed user_coupons: " . $e->getMessage() . "\n";
    }
} else {
    echo "  (skipped demo data for user_coupons — already has $couponCount rows)\n";
}

echo "\nApplying order tracking patch...\n";
$trackingSql = __DIR__ . '/database/patch_order_tracking.sql';
if (file_exists($trackingSql)) {
    foreach (preg_split('/;\s*\n/', file_get_contents($trackingSql)) as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
    echo "✓ order_tracking_events table ready\n";

    require_once __DIR__ . '/app/models/OrderTracking.php';
    $tracking = new OrderTracking($pdo);
    $orders = $pdo->query("SELECT * FROM orders ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $order) {
        $existing = (int) $pdo->query("SELECT COUNT(*) FROM order_tracking_events WHERE order_id = " . (int) $order['id'])->fetchColumn();
        if ($existing > 0) {
            continue;
        }

        $tracking->seedInitialEvents($order['id'], $order);
        $status = $order['status'];
        $stepsToLog = ['confirmed', 'processing', 'picked_up', 'in_transit', 'arrived_at_hub', 'out_for_delivery', 'delivered'];
        $statusMap = [
            'pending' => [],
            'confirmed' => ['confirmed'],
            'processing' => ['confirmed', 'processing'],
            'shipped' => ['confirmed', 'processing', 'picked_up'],
            'in_transit' => ['confirmed', 'processing', 'picked_up', 'in_transit'],
            'out_for_delivery' => ['confirmed', 'processing', 'picked_up', 'in_transit', 'arrived_at_hub', 'out_for_delivery'],
            'delivered' => $stepsToLog,
        ];

        foreach ($statusMap[$status] ?? [] as $code) {
            $tracking->logEvent($order['id'], $code, [
                'created_by' => 'system',
                'event_at' => $order['created_at'],
            ]);
        }

        if ($status === 'in_transit' && stripos($order['shipping_address'], 'Mwanza') !== false) {
            $tracking->logEvent($order['id'], 'arrived_at_hub', [
                'location' => 'Mwanza distribution hub',
                'created_by' => 'system',
            ]);
        }
    }
    echo "✓ Demo tracking events seeded for orders without history\n";
}

echo "\n✅ Database repair completed.\n";
echo "Track demo: http://localhost:8000/public/track-order.php?order_number=DFS-1002&email=ignas@example.com\n";
