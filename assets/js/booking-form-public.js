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
    function getStepName(stepNumber) { // Added missing function
        const names = ["", "location", "services", "options", "details", "review", "success"];
        return names[stepNumber] || "unknown";
    }

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

        console.log('[MoBooking JS Debug] displayServiceOptions called. Selected Service:', selectedService);

        if (!selectedService || !selectedService.options || !Array.isArray(selectedService.options) || selectedService.options.length === 0) {
            let message = '<p>No additional options for this service.</p>';
            if (!selectedService) message = '<p>No service selected for options.</p>';
            else if (!selectedService.options) message = '<p>Service options data is missing.</p>';
            else if (!Array.isArray(selectedService.options)) message = '<p>Service options data is not an array.</p>';

            $container.html(message);
            console.log('[MoBooking JS Debug] No options to display or selectedService.options is not a valid array. Message:', message, 'Options data:', selectedService ? selectedService.options : 'N/A');
            updateLiveSummary();
            return;
        }

        let html = '';
        selectedService.options.forEach((option, index) => {
            console.log(`[MoBooking JS Debug] Generating HTML for option ${index}:`, option);
            if (!option || typeof option.option_id === 'undefined') {
                console.error(`[MoBooking JS Debug] Invalid option object at index ${index}:`, option);
                html += `<div class="mobooking-form-group"><p class="mobooking-feedback error">Error: Invalid option data.</p></div>`;
                return; // skip this option
            }

            const isChecked = selectedOptions[option.option_id] && selectedOptions[option.option_id].value === '1';
            let currentValue = selectedOptions[option.option_id] ? selectedOptions[option.option_id].value : '';
            if (option.type === 'quantity' && currentValue === '') currentValue = '0'; // Default quantity to 0 if not set

            const requiredAttr = option.is_required ? 'required' : '';
            const requiredIndicator = option.is_required ? '<span class="mobooking-required">*</span>' : '';
            let priceImpact = parseFloat(option.price_impact || 0);
            let priceImpactType = option.price_impact_type || 'fixed';
            let priceDisplay = '';

            if (priceImpact !== 0) {
                 priceDisplay = priceImpactType === 'percentage'
                    ? `(+${priceImpact}%)`
                    : `(+${MOB_PARAMS.currency.symbol}${formatPrice(priceImpact)})`;
                 priceDisplay = `<span class="option-price">${priceDisplay}</span>`;
            }

            let optionFieldHtml = '';
            const optionName = `option_${option.option_id}`;

            switch (option.type) {
                case 'checkbox':
                    optionFieldHtml = `
                        <label>
                            <input type="checkbox" name="${optionName}" value="1"
                                   data-price="${priceImpact}" data-price-type="${priceImpactType}"
                                   ${requiredAttr} ${isChecked ? 'checked' : ''}>
                            ${escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                        </label>`;
                    break;
                case 'text':
                    optionFieldHtml = `
                        <label for="${optionName}" class="mobooking-label">
                            ${escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                        </label>
                        <input type="text" id="${optionName}" name="${optionName}" value="${escapeHtml(currentValue)}"
                               class="mobooking-input" data-price="${priceImpact}" data-price-type="${priceImpactType}" ${requiredAttr}>`;
                    break;
                case 'textarea':
                    optionFieldHtml = `
                        <label for="${optionName}" class="mobooking-label">
                            ${escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                        </label>
                        <textarea id="${optionName}" name="${optionName}"
                                  class="mobooking-textarea" data-price="${priceImpact}" data-price-type="${priceImpactType}" ${requiredAttr}>${escapeHtml(currentValue)}</textarea>`;
                    break;
                case 'select':
                    optionFieldHtml = `
                        <label for="${optionName}" class="mobooking-label">
                            ${escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                        </label>
                        <select id="${optionName}" name="${optionName}" class="mobooking-select" data-price="${priceImpact}" data-price-type="${priceImpactType}" ${requiredAttr}>`;
                    if (option.option_values && Array.isArray(option.option_values)) {
                        option.option_values.forEach(val => {
                            // For select, price impact is often per option value, not on the select itself.
                            // The current structure has price_impact on the main option, and option_values might have their own price_adjust.
                            // For simplicity here, assuming main price_impact applies if value selected, or individual values handle their own.
                            // The PHP preloading logic for options needs to ensure option_values are structured correctly.
                            // Current JS `handleOptionChange` and `updateLiveSummary` use the main option's price_impact.
                            // If `val.price_adjust` exists, that should be used by `handleOptionChange`.
                            // For now, data-attributes on <option> are not used for price by handleOptionChange.
                            optionFieldHtml += `<option value="${escapeHtml(val.value)}" ${val.value === currentValue ? 'selected' : ''}>${escapeHtml(val.label)}</option>`;
                        });
                    }
                    optionFieldHtml += `</select>`;
                    break;
                case 'radio':
                    optionFieldHtml = `<label class="mobooking-label">${escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}</label>`;
                    if (option.option_values && Array.isArray(option.option_values)) {
                        option.option_values.forEach((val, valIdx) => {
                            const radioId = `${optionName}_${valIdx}`;
                            optionFieldHtml += `
                                <div class="mobooking-form-group-radio">
                                    <input type="radio" id="${radioId}" name="${optionName}" value="${escapeHtml(val.value)}"
                                           ${val.value === currentValue ? 'checked' : ''} ${requiredAttr}>
                                    <label for="${radioId}">${escapeHtml(val.label)}</label>
                                </div>`;
                        });
                    }
                    break;
                case 'quantity':
                     optionFieldHtml = `
                        <label for="${optionName}" class="mobooking-label">
                            ${escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                        </label>
                        <div class="mobooking-quantity-input-wrapper">
                            <button type="button" class="mobooking-btn-quantity minus" data-target="${optionName}">-</button>
                            <input type="number" id="${optionName}" name="${optionName}" value="${escapeHtml(currentValue || '0')}" min="0"
                                   class="mobooking-input mobooking-input-quantity" data-price="${priceImpact}" data-price-type="${priceImpactType}" ${requiredAttr}>
                            <button type="button" class="mobooking-btn-quantity plus" data-target="${optionName}">+</button>
                        </div>`;
                    break;
                case 'number': // Similar to quantity but maybe without buttons initially, or we can make them consistent
                    optionFieldHtml = `
                        <label for="${optionName}" class="mobooking-label">
                            ${escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                        </label>
                        <div class="mobooking-quantity-input-wrapper">
                             <button type="button" class="mobooking-btn-quantity minus" data-target="${optionName}">-</button>
                             <input type="number" id="${optionName}" name="${optionName}" value="${escapeHtml(currentValue || '0')}" min="0"
                                   class="mobooking-input mobooking-input-quantity" data-price="${priceImpact}" data-price-type="${priceImpactType}" ${requiredAttr}>
                             <button type="button" class="mobooking-btn-quantity plus" data-target="${optionName}">+</button>
                        </div>`;
                    break;
                case 'sqm': // Square Meters with slider and input
                    const sqmRanges = option.option_values && Array.isArray(option.option_values) ? option.option_values : [];
                    const minSqm = sqmRanges.length > 0 && typeof sqmRanges[0].from !== 'undefined' ? parseFloat(sqmRanges[0].from) : 1;
                    const maxSqm = sqmRanges.length > 0 && typeof sqmRanges[sqmRanges.length -1].to !== 'undefined' && sqmRanges[sqmRanges.length -1].to !== '∞' ? parseFloat(sqmRanges[sqmRanges.length -1].to) : 1000; // Default max if not specified or infinite

                    optionFieldHtml = `
                        <label for="${optionName}" class="mobooking-label">
                            ${escapeHtml(option.name)} ${requiredIndicator} <!-- Price display for SQM is complex, usually shown in summary -->
                        </label>
                        <div class="mobooking-sqm-input-wrapper" data-sqm-ranges='${JSON.stringify(sqmRanges)}'>
                            <input type="range" id="${optionName}_slider" name="${optionName}_slider"
                                   min="${minSqm}" max="${maxSqm}" value="${escapeHtml(currentValue || minSqm.toString())}" class="mobooking-slider">
                            <input type="number" id="${optionName}" name="${optionName}" value="${escapeHtml(currentValue || minSqm.toString())}"
                                   min="${minSqm}" max="${maxSqm}" class="mobooking-input mobooking-input-sqm" ${requiredAttr}>
                            <span class="mobooking-sqm-unit">sqm</span>
                        </div>`;
                         // Display ranges if helpful
                    if (sqmRanges.length > 0) {
                        optionFieldHtml += '<div class="mobooking-sqm-ranges-display">';
                        sqmRanges.forEach(range => {
                            optionFieldHtml += `<span>${range.from}-${range.to === '∞' ? '&infin;' : range.to} sqm: ${MOB_PARAMS.currency.symbol}${range.price}/sqm</span><br>`;
                        });
                        optionFieldHtml += '</div>';
                    }
                    break;
                default:
                    optionFieldHtml = `<p class="mobooking-feedback error">Unsupported option type: ${escapeHtml(option.type)}</p>`;
            }

            html += `<div class="mobooking-form-group" data-option-id="${option.option_id}" data-option-type="${option.type}">${optionFieldHtml}`;
            if (option.description) {
                html += `<div class="option-description">${escapeHtml(option.description)}</div>`;
            }
            html += `</div>`;
        });
        $container.html(html);
        // Rebind events for newly created elements
        $container.find('input, textarea, select').on('change input', handleOptionChange);
        $container.find('.mobooking-btn-quantity').on('click', handleQuantityButtonClick);
        $container.find('.mobooking-slider').on('input', handleSqmSliderChange);
        $container.find('.mobooking-input-sqm').on('input change', handleSqmInputChange);

        updateLiveSummary();
    }

    function handleQuantityButtonClick() {
        const $button = $(this);
        const targetInputId = $button.data('target');
        const $input = $('#' + targetInputId);
        let currentValue = parseInt($input.val()) || 0;
        if ($button.hasClass('plus')) {
            currentValue++;
        } else if ($button.hasClass('minus') && currentValue > 0) {
            currentValue--;
        }
        $input.val(currentValue).trigger('change'); // Trigger change to update summary
    }

    function handleSqmSliderChange() {
        const $slider = $(this);
        const $wrapper = $slider.closest('.mobooking-sqm-input-wrapper');
        const $input = $wrapper.find('.mobooking-input-sqm');
        $input.val($slider.val()).trigger('change');
    }

    function handleSqmInputChange() {
        const $input = $(this);
        const $wrapper = $input.closest('.mobooking-sqm-input-wrapper');
        const $slider = $wrapper.find('.mobooking-slider');
        let val = parseFloat($input.val());
        const min = parseFloat($slider.attr('min'));
        const max = parseFloat($slider.attr('max'));
        if (isNaN(val)) val = min;
        if (val < min) val = min;
        if (val > max) val = max;
        $input.val(val); // Corrected value
        $slider.val(val);
        // `handleOptionChange` will be triggered by the 'change' event on $input
    }


    function handleOptionChange() {
        const $inputElement = $(this); // Could be slider, number input, select, etc.
        const $formGroup = $inputElement.closest('.mobooking-form-group');
        const optionId = $formGroup.data('option-id');
        const optionType = $formGroup.data('option-type');

        const serviceOption = selectedService.options.find(opt => opt.option_id === parseInt(optionId));

        if (!serviceOption) {
            console.error("Could not find service option data for ID:", optionId);
            return;
        }

        let value;
        let price = 0; // Default to 0, will be calculated
        let priceType = serviceOption.price_impact_type || 'fixed';
        const optionName = serviceOption.name;

        if (optionType === 'checkbox') {
            value = $inputElement.is(':checked') ? '1' : '0';
            if (value === '1') price = parseFloat(serviceOption.price_impact || 0);
        } else if (optionType === 'select') {
            value = $inputElement.val();
            const selectedChoice = serviceOption.option_values.find(ov => ov.value === value);
            if (selectedChoice && typeof selectedChoice.price_adjust !== 'undefined') {
                price = parseFloat(selectedChoice.price_adjust);
            } else { // Fallback to main option price if no specific adjustment
                price = parseFloat(serviceOption.price_impact || 0);
            }
        } else if (optionType === 'radio') {
             value = $('input[name="option_' + optionId + '"]:checked').val();
             const selectedChoice = serviceOption.option_values.find(ov => ov.value === value);
             if (selectedChoice && typeof selectedChoice.price_adjust !== 'undefined') {
                price = parseFloat(selectedChoice.price_adjust);
            } else {
                price = parseFloat(serviceOption.price_impact || 0);
            }
        } else if (optionType === 'quantity' || optionType === 'number') {
            value = $inputElement.val().trim();
            const quantityVal = parseInt(value) || 0;
            if (quantityVal > 0) {
                if (serviceOption.price_impact_type === 'per_unit') { // Assuming a type for per_unit pricing
                     price = parseFloat(serviceOption.price_impact || 0) * quantityVal;
                } else { // Fixed price impact regardless of quantity (e.g. "add this feature") or no impact
                     price = parseFloat(serviceOption.price_impact || 0);
                }
            } else { // Quantity is 0
                value = '0'; // Ensure value is '0' not empty string if that matters
                price = 0;
            }
        } else if (optionType === 'sqm') {
            value = $inputElement.val().trim();
            const sqmValue = parseFloat(value) || 0;
            if (sqmValue > 0) {
                const ranges = JSON.parse($inputElement.closest('.mobooking-sqm-input-wrapper').data('sqm-ranges') || '[]');
                for (const range of ranges) {
                    const from = parseFloat(range.from);
                    const to = range.to === '∞' || typeof range.to === 'undefined' ? Infinity : parseFloat(range.to);
                    if (sqmValue >= from && sqmValue <= to) {
                        price = parseFloat(range.price) * sqmValue;
                        break;
                    }
                }
            } else {
                 price = 0;
            }
            priceType = 'calculated'; // SQM price is always calculated directly
        }
        else { // text, textarea
            value = $inputElement.val().trim();
            if (value) price = parseFloat(serviceOption.price_impact || 0);
        }


        if (value && (optionType === 'checkbox' ? value === '1' : true) && (optionType !== 'quantity' && optionType !== 'number' || parseInt(value) > 0) ) {
            selectedOptions[optionId] = {
                name: optionName,
                value: value,
                price: price, // This is the calculated impact for this option
                priceType: priceType // This might need adjustment if base option has % and choice has fixed
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
