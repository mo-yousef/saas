// Enhanced step configuration management
function getStepConfiguration() {
  return {
    1: {
      enabled: moBookingParams.form_config.enable_location_check === "1",
      id: "mobooking-step-1",
      title: "Location Check",
    },
    2: {
      enabled: moBookingParams.form_config.bf_allow_service_selection === "1",
      id: "mobooking-step-2",
      title: "Service Selection",
    },
    3: {
      enabled: true, // Always enabled
      id: "mobooking-step-3",
      title: "Service Options",
    },
    4: {
      enabled: moBookingParams.form_config.bf_enable_pet_information === "1",
      id: "mobooking-step-4",
      title: "Pet Information",
    },
    5: {
      enabled: moBookingParams.form_config.bf_enable_service_frequency === "1",
      id: "mobooking-step-5",
      title: "Service Frequency",
    },
    6: {
      enabled: moBookingParams.form_config.bf_enable_datetime_selection === "1",
      id: "mobooking-step-6",
      title: "Date & Time",
    },
    7: {
      enabled: moBookingParams.form_config.bf_enable_property_access === "1",
      id: "mobooking-step-7",
      title: "Contact & Access",
    },
    8: {
      enabled: true, // Always enabled
      id: "mobooking-step-8",
      title: "Confirmation",
    },
  };
}

function getActiveSteps() {
  const stepConfig = getStepConfiguration();
  return Object.keys(stepConfig)
    .filter((stepNum) => stepConfig[stepNum].enabled)
    .map((stepNum) => parseInt(stepNum));
}

function getNextActiveStep(currentStep) {
  const activeSteps = getActiveSteps();
  const currentIndex = activeSteps.indexOf(currentStep);
  return currentIndex < activeSteps.length - 1
    ? activeSteps[currentIndex + 1]
    : null;
}

function getPreviousActiveStep(currentStep) {
  const activeSteps = getActiveSteps();
  const currentIndex = activeSteps.indexOf(currentStep);
  return currentIndex > 0 ? activeSteps[currentIndex - 1] : null;
}

// Enhanced form data object
let enhancedFormData = {
  location: {},
  services: [],
  serviceOptions: {},
  petInfo: {
    hasPets: false,
    details: "",
  },
  serviceFrequency: "one-time",
  dateTime: {
    date: "",
    time: "",
  },
  customer: {
    name: "",
    email: "",
    phone: "",
    streetAddress: "",
    apartment: "",
    city: "",
    state: "",
    zipCode: "",
  },
  propertyAccess: {
    method: "home",
    details: "",
  },
  specialInstructions: "",
};
function showStep(stepNumber) {
  console.log("[MoBooking] Attempting to show step:", stepNumber);

  const stepConfig = getStepConfiguration();
  const activeSteps = getActiveSteps();

  // Check if step is enabled
  if (!stepConfig[stepNumber] || !stepConfig[stepNumber].enabled) {
    console.log(
      "[MoBooking] Step",
      stepNumber,
      "is disabled, finding next active step"
    );
    const nextStep = getNextActiveStep(currentStep);
    if (nextStep) {
      showStep(nextStep);
      return;
    }
  }

  // Hide all steps
  $(".mobooking-step").hide();

  // Show current step
  $("#mobooking-step-" + stepNumber).show();

  currentStep = stepNumber;
  updateProgressBar();

  // Initialize step-specific functionality
  initializeStepFunctionality(stepNumber);

  console.log("[MoBooking] Now showing step:", stepNumber);
}

function updateProgressBar() {
  const activeSteps = getActiveSteps();
  const totalSteps = activeSteps.length;
  const currentStepIndex = activeSteps.indexOf(currentStep) + 1;
  const progress = (currentStepIndex / totalSteps) * 100;

  $("#mobooking-progress-bar").css("width", progress + "%");
  $("#mobooking-progress-text").text(
    "Step " + currentStepIndex + " of " + totalSteps
  );
}

