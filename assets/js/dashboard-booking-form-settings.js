jQuery(document).ready(function ($) {
  "use strict";
  console.log("form js dashboard");
  // --- Toast Notification Function ---
  if (typeof window.showAlert !== "function") {
    window.showAlert = (message, type = "info") => {
      console.warn("No toast handler found. Implement `window.showAlert`.", {
        message,
        type,
      });
      alert(`${type.toUpperCase()}: ${message}`);
    };
  }

  // --- Tab Navigation (Fixed) ---
  const tabs = $(".NORDBOOKING-tab-item");
  const panes = $(".NORDBOOKING-settings-tab-pane");

  function activateTab(tabId) {
    // Remove active class from all tabs and panes
    tabs.removeClass("active");
    panes.removeClass("active");

    // Add active class to selected tab and pane
    const activeTab = tabs.filter(`[data-tab="${tabId}"]`);
    const activePane = $(`#${tabId}`);

    if (activeTab.length && activePane.length) {
      activeTab.addClass("active");
      activePane.addClass("active");

      // Update URL hash
      if (history.replaceState) {
        history.replaceState(null, null, `#${tabId}`);
      }
    }
  }

  // Tab click handler
  tabs.on("click", function (e) {
    e.preventDefault();
    const tabId = $(this).data("tab");
    if (tabId) {
      activateTab(tabId);
    }
  });

  // Initialize tabs on page load
  function initializeTabs() {
    const urlHash = window.location.hash.substring(1);
    let targetTab = null;

    // Check if URL hash corresponds to an existing tab pane
    if (urlHash && panes.filter(`#${urlHash}`).length > 0) {
      targetTab = urlHash;
    } else {
      // Fallback to the first tab
      const firstTab = tabs.first();
      if (firstTab.length) {
        targetTab = firstTab.data("tab");
      }
    }

    if (targetTab) {
      activateTab(targetTab);
    }
  }

  // Initialize tabs
  initializeTabs();

  // Debug: Log tab elements
  console.log("Found tabs:", tabs.length);
  console.log("Found panes:", panes.length);

  // --- Form Submission ---
  $("#nordbooking-booking-form-settings-form").on("submit", function (e) {
    e.preventDefault();
    const form = $(this);
    const saveButton = $("#NORDBOOKING-save-bf-settings-btn");
    const originalButtonText = saveButton.text();
    const spinner = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;

    // Check if form has required fields filled
    let isValid = true;
    form
      .find("input[required], textarea[required], select[required]")
      .each(function () {
        if (!$(this).val().trim()) {
          isValid = false;
          $(this).addClass("is-invalid");
          return false;
        } else {
          $(this).removeClass("is-invalid");
        }
      });

    if (!isValid) {
      window.showAlert("Please fill in all required fields.", "error");
      return;
    }

    saveButton.prop("disabled", true).html(spinner + " Saving...");

    const settingsData = {};
    form.find("input, textarea, select").each(function () {
      const field = $(this);
      const name = field.attr("name");
      if (
        !name ||
        name.startsWith("save_") ||
        name === "nordbooking_dashboard_nonce_field"
      )
        return;

      if (field.is(":checkbox")) {
        settingsData[name] = field.is(":checked") ? "1" : "0";
      } else if (field.is(":radio")) {
        if (field.is(":checked")) {
          settingsData[name] = field.val();
        }
      } else {
        settingsData[name] = field.val();
      }
    });

    // Get nonce value
    const nonce = $('input[name="nordbooking_dashboard_nonce_field"]').val();

    // AJAX submission
    $.ajax({
      url: nordbooking_bf_settings_params?.ajax_url || ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        action: "nordbooking_save_booking_form_settings",
        nonce: nonce,
        settings: settingsData,
      },
      success: function (response) {
        if (response.success) {
          window.showAlert(
            response.data?.message || "Settings saved successfully!",
            "success"
          );

          // Update public booking URL if business slug changed
          if (settingsData.bf_business_slug) {
            updatePublicBookingURL(settingsData.bf_business_slug);
          }
        } else {
          window.showAlert(
            response.data?.message || "Failed to save settings.",
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        window.showAlert("An error occurred while saving settings.", "error");
      },
      complete: function () {
        saveButton.prop("disabled", false).html(originalButtonText);
      },
    });
  });

  // --- Dynamic Public Link and Embed Code Updates ---
  const businessSlugInput = $("#bf_business_slug");
  const publicLinkDisplay = $("#bf-public-link");
  const copyLinkBtn = $("#NORDBOOKING-copy-public-link-btn");

  function updatePublicBookingURL(slug) {
    if (!slug) return;

    const siteUrl =
      nordbooking_bf_settings_params?.site_url || window.location.origin;
    const newUrl = siteUrl.replace(/\/$/, "") + "/booking/" + slug + "/";

    if (publicLinkDisplay.length) {
      publicLinkDisplay.attr("href", newUrl).text(newUrl);
    }

    // Update embed code if exists
    const embedCodeTextarea = $("#NORDBOOKING-embed-code");
    if (embedCodeTextarea.length) {
      const embedCode = `<iframe src="${newUrl}" width="100%" height="600" frameborder="0" scrolling="auto"></iframe>`;
      embedCodeTextarea.val(embedCode);
    }
  }

  // Update URL when slug changes
  businessSlugInput.on("input", function () {
    const slug = $(this)
      .val()
      .replace(/[^a-z0-9-]/g, "")
      .replace(/--+/g, "-");
    $(this).val(slug);
    updatePublicBookingURL(slug);
  });

  // --- Copy to Clipboard Functionality ---
  function copyToClipboard(text, button) {
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard
        .writeText(text)
        .then(function () {
          showCopySuccess(button);
        })
        .catch(function () {
          fallbackCopyToClipboard(text, button);
        });
    } else {
      fallbackCopyToClipboard(text, button);
    }
  }

  function fallbackCopyToClipboard(text, button) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-999999px";
    textArea.style.top = "-999999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
      document.execCommand("copy");
      showCopySuccess(button);
    } catch (err) {
      console.error("Fallback copy failed:", err);
      window.showAlert("Copy failed. Please copy manually.", "error");
    }

    document.body.removeChild(textArea);
  }

  function showCopySuccess(button) {
    const originalHtml = button.html();
    button.html(
      '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/></svg>'
    );
    setTimeout(() => {
      button.html(originalHtml);
    }, 2000);
    window.showAlert("Copied to clipboard!", "success");
  }

  // Copy public link
  copyLinkBtn.on("click", function () {
    const linkUrl = publicLinkDisplay.attr("href") || publicLinkDisplay.text();
    if (linkUrl) {
      copyToClipboard(linkUrl, $(this));
    }
  });

  // Copy embed code
  $("#NORDBOOKING-copy-embed-code-btn").on("click", function () {
    const embedCode = $("#NORDBOOKING-embed-code").val();
    if (embedCode) {
      copyToClipboard(embedCode, $(this));
    }
  });

  // --- Custom Color Picker Initialization ---
  function initializeCustomColorPicker() {
    $(".NORDBOOKING-color-picker").each(function () {
      const $input = $(this);

      // Skip if already initialized
      if ($input.next(".nordbooking-color-picker-wrapper").length) {
        return;
      }

      const currentValue = $input.val() || "#1abc9c";

      // Create color picker wrapper
      const $wrapper = $(
        '<div class="nordbooking-color-picker-wrapper"></div>'
      );
      const $colorInput = $(
        '<input type="color" class="nordbooking-color-input">'
      );
      const $textInput = $(
        '<input type="text" class="nordbooking-color-text" placeholder="#1abc9c">'
      );
      const $preview = $('<div class="nordbooking-color-preview"></div>');

      // Set initial values
      $colorInput.val(currentValue);
      $textInput.val(currentValue);
      $preview.css("background-color", currentValue);

      // Build the picker
      $wrapper.append($preview, $textInput, $colorInput);
      $input.after($wrapper).hide();

      // Handle color input change
      $colorInput.on("input change", function () {
        const color = $(this).val().toUpperCase();
        $textInput.val(color);
        $preview.css("background-color", color);
        $input.val(color).trigger("colorchange", [color]);
      });

      // Handle text input change
      $textInput.on("input change", function () {
        const color = $(this).val().toUpperCase();
        if (/^#[0-9A-F]{6}$/i.test(color)) {
          $colorInput.val(color);
          $preview.css("background-color", color);
          $input.val(color).trigger("colorchange", [color]);
        }
      });

      // Handle text input focus to select all
      $textInput.on("focus", function () {
        $(this).select();
      });
    });
  }

  initializeCustomColorPicker();

  // --- Toggle Switch for Form Enabled/Disabled ---
  const formEnabledToggle = $("#bf_form_enabled");
  const maintenanceMessageGroup = $("#maintenance-message-group");

  function toggleMaintenanceMessage() {
    if (formEnabledToggle.is(":checked")) {
      maintenanceMessageGroup.slideUp(200);
    } else {
      maintenanceMessageGroup.slideDown(200);
    }
  }

  formEnabledToggle.on("change", toggleMaintenanceMessage);

  // Initialize on page load
  toggleMaintenanceMessage();

  // --- QR Code Download ---
  $("#download-qr-btn").on("click", function () {
    const publicUrl =
      publicLinkDisplay.attr("href") || publicLinkDisplay.text();
    if (!publicUrl) {
      window.showAlert("No public booking URL available.", "error");
      return;
    }

    // Simple QR code generation using a free service
    const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(
      publicUrl
    )}`;

    // Create a temporary link to download the QR code
    const link = document.createElement("a");
    link.href = qrApiUrl;
    link.download = "booking-form-qr-code.png";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    window.showAlert("QR code download started.", "success");
  });

  // --- Form Validation Enhancement ---
  $('input[type="email"]').on("blur", function () {
    const email = $(this).val();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email && !emailRegex.test(email)) {
      $(this).addClass("is-invalid");
      $(this).next(".invalid-feedback").remove();
      $(this).after(
        '<div class="invalid-feedback">Please enter a valid email address.</div>'
      );
    } else {
      $(this).removeClass("is-invalid");
      $(this).next(".invalid-feedback").remove();
    }
  });

  $('input[type="url"]').on("blur", function () {
    const url = $(this).val();
    const urlRegex = /^https?:\/\/.+/;
    if (url && !urlRegex.test(url)) {
      $(this).addClass("is-invalid");
      $(this).next(".invalid-feedback").remove();
      $(this).after(
        '<div class="invalid-feedback">Please enter a valid URL (starting with http:// or https://).</div>'
      );
    } else {
      $(this).removeClass("is-invalid");
      $(this).next(".invalid-feedback").remove();
    }
  });

  // --- Accessibility Enhancements ---

  // Add proper ARIA attributes to tabs
  tabs.each(function (index) {
    const tabId = $(this).data("tab");
    const paneId = `${tabId}`;

    $(this).attr({
      role: "tab",
      "aria-controls": paneId,
      "aria-selected": $(this).hasClass("active") ? "true" : "false",
      tabindex: $(this).hasClass("active") ? "0" : "-1",
    });
  });

  panes.each(function () {
    $(this).attr({
      role: "tabpanel",
      "aria-hidden": $(this).hasClass("active") ? "false" : "true",
    });
  });

  // Keyboard navigation for tabs
  tabs.on("keydown", function (e) {
    const currentIndex = tabs.index(this);
    let targetIndex = currentIndex;

    switch (e.key) {
      case "ArrowRight":
      case "ArrowDown":
        e.preventDefault();
        targetIndex = (currentIndex + 1) % tabs.length;
        break;
      case "ArrowLeft":
      case "ArrowUp":
        e.preventDefault();
        targetIndex = (currentIndex - 1 + tabs.length) % tabs.length;
        break;
      case "Home":
        e.preventDefault();
        targetIndex = 0;
        break;
      case "End":
        e.preventDefault();
        targetIndex = tabs.length - 1;
        break;
      case "Enter":
      case " ":
        e.preventDefault();
        $(this).trigger("click");
        return;
    }

    if (targetIndex !== currentIndex) {
      tabs.eq(targetIndex).focus().trigger("click");
    }
  });

  // Update ARIA attributes when tabs change
  tabs.on("click", function () {
    tabs.attr({
      "aria-selected": "false",
      tabindex: "-1",
    });
    panes.attr("aria-hidden", "true");

    $(this).attr({
      "aria-selected": "true",
      tabindex: "0",
    });

    const targetPane = $(`#${$(this).data("tab")}`);
    targetPane.attr("aria-hidden", "false");
  });

  // --- Live Preview ---
  const preview = {
    wrapper: $(".NORDBOOKING-form-preview-wrapper"),
    form: $(".NORDBOOKING-form-preview"),
    headerText: $("#preview-header-text"),
    description: $("#preview-description"),
    progressContainer: $("#preview-progress-wrapper"),
    progressBar: $(".preview-progress-bar"),
    progressFill: $(".preview-progress-fill"),
    serviceCardImage: $("#preview-service-card-image"),
    serviceCardIcon: $("#preview-service-card-icon"),
  };

  const formInputs = {
    headerText: $("#bf_header_text"),
    description: $("#bf_description"),
    themeColor: $("#bf_theme_color"),
    backgroundColor: $("#bf_background_color"),
    textColor: $("#bf_text_color"),
    borderRadius: $("#bf_border_radius"),
    progressDisplayStyle: $('input[name="bf_progress_display_style"]'),
    serviceCardDisplay: $('input[name="bf_service_card_display"]'),
  };

  function updatePreview() {
    const themeColor = formInputs.themeColor.val() || "#1abc9c";
    const backgroundColor = formInputs.backgroundColor.val() || "#ffffff";
    const textColor = formInputs.textColor.val() || "#333333";
    const borderRadius = (formInputs.borderRadius.val() || 8) + "px";
    const progressStyle =
      formInputs.progressDisplayStyle.filter(":checked").val() || "bar";
    const cardDisplay =
      formInputs.serviceCardDisplay.filter(":checked").val() || "icon";

    // Update content
    const headerText =
      formInputs.headerText.val() || "Book Our Services Online";
    const descriptionText =
      formInputs.description.val() ||
      "Complete the steps below to schedule your service";

    preview.headerText.text(headerText);
    preview.description.text(descriptionText);

    // Update CSS custom properties on the wrapper
    preview.wrapper.css({
      "--preview-bg": backgroundColor,
      "--preview-text": textColor,
      "--preview-primary": themeColor,
      "--preview-radius": borderRadius,
    });

    // Toggle progress bar based on style
    if (progressStyle === "none") {
      preview.progressContainer.hide();
    } else {
      preview.progressContainer.show();
    }

    // Toggle service card display
    if (cardDisplay === "image") {
      preview.serviceCardImage.show();
      preview.serviceCardIcon.hide();
    } else {
      preview.serviceCardImage.hide();
      preview.serviceCardIcon.show();
    }

    // Add a subtle animation to show the change
    preview.form.addClass("preview-updating");
    setTimeout(() => {
      preview.form.removeClass("preview-updating");
    }, 300);
  }

  // Bind events to all form inputs
  Object.values(formInputs).forEach((input) => {
    if (input.length) {
      input.on("input change", updatePreview);
    }
  });

  // Also trigger for color picker changes
  $(".NORDBOOKING-color-picker").on("colorchange", updatePreview);

  // Handle radio button changes specifically
  $(
    'input[name="bf_progress_display_style"], input[name="bf_service_card_display"]'
  ).on("change", updatePreview);

  // Initial call to set up preview
  updatePreview();

  // Add smooth transition class
  preview.form.addClass("preview-ready");

  console.log("NORDBOOKING Booking Form Settings initialized successfully");
});
// Add realistic preview interactions
$(document).ready(function () {
  // Add click interaction to service card
  $(".preview-service-card").on("click", function () {
    $(this).addClass("selected");
    setTimeout(() => {
      $(this).removeClass("selected");
    }, 1000);
  });

  // Add focus effects to form inputs
  $(".preview-form-group input, .preview-form-group select")
    .on("focus", function () {
      $(this).closest(".preview-form-group").addClass("focused");
    })
    .on("blur", function () {
      $(this).closest(".preview-form-group").removeClass("focused");
    });

  // Add hover effect to continue button
  $(".preview-button")
    .on("mouseenter", function () {
      $(this).addClass("hovered");
    })
    .on("mouseleave", function () {
      $(this).removeClass("hovered");
    });

  // Simulate progress bar animation
  function animateProgress() {
    const progressFill = $(".preview-progress-fill");
    const currentWidth = parseInt(progressFill.css("width"));
    const maxWidth = progressFill.parent().width();
    const percentage = (currentWidth / maxWidth) * 100;

    if (percentage < 100) {
      progressFill.css("width", Math.min(percentage + 10, 100) + "%");
    } else {
      progressFill.css("width", "33%");
    }
  }

  // Animate progress every 3 seconds
  setInterval(animateProgress, 3000);
});
