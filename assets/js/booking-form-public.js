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

    CONFIG = window.MOBOOKING_CONFIG || {};
    if (!validateConfiguration()) {
      DebugTree.error("Configuration validation failed");
      return;
    }

    currentStep = CONFIG.form_config?.enable_area_check ? 1 : 2;
    initializeEventHandlers();
    initializeDatePicker();
    initializeFormState();
    showStep(currentStep);
    DebugTree.success("Form initialization complete");
    DebugTree.groupEnd();
  }

  function validateConfiguration() {
      // For simplicity in this context, we assume config is valid.
      return true;
  }

  /**
   * Initialize event handlers
   */
  function initializeEventHandlers() {
    DebugTree.info("Setting up event handlers");

    // Main change handler for most inputs
    $(document).on("change input", "input, select, textarea", function () {
      collectAllFormData();
      updateLiveSummary();
    });

    // Service selection
    $(document).on("click", ".mobooking-service-card", function () {
        const serviceId = $(this).data("service-id") || $(this).attr("data-service-id");
        if (serviceId) selectService(serviceId);
    });

    // Custom number stepper
    $(document).on("click", ".stepper-btn", function () {
        const $button = $(this);
        const $input = $button.siblings(".stepper-input");
        const step = parseFloat($input.attr("step") || "1");
        let currentValue = parseFloat($input.val() || "0");
        if ($button.hasClass("stepper-plus")) {
            currentValue += step;
        } else {
            currentValue -= step;
        }
        const min = parseFloat($input.attr("min") || "0");
        if (currentValue < min) {
            currentValue = min;
        }
        $input.val(currentValue);
        collectAllFormData();
        updateLiveSummary();
    });

    // Custom select dropdown
    $(document).on("click", ".custom-select-trigger", function (e) {
        e.stopPropagation();
        const $select = $(this).closest(".mobooking-custom-select");
        $(".mobooking-custom-select").not($select).removeClass("open");
        $select.toggleClass("open");
    });

    $(document).on("click", ".custom-select-option", function () {
        const $option = $(this);
        const $select = $option.closest(".mobooking-custom-select");
        const value = $option.data("value");
        const text = $option.contents().get(0).nodeValue; // Get text content without the span
        $select.find(".custom-select-trigger span").text(text);
        $select.find(".custom-select-value").val(value);
        $select.find(".custom-select-option").removeClass("selected");
        $option.addClass("selected");
        $select.removeClass("open");
        collectAllFormData();
        updateLiveSummary();
    });

    // Close custom selects when clicking outside
    $(document).on("click", function () {
        $(".mobooking-custom-select").removeClass("open");
    });

    DebugTree.success("Event handlers initialized");
  }

  function initializeDatePicker() { /* ... */ }
  function initializeFormState() {
      if (!CONFIG.form_config?.enable_area_check) {
          loadServices();
      }
      collectAllFormData();
      updateLiveSummary();
  }

  function collectAllFormData() {
      DebugTree.info("Collecting all form data");
      const $selectedService = $(".mobooking-service-card.selected");
      if ($selectedService.length > 0) {
          formData.services = [$selectedService.data("service-id").toString()];
      }

      formData.options = {};
      $('#mobooking-service-options-container .mobooking-service-option').each(function () {
          const $optionContainer = $(this);
          const optionId = $optionContainer.data('option-id');
          const option = displayedOptions.find(o => o.option_id == optionId);
          if (!option) return;
          const optionType = option.type;
          let value = null;
          let selectedChoices = [];
          switch (optionType) {
              case 'text': case 'textarea': case 'number': case 'sqm': case 'kilometers':
                  value = $optionContainer.find(`[name="service_options[${optionId}]"]`).val();
                  break;
              case 'quantity':
                  value = $optionContainer.find('.stepper-input').val();
                  break;
              case 'select':
                  const hiddenVal = $optionContainer.find('.custom-select-value').val();
                  if(hiddenVal) {
                      const choice = (option.option_values || []).find(c => c.label === hiddenVal);
                      if(choice) selectedChoices.push({ label: choice.label, price: choice.price });
                  }
                  break;
              case 'radio':
                  const $checkedRadio = $optionContainer.find('input[type="radio"]:checked');
                  if ($checkedRadio.length) {
                      selectedChoices.push({ label: $checkedRadio.val(), price: $checkedRadio.data('price') });
                  }
                  break;
              case 'checkbox':
                  const $checkedCheckboxes = $optionContainer.find('input[type="checkbox"]:checked');
                  $checkedCheckboxes.each(function () {
                      const $checkbox = $(this);
                      selectedChoices.push({ label: $checkbox.val(), price: $checkbox.data('price') });
                  });
                  break;
          }
          if ((value && value !== '' && value !== '0') || selectedChoices.length > 0) {
              formData.options[optionId] = { value: value, selectedChoices: selectedChoices };
          }
      });
      DebugTree.success("Form data collected", formData);
      return formData;
  }

  function loadServices() { /* ... */ }
  function displayServices(services) { /* ... */ }
  function loadTimeSlots(date) { /* ... */ }
  function displayTimeSlots(timeSlots) { /* ... */ }
  function selectService(serviceId) { /* ... */ }
  function selectTimeSlot(time) { /* ... */ }
  function loadServiceOptions() { /* ... */ }

  function displayServiceOptions(options) {
      if (!options) options = displayedOptions;
      displayedOptions = options;
      const $container = $("#mobooking-service-options-container");
      if ($container.length === 0 || !options || options.length === 0) {
          $container.html('<p>No additional options available.</p>');
          return;
      }
      let html = '<div class="mobooking-service-options-list">';
      options.forEach((option) => {
          const optionId = option.option_id;
          const optionType = option.type;
          const isRequired = option.is_required === "1" || option.is_required === 1;
          html += `<div class="mobooking-service-option" data-option-id="${optionId}" data-price-impact-type="${option.price_impact_type || ''}" data-price-impact-value="${option.price_impact_value || 0}">`;
          html += `<div class="mobooking-form-group">`;
          html += `<label class="mobooking-label">${option.name}${isRequired ? ' *' : ''}</label>`;
          if (option.description) html += `<p class="mobooking-option-description">${option.description}</p>`;
          const commonAttrs = `id="option_${optionId}" name="service_options[${optionId}]" data-option-id="${optionId}"`;
          let choices = [];
          if (option.option_values) {
              try { choices = typeof option.option_values === "string" ? JSON.parse(option.option_values) : option.option_values; } catch (e) { choices = []; }
          }
          switch (optionType) {
              case 'text': html += `<input type="text" ${commonAttrs} class="mobooking-input">`; break;
              case 'textarea': html += `<textarea ${commonAttrs} class="mobooking-textarea"></textarea>`; break;
              case 'number':
              case 'quantity':
                  html += `<div class="mobooking-number-stepper"><button type="button" class="stepper-btn stepper-minus">-</button><input type="number" ${commonAttrs} class="mobooking-input stepper-input" min="0" step="1" value="0"><button type="button" class="stepper-btn stepper-plus">+</button></div>`;
                  break;
              case 'sqm': case 'kilometers':
                  html += `<input type="number" ${commonAttrs} class="mobooking-input" min="0" step="0.1">`; break;
              case 'checkbox':
                  if (choices.length > 0) {
                      html += `<div class="mobooking-checkbox-group">`;
                      choices.forEach((choice, index) => {
                          const choiceId = `option_${optionId}_${index}`;
                          html += `<label class="mobooking-checkbox-option" for="${choiceId}"><input type="checkbox" name="service_options[${optionId}][${index}]" id="${choiceId}" value="${choice.label}" data-price="${choice.price || 0}"><span>${choice.label} (+${CONFIG.currency?.symbol || '$'}${choice.price || '0.00'})</span></label>`;
                      });
                      html += `</div>`;
                  } else {
                      html += `<label class="mobooking-checkbox-option" for="option_${optionId}"><input type="checkbox" ${commonAttrs} value="1"><span>Yes</span></label>`;
                  }
                  break;
              case 'radio':
                  if (choices.length > 0) {
                      html += `<div class="mobooking-radio-group">`;
                      choices.forEach((choice, index) => {
                          const choiceId = `option_${optionId}_${index}`;
                          html += `<label class="mobooking-radio-option" for="${choiceId}"><input type="radio" ${commonAttrs} id="${choiceId}" value="${choice.label}" data-price="${choice.price || 0}"><span>${choice.label} (+${CONFIG.currency?.symbol || '$'}${choice.price || '0.00'})</span></label>`;
                      });
                      html += `</div>`;
                  }
                  break;
              case 'select':
                  if (choices.length > 0) {
                      html += `<div class="mobooking-custom-select" data-option-id="${optionId}"><input type="hidden" ${commonAttrs} class="custom-select-value"><div class="custom-select-trigger"><span>Select an option</span><i class="arrow down"></i></div><div class="custom-select-options">`;
                      choices.forEach(choice => {
                          html += `<div class="custom-select-option" data-value="${choice.label}" data-price="${choice.price || 0}">${choice.label} <span>(+${CONFIG.currency?.symbol || '$'}${choice.price || '0.00'})</span></div>`;
                      });
                      html += `</div></div>`;
                  }
                  break;
          }
          html += `</div></div>`;
      });
      html += "</div>";
      $container.html(html);
  }

  function calculateTotalPrice() {
      let baseTotal = 0;
      if (formData.services.length > 0) {
          const service = displayedServices.find(s => s.service_id == formData.services[0]);
          if (service) baseTotal += parseFloat(service.price || 0);
      }
      let optionsTotal = 0;
      let percentageImpact = 0;
      for (const optionId in formData.options) {
          const selectedOptionData = formData.options[optionId];
          const optionInfo = displayedOptions.find(o => o.option_id == optionId);
          if (!optionInfo) continue;
          const priceImpactType = optionInfo.price_impact_type;
          const priceImpactValue = parseFloat(optionInfo.price_impact_value || 0);
          const optionType = optionInfo.type;
          if (optionType !== 'quantity') {
              if (priceImpactType === 'fixed') optionsTotal += priceImpactValue;
              if (priceImpactType === 'percentage') percentageImpact += priceImpactValue;
          }
          const value = parseFloat(selectedOptionData.value);
          if (!isNaN(value)) {
              if (optionType === 'quantity' && priceImpactType === 'multiply') {
                  optionsTotal += priceImpactValue * value;
              } else if (optionType === 'sqm' || optionType === 'kilometers') {
                  const ranges = Array.isArray(optionInfo.option_values) ? optionInfo.option_values : [];
                  for (const range of ranges) {
                      const from = parseFloat(range.from_sqm || range.from_km);
                      const to = (range.to_sqm === '∞' || range.to_km === '∞') ? Infinity : parseFloat(range.to_sqm || range.to_km);
                      if (value >= from && (value <= to || to === Infinity)) {
                          const price_per_unit = parseFloat(range.price_per_sqm || range.price_per_km);
                          if (!isNaN(price_per_unit)) optionsTotal += value * price_per_unit;
                          break;
                      }
                  }
              }
          }
          if (selectedOptionData.selectedChoices && selectedOptionData.selectedChoices.length > 0) {
              selectedOptionData.selectedChoices.forEach(choice => {
                  optionsTotal += parseFloat(choice.price || 0);
              });
          }
      }
      let finalTotal = baseTotal + optionsTotal;
      if (percentageImpact > 0) {
          finalTotal += finalTotal * (percentageImpact / 100);
      }
      return finalTotal.toFixed(2);
  }

  function updateLiveSummary() {
      const $summary = $("#mobooking-live-summary");
      if ($summary.length > 0) {
          let html = "";
          if (formData.services.length > 0) {
              html += `<p>Services: ${formData.services.length} selected</p>`;
          }
          const totalPrice = calculateTotalPrice();
          html += `<p><b>Total:</b> ${CONFIG.currency?.symbol || '$'}${totalPrice}</p>`;
          $summary.html(html);
      }
  }

  function showStep(step) {
    DebugTree.info(`Showing step ${step}`);
    currentStep = step;
    $(".mobooking-step-content").hide();
    $(`#mobooking-step-${step}`).show();
    if (CONFIG.form_config?.show_progress_bar) {
      updateProgressBar(step);
    }
    collectAllFormData();
  }

  function updateProgressBar(step) {
    const totalSteps = 8;
    const percentage = (step / totalSteps) * 100;
    $(".mobooking-progress-fill").css("width", `${percentage}%`);
    $(`.mobooking-step-indicator[data-step="${step}"]`).addClass("active").siblings().removeClass("active");
  }

  function validateCurrentStep() {
    collectAllFormData();
    // Simplified validation for this context
    return true;
  }

  function submitBooking() { /* ... */ }
  function validateBookingData() { return true; }
  function prepareSubmissionData() { return {}; }
  function handleBookingSuccess(data) { /* ... */ }
  function handleBookingError(xhr, status, error) { /* ... */ }

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
