(function () {
  "use strict";
  // Used by pages/payment.php. It shows the cash or cashless fields, calculates the change while the
  // cashier types, and stops the form when the cash is too low.
  // actions/save_payment.php checks everything again on the server.
  var form = document.getElementById("payment-form");
  if (!form) return;
  // total comes from the data-total attribute. The other variables are elements on the page.
  var total = parseFloat(form.dataset.total),
    cash = document.getElementById("cash-fields"),
    cashless = document.getElementById("cashless-fields"),
    hint = document.getElementById("cashless-hint"),
    received = document.getElementById("amount_received"),
    change = document.getElementById("change-value"),
    box = document.getElementById("change-box"),
    error = document.getElementById("payment-error");
  // Formats a number as pesos, for example ₱1,250.00.
  function money(n) {
    return (
      "₱" +
      n.toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  }
  // Returns the selected payment method (Cash when none is selected).
  function method() {
    var radio = form.querySelector("input[name=method]:checked");
    return radio ? radio.value : "Cash";
  }
  // Shows the change, or how much is still missing.
  function update() {
    var amount = parseFloat(received.value);
    if (isNaN(amount)) {
      change.textContent = money(0);
      box.classList.remove("is-short");
      return;
    }
    var diff = Math.round((amount - total) * 100) / 100;
    if (diff < 0) {
      change.textContent = "Short by " + money(-diff);
      box.classList.add("is-short");
    } else {
      change.textContent = money(diff);
      box.classList.remove("is-short");
    }
  }
  // Shows the cash fields for Cash, or the reference field and a hint for QR and card.
  function show() {
    var isCash = method() === "Cash";
    cash.hidden = !isCash;
    cashless.hidden = isCash;
    received.disabled = !isCash;
    error.textContent = "";
    if (!isCash)
      hint.textContent =
        method() === "QR"
          ? "Customer scans the café payment QR. Enter the reference number shown on their phone."
          : "Customer taps or inserts their card. Enter the approval number from the slip.";
  }
  // Update the fields when the method changes, and the change when an amount is typed.
  form.querySelectorAll("input[name=method]").forEach(function (r) {
    r.addEventListener("change", show);
  });
  received.addEventListener("input", update);
  // Quick buttons that fill in a fixed amount.
  form.querySelectorAll("[data-amount]").forEach(function (b) {
    b.addEventListener("click", function () {
      received.value = b.dataset.amount;
      update();
    });
  });
  // The Exact button fills in the total.
  var exact = form.querySelector("[data-exact]");
  if (exact)
    exact.addEventListener("click", function () {
      received.value = total.toFixed(2);
      update();
    });
  // Stop the submit when the cash is less than the total.
  form.addEventListener("submit", function (e) {
    if (method() === "Cash") {
      var amount = parseFloat(received.value);
      if (isNaN(amount) || Math.round(amount * 100) < Math.round(total * 100)) {
        e.preventDefault();
        error.textContent =
          "The amount received must be at least " + money(total) + ".";
        received.focus();
      }
    }
  });
  // Set the starting state.
  show();
  update();
})();
