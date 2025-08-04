/**
 * Enhanced Booking Form JavaScript - Aligned with New PHP Template
 * File: assets/js/booking-form-enhanced.js
 */
jQuery(document).ready(function ($) {
  "use strict";

  console.log('MoBooking public booking form script loaded.');

  // Global parameters from PHP localization
  const MOB_PARAMS = window.mobooking_booking_form_params || {};
  let PRELOADED_SERVICES = window.MOB_PRELOADED_SERVICES || [];
  // Correctly initialize FORM_CONFIG from the localized settings
  const FORM_CONFIG = MOB_PARAMS.form_config || {};
  const CURRENCY = MOB_PARAMS.currency || { symbol: "$", code: "USD" };
  const TENANT_ID = MOB_PARAMS.tenant_id || null;
  const FORM_NONCE = MOB_PARAMS.nonce || null; // Use MOB_PARAMS
  const AJAX_URL = MOB_PARAMS.ajax_url || "/wp-admin/admin-ajax.php"; // Use MOB_PARAMS
  const I18N = MOB_PARAMS.i18n || {}; // Use MOB_PARAMS
  const IS_DEBUG = MOB_PARAMS.is_debug_mode || false; // Use MOB_PARAMS

  // Current Booking State
  let currentStep = 1;
  let selectedService = null;
  let selectedOptions = {};
  let customerDetails = {};
  let discountInfo = null;
  let totalPrice = 0;

  // --- UTILITY FUNCTIONS ---

  function debugLog(message, data = null) {
    if (IS_DEBUG) {
        console.group("MoBooking Debug: " + message);
        if (data) {
            console.log(data);
        }
        console.groupEnd();
    }
  }

  // --- AJAX DEBUG WRAPPER ---
  const originalAjax = $.ajax;
  $.ajax = function() {
      const args = Array.prototype.slice.call(arguments);
      const xhr = originalAjax.apply(this, args);

      if (IS_DEBUG) {
          const requestData = args[0] && args[0].data ? args[0].data : {};
          const action = requestData.action || 'N/A';

          console.groupCollapsed(`[AJAX SENT] ${action}`);
          console.log('Request URL:', args[0].url);
          console.log('Request Data:', requestData);
          console.groupEnd();

          xhr.done(function(responseData) {
              console.groupCollapsed(`[AJAX SUCCESS] ${action}`);
              console.log('Response:', responseData);
              console.groupEnd();
          }).fail(function(jqXHR, textStatus, errorThrown) {
              console.group(`[AJAX FAILED] ${action}`);
              console.error('Status:', textStatus);
              console.error('Error:', errorThrown);
              console.error('Response Text:', jqXHR.responseText);
              console.groupEnd();
          });
      }

      return xhr;
  };

  function safeJsonEncode(data) {
    try {
      const cleanData = cleanDataForJson(data);
      const jsonString = JSON.stringify(cleanData);
      debugLog("JSON Encoding successful", {
        original: data,
        cleaned: cleanData,
      });
      return jsonString;
    } catch (error) {
      debugLog("JSON encoding failed: " + error.message, data);
      console.error("JSON encoding failed:", error, data);
      return null;
    }
  }

  function cleanDataForJson(data) {
    if (typeof data === "string") {
      return data.replace(/[\u0000-\u001F\u007F-\u009F]/g, "").trim();
    } else if (Array.isArray(data)) {
      return data.map(cleanDataForJson);
    } else if (data && typeof data === "object") {
      const cleaned = {};
      for (const key in data) {
        if (data.hasOwnProperty(key)) {
          cleaned[key] = cleanDataForJson(data[key]);
        }
      }
      return cleaned;
    }
    return data;
  }

  function escapeHtml(text) {
    if (typeof text !== "string") return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  function formatPrice(price) {
    return parseFloat(price || 0).toFixed(2);
  }

  function showFeedback(elementSelector, type, message, autoHide = true) {
    const $element = $(elementSelector);
    $element.removeClass("success error info warning").hide();
    if (message) {
      $element.addClass(type).html(message).show();
    }
    if (autoHide && (type === "success" || type === "error")) {
      setTimeout(() => {
        if ($element.html() === message) {
          $element.fadeOut();
        }
      }, 5000);
    }
  }

  // --- STEP MANAGEMENT ---

  function showStep(stepNumber) {
    debugLog(`Showing step ${stepNumber}`);

    $(".mobooking-step").removeClass("active").hide();
    const $targetStep = $(`.mobooking-step[data-step="${stepNumber}"]`);

    if ($targetStep.length === 0) {
      debugLog(`Step ${stepNumber} not found`);
      return;
    }

    $targetStep.show().addClass("active");
    currentStep = stepNumber;
    updateProgressBar(currentStep);
    loadStepData(currentStep);

    // Scroll to top of form
    if ($(".mobooking-form-wrapper").length) {
      $("html, body").animate(
        {
          scrollTop: $(".mobooking-form-wrapper").offset().top - 50,
        },
        300
      );
    }
  }

  function updateProgressBar(step) {
    if (!FORM_CONFIG.show_progress_bar) return;

    const totalSteps = 8;
    const progressPercentage = ((step - 1) / (totalSteps - 1)) * 100;

    $(".mobooking-progress-bar").css(
      "width",
      Math.min(100, Math.max(0, progressPercentage)) + "%"
    );

    $(".mobooking-progress-step").each(function () {
      const stepNum = parseInt($(this).data("step"));
      $(this).removeClass("active completed");
      $(this).find(".step-number").text(stepNum);

      if (stepNum < step) {
        $(this).addClass("completed");
        $(this).find(".step-number").text("");
      } else if (stepNum === step) {
        $(this).addClass("active");
      }
    });
  }

  function loadStepData(stepNumber) {
    switch (stepNumber) {
      case 2:
        displayServices();
        break;
      case 3:
        displayServiceOptions();
        break;
      case 4:
        // No specific data to load, but we can bind events here
        $('input[name="has_pets"]').on('change', function() {
            if (this.value === 'yes') {
                $('#pet-details-section').show();
            } else {
                $('#pet-details-section').hide();
            }
        });
        break;
      case 5:
        // No specific data to load, but we can bind events here
        $('input[name="service_frequency"]').on('change', function() {
            customerDetails.service_frequency = this.value;
            debugLog("Service frequency stored", customerDetails.service_frequency);
        });
        break;
      case 6:
        flatpickr("#preferred-date", {
            minDate: "today",
            onChange: function(selectedDates, dateStr, instance) {
                customerDetails.date = dateStr;
                debugLog("Date selected", customerDetails.date);
                // TODO: Add logic to fetch available time slots
            }
        });
        $("#preferred-time").on('change', function() {
            customerDetails.time = this.value;
            debugLog("Time selected", customerDetails.time);
        });
        break;
      case 7:
        populateCustomerForm();
        $('input[name="property_access"]').on('change', function() {
            if (this.value === 'other') {
                $('#custom-access-details').show();
            } else {
                $('#custom-access-details').hide();
            }
        });
        break;
      case 8:
        // Populate final booking details
        let finalDetailsHtml = `
            <p><strong>${I18N.booking_reference || "Booking Reference"}:</strong> ${escapeHtml(
          customerDetails.booking_reference || "N/A"
        )}</p>
            <p><strong>${I18N.service || "Service"}:</strong> ${escapeHtml(
          selectedService.name
        )}</p>
            <p><strong>${I18N.customer || "Customer"}:</strong> ${escapeHtml(
          customerDetails.name
        )}</p>
            <p><strong>${I18N.email || "Email"}:</strong> ${escapeHtml(
          customerDetails.email
        )}</p>
            <p><strong>${I18N.total || "Total"}:</strong> ${
          CURRENCY.symbol
        }${formatPrice(totalPrice)}</p>
        `;
        $("#mobooking-final-booking-details").html(finalDetailsHtml);
        break;
    }
    updateLiveSummary();
  }

  // --- STEP 1: LOCATION CHECK ---

  function handleLocationCheck(e) {
    e.preventDefault();
    debugLog("Location check initiated");

    const zipCode = $("#mobooking-zip").val().trim();
    const countryCode = $("#mobooking-country").val();
    const $feedback = $("#mobooking-location-feedback");
    const $submitBtn = $(e.target).find('button[type="submit"]');
    const originalBtnHtml = $submitBtn.html();

    if (!zipCode) {
      showFeedback(
        $feedback,
        "error",
        I18N.zip_required || "Please enter your ZIP code."
      );
      return;
    }

    if (!countryCode) {
      showFeedback(
        $feedback,
        "error",
        I18N.country_required || "Please select your country."
      );
      return;
    }

    $submitBtn
      .prop("disabled", true)
      .html(
        '<div class="mobooking-spinner"></div> ' +
          (I18N.checking_availability || "Checking availability...")
      );
    showFeedback(
      $feedback,
      "info",
      I18N.checking_availability || "Checking availability...",
      false
    );

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: {
        action: "mobooking_check_service_area",
        nonce: FORM_NONCE,
        zip_code: zipCode,
        country_code: countryCode,
        tenant_id: TENANT_ID,
      },
      success: function (response) {
        debugLog("Location check response", response);

        if (response.success && response.data && response.data.serviced) {
          showFeedback(
            $feedback,
            "success",
            response.data.message ||
              I18N.service_available ||
              "Service is available in your area!"
          );
          setTimeout(() => showStep(2), 1500);
        } else {
          showFeedback(
            $feedback,
            "error",
            response.data?.message ||
              I18N.service_not_available ||
              "Service is not available in your area."
          );
        }
      },
      error: function (xhr, status, error) {
        debugLog("Location check error", { xhr, status, error });
        showFeedback(
          $feedback,
          "error",
          I18N.network_error || "Network error occurred. Please try again."
        );
      },
      complete: function () {
        $submitBtn.prop("disabled", false).html(originalBtnHtml);
      },
    });
  }

  // --- STEP 2: SERVICE SELECTION ---

  function displayServices() {
    debugLog("Displaying services", PRELOADED_SERVICES);

    const $container = $("#mobooking-services-container");
    $container.empty();

    if (!PRELOADED_SERVICES || PRELOADED_SERVICES.length === 0) {
      $container.html(
        `<div class="mobooking-loading"><div class="mobooking-spinner"></div>${
          I18N.loading_services || "Loading services..."
        }</div>`
      );
      loadServicesFromServer();
      return;
    }

    renderServiceCards(PRELOADED_SERVICES);
  }

  function loadServicesFromServer() {
    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: {
        action: "mobooking_get_public_services",
        nonce: FORM_NONCE,
        tenant_id: TENANT_ID,
      },
      success: function (response) {
        console.log('Services loaded from server:', response);
        debugLog("Services loaded from server", response);

        if (response.success && response.data) {
          PRELOADED_SERVICES = response.data;
          renderServiceCards(response.data);
          debugLog("Rendered services", response.data);
        } else {
          $("#mobooking-services-container").html(
            `<p>${I18N.no_services || "No services available."}</p>`
          );
        }
      },
      error: function (xhr, status, error) {
        debugLog("Services loading error", { xhr, status, error });
        $("#mobooking-services-container").html(
          `<p>${I18N.error || "Error loading services."}</p>`
        );
      },
    });
  }

  function renderServiceCards(services) {
    const $container = $("#mobooking-services-container");
    let html = "";

    services.forEach((service) => {
      const priceDisplay =
        FORM_CONFIG.show_pricing && service.price
          ? `<div class="mobooking-service-price">${
              CURRENCY.symbol
            }${formatPrice(service.price)}</div>`
          : "";

      let iconHtml = "";
      if (service.icon_svg_content) {
        iconHtml = service.icon_svg_content;
      } else if (
        service.icon &&
        (service.icon.startsWith("http") ||
          service.icon.includes("/wp-content/uploads/"))
      ) {
        iconHtml = `<img src="${escapeHtml(service.icon)}" alt="${escapeHtml(
          service.name
        )} Icon">`;
      } else if (service.icon) {
        iconHtml = `<i class="${escapeHtml(service.icon)}"></i>`;
      } else {
        iconHtml = `<i class="fas fa-broom"></i>`;
      }

      html += `
                <div class="mobooking-service-card" data-service-id="${
                  service.service_id
                }">
                    <div class="mobooking-service-header">
                        <div class="mobooking-service-icon">${iconHtml}</div>
                        <div class="mobooking-service-info">
                            <h3 class="mobooking-service-name">${escapeHtml(
                              service.name
                            )}</h3>
                            ${priceDisplay}
                        </div>
                    </div>
                    ${
                      service.description
                        ? `<div class="mobooking-service-description">${escapeHtml(
                            service.description
                          )}</div>`
                        : ""
                    }
                    <div class="mobooking-service-meta">
                        ${
                          service.duration
                            ? `<span class="duration"><i class="fas fa-clock"></i> ${
                                service.duration
                              } ${I18N.minutes || "min"}</span>`
                            : ""
                        }
                        ${
                          service.category
                            ? `<span class="category"><i class="fas fa-tag"></i> ${escapeHtml(
                                service.category
                              )}</span>`
                            : ""
                        }
                    </div>
                </div>`;
    });

    $container.html(html);

    // Pre-select if already chosen
    if (selectedService) {
      $(
        `.mobooking-service-card[data-service-id="${selectedService.service_id}"]`
      ).addClass("selected");
      $('[data-step-next="3"]').prop("disabled", false);
    } else {
      $('[data-step-next="3"]').prop("disabled", true);
    }
  }

  function handleServiceSelect(e) {
    const $card = $(e.currentTarget);
    const serviceId = parseInt($card.data("service-id"));

    debugLog("Service selected", serviceId);

    $(".mobooking-service-card").removeClass("selected");
    $card.addClass("selected");

    selectedService = PRELOADED_SERVICES.find(
      (s) => s.service_id === serviceId
    );
    selectedOptions = {};
    discountInfo = null;

    $('[data-step-next="3"]').prop("disabled", false);
    showFeedback($("#mobooking-services-feedback"), "", "", true);
    updateLiveSummary();
  }

  // --- STEP 3: SERVICE OPTIONS ---

  function displayServiceOptions() {
    debugLog("Displaying service options", {
        selectedService: selectedService,
        options: selectedService.options
    });

    const $container = $("#mobooking-service-options");
    $container.empty();

    if (
      !selectedService ||
      !selectedService.options ||
      selectedService.options.length === 0
    ) {
      $container.html(
        `<div class="no-options-message"><p>${
          I18N.no_options || "No additional options available."
        }</p></div>`
      );
      return;
    }

    let html = "";
    selectedService.options.forEach((option) => {
      if (!option || typeof option.option_id === "undefined") {
        debugLog("Invalid option data", option);
        return;
      }

      const requiredAttr = option.is_required ? "required" : "";
      const requiredIndicator = option.is_required
        ? '<span class="mobooking-required">*</span>'
        : "";
      const priceImpact = parseFloat(option.price_impact || 0);
      const priceDisplay =
        priceImpact !== 0
          ? `<span class="option-price">(+${CURRENCY.symbol}${formatPrice(
              priceImpact
            )})</span>`
          : "";

      html += `<div class="mobooking-form-group" data-option-id="${option.option_id}">`;
      html += `<label class="mobooking-label">${escapeHtml(
        option.name
      )} ${priceDisplay} ${requiredIndicator}</label>`;

      switch (option.type) {
        case "checkbox":
          html += `<input type="checkbox" id="option_${option.option_id}" name="option_${option.option_id}" 
                             value="1" data-price="${priceImpact}" ${requiredAttr}>`;
          break;
        case "text":
          html += `<input type="text" id="option_${option.option_id}" name="option_${option.option_id}" 
                             class="mobooking-input" data-price="${priceImpact}" ${requiredAttr}>`;
          break;
        case "textarea":
          html += `<textarea id="option_${option.option_id}" name="option_${option.option_id}" 
                             class="mobooking-textarea" data-price="${priceImpact}" ${requiredAttr}></textarea>`;
          break;
        case "select":
          html += `<select id="option_${option.option_id}" name="option_${option.option_id}" 
                             class="mobooking-select" data-price="${priceImpact}" ${requiredAttr}>`;
          if (option.option_values && Array.isArray(option.option_values)) {
            option.option_values.forEach((val) => {
              html += `<option value="${escapeHtml(val.value)}">${escapeHtml(
                val.label
              )}</option>`;
            });
          }
          html += "</select>";
          break;
        case "quantity":
          html += `
                        <div class="mobooking-quantity-input-wrapper">
                            <button type="button" class="mobooking-btn-quantity minus" data-target="option_${option.option_id}">-</button>
                            <input type="number" id="option_${option.option_id}" name="option_${option.option_id}" 
                                   value="0" min="0" class="mobooking-input" data-price="${priceImpact}" ${requiredAttr}>
                            <button type="button" class="mobooking-btn-quantity plus" data-target="option_${option.option_id}">+</button>
                        </div>`;
          break;
      }

      if (option.description) {
        html += `<div class="option-description">${escapeHtml(
          option.description
        )}</div>`;
      }
      html += "</div>";
    });

    $container.html(html);

    // Bind events for newly created elements
    $container
      .find("input, textarea, select")
      .on("change input", handleOptionChange);
    $container
      .find(".mobooking-btn-quantity")
      .on("click", handleQuantityButtonClick);

    updateLiveSummary();
  }

  function handleOptionChange(e) {
    const $input = $(e.target);
    const $formGroup = $input.closest(".mobooking-form-group");
    const optionId = $formGroup.data("option-id");
    const serviceOption = selectedService.options.find(
      (opt) => opt.option_id === parseInt(optionId)
    );

    if (!serviceOption) {
      debugLog("Service option not found", optionId);
      return;
    }

    debugLog("Option changed", { optionId, serviceOption });

    let value = $input.val();
    let price = 0;

    if (serviceOption.type === "checkbox") {
      value = $input.is(":checked") ? "1" : "0";
      if (value === "1") {
        price = parseFloat(serviceOption.price_impact || 0);
      }
    } else if (serviceOption.type === "quantity") {
      const quantity = parseInt(value) || 0;
      if (quantity > 0) {
        price = parseFloat(serviceOption.price_impact || 0) * quantity;
      }
      value = quantity.toString();
    } else if (value) {
      price = parseFloat(serviceOption.price_impact || 0);
    }

    if (
      value &&
      (serviceOption.type === "checkbox" ? value === "1" : true) &&
      (serviceOption.type !== "quantity" || parseInt(value) > 0)
    ) {
      selectedOptions[optionId] = {
        name: serviceOption.name,
        value: value,
        price: price,
        priceType: serviceOption.price_impact_type || "fixed",
      };
    } else {
      delete selectedOptions[optionId];
    }

    debugLog("Updated selected options", selectedOptions);
    updateLiveSummary();
  }

  function handleQuantityButtonClick(e) {
    const $button = $(e.target);
    const targetInputId = $button.data("target");
    const $input = $("#" + targetInputId);
    let currentValue = parseInt($input.val()) || 0;

    if ($button.hasClass("plus")) {
      currentValue++;
    } else if ($button.hasClass("minus") && currentValue > 0) {
      currentValue--;
    }

    $input.val(currentValue).trigger("change");
  }

  // --- STEP 4: CUSTOMER DETAILS ---

  function populateCustomerForm() {
    debugLog("Populating customer form");

    // Pre-fill if data exists
    if (customerDetails.name) $("#customer-name").val(customerDetails.name);
    if (customerDetails.email) $("#customer-email").val(customerDetails.email);
    if (customerDetails.phone) $("#customer-phone").val(customerDetails.phone);
    if (customerDetails.address)
      $("#service-address").val(customerDetails.address);
    if (customerDetails.date && customerDetails.time) $("#preferred-datetime").val(customerDetails.date + ' ' + customerDetails.time);
    if (customerDetails.instructions)
      $("#special-instructions").val(customerDetails.instructions);
  }

  function storeCustomerDetails() {
    const datetimeValue = $("#preferred-datetime").val();
    const datetime = datetimeValue ? datetimeValue.split(' ') : [null, null];
    customerDetails = {
        name: $("#customer-name").val().trim(),
        email: $("#customer-email").val().trim(),
        phone: $("#customer-phone").val().trim(),
        address: $("#service-address").val().trim(),
        date: datetime[0],
        time: datetime[1],
        instructions: $("#special-instructions").val().trim(),
        has_pets: $('input[name="has_pets"]:checked').val(),
        pet_details: $("#pet-details").val().trim(),
        service_frequency: $('input[name="service_frequency"]:checked').val(),
        property_access: $('input[name="property_access"]:checked').val(),
        access_details: $("#access-details").val().trim(),
    };

    debugLog("Customer details stored", customerDetails);
  }

  function validateCustomerDetails() {
    storeCustomerDetails();

    const $feedback = $("#mobooking-details-feedback");
    const requiredFields = [
      "name",
      "email",
      "phone",
      "address",
      "date",
      "time",
    ];
    const missingFields = requiredFields.filter(
      (field) => !customerDetails[field]
    );

    if (missingFields.length > 0) {
      showFeedback(
        $feedback,
        "error",
        I18N.required_field || "Please fill in all required fields."
      );
      return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(customerDetails.email)) {
      showFeedback(
        $feedback,
        "error",
        I18N.invalid_email || "Please enter a valid email address."
      );
      return false;
    }

    showFeedback($feedback, "", "", true);
    return true;
  }

  // --- STEP 5: REVIEW & CONFIRM ---

  function handleDiscountApply() {
    debugLog("Discount apply initiated");

    const code = $("#discount-code").val().trim();
    const $feedback = $("#discount-feedback");
    const $button = $("#apply-discount-btn");
    const originalBtnText = $button.text();

    if (!code) {
      showFeedback(
        $feedback,
        "error",
        I18N.discount_code_required || "Please enter a discount code."
      );
      return;
    }

    $button.prop("disabled", true).text(I18N.applying || "Applying...");
    showFeedback(
      $feedback,
      "info",
      I18N.applying || "Applying discount...",
      false
    );

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: {
        action: "mobooking_validate_discount",
        nonce: FORM_NONCE,
        discount_code: code,
        tenant_id: TENANT_ID,
        subtotal: calculateSubtotal(),
      },
      success: function (response) {
        debugLog("Discount validation response", response);

        if (response.success && response.data) {
          discountInfo = response.data;
          showFeedback(
            $feedback,
            "success",
            I18N.discount_applied || "Discount applied successfully!"
          );
        } else {
          discountInfo = null;
          showFeedback(
            $feedback,
            "error",
            response.data?.message ||
              I18N.invalid_discount ||
              "Invalid discount code."
          );
        }
        updateLiveSummary();
      },
      error: function (xhr, status, error) {
        debugLog("Discount validation error", { xhr, status, error });
        discountInfo = null;
        showFeedback(
          $feedback,
          "error",
          I18N.network_error || "Network error occurred."
        );
        updateLiveSummary();
      },
      complete: function () {
        $button.prop("disabled", false).text(originalBtnText);
      },
    });
  }

  // --- PRICE CALCULATION & SUMMARY UPDATE ---

  function calculateSubtotal() {
    if (!selectedService) return 0;

    let subtotal = parseFloat(selectedService.price || 0);

    $.each(selectedOptions, function (id, opt) {
      if (opt.priceType === "percentage") {
        subtotal += (parseFloat(selectedService.price || 0) * opt.price) / 100;
      } else {
        subtotal += opt.price;
      }
    });

    return subtotal;
  }

  function updateLiveSummary() {
    if (!selectedService) {
      const emptyMessage =
        "<p>" +
        (I18N.select_service || "Select a service to see summary.") +
        "</p>";
      $(
        "#mobooking-summary-content, #mobooking-summary-content-step4, #mobooking-final-summary"
      ).html(emptyMessage);
      return;
    }

    debugLog("Updating live summary");

    const subtotal = calculateSubtotal();
    let finalTotal = subtotal;
    let summaryHtml = "";

    // Base service
    summaryHtml += `
            <div class="mobooking-summary-item">
                <span>${escapeHtml(selectedService.name)}</span>
                <span>${CURRENCY.symbol}${formatPrice(
      selectedService.price
    )}</span>
            </div>
        `;

    // Options
    $.each(selectedOptions, function (id, opt) {
      summaryHtml += `
                <div class="mobooking-summary-item">
                    <span>+ ${escapeHtml(opt.name)} (${escapeHtml(
        opt.value
      )})</span>
                    <span>${CURRENCY.symbol}${formatPrice(opt.price)}</span>
                </div>
            `;
    });

    // Update pricing elements
    $("#pricing-subtotal").text(CURRENCY.symbol + formatPrice(subtotal));

    // Handle discount
    if (discountInfo) {
      let discountAmount = 0;
      const discType = discountInfo.type || discountInfo.discount_type;
      const discValue = parseFloat(
        discountInfo.value || discountInfo.discount_value || 0
      );

      if (discType === "percentage") {
        discountAmount = (subtotal * discValue) / 100;
      } else {
        discountAmount = discValue;
      }

      discountAmount = Math.min(discountAmount, subtotal);
      finalTotal -= discountAmount;

      summaryHtml += `
                <div class="mobooking-summary-item">
                    <span>${I18N.discount || "Discount"} (${escapeHtml(
        discountInfo.code || discountInfo.discount_code || ""
      )})</span>
                    <span>-${CURRENCY.symbol}${formatPrice(
        discountAmount
      )}</span>
                </div>
            `;

      $("#pricing-discount").text(
        "-" + CURRENCY.symbol + formatPrice(discountAmount)
      );
      $(".discount-applied").removeClass("hidden");
    } else {
      $("#pricing-discount").text("-" + CURRENCY.symbol + "0.00");
      $(".discount-applied").addClass("hidden");
    }

    finalTotal = Math.max(0, finalTotal);
    totalPrice = finalTotal;

    summaryHtml += `
            <div class="mobooking-summary-total">
                <span>${I18N.total || "Total"}:</span>
                <span>${CURRENCY.symbol}${formatPrice(finalTotal)}</span>
            </div>
        `;

    $(
      "#mobooking-summary-content, #mobooking-summary-content-step4, #mobooking-final-summary"
    ).html(summaryHtml);
    $("#pricing-total").text(CURRENCY.symbol + formatPrice(finalTotal));
  }

  // --- NAVIGATION HANDLERS ---

  function handleNextStep(e) {
    const $button = $(e.target);
    const targetStep = parseInt($button.data("step-next"));

    debugLog("Next step requested", {
      current: currentStep,
      target: targetStep,
    });

    // Validate current step
    if (currentStep === 2 && !selectedService) {
      showFeedback(
        $("#mobooking-services-feedback"),
        "error",
        I18N.select_service || "Please select a service."
      );
      return;
    }

    if (currentStep === 3) {
      // TODO: Add validation for service options
    }

    if (currentStep === 7 && !validateCustomerDetails()) {
      return;
    }

    showStep(targetStep);
  }

  function handlePrevStep(e) {
    const $button = $(e.target);
    const targetStep = parseInt($button.data("step-back"));

    debugLog("Previous step requested", {
      current: currentStep,
      target: targetStep,
    });
    showStep(targetStep);
  }

  // --- FINAL SUBMISSION ---

  function handleFinalSubmission() {
    debugLog("Final booking submission initiated");

    const $button = $("#final-submit-btn");
    const originalBtnHtml = $button.html();
    const $feedback = $("#mobooking-review-feedback");

    // Check terms acceptance if required
    if (
      $("#terms-acceptance").length &&
      !$("#terms-acceptance").is(":checked")
    ) {
      showFeedback(
        $feedback,
        "error",
        I18N.terms_required || "Please accept the terms and conditions."
      );
      return;
    }

    $button
      .prop("disabled", true)
      .html(
        '<div class="mobooking-spinner"></div> ' +
          (I18N.submitting || "Submitting...")
      );
    showFeedback(
      $feedback,
      "info",
      I18N.submitting || "Submitting your booking...",
      false
    );

    // Ensure customer details are current
    storeCustomerDetails();

    // Prepare submission data
    const selectedServicesPayload = [
      {
        service_id: selectedService.service_id,
        name: selectedService.name,
        price: selectedService.price,
        configured_options: selectedOptions,
      },
    ];

    const customerDetailsJson = safeJsonEncode(customerDetails);
    const selectedServicesJson = safeJsonEncode(selectedServicesPayload);
    const discountInfoJson = discountInfo ? safeJsonEncode(discountInfo) : null;
    const pricingJson = safeJsonEncode({
      subtotal: calculateSubtotal(),
      discount: discountInfo ? calculateSubtotal() - totalPrice : 0,
      total: totalPrice,
    });

    if (!customerDetailsJson || !selectedServicesJson || !pricingJson) {
      showFeedback(
        $feedback,
        "error",
        I18N.encoding_error || "Error processing form data. Please try again.",
        false
      );
      $button.prop("disabled", false).html(originalBtnHtml);
      return;
    }

    const submissionData = {
      action: "mobooking_create_booking",
      nonce: FORM_NONCE,
      tenant_id: TENANT_ID,
      selected_services: selectedServicesJson,
      customer_details: customerDetailsJson,
      discount_info: discountInfoJson,
      zip_code: $("#mobooking-zip").val() || "",
      country_code: $("#mobooking-country").val() || "",
      pricing: pricingJson,
      time_slot: customerDetails.time,
    };

    debugLog("Submitting booking data", submissionData);

    $.ajax({
      url: AJAX_URL,
      type: "POST",
      data: submissionData,
      dataType: "json",
      timeout: 30000,
      success: function (response) {
        debugLog("Booking submission response", response);

        if (response && response.success && response.data) {
          showStep(8); // Success step

          // Update success details
          $("#success-details").html(`
                        <div class="success-info">
                            <p><strong>${
                              I18N.booking_reference || "Booking Reference"
                            }:</strong> ${escapeHtml(
            response.data.booking_reference || "N/A"
          )}</p>
                            <p><strong>${
                              I18N.service || "Service"
                            }:</strong> ${escapeHtml(selectedService.name)}</p>
                            <p><strong>${
                              I18N.customer || "Customer"
                            }:</strong> ${escapeHtml(customerDetails.name)}</p>
                            <p><strong>${
                              I18N.email || "Email"
                            }:</strong> ${escapeHtml(customerDetails.email)}</p>
                            <p><strong>${I18N.total || "Total"}:</strong> ${
            CURRENCY.symbol
          }${formatPrice(response.data.final_total || totalPrice)}</p>
                            <p style="margin-top: 1rem; color: var(--muted-foreground);">
                                ${
                                  I18N.confirmation_email ||
                                  "You will receive a confirmation email shortly at"
                                } ${escapeHtml(customerDetails.email)}.
                            </p>
                        </div>
                    `);

          // Update progress to show completion
          $(".mobooking-progress-step")
            .removeClass("active")
            .addClass("completed");
          $(".mobooking-progress-line-fill").css("width", "100%");
        } else {
          let errorMessage =
            I18N.booking_failed || "Booking failed. Please try again.";
          if (response && response.data && response.data.message) {
            errorMessage = response.data.message;
          }
          showFeedback($feedback, "error", errorMessage, false);
        }
      },
      error: function (xhr, status, error) {
        debugLog("Booking submission error", { xhr, status, error });

        let errorMessage =
          I18N.network_error || "Network error occurred. Please try again.";

        if (
          xhr.responseJSON &&
          xhr.responseJSON.data &&
          xhr.responseJSON.data.message
        ) {
          errorMessage = xhr.responseJSON.data.message;
        } else if (xhr.responseText) {
          try {
            const serverError = JSON.parse(xhr.responseText);
            if (serverError && serverError.data && serverError.data.message) {
              errorMessage = serverError.data.message;
            }
          } catch (e) {
            if (xhr.status === 0) {
              errorMessage =
                I18N.network_error ||
                "Network error. Please check your connection.";
            } else if (xhr.status === 403) {
              errorMessage =
                I18N.access_denied ||
                "Access denied. Please refresh and try again.";
            } else if (xhr.status === 500) {
              errorMessage =
                I18N.server_error || "Server error. Please try again later.";
            }
          }
        } else if (status === "timeout") {
          errorMessage =
            I18N.timeout_error || "Request timed out. Please try again.";
        }

        showFeedback($feedback, "error", errorMessage, false);
      },
      complete: function () {
        $button.prop("disabled", false).html(originalBtnHtml);
      },
    });
  }

  // --- EVENT BINDING ---

  function bindEvents() {
    debugLog("Binding events");

    // Location check form
    $("#mobooking-location-form").on("submit", handleLocationCheck);

    // Step navigation
    $(document).on("click", "[data-step-next]", handleNextStep);
    $(document).on("click", "[data-step-back]", handlePrevStep);

    // Service selection
    $(document).on("click", ".mobooking-service-card", handleServiceSelect);

    // Customer details form
    $("#mobooking-details-form input, #mobooking-details-form textarea").on(
      "change input",
      storeCustomerDetails
    );

    // Discount application
    if (FORM_CONFIG.allow_discount_codes) {
      $("#apply-discount-btn").on("click", handleDiscountApply);
    }

    // Final submission
    $("#final-submit-btn").on("click", handleFinalSubmission);

    // Progress step clicks (optional navigation)
    $(".mobooking-progress-step").on("click", function () {
      const stepNumber = parseInt($(this).data("step"));
      if (stepNumber < currentStep) {
        showStep(stepNumber);
      }
    });
  }

  // --- INITIALIZATION ---

  function initializeForm() {
    debugLog("Initializing MoBooking form", {
        FORM_CONFIG,
        PRELOADED_SERVICES,
        TENANT_ID,
        AJAX_URL,
        IS_DEBUG
    });

    // Validate required data
    if (!TENANT_ID || !FORM_NONCE) {
      debugLog("Missing required data", { TENANT_ID, FORM_NONCE });
      console.error("MoBooking: Missing required tenant ID or nonce");
      return;
    }

    // Bind events
    bindEvents();

    flatpickr("#preferred-datetime", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            const date = dateStr.split(' ')[0];
            generateTimeSlots(date, instance);
        }
    });

    // Initialize first step
    let initialStep = 1;
    if (!FORM_CONFIG.enable_location_check) {
        initialStep = 2;
    }
    showStep(initialStep);
  }

  function generateTimeSlots(date, instance) {
    const dayOfWeek = new Date(date).getDay();
    const availability = selectedService.availability.find(d => d.day_of_week === dayOfWeek);
    const timePicker = instance.timeContainer;
    timePicker.innerHTML = '';

    if (availability && availability.is_enabled) {
        availability.slots.forEach(function(slot) {
            const option = document.createElement('div');
            option.classList.add('flatpickr-time-option');
            option.textContent = slot.start_time;
            option.addEventListener('click', function() {
                const datetime = date + ' ' + slot.start_time;
                instance.setDate(datetime, true);
                customerDetails.time = slot.start_time;
            });
            timePicker.appendChild(option);
        });
    }
  }

  // --- GLOBAL EXPOSURE ---

  // Expose functions for debugging and external use
  window.MoBookingForm = {
    showStep: showStep,
    updateLiveSummary: updateLiveSummary,
    debugLog: debugLog,
    getCurrentStep: () => currentStep,
    getSelectedService: () => selectedService,
    getSelectedOptions: () => selectedOptions,
    getCustomerDetails: () => customerDetails,
    getTotalPrice: () => totalPrice,
    submitBooking: handleFinalSubmission,
  };

  // Start the form
  initializeForm();
});
