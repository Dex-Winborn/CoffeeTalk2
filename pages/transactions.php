<?php
// Orders page: a form to create an order (the transaction), a filterable table of orders and a status
// drop-down on each row.
// Saving, payment and status changes are handled by the files in actions/.
require_once __DIR__ . "/../includes/functions.php";
require_login();

$conn = db();
// Values from a failed save, used to fill the form in again.
$old = old_get();
$customer = isset($old["customer_name"]) && is_string($old["customer_name"]) ? $old["customer_name"] : "";
$notes = isset($old["notes"]) && is_string($old["notes"]) ? $old["notes"] : "";

// Re-fill the order form after a failed save.
$order_lines = [];
if (isset($old["item_id"]) && is_array($old["item_id"])) {
    $old_ids = array_values($old["item_id"]);
    $old_quantities = isset($old["quantity"]) && is_array($old["quantity"]) ? array_values($old["quantity"]) : [];
    $old_addon_ids = isset($old["addon_id"]) && is_array($old["addon_id"]) ? array_values($old["addon_id"]) : [];
    $old_addon_quantities = isset($old["addon_quantity"]) && is_array($old["addon_quantity"]) ? array_values($old["addon_quantity"]) : [];
    $old_addon_parent = isset($old["addon_parent"]) && is_array($old["addon_parent"]) ? array_values($old["addon_parent"]) : [];
    
    foreach ($old_ids as $index => $old_id) {
        $old_quantity = $old_quantities[$index] ?? null;
        $order_lines[] = [
            "id" => is_string($old_id) ? $old_id : "",
            "qty" => is_string($old_quantity) ? $old_quantity : "1",
            "addons" => [],
        ];
    }
    
    // Attach addons to their parent items
    foreach ($old_addon_ids as $index => $addon_id) {
        $parent_idx = isset($old_addon_parent[$index]) ? (int)$old_addon_parent[$index] : -1;
        if ($parent_idx >= 0 && isset($order_lines[$parent_idx])) {
            $order_lines[$parent_idx]["addons"][] = [
                "id" => is_string($addon_id) ? $addon_id : "",
                "qty" => is_string($old_addon_quantities[$index] ?? null) ? $old_addon_quantities[$index] : "1",
            ];
        }
    }
}
if (!$order_lines) $order_lines[] = ["id" => "", "qty" => "1", "addons" => []];

// Active menu items grouped by category, for the item drop-downs.
$menu = [];
$addons = [];
$result = $conn->query("SELECT id,name,category,price FROM menu_items WHERE is_active=1 ORDER BY category,name");
while ($row = $result->fetch_assoc()) {
    if ($row["category"] === "Add-ons") {
        $addons[] = $row;
    } else {
        $menu[$row["category"]][] = $row;
    }
}

// Filters
$q = isset($_GET["q"]) && is_string($_GET["q"]) ? trim($_GET["q"]) : "";
$status = isset($_GET["status"]) && is_string($_GET["status"]) ? $_GET["status"] : "";
if (!in_array($status, ORDER_STATUSES, true)) $status = "";

$where = [];
$types = "";
$params = [];
if ($q !== "") {
    $where[] = "(o.order_ref LIKE ? OR o.customer_name LIKE ?)";
    $types .= "ss";
    $like = "%" . addcslashes($q, "%_\\") . "%";
    $params[] = $like;
    $params[] = $like;
}
if ($status !== "") {
    $where[] = "o.status=?";
    $types .= "s";
    $params[] = $status;
}

// Load one page of 25 orders, then the payments of those orders.
$page = isset($_GET["page"]) ? max(1, (int) $_GET["page"]) : 1;
$list = fetch_order_page($where, $types, $params, 25, $page);
$orders = $list["orders"];
$payments = fetch_payments(array_column($orders, "ref"));

// Keeps the search and status filters in the Newer and Older links.
$filters = array_filter(["q" => $q, "status" => $status], function ($value) { return $value !== ""; });
$page_url = function (int $number) use ($filters): string {
    return url("pages/transactions.php?" . http_build_query($filters + ["page" => $number]));
};

// Page settings, then the shared header.
$pageTitle = "Orders — Coffee Talk";
$activePage = "transactions";
$extraScripts = ["js/validation.js", "js/main.js"];
require __DIR__ . "/../includes/header.php";
?>
<main class="app-main"><div class="app-wrap">
<?= flash_render() ?>
<div class="app-head">
<div><div class="eyebrow">Transactions</div><h1 class="app-title">Orders</h1><p class="app-lead">Take an order, process payment, and update its service status. Changes are saved in MySQL.</p></div>
</div>

