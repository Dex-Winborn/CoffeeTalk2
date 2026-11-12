// Site-wide script, loaded on every page by includes/footer.php. It opens and closes the mobile menu
// and fills in the footer year.
// menuToggle is the menu button. navLinks is the list of links, which stays hidden on phones until the
// button is pressed.
const menuToggle = document.querySelector(".menu-toggle");
const navLinks = document.querySelector(".nav-links");

if (menuToggle && navLinks) {
  // Toggle the open class, and update the aria attributes for screen readers.
  menuToggle.addEventListener("click", () => {
    const isOpen = navLinks.classList.toggle("open");
    menuToggle.setAttribute("aria-expanded", String(isOpen));
    menuToggle.setAttribute(
      "aria-label",
      isOpen ? "Close navigation" : "Open navigation",
    );
  });

  // Close the menu again after a link is tapped.
  navLinks.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      navLinks.classList.remove("open");
      menuToggle.setAttribute("aria-expanded", "false");
      menuToggle.setAttribute("aria-label", "Open navigation");
    });
  });
}

// Footer copyright year.
const yearElement = document.querySelector("#year");
if (yearElement) {
  yearElement.textContent = new Date().getFullYear();
}

// Flash card quantity buttons (public order page)
document.addEventListener("click", function(e) {
  const minusBtn = e.target.closest(".flash-qty-minus");
  const plusBtn = e.target.closest(".flash-qty-plus");
  
  if (minusBtn || plusBtn) {
    const btn = minusBtn || plusBtn;
    const itemId = btn.dataset.itemId;
    const input = document.querySelector(".flash-qty-input[name='quantity[" + itemId + "]']");
    if (!input) return;
    
    let value = parseInt(input.value, 10) || 0;
    if (minusBtn) {
      value = Math.max(0, value - 1);
    } else {
      value = Math.min(20, value + 1);
    }
    input.value = value;
    input.dispatchEvent(new Event("input", { bubbles: true }));
  }
});

// Back-to-top button (index.php): show it after the visitor scrolls down, and jump to the top on
// click. The smooth scroll comes from the html { scroll-behavior: smooth } rule.
const backToTop = document.querySelector(".back-to-top");
if (backToTop) {
  const toggleBackToTop = () => {
    backToTop.classList.toggle("is-visible", window.scrollY > 400);
  };
  window.addEventListener("scroll", toggleBackToTop, { passive: true });
  backToTop.addEventListener("click", (event) => {
    event.preventDefault();
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
  toggleBackToTop();
}
