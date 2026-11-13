const toggle = document.querySelector("[data-menu-toggle]");
const nav = document.querySelector("[data-nav]");
const header = document.querySelector(".header");

if (toggle && nav) {
  const closeMenu = () => {
    nav.classList.remove("is-open", "open");
    toggle.setAttribute("aria-expanded", "false");
    toggle.textContent = "Menu";
  };

  toggle.addEventListener("click", () => {
    const isOpen = nav.classList.toggle("is-open");
    nav.classList.toggle("open", isOpen);
    toggle.setAttribute("aria-expanded", String(isOpen));
    toggle.textContent = isOpen ? "Close" : "Menu";
  });

  nav.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", closeMenu);
  });
}

if (header) {
  const setScrolled = () => {
    header.classList.toggle("scrolled", window.scrollY > 16);
  };

  setScrolled();
  window.addEventListener("scroll", setScrolled, { passive: true });
}

const revealTargets = document.querySelectorAll(
  ".hero-grid > *, .section-head, .card, .trust-item, .quick-answer, .panel, .process-step, .faq-card, .cta-inner, .footer-grid > div",
);

revealTargets.forEach((element, index) => {
  element.classList.add("ui-reveal");
  if (element.matches(".card, .trust-item, .process-step, .faq-card")) {
    element.style.setProperty("--reveal-delay", `${Math.min((index % 6) * 70, 350)}ms`);
  }
});

if ("IntersectionObserver" in window) {
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: "0px 0px -8% 0px" },
  );

  revealTargets.forEach((element) => observer.observe(element));
} else {
  revealTargets.forEach((element) => element.classList.add("is-visible"));
}
