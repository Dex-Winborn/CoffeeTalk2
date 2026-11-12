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

// Separate add-ons for embedding in drink items
$addon_items = $groups["Add-ons"] ?? [];
unset($groups["Add-ons"]);

// Order the sections by the menu's defined type order (MENU_CATEGORIES) instead of alphabetically.
$ordered_groups = [];
foreach (MENU_CATEGORIES as $menu_category) {
    if (isset($groups[$menu_category])) $ordered_groups[$menu_category] = $groups[$menu_category];
}
// Keep any category not in the predefined list, in its original order, at the end.
foreach ($groups as $menu_category => $category_items) {
    if (!isset($ordered_groups[$menu_category])) $ordered_groups[$menu_category] = $category_items;
}
$groups = $ordered_groups;

// Build addon options HTML for JavaScript
$addon_options = '';
if ($addon_items) {
    foreach ($addon_items as $addon) {
        $addon_options .= '<option value="' . (int)$addon['id'] . '" data-price="' . (float)$addon['price'] . '">' . e($addon['name']) . ' (+' . e(price_label($addon['price'])) . ')</option>';
    }
}

// Page settings: public navigation and the validation script.
$pageTitle = "Order menu — Coffee Talk";
$bodyClass = "app-body order-page";
$navMode = "public";
$activePage = "menu";
$announcement = "Order something good <span>·</span> Made fresh at Coffee Talk";
$extraScripts = ["js/validation.js", "js/main.js"];
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
<?php elseif (!$groups && !$addon_items): ?>
<p class="empty-state">Our menu is being updated. Please check back soon.</p>
<?php else: ?>
<?php /* Order form. Each item has a quantity box named quantity[item id]. Items left at 0 are ignored. */ ?>
<form class="public-order-form" method="post" action="<?= e(url('actions/place_order.php')) ?>" data-validate novalidate>
<?= csrf_field() ?>

<?php if ($groups): ?>
<?php foreach ($groups as $category => $category_items): ?>
<section class="flash-menu-section">
<h2 class="flash-menu-section-title"><?= e($category) ?></h2>
<div class="flash-menu-grid" role="list">
<?php foreach ($category_items as $item): ?>
<?php
// One image per menu item, matched to the photos in images/. Falls back to the logo when an item has
// no dedicated photo (e.g. add-ons).
$item_images = [
    1  => 'images/10-Iced-Americano.webp',
    2  => 'images/12-Iced-Caffe-Latte.webp',
    3  => 'images/14-Iced-Spanish-Latte.webp',
    4  => 'images/16-Iced-Mocha.webp',
    5  => 'images/18-Iced-White-Mocha.webp',
    6  => 'images/20-Iced-Caramel-Latte.webp',
    7  => 'images/22-Iced-Salted-Caramel-Latte.webp',
    8  => 'images/24-Iced-Caramel-Macchiato.webp',
    9  => 'images/26-Iced-Hazelnut-Latte.webp',
    10 => 'images/28-Iced-Vanilla-Latte.webp',
    11 => 'images/37-CT-Signature-Blend.webp',
    12 => 'images/38-Sea-Salt-Creme-Latte.webp',
    13 => 'images/39-Tres-Leches.webp',
    14 => 'images/40-Popcorn-Oat-Latte.webp',
    15 => 'images/41-Biscoff-Cloud-Latte.webp',
    16 => 'images/logo.png',
    17 => 'images/logo.png',
    18 => 'images/logo.png',
    19 => 'images/30-Matcha-Latte.webp',
    20 => 'images/31-Sea-Salt-Matcha.webp',
    21 => 'images/32-Chocolate-Latte.webp',
    22 => 'images/33-Strawberry-Latte.webp',
    23 => 'images/34-Strawberry-Matcha.webp',
    24 => 'images/52-Apple-Chamomile-Iced.webp',
    25 => 'images/53-Passion-Fruit-Burst.webp',
    26 => 'images/54-Green-Apple-Twist.webp',
    27 => 'images/55-Lychee-Fizz.webp',
    28 => 'images/50-Classic-Wellness-Tea.webp',
    29 => 'images/51-Apple-Chamomile-Hot.webp',
    30 => 'images/01-Double-Choco-Chip.webp',
    31 => 'images/02-Oreo-CB.webp',
    32 => 'images/03-Matcha-CB.webp',
    33 => 'images/04-Strawberry-CB.webp',
    34 => 'images/05-Biscoff-CB.webp',
    35 => 'images/06-Java-Chip-CB.webp',
    36 => 'images/47-Flavored-Fries.webp',
    37 => 'images/48-Cheezy-Nachos.webp',
    38 => 'images/49-Twister-Fries.webp',
    39 => 'images/35-Beef-Lasagna.webp',
    40 => 'images/36-Creamy-Carbonara.webp',
    41 => 'images/07-Classic-Croffle.webp',
    42 => 'images/08-Classic-Cinnamon.webp',
    43 => 'images/09-Classic-Choco.webp',
    44 => 'images/42-Berry-Delight.webp',
    45 => 'images/43-Nutella-Alcapone.webp',
    46 => 'images/44-Oreo-Craze.webp',
    47 => 'images/45-Biscoff-Crunch.webp',
    48 => 'images/46-Banana-Pudding.webp',
];
$img = $item_images[(int) $item['id']] ?? 'images/logo.png';

