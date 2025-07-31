/**
 * Dashboard Base JavaScript
 * @package MoBooking
 */

jQuery(document).ready(function ($) {
  "use strict";

  // Basic dashboard functionality

  // Mobile menu toggle
  $(".mb-mobile-menu-toggle").on("click", function () {
    $(".mb-sidebar").toggleClass("mb-sidebar-open");
    $("body").toggleClass("mb-sidebar-open");
  });

  // Close mobile menu when clicking outside
  $(document).on("click", function (e) {
    if (!$(e.target).closest(".mb-sidebar, .mb-mobile-menu-toggle").length) {
      $(".mb-sidebar").removeClass("mb-sidebar-open");
      $("body").removeClass("mb-sidebar-open");
    }
  });

  // Auto-hide alerts
  $(".mb-alert").each(function () {
    const $alert = $(this);
    if ($alert.hasClass("mb-alert-success")) {
      setTimeout(function () {
        $alert.fadeOut();
      }, 5000);
    }
  });

  // Tooltip initialization (if needed)
  if (typeof $.fn.tooltip !== "undefined") {
    $('[data-toggle="tooltip"]').tooltip();
  }

  // Centralized notification helpers, now using the new Toast system
  window.MoBookingDashboard = {
    showAlert: function (message, type = "info") {
      // Ensure the global showToast function exists
      if (typeof window.showToast === 'function') {
        // Map old type to new type and create a title
        const title = type.charAt(0).toUpperCase() + type.slice(1);

        window.showToast({
          type: type, // 'success', 'error', 'warning', 'info'
          title: title,
          message: message,
        });
      } else {
        // Fallback to console if the toast system isn't loaded
        console.log(`[MoBooking Fallback Alert] Type: ${type}, Message: ${message}`);
        // Optional: could even fallback to a simple browser alert
        // alert(`${title}: ${message}`);
      }
    },

    hideAlert: function () {
      // This function is now a no-op as toasts handle their own lifecycle.
      // It's kept for backward compatibility to prevent errors if called.
    },
  };

  // Export for global use
  window.showAlert = window.MoBookingDashboard.showAlert;
  window.hideAlert = window.MoBookingDashboard.hideAlert;

  // User dropdown menu
  const userMenu = document.querySelector(".user-menu");
  if (userMenu) {
    const userMenuToggle = userMenu.querySelector(".user-menu-toggle");
    userMenuToggle.addEventListener("click", function (e) {
      e.stopPropagation();
      userMenu.classList.toggle("open");
    });
  }

  // Close dropdown when clicking outside
  document.addEventListener("click", function (e) {
    const openUserMenu = document.querySelector(".user-menu.open");
    if (openUserMenu && !openUserMenu.contains(e.target)) {
      openUserMenu.classList.remove("open");
    }
  });
});
