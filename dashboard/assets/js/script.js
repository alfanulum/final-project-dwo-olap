/* ================================================
   MODERN DASHBOARD JAVASCRIPT - OPTIMIZED
   ================================================ */

(function () {
  "use strict";

  // Configuration
  const CONFIG = {
    loaderDelay: 200,
    loaderMaxTimeout: 2000,
    scrollThreshold: 300,
    animationDuration: 300,
  };

  // ==================== LOADER ====================
  function initLoader() {
    const loader = document.querySelector(".loader-wrapper");
    if (!loader) return;

    // Hide loader on page load
    window.addEventListener("load", () => {
      setTimeout(() => {
        loader.classList.add("fade-out");
        setTimeout(() => {
          loader.style.display = "none";
        }, CONFIG.animationDuration);
      }, CONFIG.loaderDelay);
    });

    // Failsafe: force hide after max timeout
    setTimeout(() => {
      if (loader.style.display !== "none") {
        loader.style.display = "none";
      }
    }, CONFIG.loaderMaxTimeout);
  }

  // ==================== SIDEBAR TOGGLE ====================
  function initSidebar() {
    const sidebar = document.querySelector(".sidebar-wrapper");
    const toggleButtons = document.querySelectorAll(".toggle-sidebar");

    if (!sidebar || !toggleButtons.length) return;

    // Toggle sidebar
    toggleButtons.forEach((btn) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        sidebar.classList.toggle("active");

        // Handle mobile overlay
        if (window.innerWidth <= 1024) {
          handleMobileOverlay(sidebar);
        }
      });
    });

    // Close sidebar on window resize
    window.addEventListener("resize", () => {
      if (window.innerWidth > 1024) {
        sidebar.classList.remove("active");
        removeOverlay();
      }
    });
  }

  // Mobile overlay handler
  function handleMobileOverlay(sidebar) {
    const existingOverlay = document.querySelector(".sidebar-overlay");

    if (sidebar.classList.contains("active")) {
      if (!existingOverlay) {
        const overlay = document.createElement("div");
        overlay.className = "sidebar-overlay";
        document.body.appendChild(overlay);

        overlay.addEventListener("click", () => {
          sidebar.classList.remove("active");
          removeOverlay();
        });
      }
    } else {
      removeOverlay();
    }
  }

  function removeOverlay() {
    const overlay = document.querySelector(".sidebar-overlay");
    if (overlay) overlay.remove();
  }

  // ==================== TAP ON TOP ====================
  function initTapOnTop() {
    const tapTop = document.querySelector(".tap-top");
    if (!tapTop) return;

    // Show/hide on scroll
    window.addEventListener("scroll", () => {
      if (window.scrollY > CONFIG.scrollThreshold) {
        tapTop.classList.add("show");
      } else {
        tapTop.classList.remove("show");
      }
    });

    // Scroll to top on click
    tapTop.addEventListener("click", () => {
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    });
  }

  // ==================== ACTIVE MENU ====================
  function initActiveMenu() {
    const currentPath = window.location.pathname;
    const menuItems = document.querySelectorAll(".sidebar-list");

    menuItems.forEach((item) => {
      const link = item.querySelector("a");
      if (!link) return;

      const href = link.getAttribute("href");

      // Check if current page matches
      if (
        href &&
        (currentPath.endsWith(href) ||
          (href === "index.php" && currentPath.endsWith("/")))
      ) {
        item.classList.add("active");
      }

      // Add click handler
      link.addEventListener("click", () => {
        menuItems.forEach((i) => i.classList.remove("active"));
        item.classList.add("active");
      });
    });
  }

  // ==================== FORM ENHANCEMENTS ====================
  function initForms() {
    const forms = document.querySelectorAll("form");

    forms.forEach((form) => {
      const inputs = form.querySelectorAll(
        "input[required], select[required], textarea[required]"
      );

      // Real-time validation
      inputs.forEach((input) => {
        input.addEventListener("blur", function () {
          validateInput(this);
        });

        input.addEventListener("input", function () {
          if (this.classList.contains("error")) {
            validateInput(this);
          }
        });
      });

      // Form submit
      form.addEventListener("submit", function (e) {
        let isValid = true;

        inputs.forEach((input) => {
          if (!validateInput(input)) {
            isValid = false;
          }
        });

        if (!isValid) {
          e.preventDefault();
          const firstError = form.querySelector(".error");
          if (firstError) firstError.focus();
        } else {
          handleSubmitLoading(this);
        }
      });
    });
  }

  function validateInput(input) {
    const value = input.value.trim();
    const isValid = value !== "";

    if (isValid) {
      input.style.borderColor = "";
      input.classList.remove("error");
    } else {
      input.style.borderColor = "#ef4444";
      input.classList.add("error");
    }

    return isValid;
  }

  function handleSubmitLoading(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    if (!submitBtn) return;

    const originalHTML = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = `
      <span style="display: inline-block; width: 14px; height: 14px; border: 2px solid currentColor; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite;"></span>
      <span style="margin-left: 8px;">Loading...</span>
    `;

    // Reset after timeout (in case of client-side issues)
    setTimeout(() => {
      if (submitBtn.disabled) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHTML;
      }
    }, 5000);
  }

  // ==================== TABLE ENHANCEMENTS ====================
  function initTables() {
    const tables = document.querySelectorAll(".table");

    tables.forEach((table) => {
      const rows = table.querySelectorAll("tbody tr");

      rows.forEach((row) => {
        // Make clickable if has data-href
        if (row.dataset.href) {
          row.style.cursor = "pointer";
          row.addEventListener("click", function () {
            window.location.href = this.dataset.href;
          });
        }
      });
    });
  }

  // ==================== SMOOTH SCROLL ====================
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
      anchor.addEventListener("click", function (e) {
        const href = this.getAttribute("href");
        if (!href || href === "#") return;

        const target = document.querySelector(href);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
        }
      });
    });
  }

  // ==================== CHART.JS DEFAULTS ====================
  function initChartDefaults() {
    if (typeof Chart === "undefined") return;

    // Animation settings
    Chart.defaults.animation.duration = 800;
    Chart.defaults.animation.easing = "easeOutQuart";

    // Tooltip settings
    Chart.defaults.plugins.tooltip.backgroundColor = "rgba(0, 0, 0, 0.85)";
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: "bold" };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 13 };

    // Legend settings
    Chart.defaults.plugins.legend.labels.padding = 15;
    Chart.defaults.plugins.legend.labels.font = { size: 13 };
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
  }

  // ==================== CARD ANIMATIONS ====================
  function initCardAnimations() {
    const cards = document.querySelectorAll(".card");
    if (!cards.length) return;

    const observerOptions = {
      threshold: 0.1,
      rootMargin: "0px 0px -50px 0px",
    };

    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry, index) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.style.opacity = "1";
            entry.target.style.transform = "translateY(0)";
          }, index * 50);
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    cards.forEach((card) => {
      card.style.opacity = "0";
      card.style.transform = "translateY(20px)";
      card.style.transition = "opacity 0.4s ease, transform 0.4s ease";
      observer.observe(card);
    });
  }

  // ==================== INITIALIZE ALL ====================
  function init() {
    // Wait for DOM
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", initAll);
    } else {
      initAll();
    }
  }

  function initAll() {
    initLoader();
    initSidebar();
    initTapOnTop();
    initActiveMenu();
    initForms();
    initTables();
    initSmoothScroll();
    initChartDefaults();
    initCardAnimations();

    console.log("✓ Dashboard initialized");
  }

  // Start
  init();
})();

// ==================== GLOBAL UTILITIES ====================

// Toast notification
window.showToast = function (message, type = "info") {
  const colors = {
    success: "#10b981",
    error: "#ef4444",
    warning: "#f59e0b",
    info: "#3b82f6",
  };

  const toast = document.createElement("div");
  toast.style.cssText = `
    position: fixed;
    top: 90px;
    right: 20px;
    background: ${colors[type] || colors.info};
    color: white;
    padding: 14px 18px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 10000;
    font-size: 14px;
    font-weight: 500;
    max-width: 350px;
    animation: slideInRight 0.3s ease;
  `;
  toast.textContent = message;

  document.body.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = "slideOutRight 0.3s ease";
    setTimeout(() => toast.remove(), 300);
  }, 3000);
};

// Format currency
window.formatCurrency = function (number) {
  return (
    "Rp " +
    parseFloat(number).toLocaleString("id-ID", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  );
};

// Format number
window.formatNumber = function (number) {
  return parseFloat(number).toLocaleString("id-ID");
};

// Animation keyframes
const styleSheet = document.createElement("style");
styleSheet.textContent = `
  @keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
  @keyframes slideOutRight {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
  }
`;
document.head.appendChild(styleSheet);
