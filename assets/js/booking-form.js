jQuery(document).ready(function ($) {
  "use strict";

  const locationForm = $("#mobooking-bf-location-form");
  const feedbackDiv = $("#mobooking-bf-feedback"); // General feedback for Step 1 (location check)
  const step1Div = $("#mobooking-bf-step-1-location");
  const tenantIdField = $("#mobooking-bf-tenant-id");

  // Step 2 elements
  const step2ServicesDiv = $("#mobooking-bf-step-2-services");
  const servicesListDiv = $("#mobooking-bf-services-list");
  const step2FeedbackDiv = $("#mobooking-bf-step-2-feedback").hide();

  // Step 3 elements
  const step3OptionsDiv = $("#mobooking-bf-step-3-options");
  const serviceOptionsDisplayDiv = $("#mobooking-bf-service-options-display");
  const step3FeedbackDiv = $("#mobooking-bf-step-3-feedback").hide();

  // Step 4 elements
  const step4DetailsDiv = $("#mobooking-bf-step-4-details");
  const step4FeedbackDiv = $("#mobooking-bf-step-4-feedback").hide();

  // Step 5 elements
  const step5ReviewDiv = $("#mobooking-bf-step-5-review");
  const reviewSummaryDiv = $("#mobooking-bf-review-summary");
  const step5FeedbackDiv = $("#mobooking-bf-step-5-feedback").hide();
  const discountCodeInput = $("#mobooking-bf-discount-code");
  const applyDiscountBtn = $("#mobooking-bf-apply-discount-btn");
  const discountFeedbackDiv = $("#mobooking-bf-discount-feedback").hide();
  const subtotalDisplay = $("#mobooking-bf-subtotal");
  const discountAppliedDisplay = $("#mobooking-bf-discount-applied");
  const finalTotalDisplay = $("#mobooking-bf-final-total");
  const currencyCode =
    typeof mobooking_booking_form_params !== "undefined" &&
    mobooking_booking_form_params.currency_code
      ? mobooking_booking_form_params.currency_code
      : "USD";

  // Access settings, ensure defaults if not fully populated
  const formSettings =
    typeof mobooking_booking_form_params !== "undefined" &&
    mobooking_booking_form_params.settings
      ? mobooking_booking_form_params.settings
      : {
          // Fallback defaults matching Settings.php if params totally fail (should not happen with PHP logic)
          bf_header_text: "Book Our Services",
          bf_show_pricing: "1",
          bf_allow_discount_codes: "1",
          bf_theme_color: "#1abc9c",
          bf_custom_css: "",
          bf_form_enabled: "1",
          bf_maintenance_message: "Booking form is currently unavailable.",
          bf_enable_location_check: "1", // Default to enabled
        };

  // Step 6 elements
  const step6ConfirmDiv = $("#mobooking-bf-step-6-confirmation");
  const confirmationMessageDiv = $("#mobooking-bf-confirmation-message");

  let mobooking_current_step = 1; // Keep track of the current visible step
  let publicServicesCache = []; // Cache for service data from Step 2

  // Enhanced tenant ID detection and initialization
  let initialTenantId = "";
  if (
    typeof mobooking_booking_form_params !== "undefined" &&
    mobooking_booking_form_params.tenant_id
  ) {
    initialTenantId = String(mobooking_booking_form_params.tenant_id);
  }

  if (initialTenantId && initialTenantId !== "0") {
    tenantIdField.val(initialTenantId);
    sessionStorage.setItem("mobooking_cart_tenant_id", initialTenantId);
    console.log("Booking form initialized with Tenant ID:", initialTenantId);
  } else {
    console.warn(
      "Booking form: Tenant ID is missing or invalid from mobooking_booking_form_params."
    );
    if (
      typeof mobooking_booking_form_params !== "undefined" &&
      mobooking_booking_form_params.debug_info
    ) {
      console.log("Debug info:", mobooking_booking_form_params.debug_info);
    }
  }

  // Apply settings from localized params
  if (formSettings.bf_form_enabled === "0") {
    // Form is disabled, show maintenance message and hide all steps
    $("#mobooking-public-booking-form-wrapper").html(
      `<div style="padding:20px; text-align:center; border:1px solid #eee; background:#f9f9f9;">
            <h1>${
              formSettings.bf_maintenance_message ||
              "Bookings are temporarily unavailable."
            }</h1>
         </div>`
    );
    // No need to proceed further with form initialization if it's disabled.
    return; // Exit ready function
  }

  // Handle bf_show_progress_bar (Placeholder log)
  if (formSettings.bf_show_progress_bar === "1") {
    console.log(
      "MoBooking: Progress bar setting is enabled, but UI is not implemented in this version."
    );
    // Future: Implement or call progress bar rendering function here.
  }

  if (formSettings.bf_header_text) {
    $("#mobooking-public-booking-form-wrapper > h1").text(
      formSettings.bf_header_text
    );
  }

  if (formSettings.bf_custom_css) {
    $('<style type="text/css"></style>')
      .html(formSettings.bf_custom_css)
      .appendTo("head");
  }

  if (formSettings.bf_theme_color) {
    const themeColorStyle = `
      .mobooking-bf-step button.button-primary,
      #mobooking-bf-review-confirm-btn,
      #mobooking-bf-apply-discount-btn {
        background-color: ${formSettings.bf_theme_color} !important;
        border-color: ${formSettings.bf_theme_color} !important;
        color: #fff !important;
      }
      /* Example for progress bar (if implemented) or active step headers */
      /* .mobooking-progress-bar .step.active { background-color: ${formSettings.bf_theme_color}; } */
    `;
    $('<style type="text/css"></style>').html(themeColorStyle).appendTo("head");
  }

  if (formSettings.bf_allow_discount_codes === "1") {
    $("#mobooking-bf-discount-section").show();
  } else {
    $("#mobooking-bf-discount-section").hide();
    sessionStorage.removeItem("mobooking_cart_discount_info"); // Clear any stored discount
  }

  // Enhanced location check logic
  if (formSettings.bf_enable_location_check === "0") {
    console.log("Location check is disabled");
    step1Div.hide();

    // Try multiple methods to get tenant ID
    let tenantIdForServices = null;

    // Method 1: From localized params
    if (
      typeof mobooking_booking_form_params !== "undefined" &&
      mobooking_booking_form_params.tenant_id
    ) {
      tenantIdForServices = String(mobooking_booking_form_params.tenant_id);
      console.log("Method 1: Got tenant ID from params:", tenantIdForServices);
    }

    // Method 2: From session storage (if previously set)
    if (!tenantIdForServices || tenantIdForServices === "0") {
      tenantIdForServices = sessionStorage.getItem("mobooking_cart_tenant_id");
      console.log(
        "Method 2: Got tenant ID from session storage:",
        tenantIdForServices
      );
    }

    // Method 3: From hidden field in form
    if (!tenantIdForServices || tenantIdForServices === "0") {
      tenantIdForServices = tenantIdField.val();
      console.log(
        "Method 3: Got tenant ID from form field:",
        tenantIdForServices
      );
    }

    // Method 4: From URL parameters (fallback)
    if (!tenantIdForServices || tenantIdForServices === "0") {
      const urlParams = new URLSearchParams(window.location.search);
      tenantIdForServices = urlParams.get("tid");
      console.log(
        "Method 4: Got tenant ID from URL params:",
        tenantIdForServices
      );
    }

    if (tenantIdForServices && tenantIdForServices !== "0") {
      console.log(
        "Location check disabled. Using Tenant ID for direct Step 2:",
        tenantIdForServices
      );

      // Set in both places for consistency
      sessionStorage.setItem("mobooking_cart_tenant_id", tenantIdForServices);
      tenantIdField.val(tenantIdForServices);

      // Set default location data since location check is disabled
      sessionStorage.setItem("mobooking_cart_zip", "00000");
      sessionStorage.setItem("mobooking_cart_country", "US");

      // Show Step 2 and load services
      displayStep(2);
      displayStep2_LoadServices();
    } else {
      console.error(
        "Booking form: Location check disabled, but no valid Tenant ID found. Cannot proceed."
      );
      console.log("All tenant ID attempts failed:", {
        fromParams: mobooking_booking_form_params?.tenant_id,
        fromSession: sessionStorage.getItem("mobooking_cart_tenant_id"),
        fromField: tenantIdField.val(),
        fromUrl: new URLSearchParams(window.location.search).get("tid"),
      });

      // Show detailed error message
      $("#mobooking-public-booking-form-wrapper").html(
        `<div class="mobooking-bf__step" style="display:block; padding: 30px; text-align: center; border: 2px solid #e74c3c; border-radius: 8px; background: #fdf2f2;">
          <div style="margin-bottom: 20px;">
            <svg style="width: 64px; height: 64px; color: #e74c3c;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
          </div>
          <h2 class="mobooking-bf__step-title" style="color: #e74c3c; margin-bottom: 15px;">Configuration Error</h2>
          <p style="color: #c0392b; margin-bottom: 20px; font-size: 16px; line-height: 1.5;">
            ${
              mobooking_booking_form_params?.i18n?.tenant_id_missing ||
              "Business identifier is missing. Cannot load booking form."
            }
          </p>
          <div style="background: #fff; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 10px 0; color: #2c3e50;">Debug Information:</h3>
            <div style="text-align: left; font-family: monospace; font-size: 12px; color: #7f8c8d;">
              <p><strong>URL:</strong> ${window.location.href}</p>
              <p><strong>Tenant ID from params:</strong> ${
                mobooking_booking_form_params?.tenant_id || "Not set"
              }</p>
              <p><strong>Location check disabled:</strong> Yes</p>
              <p><strong>Page type:</strong> ${
                mobooking_booking_form_params?.debug_info?.page_type ||
                "Unknown"
              }</p>
            </div>
          </div>
          <p style="color: #7f8c8d; font-size: 14px; margin: 0;">
            Please contact support if this problem persists, and include the debug information above.
          </p>
        </div>`
      );
    }
  } else {
    console.log("Location check is enabled, showing Step 1");
    displayStep(1);
  }

  // --- Apply Dynamic Styles from Settings ---
  const formWrapper = $("#mobooking-public-booking-form-wrapper");
  if (formWrapper.length) {
    if (formSettings.bf_theme_color) {
      formWrapper.css("--mobk-color-primary", formSettings.bf_theme_color);
      // Potentially derive a ring color if not set separately
      formWrapper.css("--mobk-color-ring", formSettings.bf_theme_color);
    }
    if (formSettings.bf_secondary_color) {
      // Assuming this is for secondary button BG or similar accents
      formWrapper.css(
        "--mobk-color-secondary",
        formSettings.bf_secondary_color
      );
    }
    if (formSettings.bf_background_color) {
      formWrapper.css(
        "--mobk-color-background",
        formSettings.bf_background_color
      );
      // If card background is same as page background, this might need adjustment or card specific var
      formWrapper.css("--mobk-color-card", formSettings.bf_background_color); // Example: card matches page bg
      // Or, keep card default white and let this be page bg only
    }
    if (formSettings.bf_font_family) {
      formWrapper.css("--mobk-font-family", formSettings.bf_font_family);
      // Also apply to body if the form is the main content and not embedded
      if (!$("body").hasClass("mobooking-form-embed-active")) {
        $("body").css("font-family", formSettings.bf_font_family);
      }
    }
    if (formSettings.bf_border_radius) {
      // Assuming bf_border_radius is a number, append 'px' or use as is if it's a full CSS value.
      // Shadcn uses rem, e.g., 0.5rem. If bf_border_radius stores "8", it could be "8px".
      const radiusValue = /^\d+$/.test(formSettings.bf_border_radius)
        ? formSettings.bf_border_radius + "px"
        : formSettings.bf_border_radius;
      formWrapper.css("--mobk-border-radius", radiusValue);
    }
  }

  locationForm.on("submit", function (e) {
    e.preventDefault();
    feedbackDiv.empty().removeClass("success error").hide();
    const zipCode = $("#mobooking-bf-zip-code").val().trim();
    const countryCode = $("#mobooking-bf-country-code").val().trim();
    const tenantId = tenantIdField.val();

    if (!zipCode) {
      feedbackDiv
        .text(
          mobooking_booking_form_params.i18n.zip_required ||
            "ZIP Code is required."
        )
        .addClass("error")
        .show();
      return;
    }
    if (!tenantId) {
      feedbackDiv
        .text(
          mobooking_booking_form_params.i18n.tenant_id_missing ||
            "Business identifier is missing."
        )
        .addClass("error")
        .show();
      return;
    }
    if (!countryCode) {
      feedbackDiv
        .text(
          mobooking_booking_form_params.i18n.country_code_required ||
            "Country Code is required."
        )
        .addClass("error")
        .show();
      return;
    }

    const submitButton = $(this).find('button[type="submit"]');
    const originalButtonText = submitButton.text();
    submitButton
      .prop("disabled", true)
      .text(mobooking_booking_form_params.i18n.checking || "Checking...");

    $.ajax({
      url: mobooking_booking_form_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_check_zip_availability",
        nonce: mobooking_booking_form_params.nonce,
        zip_code: zipCode,
        country_code: countryCode,
        tenant_id: tenantId,
      },
      success: function (response) {
        if (response.success) {
          feedbackDiv
            .text(response.data.message)
            .addClass(response.data.serviced ? "success" : "error")
            .show();
          if (response.data.serviced) {
            sessionStorage.setItem("mobooking_cart_zip", zipCode);
            sessionStorage.setItem("mobooking_cart_country", countryCode);
            sessionStorage.setItem("mobooking_cart_tenant_id", tenantId);
            displayStep(2);
            displayStep2_LoadServices();
          }
        } else {
          feedbackDiv
            .text(
              response.data.message ||
                mobooking_booking_form_params.i18n.error_generic
            )
            .addClass("error")
            .show();
        }
      },
      error: function () {
        feedbackDiv
          .text(mobooking_booking_form_params.i18n.error_generic)
          .addClass("error")
          .show();
      },
      complete: function () {
        submitButton.prop("disabled", false).text(originalButtonText);
      },
    });
  });

  function displayStep(stepToShow) {
    const steps = $(".mobooking-bf__step");
    let targetStepDiv;

    if (stepToShow === 1) targetStepDiv = step1Div;
    else if (stepToShow === 2) targetStepDiv = step2ServicesDiv;
    else if (stepToShow === 3) targetStepDiv = step3OptionsDiv;
    else if (stepToShow === 4) targetStepDiv = step4DetailsDiv;
    else if (stepToShow === 5) targetStepDiv = step5ReviewDiv;
    else if (stepToShow === 6) targetStepDiv = step6ConfirmDiv;

    steps.hide();
    if (targetStepDiv) {
      targetStepDiv.show();
      mobooking_current_step = stepToShow;
    }
  }

  function bfRenderTemplate(templateSelector, data) {
    let template = $(templateSelector).html();
    if (!template) {
      console.warn("Template not found:", templateSelector);
      return "";
    }

    // Only handle <%= value %> replacements
    for (const key in data) {
      if (Object.prototype.hasOwnProperty.call(data, key)) {
        const value = data[key] !== null && data[key] !== undefined ? data[key] : "";
        // Basic HTML escaping for safety, as values are injected into HTML structure.
        const sanitizedValue = $("<div>").text(value).html();
        template = template.replace(new RegExp("<%=\\s*" + key + "\\s*%>", "g"), sanitizedValue);
      }
    }
    // Any other <% ... %> tags will be left as is, and should be removed from templates.
    return template;
  }

  function displayStep2_LoadServices() {
    console.log("displayStep2_LoadServices called");

    const tenantId = sessionStorage.getItem("mobooking_cart_tenant_id");
    console.log("Tenant ID from session storage:", tenantId);

    if (!tenantId) {
      const errorMessage =
        formSettings.bf_enable_location_check === "0"
          ? mobooking_booking_form_params.i18n.tenant_id_missing ||
            "Business identifier is missing. Cannot load services."
          : mobooking_booking_form_params.i18n.tenant_id_missing_refresh ||
            "Tenant ID missing. Please refresh and try again.";

      console.error("No tenant ID available for loading services");

      step2FeedbackDiv.text(errorMessage).addClass("error").show();

      // Only redirect to step 1 if location check is enabled
      if (formSettings.bf_enable_location_check !== "0") {
        displayStep(1);
      }
      return;
    }

    console.log("Loading services for tenant ID:", tenantId);

    servicesListDiv
      .html(
        "<p>" +
          (mobooking_booking_form_params.i18n.loading_services ||
            "Loading services...") +
          "</p>"
      )
      .show();

    step2FeedbackDiv.empty().hide();

    $.ajax({
      url: mobooking_booking_form_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_public_services",
        nonce: mobooking_booking_form_params.nonce,
        tenant_id: tenantId,
      },
      success: function (response) {
        console.log("Services AJAX response:", response);

        servicesListDiv.empty();
        publicServicesCache = [];

        if (
          response.success &&
          Array.isArray(response.data) &&
          response.data.length > 0
        ) {
          console.log("Found", response.data.length, "services");
          publicServicesCache = response.data;

          publicServicesCache.forEach(function (service) {
            // Basic data for the simplified template
            const templateData = {
              service_id: service.service_id,
              name: service.name,
              duration: service.duration,
              // price_formatted and description will be handled manually
            };

            let itemHtml = bfRenderTemplate("#mobooking-bf-service-item-template", templateData);

            // Conditionally insert price
            if (formSettings.bf_show_pricing === "1" && service.price_formatted) {
              const priceHtml = `<span class="mobooking-bf__service-price">- ${service.price_formatted}</span>`;
              itemHtml = itemHtml.replace("<!-- price_placeholder -->", priceHtml);
            } else {
              itemHtml = itemHtml.replace("<!-- price_placeholder -->", ""); // Remove placeholder if no price
            }

            // Conditionally insert description
            if (service.description) {
              // Sanitize description before inserting. Assuming service.description is plain text.
              const sanitizedDescription = $("<div>").text(service.description).html();
              const descriptionHtml = `<p class="mobooking-bf__service-description">${sanitizedDescription}</p>`;
              itemHtml = itemHtml.replace("<!-- description_placeholder -->", descriptionHtml);
            } else {
              itemHtml = itemHtml.replace("<!-- description_placeholder -->", ""); // Remove placeholder if no description
            }

            servicesListDiv.append(itemHtml);
          });
        } else if (
          response.success &&
          Array.isArray(response.data) &&
          response.data.length === 0
        ) {
          console.log("No services found in response");

          const noServicesMessage =
            formSettings.bf_enable_location_check === "0"
              ? "No services are currently available. Please contact us directly or try again later."
              : mobooking_booking_form_params.i18n.no_services_available ||
                "No services are currently available for this area. Please try another location or check back later.";

          servicesListDiv.html(
            `<div style="text-align: center; padding: 30px; background: #f8f9fa; border-radius: 8px; border: 1px solid #dee2e6;">
              <div style="margin-bottom: 20px;">
                <svg style="width: 48px; height: 48px; color: #6c757d;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <h3 style="color: #495057; margin: 0 0 10px 0;">No Services Available</h3>
              <p style="color: #6c757d; margin: 0; font-size: 16px; line-height: 1.5;">${noServicesMessage}</p>
              ${
                formSettings.bf_enable_location_check === "0"
                  ? `
                <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 4px; border-left: 4px solid #2196f3;">
                  <p style="margin: 0; color: #1976d2; font-size: 14px;">
                    <strong>Note:</strong> This business may not have set up their services yet, or all services may be temporarily unavailable.
                  </p>
                </div>
              `
                  : ""
              }
            </div>`
          );
        } else {
          console.error("Error in services response:", response);

          const errorMessage =
            response.data?.message ||
            mobooking_booking_form_params.i18n.error_loading_services ||
            "Error loading services. Please try again.";

          servicesListDiv.html(
            `<div style="text-align: center; padding: 30px; background: #fff5f5; border-radius: 8px; border: 1px solid #fed7d7;">
              <div style="margin-bottom: 20px;">
                <svg style="width: 48px; height: 48px; color: #e53e3e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <h3 style="color: #e53e3e; margin: 0 0 10px 0;">Error Loading Services</h3>
              <p style="color: #c53030; margin: 0; font-size: 16px;">${errorMessage}</p>
            </div>`
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX error loading services:", error, xhr);

        servicesListDiv.html(
          `<div style="text-align: center; padding: 30px; background: #fff5f5; border-radius: 8px; border: 1px solid #fed7d7;">
            <div style="margin-bottom: 20px;">
              <svg style="width: 48px; height: 48px; color: #e53e3e;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h3 style="color: #e53e3e; margin: 0 0 10px 0;">Network Error</h3>
            <p style="color: #c53030; margin: 0 0 15px 0; font-size: 16px;">
              ${
                mobooking_booking_form_params.i18n.error_loading_services ||
                "Error loading services. Please try again."
              }
            </p>
            <p style="color: #a0a0a0; font-size: 12px; margin: 0;">
              Error details: ${error} (Status: ${status})
            </p>
          </div>`
        );
      },
    });
  }

  $("#mobooking-bf-services-next-btn").on("click", function () {
    const selectedServiceData = []; // Renamed for clarity, will hold at most one item
    const checkedRadio = servicesListDiv.find('input[name="selected_service"]:checked');

    if (checkedRadio.length > 0) {
      const serviceId = parseInt(checkedRadio.data("service-id"), 10);
      const serviceData = publicServicesCache.find(
        (s) => parseInt(s.service_id, 10) === serviceId
      );
      if (serviceData) {
        selectedServiceData.push(serviceData); // Store as an array with one item for consistency
      }
    }

    if (selectedServiceData.length === 0) {
      step2FeedbackDiv
        .text(
          mobooking_booking_form_params.i18n.select_one_service ||
            "Please select a service." // Updated message slightly
        )
        .addClass("error")
        .show();
      return;
    }
    step2FeedbackDiv.empty().hide(); // Clear any previous error

    sessionStorage.setItem(
      "mobooking_cart_selected_services", // Keep same session storage key for now
      JSON.stringify(selectedServiceData)
    );
    displayStep(3);
    displayStep3_LoadOptions();
  });

  function displayStep3_LoadOptions() {
    const selectedServicesJSON = sessionStorage.getItem("mobooking_cart_selected_services");
    if (!selectedServicesJSON) {
      step3FeedbackDiv.text(mobooking_booking_form_params.i18n.no_service_selected_options || "No service selected to show options.").addClass("error").show();
      displayStep(2);
      return;
    }

    const selectedServices = JSON.parse(selectedServicesJSON);
    if (!selectedServices || selectedServices.length === 0) {
      step3FeedbackDiv.text(mobooking_booking_form_params.i18n.no_service_selected_options || "No service selected.").addClass("error").show();
      displayStep(2);
      return;
    }

    const service = selectedServices[0];
    const step3Title = $("#mobooking-bf-step-3-title");
    if (step3Title.length && service.name) {
        const baseTitle = mobooking_booking_form_params.i18n.step3_title || "Step 3: Configure Service Options";
        step3Title.text(baseTitle + " for " + service.name);
    }

    serviceOptionsDisplayDiv.empty();
    step3FeedbackDiv.empty().removeClass("error success").hide();

    if (!service.options || service.options.length === 0) {
      serviceOptionsDisplayDiv.html(`<p>${mobooking_booking_form_params.i18n.no_options_for_service || "This service has no additional options."}</p>`);
      return;
    }

    service.options.forEach((option) => {
      const templateData = {
        service_id: service.service_id,
        option_id: option.option_id,
        name: option.name,
        required_attr: option.is_required == 1 ? "required" : "",
        quantity_default_value: option.is_required == 1 ? "1" : "0", // For quantity type
        option_values_json_string: option.option_values || "[]" // For SQM
      };

      let templateId = `#mobooking-bf-option-${option.type}-template`;
      let optionHtml = bfRenderTemplate(templateId, templateData);

      // Placeholder replacements
      let descriptionHtml = "";
      if (option.description) {
        descriptionHtml = `<p class="mobooking-bf__option-description">${$("<div>").text(option.description).html()}</p>`;
      }
      optionHtml = optionHtml.replace("<!-- description_placeholder -->", descriptionHtml);

      let requiredIndicatorHtml = "";
      if (option.is_required == 1 && option.type !== 'checkbox') { // Checkbox has required_attr on input
         requiredIndicatorHtml = ` <span class="mobooking-bf__required-indicator">*</span>`;
      }
      optionHtml = optionHtml.replace("<!-- required_indicator_placeholder -->", requiredIndicatorHtml);

      let priceImpactHtml = "";
      if (formSettings.bf_show_pricing === "1" && option.price_impact_value) {
        const formattedPrice = formatCurrency(parseFloat(option.price_impact_value), currencyCode);
        const baseImpactText = mobooking_booking_form_params.i18n.base_impact || "Base Impact";
        const perItemText = mobooking_booking_form_params.i18n.per_item || "Per item";

        if (option.type === 'quantity' && option.price_impact_type === 'fixed') {
          priceImpactHtml = ` <span class="mobooking-bf__option-price-impact">(${perItemText}: ${formattedPrice})</span>`;
        } else if (['select', 'radio'].includes(option.type) && option.price_impact_type !== 'multiply_value' && option.price_impact_type !== 'fixed') {
          // This covers 'fixed' or 'percentage' base impact for select/radio before individual choice adjustments
          priceImpactHtml = ` <span class="mobooking-bf__option-price-impact">(${baseImpactText}: ${formattedPrice})</span>`;
        } else if (['text', 'number', 'checkbox', 'textarea'].includes(option.type)) {
          // For direct, non-choice based options, show the price impact directly
          priceImpactHtml = ` <span class="mobooking-bf__option-price-impact">(${formattedPrice})</span>`;
        }
        // SQM option price is handled by its own specific JS, not via this placeholder.
      }
      optionHtml = optionHtml.replace("<!-- price_impact_placeholder -->", priceImpactHtml);

      // Handle loops for select/radio
      if (option.type === "select" || option.type === "radio") {
        let choicesHtml = "";
        const parsedChoices = JSON.parse(option.option_values || "[]");

        if (option.type === "select" && option.is_required != 1) {
          choicesHtml += `<option value="">${mobooking_booking_form_params.i18n.select_optional || '-- Select (optional) --'}</option>`;
        }

        parsedChoices.forEach((choice, index) => {
          const choicePriceAdjustDisplay = choice.price_adjust ?
            `(${parseFloat(choice.price_adjust) > 0 ? '+' : ''}${formatCurrency(parseFloat(choice.price_adjust), currencyCode, false)})` : "";

          if (option.type === "select") {
            choicesHtml += `<option value="${$("<div>").text(choice.value).html()}" data-price-adjust="${parseFloat(choice.price_adjust) || 0}">` +
                           `${$("<div>").text(choice.label).html()} ${choicePriceAdjustDisplay}</option>`;
          } else { // radio
            const radioName = `service_option[${service.service_id}][${option.option_id}]`;
            const radioId = `option_${service.service_id}_${option.option_id}_${index}`;
            const checkedAttr = (option.is_required == 1 && index === 0) ? "checked" : "";
            choicesHtml += `<label class="mobooking-bf__label mobooking-bf__label--radio">` +
                           `<input type="radio" id="${radioId}" class="mobooking-bf__radio" name="${radioName}" value="${$("<div>").text(choice.value).html()}" data-price-adjust="${parseFloat(choice.price_adjust) || 0}" ${checkedAttr}>` +
                           `<span class="mobooking-bf__option-name">${$("<div>").text(choice.label).html()}</span> ${choicePriceAdjustDisplay}</label>`;
          }
        });
        optionHtml = optionHtml.replace("<!-- options_loop_placeholder -->", choicesHtml);
      }
      serviceOptionsDisplayDiv.append(optionHtml);
    });
  }

  $("#mobooking-bf-options-next-btn").on("click", function () {
    const selectedServicesJSON = sessionStorage.getItem(
      "mobooking_cart_selected_services"
    );
    if (!selectedServicesJSON) {
      step3FeedbackDiv.text("No services selected.").addClass("error").show();
      return;
    }
    const selectedServices = JSON.parse(selectedServicesJSON);
    // Since we only have one service selected now:
    const service = selectedServices[0];
    service.configured_options = []; // Initialize/clear previous configured options

    if (service.options && service.options.length > 0) {
      service.options.forEach((option) => {
        let $optionField;
        let selectedValue = null;
        const serviceId = service.service_id; // service_id from the parent service object
        const optionId = option.option_id;    // option_id from the current option definition

        // Construct the correct name attribute based on option type
        let fieldName = `service_option[${serviceId}][${optionId}]`;
        if (option.type === "quantity") {
          fieldName = `service_option[${serviceId}][${optionId}][quantity]`;
        } else if (option.type === "sqm") {
          fieldName = `service_option[${serviceId}][${optionId}][total_sqm]`;
        }
        // For radio buttons, all inputs share the same name.
        // For checkboxes, if it were multiple checkboxes for one option, they'd share a name ending in [].
        // But our current structure is one checkbox per option, or a group of radios.

        $optionField = $(`[name="${fieldName}"]`);

        if ($optionField.length > 0) {
          switch (option.type) {
            case "checkbox":
              selectedValue = $optionField.is(":checked") ? "1" : "0";
              break;
            case "radio":
              selectedValue = $optionField.filter(":checked").val() || "";
              break;
            case "select":
            case "text":
            case "number":
            case "textarea":
            case "quantity": // Input type number
            case "sqm":      // Input type number
              selectedValue = $optionField.val() || "";
              break;
            default:
              console.warn("Unknown option type for value retrieval:", option.type);
              selectedValue = ""; // Default to empty if type is not handled
          }
          service.configured_options.push({
            option_id: option.option_id,
            name: option.name, // Include name for easier debugging/review
            type: option.type, // Include type
            selected_value: selectedValue,
          });
        } else {
          console.warn(`Option field not found for service ${serviceId}, option ${optionId} with name ${fieldName}`);
        }
      });
    }
    // Update the single service in the array and save back
    selectedServices[0] = service;
    sessionStorage.setItem(
      "mobooking_cart_selected_services",
      JSON.stringify(selectedServices)
    );
    displayStep(4); // Proceed to details step
  });

  $("#mobooking-bf-details-next-btn").on("click", function () {
    let isValid = true;
    let errors = [];
    const customerDetails = {
      customer_name: $("#mobooking-bf-customer-name").val().trim(),
      customer_email: $("#mobooking-bf-customer-email").val().trim(),
      customer_phone:
        formSettings.bf_require_phone === "1"
          ? $("#mobooking-bf-customer-phone").val().trim()
          : $("#mobooking-bf-customer-phone").val().trim(),
      service_address: $("#mobooking-bf-service-address").val().trim(),
      booking_date:
        formSettings.bf_allow_date_time_selection === "1"
          ? $("#mobooking-bf-booking-date").val().trim()
          : "",
      booking_time:
        formSettings.bf_allow_date_time_selection === "1"
          ? $("#mobooking-bf-booking-time").val().trim()
          : "",
      special_instructions:
        formSettings.bf_allow_special_instructions === "1"
          ? $("#mobooking-bf-special-instructions").val().trim()
          : "",
    };

    // Name validation
    if (!customerDetails.customer_name) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.name_required);
    }

    // Email validation
    if (!customerDetails.customer_email) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.email_required);
    } else if (
      !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerDetails.customer_email)
    ) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.email_invalid);
    }

    // Phone validation (conditional)
    if (
      formSettings.bf_require_phone === "1" &&
      !customerDetails.customer_phone
    ) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.phone_required);
    }

    // Service address validation
    if (!customerDetails.service_address) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.address_required);
    }

    // Date and Time validation (conditional)
    if (formSettings.bf_allow_date_time_selection === "1") {
      if (!customerDetails.booking_date) {
        isValid = false;
        errors.push(mobooking_booking_form_params.i18n.date_required);
      }
      if (!customerDetails.booking_time) {
        isValid = false;
        errors.push(mobooking_booking_form_params.i18n.time_required);
      }
    }

    if (!isValid) {
      step4FeedbackDiv.html(errors.join("<br>")).addClass("error").show();
      return;
    }
    sessionStorage.setItem(
      "mobooking_cart_booking_details",
      JSON.stringify(customerDetails)
    );
    displayStep(5);
    displayStep5_ReviewBooking();
  });

  function calculateTotalPrice(selectedServices, discountInfo = null) {
    let subtotal = 0;
    let serviceDetailsForSummary = [];
    selectedServices.forEach((service) => {
      let currentServicePrice = parseFloat(service.price) || 0;
      let serviceOptionsSummary = [];
      if (service.configured_options && service.configured_options.length > 0) {
        service.configured_options.forEach((confOpt) => {
          const originalOption = service.options.find(
            (opt) => opt.option_id == confOpt.option_id
          );
          if (!originalOption) return;
          let meaningfulSelection = false;
          if (
            originalOption.type === "checkbox" &&
            confOpt.selected_value === "1"
          )
            meaningfulSelection = true;
          else if (
            originalOption.type === "quantity" &&
            parseInt(confOpt.selected_value, 10) > 0
          )
            meaningfulSelection = true;
          else if (
            ["text", "textarea", "radio", "select"].includes(
              originalOption.type
            ) &&
            confOpt.selected_value &&
            confOpt.selected_value.trim() !== ""
          )
            meaningfulSelection = true;
          if (!meaningfulSelection) return;
          const optionPrice = parseFloat(originalOption.price_impact) || 0;
          let finalOptionPrice = 0;
          if (originalOption.price_impact_type === "fixed") {
            finalOptionPrice = optionPrice;
          } else if (originalOption.price_impact_type === "percentage") {
            finalOptionPrice = (currentServicePrice * optionPrice) / 100;
          } else if (originalOption.price_impact_type === "multiply") {
            const quantity = parseInt(confOpt.selected_value, 10) || 1;
            finalOptionPrice = optionPrice * quantity;
          }
          currentServicePrice += finalOptionPrice;
          serviceOptionsSummary.push({
            name: originalOption.option_name,
            value: confOpt.selected_value,
            price: finalOptionPrice,
          });
        });
      }
      serviceDetailsForSummary.push({
        name: service.name,
        base_price: parseFloat(service.price) || 0,
        options: serviceOptionsSummary,
        final_service_price_raw: currentServicePrice,
        options_summary: serviceOptionsSummary,
      });
      subtotal += currentServicePrice;
    });
    let discountAmount = 0;
    let finalTotal = subtotal;
    if (discountInfo && discountInfo.valid) {
      if (discountInfo.discount_type === "percentage") {
        discountAmount =
          (subtotal * parseFloat(discountInfo.discount_value)) / 100;
      } else if (discountInfo.discount_type === "fixed") {
        discountAmount = parseFloat(discountInfo.discount_value);
      }
      finalTotal = Math.max(0, subtotal - discountAmount);
    }
    return {
      subtotal,
      discountAmount,
      finalTotal,
      serviceDetailsForSummary,
    };
  }

  function formatCurrency(amount, currency = "USD", includeSymbol = true) {
    const options = {
      minimumFractionDigits: 2, // Ensure two decimal places
      maximumFractionDigits: 2
    };
    if (includeSymbol) {
      options.style = "currency";
      options.currency = currency;
    } else {
      options.style = "decimal";
      // For decimal style, currency option is not used, but we maintain consistency in formatting.
    }
    return new Intl.NumberFormat("en-US", options).format(amount);
  }

  function displayStep5_ReviewBooking() {
    const selectedServicesJSON = sessionStorage.getItem(
      "mobooking_cart_selected_services"
    );
    const bookingDetailsJSON = sessionStorage.getItem(
      "mobooking_cart_booking_details"
    );
    if (!selectedServicesJSON || !bookingDetailsJSON) {
      step5FeedbackDiv
        .text("Missing booking information.")
        .addClass("error")
        .show();
      return;
    }
    const selectedServices = JSON.parse(selectedServicesJSON);
    const bookingDetails = JSON.parse(bookingDetailsJSON);
    const discountInfoJSON = sessionStorage.getItem(
      "mobooking_cart_discount_info"
    );
    const discountInfo = discountInfoJSON ? JSON.parse(discountInfoJSON) : null;
    const pricing = calculateTotalPrice(selectedServices, discountInfo);

    let summaryHtml = `<h3>${
      mobooking_booking_form_params.i18n.booking_summary || "Booking Summary"
    }</h3>`;
    summaryHtml += `<h4>${
      mobooking_booking_form_params.i18n.customer_details || "Customer Details"
    }</h4>`;
    summaryHtml += `<p><strong>${
      mobooking_booking_form_params.i18n.name_label || "Name"
    }:</strong> ${$("<div>").text(bookingDetails.customer_name).html()}</p>`;
    summaryHtml += `<p><strong>${
      mobooking_booking_form_params.i18n.email_label || "Email"
    }:</strong> ${$("<div>").text(bookingDetails.customer_email).html()}</p>`;
    summaryHtml += `<p><strong>${
      mobooking_booking_form_params.i18n.phone_label || "Phone"
    }:</strong> ${$("<div>").text(bookingDetails.customer_phone).html()}</p>`;
    summaryHtml += `<p><strong>${
      mobooking_booking_form_params.i18n.address_label || "Address"
    }:</strong><br>${$("<div>")
      .text(bookingDetails.service_address)
      .html()
      .replace(/\n/g, "<br>")}</p>`;
    summaryHtml += `<p><strong>${
      mobooking_booking_form_params.i18n.datetime_label || "Date & Time"
    }:</strong> ${$("<div>").text(bookingDetails.booking_date).html()} at ${$(
      "<div>"
    )
      .text(bookingDetails.booking_time)
      .html()}</p>`;
    if (bookingDetails.special_instructions)
      summaryHtml += `<p><strong>${
        mobooking_booking_form_params.i18n.instructions_label || "Instructions"
      }:</strong><br>${$("<div>")
        .text(bookingDetails.special_instructions)
        .html()
        .replace(/\n/g, "<br>")}</p>`;

    summaryHtml += `<hr><h4>${
      mobooking_booking_form_params.i18n.services_summary || "Services Summary"
    }</h4>`;
    pricing.serviceDetailsForSummary.forEach((item) => {
      summaryHtml += `<div style="margin-bottom:10px;"><strong>${$("<div>")
        .text(item.name)
        .html()}</strong> - ${formatCurrency(
        item.final_service_price_raw,
        currencyCode
      )}`;
      if (item.options_summary && item.options_summary.length > 0) {
        summaryHtml += `<ul style="margin:5px 0 0 20px;">`;
        item.options_summary.forEach((opt) => {
          summaryHtml += `<li>${$("<div>").text(opt.name).html()}: ${$("<div>")
            .text(opt.value)
            .html()} (+${formatCurrency(opt.price, currencyCode)})</li>`;
        });
        summaryHtml += `</ul>`;
      }
      summaryHtml += `</div>`;
    });

    if (formSettings.bf_show_pricing === "1") {
      summaryHtml += `<hr><h4>${
        mobooking_booking_form_params.i18n.pricing_summary || "Pricing Summary"
      }</h4>`;
      summaryHtml += `<p><strong>${
        mobooking_booking_form_params.i18n.subtotal_label || "Subtotal"
      }:</strong> <span id="mobooking-bf-subtotal">${formatCurrency(
        pricing.subtotal,
        currencyCode
      )}</span></p>`;
      if (pricing.discountAmount > 0) {
        summaryHtml += `<p><strong>${
          mobooking_booking_form_params.i18n.discount_label || "Discount"
        }:</strong> <span id="mobooking-bf-discount-applied">-${formatCurrency(
          pricing.discountAmount,
          currencyCode
        )}</span></p>`;
      }
      summaryHtml += `<p><strong>${
        mobooking_booking_form_params.i18n.total_label || "Total"
      }:</strong> <span id="mobooking-bf-final-total">${formatCurrency(
        pricing.finalTotal,
        currencyCode
      )}</span></p>`;
    }

    reviewSummaryDiv.html(summaryHtml);
    step5FeedbackDiv.empty().hide();
  }

  applyDiscountBtn.on("click", function () {
    const code = discountCodeInput.val().trim(),
      tenantId = sessionStorage.getItem("mobooking_cart_tenant_id");
    if (!code) {
      discountFeedbackDiv
        .text(mobooking_booking_form_params.i18n.enter_discount_code)
        .addClass("error")
        .show();
      return;
    }
    if (!tenantId) return;
    const $button = $(this);
    $button.prop("disabled", true);
    discountFeedbackDiv.empty().removeClass("success error").hide();
    $.ajax({
      url: mobooking_booking_form_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_validate_discount_public",
        nonce: mobooking_booking_form_params.nonce,
        discount_code: code,
        tenant_id: tenantId,
      },
      success: function (response) {
        if (response.success && response.data.valid)
          sessionStorage.setItem(
            "mobooking_cart_discount_info",
            JSON.stringify(response.data)
          );
        else {
          sessionStorage.removeItem("mobooking_cart_discount_info");
          discountFeedbackDiv
            .text(
              response.data.message ||
                mobooking_booking_form_params.i18n.invalid_discount_code
            )
            .addClass("error")
            .show();
        }
      },
      error: function () {
        sessionStorage.removeItem("mobooking_cart_discount_info");
        discountFeedbackDiv
          .text(mobooking_booking_form_params.i18n.error_applying_discount)
          .addClass("error")
          .show();
      },
      complete: function (xhr) {
        const response = xhr.responseJSON;
        if (response && response.success && response.data.valid) {
          discountFeedbackDiv
            .text(
              mobooking_booking_form_params.i18n.discount_applied ||
                "Discount applied!"
            )
            .addClass("success")
            .show();
          displayStep5_ReviewBooking();
        }
        $button.prop("disabled", false);
        setTimeout(function () {
          discountFeedbackDiv.fadeOut().empty().removeClass("success error");
        }, 3000);
      },
    });
  });

  $("#mobooking-bf-review-confirm-btn").on("click", function () {
    const selectedServicesJSON = sessionStorage.getItem(
      "mobooking_cart_selected_services"
    );
    const bookingDetailsJSON = sessionStorage.getItem(
      "mobooking_cart_booking_details"
    );
    const discountInfoJSON = sessionStorage.getItem(
      "mobooking_cart_discount_info"
    );
    const tenantId = sessionStorage.getItem("mobooking_cart_tenant_id");
    const zipCode = sessionStorage.getItem("mobooking_cart_zip");
    const countryCode = sessionStorage.getItem("mobooking_cart_country");
    if (!selectedServicesJSON || !bookingDetailsJSON || !tenantId) {
      step5FeedbackDiv
        .text(
          mobooking_booking_form_params.i18n.booking_error ||
            "Missing required booking information."
        )
        .addClass("error")
        .show();
      return;
    }
    const $button = $(this);
    const originalButtonText = $button.text();
    $button
      .prop("disabled", true)
      .text(mobooking_booking_form_params.i18n.submitting || "Submitting...");
    step5FeedbackDiv.empty().hide();
    $.ajax({
      url: mobooking_booking_form_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_create_booking",
        nonce: mobooking_booking_form_params.nonce,
        tenant_id: tenantId,
        zip_code: zipCode || "",
        country_code: countryCode || "",
        selected_services: selectedServicesJSON,
        booking_details: bookingDetailsJSON,
        discount_info: discountInfoJSON || "",
      },
      success: function (response) {
        if (response.success) {
          sessionStorage.removeItem("mobooking_cart_selected_services");
          sessionStorage.removeItem("mobooking_cart_booking_details");
          sessionStorage.removeItem("mobooking_cart_discount_info");
          sessionStorage.removeItem("mobooking_cart_zip");
          sessionStorage.removeItem("mobooking_cart_country");
          sessionStorage.removeItem("mobooking_cart_tenant_id");
          confirmationMessageDiv.html(
            response.data.message ||
              mobooking_booking_form_params.i18n.booking_submitted ||
              "Your booking has been submitted successfully!"
          );
          displayStep(6);
        } else {
          step5FeedbackDiv
            .text(
              response.data.message ||
                mobooking_booking_form_params.i18n.booking_error ||
                "There was an error submitting your booking."
            )
            .addClass("error")
            .show();
        }
      },
      error: function () {
        step5FeedbackDiv
          .text(
            mobooking_booking_form_params.i18n.error_ajax ||
              "A network error occurred. Please try again."
          )
          .addClass("error")
          .show();
      },
      complete: function () {
        $button.prop("disabled", false).text(originalButtonText);
      },
    });
  });

  // Back buttons
  $("#mobooking-bf-services-back-btn").on("click", function () {
    displayStep(1);
  });

  $("#mobooking-bf-options-back-btn").on("click", function () {
    displayStep(2);
  });

  $("#mobooking-bf-details-back-btn").on("click", function () {
    displayStep(3);
  });

  $("#mobooking-bf-review-back-btn").on("click", function () {
    displayStep(4);
  });

  // Initialize date picker if available
  if (typeof $.fn.datepicker === "function") {
    $("#mobooking-bf-booking-date").datepicker({
      dateFormat: "yy-mm-dd",
      minDate: 0,
    });
  } else {
    $("#mobooking-bf-booking-date").attr("type", "date");
  }
});