function initializeStepFunctionality(stepNumber) {
  switch (stepNumber) {
    case 1:
      // Location check already handled
      break;
    case 2:
      loadServices();
      break;
    case 3:
      loadServiceOptions();
      break;
    case 4:
      initializePetInformation();
      break;
    case 5:
      initializeServiceFrequency();
      break;
    case 6:
      initializeDateTimeSelection();
      break;
    case 7:
      initializeContactAndAccess();
      break;
    case 8:
      displayBookingConfirmation();
      break;
  }
}
// Step 4: Pet Information Handlers
function initializePetInformation() {
  console.log("[MoBooking] Initializing pet information step");

  // Handle pet radio button changes
  $('input[name="has_pets"]').on("change", function () {
    const hasPets = $(this).val() === "yes";
    if (hasPets) {
      $("#pet-details-section").slideDown();
      $("#pet-details").attr("required", true);
    } else {
      $("#pet-details-section").slideUp();
      $("#pet-details").removeAttr("required").val("");
    }
    enhancedFormData.petInfo.hasPets = hasPets;
  });

  // Handle pet details input
  $("#pet-details").on("input", function () {
    enhancedFormData.petInfo.details = $(this).val();
  });

  // Navigation buttons
  $("#mobooking-step-4-back").on("click", function () {
    const prevStep = getPreviousActiveStep(currentStep);
    if (prevStep) navigateToStep(prevStep);
  });

  $("#mobooking-step-4-continue").on("click", function () {
    if (validatePetInformation()) {
      const nextStep = getNextActiveStep(currentStep);
      if (nextStep) navigateToStep(nextStep);
    }
  });
}

function validatePetInformation() {
  const hasPets = $('input[name="has_pets"]:checked').val() === "yes";

  if (hasPets && !$("#pet-details").val().trim()) {
    showFeedback("error", "Please provide details about your pets.");
    return false;
  }

  return true;
}

// Step 5: Service Frequency Handlers
function initializeServiceFrequency() {
  console.log("[MoBooking] Initializing service frequency step");

  // Handle frequency selection
  $('input[name="service_frequency"]').on("change", function () {
    const frequency = $(this).val();
    enhancedFormData.serviceFrequency = frequency;
    updatePricingWithFrequency(frequency);
    updateFrequencyDisplay(frequency);
  });

  // Navigation buttons
  $("#mobooking-step-5-back").on("click", function () {
    const prevStep = getPreviousActiveStep(currentStep);
    if (prevStep) navigateToStep(prevStep);
  });

  $("#mobooking-step-5-continue").on("click", function () {
    if (validateServiceFrequency()) {
      const nextStep = getNextActiveStep(currentStep);
      if (nextStep) navigateToStep(nextStep);
    }
  });
}

function validateServiceFrequency() {
  const frequency = $('input[name="service_frequency"]:checked').val();
  if (!frequency) {
    showFeedback("error", "Please select a service frequency.");
    return false;
  }
  return true;
}

function updatePricingWithFrequency(frequency) {
  // Apply frequency-based discounts
  let discountMultiplier = 1;
  switch (frequency) {
    case "weekly":
      discountMultiplier = 0.9; // 10% discount
      break;
    case "monthly":
      discountMultiplier = 0.95; // 5% discount
      break;
    case "one-time":
    default:
      discountMultiplier = 1;
      break;
  }

  // Update pricing display
  updateLiveSummary();
}

function updateFrequencyDisplay(frequency) {
  // Add visual feedback for selected frequency
  $(".mobooking-frequency-card").removeClass("selected");
  $('input[name="service_frequency"][value="' + frequency + '"]')
    .closest(".mobooking-frequency-card")
    .addClass("selected");
}

