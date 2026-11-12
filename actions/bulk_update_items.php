<?php
// Bulk availability update: applies many menu-item Available/Unavailable changes in one request.
// This is what the "Confirm changes" button on pages/records.php posts to, so the list is only
// reloaded once (not after every single change).
require_once __DIR__ . "/../includes/validation.php";
require_login();
require_post("pages/records.php");

// Send the user back to the same filtered list they were editing. Only the known filter fields are
// accepted, and each is checked against the values records.php understands.
$q = post_string("return_q");
$category = post_string("return_category");
$status = post_string("return_status");
if (!in_array($status, ["active", "inactive", "all"], true)) $status = "active";
if (!in_array($category, MENU_CATEGORIES, true)) $category = "";
$backParams = [];
if ($q !== "") $backParams["q"] = $q;
if ($category !== "") $backParams["category"] = $category;
if ($status !== "active") $backParams["status"] = $status;
$back = "pages/records.php" . ($backParams ? "?" . http_build_query($backParams) : "");

// The form sends active[<id>] = "1" (Available) or "0" (Unavailable) for every row. Only the rows the
// browser left enabled (the ones that actually changed) are present.
$active = $_POST["active"] ?? [];
if (!is_array($active) || !$active) {
    flash_set("error", "No menu changes were submitted.");
    redirect($back);
}

$conn = db();
$find = $conn->prepare("SELECT is_active FROM menu_items WHERE id=?");
$update = $conn->prepare("UPDATE menu_items SET is_active=? WHERE id=?");
$changed = 0;
$missing = 0;

// Save everything together. If anything fails, rollback() cancels the whole batch.
$conn->begin_transaction();
try {
    foreach ($active as $raw_id => $raw_state) {
        $id = filter_var($raw_id, FILTER_VALIDATE_INT);
        $state = filter_var($raw_state, FILTER_VALIDATE_INT);
        // Ignore anything that is not a positive id with a 0/1 value.
        if ($id === false || $id < 1 || ($state !== 0 && $state !== 1)) continue;

        $find->bind_param("i", $id);
        $find->execute();
        $row = $find->get_result()->fetch_assoc();
        if (!$row) { $missing++; continue; }
        // Skip rows whose saved value already matches, so the count reflects real changes only.
        if ((int) $row["is_active"] === $state) continue;

        $update->bind_param("ii", $state, $id);
        $update->execute();
        $changed++;
    }
    $conn->commit();
} catch (mysqli_sql_exception $ex) {
    $conn->rollback();
    flash_set("error", "The menu changes could not be saved. Please try again.");
    redirect($back);
}
$find->close();
$update->close();

if ($changed > 0) {
    flash_set("success", $changed === 1
        ? "1 menu item was updated."
        : $changed . " menu items were updated.");
} elseif ($missing > 0) {
    flash_set("error", "Some items no longer exist and were not updated.");
} else {
    flash_set("success", "No changes to save.");
}
redirect($back);