jQuery(document).ready(function($) {
    'use strict';

    const locationForm = $('#mobooking-bf-location-form');
    const feedbackDiv = $('#mobooking-bf-feedback'); // General feedback for Step 1 (location check)
    const step1Div = $('#mobooking-bf-step-1-location');
    const tenantIdField = $('#mobooking-bf-tenant-id');

    // Step 2 elements
    const step2ServicesDiv = $('#mobooking-bf-step-2-services');
    const servicesListDiv = $('#mobooking-bf-services-list');
    const step2FeedbackDiv = $('#mobooking-bf-step-2-feedback');

    // Step 3 elements
    const step3OptionsDiv = $('#mobooking-bf-step-3-options');
    // const step3FeedbackDiv = $('#mobooking-bf-step-3-feedback'); // For later if needed

    let mobooking_current_step = 1; // Keep track of the current visible step
    let publicServicesCache = []; // Cache for service data from Step 2

    // Attempt to get tenant_id from URL query param 'tid'
    const urlParams = new URLSearchParams(window.location.search);
    const tenantIdFromUrl = urlParams.get('tid');
    if (tenantIdFromUrl) {
        tenantIdField.val(tenantIdFromUrl);
    } else if (typeof mobooking_booking_form_params !== 'undefined' && mobooking_booking_form_params.tenant_id) {
        // Fallback to localized script param if available
        tenantIdField.val(mobooking_booking_form_params.tenant_id);
    }


    locationForm.on('submit', function(e) {
        e.preventDefault();
        feedbackDiv.empty().removeClass('success error').hide();

        const zipCode = $('#mobooking-bf-zip-code').val().trim();
        const countryCode = $('#mobooking-bf-country-code').val().trim();
        const tenantId = tenantIdField.val();

        if (!zipCode) {
            feedbackDiv.text(mobooking_booking_form_params.i18n.zip_required || 'ZIP Code is required.').addClass('error').show();
            return;
        }
        if (!tenantId) {
            feedbackDiv.text(mobooking_booking_form_params.i18n.tenant_id_missing || 'Business identifier is missing.').addClass('error').show();
            return;
        }
        if (!countryCode) { // Though it has a default, user might clear it
            feedbackDiv.text(mobooking_booking_form_params.i18n.country_code_required || 'Country Code is required.').addClass('error').show();
            return;
        }


        const submitButton = $(this).find('button[type="submit"]');
        const originalButtonText = submitButton.text();
        submitButton.prop('disabled', true).text(mobooking_booking_form_params.i18n.checking || 'Checking...');

        $.ajax({
            url: mobooking_booking_form_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_check_zip_availability',
                nonce: mobooking_booking_form_params.nonce,
                zip_code: zipCode,
                country_code: countryCode,
                tenant_id: tenantId
            },
            success: function(response) {
                if (response.success) {
                    feedbackDiv.text(response.data.message).addClass(response.data.serviced ? 'success' : 'error').show();
                    if (response.data.serviced) {
                        // Store validated data for next steps
                        sessionStorage.setItem('mobooking_cart_zip', zipCode);
                        sessionStorage.setItem('mobooking_cart_country', countryCode);
                        sessionStorage.setItem('mobooking_cart_tenant_id', tenantId);

                        displayStep(2); // Move to step 2
                        displayStep2_LoadServices(); // Load services for step 2
                    }
                } else {
                    // This case handles {success: false, data: {message: "..."}} from wp_send_json_error
                    feedbackDiv.text(response.data.message || mobooking_booking_form_params.i18n.error_generic).addClass('error').show();
                }
            },
            error: function() { // This handles network errors or non-JSON responses
                feedbackDiv.text(mobooking_booking_form_params.i18n.error_generic).addClass('error').show();
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    // Back button from Step 2 to Step 1 (this ID was in the old HTML, now it's #mobooking-bf-services-back-btn)
    // This will be replaced by the new back button handlers below.
    // $('#mobooking-bf-back-to-step-1').on('click', function() { ... });


    function displayStep(stepToShow) {
        $('.mobooking-bf-step').hide(); // Hide all steps first
        let targetStepDiv;

        if (stepToShow === 1) targetStepDiv = step1Div;
        else if (stepToShow === 2) targetStepDiv = step2ServicesDiv;
        else if (stepToShow === 3) targetStepDiv = step3OptionsDiv;
        // else if (stepToShow === 4) targetStepDiv = $('#mobooking-bf-step-4-details'); // For future step

        if (targetStepDiv && targetStepDiv.length) {
            targetStepDiv.slideDown();
        }
        mobooking_current_step = stepToShow;
        // TODO: Update a visual progress bar if implemented
    }

    function bfRenderTemplate(templateId, data) {
        let template = $(templateId).html();
        if (!template) return '';
        for (const key in data) {
            const value = (typeof data[key] === 'string' || typeof data[key] === 'number') ? data[key] : '';
            // Basic sanitization for HTML display
            const sanitizedValue = $('<div>').text(value).html();
            template = template.replace(new RegExp('<%=\\s*' + key + '\\s*%>', 'g'), sanitizedValue);
        }
        return template;
    }

    function displayStep2_LoadServices() {
        const tenantId = sessionStorage.getItem('mobooking_cart_tenant_id');
        if (!tenantId) {
            feedbackDiv.text(mobooking_booking_form_params.i18n.tenant_id_missing_refresh || 'Tenant ID missing. Please refresh.').addClass('error').show();
            displayStep(1); // Force back to step 1
            return;
        }
        servicesListDiv.html('<p>' + (mobooking_booking_form_params.i18n.loading_services || 'Loading services...') + '</p>');
        step2FeedbackDiv.empty().hide();

        $.ajax({
            url: mobooking_booking_form_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_public_services',
                nonce: mobooking_booking_form_params.nonce,
                tenant_id: tenantId
            },
            success: function(response) {
                servicesListDiv.empty();
                publicServicesCache = [];
                if (response.success && response.data.length) {
                    publicServicesCache = response.data;
                    publicServicesCache.forEach(function(service) {
                        // price_formatted is assumed to be provided by PHP AJAX handler
                        servicesListDiv.append(bfRenderTemplate('#mobooking-bf-service-item-template', service));
                    });
                } else if (response.success) {
                    servicesListDiv.html('<p>' + (mobooking_booking_form_params.i18n.no_services_available || 'No services available.') + '</p>');
                } else {
                    servicesListDiv.html('<p>' + (response.data.message || mobooking_booking_form_params.i18n.error_loading_services || 'Error loading services.') + '</p>');
                }
            },
            error: function() {
                servicesListDiv.html('<p>' + (mobooking_booking_form_params.i18n.error_loading_services || 'Error loading services.') + '</p>');
            }
        });
    }

    $('#mobooking-bf-services-next-btn').on('click', function() {
        const selectedServicesData = [];
        servicesListDiv.find('input[name="selected_services[]"]:checked').each(function() {
            const serviceId = parseInt($(this).data('service-id'), 10);
            const serviceData = publicServicesCache.find(s => parseInt(s.service_id, 10) === serviceId);
            if (serviceData) {
                selectedServicesData.push(serviceData);
            }
        });

        if (selectedServicesData.length === 0) {
            step2FeedbackDiv.text(mobooking_booking_form_params.i18n.select_one_service || 'Please select at least one service.').addClass('error').show();
            return;
        }
        step2FeedbackDiv.empty().removeClass('error').hide();
        sessionStorage.setItem('mobooking_cart_selected_services', JSON.stringify(selectedServicesData));

        // displayStep3_ServiceOptions(); // This function will be for rendering options in Step 3 (next sub-task)
        console.log("Selected services for Step 3:", selectedServicesData);
        displayStep(3); // Show placeholder for Step 3 for now
    });

    // Back buttons handlers
    $('#mobooking-bf-services-back-btn').on('click', function() { displayStep(1); });
    $('#mobooking-bf-options-back-btn').on('click', function() { displayStep(2); });
    // Example for future Step 4 back to Step 3
    // $('#mobooking-bf-details-back-btn').on('click', function() { displayStep(3); });


    // Initial setup on page load: Ensure Step 1 is shown.
    displayStep(1);
});
