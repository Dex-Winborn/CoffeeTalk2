(function () {
  "use strict";
  // Used by pages/payment.php. It calculates the change while the cashier types
  // and stops the form when the cash is too low.
  // actions/save_payment.php checks everything again on the server.
  var form = document.getElementById("payment-form");
  if (!form) return;
  var total = parseFloat(form.dataset.total),
    cash = document.getElementById("cash-fields"),
    received = document.getElementById("amount_received"),
    change = document.getElementById("change-value"),
    box = document.getElementById("change-box"),
    error = document.getElementById("payment-error");

  function money(n) {
    return (
      "₱" +
      n.toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      })
    );
  }

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

  form.querySelectorAll("[data-amount]").forEach(function (b) {
    b.addEventListener("click", function () {
      received.value = b.dataset.amount;
      update();
    });
  });

  var exact = form.querySelector("[data-exact]");
  if (exact)
    exact.addEventListener("click", function () {
      received.value = total.toFixed(2);
      update();
    });

  form.addEventListener("submit", function (e) {
    var amount = parseFloat(received.value);
    if (isNaN(amount) || Math.round(amount * 100) < Math.round(total * 100)) {
      e.preventDefault();
      error.textContent = "The amount received must be at least " + money(total) + ".";
      received.focus();
    }
  });

  update();
})();