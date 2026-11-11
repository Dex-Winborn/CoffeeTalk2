<?php
/** Soft delete: the item stays in MySQL but leaves the public menu. */
// DELETE (soft delete): is_active is set to 0 instead of removing the row, so old orders that point to
// the item stay valid.
require_once __DIR__ . "/../includes/validation.php";
require_login();
require_post("pages/records.php");

// The id must be a positive whole number.
$id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    flash_set("error", "Invalid menu item.");
    redirect("pages/records.php");
}

try {
    // Deactivate the item. set_menu_active() returns its name, or null when the item does not exist.
    $name = set_menu_active($id, false);
    if ($name === null) {
        flash_set("error", "That menu item no longer exists.");
    } else {
        flash_set("success", "“" . $name . "” was deleted (deactivated). Restore it from the Inactive filter if needed.");
    }
} catch (mysqli_sql_exception $ex) {
    flash_set("error", "The item could not be deleted. Please try again.");
}
redirect("pages/records.php");
