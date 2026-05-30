<?php
$adminPage = $adminPage ?? '';
$adminRoot = $adminRoot ?? '';

$navItems = [
    'dashboard' => ['label' => 'Dashboard', 'href' => $adminRoot . 'dashboard.php'],
    'analytics' => ['label' => 'Analytics', 'href' => $adminRoot . 'analytics/index.php'],
    'users' => ['label' => 'Users & Accounts', 'href' => $adminRoot . 'users/index.php'],
    'orders' => ['label' => 'Orders', 'href' => $adminRoot . 'orders/index.php'],
    'products' => ['label' => 'Products', 'href' => $adminRoot . 'products/index.php'],
    'categories' => ['label' => 'Categories', 'href' => $adminRoot . 'categories/index.php'],
    'payments' => ['label' => 'Payments', 'href' => $adminRoot . 'payments/index.php'],
    'inventory' => ['label' => 'Inventory', 'href' => $adminRoot . 'inventory/index.php'],
    'activities' => ['label' => 'Activity Log', 'href' => $adminRoot . 'activities/index.php'],
    'reports' => ['label' => 'Reports', 'href' => $adminRoot . 'reports/index.php'],
    'messages' => ['label' => 'Messages', 'href' => $adminRoot . 'messages/index.php'],
    'settings' => ['label' => 'Store Settings', 'href' => $adminRoot . 'settings/index.php'],
];
?>
<aside class="admin-sidebar">
    <a class="logo" href="<?php echo $adminRoot; ?>dashboard.php">
        <span class="logo__mark">DF</span>
        <span class="logo__text">Admin Panel</span>
    </a>
    <p class="admin-sidebar__hint">Store operations — separate from customer accounts</p>
    <nav>
        <?php foreach ($navItems as $key => $item): ?>
            <a href="<?php echo htmlspecialchars($item['href']); ?>" class="<?php echo $adminPage === $key ? 'is-active' : ''; ?>">
                <?php echo htmlspecialchars($item['label']); ?>
            </a>
        <?php endforeach; ?>
        <a href="<?php echo $adminRoot; ?>logout.php" class="admin-nav-logout" style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 16px;">Logout</a>
    </nav>
</aside>
