/**
 * Complete MoBooking Booking Form JavaScript
 * Handles all form interactions, AJAX requests, and form validation
 * Fixed jQuery loading and location check disable functionality
 */

// Ensure jQuery is available before initializing
(function () {
  "use strict";

  // Check if jQuery is loaded
  if (typeof jQuery === "undefined") {
    console.error("[MoBooking] jQuery is required but not loaded");

    // Show error message
    const container = document.getElementById(
      "mobooking-booking-form-container"
    );
    if (container) {
      container.innerHTML = `
              <div style="padding: 2rem; text-align: center; background: #f8d7da; color: #721c24; border-radius: 8px; margin: 2rem;">
                  <h3>Loading Error</h3>
                  <p>This booking form requires jQuery to function properly. Please contact the site administrator.</p>
              </div>
          `;
    }
    return;
  }

  // Check for required parameters
  if (typeof moBookingParams === "undefined") {
    console.error("[MoBooking] Required parameters not loaded");

    const container = document.getElementById(
      "mobooking-booking-form-container"
    );
    if (container) {
      jQuery(container).html(`
              <div style="padding: 2rem; text-align: center; background: #f8d7da; color: #721c24; border-radius: 8px; margin: 2rem;">
                  <h3>Configuration Error</h3>
                  <p>The booking form is not properly configured. Please contact the site administrator.</p>
              </div>
          `);
    }
    return;
  }

  // Initialize when DOM is ready
  jQuery(document).ready(function ($) {
    initializeMoBookingForm($);
  });

  function initializeMoBookingForm($) {
    // Global variables
    let currentStep = 1;
    let selectedServices = [];
    let serviceOptions = {};
    let availableTimeSlots = [];
    let appliedDiscount = null;
    let formData = {
      location: "",
      customer: {},
      bookingDetails: {},
    };

    // Initialize form
    initializeForm();

    function initializeForm() {
      console.log("[MoBooking] Initializing booking form");
      console.log("[MoBooking] Form config:", moBookingParams.form_config);

      // Check if location check is enabled
      if (
        !moBookingParams.form_config.enable_location_check ||
        moBookingParams.form_config.enable_location_check === "0"
      ) {
        console.log(
          "[MoBooking] Location check is disabled, skipping to step 2"
        );

        // Hide step 1
        $("#mobooking-step-1").hide();

        // Skip to step 2 (services)
        currentStep = 2;
        showStep(currentStep);
        loadServices();

        // Update progress bar to show step 2 as first step
        updateProgressBar();
      } else {
        console.log("[MoBooking] Location check is enabled, showing step 1");
        showStep(currentStep);
      }

      // Initialize datepicker
      initializeDatepicker();

      // Bind event handlers
      bindEventHandlers();
    }

    function initializeDatepicker() {
      $("#preferred-date").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: 0, // Today or later
        maxDate: "+3M", // Up to 3 months ahead
        beforeShowDay: function (date) {
          // You can add logic here to disable specific dates
          return [true, ""];
        },
        onSelect: function (dateText) {
          loadAvailableTimeSlots(dateText);
        },
      });
    }

    function bindEventHandlers() {
      // Location form submission
      $("#mobooking-location-form").on("submit", function (e) {
        e.preventDefault();
        handleLocationSubmission();
      });

      // Service selection
      $(document).on("change", ".mobooking-service-checkbox", function () {
        handleServiceSelection($(this));
      });

      // Service options change
      $(document).on("change", ".mobooking-option-input", function () {
        updateServiceOptions();
        updatePricingSummary();
      });

      // Navigation buttons
      $(".mobooking-btn-next").on("click", function () {
        const nextStep = currentStep + 1;
        navigateToStep(nextStep);
      });

      $(".mobooking-btn-back").on("click", function () {
        const prevStep = currentStep - 1;
        if (prevStep >= 1) {
          navigateToStep(prevStep);
        }
      });

      // Details form submission
      $("#mobooking-details-form").on("submit", function (e) {
        e.preventDefault();
        if (validateCurrentStep()) {
          navigateToStep(5); // Go to review step
        }
      });

      // Discount code application
      $("#mobooking-apply-discount-btn").on("click", function () {
        applyDiscountCode();
      });

      // Final booking submission
      $("#mobooking-confirm-booking-btn").on("click", function () {
        submitBooking();
      });

      // Phone number formatting
      $("#customer-phone").on("input", function () {
        formatPhoneNumber($(this));
      });
    }

    function showStep(stepNumber) {
      console.log("[MoBooking] Showing step:", stepNumber);

      // Hide all steps
      $(".mobooking-step").hide();

      // Show current step
      $("#mobooking-step-" + stepNumber).show();

      currentStep = stepNumber;
      updateProgressBar();
    }

    function updateProgressBar() {
      const totalSteps =
        moBookingParams.form_config.enable_location_check === "1" ? 5 : 4;
      let displayStep = currentStep;

      // Adjust display step if location check is disabled
      if (moBookingParams.form_config.enable_location_check !== "1") {
        displayStep = currentStep - 1;
        if (displayStep < 1) displayStep = 1;
      }

      const progressPercentage = (displayStep / totalSteps) * 100;

      $("#mobooking-progress-bar").css("width", progressPercentage + "%");
      $("#mobooking-progress-text").text(
        "Step " + displayStep + " of " + totalSteps
      );
    }

    function handleLocationSubmission() {
      const location = $("#mobooking-location-input").val().trim();

      if (!location) {
        showFeedback("error", moBookingParams.i18n.error_location_required);
        return;
      }

      showLoading(true);

      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_check_location",
          nonce: moBookingParams.nonce,
          location: location,
          tenant_id: moBookingParams.tenant_id,
        },
        success: function (response) {
          showLoading(false);

          if (response.success) {
            formData.location = location;
            showFeedback("success", response.data.message);

            setTimeout(function () {
              navigateToStep(2);
            }, 1000);
          } else {
            showFeedback(
              "error",
              response.data.message || moBookingParams.i18n.location_not_covered
            );
          }
        },
        error: function () {
          showLoading(false);
          showFeedback("error", moBookingParams.i18n.error_generic);
        },
      });
    }

    function loadServices() {
      console.log(
        "[MoBooking] Loading services for tenant:",
        moBookingParams.tenant_id
      );

      showLoading(true);

      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_services",
          nonce: moBookingParams.nonce,
          tenant_id: moBookingParams.tenant_id,
        },
        success: function (response) {
          showLoading(false);

          if (response.success && response.data.services) {
            displayServices(response.data.services);
          } else {
            showFeedback(
              "error",
              response.data.message ||
                moBookingParams.i18n.error_loading_services
            );
          }
        },
        error: function () {
          showLoading(false);
          showFeedback("error", moBookingParams.i18n.error_generic);
        },
      });
    }

    function displayServices(services) {
      const $servicesList = $("#mobooking-services-list");
      $servicesList.empty();

      if (!services || services.length === 0) {
        $servicesList.html(
          '<p class="mobooking-no-services">' +
            moBookingParams.i18n.no_services_available +
            "</p>"
        );
        return;
      }

      services.forEach(function (service) {
        const serviceHtml = `
                  <div class="mobooking-service-item" data-service-id="${
                    service.id
                  }">
                      <div class="mobooking-service-content">
                          <div class="mobooking-service-header">
                              <h3 class="mobooking-service-name">${
                                service.name
                              }</h3>
                              <div class="mobooking-service-price">${formatPrice(
                                service.price
                              )}</div>
                          </div>
                          ${
                            service.description
                              ? `<p class="mobooking-service-description">${service.description}</p>`
                              : ""
                          }
                          <div class="mobooking-service-duration">
                              <i class="fas fa-clock"></i>
                              ${service.duration} minutes
                          </div>
                      </div>
                      <div class="mobooking-service-actions">
                          <label class="mobooking-checkbox-wrapper">
                              <input type="checkbox" class="mobooking-service-checkbox" 
                                     data-service-id="${service.id}" 
                                     data-service-name="${service.name}"
                                     data-service-price="${service.price}"
                                     data-service-duration="${
                                       service.duration
                                     }">
                              <span class="mobooking-checkmark"></span>
                          </label>
                      </div>
                  </div>
              `;
        $servicesList.append(serviceHtml);
      });
    }

    function handleServiceSelection($checkbox) {
      const serviceId = $checkbox.data("service-id");
      const serviceName = $checkbox.data("service-name");
      const servicePrice = parseFloat($checkbox.data("service-price")) || 0;
      const serviceDuration = parseInt($checkbox.data("service-duration")) || 0;

      if ($checkbox.is(":checked")) {
        // Add service to selection
        selectedServices.push({
          id: serviceId,
          name: serviceName,
          price: servicePrice,
          duration: serviceDuration,
        });
      } else {
        // Remove service from selection
        selectedServices = selectedServices.filter(function (service) {
          return service.id !== serviceId;
        });

        // Remove service options for this service
        delete serviceOptions[serviceId];
      }

      updatePricingSummary();
      console.log("[MoBooking] Selected services:", selectedServices);
    }

    function loadServiceOptions() {
      if (selectedServices.length === 0) return;

      showLoading(true);

      const serviceIds = selectedServices.map(function (service) {
        return service.id;
      });

      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_service_options",
          nonce: moBookingParams.nonce,
          service_ids: serviceIds,
          tenant_id: moBookingParams.tenant_id,
        },
        success: function (response) {
          showLoading(false);

          if (response.success) {
            displayServiceOptions(response.data.options || {});
          } else {
            showFeedback(
              "error",
              response.data.message ||
                moBookingParams.i18n.error_loading_options
            );
          }
        },
        error: function () {
          showLoading(false);
          showFeedback("error", moBookingParams.i18n.error_generic);
        },
      });
    }

    function displayServiceOptions(options) {
      const $optionsContainer = $("#mobooking-service-options-container");
      $optionsContainer.empty();

      if (!options || Object.keys(options).length === 0) {
        // No options available, proceed to next step
        navigateToStep(4);
        return;
      }

      selectedServices.forEach(function (service) {
        const serviceOptions = options[service.id];

        if (serviceOptions && serviceOptions.length > 0) {
          let serviceOptionsHtml = `
                      <div class="mobooking-service-options-group" data-service-id="${service.id}">
                          <h3 class="mobooking-service-options-title">${service.name} - Options</h3>
                  `;

          serviceOptions.forEach(function (option) {
            serviceOptionsHtml += createOptionInput(option, service.id);
          });

          serviceOptionsHtml += "</div>";
          $optionsContainer.append(serviceOptionsHtml);
        }
      });
    }

    function createOptionInput(option, serviceId) {
      let inputHtml = "";
      const isRequired = option.required === "1" ? "required" : "";
      const requiredStar =
        option.required === "1"
          ? '<span class="mobooking-required">*</span>'
          : "";

      switch (option.type) {
        case "checkbox":
          inputHtml = `
                      <div class="mobooking-option-item">
                          <label class="mobooking-option-label">
                              <input type="checkbox" class="mobooking-option-input" 
                                     data-service-id="${serviceId}" 
                                     data-option-id="${option.id}"
                                     data-option-price="${option.price}"
                                     data-option-type="${
                                       option.type
                                     }" ${isRequired}>
                              ${option.name} ${requiredStar}
                              ${
                                option.price > 0
                                  ? `<span class="mobooking-option-price">(+${formatPrice(
                                      option.price
                                    )})</span>`
                                  : ""
                              }
                          </label>
                          ${
                            option.description
                              ? `<p class="mobooking-option-description">${option.description}</p>`
                              : ""
                          }
                      </div>
                  `;
          break;

        case "select":
          let selectOptions = "";
          if (option.options) {
            option.options.forEach(function (selectOption) {
              selectOptions += `<option value="${
                selectOption.value
              }" data-price="${selectOption.price || 0}">${
                selectOption.label
              }</option>`;
            });
          }

          inputHtml = `
                      <div class="mobooking-option-item">
                          <label class="mobooking-option-label">
                              ${option.name} ${requiredStar}
                              ${
                                option.price > 0
                                  ? `<span class="mobooking-option-price">(+${formatPrice(
                                      option.price
                                    )})</span>`
                                  : ""
                              }
                          </label>
                          <select class="mobooking-option-input" 
                                  data-service-id="${serviceId}" 
                                  data-option-id="${option.id}"
                                  data-option-price="${option.price}"
                                  data-option-type="${
                                    option.type
                                  }" ${isRequired}>
                              <option value="">Please select...</option>
                              ${selectOptions}
                          </select>
                          ${
                            option.description
                              ? `<p class="mobooking-option-description">${option.description}</p>`
                              : ""
                          }
                      </div>
                  `;
          break;

        case "text":
        case "number":
          inputHtml = `
                      <div class="mobooking-option-item">
                          <label class="mobooking-option-label">
                              ${option.name} ${requiredStar}
                              ${
                                option.price > 0
                                  ? `<span class="mobooking-option-price">(+${formatPrice(
                                      option.price
                                    )})</span>`
                                  : ""
                              }
                          </label>
                          <input type="${
                            option.type
                          }" class="mobooking-option-input" 
                                 data-service-id="${serviceId}" 
                                 data-option-id="${option.id}"
                                 data-option-price="${option.price}"
                                 data-option-type="${option.type}" 
                                 placeholder="${
                                   option.placeholder || ""
                                 }" ${isRequired}>
                          ${
                            option.description
                              ? `<p class="mobooking-option-description">${option.description}</p>`
                              : ""
                          }
                      </div>
                  `;
          break;

        case "textarea":
          inputHtml = `
                      <div class="mobooking-option-item">
                          <label class="mobooking-option-label">
                              ${option.name} ${requiredStar}
                          </label>
                          <textarea class="mobooking-option-input" 
                                    data-service-id="${serviceId}" 
                                    data-option-id="${option.id}"
                                    data-option-price="${option.price}"
                                    data-option-type="${option.type}" 
                                    placeholder="${
                                      option.placeholder || ""
                                    }" ${isRequired}></textarea>
                          ${
                            option.description
                              ? `<p class="mobooking-option-description">${option.description}</p>`
                              : ""
                          }
                      </div>
                  `;
          break;
      }

      return inputHtml;
    }

    function updateServiceOptions() {
      $(".mobooking-option-input").each(function () {
        const $input = $(this);
        const serviceId = $input.data("service-id");
        const optionId = $input.data("option-id");
        const optionPrice = parseFloat($input.data("option-price")) || 0;
        const optionType = $input.data("option-type");

        if (!serviceOptions[serviceId]) {
          serviceOptions[serviceId] = {};
        }

        let value = null;
        let priceImpact = 0;

        switch (optionType) {
          case "checkbox":
            if ($input.is(":checked")) {
              value = true;
              priceImpact = optionPrice;
            } else {
              value = false;
              priceImpact = 0;
            }
            break;

          case "select":
            value = $input.val();
            if (value) {
              const selectedOption = $input.find("option:selected");
              priceImpact =
                parseFloat(selectedOption.data("price")) || optionPrice;
            }
            break;

          case "number":
            value = parseFloat($input.val()) || 0;
            priceImpact = value * optionPrice; // Multiply by quantity
            break;

          case "text":
          case "textarea":
            value = $input.val();
            priceImpact = value ? optionPrice : 0;
            break;
        }

        serviceOptions[serviceId][optionId] = {
          value: value,
          priceImpact: priceImpact,
        };
      });

      console.log("[MoBooking] Service options updated:", serviceOptions);
    }

    function loadAvailableTimeSlots(date) {
      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_time_slots",
          nonce: moBookingParams.nonce,
          date: date,
          tenant_id: moBookingParams.tenant_id,
          services: selectedServices.map((s) => s.id),
        },
        success: function (response) {
          if (response.success) {
            displayTimeSlots(response.data.slots);
          } else {
            showFeedback(
              "error",
              response.data.message || moBookingParams.i18n.error_loading_slots
            );
          }
        },
        error: function () {
          showFeedback("error", moBookingParams.i18n.error_generic);
        },
      });
    }

    function displayTimeSlots(slots) {
      const $timeSlotsContainer = $("#mobooking-time-slots");
      $timeSlotsContainer.empty();

      if (!slots || slots.length === 0) {
        $timeSlotsContainer.html(
          '<p class="mobooking-no-slots">No available time slots for this date.</p>'
        );
        return;
      }

      slots.forEach(function (slot) {
        const $timeSlot = $(`
                  <label class="mobooking-time-slot">
                      <input type="radio" name="preferred-time" value="${
                        slot.time
                      }" ${slot.available ? "" : "disabled"}>
                      <span class="mobooking-time-slot-text">${
                        slot.display
                      }</span>
                  </label>
              `);

        $timeSlotsContainer.append($timeSlot);
      });
    }

    function applyDiscountCode() {
      const discountCode = $("#mobooking-discount-code").val().trim();

      if (!discountCode) {
        showFeedback("error", moBookingParams.i18n.error_discount_required);
        return;
      }

      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_validate_discount",
          nonce: moBookingParams.nonce,
          code: discountCode,
          tenant_id: moBookingParams.tenant_id,
          subtotal: calculateSubtotal(),
        },
        success: function (response) {
          if (response.success) {
            appliedDiscount = response.data.discount;
            showFeedback("success", response.data.message);
            updatePricingSummary();

            // Disable discount input and button
            $("#mobooking-discount-code").prop("disabled", true);
            $("#mobooking-apply-discount-btn")
              .prop("disabled", true)
              .text("Applied");
          } else {
            showFeedback(
              "error",
              response.data.message || moBookingParams.i18n.discount_invalid
            );
          }
        },
        error: function () {
          showFeedback("error", moBookingParams.i18n.error_generic);
        },
      });
    }

    function calculateSubtotal() {
      let subtotal = 0;

      // Add service prices
      selectedServices.forEach(function (service) {
        subtotal += service.price;
      });

      // Add option prices
      Object.keys(serviceOptions).forEach(function (serviceId) {
        const options = serviceOptions[serviceId];
        Object.keys(options).forEach(function (optionId) {
          subtotal += options[optionId].priceImpact || 0;
        });
      });

      return subtotal;
    }

    function calculateDiscount(subtotal) {
      if (!appliedDiscount) return 0;

      if (appliedDiscount.type === "percentage") {
        return (subtotal * appliedDiscount.amount) / 100;
      } else {
        return Math.min(appliedDiscount.amount, subtotal);
      }
    }

    function updatePricingSummary() {
      const subtotal = calculateSubtotal();
      const discountAmount = calculateDiscount(subtotal);
      const finalTotal = Math.max(0, subtotal - discountAmount);

      // Update pricing display
      $("#mobooking-subtotal").text(formatPrice(subtotal));
      $("#mobooking-discount-amount").text(formatPrice(discountAmount));
      $("#mobooking-final-total").text(formatPrice(finalTotal));

      // Show/hide discount row
      const $discountRow = $("#mobooking-discount-row");
      if (discountAmount > 0) {
        $discountRow.show();
      } else {
        $discountRow.hide();
      }
    }

    function updateBookingSummary() {
      const $summaryContainer = $("#mobooking-booking-summary");
      let summaryHtml = "";

      // Location (if enabled)
      if (
        moBookingParams.form_config.enable_location_check === "1" &&
        formData.location
      ) {
        summaryHtml += `
                  <div class="mobooking-summary-item">
                      <strong>Location:</strong> ${formData.location}
                  </div>
              `;
      }

      // Services
      summaryHtml +=
        '<div class="mobooking-summary-item"><strong>Services:</strong><ul>';
      selectedServices.forEach(function (service) {
        summaryHtml += `<li>${service.name} - ${formatPrice(
          service.price
        )}</li>`;
      });
      summaryHtml += "</ul></div>";

      // Options (if any)
      const hasOptions = Object.keys(serviceOptions).some(
        (serviceId) => Object.keys(serviceOptions[serviceId]).length > 0
      );

      if (hasOptions) {
        summaryHtml +=
          '<div class="mobooking-summary-item"><strong>Options:</strong><ul>';
        Object.keys(serviceOptions).forEach(function (serviceId) {
          const options = serviceOptions[serviceId];
          Object.keys(options).forEach(function (optionId) {
            const option = options[optionId];
            if (option.value && option.priceImpact > 0) {
              summaryHtml += `<li>Option: ${option.value} - ${formatPrice(
                option.priceImpact
              )}</li>`;
            }
          });
        });
        summaryHtml += "</ul></div>";
      }

      // Customer details
      const customerName = $("#customer-name").val();
      const customerEmail = $("#customer-email").val();
      const customerPhone = $("#customer-phone").val();
      const serviceAddress = $("#service-address").val();
      const preferredDate = $("#preferred-date").val();
      const preferredTime = $('input[name="preferred-time"]:checked').val();
      const specialInstructions = $("#special-instructions").val();

      if (customerName) {
        summaryHtml += `<div class="mobooking-summary-item"><strong>Name:</strong> ${customerName}</div>`;
      }
      if (customerEmail) {
        summaryHtml += `<div class="mobooking-summary-item"><strong>Email:</strong> ${customerEmail}</div>`;
      }
      if (customerPhone) {
        summaryHtml += `<div class="mobooking-summary-item"><strong>Phone:</strong> ${customerPhone}</div>`;
      }
      if (serviceAddress) {
        summaryHtml += `<div class="mobooking-summary-item"><strong>Address:</strong> ${serviceAddress}</div>`;
      }
      if (preferredDate) {
        summaryHtml += `<div class="mobooking-summary-item"><strong>Date:</strong> ${preferredDate}</div>`;
      }
      if (preferredTime) {
        summaryHtml += `<div class="mobooking-summary-item"><strong>Time:</strong> ${preferredTime}</div>`;
      }
      if (specialInstructions) {
        summaryHtml += `<div class="mobooking-summary-item"><strong>Special Instructions:</strong> ${specialInstructions}</div>`;
      }

      $summaryContainer.html(summaryHtml);
    }

    function submitBooking() {
      if (!validateCurrentStep()) return;

      const $submitBtn = $("#mobooking-confirm-booking-btn");
      $submitBtn
        .prop("disabled", true)
        .html('<i class="fas fa-spinner fa-spin"></i> Processing...');

      // Collect all form data
      const bookingData = {
        tenant_id: moBookingParams.tenant_id,
        location: formData.location,
        services: selectedServices,
        options: serviceOptions,
        customer: {
          name: $("#customer-name").val(),
          email: $("#customer-email").val(),
          phone: $("#customer-phone").val(),
        },
        booking_details: {
          service_address: $("#service-address").val(),
          preferred_date: $("#preferred-date").val(),
          preferred_time: $('input[name="preferred-time"]:checked').val(),
          special_instructions: $("#special-instructions").val(),
        },
        pricing: {
          subtotal: calculateSubtotal(),
          discount: appliedDiscount,
          discount_amount: calculateDiscount(calculateSubtotal()),
          final_total: Math.max(
            0,
            calculateSubtotal() - calculateDiscount(calculateSubtotal())
          ),
        },
      };

      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_submit_booking",
          nonce: moBookingParams.nonce,
          booking_data: JSON.stringify(bookingData),
        },
        success: function (response) {
          if (response.success) {
            // Show success message
            $(".mobooking-booking-form-container").html(`
                          <div class="mobooking-success-message">
                              <i class="fas fa-check-circle"></i>
                              <h2>Booking Confirmed!</h2>
                              <p>${response.data.message}</p>
                              ${
                                response.data.booking_reference
                                  ? `<p><strong>Booking Reference:</strong> ${response.data.booking_reference}</p>`
                                  : ""
                              }
                          </div>
                      `);
          } else {
            showFeedback(
              "error",
              response.data.message || moBookingParams.i18n.error_generic
            );
            $submitBtn
              .prop("disabled", false)
              .html('<i class="fas fa-check"></i> Confirm Booking');
          }
        },
        error: function () {
          showFeedback("error", moBookingParams.i18n.error_generic);
          $submitBtn
            .prop("disabled", false)
            .html('<i class="fas fa-check"></i> Confirm Booking');
        },
      });
    }

    function navigateToStep(stepNumber) {
      // Validate current step before proceeding
      if (stepNumber > currentStep && !validateCurrentStep()) {
        return;
      }

      // Special handling for step transitions
      if (stepNumber === 2 && currentStep === 1) {
        loadServices();
      } else if (stepNumber === 3 && currentStep === 2) {
        if (selectedServices.length === 0) {
          showFeedback("error", moBookingParams.i18n.error_services);
          return;
        }
        loadServiceOptions();
      } else if (stepNumber === 5 && currentStep === 4) {
        updateBookingSummary();
        updatePricingSummary();
      }

      currentStep = stepNumber;
      showStep(stepNumber);
    }

    function validateCurrentStep() {
      switch (currentStep) {
        case 1:
          if (moBookingParams.form_config.enable_location_check !== "1") {
            return true; // Skip validation if location check is disabled
          }
          return $("#mobooking-location-input").val().trim() !== "";
        case 2:
          return selectedServices.length > 0;
        case 3:
          return validateRequiredOptions();
        case 4:
          return $("#mobooking-details-form")[0].checkValidity();
        case 5:
          return true; // Final validation happens in submission
        default:
          return true;
      }
    }

    function validateRequiredOptions() {
      let isValid = true;

      $(".mobooking-option-input[required]").each(function () {
        const $input = $(this);
        const value = $input.val();
        const type = $input.attr("type");

        if (type === "checkbox") {
          if (!$input.is(":checked")) {
            isValid = false;
            $input
              .closest(".mobooking-option-item")
              .addClass("mobooking-error");
          } else {
            $input
              .closest(".mobooking-option-item")
              .removeClass("mobooking-error");
          }
        } else {
          if (!value || value.trim() === "") {
            isValid = false;
            $input
              .closest(".mobooking-option-item")
              .addClass("mobooking-error");
          } else {
            $input
              .closest(".mobooking-option-item")
              .removeClass("mobooking-error");
          }
        }
      });

      if (!isValid) {
        showFeedback("error", moBookingParams.i18n.error_required_options);
      }

      return isValid;
    }

    function showFeedback(type, message) {
      const $feedback = $("#mobooking-feedback");
      $feedback.removeClass(
        "mobooking-feedback-success mobooking-feedback-error mobooking-feedback-warning"
      );
      $feedback.addClass("mobooking-feedback-" + type);
      $feedback.html(message).show();

      // Auto-hide success messages
      if (type === "success") {
        setTimeout(function () {
          $feedback.fadeOut();
        }, 5000);
      }

      // Scroll to feedback
      $feedback[0].scrollIntoView({ behavior: "smooth", block: "nearest" });
    }

    function showLoading(show) {
      if (show) {
        $(".mobooking-loading").show();
      } else {
        $(".mobooking-loading").hide();
      }
    }

    function formatPrice(price) {
      const numPrice = parseFloat(price) || 0;
      return moBookingParams.currency_symbol + numPrice.toFixed(2);
    }

    function formatPhoneNumber($input) {
      let value = $input.val().replace(/\D/g, "");

      if (value.length >= 6) {
        if (value.length === 10) {
          value = value.replace(/(\d{3})(\d{3})(\d{4})/, "($1) $2-$3");
        } else if (value.length === 11 && value[0] === "1") {
          value = value.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, "$1-$2-$3-$4");
        }
      }

      $input.val(value);
    }

    // Prevent form submission on Enter key (except in textareas)
    $(document).on(
      "keypress",
      ".mobooking-booking-form-container input:not(textarea)",
      function (e) {
        if (e.which === 13) {
          // Enter key
          e.preventDefault();

          // Find the current step's continue button and click it
          const currentStep = $(".mobooking-step:visible");
          const continueBtn = currentStep
            .find(".mobooking-btn-primary:visible")
            .last();

          if (continueBtn.length && !continueBtn.prop("disabled")) {
            continueBtn.click();
          }
        }
      }
    );

    // Initialize tooltips if jQuery UI tooltip is available
    if ($.fn.tooltip) {
      $(".mobooking-tooltip").tooltip();
    }

    // Add smooth transitions
    $(".mobooking-step").hide();

    console.log("[MoBooking] Booking form initialization complete");
  }
})();
