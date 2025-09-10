/**
 * Complete NORDBOOKING Booking Form JavaScript
 * Fixes service options display and implements live price calculation
 */

jQuery(document).ready(function ($) {
  "use strict";

  // ==========================================
  // GLOBAL VARIABLES
  // ==========================================

  const locationForm = $("#NORDBOOKING-bf-location-form");
  const feedbackDiv = $("#NORDBOOKING-bf-feedback");
  const step1Div = $("#NORDBOOKING-bf-step-1-location");
  const tenantIdField = $("#NORDBOOKING-bf-tenant-id");

  // Step 2 elements
  const step2ServicesDiv = $("#NORDBOOKING-bf-step-2-services");
  const servicesListDiv = $("#NORDBOOKING-bf-services-list");
  const step2FeedbackDiv = $("#NORDBOOKING-bf-step-2-feedback").hide();

  // Step 3 elements
  const step3OptionsDiv = $("#NORDBOOKING-bf-step-3-options");
  const serviceOptionsDisplayDiv = $("#NORDBOOKING-bf-service-options-display");
  const step3FeedbackDiv = $("#NORDBOOKING-bf-step-3-feedback").hide();

  // Step 4 elements
  const step4DetailsDiv = $("#NORDBOOKING-bf-step-4-details");
  const step4FeedbackDiv = $("#NORDBOOKING-bf-step-4-feedback").hide();

  // Step 5 elements
  const step5ReviewDiv = $("#NORDBOOKING-bf-step-5-review");
  const reviewSummaryDiv = $("#NORDBOOKING-bf-review-summary");
  const step5FeedbackDiv = $("#NORDBOOKING-bf-step-5-feedback").hide();
  const discountCodeInput = $("#NORDBOOKING-bf-discount-code");
  const applyDiscountBtn = $("#NORDBOOKING-bf-apply-discount-btn");
  const discountFeedbackDiv = $("#NORDBOOKING-bf-discount-feedback").hide();
  const subtotalDisplay = $("#NORDBOOKING-bf-subtotal");
  const discountAppliedDisplay = $("#NORDBOOKING-bf-discount-applied");
  const finalTotalDisplay = $("#NORDBOOKING-bf-final-total");

  // Sidebar elements
  const sidebarSummaryDiv = $("#NORDBOOKING-bf-sidebar-summary");

  // Currency settings
  const currencyCode =
    typeof nordbooking_booking_form_params !== "undefined" &&
    nordbooking_booking_form_params.currency_code
      ? nordbooking_booking_form_params.currency_code
      : "USD";
  const currencySymbol =
    typeof nordbooking_booking_form_params !== "undefined" &&
    nordbooking_booking_form_params.currency_symbol
      ? nordbooking_booking_form_params.currency_symbol
      : "$";

  // Form settings
  const formSettings =
    typeof nordbooking_booking_form_params !== "undefined" &&
    nordbooking_booking_form_params.settings
      ? nordbooking_booking_form_params.settings
      : {
          bf_header_text: "Book Our Services",
          bf_show_pricing: "1",
          bf_allow_discount_codes: "1",
          bf_theme_color: "#1abc9c",
          bf_custom_css: "",
          bf_form_enabled: "1",
          bf_maintenance_message: "Booking form is currently unavailable.",
          bf_enable_location_check: "1",
          bf_allow_service_selection: "1",
          bf_confirmation_message: "Thank you for your booking!",
          bf_form_title: "Book Our Services",
          bf_form_description:
            "Select your preferred service and book an appointment.",
          bf_button_text: "Book Now",
          bf_button_color: "#1abc9c",
          bf_text_color: "#333333",
          bf_background_color: "#ffffff",
        };

  // Global state
  let currentSelectionForSummary = {
    service: null,
    options: {},
    details: {},
    pricing: {
      subtotal: 0,
      optionsTotal: 0,
      discountAmount: 0,
      finalTotal: 0,
    },
  };

  let pricingForReviewPanel = {
    subtotal: 0,
    discountAmount: 0,
    finalTotal: 0,
  };

  let appliedDiscount = null;

  // ==========================================
  // INITIALIZATION
  // ==========================================

  console.log("[NORDBOOKING Debug] Booking form initialized");

  // Check if form is enabled
  if (formSettings.bf_form_enabled !== "1") {
    $(".NORDBOOKING-bf__container").html(`
      <div class="NORDBOOKING-bf__maintenance">
        <h3>${
          nordbooking_booking_form_params.i18n.form_disabled_title ||
          "Booking Unavailable"
        }</h3>
        <p>${formSettings.bf_maintenance_message}</p>
      </div>
    `);
    return;
  }

  // Initialize form
  initializeForm();

  // ==========================================
  // FORM INITIALIZATION
  // ==========================================

  function initializeForm() {
    // Check if location check is disabled
    if (formSettings.bf_enable_location_check !== "1") {
      const tenantId = tenantIdField.val();
      if (tenantId) {
        console.log("Location check is disabled, proceeding to services");
        displayStep(2);
        loadServicesForTenant(tenantId);
        return;
      } else {
        console.error("Tenant ID not found and location check is disabled");
        feedbackDiv
          .text(
            nordbooking_booking_form_params.i18n.tenant_id_missing ||
              "Configuration error"
          )
          .addClass("error")
          .show();
        return;
      }
    } else {
      console.log("Location check is enabled, showing Step 1");
      displayStep(1);
    }
  }

  // ==========================================
  // STEP DISPLAY FUNCTIONS
  // ==========================================

  function displayStep(stepNumber) {
    console.log(`[NORDBOOKING Debug] Displaying step ${stepNumber}`);

    // Hide all steps
    $(".NORDBOOKING-bf__step").hide();

    // Show current step
    $(
      `#NORDBOOKING-bf-step-${stepNumber}-location, #NORDBOOKING-bf-step-${stepNumber}-services, #NORDBOOKING-bf-step-${stepNumber}-options, #NORDBOOKING-bf-step-${stepNumber}-details, #NORDBOOKING-bf-step-${stepNumber}-review`
    ).show();

    // Update progress bar
    updateProgressBar(stepNumber);

    // Scroll to top of form
    $(".NORDBOOKING-bf__container")
      .get(0)
      .scrollIntoView({ behavior: "smooth" });
  }

  function updateProgressBar(currentStep) {
    const totalSteps = 5;
    const progress = (currentStep / totalSteps) * 100;

    $(".NORDBOOKING-bf__progress-bar").css("width", progress + "%");
    $(".NORDBOOKING-bf__progress-text").text(
      `Step ${currentStep} of ${totalSteps}`
    );
  }

  // ==========================================
  // STEP 1: LOCATION CHECK
  // ==========================================

  locationForm.on("submit", function (e) {
    e.preventDefault();
    const zipCode = $("#NORDBOOKING-bf-zip").val().trim();
    const countryCode = $("#NORDBOOKING-bf-country").val().trim();
    const tenantId = tenantIdField.val();

    feedbackDiv.empty().hide();

    if (!zipCode) {
      feedbackDiv
        .text(
          nordbooking_booking_form_params.i18n.zip_required ||
            "ZIP code is required"
        )
        .addClass("error")
        .show();
      return;
    }
    if (!countryCode) {
      feedbackDiv
        .text(
          nordbooking_booking_form_params.i18n.country_code_required ||
            "Country is required"
        )
        .addClass("error")
        .show();
      return;
    }
    if (!tenantId) {
      feedbackDiv
        .text(
          nordbooking_booking_form_params.i18n.tenant_id_missing ||
            "Tenant ID missing"
        )
        .addClass("error")
        .show();
      return;
    }

    const submitButton = $(this).find('button[type="submit"]');
    const originalButtonText = submitButton.text();
    submitButton
      .prop("disabled", true)
      .text(nordbooking_booking_form_params.i18n.checking || "Checking...");

    $.ajax({
      url: nordbooking_booking_form_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_check_service_area",
        nonce: nordbooking_booking_form_params.nonce,
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
            setTimeout(() => {
              displayStep(2);
              loadServicesForTenant(tenantId);
            }, 1000);
          }
        } else {
          feedbackDiv
            .text(response.data.message || "Error checking service area")
            .addClass("error")
            .show();
        }
      },
      error: function () {
        feedbackDiv
          .text(
            nordbooking_booking_form_params.i18n.ajax_error ||
              "Connection error"
          )
          .addClass("error")
          .show();
      },
      complete: function () {
        submitButton.prop("disabled", false).text(originalButtonText);
      },
    });
  });

  // ==========================================
  // STEP 2: SERVICE SELECTION
  // ==========================================

  function loadServicesForTenant(tenantId) {
    console.log(`[NORDBOOKING Debug] Loading services for tenant: ${tenantId}`);

    servicesListDiv.html(
      '<div class="NORDBOOKING-bf__loading">Loading available services...</div>'
    );

    $.ajax({
      url: nordbooking_booking_form_params.ajax_url,
      type: "POST",
      data: {
        action: "nordbooking_get_services",
        nonce: nordbooking_booking_form_params.nonce,
        tenant_id: tenantId,
      },
      success: function (response) {
        console.log("[NORDBOOKING Debug] Services response:", response);

        if (response.success && response.data.services) {
          renderServices(response.data.services);
        } else {
          servicesListDiv.html(
            `<p class="NORDBOOKING-bf__error">${
              response.data.message || "No services available"
            }</p>`
          );
        }
      },
      error: function () {
        servicesListDiv.html(
          `<p class="NORDBOOKING-bf__error">${
            nordbooking_booking_form_params.i18n.ajax_error ||
            "Error loading services"
          }</p>`
        );
      },
    });
  }

  function renderServices(services) {
    console.log("[NORDBOOKING Debug] Rendering services:", services);

    if (!services || services.length === 0) {
      servicesListDiv.html(
        '<p class="NORDBOOKING-bf__error">No services available</p>'
      );
      return;
    }

    let servicesHtml = "";
    services.forEach((service) => {
      const price = parseFloat(service.price) || 0;
      const duration = parseInt(service.duration) || 0;
      const priceDisplay =
        formSettings.bf_show_pricing === "1"
          ? `<span class="NORDBOOKING-bf__service-price">${currencySymbol}${price.toFixed(
              2
            )}</span>`
          : "";

      servicesHtml += `
        <div class="NORDBOOKING-bf__service-item">
          <div class="NORDBOOKING-bf__service-icon">
            ${service.icon ? `<i class="dashicons ${service.icon}"></i>` : ""}
          </div>
          <div class="NORDBOOKING-bf__service-content">
            <label class="NORDBOOKING-bf__label NORDBOOKING-bf__label--radio">
              <input type="radio" name="selected_service" value="${
                service.service_id
              }" class="NORDBOOKING-bf__radio" data-service='${JSON.stringify(
        service
      )}'>
              <span class="NORDBOOKING-bf__service-name">${service.name}</span>
              ${priceDisplay}
            </label>
            <div class="NORDBOOKING-bf__service-duration">${duration} minutes</div>
            ${
              service.description
                ? `<div class="NORDBOOKING-bf__service-description">${service.description}</div>`
                : ""
            }
          </div>
        </div>
      `;
    });

    servicesListDiv.html(servicesHtml);

    // Initialize service selection handling
    initializeServiceSelection();
  }

  function initializeServiceSelection() {
    $(document)
      .off("change.service-selection")
      .on(
        "change.service-selection",
        'input[name="selected_service"]',
        function () {
          const serviceData = $(this).data("service");
          console.log("[NORDBOOKING Debug] Service selected:", serviceData);

          currentSelectionForSummary.service = serviceData;
          updatePricing();
          renderOrUpdateSidebarSummary();

          $("#NORDBOOKING-bf-services-next-btn")
            .prop("disabled", false)
            .css("opacity", "1");
        }
      );
  }

  // Services next button
  $("#NORDBOOKING-bf-services-next-btn").on("click", function () {
    const selectedService = $('input[name="selected_service"]:checked');

    if (selectedService.length === 0) {
      step2FeedbackDiv
        .text(
          nordbooking_booking_form_params.i18n.select_service_required ||
            "Please select a service"
        )
        .addClass("error")
        .show();
      return;
    }

    const serviceData = selectedService.data("service");
    currentSelectionForSummary.service = serviceData;

    step2FeedbackDiv.empty().hide();

    sessionStorage.setItem(
      "nordbooking_cart_selected_services",
      JSON.stringify([serviceData])
    );

    displayStep(3);
    displayStep3_LoadOptions();
  });

  // Services back button
  $("#NORDBOOKING-bf-services-back-btn").on("click", function () {
    displayStep(1);
  });

  // ==========================================
  // STEP 3: SERVICE OPTIONS (FIXED VERSION)
  // ==========================================

  function displayStep3_LoadOptions() {
    console.log("[NORDBOOKING Debug] Loading service options...");

    const service = currentSelectionForSummary.service;

    if (!service) {
      const selectedServicesJSON = sessionStorage.getItem(
        "nordbooking_cart_selected_services"
      );
      if (!selectedServicesJSON) {
        step3FeedbackDiv
          .text(
            nordbooking_booking_form_params.i18n.no_service_selected_options ||
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
            nordbooking_booking_form_params.i18n.no_service_selected_options ||
              "No service selected."
          )
          .addClass("error")
          .show();
        displayStep(2);
        return;
      }
      currentSelectionForSummary.service = tempSelectedServices[0];
    }

    const currentService = currentSelectionForSummary.service;
    console.log("[NORDBOOKING Debug] Current service:", currentService);

    // Update step title
    const step3Title = $("#NORDBOOKING-bf-step-3-title");
    if (step3Title.length && currentService.name) {
      const baseTitle =
        nordbooking_booking_form_params.i18n.step3_title ||
        "Step 3: Configure Service Options";
      step3Title.text(baseTitle + " for " + currentService.name);
    }

    // Clear previous content
    serviceOptionsDisplayDiv.empty();
    step3FeedbackDiv.empty().removeClass("error success").hide();

    // Check if service has options
    if (!currentService.options || currentService.options.length === 0) {
      console.log("[NORDBOOKING Debug] No options found for service");
      serviceOptionsDisplayDiv.html(
        `<p>${
          nordbooking_booking_form_params.i18n.no_options_for_service ||
          "This service has no additional options."
        }</p>`
      );
      currentSelectionForSummary.options = {};
      updatePricing();
      renderOrUpdateSidebarSummary();
      return;
    }

    console.log(
      "[NORDBOOKING Debug] Processing options:",
      currentService.options
    );

    // Process each option using template system
    currentService.options.forEach((option, index) => {
      console.log(`[NORDBOOKING Debug] Processing option ${index}:`, option);

      try {
        const optionHtml = generateOptionFromTemplate(
          option,
          currentService.service_id
        );
        if (optionHtml) {
          serviceOptionsDisplayDiv.append(optionHtml);
          console.log(
            `[NORDBOOKING Debug] Option ${index} rendered successfully`
          );
        } else {
          console.warn(`[NORDBOOKING Debug] Failed to render option ${index}`);
        }
      } catch (error) {
        console.error(
          `[NORDBOOKING Debug] Error rendering option ${index}:`,
          error
        );
        const fallbackHtml = generateFallbackOptionHtml(
          option,
          currentService.service_id
        );
        serviceOptionsDisplayDiv.append(fallbackHtml);
      }
    });

    // Initialize interactive elements
    initializeOptionInteractivity();

    // Initialize options state
    currentSelectionForSummary.options = {};
    updatePricing();
    renderOrUpdateSidebarSummary();
  }

  function generateOptionFromTemplate(option, serviceId) {
    const templateId = `NORDBOOKING-bf-option-${option.type}-template`;
    const template = $(`#${templateId}`);

    if (template.length === 0) {
      console.warn(`[NORDBOOKING Debug] Template not found: ${templateId}`);
      return generateFallbackOptionHtml(option, serviceId);
    }

    let html = template.html();

    // Template data
    const templateData = {
      service_id: serviceId,
      option_id: option.option_id,
      name: escapeHtml(option.name || ""),
      required_attr: option.is_required == 1 ? "required" : "",
      quantity_default_value:
        option.is_required == 1 &&
        (option.type === "quantity" || option.type === "number")
          ? 1
          : 0,
      description: escapeHtml(option.description || ""),
      type: option.type,
      price_impact: parseFloat(option.price_impact) || 0,
      price_impact_type: option.price_impact_type || "fixed",
    };

    // Replace template variables
    html = html.replace(/<%=\s*service_id\s*%>/g, templateData.service_id);
    html = html.replace(/<%=\s*option_id\s*%>/g, templateData.option_id);
    html = html.replace(/<%=\s*name\s*%>/g, templateData.name);
    html = html.replace(
      /<%=\s*required_attr\s*%>/g,
      templateData.required_attr
    );
    html = html.replace(
      /<%=\s*quantity_default_value\s*%>/g,
      templateData.quantity_default_value
    );

    // Handle price impact placeholder
    let priceImpactHtml = "";
    if (templateData.price_impact > 0) {
      if (templateData.price_impact_type === "percentage") {
        priceImpactHtml = `<span class="NORDBOOKING-bf__option-price">(+${templateData.price_impact}%)</span>`;
      } else if (templateData.price_impact_type === "multiply") {
        priceImpactHtml = `<span class="NORDBOOKING-bf__option-price">(×${templateData.price_impact})</span>`;
      } else {
        priceImpactHtml = `<span class="NORDBOOKING-bf__option-price">(+${currencySymbol}${templateData.price_impact})</span>`;
      }
    }
    html = html.replace(
      /<!--\s*price_impact_placeholder\s*-->/g,
      priceImpactHtml
    );

    // Handle description placeholder
    let descriptionHtml = "";
    if (templateData.description) {
      descriptionHtml = `<p class="NORDBOOKING-bf__option-description">${templateData.description}</p>`;
    }
    html = html.replace(
      /<!--\s*description_placeholder\s*-->/g,
      descriptionHtml
    );

    // Handle required indicator placeholder
    let requiredIndicatorHtml = "";
    if (option.is_required == 1) {
      requiredIndicatorHtml =
        '<span class="NORDBOOKING-bf__required" style="color: red;">*</span>';
    }
    html = html.replace(
      /<!--\s*required_indicator_placeholder\s*-->/g,
      requiredIndicatorHtml
    );

    // Handle select/radio options loop placeholder
    if (option.type === "select" || option.type === "radio") {
      html = handleSelectRadioOptions(html, option, serviceId);
    }

    return $(html);
  }

  function handleSelectRadioOptions(html, option, serviceId) {
    let optionsLoopHtml = "";

    if (option.option_values) {
      try {
        let optionValues =
          typeof option.option_values === "string"
            ? JSON.parse(option.option_values)
            : option.option_values;

        if (Array.isArray(optionValues) && optionValues.length > 0) {
          optionValues.forEach((choice, index) => {
            const choiceLabel = escapeHtml(
              choice.label || choice.value || choice
            );
            const choiceValue = choice.value || choice.label || choice;
            const choicePrice = parseFloat(choice.price) || 0;

            if (option.type === "select") {
              optionsLoopHtml += `<option value="${escapeHtml(
                choiceValue
              )}" data-price="${choicePrice}">${choiceLabel}</option>`;
            } else if (option.type === "radio") {
              const radioId = `option-${serviceId}-${option.option_id}-${index}`;
              let priceSuffix = "";
              if (choicePrice > 0) {
                priceSuffix = `<span class="NORDBOOKING-bf__option-price">(+${currencySymbol}${choicePrice})</span>`;
              }

              optionsLoopHtml += `
                <div class="NORDBOOKING-bf__radio-option">
                  <input type="radio" name="service_option[${serviceId}][${
                option.option_id
              }]" value="${escapeHtml(
                choiceValue
              )}" id="${radioId}" class="NORDBOOKING-bf__radio" data-price="${choicePrice}" ${
                option.is_required == 1 ? "required" : ""
              }>
                  <label for="${radioId}" class="NORDBOOKING-bf__label NORDBOOKING-bf__label--radio">${choiceLabel} ${priceSuffix}</label>
                </div>`;
            }
          });
        }
      } catch (e) {
        console.error("[NORDBOOKING Debug] Error parsing option values:", e);
      }
    }

    return html.replace(
      /<!--\s*options_loop_placeholder\s*-->/g,
      optionsLoopHtml
    );
  }

  function generateFallbackOptionHtml(option, serviceId) {
    console.log(
      "[NORDBOOKING Debug] Using fallback HTML generation for option:",
      option
    );

    const name = escapeHtml(option.name || "");
    const description = escapeHtml(option.description || "");
    const isRequired = option.is_required == 1;
    const requiredAttr = isRequired ? "required" : "";
    const requiredIndicator = isRequired
      ? '<span class="NORDBOOKING-bf__required" style="color: red;">*</span>'
      : "";
    const priceImpact = parseFloat(option.price_impact) || 0;
    const priceImpactType = option.price_impact_type || "fixed";

    let priceImpactHtml = "";
    if (priceImpact > 0) {
      if (priceImpactType === "percentage") {
        priceImpactHtml = `<span class="NORDBOOKING-bf__option-price">(+${priceImpact}%)</span>`;
      } else if (priceImpactType === "multiply") {
        priceImpactHtml = `<span class="NORDBOOKING-bf__option-price">(×${priceImpact})</span>`;
      } else {
        priceImpactHtml = `<span class="NORDBOOKING-bf__option-price">(+${currencySymbol}${priceImpact})</span>`;
      }
    }

    let descriptionHtml = "";
    if (description) {
      descriptionHtml = `<p class="NORDBOOKING-bf__option-description">${description}</p>`;
    }

    const commonClasses =
      "NORDBOOKING-bf__option-group NORDBOOKING-bf__option-item";
    const dataAttrs = `data-service-id="${serviceId}" data-option-id="${option.option_id}" data-option-type="${option.type}"`;

    switch (option.type) {
      case "checkbox":
        return $(`
          <div class="${commonClasses}" ${dataAttrs}>
            <div class="NORDBOOKING-bf__checkbox-wrapper">
              <input type="checkbox" name="service_option[${serviceId}][${option.option_id}]" value="1" class="NORDBOOKING-bf__checkbox" id="option-${serviceId}-${option.option_id}" data-price="${priceImpact}" data-price-type="${priceImpactType}" ${requiredAttr}>
              <label for="option-${serviceId}-${option.option_id}" class="NORDBOOKING-bf__label">${name} ${priceImpactHtml} ${requiredIndicator}</label>
            </div>
            ${descriptionHtml}
          </div>
        `);

      case "text":
        return $(`
          <div class="${commonClasses}" ${dataAttrs}>
            <label for="option-${serviceId}-${option.option_id}" class="NORDBOOKING-bf__label">${name} ${priceImpactHtml} ${requiredIndicator}</label>
            <input type="text" id="option-${serviceId}-${option.option_id}" name="service_option[${serviceId}][${option.option_id}]" class="NORDBOOKING-bf__input" data-price="${priceImpact}" data-price-type="${priceImpactType}" ${requiredAttr}>
            ${descriptionHtml}
          </div>
        `);

      case "textarea":
        return $(`
          <div class="${commonClasses}" ${dataAttrs}>
            <label for="option-${serviceId}-${option.option_id}" class="NORDBOOKING-bf__label">${name} ${priceImpactHtml} ${requiredIndicator}</label>
            <textarea id="option-${serviceId}-${option.option_id}" name="service_option[${serviceId}][${option.option_id}]" class="NORDBOOKING-bf__textarea" rows="3" data-price="${priceImpact}" data-price-type="${priceImpactType}" ${requiredAttr}></textarea>
            ${descriptionHtml}
          </div>
        `);

      case "number":
      case "quantity":
        const defaultValue = isRequired ? 1 : 0;
        return $(`
          <div class="${commonClasses}" ${dataAttrs}>
            <label for="option-${serviceId}-${option.option_id}" class="NORDBOOKING-bf__label">${name} ${priceImpactHtml} ${requiredIndicator}</label>
            <div class="NORDBOOKING-bf__number-input-wrapper" style="display: flex; align-items: center; gap: 0.5rem; max-width: 150px;">
              <button type="button" class="NORDBOOKING-bf__button NORDBOOKING-bf__button--outline NORDBOOKING-bf__number-btn" data-action="decrease" style="padding: 0; width: 2rem; height: 2rem;">-</button>
              <input type="number" id="option-${serviceId}-${option.option_id}" name="service_option[${serviceId}][${option.option_id}]" value="${defaultValue}" min="0" class="NORDBOOKING-bf__input NORDBOOKING-bf__input--number" data-price="${priceImpact}" data-price-type="${priceImpactType}" ${requiredAttr} style="text-align: center; flex: 1;">
              <button type="button" class="NORDBOOKING-bf__button NORDBOOKING-bf__button--outline NORDBOOKING-bf__number-btn" data-action="increase" style="padding: 0; width: 2rem; height: 2rem;">+</button>
            </div>
            ${descriptionHtml}
          </div>
        `);

      case "sqm":
        // Special handling for SQM type
        return generateSqmOptionHtml(option, serviceId);

      default:
        return $(`
          <div class="${commonClasses}" ${dataAttrs}>
            <label for="option-${serviceId}-${option.option_id}" class="NORDBOOKING-bf__label">${name} ${priceImpactHtml} ${requiredIndicator}</label>
            <input type="text" id="option-${serviceId}-${option.option_id}" name="service_option[${serviceId}][${option.option_id}]" class="NORDBOOKING-bf__input" data-price="${priceImpact}" data-price-type="${priceImpactType}" ${requiredAttr}>
            ${descriptionHtml}
          </div>
        `);
    }
  }

  function generateSqmOptionHtml(option, serviceId) {
    const name = escapeHtml(option.name || "");
    const description = escapeHtml(option.description || "");
    const isRequired = option.is_required == 1;
    const requiredAttr = isRequired ? "required" : "";
    const requiredIndicator = isRequired
      ? '<span class="NORDBOOKING-bf__required" style="color: red;">*</span>'
      : "";

    let descriptionHtml = "";
    if (description) {
      descriptionHtml = `<p class="NORDBOOKING-bf__option-description">${description}</p>`;
    }

    let rangesHtml = "";
    if (option.option_values) {
      try {
        let ranges =
          typeof option.option_values === "string"
            ? JSON.parse(option.option_values)
            : option.option_values;
        if (Array.isArray(ranges)) {
          ranges.forEach((range, index) => {
            const fromValue = range.from || 0;
            const toValue = range.to || "∞";
            const price = parseFloat(range.price) || 0;
            rangesHtml += `
              <div class="NORDBOOKING-bf__sqm-range">
                <span>${fromValue} - ${toValue} sqm: ${currencySymbol}${price}/sqm</span>
              </div>
            `;
          });
        }
      } catch (e) {
        console.error("[NORDBOOKING Debug] Error parsing SQM ranges:", e);
      }
    }

    const commonClasses =
      "NORDBOOKING-bf__option-group NORDBOOKING-bf__option-item";
    const dataAttrs = `data-service-id="${serviceId}" data-option-id="${option.option_id}" data-option-type="sqm"`;

    return $(`
      <div class="${commonClasses}" ${dataAttrs}>
        <label for="option-${serviceId}-${option.option_id}" class="NORDBOOKING-bf__label">${name} ${requiredIndicator}</label>
        <div class="NORDBOOKING-bf__sqm-ranges">${rangesHtml}</div>
        <input type="number" id="option-${serviceId}-${option.option_id}" name="service_option[${serviceId}][${option.option_id}]" class="NORDBOOKING-bf__input" placeholder="Enter square meters" min="1" step="0.1" data-option-ranges='${JSON.stringify(option.option_values)}' ${requiredAttr}>
        ${descriptionHtml}
      </div>
    `);
  }

  function initializeOptionInteractivity() {
    console.log("[NORDBOOKING Debug] Initializing option interactivity...");

    // Remove existing handlers to prevent duplicates
    $(document).off("click.NORDBOOKING-options");
    $(document).off("change.NORDBOOKING-options");
    $(document).off("input.NORDBOOKING-options");

    // Handle quantity/number button controls
    $(document).on(
      "click.NORDBOOKING-options",
      ".NORDBOOKING-bf__number-btn",
      function (e) {
        e.preventDefault();
        const action = $(this).data("action");
        const wrapper = $(this).closest(
          ".NORDBOOKING-bf__number-input-wrapper"
        );
        const input = wrapper.find('input[type="number"]');
        let currentValue = parseInt(input.val()) || 0;

        if (action === "increase") {
          input.val(currentValue + 1).trigger("change");
        } else if (action === "decrease" && currentValue > 0) {
          input.val(currentValue - 1).trigger("change");
        }
      }
    );

    // Handle all option changes for pricing updates
    $(document).on(
      "change.NORDBOOKING-options input.NORDBOOKING-options",
      ".NORDBOOKING-bf__option-item input, .NORDBOOKING-bf__option-item select, .NORDBOOKING-bf__option-item textarea",
      function () {
        console.log(
          "[NORDBOOKING Debug] Option changed:",
          $(this).attr("name"),
          $(this).val()
        );
        updateOptionsSelection();
        updatePricing();
        renderOrUpdateSidebarSummary();
      }
    );
  }

  function updateOptionsSelection() {
    if (!currentSelectionForSummary.service) {
      console.log(
        "[NORDBOOKING Debug] No service selected, skipping options update"
      );
      return;
    }

    currentSelectionForSummary.options = {};
    let optionsTotal = 0;

    $(".NORDBOOKING-bf__option-item").each(function () {
      const $item = $(this);
      const optionId = $item.data("option-id");
      const optionType = $item.data("option-type");

      if (!optionId) {
        console.warn(
          "[NORDBOOKING Debug] Option item missing option-id:",
          $item
        );
        return;
      }

      let value = null;
      let price = 0;
      let name = getOptionName($item);

      switch (optionType) {
        case "checkbox":
          const checkbox = $item.find('input[type="checkbox"]');
          if (checkbox.is(":checked")) {
            value = "1";
            price = calculateOptionPrice(checkbox);
          }
          break;

        case "text":
        case "textarea":
          const textInput = $item.find('input[type="text"], textarea');
          value = textInput.val().trim();
          if (value) {
            price = calculateOptionPrice(textInput);
          }
          break;

        case "number":
        case "quantity":
          const numberInput = $item.find('input[type="number"]');
          const numValue = parseInt(numberInput.val()) || 0;
          if (numValue > 0) {
            value = numValue.toString();
            price = calculateOptionPrice(numberInput, numValue);
          }
          break;

        case "select":
          const select = $item.find("select");
          const selectedOption = select.find("option:selected");
          value = select.val();
          if (value) {
            price = parseFloat(selectedOption.data("price")) || 0;
          }
          break;

        case "radio":
          const checkedRadio = $item.find('input[type="radio"]:checked');
          if (checkedRadio.length) {
            value = checkedRadio.val();
            price = parseFloat(checkedRadio.data("price")) || 0;
          }
          break;

        case "sqm":
          const sqmInput = $item.find('input[type="number"]');
          const sqmValue = parseFloat(sqmInput.val()) || 0;
          if (sqmValue > 0) {
            value = sqmValue.toString();
            price = calculateSqmPrice(sqmInput, sqmValue);
          }
          break;
      }

      if (value !== null && value !== "") {
        currentSelectionForSummary.options[optionId] = {
          name: name,
          value: value,
          price: price,
          type: optionType,
        };

        optionsTotal += price;

        console.log(`[NORDBOOKING Debug] Option ${optionId} updated:`, {
          name: name,
          value: value,
          price: price,
          type: optionType,
        });
      }
    });

    currentSelectionForSummary.pricing.optionsTotal = optionsTotal;
  }

  function calculateOptionPrice(input, quantity = 1) {
    const basePrice = parseFloat(input.data("price")) || 0;
    const priceType = input.data("price-type") || "fixed";
    const servicePrice =
      parseFloat(currentSelectionForSummary.service.price) || 0;

    switch (priceType) {
      case "percentage":
        return ((servicePrice * basePrice) / 100) * quantity;
      case "multiply":
        return servicePrice * basePrice * quantity;
      case "fixed":
      default:
        return basePrice * quantity;
    }
  }

  function calculateSqmPrice(input, sqmValue) {
    try {
      const rangesData = input.data("option-ranges");
      let ranges =
        typeof rangesData === "string" ? JSON.parse(rangesData) : rangesData;

      if (!Array.isArray(ranges)) {
        return 0;
      }

      // Find the appropriate range
      for (let range of ranges) {
        const fromValue = parseFloat(range.from) || 0;
        const toValue =
          range.to === "∞" ? Infinity : parseFloat(range.to) || Infinity;
        const price = parseFloat(range.price) || 0;

        if (sqmValue >= fromValue && sqmValue <= toValue) {
          return sqmValue * price;
        }
      }

      return 0;
    } catch (e) {
      console.error("[NORDBOOKING Debug] Error calculating SQM price:", e);
      return 0;
    }
  }

  // Options navigation buttons
  $("#NORDBOOKING-bf-options-next-btn").on("click", function () {
    // Validate required options
    let missingRequired = [];
    $(".NORDBOOKING-bf__option-item").each(function () {
      const $item = $(this);
      const optionType = $item.data("option-type");
      const optionName = getOptionName($item);

      const requiredInput = $item.find(
        "input[required], select[required], textarea[required]"
      );
      if (requiredInput.length > 0) {
        let hasValue = false;

        if (optionType === "checkbox") {
          hasValue = requiredInput.is(":checked");
        } else if (optionType === "radio") {
          hasValue = $item.find('input[type="radio"]:checked').length > 0;
        } else if (optionType === "select") {
          hasValue = requiredInput.val() !== "";
        } else {
          hasValue = requiredInput.val().trim() !== "";
        }

        if (!hasValue) {
          missingRequired.push(optionName);
        }
      }
    });

    if (missingRequired.length > 0) {
      step3FeedbackDiv
        .text(`Please fill in required options: ${missingRequired.join(", ")}`)
        .addClass("error")
        .show();
      return;
    }

    step3FeedbackDiv.empty().hide();
    displayStep(4);
  });

  $("#NORDBOOKING-bf-options-back-btn").on("click", function () {
    displayStep(2);
  });

  // ==========================================
  // STEP 4: BOOKING DETAILS
  // ==========================================

  $("#NORDBOOKING-bf-details-form").on("submit", function (e) {
    e.preventDefault();

    // Collect form data
    const formData = {
      customer_name: $("#NORDBOOKING-bf-customer-name").val().trim(),
      customer_email: $("#NORDBOOKING-bf-customer-email").val().trim(),
      customer_phone: $("#NORDBOOKING-bf-customer-phone").val().trim(),
      service_address: $("#NORDBOOKING-bf-service-address").val().trim(),
      preferred_date: $("#NORDBOOKING-bf-preferred-date").val(),
      preferred_time: $("#NORDBOOKING-bf-preferred-time").val(),
      special_instructions: $("#NORDBOOKING-bf-special-instructions")
        .val()
        .trim(),
    };

    // Validate required fields
    const requiredFields = [
      "customer_name",
      "customer_email",
      "customer_phone",
      "service_address",
    ];
    let missingFields = [];

    requiredFields.forEach((field) => {
      if (!formData[field]) {
        missingFields.push(field.replace("customer_", "").replace("_", " "));
      }
    });

    if (missingFields.length > 0) {
      step4FeedbackDiv
        .text(`Please fill in required fields: ${missingFields.join(", ")}`)
        .addClass("error")
        .show();
      return;
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.customer_email)) {
      step4FeedbackDiv
        .text("Please enter a valid email address")
        .addClass("error")
        .show();
      return;
    }

    // Store details
    currentSelectionForSummary.details = formData;

    step4FeedbackDiv.empty().hide();
    displayStep(5);
    displayStep5_Review();
  });

  $("#NORDBOOKING-bf-details-back-btn").on("click", function () {
    displayStep(3);
  });

  // ==========================================
  // STEP 5: REVIEW AND CONFIRMATION
  // ==========================================

  function displayStep5_Review() {
    console.log("[NORDBOOKING Debug] Displaying review step");

    updatePricing();
    renderReviewSummary();

    // Initialize discount functionality
    initializeDiscountFunctionality();
  }

  function renderReviewSummary() {
    if (!currentSelectionForSummary.service) {
      reviewSummaryDiv.html('<p class="error">No service selected</p>');
      return;
    }

    const service = currentSelectionForSummary.service;
    const details = currentSelectionForSummary.details;
    const pricing = currentSelectionForSummary.pricing;

    let summaryHtml = `
      <div class="NORDBOOKING-bf__review-section">
        <h4>Service Details</h4>
        <div class="NORDBOOKING-bf__review-service">
          <h5>${escapeHtml(service.name)}</h5>
          <p><strong>Duration:</strong> ${service.duration} minutes</p>
          <p><strong>Base Price:</strong> ${currencySymbol}${parseFloat(
      service.price
    ).toFixed(2)}</p>
        </div>
      </div>
    `;

    // Show selected options
    if (Object.keys(currentSelectionForSummary.options).length > 0) {
      summaryHtml +=
        '<div class="NORDBOOKING-bf__review-section"><h4>Selected Options</h4>';
      Object.values(currentSelectionForSummary.options).forEach((option) => {
        summaryHtml += `
          <div class="NORDBOOKING-bf__review-option">
            <strong>${escapeHtml(option.name)}:</strong> ${escapeHtml(
          option.value
        )}
            ${
              option.price > 0
                ? `<span class="NORDBOOKING-bf__option-price">(+${currencySymbol}${option.price.toFixed(
                    2
                  )})</span>`
                : ""
            }
          </div>
        `;
      });
      summaryHtml += "</div>";
    }

    // Show customer details
    summaryHtml += `
      <div class="NORDBOOKING-bf__review-section">
        <h4>Customer Information</h4>
        <p><strong>Name:</strong> ${escapeHtml(details.customer_name)}</p>
        <p><strong>Email:</strong> ${escapeHtml(details.customer_email)}</p>
        <p><strong>Phone:</strong> ${escapeHtml(details.customer_phone)}</p>
        <p><strong>Service Address:</strong> ${escapeHtml(
          details.service_address
        )}</p>
        ${
          details.preferred_date
            ? `<p><strong>Preferred Date:</strong> ${details.preferred_date}</p>`
            : ""
        }
        ${
          details.preferred_time
            ? `<p><strong>Preferred Time:</strong> ${details.preferred_time}</p>`
            : ""
        }
        ${
          details.special_instructions
            ? `<p><strong>Special Instructions:</strong> ${escapeHtml(
                details.special_instructions
              )}</p>`
            : ""
        }
      </div>
    `;

    reviewSummaryDiv.html(summaryHtml);

    // Update pricing displays
    updatePricingDisplay();
  }

  function initializeDiscountFunctionality() {
    if (formSettings.bf_allow_discount_codes !== "1") {
      $(".NORDBOOKING-bf__discount-section").hide();
      return;
    }

    applyDiscountBtn.off("click").on("click", function () {
      const discountCode = discountCodeInput.val().trim();

      if (!discountCode) {
        discountFeedbackDiv
          .text("Please enter a discount code")
          .addClass("error")
          .show();
        return;
      }

      // Apply discount via AJAX
      $.ajax({
        url: nordbooking_booking_form_params.ajax_url,
        type: "POST",
        data: {
          action: "nordbooking_apply_discount",
          nonce: nordbooking_booking_form_params.nonce,
          discount_code: discountCode,
          tenant_id: tenantIdField.val(),
          subtotal: currentSelectionForSummary.pricing.subtotal,
        },
        success: function (response) {
          if (response.success) {
            appliedDiscount = response.data.discount;
            currentSelectionForSummary.pricing.discountAmount =
              response.data.discount_amount;

            discountFeedbackDiv
              .text(`Discount applied: ${response.data.discount.name}`)
              .removeClass("error")
              .addClass("success")
              .show();
            discountCodeInput.prop("disabled", true);
            applyDiscountBtn.text("Applied").prop("disabled", true);

            updatePricing();
            updatePricingDisplay();
            renderOrUpdateSidebarSummary();
          } else {
            discountFeedbackDiv
              .text(response.data.message || "Invalid discount code")
              .addClass("error")
              .show();
          }
        },
        error: function () {
          discountFeedbackDiv
            .text("Error applying discount")
            .addClass("error")
            .show();
        },
      });
    });
  }

  $("#NORDBOOKING-bf-review-back-btn").on("click", function () {
    displayStep(4);
  });

  $("#NORDBOOKING-bf-final-submit-btn").on("click", function () {
    // Final booking submission
    submitBooking();
  });

  // ==========================================
  // PRICING CALCULATIONS
  // ==========================================

  function updatePricing() {
    if (!currentSelectionForSummary.service) {
      return;
    }

    const servicePrice =
      parseFloat(currentSelectionForSummary.service.price) || 0;
    const optionsTotal = currentSelectionForSummary.pricing.optionsTotal || 0;
    const discountAmount =
      currentSelectionForSummary.pricing.discountAmount || 0;

    const subtotal = servicePrice + optionsTotal;
    const finalTotal = Math.max(0, subtotal - discountAmount);

    currentSelectionForSummary.pricing.subtotal = subtotal;
    currentSelectionForSummary.pricing.finalTotal = finalTotal;

    // Update review panel pricing
    pricingForReviewPanel.subtotal = subtotal;
    pricingForReviewPanel.discountAmount = discountAmount;
    pricingForReviewPanel.finalTotal = finalTotal;

    console.log(
      "[NORDBOOKING Debug] Pricing updated:",
      currentSelectionForSummary.pricing
    );
  }

  function updatePricingDisplay() {
    const pricing = currentSelectionForSummary.pricing;

    subtotalDisplay.text(`${currencySymbol}${pricing.subtotal.toFixed(2)}`);
    finalTotalDisplay.text(`${currencySymbol}${pricing.finalTotal.toFixed(2)}`);

    if (pricing.discountAmount > 0) {
      discountAppliedDisplay
        .text(`-${currencySymbol}${pricing.discountAmount.toFixed(2)}`)
        .show();
    } else {
      discountAppliedDisplay.hide();
    }
  }

  // ==========================================
  // SIDEBAR SUMMARY
  // ==========================================

  function renderOrUpdateSidebarSummary() {
    if (!sidebarSummaryDiv.length) {
      console.log("[NORDBOOKING Debug] Sidebar summary div not found");
      return;
    }

    if (!currentSelectionForSummary.service) {
      sidebarSummaryDiv.html(
        '<p class="NORDBOOKING-bf__sidebar-empty">No service selected</p>'
      );
      return;
    }

    const service = currentSelectionForSummary.service;
    const pricing = currentSelectionForSummary.pricing;

    let summaryHtml = `
      <div class="NORDBOOKING-bf__sidebar-service">
        <h4>${escapeHtml(service.name)}</h4>
        <div class="NORDBOOKING-bf__sidebar-price">
          <span class="NORDBOOKING-bf__sidebar-service-price">${currencySymbol}${parseFloat(
      service.price
    ).toFixed(2)}</span>
          <span class="NORDBOOKING-bf__sidebar-duration">${
            service.duration
          } min</span>
        </div>
      </div>
    `;

    // Show selected options
    if (Object.keys(currentSelectionForSummary.options).length > 0) {
      summaryHtml +=
        '<div class="NORDBOOKING-bf__sidebar-options"><h5>Options</h5>';
      Object.values(currentSelectionForSummary.options).forEach((option) => {
        summaryHtml += `
          <div class="NORDBOOKING-bf__sidebar-option">
            <span class="NORDBOOKING-bf__sidebar-option-name">${escapeHtml(
              option.name
            )}: ${escapeHtml(option.value)}</span>
            ${
              option.price > 0
                ? `<span class="NORDBOOKING-bf__sidebar-option-price">+${currencySymbol}${option.price.toFixed(
                    2
                  )}</span>`
                : ""
            }
          </div>
        `;
      });
      summaryHtml += "</div>";
    }

    // Show pricing summary
    summaryHtml += `
      <div class="NORDBOOKING-bf__sidebar-pricing">
        <div class="NORDBOOKING-bf__sidebar-subtotal">
          <span>Subtotal:</span>
          <span>${currencySymbol}${pricing.subtotal.toFixed(2)}</span>
        </div>
        ${
          pricing.discountAmount > 0
            ? `
          <div class="NORDBOOKING-bf__sidebar-discount">
            <span>Discount:</span>
            <span>-${currencySymbol}${pricing.discountAmount.toFixed(2)}</span>
          </div>
        `
            : ""
        }
        <div class="NORDBOOKING-bf__sidebar-total">
          <span><strong>Total:</strong></span>
          <span><strong>${currencySymbol}${pricing.finalTotal.toFixed(
      2
    )}</strong></span>
        </div>
      </div>
    `;

    sidebarSummaryDiv.html(summaryHtml);

    console.log("[NORDBOOKING Debug] Sidebar summary updated");
  }

  // ==========================================
  // BOOKING SUBMISSION
  // ==========================================

  function submitBooking() {
    const submitBtn = $("#NORDBOOKING-bf-final-submit-btn");
    const originalBtnText = submitBtn.text();

    submitBtn.prop("disabled", true).text("Submitting...");

    const bookingData = {
      action: "nordbooking_submit_booking",
      nonce: nordbooking_booking_form_params.nonce,
      tenant_id: tenantIdField.val(),
      service: currentSelectionForSummary.service,
      options: currentSelectionForSummary.options,
      details: currentSelectionForSummary.details,
      pricing: currentSelectionForSummary.pricing,
      discount: appliedDiscount,
    };

    $.ajax({
      url: nordbooking_booking_form_params.ajax_url,
      type: "POST",
      data: bookingData,
      success: function (response) {
        if (response.success) {
          // Show success message
          displayStep6_Success(response.data);
        } else {
          step5FeedbackDiv
            .text(response.data.message || "Booking submission failed")
            .addClass("error")
            .show();
        }
      },
      error: function () {
        step5FeedbackDiv
          .text("Connection error. Please try again.")
          .addClass("error")
          .show();
      },
      complete: function () {
        submitBtn.prop("disabled", false).text(originalBtnText);
      },
    });
  }

  function displayStep6_Success(data) {
    const successHtml = `
      <div class="NORDBOOKING-bf__success">
        <div class="NORDBOOKING-bf__success-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2>Booking Confirmed!</h2>
        <p>Your booking has been successfully submitted.</p>
        ${
          data.booking_reference
            ? `<p><strong>Reference:</strong> ${data.booking_reference}</p>`
            : ""
        }
        <p>You will receive a confirmation email shortly.</p>
        <div class="NORDBOOKING-bf__success-actions">
          <button type="button" class="NORDBOOKING-bf__button NORDBOOKING-bf__button--primary" onclick="window.location.reload();">
            Book Another Service
          </button>
        </div>
      </div>
    `;

    $(".NORDBOOKING-bf__container").html(successHtml);
  }

  // ==========================================
  // UTILITY FUNCTIONS
  // ==========================================

  function getOptionName($item) {
    let name = $item.find(".NORDBOOKING-bf__label").first().text().trim();
    name = name.replace(/\([+×]\$?[\d.%]+\)/g, "");
    name = name.replace(/\*\s*$/, "");
    return name.trim() || "Unknown Option";
  }

  function escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  // ==========================================
  // DEBUG FUNCTIONS
  // ==========================================

  function debugServiceOptions() {
    console.log("=== NORDBOOKING Service Options Debug ===");
    console.log("Current service:", currentSelectionForSummary.service);
    console.log("Current options:", currentSelectionForSummary.options);
    console.log("Current pricing:", currentSelectionForSummary.pricing);

    if (
      currentSelectionForSummary.service &&
      currentSelectionForSummary.service.options
    ) {
      console.log(
        "Service options:",
        currentSelectionForSummary.service.options
      );
    }

    console.log("Available templates:");
    $('script[type="text/template"]').each(function () {
      const id = $(this).attr("id");
      if (id && id.includes("option")) {
        console.log(`- ${id}: ${$(this).length > 0 ? "Found" : "Missing"}`);
      }
    });

    console.log("=====================================");
  }

  // Make debug function available globally
  window.debugServiceOptions = debugServiceOptions;
  window.nordbookingCurrentSelection = currentSelectionForSummary;

  // Initialize pricing
  updatePricing();
  renderOrUpdateSidebarSummary();

  console.log("[NORDBOOKING Debug] Booking form setup complete");
});
