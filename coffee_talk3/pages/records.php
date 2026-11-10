<?php
require_once __DIR__ . "/../includes/functions.php";
require_login();

$q = isset($_GET["q"]) && is_string($_GET["q"]) ? trim($_GET["q"]) : "";
$category = isset($_GET["category"]) && is_string($_GET["category"]) ? $_GET["category"] : "";
$status = isset($_GET["status"]) && is_string($_GET["status"]) ? $_GET["status"] : "active";
if (!in_array($status, ["active", "inactive", "all"], true)) $status = "active";
if (!in_array($category, MENU_CATEGORIES, true)) $category = "";

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
$extraScripts = ["js/validation.js"];
require __DIR__ . "/../includes/header.php";
?>
<main class="app-main"><div class="app-wrap">
<?= flash_render() ?>
<div class="app-head">
<div><div class="eyebrow">Records · CRUD</div><h1 class="app-title">Menu items</h1><p class="app-lead">Items are saved in MySQL and shown on the public menu when active.</p></div>
<div class="app-head-actions"><a class="button" href="<?= e(url('pages/record_form.php')) ?>">+ Add menu item</a></div>
</div>

<form class="filter-bar" method="get" action="<?= e(url('pages/records.php')) ?>">
<div class="field"><label for="q">Search</label><input type="search" id="q" name="q" placeholder="Item name" value="<?= e($q) ?>" /></div>
<div class="field"><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option><?php foreach (MENU_CATEGORIES as $option): ?><option value="<?= e($option) ?>"<?= $option === $category ? ' selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
<div class="field"><label for="status">Status</label><select id="status" name="status"><option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Active</option><option value="inactive"<?= $status === 'inactive' ? ' selected' : '' ?>>Inactive</option><option value="all"<?= $status === 'all' ? ' selected' : '' ?>>All</option></select></div>
<div class="filter-actions"><button class="button" type="submit">Filter</button><a class="text-link" href="<?= e(url('pages/records.php')) ?>">Reset</a></div>
</form>

<section class="panel">
<div class="panel-head"><h2><?= count($items) ?> <?= count($items) === 1 ? 'item' : 'items' ?></h2></div>
<?php if ($items): ?>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($items as $item): ?>
<tr<?= $item['is_active'] ? '' : ' class="is-inactive"' ?>>
<td data-label="ID"><?= (int) $item['id'] ?></td>
<td data-label="Name"><?= e($item['name']) ?><?php if ($item['description'] !== ''): ?><small class="cell-note"><?= e($item['description']) ?></small><?php endif; ?></td>
<td data-label="Category"><?= e($item['category']) ?></td>
<td data-label="Price"><?= e(peso($item['price'])) ?></td>
<td data-label="Status"><span class="badge <?= $item['is_active'] ? 'badge-active' : 'badge-inactive' ?>"><?= $item['is_active'] ? 'Active' : 'Inactive' ?></span></td>
<td data-label="Updated"><?= e(date('M j, Y', strtotime($item['updated_at']))) ?></td>
<td data-label="Actions">
<div class="row-actions">
<a class="btn-sm" href="<?= e(url('pages/record_form.php?id=' . (int) $item['id'])) ?>">Edit</a>
<?php if ($item['is_active']): ?>
<form method="post" action="<?= e(url('actions/delete_record.php')) ?>" data-confirm="Delete ‘<?= e($item['name']) ?>’? It will be deactivated and removed from the public menu.">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int) $item['id'] ?>" />
<button class="btn-sm btn-danger" type="submit">Delete</button>
</form>
<?php else: ?>
<form method="post" action="<?= e(url('actions/restore_record.php')) ?>">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= (int) $item['id'] ?>" />
<button class="btn-sm" type="submit">Restore</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php else: ?>
<p class="empty-state">No menu items match these filters.</p>
<?php endif; ?>
</section>
</div></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>
