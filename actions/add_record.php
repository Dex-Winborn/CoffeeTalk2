<?php
require_once __DIR__ . "/../includes/validation.php";
require_login();
require_post("pages/records.php");

[$errors, $item] = validate_item();
if ($errors) {
    old_set($_POST);
    foreach ($errors as $error) flash_set("error", $error);
    redirect("pages/record_form.php");
}

try {
    $stmt = db()->prepare("INSERT INTO menu_items(name,category,description,price) VALUES(?,?,?,?)");
    $stmt->bind_param("sssd", $item["name"], $item["category"], $item["description"], $item["price"]);
    $stmt->execute();
    $stmt->close();
    flash_set("success", "Menu item “" . $item["name"] . "” was added.");
    redirect("pages/records.php");
} catch (mysqli_sql_exception $ex) {
    old_set($_POST);
    flash_set("error", $ex->getCode() === 1062
        ? "That name is already used by another item in this category."
        : "The item could not be saved. Please try again.");
    redirect("pages/record_form.php");
}
