(function () {
  "use strict";
  var button = document.getElementById("print-receipt");
  if (button)
    button.addEventListener("click", function () {
      window.print();
    });
})();
