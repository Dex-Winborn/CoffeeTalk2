<?php
/** Public customer order from pages/menu.php. Not logged in: user_id stays NULL. */
require_once __DIR__ . "/../includes/validation.php";
require_post("pages/menu.php");

[$errors, $clean] = validate_public_order();
if ($errors) {
    foreach ($errors as $error) flash_set("error", $error);
    redirect("pages/menu.php");
}

try {
    $order = create_order(null, $clean["customer_name"], $clean["lines"], $clean["notes"]);
} catch (mysqli_sql_exception $ex) {
    flash_set("error", "We could not save the order. Please try again.");
    redirect("pages/menu.php");
}

if ($order === null) {
    flash_set("error", "An item is no longer available. Please review your order.");
    redirect("pages/menu.php");
}

redirect("pages/menu.php?placed=" . rawurlencode($order["ref"]));
