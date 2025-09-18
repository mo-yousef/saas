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

  // --- Main Form Submission ---
  const form = $("#NORDBOOKING-business-settings-form");
  const saveButtons = $(
    "#NORDBOOKING-save-biz-settings-btn, #NORDBOOKING-save-biz-settings-btn-footer"
  );

  form.on("submit", function (e) {
    e.preventDefault();
    console.log("Form submission started");

    const originalButtonText = saveButtons.first().text();
    saveButtons
      .prop("disabled", true)
      .text(nordbooking_biz_settings_params.i18n.saving || "Saving...");

    // Serialize form data
    let settingsData = $(this)
      .serializeArray()
      .reduce((obj, item) => {
        obj[item.name] = item.value;
        return obj;
      }, {});

    console.log("Settings data to save:", settingsData);

    // Add email template data if the editor is present and initialized
    if (window.EmailEditor && window.EmailEditor.isInitialized) {
      const emailData = window.EmailEditor.templatesData;
      const emailTemplates = nordbooking_biz_settings_params.templates;
      for (const key in emailData) {
        if (
          emailData.hasOwnProperty(key) &&
          emailTemplates.hasOwnProperty(key)
        ) {
          const subjectKey = emailTemplates[key].subject_key;
          const bodyKey = emailTemplates[key].body_key;
          settingsData[subjectKey] = emailData[key].subject;
          settingsData[bodyKey] = JSON.stringify(emailData[key].body);
        }
      }
    }

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
        window.showAlert(
          nordbooking_biz_settings_params.i18n.error_ajax,
          "error"
        );
      },
      complete: () => {
        saveButtons.prop("disabled", false).text(originalButtonText);
      },
    });
  });
});
