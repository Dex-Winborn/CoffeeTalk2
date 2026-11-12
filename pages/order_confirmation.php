<?php
// Customer-facing order confirmation receipt. Shows the order reference and details.
// The customer must show this to the cashier to pay before the order is prepared.
require_once __DIR__ . "/../includes/functions.php";

$ref = isset($_GET["ref"]) && is_string($_GET["ref"]) ? $_GET["ref"] : "";
if (!preg_match('/^CT-\d{8}-\d+$/', $ref)) {
    flash_set("error", "Invalid order reference.");
    redirect("pages/menu.php");
}

// Load the order. Redirect if it was not found or is cancelled.
$order = fetch_order($ref);
if (!$order) {
    flash_set("error", "That order was not found.");
    redirect("pages/menu.php");
}
if ($order["status"] === "Cancelled") {
    flash_set("error", "This order has been cancelled.");
    redirect("pages/menu.php");
}

// Check if already paid
$payment = fetch_payment($ref);
$is_paid = $payment !== null;

// Group add-ons under the main item they belong to (shared with the dashboard).
$processed_items = group_items_with_addons($order['items']);

$pageTitle = "Order Confirmation " . $order["ref"] . " — Coffee Talk";
$bodyClass = "app-body order-page";
$navMode = "public";
$activePage = "menu";
require __DIR__ . "/../includes/header.php";
?>
<main class="app-main"><div class="app-wrap app-wrap-narrow">
<div class="app-head"><div><div class="eyebrow">Order Confirmation</div><h1 class="app-title">Your order is ready to pay</h1></div></div>

<?php if ($is_paid): ?>
<div class="alert alert-success" role="alert">
  <strong>Paid!</strong> Your order has been paid. The barista will now prepare it.
</div>
<?php else: ?>
<div class="alert alert-warning" role="alert" style="border-color: var(--orange); background: rgba(253, 137, 18, 0.15);">
  <strong>Action required:</strong> Please show this screen (or a screenshot) to the cashier at the counter to pay.
  <br>Your order will <strong>not be prepared</strong> until payment is received.
</div>
<?php endif; ?>

<article class="receipt slim" id="customer-receipt">
<header class="receipt-head">
<h1>Coffee Talk</h1>
<p>Elfa Building, 2nd Floor · Chipeco Avenue<br />Calamba City, Laguna</p>
<p class="receipt-title"><?= $is_paid ? 'PAID RECEIPT' : 'ORDER CONFIRMATION' ?></p>
</header>
<dl class="receipt-meta">
<div><dt>Order no.</dt><dd><?= e($order['ref']) ?></dd></div>
<div><dt>Date</dt><dd><?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?></dd></div>
<div><dt>Customer</dt><dd><?= e($order['customer_name']) ?></dd></div>
<?php if ($is_paid): ?>
<div><dt>Paid via</dt><dd><?= e($payment['method']) ?></dd></div>
<div><dt>Paid at</dt><dd><?= e(date('M j, Y g:i A', strtotime($payment['paid_at']))) ?></dd></div>
<?php endif; ?>
</dl>
<table class="receipt-items">
<thead><tr><th>Item</th><th>Qty</th><th>Price</th></tr></thead>
<tbody>
<?php foreach ($processed_items as $line): 
    $addon_total = 0;
    foreach ($line['addons'] as $addon) {
        $addon_total += $addon['total'];
    }
    $combined_total = $line['total'] + $addon_total;
?>
<tr>
<td>
<?= e($line['name']) ?> · <span class="receipt-item-price"><?= e(peso($line['total'])) ?></span>
<?php if ($line['addons']): ?>
<div class="receipt-addon-list">
<?php foreach ($line['addons'] as $addon): ?>
<div class="receipt-addon-line">+ <?= e($addon['name']) ?> ×<?= (int) $addon['qty'] ?> · <?= e(peso($addon['total'])) ?></div>
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
<div class="receipt-total">
<div class="receipt-total-row"><span>Total</span><strong><?= e(peso($order['total'])) ?></strong></div>
<?php if ($order['notes']): ?>
<div class="receipt-notes"><strong>Notes:</strong> <?= e($order['notes']) ?></div>
<?php endif; ?>
</div>
<footer class="receipt-foot">
<p>Thank you for ordering at Coffee Talk!</p>
<?php if (!$is_paid): ?>
<p class="receipt-instruction">Present this to the cashier to complete your payment.</p>
<?php endif; ?>
</footer>
</article>

<div class="form-actions" style="margin-top: 24px;">
<?php if (!$is_paid): ?>
<a class="button" href="<?= e(url('pages/menu.php')) ?>">Back to menu</a>
<?php else: ?>
<a class="button" href="<?= e(url('pages/menu.php')) ?>">Back to menu</a>
<a class="text-link" href="<?= e(url('pages/receipt.php?ref=' . $order['ref'])) ?>" target="_blank">View official receipt</a>
<?php endif; ?>
</div>
</div></main>
<?php require __DIR__ . "/../includes/footer.php"; ?>