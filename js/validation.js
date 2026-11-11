(function () {
  "use strict";
  // Browser-side checks, loaded on pages with forms. They show messages next to the fields before the
  // form is sent.
  // PHP repeats the same checks (includes/validation.php), because JavaScript can be bypassed.
  // This file also handles confirm boxes for destructive actions, and the add/remove rows and running
  // total of the order form.
  // Finds the element where a field's error message is shown.
  function errorBox(field) {
    var wrap = field.closest(".field") || field.closest(".public-menu-item");
    return wrap ? wrap.querySelector(".field-error") : null;
  }
  // Returns an error message for a field, or an empty string when it is valid.
  // Checks run in this order: required, minimum length, email format, number range, pattern, strong
  // password, matching password.
  function messageFor(field) {
    var value = field.value.trim(),
      wrap = field.closest(".field") || field.closest(".public-menu-item"),
      label = wrap && wrap.querySelector("label"),
      name = label ? label.firstChild.textContent.trim() : "This field";
    if (field.required && value === "")
      return field.tagName === "SELECT"
        ? "Please choose an option."
        : name + " is required.";
    if (value === "") return "";
    if (field.minLength > 0 && value.length < field.minLength)
      return name + " must be at least " + field.minLength + " characters.";
    if (field.type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value))
      return "Please enter a valid email address.";
    if (field.type === "number") {
      var n = Number(value);
      if (isNaN(n)) return "Please enter a number.";
      if (field.min !== "" && n < Number(field.min))
        return "Must be at least " + field.min + ".";
      if (field.max !== "" && n > Number(field.max))
        return "Must be " + field.max + " or less.";
      if (field.step === "1" && !Number.isInteger(n))
        return "Please enter a whole number.";
    }
    if (field.pattern && !new RegExp("^(?:" + field.pattern + ")$").test(value))
      return (
        field.dataset.patternMessage || "Please match the requested format."
      );
    if (
      field.hasAttribute("data-strong-password") &&
      (field.value.length < 8 ||
        !/[A-Za-z]/.test(field.value) ||
        !/[0-9]/.test(field.value))
    )
      return "Use at least 8 characters with a letter and a number.";
    if (field.dataset.match) {
      var other = document.getElementById(field.dataset.match);
      if (other && other.value !== field.value)
        return "Passwords do not match.";
    }
    return "";
  }
  // Runs messageFor, shows or clears the message, and marks the field red (has-error).
  function check(field) {
    var msg = messageFor(field),
      box = errorBox(field);
    if (box) box.textContent = msg;
    field.classList.toggle("has-error", !!msg);
    field.setAttribute("aria-invalid", msg ? "true" : "false");
    return !msg;
  }
  // Forms marked data-validate: check a field when the user leaves it, check again while typing after
  // an error, and block the submit when any field is invalid.
  document.querySelectorAll("form[data-validate]").forEach(function (form) {
    function fields() {
      return form.querySelectorAll("input:not([type=hidden]),select,textarea");
    }
    form.addEventListener("focusout", function (e) {
      if (e.target.matches("input,select,textarea") && e.target.value !== "")
        check(e.target);
    });
    form.addEventListener("input", function (e) {
      if (e.target.classList.contains("has-error")) check(e.target);
    });
    // On submit, check every field. Stop and focus the first invalid one.
    form.addEventListener("submit", function (e) {
      var first = null;
      fields().forEach(function (f) {
        if (!check(f) && !first) first = f;
      });
      if (first) {
        e.preventDefault();
        first.focus();
      }
    });
  });
  // Forms with data-confirm ask for confirmation before they are sent (delete, cancel order).
  // If data-confirm-value is set, it only asks when that option is selected in the status drop-down.
  document.querySelectorAll("form[data-confirm]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      var only = form.dataset.confirmValue,
        select = form.querySelector("select[name=status]");
      if (only && (!select || select.value !== only)) return;
      if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    });
  });
  // Order form (pages/transactions.php): add or remove item rows and show a running total.
  var lines = document.getElementById("order-lines"),
    add = document.getElementById("add-line"),
    totalOut = document.getElementById("order-total");
  // Adds up price × quantity of every row. The price comes from the selected option's data-price.
  // This is only a preview; PHP calculates the real total from the prices in the database.
  function updateTotal() {
    if (!lines || !totalOut) return;
    var total = 0;
    lines.querySelectorAll(".order-line").forEach(function (row) {
      var select = row.querySelector("select"),
        opt = select.options[select.selectedIndex],
        price = opt && opt.dataset.price ? parseFloat(opt.dataset.price) : 0,
        qty = parseInt(row.querySelector("input").value, 10);
      total += price * (isNaN(qty) || qty < 1 ? 0 : qty);
    });
    totalOut.textContent =
      "₱" +
      total.toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
  }
  // Hide the remove button when only one row is left.
  function syncRemove() {
    if (!lines) return;
    var rows = lines.querySelectorAll(".order-line");
    rows.forEach(function (row) {
      row.querySelector(".line-remove").hidden = rows.length < 2;
    });
  }
  // Recalculate on any change, and handle the remove button.
  if (lines) {
    lines.addEventListener("change", updateTotal);
    lines.addEventListener("input", updateTotal);
    lines.addEventListener("click", function (e) {
      var b = e.target.closest(".line-remove");
      if (b) {
        b.closest(".order-line").remove();
        syncRemove();
        updateTotal();
      }
    });
    // The + Add another item button copies the first row, resets it and focuses the drop-down.
    if (add)
      add.addEventListener("click", function () {
        var copy = lines.querySelector(".order-line").cloneNode(true);
        copy.querySelector("select").selectedIndex = 0;
        copy.querySelector("input").value = "1";
        copy.querySelectorAll(".field-error").forEach(function (x) {
          x.textContent = "";
        });
        copy.querySelectorAll(".has-error").forEach(function (x) {
          x.classList.remove("has-error");
        });
        lines.appendChild(copy);
        syncRemove();
        updateTotal();
        copy.querySelector("select").focus();
      });
    syncRemove();
    updateTotal();
  }
})();
