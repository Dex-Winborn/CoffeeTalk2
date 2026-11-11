(function () {
  "use strict";
  // Used by pages/receipt.php. The Print / Save as PDF button opens the browser's print dialog.
  var button = document.getElementById("print-receipt");
  if (button)
    button.addEventListener("click", function () {
      window.print();
    });
})();
