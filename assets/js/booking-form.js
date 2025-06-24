jQuery(document).ready(function ($) {
  "use strict";

  const locationForm = $("#mobooking-bf-location-form");
  const feedbackDiv = $("#mobooking-bf-feedback"); // General feedback for Step 1 (location check)
  const step1Div = $("#mobooking-bf-step-1-location");
  const tenantIdField = $("#mobooking-bf-tenant-id");

  // Step 2 elements
  const step2ServicesDiv = $("#mobooking-bf-step-2-services");
  const servicesListDiv = $("#mobooking-bf-services-list");
  const step2FeedbackDiv = $("#mobooking-bf-step-2-feedback").hide();

  // Step 3 elements
  const step3OptionsDiv = $("#mobooking-bf-step-3-options");
  const serviceOptionsDisplayDiv = $("#mobooking-bf-service-options-display");
  const step3FeedbackDiv = $("#mobooking-bf-step-3-feedback").hide();

  // Step 4 elements
  const step4DetailsDiv = $("#mobooking-bf-step-4-details");
  const step4FeedbackDiv = $("#mobooking-bf-step-4-feedback").hide();

  // Step 5 elements
  const step5ReviewDiv = $("#mobooking-bf-step-5-review");
  const reviewSummaryDiv = $("#mobooking-bf-review-summary");
  const step5FeedbackDiv = $("#mobooking-bf-step-5-feedback").hide();
  const discountCodeInput = $("#mobooking-bf-discount-code");
  const applyDiscountBtn = $("#mobooking-bf-apply-discount-btn");
  const discountFeedbackDiv = $("#mobooking-bf-discount-feedback").hide();
  const subtotalDisplay = $("#mobooking-bf-subtotal");
  const discountAppliedDisplay = $("#mobooking-bf-discount-applied");
  const finalTotalDisplay = $("#mobooking-bf-final-total");
  const currencyCode =
    typeof mobooking_booking_form_params !== "undefined" &&
    mobooking_booking_form_params.currency_code
      ? mobooking_booking_form_params.currency_code
      : "USD";

  // Access settings, ensure defaults if not fully populated
  const formSettings = (typeof mobooking_booking_form_params !== 'undefined' && mobooking_booking_form_params.settings)
    ? mobooking_booking_form_params.settings
    : { // Fallback defaults matching Settings.php if params totally fail (should not happen with PHP logic)
        bf_header_text: 'Book Our Services',
        bf_show_pricing: '1',
        bf_allow_discount_codes: '1',
        bf_theme_color: '#1abc9c',
        bf_custom_css: '',
        bf_form_enabled: '1',
        bf_maintenance_message: 'Booking form is currently unavailable.'
      };


  // Step 6 elements
  const step6ConfirmDiv = $("#mobooking-bf-step-6-confirmation");
  const confirmationMessageDiv = $("#mobooking-bf-confirmation-message");

  let mobooking_current_step = 1; // Keep track of the current visible step
  let publicServicesCache = []; // Cache for service data from Step 2

  // Initialize tenant_id from PHP parameters (which now handles slug or ?tid=)
  let initialTenantId = "";
  if (
    typeof mobooking_booking_form_params !== "undefined" &&
    mobooking_booking_form_params.tenant_id
  ) {
    initialTenantId = String(mobooking_booking_form_params.tenant_id); // Ensure it's a string
  }

  if (initialTenantId && initialTenantId !== "0") {
    // Check it's not 0 or empty
    tenantIdField.val(initialTenantId);
    // Pre-populate sessionStorage as the tenant ID is known.
    sessionStorage.setItem("mobooking_cart_tenant_id", initialTenantId);
    // console.log('Booking form initialized with Tenant ID:', initialTenantId);
  } else {
    // console.warn('Booking form: Tenant ID is missing or invalid from mobooking_booking_form_params.');
    // The location form validation will catch if tenantIdField is empty later.
  }

  locationForm.on("submit", function (e) {
    e.preventDefault();
    feedbackDiv.empty().removeClass("success error").hide();
    const zipCode = $("#mobooking-bf-zip-code").val().trim();
    const countryCode = $("#mobooking-bf-country-code").val().trim();
    const tenantId = tenantIdField.val();

    if (!zipCode) {
      feedbackDiv
        .text(
          mobooking_booking_form_params.i18n.zip_required ||
            "ZIP Code is required."
        )
        .addClass("error")
        .show();
      return;
    }
    if (!tenantId) {
      feedbackDiv
        .text(
          mobooking_booking_form_params.i18n.tenant_id_missing ||
            "Business identifier is missing."
        )
        .addClass("error")
        .show();
      return;
    }
    if (!countryCode) {
      feedbackDiv
        .text(
          mobooking_booking_form_params.i18n.country_code_required ||
            "Country Code is required."
        )
        .addClass("error")
        .show();
      return;
    }

    const submitButton = $(this).find('button[type="submit"]');
    const originalButtonText = submitButton.text();
    submitButton
      .prop("disabled", true)
      .text(mobooking_booking_form_params.i18n.checking || "Checking...");

    $.ajax({
      url: mobooking_booking_form_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_check_zip_availability",
        nonce: mobooking_booking_form_params.nonce,
        zip_code: zipCode,
        country_code: countryCode,
        tenant_id: tenantId,
      },
      success: function (response) {
        if (response.success) {
          feedbackDiv
            .text(response.data.message)
            .addClass(response.data.serviced ? "success" : "error")
            .show();
          if (response.data.serviced) {
            sessionStorage.setItem("mobooking_cart_zip", zipCode);
            sessionStorage.setItem("mobooking_cart_country", countryCode);
            sessionStorage.setItem("mobooking_cart_tenant_id", tenantId);
            displayStep(2);
            displayStep2_LoadServices();
          }
        } else {
          feedbackDiv
            .text(
              response.data.message ||
                mobooking_booking_form_params.i18n.error_generic
            )
            .addClass("error")
            .show();
        }
      },
      error: function () {
        feedbackDiv
          .text(mobooking_booking_form_params.i18n.error_generic)
          .addClass("error")
          .show();
      },
      complete: function () {
        submitButton.prop("disabled", false).text(originalButtonText);
      },
    });
  });

  function displayStep(stepToShow) {
    const steps = $(".mobooking-bf__step");
    let targetStepDiv;

    if (stepToShow === 1) targetStepDiv = step1Div;
    else if (stepToShow === 2) targetStepDiv = step2ServicesDiv;
    else if (stepToShow === 3) targetStepDiv = step3OptionsDiv;
    else if (stepToShow === 4) targetStepDiv = step4DetailsDiv;
    else if (stepToShow === 5) targetStepDiv = step5ReviewDiv;
    else if (stepToShow === 6) targetStepDiv = step6ConfirmDiv;

    steps.each(function() {
      const currentStep = $(this);
      if (currentStep.is(targetStepDiv)) {
        // If it's the target step and not already visible, fade it in
        if (!currentStep.hasClass('fade-in')) {
          currentStep.removeClass('fade-out mobooking-bf__hidden').show(); // Remove hidden, show for animation
          // Timeout to allow display:block to take effect before adding animation class
          setTimeout(() => {
            currentStep.addClass('fade-in');
          }, 10);
        }
      } else {
        // If it's not the target step and is visible, fade it out
        if (currentStep.hasClass('fade-in')) {
          currentStep.removeClass('fade-in').addClass('fade-out');
          // Hide after animation - listen for animationend
          currentStep.one('animationend', function() {
            if (currentStep.hasClass('fade-out')) { // Check if it's still meant to be hidden
              currentStep.addClass('mobooking-bf__hidden').hide();
            }
          });
        } else if (!currentStep.hasClass('mobooking-bf__hidden')) {
            // If it was never faded in but is visible, just hide it
            currentStep.addClass('mobooking-bf__hidden').hide();
        }
      }
    });

    // Ensure the target step is not accidentally hidden by animationend if re-shown quickly
    if (targetStepDiv && targetStepDiv.length) {
        if (targetStepDiv.hasClass('fade-out')) { // If it was fading out, reverse that
            targetStepDiv.removeClass('fade-out mobooking-bf__hidden').show().addClass('fade-in');
        } else if (targetStepDiv.hasClass('mobooking-bf__hidden')) { // If it was hidden, show and fade in
            targetStepDiv.removeClass('mobooking-bf__hidden').show();
            setTimeout(() => {
                targetStepDiv.addClass('fade-in');
            }, 10);
        }
    }


    mobooking_current_step = stepToShow;
  }

  function bfRenderTemplate(templateId, data) {
    let template = $(templateId).html();
    if (!template) return "";
    for (const key in data) {
      const value =
        typeof data[key] === "string" || typeof data[key] === "number"
          ? data[key]
          : "";
      const sanitizedValue = $("<div>").text(value).html();
      template = template.replace(
        new RegExp("<%=\\s*" + key + "\\s*%>", "g"),
        sanitizedValue
      );
    }
    return template;
  }

  function displayStep2_LoadServices() {
    const tenantId = sessionStorage.getItem("mobooking_cart_tenant_id");
    if (!tenantId) {
      feedbackDiv
        .text(
          mobooking_booking_form_params.i18n.tenant_id_missing_refresh ||
            "Tenant ID missing."
        )
        .addClass("error")
        .show();
      displayStep(1);
      return;
    }
    servicesListDiv
      .html(
        "<p>" +
          (mobooking_booking_form_params.i18n.loading_services || "Loading...")
      )
      .show();
    step2FeedbackDiv.empty().hide();
    $.ajax({
      url: mobooking_booking_form_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_get_public_services",
        nonce: mobooking_booking_form_params.nonce,
        tenant_id: tenantId,
      },
      success: function (response) {
        servicesListDiv.empty();
        publicServicesCache = [];
        if (response.success && response.data.length) {
          publicServicesCache = response.data;
          publicServicesCache.forEach(function (service) {
            servicesListDiv.append(
              bfRenderTemplate("#mobooking-bf-service-item-template", service)
            );
          });
        } else if (response.success) {
          servicesListDiv.html(
            "<p>" +
              (mobooking_booking_form_params.i18n.no_services_available ||
                "No services available.") +
              "</p>"
          );
        } else {
          servicesListDiv.html(
            "<p>" +
              (response.data.message ||
                mobooking_booking_form_params.i18n.error_loading_services ||
                "Error.") +
              "</p>"
          );
        }
      },
      error: function () {
        servicesListDiv.html(
          "<p>" +
            (mobooking_booking_form_params.i18n.error_loading_services ||
              "Error.") +
            "</p>"
        );
      },
    });
  }

  $("#mobooking-bf-services-next-btn").on("click", function () {
    const selectedServicesData = [];
    servicesListDiv
      .find('input[name="selected_services[]"]:checked')
      .each(function () {
        const serviceId = parseInt($(this).data("service-id"), 10);
        const serviceData = publicServicesCache.find(
          (s) => parseInt(s.service_id, 10) === serviceId
        );
        if (serviceData) selectedServicesData.push(serviceData);
      });
    if (selectedServicesData.length === 0) {
      step2FeedbackDiv
        .text(
          mobooking_booking_form_params.i18n.select_one_service ||
            "Select a service."
        )
        .addClass("error")
        .show();
      return;
    }
    step2FeedbackDiv.empty().removeClass("error").hide();
    sessionStorage.setItem(
      "mobooking_cart_selected_services",
      JSON.stringify(selectedServicesData)
    );
    displayStep(3);
    displayStep3_ServiceOptions();
  });

  // Helper function to format currency
  function formatCurrency(amount, code, isImpact = false) {
    const numAmount = parseFloat(amount);
    if (isNaN(numAmount)) return amount; // Return original if not a number

    const formattedAmount = numAmount.toFixed(2);
    const absFormattedAmount = Math.abs(numAmount).toFixed(2);

    if (isImpact) {
      const sign = numAmount > 0 ? "+" : numAmount < 0 ? "-" : "";
      return `${sign}${absFormattedAmount} ${code}`;
    } else {
      return `${code} ${formattedAmount}`;
    }
  }

  function formatOptionPriceImpact(option) {
    if (option.price_impact_value && option.price_impact_type) {
      let val = parseFloat(option.price_impact_value);
      if (isNaN(val) || val === 0) return ""; // No impact or invalid value

      if (option.price_impact_type === "percentage") {
        let sign = val > 0 ? "+" : ""; // Percentage can also be negative
        return `${sign}${Math.abs(val).toFixed(2)}%`;
      } else if (
        option.price_impact_type === "fixed" ||
        option.price_impact_type === "multiply_value"
      ) {
        // For multiply_value, the actual impact is calculated later. Here, we just show the per-item impact.
        return formatCurrency(val, currencyCode, true);
      }
    }
    return "";
  }

  function displayStep3_ServiceOptions() {
    const selectedServicesString = sessionStorage.getItem(
      "mobooking_cart_selected_services"
    );
    if (!selectedServicesString) {
      step2FeedbackDiv
        .text(mobooking_booking_form_params.i18n.error_generic)
        .addClass("error")
        .show();
      displayStep(2);
      return;
    }
    const selectedServices = JSON.parse(selectedServicesString);
    serviceOptionsDisplayDiv.empty();
    step3FeedbackDiv.empty().hide();
    if (!selectedServices || selectedServices.length === 0) {
      serviceOptionsDisplayDiv.html(
        "<p>" +
          (mobooking_booking_form_params.i18n.no_options_for_services ||
            "No options.") +
          "</p>"
      );
      return;
    }
    let hasAnyOptionsToShow = false;
    selectedServices.forEach(function (service) {
      if (
        service.options &&
        Array.isArray(service.options) &&
        service.options.length > 0
      ) {
        hasAnyOptionsToShow = true;
        serviceOptionsDisplayDiv.append(
          $("<h3>").text(
            service.name +
              " - " +
              (mobooking_booking_form_params.i18n.configure_options ||
                "Configure")
          )
        );
        service.options.forEach(function (option) {
          let templateId = "#mobooking-bf-option-" + option.type + "-template";
          if (!$(templateId).length)
            templateId = "#mobooking-bf-option-text-template";
          let templateData = {
            ...option,
            service_id: service.service_id,
            price_impact_value_formatted: formatOptionPriceImpact(option),
            is_required:
              option.is_required == 1 || option.is_required === true ? 1 : 0,
          };
          if (
            (option.type === "select" || option.type === "radio") &&
            typeof option.option_values === "string"
          ) {
            try {
              templateData.parsed_option_values = JSON.parse(
                option.option_values
              );
            } catch (e) {
              templateData.parsed_option_values = [];
            }
          } else if (Array.isArray(option.option_values))
            templateData.parsed_option_values = option.option_values;
          else templateData.parsed_option_values = [];
          serviceOptionsDisplayDiv.append(
            bfRenderTemplate(templateId, templateData)
          );
        });
      }
    });
    if (!hasAnyOptionsToShow)
      serviceOptionsDisplayDiv.html(
        "<p>" +
          (mobooking_booking_form_params.i18n.no_options_for_services ||
            "No options.") +
          "</p>"
      );
  }

  $("#mobooking-bf-options-next-btn").on("click", function () {
    let selectedServicesString = sessionStorage.getItem(
      "mobooking_cart_selected_services"
    );
    if (!selectedServicesString) {
      step3FeedbackDiv
        .text(mobooking_booking_form_params.i18n.error_generic)
        .addClass("error")
        .show();
      return;
    }
    let selectedServices = JSON.parse(selectedServicesString);
    let allRequiredFilled = true;
    let validationError = "";
    selectedServices.forEach(function (service, serviceIndex) {
      if (!service.options || service.options.length === 0) {
        selectedServices[serviceIndex].configured_options = [];
        return;
      }
      let configuredOptionsForService = [];
      service.options.forEach(function (option) {
        const $optionItem = serviceOptionsDisplayDiv.find(
          `.mobooking-bf-option-item[data-service-id="${service.service_id}"][data-option-id="${option.option_id}"]`
        );
        if (!$optionItem.length) return;
        let selectedValue = "",
          inputSelector = `[name^="service_option[${service.service_id}][${option.option_id}]"]`;
        switch (option.type) {
          case "checkbox":
            selectedValue = $optionItem.find(inputSelector).is(":checked")
              ? "1"
              : "0";
            break;
          case "quantity":
          case "number":
            selectedValue = $optionItem.find(inputSelector).val();
            break;
          case "radio":
            selectedValue = $optionItem.find(inputSelector + ":checked").val();
            if (typeof selectedValue === "undefined") selectedValue = "";
            break;
          default:
            selectedValue = $optionItem.find(inputSelector).val();
        }
        if (
          option.is_required == 1 &&
          (selectedValue === "" ||
            (selectedValue === "0" && option.type === "quantity"))
        ) {
          allRequiredFilled = false;
          validationError += `${
            mobooking_booking_form_params.i18n.option_required_prefix ||
            "Option"
          } '${option.name}' ${
            mobooking_booking_form_params.i18n.option_required_suffix ||
            "is required"
          } ${
            mobooking_booking_form_params.i18n.for_service || "for service"
          } '${service.name}'.\n`;
        }
        configuredOptionsForService.push({
          option_id: option.option_id,
          option_name: option.name,
          selected_value: selectedValue,
          price_impact_type: option.price_impact_type,
          price_impact_value: option.price_impact_value,
        });
      });
      selectedServices[serviceIndex].configured_options =
        configuredOptionsForService;
    });
    if (!allRequiredFilled) {
      step3FeedbackDiv
        .html(validationError.replace(/\n/g, "<br>"))
        .addClass("error")
        .show();
      return;
    }
    step3FeedbackDiv.empty().removeClass("error").hide();
    sessionStorage.setItem(
      "mobooking_cart_selected_services",
      JSON.stringify(selectedServices)
    );
    displayStep(4);
    displayStep4_CustomerDetails();
  });

  function displayStep4_CustomerDetails() {
    step4FeedbackDiv.empty().hide();
    if (typeof $.fn.datepicker === "function") {
      $("#mobooking-bf-booking-date").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: 0,
      });
    } else {
      if ($("#mobooking-bf-booking-date").attr("type") !== "date")
        $("#mobooking-bf-booking-date").prop("type", "date");
    }
    const zip = sessionStorage.getItem("mobooking_cart_zip"),
      country = sessionStorage.getItem("mobooking_cart_country"),
      serviceAddressField = $("#mobooking-bf-service-address");
    if (zip) {
      const currentAddress = serviceAddressField.val();
      let prefillAddress = zip;
      if (country) prefillAddress += ", " + country;
      if (!currentAddress || !currentAddress.includes(zip))
        serviceAddressField.val(
          (currentAddress ? currentAddress + "\n" : "") + prefillAddress
        );
    }
  }

  $("#mobooking-bf-details-next-btn").on("click", function () {
    step4FeedbackDiv.empty().removeClass("error").hide();
    let isValid = true;
    let errors = [];
    const customerDetails = {
      customer_name: $("#mobooking-bf-customer-name").val().trim(),
      customer_email: $("#mobooking-bf-customer-email").val().trim(),
      customer_phone: $("#mobooking-bf-customer-phone").val().trim(),
      service_address: $("#mobooking-bf-service-address").val().trim(),
      booking_date: $("#mobooking-bf-booking-date").val().trim(),
      booking_time: $("#mobooking-bf-booking-time").val().trim(),
      special_instructions: $("#mobooking-bf-special-instructions")
        .val()
        .trim(),
    };
    if (!customerDetails.customer_name) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.name_required);
    }
    if (!customerDetails.customer_email) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.email_required);
    } else if (
      !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(customerDetails.customer_email)
    ) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.email_invalid);
    }
    if (!customerDetails.customer_phone) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.phone_required);
    }
    if (!customerDetails.service_address) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.address_required);
    }
    if (!customerDetails.booking_date) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.date_required);
    }
    if (!customerDetails.booking_time) {
      isValid = false;
      errors.push(mobooking_booking_form_params.i18n.time_required);
    }
    if (!isValid) {
      step4FeedbackDiv.html(errors.join("<br>")).addClass("error").show();
      return;
    }
    sessionStorage.setItem(
      "mobooking_cart_booking_details",
      JSON.stringify(customerDetails)
    );
    displayStep(5);
    displayStep5_ReviewBooking();
  });

  function calculateTotalPrice(selectedServices, discountInfo = null) {
    let subtotal = 0;
    let serviceDetailsForSummary = [];
    selectedServices.forEach((service) => {
      let currentServicePrice = parseFloat(service.price) || 0;
      let serviceOptionsSummary = [];
      if (service.configured_options && service.configured_options.length > 0) {
        service.configured_options.forEach((confOpt) => {
          const originalOption = service.options.find(
            (opt) => opt.option_id == confOpt.option_id
          );
          if (!originalOption) return;
          let meaningfulSelection = false;
          if (
            originalOption.type === "checkbox" &&
            confOpt.selected_value === "1"
          )
            meaningfulSelection = true;
          else if (
            originalOption.type === "quantity" &&
            parseInt(confOpt.selected_value, 10) > 0
          )
            meaningfulSelection = true;
          else if (
            !["checkbox", "quantity"].includes(originalOption.type) &&
            confOpt.selected_value !== ""
          )
            meaningfulSelection = true;
          if (meaningfulSelection) {
            let optionPriceImpact = 0;
            const impactVal =
              parseFloat(originalOption.price_impact_value) || 0;
            const selectedVal = confOpt.selected_value;
            if (originalOption.price_impact_type === "fixed") {
              optionPriceImpact = impactVal;
            } else if (originalOption.price_impact_type === "percentage") {
              optionPriceImpact =
                (parseFloat(service.price) || 0) * (impactVal / 100);
            } else if (
              originalOption.price_impact_type === "multiply_value" &&
              originalOption.type === "quantity"
            ) {
              optionPriceImpact = impactVal * (parseInt(selectedVal, 10) || 0);
            } else if (
              originalOption.type === "select" ||
              originalOption.type === "radio"
            ) {
              let choices = [];
              if (Array.isArray(originalOption.parsed_option_values))
                choices = originalOption.parsed_option_values;
              else if (typeof originalOption.option_values === "string") {
                try {
                  choices = JSON.parse(originalOption.option_values);
                } catch (e) {}
              }
              const chosen = choices.find((c) => c.value === selectedVal);
              if (chosen && chosen.price_adjust)
                optionPriceImpact += parseFloat(chosen.price_adjust) || 0;
            }
            currentServicePrice += optionPriceImpact;
            // Store raw impact, formatting will be done at display time
            serviceOptionsSummary.push({
              name: confOpt.option_name,
              value: selectedVal,
              impact_raw: optionPriceImpact,
              impact_type: originalOption.price_impact_type,
            });
          }
        });
      }
      subtotal += currentServicePrice;
      serviceDetailsForSummary.push({
        name: service.name,
        base_price_raw: parseFloat(service.price), // Store raw base price
        options_summary: serviceOptionsSummary,
        final_service_price_raw: currentServicePrice, // Store raw final price for this service
      });
    });

    let discountAmount = 0;
    // Apply discount only if discounts are enabled by settings
    if (window.mobookingShouldShowDiscounts() && discountInfo && discountInfo.valid && discountInfo.discount) {
      const disc = discountInfo.discount;
      if (disc.type === "percentage")
        discountAmount = subtotal * (parseFloat(disc.value) / 100);
      else if (disc.type === "fixed_amount")
        discountAmount = parseFloat(disc.value);
      discountAmount = Math.min(discountAmount, subtotal); // Ensure discount doesn't exceed subtotal
    }
    let finalTotal = subtotal - discountAmount;
    if (finalTotal < 0) finalTotal = 0;

    return {
      subtotal_raw: subtotal,
      discount_applied_raw: discountAmount,
      final_total_raw: finalTotal,
      serviceDetailsForSummary: serviceDetailsForSummary,
      appliedDiscountCode:
        window.mobookingShouldShowDiscounts() && discountInfo && discountInfo.valid ? discountInfo.discount.code : null,
    };
  }

  function displayStep5_ReviewBooking() {
    step5FeedbackDiv.empty().hide();
    discountFeedbackDiv.empty().removeClass("success error").hide();

    // Hide or show pricing related elements based on settings
    if (window.mobookingShouldShowPricing()) {
        $('#mobooking-bf-pricing-summary-section').show(); // Assuming you wrap totals in this div
    } else {
        $('#mobooking-bf-pricing-summary-section').hide();
    }
    if(window.mobookingShouldShowDiscounts()){
        $('#mobooking-bf-discount-section').show(); // Already handled at init, but good to re-check
        if(discountAppliedDisplay.closest('p').length) discountAppliedDisplay.closest('p').show();

    } else {
        $('#mobooking-bf-discount-section').hide();
        if(discountAppliedDisplay.closest('p').length) discountAppliedDisplay.closest('p').hide();
    }
    const tenantId = sessionStorage.getItem("mobooking_cart_tenant_id"),
      selectedServicesString = sessionStorage.getItem(
        "mobooking_cart_selected_services"
      ),
      bookingDetailsString = sessionStorage.getItem(
        "mobooking_cart_booking_details"
      ),
      discountInfoString = sessionStorage.getItem(
        "mobooking_cart_discount_info"
      ),
      discountInfo = discountInfoString ? JSON.parse(discountInfoString) : null;

    if (!selectedServicesString || !bookingDetailsString || !tenantId) {
      step5FeedbackDiv
        .text(mobooking_booking_form_params.i18n.error_review_data_missing)
        .addClass("error")
        .show();
      return;
    }
    const selectedServices = JSON.parse(selectedServicesString),
      bookingDetails = JSON.parse(bookingDetailsString);
    const pricing = calculateTotalPrice(selectedServices, discountInfo); // pricing object now contains raw numbers
    sessionStorage.setItem("mobooking_cart_pricing", JSON.stringify(pricing)); // Store pricing with raw numbers

    let summaryHtml = `<h4>${
      mobooking_booking_form_params.i18n.customer_details || "Customer Details"
    }</h4>`;
    summaryHtml += `<p><strong>${
      mobooking_booking_form_params.i18n.name_label || "Name"
    }:</strong> ${$("<div>").text(bookingDetails.customer_name).html()}</p>`;
    summaryHtml += `<p><strong>${
      mobooking_booking_form_params.i18n.email_label || "Email"
    }:</strong> ${$("<div>").text(bookingDetails.customer_email).html()}</p>`;
    summaryHtml += `<p><strong>${
      mobooking_booking_form_params.i18n.phone_label || "Phone"
    }:</strong> ${$("<div>").text(bookingDetails.customer_phone).html()}</p>`;
    summaryHtml += `<p><strong>${
      mobooking_booking_form_params.i18n.address_label || "Address"
    }:</strong><br>${$("<div>")
      .text(bookingDetails.service_address)
      .html()
      .replace(/\n/g, "<br>")}</p>`;
    summaryHtml += `<p><strong>${
      mobooking_booking_form_params.i18n.datetime_label || "Date & Time"
    }:</strong> ${$("<div>").text(bookingDetails.booking_date).html()} at ${$(
      "<div>"
    )
      .text(bookingDetails.booking_time)
      .html()}</p>`;
    if (bookingDetails.special_instructions)
      summaryHtml += `<p><strong>${
        mobooking_booking_form_params.i18n.instructions_label || "Instructions"
      }:</strong><br>${$("<div>")
        .text(bookingDetails.special_instructions)
        .html()
        .replace(/\n/g, "<br>")}</p>`;

    summaryHtml += `<hr><h4>${
      mobooking_booking_form_params.i18n.services_summary || "Services Summary"
    }</h4>`;
    pricing.serviceDetailsForSummary.forEach((item) => {
      summaryHtml += `<div style="margin-bottom:10px;"><strong>${$("<div>")
        .text(item.name)
        .html()}</strong> - ${formatCurrency(
        item.final_service_price_raw,
        currencyCode
      )}`;
      if (item.options_summary && item.options_summary.length > 0) {
        summaryHtml += '<ul style="font-size:0.9em; margin-left:20px;">';
        item.options_summary.forEach((opt) => {
          summaryHtml += `<li>${$("<div>").text(opt.name).html()}: ${$("<div>")
            .text(opt.value)
            .html()}`;
          if (parseFloat(opt.impact_raw) !== 0) {
            summaryHtml += ` (${formatCurrency(
              opt.impact_raw,
              currencyCode,
              true
            )})`;
          }
          summaryHtml += `</li>`;
        });
        summaryHtml += "</ul>";
      }
      summaryHtml += "</div>";
    });
    reviewSummaryDiv.html(summaryHtml);

    subtotalDisplay.text(formatCurrency(pricing.subtotal_raw, currencyCode));
    discountAppliedDisplay.text(
      formatCurrency(pricing.discount_applied_raw, currencyCode)
    );
    finalTotalDisplay.text(
      formatCurrency(pricing.final_total_raw, currencyCode)
    );

    if (discountInfo && discountInfo.valid) {
      discountCodeInput.val(discountInfo.discount.code).prop("disabled", true);
      applyDiscountBtn.prop("disabled", true);
      discountFeedbackDiv
        .text(
          (mobooking_booking_form_params.i18n.discount_applied ||
            "Discount Applied:") +
            " (" +
            $("<div>").text(discountInfo.discount.code).html() +
            ")"
        )
        .removeClass("error")
        .addClass("success")
        .show();
    } else {
      discountCodeInput.val("").prop("disabled", false);
      applyDiscountBtn.prop("disabled", false);
    }
  }

  applyDiscountBtn.on("click", function () {
    const code = discountCodeInput.val().trim(),
      tenantId = sessionStorage.getItem("mobooking_cart_tenant_id");
    if (!code) {
      discountFeedbackDiv
        .text(mobooking_booking_form_params.i18n.enter_discount_code)
        .addClass("error")
        .show();
      return;
    }
    if (!tenantId) return;
    const $button = $(this);
    $button.prop("disabled", true);
    discountFeedbackDiv.empty().removeClass("success error").hide();
    $.ajax({
      url: mobooking_booking_form_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_validate_discount_public",
        nonce: mobooking_booking_form_params.nonce,
        discount_code: code,
        tenant_id: tenantId,
      },
      success: function (response) {
        if (response.success && response.data.valid)
          sessionStorage.setItem(
            "mobooking_cart_discount_info",
            JSON.stringify(response.data)
          );
        else {
          sessionStorage.removeItem("mobooking_cart_discount_info");
          discountFeedbackDiv
            .text(
              response.data.message ||
                mobooking_booking_form_params.i18n.invalid_discount_code
            )
            .addClass("error")
            .show();
        }
      },
      error: function () {
        sessionStorage.removeItem("mobooking_cart_discount_info");
        discountFeedbackDiv
          .text(mobooking_booking_form_params.i18n.error_applying_discount)
          .addClass("error")
          .show();
      },
      complete: function (xhr) {
        const response = xhr.responseJSON;
        if (!(response && response.success && response.data.valid))
          $button.prop("disabled", false);
        displayStep5_ReviewBooking();
      },
    });
  });

  $("#mobooking-bf-review-confirm-btn").on("click", function () {
    const finalBookingData = {
      tenant_id: sessionStorage.getItem("mobooking_cart_tenant_id"),
      selected_services: JSON.parse(
        sessionStorage.getItem("mobooking_cart_selected_services") || "[]"
      ),
      customer_details: JSON.parse(
        sessionStorage.getItem("mobooking_cart_booking_details") || "{}"
      ), // Renamed from booking_details
      pricing: JSON.parse(
        sessionStorage.getItem("mobooking_cart_pricing") || "{}"
      ),
      discount_info: JSON.parse(
        sessionStorage.getItem("mobooking_cart_discount_info") || "null"
      ),
      // Explicitly add zip_code from step 1 to the root of finalBookingData for easier access in PHP
      zip_code: sessionStorage.getItem("mobooking_cart_zip"),
    };
    if (
      !finalBookingData.tenant_id ||
      finalBookingData.selected_services.length === 0 ||
      !finalBookingData.customer_details.customer_name
    ) {
      step5FeedbackDiv
        .text(mobooking_booking_form_params.i18n.error_review_incomplete)
        .addClass("error")
        .show();
      return;
    }
    step5FeedbackDiv.empty().removeClass("error").hide();
    // Removed storing 'mobooking_final_booking_data' as we pass it directly.
    displayStep6_Confirmation(finalBookingData);
  });

  function displayStep6_Confirmation(finalBookingData) {
    const confirmButton = $("#mobooking-bf-review-confirm-btn");
    const originalButtonText = confirmButton.text();
    confirmButton
      .prop("disabled", true)
      .text(
        mobooking_booking_form_params.i18n.processing_booking || "Processing..."
      );
    step5FeedbackDiv.empty().hide();

    $.ajax({
      url: mobooking_booking_form_params.ajax_url,
      type: "POST",
      data: {
        action: "mobooking_create_booking",
        nonce: mobooking_booking_form_params.nonce,
        finalBookingData: JSON.stringify(finalBookingData),
      },
      success: function (response) {
        if (response.success) {
          sessionStorage.removeItem("mobooking_cart_zip");
          sessionStorage.removeItem("mobooking_cart_country");
          sessionStorage.removeItem("mobooking_cart_tenant_id");
          sessionStorage.removeItem("mobooking_cart_selected_services");
          sessionStorage.removeItem("mobooking_cart_booking_details");
          sessionStorage.removeItem("mobooking_cart_pricing");
          sessionStorage.removeItem("mobooking_cart_discount_info");
          // sessionStorage.removeItem('mobooking_final_booking_data'); // This was never stored with this key

          const successMsg = formSettings.bf_success_message || response.data.message || mobooking_booking_form_params.i18n.your_booking_confirmed || "Booking Confirmed!";
          // Replace placeholders in success message
          let finalSuccessMsg = successMsg.replace(/\{\{booking_reference\}\}/g, $("<div>").text(response.data.booking_reference).html());
          // Add more placeholder replacements if needed: {{customer_name}}, {{total_price}} etc.
          // For example, if customer_name is available in finalBookingData (passed to this function)
          if (finalBookingData && finalBookingData.customer_details && finalBookingData.customer_details.customer_name) {
            finalSuccessMsg = finalSuccessMsg.replace(/\{\{customer_name\}\}/g, $("<div>").text(finalBookingData.customer_details.customer_name).html());
          }


          confirmationMessageDiv.html("<p>" + finalSuccessMsg + "</p>");

          if (response.data.booking_reference && !successMsg.includes('{{booking_reference}}')) {
            confirmationMessageDiv.append(
              "<p>" +
                (mobooking_booking_form_params.i18n.your_ref_is || "Ref:") +
                " <strong>" +
                $("<div>").text(response.data.booking_reference).html() +
                "</strong></p>"
            );
          }
          displayStep(6);
          $("#mobooking-bf-review-back-btn").hide();
          confirmButton.hide();
        } else {
          step5FeedbackDiv
            .text(
              response.data.message ||
                mobooking_booking_form_params.i18n.error_booking_failed
            )
            .addClass("error")
            .show();
          confirmButton.prop("disabled", false).text(originalButtonText);
        }
      },
      error: function () {
        step5FeedbackDiv
          .text(mobooking_booking_form_params.i18n.error_booking_failed_ajax)
          .addClass("error")
          .show();
        confirmButton.prop("disabled", false).text(originalButtonText);
      },
    });
  }

  // Back buttons handlers
  $("#mobooking-bf-services-back-btn").on("click", function () {
    displayStep(1);
  });
  $("#mobooking-bf-options-back-btn").on("click", function () {
    displayStep(2);
  });
  $("#mobooking-bf-details-back-btn").on("click", function () {
    displayStep(3);
  });
  $("#mobooking-bf-review-back-btn").on("click", function () {
    displayStep(4);
  });

  // Initial setup on page load: Ensure Step 1 is shown.
  // Apply settings from localized params
  if (formSettings.bf_form_enabled === '0') {
    // Form is disabled, show maintenance message and hide all steps
    $('#mobooking-public-booking-form-wrapper').html(
        `<div style="padding:20px; text-align:center; border:1px solid #eee; background:#f9f9f9;">
            <h1>${formSettings.bf_maintenance_message || 'Bookings are temporarily unavailable.'}</h1>
         </div>`
    );
    // No need to proceed further with form initialization if it's disabled.
    return; // Exit ready function
  }


  if (formSettings.bf_header_text) {
    $('#mobooking-public-booking-form-wrapper > h1').text(formSettings.bf_header_text);
  }

  if (formSettings.bf_custom_css) {
    $('<style type="text/css"></style>')
      .html(formSettings.bf_custom_css)
      .appendTo("head");
  }

  if (formSettings.bf_theme_color) {
    const themeColorStyle = `
      .mobooking-bf-step button.button-primary,
      #mobooking-bf-review-confirm-btn,
      #mobooking-bf-apply-discount-btn {
        background-color: ${formSettings.bf_theme_color} !important;
        border-color: ${formSettings.bf_theme_color} !important;
        color: #fff !important;
      }
      /* Example for progress bar (if implemented) or active step headers */
      /* .mobooking-progress-bar .step.active { background-color: ${formSettings.bf_theme_color}; } */
    `;
    $('<style type="text/css"></style>')
      .html(themeColorStyle)
      .appendTo("head");
  }

  if (formSettings.bf_allow_discount_codes === '1') {
    $('#mobooking-bf-discount-section').show();
  } else {
    $('#mobooking-bf-discount-section').hide();
    sessionStorage.removeItem('mobooking_cart_discount_info'); // Clear any stored discount
  }

  // Initial display
  displayStep(1);

  // --- Apply Dynamic Styles from Settings ---
  const formWrapper = $('#mobooking-public-booking-form-wrapper');
  if (formWrapper.length) {
    if (formSettings.bf_theme_color) {
      formWrapper.css('--mobk-color-primary', formSettings.bf_theme_color);
      // Potentially derive a ring color if not set separately
      formWrapper.css('--mobk-color-ring', formSettings.bf_theme_color);
    }
    if (formSettings.bf_secondary_color) { // Assuming this is for secondary button BG or similar accents
      formWrapper.css('--mobk-color-secondary', formSettings.bf_secondary_color);
    }
    if (formSettings.bf_background_color) {
      formWrapper.css('--mobk-color-background', formSettings.bf_background_color);
      // If card background is same as page background, this might need adjustment or card specific var
      formWrapper.css('--mobk-color-card', formSettings.bf_background_color); // Example: card matches page bg
                                                                            // Or, keep card default white and let this be page bg only
    }
    if (formSettings.bf_font_family) {
      formWrapper.css('--mobk-font-family', formSettings.bf_font_family);
      // Also apply to body if the form is the main content and not embedded
      if (!$('body').hasClass('mobooking-form-embed-active')) {
        $('body').css('font-family', formSettings.bf_font_family);
      }
    }
    if (formSettings.bf_border_radius) {
      // Assuming bf_border_radius is a number, append 'px' or use as is if it's a full CSS value.
      // Shadcn uses rem, e.g., 0.5rem. If bf_border_radius stores "8", it could be "8px".
      // The CSS variables are defined with 'rem'. Let's assume bf_border_radius is a number for pixels.
      const radiusValue = parseFloat(formSettings.bf_border_radius);
      if (!isNaN(radiusValue)) {
        formWrapper.css('--mobk-border-radius', radiusValue + 'px');
        // Potentially derive sm and lg based on this, or expect them to be set if customizable
        formWrapper.css('--mobk-border-radius-sm', Math.max(0, radiusValue - 2) + 'px');
        formWrapper.css('--mobk-border-radius-lg', (radiusValue + 4) + 'px');
      }
    }
  }
  // --- End Apply Dynamic Styles ---


  // Helper function to check if pricing should be shown
  window.mobookingShouldShowPricing = function() {
    return formSettings.bf_show_pricing === '1';
  }
  window.mobookingShouldShowDiscounts = function() {
    return formSettings.bf_allow_discount_codes === '1';
  }

  // Update success message on confirmation step
  if (formSettings.bf_success_message) {
    // This needs to be applied when step 6 is shown.
    // Let's modify displayStep6_Confirmation success part.
  }

});