<section class="panel">
<div class="panel-head"><h2>New order</h2></div>
<?php if ($menu): ?>
<?php /* New order form. js/validation.js adds and removes item rows and updates the total. */ ?>
<form class="order-form" method="post" action="<?= e(url('actions/save_transaction.php')) ?>" data-validate novalidate>
<?= csrf_field() ?>
<div class="field"><label for="customer_name">Customer name</label><input type="text" id="customer_name" name="customer_name" required minlength="2" maxlength="100" value="<?= e($customer) ?>" /><span class="field-error" aria-live="polite"></span></div>
<?php /* One row per item (menu item and quantity). */ ?>
<div class="order-lines" id="order-lines">
<?php foreach ($order_lines as $line_index => $line): ?>
<div class="order-line" data-line-index="<?= $line_index ?>">
<div class="order-line-main">
<div class="field"><label>Menu item</label><select name="item_id[]" required><option value="">Choose an item…</option><?php foreach ($menu as $category => $items): ?><optgroup label="<?= e($category) ?>"><?php foreach ($items as $item): ?><option value="<?= (int) $item['id'] ?>" data-price="<?= e($item['price']) ?>"<?= (string) $item['id'] === $line['id'] ? ' selected' : '' ?>><?= e($item['name']) ?> — <?= e(peso($item['price'])) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select><span class="field-error" aria-live="polite"></span></div>
<div class="field field-qty"><label>Qty</label><input type="number" name="quantity[]" required min="1" max="50" step="1" value="<?= e($line['qty']) ?>" /><span class="field-error" aria-live="polite"></span></div>
<button class="btn-sm line-remove" type="button" aria-label="Remove item">×</button>
</div>
<?php /* Add-on option bar - smaller than the item to indicate it's an option */ ?>
<div class="addon-bar" data-line-index="<?= $line_index ?>">
<div class="addon-fields">
<div class="field addon-select-field"><label><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg> Add add-on</label><select name="addon_id[]" class="addon-select" data-line-index="<?= $line_index ?>"><option value="">Choose an add-on…</option><?php foreach ($addons as $addon): ?><option value="<?= (int) $addon['id'] ?>" data-price="<?= e($addon['price']) ?>"><?= e($addon['name']) ?> — <?= e(peso($addon['price'])) ?></option><?php endforeach; ?></select><span class="field-error" aria-live="polite"></span></div>
<div class="field field-qty addon-qty-field"><label>Qty</label><input type="number" name="addon_quantity[]" class="addon-qty" value="1" min="1" max="20" /><span class="field-error" aria-live="polite"></span></div>
<input type="hidden" name="addon_parent[]" value="<?= $line_index ?>" />
<button type="button" class="addon-add-btn" data-line-index="<?= $line_index ?>">Add</button>
</div>
</div>
<?php if (!empty($line['addons'])): ?>
<div class="addon-list" data-line-index="<?= $line_index ?>">
<?php foreach ($line['addons'] as $addon_index => $addon): 
    // Find addon price from the addons list
    $addon_price = 0;
    foreach ($addons as $a) {
        if ((int) $a['id'] === (int) $addon['id']) {
            $addon_price = (float) $a['price'];
            break;
        }
    }
    $addon_line_total = round($addon_price * $addon['qty'], 2);
?>
<div class="addon-item" data-addon-index="<?= $addon_index ?>">
<div class="addon-item-fields">
<div class="field addon-select-field"><label>Add-on</label><select name="addon_id[]" class="addon-select"><option value="">Choose an add-on…</option><?php foreach ($addons as $a): ?><option value="<?= (int) $a['id'] ?>" data-price="<?= e($a['price']) ?>"<?= (string) $a['id'] === $addon['id'] ? ' selected' : '' ?>><?= e($a['name']) ?> — <?= e(peso($a['price'])) ?></option><?php endforeach; ?></select><span class="field-error" aria-live="polite"></span></div>
<div class="field field-qty addon-qty-field"><label>Qty</label><input type="number" name="addon_quantity[]" class="addon-qty" value="<?= e($addon['qty']) ?>" min="1" max="20" /><span class="field-error" aria-live="polite"></span></div>
<input type="hidden" name="addon_parent[]" value="<?= $line_index ?>" />
</div>
<button type="button" class="addon-remove-btn" aria-label="Remove add-on" data-line-index="<?= $line_index ?>" data-addon-index="<?= $addon_index ?>">×</button>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<div class="field-wide"><button class="btn-sm" type="button" id="add-line">+ Add another item</button></div>
<div class="field field-wide"><label for="notes">Notes <span class="optional">(optional)</span></label><input type="text" id="notes" name="notes" maxlength="255" placeholder="Less sweet, oat milk, no ice…" value="<?= e($notes) ?>" /><span class="field-error" aria-live="polite"></span></div>
<div class="order-submit"><div class="order-total">Total <strong id="order-total">₱0.00</strong></div><button class="button" type="submit">Save order</button></div>
</form>
<?php else: ?>
<p class="empty-state">There are no active menu items. <a class="text-link" href="<?= e(url('pages/records.php')) ?>">Add or restore one first →</a></p>
<?php endif; ?>
</section>

