/**
 * Complete MoBooking Booking Form JavaScript
 * File: assets/js/booking-form-complete.js
 *
 * Handles all form interactions, validation, AJAX requests,
 * and provides a smooth user experience for the booking process.
 */

(function ($) {
  "use strict";

  // Initialize when document is ready
  $(document).ready(function () {
    new MoBookingForm();
  });

  /**
   * Main MoBooking Form Class
   */
  function MoBookingForm() {
    this.init();
  }

  MoBookingForm.prototype = {
    // Form state
    currentStep: 1,
    selectedServices: [],
    serviceOptions: {},
    availableTimeSlots: [],
    appliedDiscount: null,
    formData: {
      location: "",
      customer: {},
      bookingDetails: {},
    },

    // DOM elements
    $container: null,
    $progressBar: null,
    $progressText: null,
    $feedback: null,

    /**
     * Initialize the booking form
     */
    init: function () {
      console.log("[MoBooking] Initializing booking form...");

      // Check if required parameters are available
      if (typeof moBookingParams === "undefined") {
        console.error("[MoBooking] Missing required parameters");
        this.showError("Configuration error: Missing required parameters");
        return;
      }

      // Cache DOM elements
      this.cacheDOMElements();

      // Initialize form components
      this.initializeDatePicker();
      this.bindEventHandlers();

      // Start the form flow
      this.startFormFlow();

      console.log("[MoBooking] Form initialized successfully");
    },

    /**
     * Cache frequently used DOM elements
     */
    cacheDOMElements: function () {
      this.$container = $("#mobooking-booking-form-container");
      this.$progressBar = $("#mobooking-progress-bar");
      this.$progressText = $("#mobooking-progress-text");
      this.$feedback = $("#mobooking-feedback");
    },

    /**
     * Initialize jQuery UI Datepicker
     */
    initializeDatePicker: function () {
      const self = this;

      $("#preferred-date").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: 1, // Tomorrow onwards
        maxDate: "+3M", // Up to 3 months ahead
        showAnim: "fadeIn",
        showOtherMonths: true,
        selectOtherMonths: false,
        beforeShowDay: function (date) {
          // You can add custom logic here to disable specific dates
          const day = date.getDay();
          // Example: Disable Sundays (day === 0)
          return [day !== 0, day === 0 ? "unavailable" : "available"];
        },
        onSelect: function (dateText) {
          console.log("[MoBooking] Date selected:", dateText);
          self.loadAvailableTimeSlots(dateText);
        },
      });
    },

    /**
     * Bind all event handlers
     */
    bindEventHandlers: function () {
      const self = this;

      // Location form submission
      $("#mobooking-location-form").on("submit", function (e) {
        e.preventDefault();
        self.handleLocationSubmission();
      });

      // Step navigation
      $("#mobooking-step-2-back").on("click", () => this.navigateToStep(1));
      $("#mobooking-step-2-continue").on("click", () => this.navigateToStep(3));
      $("#mobooking-step-3-back").on("click", () => this.navigateToStep(2));
      $("#mobooking-step-3-continue").on("click", () => this.navigateToStep(4));
      $("#mobooking-step-4-back").on("click", () => this.navigateToStep(3));
      $("#mobooking-step-4-continue").on("click", () => this.navigateToStep(5));
      $("#mobooking-step-5-back").on("click", () => this.navigateToStep(4));

      // Form submissions
      $("#mobooking-submit-booking").on("click", function (e) {
        e.preventDefault();
        self.handleBookingSubmission();
      });

      // Service selection
      $(document).on("change", ".mobooking-service-checkbox", function () {
        self.handleServiceSelection();
      });

      // Service options
      $(document).on(
        "change input keyup",
        ".mobooking-option-input",
        function () {
          self.handleOptionChange($(this));
        }
      );

      // Customer details validation
      $(
        "#mobooking-details-form input, #mobooking-details-form textarea, #mobooking-details-form select"
      ).on("blur change", function () {
        self.validateCustomerDetails();
      });

      // Discount code
      $("#apply-discount-btn").on("click", function () {
        self.handleDiscountApplication();
      });

      // Enter key handling for discount code
      $("#discount-code").on("keypress", function (e) {
        if (e.which === 13) {
          e.preventDefault();
          self.handleDiscountApplication();
        }
      });

      // Service card click handling
      $(document).on("click", ".mobooking-service-card", function (e) {
        if (!$(e.target).is('input[type="checkbox"]')) {
          const $checkbox = $(this).find(".mobooking-service-checkbox");
          $checkbox
            .prop("checked", !$checkbox.prop("checked"))
            .trigger("change");
        }
      });
    },

    /**
     * Start the form flow based on configuration
     */
    startFormFlow: function () {
      if (!moBookingParams.form_config.enable_location_check) {
        console.log("[MoBooking] Skipping location check, going to services");
        this.currentStep = 2;
        this.loadServices();
      } else {
        console.log("[MoBooking] Starting with location check");
      }

      this.showStep(this.currentStep);
    },

    /**
     * Handle location form submission
     */
    handleLocationSubmission: function () {
      const location = $("#mobooking-location-input").val().trim();

      if (!location) {
        this.showFeedback("error", moBookingParams.i18n.error_location);
        return;
      }

      console.log("[MoBooking] Checking service area for:", location);
      this.showLoading("#mobooking-step-1", "Checking service area...");

      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_check_service_area",
          nonce: moBookingParams.nonce,
          tenant_user_id: moBookingParams.tenant_user_id,
          location: location,
        },
        success: (response) => {
          this.hideLoading("#mobooking-step-1");

          if (response.success) {
            console.log("[MoBooking] Service area check passed");
            this.formData.location = location;
            this.navigateToStep(2);
          } else {
            this.showFeedback(
              "error",
              response.data.message || "Service not available in your area."
            );
          }
        },
        error: () => {
          this.hideLoading("#mobooking-step-1");
          this.showFeedback("error", moBookingParams.i18n.error_generic);
        },
      });
    },

    /**
     * Load available services for the tenant
     */
    loadServices: function () {
      console.log("[MoBooking] Loading services...");
      this.showLoading("#mobooking-services-list", "Loading services...");

      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_public_services",
          nonce: moBookingParams.nonce,
          tenant_user_id: moBookingParams.tenant_user_id,
        },
        success: (response) => {
          this.hideLoading("#mobooking-services-list");

          if (response.success && response.data.services) {
            console.log(
              "[MoBooking] Services loaded:",
              response.data.services.length
            );
            this.renderServices(response.data.services);
          } else {
            this.showFeedback("error", "No services available.");
          }
        },
        error: () => {
          this.hideLoading("#mobooking-services-list");
          this.showFeedback("error", moBookingParams.i18n.error_generic);
        },
      });
    },

    /**
     * Render services in the UI
     */
    renderServices: function (services) {
      let html = "";

      services.forEach((service) => {
        const priceDisplay = moBookingParams.form_config.show_pricing
          ? `<div class="mobooking-service-price">${
              moBookingParams.currency.symbol
            }${parseFloat(service.price).toFixed(2)}</div>`
          : "";

        html += `
                    <div class="mobooking-service-card" data-service-id="${
                      service.service_id
                    }">
                        <input type="checkbox" class="mobooking-service-checkbox" value="${
                          service.service_id
                        }" 
                               data-name="${this.escapeHtml(service.name)}" 
                               data-price="${service.price}" 
                               data-duration="${service.duration}">
                        
                        <div class="mobooking-service-header">
                            <div class="mobooking-service-icon">
                                ${
                                  service.icon
                                    ? `<i class="${service.icon}"></i>`
                                    : '<i class="fas fa-star"></i>'
                                }
                            </div>
                            <div class="mobooking-service-name">${this.escapeHtml(
                              service.name
                            )}</div>
                            ${priceDisplay}
                        </div>
                        
                        ${
                          service.description
                            ? `<div class="mobooking-service-description">${this.escapeHtml(
                                service.description
                              )}</div>`
                            : ""
                        }
                        
                        <div class="mobooking-service-duration">
                            <i class="fas fa-clock"></i> ${
                              service.duration
                            } minutes
                        </div>
                    </div>
                `;
      });

      $("#mobooking-services-list").html(html).show();
      $("#mobooking-services-loading").hide();
    },

    /**
     * Handle service selection changes
     */
    handleServiceSelection: function () {
      const $checkedBoxes = $(".mobooking-service-checkbox:checked");

      // Update visual selection state
      $(".mobooking-service-card").removeClass("selected");
      $checkedBoxes.each(function () {
        $(this).closest(".mobooking-service-card").addClass("selected");
      });

      // Update selected services array
      this.selectedServices = [];
      $checkedBoxes.each((index, checkbox) => {
        const $checkbox = $(checkbox);
        this.selectedServices.push({
          service_id: parseInt($checkbox.val()),
          name: $checkbox.data("name"),
          price: parseFloat($checkbox.data("price")),
          duration: parseInt($checkbox.data("duration")),
          configured_options: {},
        });
      });

      console.log("[MoBooking] Selected services:", this.selectedServices);

      // Update continue button state
      const $continueBtn = $("#mobooking-step-2-continue");
      $continueBtn.prop("disabled", this.selectedServices.length === 0);

      // Update summary
      this.updateSidebarSummary();

      // Store in hidden field
      $("#mobooking-selected-services").val(
        JSON.stringify(this.selectedServices)
      );
    },

    /**
     * Load service options for selected services
     */
    loadServiceOptions: function () {
      if (this.selectedServices.length === 0) {
        $("#mobooking-service-options").html("<p>No services selected.</p>");
        return;
      }

      console.log("[MoBooking] Loading service options...");
      this.showLoading(
        "#mobooking-service-options",
        "Loading service options..."
      );

      const serviceIds = this.selectedServices.map((s) => s.service_id);

      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_service_options",
          nonce: moBookingParams.nonce,
          tenant_user_id: moBookingParams.tenant_user_id,
          service_ids: serviceIds,
        },
        success: (response) => {
          this.hideLoading("#mobooking-service-options");

          if (response.success) {
            console.log("[MoBooking] Service options loaded");
            this.renderServiceOptions(response.data.options || {});
          } else {
            $("#mobooking-service-options").html(
              "<p>No additional options available.</p>"
            );
          }
        },
        error: () => {
          this.hideLoading("#mobooking-service-options");
          this.showFeedback("error", moBookingParams.i18n.error_generic);
        },
      });
    },

    /**
     * Render service options in the UI
     */
    renderServiceOptions: function (options) {
      let html = "";

      this.selectedServices.forEach((service) => {
        const serviceOptions = options[service.service_id] || [];

        if (serviceOptions.length > 0) {
          html += `<div class="mobooking-service-options-section">
                        <h3>${this.escapeHtml(service.name)} Options</h3>`;

          serviceOptions.forEach((option) => {
            html += this.renderSingleOption(option, service.service_id);
          });

          html += "</div>";
        }
      });

      if (html === "") {
        html = "<p>No additional options available for selected services.</p>";
      }

      $("#mobooking-service-options").html(html);
    },

    /**
     * Render a single service option
     */
    renderSingleOption: function (option, serviceId) {
      let html = `
                <div class="mobooking-service-option" data-option-id="${
                  option.option_id
                }" data-service-id="${serviceId}">
                    <div class="mobooking-option-header">
                        <div class="mobooking-option-name">${this.escapeHtml(
                          option.name
                        )}</div>
                        ${
                          option.is_required
                            ? '<span class="mobooking-option-required">Required</span>'
                            : ""
                        }
                    </div>
            `;

      if (option.description) {
        html += `<div class="mobooking-option-description">${this.escapeHtml(
          option.description
        )}</div>`;
      }

      // Render input based on option type
      switch (option.type) {
        case "checkbox":
          html += this.renderCheckboxOptions(option, serviceId);
          break;
        case "radio":
        case "select":
          html += this.renderRadioSelectOptions(option, serviceId);
          break;
        case "text":
          html += this.renderTextInput(option, serviceId);
          break;
        case "number":
        case "quantity":
          html += this.renderNumberInput(option, serviceId);
          break;
        case "textarea":
          html += this.renderTextareaInput(option, serviceId);
          break;
        case "sqm":
          html += this.renderSqmInput(option, serviceId);
          break;
        default:
          html += this.renderTextInput(option, serviceId);
      }

      html += "</div>";
      return html;
    },

    /**
     * Render checkbox options
     */
    renderCheckboxOptions: function (option, serviceId) {
      if (!option.option_values) return "";

      let values;
      try {
        values =
          typeof option.option_values === "string"
            ? JSON.parse(option.option_values)
            : option.option_values;
      } catch (e) {
        return "";
      }

      let html = '<div class="mobooking-option-checkbox-group">';

      if (Array.isArray(values)) {
        values.forEach((value, index) => {
          const itemValue = typeof value === "object" ? value.value : value;
          const itemLabel = typeof value === "object" ? value.label : value;
          const itemPrice = typeof value === "object" ? value.price || 0 : 0;

          html += `
                        <div class="mobooking-option-item">
                            <input type="checkbox" 
                                   class="mobooking-option-input" 
                                   name="option_${option.option_id}[]" 
                                   value="${this.escapeHtml(itemValue)}"
                                   data-option-id="${option.option_id}"
                                   data-service-id="${serviceId}"
                                   data-price="${itemPrice}"
                                   ${
                                     option.is_required && index === 0
                                       ? "required"
                                       : ""
                                   }>
                            <label class="mobooking-option-item-label">${this.escapeHtml(
                              itemLabel
                            )}</label>
                            ${
                              itemPrice > 0 &&
                              moBookingParams.form_config.show_pricing
                                ? `<span class="mobooking-option-item-price">+${
                                    moBookingParams.currency.symbol
                                  }${parseFloat(itemPrice).toFixed(2)}</span>`
                                : ""
                            }
                        </div>
                    `;
        });
      }

      html += "</div>";
      return html;
    },

    /**
     * Render radio/select options
     */
    renderRadioSelectOptions: function (option, serviceId) {
      if (!option.option_values) return "";

      let values;
      try {
        values =
          typeof option.option_values === "string"
            ? JSON.parse(option.option_values)
            : option.option_values;
      } catch (e) {
        return "";
      }

      let html = "";

      if (option.type === "select") {
        html += `<select class="mobooking-option-input mobooking-select" 
                                name="option_${option.option_id}" 
                                data-option-id="${option.option_id}"
                                data-service-id="${serviceId}"
                                ${option.is_required ? "required" : ""}>
                            <option value="">Choose an option...</option>`;

        if (Array.isArray(values)) {
          values.forEach((value) => {
            const itemValue = typeof value === "object" ? value.value : value;
            const itemLabel = typeof value === "object" ? value.label : value;
            const itemPrice = typeof value === "object" ? value.price || 0 : 0;

            html += `<option value="${this.escapeHtml(
              itemValue
            )}" data-price="${itemPrice}">
                                    ${this.escapeHtml(itemLabel)}
                                    ${
                                      itemPrice > 0 &&
                                      moBookingParams.form_config.show_pricing
                                        ? ` (+${
                                            moBookingParams.currency.symbol
                                          }${parseFloat(itemPrice).toFixed(2)})`
                                        : ""
                                    }
                                 </option>`;
          });
        }

        html += "</select>";
      } else {
        // Radio buttons
        html += '<div class="mobooking-option-radio-group">';

        if (Array.isArray(values)) {
          values.forEach((value, index) => {
            const itemValue = typeof value === "object" ? value.value : value;
            const itemLabel = typeof value === "object" ? value.label : value;
            const itemPrice = typeof value === "object" ? value.price || 0 : 0;

            html += `
                            <div class="mobooking-option-item">
                                <input type="radio" 
                                       class="mobooking-option-input" 
                                       name="option_${option.option_id}" 
                                       value="${this.escapeHtml(itemValue)}"
                                       data-option-id="${option.option_id}"
                                       data-service-id="${serviceId}"
                                       data-price="${itemPrice}"
                                       ${
                                         option.is_required && index === 0
                                           ? "required"
                                           : ""
                                       }>
                                <label class="mobooking-option-item-label">${this.escapeHtml(
                                  itemLabel
                                )}</label>
                                ${
                                  itemPrice > 0 &&
                                  moBookingParams.form_config.show_pricing
                                    ? `<span class="mobooking-option-item-price">+${
                                        moBookingParams.currency.symbol
                                      }${parseFloat(itemPrice).toFixed(
                                        2
                                      )}</span>`
                                    : ""
                                }
                            </div>
                        `;
          });
        }

        html += "</div>";
      }

      return html;
    },

    /**
     * Render text input
     */
    renderTextInput: function (option, serviceId) {
      return `
                <input type="text" 
                       class="mobooking-option-input mobooking-input" 
                       name="option_${option.option_id}" 
                       placeholder="${option.description || ""}"
                       data-option-id="${option.option_id}"
                       data-service-id="${serviceId}"
                       ${option.is_required ? "required" : ""}>
            `;
    },

    /**
     * Render number input
     */
    renderNumberInput: function (option, serviceId) {
      return `
                <input type="number" 
                       class="mobooking-option-input mobooking-input" 
                       name="option_${option.option_id}" 
                       min="1"
                       placeholder="${option.description || "Enter quantity"}"
                       data-option-id="${option.option_id}"
                       data-service-id="${serviceId}"
                       ${option.is_required ? "required" : ""}>
            `;
    },

    /**
     * Render textarea input
     */
    renderTextareaInput: function (option, serviceId) {
      return `
                <textarea class="mobooking-option-input mobooking-textarea" 
                         name="option_${option.option_id}" 
                         rows="3"
                         placeholder="${option.description || ""}"
                         data-option-id="${option.option_id}"
                         data-service-id="${serviceId}"
                         ${option.is_required ? "required" : ""}></textarea>
            `;
    },

    /**
     * Render square meter input
     */
    renderSqmInput: function (option, serviceId) {
      return `
                <input type="number" 
                       class="mobooking-option-input mobooking-input" 
                       name="option_${option.option_id}" 
                       min="1"
                       step="0.1"
                       placeholder="Enter square meters"
                       data-option-id="${option.option_id}"
                       data-service-id="${serviceId}"
                       data-type="sqm"
                       ${option.is_required ? "required" : ""}>
            `;
    },

    /**
     * Handle option value changes
     */
    handleOptionChange: function ($input) {
      const optionId = $input.data("option-id");
      const serviceId = $input.data("service-id");

      if (!this.serviceOptions[serviceId]) {
        this.serviceOptions[serviceId] = {};
      }

      // Handle different input types
      if ($input.is(":checkbox")) {
        // Checkbox - collect all checked values
        const checkedValues = [];
        $(`input[name="option_${optionId}[]"]:checked`).each(function () {
          checkedValues.push({
            value: $(this).val(),
            price: parseFloat($(this).data("price") || 0),
          });
        });
        this.serviceOptions[serviceId][optionId] = checkedValues;
      } else if ($input.is(":radio") || $input.is("select")) {
        // Radio or select - single value
        const selectedOption = $input.find(":selected");
        const price = $input.is("select")
          ? parseFloat(selectedOption.data("price") || 0)
          : parseFloat($input.data("price") || 0);

        this.serviceOptions[serviceId][optionId] = {
          value: $input.val(),
          price: price,
        };
      } else {
        // Text, number, textarea, etc.
        let price = 0;
        const value = $input.val();

        // Handle SQM pricing
        if ($input.data("type") === "sqm" && value) {
          this.calculateSqmPrice(optionId, parseFloat(value), serviceId);
          return; // Will update via AJAX callback
        } else if ($input.data("price")) {
          price = parseFloat($input.data("price"));
        }

        this.serviceOptions[serviceId][optionId] = {
          value: value,
          price: price,
        };
      }

      // Update selected services with configured options
      this.selectedServices.forEach((service) => {
        if (service.service_id == serviceId) {
          service.configured_options = this.serviceOptions[serviceId] || {};
        }
      });

      // Update displays
      this.updateSidebarSummary();
      this.updatePricingSummary();

      // Store in hidden field
      $("#mobooking-service-options").val(JSON.stringify(this.serviceOptions));

      console.log("[MoBooking] Service options updated:", this.serviceOptions);
    },

    /**
     * Calculate SQM pricing via AJAX
     */
    calculateSqmPrice: function (optionId, sqmValue, serviceId) {
      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_calculate_sqm_pricing",
          nonce: moBookingParams.nonce,
          tenant_user_id: moBookingParams.tenant_user_id,
          option_id: optionId,
          sqm_value: sqmValue,
        },
        success: (response) => {
          if (response.success) {
            // Update option price
            if (!this.serviceOptions[serviceId]) {
              this.serviceOptions[serviceId] = {};
            }

            this.serviceOptions[serviceId][optionId] = {
              value: sqmValue,
              price: response.data.calculated_price,
            };

            // Update selected services
            this.selectedServices.forEach((service) => {
              if (service.service_id == serviceId) {
                service.configured_options =
                  this.serviceOptions[serviceId] || {};
              }
            });

            // Update displays
            this.updateSidebarSummary();
            this.updatePricingSummary();

            console.log(
              "[MoBooking] SQM price calculated:",
              response.data.calculated_price
            );
          }
        },
        error: () => {
          console.error("[MoBooking] Failed to calculate SQM pricing");
        },
      });
    },

    /**
     * Load available time slots for selected date
     */
    loadAvailableTimeSlots: function (date) {
      const $timeSelect = $("#preferred-time");
      $timeSelect
        .html('<option value="">Loading...</option>')
        .prop("disabled", true);

      console.log("[MoBooking] Loading time slots for:", date);

      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_get_available_time_slots",
          nonce: moBookingParams.nonce,
          tenant_user_id: moBookingParams.tenant_user_id,
          date: date,
          services: JSON.stringify(
            this.selectedServices.map((s) => ({
              service_id: s.service_id,
              duration: s.duration,
            }))
          ),
        },
        success: (response) => {
          if (response.success && response.data.time_slots) {
            let options = '<option value="">Select time...</option>';

            response.data.time_slots.forEach((slot) => {
              options += `<option value="${slot.time}">${slot.display_time}</option>`;
            });

            $timeSelect.html(options).prop("disabled", false);
            console.log(
              "[MoBooking] Time slots loaded:",
              response.data.time_slots.length
            );
          } else {
            $timeSelect
              .html('<option value="">No available times</option>')
              .prop("disabled", true);
          }
        },
        error: () => {
          $timeSelect
            .html('<option value="">Error loading times</option>')
            .prop("disabled", true);
        },
      });
    },

    /**
     * Validate customer details form
     */
    validateCustomerDetails: function () {
      const form = $("#mobooking-details-form")[0];
      const $continueBtn = $("#mobooking-step-4-continue");

      if (form.checkValidity()) {
        $continueBtn.prop("disabled", false);

        // Collect customer data
        this.formData.customer = {
          name: $("#customer-name").val().trim(),
          email: $("#customer-email").val().trim(),
          phone: $("#customer-phone").val().trim(),
          address: $("#service-address").val().trim(),
          date: $("#preferred-date").val(),
          time: $("#preferred-time").val(),
          instructions: $("#special-instructions").val().trim(),
        };

        console.log(
          "[MoBooking] Customer details updated:",
          this.formData.customer
        );
      } else {
        $continueBtn.prop("disabled", true);
      }
    },

    /**
     * Handle discount code application
     */
    handleDiscountApplication: function () {
      const discountCode = $("#discount-code").val().trim();
      const $feedback = $("#mobooking-discount-feedback");
      const $btn = $("#apply-discount-btn");

      if (!discountCode) {
        $feedback
          .removeClass("success")
          .addClass("error")
          .text("Please enter a discount code.")
          .show();
        return;
      }

      console.log("[MoBooking] Applying discount code:", discountCode);
      $btn.prop("disabled", true).text("Applying...");

      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_apply_discount",
          nonce: moBookingParams.nonce,
          tenant_user_id: moBookingParams.tenant_user_id,
          discount_code: discountCode,
          subtotal: this.calculateSubtotal(),
        },
        success: (response) => {
          if (response.success) {
            this.appliedDiscount = response.data.discount;
            $feedback
              .removeClass("error")
              .addClass("success")
              .text(moBookingParams.i18n.discount_applied)
              .show();
            $("#mobooking-discount-applied").val(
              JSON.stringify(this.appliedDiscount)
            );
            this.updatePricingSummary();

            console.log("[MoBooking] Discount applied:", this.appliedDiscount);
          } else {
            $feedback
              .removeClass("success")
              .addClass("error")
              .text(
                response.data.message || moBookingParams.i18n.discount_invalid
              )
              .show();
          }
        },
        error: () => {
          $feedback
            .removeClass("success")
            .addClass("error")
            .text(moBookingParams.i18n.error_generic)
            .show();
        },
        complete: () => {
          $btn
            .prop("disabled", false)
            .text(moBookingParams.i18n.apply_discount);
        },
      });
    },

    /**
     * Handle final booking submission
     */
    handleBookingSubmission: function () {
      // Validate terms if required
      if ($("#accept-terms").length && !$("#accept-terms").is(":checked")) {
        this.showFeedback("error", "Please accept the terms and conditions.");
        return;
      }

      console.log("[MoBooking] Submitting booking...");

      // Prepare booking data payload
      const bookingPayload = {
        tenant_user_id: moBookingParams.tenant_user_id,
        customer: this.formData.customer,
        services: this.selectedServices,
        service_options: this.serviceOptions,
        location: this.formData.location,
        discount: this.appliedDiscount,
        pricing: {
          subtotal: this.calculateSubtotal(),
          discount_amount: this.appliedDiscount
            ? this.appliedDiscount.discount_amount
            : 0,
          total: this.calculateTotal(),
        },
      };

      console.log("[MoBooking] Booking payload:", bookingPayload);

      const $submitBtn = $("#mobooking-submit-booking");
      $submitBtn
        .prop("disabled", true)
        .html('<i class="fas fa-spinner fa-spin"></i> Processing...');

      $.ajax({
        url: moBookingParams.ajax_url,
        type: "POST",
        data: {
          action: "mobooking_create_booking",
          nonce: moBookingParams.nonce,
          booking_data: JSON.stringify(bookingPayload),
        },
        success: (response) => {
          if (response.success) {
            console.log(
              "[MoBooking] Booking created successfully:",
              response.data
            );

            // Show success step
            if (response.data.booking_reference) {
              $("#mobooking-reference-number").text(
                response.data.booking_reference
              );
              $("#mobooking-booking-reference").show();
            }

            if (response.data.message) {
              $("#mobooking-success-message p").text(response.data.message);
            }

            this.navigateToStep(6);
          } else {
            this.showFeedback(
              "error",
              response.data.message || "Booking submission failed."
            );
            $submitBtn
              .prop("disabled", false)
              .html('<i class="fas fa-check"></i> Confirm Booking');
          }
        },
        error: () => {
          this.showFeedback("error", moBookingParams.i18n.error_generic);
          $submitBtn
            .prop("disabled", false)
            .html('<i class="fas fa-check"></i> Confirm Booking');
        },
      });
    },

    /**
     * Navigate to a specific step
     */
    navigateToStep: function (stepNumber) {
      // Validate current step before proceeding
      if (stepNumber > this.currentStep && !this.validateCurrentStep()) {
        return;
      }

      console.log(`[MoBooking] Navigating to step ${stepNumber}`);

      // Special handling for step transitions
      if (stepNumber === 2 && this.currentStep === 1) {
        this.loadServices();
      } else if (stepNumber === 3 && this.currentStep === 2) {
        if (this.selectedServices.length === 0) {
          this.showFeedback("error", moBookingParams.i18n.error_services);
          return;
        }
        this.loadServiceOptions();
      } else if (stepNumber === 5 && this.currentStep === 4) {
        this.updateBookingSummary();
        this.updatePricingSummary();
      }

      this.currentStep = stepNumber;
      this.showStep(stepNumber);
    },

    /**
     * Validate current step before allowing navigation
     */
    validateCurrentStep: function () {
      switch (this.currentStep) {
        case 1:
          return $("#mobooking-location-input").val().trim() !== "";
        case 2:
          return this.selectedServices.length > 0;
        case 3:
          return this.validateRequiredOptions();
        case 4:
          return $("#mobooking-details-form")[0].checkValidity();
        case 5:
          return true; // Final validation happens in submission
        default:
          return true;
      }
    },

    /**
     * Validate required service options
     */
    validateRequiredOptions: function () {
      let isValid = true;

      $(".mobooking-service-option").each((index, element) => {
        const $option = $(element);
        const $inputs = $option.find(".mobooking-option-input");
        const isRequired =
          $option.find(".mobooking-option-required").length > 0;

        if (isRequired) {
          let hasValue = false;

          $inputs.each((inputIndex, input) => {
            const $input = $(input);
            if ($input.is(":checkbox") || $input.is(":radio")) {
              if ($input.is(":checked")) hasValue = true;
            } else if ($input.val() && $input.val().trim() !== "") {
              hasValue = true;
            }
          });

          if (!hasValue) {
            isValid = false;
            $option.addClass("mobooking-option-error");
          } else {
            $option.removeClass("mobooking-option-error");
          }
        }
      });

      if (!isValid) {
        this.showFeedback("error", moBookingParams.i18n.error_required_option);
      }

      return isValid;
    },

    /**
     * Show a specific step
     */
    showStep: function (stepNumber) {
      // Hide all steps
      $(".mobooking-step").hide().removeClass("active");

      // Show current step
      $(`#mobooking-step-${stepNumber}`).show().addClass("active");

      // Update progress bar
      this.updateProgressBar(stepNumber);

      // Scroll to top
      this.$container[0].scrollIntoView({
        behavior: "smooth",
        block: "start",
      });

      console.log(`[MoBooking] Showing step ${stepNumber}`);
    },

    /**
     * Update progress bar
     */
    updateProgressBar: function (stepNumber) {
      if (!moBookingParams.form_config.show_progress_bar) return;

      const totalSteps = 5; // Don't count success step
      const progress = Math.min((stepNumber / totalSteps) * 100, 100);

      this.$progressBar.css("width", progress + "%");
      this.$progressText.text(
        `Step ${Math.min(stepNumber, totalSteps)} of ${totalSteps}`
      );
    },

    /**
     * Update sidebar summary
     */
    updateSidebarSummary: function () {
      let html = "<h4>Selected Services</h4>";

      if (this.selectedServices.length === 0) {
        html += "<p>No services selected.</p>";
      } else {
        html += '<div class="mobooking-summary-services">';

        this.selectedServices.forEach((service) => {
          html += `
                        <div class="mobooking-summary-item">
                            <div class="mobooking-summary-label">${this.escapeHtml(
                              service.name
                            )}</div>
                            <div class="mobooking-summary-value">${
                              moBookingParams.currency.symbol
                            }${service.price.toFixed(2)}</div>
                        </div>
                    `;

          // Add configured options
          const options = service.configured_options || {};
          Object.keys(options).forEach((optionId) => {
            const option = options[optionId];
            if (Array.isArray(option)) {
              // Checkbox options
              option.forEach((item) => {
                if (item.price > 0) {
                  html += `
                                        <div class="mobooking-summary-item mobooking-summary-option">
                                            <div class="mobooking-summary-label">+ ${this.escapeHtml(
                                              item.value
                                            )}</div>
                                            <div class="mobooking-summary-value">${
                                              moBookingParams.currency.symbol
                                            }${item.price.toFixed(2)}</div>
                                        </div>
                                    `;
                }
              });
            } else if (option.price > 0) {
              // Single option
              html += `
                                <div class="mobooking-summary-item mobooking-summary-option">
                                    <div class="mobooking-summary-label">+ ${this.escapeHtml(
                                      option.value
                                    )}</div>
                                    <div class="mobooking-summary-value">${
                                      moBookingParams.currency.symbol
                                    }${option.price.toFixed(2)}</div>
                                </div>
                            `;
            }
          });
        });

        html += "</div>";

        if (moBookingParams.form_config.show_pricing) {
          const subtotal = this.calculateSubtotal();
          html += `
                        <div class="mobooking-summary-total">
                            <div class="mobooking-summary-item">
                                <div class="mobooking-summary-label"><strong>Subtotal</strong></div>
                                <div class="mobooking-summary-value"><strong>${
                                  moBookingParams.currency.symbol
                                }${subtotal.toFixed(2)}</strong></div>
                            </div>
                        </div>
                    `;
        }
      }

      $("#mobooking-summary-content, #mobooking-summary-sidebar").html(html);
    },

    /**
     * Update booking summary for review step
     */
    updateBookingSummary: function () {
      let html = "";

      // Customer details
      html += `
                <div class="mobooking-summary-section">
                    <h3><i class="fas fa-user"></i> Customer Information</h3>
                    <div class="mobooking-summary-item">
                        <div class="mobooking-summary-label">Name</div>
                        <div class="mobooking-summary-value">${this.escapeHtml(
                          this.formData.customer.name
                        )}</div>
                    </div>
                    <div class="mobooking-summary-item">
                        <div class="mobooking-summary-label">Email</div>
                        <div class="mobooking-summary-value">${this.escapeHtml(
                          this.formData.customer.email
                        )}</div>
                    </div>
                    <div class="mobooking-summary-item">
                        <div class="mobooking-summary-label">Phone</div>
                        <div class="mobooking-summary-value">${this.escapeHtml(
                          this.formData.customer.phone
                        )}</div>
                    </div>
                </div>
            `;

      // Service details
      html += `
                <div class="mobooking-summary-section">
                    <h3><i class="fas fa-calendar-alt"></i> Service Details</h3>
                    <div class="mobooking-summary-item">
                        <div class="mobooking-summary-label">Date</div>
                        <div class="mobooking-summary-value">${this.formatDate(
                          this.formData.customer.date
                        )}</div>
                    </div>
                    <div class="mobooking-summary-item">
                        <div class="mobooking-summary-label">Time</div>
                        <div class="mobooking-summary-value">${
                          this.formData.customer.time
                        }</div>
                    </div>
                    <div class="mobooking-summary-item">
                        <div class="mobooking-summary-label">Address</div>
                        <div class="mobooking-summary-value">${this.escapeHtml(
                          this.formData.customer.address
                        )}</div>
                    </div>
            `;

      if (this.formData.customer.instructions) {
        html += `
                    <div class="mobooking-summary-item">
                        <div class="mobooking-summary-label">Special Instructions</div>
                        <div class="mobooking-summary-value">${this.escapeHtml(
                          this.formData.customer.instructions
                        )}</div>
                    </div>
                `;
      }

      html += "</div>";

      // Services summary
      html += `
                <div class="mobooking-summary-section">
                    <h3><i class="fas fa-broom"></i> Selected Services</h3>
            `;

      this.selectedServices.forEach((service) => {
        html += `
                    <div class="mobooking-summary-item">
                        <div class="mobooking-summary-label">${this.escapeHtml(
                          service.name
                        )}</div>
                        <div class="mobooking-summary-value">${
                          moBookingParams.currency.symbol
                        }${service.price.toFixed(2)}</div>
                    </div>
                `;

        // Add configured options
        const options = service.configured_options || {};
        Object.keys(options).forEach((optionId) => {
          const option = options[optionId];
          if (Array.isArray(option)) {
            option.forEach((item) => {
              html += `
                                <div class="mobooking-summary-item mobooking-summary-suboption">
                                    <div class="mobooking-summary-label">+ ${this.escapeHtml(
                                      item.value
                                    )}</div>
                                    <div class="mobooking-summary-value">${
                                      item.price > 0
                                        ? moBookingParams.currency.symbol +
                                          item.price.toFixed(2)
                                        : "Included"
                                    }</div>
                                </div>
                            `;
            });
          } else {
            html += `
                            <div class="mobooking-summary-item mobooking-summary-suboption">
                                <div class="mobooking-summary-label">+ ${this.escapeHtml(
                                  option.value
                                )}</div>
                                <div class="mobooking-summary-value">${
                                  option.price > 0
                                    ? moBookingParams.currency.symbol +
                                      option.price.toFixed(2)
                                    : "Included"
                                }</div>
                            </div>
                        `;
          }
        });
      });

      html += "</div>";

      $("#mobooking-booking-summary").html(html);
    },

    /**
     * Update pricing summary
     */
    updatePricingSummary: function () {
      if (!moBookingParams.form_config.show_pricing) {
        $("#mobooking-pricing-summary").hide();
        return;
      }

      const subtotal = this.calculateSubtotal();
      const discountAmount = this.appliedDiscount
        ? this.appliedDiscount.discount_amount
        : 0;
      const total = subtotal - discountAmount;

      let html = `
                <div class="mobooking-pricing-row">
                    <div>Services Subtotal</div>
                    <div>${moBookingParams.currency.symbol}${subtotal.toFixed(
        2
      )}</div>
                </div>
            `;

      if (discountAmount > 0) {
        html += `
                    <div class="mobooking-pricing-row">
                        <div>Discount (${this.appliedDiscount.code})</div>
                        <div>-${
                          moBookingParams.currency.symbol
                        }${discountAmount.toFixed(2)}</div>
                    </div>
                `;
      }

      html += `
                <div class="mobooking-pricing-row total">
                    <div>Total</div>
                    <div>${moBookingParams.currency.symbol}${total.toFixed(
        2
      )}</div>
                </div>
            `;

      $("#mobooking-pricing-summary").html(html).show();
    },

    /**
     * Calculate subtotal price
     */
    calculateSubtotal: function () {
      let subtotal = 0;

      this.selectedServices.forEach((service) => {
        subtotal += service.price;

        const options = service.configured_options || {};
        Object.keys(options).forEach((optionId) => {
          const option = options[optionId];
          if (Array.isArray(option)) {
            option.forEach((item) => {
              subtotal += item.price || 0;
            });
          } else {
            subtotal += option.price || 0;
          }
        });
      });

      return subtotal;
    },

    /**
     * Calculate total price after discounts
     */
    calculateTotal: function () {
      const subtotal = this.calculateSubtotal();
      const discountAmount = this.appliedDiscount
        ? this.appliedDiscount.discount_amount
        : 0;
      return Math.max(0, subtotal - discountAmount);
    },

    /**
     * Show loading state
     */
    showLoading: function (selector, message = "Loading...") {
      $(selector).html(`
                <div class="mobooking-loading">
                    <div class="mobooking-spinner"></div>
                    <p>${message}</p>
                </div>
            `);
    },

    /**
     * Hide loading state
     */
    hideLoading: function (selector) {
      $(selector).find(".mobooking-loading").remove();
    },

    /**
     * Show feedback message
     */
    showFeedback: function (type, message) {
      this.$feedback
        .removeClass("success error warning")
        .addClass(type)
        .html(message)
        .show();

      // Auto-hide after 5 seconds for non-error messages
      if (type !== "error") {
        setTimeout(() => {
          this.$feedback.fadeOut();
        }, 5000);
      }

      // Scroll to feedback
      this.$feedback[0].scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
    },

    /**
     * Show error message
     */
    showError: function (message) {
      this.showFeedback("error", message);
    },

    /**
     * Format date for display
     */
    formatDate: function (dateString) {
      if (!dateString) return "";

      const date = new Date(dateString);
      return date.toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
      });
    },

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml: function (text) {
      if (!text) return "";
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    },
  };

  // Utility functions for global access
  window.MoBookingUtils = {
    /**
     * Format price with currency
     */
    formatPrice: function (amount) {
      if (typeof moBookingParams !== "undefined") {
        return moBookingParams.currency.symbol + parseFloat(amount).toFixed(2);
      }
      return parseFloat(amount).toFixed(2);
    },

    /**
     * Validate email address
     */
    isValidEmail: function (email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    },

    /**
     * Validate phone number (basic)
     */
    isValidPhone: function (phone) {
      const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
      return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ""));
    },

    /**
     * Show toast notification
     */
    showToast: function (message, type = "info") {
      // Create toast element
      const toast = $(`
                <div class="mobooking-toast mobooking-toast-${type}">
                    <div class="mobooking-toast-content">
                        <i class="fas fa-${
                          type === "success"
                            ? "check-circle"
                            : type === "error"
                            ? "exclamation-circle"
                            : "info-circle"
                        }"></i>
                        <span>${message}</span>
                    </div>
                    <button class="mobooking-toast-close">&times;</button>
                </div>
            `);

      // Add to page
      $("body").append(toast);

      // Auto-remove after 5 seconds
      setTimeout(() => {
        toast.fadeOut(() => toast.remove());
      }, 5000);

      // Manual close
      toast.find(".mobooking-toast-close").on("click", () => {
        toast.fadeOut(() => toast.remove());
      });
    },
  };

  // Add toast CSS if not already present
  if (!$("#mobooking-toast-styles").length) {
    $("head").append(`
            <style id="mobooking-toast-styles">
                .mobooking-toast {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    padding: 1rem;
                    z-index: 10000;
                    max-width: 350px;
                    animation: slideInRight 0.3s ease-out;
                }
                
                .mobooking-toast-content {
                    display: flex;
                    align-items: center;
                    gap: 0.75rem;
                }
                
                .mobooking-toast-close {
                    position: absolute;
                    top: 0.5rem;
                    right: 0.5rem;
                    background: none;
                    border: none;
                    font-size: 1.25rem;
                    cursor: pointer;
                    opacity: 0.6;
                }
                
                .mobooking-toast-close:hover {
                    opacity: 1;
                }
                
                .mobooking-toast-success {
                    border-left: 4px solid #27ae60;
                }
                
                .mobooking-toast-success i {
                    color: #27ae60;
                }
                
                .mobooking-toast-error {
                    border-left: 4px solid #e74c3c;
                }
                
                .mobooking-toast-error i {
                    color: #e74c3c;
                }
                
                .mobooking-toast-info {
                    border-left: 4px solid #3498db;
                }
                
                .mobooking-toast-info i {
                    color: #3498db;
                }
                
                @keyframes slideInRight {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                
                @media (max-width: 480px) {
                    .mobooking-toast {
                        right: 10px;
                        left: 10px;
                        max-width: none;
                    }
                }
            </style>
        `);
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
})(jQuery);

/**
 * WordPress Compatibility & Fallbacks
 */
document.addEventListener("DOMContentLoaded", function () {
  // Fallback for if jQuery is not loaded
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

  // Initialize form if container exists
  const formContainer = document.getElementById(
    "mobooking-booking-form-container"
  );
  if (formContainer) {
    console.log("[MoBooking] Form container found, initializing...");
  } else {
    console.warn("[MoBooking] Form container not found on this page");
  }
});

/**
 * Global error handler for unhandled booking form errors
 */
window.addEventListener("error", function (event) {
  if (event.filename && event.filename.includes("booking-form")) {
    console.error("[MoBooking] JavaScript Error:", event.error);

    // Show user-friendly error message
    if (typeof MoBookingUtils !== "undefined") {
      MoBookingUtils.showToast(
        "An unexpected error occurred. Please refresh the page and try again.",
        "error"
      );
    }
  }
});
