/**
 * MoBooking Public Booking Form - Fixed Version
 * Handles multi-step booking form with proper service loading and step navigation
 */

jQuery(document).ready(function ($) {
  "use strict";

  // ==========================================
  // GLOBAL VARIABLES & CONFIGURATION
  // ==========================================

  let currentStep = 1;
  let formData = {
    location: "",
    services: [],
    serviceOptions: {},
    customerDetails: {},
    discount: null,
  };

  // Get configuration from localized script
  const AJAX_URL = window.mobooking_booking_params?.ajax_url || "";
  const FORM_NONCE = window.mobooking_booking_params?.nonce || "";
  const TENANT_ID = window.mobooking_booking_params?.tenant_id || "";
  const TENANT_USER_ID = window.mobooking_booking_params?.tenant_user_id || "";
  const FORM_CONFIG = window.mobooking_booking_params?.form_config || {};
  const I18N = window.mobooking_booking_params?.i18n || {};
  const CURRENCY = window.mobooking_booking_params?.currency || {
    symbol: "$",
    code: "USD",
  };

  // Debug logging
  function debugLog(message, data = null) {
    if (window.location.search.includes("debug=1")) {
      console.log("[MoBooking Debug]", message, data);
    }
  }

  // ==========================================
  // FORM INITIALIZATION
  // ==========================================

  function initializeForm() {
    debugLog("Initializing MoBooking form");
    debugLog("Form config", FORM_CONFIG);
    debugLog("Tenant ID", TENANT_ID);
    debugLog("Tenant User ID", TENANT_USER_ID);

    // Validate required data
    if (!TENANT_ID || !TENANT_USER_ID || !FORM_NONCE) {
      debugLog("Missing required data", {
        TENANT_ID,
        TENANT_USER_ID,
        FORM_NONCE,
      });
      console.error("MoBooking: Missing required configuration data");
      showError("Configuration error. Please contact support.");
      return;
    }

    // Check if form is enabled
    if (!FORM_CONFIG.form_enabled) {
      showMaintenanceMessage();
      return;
    }

    // Bind events
    bindEvents();

    // Initialize date picker if available
    if (typeof flatpickr !== "undefined") {
      initializeDatePicker();
    }

    // Determine starting step based on configuration
    const startStep = FORM_CONFIG.enable_location_check ? 1 : 2;

    if (startStep === 2) {
      // Skip location check and load services immediately
      debugLog("Location check disabled, loading services directly");
      loadServices();
    }

    showStep(startStep);
  }

  // ==========================================
  // STEP NAVIGATION
  // ==========================================

  function showStep(stepNumber) {
    debugLog(`Showing step ${stepNumber}`);

    // Hide all steps
    $(".mobooking-step").hide().removeClass("active");

    // Show target step
    const $targetStep = $(`#mobooking-step-${stepNumber}`);
    if ($targetStep.length) {
      $targetStep.show().addClass("active");
      currentStep = stepNumber;
      updateProgressBar();

      // Initialize step-specific functionality
      initializeStepContent(stepNumber);
    } else {
      debugLog(`Step ${stepNumber} not found`);
    }
  }

  function initializeStepContent(stepNumber) {
    switch (stepNumber) {
      case 1:
        // Location check - already initialized
        break;
      case 2:
        if (formData.services.length === 0) {
          loadServices();
        }
        break;
      case 3:
        loadServiceOptions();
        break;
      case 4:
        // Customer details - form already rendered
        break;
      case 5:
        updateReviewStep();
        break;
    }
  }

  function updateProgressBar() {
    const totalSteps = getTotalSteps();
    const progress = (currentStep / totalSteps) * 100;

    $(".mobooking-progress-bar").css("width", `${progress}%`);
    $(".mobooking-progress-text").text(`Step ${currentStep} of ${totalSteps}`);

    // Update step indicators
    $(".mobooking-progress-step").each(function () {
      const stepNum = parseInt($(this).data("step"));
      $(this).removeClass("active completed");

      if (stepNum === currentStep) {
        $(this).addClass("active");
      } else if (stepNum < currentStep) {
        $(this).addClass("completed");
      }
    });
  }

  function getTotalSteps() {
    // Calculate total steps based on configuration
    let total = 2; // Services and Review are always enabled

    if (FORM_CONFIG.enable_location_check) total++;
    if (hasServiceOptions()) total++;

    total++; // Customer details always enabled

    return total;
  }

  // ==========================================
  // STEP 1: LOCATION CHECK
  // ==========================================

  function handleLocationCheck(e) {
    e.preventDefault();

    const $form = $(this);
    const $feedback = $("#mobooking-location-feedback");
    const $submitBtn = $form.find('button[type="submit"]');
    const originalBtnHtml = $submitBtn.html();
    const location = $("#mobooking-location-input").val().trim();

    if (!location) {
      showFeedback(
        $feedback,
        "error",
        I18N.location_required || "Please enter a location."
      );
      return;
    }

    $submitBtn
      .prop("disabled", true)
      .html(
        '<i class="fas fa-spinner fa-spin"></i> ' +
          (I18N.checking || "Checking...")
      );
    showFeedback($feedback, "", "");

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      dataType: "json",
      data: {
        action: "mobooking_check_service_area",
        nonce: FORM_NONCE,
        tenant_user_id: TENANT_USER_ID,
        location: location,
      },
      success: function (response) {
        debugLog("Location check response", response);

        if (response.success) {
          formData.location = location;
          showFeedback(
            $feedback,
            "success",
            response.data?.message || "Service available in your area!"
          );
          setTimeout(() => {
            showStep(2);
            loadServices();
          }, 1500);
        } else {
          showFeedback(
            $feedback,
            "error",
            response.data?.message || "Service not available in your area."
          );
        }
      },
      error: function (xhr, status, error) {
        debugLog("Location check error", { xhr, status, error });
        let errorMessage =
          I18N.network_error || "Network error occurred. Please try again.";

        if (
          xhr.responseJSON &&
          xhr.responseJSON.data &&
          xhr.responseJSON.data.message
        ) {
          errorMessage = xhr.responseJSON.data.message;
        }

        showFeedback($feedback, "error", errorMessage);
      },
      complete: function () {
        $submitBtn.prop("disabled", false).html(originalBtnHtml);
      },
    });
  }

  // ==========================================
  // STEP 2: SERVICE SELECTION
  // ==========================================

  function loadServices() {
    debugLog("Loading services for tenant", TENANT_USER_ID);

    const $container = $("#mobooking-services-list");
    const $loading = $("#mobooking-services-loading");

    $loading.show();
    $container.hide();

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      dataType: "json",
      data: {
        action: "mobooking_get_public_services",
        nonce: FORM_NONCE,
        tenant_id: TENANT_USER_ID, // Using correct parameter name
      },
      success: function (response) {
        debugLog("Services response", response);

        if (response.success && response.data) {
          renderServices(response.data);
        } else {
          $container.html(
            `<div class="mobooking-no-services"><p>${
              I18N.no_services || "No services available."
            }</p></div>`
          );
        }
      },
      error: function (xhr, status, error) {
        debugLog("Services loading error", { xhr, status, error });
        $container.html(
          `<div class="mobooking-error"><p>${
            I18N.error_loading_services ||
            "Error loading services. Please try again."
          }</p></div>`
        );
      },
      complete: function () {
        $loading.hide();
        $container.show();
      },
    });
  }

  function renderServices(services) {
    debugLog("Rendering services", services);

    const $container = $("#mobooking-services-list");

    if (!services || services.length === 0) {
      $container.html(
        `<div class="mobooking-no-services"><p>${
          I18N.no_services || "No services available."
        }</p></div>`
      );
      return;
    }

    let html = '<div class="mobooking-services-grid">';

    services.forEach((service) => {
      const price = parseFloat(service.price) || 0;
      const duration = parseInt(service.duration) || 0;
      const showPricing = FORM_CONFIG.show_pricing;

      html += `
        <div class="mobooking-service-card" data-service-id="${
          service.service_id
        }">
          <div class="mobooking-service-header">
            ${
              service.icon
                ? `<i class="dashicons dashicons-${service.icon}"></i>`
                : '<i class="dashicons dashicons-admin-tools"></i>'
            }
            <h3 class="mobooking-service-name">${escapeHtml(service.name)}</h3>
          </div>
          
          <div class="mobooking-service-content">
            ${
              service.description
                ? `<p class="mobooking-service-description">${escapeHtml(
                    service.description
                  )}</p>`
                : ""
            }
            
            <div class="mobooking-service-meta">
              ${
                showPricing && price > 0
                  ? `<div class="mobooking-service-price">${
                      CURRENCY.symbol
                    }${price.toFixed(2)}</div>`
                  : ""
              }
              ${
                duration > 0
                  ? `<div class="mobooking-service-duration">${formatDuration(
                      duration
                    )}</div>`
                  : ""
              }
            </div>
          </div>
          
          <div class="mobooking-service-actions">
            <button type="button" class="mobooking-btn mobooking-btn-outline mobooking-service-select-btn">
              <i class="fas fa-plus"></i>
              ${I18N.select_service || "Select"}
            </button>
          </div>
        </div>
      `;
    });

    html += "</div>";
    $container.html(html);

    // Update continue button state
    updateServicesContinueButton();
  }

  function handleServiceSelect() {
    const $card = $(this);
    const serviceId = parseInt($card.data("service-id"));

    $card.toggleClass("selected");

    if ($card.hasClass("selected")) {
      // Add service to selection
      const serviceName = $card.find(".mobooking-service-name").text();
      const servicePrice =
        parseFloat(
          $card
            .find(".mobooking-service-price")
            .text()
            .replace(/[^0-9.]/g, "")
        ) || 0;

      formData.services.push({
        id: serviceId,
        name: serviceName,
        price: servicePrice,
      });

      $card
        .find(".mobooking-service-select-btn")
        .html('<i class="fas fa-check"></i> ' + (I18N.selected || "Selected"))
        .removeClass("mobooking-btn-outline")
        .addClass("mobooking-btn-primary");
    } else {
      // Remove service from selection
      formData.services = formData.services.filter((s) => s.id !== serviceId);

      $card
        .find(".mobooking-service-select-btn")
        .html(
          '<i class="fas fa-plus"></i> ' + (I18N.select_service || "Select")
        )
        .removeClass("mobooking-btn-primary")
        .addClass("mobooking-btn-outline");
    }

    updateServicesContinueButton();
    updateSidebarSummary();
  }

  function updateServicesContinueButton() {
    const $continueBtn = $("#mobooking-step-2-continue");
    const hasSelectedServices = formData.services.length > 0;

    $continueBtn.prop("disabled", !hasSelectedServices);

    if (hasSelectedServices) {
      $continueBtn.removeClass("mobooking-btn-disabled");
    } else {
      $continueBtn.addClass("mobooking-btn-disabled");
    }
  }

  // ==========================================
  // STEP 3: SERVICE OPTIONS
  // ==========================================

  function loadServiceOptions() {
    if (formData.services.length === 0) {
      showStep(4); // Skip to customer details if no services
      return;
    }

    const serviceIds = formData.services.map((s) => s.id);
    debugLog("Loading service options for services", serviceIds);

    // Check if any service has options first
    if (!hasServiceOptions()) {
      showStep(4); // Skip options step
      return;
    }

    const $container = $("#mobooking-service-options-display");
    $container.html(
      '<div class="mobooking-loading"><div class="mobooking-spinner"></div><p>' +
        (I18N.loading_options || "Loading options...") +
        "</p></div>"
    );

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      dataType: "json",
      data: {
        action: "mobooking_get_service_options",
        nonce: FORM_NONCE,
        tenant_user_id: TENANT_USER_ID,
        service_ids: serviceIds,
      },
      success: function (response) {
        debugLog("Service options response", response);

        if (response.success && response.data) {
          renderServiceOptions(response.data);
        } else {
          showStep(4); // Skip to next step if no options
        }
      },
      error: function (xhr, status, error) {
        debugLog("Service options error", { xhr, status, error });
        showStep(4); // Skip to next step on error
      },
    });
  }

  function renderServiceOptions(optionsData) {
    const $container = $("#mobooking-service-options-display");
    let html = "";

    Object.keys(optionsData).forEach((serviceId) => {
      const serviceOptions = optionsData[serviceId];
      const serviceName =
        formData.services.find((s) => s.id == serviceId)?.name || "Service";

      if (serviceOptions && serviceOptions.length > 0) {
        html += `<div class="mobooking-service-options-group" data-service-id="${serviceId}">`;
        html += `<h3 class="mobooking-service-options-title">${escapeHtml(
          serviceName
        )} Options</h3>`;

        serviceOptions.forEach((option) => {
          html += renderSingleOption(option, serviceId);
        });

        html += "</div>";
      }
    });

    if (html) {
      $container.html(html);
    } else {
      showStep(4); // Skip to next step if no options to render
    }
  }

  function renderSingleOption(option, serviceId) {
    const optionId = `option_${serviceId}_${option.option_id}`;
    let html = `<div class="mobooking-option-item" data-option-id="${option.option_id}" data-service-id="${serviceId}">`;

    html += `<label class="mobooking-option-label">${escapeHtml(option.name)}`;
    if (option.required) html += ' <span class="mobooking-required">*</span>';
    html += "</label>";

    if (option.description) {
      html += `<p class="mobooking-option-description">${escapeHtml(
        option.description
      )}</p>`;
    }

    switch (option.type) {
      case "checkbox":
        html += `<input type="checkbox" id="${optionId}" name="${optionId}" value="1" class="mobooking-option-input">`;
        break;
      case "text":
        html += `<input type="text" id="${optionId}" name="${optionId}" class="mobooking-input mobooking-option-input" ${
          option.required ? "required" : ""
        }>`;
        break;
      case "number":
        html += `<input type="number" id="${optionId}" name="${optionId}" class="mobooking-input mobooking-option-input" min="1" ${
          option.required ? "required" : ""
        }>`;
        break;
      case "select":
        html += `<select id="${optionId}" name="${optionId}" class="mobooking-select mobooking-option-input" ${
          option.required ? "required" : ""
        }>`;
        html +=
          '<option value="">' +
          (I18N.select_option || "Select...") +
          "</option>";
        if (option.options) {
          option.options.forEach((opt) => {
            html += `<option value="${escapeHtml(opt)}">${escapeHtml(
              opt
            )}</option>`;
          });
        }
        html += "</select>";
        break;
      case "textarea":
        html += `<textarea id="${optionId}" name="${optionId}" class="mobooking-textarea mobooking-option-input" rows="3" ${
          option.required ? "required" : ""
        }></textarea>`;
        break;
    }

    if (option.price_impact && option.price_impact !== "0") {
      html += `<div class="mobooking-option-price">+${
        CURRENCY.symbol
      }${parseFloat(option.price_impact).toFixed(2)}</div>`;
    }

    html += "</div>";
    return html;
  }

  function hasServiceOptions() {
    // This would normally check if any selected services have options
    // For now, we'll assume they might have options
    return true;
  }

  // ==========================================
  // STEP 4: CUSTOMER DETAILS
  // ==========================================

  function storeCustomerDetails() {
    const $form = $("#mobooking-details-form");

    formData.customerDetails = {
      name: $form.find("#customer-name").val(),
      email: $form.find("#customer-email").val(),
      phone: $form.find("#customer-phone").val(),
      address: $form.find("#service-address").val(),
      city: $form.find("#service-city").val(),
      zip: $form.find("#service-zip").val(),
      instructions: $form.find("#special-instructions").val(),
    };
  }

  // ==========================================
  // STEP 5: REVIEW & CONFIRMATION
  // ==========================================

  function updateReviewStep() {
    const $summary = $("#mobooking-review-summary");
    let html = '<div class="mobooking-review-sections">';

    // Services summary
    html += '<div class="mobooking-review-section">';
    html += "<h3>" + (I18N.selected_services || "Selected Services") + "</h3>";

    let subtotal = 0;
    formData.services.forEach((service) => {
      html += `<div class="mobooking-review-item">
        <span class="mobooking-review-service-name">${escapeHtml(
          service.name
        )}</span>
        <span class="mobooking-review-service-price">${
          CURRENCY.symbol
        }${service.price.toFixed(2)}</span>
      </div>`;
      subtotal += service.price;
    });
    html += "</div>";

    // Customer details summary
    if (formData.customerDetails.name) {
      html += '<div class="mobooking-review-section">';
      html += "<h3>" + (I18N.customer_details || "Customer Details") + "</h3>";
      html += `<div class="mobooking-review-item">
        <strong>${escapeHtml(formData.customerDetails.name)}</strong><br>
        ${escapeHtml(formData.customerDetails.email)}<br>
        ${escapeHtml(formData.customerDetails.phone)}
      </div>`;
      html += "</div>";
    }

    // Pricing summary
    html += '<div class="mobooking-review-section">';
    html += "<h3>" + (I18N.pricing || "Pricing") + "</h3>";
    html += `<div class="mobooking-review-item">
      <span>${I18N.subtotal || "Subtotal"}:</span>
      <span>${CURRENCY.symbol}${subtotal.toFixed(2)}</span>
    </div>`;

    if (formData.discount) {
      html += `<div class="mobooking-review-item">
        <span>${I18N.discount || "Discount"}:</span>
        <span>-${CURRENCY.symbol}${formData.discount.amount.toFixed(2)}</span>
      </div>`;
      subtotal -= formData.discount.amount;
    }

    html += `<div class="mobooking-review-item mobooking-review-total">
      <span><strong>${I18N.total || "Total"}:</strong></span>
      <span><strong>${CURRENCY.symbol}${subtotal.toFixed(2)}</strong></span>
    </div>`;
    html += "</div>";

    html += "</div>";
    $summary.html(html);
  }

  // ==========================================
  // DISCOUNT HANDLING
  // ==========================================

  function handleDiscountApply() {
    const $input = $("#discount-code-input");
    const $btn = $(this);
    const $feedback = $("#discount-feedback");
    const code = $input.val().trim();

    if (!code) {
      showFeedback(
        $feedback,
        "error",
        I18N.discount_code_required || "Please enter a discount code."
      );
      return;
    }

    const originalBtnHtml = $btn.html();
    $btn
      .prop("disabled", true)
      .html(
        '<i class="fas fa-spinner fa-spin"></i> ' +
          (I18N.applying || "Applying...")
      );

    const subtotal = formData.services.reduce(
      (sum, service) => sum + service.price,
      0
    );

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      dataType: "json",
      data: {
        action: "mobooking_apply_discount",
        nonce: FORM_NONCE,
        tenant_user_id: TENANT_USER_ID,
        discount_code: code,
        subtotal: subtotal,
      },
      success: function (response) {
        if (response.success) {
          formData.discount = response.data;
          showFeedback(
            $feedback,
            "success",
            I18N.discount_applied || "Discount applied successfully!"
          );
          updateReviewStep();
        } else {
          showFeedback(
            $feedback,
            "error",
            response.data?.message ||
              I18N.invalid_discount ||
              "Invalid discount code."
          );
        }
      },
      error: function () {
        showFeedback(
          $feedback,
          "error",
          I18N.network_error || "Network error occurred."
        );
      },
      complete: function () {
        $btn.prop("disabled", false).html(originalBtnHtml);
      },
    });
  }

  // ==========================================
  // FINAL SUBMISSION
  // ==========================================

  function handleFinalSubmission() {
    const $btn = $(this);
    const $feedback = $("#final-submission-feedback");

    // Validate required fields
    if (!validateFinalSubmission()) {
      return;
    }

    const originalBtnHtml = $btn.html();
    $btn
      .prop("disabled", true)
      .html(
        '<i class="fas fa-spinner fa-spin"></i> ' +
          (I18N.submitting || "Submitting...")
      );

    const submissionData = {
      action: "mobooking_create_booking",
      nonce: FORM_NONCE,
      tenant_user_id: TENANT_USER_ID,
      location: formData.location,
      services: JSON.stringify(formData.services),
      service_options: JSON.stringify(formData.serviceOptions),
      customer_details: JSON.stringify(formData.customerDetails),
      discount_code: formData.discount?.code || "",
      total_amount: calculateFinalTotal(),
    };

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      dataType: "json",
      data: submissionData,
      success: function (response) {
        if (response.success) {
          showSuccessMessage(response.data);
        } else {
          showFeedback(
            $feedback,
            "error",
            response.data?.message ||
              I18N.submission_error ||
              "Error submitting booking. Please try again."
          );
        }
      },
      error: function () {
        showFeedback(
          $feedback,
          "error",
          I18N.network_error || "Network error occurred. Please try again."
        );
      },
      complete: function () {
        $btn.prop("disabled", false).html(originalBtnHtml);
      },
    });
  }

  function validateFinalSubmission() {
    // Basic validation
    if (formData.services.length === 0) {
      showError(
        I18N.no_services_selected || "Please select at least one service."
      );
      return false;
    }

    if (!formData.customerDetails.name || !formData.customerDetails.email) {
      showError(
        I18N.customer_details_required ||
          "Please fill in all required customer details."
      );
      return false;
    }

    return true;
  }

  function calculateFinalTotal() {
    let total = formData.services.reduce(
      (sum, service) => sum + service.price,
      0
    );

    if (formData.discount) {
      total -= formData.discount.amount;
    }

    return Math.max(0, total);
  }

  function showSuccessMessage(data) {
    const $container = $(".mobooking-form-container");
    const html = `
      <div class="mobooking-success-message">
        <div class="mobooking-success-icon">
          <i class="fas fa-check-circle"></i>
        </div>
        <h2>${I18N.booking_successful || "Booking Successful!"}</h2>
        <p>${
          FORM_CONFIG.success_message ||
          "Thank you for your booking! We will contact you soon."
        }</p>
        ${
          data.booking_reference
            ? `<p><strong>Reference:</strong> ${data.booking_reference}</p>`
            : ""
        }
      </div>
    `;
    $container.html(html);
  }

  // ==========================================
  // UTILITY FUNCTIONS
  // ==========================================

  function showFeedback($element, type, message, autoHide = true) {
    $element
      .removeClass("success error warning info")
      .addClass(type)
      .html(message)
      .show();

    if (autoHide && type === "success") {
      setTimeout(() => $element.fadeOut(), 3000);
    }
  }

  function showError(message) {
    const $container = $(".mobooking-form-container");
    $container.find(".mobooking-error-message").remove();
    $container.prepend(
      `<div class="mobooking-error-message"><i class="fas fa-exclamation-triangle"></i> ${message}</div>`
    );
  }

  function showMaintenanceMessage() {
    const $container = $(".mobooking-form-container");
    const html = `
      <div class="mobooking-maintenance">
        <h3>${I18N.form_unavailable || "Booking Unavailable"}</h3>
        <p>${
          FORM_CONFIG.maintenance_message ||
          "Booking form is currently unavailable."
        }</p>
      </div>
    `;
    $container.html(html);
  }

  function escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  function formatDuration(minutes) {
    if (minutes < 60) {
      return `${minutes} min`;
    }
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    if (remainingMinutes === 0) {
      return `${hours}h`;
    }
    return `${hours}h ${remainingMinutes}min`;
  }

  function updateSidebarSummary() {
    // Update sidebar summary if it exists
    const $sidebar = $(".mobooking-sidebar-summary");
    if ($sidebar.length && formData.services.length > 0) {
      let html =
        "<h3>" + (I18N.selected_services || "Selected Services") + "</h3>";
      formData.services.forEach((service) => {
        html += `<div class="mobooking-summary-item">${escapeHtml(
          service.name
        )} - ${CURRENCY.symbol}${service.price.toFixed(2)}</div>`;
      });
      $sidebar.html(html);
    }
  }

  function initializeDatePicker() {
    if ($("#preferred-datetime").length) {
      flatpickr("#preferred-datetime", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
        minuteIncrement: 15,
        time_24hr: false,
      });
    }
  }

  // ==========================================
  // EVENT BINDING
  // ==========================================

  function bindEvents() {
    debugLog("Binding events");

    // Location check form
    $(document).on("submit", "#mobooking-location-form", handleLocationCheck);

    // Step navigation buttons
    $(document).on(
      "click",
      '[id^="mobooking-step-"][id$="-back"]',
      function () {
        const prevStep = currentStep - 1;
        if (prevStep >= 1) {
          showStep(prevStep);
        }
      }
    );

    $(document).on(
      "click",
      '[id^="mobooking-step-"][id$="-continue"]',
      function () {
        const stepId = $(this).attr("id");
        const stepNumber = parseInt(stepId.match(/step-(\d+)/)[1]);

        if (validateStep(stepNumber)) {
          const nextStep = stepNumber + 1;
          showStep(nextStep);
        }
      }
    );

    // Service selection
    $(document).on("click", ".mobooking-service-card", handleServiceSelect);

    // Service options change
    $(document).on("change input", ".mobooking-option-input", function () {
      const serviceId = $(this)
        .closest(".mobooking-option-item")
        .data("service-id");
      const optionId = $(this)
        .closest(".mobooking-option-item")
        .data("option-id");
      const value = $(this).val();

      if (!formData.serviceOptions[serviceId]) {
        formData.serviceOptions[serviceId] = {};
      }
      formData.serviceOptions[serviceId][optionId] = value;
    });

    // Customer details form
    $(document).on(
      "change input",
      "#mobooking-details-form input, #mobooking-details-form textarea",
      storeCustomerDetails
    );

    // Discount application
    if (FORM_CONFIG.allow_discount_codes) {
      $(document).on("click", "#apply-discount-btn", handleDiscountApply);
    }

    // Final submission
    $(document).on("click", "#final-submit-btn", handleFinalSubmission);

    // Progress step clicks (optional navigation)
    $(document).on("click", ".mobooking-progress-step", function () {
      const stepNumber = parseInt($(this).data("step"));
      if (stepNumber < currentStep) {
        showStep(stepNumber);
      }
    });

    debugLog("Events bound successfully");
  }

  function validateStep(stepNumber) {
    switch (stepNumber) {
      case 1:
        // Location validation
        const location = $("#mobooking-location-input").val().trim();
        if (!location) {
          showError(I18N.location_required || "Please enter a location.");
          return false;
        }
        break;

      case 2:
        // Service selection validation
        if (formData.services.length === 0) {
          showError(
            I18N.no_services_selected || "Please select at least one service."
          );
          return false;
        }
        break;

      case 3:
        // Service options validation
        const $requiredOptions = $(".mobooking-option-input[required]");
        let hasErrors = false;

        $requiredOptions.each(function () {
          if (!$(this).val()) {
            $(this).addClass("error");
            hasErrors = true;
          } else {
            $(this).removeClass("error");
          }
        });

        if (hasErrors) {
          showError(
            I18N.required_options || "Please fill in all required options."
          );
          return false;
        }
        break;

      case 4:
        // Customer details validation
        const requiredFields = ["#customer-name", "#customer-email"];
        let missingFields = [];

        requiredFields.forEach((field) => {
          const $field = $(field);
          if (!$field.val().trim()) {
            $field.addClass("error");
            missingFields.push(
              $field
                .closest(".mobooking-form-group")
                .find("label")
                .text()
                .replace("*", "")
                .trim()
            );
          } else {
            $field.removeClass("error");
          }
        });

        if (missingFields.length > 0) {
          showError(
            (I18N.required_fields || "Please fill in required fields") +
              ": " +
              missingFields.join(", ")
          );
          return false;
        }

        // Email validation
        const email = $("#customer-email").val();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
          $("#customer-email").addClass("error");
          showError(
            I18N.invalid_email || "Please enter a valid email address."
          );
          return false;
        }

        break;
    }

    return true;
  }

  // ==========================================
  // INITIALIZATION CALL
  // ==========================================

  // Initialize the form when the document is ready
  initializeForm();
});