<?php /* Search by reference or customer, and filter by status. */ ?>
<form class="filter-bar" method="get" action="<?= e(url('pages/transactions.php')) ?>">
<div class="field"><label for="q">Search</label><input type="search" id="q" name="q" placeholder="Reference or customer" value="<?= e($q) ?>" /></div>
<div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option><?php foreach (ORDER_STATUSES as $option): ?><option value="<?= e($option) ?>"<?= $option === $status ? ' selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
<div class="filter-actions"><button class="button" type="submit">Filter</button><a class="text-link" href="<?= e(url('pages/transactions.php')) ?>">Reset</a></div>
</form>

<section class="panel">
<div class="panel-head"><h2><?= (int) $list['total'] ?> <?= $list['total'] === 1 ? 'order' : 'orders' ?></h2><?php if ($list['pages'] > 1): ?><span class="cell-note">Page <?= (int) $list['page'] ?> of <?= (int) $list['pages'] ?></span><?php endif; ?></div>
<?php if ($orders): ?>
<div class="table-wrap"><table class="data-table">
<thead><tr><th>Reference</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Payment</th><th>Taken by</th><th>Placed</th><th>Update</th></tr></thead>
<tbody>
<?php /* One table row per order. */ ?>
<?php foreach ($orders as $order): ?>
<?php
// The payment of this order, if any, and the statuses it may move to. A paid order cannot be
// cancelled.
$pay = $payments[$order['ref']] ?? null;
$next = allowed_status_changes($order['status']);
if ($pay) $next = array_values(array_diff($next, ["Cancelled"]));
?>
<tr>
<td data-label="Reference"><?= e($order['ref']) ?></td>
<td data-label="Customer"><?= e($order['customer_name']) ?><?php if ($order['notes'] !== ''): ?><small class="cell-note"><?= e($order['notes']) ?></small><?php endif; ?></td>
<td data-label="Items"><ul class="item-list"><?php foreach (group_items_with_addons($order['items']) as $line): ?><?php $line_total = $line['total'] + array_sum(array_column($line['addons'], 'total')); ?><li><?= (int) $line['qty'] ?> × <?= e($line['name']) ?><small><?= e(peso($line_total)) ?></small><?php if (!empty($line['addons'])): ?><ul class="item-addon-list"><?php foreach ($line['addons'] as $addon): ?><li>+ <?= e($addon['name']) ?> ×<?= (int) $addon['qty'] ?></li><?php endforeach; ?></ul><?php endif; ?></li><?php endforeach; ?></ul></td>
<td data-label="Total"><strong><?= e(peso($order['total'])) ?></strong></td>
<td data-label="Status"><span class="<?= e(status_class($order['status'])) ?>"><?= e($order['status']) ?></span></td>
<?php /*
   Payment column: Paid with a receipt link, Unpaid with a Take payment button, or a dash for cancelled
   orders.
*/ ?>
<td data-label="Payment">
<?php if ($pay): ?>
<div class="pay-cell"><span class="badge badge-paid">Paid · <?= e($pay['method']) ?></span><a class="btn-sm" href="<?= e(url('pages/receipt.php?ref=' . $order['ref'])) ?>">Receipt</a></div>
<?php elseif ($order['status'] === 'Cancelled'): ?>
—
<?php else: ?>
<div class="pay-cell"><span class="badge badge-unpaid">Unpaid</span><a class="btn-sm" href="<?= e(url('pages/payment.php?ref=' . $order['ref'])) ?>">Take payment</a></div>
<?php endif; ?>
</td>
<td data-label="Taken by"><?= e($order['taken_by'] ?? 'Online (customer)') ?></td>
<td data-label="Placed"><?= e(date('M j, g:i A', strtotime($order['created_at']))) ?></td>
<?php /*
   Status update: the drop-down only offers the allowed next statuses. Cancelling asks for confirmation
   (data-confirm).
*/ ?>
<td data-label="Update">
<?php if ($next): ?>
<form class="status-form" method="post" action="<?= e(url('actions/update_transaction.php')) ?>" data-confirm="Cancel order <?= e($order['ref']) ?>? This cannot be undone." data-confirm-value="Cancelled">
<?= csrf_field() ?>
<input type="hidden" name="order_ref" value="<?= e($order['ref']) ?>" />
<select name="status" aria-label="New status"><?php foreach ($next as $option): ?><option value="<?= e($option) ?>"><?= e($option) ?></option><?php endforeach; ?></select>
<button class="btn-sm" type="submit">Update</button>
</form>
<?php else: ?>
<span class="cell-note">Final</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php /* Pagination links. */ ?>
<?php if ($list['pages'] > 1): ?>
<div class="filter-actions">
<?php if ($list['page'] > 1): ?><a class="btn-sm" href="<?= e($page_url($list['page'] - 1)) ?>">← Newer</a><?php endif; ?>
<?php if ($list['page'] < $list['pages']): ?><a class="btn-sm" href="<?= e($page_url($list['page'] + 1)) ?>">Older →</a><?php endif; ?>
</div>
<?php endif; ?>
<?php else: ?>
<p class="empty-state">No orders match these filters.</p>
<?php endif; ?>
</section>
</div></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>