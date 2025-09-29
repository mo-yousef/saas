jQuery(document).ready(function ($) {
  "use strict";

  console.log("=== NORDBOOKING Business Settings Script Starting ===");
  console.log("jQuery version:", $.fn.jquery);
  console.log("Document ready fired at:", new Date().toISOString());

  if (typeof nordbooking_biz_settings_params === "undefined") {
    console.error("Business settings parameters are not available.");
    console.error(
      "Available global variables:",
      Object.keys(window).filter((k) => k.includes("nordbooking"))
    );
    return;
  }

  console.log(
    "Business settings params loaded:",
    nordbooking_biz_settings_params
  );

  // Ensure showAlert function is available
  if (typeof window.showAlert !== "function") {
    console.warn("showAlert not available, using alert fallback");
    window.showAlert = function (message, type) {
      console.warn("showAlert fallback called:", message, type);
      alert(type.toUpperCase() + ": " + message);
    };
  } else {
    console.log("showAlert function is available");
  }

  // --- Load Existing Settings ---
  function loadExistingSettings() {
    console.log("Loading existing settings...");
    
    $.ajax({
      url: nordbooking_biz_settings_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_business_settings",
        nonce: nordbooking_biz_settings_params.nonce,
      },
      success: function(response) {
        if (response.success && response.data.settings) {
          const settings = response.data.settings;
          console.log("Loaded settings:", settings);
          
          // Populate form fields
          Object.keys(settings).forEach(function(key) {
            const value = settings[key];
            const $field = $('[name="' + key + '"]');
            
            if ($field.length > 0) {
              if ($field.is(':checkbox')) {
                $field.prop('checked', value === '1' || value === true);
              } else if ($field.is(':radio')) {
                $field.filter('[value="' + value + '"]').prop('checked', true);
              } else {
                $field.val(value);
              }
            }
          });
          
          // Handle logo preview
          if (settings.biz_logo_url && settings.biz_logo_url !== '') {
            $('.logo-preview').html('<img src="' + settings.biz_logo_url + '" alt="Company Logo">');
            $('#NORDBOOKING-remove-logo-btn').show();
          }
          
          // Trigger color picker updates
          if (typeof $.fn.wpColorPicker === "function") {
            $('.NORDBOOKING-color-picker').wpColorPicker('refresh');
          }
          
          // Update email notification toggles
          $('.email-toggle-switch input[type="checkbox"]').trigger('change');
          $('input[name*="_use_primary"]').trigger('change');
          
          console.log("Settings loaded and form populated successfully");
        } else {
          console.error("Failed to load settings:", response);
        }
      },
      error: function(xhr, status, error) {
        console.error("Error loading settings:", xhr, status, error);
      }
    });
  }

  // --- Tab Navigation ---
  const navTabs = $(".nav-tab-wrapper .nav-tab");
  const tabContents = $(".settings-tab-content");

  console.log(
    "Found tabs:",
    navTabs.length,
    "Found tab contents:",
    tabContents.length
  );

  navTabs.on("click", function (e) {
    e.preventDefault();
    const tabId = $(this).data("tab");
    console.log("Tab clicked:", tabId);

    navTabs.removeClass("nav-tab-active");
    $(this).addClass("nav-tab-active");

    tabContents.hide();
    const targetTab = $("#" + tabId + "-tab");
    console.log("Target tab element:", targetTab.length);
    targetTab.show();

    if (history.pushState) {
      history.pushState(null, null, "#" + tabId);
    } else {
      location.hash = "#" + tabId;
    }
  });

  // Activate tab based on URL hash on page load
  if (window.location.hash) {
    const hashWithoutHash = window.location.hash.substring(1);
    const activeTab = navTabs.filter('[data-tab="' + hashWithoutHash + '"]');
    if (activeTab.length) {
      activeTab.trigger("click");
    } else {
      // Default to the first tab if hash doesn't match any tab
      navTabs.first().trigger("click");
    }
  } else {
    // Default to the first tab if no hash is present
    navTabs.first().trigger("click");
  }

  // --- Color Picker ---
  if (typeof $.fn.wpColorPicker === "function") {
    $(".NORDBOOKING-color-picker").wpColorPicker();
  } else {
    console.warn("WordPress Color Picker not available");
  }

  // --- Logo Uploader ---
  const logoFileInput = $("#NORDBOOKING-logo-file-input");
  const uploadLogoBtn = $("#NORDBOOKING-upload-logo-btn");
  const removeLogoBtn = $("#NORDBOOKING-remove-logo-btn");
  const logoPreview = $(".logo-preview");
  const progressBarWrapper = $(".progress-bar-wrapper");
  const progressBar = $(".progress-bar");

  uploadLogoBtn.on("click", (e) => {
    e.preventDefault();
    logoFileInput.click();
  });

  logoFileInput.on("change", function () {
    if (this.files[0]) {
      uploadLogo(this.files[0]);
    }
  });

  function uploadLogo(file) {
    const formData = new FormData();
    formData.append("logo", file);
    formData.append("action", "nordbooking_upload_logo");
    formData.append("nonce", nordbooking_biz_settings_params.nonce);

    $.ajax({
      url: nordbooking_biz_settings_params.ajax_url,
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      xhr: function () {
        const xhr = new window.XMLHttpRequest();
        xhr.upload.addEventListener(
          "progress",
          (evt) => {
            if (evt.lengthComputable) {
              const percent = Math.round((evt.loaded / evt.total) * 100);
              progressBar.width(percent + "%");
            }
          },
          false
        );
        return xhr;
      },
      beforeSend: () => {
        progressBar.width("0%");
        progressBarWrapper.show();
      },
      success: (response) => {
        if (response.success) {
          $("#biz_logo_url").val(response.data.url);
          logoPreview.html(
            `<img src="${response.data.url}" alt="Company Logo">`
          );
          removeLogoBtn.show();
          window.showAlert("Logo uploaded successfully.", "success");
        } else {
          window.showAlert(
            response.data.message || "Error uploading logo.",
            "error"
          );
        }
      },
      error: () => window.showAlert("AJAX error.", "error"),
      complete: () => progressBarWrapper.hide(),
    });
  }

  removeLogoBtn.on("click", (e) => {
    e.preventDefault();
    $("#biz_logo_url").val("");
    logoPreview.html(
      '<div class="logo-placeholder"><span>No Logo</span></div>'
    );
    $(this).hide();
  });

  // --- Email Notification Toggles ---
  $('.email-toggle-switch input[type="checkbox"]').on('change', function() {
    const isEnabled = $(this).is(':checked');
    const notificationItem = $(this).closest('.email-notification-item');
    const recipientSettings = notificationItem.find('.email-recipient-settings');
    
    if (isEnabled) {
      recipientSettings.css({
        'opacity': '1',
        'pointer-events': 'auto'
      });
      notificationItem.removeClass('disabled');
    } else {
      recipientSettings.css({
        'opacity': '0.5',
        'pointer-events': 'none'
      });
      notificationItem.addClass('disabled');
    }
  });

  // Handle radio button changes for email recipient selection
  $('input[name*="_use_primary"]').on('change', function() {
    const usePrimary = $(this).val() === '1';
    const notificationItem = $(this).closest('.email-notification-item');
    const customEmailField = notificationItem.find('.custom-email-field');
    
    if (usePrimary) {
      customEmailField.css({
        'opacity': '0.5',
        'pointer-events': 'none'
      });
    } else {
      customEmailField.css({
        'opacity': '1',
        'pointer-events': 'auto'
      });
    }
  });

  // --- Main Form Submission ---
  const form = $("#NORDBOOKING-business-settings-form");
  const saveButtons = $(
    "#NORDBOOKING-save-biz-settings-btn, #NORDBOOKING-save-biz-settings-btn-footer"
  );

  let isSubmitting = false; // Prevent multiple submissions

  // Remove any existing handlers to prevent duplicates
  form.off("submit");
  saveButtons.off("click");

  form.on("submit", function (e) {
    e.preventDefault();
    e.stopPropagation();
    
    if (isSubmitting) {
      console.log("Form submission already in progress, ignoring");
      return false;
    }
    
    isSubmitting = true;
    console.log("Form submission started");

    const originalButtonText = saveButtons.first().text();
    saveButtons
      .prop("disabled", true)
      .text(nordbooking_biz_settings_params.i18n.saving || "Saving...");

    // Serialize form data including checkboxes and radio buttons
    let settingsData = {};
    
    // Get all form elements
    $(this).find('input, select, textarea').each(function() {
      const $element = $(this);
      const name = $element.attr('name');
      
      if (!name || name === 'nordbooking_dashboard_nonce_field' || name === '_wp_http_referer') return;
      
      if ($element.is(':checkbox')) {
        // For checkboxes, set value to '1' if checked, '0' if not
        settingsData[name] = $element.is(':checked') ? '1' : '0';
      } else if ($element.is(':radio')) {
        // For radio buttons, only set value if this one is checked
        if ($element.is(':checked')) {
          settingsData[name] = $element.val();
        }
      } else {
        // For other inputs, just get the value
        settingsData[name] = $element.val();
      }
    });

    console.log("Settings data to save:", settingsData);

    $.ajax({
      url: nordbooking_biz_settings_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_save_business_settings",
        nonce: nordbooking_biz_settings_params.nonce,
        settings: settingsData,
      },
      success: (response) => {
        console.log("AJAX response:", response);
        if (response.success) {
          window.showAlert(
            response.data.message ||
              nordbooking_biz_settings_params.i18n.save_success,
            "success"
          );
        } else {
          console.error("Save failed:", response.data);
          window.showAlert(
            response.data.message ||
              nordbooking_biz_settings_params.i18n.error_saving,
            "error"
          );
        }
      },
      error: (xhr, status, error) => {
        console.error("AJAX error:", xhr, status, error);
        console.error("Response text:", xhr.responseText);
        
        let errorMessage = nordbooking_biz_settings_params.i18n.error_ajax;
        
        // Try to extract more specific error information
        if (xhr.responseText) {
          try {
            const errorResponse = JSON.parse(xhr.responseText);
            if (errorResponse.data && errorResponse.data.message) {
              errorMessage = errorResponse.data.message;
            }
          } catch (e) {
            // If it's not JSON, check for common error patterns
            if (xhr.responseText.includes('Fatal error')) {
              errorMessage = 'Server error occurred. Please check the error logs.';
            }
          }
        }
        
        window.showAlert(errorMessage, "error");
      },
      complete: () => {
        saveButtons.prop("disabled", false).text(originalButtonText);
        isSubmitting = false; // Reset the flag
      },
    });
    
    return false;
  });

  // --- Load Settings on Page Load ---
  loadExistingSettings();
});
