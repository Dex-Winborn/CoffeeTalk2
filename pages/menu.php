<?php
// Public order menu (no login needed). It lists the active menu items with quantity boxes. The form is
// processed by actions/place_order.php.
require_once __DIR__ . "/../includes/functions.php";

// Load the active menu items. If MySQL is not available, show a message instead of crashing.
$items = [];
$db_error = false;
try {
    $result = db_connect()->query("SELECT id,name,category,description,price FROM menu_items WHERE is_active=1 ORDER BY category,id");
    while ($row = $result->fetch_assoc()) $items[] = $row;
} catch (mysqli_sql_exception $e) {
    $db_error = true;
}

// After an order is saved, place_order.php returns here with ?placed=reference. Only a correctly
// formatted reference is accepted.
$placed = isset($_GET["placed"]) && is_string($_GET["placed"]) && preg_match('/^CT-\d{8}-\d+$/', $_GET["placed"]) ? $_GET["placed"] : "";

// Group once so the markup stays a single loop.
$groups = [];
foreach ($items as $item) $groups[$item["category"]][] = $item;

// Page settings: public navigation and the validation script.
$pageTitle = "Order menu — Coffee Talk";
$bodyClass = "app-body order-page";
$navMode = "public";
$activePage = "menu";
$announcement = "Order something good <span>·</span> Made fresh at Coffee Talk";
$extraScripts = ["js/validation.js"];
require __DIR__ . "/../includes/header.php";
?>
<main class="app-main"><div class="app-wrap">
<div class="app-head"><div><div class="eyebrow">Coffee Talk Menu</div><h1 class="app-title">Pick your favorites.</h1><p class="app-lead">Choose quantities, add your name, and place an order. Pay at the café counter.</p></div></div>
<?= flash_render() ?>
<?php if ($placed): ?>
<div class="order-success"><span class="done-mark">✓</span><div><h2>Order placed!</h2><p>Your reference is <strong><?= e($placed) ?></strong>. Please show it to the barista at the counter.</p></div></div>
<?php endif; ?>
<?php if ($db_error): ?>
<div class="alert alert-error">Menu unavailable. Please check that MySQL is running and the database is imported.</div>
<?php elseif (!$groups): ?>
<p class="empty-state">Our menu is being updated. Please check back soon.</p>
<?php else: ?>
<?php /* Order form. Each item has a quantity box named quantity[item id]. Items left at 0 are ignored. */ ?>
<form class="public-order-form" method="post" action="<?= e(url('actions/place_order.php')) ?>" data-validate novalidate>
<?= csrf_field() ?>
<div class="full-menu public-menu-grid">
<?php /* One section per category. */ ?>
<?php foreach ($groups as $category => $category_items): ?>
<section class="menu-category"><h2><?= e($category) ?></h2>
<?php foreach ($category_items as $item): ?>
<div class="public-menu-item">
<div><strong><?= e($item['name']) ?></strong><?php if ($item['description'] !== ''): ?><small><?= e($item['description']) ?></small><?php endif; ?><span><?= e(peso($item['price'])) ?></span><p class="field-error" aria-live="polite"></p></div>
<label class="qty-label" for="item-<?= (int) $item['id'] ?>">Qty<input id="item-<?= (int) $item['id'] ?>" type="number" name="quantity[<?= (int) $item['id'] ?>]" min="0" max="20" step="1" value="0" /></label>
</div>
<?php endforeach; ?>
</section>
<?php endforeach; ?>
</div>
<?php /* Customer name (required) and optional notes. */ ?>
<section class="panel order-customer">
<div class="field"><label for="customer_name">Your name</label><input type="text" id="customer_name" name="customer_name" required minlength="2" maxlength="100" autocomplete="name" placeholder="So we can call your order" /><span class="field-error" aria-live="polite"></span></div>
<div class="field"><label for="notes">Order notes <span class="optional">(optional)</span></label><input type="text" id="notes" name="notes" maxlength="255" placeholder="Less sweet, oat milk, no ice…" /></div>
<button class="button" type="submit">Place order</button>
</section>
</form>
<?php endif; ?>
</div></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>
