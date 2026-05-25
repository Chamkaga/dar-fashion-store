<?php
require_once __DIR__ . '/../../app/includes/auth.php';
require_admin('../login.php');
require_once __DIR__ . '/../../app/config/db.php';
$conn = (new Database())->connect();
$settings = [];
$regions = [];
if ($conn) {
    try {
        $settings = $conn->query("SELECT * FROM store_settings ORDER BY setting_key")->fetchAll(PDO::FETCH_ASSOC);
        $regions = $conn->query("SELECT * FROM delivery_regions ORDER BY region_name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $settings = [
            ['setting_key' => 'store_name', 'setting_value' => 'Dar Fashion Store', 'updated_at' => date('Y-m-d H:i:s')],
            ['setting_key' => 'contact_phone', 'setting_value' => '+255700000000', 'updated_at' => date('Y-m-d H:i:s')],
            ['setting_key' => 'payment_methods', 'setting_value' => 'M-Pesa, Card Demo, Cash on Delivery', 'updated_at' => date('Y-m-d H:i:s')],
        ];
        $regions = [
            ['region_name' => 'Dar es Salaam', 'delivery_fee' => 5000, 'estimated_days' => '1 day', 'delivery_method' => 'Local courier'],
            ['region_name' => 'Morogoro', 'delivery_fee' => 8000, 'estimated_days' => '2 days', 'delivery_method' => 'Road courier'],
            ['region_name' => 'Mwanza', 'delivery_fee' => 12000, 'estimated_days' => '3-4 days', 'delivery_method' => 'Road courier'],
            ['region_name' => 'Zanzibar', 'delivery_fee' => 10000, 'estimated_days' => '2 days', 'delivery_method' => 'Sea delivery'],
        ];
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Settings</title><link rel="stylesheet" href="../../assets/css/style.css"><link rel="stylesheet" href="../../assets/css/responsive.css"></head>
<body><main class="section"><div class="container"><div class="section-heading"><p class="section-kicker">Admin</p><h1>Settings</h1></div><section class="admin-panel"><h2>Store Settings</h2><div class="cart-table"><div class="cart-row cart-row--head"><span>Setting</span><span>Value</span><span>Area</span><span>Status</span><span>Updated</span></div><?php foreach ($settings as $setting): ?><div class="cart-row"><span><?php echo htmlspecialchars($setting['setting_key']); ?></span><span><?php echo htmlspecialchars($setting['setting_value']); ?></span><span>Store</span><span>Active</span><span><?php echo htmlspecialchars(date('M d, Y', strtotime($setting['updated_at']))); ?></span></div><?php endforeach; ?></div></section><section class="admin-panel"><h2>Delivery Fees per Region</h2><div class="cart-table"><div class="cart-row cart-row--head"><span>Region</span><span>Fee</span><span>ETA</span><span>Method</span><span>Status</span></div><?php foreach ($regions as $region): ?><div class="cart-row"><span><?php echo htmlspecialchars($region['region_name']); ?></span><span>TZS <?php echo number_format((float) $region['delivery_fee'], 0); ?></span><span><?php echo htmlspecialchars($region['estimated_days']); ?></span><span><?php echo htmlspecialchars($region['delivery_method']); ?></span><span>Active</span></div><?php endforeach; ?></div></section><p><a href="../dashboard.php">Back to dashboard</a></p></div></main></body></html>
