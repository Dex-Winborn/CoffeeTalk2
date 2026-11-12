(function () {
  "use strict";
  // Bulk availability editor for the Menu items table (pages/records.php).
  // The drop-downs stage changes without reloading the page. The sticky bar shows how many rows are
  // waiting and the Confirm button saves them all in one request (actions/bulk_update_items.php).
  var form = document.getElementById("bulk-menu-form");
  if (!form) return;

  var selects = Array.prototype.slice.call(
    form.querySelectorAll("select.bulk-toggle")
  );
  if (!selects.length) return;

  var bar = document.getElementById("bulk-bar");
  var countOut = document.getElementById("bulk-count");
  var saveBtn = document.getElementById("bulk-save");
  var discardBtn = document.getElementById("bulk-discard");
  var submitting = false;

  // A row counts as changed when its drop-down no longer matches the value it was loaded with.
  function isChanged(select) {
    return select.value !== select.dataset.original;
  }

  // Update the row highlight, the status badge and the confirm bar to match the staged values.
  function refresh() {
    var pending = 0;
    selects.forEach(function (select) {
      var changed = isChanged(select);
      if (changed) pending++;
      var row = select.closest("tr");
      if (row) {
        row.classList.toggle("is-changed", changed);
        var badge = row.querySelector(".status-badge");
        if (badge) {
          var available = select.value === "1";
          badge.textContent = available ? "Available" : "Unavailable";
          badge.classList.toggle("badge-active", available);
          badge.classList.toggle("badge-inactive", !available);
        }
        // Dim or un-dim the row to match the staged value.
        row.classList.toggle("is-inactive", select.value !== "1");
      }
    });

    if (countOut) {
      countOut.textContent =
        pending === 0
          ? "No pending changes"
          : pending + (pending === 1 ? " unsaved change" : " unsaved changes");
    }
    if (saveBtn) saveBtn.disabled = pending === 0;
    if (discardBtn) discardBtn.disabled = pending === 0;
    if (bar) bar.classList.toggle("has-changes", pending > 0);
  }

  selects.forEach(function (select) {
    select.addEventListener("change", refresh);
  });

  // Put every drop-down back to its saved value and clear the highlights.
  if (discardBtn) {
    discardBtn.addEventListener("click", function () {
      selects.forEach(function (select) {
        select.value = select.dataset.original;
      });
      refresh();
    });
  }

  // Only send the rows that actually changed, so the server updates just those.
  form.addEventListener("submit", function () {
    submitting = true;
    selects.forEach(function (select) {
      if (!isChanged(select)) select.disabled = true;
    });
  });

  // Warn before leaving with staged changes (but not when the save is already on its way).
  window.addEventListener("beforeunload", function (event) {
    if (submitting) return;
    if (selects.some(isChanged)) {
      event.preventDefault();
      event.returnValue = "";
    }
  });

  refresh();
})();