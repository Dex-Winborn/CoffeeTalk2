<?php
/** Shared session, database, security, URL and rendering helpers. */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(["lifetime" => 0, "path" => "/", "httponly" => true, "samesite" => "Lax"]);
    session_start();
}
require_once __DIR__ . "/../config/database.php";

const ORDER_STATUSES = ["Pending", "Preparing", "Served", "Cancelled"];
const PAYMENT_METHODS = ["Cash", "QR", "Card"];
const MENU_CATEGORIES = [
    "Coffee (Iced / Hot)", "Premium Coffee", "Add-ons", "Non-Coffee",
    "Wellness Blend (Iced)", "Wellness Blend (Hot)", "Chill Blend (Frappe)",
    "Snack", "Pasta", "Classic Croffle", "Premium Croffle",
];

function base_url(): string
{
    static $base = null;
    if ($base !== null) return $base;
    $base = "";
    $root = realpath(__DIR__ . "/..");
    $doc = isset($_SERVER["DOCUMENT_ROOT"]) ? realpath($_SERVER["DOCUMENT_ROOT"]) : false;
    if ($root && $doc) {
        $root = str_replace("\\", "/", $root);
        $doc = rtrim(str_replace("\\", "/", $doc), "/");
        if (stripos($root, $doc) === 0) $base = rtrim(substr($root, strlen($doc)), "/");
    }
    return $base;
}
function url(string $path = ""): string { return base_url() . "/" . ltrim($path, "/"); }
function e($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8"); }
function redirect(string $path): void { header("Location: " . url($path)); exit; }
function peso($amount): string { return "₱" . number_format((float) $amount, 2); }
function price_label($amount): string
{
    $amount = (float) $amount;
    return floor($amount) == $amount ? number_format($amount, 0) : number_format($amount, 2);
}
function status_class(string $status): string { return "badge badge-" . strtolower($status); }
function allowed_status_changes(string $current): array
{
    return ["Pending" => ["Preparing", "Served", "Cancelled"], "Preparing" => ["Served", "Cancelled"], "Served" => [], "Cancelled" => []][$current] ?? [];
}
function order_base_ref(string $ref): string
{
    return preg_match('/^(CT-\d{8}-\d+)/', $ref, $m) ? $m[1] : $ref;
}
function group_orders(array $rows): array
{
    $groups = [];
    foreach ($rows as $row) {
        $base = order_base_ref($row["order_ref"]);
        if (!isset($groups[$base])) {
            $groups[$base] = ["ref" => $base, "customer_name" => $row["customer_name"], "taken_by" => $row["taken_by"] ?? null,
                "created_at" => $row["created_at"], "status" => $row["status"], "notes" => $row["notes"] ?? "", "items" => [], "total" => 0.0];
        }
        array_unshift($groups[$base]["items"], ["name" => $row["item_name"], "qty" => (int) $row["quantity"],
            "unit" => (float) ($row["unit_price"] ?? 0), "total" => (float) $row["total_amount"]]);
        $groups[$base]["total"] += (float) $row["total_amount"];
    }
    return array_values($groups);
}
function fetch_order(string $baseRef): ?array
{
    $like = $baseRef . "-%";
    $stmt = db()->prepare("SELECT o.order_ref, o.customer_name, o.quantity, o.unit_price, o.total_amount, o.status, o.notes, o.created_at, m.name AS item_name, u.full_name AS taken_by FROM orders o JOIN menu_items m ON m.id=o.item_id LEFT JOIN users u ON u.id=o.user_id WHERE o.order_ref=? OR o.order_ref LIKE ? ORDER BY o.id DESC");
    $stmt->bind_param("ss", $baseRef, $like);
    $stmt->execute();
    $groups = group_orders($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();
    return $groups[0] ?? null;
}
function fetch_payment(string $baseRef): ?array
{
    $stmt = db()->prepare("SELECT p.*, u.full_name AS cashier FROM payments p LEFT JOIN users u ON u.id=p.received_by WHERE p.order_ref=?");
    $stmt->bind_param("s", $baseRef);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $payment ?: null;
}
function fetch_payments(array $refs): array
{
    if (!$refs) return [];
    $refs = array_values($refs);
    $marks = implode(",", array_fill(0, count($refs), "?"));
    $stmt = db()->prepare("SELECT order_ref, method, amount_received, change_given FROM payments WHERE order_ref IN ($marks)");
    $stmt->bind_param(str_repeat("s", count($refs)), ...$refs);
    $stmt->execute();
    $map = [];
    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) $map[$row["order_ref"]] = $row;
    $stmt->close();
    return $map;
}
/** Activates or deactivates a menu item. Returns its name, or null when it no longer exists. */
function set_menu_active(int $itemId, bool $active): ?string
{
    $stmt = db()->prepare("SELECT name FROM menu_items WHERE id=?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$item) return null;

    $flag = $active ? 1 : 0;
    $stmt = db()->prepare("UPDATE menu_items SET is_active=? WHERE id=?");
    $stmt->bind_param("ii", $flag, $itemId);
    $stmt->execute();
    $stmt->close();
    return $item["name"];
}
/**
 * Saves an order and all of its lines in one transaction.
 * Prices come from the database, never from the request.
 * Returns ["ref", "items" => [[name, quantity, total]], "total"], or null when an item is unavailable.
 */
function create_order(?int $userId, string $customerName, array $lines, string $notes): ?array
{
    $conn = db();
    $find = $conn->prepare("SELECT name, price FROM menu_items WHERE id=? AND is_active=1");
    $insert = $conn->prepare("INSERT INTO orders(order_ref,user_id,item_id,customer_name,quantity,unit_price,total_amount,notes) VALUES(?,?,?,?,?,?,?,?)");
    $set_ref = $conn->prepare("UPDATE orders SET order_ref=? WHERE id=?");
    $base_ref = null;
    $saved = [];
    $grand_total = 0.0;
    $conn->begin_transaction();
    try {
        foreach (array_values($lines) as $position => $line) {
            $find->bind_param("i", $line["item_id"]);
            $find->execute();
            $item = $find->get_result()->fetch_assoc();
            if (!$item) {
                $conn->rollback();
                return null;
            }
            $unit = (float) $item["price"];
            $total = round($unit * $line["quantity"], 2);
            $grand_total += $total;
            $saved[] = ["name" => $item["name"], "quantity" => $line["quantity"], "total" => $total];
            $temp_ref = "TMP-" . bin2hex(random_bytes(6));
            $insert->bind_param("sisidds", $temp_ref, $line["item_id"], $customerName, $line["quantity"], $unit, $total, $notes);
            $insert->execute();
            $order_id = (int) $conn->insert_id;
            if ($base_ref === null) $base_ref = sprintf("CT-%s-%04d", date("Ymd"), $order_id);
            $ref = $position === 0 ? $base_ref : $base_ref . "-" . ($position + 1);
            $set_ref->bind_param("si", $ref, $order_id);
            $set_ref->execute();
        }
        $conn->commit();
        return ["ref" => $base_ref, "items" => $saved, "total" => round($grand_total, 2)];
    } catch (mysqli_sql_exception $ex) {
        $conn->rollback();
        throw $ex;
    }
}
function flash_set(string $type, string $message): void { $_SESSION["flash"][] = ["type" => $type, "message" => $message]; }
function flash_render(): string
{
    if (empty($_SESSION["flash"])) return "";
    $html = "";
    foreach ($_SESSION["flash"] as $flash) {
        $type = $flash["type"] === "success" ? "success" : "error";
        $html .= '<div class="alert alert-' . $type . '" role="alert">' . e($flash["message"]) . "</div>";
    }
    unset($_SESSION["flash"]);
    return $html;
}
function old_set(array $data): void
{
    unset($data["password"], $data["confirm_password"], $data["csrf_token"]);
    $_SESSION["old"] = $data;
}
function old_get(): array
{
    $old = $_SESSION["old"] ?? [];
    unset($_SESSION["old"]);
    return is_array($old) ? $old : [];
}
function csrf_token(): string
{
    if (empty($_SESSION["csrf"])) $_SESSION["csrf"] = bin2hex(random_bytes(32));
    return $_SESSION["csrf"];
}
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function require_post(string $backTo): void
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") redirect($backTo);
    $sent = $_POST["csrf_token"] ?? "";
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        flash_set("error", "Your session expired or the request was invalid. Please try again.");
        redirect($backTo);
    }
}
function is_logged_in(): bool { return !empty($_SESSION["user_id"]); }
function require_login(): void
{
    if (!is_logged_in()) { flash_set("error", "Please log in to continue."); redirect("auth/login.php"); }
}
function require_admin(): void
{
    require_login();
    if (!is_admin()) { flash_set("error", "Administrator access is required."); redirect("pages/dashboard.php"); }
}
function is_admin(): bool { return ($_SESSION["role"] ?? "staff") === "admin"; }
function guest_only(): void
{
    if (is_logged_in()) redirect(is_admin() ? "pages/admin_dashboard.php" : "pages/dashboard.php");
}
function current_user(): array
{
    return ["id" => (int) ($_SESSION["user_id"] ?? 0), "full_name" => $_SESSION["full_name"] ?? "", "username" => $_SESSION["username"] ?? "", "role" => $_SESSION["role"] ?? "staff"];
}
function public_menu_html(): string
{
    $empty = '<p class="menu-empty">Our menu is being updated. Please check back soon.</p>';
    try { $result = db_connect()->query("SELECT name, category, description, price FROM menu_items WHERE is_active=1 ORDER BY id"); }
    catch (mysqli_sql_exception $e) { return $empty; }
    $groups = [];
    while ($row = $result->fetch_assoc()) $groups[$row["category"]][] = $row;
    if (!$groups) return $empty;
    $columns = [["Coffee (Iced / Hot)", "Premium Coffee", "Add-ons"], ["Non-Coffee", "Wellness Blend (Iced)", "Wellness Blend (Hot)", "Chill Blend (Frappe)"], ["Snack", "Pasta", "Classic Croffle", "Premium Croffle"]];
    $known = array_merge(...$columns);
    foreach (array_keys($groups) as $category) if (!in_array($category, $known, true)) $columns[2][] = $category;
    $html = '<div class="full-menu">';
    foreach ($columns as $categories) {
        $html .= '<div class="menu-column">';
        foreach ($categories as $category) if (!empty($groups[$category])) $html .= menu_category_html($category, $groups[$category]);
        $html .= "</div>";
    }
    return $html . "</div>";
}
function menu_category_html(string $category, array $items): string
{
    $title = e($category);
    if (preg_match('/^(.*?)\s*(\(.*\))$/', $category, $m)) $title = e($m[1]) . " <span>" . e($m[2]) . "</span>";
    if ($category === "Wellness Blend (Hot)") $open = '<div class="menu-category menu-subcategory"><h4>(Hot)</h4>';
    elseif ($category === "Add-ons") $open = '<div class="menu-addons"><h3>' . $title . "</h3>";
    else $open = '<div class="menu-category"><h3>' . $title . "</h3>";
    $html = $open . '<div class="menu-list">';
    foreach ($items as $item) {
        $note = $item["description"] !== "" ? " <small>" . e($item["description"]) . "</small>" : "";
        $html .= "<div><span>" . e($item["name"]) . $note . "</span><b>" . e(price_label($item["price"])) . "</b></div>";
    }
    return $html . "</div></div>";
}
/** Loads the given base references with all of their lines, in the same order. */
function fetch_orders_by_refs(array $refs): array
{
    if (!$refs) return [];
    $refs = array_values($refs);
    $marks = implode(",", array_fill(0, count($refs), "?"));
    $stmt = db()->prepare("SELECT o.order_ref, o.customer_name, o.quantity, o.unit_price, o.total_amount, o.status, o.notes, o.created_at, m.name AS item_name, u.full_name AS taken_by FROM orders o JOIN menu_items m ON m.id=o.item_id LEFT JOIN users u ON u.id=o.user_id WHERE SUBSTRING_INDEX(o.order_ref,'-',3) IN ($marks) ORDER BY o.id DESC");
    $stmt->bind_param(str_repeat("s", count($refs)), ...$refs);
    $stmt->execute();
    $orders = group_orders($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    $stmt->close();

    // group_orders() keys by reference order of the rows; restore the requested order.
    $by_ref = [];
    foreach ($orders as $order) $by_ref[$order["ref"]] = $order;
    $sorted = [];
    foreach ($refs as $ref) if (isset($by_ref[$ref])) $sorted[] = $by_ref[$ref];
    return $sorted;
}
/** Most recent orders, newest first. */
function fetch_recent_orders(int $limit = 6): array
{
    $stmt = db()->prepare("SELECT SUBSTRING_INDEX(order_ref,'-',3) AS base_ref FROM orders GROUP BY base_ref ORDER BY MAX(id) DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $refs = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), "base_ref");
    $stmt->close();
    return fetch_orders_by_refs($refs);
}
/**
 * One page of orders, newest first. $where clauses use the "o." alias of `orders`.
 * Returns ["orders", "total", "page", "pages"].
 */
function fetch_order_page(array $where, string $types, array $params, int $perPage, int $page): array
{
    $conn = db();
    $condition = $where ? " WHERE " . implode(" AND ", $where) : "";

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT SUBSTRING_INDEX(o.order_ref,'-',3)) AS total FROM orders o" . $condition);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = (int) ($stmt->get_result()->fetch_assoc()["total"] ?? 0);
    $stmt->close();

    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);

    $stmt = $conn->prepare("SELECT SUBSTRING_INDEX(o.order_ref,'-',3) AS base_ref FROM orders o" . $condition . " GROUP BY base_ref ORDER BY MAX(o.id) DESC LIMIT ? OFFSET ?");
    $page_params = array_merge($params, [$perPage, ($page - 1) * $perPage]);
    $stmt->bind_param($types . "ii", ...$page_params);
    $stmt->execute();
    $refs = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), "base_ref");
    $stmt->close();

    return ["orders" => fetch_orders_by_refs($refs), "total" => $total, "page" => $page, "pages" => $pages];
}
