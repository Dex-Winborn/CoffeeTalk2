<?php
// READ: lists the menu items in an HTML table with search and filters. Each row has Edit and Delete
// (soft delete) or Restore buttons.
require_once __DIR__ . "/../includes/functions.php";
require_login();

// Filters from the URL: search text, category and status (available by default). Unknown values are
// replaced with safe defaults.
$q = isset($_GET["q"]) && is_string($_GET["q"]) ? trim($_GET["q"]) : "";
$category = isset($_GET["category"]) && is_string($_GET["category"]) ? $_GET["category"] : "";
$status = isset($_GET["status"]) && is_string($_GET["status"]) ? $_GET["status"] : "active";
if (!in_array($status, ["active", "inactive", "all"], true)) $status = "active";
if (!in_array($category, MENU_CATEGORIES, true)) $category = "";

// Conditions are added only for the filters that were used. The values are bound as parameters, and %
// and _ in the search text are escaped.
$where = [];
$types = "";
$params = [];
if ($q !== "") {
    $where[] = "name LIKE ?";
    $types .= "s";
    $params[] = "%" . addcslashes($q, "%_\\") . "%";
}
if ($category !== "") {
    $where[] = "category = ?";
    $types .= "s";
    $params[] = $category;
}
if ($status === "active") $where[] = "is_active=1";
elseif ($status === "inactive") $where[] = "is_active=0";

// Put the query together and run it with a prepared statement.
$sql = "SELECT id,name,category,description,price,is_active,updated_at FROM menu_items"
    . ($where ? " WHERE " . implode(" AND ", $where) : "")
    . " ORDER BY id";
$stmt = db()->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = "Menu items — Coffee Talk";
$activePage = "records";
$extraScripts = ["js/validation.js", "js/bulk-menu.js"];
require __DIR__ . "/../includes/header.php";
?>
<main class="app-main"><div class="app-wrap">
<?= flash_render() ?>
<div class="app-head">
<div><div class="eyebrow">Records · CRUD</div><h1 class="app-title">Menu items</h1><p class="app-lead">Items are saved in MySQL and shown on the public menu when active.</p></div>
<div class="app-head-actions"><a class="button" href="<?= e(url('pages/record_form.php')) ?>">+ Add menu item</a></div>
</div>

<?php /* Search and filter form. It uses GET, so the filters stay in the address. */ ?>
<form class="filter-bar" method="get" action="<?= e(url('pages/records.php')) ?>">
<div class="field"><label for="q">Search</label><input type="search" id="q" name="q" placeholder="Item name" value="<?= e($q) ?>" /></div>
<div class="field"><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option><?php foreach (MENU_CATEGORIES as $option): ?><option value="<?= e($option) ?>"<?= $option === $category ? ' selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="status">Status</label><select id="status" name="status"><option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Available</option><option value="inactive"<?= $status === 'inactive' ? ' selected' : '' ?>>Unavailable</option><option value="all"<?= $status === 'all' ? ' selected' : '' ?>>All</option></select></div>
<div class="filter-actions"><button class="button" type="submit">Filter</button><a class="text-link" href="<?= e(url('pages/records.php')) ?>">Reset</a></div>
</form>

<?php if ($items): ?>
<?php /* Bulk confirm bar: sits just under the filters so the staged-change count and the Confirm
   button are visible before the table. The button submits the form further down via its form
   attribute. */ ?>
<div class="bulk-bar" id="bulk-bar">
<span class="bulk-bar-count" id="bulk-count">No pending changes</span>
<div class="bulk-bar-actions">
<button type="button" class="btn-sm" id="bulk-discard">Discard</button>
<button type="submit" class="button" id="bulk-save" form="bulk-menu-form">Confirm changes</button>
</div>
</div>
<?php endif; ?>

<section class="panel">
<div class="panel-head"><h2><?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?></h2></div>
<?php if ($items): ?>
<?php /* Bulk editor: every availability drop-down belongs to one form. Changes are staged in the
   browser and saved together with the Confirm bar, instead of reloading after each change. */ ?>
<form id="bulk-menu-form" method="post" action="<?= e(url('actions/bulk_update_items.php')) ?>">
<?= csrf_field() ?>
<input type="hidden" name="return_q" value="<?= e($q) ?>" />
<input type="hidden" name="return_category" value="<?= e($category) ?>" />
<input type="hidden" name="return_status" value="<?= e($status) ?>" />
<div class="table-wrap"><table class="data-table">
<thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
<tbody>
<?php /* One table row per item. Unavailable items are dimmed. The drop-down stages a change; the
   badge shows the saved status until the whole form is confirmed. */ ?>
<?php foreach ($items as $item): ?>
<tr<?= $item['is_active'] ? '' : ' class="is-inactive"' ?>>
<td data-label="ID"><?= (int) $item['id'] ?></td>
<td data-label="Name"><?= e($item['name']) ?><?php if ($item['description'] !== ''): ?><small class="cell-note"><?= e($item['description']) ?></small><?php endif; ?></td>
<td data-label="Category"><?= e($item['category']) ?></td>
<td data-label="Price"><?= e(peso($item['price'])) ?></td>
<td data-label="Status"><span class="badge status-badge <?= $item['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $item['is_active'] ? 'Available' : 'Unavailable' ?></span></td>
<td data-label="Updated"><?= e(date('M j, Y', strtotime($item['updated_at']))) ?></td>
<td data-label="Actions">
<div class="row-actions">
<a class="btn-sm" href="<?= e(url('pages/record_form.php?id=' . (int) $item['id'])) ?>">Edit</a>
<select class="bulk-toggle" name="active[<?= (int) $item['id'] ?>]" data-original="<?= $item['is_active'] ? '1' : '0' ?>" aria-label="Availability for <?= e($item['name']) ?>">
<option value="1"<?= $item['is_active'] ? ' selected' : '' ?>>Available</option>
<option value="0"<?= $item['is_active'] ? '' : ' selected' ?>>Unavailable</option>
</select>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
</form>
<?php else: ?>
<p class="empty-state">No menu items match these filters.</p>
<?php endif; ?>
</section>
</div></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>
