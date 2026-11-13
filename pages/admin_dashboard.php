<?php
// Admin dashboard: summary counts for the whole system. Only administrators can open it
// (require_admin).
require_once __DIR__ . "/../includes/functions.php";
require_admin();

// One query returns all four counters.
$totals = db()->query("SELECT
        (SELECT COUNT(*) FROM users) AS accounts,
        (SELECT COUNT(*) FROM users WHERE role = 'staff') AS staff,
        (SELECT COUNT(*) FROM menu_items WHERE is_active = 1) AS menu_items,
        (SELECT COUNT(DISTINCT SUBSTRING_INDEX(order_ref,'-',3)) FROM orders) AS orders")->fetch_assoc();

// Page settings, then the shared header.
$pageTitle = "Admin Dashboard — Coffee Talk";
$activePage = "admin-dashboard";
require __DIR__ . "/../includes/header.php";
?>
<?php /* Page content: heading, counter cards and shortcuts to the other pages. */ ?>
<main class="app-main"><div class="app-wrap">
<?= flash_render() ?>
<div class="app-head">
<div><div class="eyebrow">Coffee Talk Café System</div><h1 class="app-title">Admin Dashboard</h1><p class="app-lead">Welcome, <?= e(current_user()['full_name']) ?>. Manage café records and review the system overview.</p></div>
<div class="app-head-actions"><a class="button" href="<?= e(url('pages/dashboard.php')) ?>">Staff Dashboard</a></div>
</div>
<?php /* Counter cards. (int) makes sure only numbers are printed. */ ?>
<div class="stat-grid admin-stat-grid">
<div class="stat-card"><span class="stat-label">User accounts</span><strong class="stat-value"><?= (int) $totals['accounts'] ?></strong><small>All registered accounts</small></div>
<div class="stat-card"><span class="stat-label">Staff accounts</span><strong class="stat-value"><?= (int) $totals['staff'] ?></strong><small>Staff access</small></div>
<div class="stat-card"><span class="stat-label">Menu items</span><strong class="stat-value"><?= (int) $totals['menu_items'] ?></strong><small>Currently active</small></div>
<div class="stat-card"><span class="stat-label">Orders</span><strong class="stat-value"><?= (int) $totals['orders'] ?></strong><small>Saved transactions</small></div>
</div>
<?php /* Shortcut cards to the other pages. */ ?>
<div class="quick-grid">
<a class="quick-card" href="<?= e(url('pages/records.php')) ?>"><h2>Menu items</h2><p>Create, update, deactivate, and restore café products.</p><span class="text-link">Manage menu →</span></a>
<a class="quick-card" href="<?= e(url('pages/transactions.php')) ?>"><h2>Orders & payments</h2><p>Review transactions, update their status, and record payments.</p><span class="text-link">Manage orders →</span></a>
<a class="quick-card" href="<?= e(url('index.php')) ?>"><h2>Public website</h2><p>Open the café home page and current live menu.</p><span class="text-link">View website →</span></a>
</div>
</div></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>