// Categories that can have add-ons (drinks)
$has_addons = in_array($category, ['Coffee (Iced / Hot)', 'Premium Coffee', 'Non-Coffee', 'Wellness Blend (Iced)', 'Wellness Blend (Hot)', 'Chill Blend (Frappe)']);
?>
<article class="flash-card" role="listitem" data-item-id="<?= (int) $item['id'] ?>" data-has-addons="<?= $has_addons ? 'true' : 'false' ?>">
<div class="flash-card-image">
<span class="flash-card-category"><?= e($category) ?></span>
<img src="<?= e(url($img)) ?>" alt="" loading="lazy" onerror="this.src='<?= e(url('images/logo.png')) ?>'" />
</div>
<div class="flash-card-content">
<h3 class="flash-card-name"><?= e($item['name']) ?></h3>
<?php if ($item['description'] !== ''): ?>
<p class="flash-card-description"><?= e($item['description']) ?></p>
<?php endif; ?>
<div class="flash-card-price"><?= e(peso($item['price'])) ?></div>
</div>
<div class="flash-card-footer">
<div class="flash-qty" role="group" aria-label="Quantity">
<button type="button" class="flash-qty-btn flash-qty-minus" aria-label="Decrease quantity" data-item-id="<?= (int) $item['id'] ?>">−</button>
<input type="number" class="flash-qty-input" name="quantity[<?= (int) $item['id'] ?>]" id="item-<?= (int) $item['id'] ?>" min="0" max="20" step="1" value="0" aria-label="Quantity" inputmode="numeric" />
<button type="button" class="flash-qty-btn flash-qty-plus" aria-label="Increase quantity" data-item-id="<?= (int) $item['id'] ?>">+</button>
</div>
</div>

<?php if ($has_addons && $addon_items): ?>
<div class="flash-card-addons" hidden>
<div class="flash-addons-header">Add-ons <span class="flash-addon-hint">(optional)</span></div>
<div class="flash-addons-list" data-parent-id="<?= (int) $item['id'] ?>">
<!-- Add-on rows will be inserted here by JavaScript -->
</div>
<div class="flash-addon-fields">
<div class="flash-addon-select-field">
<label for="addon-select-<?= (int) $item['id'] ?>">Add-on</label>
<select id="addon-select-<?= (int) $item['id'] ?>" class="flash-addon-select" data-parent-id="<?= (int) $item['id'] ?>">
<option value="">Choose an add-on…</option>
<?= $addon_options ?>
</select>
<span class="field-error" aria-live="polite"></span>
</div>
<div class="flash-addon-qty-field">
<label>Qty</label>
<input type="number" class="flash-addon-qty" value="1" min="1" max="20" />
</div>
<button type="button" class="flash-addon-add" data-parent-id="<?= (int) $item['id'] ?>">Add</button>
</div>
</div>
<?php endif; ?>

</article>
<?php endforeach; ?>
</div>
</section>
<?php endforeach; ?>
<?php endif; ?>

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