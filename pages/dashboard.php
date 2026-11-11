<?php
// Staff dashboard: counters for today and the most recent orders. A visitor who is not logged in is
// sent to the login page by require_login().
require_once __DIR__ . "/../includes/functions.php";
require_login();

$user = current_user();
// One round trip instead of five, and no DATE() wrapper so the indexes still apply.
$stats = db()->query("SELECT
        (SELECT COUNT(*) FROM menu_items WHERE is_active=1) AS items,
        (SELECT COUNT(*) FROM menu_items WHERE is_active=0) AS inactive,
        (SELECT COUNT(*) FROM orders WHERE created_at >= CURDATE()) AS orders_today,
        (SELECT COUNT(*) FROM orders WHERE status IN ('Pending','Preparing')) AS open_orders,
        (SELECT COALESCE(SUM(amount_due),0) FROM payments WHERE paid_at >= CURDATE()) AS sales")->fetch_assoc();
// The last 6 orders, shown in the table below.
$recent = fetch_recent_orders(6);
// First word of the full name, used in the greeting.
$first_name = explode(" ", trim($user["full_name"]))[0] ?: $user["username"];

$pageTitle = "Staff Dashboard — Coffee Talk";
$activePage = "dashboard";
require __DIR__ . "/../includes/header.php";
?>
<?php /* Dashboard content. */ ?>
<main class="app-main"><div class="app-wrap">
<?= flash_render() ?>
<div class="app-head">
<div><div class="eyebrow">Coffee Talk Café System</div><h1 class="app-title">Staff Dashboard</h1><p class="app-lead">Hello, <?= e($first_name) ?>. Signed in as <strong><?= e($user['username']) ?></strong>. Here is what is happening at the café.</p></div>
<div class="app-head-actions"><a class="button" href="<?= e(url('pages/transactions.php')) ?>">Take an order</a></div>
</div>
<?php /* Counter cards: active items, orders today, open orders and money collected today. */ ?>
<div class="stat-grid">
<div class="stat-card"><span class="stat-label">Active menu items</span><strong class="stat-value"><?= (int) $stats['items'] ?></strong><small><?= (int) $stats['inactive'] ?> inactive</small></div>
<div class="stat-card"><span class="stat-label">Orders today</span><strong class="stat-value"><?= (int) $stats['orders_today'] ?></strong><small>Since midnight</small></div>
<div class="stat-card"><span class="stat-label">Open orders</span><strong class="stat-value"><?= (int) $stats['open_orders'] ?></strong><small>Pending or preparing</small></div>
<div class="stat-card"><span class="stat-label">Collected today</span><strong class="stat-value"><?= e(peso($stats['sales'])) ?></strong><small>Cash, QR and card</small></div>
</div>
<div class="quick-grid">
<a class="quick-card" href="<?= e(url('pages/records.php')) ?>"><h2>Menu items</h2><p>Add, edit and deactivate the drinks and food on the public menu.</p><span class="text-link">Manage menu →</span></a>
<a class="quick-card" href="<?= e(url('pages/transactions.php')) ?>"><h2>Orders</h2><p>Take orders, process payment and move them through service.</p><span class="text-link">Manage orders →</span></a>
<a class="quick-card" href="<?= e(url('pages/menu.php')) ?>"><h2>Public order menu</h2><p>See the live menu and place a customer order.</p><span class="text-link">View ordering →</span></a>
</div>
<?php /* Table of the recent orders. The data-label attributes are used by the phone layout in the CSS. */ ?>
<section class="panel">
<div class="panel-head"><h2>Recent orders</h2><a class="text-link" href="<?= e(url('pages/transactions.php')) ?>">All orders →</a></div>
<?php if ($recent): ?>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>Reference</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead>
<tbody>
<?php /* One table row per order. e() escapes every value. */ ?>
<?php foreach ($recent as $row): ?>
<tr>
<td data-label="Reference"><?= e($row['ref']) ?></td>
<td data-label="Customer"><?= e($row['customer_name']) ?></td>
<td data-label="Items"><ul class="item-list"><?php foreach ($row['items'] as $line): ?><li><?= (int) $line['qty'] ?> × <?= e($line['name']) ?></li><?php endforeach; ?></ul></td>
<td data-label="Total"><strong><?= e(peso($row['total'])) ?></strong></td>
<td data-label="Status"><span class="<?= e(status_class($row['status'])) ?>"><?= e($row['status']) ?></span></td>
<td data-label="Placed"><?= e(date('M j, g:i A', strtotime($row['created_at']))) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php else: ?>
<p class="empty-state">No orders yet. Take the first one from the Orders page.</p>
<?php endif; ?>
</section>
</div></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>