// Step 6: Date & Time Selection Handlers
function initializeDateTimeSelection() {
  console.log("[MoBooking] Initializing date & time selection step");

  // Initialize date picker constraints
  const today = new Date();
  const maxDate = new Date();
  maxDate.setDate(today.getDate() + 90); // 90 days ahead

  $("#preferred-date").attr("min", today.toISOString().split("T")[0]);
  $("#preferred-date").attr("max", maxDate.toISOString().split("T")[0]);

  // Handle date selection
  $("#preferred-date").on("change", function () {
    const selectedDate = $(this).val();
    enhancedFormData.dateTime.date = selectedDate;

    if (selectedDate) {
      loadAvailableTimeSlots(selectedDate);
    }
  });

  // Handle time selection
  $("#preferred-time").on("change", function () {
    const selectedTime = $(this).val();
    enhancedFormData.dateTime.time = selectedTime;
    updateDateTimeSummary();
  });

  // Navigation buttons
  $("#mobooking-step-6-back").on("click", function () {
    const prevStep = getPreviousActiveStep(currentStep);
    if (prevStep) navigateToStep(prevStep);
  });

  $("#mobooking-step-6-continue").on("click", function () {
    if (validateDateTime()) {
      const nextStep = getNextActiveStep(currentStep);
      if (nextStep) navigateToStep(nextStep);
    }
  });
}

function validateDateTime() {
  const date = $("#preferred-date").val();
  const time = $("#preferred-time").val();

  if (!date) {
    showFeedback("error", "Please select a preferred date.");
    return false;
  }

  if (!time) {
    showFeedback("error", "Please select a preferred time.");
    return false;
  }

  // Check if selected datetime is in the future
  const selectedDateTime = new Date(date + "T" + time);
  const now = new Date();

  if (selectedDateTime <= now) {
    showFeedback("error", "Please select a future date and time.");
    return false;
  }

  return true;
}

function loadAvailableTimeSlots(date) {
  console.log("[MoBooking] Loading time slots for date:", date);

  // Show loading state
  $("#available-time-slots")
    .html('<div class="mobooking-loading">Loading available times...</div>')
    .show();

  $.ajax({
    url: moBookingParams.ajax_url,
    type: "POST",
    data: {
      action: "mobooking_get_available_times",
      nonce: moBookingParams.nonce,
      tenant_id: moBookingParams.tenant_user_id,
      date: date,
      service_frequency: enhancedFormData.serviceFrequency,
    },
    success: function (response) {
      if (response.success && response.data.time_slots) {
        displayTimeSlots(response.data.time_slots);
      } else {
        $("#available-time-slots").html(
          "<p>No available times for this date.</p>"
        );
      }
    },
    error: function () {
      $("#available-time-slots").html("<p>Error loading available times.</p>");
    },
  });
}

function displayTimeSlots(timeSlots) {
  let slotsHTML = '<div class="mobooking-time-slots-grid">';

  timeSlots.forEach(function (slot) {
    const timeDisplay = formatTime(slot.time);
    const isAvailable = slot.available;
    const slotClass = isAvailable ? "available" : "unavailable";

    slotsHTML += `
            <button type="button" 
                    class="mobooking-time-slot ${slotClass}" 
                    data-time="${slot.time}"
                    ${!isAvailable ? "disabled" : ""}>
                ${timeDisplay}
            </button>
        `;
  });

  slotsHTML += "</div>";
  $("#available-time-slots").html(slotsHTML);

  // Handle time slot selection
  $(".mobooking-time-slot.available").on("click", function () {
    const selectedTime = $(this).data("time");
    $("#preferred-time").val(selectedTime);
    $(".mobooking-time-slot").removeClass("selected");
    $(this).addClass("selected");
    enhancedFormData.dateTime.time = selectedTime;
    updateDateTimeSummary();
  });
}

function updateDateTimeSummary() {
  const date = enhancedFormData.dateTime.date;
  const time = enhancedFormData.dateTime.time;

  if (date && time) {
    const formattedDate = formatDate(date);
    const formattedTime = formatTime(time);

    $("#mobooking-datetime-summary").html(
      `<div class="mobooking-summary-item">
                <strong>Selected Date & Time:</strong><br>
                ${formattedDate} at ${formattedTime}
            </div>`
    );
  }
}

