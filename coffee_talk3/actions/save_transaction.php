<?php
/** Staff-entered order from pages/transactions.php; continues to the payment page. */
require_once __DIR__ . "/../includes/validation.php";
require_login();
require_post("pages/transactions.php");

[$errors, $clean] = validate_order();
if ($errors) {
    old_set($_POST);
    foreach ($errors as $error) flash_set("error", $error);
    redirect("pages/transactions.php");
}

try {
    $order = create_order(current_user()["id"], $clean["customer_name"], $clean["lines"], $clean["notes"]);
} catch (mysqli_sql_exception $ex) {
    old_set($_POST);
    flash_set("error", "The order could not be saved. Please try again.");
    redirect("pages/transactions.php");
}

if ($order === null) {
    old_set($_POST);
    flash_set("error", "One of the chosen menu items is not available.");
    redirect("pages/transactions.php");
}

$summary = [];
foreach ($order["items"] as $item) {
    $summary[] = $item["quantity"] . " × " . $item["name"];
}
flash_set("success", "Order " . $order["ref"] . " saved: " . implode(", ", $summary) . " (" . peso($order["total"]) . ").");
redirect("pages/payment.php?ref=" . rawurlencode($order["ref"]));
