/**
 * MoBooking Public Form JavaScript - Complete Refactored Version
 * Handles all form interactions, data collection, and submission
 */

(function ($) {
  "use strict";

  // Global variables
  let CONFIG = {};
  let currentStep = 1;
  let displayedOptions = [];
  let displayedServices = [];
  let formData = {
    location: {},
    services: [],
    options: {},
    pets: { has_pets: false, details: "" },
    frequency: "one-time",
    datetime: { date: "", time: "" },
    customer: { name: "", email: "", phone: "", address: "", instructions: "" },
    access: { method: "home", details: "" },
  };
  let debugResponses = [];
  let isSubmitting = false;

  // Debug Tree for enhanced logging
  const DebugTree = {
    group: function (title) {
      if (
        CONFIG.form_config?.debug_mode ||
        window.location.search.includes("debug=1")
      ) {
        console.group(`🌳 ${title}`);
      }
    },
    groupEnd: function () {
      if (
        CONFIG.form_config?.debug_mode ||
        window.location.search.includes("debug=1")
      ) {
        console.groupEnd();
      }
    },
    info: function (message, data = null) {
      if (
        CONFIG.form_config?.debug_mode ||
        window.location.search.includes("debug=1")
      ) {
        console.log(`ℹ️ ${message}`, data || "");
      }
    },
    success: function (message, data = null) {
      if (
        CONFIG.form_config?.debug_mode ||
        window.location.search.includes("debug=1")
      ) {
        console.log(`✅ ${message}`, data || "");
      }
    },
    warning: function (message, data = null) {
      if (
        CONFIG.form_config?.debug_mode ||
        window.location.search.includes("debug=1")
      ) {
        console.warn(`⚠️ ${message}`, data || "");
      }
    },
    error: function (message, data = null) {
      console.error(`❌ ${message}`, data || "");
    },
  };

  /**
   * Initialize the booking form
   */
  function initializeBookingForm() {
    DebugTree.group("🚀 Initializing MoBooking Form");

    // Get configuration from global scope - try multiple possible sources
    CONFIG =
      window.MOBOOKING_CONFIG ||
      window.CONFIG ||
      window.mobooking_config ||
      window.mobooking_booking_form_params ||
      {};

    DebugTree.info("Attempting to load configuration...");
    DebugTree.info("Available window objects:", {
      MOBOOKING_CONFIG: !!window.MOBOOKING_CONFIG,
      CONFIG: !!window.CONFIG,
      mobooking_config: !!window.mobooking_config,
      mobooking_booking_form_params: !!window.mobooking_booking_form_params,
    });

    if (window.MOBOOKING_CONFIG) {
      DebugTree.success("Found MOBOOKING_CONFIG", window.MOBOOKING_CONFIG);
      CONFIG = window.MOBOOKING_CONFIG;
    } else if (window.CONFIG) {
      DebugTree.success("Found CONFIG", window.CONFIG);
      CONFIG = window.CONFIG;
    } else {
      DebugTree.warning("No primary config found, checking alternatives...");
      checkAlternativeConfigs();
    }

    if (!validateConfiguration()) {
      DebugTree.error("Configuration validation failed");
      showConfigurationError();
      return;
    }

    // Determine starting step
    currentStep = CONFIG.form_config?.enable_area_check ? 1 : 2;
    DebugTree.info(`Starting at step ${currentStep}`);

    // Initialize components
    initializeEventHandlers();
    initializeDatePicker();
    initializeFormState();

    // Start form
    showStep(currentStep);

    // Expose debug functions globally
    window.MoBookingDebug = {
      tree: DebugTree,
      config: CONFIG,
      formData: formData,
      responses: debugResponses,
      collectFormData: collectAllFormData,
      testSubmission: testFormSubmission,
    };

    DebugTree.success("Form initialization complete");
    DebugTree.groupEnd();
  }

  /**
   * Validate configuration
   */
  function validateConfiguration() {
    DebugTree.group("✅ Validating Configuration");

    // Log the full config for debugging
    DebugTree.info("Full config object:", CONFIG);

    const required = ["ajax_url", "nonce", "tenant_id"];
    let isValid = true;

    required.forEach((key) => {
      if (!CONFIG[key]) {
        DebugTree.error(`Missing required config: ${key}`);
        isValid = false;
      } else {
        DebugTree.success(`✓ ${key}:`, CONFIG[key]);
      }
    });

    // Create default form_config if missing
    if (!CONFIG.form_config) {
      DebugTree.warning("form_config missing, creating default");
      CONFIG.form_config = {
        enable_area_check: false,
        enable_pet_information: true,
        enable_service_frequency: true,
        enable_datetime_selection: true,
        enable_property_access: true,
        show_progress_bar: true,
        debug_mode: true,
      };
      DebugTree.success("Default form_config created:", CONFIG.form_config);
    } else {
      DebugTree.success("✓ form_config:", CONFIG.form_config);
    }

    // Create default i18n if missing
    if (!CONFIG.i18n) {
      DebugTree.warning("i18n missing, creating default");
      CONFIG.i18n = {
        loading_services: "Loading services...",
        select_service: "Please select at least one service.",
        booking_error: "There was an error. Please try again.",
        name_required: "Please enter your name.",
        email_required: "Please enter your email address.",
        phone_required: "Please enter your phone number.",
        address_required: "Please enter your service address.",
        date_required: "Please select a preferred date.",
        time_required: "Please select a preferred time.",
        booking_submitted: "Your booking has been submitted successfully!",
      };
      DebugTree.success("Default i18n created:", CONFIG.i18n);
    } else {
      DebugTree.success("✓ i18n:", CONFIG.i18n);
    }

    DebugTree.groupEnd();
    return isValid;
  }

  /**
   * Initialize event handlers
   */
  function initializeEventHandlers() {
    DebugTree.info("Setting up event handlers");

    // Form input changes
    $(document).on("change input", "input, select, textarea", function () {
      collectAllFormData();
      updateLiveSummary();
    });

    // Service selection
    $(document).on("click", ".mobooking-service-card", function () {
      const serviceId =
        $(this).data("service-id") || $(this).attr("data-service-id");
      if (serviceId) {
        selectService(serviceId);
      }
    });

    // Time slot selection
    $(document).on("click", ".mobooking-time-slot", function () {
      const time = $(this).data("time") || $(this).attr("data-time");
      if (time) {
        selectTimeSlot(time);
      }
    });

    // Date picker change
    $(document).on("change", "#mobooking-service-date", function () {
      const selectedDate = $(this).val();
      if (selectedDate) {
        loadTimeSlots(selectedDate);
      }
    });

    // Pet information toggle
    $(document).on("change", 'input[name="has_pets"]', function () {
      DebugTree.info(`Pet selection changed to: ${this.value}`);
      const $petDetailsContainer = $("#mobooking-pet-details-container");

      if (this.value === "yes") {
        $petDetailsContainer.removeClass("hidden").show();
        DebugTree.info("Showing pet details container");
      } else {
        $petDetailsContainer.addClass("hidden").hide();
        // Clear the textarea when hiding
        $("#mobooking-pet-details").val("");
        DebugTree.info("Hiding pet details container");
      }

      // Update form data immediately
      collectAllFormData();
    });

    // Property access toggle
    $(document).on("change", 'input[name="property_access"]', function () {
      DebugTree.info(`Property access changed to: ${this.value}`);
      const $customAccessDetails = $("#mobooking-custom-access-details");

      if (this.value === "other") {
        $customAccessDetails.removeClass("hidden").show();
        DebugTree.info("Showing custom access details");
      } else {
        $customAccessDetails.addClass("hidden").hide();
        // Clear the textarea when hiding
        $("#mobooking-access-instructions").val("");
        DebugTree.info("Hiding custom access details");
      }

      // Update form data immediately
      collectAllFormData();
    });

    // Form submission
    $(document).on(
      "click",
      'button[onclick="moBookingSubmitForm()"]',
      function (e) {
        e.preventDefault();
        e.stopPropagation();
        moBookingSubmitForm();
      }
    );

    DebugTree.success("Event handlers initialized");
  }

  /**
   * Initialize date picker
   */
  function initializeDatePicker() {
    const $datePicker = $("#mobooking-service-date");
    if ($datePicker.length && typeof flatpickr !== "undefined") {
      flatpickr($datePicker[0], {
        minDate: "today",
        dateFormat: "Y-m-d",
        onChange: function (selectedDates, dateStr) {
          formData.datetime.date = dateStr;
          if (dateStr) {
            loadTimeSlots(dateStr);
          }
        },
      });
    }
  }

  /**
   * Initialize form state
   */
  function initializeFormState() {
    // Load services if not starting with area check
    if (!CONFIG.form_config?.enable_area_check) {
      loadServices();
    }

    collectAllFormData();
    updateLiveSummary();
  }

  /**
   * Collect all form data from DOM
   */
  function collectAllFormData() {
    DebugTree.info("Collecting all form data");

    // Location data
    formData.location = {
      zip_code: $("#mobooking-zip").val() || "",
      country_code: $("#mobooking-country").val() || "",
    };

    // Services data
    formData.services = [];
    const serviceSelectors = [
      'input[name="selected_service"]:checked',
      ".mobooking-service-card.selected",
      "[data-service-id].selected",
    ];

    serviceSelectors.forEach((selector) => {
      $(selector).each(function () {
        let serviceId;
        if ($(this).is("input")) {
          serviceId = $(this).val();
        } else {
          serviceId =
            $(this).data("service-id") || $(this).attr("data-service-id");
        }
        if (
          serviceId &&
          formData.services.indexOf(serviceId.toString()) === -1
        ) {
          formData.services.push(serviceId.toString());
        }
      });
    });

    // Service options
    formData.options = {};
    $('[name^="service_options"]').each(function () {
        const $input = $(this);
        const name = $input.attr("name");
        const match = name.match(/service_options\[(\d+)\]/);
        if (match) {
            const optionId = match[1];
            const optionType = $input.data('option-type');
            const value = $input.attr('type') === 'checkbox' ? ($input.is(':checked') ? 1 : 0) : $input.val();

            formData.options[optionId] = {
                value: value
            };

            if (optionType === 'sqm' || optionType === 'kilometers') {
                // Find the full option data which was stored when options were displayed
                const fullOption = displayedOptions.find(o => o.option_id == optionId);
                if (fullOption) {
                    formData.options[optionId].ranges = fullOption.option_values;
                    formData.options[optionId].type = optionType;
                }
            }
        }
    });

    // Pet information
    formData.pets = {
      has_pets: $('input[name="has_pets"]:checked').val() === "yes",
      details: $("#mobooking-pet-details").val() || "",
    };

    // Service frequency
    formData.frequency =
      $('input[name="frequency"]:checked').val() || "one-time";

    // DateTime
    formData.datetime = {
      date: $("#mobooking-service-date").val() || "",
      time:
        $(".mobooking-time-slot.selected").data("time") ||
        $(".mobooking-time-slot.selected").attr("data-time") ||
        "",
    };

    // Customer details
    formData.customer = {
      name: $("#mobooking-customer-name").val() || "",
      email: $("#mobooking-customer-email").val() || "",
      phone: $("#mobooking-customer-phone").val() || "",
      address: $("#mobooking-service-address").val() || "",
      instructions: $("#mobooking-special-instructions").val() || "",
    };

    // Property access
    formData.access = {
      method: $('input[name="property_access"]:checked').val() || "home",
      details: $("#mobooking-access-instructions").val() || "",
    };

    DebugTree.success("Form data collected", formData);

    // Update debug display if it exists
    updateDebugInfo();

    return formData;
  }

  /**
   * Load services for the tenant
   */
  function loadServices() {
    DebugTree.group("📦 Loading Services");

    const $container = $("#mobooking-services-container");
    const $feedback = $("#mobooking-services-feedback");

    if ($container.length === 0) {
      DebugTree.warning("Services container not found");
      return;
    }

    $container.html('<div class="mobooking-loading">Loading services...</div>');

    $.ajax({
      url: CONFIG.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_public_services",
        tenant_id: CONFIG.tenant_id,
        nonce: CONFIG.nonce,
      },
      success: function (response) {
        DebugTree.success("Services loaded", response);
        debugResponses.push({
          action: "get_public_services",
          success: true,
          response: response,
          timestamp: new Date().toISOString(),
        });

        // Handle different response formats
        let services = [];
        if (response.success && response.data) {
          if (Array.isArray(response.data)) {
            services = response.data;
          } else if (
            response.data.services &&
            Array.isArray(response.data.services)
          ) {
            services = response.data.services;
          }
        }

        if (services.length > 0) {
          displayServices(services);
        } else {
          showFeedback($feedback, "error", CONFIG.i18n.no_services_available);
        }
      },
      error: function (xhr, status, error) {
        DebugTree.error("Failed to load services", { xhr, status, error });
        debugResponses.push({
          action: "get_public_services",
          success: false,
          error: error,
          timestamp: new Date().toISOString(),
        });
        showFeedback($feedback, "error", CONFIG.i18n.error_loading_services);
      },
    });

    DebugTree.groupEnd();
  }

  /**
   * Display services in the container
   */
  function displayServices(services) {
    displayedServices = services; // Store for later use
    DebugTree.info("Displaying services", services);

    const $container = $("#mobooking-services-container");
    let html = '<div class="mobooking-services-grid">';

    services.forEach((service) => {
      const imageHtml = service.image_url
        ? `<img src="${service.image_url}" alt="${service.name}" class="mobooking-service-image">`
        : "";
      const iconHtml = service.icon
        ? `<img src="${service.icon}" alt="${service.name}" class="mobooking-service-icon">`
        : "";
      const priceHtml =
        CONFIG.settings?.bf_show_pricing === "1"
          ? `<span class="mobooking-service-price">${
              CONFIG.currency?.symbol || "$"
            }${service.price}</span>`
          : "";

      html += `
        <div class="mobooking-service-card" data-service-id="${
          service.service_id
        }">
          ${imageHtml}
          <div class="mobooking-service-content">
            ${iconHtml}
            <h3 class="mobooking-service-name">${service.name}</h3>
            <p class="mobooking-service-description">${
              service.description || ""
            }</p>
            ${priceHtml}
            <div class="mobooking-service-duration">${
              service.duration
            } minutes</div>
          </div>
        </div>
      `;
    });

    html += "</div>";
    $container.html(html);
  }

  /**
   * Load time slots for selected date
   */
  function loadTimeSlots(date) {
    DebugTree.group(`⏰ Loading Time Slots for ${date}`);

    const $container = $("#mobooking-time-slots");
    const $containerWrapper = $("#mobooking-time-slots-container");

    if ($container.length === 0) {
      DebugTree.warning("Time slots container not found");
      return;
    }

    $container.html(
      '<div class="mobooking-loading">Loading available times...</div>'
    );
    $containerWrapper.removeClass("hidden").show();

    $.ajax({
      url: CONFIG.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_available_time_slots",
        tenant_id: CONFIG.tenant_id,
        date: date,
        services: JSON.stringify(formData.services),
        nonce: CONFIG.nonce,
      },
      success: function (response) {
        DebugTree.success("Time slots loaded", response);
        debugResponses.push({
          action: "get_available_time_slots",
          response: response,
        });

        if (response.success && response.data && response.data.time_slots) {
          displayTimeSlots(response.data.time_slots);
        } else {
          $container.html(
            '<div class="mobooking-no-slots">No available time slots for this date.</div>'
          );
        }
      },
      error: function (xhr, status, error) {
        DebugTree.error("Failed to load time slots", { xhr, status, error });
        $container.html(
          '<div class="mobooking-error">Could not load time slots. Please try again.</div>'
        );
      },
    });

    DebugTree.groupEnd();
  }

  /**
   * Display time slots
   */
  function displayTimeSlots(timeSlots) {
    const $container = $("#mobooking-time-slots");
    let html = "";

    timeSlots.forEach((slot) => {
      html += `
        <button type="button" class="mobooking-time-slot" data-time="${
          slot.start_time
        }">
          ${slot.display || slot.start_time}
        </button>
      `;
    });

    $container.html(html);
  }

  /**
   * Select a service (single selection only)
   */
  function selectService(serviceId) {
    DebugTree.info(`Service ${serviceId} selected`);

    // Remove selection from ALL service cards (single selection)
    $(".mobooking-service-card").removeClass("selected");
    $('input[name="selected_service"]').prop("checked", false);

    // Select the clicked card only
    const $serviceCard = $(
      `.mobooking-service-card[data-service-id="${serviceId}"]`
    );
    $serviceCard.addClass("selected");

    // Update form data (single service only)
    formData.services = [serviceId.toString()];

    collectAllFormData();
    updateLiveSummary();

    // Load service options immediately after selection
    setTimeout(() => {
      loadServiceOptions();
    }, 100);
  }

  /**
   * Select a time slot
   */
  function selectTimeSlot(time) {
    DebugTree.info(`Time slot selected: ${time}`);

    if (
      typeof time !== "string" ||
      time.trim() === "" ||
      time === "undefined"
    ) {
      DebugTree.warning("Invalid time value", time);
      return;
    }

    $(".mobooking-time-slot").removeClass("selected");
    $(`.mobooking-time-slot[data-time="${time}"]`).addClass("selected");

    formData.datetime.time = time;
    updateLiveSummary();
  }

  /**
   * Load service options
   */
  function loadServiceOptions() {
    if (formData.services.length === 0) {
      const $container = $("#mobooking-service-options-container");
      if ($container.length > 0) {
        $container.html(
          '<p class="text-gray-600">Select your service first to see available options.</p>'
        );
      }
      return;
    }

    DebugTree.group("🔧 Loading Service Options");

    const $container = $("#mobooking-service-options-container");
    $container.html(
      '<div class="mobooking-loading">Loading service options...</div>'
    );

    $.ajax({
      url: CONFIG.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_public_service_options",
        tenant_id: CONFIG.tenant_id,
        service_ids: formData.services,
        nonce: CONFIG.nonce,
      },
      success: function (response) {
        DebugTree.success("Service options loaded", response);
        debugResponses.push({
          action: "get_service_options",
          response: response,
        });

        if (response.success && response.data && response.data.length > 0) {
          displayServiceOptions(response.data);
        } else {
          $container.html(
            '<p class="text-gray-600">No additional options available for selected services.</p>'
          );
        }
      },
      error: function (xhr, status, error) {
        DebugTree.error("Failed to load service options", {
          xhr,
          status,
          error,
        });
        $container.html(
          '<p class="text-gray-600">Service options are temporarily unavailable.</p>'
        );
      },
    });

    DebugTree.groupEnd();
  }

  /**
   * Display service options
   */
  function displayServiceOptions(options) {
    displayedOptions = options; // Store for later use
    const $container = $("#mobooking-service-options-container");
    if ($container.length === 0 || !options || options.length === 0) {
      $container.html(
        '<p class="text-gray-600">No additional options available for selected services.</p>'
      );
      return;
    }

    DebugTree.info("Displaying service options", options);

    let html = '<div class="mobooking-service-options-list">';

    options.forEach((option) => {
      html += `<div class="mobooking-service-option">`;
      html += `<div class="mobooking-form-group">`;
      html += `<label for="option_${option.option_id}" class="mobooking-label">${option.name}`;

      if (option.is_required === "1" || option.is_required === 1) {
        html += " *";
      }

      html += `</label>`;

      if (option.description) {
        html += `<p class="mobooking-option-description">${option.description}</p>`;
      }

      // Generate input based on option type
      if (option.type === "number" || option.type === "quantity") {
        html += `<input type="number" name="service_options[${option.option_id}]" id="option_${option.option_id}" class="mobooking-input" min="0" step="1" placeholder="Enter quantity">`;
      } else if (option.type === "sqm" || option.type === 'kilometers') {
        const placeholder = option.type === 'sqm' ? "Enter square meters" : "Enter kilometers";
        const step = option.type === 'sqm' ? "0.01" : "0.1";
        html += `<input type="number" name="service_options[${option.option_id}]" id="option_${option.option_id}" class="mobooking-input" min="0" step="${step}" placeholder="${placeholder}" data-option-type="${option.type}">`;
      } else if (option.type === "text") {
        html += `<input type="text" name="service_options[${option.option_id}]" id="option_${option.option_id}" class="mobooking-input" placeholder="Enter text">`;
      } else if (option.type === "textarea") {
        html += `<textarea name="service_options[${option.option_id}]" id="option_${option.option_id}" class="mobooking-textarea" placeholder="Enter details"></textarea>`;
      } else if (option.type === "checkbox") {
        html += `<div class="mobooking-checkbox-group">`;
        html += `<label class="mobooking-checkbox-option">`;
        html += `<input type="checkbox" name="service_options[${option.option_id}]" id="option_${option.option_id}" value="1">`;
        html += `<span>Yes, add this option</span>`;
        html += `</label>`;
        html += `</div>`;
      } else if (option.type === "select" && option.option_values) {
        html += `<select name="service_options[${option.option_id}]" id="option_${option.option_id}" class="mobooking-select">`;
        html += `<option value="">Select an option</option>`;

        let selectOptions = [];
        try {
          selectOptions =
            typeof option.option_values === "string"
              ? JSON.parse(option.option_values)
              : option.option_values;
        } catch (e) {
          selectOptions = [];
        }

        if (Array.isArray(selectOptions)) {
          selectOptions.forEach((selectOption) => {
            html += `<option value="${selectOption.value || selectOption}">${
              selectOption.label || selectOption
            }</option>`;
          });
        }
        html += `</select>`;
      }

      // Show price impact if any
      if (option.price_impact_type && option.price_impact_value) {
        html += `<small class="mobooking-price-impact">`;
        if (option.price_impact_type === "fixed") {
          html += `+${option.price_impact_value}`;
        } else if (option.price_impact_type === "percentage") {
          html += `+${option.price_impact_value}%`;
        }
        html += `</small>`;
      }

      html += `</div>`;
      html += `</div>`;
    });

    html += "</div>";
    $container.html(html);

    DebugTree.success("Service options displayed successfully");
  }

  /**
   * Show step
   */
  function showStep(step) {
    DebugTree.info(`Showing step ${step}`);

    currentStep = step;

    // Hide all steps
    $(".mobooking-step-content").hide();

    // Show current step
    $(`#mobooking-step-${step}`).show();

    // Update progress bar if enabled
    if (CONFIG.form_config?.show_progress_bar) {
      updateProgressBar(step);
    }

    // Collect data when showing step
    collectAllFormData();
  }

  /**
   * Update progress bar
   */
  function updateProgressBar(step) {
    const totalSteps = 8; // Adjust based on your form
    const percentage = (step / totalSteps) * 100;

    $(".mobooking-progress-fill").css("width", `${percentage}%`);
    $(`.mobooking-step-indicator[data-step="${step}"]`)
      .addClass("active")
      .siblings()
      .removeClass("active");
  }

  /**
   * Validate current step
   */
  function validateCurrentStep() {
    collectAllFormData();

    switch (currentStep) {
      case 2: // Services
        if (formData.services.length === 0) {
          showFeedback(
            $("#mobooking-services-feedback"),
            "error",
            CONFIG.i18n.select_one_service
          );
          return false;
        }
        break;
      case 6: // Date & Time
        if (!formData.datetime.date) {
          showFeedback(
            $("#mobooking-datetime-feedback"),
            "error",
            CONFIG.i18n.date_required
          );
          return false;
        }
        if (!formData.datetime.time) {
          showFeedback(
            $("#mobooking-datetime-feedback"),
            "error",
            CONFIG.i18n.time_required
          );
          return false;
        }
        break;
      case 7: // Contact
        const required = [
          { field: "name", message: CONFIG.i18n.name_required },
          { field: "email", message: CONFIG.i18n.email_required },
          { field: "phone", message: CONFIG.i18n.phone_required },
          { field: "address", message: CONFIG.i18n.address_required },
        ];

        for (let req of required) {
          if (!formData.customer[req.field]) {
            showFeedback(
              $("#mobooking-contact-feedback"),
              "error",
              req.message
            );
            return false;
          }
        }

        if (!validateEmail(formData.customer.email)) {
          showFeedback(
            $("#mobooking-contact-feedback"),
            "error",
            "Please provide a valid email address."
          );
          return false;
        }
        break;
    }

    return true;
  }

  /**
   * Submit booking
   */
  function submitBooking() {
    DebugTree.group("📤 Submitting Booking");

    if (isSubmitting) {
      DebugTree.warning("Submission already in progress");
      return;
    }

    isSubmitting = true;

    // Collect latest form data
    collectAllFormData();

    // Validate all required data
    if (!validateBookingData()) {
      isSubmitting = false;
      DebugTree.groupEnd();
      return;
    }

    const $submitBtn = $(
      "button[onclick='moBookingSubmitForm()'], .mobooking-submit-booking"
    );
    const originalBtnHtml = $submitBtn.html();

    $submitBtn
      .prop("disabled", true)
      .html('<div class="mobooking-spinner"></div> Submitting...');

    // Prepare submission data
    const submissionData = prepareSubmissionData();

    DebugTree.info("Submitting with data", submissionData);

    $.ajax({
      url: CONFIG.ajax_url,
      type: "POST",
      data: submissionData,
      timeout: 30000,
      success: function (response) {
        DebugTree.success("Booking submission successful", response);

        if (response.success) {
          handleBookingSuccess(response.data);
        } else {
          const errorMessage =
            response.data?.message || CONFIG.i18n.booking_error;
          showFeedback($("#mobooking-contact-feedback"), "error", errorMessage);
        }
      },
      error: function (xhr, status, error) {
        DebugTree.error("Booking submission failed", { xhr, status, error });
        handleBookingError(xhr, status, error);
      },
      complete: function () {
        isSubmitting = false;
        $submitBtn.prop("disabled", false).html(originalBtnHtml);
        DebugTree.groupEnd();
      },
    });
  }

  /**
   * Validate booking data before submission
   */
  function validateBookingData() {
    const errors = [];

    if (!formData.services || formData.services.length === 0) {
      errors.push("No services selected");
    }

    if (!formData.customer.name) errors.push("Name is required");
    if (!formData.customer.email) errors.push("Email is required");
    if (!formData.customer.phone) errors.push("Phone is required");
    if (!formData.datetime.date) errors.push("Date is required");
    if (!formData.datetime.time) errors.push("Time is required");

    if (formData.customer.email && !validateEmail(formData.customer.email)) {
      errors.push("Valid email is required");
    }

    if (errors.length > 0) {
      DebugTree.error("Validation errors", errors);
      showFeedback(
        $("#mobooking-contact-feedback"),
        "error",
        errors.join(", ")
      );
      return false;
    }

    return true;
  }

  /**
   * Prepare data for submission
   */
  function prepareSubmissionData() {
    const customerDetails = {
      name: formData.customer.name,
      email: formData.customer.email,
      phone: formData.customer.phone,
      address: formData.customer.address,
      instructions: formData.customer.instructions,
      date: formData.datetime.date,
      time: formData.datetime.time,
    };

    const selectedServices = formData.services.map((serviceId) => ({
      service_id: parseInt(serviceId),
      configured_options: formData.options || {},
    }));

    const petInformation = {
      has_pets: formData.pets.has_pets,
      details: formData.pets.details,
    };

    const propertyAccess = {
      method: formData.access.method,
      details: formData.access.details,
    };

    return {
      action: "mobooking_create_booking",
      nonce: CONFIG.nonce,
      tenant_id: CONFIG.tenant_id,
      customer_details: JSON.stringify(customerDetails),
      selected_services: JSON.stringify(selectedServices),
      service_options: JSON.stringify(formData.options || {}),
      pet_information: JSON.stringify(petInformation),
      property_access: JSON.stringify(propertyAccess),
      service_frequency: formData.frequency || "one-time",
    };
  }

  /**
   * Handle successful booking
   */
  function handleBookingSuccess(data) {
    DebugTree.success("Booking created successfully", data);

    if (typeof populateBookingSummary === "function" && data.booking_data) {
      populateBookingSummary({ booking_data: data.booking_data });
    }

    if (typeof showStep === "function") {
      showStep(8); // Success step
    } else {
      showFeedback(
        $("#mobooking-contact-feedback"),
        "success",
        data.message || CONFIG.i18n.booking_submitted
      );
    }
  }

  /**
   * Handle booking errors
   */
  function handleBookingError(xhr, status, error) {
    let errorMessage = CONFIG.i18n.booking_error;

    if (
      xhr.responseJSON &&
      xhr.responseJSON.data &&
      xhr.responseJSON.data.message
    ) {
      errorMessage = xhr.responseJSON.data.message;
    } else if (xhr.status === 500) {
      errorMessage = "Server error occurred. Please try again.";
    } else if (xhr.status === 403) {
      errorMessage = "Security check failed. Please refresh the page.";
    } else if (xhr.status === 400) {
      errorMessage = "Invalid form data. Please check your information.";
    } else if (xhr.status === 0) {
      errorMessage = "Network error. Please check your connection.";
    }

    showFeedback($("#mobooking-contact-feedback"), "error", errorMessage);
  }

  /**
   * Utility functions
   */
  function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function showFeedback($element, type, message) {
    if ($element.length === 0) {
      console.log(`${type.toUpperCase()}: ${message}`);
      return;
    }

    $element
      .removeClass("success error info warning")
      .addClass(type)
      .html(message)
      .show();

    setTimeout(function () {
      $element.fadeOut();
    }, 5000);
  }

  function calculateTotalPrice() {
    let total = 0;

    // Base service price (assuming single service selection for now)
    if (formData.services.length > 0) {
        const serviceId = formData.services[0];
        const service = displayedServices.find(s => s.service_id == serviceId);
        if (service) {
            total += parseFloat(service.price);
        }
    }

    // Add option prices
    for (const optionId in formData.options) {
        const option = formData.options[optionId];
        if (option.type === 'sqm' || option.type === 'kilometers') {
            const value = parseFloat(option.value);
            if (!isNaN(value) && option.ranges) {
                for (const range of option.ranges) {
                    const from = parseFloat(range.from_sqm || range.from_km);
                    const to = (range.to_sqm === '∞' || range.to_km === '∞') ? Infinity : parseFloat(range.to_sqm || range.to_km);
                    if (value >= from && value <= to) {
                        const price_per_unit = parseFloat(range.price_per_sqm || range.price_per_km);
                        total += value * price_per_unit;
                        break; // Stop after finding the correct range
                    }
                }
            }
        }
        // Add other option price calculations here if needed (e.g., fixed, percentage)
    }

    return total.toFixed(2);
  }

  function updateLiveSummary() {
    // Update any live summary displays
    const $summary = $("#mobooking-live-summary");
    if ($summary.length > 0) {
      let html = "";

      if (formData.services.length > 0) {
        html += `<p>Services: ${formData.services.length} selected</p>`;
      }

      if (formData.datetime.date && formData.datetime.time) {
        html += `<p>Date: ${formData.datetime.date} at ${formData.datetime.time}</p>`;
      }

      const totalPrice = calculateTotalPrice();
      html += `<p><b>Total:</b> ${CONFIG.currency?.symbol || '$'}${totalPrice}</p>`;


      $summary.html(html);
    }
  }

  function updateDebugInfo() {
    if (
      !CONFIG.form_config?.debug_mode &&
      !window.location.search.includes("debug=1")
    ) {
      return;
    }

    const $debugInfo = $("#mobooking-debug-info");
    if ($debugInfo.length === 0) return;

    try {
      const debugData = {
        config: CONFIG,
        formData: formData,
        responses: debugResponses,
        currentStep: currentStep,
        timestamp: new Date().toISOString(),
      };

      $debugInfo.html(`
        <h4>🔧 Debug Information (Development)</h4>
        <div class="debug-section">
          <h5>Form Configuration:</h5>
          <pre>${JSON.stringify(CONFIG, null, 2)}</pre>
        </div>
        <div class="debug-section">
          <h5>Form Data:</h5>
          <pre>${JSON.stringify(formData, null, 2)}</pre>
        </div>
        <div class="debug-section">
          <h5>API Responses:</h5>
          <pre>${JSON.stringify(debugResponses, null, 2)}</pre>
        </div>
      `);
    } catch (error) {
      console.error("Error updating debug info:", error);
      $debugInfo.html(`
        <h4>🔧 Debug Information (Development)</h4>
        <p style="color: red;">Error displaying debug information: ${error.message}</p>
      `);
    }
  }

  function testFormSubmission() {
    console.log("🧪 Testing form submission");
    collectAllFormData();
    console.log("Current form data:", formData);
    console.log("Validation result:", validateBookingData());
  }

  /**
   * Global functions for form interaction
   */
  window.selectService = selectService;
  window.selectTimeSlot = selectTimeSlot;

  window.moBookingNextStep = function () {
    DebugTree.info("Next step requested");
    if (validateCurrentStep()) {
      collectAllFormData();
      showStep(currentStep + 1);
    }
  };

  window.moBookingPreviousStep = function () {
    DebugTree.info("Previous step requested");
    if (currentStep > 1) {
      showStep(currentStep - 1);
    }
  };

  window.moBookingSubmitForm = function () {
    DebugTree.info("Form submission requested");
    collectAllFormData();
    submitBooking();
  };

  // Initialize when document is ready
  $(document).ready(function () {
    initializeBookingForm();
  });

  DebugTree.success("MoBooking Public Form JavaScript loaded successfully");
})(jQuery);
