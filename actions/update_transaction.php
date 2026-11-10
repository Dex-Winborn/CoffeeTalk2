<?php
require_once __DIR__ . "/../includes/validation.php";
require_login();
require_post("pages/transactions.php");

$base_ref = post_string("order_ref");
$new_status = post_string("status");
if (!preg_match('/^CT-\d{8}-\d+$/', $base_ref) || !in_array($new_status, ORDER_STATUSES, true)) {
    flash_set("error", "Invalid order or status.");
    redirect("pages/transactions.php");
}

try {
    $conn = db();
    $stmt = $conn->prepare("SELECT id, status FROM orders WHERE order_ref=? OR order_ref LIKE ?");
    $like = $base_ref . "-%";
    $stmt->bind_param("ss", $base_ref, $like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!$rows) {
        flash_set("error", "That order no longer exists.");
        redirect("pages/transactions.php");
    }

    // Each line only moves along an allowed transition, so a served order stays served.
    $update = $conn->prepare("UPDATE orders SET status=? WHERE id=? AND status=?");
    $changed = 0;
    foreach ($rows as $row) {
        if (!in_array($new_status, allowed_status_changes($row["status"]), true)) continue;
        $id = (int) $row["id"];
        $update->bind_param("sis", $new_status, $id, $row["status"]);
        $update->execute();
        $changed += $update->affected_rows;
    }
    $update->close();

    flash_set($changed > 0 ? "success" : "error", $changed > 0
        ? "Order " . $base_ref . " is now " . $new_status . "."
        : "Order " . $base_ref . " cannot change to " . $new_status . " from its current status.");
} catch (mysqli_sql_exception $ex) {
    flash_set("error", "The status could not be updated. Please try again.");
}
redirect("pages/transactions.php");
