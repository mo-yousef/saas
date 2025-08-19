/**
 * Refactored MoBooking Public Booking Form JavaScript
 * Aligns with the new compact, dashboard-style design.
 */
jQuery(document).ready(function ($) {
    "use strict";

    // --- CONFIG & STATE ---
    const CONFIG = window.MOBOOKING_CONFIG || {};
    const state = {
        currentStep: 1,
        totalSteps: 4,
        selectedServices: {}, // Store multiple services
        bookingDate: "",
        bookingTime: "",
        customer: {},
        summary: {
            baseTotal: 0,
            total: 0,
        }
    };

    // --- DOM REFERENCES ---
    const els = {
        form: $("#mobooking-public-form"),
        steps: $(".mobooking-step-content"),
        progressFill: $("#mobooking-progress-fill"),
        progressSteps: $("#mobooking-progress-steps"),

        // Step 1: Services
        servicesContainer: $("#mobooking-services-container"),
        serviceFeedback: $("#mobooking-service-feedback"),

        // Step 2: Date & Time
        dateInput: $("#mobooking-service-date"),
        timeSlotsContainer: $("#mobooking-time-slots"),
        dateTimeFeedback: $("#mobooking-datetime-feedback"),

        // Step 3: Info
        nameInput: $("#mobooking-customer-name"),
        emailInput: $("#mobooking-customer-email"),
        phoneInput: $("#mobooking-customer-phone"),
        addressInput: $("#mobooking-service-address"),
        instructionsInput: $("#mobooking-special-instructions"),
        contactFeedback: $("#mobooking-contact-feedback"),

        // Step 4: Success
        successMessage: $("#mobooking-success-message"),
        finalSummary: $("#mobooking-booking-summary"),

        // Sidebar
        liveSummaryContent: $("#mobooking-summary-content")
    };

    // --- STEP NAVIGATION ---
    function showStep(step) {
        state.currentStep = step;
        els.steps.removeClass("active");
        $(`#mobooking-step-${step}`).addClass("active");
        updateProgressBar();
    }

    function updateProgressBar() {
        // Simple progress update
        const progress = ((state.currentStep - 1) / (state.totalSteps - 1)) * 100;
        els.progressFill.css("width", `${progress}%`);
    }

    window.moBookingNextStep = function() {
        if (validateStep(state.currentStep)) {
            showStep(state.currentStep + 1);
        }
    };

    window.moBookingPreviousStep = function() {
        showStep(state.currentStep - 1);
    };

    function validateStep(step) {
        let isValid = true;
        switch(step) {
            case 1:
                if (Object.keys(state.selectedServices).length === 0) {
                    showFeedback(els.serviceFeedback, 'error', CONFIG.i18n.select_one_service || 'Please select at least one service.');
                    isValid = false;
                } else {
                    showFeedback(els.serviceFeedback, 'clear');
                }
                break;
            case 2:
                if (!state.bookingDate || !state.bookingTime) {
                    showFeedback(els.dateTimeFeedback, 'error', CONFIG.i18n.date_required || 'Please select a date and time.');
                    isValid = false;
                } else {
                    showFeedback(els.dateTimeFeedback, 'clear');
                }
                break;
            case 3:
                // Simple validation for contact info
                if (!els.nameInput.val() || !els.emailInput.val() || !els.phoneInput.val() || !els.addressInput.val()) {
                    showFeedback(els.contactFeedback, 'error', 'Please fill in all required fields.');
                    isValid = false;
                } else {
                     showFeedback(els.contactFeedback, 'clear');
                }
                break;
        }
        return isValid;
    }

    function showFeedback(el, type, message) {
        el.removeClass('success error').empty();
        if (type === 'clear') {
            el.hide();
        } else {
            el.addClass(type).text(message).show();
        }
    }

    // --- SERVICES (Step 1) ---
    function loadServices() {
        $.post(CONFIG.ajax_url, {
            action: 'mobooking_get_public_services',
            nonce: CONFIG.nonce,
            tenant_id: CONFIG.tenant_id
        }).done(function(response) {
            if (response.success && response.data.services.length) {
                renderServices(response.data.services);
            } else {
                els.servicesContainer.html(`<p>${CONFIG.i18n.no_services_available || 'No services available.'}</p>`);
            }
        }).fail(function() {
            els.servicesContainer.html(`<p>${CONFIG.i18n.error_loading_services || 'Error loading services.'}</p>`);
        });
    }

    function renderServices(services) {
        let html = '';
        services.forEach(service => {
            html += `
                <label class="mobooking-service-card" data-service-id="${service.service_id}">
                    <input type="checkbox" class="mobooking-service-checkbox" value="${service.service_id}">
                    <div class="service-details">
                        <span class="service-name">${service.name}</span>
                        <span class="service-price">${CONFIG.currency.symbol}${service.price}</span>
                    </div>
                </label>
            `;
        });
        els.servicesContainer.html(html);
    }

    els.servicesContainer.on('change', '.mobooking-service-checkbox', function() {
        const serviceId = $(this).val();
        const card = $(this).closest('.mobooking-service-card');

        if (this.checked) {
            card.addClass('selected');
            // In a real scenario, you'd fetch the full service object
            state.selectedServices[serviceId] = {
                id: serviceId,
                name: card.find('.service-name').text(),
                price: parseFloat(card.find('.service-price').text().replace(CONFIG.currency.symbol, ''))
            };
        } else {
            card.removeClass('selected');
            delete state.selectedServices[serviceId];
        }
        updateLiveSummary();
    });

    // --- DATE & TIME (Step 2) ---
    function initDatePicker() {
        els.dateInput.flatpickr({
            inline: true,
            minDate: 'today',
            dateFormat: 'Y-m-d',
            onChange: function(selectedDates, dateStr) {
                state.bookingDate = dateStr;
                loadTimeSlots(dateStr);
            }
        });
    }

    function loadTimeSlots(date) {
        // Mock time slots
        const slots = ['09:00', '11:00', '13:00', '15:00'];
        renderTimeSlots(slots);
    }

    function renderTimeSlots(slots) {
        let html = '';
        slots.forEach(slot => {
            html += `<button type="button" class="mobooking-time-slot">${slot}</button>`;
        });
        els.timeSlotsContainer.html(html);
    }

    els.timeSlotsContainer.on('click', '.mobooking-time-slot', function() {
        els.timeSlotsContainer.find('.mobooking-time-slot').removeClass('selected');
        $(this).addClass('selected');
        state.bookingTime = $(this).text();
        updateLiveSummary();
    });

    // --- SUMMARY ---
    function updateLiveSummary() {
        let serviceHtml = '<p>No services selected.</p>';
        let total = 0;

        if (Object.keys(state.selectedServices).length > 0) {
            serviceHtml = '';
            for (const id in state.selectedServices) {
                const service = state.selectedServices[id];
                serviceHtml += `
                    <div class="summary-item">
                        <span class="summary-label">${service.name}</span>
                        <span class="summary-value">${CONFIG.currency.symbol}${service.price.toFixed(2)}</span>
                    </div>`;
                total += service.price;
            }
        }

        state.summary.baseTotal = total;
        state.summary.total = total; // Add other charges like options if any

        let summaryHtml = serviceHtml;
        summaryHtml += `<div class="summary-total">
            <span class="summary-label">Total</span>
            <span class="summary-value">${CONFIG.currency.symbol}${state.summary.total.toFixed(2)}</span>
        </div>`;

        if (state.bookingDate && state.bookingTime) {
            summaryHtml += `<div class="summary-item summary-meta">
                <span class="summary-label">Date & Time</span>
                <span class="summary-value">${state.bookingDate} at ${state.bookingTime}</span>
            </div>`;
        }

        els.liveSummaryContent.html(summaryHtml);
    }

    // --- SUBMISSION ---
    window.moBookingSubmitForm = function() {
        if (!validateStep(3)) return;

        state.customer = {
            name: els.nameInput.val(),
            email: els.emailInput.val(),
            phone: els.phoneInput.val(),
            address: els.addressInput.val(),
            instructions: els.instructionsInput.val()
        };

        const payload = {
            action: 'mobooking_create_booking',
            nonce: CONFIG.nonce,
            tenant_id: CONFIG.tenant_id,
            customer_details: JSON.stringify(state.customer),
            selected_services: JSON.stringify(Object.values(state.selectedServices)),
            booking_date: state.bookingDate,
            booking_time: state.bookingTime,
            total_price: state.summary.total
        };

        showFeedback(els.contactFeedback, 'success', 'Submitting...');

        $.post(CONFIG.ajax_url, payload).done(function(response) {
            if (response.success) {
                showStep(4); // Move to success step
                renderFinalSummary(response.data);
            } else {
                showFeedback(els.contactFeedback, 'error', response.data.message || 'An error occurred.');
            }
        }).fail(function() {
            showFeedback(els.contactFeedback, 'error', 'A network error occurred.');
        });
    };

    function renderFinalSummary(data) {
        els.successMessage.text(CONFIG.success_message || 'Your booking is confirmed!');
        // You can use the data from the server or state to build the final summary
        let summaryHtml = `
            <p><strong>Booking Reference:</strong> ${data.booking_reference}</p>
        `;
        els.finalSummary.html(els.liveSummaryContent.html() + summaryHtml);
    }

    window.moBookingResetForm = function() {
        location.reload();
    };

    // --- INITIALIZATION ---
    function init() {
        showStep(1);
        loadServices();
        initDatePicker();
        updateLiveSummary();
    }

    init();
});
