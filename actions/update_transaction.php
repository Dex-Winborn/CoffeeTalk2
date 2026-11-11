<?php
// Changes an order's status (Pending, Preparing, Served, Cancelled). An order with several items has
// several rows, and all of them are updated together.
require_once __DIR__ . "/../includes/validation.php";
require_login();
require_post("pages/transactions.php");

// Check that the reference has the right format and the new status is one of the allowed values.
$base_ref = post_string("order_ref");
$new_status = post_string("status");
if (!preg_match('/^CT-\d{8}-\d+$/', $base_ref) || !in_array($new_status, ORDER_STATUSES, true)) {
    flash_set("error", "Invalid order or status.");
    redirect("pages/transactions.php");
}

try {
    $conn = db();
    // Find every row of this order (the base reference and its -2, -3 ... lines).
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

    // A paid order cannot be cancelled.
    if ($new_status === "Cancelled" && fetch_payment($base_ref)) {
        flash_set("error", "Order " . $base_ref . " is already paid and cannot be cancelled.");
        redirect("pages/transactions.php");
    }

    // Each line only moves along an allowed transition, so a served order stays served.
    $update = $conn->prepare("UPDATE orders SET status=? WHERE id=? AND status=?");
    $changed = 0;
    foreach ($rows as $row) {
        if (!in_array($new_status, allowed_status_changes($row["status"]), true)) continue;
        $id = (int) $row["id"];
        // The extra status=? condition stops the update if the status changed after it was read.
        $update->bind_param("sis", $new_status, $id, $row["status"]);
        $update->execute();
        $changed += $update->affected_rows;
    }
    $update->close();

    // Tell the user whether anything changed.
    flash_set($changed > 0 ? "success" : "error", $changed > 0
        ? "Order " . $base_ref . " is now " . $new_status . "."
        : "Order " . $base_ref . " cannot change to " . $new_status . " from its current status.");
} catch (mysqli_sql_exception $ex) {
    flash_set("error", "The status could not be updated. Please try again.");
}
redirect("pages/transactions.php");
