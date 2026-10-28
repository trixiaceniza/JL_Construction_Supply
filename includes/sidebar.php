
<?php
require_once __DIR__ . '/auth.php';

$role = $_SESSION['role'] ?? 'staff';
$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

$dashboard = $role === 'owner'
    ? 'owner/dashboard.php'
    : 'staff/dashboard.php';

$menuItems = [
    [
        'label' => 'Dashboard',
        'icon' => '▦',
        'path' => $dashboard,
    ],
];

if ($role === 'owner') {
    $menuItems = array_merge($menuItems, [
        ['label' => 'Products',      'icon' => '▤', 'path' => 'owner/products.php'],
        ['label' => 'Inventory',     'icon' => '▥', 'path' => 'owner/inventory.php'],
        ['label' => 'Staff Accounts','icon' => '♙', 'path' => 'owner/staff.php'],
        ['label' => 'All Sales',     'icon' => '₱', 'path' => 'owner/sales.php'],
        ['label' => 'Reports',       'icon' => '▥', 'path' => 'owner/reports.php'],
    ]);
} else {
    $menuItems = array_merge($menuItems, [
        ['label' => 'Cashier / POS', 'icon' => '▣', 'path' => 'staff/pos.php'],
        ['label' => 'My Sales',      'icon' => '▤', 'path' => 'staff/sales_history.php'],
    ]);
}
?>

<aside class="sidebar" id="sidebar">
    <a class="sidebar-brand" href="<?= e(url($dashboard)) ?>">
        <span class="brand-icon">JL</span>
        <span class="sidebar-brand-text">
            <strong>J&L</strong>
            <small>CONSTRUCTION SUPPLY</small>
        </span>
    </a>

    <div class="sidebar-divider"></div>

    <p class="sidebar-section">MAIN MENU</p>

    <nav class="sidebar-nav">
        <?php foreach ($menuItems as $item): ?>
            <?php
            $isActive = str_ends_with(
                $currentPath,
                '/' . $item['path']
            );
            ?>
            <a
                class="sidebar-link <?= $isActive ? 'active' : '' ?>"
                href="<?= e(url($item['path'])) ?>"
                <?= $isActive ? 'aria-current="page"' : '' ?>
            >
                <span class="sidebar-icon"><?= e($item['icon']) ?></span>
                <span><?= e($item['label']) ?></span>
                <?php if ($isActive): ?>
                    <span class="active-indicator"></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-user">
            <div class="user-avatar">
                <?= e(strtoupper(substr(currentUserName(), 0, 1))) ?>
            </div>
            <div class="user-details">
                <strong><?= e(currentUserName()) ?></strong>
                <small><?= e(ucfirst($role)) ?> Account</small>
            </div>
        </div>

        <a class="sidebar-link logout-link"
           href="<?= e(url('auth/logout.php')) ?>"
           data-confirm="Are you sure you want to log out?">
            <span class="sidebar-icon">↪</span>
            <span>Logout</span>
        </a>
    </div>
</aside>

<div class="main-area">
    <header class="page-topbar">
        <button type="button" class="menu-toggle"
                id="menuToggle" aria-label="Toggle sidebar"
                aria-controls="sidebar" aria-expanded="false">
            ☰
        </button>

        <div class="page-topbar-title">
            <strong><?= e($pageTitle) ?></strong>
            <small>Inventory &amp; Sales Management System</small>
        </div>

        <div class="topbar-date">
            <?= e(date('M d, Y')) ?>
        </div>
    </header>

    <main class="container">
