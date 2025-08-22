// Enhanced AJAX save functionality for booking form settings
// Add this to assets/js/dashboard-booking-form-settings.js or update existing code

jQuery(document).ready(function ($) {
  "use strict";

  // Form submission handler with improved data collection
  $("#mobooking-booking-form-settings-form").on("submit", function (e) {
    e.preventDefault();

    const form = $(this);
    const saveButton = $("#mobooking-save-bf-settings-btn");
    const originalButtonText = saveButton.text();

    // Disable form and show loading state
    saveButton.prop("disabled", true).text("Saving...");

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
          window.showAlert(response.data?.message || "Settings saved successfully.", 'success');

          // Update shareable links if slug was saved
          if (settingsData.bf_business_slug) {
            updateShareableLinks(settingsData.bf_business_slug);
          }
        } else {
          window.showAlert(response?.data?.message || "Failed to save settings.", 'error');
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

        window.showAlert(errorMessage, 'error');
      },
      complete: function () {
        // Re-enable form
        saveButton.prop("disabled", false).text(originalButtonText);
      },
    });
  });

    // Share & Embed logic
    const businessSlugInput = $('#bf_business_slug');
    const publicLinkInput = $('#mobooking-public-link');
    const embedCodeTextarea = $('#mobooking-embed-code');
    const copyLinkBtn = $('#mobooking-copy-public-link-btn');
    const copyEmbedBtn = $('#mobooking-copy-embed-code-btn');

    let baseSiteUrl = (typeof mobooking_bf_settings_params !== 'undefined' && mobooking_bf_settings_params.site_url)
        ? mobooking_bf_settings_params.site_url
        : window.location.origin + '/';

    if (baseSiteUrl.slice(-1) !== '/') {
        baseSiteUrl += '/';
    }

    function updateShareableLinks(slug) {
        const sanitizedSlug = slug.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, '');
        if (sanitizedSlug) {
            const publicLink = baseSiteUrl + 'booking/' + sanitizedSlug + '/';
            const embedCode = `<iframe src="${publicLink}" title="Booking Form" style="width:100%; height:800px; border:1px solid #ccc;"></iframe>`;

            publicLinkInput.val(publicLink);
            embedCodeTextarea.val(embedCode);
            copyLinkBtn.prop('disabled', false);
            copyEmbedBtn.prop('disabled', false);

            const previewLink = publicLinkInput.closest('.input-group').find('a.btn-outline');
            if (previewLink.length) {
                previewLink.attr('href', publicLink).show();
            } else {
                 publicLinkInput.closest('.input-group').append(`<a href="${publicLink}" target="_blank" class="btn btn-outline">Preview</a>`);
            }

        } else {
            publicLinkInput.val('').attr('placeholder', 'Link will appear here once slug is saved.');
            embedCodeTextarea.val('').attr('placeholder', 'Embed code will appear here once slug is saved.');
            copyLinkBtn.prop('disabled', true);
            copyEmbedBtn.prop('disabled', true);
            publicLinkInput.closest('.input-group').find('a.btn-outline').hide();
        }
    }

    if (businessSlugInput.length) {
        businessSlugInput.on('input', function() {
            updateShareableLinks($(this).val());
        });
        updateShareableLinks(businessSlugInput.val());
    }

    function copyToClipboard(text, button) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => showCopySuccess(button), () => fallbackCopyTextToClipboard(text, button));
        } else {
            fallbackCopyTextToClipboard(text, button);
        }
    }

    function fallbackCopyTextToClipboard(text, button) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showCopySuccess(button);
        } catch (err) {
            window.showAlert('Failed to copy text.', 'error');
        }
        document.body.removeChild(textArea);
    }

    function showCopySuccess(button) {
        const originalText = button.text();
        button.text('Copied!').addClass('btn-primary').removeClass('btn-secondary');
        setTimeout(() => {
            button.text(originalText).removeClass('btn-primary').addClass('btn-secondary');
        }, 2000);
    }

    copyLinkBtn.on('click', () => copyToClipboard(publicLinkInput.val(), copyLinkBtn));
    copyEmbedBtn.on('click', () => copyToClipboard(embedCodeTextarea.val(), copyEmbedBtn));

    // Form state management
    $('#bf_form_enabled').on('change', function() {
        const isEnabled = $(this).is(':checked');
        const maintenanceField = $('#bf_maintenance_message').closest('.form-group');
        if (isEnabled) {
            maintenanceField.hide();
        } else {
            maintenanceField.show();
        }
    }).trigger('change');

    // Flush rewrite rules
    $('#mobooking-flush-rewrite-rules-btn').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        button.prop('disabled', true).text('Flushing...');

        $.ajax({
            url: window.mobooking_bf_settings_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_flush_rewrite_rules',
                nonce: window.mobooking_bf_settings_params.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.showAlert(response.data.message || 'Rewrite rules flushed successfully.', 'success');
                } else {
                    window.showAlert(response.data.message || 'Failed to flush rewrite rules.', 'error');
                }
            },
            error: function() {
                window.showAlert('An error occurred while flushing rewrite rules.', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Flush Rewrite Rules');
            }
        });
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

  // Modern Toggle Switch handler
  function updateSwitchState(input) {
    const switchLabel = $(input).closest('.switch');
    if ($(input).is(':checked')) {
      switchLabel.addClass('switch-checked');
    } else {
      switchLabel.removeClass('switch-checked');
    }
  }

  $('.switch input[type="checkbox"]').each(function () {
    updateSwitchState(this);
  });

  $('.switch input[type="checkbox"]').on('change', function () {
    updateSwitchState(this);
  });
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
