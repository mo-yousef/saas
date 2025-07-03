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

  // Form validation helpers
  window.MoBookingDashboard = {
    showAlert: function (message, type = "info") {
      const alertClass =
        type === "error"
          ? "mb-alert-error"
          : type === "success"
          ? "mb-alert-success"
          : type === "warning"
          ? "mb-alert-warning"
          : "mb-alert-info";

      let $alertContainer = $("#mb-alert-container");

      // If no alert container exists, create one
      if (!$alertContainer.length) {
        $alertContainer = $('<div id="mb-alert-container"></div>');
        $(".mb-page-container").prepend($alertContainer);
      }

      $alertContainer.html(
        '<div class="mb-alert ' + alertClass + '">' + message + "</div>"
      );

      // Auto-hide success messages
      if (type === "success") {
        setTimeout(function () {
          $alertContainer.empty();
        }, 3000);
      }

      // Scroll to top to show alert
      $("html, body").animate({ scrollTop: 0 }, 300);
    },

    hideAlert: function () {
      $("#mb-alert-container").empty();
    },
  };

  // Export for global use
  window.showAlert = window.MoBookingDashboard.showAlert;
  window.hideAlert = window.MoBookingDashboard.hideAlert;
});
