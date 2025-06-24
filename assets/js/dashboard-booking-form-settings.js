// Enhanced AJAX save functionality for booking form settings
// Add this to assets/js/dashboard-booking-form-settings.js or update existing code

jQuery(document).ready(function ($) {
  "use strict";

  // Form submission handler with improved data collection
  $("#mobooking-booking-form-settings-form").on("submit", function (e) {
    e.preventDefault();

    const form = $(this);
    const saveButton = $("#mobooking-save-bf-settings-btn");
    const feedbackDiv = $("#mobooking-settings-feedback");
    const originalButtonText = saveButton.text();

    // Disable form and show loading state
    saveButton.prop("disabled", true).text("Saving...");
    feedbackDiv.hide().removeClass("notice-success notice-error");

    // Enhanced data collection function
    function collectFormData() {
      const settingsData = {};

      // Get all form fields
      form.find("input, textarea, select").each(function () {
        const field = $(this);
        const name = field.attr("name");

        if (!name || name === "save_booking_form_settings") return; // Skip submit button

        // Handle different field types
        if (field.is(":checkbox")) {
          settingsData[name] = field.is(":checked") ? "1" : "0";
        } else if (field.is(":radio")) {
          if (field.is(":checked")) {
            settingsData[name] = field.val();
          }
        } else if (field.hasClass("wp-color-picker")) {
          // Handle WordPress color picker
          settingsData[name] = field.val() || field.data("default-color") || "";
        } else {
          settingsData[name] = field.val() || "";
        }
      });

      return settingsData;
    }

    const settingsData = collectFormData();

    // Validate required parameters
    if (
      !window.mobooking_bf_settings_params ||
      !window.mobooking_bf_settings_params.ajax_url ||
      !window.mobooking_bf_settings_params.nonce
    ) {
      feedbackDiv
        .text("Configuration error: Missing AJAX parameters.")
        .addClass("notice notice-error")
        .show();

      saveButton.prop("disabled", false).text(originalButtonText);
      return;
    }

    // Perform AJAX request
    $.ajax({
      url: window.mobooking_bf_settings_params.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "mobooking_save_booking_form_settings",
        nonce: window.mobooking_bf_settings_params.nonce,
        settings: settingsData,
      },
      success: function (response) {
        if (response && response.success) {
          feedbackDiv
            .text(response.data?.message || "Settings saved successfully.")
            .addClass("notice notice-success")
            .show();

          // Update shareable links if slug was saved
          if (settingsData.bf_business_slug) {
            updateShareableLinks(settingsData.bf_business_slug);
          }

          // Auto-hide success message after 3 seconds
          setTimeout(() => {
            feedbackDiv.fadeOut();
          }, 3000);
        } else {
          feedbackDiv
            .text(response?.data?.message || "Failed to save settings.")
            .addClass("notice notice-error")
            .show();
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", { xhr, status, error });

        let errorMessage = "Network error occurred.";
        if (xhr.responseJSON?.data?.message) {
          errorMessage = xhr.responseJSON.data.message;
        } else if (xhr.responseText) {
          errorMessage = "Server error: " + error;
        }

        feedbackDiv.text(errorMessage).addClass("notice notice-error").show();
      },
      complete: function () {
        // Re-enable form
        saveButton.prop("disabled", false).text(originalButtonText);
      },
    });
  });

  // Helper function to update shareable links
  function updateShareableLinks(slug) {
    if (!slug) return;

    const baseUrl =
      window.mobooking_bf_settings_params.site_url ||
      window.location.origin + "/";
    const publicUrl = baseUrl + "bookings/" + slug + "/";
    const embedUrl = baseUrl + "bookings/" + slug + "/embed/";

    // Update public link
    $("#bf-public-link").attr("href", publicUrl).text(publicUrl);

    // Update embed code
    const embedCode = `<iframe src="${embedUrl}" width="100%" height="600" frameborder="0"></iframe>`;
    $("#bf-embed-code").val(embedCode);

    // Update QR code if present
    const qrContainer = $("#qr-code-container");
    if (qrContainer.length) {
      const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(
        publicUrl
      )}`;
      qrContainer.html(`<img src="${qrUrl}" alt="QR Code" />`);
    }
  }

  // Real-time slug sanitization for better UX
  $("input[name='bf_business_slug']").on("input", function () {
    const input = $(this);
    let value = input.val();

    // Sanitize slug in real-time
    value = value
      .toLowerCase()
      .replace(/[^a-z0-9\-]/g, "")
      .replace(/\-+/g, "-")
      .replace(/^\-|\-$/g, "");

    if (input.val() !== value) {
      input.val(value);
    }
  });

  // Initialize color pickers if available
  if (typeof $.fn.wpColorPicker === "function") {
    $(".mobooking-color-picker").wpColorPicker({
      change: function (event, ui) {
        // Trigger change event for real-time updates if needed
        $(this).trigger("input");
      },
    });
  }

  // Tab navigation improvement
  $(".nav-tab-wrapper .nav-tab").on("click", function (e) {
    e.preventDefault();

    const tab = $(this);
    const tabId = tab.data("tab");

    // Update active states
    $(".nav-tab").removeClass("nav-tab-active");
    tab.addClass("nav-tab-active");

    // Show/hide tab content
    $(".mobooking-settings-tab-content").hide();
    $("#mobooking-" + tabId + "-settings-tab").show();

    // Update URL hash without scrolling
    if (history.replaceState) {
      history.replaceState(null, null, "#" + tabId);
    }
  });

  // Initialize active tab from URL hash
  const urlHash = window.location.hash.substring(1);
  if (urlHash) {
    const targetTab = $(".nav-tab[data-tab='" + urlHash + "']");
    if (targetTab.length) {
      targetTab.trigger("click");
    }
  }
});

// Backup parameter initialization if not properly localized
if (typeof window.mobooking_bf_settings_params === "undefined") {
  console.warn("mobooking_bf_settings_params not found. Using fallback.");
  window.mobooking_bf_settings_params = {
    ajax_url: window.ajaxurl || "/wp-admin/admin-ajax.php",
    nonce: "",
    site_url: window.location.origin + "/",
    i18n: {
      saving: "Saving...",
      save_success: "Settings saved successfully.",
      error_saving: "Error saving settings.",
      error_ajax: "Network error occurred.",
    },
  };
}
