<?php
/** Puts a deactivated menu item back on the public menu. */
// Undo of delete_record.php: sets is_active back to 1.
require_once __DIR__ . "/../includes/validation.php";
require_login();
require_post("pages/records.php");

$id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    flash_set("error", "Invalid menu item.");
    redirect("pages/records.php");
}

try {
    // Reactivate the item.
    $name = set_menu_active($id, true);
    if ($name === null) {
        flash_set("error", "That menu item no longer exists.");
    } else {
        flash_set("success", "“" . $name . "” was restored to the menu.");
    }
} catch (mysqli_sql_exception $ex) {
    flash_set("error", "The item could not be restored. Please try again.");
}
redirect("pages/records.php");
