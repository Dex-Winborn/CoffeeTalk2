<?php
/** Server-side validation; client checks are only convenience. */
// Every action file calls these functions before it touches the database. They read $_POST, check the
// values and return [errors, clean values].
// The JavaScript checks in js/validation.js are only a convenience; these are the checks that count.
// Also loads the session and database helpers.
require_once __DIR__ . "/functions.php";

// Order size limits: up to 20 lines per order, 50 of one item for staff and 20 of one item for public
// customers.
const MAX_LINES_PER_ORDER = 20;
const MAX_LINE_QUANTITY = 50;
const MAX_PUBLIC_LINE_QUANTITY = 20;

// Reads a text field from the form. Returns an empty string when it is missing or is not plain text
// (for example an array). Spaces at the ends are removed.
function post_string(string $key): string
{
    $value = $_POST[$key] ?? "";
    return is_string($value) ? trim($value) : "";
}

// Same, but spaces are kept, because they can be part of a password.
function post_password(string $key): string
{
    $value = $_POST[$key] ?? "";
    return is_string($value) ? $value : "";
}

// Password rule: at least 8 characters with a letter and a number. Returns an error message, or an
// empty string when the password is fine.
function password_problem(string $password): string
{
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return "Password must be at least 8 characters and include a letter and a number.";
    }
    return "";
}

// Checks the add/edit menu item form (name, category, description and price).
function validate_item(): array
{
    $errors = [];
    $name = post_string("name");
    $category = post_string("category");
    $description = post_string("description");
    $raw_price = post_string("price");

    if ($name === "" || mb_strlen($name) < 2 || mb_strlen($name) > 80) {
        $errors[] = "Item name is required and must be 2 to 80 characters.";
    }
    if (!in_array($category, MENU_CATEGORIES, true)) {
        $errors[] = "Please choose a valid category.";
    }
    if (mb_strlen($description) > 255) {
        $errors[] = "Description must be 255 characters or fewer.";
    }
    // The price must be a number above 0 and within the limit of the DECIMAL(8,2) column.
    $price = filter_var($raw_price, FILTER_VALIDATE_FLOAT);
    if ($price === false || $price <= 0 || $price > 99999.99) {
        $errors[] = "Price must be greater than 0 and no more than 99,999.99.";
        $price = 0;
    }

    return [$errors, [
        "name" => $name,
        "category" => $category,
        "description" => $description,
        "price" => round((float) $price, 2),
    ]];
}

// Checks the registration form. The username must be 4 to 30 letters, numbers or underscores.
// Duplicates are checked in auth/register.php.
function validate_registration(): array
{
    $errors = [];
    $name = post_string("full_name");
    $username = post_string("username");
    $email = post_string("email");
    $password = post_password("password");
    $confirm = post_password("confirm_password");

    if ($name === "" || mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = "Full name is required (2 to 100 characters).";
    }
    if (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $username)) {
        $errors[] = "Username must be 4 to 30 letters, numbers, or underscores.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
        $errors[] = "Please enter a valid email address.";
    }
    $password_error = password_problem($password);
    if ($password_error !== "") {
        $errors[] = $password_error;
    }
    if ($password !== $confirm) {
        $errors[] = "Password and confirmation do not match.";
    }

    return [$errors, [
        "full_name" => $name,
        "username" => $username,
        "email" => $email,
        "password" => $password,
    ]];
}

/** Staff-entered order: parallel item_id[] / quantity[] lists. */
function validate_order(): array
{
    $errors = [];
    $customer = post_string("customer_name");
    $notes = post_string("notes");
    $ids = $_POST["item_id"] ?? [];
    $quantities = $_POST["quantity"] ?? [];
    // item_id[] and quantity[] must be arrays; anything else is ignored.
    if (!is_array($ids) || !is_array($quantities)) {
        $ids = [];
        $quantities = [];
    }
    $ids = array_values($ids);
    $quantities = array_values($quantities);

    if ($customer === "" || mb_strlen($customer) < 2 || mb_strlen($customer) > 100) {
        $errors[] = "Customer name is required (2 to 100 characters).";
    }

    $lines = [];
    // Each row needs a valid item id and a whole-number quantity from 1 to 50.
    foreach ($ids as $index => $raw_id) {
        $item_id = filter_var($raw_id, FILTER_VALIDATE_INT);
        $quantity = filter_var($quantities[$index] ?? "", FILTER_VALIDATE_INT);
        if ($item_id === false || $item_id < 1 || $quantity === false || $quantity < 1 || $quantity > MAX_LINE_QUANTITY) {
            $errors[] = "Item " . ($index + 1) . " needs a menu item and a quantity from 1 to " . MAX_LINE_QUANTITY . ".";
            continue;
        }
        $lines[] = ["item_id" => (int) $item_id, "quantity" => (int) $quantity];
    }
    if (!$lines && !$errors) {
        $errors[] = "Add at least one menu item.";
    }
    if (count($lines) > MAX_LINES_PER_ORDER) {
        $errors[] = "An order can have at most " . MAX_LINES_PER_ORDER . " items.";
    }
    if (mb_strlen($notes) > 255) {
        $errors[] = "Notes must be 255 characters or fewer.";
    }

    return [$errors, ["customer_name" => $customer, "lines" => $lines, "notes" => $notes]];
}

/** Public order: quantity[menu_item_id] => amount, where 0 means "not ordered". */
function validate_public_order(): array
{
    $errors = [];
    $customer = post_string("customer_name");
    $notes = post_string("notes");
    $quantities = $_POST["quantity"] ?? [];

    if ($customer === "" || mb_strlen($customer) < 2 || mb_strlen($customer) > 100) {
        $errors[] = "Please enter your name (2 to 100 characters).";
    }
    if (mb_strlen($notes) > 255) {
        $errors[] = "Order notes must be 255 characters or fewer.";
    }

    $lines = [];
    if (is_array($quantities)) {
        // The public form sends every menu item. Items left at 0 are skipped; the rest become order
        // lines.
        foreach ($quantities as $raw_id => $raw_quantity) {
            $item_id = filter_var((string) $raw_id, FILTER_VALIDATE_INT);
            $quantity = filter_var($raw_quantity, FILTER_VALIDATE_INT);
            if ($item_id === false || $item_id < 1 || $quantity === false || $quantity < 0 || $quantity > MAX_PUBLIC_LINE_QUANTITY) {
                $errors[] = "Please check the item quantities and try again.";
                break;
            }
            if ($quantity > 0) {
                $lines[] = ["item_id" => (int) $item_id, "quantity" => (int) $quantity];
            }
        }
    }
    if (!$lines && !$errors) {
        $errors[] = "Choose at least one menu item.";
    }
    if (count($lines) > MAX_LINES_PER_ORDER) {
        $errors[] = "An order can include up to " . MAX_LINES_PER_ORDER . " menu items.";
    }

    return [$errors, ["customer_name" => $customer, "lines" => $lines, "notes" => $notes]];
}
