/**
 * MoBooking Public Booking Form JavaScript
 * File: assets/js/booking-form-public.js
 */

(function ($) {
  "use strict";

  // Debug Tree Console Logger
  const DebugTree = {
    logs: [],
    level: 0,

    log: function (message, data = null, type = "info") {
      const timestamp = new Date().toISOString();
      const indent = "  ".repeat(this.level);
      const logEntry = {
        timestamp,
        level: this.level,
        message,
        data,
        type,
      };

      this.logs.push(logEntry);

      // Console output with formatting
      const consoleMessage = `%c[MoBooking ${type.toUpperCase()}] ${indent}${message}`;
      const styles = this.getConsoleStyles(type);

      if (data) {
        console.groupCollapsed(consoleMessage, styles);
        console.log(data);
        console.groupEnd();
      } else {
        console.log(consoleMessage, styles);
      }
    },

    getConsoleStyles: function (type) {
      const styles = {
        info: "color: #1abc9c; font-weight: bold;",
        success: "color: #27ae60; font-weight: bold;",
        warning: "color: #f39c12; font-weight: bold;",
        error: "color: #e74c3c; font-weight: bold;",
        debug: "color: #9b59b6; font-weight: bold;",
      };
      return styles[type] || styles.info;
    },

    group: function (title) {
      this.log(`📁 ${title}`, null, "debug");
      this.level++;
    },

    groupEnd: function () {
      this.level = Math.max(0, this.level - 1);
    },

    error: function (message, data = null) {
      this.log(`❌ ${message}`, data, "error");
    },

    success: function (message, data = null) {
      this.log(`✅ ${message}`, data, "success");
    },

    warning: function (message, data = null) {
      this.log(`⚠️ ${message}`, data, "warning");
    },

    info: function (message, data = null) {
      this.log(`ℹ️ ${message}`, data, "info");
    },

    debug: function (message, data = null) {
      this.log(`🔧 ${message}`, data, "debug");
    },

    exportLogs: function () {
      return {
        logs: this.logs,
        summary: {
          total: this.logs.length,
          errors: this.logs.filter((l) => l.type === "error").length,
          warnings: this.logs.filter((l) => l.type === "warning").length,
          successes: this.logs.filter((l) => l.type === "success").length,
        },
      };
    },
  };

  // Global variables
  let currentStep = 1;
  let maxCompletedStep = 1;
  let formData = {
    location: {},
    services: [],
    options: {},
    pets: {},
    frequency: "one-time",
    datetime: {},
    customer: {},
    access: {},
  };
  let debugResponses = [];
  let CONFIG = null;

  // Initialize when document is ready
  $(document).ready(function () {
    initializeBookingForm();
  });

  function initializeBookingForm() {
    DebugTree.group("🚀 Initializing MoBooking Form");

    // Check for configuration
    if (typeof window.MOBOOKING_CONFIG === "undefined") {
      DebugTree.error("MOBOOKING_CONFIG not found in window object");
      checkAlternativeConfigs();
      return;
    }

    CONFIG = window.MOBOOKING_CONFIG;
    DebugTree.success("Configuration loaded", CONFIG);

    // Continue with normal initialization
    initializeWithConfig();
  }

  function checkAlternativeConfigs() {
    DebugTree.group("🔍 Checking Alternative Configurations");

    const alternatives = [
      "mobooking_booking_form_params",
      "MOBOOKING_PARAMS",
      "mobooking_config",
      "MoBookingConfig",
    ];

    alternatives.forEach((alt) => {
      if (typeof window[alt] !== "undefined") {
        DebugTree.success(`Found alternative config: ${alt}`, window[alt]);
        CONFIG = window[alt];
        return; // Exit early once we find a config
      } else {
        DebugTree.debug(`${alt} not found`);
      }
    });

    if (!CONFIG) {
      DebugTree.error("No configuration found anywhere");
      showConfigurationError();
    } else {
      DebugTree.success("Using alternative configuration", CONFIG);
      // Continue with initialization
      initializeWithConfig();
    }

    DebugTree.groupEnd();
  }

  function initializeWithConfig() {
    DebugTree.group("🔧 Initializing with Found Config");

    // Validate configuration
    if (!validateConfiguration()) {
      DebugTree.error("Configuration validation failed");
      showConfigurationError();
      return;
    }

    // Set initial step
    currentStep = CONFIG.form_config.enable_area_check ? 1 : 2;
    DebugTree.info(`Starting at step ${currentStep}`);

    // Initialize form components
    DebugTree.group("🔧 Initializing Components");
    initializeEventHandlers();
    initializeDatePicker();
    initializeFormState();
    DebugTree.groupEnd();

    // Start form
    showStep(currentStep);

    DebugTree.groupEnd();
    DebugTree.success("Form initialization complete");

    // Expose debug functions globally
    window.MoBookingDebug = {
      tree: DebugTree,
      config: CONFIG,
      formData: formData,
      responses: debugResponses,
      testAjax: testAjaxEndpoint,
      exportDebug: exportDebugData,
    };

    DebugTree.info("Debug tools available at window.MoBookingDebug");
  }

  function validateConfiguration() {
    DebugTree.group("✅ Validating Configuration");

    const required = ["ajax_url", "nonce", "tenant_id"];
    let isValid = true;

    // Log the full config first for debugging
    DebugTree.info("Full config object:", CONFIG);

    required.forEach((key) => {
      if (!CONFIG[key]) {
        DebugTree.error(`Missing required config: ${key}`);
        isValid = false;
      } else {
        DebugTree.success(`✓ ${key}:`, CONFIG[key]);
      }
    });

    // Check for form_config, but create default if missing
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

    // Check for i18n, create default if missing
    if (!CONFIG.i18n) {
      DebugTree.warning("i18n missing, creating default");
      CONFIG.i18n = {
        loading_services: "Loading services...",
        select_service: "Please select at least one service.",
        booking_error: "There was an error. Please try again.",
      };
      DebugTree.success("Default i18n created:", CONFIG.i18n);
    } else {
      DebugTree.success("✓ i18n:", CONFIG.i18n);
    }

    DebugTree.groupEnd();
    return isValid;
  }

  function initializeEventHandlers() {
    DebugTree.group("🎯 Setting Up Event Handlers");

    // Area check form
    $("#mobooking-area-check-form")
      .off("submit")
      .on("submit", function (e) {
        e.preventDefault();
        DebugTree.info("Area check form submitted");
        checkServiceArea();
      });

    // Pet question toggle
    $('input[name="has_pets"]')
      .off("change")
      .on("change", function () {
        DebugTree.info(`Pet selection changed to: ${this.value}`);
        if (this.value === "yes") {
          $("#mobooking-pet-details-container").removeClass("hidden");
        } else {
          $("#mobooking-pet-details-container").addClass("hidden");
        }
      });

    // Property access toggle
    $('input[name="property_access"]')
      .off("change")
      .on("change", function () {
        DebugTree.info(`Property access changed to: ${this.value}`);
        if (this.value === "other") {
          $("#mobooking-custom-access-details").removeClass("hidden");
        } else {
          $("#mobooking-custom-access-details").addClass("hidden");
        }
      });

    DebugTree.success("Event handlers attached");
    DebugTree.groupEnd();
  }

  function initializeDatePicker() {
    DebugTree.group("📅 Initializing Date Picker");

    if (typeof flatpickr !== "undefined") {
      flatpickr("#mobooking-service-date", {
        minDate: "today",
        dateFormat: "Y-m-d",
        onChange: function (selectedDates, dateStr) {
          DebugTree.info(`Date selected: ${dateStr}`);
          formData.datetime.date = dateStr; // Store the date immediately
          if (dateStr) {
            loadTimeSlots(dateStr);
          } else {
            // Clear time slots if date is cleared
            $("#mobooking-time-slots-container").addClass("hidden");
            formData.datetime.time = null;
          }
          updateLiveSummary();
          updateDebugInfo();
        },
      });
      DebugTree.success("Flatpickr initialized");
    } else {
      DebugTree.warning("Flatpickr not available");
    }

    DebugTree.groupEnd();
  }

  function initializeFormState() {
    DebugTree.group("📊 Initializing Form State");

    updateProgressBar();
    updateLiveSummary();
    updateDebugInfo();

    DebugTree.success("Form state initialized");
    DebugTree.groupEnd();
  }

  function showStep(step) {
    DebugTree.group(`🎬 Showing Step ${step}`);

    // Skip disabled steps
    if (step === 4 && !CONFIG.form_config.enable_pet_information) {
      DebugTree.info("Skipping pet information step (disabled)");
      step++;
    }
    if (step === 5 && !CONFIG.form_config.enable_service_frequency) {
      DebugTree.info("Skipping service frequency step (disabled)");
      step++;
    }
    if (step === 6 && !CONFIG.form_config.enable_datetime_selection) {
      DebugTree.info("Skipping datetime selection step (disabled)");
      step++;
    }
    if (step === 7 && !CONFIG.form_config.enable_property_access) {
      DebugTree.info("Skipping property access step (disabled)");
      step++;
    }

    // Hide all steps
    $(".mobooking-step-content").removeClass("active");

    // Show target step
    const $targetStep = $(`#mobooking-step-${step}`);
    if ($targetStep.length) {
      $targetStep.addClass("active");
      DebugTree.success(`Step ${step} is now active`);
    } else {
      DebugTree.error(`Step ${step} element not found`);
    }

    currentStep = step;
    maxCompletedStep = Math.max(maxCompletedStep, step);

    updateProgressBar();
    updateLiveSummary();
    updateDebugInfo();

    // Load step-specific data
    if (step === 2) {
      DebugTree.info("Loading services for step 2");
      loadServices();
    } else if (step === 3) {
      DebugTree.info("Loading service options for step 3");
      loadServiceOptions();
    }

    DebugTree.groupEnd();
  }

  function loadServices() {
    DebugTree.group("🛠️ Loading Services");

    const $container = $("#mobooking-services-container");
    $container.html(
      '<div style="text-align: center; padding: 40px 0;"><div class="mobooking-spinner"></div><span>Loading services...</span></div>'
    );

    const ajaxData = {
      action: "mobooking_get_public_services",
      nonce: CONFIG.nonce,
      tenant_id: CONFIG.tenant_id,
    };

    DebugTree.info("Making AJAX request", {
      url: CONFIG.ajax_url,
      data: ajaxData,
    });

    $.ajax({
      url: CONFIG.ajax_url,
      type: "POST",
      data: ajaxData,
      success: function (response) {
        DebugTree.group("📥 Services AJAX Response");
        DebugTree.success("AJAX request successful", response);

        debugResponses.push({
          action: "get_public_services",
          success: true,
          response: response,
          timestamp: new Date().toISOString(),
        });

        if (response.success && response.data && Array.isArray(response.data)) {
          DebugTree.success(`Received ${response.data.length} services`);
          renderServices(response.data);
        } else {
          DebugTree.error("Invalid service response structure", response);
          $container.html(
            '<p style="text-align: center; color: #6b7280;">No services available at the moment.</p>'
          );
        }

        DebugTree.groupEnd();
      },
      error: function (xhr, status, error) {
        DebugTree.group("❌ Services AJAX Error");

        const errorData = {
          status: xhr.status,
          statusText: xhr.statusText,
          responseText: xhr.responseText,
          error: error,
        };

        DebugTree.error("AJAX request failed", errorData);

        debugResponses.push({
          action: "get_public_services",
          success: false,
          error: errorData,
          timestamp: new Date().toISOString(),
        });

        $container.html(
          '<p style="text-align: center; color: #ef4444;">Error loading services. Check console for details.</p>'
        );

        DebugTree.groupEnd();
      },
      complete: function () {
        updateDebugInfo();
      },
    });

    DebugTree.groupEnd();
  }

  function renderServices(services) {
    DebugTree.group("🎨 Rendering Services");
    DebugTree.info(`Rendering ${services.length} services`);

    const $container = $("#mobooking-services-container");

    if (!services || !Array.isArray(services) || services.length === 0) {
      DebugTree.warning("No services to render");
      $container.html(
        '<p style="text-align: center; color: #6b7280;">No services available at the moment.</p>'
      );
      DebugTree.groupEnd();
      return;
    }

    let html = '<div class="mobooking-grid mobooking-grid-2">';

    services.forEach(function (service, index) {
      const serviceId = service.service_id || service.id;
      const price = service.price ? parseFloat(service.price) : 0;
      const priceDisplay =
        price > 0 ? `${price.toFixed(2)}` : "Price on request";
      const description = service.description || "Professional service";
      const duration = service.duration || "";

      DebugTree.debug(`Service ${index + 1}`, {
        id: serviceId,
        name: service.name,
        price: price,
        description: description,
      });

      html += `
                <div class="mobooking-service-card" data-service-id="${serviceId}" onclick="selectService(${serviceId})">
                    <input type="radio" name="selected_service" value="${serviceId}" style="display: none;">
                    <div class="mobooking-service-title">${service.name}</div>
                    <div class="mobooking-service-description">${description}</div>
                    ${
                      duration
                        ? `<div class="mobooking-service-duration">Duration: ${duration}</div>`
                        : ""
                    }
                    <div class="mobooking-service-price">${priceDisplay}</div>
                </div>
            `;
    });

    html += "</div>";
    $container.html(html);

    DebugTree.success("Services rendered successfully");
    DebugTree.groupEnd();
  }

  function loadServiceOptions() {
    const selectedServices = formData.services;
    const $container = $("#mobooking-service-options-container");

    if (selectedServices.length === 0) {
      DebugTree.info("No services selected, showing placeholder");
      $container.html(
        '<p style="color: #6b7280;">Please select a service first.</p>'
      );
      return;
    }

    DebugTree.group("⚙️ Loading Service Options");
    DebugTree.info(
      `Loading options for ${selectedServices.length} services`,
      selectedServices
    );

    $container.html(
      '<div style="text-align: center; padding: 20px 0;"><div class="mobooking-spinner"></div><span>Loading service options...</span></div>'
    );

    // Try the BookingFormAjax handler first with correct parameters
    const ajaxData = {
      action: "mobooking_get_public_service_options",
      nonce: MOBOOKING_CONFIG.nonce, // ✅ Correct
      tenant_id: MOBOOKING_CONFIG.tenant_id, // ✅ Correct
      service_ids: selectedServices,
    };

    DebugTree.info("Service options AJAX request:", ajaxData);

    $.ajax({
      url: CONFIG.ajax_url,
      type: "POST",
      data: ajaxData,
      success: function (response) {
        DebugTree.success("Service options loaded", response);
        debugResponses.push({
          action: "get_service_options",
          response: response,
        });

        if (response.success && response.data) {
          renderServiceOptions(response.data);
        } else {
          DebugTree.warning("No service options available", response);
          $container.html(
            '<p style="color: #6b7280;">No additional options available for selected services.</p>'
          );
        }
      },
      error: function (xhr, status, error) {
        DebugTree.error("Service options loading failed", {
          status: xhr.status,
          statusText: xhr.statusText,
          responseText: xhr.responseText,
          error: error,
          ajaxData: ajaxData,
        });
        $container.html(
          '<p style="color: #6b7280;">Service options are not available at this time. You can continue to the next step.</p>'
        );
      },
      complete: function () {
        updateDebugInfo();
      },
    });

    DebugTree.groupEnd();
  }

  function renderServiceOptions(options) {
    DebugTree.group("🎛️ Rendering Service Options");
    DebugTree.info(`Rendering ${options.length} options`);

    const $container = $("#mobooking-service-options-container");
    let html = "";

    options.forEach(function (option) {
      DebugTree.debug("Processing option", option);

      html += '<div class="mobooking-form-group">';
      html += `<label for="option-${option.id}" class="mobooking-label">${
        option.name
      }${option.required ? " *" : ""}</label>`;

      if (option.type === "text") {
        html += `<input type="text" id="option-${
          option.id
        }" class="mobooking-input" name="service_options[${option.id}]" ${
          option.required ? "required" : ""
        }>`;
      } else if (option.type === "number" || option.type === "quantity") {
        html += `<div class="flex items-center gap-2">
            <button type="button" onclick="this.nextElementSibling.stepDown()" class="px-3 py-1 border rounded">−</button>
            <input type="number" value="1" min="0" id="option-${option.id}" class="w-20 text-center px-3 py-2 border rounded mobooking-input" name="service_options[${option.id}]" ${option.required ? "required" : ""}>
            <button type="button" onclick="this.previousElementSibling.stepUp()" class="px-3 py-1 border rounded">+</button>
        </div>`;
      } else if (option.type === "sqm") {
        const sliderId = `sqm-slider-${option.id}`;
        const inputId = `sqm-input-${option.id}`;
        html += `<div class="mobooking-bf__sqm-input-group">
            <input type="range" id="${sliderId}" min="0" max="500" value="50" class="mobooking-bf__slider" oninput="document.getElementById('${inputId}').value = this.value">
            <input type="number" id="${inputId}" min="0" max="500" value="50" class="w-20 px-2 py-1 border rounded text-center mobooking-bf__input--number" name="service_options[${option.id}]" ${option.required ? "required" : ""} oninput="document.getElementById('${sliderId}').value = this.value">
        </div>`;
      } else if (option.type === 'textarea') {
        html += `<textarea id="option-${
          option.id
        }" class="mobooking-textarea" name="service_options[${option.id}]" ${
          option.required ? "required" : ""
        }></textarea>`;
      } else if (option.type === "select") {
        const optionId = `option-${option.id}`;
        const dropdownId = `dropdown-${option.id}`;
        const toggleId = `dropdown-toggle-${option.id}`;
        const optionsId = `dropdown-options-${option.id}`;

        html += `<div class="relative" id="${dropdownId}">
            <input type="hidden" name="service_options[${option.id}]" id="${optionId}">
            <div id="${toggleId}" class="cursor-pointer border px-4 py-2 rounded bg-white hover:bg-gray-50 mobooking-bf__input" onclick="toggleDropdown('${optionsId}')">
                Choose...
            </div>
            <div id="${optionsId}" class="absolute w-full border mt-1 rounded bg-white shadow hidden z-10">`;

        if (option.option_values) {
            let values = option.option_values;
            if (typeof values === 'string') {
                try {
                    values = JSON.parse(values);
                } catch (e) {
                    DebugTree.error('Failed to parse option_values for select', { optionId: option.id, values: option.option_values });
                    values = [];
                }
            }

            if (Array.isArray(values)) {
                values.forEach(function (item) {
                    html += `<div class="px-4 py-2 hover:bg-gray-100 cursor-pointer" onclick="selectDropdownOption(this, '${toggleId}', '${optionId}', '${optionsId}')" data-value="${item.value}">${item.label}</div>`;
                });
            }
        }

        html += `</div></div>`;
      } else if (option.type === "radio") {
        html += '<div class="grid grid-cols-1 gap-3">';
        if (option.option_values) {
            let values = option.option_values;
            if (typeof values === 'string') {
                try {
                    values = JSON.parse(values);
                } catch (e) {
                    DebugTree.error('Failed to parse option_values for radio', { optionId: option.id, values: option.option_values });
                    values = [];
                }
            }

            if (Array.isArray(values)) {
                values.forEach(function (item) {
                    const radioId = `option-${option.id}-${item.value}`;
                    // Note: The classes like 'grid', 'gap-3', 'peer', 'hidden' are placeholders for the new CSS classes I will add.
                    html += `<label class="mobooking-bf__radio-card">
                        <input type="radio" id="${radioId}" name="service_options[${option.id}]" value="${item.value}" class="mobooking-bf__radio-card-input" ${option.required ? "required" : ""}>
                        <div class="mobooking-bf__radio-card-content">
                            <div class="mobooking-bf__radio-card-title">${item.label}</div>
                            ${item.description ? `<div class="mobooking-bf__radio-card-subtitle">${item.description}</div>` : ''}
                        </div>
                    </label>`;
                });
            }
        }
        html += '</div>';
      } else if (option.type === "checkbox") {
        html += `<label><input type="checkbox" id="option-${
          option.id
        }" name="service_options[${option.id}]" value="1"> ${
          option.description || option.name
        }</label>`;
      }

      if (option.description && option.type !== "checkbox") {
        html += `<p style="font-size: 14px; color: #6b7280; margin-top: 5px;">${option.description}</p>`;
      }

      html += "</div>";
    });

    $container.html(
      html ||
        '<p style="color: #6b7280;">No additional options available for selected services.</p>'
    );

    DebugTree.success("Service options rendered");
    DebugTree.groupEnd();
  }

  function loadTimeSlots(date) {
    DebugTree.group(`⏰ Loading Time Slots for ${date}`);

    const $container = $("#mobooking-time-slots-container");
    const $slotsGrid = $("#mobooking-time-slots");

    $container.removeClass("hidden");
    $slotsGrid.html(
      '<div style="text-align: center; padding: 20px;"><div class="mobooking-spinner"></div><span>Loading available times...</span></div>'
    );

    // Try the BookingFormAjax handler first
    const ajaxData = {
      action: "mobooking_get_available_time_slots",
      nonce: CONFIG.nonce,
      tenant_id: CONFIG.tenant_id,
      date: date,
      services: formData.services, // Send as an array
    };

    DebugTree.info("Time slots AJAX request:", ajaxData);

    $.ajax({
      url: CONFIG.ajax_url,
      type: "POST",
      data: ajaxData,
      success: function (response) {
        DebugTree.success("Time slots loaded", response);
        debugResponses.push({
          action: "get_available_time_slots",
          response: response,
        });

        if (response.success && response.data) {
          // Handle different response formats
          let slots = response.data;
          if (response.data.time_slots) {
            slots = response.data.time_slots;
          }
          renderTimeSlots(slots);
        } else {
          DebugTree.warning("No time slots available", response);
          $slotsGrid.html(
            '<p style="text-align: center; color: #6b7280;">No available time slots for this date.</p>'
          );
        }
      },
      error: function (xhr, status, error) {
        DebugTree.error("Time slots loading failed", {
          status: xhr.status,
          statusText: xhr.statusText,
          responseText: xhr.responseText,
          error: error,
          ajaxData: ajaxData,
        });

        // Try fallback with different action name
        DebugTree.warning("Trying fallback time slots handler");
        loadTimeSlotsFallback(date);
      },
      complete: function () {
        updateDebugInfo();
      },
    });

    DebugTree.groupEnd();
  }

  function loadTimeSlotsFallback(date) {
    DebugTree.group(`⏰ Loading Time Slots (Fallback) for ${date}`);

    const $slotsGrid = $("#mobooking-time-slots");

    // Try the functions.php handler
    const fallbackData = {
      action: "mobooking_get_available_slots", // Different action name
      nonce: CONFIG.nonce,
      tenant_id: CONFIG.tenant_id, // Back to tenant_id
      date: date,
    };

    DebugTree.info("Fallback time slots request:", fallbackData);

    $.ajax({
      url: CONFIG.ajax_url,
      type: "POST",
      data: fallbackData,
      success: function (response) {
        DebugTree.success("Fallback time slots loaded", response);
        debugResponses.push({
          action: "get_available_slots_fallback",
          response: response,
        });

        if (response.success && response.data) {
          renderTimeSlots(response.data);
        } else {
          DebugTree.warning("Fallback also returned no slots", response);
          $slotsGrid.html(
            '<p style="text-align: center; color: #6b7280;">No available time slots for this date.</p>'
          );
        }
      },
      error: function (xhr, status, error) {
        DebugTree.error("Fallback time slots also failed", {
          xhr: xhr.responseText,
          status,
          error,
        });

        // Generate default time slots as last resort
        DebugTree.warning("Generating default time slots");
        const defaultSlots = generateDefaultTimeSlots();
        renderTimeSlots(defaultSlots);
      },
      complete: function () {
        updateDebugInfo();
      },
    });

    DebugTree.groupEnd();
  }

  function generateDefaultTimeSlots() {
    DebugTree.info("Generating default time slots");
    const slots = [];
    const startHour = 9;
    const endHour = 17;

    for (let hour = startHour; hour < endHour; hour++) {
      const startTime24 = String(hour).padStart(2, "0") + ":00:00";
      const endTime24 = String(hour + 1).padStart(2, "0") + ":00:00";

      slots.push({
        start_time: startTime24,
        end_time: endTime24,
      });
    }

    DebugTree.success(`Generated ${slots.length} default time slots`);
    return slots;
  }

  function formatTime(timeStr) {
    if (!timeStr) return '';
    // Check if the time string includes seconds
    const hasSeconds = timeStr.split(':').length === 3;
    const date = new Date(`1970-01-01T${timeStr}`);
    return date.toLocaleTimeString(navigator.language, {
      hour: 'numeric',
      minute: '2-digit',
      hour12: true
    });
  }

  function renderTimeSlots(slots) {
    DebugTree.group("🕐 Rendering Time Slots");
    DebugTree.info(`Rendering ${slots.length} time slots`);

    const $slotsGrid = $("#mobooking-time-slots");
    let html = "";

    slots.forEach(function (slot) {
      const startTime = slot.start_time || slot.time; // Handle both response structures
      const endTime = slot.end_time;

      if (startTime) {
        let display;
        if (endTime) {
          // Format both start and end times
          display = `${formatTime(startTime)} - ${formatTime(endTime)}`;
        } else {
          // Fallback for older structures or if end_time is missing
          display = formatTime(startTime);
        }

        html += `
          <div class="mobooking-time-slot" data-time="${startTime}" onclick="selectTimeSlot('${startTime}')">
              ${display}
          </div>
        `;
      } else {
        DebugTree.warning("Slot object is missing time information", slot);
      }
    });

    if (html === "") {
        html = '<p style="text-align: center; color: #6b7280;">No available time slots for this date.</p>';
        DebugTree.warning("Rendered no time slots as all were invalid.");
    }

    $slotsGrid.html(html);
    DebugTree.success("Time slots rendered");
    DebugTree.groupEnd();
  }

  function updateProgressBar() {
    if (!CONFIG.form_config.show_progress_bar) return;

    const totalSteps = 8;
    const progress = (currentStep / totalSteps) * 100;
    $("#mobooking-progress-fill").css("width", progress + "%");

    // Update step indicators
    $(".mobooking-step-indicator").each(function () {
      const stepNum = parseInt($(this).data("step"));
      $(this).removeClass("active completed");

      if (stepNum === currentStep) {
        $(this).addClass("active");
      } else if (stepNum < currentStep) {
        $(this).addClass("completed");
      }
    });
  }

  function updateLiveSummary() {
    DebugTree.debug("Updating live summary");
    const $content = $("#mobooking-summary-content");
    let summary = [];

    if (formData.location.zip_code) {
      summary.push(
        `<strong>Location:</strong> ${formData.location.zip_code}, ${formData.location.country_code}`
      );
    }

    if (formData.services.length > 0) {
      summary.push(
        `<strong>Services:</strong> ${formData.services.length} selected`
      );
    }

    if (formData.frequency) {
      summary.push(`<strong>Frequency:</strong> ${formData.frequency}`);
    }

    if (formData.datetime.date) {
      summary.push(`<strong>Date:</strong> ${formData.datetime.date}`);
    }

    if (formData.datetime.time) {
      summary.push(`<strong>Time:</strong> ${formData.datetime.time}`);
    }

    if (summary.length > 0) {
      $content.html(summary.join("<br>"));
    } else {
      $content.html("<p>Complete the form to see your booking summary</p>");
    }
  }

  function updateDebugInfo() {
    if ($("#mobooking-debug-section").length) {
      $("#mobooking-debug-config").text(JSON.stringify(CONFIG, null, 2));
      $("#mobooking-debug-data").text(JSON.stringify(formData, null, 2));
      $("#mobooking-debug-responses").text(
        JSON.stringify(debugResponses, null, 2)
      );
    }
  }

  function showConfigurationError() {
    const errorHtml = `
            <div style="padding: 20px; text-align: center; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; margin: 20px;">
                <h3 style="color: #dc2626; margin-bottom: 10px;">⚠️ Configuration Error</h3>
                <p style="color: #7f1d1d;">The booking form configuration could not be loaded. Please refresh the page or contact support.</p>
                <button onclick="location.reload()" style="margin-top: 10px; padding: 8px 16px; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Refresh Page
                </button>
            </div>
        `;
    $("#mobooking-services-container").html(errorHtml);
  }

  function testAjaxEndpoint() {
    DebugTree.group("🧪 Testing AJAX Endpoint");

    const testData = {
      action: "mobooking_get_public_services",
      nonce: CONFIG.nonce,
      tenant_id: CONFIG.tenant_id,
    };

    DebugTree.info("Testing with data", testData);

    return $.ajax({
      url: CONFIG.ajax_url,
      type: "POST",
      data: testData,
    })
      .done(function (response) {
        DebugTree.success("Test successful", response);
      })
      .fail(function (xhr, status, error) {
        DebugTree.error("Test failed", {
          xhr: xhr.responseText,
          status,
          error,
        });
      })
      .always(function () {
        DebugTree.groupEnd();
      });
  }

  function exportDebugData() {
    return {
      config: CONFIG,
      formData: formData,
      debugResponses: debugResponses,
      debugTree: DebugTree.exportLogs(),
      currentStep: currentStep,
      timestamp: new Date().toISOString(),
    };
  }

  // Global function exports for onclick handlers
  window.selectService = function (serviceId) {
    DebugTree.info(`Selecting service ${serviceId}`);

    // Remove selection from all cards
    $(".mobooking-service-card").removeClass("selected");
    $('input[name="selected_service"]').prop("checked", false);

    // Select the clicked card
    const $card = $(`.mobooking-service-card[data-service-id="${serviceId}"]`);
    const $radio = $card.find('input[type="radio"]');

    $card.addClass("selected");
    $radio.prop("checked", true);

    // Update form data (single service selection)
    formData.services = [serviceId];

    DebugTree.info(`Service ${serviceId} selected`);
    updateLiveSummary();
    updateDebugInfo();
  };

  window.selectTimeSlot = function (time) {
    DebugTree.info(`Time slot selected: ${time}`);

    // Robustness check: Ensure time is a non-empty string and not the string "undefined"
    if (typeof time !== 'string' || time.trim() === '' || time === 'undefined') {
      DebugTree.warning('Invalid time value passed to selectTimeSlot', time);
      return; // Exit if time is not valid
    }

    $(".mobooking-time-slot").removeClass("selected");
    $(`.mobooking-time-slot[data-time="${time}"]`).addClass("selected");
    formData.datetime.time = time;
    updateLiveSummary();
    updateDebugInfo();
  };

  window.moBookingNextStep = function () {
    DebugTree.info("Next step requested");
    if (validateCurrentStep()) {
      collectStepData();
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
    collectStepData();
    submitBooking();
  };

  function submitBooking() {
    DebugTree.group("📤 Submitting Booking (Demo Mode)");

    if (!validateCurrentStep()) {
      DebugTree.error("Validation failed, cannot submit");
      return;
    }

    // Show loading state
    const $feedback = $("#mobooking-contact-feedback");
    const $submitBtn = $('button:contains("Submit Booking")');
    const originalBtnHtml = $submitBtn.html();

    $submitBtn
      .prop("disabled", true)
      .html('<div class="mobooking-spinner"></div> Submitting booking...');

    // Simulate successful booking submission
    DebugTree.info("Simulating booking submission...");

    setTimeout(() => {
      const mockBookingData = {
        booking_reference: "MB-" + Date.now(),
        booking_id: Math.floor(Math.random() * 1000) + 1,
        total_amount: 150.0,
        message: "Booking submitted successfully!",
      };

      DebugTree.success("Mock booking submitted successfully", mockBookingData);
      debugResponses.push({
        action: "submit_booking_demo",
        response: { success: true, data: mockBookingData },
      });

      populateBookingSummary(mockBookingData);
      showStep(8);

      $submitBtn.prop("disabled", false).html(originalBtnHtml);
      updateDebugInfo();
    }, 2000); // 2 second delay to simulate processing

    DebugTree.groupEnd();
  }

  function validateCurrentStep() {
    DebugTree.debug(`Validating step ${currentStep}`);

    switch (currentStep) {
      case 2:
        if (formData.services.length === 0) {
          DebugTree.warning("No service selected");
          showFeedback(
            $("#mobooking-service-feedback"),
            "error",
            "Please select a service."
          );
          return false;
        }
        break;
      case 4:
        if (
          $('input[name="has_pets"]:checked').val() === "yes" &&
          !$("#mobooking-pet-details").val().trim()
        ) {
          DebugTree.warning("Pet details required but missing");
          showFeedback(
            $("#mobooking-pet-feedback"),
            "error",
            "Please provide details about your pets."
          );
          return false;
        }
        break;
      case 6:
        if (!formData.datetime.date) {
          DebugTree.warning("Date not selected");
          showFeedback(
            $("#mobooking-datetime-feedback"),
            "error",
            "Please select a date."
          );
          return false;
        }
        if (!formData.datetime.time) {
          DebugTree.warning("Time not selected");
          showFeedback(
            $("#mobooking-datetime-feedback"),
            "error",
            "Please select a time slot."
          );
          return false;
        }
        break;
      case 7:
        if (!$("#mobooking-customer-name").val().trim()) {
          showFeedback(
            $("#mobooking-contact-feedback"),
            "error",
            "Please enter your name."
          );
          return false;
        }
        if (
          !$("#mobooking-customer-email").val().trim() ||
          !validateEmail($("#mobooking-customer-email").val())
        ) {
          showFeedback(
            $("#mobooking-contact-feedback"),
            "error",
            "Please enter a valid email address."
          );
          return false;
        }
        if (!$("#mobooking-customer-phone").val().trim()) {
          showFeedback(
            $("#mobooking-contact-feedback"),
            "error",
            "Please enter your phone number."
          );
          return false;
        }
        if (!$("#mobooking-service-address").val().trim()) {
          showFeedback(
            $("#mobooking-contact-feedback"),
            "error",
            "Please enter the service address."
          );
          return false;
        }
        if (
          $('input[name="property_access"]:checked').val() === "other" &&
          !$("#mobooking-access-instructions").val().trim()
        ) {
          showFeedback(
            $("#mobooking-contact-feedback"),
            "error",
            "Please provide access details."
          );
          return false;
        }
        break;
    }

    DebugTree.success(`Step ${currentStep} validation passed`);
    return true;
  }

  function collectStepData() {
    DebugTree.debug(`Collecting data for step ${currentStep}`);

    switch (currentStep) {
      case 1:
        formData.location = {
          zip_code: $("#mobooking-zip").val(),
          country_code: $("#mobooking-country").val(),
        };
        DebugTree.info("Collected location data", formData.location);
        break;
      case 2:
        // Services already updated in selectService function
        DebugTree.info("Services data already collected", formData.services);
        break;
      case 3:
        // Collect service options
        const options = {};
        $('[name^="service_options"]').each(function () {
          const name = $(this).attr("name");
          const match = name.match(/service_options\[(\d+)\]/);
          if (match) {
            const optionId = match[1];
            if ($(this).attr("type") === "checkbox") {
              options[optionId] = $(this).is(":checked") ? 1 : 0;
            } else {
              options[optionId] = $(this).val();
            }
          }
        });
        formData.options = options;
        DebugTree.info("Collected service options", formData.options);
        break;
      case 4:
        formData.pets = {
          has_pets: $('input[name="has_pets"]:checked').val() === "yes",
          details: $("#mobooking-pet-details").val(),
        };
        DebugTree.info("Collected pet data", formData.pets);
        break;
      case 5:
        formData.frequency = $('input[name="frequency"]:checked').val();
        DebugTree.info("Collected frequency data", formData.frequency);
        break;
      case 6:
        formData.datetime = {
          date: $("#mobooking-service-date").val(),
          time: formData.datetime.time || null,
        };
        DebugTree.info("Collected datetime data", formData.datetime);
        break;
      case 7:
        formData.customer = {
          name: $("#mobooking-customer-name").val(),
          email: $("#mobooking-customer-email").val(),
          phone: $("#mobooking-customer-phone").val(),
          address: $("#mobooking-service-address").val(),
          instructions: $("#mobooking-special-instructions").val(),
        };
        formData.access = {
          method: $('input[name="property_access"]:checked').val(),
          details: $("#mobooking-access-instructions").val(),
        };
        DebugTree.info("Collected customer and access data", {
          customer: formData.customer,
          access: formData.access,
        });
        break;
    }

    updateLiveSummary();
    updateDebugInfo();
  }

  function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function showFeedback($element, type, message) {
    $element
      .removeClass("success error info warning")
      .addClass(type)
      .html(message)
      .show();
    setTimeout(function () {
      $element.hide();
    }, 5000);
  }

  function populateBookingSummary(bookingData) {
    DebugTree.info("Populating booking summary", bookingData);
    const $summary = $("#mobooking-booking-summary");
    let summaryHtml = "";

    if (bookingData.booking_reference) {
      summaryHtml += `<p><strong>Booking Reference:</strong> ${bookingData.booking_reference}</p>`;
    }

    if (formData.location.zip_code) {
      summaryHtml += `<p><strong>Service Area:</strong> ${formData.location.zip_code}, ${formData.location.country_code}</p>`;
    }

    if (formData.services.length > 0) {
      summaryHtml += `<p><strong>Service:</strong> ${formData.services.length} service selected</p>`;
    }

    if (formData.datetime.date && formData.datetime.time) {
      summaryHtml += `<p><strong>Scheduled:</strong> ${formData.datetime.date} at ${formData.datetime.time}</p>`;
    }

    if (formData.frequency) {
      summaryHtml += `<p><strong>Frequency:</strong> ${formData.frequency}</p>`;
    }

    if ($("#mobooking-customer-name").val()) {
      summaryHtml += `<p><strong>Contact:</strong> ${$(
        "#mobooking-customer-name"
      ).val()} (${$("#mobooking-customer-email").val()})</p>`;
    }

    if (bookingData.total_amount) {
      summaryHtml += `<p><strong>Total Amount:</strong> ${bookingData.total_amount}</p>`;
    }

    $summary.html(summaryHtml);
  }

  window.toggleDropdown = function(optionsId) {
      const options = document.getElementById(optionsId);
      if (options) {
        options.classList.toggle('hidden');
      }
  }

  window.selectDropdownOption = function(optionElement, toggleId, hiddenInputId, optionsId) {
      const toggle = document.getElementById(toggleId);
      const hiddenInput = document.getElementById(hiddenInputId);

      if (toggle && hiddenInput) {
        toggle.textContent = optionElement.textContent;
        hiddenInput.value = optionElement.getAttribute('data-value');
        // Manually trigger change event for any listeners
        $(hiddenInput).trigger('change');
      }

      // Close the dropdown
      toggleDropdown(optionsId);
  }

  // Close dropdowns when clicking outside
  document.addEventListener('click', function(event) {
      const dropdowns = document.querySelectorAll('.relative[id^="dropdown-"]');
      dropdowns.forEach(function(dropdown) {
          if (!dropdown.contains(event.target)) {
              const options = dropdown.querySelector('[id^="dropdown-options-"]');
              if (options && !options.classList.contains('hidden')) {
                  options.classList.add('hidden');
              }
          }
      });
  });

  DebugTree.success("MoBooking Public Form JavaScript loaded successfully");
})(jQuery);
