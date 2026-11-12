<?php
// Payment page for one order. Only Cash payment is supported.
require_once __DIR__ . "/../includes/functions.php";
require_login();

// Order reference from the link, checked against the expected format.
$ref = isset($_GET["ref"]) && is_string($_GET["ref"]) ? $_GET["ref"] : "";
if (!preg_match('/^CT-\d{8}-\d+$/', $ref)) {
    flash_set("error", "Invalid order reference.");
    redirect("pages/transactions.php");
}

// Load the order. Redirect if it was not found, is already paid, or is cancelled.
$order = fetch_order($ref);
if (!$order) {
    flash_set("error", "That order was not found.");
    redirect("pages/transactions.php");
}
if (fetch_payment($ref)) redirect("pages/receipt.php?ref=" . $ref);
if ($order["status"] === "Cancelled") {
    flash_set("error", "A cancelled order cannot be paid.");
    redirect("pages/transactions.php");
}

// Values typed before an earlier error, so the form is filled in again.
$old = old_get();
$received_value = is_string($old["amount_received"] ?? null) ? $old["amount_received"] : "";
// Total due. It is also passed to js/payment.js through the data-total attribute.
$total = round($order["total"], 2);
$total_input = number_format($total, 2, ".", "");

// Group add-ons under the main item they belong to (shared with the dashboard and receipts).
$processed_items = group_items_with_addons($order['items']);

$pageTitle = "Payment — Coffee Talk";
$activePage = "transactions";
$extraScripts = ["js/payment.js"];
require __DIR__ . "/../includes/header.php";
?>
<main class="app-main"><div class="app-wrap app-wrap-narrow">
<?= flash_render() ?>
<div class="app-head"><div><div class="eyebrow">Order <?= e($order['ref']) ?></div><h1 class="app-title">Take payment</h1><p class="app-lead">Cash payment only.</p></div></div>

<?php /* Order summary: the items and the total due. */ ?>
<section class="panel">
<div class="panel-head"><h2>Order summary</h2><span class="cell-note"><?= e($order['customer_name']) ?></span></div>
<ul class="summary-lines">
<?php foreach ($processed_items as $line): ?>
<li>
<span>
<?= (int) $line['qty'] ?> × <?= e($line['name']) ?>
<?php if (!empty($line['addons'])): ?>
<div class="receipt-addon-list">
<?php foreach ($line['addons'] as $addon): ?>
<div class="receipt-addon-line">+ <?= e($addon['name']) ?> ×<?= (int) $addon['qty'] ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</span>
<span><?= e(peso($line['total'] + array_sum(array_column($line['addons'], 'total')))) ?></span>
</li>
<?php endforeach; ?>
</ul>
<div class="summary-total"><span>Total due</span><strong><?= e(peso($total)) ?></strong></div>
</section>

<?php /* Payment form - Cash only */ ?>
<form class="panel" method="post" action="<?= e(url('actions/save_payment.php')) ?>" id="payment-form" data-total="<?= e($total_input) ?>" novalidate>
<?= csrf_field() ?>
<input type="hidden" name="order_ref" value="<?= e($order['ref']) ?>" />
<input type="hidden" name="method" value="Cash" />
<div class="panel-head"><h2>Payment method</h2></div>
<div class="pay-option-selected"><strong>Cash</strong><small>Customer hands cash</small></div>
<div id="cash-fields">
<div class="field"><label for="amount_received">Amount received (₱)</label><input type="number" id="amount_received" name="amount_received" min="<?= e($total_input) ?>" step="0.01" inputmode="decimal" placeholder="0.00" value="<?= e($received_value) ?>" /></div>
<div class="quick-cash"><button class="btn-sm" type="button" data-exact>Exact</button><button class="btn-sm" type="button" data-amount="100">₱100</button><button class="btn-sm" type="button" data-amount="200">₱200</button><button class="btn-sm" type="button" data-amount="500">₱500</button><button class="btn-sm" type="button" data-amount="1000">₱1,000</button></div>
<div class="change-box" id="change-box"><span>Change</span><strong id="change-value">₱0.00</strong></div>
</div>
<span class="field-error" id="payment-error" aria-live="polite"></span>
<div class="form-actions"><button class="button" type="submit">Confirm payment</button><a class="text-link" href="<?= e(url('pages/transactions.php')) ?>">Pay later</a></div>
</form>
</div></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>