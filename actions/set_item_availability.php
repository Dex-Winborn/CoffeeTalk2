<?php
// Sets a menu item's availability (Active/Inactive on public menu via is_active).
require_once __DIR__ . "/../includes/validation.php";
require_login();
require_post("pages/records.php");

$id = filter_var($_POST["id"] ?? "", FILTER_VALIDATE_INT);
$status = isset($_POST["status"]) && is_string($_POST["status"]) ? $_POST["status"] : "";
if ($id === false || $id < 1 || !in_array($status, ["available", "unavailable"], true)) {
    flash_set("error", "Invalid availability request.");
    redirect("pages/records.php");
}

$active = $status === "available";
try {
    $name = set_menu_active($id, $active);
    if ($name === null) {
        flash_set("error", "That menu item no longer exists.");
    } elseif ($active) {
        flash_set("success", "“" . $name . "” is now Available.");
    } else {
        flash_set("success", "“" . $name . "” is now Unavailable.");
    }
} catch (mysqli_sql_exception $ex) {
    flash_set("error", "The availability could not be updated. Please try again.");
}
redirect("pages/records.php");