// Step 7: Contact & Property Access Handlers
function initializeContactAndAccess() {
  console.log("[MoBooking] Initializing contact and access step");

  // Pre-fill location data from Step 1 if available
  if (enhancedFormData.location.city) {
    $("#service-city").val(enhancedFormData.location.city);
  }
  if (enhancedFormData.location.state) {
    $("#service-state").val(enhancedFormData.location.state);
  }
  if (enhancedFormData.location.zipCode) {
    $("#service-zipcode").val(enhancedFormData.location.zipCode);
  }

  // Handle contact form inputs
  $("#customer-name").on("input", function () {
    enhancedFormData.customer.name = $(this).val();
    updateContactSummary();
  });

  $("#customer-email").on("input", function () {
    enhancedFormData.customer.email = $(this).val();
  });

  $("#customer-phone").on("input", function () {
    enhancedFormData.customer.phone = $(this).val();
    formatPhoneNumber($(this));
  });

  $("#street-address").on("input", function () {
    enhancedFormData.customer.streetAddress = $(this).val();
    updateContactSummary();
  });

  $("#apartment").on("input", function () {
    enhancedFormData.customer.apartment = $(this).val();
  });

  // Handle property access selection
  $('input[name="property_access"]').on("change", function () {
    const accessMethod = $(this).val();
    enhancedFormData.propertyAccess.method = accessMethod;

    if (accessMethod === "other") {
      $("#custom-access-details").slideDown();
      $("#access-details").attr("required", true);
    } else {
      $("#custom-access-details").slideUp();
      $("#access-details").removeAttr("required").val("");
      enhancedFormData.propertyAccess.details = "";
    }
  });

  $("#access-details").on("input", function () {
    enhancedFormData.propertyAccess.details = $(this).val();
  });

  $("#special-instructions").on("input", function () {
    enhancedFormData.specialInstructions = $(this).val();
  });

  // Navigation buttons
  $("#mobooking-step-7-back").on("click", function () {
    const prevStep = getPreviousActiveStep(currentStep);
    if (prevStep) navigateToStep(prevStep);
  });

  $("#mobooking-step-7-continue").on("click", function () {
    if (validateContactAndAccess()) {
      submitBooking();
    }
  });
}

function validateContactAndAccess() {
  const requiredFields = [
    { field: "#customer-name", message: "Please enter your full name." },
    { field: "#customer-email", message: "Please enter your email address." },
    { field: "#customer-phone", message: "Please enter your phone number." },
    { field: "#street-address", message: "Please enter your street address." },
  ];

  for (let item of requiredFields) {
    if (!$(item.field).val().trim()) {
      showFeedback("error", item.message);
      $(item.field).focus();
      return false;
    }
  }

  // Validate email format
  const email = $("#customer-email").val();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    showFeedback("error", "Please enter a valid email address.");
    $("#customer-email").focus();
    return false;
  }

  // Validate property access details if "other" is selected
  const accessMethod = $('input[name="property_access"]:checked').val();
  if (accessMethod === "other" && !$("#access-details").val().trim()) {
    showFeedback("error", "Please provide details for property access.");
    $("#access-details").focus();
    return false;
  }

  return true;
}

function updateContactSummary() {
  const name = enhancedFormData.customer.name;
  const address = enhancedFormData.customer.streetAddress;

  if (name || address) {
    $("#mobooking-contact-summary").html(
      `<div class="mobooking-summary-item">
                ${name ? `<strong>Customer:</strong> ${name}<br>` : ""}
                ${address ? `<strong>Address:</strong> ${address}` : ""}
            </div>`
    );
  }
}

// Step 8: Booking Confirmation
function displayBookingConfirmation() {
  console.log("[MoBooking] Displaying booking confirmation");

  // This function will be called after successful booking submission
  // The booking details will be populated from the server response
}
function navigateToStep(stepNumber) {
  console.log("[MoBooking] Navigating to step:", stepNumber);

  const stepConfig = getStepConfiguration();

  // Check if target step is enabled
  if (!stepConfig[stepNumber] || !stepConfig[stepNumber].enabled) {
    console.log("[MoBooking] Step", stepNumber, "is disabled");

    // If going forward, find next enabled step
    if (stepNumber > currentStep) {
      const nextStep = getNextActiveStep(currentStep);
      if (nextStep) {
        navigateToStep(nextStep);
        return;
      }
    }
    // If going backward, find previous enabled step
    else {
      const prevStep = getPreviousActiveStep(currentStep);
      if (prevStep) {
        navigateToStep(prevStep);
        return;
      }
    }
  }

  // Validate current step before proceeding forward
  if (stepNumber > currentStep && !validateCurrentStep()) {
    return;
  }

  // Special handling for step transitions
  if (stepNumber === 2 && currentStep === 1) {
    // Store location data when moving from step 1 to 2
    storeLocationData();
  }

  showStep(stepNumber);
}

