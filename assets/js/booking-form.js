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

  // Sidebar elements
  const sidebarSummaryDiv = $("#mobooking-bf-sidebar-summary");
  const sidebarContentDiv = $("#mobooking-bf-sidebar-content");
  const sidebarSubtotal = $("#mobooking-bf-sidebar-subtotal");
  const sidebarDiscountItem = $("#mobooking-bf-sidebar-discount-item");
  const sidebarDiscountApplied = $("#mobooking-bf-sidebar-discount-applied");
  const sidebarFinalTotal = $("#mobooking-bf-sidebar-final-total");

  let mobooking_current_step = 1; // Keep track of the current visible step
  let publicServicesCache = []; // Cache for service data from Step 2
  let currentSelectionForSummary = {
    // To hold data for sidebar summary
    service: null,
    options: {}, // Store as { optionId: { name: 'Opt Name', value: 'Val', price: X } }
    customerDetails: {},
    discountInfo: null,
    totals: {
      subtotal: 0,
      discountAmount: 0,
      finalTotal: 0,
    },
  };

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

  // Check if location checking is disabled and handle tenant ID setup
  if (formSettings.bf_enable_location_check === "0") {
    console.log("Location check is disabled");

    // Try multiple methods to get tenant ID when location check is disabled
    let tenantIdForServices = "";

    // Method 1: From localized params (highest priority)
    if (
      typeof mobooking_booking_form_params !== "undefined" &&
      mobooking_booking_form_params.tenant_id &&
      mobooking_booking_form_params.tenant_id !== "0"
    ) {
      tenantIdForServices = String(mobooking_booking_form_params.tenant_id);
      console.log("Method 1: Got tenant ID from params:", tenantIdForServices);
    }

    // Method 2: From session storage
    if (!tenantIdForServices) {
      const sessionTenantId = sessionStorage.getItem(
        "mobooking_cart_tenant_id"
      );
      if (sessionTenantId && sessionTenantId !== "0") {
        tenantIdForServices = sessionTenantId;
        console.log(
          "Method 2: Got tenant ID from session:",
          tenantIdForServices
        );
      }
    }

    // Method 3: From hidden field
    if (!tenantIdForServices) {
      const fieldTenantId = tenantIdField.val();
      if (fieldTenantId && fieldTenantId !== "0") {
        tenantIdForServices = fieldTenantId;
        console.log("Method 3: Got tenant ID from field:", tenantIdForServices);
      }
    }

    // Method 4: From URL parameter
    if (!tenantIdForServices) {
      const urlParams = new URLSearchParams(window.location.search);
      const urlTenantId = urlParams.get("tid");
      if (urlTenantId && urlTenantId !== "0") {
        tenantIdForServices = urlTenantId;
        console.log("Method 4: Got tenant ID from URL:", tenantIdForServices);
      }
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
      return; // Exit if no tenant ID found and location check is disabled
    }
  } else {
    // Location check is enabled, show step 1 normally
    console.log("Location check is enabled, showing Step 1");
    displayStep(1);
  }

  // --- Location Form (Step 1) ---
  locationForm.on("submit", function (e) {
    e.preventDefault();
    const zipCode = $("#mobooking-bf-zip").val().trim();
    const countryCode = $("#mobooking-bf-country").val().trim();
    const tenantId = tenantIdField.val();

    feedbackDiv.empty().hide();

    if (!zipCode) {
      feedbackDiv
        .text(mobooking_booking_form_params.i18n.zip_required)
        .addClass("error")
        .show();
      return;
    }
    if (!countryCode) {
      feedbackDiv
        .text(mobooking_booking_form_params.i18n.country_code_required)
        .addClass("error")
        .show();
      return;
    }
    if (!tenantId) {
      feedbackDiv
        .text(mobooking_booking_form_params.i18n.tenant_id_missing)
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
        action: "mobooking_check_service_area",
        nonce: mobooking_booking_form_params.nonce,
        zip_code: zipCode,
        country_code: countryCode,
        tenant_id: tenantId,
      },
      success: function (response) {
        if (response.success) {
          feedbackDiv
            .text(response.data.message || "Area is serviced.")
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

    // Animate out current step if it's different from target
    const currentActiveStep = $(
      ".mobooking-bf__step:not(.mobooking-bf__hidden):not(.fade-in)"
    );
    if (
      currentActiveStep.length &&
      currentActiveStep.attr("id") !== targetStepDiv?.attr("id")
    ) {
      currentActiveStep.removeClass("fade-in").addClass("mobooking-bf__hidden");
    }

    // Hide all steps initially then show the target one with animation
    steps.addClass("mobooking-bf__hidden").removeClass("fade-in");

    if (targetStepDiv) {
      // Use a short timeout to allow the 'display: none' from mobooking-bf__hidden to apply first,
      // then remove it and trigger the animation by adding 'fade-in'.
      setTimeout(function () {
        targetStepDiv
          .removeClass("mobooking-bf__hidden")
          .show()
          .addClass("fade-in");
      }, 50); // Small delay, adjust if needed
      mobooking_current_step = stepToShow;
    }

    // Manage sidebar visibility
    if (stepToShow === 3 || stepToShow === 5) {
      sidebarSummaryDiv.removeClass("mobooking-bf__hidden");
      renderOrUpdateSidebarSummary(); // Update content when shown
    } else {
      sidebarSummaryDiv.addClass("mobooking-bf__hidden");
    }
  }

  function bfRenderTemplate(templateSelector, data) {
    let template = $(templateSelector).html();
    if (!template) {
      console.warn("Template not found:", templateSelector);
      return "";
    }

    // Robust template settings, similar to Underscore.js
    const settings = {
      evaluate: /<%([\s\S]+?)%>/g,
      interpolate: /<%=([\s\S]+?)%>/g,
      escape: /<%-([\s\S]+?)%>/g,
    };

    // Create a function string, using 'obj' as the data object name.
    let js = "let p=[],print=function(){p.push.apply(p,arguments);};";
    js += "with(obj||{}){p.push('";

    template = template
      .replace(/[\r\t\n]/g, " ")
      .split("<%")
      .join("\t")
      .replace(
        settings.escape,
        "');p.push(typeof $1==='undefined'?'':$('<div>').text($1).html());p.push('"
      )
      .replace(
        settings.interpolate,
        // Properly quote and escape strings for direct JavaScript injection
        "');p.push(typeof $1==='undefined'?'':(typeof $1 === 'string' ? JSON.stringify($1) : $1));p.push('"
      )
      .replace(
        settings.evaluate,
        "');try{$1}catch(e){console.error('Error in template execution:',e,' offending code:', String.raw`$1`);}p.push('" // Use String.raw for logging
      )
      .replace(/\t=(.+?)%>/g, "');p.push($1);p.push('")
      .split("\t")
      .join("');")
      .split("%>")
      .join("p.push('")
      .replace(/(\s|&nbsp;)+/g, " "); // Minimize multiple spaces

    js += "');}return p.join('');";

    try {
      const compiled = new Function("obj", js);
      return compiled(data);
    } catch (e) {
      console.error("Error compiling template:", e, "Generated JS:", js);
      return `<p style="color:red;">Error rendering template: ${templateSelector}. Check console.</p>`;
    }
  }

  function displayStep2_LoadServices() {
    console.log("displayStep2_LoadServices called");

    let tenantId =
      sessionStorage.getItem("mobooking_cart_tenant_id") || tenantIdField.val();

    // Enhanced tenant ID validation with detailed logging
    if (!tenantId || tenantId === "0" || tenantId === "") {
      console.error(
        "No tenant ID available for loading services. Current values:"
      );
      console.error(
        "- sessionStorage tenant_id:",
        sessionStorage.getItem("mobooking_cart_tenant_id")
      );
      console.error("- tenantIdField value:", tenantIdField.val());
      console.error(
        "- mobooking_booking_form_params.tenant_id:",
        mobooking_booking_form_params?.tenant_id
      );

      const errorMessage =
        formSettings.bf_enable_location_check !== "0"
          ? mobooking_booking_form_params.i18n.tenant_id_missing ||
            "Business identifier is missing. Cannot load services."
          : mobooking_booking_form_params.i18n.tenant_id_missing_refresh ||
            "Tenant ID missing. Please refresh and try again.";

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
              duration: service.duration || 60,
              price: service.price || 0,
              description: service.description || "",
              category: service.category || "",
            };

            // Get template - try multiple possible template IDs for compatibility
            let template =
              $("#mobooking-bf-service-item-template").html() ||
              $("#mobooking-bf-service-card-template").html();

            if (!template) {
              console.error(
                "No service template found. Available templates:",
                $("#mobooking-bf-service-item-template").length,
                $("#mobooking-bf-service-card-template").length
              );
              servicesListDiv.html(
                "<p>Error: Service template missing. Please contact support.</p>"
              );
              return;
            }

            // FIXED: Proper template replacement handling conditional logic
            let serviceHtml = template;

            // Replace basic template variables first
            Object.keys(templateData).forEach(function (key) {
              const regex = new RegExp("<%= " + key + " %>", "g");
              serviceHtml = serviceHtml.replace(regex, templateData[key]);
            });

            // Handle conditional blocks for price
            if (templateData.price && templateData.price > 0) {
              // Replace the conditional price block
              serviceHtml = serviceHtml.replace(
                /<% if \(typeof price !== 'undefined' && price > 0\) \{ %>([\s\S]*?)<% \} %>/g,
                "$1"
              );
              // Replace the price placeholder
              serviceHtml = serviceHtml.replace(
                /\$<%= price %>/g,
                "$" + templateData.price
              );
            } else {
              // Remove the entire conditional price block
              serviceHtml = serviceHtml.replace(
                /<% if \(typeof price !== 'undefined' && price > 0\) \{ %>[\s\S]*?<% \} %>/g,
                ""
              );
            }

            // Handle conditional blocks for description
            if (
              templateData.description &&
              templateData.description.trim() !== ""
            ) {
              serviceHtml = serviceHtml.replace(
                /<% if \(typeof description !== 'undefined' && description\) \{ %>([\s\S]*?)<% \} %>/g,
                "$1"
              );
            } else {
              serviceHtml = serviceHtml.replace(
                /<% if \(typeof description !== 'undefined' && description\) \{ %>[\s\S]*?<% \} %>/g,
                ""
              );
            }

            // Handle conditional blocks for category
            if (templateData.category && templateData.category.trim() !== "") {
              serviceHtml = serviceHtml.replace(
                /<% if \(typeof category !== 'undefined' && category\) \{ %>([\s\S]*?)<% \} %>/g,
                "$1"
              );
            } else {
              serviceHtml = serviceHtml.replace(
                /<% if \(typeof category !== 'undefined' && category\) \{ %>[\s\S]*?<% \} %>/g,
                ""
              );
            }

            // Clean up any remaining template syntax
            serviceHtml = serviceHtml.replace(/<%= \w+ %>/g, "");
            serviceHtml = serviceHtml.replace(/<% [^%]*? %>/g, "");

            // Handle icon placeholder
            let iconHtml = "";
            if (service.icon && service.icon.startsWith("dashicons-")) {
              iconHtml = `<span class="dashicons ${service.icon}"></span>`;
            } else if (service.icon && service.icon.startsWith("fa-")) {
              iconHtml = `<i class="fas ${service.icon}"></i>`;
            } else {
              iconHtml = `<i class="fas fa-broom"></i>`; // Default cleaning icon
            }
            serviceHtml = serviceHtml.replace(
              /<!-- icon_placeholder -->/g,
              iconHtml
            );

            // Handle image placeholder
            let imageHtml = "";
            if (service.image_url) {
              imageHtml = `<div class="mobooking-bf__service-image">
                            <img src="${service.image_url}" alt="${service.name}" loading="lazy">
                          </div>`;
            }
            serviceHtml = serviceHtml.replace(
              /<!-- image_placeholder -->/g,
              imageHtml
            );

            // Create jQuery object and ensure proper data attributes
            const $serviceElement = $(serviceHtml);
            $serviceElement.attr("data-service-id", service.service_id);

            // Ensure the radio input has the correct value
            $serviceElement
              .find('input[name="selected_service"]')
              .val(service.service_id);

            // Add to services list
            servicesListDiv.append($serviceElement);
          });

          // Update step 2 next button state
          updateStep2NextButtonState();
        } else {
          console.log("No services found in response");

          servicesListDiv.html(
            `<div class="mobooking-bf__no-services">
              <div class="mobooking-bf__no-services-icon">
                <i class="fas fa-search"></i>
              </div>
              <h3>${
                mobooking_booking_form_params.i18n.no_services_available ||
                "No services available"
              }</h3>
              <p>Please try again later or contact us directly.</p>
            </div>`
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Services AJAX error:", status, error);
        console.error("Response:", xhr.responseText);

        step2FeedbackDiv
          .text(
            mobooking_booking_form_params.i18n.error_loading_services ||
              "Could not load services. Please try again."
          )
          .addClass("error")
          .show();

        servicesListDiv.html(
          `<div class="mobooking-bf__error-state">
            <div class="mobooking-bf__error-icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Unable to Load Services</h3>
            <p>There was a problem connecting to our servers. Please try refreshing the page.</p>
            <button type="button" onclick="location.reload()" class="mobooking-bf__button mobooking-bf__button--outline">
              <i class="fas fa-refresh"></i> Refresh Page
            </button>
          </div>`
        );
      },
    });
  }

  // Function to update Step 2 next button state
  function updateStep2NextButtonState() {
    const servicesNextBtn = $("#mobooking-bf-services-next-btn");
    const checkedService = servicesListDiv.find(
      'input[name="selected_service"]:checked'
    );

    if (checkedService.length > 0) {
      servicesNextBtn.prop("disabled", false).css("opacity", "1");
    } else {
      servicesNextBtn.prop("disabled", true).css("opacity", "0.5");
    }
  }

  // Add event handler for service selection to update button state
  $(document).on("change", 'input[name="selected_service"]', function () {
    updateStep2NextButtonState();

    // Store selected service data
    const serviceId = parseInt($(this).val(), 10);
    const serviceData = publicServicesCache.find(
      (s) => parseInt(s.service_id, 10) === serviceId
    );

    if (serviceData) {
      currentSelectionForSummary.service = serviceData;
      currentSelectionForSummary.options = {}; // Reset options when service changes

      // Store in sessionStorage for consistency
      sessionStorage.setItem(
        "mobooking_selected_service",
        JSON.stringify(serviceData)
      );

      console.log("Service selected:", serviceData.name);
    }
  });

  $("#mobooking-bf-services-next-btn").on("click", function () {
    const checkedRadio = servicesListDiv.find(
      'input[name="selected_service"]:checked'
    );
    let selectedServiceForCart = [];

    if (checkedRadio.length > 0) {
      const serviceId = parseInt(checkedRadio.val(), 10);
      const serviceData = publicServicesCache.find(
        (s) => parseInt(s.service_id, 10) === serviceId
      );
      if (serviceData) {
        selectedServiceForCart.push(serviceData);
        currentSelectionForSummary.service = serviceData;
        currentSelectionForSummary.options = {};
      }
    }

    if (selectedServiceForCart.length === 0) {
      step2FeedbackDiv
        .text(
          mobooking_booking_form_params.i18n.select_one_service ||
            "Please select a service."
        )
        .addClass("error")
        .show();
      return;
    }

    step2FeedbackDiv.empty().hide();

    sessionStorage.setItem(
      "mobooking_cart_selected_services",
      JSON.stringify(selectedServiceForCart)
    );

    displayStep(3);
    displayStep3_LoadOptions();
  });

  function displayStep3_LoadOptions() {
    // Retrieve the single selected service from our currentSelectionForSummary or session storage
    const service = currentSelectionForSummary.service;

    if (!service) {
      const selectedServicesJSON = sessionStorage.getItem(
        "mobooking_cart_selected_services"
      );
      if (!selectedServicesJSON) {
        step3FeedbackDiv
          .text(
            mobooking_booking_form_params.i18n.no_service_selected_options ||
              "No service selected to show options."
          )
          .addClass("error")
          .show();
        displayStep(2);
        return;
      }
      const tempSelectedServices = JSON.parse(selectedServicesJSON);
      if (!tempSelectedServices || tempSelectedServices.length === 0) {
        step3FeedbackDiv
          .text(
            mobooking_booking_form_params.i18n.no_service_selected_options ||
              "No service selected."
          )
          .addClass("error")
          .show();
        displayStep(2);
        return;
      }
      currentSelectionForSummary.service = tempSelectedServices[0]; // Populate if navigating directly to step 3
    }
    // Re-assign service in case it was populated from session storage
    const currentService = currentSelectionForSummary.service;

    const step3Title = $("#mobooking-bf-step-3-title");
    if (step3Title.length && currentService.name) {
      const baseTitle =
        mobooking_booking_form_params.i18n.step3_title ||
        "Step 3: Configure Service Options";
      step3Title.text(baseTitle + " for " + currentService.name);
    }

    serviceOptionsDisplayDiv.empty();
    step3FeedbackDiv.empty().removeClass("error success").hide();

    if (!currentService.options || currentService.options.length === 0) {
      serviceOptionsDisplayDiv.html(
        `<p>${
          mobooking_booking_form_params.i18n.no_options_for_service ||
          "This service has no additional options."
        }</p>`
      );
      // Update summary even if no options
      currentSelectionForSummary.options = {};
      renderOrUpdateSidebarSummary();
      return;
    }

    currentService.options.forEach((option) => {
      const templateData = {
        service_id: currentService.service_id,
        option_id: option.option_id,
        name: option.name,
        required_attr: option.is_required == 1 ? "required" : "",
        quantity_default_value:
          option.is_required == 1 &&
          (option.type === "quantity" || option.type === "number")
            ? 1
            : 0,
        description: option.description || "",
        type: option.type,
        price_impact: option.price_impact || 0,
      };

      let optionHtml = "";
      if (option.type === "checkbox") {
        optionHtml = `
          <div class="mobooking-bf__option-group" data-option-id="${
            option.option_id
          }">
            <label class="mobooking-bf__option-label">
              <input type="checkbox" name="option_${
                option.option_id
              }" value="1" ${templateData.required_attr}>
              <span class="mobooking-bf__option-name">${
                templateData.name
              }</span>
              ${
                templateData.price_impact > 0
                  ? `<span class="mobooking-bf__option-price">+${templateData.price_impact}</span>`
                  : ""
              }
            </label>
            ${
              templateData.description
                ? `<p class="mobooking-bf__option-description">${templateData.description}</p>`
                : ""
            }
          </div>
        `;
      } else if (option.type === "text") {
        optionHtml = `
          <div class="mobooking-bf__option-group" data-option-id="${
            option.option_id
          }">
            <label class="mobooking-bf__option-label">${
              templateData.name
            }</label>
            <input type="text" name="option_${
              option.option_id
            }" class="mobooking-bf__option-input" ${templateData.required_attr}>
            ${
              templateData.description
                ? `<p class="mobooking-bf__option-description">${templateData.description}</p>`
                : ""
            }
          </div>
        `;
      } else if (option.type === "number" || option.type === "quantity") {
        optionHtml = `
          <div class="mobooking-bf__option-group" data-option-id="${
            option.option_id
          }">
            <label class="mobooking-bf__option-label">
              ${templateData.name}
              ${
                templateData.price_impact > 0
                  ? `<span class="mobooking-bf__option-price">${templateData.price_impact} each</span>`
                  : ""
              }
            </label>
            <div class="mobooking-bf__quantity-control">
              <button type="button" class="mobooking-bf__quantity-btn" data-action="decrease">-</button>
              <input type="number" name="option_${option.option_id}" value="${
          templateData.quantity_default_value
        }" min="0" class="mobooking-bf__quantity-input" ${
          templateData.required_attr
        }>
              <button type="button" class="mobooking-bf__quantity-btn" data-action="increase">+</button>
            </div>
            ${
              templateData.description
                ? `<p class="mobooking-bf__option-description">${templateData.description}</p>`
                : ""
            }
          </div>
        `;
      } else if (option.type === "select") {
        let selectOptions = "";
        if (option.choices && Array.isArray(option.choices)) {
          option.choices.forEach((choice) => {
            selectOptions += `<option value="${choice.value}" data-price="${
              choice.price || 0
            }">${choice.label}</option>`;
          });
        }
        optionHtml = `
          <div class="mobooking-bf__option-group" data-option-id="${
            option.option_id
          }">
            <label class="mobooking-bf__option-label">${
              templateData.name
            }</label>
            <select name="option_${
              option.option_id
            }" class="mobooking-bf__option-select" ${
          templateData.required_attr
        }>
              <option value="">Choose an option...</option>
              ${selectOptions}
            </select>
            ${
              templateData.description
                ? `<p class="mobooking-bf__option-description">${templateData.description}</p>`
                : ""
            }
          </div>
        `;
      } else if (option.type === "textarea") {
        optionHtml = `
          <div class="mobooking-bf__option-group" data-option-id="${
            option.option_id
          }">
            <label class="mobooking-bf__option-label">${
              templateData.name
            }</label>
            <textarea name="option_${
              option.option_id
            }" class="mobooking-bf__option-textarea" rows="3" ${
          templateData.required_attr
        }></textarea>
            ${
              templateData.description
                ? `<p class="mobooking-bf__option-description">${templateData.description}</p>`
                : ""
            }
          </div>
        `;
      }

      serviceOptionsDisplayDiv.append(optionHtml);
    });

    // Update summary after options are loaded
    renderOrUpdateSidebarSummary();
  }

  // Handle option changes for pricing updates
  $(document).on(
    "change input",
    ".mobooking-bf__option-group input, .mobooking-bf__option-group select, .mobooking-bf__option-group textarea",
    function () {
      updateOptionsInSummary();
      renderOrUpdateSidebarSummary();
    }
  );

  // Handle quantity buttons
  $(document).on("click", ".mobooking-bf__quantity-btn", function () {
    const button = $(this);
    const group = button.closest(".mobooking-bf__option-group");
    const input = group.find(".mobooking-bf__quantity-input");
    const action = button.data("action");
    let currentValue = parseInt(input.val()) || 0;

    if (action === "increase") {
      currentValue += 1;
    } else if (action === "decrease" && currentValue > 0) {
      currentValue -= 1;
    }

    input.val(currentValue).trigger("change");
  });

  function updateOptionsInSummary() {
    currentSelectionForSummary.options = {};

    $(".mobooking-bf__option-group").each(function () {
      const group = $(this);
      const optionId = group.data("option-id");
      const input = group.find("input, select, textarea");

      if (input.length) {
        let value = "";
        let price = 0;
        const optionName = group
          .find(".mobooking-bf__option-label")
          .text()
          .trim();

        if (input.is(":checkbox")) {
          if (input.is(":checked")) {
            value = "Yes";
            price =
              parseFloat(
                group
                  .find(".mobooking-bf__option-price")
                  .text()
                  .replace(/[^0-9.]/g, "")
              ) || 0;
          }
        } else if (input.is("select")) {
          const selectedOption = input.find("option:selected");
          value = selectedOption.text();
          price = parseFloat(selectedOption.data("price")) || 0;
        } else {
          value = input.val();
          if (input.is('[type="number"]')) {
            const quantity = parseInt(value) || 0;
            if (quantity > 0) {
              const unitPrice =
                parseFloat(
                  group
                    .find(".mobooking-bf__option-price")
                    .text()
                    .replace(/[^0-9.]/g, "")
                ) || 0;
              price = unitPrice * quantity;
              value = quantity.toString();
            }
          }
        }

        if (value && value !== "" && value !== "0") {
          currentSelectionForSummary.options[optionId] = {
            name: optionName,
            value: value,
            price: price,
          };
        }
      }
    });
  }

  $("#mobooking-bf-options-next-btn").on("click", function () {
    // Validate required options
    let allValid = true;
    $(".mobooking-bf__option-group").each(function () {
      const group = $(this);
      const input = group.find(
        "input[required], select[required], textarea[required]"
      );

      if (input.length) {
        if (input.is(":checkbox") && !input.is(":checked")) {
          allValid = false;
          group.addClass("error");
        } else if (input.is("select") && !input.val()) {
          allValid = false;
          group.addClass("error");
        } else if (!input.is(":checkbox") && !input.val()) {
          allValid = false;
          group.addClass("error");
        } else {
          group.removeClass("error");
        }
      }
    });

    if (!allValid) {
      step3FeedbackDiv
        .text("Please complete all required options.")
        .addClass("error")
        .show();
      return;
    }

    step3FeedbackDiv.empty().hide();

    // Update options in summary
    updateOptionsInSummary();

    displayStep(4);
  });

  $("#mobooking-bf-details-next-btn").on("click", function () {
    // Validate customer details
    const customerName = $("#mobooking-bf-customer-name").val().trim();
    const customerEmail = $("#mobooking-bf-customer-email").val().trim();
    const customerPhone = $("#mobooking-bf-customer-phone").val().trim();
    const serviceAddress = $("#mobooking-bf-service-address").val().trim();
    const bookingDate = $("#mobooking-bf-booking-date").val();
    const bookingTime = $("#mobooking-bf-booking-time").val();

    step4FeedbackDiv.empty().hide();

    if (!customerName) {
      step4FeedbackDiv
        .text(mobooking_booking_form_params.i18n.name_required)
        .addClass("error")
        .show();
      return;
    }
    if (!customerEmail) {
      step4FeedbackDiv
        .text(mobooking_booking_form_params.i18n.email_required)
        .addClass("error")
        .show();
      return;
    }
    if (!customerPhone) {
      step4FeedbackDiv
        .text(mobooking_booking_form_params.i18n.phone_required)
        .addClass("error")
        .show();
      return;
    }
    if (!serviceAddress) {
      step4FeedbackDiv
        .text(mobooking_booking_form_params.i18n.address_required)
        .addClass("error")
        .show();
      return;
    }
    if (!bookingDate) {
      step4FeedbackDiv
        .text(mobooking_booking_form_params.i18n.date_required)
        .addClass("error")
        .show();
      return;
    }
    if (!bookingTime) {
      step4FeedbackDiv
        .text(mobooking_booking_form_params.i18n.time_required)
        .addClass("error")
        .show();
      return;
    }

    // Store customer details
    const customerDetails = {
      customer_name: customerName,
      customer_email: customerEmail,
      customer_phone: customerPhone,
      service_address: serviceAddress,
      booking_date: bookingDate,
      booking_time: bookingTime,
      special_instructions: $("#mobooking-bf-special-instructions")
        .val()
        .trim(),
    };

    currentSelectionForSummary.customerDetails = customerDetails;
    sessionStorage.setItem(
      "mobooking_cart_booking_details",
      JSON.stringify(customerDetails)
    );

    displayStep(5);
    displayStep5_PrepareReview();
  });

  function displayStep5_PrepareReview() {
    const selectedServicesJSON = sessionStorage.getItem(
      "mobooking_cart_selected_services"
    );
    const bookingDetailsJSON = sessionStorage.getItem(
      "mobooking_cart_booking_details"
    );

    if (!selectedServicesJSON || !bookingDetailsJSON) {
      step5FeedbackDiv
        .text("Missing booking information for review.")
        .addClass("error")
        .show();
      return;
    }

    const selectedServices = JSON.parse(selectedServicesJSON);
    const bookingDetails = JSON.parse(bookingDetailsJSON);
    const discountInfo = currentSelectionForSummary.discountInfo;

    // Calculate pricing
    const pricingForReviewPanel = calculateTotalPrice(
      selectedServices,
      discountInfo
    );

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

    if (bookingDetails.booking_date && bookingDetails.booking_time) {
      summaryHtml += `<p><strong>${
        mobooking_booking_form_params.i18n.date_time_label || "Date & Time"
      }:</strong> ${bookingDetails.booking_date} at ${
        bookingDetails.booking_time
      }</p>`;
    }

    if (bookingDetails.special_instructions) {
      summaryHtml += `<p><strong>${
        mobooking_booking_form_params.i18n.instructions_label ||
        "Special Instructions"
      }:</strong><br>${$("<div>")
        .text(bookingDetails.special_instructions)
        .html()
        .replace(/\n/g, "<br>")}</p>`;
    }

    summaryHtml += `<h4>${
      mobooking_booking_form_params.i18n.service_details || "Service Details"
    }</h4>`;

    selectedServices.forEach((service) => {
      summaryHtml += `<div class="mobooking-bf__review-service">`;
      summaryHtml += `<h5>${$("<div>").text(service.name).html()}</h5>`;
      summaryHtml += `<p>${
        mobooking_booking_form_params.i18n.duration_label || "Duration"
      }: ${service.duration} minutes</p>`;
      summaryHtml += `<p>${
        mobooking_booking_form_params.i18n.price_label || "Price"
      }: ${service.price}</p>`;

      // Show selected options
      Object.values(currentSelectionForSummary.options).forEach((option) => {
        summaryHtml += `<p><strong>${option.name}:</strong> ${option.value}`;
        if (option.price > 0) {
          summaryHtml += ` (+${option.price})`;
        }
        summaryHtml += `</p>`;
      });

      summaryHtml += `</div>`;
    });

    // Pricing summary
    summaryHtml += `<div class="mobooking-bf__pricing-summary">`;
    summaryHtml += `<p><strong>${
      mobooking_booking_form_params.i18n.subtotal_label || "Subtotal"
    }:</strong> ${pricingForReviewPanel.subtotal.toFixed(2)}</p>`;

    if (pricingForReviewPanel.discountAmount > 0) {
      summaryHtml += `<p><strong>${
        mobooking_booking_form_params.i18n.discount_label || "Discount"
      }:</strong> -${pricingForReviewPanel.discountAmount.toFixed(2)}</p>`;
    }

    summaryHtml += `<p class="mobooking-bf__total"><strong>${
      mobooking_booking_form_params.i18n.total_label || "Total"
    }:</strong> ${pricingForReviewPanel.finalTotal.toFixed(2)}</p>`;
    summaryHtml += `</div>`;

    reviewSummaryDiv.html(summaryHtml);

    // Update pricing displays
    subtotalDisplay.text(`${pricingForReviewPanel.subtotal.toFixed(2)}`);
    finalTotalDisplay.text(`${pricingForReviewPanel.finalTotal.toFixed(2)}`);

    if (pricingForReviewPanel.discountAmount > 0) {
      discountAppliedDisplay
        .text(`-${pricingForReviewPanel.discountAmount.toFixed(2)}`)
        .parent()
        .show();
    } else {
      discountAppliedDisplay.parent().hide();
    }
  }

  function calculateTotalPrice(selectedServices, discountInfo = null) {
    let subtotal = 0;

    // Calculate service base price
    selectedServices.forEach((service) => {
      subtotal += parseFloat(service.price) || 0;
    });

    // Add option prices
    Object.values(currentSelectionForSummary.options).forEach((option) => {
      subtotal += parseFloat(option.price) || 0;
    });

    let discountAmount = 0;
    if (discountInfo && discountInfo.is_valid) {
      if (discountInfo.type === "percentage") {
        discountAmount = subtotal * (parseFloat(discountInfo.value) / 100);
      } else if (discountInfo.type === "fixed") {
        discountAmount = parseFloat(discountInfo.value);
      }
      discountAmount = Math.min(discountAmount, subtotal); // Don't discount more than subtotal
    }

    const finalTotal = Math.max(0, subtotal - discountAmount);

    return {
      subtotal: subtotal,
      discountAmount: discountAmount,
      finalTotal: finalTotal,
    };
  }

  // Discount code handling
  applyDiscountBtn.on("click", function () {
    const discountCode = discountCodeInput.val().trim();
    const tenantId = sessionStorage.getItem("mobooking_cart_tenant_id");

    if (!discountCode) {
      discountFeedbackDiv
        .text(mobooking_booking_form_params.i18n.discount_code_required)
        .addClass("error")
        .show();
      return;
    }

    const originalButtonText = $(this).text();
    $(this).prop("disabled", true).text("Checking...");

    $.ajax({
      url: mobooking_booking_form_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_validate_discount_code",
        nonce: mobooking_booking_form_params.nonce,
        discount_code: discountCode,
        tenant_id: tenantId,
      },
      success: function (response) {
        if (response.success && response.data.is_valid) {
          currentSelectionForSummary.discountInfo = response.data;
          sessionStorage.setItem(
            "mobooking_cart_discount_info",
            JSON.stringify(response.data)
          );

          discountFeedbackDiv
            .text(mobooking_booking_form_params.i18n.discount_applied)
            .removeClass("error")
            .addClass("success")
            .show();

          // Refresh pricing displays
          displayStep5_PrepareReview();
          renderOrUpdateSidebarSummary();
        } else {
          discountFeedbackDiv
            .text(
              response.data?.message ||
                mobooking_booking_form_params.i18n.invalid_discount_code
            )
            .addClass("error")
            .show();
        }
      },
      error: function () {
        discountFeedbackDiv
          .text(mobooking_booking_form_params.i18n.discount_error)
          .addClass("error")
          .show();
      },
      complete: function () {
        applyDiscountBtn.prop("disabled", false).text(originalButtonText);
      },
    });
  });

  // Final booking submission
  $("#mobooking-bf-submit-booking-btn").on("click", function (e) {
    e.preventDefault();

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
          "Missing required booking information. Please go back and complete all steps."
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
          // Clear session storage
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

  // New booking button on confirmation step
  $("#mobooking-bf-new-booking-btn").on("click", function () {
    // Reset form state
    currentSelectionForSummary = {
      service: null,
      options: {},
      customerDetails: {},
      discountInfo: null,
      totals: { subtotal: 0, discountAmount: 0, finalTotal: 0 },
    };

    // Clear form inputs
    $("#mobooking-public-booking-form")[0]?.reset();

    // Go back to appropriate first step
    if (formSettings.bf_enable_location_check === "0") {
      displayStep(2);
      displayStep2_LoadServices();
    } else {
      displayStep(1);
    }
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

  // --- Sidebar Summary Logic ---
  function renderOrUpdateSidebarSummary() {
    if (!currentSelectionForSummary.service) {
      sidebarContentDiv.html("<p>No service selected</p>");
      return;
    }

    const service = currentSelectionForSummary.service;
    const options = currentSelectionForSummary.options;
    const discountInfo = currentSelectionForSummary.discountInfo;

    // Calculate totals
    let subtotal = parseFloat(service.price) || 0;
    Object.values(options).forEach((option) => {
      subtotal += parseFloat(option.price) || 0;
    });

    let discountAmount = 0;
    if (discountInfo && discountInfo.is_valid) {
      if (discountInfo.type === "percentage") {
        discountAmount = subtotal * (parseFloat(discountInfo.value) / 100);
      } else if (discountInfo.type === "fixed") {
        discountAmount = parseFloat(discountInfo.value);
      }
      discountAmount = Math.min(discountAmount, subtotal);
    }

    const finalTotal = Math.max(0, subtotal - discountAmount);

    // Update summary object
    currentSelectionForSummary.totals = {
      subtotal: subtotal,
      discountAmount: discountAmount,
      finalTotal: finalTotal,
    };

    let summaryHtml = `
      <h4>${service.name}</h4>
      <p class="mobooking-bf__sidebar-price">${service.price}</p>
      <p class="mobooking-bf__sidebar-duration">${service.duration} minutes</p>
    `;

    if (Object.keys(options).length > 0) {
      summaryHtml += `<div class="mobooking-bf__sidebar-options">`;
      summaryHtml += `<h5>Options:</h5>`;
      Object.values(options).forEach((option) => {
        summaryHtml += `<p>${option.name}: ${option.value}`;
        if (option.price > 0) {
          summaryHtml += ` (+${option.price})`;
        }
        summaryHtml += `</p>`;
      });
      summaryHtml += `</div>`;
    }

    summaryHtml += `
      <div class="mobooking-bf__sidebar-totals">
        <p><strong>Subtotal: ${subtotal.toFixed(2)}</strong></p>
    `;

    if (discountAmount > 0) {
      summaryHtml += `<p class="mobooking-bf__discount">Discount: -${discountAmount.toFixed(
        2
      )}</p>`;
    }

    summaryHtml += `<p class="mobooking-bf__total"><strong>Total: ${finalTotal.toFixed(
      2
    )}</strong></p>`;
    summaryHtml += `</div>`;

    sidebarContentDiv.html(summaryHtml);

    // Update individual pricing elements if they exist
    sidebarSubtotal.text(`${subtotal.toFixed(2)}`);
    sidebarFinalTotal.text(`${finalTotal.toFixed(2)}`);

    if (discountAmount > 0) {
      sidebarDiscountApplied.text(`-${discountAmount.toFixed(2)}`);
      sidebarDiscountItem.show();
    } else {
      sidebarDiscountItem.hide();
    }
  }
});
