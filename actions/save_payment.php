<?php
require_once __DIR__ . "/../includes/validation.php";
require_login();
require_post("pages/transactions.php");

$ref = post_string("order_ref");
if (!preg_match('/^CT-\d{8}-\d+$/', $ref)) {
    flash_set("error", "Invalid order reference.");
    redirect("pages/transactions.php");
}
$payment_page = "pages/payment.php?ref=" . $ref;

$order = fetch_order($ref);
if (!$order) {
    flash_set("error", "That order was not found.");
    redirect("pages/transactions.php");
}
// Already paid: show the receipt instead of charging twice.
if (fetch_payment($ref)) redirect("pages/receipt.php?ref=" . $ref);
if ($order["status"] === "Cancelled") {
    flash_set("error", "A cancelled order cannot be paid.");
    redirect("pages/transactions.php");
}

$method = post_string("method");
if (!in_array($method, PAYMENT_METHODS, true)) {
    flash_set("error", "Please choose a payment method.");
    redirect($payment_page);
}
$reference_no = post_string("reference_no");
if (mb_strlen($reference_no) > 40) {
    old_set($_POST);
    flash_set("error", "Reference number must be 40 characters or fewer.");
    redirect($payment_page);
}

$due = round($order["total"], 2);
$received = $due;
$change = 0.0;
if ($method === "Cash") {
    $cash = filter_var(post_string("amount_received"), FILTER_VALIDATE_FLOAT);
    if ($cash === false || $cash > 1000000 || round($cash * 100) < round($due * 100)) {
        old_set($_POST);
        flash_set("error", "Enter cash received of at least " . peso($due) . ".");
        redirect($payment_page);
    }
    $received = round((float) $cash, 2);
    $change = round($received - $due, 2);
    $reference_no = "";
}

try {
    $user_id = current_user()["id"];
    $stmt = db()->prepare("INSERT INTO payments(order_ref,method,amount_due,amount_received,change_given,reference_no,received_by) VALUES(?,?,?,?,?,?,?)");
    $stmt->bind_param("ssdddsi", $ref, $method, $due, $received, $change, $reference_no, $user_id);
    $stmt->execute();
    $stmt->close();
    flash_set("success", "Payment received for order " . $ref . ".");
    redirect("pages/receipt.php?ref=" . $ref);
} catch (mysqli_sql_exception $ex) {
    // 1062: another request recorded this payment first.
    if ($ex->getCode() === 1062) redirect("pages/receipt.php?ref=" . $ref);
    old_set($_POST);
    flash_set("error", "The payment could not be saved. Please try again.");
    redirect($payment_page);
}
