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
    if (step === 6) initDatePicker();
    if (step === 7) {
      $("#mobooking-zip-readonly").val(state.zip);
      // TODO: Initialize Google Maps Places Autocomplete here when API key is available
    }
    if (step === 8) renderConfirmationSummary();

    // Hide summary on contact step, otherwise show it if a service is selected
    if (step === 7) {
      els.liveSummaryContainer.removeClass("active");
    } else if (state.service) {
      els.liveSummaryContainer.addClass("active");
    }

    updateLiveSummary();
  }

  function nextStep() {
    const next = state.currentStep + 1;
    if (validateStep(state.currentStep)) showStep(next);
  }

  function prevStep() {
    const prev = state.currentStep - 1;
    if (prev >= 1) {
      // Hide summary when going back to the step before service selection
      if (prev === 1) {
        els.liveSummaryContainer.removeClass("active");
      }
      showStep(prev);
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
        // Validate required options
        const missing = [];
        els.optionsContainer.find("[data-required='1']").each(function () {
          const type = $(this).data("type");
          if (type === "checkbox" && !$(this).is(":checked"))
            missing.push($(this).data("name") || "Option");
          else if (
            (type === "text" ||
              type === "textarea" ||
              type === "number" ||
              type === "quantity" ||
              type === "sqm") &&
            !$(this).val()
          )
            missing.push($(this).data("name") || "Option");
          else if ((type === "select" || type === "radio") && !$(this).val())
            missing.push($(this).data("name") || "Option");
        });
        if (missing.length)
          return (
            showFeedback(
              els.optionsFeedback,
              "error",
              `Please fill required: ${missing.join(", ")}`
            ),
            false
          );
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
      action: "mobooking_get_services",
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
            ${
              CONFIG.settings.service_card_display === "icon" && svc.icon
                ? `<div class="mobooking-service-icon"><img src="${
                    svc.icon
                  }" alt="${escapeHtml(svc.name)}"></div>`
                : svc.image_url
                ? `<div class="mobooking-service-image"><img src="${
                    svc.image_url
                  }" alt="${escapeHtml(svc.name)}"></div>`
                : ""
            }
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
          <div class="mobooking-service-footer">
            <div>
              <div class="mobooking-service-price">${priceDisplay}</div>
              <div class="mobooking-service-duration">${duration} minutes</div>
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

        // Show summary smoothly
        els.liveSummaryContainer.addClass("active");

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

      html += `<div class="mobooking-form-group" data-option-id="${id}">`;
      html += `<label class="mobooking-label">${escapeHtml(name)}${
        impactValue > 0 ? priceImpactLabel(impactType, impactValue) : ""
      }${isReq ? ' <span style="color:#ef4444">*</span>' : ""}</label>`;

      if (type === "checkbox") {
        html += `<label class="mobooking-radio-option"><input type="checkbox" class="mobooking-option-input" data-type="checkbox" data-required="${isReq}" data-name="${escapeHtml(
          name
        )}" data-impact-type="${impactType}" data-impact-value="${impactValue}" value="1"> <span>${escapeHtml(
          opt.description || ""
        )}</span></label>`;
      } else if (type === "select") {
        html += `<select class="mobooking-select mobooking-option-input" data-type="select" data-required="${isReq}" data-name="${escapeHtml(
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
            price > 0 ? ` (+${CONFIG.currency_symbol}${price.toFixed(2)})` : ""
          }</option>`;
        });
        html += `</select>`;
        if (opt.description)
          html += `<div class="mobooking-option-description" style="color:#6b7280;font-size:0.875rem;margin-top:0.5rem;">${escapeHtml(
            opt.description
          )}</div>`;
      } else if (type === "radio") {
        values.forEach((v, idx) => {
          const label = v.label || v.value || v;
          const value = v.value || v.label || v;
          const price = parseFloat(v.price) || 0;
          const rid = `mobooking-opt-${id}-${idx}`;
          html += `<label class="mobooking-radio-option"><input type="radio" name="mobooking-option-${id}" id="${rid}" class="mobooking-option-input" data-type="radio" data-required="${isReq}" data-name="${escapeHtml(
            name
          )}" value="${escapeHtml(
            value
          )}" data-price="${price}"> <span>${escapeHtml(label)}${
            price > 0 ? ` (+${CONFIG.currency_symbol}${price.toFixed(2)})` : ""
          }</span></label>`;
        });
        if (opt.description)
          html += `<div class="mobooking-option-description" style="color:#6b7280;font-size:0.875rem;margin-top:0.5rem;">${escapeHtml(
            opt.description
          )}</div>`;
      } else if (type === "number" || type === "quantity") {
        html += `<input type="number" min="0" class="mobooking-input mobooking-option-input" data-type="${type}" data-required="${isReq}" data-name="${escapeHtml(
          name
        )}" data-impact-type="${impactType}" data-impact-value="${impactValue}" value="${
          isReq ? 1 : 0
        }">`;
        if (opt.description)
          html += `<div class="mobooking-option-description" style="color:#6b7280;font-size:0.875rem;margin-top:0.5rem;">${escapeHtml(
            opt.description
          )}</div>`;
      } else if (type === "textarea") {
        html += `<textarea class="mobooking-textarea mobooking-option-input" data-type="textarea" data-required="${isReq}" data-name="${escapeHtml(
          name
        )}" data-impact-type="${impactType}" data-impact-value="${impactValue}"></textarea>`;
      } else if (type === "sqm") {
        html += `<input type="number" min="1" step="0.1" placeholder="Enter square meters" class="mobooking-input mobooking-option-input" data-type="sqm" data-required="${isReq}" data-name="${escapeHtml(
          name
        )}" data-price-per-unit="${impactValue}">`;
      } else if (type === "kilometers") {
        html += `<input type="number" min="1" step="0.1" placeholder="Enter kilometers" class="mobooking-input mobooking-option-input" data-type="kilometers" data-required="${isReq}" data-name="${escapeHtml(
          name
        )}" data-price-per-unit="${impactValue}">`;
      } else {
        // text default
        html += `<input type="text" class="mobooking-input mobooking-option-input" data-type="text" data-required="${isReq}" data-name="${escapeHtml(
          name
        )}" data-impact-type="${impactType}" data-impact-value="${impactValue}" placeholder="Enter ${escapeHtml(
          name.toLowerCase()
        )}...">`;
        if (opt.description)
          html += `<div class="mobooking-option-description" style="color:#6b7280;font-size:0.875rem;margin-top:0.5rem;">${escapeHtml(
            opt.description
          )}</div>`;
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

    els.optionsContainer.find(".mobooking-option-input").each(function () {
      const $el = $(this);
      const type = $el.data("type");
      const name = $el.data("name") || "Option";
      const optWrap = $el.closest("[data-option-id]");
      const optionId = parseInt(optWrap.data("option-id"), 10);

      let value = null;
      let price = 0;

      if (type === "checkbox") {
        if ($el.is(":checked")) {
          value = "1";
          price = calcImpactPrice(
            $el.data("impact-type"),
            parseFloat($el.data("impact-value")) || 0,
            1
          );
        }
      } else if (type === "select") {
        value = $el.val();
        const p = parseFloat($el.find("option:selected").data("price")) || 0;
        if (value) price = p; // select values carry their own fixed price
      } else if (type === "radio") {
        const selected = $(
          `input[name="mobooking-option-${optionId}"]:checked`
        );
        if (selected.length) {
          value = selected.val();
          price = parseFloat(selected.data("price")) || 0; // radios carry own fixed price
        }
      } else if (type === "number" || type === "quantity") {
        const qty = parseFloat($el.val()) || 0;
        if (qty > 0) {
          value = String(qty);
          price = calcImpactPrice(
            $el.data("impact-type"),
            parseFloat($el.data("impact-value")) || 0,
            qty
          );
        }
      } else if (type === "textarea" || type === "text") {
        const txt = ($el.val() || "").trim();
        if (txt) {
          value = txt;
          price = calcImpactPrice(
            $el.data("impact-type"),
            parseFloat($el.data("impact-value")) || 0,
            1
          );
        }
      } else if (type === "sqm" || type === "kilometers") {
        const quantity = parseFloat($el.val()) || 0;
        if (quantity > 0) {
          value = String(quantity);
          const pricePerUnit = parseFloat($el.data("price-per-unit")) || 0;
          price = quantity * pricePerUnit;
        }
      }

      if (value !== null && value !== "") {
        opts[optionId] = { id: optionId, name, type, value, price };
        optionsTotal += price;
      }
    });

    state.optionsById = opts;
    state.pricing.options = optionsTotal;
    recalcTotal();
  }

  function calcImpactPrice(type, impact, quantity) {
    const base = state.pricing.base || 0;
    if (!impact) return 0;
    if (type === "percentage") return ((base * impact) / 100) * (quantity || 1);
    if (type === "multiply") return base * impact * (quantity || 1);
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
      onReady: function(selectedDates, dateStr, instance) {
        $(instance.calendarContainer).addClass('mobooking-flatpickr');
      }
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
          html += `<button type=\"button\" class=\"mobooking-btn mobooking-btn-outline mobooking-time-slot\" data-time=\"${
            s.start_time
          }\" style=\"margin:5px;\">${escapeHtml(
            s.display || `${s.start_time}`
          )}</button>`;
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
      $("#mobooking-custom-access-details").removeClass("is-collapsed");
    } else {
      $("#mobooking-custom-access-details").addClass("is-collapsed");
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
    let html = "";

    // Service
    if (state.service) {
      html += `<div class="mobooking-summary-item">
            <span class="item-label">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                ${escapeHtml(state.service.name)}
            </span>
            <span class="item-value">${
              CONFIG.currency_symbol
            }${(parseFloat(state.service.price) || 0).toFixed(2)}</span>
        </div>`;
    }

    // Options
    const optList = Object.values(state.optionsById || {});
    if (optList.length) {
      html += optList
        .map(
          (o) => `
            <div class="mobooking-summary-item">
                <span class="item-label" style="padding-left: 2.75rem;">${escapeHtml(
                  o.name
                )}${
            o.value && o.type !== "checkbox"
              ? `: ${escapeHtml(String(o.value))}`
              : ""
          }</span>
                <span class="item-value">${
                  o.price > 0
                    ? `+${CONFIG.currency_symbol}${o.price.toFixed(2)}`
                    : ""
                }</span>
            </div>
        `
        )
        .join("");
    }

    // Date & Time
    if (state.date || state.time) {
      html += `<div class="mobooking-summary-item">
            <span class="item-label">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><path d="M3 10h18"></path></svg>
                Date & Time
            </span>
            <span class="item-value">${escapeHtml(state.date || "")}${
        state.time ? ` @ ${escapeHtml(state.time)}` : ""
      }</span>
        </div>`;
    }

    // Total
    html += `<div class="mobooking-summary-item mobooking-summary-total">
        <span class="item-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z"></path><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z"></path></svg>
            Total
        </span>
        <span class="item-value">${
          CONFIG.currency_symbol
        }${(state.pricing.total || 0).toFixed(2)}</span>
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
      // Re-validate customer details just in case
      showStep(7);
      return;
    }

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

    showFeedback(
      els.confirmationFeedback,
      "",
      CONFIG.i18n.submitting_booking || "Submitting booking..."
    );

    $.post(CONFIG.ajax_url, payload)
      .done(function (res) {
        if (res.success) {
          showStep(9); // Success step is now 9
        } else {
          showFeedback(
            els.confirmationFeedback,
            "error",
            res.data?.message || CONFIG.i18n.booking_error || "Submission error"
          );
        }
      })
      .fail(function () {
        showFeedback(
          els.confirmationFeedback,
          "error",
          CONFIG.i18n.error_ajax || "Network error"
        );
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

  // Property access radios active styling
  $(document).on("change", 'input[name="property_access"]', function () {
    const name = $(this).attr("name");
    $(`input[name="${name}"]`).each(function () {
      $(this).closest(".mobooking-radio-option").removeClass("active");
    });
    if ($(this).is(":checked"))
      $(this).closest(".mobooking-radio-option").addClass("active");
    const val = $(this).val();
    state.propertyAccess.method = val;
    if (val === "other")
      els.accessDetailsWrap
        .removeClass("hidden")
        .removeClass("mobooking-collapsed");
    else els.accessDetailsWrap.addClass("mobooking-collapsed");
  });
});
