<?php
require_once __DIR__ . "/../includes/functions.php";
require_login();

$id = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT);
$is_edit = $id !== false && $id > 0;
$values = ["name" => "", "category" => MENU_CATEGORIES[0], "description" => "", "price" => ""];

if ($is_edit) {
    $stmt = db()->prepare("SELECT name,category,description,price FROM menu_items WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) {
        flash_set("error", "That menu item was not found.");
        redirect("pages/records.php");
    }
    $values = [
        "name" => $item["name"],
        "category" => $item["category"],
        "description" => $item["description"],
        "price" => number_format((float) $item["price"], 2, ".", ""),
    ];
}

// Re-fill the form after a failed save.
foreach (old_get() as $key => $old_value) {
    if (isset($values[$key]) && is_string($old_value)) $values[$key] = $old_value;
}

$action = $is_edit ? "actions/update_record.php" : "actions/add_record.php";
$pageTitle = ($is_edit ? "Edit menu item" : "Add menu item") . " — Coffee Talk";
$activePage = "records";
$extraScripts = ["js/validation.js"];
require __DIR__ . "/../includes/header.php";
?>
<main class="app-main"><div class="app-wrap app-wrap-narrow">
<?= flash_render() ?>
<div class="app-head"><div><div class="eyebrow"><?= $is_edit ? 'Update record' : 'Create record' ?></div><h1 class="app-title"><?= $is_edit ? 'Edit menu item' : 'Add menu item' ?></h1></div></div>
<section class="panel">
<form method="post" action="<?= e(url($action)) ?>" data-validate novalidate>
<?= csrf_field() ?>
<?php if ($is_edit): ?><input type="hidden" name="id" value="<?= (int) $id ?>" /><?php endif; ?>
<div class="field"><label for="name">Item name</label><input type="text" id="name" name="name" required minlength="2" maxlength="80" value="<?= e($values['name']) ?>" /><span class="field-error" aria-live="polite"></span></div>
<div class="field"><label for="category">Category</label><select id="category" name="category" required><?php foreach (MENU_CATEGORIES as $option): ?><option value="<?= e($option) ?>"<?= $option === $values['category'] ? ' selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select><span class="field-error" aria-live="polite"></span></div>
<div class="field"><label for="price">Price (₱)</label><input type="number" id="price" name="price" required min="0.01" max="99999.99" step="0.01" inputmode="decimal" value="<?= e($values['price']) ?>" /><span class="field-error" aria-live="polite"></span></div>
<div class="field"><label for="description">Description <span class="optional">(optional)</span></label><input type="text" id="description" name="description" maxlength="255" value="<?= e($values['description']) ?>" /><small class="field-hint">A short note shown beside the item name.</small><span class="field-error" aria-live="polite"></span></div>
<div class="form-actions"><button class="button" type="submit"><?= $is_edit ? 'Save changes' : 'Add item' ?></button><a class="text-link" href="<?= e(url('pages/records.php')) ?>">Cancel</a></div>
</form>
</section>
</div></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>
