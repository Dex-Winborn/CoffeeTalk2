<?php
require_once __DIR__ . "/../includes/functions.php";
require_login();

$ref = isset($_GET["ref"]) && is_string($_GET["ref"]) ? $_GET["ref"] : "";
if (!preg_match('/^CT-\d{8}-\d+$/', $ref)) {
    flash_set("error", "Invalid order reference.");
    redirect("pages/transactions.php");
}

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

$old = old_get();
$method = isset($old["method"]) && in_array($old["method"], PAYMENT_METHODS, true) ? $old["method"] : "Cash";
$received_value = is_string($old["amount_received"] ?? null) ? $old["amount_received"] : "";
$reference_value = is_string($old["reference_no"] ?? null) ? $old["reference_no"] : "";
$total = round($order["total"], 2);
$total_input = number_format($total, 2, ".", "");

$pageTitle = "Payment — Coffee Talk";
$activePage = "transactions";
$extraScripts = ["js/payment.js"];
require __DIR__ . "/../includes/header.php";
?>
<main class="app-main"><div class="app-wrap app-wrap-narrow">
<?= flash_render() ?>
<div class="app-head"><div><div class="eyebrow">Order <?= e($order['ref']) ?></div><h1 class="app-title">Take payment</h1><p class="app-lead">Choose cash or cashless payment and save the transaction.</p></div></div>

<section class="panel">
<div class="panel-head"><h2>Order summary</h2><span class="cell-note"><?= e($order['customer_name']) ?></span></div>
<ul class="summary-lines"><?php foreach ($order['items'] as $line): ?><li><span><?= (int) $line['qty'] ?> × <?= e($line['name']) ?></span><span><?= e(peso($line['total'])) ?></span></li><?php endforeach; ?></ul>
<div class="summary-total"><span>Total due</span><strong><?= e(peso($total)) ?></strong></div>
</section>

<form class="panel" method="post" action="<?= e(url('actions/save_payment.php')) ?>" id="payment-form" data-total="<?= e($total_input) ?>" novalidate>
<?= csrf_field() ?>
<input type="hidden" name="order_ref" value="<?= e($order['ref']) ?>" />
<div class="panel-head"><h2>Payment method</h2></div>
<div class="pay-options" role="radiogroup" aria-label="Payment method">
<label class="pay-option"><input type="radio" name="method" value="Cash"<?= $method === 'Cash' ? ' checked' : '' ?> /><span class="pay-card"><strong>Cash</strong><small>Customer hands cash</small></span></label>
<label class="pay-option"><input type="radio" name="method" value="QR"<?= $method === 'QR' ? ' checked' : '' ?> /><span class="pay-card"><strong>Cashless · QR</strong><small>GCash, Maya, bank QR</small></span></label>
<label class="pay-option"><input type="radio" name="method" value="Card"<?= $method === 'Card' ? ' checked' : '' ?> /><span class="pay-card"><strong>Cashless · Card</strong><small>Debit or credit card</small></span></label>
</div>
<div id="cash-fields">
<div class="field"><label for="amount_received">Amount received (₱)</label><input type="number" id="amount_received" name="amount_received" min="<?= e($total_input) ?>" step="0.01" inputmode="decimal" placeholder="0.00" value="<?= e($received_value) ?>" /></div>
<div class="quick-cash"><button class="btn-sm" type="button" data-exact>Exact</button><button class="btn-sm" type="button" data-amount="100">₱100</button><button class="btn-sm" type="button" data-amount="200">₱200</button><button class="btn-sm" type="button" data-amount="500">₱500</button><button class="btn-sm" type="button" data-amount="1000">₱1,000</button></div>
<div class="change-box" id="change-box"><span>Change</span><strong id="change-value">₱0.00</strong></div>
</div>
<div id="cashless-fields" hidden>
<div class="change-box"><span>Amount to pay</span><strong><?= e(peso($total)) ?></strong></div>
<p class="field-hint" id="cashless-hint"></p>
<div class="field"><label for="reference_no">Reference / approval no. <span class="optional">(optional)</span></label><input type="text" id="reference_no" name="reference_no" maxlength="40" value="<?= e($reference_value) ?>" /></div>
</div>
<span class="field-error" id="payment-error" aria-live="polite"></span>
<div class="form-actions"><button class="button" type="submit">Confirm payment</button><a class="text-link" href="<?= e(url('pages/transactions.php')) ?>">Pay later</a></div>
</form>
</div></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>
