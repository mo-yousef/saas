/**
 * Complete MoBooking Booking Form JavaScript with ShadCN UI Design
 * Fully fixed with working AJAX and modern UI components
 * Updated to skip location step and auto-load service options
 */

jQuery(document).ready(function ($) {
  "use strict";

  // ==========================================
  // CONFIG & STATE
  // ==========================================

  const CONFIG = {
    ajax_url:
      window.MOBOOKING_CONFIG?.ajax_url ||
      window.mobooking_booking_form_params?.ajax_url ||
      "/wp-admin/admin-ajax.php",
    tenant_id:
      window.MOBOOKING_CONFIG?.tenant_id ||
      window.mobooking_booking_form_params?.tenant_id ||
      0,
    nonce:
      window.MOBOOKING_CONFIG?.nonce ||
      window.mobooking_booking_form_params?.nonce ||
      "",
    currency_symbol:
      window.MOBOOKING_CONFIG?.currency?.symbol ||
      window.mobooking_booking_form_params?.currency_symbol ||
      "$",
    i18n:
      window.MOBOOKING_CONFIG?.i18n ||
      window.mobooking_booking_form_params?.i18n ||
      {},
    settings:
      window.MOBOOKING_CONFIG?.settings ||
      window.mobooking_booking_form_params?.settings ||
      {},
  };

  const state = {
    currentStep: 1,
    totalSteps: 9,
    zip: "",
    areaName: "",
    service: null, // { service_id, name, price, duration, ... }
    optionsById: {}, // { [option_id]: { id, name, type, value, price, meta } }
    pricing: {
      base: 0,
      options: 0,
      discount: 0,
      total: 0,
    },
    pets: { has_pets: false, details: "" },
    frequency: "one-time",
    date: "",
    time: "",
    customer: { name: "", email: "", phone: "", address: "", instructions: "" },
    propertyAccess: { method: "home", details: "" },
    latestTimeSlots: [],
  };

  // DOM refs
  const els = {
    layout: $(".mobooking-layout"),
    progressFill: $("#mobooking-progress-fill"),
    stepIndicators: $(".mobooking-step-indicator"),
    steps: $(".mobooking-step-content"),
    // Step 1
    areaForm: $("#mobooking-area-check-form"),
    areaFeedback: $("#mobooking-location-feedback"),
    // Step 2
    servicesContainer: $("#mobooking-services-container"),
    serviceFeedback: $("#mobooking-service-feedback"),
    // Step 3
    optionsContainer: $("#mobooking-service-options-container"),
    optionsFeedback: $("#mobooking-options-feedback"),
    // Step 6
    dateInput: $("#mobooking-service-date"),
    timeSlotsWrap: $("#mobooking-time-slots-container"),
    timeSlots: $("#mobooking-time-slots"),
    dateTimeFeedback: $("#mobooking-datetime-feedback"),
    // Step 7
    nameInput: $("#mobooking-customer-name"),
    emailInput: $("#mobooking-customer-email"),
    phoneInput: $("#mobooking-customer-phone"),
    addressInput: $("#mobooking-service-address"),
    contactFeedback: $("#mobooking-contact-feedback"),
    accessDetailsWrap: $("#mobooking-custom-access-details"),
    // Step 8 (Confirmation)
    confirmationSummary: $("#mobooking-confirmation-summary"),
    confirmationFeedback: $("#mobooking-confirmation-feedback"),
    // Live summary
    liveSummaryContainer: $("#mobooking-live-summary"),
    liveSummaryContent: $("#mobooking-summary-content"),
    // Success
    successMessage: $("#mobooking-success-message"),
  };

  // ==========================================
  // STEP NAVIGATION
  // ==========================================

  function updateStepVisibility() {
    const service = state.service;
    if (!service) {
      // Show all conditional steps if no service is selected
      $("#mobooking-step-4, #mobooking-step-5").removeClass("hidden");
      $('.mobooking-step-indicator[data-step="4"], .mobooking-step-indicator[data-step="5"]').removeClass("hidden");
      return;
    }

    const disablePets = service.disable_pet_question == "1";
    const disableFreq = service.disable_frequency_option == "1";

    $("#mobooking-step-4").toggleClass("hidden", disablePets);
    $('.mobooking-step-indicator[data-step="4"]').toggleClass("hidden", disablePets);

    $("#mobooking-step-5").toggleClass("hidden", disableFreq);
    $('.mobooking-step-indicator[data-step="5"]').toggleClass("hidden", disableFreq);
  }

  function showStep(step) {
    state.currentStep = step;

    // Add active step class to the form card
    els.layout
      .find(".mobooking-form-card")
      .removeClass(function (index, className) {
        return (className.match(/(^|\s)step-active-\S+/g) || []).join(" ");
      })
      .addClass(`step-active-${step}`);

    els.steps.removeClass("active").hide();
    $(`#mobooking-step-${step}`).addClass("active").show();

    const visibleIndicators = els.stepIndicators;
    visibleIndicators.removeClass("active completed");
    visibleIndicators.each(function () {
      const s = parseInt($(this).data("step"), 10);
      if (s < step) $(this).addClass("completed");
      if (s === step) $(this).addClass("active");
    });

    const totalInd = visibleIndicators.length || state.totalSteps;
    const idx = Math.max(1, Math.min(step, totalInd));
    const progress = ((idx - 1) / Math.max(1, totalInd - 1)) * 100;
    els.progressFill.css("width", `${progress}%`);

    // Step-specific hooks
    if (step === 2) loadServices();
    if (step === 3) ensureOptionsLoaded();
    if (step === 4) {
      // Ensure the active class is set on the correct pet radio button
      $('input[name="has_pets"]:checked')
        .closest(".mobooking-radio-option")
        .addClass("active");
    }
    if (step === 6) initDatePicker();
    if (step === 7) {
      $("#mobooking-zip-readonly").val(state.zip);
      // Ensure the active class is set on the correct property access radio
      $('input[name="property_access"]:checked')
        .closest(".mobooking-radio-option")
        .addClass("active");
      // TODO: Initialize Google Maps Places Autocomplete here when API key is available
    }
    if (step === 8) renderConfirmationSummary();

    // Show summary only on steps 3, 4, 5, 6 and if a service is selected
    const summaryVisibleSteps = [3, 4, 5];
    if (summaryVisibleSteps.includes(step) && state.service) {
      els.liveSummaryContainer.addClass("active");
    } else {
      els.liveSummaryContainer.removeClass("active");
    }

    updateLiveSummary();
  }

  function nextStep() {
    if (!validateStep(state.currentStep)) return;

    let next = state.currentStep + 1;

    // Skip step 4 if it's hidden
    if (next === 4 && $("#mobooking-step-4").hasClass("hidden")) {
      next++;
    }
    // Skip step 5 if it's hidden
    if (next === 5 && $("#mobooking-step-5").hasClass("hidden")) {
      next++;
    }

    if (next <= state.totalSteps) {
      showStep(next);
    }
  }

  function prevStep() {
    let prev = state.currentStep - 1;

    // Skip step 5 if it's hidden
    if (prev === 5 && $("#mobooking-step-5").hasClass("hidden")) {
      prev--;
    }
    // Skip step 4 if it's hidden
    if (prev === 4 && $("#mobooking-step-4").hasClass("hidden")) {
      prev--;
    }

    if (prev >= 1) {
      resetStepData(state.currentStep); // Reset data for the step we are leaving
      showStep(prev);
    }
  }

  function resetStepData(step) {
    switch (step) {
      case 2: // Reset service selection
        state.service = null;
        els.servicesContainer
          .find('input[name="mobooking-selected-service"]')
          .prop("checked", false);
        els.servicesContainer
          .find(".mobooking-service-card")
          .removeClass("active");
        break;
      case 3: // Reset service options
        state.optionsById = {};
        state.pricing.options = 0;
        els.optionsContainer.find(".mobooking-option-input").val("");
        els.optionsContainer
          .find('input[type="checkbox"]')
          .prop("checked", false);
        els.optionsContainer.find('input[type="radio"]').prop("checked", false);
        els.optionsContainer
          .find(".mobooking-form-group, .mobooking-radio-option")
          .removeClass("active");
        recalcTotal();
        break;
      case 4: // Reset pet info
        state.pets = { has_pets: false, details: "" };
        $('input[name="has_pets"][value="no"]')
          .prop("checked", true)
          .trigger("change");
        $("#mobooking-pet-details").val("");
        break;
      case 5: // Reset frequency
        state.frequency = "one-time";
        $('input[name="frequency"][value="one-time"]')
          .prop("checked", true)
          .trigger("change");
        break;
      case 6: // Reset date and time
        state.date = "";
        state.time = "";
        if (els.dateInput.data("fp")) {
          els.dateInput.data("fp").clear();
        } else {
          els.dateInput.val("");
        }
        els.timeSlots.empty();
        collapseTimeSlots(true);
        break;
      case 7: // Reset customer details
        state.customer = {
          name: "",
          email: "",
          phone: "",
          address: "",
          instructions: "",
        };
        state.propertyAccess = { method: "home", details: "" };
        els.nameInput.val("");
        els.emailInput.val("");
        els.phoneInput.val("");
        els.addressInput.val("");
        $("#mobooking-special-instructions").val("");
        $('input[name="property_access"][value="home"]')
          .prop("checked", true)
          .trigger("change");
        $("#mobooking-access-instructions").val("");
        break;
      case 8: // Reset confirmation summary
        $("#mobooking-confirmation-details").html("");
        break;
    }
  }

  function validateStep(step) {
    switch (step) {
      case 1:
        // Area check (optional if feature disabled). If form shows step 1, require zip/country.
        if (!$("#mobooking-step-1").length) return true;
        if (
          !CONFIG.settings?.bf_enable_location_check ||
          CONFIG.settings.bf_enable_location_check === "0"
        )
          return true;
        const zip = $("#mobooking-zip").val()?.trim();
        const country = $("#mobooking-country").val()?.trim();
        if (!zip)
          return (
            showFeedback(
              els.areaFeedback,
              "error",
              CONFIG.i18n.zip_required || "ZIP required"
            ),
            false
          );
        if (!country)
          return (
            showFeedback(
              els.areaFeedback,
              "error",
              CONFIG.i18n.country_required || "Country required"
            ),
            false
          );
        return true;
      case 2:
        if (!state.service)
          return (
            showFeedback(
              els.serviceFeedback,
              "error",
              CONFIG.i18n.select_service || "Select a service"
            ),
            false
          );
        return true;
      case 3:
        // Validate required options using a group-based approach
        const missing = [];
        els.optionsContainer.find(".mobooking-form-group").each(function () {
          const $group = $(this);
          const requiredInputs = $group.find(
            ".mobooking-option-input[data-required='1']"
          );

          if (!requiredInputs.length) {
            return; // Skip non-required groups
          }

          const firstInput = requiredInputs.first();
          const type = firstInput.data("type");
          const name = firstInput.data("name") || "Option";
          let isValid = true;

          if (type === "toggle") {
            if (!firstInput.is(":checked")) {
              isValid = false;
            }
          } else if (type === "checkbox") {
            if ($group.find("input[type='checkbox']:checked").length === 0) {
              isValid = false;
            }
          } else if (type === "radio") {
            if ($group.find("input[type='radio']:checked").length === 0) {
              isValid = false;
            }
          } else {
            const value = firstInput.val();
            if (!value || String(value).trim() === "") {
              isValid = false;
            }
          }

          if (!isValid && !missing.includes(name)) {
            missing.push(name);
          }
        });

        if (missing.length) {
          const errorMsg =
            (CONFIG.i18n.fill_required_options ||
              "Please fill all required options:") +
            " " +
            missing.join(", ");
          return showFeedback(els.optionsFeedback, "error", errorMsg), false;
        }
        return true;
      case 4:
        // Pets: if yes, require details
        const hasPets = $('input[name="has_pets"]:checked').val() === "yes";
        if (hasPets) {
          const details = $("#mobooking-pet-details").val().trim();
          if (!details)
            return (
              showFeedback(
                $("#mobooking-pet-feedback"),
                "error",
                CONFIG.i18n.pet_details_required || "Please add pet details"
              ),
              false
            );
        }
        return true;
      case 5:
        // Frequency always valid
        return true;
      case 6:
        if (!state.date)
          return (
            showFeedback(
              els.dateTimeFeedback,
              "error",
              CONFIG.i18n.select_date || "Select a date"
            ),
            false
          );
        if (!state.time)
          return (
            showFeedback(
              els.dateTimeFeedback,
              "error",
              CONFIG.i18n.select_time || "Select a time"
            ),
            false
          );
        return true;
      case 7:
        const name = els.nameInput.val().trim();
        const email = els.emailInput.val().trim();
        const phone = els.phoneInput.val().trim();
        const address = els.addressInput.val().trim();
        if (!name)
          return (
            showFeedback(
              els.contactFeedback,
              "error",
              CONFIG.i18n.name_required || "Name required"
            ),
            false
          );
        if (!email || !/^\S+@\S+\.\S+$/.test(email))
          return (
            showFeedback(
              els.contactFeedback,
              "error",
              CONFIG.i18n.email_required || "Valid email required"
            ),
            false
          );
        if (!phone)
          return (
            showFeedback(
              els.contactFeedback,
              "error",
              CONFIG.i18n.phone_required || "Phone required"
            ),
            false
          );
        if (!address)
          return (
            showFeedback(
              els.contactFeedback,
              "error",
              CONFIG.i18n.address_required || "Address required"
            ),
            false
          );
        return true;
      default:
        return true;
    }
  }

  function showFeedback($el, type, message) {
    $el.removeClass("success error").addClass(type).text(message).show();
    return $el;
  }

  // Expose for template buttons
  window.moBookingNextStep = nextStep;
  window.moBookingPreviousStep = prevStep;

  // ==========================================
  // STEP 1: AREA CHECK (optional)
  // ==========================================

  function debounce(func, wait) {
    let timeout;
    return function (...args) {
      const context = this;
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(context, args), wait);
    };
  }

  $("#mobooking-zip").on(
    "input",
    debounce(function () {
      const zip = $(this).val()?.trim();
      state.zip = zip;

      if (zip.length < 4) {
        // Don't search for very short zips
        $("#mobooking-area-name").text("");
        els.areaFeedback.hide();
        return;
      }

      showFeedback(
        els.areaFeedback,
        "info",
        CONFIG.i18n.checking_availability || "Checking availability..."
      );

      $.post(CONFIG.ajax_url, {
        action: "mobooking_check_service_area",
        nonce: CONFIG.nonce,
        tenant_id: CONFIG.tenant_id,
        location: zip,
      })
        .done(function (res) {
          if (res.success) {
            state.areaName = res.data.area_name || "";
            $(".area-name-wrap").addClass("valid");
            $("#mobooking-area-name").text(state.areaName).addClass("valid");
            showFeedback(
              els.areaFeedback,
              "success",
              res.data?.message || CONFIG.i18n.service_available
            );
            // Enable the next button if it was disabled
            $("#mobooking-step-1 button[type=submit]").prop("disabled", false);
          } else {
            state.areaName = "";
            $("#mobooking-area-name").text("").removeClass("valid");
            showFeedback(
              els.areaFeedback,
              "error",
              res.data?.message || CONFIG.i18n.service_not_available
            );
            $("#mobooking-step-1 button[type=submit]").prop("disabled", true);
          }
        })
        .fail(function (xhr) {
          let errorMessage = CONFIG.i18n.error_ajax || "Network error";
          if (
            xhr.responseJSON &&
            xhr.responseJSON.data &&
            xhr.responseJSON.data.message
          ) {
            errorMessage = xhr.responseJSON.data.message;
          }
          showFeedback(els.areaFeedback, "error", errorMessage);
        });
    }, 500)
  );

  els.areaForm.on("submit", function (e) {
    e.preventDefault();
    if (state.areaName) {
      showStep(2);
    } else {
      showFeedback(
        els.areaFeedback,
        "error",
        "Please enter a valid ZIP code in a serviced area."
      );
    }
  });

  // ==========================================
  // STEP 2: SERVICES
  // ==========================================

  function loadServices() {
    els.servicesContainer.html(`
      <div style="text-align:center;padding:40px 0;">
        <div class="mobooking-spinner" style="margin:0 auto;"></div>
        <div style="margin-top:10px;">${
          CONFIG.i18n.loading_services || "Loading services..."
        }</div>
      </div>
    `);

    $.post(CONFIG.ajax_url, {
      action: "mobooking_get_public_services",
      nonce: CONFIG.nonce,
      tenant_id: CONFIG.tenant_id,
    })
      .done(function (response) {
        if (response.success && response.data?.services?.length) {
          renderServices(response.data.services);
        } else {
          els.servicesContainer.html(
            `<p>${
              response.data?.message ||
              CONFIG.i18n.no_services_available ||
              "No services available"
            }</p>`
          );
        }
      })
      .fail(function (xhr) {
        // Fallback to direct endpoint if admin-ajax is not available
        if (
          xhr?.status === 404 &&
          window.mobooking_booking_form_params?.direct_url
        ) {
          $.get(window.mobooking_booking_form_params.direct_url)
            .done(function (resp) {
              if (resp?.success && resp?.data?.services) {
                renderServices(resp.data.services);
              } else {
                els.servicesContainer.html(
                  `<p>${
                    resp?.data?.message ||
                    CONFIG.i18n.no_services_available ||
                    "No services available"
                  }</p>`
                );
              }
            })
            .fail(function () {
              els.servicesContainer.html(
                `<p>${
                  CONFIG.i18n.error_loading_services || "Error loading services"
                }</p>`
              );
            });
          return;
        }
        els.servicesContainer.html(
          `<p>${
            CONFIG.i18n.error_loading_services || "Error loading services"
          }</p>`
        );
      });
  }

  function renderServices(services) {
    let html = '<div class="mobooking-services-grid">';
    services.forEach((svc) => {
      const price = parseFloat(svc.price) || 0;
      const priceDisplay = `${CONFIG.currency_symbol}${price.toFixed(2)}`;
      const duration = parseInt(svc.duration) || 0;
      html += `
        <label class="mobooking-service-card" style="cursor:pointer;">
          <input type="radio" name="mobooking-selected-service" value="${
            svc.service_id
          }" data-service='${JSON.stringify(
        svc
      )}' style="position:absolute;opacity:0;pointer-events:none;">
          <div class="mobooking-service-header">
            ${(() => {
                if (CONFIG.settings.bf_service_card_display === 'icon' && svc.icon) {
                    return svc.icon;
                }
                if (CONFIG.settings.bf_service_card_display === 'image' && svc.image_url) {
                    return `<div class="mobooking-service-image"><img src="${svc.image_url}" alt="${escapeHtml(svc.name)}"></div>`;
                }
                return '';
            })()}
            <div style="flex:1;">
              <div class="mobooking-service-title">${escapeHtml(svc.name)}</div>
              ${
                svc.description
                  ? `<div class="mobooking-service-description">${escapeHtml(
                      svc.description
                    )}</div>`
                  : ""
              }
            </div>
          </div>
        </label>`;
    });
    html += "</div>";
    els.servicesContainer.html(html);

    els.servicesContainer
      .find('input[name="mobooking-selected-service"]')
      .on("change", function () {
        const svc = $(this).data("service");
        // Toggle active class on selected card
        els.servicesContainer
          .find(".mobooking-service-card")
          .removeClass("active");
        $(this).closest(".mobooking-service-card").addClass("active");

        state.service = svc;
        state.pricing.base = parseFloat(svc.price) || 0;
        recalcTotal();
        updateLiveSummary();
        updateStepVisibility();

        // Auto advance to options
        setTimeout(() => showStep(3), 200);
      });
  }

  // ==========================================
  // STEP 3: SERVICE OPTIONS
  // ==========================================

  function ensureOptionsLoaded() {
    if (!state.service) {
      showFeedback(
        els.optionsFeedback,
        "error",
        CONFIG.i18n.select_service || "Select a service in previous step"
      );
      return;
    }

    els.optionsContainer.html(`
      <div class="mobooking-card">
        <div class="mobooking-text-center">
          <div class="mobooking-spinner" style="margin: 2rem auto;"></div>
          <p>${CONFIG.i18n.loading_options || "Loading service options..."}</p>
        </div>
      </div>
    `);

    $.post(CONFIG.ajax_url, {
      action: "mobooking_get_public_service_options",
      nonce: CONFIG.nonce,
      tenant_id: CONFIG.tenant_id,
      service_ids: [state.service.service_id],
    })
      .done(function (res) {
        if (res?.success) {
          // Support two formats:
          // 1) { success: true, data: { options: { [serviceId]: [...] } } }
          // 2) { success: true, data: [ ...options ] }
          if (res.data && Array.isArray(res.data)) {
            renderOptions(res.data);
          } else {
            const optionsMap = res?.data?.options || {};
            const list = optionsMap[state.service.service_id] || [];
            renderOptions(list);
          }
        } else {
          els.optionsContainer.html(
            `<p>${
              CONFIG.i18n.error_loading_options || "Unable to load options"
            }</p>`
          );
        }
      })
      .fail(function () {
        els.optionsContainer.html(
          `<p>${
            CONFIG.i18n.error_loading_options || "Unable to load options"
          }</p>`
        );
      });
  }

  function renderOptions(options) {
    if (!options || options.length === 0) {
      els.optionsContainer.html(
        `<p>${
          CONFIG.i18n.no_options_available ||
          "No additional options available for this service."
        }</p>`
      );
      state.optionsById = {};
      state.pricing.options = 0;
      recalcTotal();
      updateLiveSummary();
      return;
    }

    state.optionsById = {};

    let html = "";
    options.forEach((opt) => {
      const id = opt.option_id;
      const name = opt.name || "Option";
      const type = opt.type || "text";
      const isReq = opt.is_required ? 1 : 0;
      const impactType = opt.price_impact_type || "fixed";
      const impactValue = parseFloat(opt.price_impact_value) || 0;
      const values = Array.isArray(opt.option_values) ? opt.option_values : [];

      html += `<div class="mobooking-form-group mobooking-form-group-${type}" data-option-id="${id}">`;

      // Define label and description parts
      const labelHtml = `<label class="mobooking-label">${escapeHtml(name)}${
        impactValue > 0 && !["select", "radio", "checkbox"].includes(type)
          ? priceImpactLabel(impactType, impactValue)
          : ""
      }${isReq ? ' <span style="color:#ef4444">*</span>' : ""}</label>`;

      const descriptionHtml = opt.description
        ? `<div class="mobooking-option-description">${escapeHtml(
            opt.description
          )}</div>`
        : "";

      if (type === "toggle") {
        html += `<div class="mobooking-input-group">`;
        html += labelHtml;
        html += `<label class="mobooking-toggle-switch"><input type="checkbox" class="mobooking-option-input" data-type="toggle" data-required="${isReq}" data-name="${escapeHtml(
          name
        )}" data-impact-type="${impactType}" data-impact-value="${impactValue}" value="1"> <span class="slider"></span></label>`;
        html += `</div>`;
        html += descriptionHtml;
      } else if (type === "quantity") {
        html += `<div class="mobooking-input-group">`;
        html += labelHtml;
        html += `<div class="mobooking-quantity-stepper">`;
        html += `<button type="button" class="stepper-btn stepper-minus" aria-label="Decrease quantity">
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>`;
        html += `<input type="number" min="0" class="form-input mobooking-option-input" data-type="quantity" data-required="${isReq}" data-name="${escapeHtml(
          name
        )}" data-impact-type="${impactType}" data-impact-value="${impactValue}" value="${
          isReq ? 1 : 0
        }" readonly>`;
        html += `<button type="button" class="stepper-btn stepper-plus" aria-label="Increase quantity">
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg></button>`;
        html += `</div>`;
        html += `</div>`;
        html += descriptionHtml;
      } else {
        // Default behavior for other inputs
        html += labelHtml;

        if (type === "checkbox") {
          values.forEach((v, idx) => {
            const label = v.label || v.value || v;
            const value = v.value || v.label || v;
            const price = parseFloat(v.price) || 0;
            const cid = `mobooking-opt-${id}-${idx}`;
            html += `<label class="mobooking-checkbox-option mobooking-selectable-option"><input type="checkbox" name="mobooking-option-${id}[]" id="${cid}" class="mobooking-option-input" data-type="checkbox" data-required="${isReq}" data-name="${escapeHtml(
              name
            )}" value="${escapeHtml(
              value
            )}" data-price="${price}"><span>${escapeHtml(label)}${
              price > 0
                ? ` (+${CONFIG.currency_symbol}${price.toFixed(2)})`
                : ""
            }</span></label>`;
          });
          html += descriptionHtml;
        } else if (type === "select") {
          html += `<select class="form-select mobooking-option-input" data-type="select" data-required="${isReq}" data-name="${escapeHtml(
            name
          )}">`;
          html += `<option value="">${"Choose an option"}</option>`;
          values.forEach((v) => {
            const label = v.label || v.value || v;
            const value = v.value || v.label || v;
            const price = parseFloat(v.price) || 0;
            html += `<option value="${escapeHtml(
              value
            )}" data-price="${price}">${escapeHtml(label)}${
              price > 0
                ? ` (+${CONFIG.currency_symbol}${price.toFixed(2)})`
                : ""
            }</option>`;
          });
          html += `</select>`;
          html += descriptionHtml;
        } else if (type === "radio") {
          values.forEach((v, idx) => {
            const label = v.label || v.value || v;
            const value = v.value || v.label || v;
            const price = parseFloat(v.price) || 0;
            const rid = `mobooking-opt-${id}-${idx}`;
            html += `<label class="mobooking-radio-option mobooking-selectable-option"><input type="radio" name="mobooking-option-${id}" id="${rid}" class="mobooking-option-input" data-type="radio" data-required="${isReq}" data-name="${escapeHtml(
              name
            )}" value="${escapeHtml(
              value
            )}" data-price="${price}"> <span>${escapeHtml(label)}${
              price > 0
                ? ` (+${CONFIG.currency_symbol}${price.toFixed(2)})`
                : ""
            }</span></label>`;
          });
          html += descriptionHtml;
        } else if (type === "number") {
          html += `<input type="number" min="0" class="form-input mobooking-option-input" data-type="number" data-required="${isReq}" data-name="${escapeHtml(
            name
          )}" data-impact-type="${impactType}" data-impact-value="${impactValue}" value="${
            isReq ? 1 : 0
          }">`;
          html += descriptionHtml;
        } else if (type === "textarea") {
          html += `<textarea class="form-textarea mobooking-option-input" data-type="textarea" data-required="${isReq}" data-name="${escapeHtml(
            name
          )}" data-impact-type="${impactType}" data-impact-value="${impactValue}"></textarea>`;
          html += descriptionHtml;
        } else if (type === "sqm") {
          html += `<input type="number" min="1" step="0.1" placeholder="Enter square meters" class="form-input mobooking-option-input" data-type="sqm" data-required="${isReq}" data-name="${escapeHtml(
            name
          )}" data-price-per-unit="${impactValue}">`;
          html += descriptionHtml;
        } else if (type === "kilometers") {
          html += `<input type="number" min="1" step="0.1" placeholder="Enter kilometers" class="form-input mobooking-option-input" data-type="kilometers" data-required="${isReq}" data-name="${escapeHtml(
            name
          )}" data-price-per-unit="${impactValue}">`;
          html += descriptionHtml;
        } else {
          // text default
          html += `<input type="text" class="form-input mobooking-option-input" data-type="text" data-required="${isReq}" data-name="${escapeHtml(
            name
          )}" data-impact-type="${impactType}" data-impact-value="${impactValue}" placeholder="Enter ${escapeHtml(
            name.toLowerCase()
          )}...">`;
          html += descriptionHtml;
        }
      }

      html += "</div>";
    });

    els.optionsContainer.html(html);

    els.optionsContainer
      .find(".mobooking-option-input")
      .on("change input", function () {
        // Toggle active class for UI feedback
        const $input = $(this);
        const type = $input.attr("type");
        const $group = $input.closest(".mobooking-form-group");
        if (type === "radio") {
          // Clear actives for this radio group
          const name = $input.attr("name");
          $(`input[name="${name}"]`).each(function () {
            $(this).closest(".mobooking-radio-option").removeClass("active");
          });
          if ($input.is(":checked"))
            $input.closest(".mobooking-radio-option").addClass("active");
        } else if (type === "checkbox") {
          $input
            .closest(".mobooking-radio-option")
            .toggleClass("active", $input.is(":checked"));
        } else if ($input.is("select")) {
          $group.toggleClass("active", !!$input.val());
        } else {
          $group.toggleClass(
            "active",
            !!($input.val() || "").toString().trim()
          );
        }

        collectOptionsAndPrice();
        updateLiveSummary();
      });

    // initialize price once
    collectOptionsAndPrice();
    updateLiveSummary();
  }

  // Quantity Stepper Logic
  $(document).on("click", ".stepper-btn", function () {
    const $button = $(this);
    const $input = $button.siblings('input[type="number"]');
    if (!$input.length) return;

    let currentValue = parseInt($input.val(), 10) || 0;
    const min = parseInt($input.attr("min"), 10) || 0;

    if ($button.hasClass("stepper-plus")) {
      currentValue++;
    } else if ($button.hasClass("stepper-minus")) {
      currentValue--;
    }

    if (currentValue < min) {
      currentValue = min;
    }

    $input.val(currentValue);

    // Disable/enable buttons
    $button.siblings(".stepper-minus").prop("disabled", currentValue === min);

    // Trigger change event to update pricing
    $input.trigger("change");
  });

  function priceImpactLabel(type, value) {
    if (type === "percentage")
      return ` <span style=\"color:#6b7280\">(+${value}%)</span>`;
    if (type === "multiply")
      return ` <span style=\"color:#6b7280\">(×${value})</span>`;
    return ` <span style=\"color:#6b7280\">(+${
      CONFIG.currency_symbol
    }${Number(value).toFixed(2)})</span>`;
  }

  function collectOptionsAndPrice() {
    let optionsTotal = 0;
    const opts = {};

    els.optionsContainer.find(".mobooking-form-group").each(function () {
      const optWrap = $(this);
      const optionId = parseInt(optWrap.data("option-id"), 10);
      const firstInput = optWrap.find(".mobooking-option-input").first();
      if (!firstInput.length) return;

      const type = firstInput.data("type");
      const name = firstInput.data("name") || "Option";
      let value = null;
      let price = 0;

      if (type === "toggle") {
        if (firstInput.is(":checked")) {
          value = "1";
          price = calcImpactPrice(
            firstInput.data("impact-type"),
            parseFloat(firstInput.data("impact-value")) || 0,
            1
          );
        }
      } else if (type === "checkbox") {
        const checked = optWrap.find('input[type="checkbox"]:checked');
        if (checked.length > 0) {
          const values = [];
          let totalPrice = 0;
          checked.each(function () {
            const cb = $(this);
            values.push(cb.val());
            totalPrice += parseFloat(cb.data("price")) || 0;
          });
          value = values.join(", ");
          price = totalPrice;
        }
      } else if (type === "select") {
        value = firstInput.val();
        const p =
          parseFloat(firstInput.find("option:selected").data("price")) || 0;
        if (value) price = p;
      } else if (type === "radio") {
        const selected = optWrap.find('input[type="radio"]:checked');
        if (selected.length) {
          value = selected.val();
          price = parseFloat(selected.data("price")) || 0;
        }
      } else if (type === "number" || type === "quantity") {
        const qty = parseFloat(firstInput.val()) || 0;
        if (qty > 0) {
          value = String(qty);
          price = calcImpactPrice(
            firstInput.data("impact-type"),
            parseFloat(firstInput.data("impact-value")) || 0,
            qty
          );
        }
      } else if (type === "textarea" || type === "text") {
        const txt = (firstInput.val() || "").trim();
        if (txt) {
          value = txt;
          price = calcImpactPrice(
            firstInput.data("impact-type"),
            parseFloat(firstInput.data("impact-value")) || 0,
            1
          );
        }
      } else if (type === "sqm" || type === "kilometers") {
        const quantity = parseFloat(firstInput.val()) || 0;
        if (quantity > 0) {
          value = String(quantity);
          const pricePerUnit =
            parseFloat(firstInput.data("price-per-unit")) || 0;
          price = quantity * pricePerUnit;
        }
      }

      if (value !== null && value !== "") {
        opts[optionId] = { id: optionId, name, type, value, price };
      }
    });

    // Recalculate total from scratch
    for (const id in opts) {
      optionsTotal += opts[id].price;
    }

    state.optionsById = opts;
    state.pricing.options = optionsTotal;
    recalcTotal();
  }

  function calcImpactPrice(type, impact, quantity) {
    const base = state.pricing.base || 0;
    if (!impact) return 0;
    if (type === "percentage") return (base * impact) / 100;
    if (type === "multiply") return base * (impact > 0 ? impact - 1 : 0);
    return impact * (quantity || 1);
  }

  function recalcTotal() {
    const base = state.pricing.base || 0;
    const opts = state.pricing.options || 0;
    const disc = state.pricing.discount || 0;
    state.pricing.total = Math.max(0, base + opts - disc);
    updateLiveSummary();
  }

  // ==========================================
  // STEP 4: PETS
  // ==========================================

  $(document).on("change", 'input[name="has_pets"]', function () {
    const val = $(this).val();
    const show = val === "yes";
    if (show) {
      $("#mobooking-pet-details-container").removeClass("is-collapsed");
    } else {
      $("#mobooking-pet-details-container").addClass("is-collapsed");
    }
  });

  // ==========================================
  // STEP 5: FREQUENCY
  // ==========================================

  $(document).on("change", 'input[name="frequency"]', function () {
    state.frequency = $(this).val();
  });

  // ==========================================
  // STEP 6: DATE/TIME
  // ==========================================

  function initDatePicker() {
    if (!els.dateInput.length) return;

    // If instance exists, just redraw and exit
    if (els.dateInput.data("fp")) {
      els.dateInput.data("fp").redraw();
      return;
    }

    els.dateInput.flatpickr({
      dateFormat: "Y-m-d",
      minDate: "today",
      inline: true, // show calendar by default
      onChange: function (selectedDates, dateStr) {
        state.date = dateStr || "";
        state.time = "";
        collapseTimeSlots(true);
        els.timeSlots.empty();
        if (state.date) loadTimeSlots(dateStr);
      },
      onReady: function (selectedDates, dateStr, instance) {
        $(instance.calendarContainer).addClass("mobooking-flatpickr");
      },
    });
  }

  function collapseTimeSlots(collapsed) {
    if (collapsed) {
      els.timeSlotsWrap.addClass("is-collapsed");
    } else {
      els.timeSlotsWrap.removeClass("is-collapsed");
    }
  }

  function loadTimeSlots(dateStr) {
    els.dateTimeFeedback.text("").hide();
    els.timeSlotsWrap.removeClass("hidden");
    els.timeSlots.html(
      `<div class="mobooking-spinner" style="margin: 10px auto;"></div>`
    );

    $.post(CONFIG.ajax_url, {
      action: "mobooking_get_available_time_slots",
      nonce: CONFIG.nonce,
      tenant_id: CONFIG.tenant_id,
      date: dateStr,
      services: [state.service?.service_id || 0],
    })
      .done(function (res) {
        const slots = res?.data?.time_slots || [];
        state.latestTimeSlots = slots;
        if (!slots.length) {
          els.timeSlots.html(
            `<p style=\"color:#6b7280;\">No time slots available for this date.</p>`
          );
          return;
        }
        let html = "";
        slots.forEach((s, i) => {
          html += `<a class=\"mobooking-btn mobooking-btn-outline mobooking-time-slot\" data-time=\"${
            s.start_time
          }\">${escapeHtml(s.display || `${s.start_time}`)}</a>`;
        });
        els.timeSlots.html(html);
        collapseTimeSlots(false);
        els.timeSlots.find(".mobooking-time-slot").on("click", function () {
          els.timeSlots
            .find(".mobooking-time-slot")
            .removeClass("selected active");
          $(this).addClass("selected active");
          state.time = $(this).data("time");
          updateLiveSummary();
        });
      })
      .fail(function () {
        els.timeSlots.html(
          `<p>${CONFIG.i18n.error_ajax || "Network error"}</p>`
        );
      });
  }

  // ==========================================
  // STEP 7: CUSTOMER & ACCESS
  // ==========================================

  $(document).on("change", 'input[name="property_access"]', function () {
    const name = $(this).attr("name");
    $(`input[name="${name}"]`).each(function () {
      $(this).closest(".mobooking-radio-option").removeClass("active");
    });
    if ($(this).is(":checked"))
      $(this).closest(".mobooking-radio-option").addClass("active");
    const val = $(this).val();
    state.propertyAccess.method = val;
    if (val === "other") {
      els.accessDetailsWrap.removeClass("is-collapsed");
    } else {
      els.accessDetailsWrap.addClass("is-collapsed");
    }
  });

  // ==========================================
  // LIVE SUMMARY
  // ==========================================

  function getSummaryHtml() {
    let html = "";
    if (state.service) {
      html += `<div class="summary-item">
              <span>${escapeHtml(state.service.name)}</span>
              <span>${CONFIG.currency_symbol}${(
        parseFloat(state.service.price) || 0
      ).toFixed(2)}</span>
          </div>`;
    }

    const optList = Object.values(state.optionsById || {});
    if (optList.length) {
      optList.forEach((o) => {
        html += `<div class="summary-item">
                  <span>${escapeHtml(o.name)}${
          o.value && o.type !== "checkbox"
            ? `: ${escapeHtml(String(o.value))}`
            : ""
        }</span>
                  <span>${
                    o.price > 0
                      ? `+${CONFIG.currency_symbol}${o.price.toFixed(2)}`
                      : ""
                  }</span>
              </div>`;
      });
    }

    if (state.date || state.time) {
      html += `<div class="summary-item">
              <span>Date & Time</span>
              <strong>${escapeHtml(state.date || "")}${
        state.time ? ` @ ${escapeHtml(state.time)}` : ""
      }</strong>
          </div>`;
    }

    html += `<div class="summary-item summary-total">
          <strong>Total</strong>
          <strong>${CONFIG.currency_symbol}${(state.pricing.total || 0).toFixed(
      2
    )}</strong>
      </div>`;
    return html;
  }

  function updateLiveSummary() {
    if (!els.liveSummaryContent.length) return;
    const html = getSummaryHtml();
    els.liveSummaryContent.html(
      html || `<p>${"Your selections will appear here."}</p>`
    );
  }

  function getConfirmationSummaryHtml() {
    let html = '<div class="mobooking-confirmation-grid">';

    // --- Service Details Column ---
    html += '<div class="mobooking-confirmation-column">';
    html += '<h4>Service Details</h4>';

    if (state.service) {
      html += `<div class="mobooking-summary-item">
          <span class="item-label">${escapeHtml(state.service.name)}</span>
          <span class="item-value">${CONFIG.currency_symbol}${(
        parseFloat(state.service.price) || 0
      ).toFixed(2)}</span>
      </div>`;
    }

    const optList = Object.values(state.optionsById || {});
    if (optList.length) {
      html += '<div class="mobooking-summary-options">';
      optList.forEach((o) => {
        html += `<div class="mobooking-summary-item">
            <span class="item-label">${escapeHtml(o.name)}${
          o.value && !["checkbox", "toggle"].includes(o.type)
            ? `: ${escapeHtml(String(o.value))}`
            : ""
        }</span>
            <span class="item-value">${
              o.price > 0
                ? `+${CONFIG.currency_symbol}${o.price.toFixed(2)}`
                : "Included"
            }</span>
        </div>`;
      });
      html += "</div>";
    }
    html += "</div>"; // end column

    // --- Booking Details Column ---
    html += '<div class="mobooking-confirmation-column">';
    html += '<h4>Booking Details</h4>';

    if (state.date || state.time) {
      html += `<div class="mobooking-summary-item">
          <span class="item-label">Date & Time</span>
          <span class="item-value">${escapeHtml(state.date || "")}${
        state.time ? ` @ ${escapeHtml(state.time)}` : ""
      }</span>
      </div>`;
    }

    html += `<div class="mobooking-summary-item">
        <span class="item-label">Frequency</span>
        <span class="item-value" style="text-transform: capitalize;">${escapeHtml(
          state.frequency
        )}</span>
    </div>`;

    if (state.pets.has_pets) {
      html += `<div class="mobooking-summary-item">
          <span class="item-label">Pets</span>
          <span class="item-value">Yes</span>
      </div>`;
    }
    html += "</div>"; // end column

    html += "</div>"; // end grid

    // --- Total ---
    html += `<div class="mobooking-summary-item mobooking-summary-total">
        <span class="item-label">Total</span>
        <span class="item-value">${CONFIG.currency_symbol}${(
      state.pricing.total || 0
    ).toFixed(2)}</span>
    </div>`;

    return html;
  }

  function renderConfirmationSummary() {
    const html = getConfirmationSummaryHtml();
    $("#mobooking-confirmation-details").html(html);
  }

  // ==========================================
  // SUBMISSION
  // ==========================================

  function confirmAndSubmitBooking() {
    // Final validation before submission
    if (!validateStep(7)) {
      showStep(7);
      return;
    }

    const startTime = Date.now();
    const minDisplayTime = 2000; // 2 seconds

    // Hide form content and show a larger spinner
    $("#mobooking-step-8 .mobooking-step-title, #mobooking-confirmation-details, #mobooking-step-8 .mobooking-button-group").slideUp();
    els.confirmationFeedback.html(
      '<div class="mobooking-spinner" style="margin: 3rem auto; width: 40px; height: 40px;"></div>'
    ).show();


    // Collect all data
    state.pets.has_pets = $('input[name="has_pets"]:checked').val() === "yes";
    state.pets.details = $("#mobooking-pet-details").val().trim();
    state.frequency =
      $('input[name="frequency"]:checked').val() || state.frequency;
    state.customer = {
      name: els.nameInput.val().trim(),
      email: els.emailInput.val().trim(),
      phone: els.phoneInput.val().trim(),
      address: els.addressInput.val().trim(),
      date: state.date,
      time: state.time,
      instructions: $("#mobooking-special-instructions").val()?.trim() || "",
    };
    state.propertyAccess.method =
      $('input[name="property_access"]:checked').val() || "home";
    state.propertyAccess.details =
      $("#mobooking-access-instructions").val()?.trim() || "";

    const payload = {
      action: "mobooking_create_booking",
      tenant_id: CONFIG.tenant_id,
      nonce: CONFIG.nonce,
      selected_services: JSON.stringify([
        {
          service_id: state.service?.service_id,
          configured_options: state.optionsById,
        },
      ]),
      service_options: JSON.stringify(state.optionsById),
      customer_details: JSON.stringify(state.customer),
      service_frequency: state.frequency,
      pet_information: JSON.stringify(state.pets),
      property_access: JSON.stringify(state.propertyAccess),
      pricing: JSON.stringify(state.pricing),
    };

    const handleSuccess = () => {
      const elapsedTime = Date.now() - startTime;
      const remainingTime = Math.max(0, minDisplayTime - elapsedTime);
      setTimeout(() => showStep(9), remainingTime);
    };

    const handleError = (message) => {
      const elapsedTime = Date.now() - startTime;
      const remainingTime = Math.max(0, minDisplayTime - elapsedTime);
      setTimeout(() => {
        // Restore form and show error
        $("#mobooking-step-8 .mobooking-step-title, #mobooking-confirmation-details, #mobooking-step-8 .mobooking-button-group").slideDown();
        showFeedback(els.confirmationFeedback, "error", message);
      }, remainingTime);
    };

    $.post(CONFIG.ajax_url, payload)
      .done(function (res) {
        if (res.success) {
          handleSuccess();
        } else {
          handleError(res.data?.message || CONFIG.i18n.booking_error || "Submission error");
        }
      })
      .fail(function () {
        handleError(CONFIG.i18n.error_ajax || "Network error");
      });
  }

  // Expose to global scope for template buttons
  window.moBookingSubmitForm = confirmAndSubmitBooking;

  window.moBookingResetForm = function () {
    window.location.reload();
  };

  // ==========================================
  // UTIL
  // ==========================================

  function escapeHtml(text) {
    if (text == null) return "";
    const div = document.createElement("div");
    div.textContent = String(text);
    return div.innerHTML;
  }

  // ==========================================
  // INIT
  // ==========================================

  // If step 1 is disabled, start at step 2
  const startStep = $("#mobooking-step-1").length ? 1 : 2;

  // If location check is disabled, make the ZIP input in step 7 editable
  if (
    !CONFIG.settings?.bf_enable_location_check ||
    CONFIG.settings.bf_enable_location_check === "0"
  ) {
    const zipInput = $("#mobooking-zip-readonly");
    zipInput.prop("readonly", false);

    // Also, update state when user types in their zip
    zipInput.on("input", function () {
      state.zip = $(this).val();
    });

    // Also, hide the "previous" button on step 2, as there's no step 1
    $("#mobooking-step-2 .mobooking-btn-secondary").hide();
  }

  // Add collapsible classes
  els.timeSlotsWrap.addClass("mobooking-collapsible is-collapsed");
  $("#mobooking-custom-access-details").addClass(
    "mobooking-collapsible is-collapsed"
  );
  $("#mobooking-pet-details-container").addClass(
    "mobooking-collapsible is-collapsed"
  );

  showStep(startStep);

  // Pets step active styling for radios
  $(document).on("change", 'input[name="has_pets"]', function () {
    const name = $(this).attr("name");
    $(`input[name="${name}"]`).each(function () {
      $(this).closest(".mobooking-radio-option").removeClass("active");
    });
    if ($(this).is(":checked"))
      $(this).closest(".mobooking-radio-option").addClass("active");
  });

  // Frequency radios active styling
  $(document).on("change", 'input[name="frequency"]', function () {
    const name = $(this).attr("name");
    $(`input[name="${name}"]`).each(function () {
      $(this).closest(".mobooking-radio-option").removeClass("active");
    });
    if ($(this).is(":checked"))
      $(this).closest(".mobooking-radio-option").addClass("active");
    state.frequency = $(this).val();
  });

  // Property access radios active styling is handled in the STEP 7 section
});
