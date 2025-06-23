jQuery(document).ready(function ($) {
  "use strict";

  // Robust initialization for mobooking_bf_settings_params
  if (typeof window.mobooking_bf_settings_params === "undefined") {
    console.error("CRITICAL: mobooking_bf_settings_params is not defined. Please ensure it is localized by WordPress.");
    window.mobooking_bf_settings_params = {};
  }
  window.mobooking_bf_settings_params.ajax_url = window.mobooking_bf_settings_params.ajax_url || '';
  window.mobooking_bf_settings_params.nonce = window.mobooking_bf_settings_params.nonce || '';
  window.mobooking_bf_settings_params.site_url = window.mobooking_bf_settings_params.site_url || (window.location.origin + '/');
  window.mobooking_bf_settings_params.i18n = window.mobooking_bf_settings_params.i18n || {};
  const i18n_defaults = {
    saving: "Saving...",
    save_success: "Settings saved.",
    error_saving: "Error saving.",
    error_loading: "Error loading.",
    error_ajax: "AJAX error.",
    invalid_json: "Invalid JSON.",
    copied: "Copied!",
    copy_failed: "Copy failed.",
    booking_form_title: "Booking Form",
    link_will_appear_here: "Link will appear here once slug is saved.",
    embed_will_appear_here: "Embed code will appear here once slug is saved."
  };
  for (const key in i18n_defaults) {
    if (typeof window.mobooking_bf_settings_params.i18n[key] === 'undefined') {
      window.mobooking_bf_settings_params.i18n[key] = i18n_defaults[key];
    }
  }
  // End robust initialization

  const form = $("#mobooking-booking-form-settings-form");
  const feedbackDiv = $("#mobooking-settings-feedback");
  const saveButton = $("#mobooking-save-bf-settings-btn");

  // Tab navigation
  const navTabs = $(".nav-tab-wrapper .nav-tab");
  const tabContents = $(".mobooking-settings-tab-content");

  navTabs.on("click", function (e) {
    e.preventDefault();
    navTabs.removeClass("nav-tab-active");
    $(this).addClass("nav-tab-active");
    tabContents.hide();
    $("#mobooking-" + $(this).data("tab") + "-settings-tab").show();
  });

  // Initialize Color Picker
  if (typeof $.fn.wpColorPicker === "function") {
    $(".mobooking-color-picker").wpColorPicker();
  } else {
    console.warn("WP Color Picker not available.");
  }

  // Dynamic update for public link and embed code
  const businessSlugInput = $("#bf_business_slug");
  const publicLinkInput = $("#mobooking-public-link");
  const embedCodeTextarea = $("#mobooking-embed-code");
  const copyLinkBtn = $("#mobooking-copy-public-link-btn");
  const copyEmbedBtn = $("#mobooking-copy-embed-code-btn");

  let baseSiteUrl = mobooking_bf_settings_params.site_url;
  if (baseSiteUrl.slice(-1) !== '/') {
    baseSiteUrl += '/';
  }

  function updateShareableLinks(slug) {
    const sanitizedSlug = slug.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, '');
    if (sanitizedSlug) {
      const bookingFormTitle = mobooking_bf_settings_params.i18n.booking_form_title;
      const publicLink = baseSiteUrl + sanitizedSlug + '/booking/';
      const embedCode = `<iframe src="${publicLink}" title="${bookingFormTitle}" style="width:100%; height:800px; border:1px solid #ccc;"></iframe>`;
      publicLinkInput.val(publicLink);
      embedCodeTextarea.val(embedCode);
      copyLinkBtn.prop("disabled", false);
      copyEmbedBtn.prop("disabled", false);
    } else {
      publicLinkInput.val("").attr("placeholder", mobooking_bf_settings_params.i18n.link_will_appear_here);
      embedCodeTextarea.val("").attr("placeholder", mobooking_bf_settings_params.i18n.embed_will_appear_here);
      copyLinkBtn.prop("disabled", true);
      copyEmbedBtn.prop("disabled", true);
    }
  }

  if (businessSlugInput.length) {
    businessSlugInput.on("input", function () {
      updateShareableLinks($(this).val());
    });
    updateShareableLinks(businessSlugInput.val()); // Initial update
  }

  form.on("submit", function (e) {
    e.preventDefault();

    let currentSlug = businessSlugInput.val();
    if (currentSlug) { // Only sanitize if there's a value, to avoid 'undefined' issues if field is empty
        let sanitizedClientSlug = currentSlug.trim().toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]+/g, '').replace(/^-+|-+$/g, '');
        businessSlugInput.val(sanitizedClientSlug);
    }

    feedbackDiv.empty().removeClass("success error notice notice-success notice-error").hide();
    const originalButtonText = saveButton.text();
    saveButton.prop("disabled", true).text(mobooking_bf_settings_params.i18n.saving);

    let settingsData = {};
    form.find(':input:not([type="submit"])').each(function () {
      const $field = $(this);
      const name = $field.attr("name");
      if (!name) return; // Skip inputs without a name

      // Skip nonce field and other WP fields from being part of the settings data payload
      if (name === 'mobooking_dashboard_nonce_field' || name === '_wp_http_referer') {
          return;
      }

      if ($field.is(":checkbox")) {
        settingsData[name] = $field.is(":checked") ? "1" : "0";
      } else if ($field.is(":radio")) { // Though no radio buttons on this specific form
        if ($field.is(":checked")) {
          settingsData[name] = $field.val();
        }
      } else {
        settingsData[name] = $field.val();
      }
    });

    if (!mobooking_bf_settings_params.ajax_url || !mobooking_bf_settings_params.nonce) {
        console.error("AJAX URL or Nonce is missing. Cannot save settings.", mobooking_bf_settings_params);
        feedbackDiv.text("Configuration error: AJAX URL or Nonce missing.").addClass("notice notice-error").show();
        saveButton.prop("disabled", false).text(originalButtonText);
        return;
    }

    $.ajax({
      url: mobooking_bf_settings_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_save_booking_form_settings",
        nonce: mobooking_bf_settings_params.nonce,
        settings: settingsData,
      },
      success: function (response) {
        if (response.success) {
          feedbackDiv.text(response.data.message || mobooking_bf_settings_params.i18n.save_success).addClass("notice notice-success").show();
          // If slug was saved, ensure the shareable links are based on the *potentially server-sanitized* new value.
          // Best way is if server returns the saved slug, or we re-fetch settings.
          // For now, client-side value is already updated by sanitize on input, and then again on submit.
          if (settingsData.bf_business_slug !== undefined) {
             updateShareableLinks(settingsData.bf_business_slug); // re-run with current (client-sanitized) slug
          }
        } else {
          feedbackDiv.text(response.data.message || mobooking_bf_settings_params.i18n.error_saving).addClass("notice notice-error").show();
        }
      },
      error: function () {
        feedbackDiv.text(mobooking_bf_settings_params.i18n.error_ajax).addClass("notice notice-error").show();
      },
      complete: function () {
        saveButton.prop("disabled", false).text(originalButtonText);
        setTimeout(function () { feedbackDiv.fadeOut(); }, 5000);
      },
    });
  });

  // Copy to clipboard functionality
  function copyToClipboard(element, feedbackElement, buttonElement) {
    if (!element || !element.value) return;
    navigator.clipboard.writeText(element.value)
      .then(function () {
        const originalButtonText = buttonElement.text();
        feedbackElement.text(mobooking_bf_settings_params.i18n.copied).show();
        buttonElement.text(mobooking_bf_settings_params.i18n.copied);
        setTimeout(function () {
          feedbackElement.fadeOut().empty();
          buttonElement.text(originalButtonText);
        }, 2000);
      })
      .catch(function (err) {
        console.error("Failed to copy text: ", err);
        feedbackElement.text(mobooking_bf_settings_params.i18n.copy_failed).addClass("error").show();
        setTimeout(function () { feedbackElement.fadeOut().empty().removeClass("error");}, 2000);
      });
  }

  $("#mobooking-copy-public-link-btn").on("click", function () {
    copyToClipboard(document.getElementById("mobooking-public-link"), $("#mobooking-copy-link-feedback"), $(this));
  });

  $("#mobooking-copy-embed-code-btn").on("click", function () {
    copyToClipboard(document.getElementById("mobooking-embed-code"), $("#mobooking-copy-embed-feedback"), $(this));
  });
});
