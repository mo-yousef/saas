/**
 * MoBooking 8-Step Booking Form JavaScript
 * Handles the restructured booking flow.
 */
jQuery(document).ready(function ($) {
    "use strict";

    // ==========================================
    // GLOBAL STATE & CONFIG
    // ==========================================
    const MOB_PARAMS = window.mobooking_booking_form_params || {};
    const FORM_SETTINGS = MOB_PARAMS.settings || {};
    const CURRENCY_SYMBOL = MOB_PARAMS.currency_symbol || '$';
    const TENANT_ID = MOB_PARAMS.tenant_id || null;
    const AJAX_URL = MOB_PARAMS.ajax_url;
    const NONCE = MOB_PARAMS.nonce;

    let currentStep = 1;
    let bookingData = {
        service: null,
        options: {},
        pets: { has_pets: 'no', details: '' },
        frequency: 'one-time',
        datetime: { date: '', time: '' },
        contact: { name: '', email: '', phone: '', address: '', instructions: '' },
        access: { type: 'im_home', details: '' },
        pricing: { base: 0, options: 0, subtotal: 0, discount: 0, total: 0 },
        discount_code: null,
    };

    // ==========================================
    // INITIALIZATION
    // ==========================================
    function initializeForm() {
        if (FORM_SETTINGS.bf_form_enabled !== '1') {
            $('.mobooking-bf-wrapper').html(`<div class="mobooking-bf__feedback mobooking-bf__feedback--error">${FORM_SETTINGS.bf_maintenance_message}</div>`);
            return;
        }

        if ($.fn.datepicker) {
            $(".mobooking-datepicker").datepicker({ dateFormat: "yy-mm-dd", minDate: 0 });
        }

        attachEventHandlers();

        const initialStep = $('#mobooking-bf-step-1-location').data('disabled') === true ? 2 : 1;
        displayStep(initialStep);
        if (initialStep === 2) {
            loadServicesForTenant(TENANT_ID);
        }
    }

    // ==========================================
    // EVENT HANDLERS
    // ==========================================
    function attachEventHandlers() {
        $(document).on('click', '.mobooking-bf__button[data-nav]', function() { navigate($(this).data('nav')); });
        $('#mobooking-bf-location-form').on('submit', handleLocationCheck);
        $(document).on('change', 'input[name="selected_service"]', handleServiceSelection);
        $('#mobooking-bf-service-options-display').on('change input', 'input, select, textarea', handleOptionChange);
        $('input[name="has_pets"]').on('change', handlePetStatusChange);
        $('#mobooking-bf-pet-details').on('input', e => { bookingData.pets.details = e.target.value; renderOrUpdateSidebarSummary(); });
        $('input[name="service_frequency"]').on('change', e => { bookingData.frequency = e.target.value; renderOrUpdateSidebarSummary(); });
        $('#mobooking-bf-preferred-date').on('change', handleDateChange);
        $(document).on('click', '.mobooking-time-slot.available', handleTimeSlotSelection);
        $('#mobooking-bf-property-access').on('change', handlePropertyAccessChange);
        $('#mobooking-bf-property-access-other').on('input', e => { bookingData.access.details = e.target.value; });
        $('#mobooking-bf-details-form').on('input', 'input, textarea', handleContactDetailsChange);
        $('#mobooking-bf-final-submit-btn').on('click', submitBooking);
    }

    // ==========================================
    // NAVIGATION & DISPLAY
    // ==========================================
    function displayStep(stepNumber) {
        currentStep = stepNumber;
        $('.mobooking-bf__step').hide();
        $(`.mobooking-bf__step[data-step="${stepNumber}"]`).show();
        $('.mobooking-bf-wrapper').get(0).scrollIntoView({ behavior: 'smooth', block: 'start' });
        updateProgressBar();
        updateSidebar();
    }

    function getActiveSteps() {
        return $('.mobooking-bf__step').filter((i, el) => $(el).data('disabled') !== true).map((i, el) => $(el).data('step')).get();
    }

    function updateProgressBar() {
        const activeSteps = getActiveSteps();
        const totalSteps = activeSteps.length;
        const currentStepIndex = activeSteps.indexOf(currentStep);
        const progress = totalSteps > 0 ? ((currentStepIndex + 1) / totalSteps) * 100 : 0;
        $('.mobooking-progress-bar').css('width', progress + '%');
        $('.mobooking-progress-text').text(`Step ${currentStepIndex + 1} of ${totalSteps}`);
    }

    function navigate(direction) {
        const activeSteps = getActiveSteps();
        const currentStepIndex = activeSteps.indexOf(currentStep);

        if (direction === 'next') {
            if (!validateStep(currentStep)) return;
            if (currentStepIndex < activeSteps.length - 1) {
                const nextStep = activeSteps[currentStepIndex + 1];
                if (nextStep === 3 && bookingData.service) displayStep3_LoadOptions();
                displayStep(nextStep);
            }
        } else if (direction === 'back' && currentStepIndex > 0) {
            displayStep(activeSteps[currentStepIndex - 1]);
        }
    }

    function validateStep(step) {
        if (step === 2 && !bookingData.service) { alert('Please select a service.'); return false; }
        if (step === 7) {
            const { name, email, address } = bookingData.contact;
            if (!name || !email || !address) { alert('Please fill in all required contact details (Name, Email, Address).'); return false; }
        }
        return true;
    }

    function updateSidebar() {
        const activeSteps = getActiveSteps();
        const lastStepBeforeSuccess = activeSteps[activeSteps.length - 2];
        $('#mobooking-final-submit-button-container').toggle(currentStep === lastStepBeforeSuccess);
        renderOrUpdateSidebarSummary();
    }

    // ==========================================
    // SPECIFIC STEP HANDLERS
    // ==========================================
    function handleLocationCheck(e) {
        e.preventDefault();
        navigate('next');
        loadServicesForTenant(TENANT_ID);
    }

    function handleServiceSelection() {
        bookingData.service = $(this).data('service');
        bookingData.options = {};
        updatePricing();
        navigate('next');
    }

    function handleOptionChange() {
        updateOptionsSelection();
        updatePricing();
    }

    function handlePetStatusChange() {
        const hasPets = $(this).val();
        bookingData.pets.has_pets = hasPets;
        $('#mobooking-bf-pet-details-group').slideToggle(hasPets === 'yes');
        if (hasPets === 'no') {
            $('#mobooking-bf-pet-details').val('');
            bookingData.pets.details = '';
        }
        renderOrUpdateSidebarSummary();
    }

    function handleDateChange() {
        bookingData.datetime.date = $(this).val();
        const timeSlotsHtml = `<div class="mobooking-time-slot available" data-time="09:00">09:00 AM</div>`;
        $('#mobooking-bf-time-slots').html(timeSlotsHtml);
        renderOrUpdateSidebarSummary();
    }

    function handleTimeSlotSelection() {
        $('.mobooking-time-slot.selected').removeClass('selected');
        $(this).addClass('selected');
        bookingData.datetime.time = $(this).data('time');
        renderOrUpdateSidebarSummary();
    }

    function handlePropertyAccessChange() {
        const accessType = $(this).val();
        bookingData.access.type = accessType;
        $('#mobooking-bf-property-access-other-group').slideToggle(accessType === 'other');
        if (accessType !== 'other') {
            $('#mobooking-bf-property-access-other').val('');
            bookingData.access.details = '';
        }
    }

    function handleContactDetailsChange(e) {
        const fieldId = e.target.id.replace('mobooking-bf-', '').replace('customer-', '').replace('service-', '');
        if (bookingData.contact.hasOwnProperty(fieldId)) {
            bookingData.contact[fieldId] = e.target.value;
        }
    }

    // ==========================================
    // DATA LOADING & RENDERING
    // ==========================================
    function loadServicesForTenant(tenantId) {
        $("#mobooking-bf-services-list").html('<p>Loading services...</p>');
        $.ajax({
            url: AJAX_URL, type: 'POST', data: { action: 'mobooking_get_services', nonce: NONCE, tenant_id: tenantId },
            success: (response) => {
                if (response.success && response.data.services) renderServices(response.data.services);
                else $("#mobooking-bf-services-list").html(`<p class="mobooking-bf__feedback mobooking-bf__feedback--error">${response.data.message || "No services"}</p>`);
            },
            error: () => { $("#mobooking-bf-services-list").html(`<p class="mobooking-bf__feedback mobooking-bf__feedback--error">Error loading services.</p>`); }
        });
    }

    function renderServices(services) {
        const container = $("#mobooking-bf-services-list");
        if (!services || services.length === 0) { container.html('<p>No services available</p>'); return; }
        let html = "";
        services.forEach(service => {
            const price = parseFloat(service.price) || 0;
            const priceDisplay = FORM_SETTINGS.bf_show_pricing === "1" ? `<span class="mobooking-bf__service-price">${CURRENCY_SYMBOL}${price.toFixed(2)}</span>` : "";
            html += `<div class="mobooking-bf__service-item">
                        <label class="mobooking-bf__label--radio">
                            <input type="radio" name="selected_service" value="${service.service_id}" class="mobooking-bf__radio" data-service='${JSON.stringify(service)}'>
                            <span class="mobooking-bf__service-name">${escapeHtml(service.name)}</span>
                            ${priceDisplay}
                        </label>
                        <div class="mobooking-bf__service-description">${escapeHtml(service.description)}</div>
                    </div>`;
        });
        container.html(html);
    }

    function displayStep3_LoadOptions() {
        const { service } = bookingData;
        const container = $("#mobooking-bf-service-options-display");
        container.empty();
        if (!service || !service.options || service.options.length === 0) { container.html('<p>This service has no additional options.</p>'); return; }
        service.options.forEach(option => {
            const optionHtml = generateOptionFromTemplate(option, service.service_id);
            container.append(optionHtml);
        });
        initializeOptionInteractivity();
    }

    function generateOptionFromTemplate(option, serviceId) {
        const name = escapeHtml(option.name || "");
        const desc = option.description ? `<p class="mobooking-bf__option-description">${escapeHtml(option.description)}</p>` : "";
        const price = parseFloat(option.price_impact) || 0;
        const priceHtml = price > 0 ? `<span class="mobooking-bf__option-price">(+${CURRENCY_SYMBOL}${price.toFixed(2)})</span>` : "";
        return `<div class="mobooking-bf__option-item"><label><input type="checkbox" name="service_options[]" value="${option.option_id}" data-price="${price}"> ${name} ${priceHtml}</label>${desc}</div>`;
    }

    function initializeOptionInteractivity() {
        $('#mobooking-bf-service-options-display input').off('change').on('change', handleOptionChange);
    }

    function updateOptionsSelection() {
        bookingData.options = {};
        $('#mobooking-bf-service-options-display input[type="checkbox"]:checked').each(function() {
            const el = $(this);
            const optionId = el.val();
            bookingData.options[optionId] = {
                name: el.closest('label').text().trim().replace(/\s*\(.*\)\s*$/, ''),
                value: 'yes',
                price: parseFloat(el.data('price')) || 0
            };
        });
    }

    // ==========================================
    // PRICING & SUMMARY
    // ==========================================
    function updatePricing() {
        if (!bookingData.service) return;
        const servicePrice = parseFloat(bookingData.service.price) || 0;
        let optionsTotal = 0;
        Object.values(bookingData.options).forEach(opt => { optionsTotal += (opt.price || 0); });
        bookingData.pricing.base = servicePrice;
        bookingData.pricing.options = optionsTotal;
        bookingData.pricing.subtotal = servicePrice + optionsTotal;
        bookingData.pricing.total = Math.max(0, bookingData.pricing.subtotal - bookingData.pricing.discount);
        renderOrUpdateSidebarSummary();
    }

    function renderOrUpdateSidebarSummary() {
        const { service, options, pets, frequency, pricing } = bookingData;
        const summaryContainer = $('#mobooking-bf-sidebar-summary');
        if (!service) { summaryContainer.html('<p class="mobooking-bf__sidebar-empty">Your selections will appear here.</p>'); return; }

        let html = `<h4>${escapeHtml(service.name)}</h4>`;
        if (Object.keys(options).length > 0) {
            html += '<h5>Options:</h5><ul>';
            Object.values(options).forEach(opt => { html += `<li>${escapeHtml(opt.name)}</li>`; });
            html += '</ul>';
        }
        if (pets.has_pets === 'yes') html += `<p><strong>Pets:</strong> Yes</p>`;
        html += `<p><strong>Frequency:</strong> ${escapeHtml(frequency)}</p>`;
        summaryContainer.html(html);

        $('#mobooking-bf-sidebar-subtotal').text(CURRENCY_SYMBOL + pricing.subtotal.toFixed(2));
        $('#mobooking-bf-sidebar-total').text(CURRENCY_SYMBOL + pricing.total.toFixed(2));
        $('#mobooking-discount-display-sidebar').toggle(pricing.discount > 0).find('span').text(`-${CURRENCY_SYMBOL}${pricing.discount.toFixed(2)}`);
    }

    // ==========================================
    // SUBMISSION
    // ==========================================
    function submitBooking() {
        if (!validateStep(7)) return;
        const submitBtn = $('#mobooking-bf-final-submit-btn').prop('disabled', true).text('Submitting...');
        $.ajax({
            url: AJAX_URL, type: 'POST', data: { action: 'mobooking_submit_booking', nonce: NONCE, tenant_id: TENANT_ID, booking_data: bookingData },
            success: (response) => {
                if (response.success) {
                    displayStep(8);
                    $('#mobooking-conf-ref').text(response.data.booking_reference);
                    $('#mobooking-conf-service').text(bookingData.service.name);
                    $('#mobooking-conf-datetime').text(`${bookingData.datetime.date} at ${bookingData.datetime.time}`);
                    $('#mobooking-conf-total').text(CURRENCY_SYMBOL + bookingData.pricing.total.toFixed(2));
                } else {
                    alert('Booking failed: ' + (response.data.message || 'Unknown error'));
                    submitBtn.prop('disabled', false).text('Confirm Booking');
                }
            },
            error: () => { alert('An error occurred.'); submitBtn.prop('disabled', false).text('Confirm Booking'); }
        });
    }

    function escapeHtml(text) { return text ? $('<div>').text(text).html() : ''; }

    initializeForm();
});
