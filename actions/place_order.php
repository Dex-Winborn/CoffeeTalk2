<?php
/** Public customer order from pages/menu.php. Not logged in: user_id stays NULL. */
// Steps: check the CSRF token, validate, save the order with create_order(), then go back to the menu
// with the order reference.
require_once __DIR__ . "/../includes/validation.php";
// No login is required here, because customers can order. The CSRF token is still checked.
require_post("pages/menu.php");

[$errors, $clean] = validate_public_order();
if ($errors) {
    foreach ($errors as $error) flash_set("error", $error);
    redirect("pages/menu.php");
}

// user_id is null because the customer is not logged in.
try {
    $order = create_order(null, $clean["customer_name"], $clean["lines"], $clean["notes"]);
} catch (mysqli_sql_exception $ex) {
    flash_set("error", "We could not save the order. Please try again.");
    redirect("pages/menu.php");
}

// null means an item was deactivated while the customer was ordering.
if ($order === null) {
    flash_set("error", "An item is no longer available. Please review your order.");
    redirect("pages/menu.php");
}

// Success: the menu page shows the order reference.
redirect("pages/menu.php?placed=" . rawurlencode($order["ref"]));
