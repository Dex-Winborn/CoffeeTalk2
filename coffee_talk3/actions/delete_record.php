<?php
/** Soft delete: the item stays in MySQL but leaves the public menu. */
require_once __DIR__ . "/../includes/validation.php";
require_login();
require_post("pages/records.php");

$id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
if ($id === false || $id < 1) {
    flash_set("error", "Invalid menu item.");
    redirect("pages/records.php");
}

try {
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
