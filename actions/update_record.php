<?php
// UPDATE: saves the changes made in pages/record_form.php to an existing menu item.
require_once __DIR__ . "/../includes/validation.php";
require_login();
require_post("pages/records.php");

// The form sends the id of the item being edited.
$id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    flash_set("error", "Invalid menu item.");
    redirect("pages/records.php");
}

[$errors, $item] = validate_item();
if ($errors) {
    old_set($_POST);
    foreach ($errors as $error) flash_set("error", $error);
    redirect("pages/record_form.php?id=" . $id);
}

try {
    // WHERE id=? makes sure only this one item is changed.
    $stmt = db()->prepare("UPDATE menu_items SET name=?, category=?, description=?, price=? WHERE id=?");
    $stmt->bind_param("sssdi", $item["name"], $item["category"], $item["description"], $item["price"], $id);
    $stmt->execute();
    $stmt->close();
    flash_set("success", "Menu item “" . $item["name"] . "” was updated.");
    redirect("pages/records.php");
} catch (mysqli_sql_exception $ex) {
    old_set($_POST);
    flash_set("error", $ex->getCode() === 1062
        ? "That name is already used by another item in this category."
        : "The item could not be updated. Please try again.");
    redirect("pages/record_form.php?id=" . $id);
}
