<?php
// Digital receipt for a paid order. The button can print it or save it as PDF (js/receipt.js).
require_once __DIR__ . "/../includes/functions.php";
require_login();

$ref = isset($_GET["ref"]) && is_string($_GET["ref"]) ? $_GET["ref"] : "";
if (!preg_match('/^CT-\d{8}-\d+$/', $ref)) {
    flash_set("error", "Invalid order reference.");
    redirect("pages/transactions.php");
}

// Load the order and its payment. An unpaid order is sent to the payment page.
$order = fetch_order($ref);
if (!$order) {
    flash_set("error", "That order was not found.");
    redirect("pages/transactions.php");
}

$payment = fetch_payment($ref);
if (!$payment) {
    flash_set("error", "This order has not been paid yet.");
    redirect("pages/payment.php?ref=" . $ref);
}

// Cash receipts show the amount received and the change. QR and card receipts show the reference
// number.
$is_cash = $payment["method"] === "Cash";
$method_label = $is_cash ? "Cash" : "Cashless · " . $payment["method"];

// Process items: group addons with their parent items
$processed_items = [];
$current_main = null;
foreach ($order['items'] as $line) {
    $is_addon = strtolower($line['category']) === 'add-ons';
    if ($is_addon) {
        if ($current_main !== null) {
            $current_main['addons'][] = $line;
        } else {
            // Orphan addon, treat as regular item
            $processed_items[] = $line;
        }
    } else {
        if ($current_main !== null) {
            $processed_items[] = $current_main;
        }
        $current_main = $line;
        $current_main['addons'] = [];
    }
}
if ($current_main !== null) {
    $processed_items[] = $current_main;
}

$pageTitle = "Receipt " . $order["ref"] . " — Coffee Talk";
$activePage = "transactions";
$extraScripts = ["js/receipt.js"];
require __DIR__ . "/../includes/header.php";
?>
<main class="app-main"><div class="app-wrap app-wrap-narrow">
<div class="no-print"><?= flash_render() ?></div>
<?php /* The receipt itself. Elements with the no-print class are hidden when printing. */ ?>
<article class="receipt slim" id="receipt">
<header class="receipt-head"><h1>Coffee Talk</h1><p>Elfa Building, 2nd Floor · Chipeco Avenue<br />Calamba City, Laguna</p><p class="receipt-title">DIGITAL RECEIPT</p></header>
<dl class="receipt-meta">
<div><dt>Order no.</dt><dd><?= e($order['ref']) ?></dd></div>
<div><dt>Date</dt><dd><?= e(date('M j, Y g:i A', strtotime($payment['paid_at']))) ?></dd></div>
<div><dt>Customer</dt><dd><?= e($order['customer_name']) ?></dd></div>
<div><dt>Cashier</dt><dd><?= e($payment['cashier'] ?? '—') ?></dd></div>
</dl>
<table class="receipt-items">
<thead><tr><th>Item</th><th>Qty</th><th>Price</th></tr></thead>
<tbody>
<?php foreach ($processed_items as $line): 
    // Calculate total with addons
    $addon_total = 0;
    foreach ($line['addons'] as $addon) {
        $addon_total += $addon['total'];
    }
    $combined_total = $line['total'] + $addon_total;
?>
<tr>
<td>
<?= e($line['name']) ?>
<?php if ($line['addons']): ?>
<div class="receipt-addon-list">
<?php foreach ($line['addons'] as $addon): ?>
<div class="receipt-addon-line">+ <?= e($addon['name']) ?> ×<?= (int) $addon['qty'] ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</td>
<td><?= (int) $line['qty'] ?></td>
<td><?= e(peso($combined_total)) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php if ($order['notes'] !== ''): ?><p class="receipt-note">Note: <?= e($order['notes']) ?></p><?php endif; ?>
<div class="receipt-lines">
<div class="receipt-row receipt-grand"><span>TOTAL</span><span><?= e(peso($payment['amount_due'])) ?></span></div>
<div class="receipt-row"><span>Payment method</span><span><?= e($method_label) ?></span></div>
<?php if ($is_cash): ?>
<div class="receipt-row"><span>Amount received</span><span><?= e(peso($payment['amount_received'])) ?></span></div>
<div class="receipt-row receipt-change"><span>Change</span><span><?= e(peso($payment['change_given'])) ?></span></div>
<?php else: ?>
<div class="receipt-row receipt-change"><span>Amount paid</span><span><?= e(peso($payment['amount_due'])) ?></span></div>
<?php if ($payment['reference_no'] !== ''): ?><div class="receipt-row"><span>Reference no.</span><span><?= e($payment['reference_no']) ?></span></div><?php endif; ?>
<?php endif; ?>
</div>
<p class="receipt-thanks">Thank you! Good coffee, better conversations. ☕</p>
</article>
<div class="form-actions receipt-actions no-print"><button class="button" type="button" id="print-receipt">Print / Save as PDF</button><a class="text-link" href="<?= e(url('pages/transactions.php')) ?>">Back to orders</a></div>
</div></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>