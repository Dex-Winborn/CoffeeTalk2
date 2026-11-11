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