function storeLocationData() {
  // Store location information from step 1 for use in step 7
  const locationInput = $("#mobooking-location-input").val();
  if (locationInput) {
    // This would be enhanced to parse the location and extract city, state, zip
    // For now, we'll store the raw input
    enhancedFormData.location.input = locationInput;

    // In a real implementation, you'd call an API to parse the location
    // For this example, we'll assume basic parsing
    parseLocationData(locationInput);
  }
}

function parseLocationData(locationInput) {
  // Basic parsing - in reality this would be more sophisticated
  // or use the same API call that validates the location

  // Check if it looks like a ZIP code (5 digits)
  const zipMatch = locationInput.match(/\b\d{5}\b/);
  if (zipMatch) {
    enhancedFormData.location.zipCode = zipMatch[0];
  }

  // You would expand this with proper location parsing
  // or store the parsed data from the location validation API call
}

function validateCurrentStep() {
  switch (currentStep) {
    case 1:
      if (moBookingParams.form_config.enable_location_check === "1") {
        return $("#mobooking-location-input").val().trim() !== "";
      }
      return true;
    case 2:
      return selectedServices.length > 0;
    case 3:
      return validateServiceOptions();
    case 4:
      return validatePetInformation();
    case 5:
      return validateServiceFrequency();
    case 6:
      return validateDateTime();
    case 7:
      return validateContactAndAccess();
    default:
      return true;
  }
}
function submitBooking() {
  console.log("[MoBooking] Submitting booking with enhanced data");

  // Show loading state
  const $submitBtn = $("#mobooking-step-7-continue");
  const originalBtnText = $submitBtn.html();
  $submitBtn
    .prop("disabled", true)
    .html('<div class="mobooking-spinner"></div> Submitting...');

  // Prepare comprehensive booking data
  const bookingData = {
    action: "mobooking_submit_booking",
    nonce: moBookingParams.nonce,
    tenant_id: moBookingParams.tenant_user_id,

    // Enhanced customer details
    customer_details: JSON.stringify({
      name: enhancedFormData.customer.name,
      email: enhancedFormData.customer.email,
      phone: enhancedFormData.customer.phone,
      address: {
        street: enhancedFormData.customer.streetAddress,
        apartment: enhancedFormData.customer.apartment,
        city: enhancedFormData.customer.city || enhancedFormData.location.city,
        state:
          enhancedFormData.customer.state || enhancedFormData.location.state,
        zip_code:
          enhancedFormData.customer.zipCode ||
          enhancedFormData.location.zipCode,
      },
      date: enhancedFormData.dateTime.date,
      time: enhancedFormData.dateTime.time,
      instructions: enhancedFormData.specialInstructions,
    }),

    // Selected services (existing)
    selected_services: JSON.stringify(selectedServices),

    // Service options (existing)
    service_options: JSON.stringify(serviceOptions),

    // New enhanced data
    pet_information: JSON.stringify({
      has_pets: enhancedFormData.petInfo.hasPets,
      details: enhancedFormData.petInfo.details,
    }),

    service_frequency: enhancedFormData.serviceFrequency,

    property_access: JSON.stringify({
      method: enhancedFormData.propertyAccess.method,
      details: enhancedFormData.propertyAccess.details,
    }),

    // Location data
    location_data: JSON.stringify(enhancedFormData.location),

    // Pricing information
    pricing_data: JSON.stringify({
      subtotal: calculateSubtotal(),
      frequency_discount: calculateFrequencyDiscount(),
      discount_code: appliedDiscount,
      final_total: calculateFinalTotal(),
    }),
  };

  console.log("[MoBooking] Booking data prepared:", bookingData);

  $.ajax({
    url: moBookingParams.ajax_url,
    type: "POST",
    data: bookingData,
    success: function (response) {
      console.log("[MoBooking] Booking submission response:", response);

      if (response.success) {
        // Store booking confirmation data
        const bookingConfirmation = response.data;

        // Move to confirmation step
        showBookingConfirmation(bookingConfirmation);
        showStep(8);
      } else {
        showFeedback(
          "error",
          response.data.message ||
            "Booking submission failed. Please try again."
        );
        $submitBtn.prop("disabled", false).html(originalBtnText);
      }
    },
    error: function (xhr, status, error) {
      console.error("[MoBooking] Booking submission error:", error);
      showFeedback(
        "error",
        "An error occurred while submitting your booking. Please try again."
      );
      $submitBtn.prop("disabled", false).html(originalBtnText);
    },
  });
}

