jQuery(document).ready(function ($) {
  "use strict";

  if (typeof nordbooking_biz_settings_params === "undefined") {
    console.error("Business settings parameters are not available.");
    return;
  }

  // --- Tab Navigation ---
  const navTabs = $(".nav-tab-wrapper .nav-tab");
  const tabContents = $(".settings-tab-content");

  navTabs.on("click", function (e) {
    e.preventDefault();
    const tabId = $(this).data("tab");

    navTabs.removeClass("nav-tab-active");
    $(this).addClass("nav-tab-active");

    tabContents.hide();
    $("#" + tabId + "-tab").show();

    if (history.pushState) {
      history.pushState(null, null, "#" + tabId);
    } else {
      location.hash = "#" + tabId;
    }
  });

  // Activate tab based on URL hash on page load
  if (window.location.hash) {
    const activeTab = navTabs.filter('[href="' + window.location.hash + '"]');
    if (activeTab.length) {
      activeTab.trigger("click");
    }
  } else {
    // Default to the first tab if no hash is present
    navTabs.first().trigger("click");
  }

  // --- Color Picker ---
  $(".NORDBOOKING-color-picker").wpColorPicker();

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
    const originalButtonText = saveButtons.first().text();
    saveButtons
      .prop("disabled", true)
      .text(nordbooking_biz_settings_params.i18n.saving || "Saving...");

    // Serialize form data, including dynamically updated hidden fields from the email editor
    let settingsData = $(this)
      .serializeArray()
      .reduce((obj, item) => {
        obj[item.name] = item.value;
        return obj;
      }, {});

    $.ajax({
      url: nordbooking_biz_settings_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_save_business_settings",
        nonce: nordbooking_biz_settings_params.nonce,
        settings: settingsData,
      },
      success: (response) => {
        if (response.success) {
          window.showAlert(
            response.data.message ||
              nordbooking_biz_settings_params.i18n.save_success,
            "success"
          );
        } else {
          window.showAlert(
            response.data.message ||
              nordbooking_biz_settings_params.i18n.error_saving,
            "error"
          );
        }
      },
      error: () => {
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
