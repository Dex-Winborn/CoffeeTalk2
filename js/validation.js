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
  
  // Get all addon select options for price lookup
  function getAddonPrice(addonId) {
    var select = lines.querySelector('select[name="addon_id[]"]');
    if (!select) return 0;
    var opt = select.querySelector('option[value="' + addonId + '"]');
    return opt && opt.dataset.price ? parseFloat(opt.dataset.price) : 0;
  }
  
  // Adds up price × quantity of every row. The price comes from the selected option's data-price.
  // This is only a preview; PHP calculates the real total from the prices in the database.
  function updateTotal() {
    if (!lines || !totalOut) return;
    var total = 0;
    lines.querySelectorAll(".order-line").forEach(function (row) {
      var select = row.querySelector("select[name='item_id[]']"),
        opt = select && select.options[select.selectedIndex],
        price = opt && opt.dataset.price ? parseFloat(opt.dataset.price) : 0,
        qty = parseInt(row.querySelector("input[name='quantity[]']").value, 10);
      total += price * (isNaN(qty) || qty < 1 ? 0 : qty);
      
      // Add add-on prices
      row.querySelectorAll(".addon-item").forEach(function (addonRow) {
        var addonSelect = addonRow.querySelector("select[name='addon_id[]']"),
          addonOpt = addonSelect && addonSelect.options[addonSelect.selectedIndex],
          addonPrice = addonOpt && addonOpt.dataset.price ? parseFloat(addonOpt.dataset.price) : 0,
          addonQty = parseInt(addonRow.querySelector("input[name='addon_quantity[]']").value, 10);
        total += addonPrice * (isNaN(addonQty) || addonQty < 1 ? 0 : addonQty);
      });
    });
    totalOut.textContent =
      "₱" +
      total.toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
  }
  
  // Update line indexes after add/remove
  function updateLineIndexes() {
    if (!lines) return;
    var rows = lines.querySelectorAll(".order-line");
    rows.forEach(function (row, index) {
      row.dataset.lineIndex = index;
      // Update main select and inputs
      row.querySelectorAll("[name='item_id[]'], [name='quantity[]']").forEach(function (el) {
        // No index in name needed, they're arrays
      });
      // Update addon bar
      var addonBar = row.querySelector(".addon-bar");
      if (addonBar) {
        addonBar.dataset.lineIndex = index;
        addonBar.querySelectorAll("[name='addon_parent[]']").forEach(function (el) {
          el.value = index;
        });
        var addBtn = addonBar.querySelector(".addon-add-btn");
        if (addBtn) addBtn.dataset.lineIndex = index;
      }
      // Update addon list items
      row.querySelectorAll(".addon-item").forEach(function (addonRow, addonIndex) {
        addonRow.dataset.addonIndex = addonIndex;
        addonRow.querySelectorAll("[name='addon_parent[]']").forEach(function (el) {
          el.value = index;
        });
        var removeBtn = addonRow.querySelector(".addon-remove-btn");
        if (removeBtn) {
          removeBtn.dataset.lineIndex = index;
          removeBtn.dataset.addonIndex = addonIndex;
        }
      });
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
  
  // Create addon item HTML
  function createAddonItem(lineIndex, addonIndex, addonId, addonQty) {
    var addonSelect = lines.querySelector('.addon-bar select[name="addon_id[]"]');
    var addonOptionsHtml = '<option value="">Choose an add-on…</option>';
    if (addonSelect) {
      Array.from(addonSelect.options).forEach(function (opt) {
        if (opt.value) {
          addonOptionsHtml += '<option value="' + opt.value + '" data-price="' + (opt.dataset.price || '') + '"' + (opt.value == addonId ? ' selected' : '') + '>' + opt.textContent + '</option>';
        }
      });
    }
    
    var div = document.createElement("div");
    div.className = "addon-item";
    div.dataset.addonIndex = addonIndex;
    div.innerHTML = 
      '<div class="addon-item-fields">' +
        '<div class="field addon-select-field"><label>Add-on</label><select name="addon_id[]" class="addon-select">' + addonOptionsHtml + '</select><span class="field-error" aria-live="polite"></span></div>' +
        '<div class="field field-qty addon-qty-field"><label>Qty</label><input type="number" name="addon_quantity[]" class="addon-qty" value="' + (addonQty || 1) + '" min="1" max="20" /><span class="field-error" aria-live="polite"></span></div>' +
        '<input type="hidden" name="addon_parent[]" value="' + lineIndex + '" />' +
      '</div>' +
      '<button type="button" class="addon-remove-btn" aria-label="Remove add-on" data-line-index="' + lineIndex + '" data-addon-index="' + addonIndex + '">×</button>';
    return div;
  }
  
  // Recalculate on any change, and handle the remove button.
  if (lines) {
    lines.addEventListener("change", updateTotal);
    lines.addEventListener("input", updateTotal);
    lines.addEventListener("click", function (e) {
      // Remove main line
      var b = e.target.closest(".line-remove");
      if (b) {
        b.closest(".order-line").remove();
        syncRemove();
        updateLineIndexes();
        updateTotal();
        return;
      }
      
      // Add addon
      var addBtn = e.target.closest(".addon-add-btn");
      if (addBtn) {
        var lineIndex = parseInt(addBtn.dataset.lineIndex, 10);
        var row = lines.querySelector('.order-line[data-line-index="' + lineIndex + '"]');
        if (!row) return;
        
        // Updated selectors for new field wrapper structure
        var addonSelect = row.querySelector('.addon-bar .addon-select-field select[name="addon_id[]"]');
        var addonQtyInput = row.querySelector('.addon-bar .addon-qty-field input[name="addon_quantity[]"]');
        
        if (!addonSelect || !addonSelect.value) {
          addonSelect.classList.add("has-error");
          return;
        }
        addonSelect.classList.remove("has-error");
        
        var addonId = addonSelect.value;
        var addonQty = parseInt(addonQtyInput.value, 10) || 1;
        
        // Reset the addon bar
        addonSelect.selectedIndex = 0;
        addonQtyInput.value = 1;
        
        // Create addon item
        var addonList = row.querySelector(".addon-list");
        if (!addonList) {
          addonList = document.createElement("div");
          addonList.className = "addon-list";
          addonList.dataset.lineIndex = lineIndex;
          row.appendChild(addonList);
        }
        
        var addonItems = addonList.querySelectorAll(".addon-item");
        var addonIndex = addonItems.length;
        
        var addonItem = createAddonItem(lineIndex, addonIndex, addonId, addonQty);
        addonList.appendChild(addonItem);
        
        updateTotal();
        return;
      }
      
      // Remove addon
      var removeAddonBtn = e.target.closest(".addon-remove-btn");
      if (removeAddonBtn) {
        var lineIndex = parseInt(removeAddonBtn.dataset.lineIndex, 10);
        var addonIndex = parseInt(removeAddonBtn.dataset.addonIndex, 10);
        var row = lines.querySelector('.order-line[data-line-index="' + lineIndex + '"]');
        if (!row) return;
        
        var addonList = row.querySelector(".addon-list");
        if (!addonList) return;
        
        var addonItems = addonList.querySelectorAll(".addon-item");
        var targetItem = addonList.querySelector('.addon-item[data-addon-index="' + addonIndex + '"]');
        if (targetItem) {
          targetItem.remove();
          
          // Re-index remaining addon items
          var remainingItems = addonList.querySelectorAll(".addon-item");
          remainingItems.forEach(function (item, idx) {
            item.dataset.addonIndex = idx;
            var removeBtn = item.querySelector(".addon-remove-btn");
            if (removeBtn) removeBtn.dataset.addonIndex = idx;
            item.querySelectorAll("[name='addon_parent[]']").forEach(function (el) {
              el.value = lineIndex;
            });
          });
          
          if (remainingItems.length === 0) {
            addonList.remove();
          }
        }
        
        updateTotal();
        return;
      }
    });
    
    // The + Add another item button copies the first row, resets it and focuses the drop-down.
    if (add)
      add.addEventListener("click", function () {
        var firstRow = lines.querySelector(".order-line");
        if (!firstRow) return;
        var copy = firstRow.cloneNode(true);
        copy.querySelector("select[name='item_id[]']").selectedIndex = 0;
        copy.querySelector("input[name='quantity[]']").value = "1";
        // Remove any addon items from the copy
        copy.querySelectorAll(".addon-list").forEach(function (el) { el.remove(); });
        // Reset addon bar
        copy.querySelectorAll('.addon-bar select[name="addon_id[]"]').forEach(function (el) { el.selectedIndex = 0; });
        copy.querySelectorAll('.addon-bar input[name="addon_quantity[]"]').forEach(function (el) { el.value = "1"; });
        
        copy.querySelectorAll(".field-error").forEach(function (x) {
          x.textContent = "";
        });
        copy.querySelectorAll(".has-error").forEach(function (x) {
          x.classList.remove("has-error");
        });
        lines.appendChild(copy);
        syncRemove();
        updateLineIndexes();
        updateTotal();
        copy.querySelector("select[name='item_id[]']").focus();
      });
    
    // Initialize line indexes
    updateLineIndexes();
    syncRemove();
    updateTotal();
  }

  // Public menu (pages/menu.php): handle add-ons per item
  var publicForm = document.querySelector(".public-order-form");
  if (publicForm) {
    var flashCards = publicForm.querySelectorAll(".flash-card[data-has-addons='true']");
    
    // Get addon options template from the first select
    var addonOptionsTemplate = '';
    var firstSelect = publicForm.querySelector('.flash-addon-select');
    if (firstSelect) {
      addonOptionsTemplate = firstSelect.innerHTML;
    }
    
    flashCards.forEach(function (card) {
      var itemId = card.dataset.itemId;
      var qtyInput = card.querySelector('.flash-qty-input[name="quantity[' + itemId + ']"]');
      var addonSection = card.querySelector('.flash-card-addons');
      var addonList = card.querySelector('.flash-addons-list');
      var addonSelect = card.querySelector('.flash-addon-select');
      var addonQtyInput = card.querySelector('.flash-addon-qty');
      var addonAddBtn = card.querySelector('.flash-addon-add');
      
      // Show/hide addon section based on quantity
      function toggleAddonSection() {
        var qty = parseInt(qtyInput.value, 10) || 0;
        if (qty > 0) {
          addonSection.hidden = false;
        } else {
          addonSection.hidden = true;
          // Clear addons when quantity goes to 0
          addonList.innerHTML = '';
        }
      }
      
      qtyInput.addEventListener('change', toggleAddonSection);
      qtyInput.addEventListener('input', toggleAddonSection);
      
      // Add addon button
      if (addonAddBtn) {
        addonAddBtn.addEventListener('click', function () {
          if (!addonSelect.value) {
            addonSelect.classList.add('has-error');
            return;
          }
          addonSelect.classList.remove('has-error');
          
          var addonId = addonSelect.value;
          var addonQty = parseInt(addonQtyInput.value, 10) || 1;
          var addonName = addonSelect.options[addonSelect.selectedIndex].textContent;
          var addonPrice = addonSelect.options[addonSelect.selectedIndex].dataset.price || 0;
          
          // Create addon row
          var addonIndex = addonList.querySelectorAll('.flash-addon-item').length;
          var div = document.createElement('div');
          div.className = 'flash-addon-item';
          div.dataset.addonIndex = addonIndex;
          div.innerHTML = 
            '<span class="flash-addon-name">' + addonName + '</span>' +
            '<span class="flash-addon-qty">×' + addonQty + '</span>' +
            '<input type="hidden" name="addon[' + itemId + '][]" value="' + addonId + '" />' +
            '<input type="hidden" name="addon_qty[' + itemId + '][]" value="' + addonQty + '" />' +
            '<button type="button" class="flash-addon-remove" aria-label="Remove add-on" data-addon-index="' + addonIndex + '">×</button>';
          addonList.appendChild(div);
          
          // Reset select
          addonSelect.selectedIndex = 0;
          addonQtyInput.value = 1;
        });
      }
      
      // Remove addon (event delegation)
      addonList.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('.flash-addon-remove');
        if (removeBtn) {
          var addonIndex = parseInt(removeBtn.dataset.addonIndex, 10);
          var items = addonList.querySelectorAll('.flash-addon-item');
          var targetItem = addonList.querySelector('.flash-addon-item[data-addon-index="' + addonIndex + '"]');
          if (targetItem) {
            targetItem.remove();
            // Re-index remaining items
            var remainingItems = addonList.querySelectorAll('.flash-addon-item');
            remainingItems.forEach(function (item, idx) {
              item.dataset.addonIndex = idx;
              var btn = item.querySelector('.flash-addon-remove');
              if (btn) btn.dataset.addonIndex = idx;
            });
          }
        }
      });
      
      // Initialize
      toggleAddonSection();
    });
  }
})();