function showBookingConfirmation(confirmationData) {
  let detailsHTML = '<div class="mobooking-confirmation-details">';

  if (confirmationData.booking_reference) {
    detailsHTML += `<div class="mobooking-confirmation-item">
                <strong>Booking Reference:</strong> ${confirmationData.booking_reference}
            </div>`;
  }

  detailsHTML += `<div class="mobooking-confirmation-item">
            <strong>Service Date:</strong> ${formatDate(
              enhancedFormData.dateTime.date
            )} at ${formatTime(enhancedFormData.dateTime.time)}
        </div>
        <div class="mobooking-confirmation-item">
            <strong>Service Address:</strong> ${
              enhancedFormData.customer.streetAddress
            }
            ${
              enhancedFormData.customer.apartment
                ? ", " + enhancedFormData.customer.apartment
                : ""
            }
        </div>
        <div class="mobooking-confirmation-item">
            <strong>Service Frequency:</strong> ${
              enhancedFormData.serviceFrequency
            }
        </div>`;

  if (enhancedFormData.petInfo.hasPets) {
    detailsHTML += `<div class="mobooking-confirmation-item">
                <strong>Pet Information:</strong> ${enhancedFormData.petInfo.details}
            </div>`;
  }

  detailsHTML += `<div class="mobooking-confirmation-item">
            <strong>Property Access:</strong> ${getPropertyAccessLabel(
              enhancedFormData.propertyAccess.method
            )}
            ${
              enhancedFormData.propertyAccess.details
                ? "<br><em>" + enhancedFormData.propertyAccess.details + "</em>"
                : ""
            }
        </div>`;

  if (confirmationData.total_amount) {
    detailsHTML += `<div class="mobooking-confirmation-item">
                <strong>Total Amount:</strong> ${formatCurrency(
                  confirmationData.total_amount
                )}
            </div>`;
  }

  detailsHTML += "</div>";

  $("#mobooking-final-booking-details").html(detailsHTML);
}

// Helper functions
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    weekday: "long",
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

function formatTime(timeString) {
  const [hours, minutes] = timeString.split(":");
  const date = new Date();
  date.setHours(parseInt(hours), parseInt(minutes));
  return date.toLocaleTimeString("en-US", {
    hour: "numeric",
    minute: "2-digit",
    hour12: true,
  });
}

function formatCurrency(amount) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: moBookingParams.currency.code || "USD",
  }).format(amount);
}

function getPropertyAccessLabel(method) {
  const labels = {
    home: "I'll be home",
    key_mat: "Key under mat",
    lockbox: "Lockbox",
    concierge: "Building concierge",
    other: "Other",
  };
  return labels[method] || method;
}

function calculateFrequencyDiscount() {
  switch (enhancedFormData.serviceFrequency) {
    case "weekly":
      return 0.1; // 10%
    case "monthly":
      return 0.05; // 5%
    default:
      return 0;
  }
}

function calculateFinalTotal() {
  const subtotal = calculateSubtotal();
  const frequencyDiscount = calculateFrequencyDiscount();
  const discountAmount = subtotal * frequencyDiscount;
  return subtotal - discountAmount;
}
