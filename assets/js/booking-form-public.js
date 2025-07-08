jQuery(document).ready(function($) {
    'use strict';

    // Global parameters localized from PHP
    const MOB_PARAMS = window.mobooking_booking_form_params || {};
    const PRELOADED_SERVICES = window.MOB_PRELOADED_SERVICES || [];

    // Current Booking State
    let currentStep = 1;
    let selectedService = null;
    let selectedOptions = {}; // Store as { optionId: { name, value, price, priceType }, ... }
    let customerDetails = {};
    let discountInfo = null;
    let totalPrice = 0;
    let locationVerified = !(MOB_PARAMS.settings && MOB_PARAMS.settings.bf_enable_location_check === '1');

    // --- UTILITY FUNCTIONS ---
    function escapeHtml(text) {
        if (typeof text !== 'string') return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatPrice(price) {
        return parseFloat(price || 0).toFixed(2);
    }

    function showFeedback(elementSelector, type, message, autoHide = true) {
        const $element = $(elementSelector);
        $element.removeClass('success error info').hide();
        if (message) {
            $element.addClass(type).html(message).show(); // Use html to allow spinner
        }
        if (autoHide && (type === 'success' || type === 'error')) {
            setTimeout(() => {
                if ($element.html() === message) {
                    $element.fadeOut();
                }
            }, 3000);
        }
    }

    // --- STEP MANAGEMENT ---
    function showStep(stepNumber) {
        $('.mobooking-step').removeClass('active').hide();
        const $targetStep = $(`.mobooking-step[data-step="${stepNumber}"]`);
        $targetStep.show().addClass('active'); // Show first, then add class for transition

        currentStep = stepNumber;
        updateProgressBar(currentStep);
        loadStepData(currentStep);
        // Scroll to top of form
        $('html, body').animate({ scrollTop: $('.mobooking-form-wrapper').offset().top - 50 }, 300);
    }

    function updateProgressBar(step) {
        const totalDisplaySteps = 5; // Location, Services, Options, Details, Review
        const progressPercentage = ((step - 1) / (totalDisplaySteps -1)) * 100;
        $('.mobooking-progress-line-fill').css('width', Math.min(100, Math.max(0, progressPercentage)) + '%');

        $('.mobooking-progress-step').each(function() {
            const stepNum = parseInt($(this).data('step'));
            $(this).removeClass('active completed');
            $(this).find('.step-number').text(stepNum); // Reset text first

            if (stepNum < step) {
                $(this).addClass('completed');
                $(this).find('.step-number').text(''); // Clear number for checkmark
            } else if (stepNum === step) {
                $(this).addClass('active');
            }
        });
    }

    function loadStepData(stepNumber) {
        switch (stepNumber) {
            case 2: // Services
                displayServices();
                break;
            case 3: // Options
                displayServiceOptions();
                break;
            case 5: // Review
                populateReviewData();
                break;
        }
        updateLiveSummary(); // Always update summary when step changes
    }

    // --- INITIALIZATION ---
    function initializeForm() {
        console.log('MoBooking jQuery Form Initializing. Params:', MOB_PARAMS);
        console.log('Preloaded Services:', PRELOADED_SERVICES);

        if (MOB_PARAMS.settings && MOB_PARAMS.settings.bf_enable_location_check === '1') {
            showStep(1);
        } else {
            locationVerified = true; // Skip location check
            showStep(2);
        }
        bindEvents();
    }

    // --- EVENT BINDING ---
    function bindEvents() {
        // Step Navigation
        $(document).on('click', '[data-step-next]', handleNextStep);
        $(document).on('click', '[data-step-back]', handlePrevStep);

        // Location Check
        $('#mobooking-location-form').on('submit', handleLocationCheckSubmit);

        // Service Selection
        $('#mobooking-services-container').on('click', '.mobooking-service-card', handleServiceSelect);

        // Option Selection (dynamically bound when options are rendered)
        // See displayServiceOptions function

        // Customer Details Input
        $('#mobooking-details-form input, #mobooking-details-form textarea').on('change input', storeCustomerDetails);

        // Discount
        if (MOB_PARAMS.settings && MOB_PARAMS.settings.bf_allow_discount_codes === '1') {
            $('#apply-discount-btn').on('click', handleDiscountApply);
        }

        // Final Submit
        $('#final-submit-btn').on('click', handleFinalBookingSubmit);
    }

    // --- STEP 1: LOCATION CHECK ---
    function handleLocationCheckSubmit(e) {
        e.preventDefault();
        const zipCode = $('#mobooking-zip').val().trim();
        const countryCode = $('#mobooking-country').val();
        const $feedback = $('#mobooking-location-feedback');
        const $submitBtn = $(this).find('button[type="submit"]');
        const originalBtnHtml = $submitBtn.html();

        if (!zipCode) {
            showFeedback($feedback, 'error', MOB_PARAMS.i18n.zipRequired); return;
        }
        if (!countryCode) {
            showFeedback($feedback, 'error', MOB_PARAMS.i18n.countryRequired); return;
        }
        showFeedback($feedback, '', '', true); // Clear

        $submitBtn.prop('disabled', true).html('<div class="mobooking-spinner"></div> ' + MOB_PARAMS.i18n.checking);
        showFeedback($feedback, 'info', MOB_PARAMS.i18n.checking, false);

        $.ajax({
            url: MOB_PARAMS.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_check_service_area',
                nonce: MOB_PARAMS.nonce,
                zip_code: zipCode,
                country_code: countryCode,
                tenant_id: MOB_PARAMS.tenantId
            },
            success: function(response) {
                if (response.success && response.data.serviced) {
                    showFeedback($feedback, 'success', response.data.message);
                    locationVerified = true;
                    setTimeout(() => showStep(2), 1500);
                } else {
                    showFeedback($feedback, 'error', response.data.message || 'Service not available in this area.');
                }
            },
            error: function() {
                showFeedback($feedback, 'error', MOB_PARAMS.i18n.connectionError);
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html(originalBtnHtml);
            }
        });
    }

    // --- STEP 2: SERVICE SELECTION ---
    function displayServices() {
        const $container = $('#mobooking-services-container');
        const $feedback = $('#mobooking-services-feedback');
        $container.empty(); // Clear previous services

        if (!PRELOADED_SERVICES || PRELOADED_SERVICES.length === 0) {
            $container.html(`<p>${MOB_PARAMS.i18n.noServicesAvailable}</p>`);
            return;
        }

        let html = '';
        PRELOADED_SERVICES.forEach(service => {
            const priceDisplay = (MOB_PARAMS.settings && MOB_PARAMS.settings.bf_show_pricing === '1' && service.price)
                ? `<div class="mobooking-service-price">${MOB_PARAMS.currency.symbol}${formatPrice(service.price)}</div>`
                : '';
            html += `
                <div class="mobooking-service-card" data-service-id="${service.service_id}">
                    <div class="mobooking-service-header">
                        <div class="mobooking-service-icon"><i class="${escapeHtml(service.icon || 'fas fa-broom')}"></i></div>
                        <div class="mobooking-service-info">
                            <div class="mobooking-service-name">${escapeHtml(service.name)}</div>
                            ${priceDisplay}
                        </div>
                        <input type="radio" name="selected_service_radio" value="${service.service_id}" class="sr-only">
                    </div>
                    ${service.description ? `<div class="mobooking-service-description">${escapeHtml(service.description)}</div>` : ''}
                    <div class="mobooking-service-meta">
                        ${service.duration ? `<div><i class="fas fa-clock"></i> ${escapeHtml(service.duration.toString())} min</div>` : ''}
                        ${service.category ? `<div><i class="fas fa-tag"></i> ${escapeHtml(service.category)}</div>` : ''}
                    </div>
                </div>`;
        });
        $container.html(html);
        // Pre-select if already chosen
        if (selectedService) {
            $(`.mobooking-service-card[data-service-id="${selectedService.service_id}"]`).addClass('selected').find('input[type="radio"]').prop('checked', true);
            $('[data-step-next="3"]').prop('disabled', false);
        } else {
            $('[data-step-next="3"]').prop('disabled', true);
        }
    }

    function handleServiceSelect() {
        const $card = $(this);
        const serviceId = $card.data('service-id');

        $('.mobooking-service-card').removeClass('selected');
        $card.addClass('selected').find('input[type="radio"]').prop('checked', true);

        selectedService = PRELOADED_SERVICES.find(s => s.service_id === serviceId);
        selectedOptions = {}; // Reset options when service changes
        discountInfo = null; // Reset discount

        $('[data-step-next="3"]').prop('disabled', false);
        showFeedback($('#mobooking-services-feedback'), '', '', true); // Clear feedback
        updateLiveSummary();
    }

    // --- STEP 3: SERVICE OPTIONS ---
    function displayServiceOptions() {
        const $container = $('#mobooking-service-options');
        $container.empty();
        if (!selectedService || !selectedService.options || selectedService.options.length === 0) {
            $container.html('<p>No additional options for this service.</p>');
            updateLiveSummary();
            return;
        }

        let html = '';
        selectedService.options.forEach(option => {
            const isChecked = selectedOptions[option.option_id] && selectedOptions[option.option_id].value === '1';
            const currentValue = selectedOptions[option.option_id] ? selectedOptions[option.option_id].value : '';
            const requiredAttr = option.is_required ? 'required' : '';
            const requiredIndicator = option.is_required ? '<span class="mobooking-required">*</span>' : '';
            let priceDisplay = '';
            if (option.price_impact && parseFloat(option.price_impact) != 0) {
                 priceDisplay = option.price_impact_type === 'percentage'
                    ? `(+${option.price_impact}%)`
                    : `(+${MOB_PARAMS.currency.symbol}${formatPrice(option.price_impact)})`;
                 priceDisplay = `<span class="option-price">${priceDisplay}</span>`;
            }

            html += `<div class="mobooking-form-group" data-option-id="${option.option_id}">`;
            switch (option.type) {
                case 'checkbox':
                    html += `
                        <label>
                            <input type="checkbox" name="option_${option.option_id}" value="1"
                                   data-price="${option.price_impact || 0}" data-price-type="${option.price_impact_type || 'fixed'}"
                                   ${requiredAttr} ${isChecked ? 'checked' : ''}>
                            ${escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                        </label>`;
                    break;
                case 'text':
                    html += `
                        <label for="option_${option.option_id}" class="mobooking-label">
                            ${escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                        </label>
                        <input type="text" id="option_${option.option_id}" name="option_${option.option_id}" value="${escapeHtml(currentValue)}"
                               class="mobooking-input" data-price="${option.price_impact || 0}" data-price-type="${option.price_impact_type || 'fixed'}" ${requiredAttr}>`;
                    break;
                case 'textarea':
                    html += `
                        <label for="option_${option.option_id}" class="mobooking-label">
                            ${escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                        </label>
                        <textarea id="option_${option.option_id}" name="option_${option.option_id}"
                                  class="mobooking-textarea" data-price="${option.price_impact || 0}" data-price-type="${option.price_impact_type || 'fixed'}" ${requiredAttr}>${escapeHtml(currentValue)}</textarea>`;
                    break;
                // Add other option types (select, radio, quantity) here if needed, parsing option.option_values
            }
            if (option.description) {
                html += `<div class="option-description">${escapeHtml(option.description)}</div>`;
            }
            html += `</div>`;
        });
        $container.html(html);
        $container.find('input, textarea, select').on('change input', handleOptionChange);
        updateLiveSummary();
    }

    function handleOptionChange() {
        const $input = $(this);
        const optionId = $input.closest('.mobooking-form-group').data('option-id');
        const serviceOption = selectedService.options.find(opt => opt.option_id === optionId);
        if (!serviceOption) return;

        let value;
        if ($input.is(':checkbox')) {
            value = $input.is(':checked') ? '1' : '0';
        } else {
            value = $input.val().trim();
        }

        if (value && value !== '0') {
            selectedOptions[optionId] = {
                name: serviceOption.name,
                value: value,
                price: parseFloat(serviceOption.price_impact || 0),
                priceType: serviceOption.price_impact_type || 'fixed'
            };
        } else {
            delete selectedOptions[optionId];
        }
        updateLiveSummary();
    }

    // --- STEP 4: CUSTOMER DETAILS ---
    function storeCustomerDetails() {
        customerDetails = {
            name: $('#customer-name').val().trim(),
            email: $('#customer-email').val().trim(),
            phone: $('#customer-phone').val().trim(),
            address: $('#service-address').val().trim(),
            date: $('#preferred-date').val(),
            time: $('#preferred-time').val(),
            instructions: $('#special-instructions').val().trim()
        };
    }

    function validateCustomerDetails() {
        storeCustomerDetails(); // Ensure current details are in state
        const $feedback = $('#mobooking-details-feedback');
        if (!customerDetails.name || !customerDetails.email || !customerDetails.phone || !customerDetails.address) {
            showFeedback($feedback, 'error', MOB_PARAMS.i18n.fillRequiredFields); return false;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(customerDetails.email)) {
            showFeedback($feedback, 'error', MOB_PARAMS.i18n.invalidEmail); return false;
        }
        showFeedback($feedback, '', '', true); // Clear
        return true;
    }

    // --- STEP 5: REVIEW & CONFIRM ---
    function populateReviewData() {
        if (!selectedService) return;
        let reviewHtml = `<div class="mobooking-review-section"><h4>Service Details</h4>`;
        reviewHtml += `<div class="review-item"><strong>Service:</strong> <span>${escapeHtml(selectedService.name)}</span></div>`;
        if (MOB_PARAMS.settings.bf_show_pricing === '1') {
            reviewHtml += `<div class="review-item"><strong>Base Price:</strong> <span>${MOB_PARAMS.currency.symbol}${formatPrice(selectedService.price)}</span></div>`;
        }
        if (Object.keys(selectedOptions).length > 0) {
            reviewHtml += '<h5>Options:</h5><ul>';
            $.each(selectedOptions, function(id, opt) {
                reviewHtml += `<li>${escapeHtml(opt.name)}: ${escapeHtml(opt.value)}`;
                if (opt.price > 0 && MOB_PARAMS.settings.bf_show_pricing === '1') {
                     let optPriceDisplay = opt.priceType === 'percentage' ? `(+${opt.price}%)` : `(+${MOB_PARAMS.currency.symbol}${formatPrice(opt.price)})`;
                     reviewHtml += ` <span class="option-price">${optPriceDisplay}</span>`;
                }
                reviewHtml += `</li>`;
            });
            reviewHtml += '</ul>';
        }
        reviewHtml += `</div>`;

        reviewHtml += `<div class="mobooking-review-section"><h4>Customer Information</h4>`;
        reviewHtml += `<div class="review-item"><strong>Name:</strong> <span>${escapeHtml(customerDetails.name)}</span></div>`;
        reviewHtml += `<div class="review-item"><strong>Email:</strong> <span>${escapeHtml(customerDetails.email)}</span></div>`;
        reviewHtml += `<div class="review-item"><strong>Phone:</strong> <span>${escapeHtml(customerDetails.phone)}</span></div>`;
        reviewHtml += `<div class="review-item"><strong>Address:</strong> <span>${escapeHtml(customerDetails.address)}</span></div>`;
        if(customerDetails.date) reviewHtml += `<div class="review-item"><strong>Preferred Date:</strong> <span>${escapeHtml(customerDetails.date)}</span></div>`;
        if(customerDetails.time) reviewHtml += `<div class="review-item"><strong>Preferred Time:</strong> <span>${escapeHtml(customerDetails.time)}</span></div>`;
        if(customerDetails.instructions) reviewHtml += `<div class="review-item"><strong>Instructions:</strong> <span>${escapeHtml(customerDetails.instructions)}</span></div>`;
        reviewHtml += `</div>`;
        $('#mobooking-review-details').html(reviewHtml);
        updateLiveSummary(); // Ensure pricing summary is also updated
    }

    function handleDiscountApply() {
        const code = $('#discount-code').val().trim();
        const $feedback = $('#discount-feedback');
        const $button = $(this);
        const originalBtnText = $button.text();

        if (!code) {
            showFeedback($feedback, 'error', MOB_PARAMS.i18n.enterDiscountCode); return;
        }
        $button.prop('disabled', true).text('Applying...');
        showFeedback($feedback, 'info', 'Applying discount...', false);

        $.ajax({
            url: MOB_PARAMS.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_validate_discount',
                nonce: MOB_PARAMS.nonce,
                discount_code: code,
                tenant_id: MOB_PARAMS.tenantId,
                subtotal: calculateSubtotal() // Pass current subtotal before discount
            },
            success: function(response) {
                if (response.success && response.data) {
                    discountInfo = response.data;
                    showFeedback($feedback, 'success', MOB_PARAMS.i18n.discountApplied);
                } else {
                    discountInfo = null;
                    showFeedback($feedback, 'error', response.data?.message || MOB_PARAMS.i18n.invalidDiscount);
                }
                updateLiveSummary();
            },
            error: function() {
                discountInfo = null;
                showFeedback($feedback, 'error', MOB_PARAMS.i18n.connectionError);
                updateLiveSummary();
            },
            complete: function() {
                $button.prop('disabled', false).text(originalBtnText);
            }
        });
    }

    // --- PRICE CALCULATION & SUMMARY UDPATE ---
    function calculateSubtotal() {
        if (!selectedService) return 0;
        let subtotal = parseFloat(selectedService.price || 0);
        $.each(selectedOptions, function(id, opt) {
            if (opt.priceType === 'percentage') {
                subtotal += (parseFloat(selectedService.price || 0) * opt.price) / 100;
            } else {
                subtotal += opt.price;
            }
        });
        return subtotal;
    }

    function updateLiveSummary() {
        if (!selectedService && currentStep > 1) { // Don't clear if on step 1 or before service selected
             // If no service selected yet, but user is beyond service selection, show placeholder
            if (currentStep >= 3) { // Options, Details, Review steps
                 $('#mobooking-summary-content, #mobooking-summary-content-step4, #mobooking-final-summary').html('<p>Please select a service first.</p>');
                 $('#pricing-subtotal').text(MOB_PARAMS.currency.symbol + '0.00');
                 $('#pricing-discount').text('-' + MOB_PARAMS.currency.symbol + '0.00');
                 $('#pricing-total').text(MOB_PARAMS.currency.symbol + '0.00');
                 $('.discount-applied').addClass('hidden');
            }
            return;
        }
        if (!selectedService) { // truly no service selected, e.g. on step 2 initial load
            $('#mobooking-summary-content, #mobooking-summary-content-step4, #mobooking-final-summary').html('<p>Select a service to see summary.</p>');
            return;
        }

        const subtotal = calculateSubtotal();
        let finalTotal = subtotal;
        let summaryHtml = '';

        summaryHtml += `<div class="mobooking-summary-item"><span>${escapeHtml(selectedService.name)}</span><span>${MOB_PARAMS.currency.symbol}${formatPrice(selectedService.price)}</span></div>`;
        $.each(selectedOptions, function(id, opt) {
            let optPrice = 0;
            if (opt.priceType === 'percentage') {
                optPrice = (parseFloat(selectedService.price || 0) * opt.price) / 100;
            } else {
                optPrice = opt.price;
            }
            summaryHtml += `<div class="mobooking-summary-item"><span>+ ${escapeHtml(opt.name)}</span><span>${MOB_PARAMS.currency.symbol}${formatPrice(optPrice)}</span></div>`;
        });

        $('#pricing-subtotal').text(MOB_PARAMS.currency.symbol + formatPrice(subtotal));

        if (discountInfo) {
            let discountAmount = 0;
            if (discountInfo.type === 'percentage' || discountInfo.discount_type === 'percentage') {
                discountAmount = (subtotal * parseFloat(discountInfo.value || discountInfo.discount_value || 0)) / 100;
            } else {
                discountAmount = parseFloat(discountInfo.value || discountInfo.discount_value || 0);
            }
            discountAmount = Math.min(discountAmount, subtotal); // Cannot be more than subtotal
            finalTotal -= discountAmount;
            summaryHtml += `<div class="mobooking-summary-item"><span>Discount (${escapeHtml(discountInfo.code)})</span><span>-${MOB_PARAMS.currency.symbol}${formatPrice(discountAmount)}</span></div>`;
            $('#pricing-discount').text('-' + MOB_PARAMS.currency.symbol + formatPrice(discountAmount));
            $('.discount-applied').removeClass('hidden');
        } else {
            $('#pricing-discount').text('-' + MOB_PARAMS.currency.symbol + '0.00');
            $('.discount-applied').addClass('hidden');
        }

        finalTotal = Math.max(0, finalTotal); // Ensure total is not negative
        totalPrice = finalTotal; // Store for submission

        summaryHtml += `<div class="mobooking-summary-total"><span>Total:</span><span>${MOB_PARAMS.currency.symbol}${formatPrice(finalTotal)}</span></div>`;

        $('#mobooking-summary-content, #mobooking-summary-content-step4, #mobooking-final-summary').html(summaryHtml);
        $('#pricing-total').text(MOB_PARAMS.currency.symbol + formatPrice(finalTotal));
    }

    // --- NAVIGATION HANDLERS ---
    function handleNextStep() {
        const $button = $(this);
        const targetStep = parseInt($button.data('step-next'));
        const $feedback = $(`#mobooking-${getStepName(currentStep)}-feedback`);

        // Validate current step before proceeding
        if (currentStep === 1 && MOB_PARAMS.settings.bf_enable_location_check === '1' && !locationVerified) {
            showFeedback($feedback, 'error', "Please verify location first."); return;
        }
        if (currentStep === 2 && !selectedService) {
            showFeedback($feedback, 'error', MOB_PARAMS.i18n.selectServiceRequired); return;
        }
        if (currentStep === 3) { // Validate required options
            let allRequiredFilled = true;
            $('#mobooking-service-options .mobooking-form-group').each(function() {
                const $input = $(this).find('input[required], textarea[required], select[required]');
                if ($input.length) {
                    if (($input.is(':checkbox') && !$input.is(':checked')) || (!$input.is(':checkbox') && !$input.val().trim())) {
                        allRequiredFilled = false;
                        return false; // break .each loop
                    }
                }
            });
            if (!allRequiredFilled) {
                showFeedback($feedback, 'error', MOB_PARAMS.i18n.fillRequiredFields); return;
            }
        }
        if (currentStep === 4 && !validateCustomerDetails()) {
            return; // validateCustomerDetails shows its own feedback
        }
        showFeedback($feedback, '', '', true); // Clear current step feedback if validation passes
        showStep(targetStep);
    }

    function handlePrevStep() {
        const targetStep = parseInt($(this).data('step-back'));
        showStep(targetStep);
    }

    // --- FINAL SUBMISSION ---
    function handleFinalBookingSubmit() {
        const $button = $(this);
        const originalBtnHtml = $button.html();
        const $feedback = $('#mobooking-review-feedback');

        $button.prop('disabled', true).html('<div class="mobooking-spinner"></div> ' + MOB_PARAMS.i18n.submitting);
        showFeedback($feedback, 'info', MOB_PARAMS.i18n.submitting, false);

        // Consolidate data for submission
        const submissionData = {
            action: 'mobooking_create_booking',
            nonce: MOB_PARAMS.nonce,
            tenant_id: MOB_PARAMS.tenantId,
            selected_services: JSON.stringify([{ // Current structure assumes one service
                service_id: selectedService.service_id,
                name: selectedService.name,
                price: selectedService.price,
                configured_options: selectedOptions
            }]),
            customer_details: JSON.stringify(customerDetails),
            discount_info: discountInfo ? JSON.stringify(discountInfo) : null,
            zip_code: $('#mobooking-zip').val() || (customerDetails.zip_code || ''), // From step 1 or details
            country_code: $('#mobooking-country').val() || (customerDetails.country_code || ''),
            pricing: JSON.stringify({
                subtotal: calculateSubtotal(),
                discount: discountInfo ? (calculateSubtotal() - totalPrice) : 0, // Recalculate discount amount based on final total
                total: totalPrice
            })
        };

        $.ajax({
            url: MOB_PARAMS.ajaxUrl,
            type: 'POST',
            data: submissionData,
            success: function(response) {
                if (response.success && response.data) {
                    showStep(6); // Success step
                    $('#success-details').html(`
                        <div class="success-detail"><strong>Booking Reference:</strong> <span>${escapeHtml(response.data.booking_reference || 'N/A')}</span></div>
                        <div class="success-detail"><strong>Service:</strong> <span>${escapeHtml(selectedService.name)}</span></div>
                        <div class="success-detail"><strong>Customer:</strong> <span>${escapeHtml(customerDetails.name)}</span></div>
                        <div class="success-detail"><strong>Email:</strong> <span>${escapeHtml(customerDetails.email)}</span></div>
                        <div class="success-detail"><strong>Total:</strong> <span>${MOB_PARAMS.currency.symbol}${formatPrice(response.data.final_total || totalPrice)}</span></div>
                        <p style="margin-top: 1rem; color: var(--muted-foreground);">
                            You will receive a confirmation email shortly at ${escapeHtml(customerDetails.email)}.
                        </p>`);
                } else {
                    showFeedback($feedback, 'error', response.data?.message || 'Booking submission failed.');
                }
            },
            error: function() {
                showFeedback($feedback, 'error', MOB_PARAMS.i18n.connectionError);
            },
            complete: function() {
                $button.prop('disabled', false).html(originalBtnHtml);
            }
        });
    }

    // Start the form
    initializeForm();
});
