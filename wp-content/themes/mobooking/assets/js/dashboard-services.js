jQuery(document).ready(function($) {
    'use strict';

    const servicesListContainer = $('#mobooking-services-list-container');
    const serviceFormContainer = $('#mobooking-service-form-container');
    const serviceForm = $('#mobooking-service-form');
    const serviceFormTitle = $('#mobooking-service-form-title');
    const serviceIdField = $('#mobooking-service-id'); // Hidden input for service_id
    const feedbackDiv = $('#mobooking-service-form-feedback').hide();
    const optionsListContainer = $('#mobooking-service-options-list'); // Renamed for clarity
    const addServiceOptionBtn = $('#mobooking-add-service-option-btn');
    const optionTemplateHtml = $('#mobooking-service-option-template').html();
    if (optionTemplateHtml) { // Ensure template exists before removing
        $('#mobooking-service-option-template').remove(); // Remove template from DOM after getting HTML
    }


    let servicesDataCache = []; // Cache for service data
    let optionIndex = 0; // Used for unique IDs if needed, not for field names currently

    // Basic templating function
    function renderTemplate(templateId, data) {
        let template = $(templateId).html();
        if (!template) return '';
        for (const key in data) {
            const value = (typeof data[key] === 'string' || typeof data[key] === 'number') ? data[key] : '';
            const regex = new RegExp('<%=\\s*' + key + '\\s*%>', 'g');
            template = template.replace(regex, value);
        }
        return template;
    }

    // Basic XSS protection for display
    function sanitizeHTML(str) {
        if (typeof str !== 'string') return '';
        // Create a temporary div element
        var temp = document.createElement('div');
        // Set its textContent to the input string (this escapes HTML)
        temp.textContent = str;
        // Return the innerHTML (which is now the escaped string)
        return temp.innerHTML;
    }

    function populateForm(service) {
        serviceForm[0].reset();
        serviceIdField.val(service.service_id);
        $('#mobooking-service-name').val(service.name);
        $('#mobooking-service-description').val(service.description);
        $('#mobooking-service-price').val(parseFloat(service.price).toFixed(2));
        $('#mobooking-service-duration').val(service.duration);
        $('#mobooking-service-category').val(service.category);
        $('#mobooking-service-icon').val(service.icon);
        $('#mobooking-service-image-url').val(service.image_url);
        $('#mobooking-service-status').val(service.status);

        optionsListContainer.empty(); // Clear previous options
        optionIndex = 0;
        addServiceOptionBtn.prop('disabled', !service.service_id); // Enable only if service exists

        if (service.service_id && service.options && Array.isArray(service.options) && service.options.length) {
            service.options.forEach(function(opt) {
                optionIndex++;
                const newOptionRow = $(optionTemplateHtml); // Create new row from template
                newOptionRow.find('input[name="options[][option_id]"]').val(opt.option_id || '');
                newOptionRow.find('input[name="options[][name]"]').val(opt.name || '');
                newOptionRow.find('textarea[name="options[][description]"]').val(opt.description || '');
                newOptionRow.find('select[name="options[][type]"]').val(opt.type || 'checkbox');

                const isRequiredCheckbox = newOptionRow.find('input[name="options[][is_required_cb]"]');
                const isRequiredHidden = newOptionRow.find('input[name="options[][is_required]"]');
                if (opt.is_required && (opt.is_required === '1' || opt.is_required === 1 || opt.is_required === true) ) {
                    isRequiredCheckbox.prop('checked', true);
                    isRequiredHidden.val('1');
                } else {
                    isRequiredCheckbox.prop('checked', false);
                    isRequiredHidden.val('0');
                }

                newOptionRow.find('select[name="options[][price_impact_type]"]').val(opt.price_impact_type || '');
                newOptionRow.find('input[name="options[][price_impact_value]"]').val(opt.price_impact_value || '');

                let optionValuesText = '';
                // opt.option_values is expected to be a string (JSON) from server for select/radio
                if (opt.option_values) {
                    if (typeof opt.option_values === 'string') {
                         try { // Attempt to parse then stringify to ensure it's well-formed and consistently formatted for display
                            const parsedValues = JSON.parse(opt.option_values);
                            optionValuesText = JSON.stringify(parsedValues, null, 2); // Pretty print
                        } catch (e) { // If not valid JSON, display as is (might indicate error or plain text for other types)
                            optionValuesText = opt.option_values;
                        }
                    } else if (typeof opt.option_values === 'object') { // Should not happen if PHP sends JSON string
                        optionValuesText = JSON.stringify(opt.option_values, null, 2);
                    }
                }
                newOptionRow.find('textarea[name="options[][option_values]"]').val(optionValuesText);
                newOptionRow.find('input[name="options[][sort_order]"]').val(opt.sort_order || optionIndex);
                optionsListContainer.append(newOptionRow);
                toggleOptionDetailFields(newOptionRow); // Call after appending and populating
            });
        } else if (!service.service_id) { // New service
             optionsListContainer.html('<p><em>' + mobooking_services_params.i18n.save_service_before_options + '</em></p>');
        } else { // Existing service, no options
            optionsListContainer.html('<p><em>' + (mobooking_services_params.i18n.no_options_for_service || 'No options configured for this service yet. Click "Add Option" to create one.') + '</em></p>');
        }
    }

    // Show/hide option_values and price_impact_value fields based on selections
    function toggleOptionDetailFields($row) {
        const type = $row.find('.mobooking-option-type').val();
        if (type === 'select' || type === 'radio') {
            $row.find('.mobooking-option-values-field').slideDown();
        } else {
            $row.find('.mobooking-option-values-field').slideUp();
        }
        const priceType = $row.find('.mobooking-option-price-type').val();
        if (priceType && priceType !== '') {
            $row.find('.mobooking-option-price-value-field').slideDown();
        } else {
            $row.find('.mobooking-option-price-value-field').slideUp();
            $row.find('input[name="options[][price_impact_value]"]').val(''); // Clear value if type is none
        }
    }

    optionsListContainer.on('change', '.mobooking-option-type, .mobooking-option-price-type', function() {
        toggleOptionDetailFields($(this).closest('.mobooking-service-option-row'));
    });

    $('#mobooking-add-service-option-btn').on('click', function() {
        if ($(this).is(':disabled')) return;

        if (optionsListContainer.find('p').length && optionsListContainer.children().length === 1) {
            optionsListContainer.empty(); // Clear "No options..." message if present
        }

        optionIndex++;
        const newOptionRow = $(optionTemplateHtml);
        optionsListContainer.append(newOptionRow);
        toggleOptionDetailFields(newOptionRow);
        newOptionRow.find('input[name="options[][sort_order]"]').val(optionsListContainer.children('.mobooking-service-option-row').length);
        newOptionRow.find('input[name="options[][name]"]').focus();
    });

    optionsListContainer.on('click', '.mobooking-remove-service-option-btn', function() {
        $(this).closest('.mobooking-service-option-row').remove();
        // Re-calculate sort orders
        optionsListContainer.find('.mobooking-service-option-row').each(function(idx, el) {
            $(el).find('input[name="options[][sort_order]"]').val(idx + 1);
        });
        if (optionsListContainer.children('.mobooking-service-option-row').length === 0) { // Check if only .mobooking-service-option-row are zero
             optionsListContainer.html('<p><em>' + (mobooking_services_params.i18n.no_options_prompt || 'Click "Add Option" to create service options.') + '</em></p>');
        }
    });

    // Handle 'is_required' checkbox to hidden field sync
    optionsListContainer.on('change', 'input[name="options[][is_required_cb]"]', function() {
        const $checkbox = $(this);
        const $hiddenInput = $checkbox.closest('.mobooking-service-option-row').find('input[name="options[][is_required]"]');
        $hiddenInput.val($checkbox.is(':checked') ? '1' : '0');
    });


    function loadServices() {
        servicesListContainer.html('<p>' + mobooking_services_params.i18n.loading_services + '</p>');
        $.ajax({
            url: mobooking_services_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_services',
                nonce: mobooking_services_params.nonce
            },
            success: function(response) {
                servicesListContainer.empty();
                servicesDataCache = [];
                if (response.success && response.data.length) {
                    servicesDataCache = response.data;
                    response.data.forEach(function(service) {
                        const cleanService = {};
                        // Sanitize all string properties before rendering
                        for (const key in service) {
                            if (typeof service[key] === 'string') {
                                cleanService[key] = sanitizeHTML(service[key]);
                            } else {
                                cleanService[key] = service[key];
                            }
                        }
                        cleanService.service_id = parseInt(service.service_id, 10);
                        cleanService.price = parseFloat(service.price).toFixed(2);

                        servicesListContainer.append(renderTemplate('#mobooking-service-item-template', cleanService));
                    });
                } else if (response.success && response.data.length === 0) {
                    servicesListContainer.html('<p>' + mobooking_services_params.i18n.no_services_found + '</p>');
                } else {
                    const message = (response.data && response.data.message) ? sanitizeHTML(response.data.message) : mobooking_services_params.i18n.error_loading_services;
                    servicesListContainer.html('<p>' + message + '</p>');
                }
            },
            error: function() {
                servicesListContainer.html('<p>' + mobooking_services_params.i18n.error_loading_services + '</p>');
            }
        });
    }

    // Delegated Edit Service Button Click
    servicesListContainer.on('click', '.mobooking-edit-service-btn', function() {
        const serviceId = parseInt($(this).data('id'), 10);
        const serviceToEdit = servicesDataCache.find(s => parseInt(s.service_id, 10) === serviceId);

        if (serviceToEdit) {
            serviceFormTitle.text(mobooking_services_params.i18n.edit_service);
            populateForm(serviceToEdit); // populateForm now uses the full service object
            feedbackDiv.empty().hide();
            serviceFormContainer.slideDown();
            $('html, body').animate({ scrollTop: serviceFormContainer.offset().top - 50 }, 300);
        } else {
            // This might happen if cache is stale or item was removed.
            // Optionally, fetch the single service details via AJAX here.
            alert(mobooking_services_params.i18n.error_finding_service || 'Error: Could not find service data. Please refresh.');
        }
    });

    // Delegated Delete Service
    servicesListContainer.on('click', '.mobooking-delete-service-btn', function() {
        const serviceId = $(this).data('id');
        if (confirm(mobooking_services_params.i18n.confirm_delete_service)) {
            $.ajax({
                url: mobooking_services_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mobooking_delete_service',
                    nonce: mobooking_services_params.nonce,
                    service_id: serviceId
                },
                success: function(response) {
                    if (response.success) {
                        loadServices(); // Refresh list
                        // Display a temporary success message not tied to the form
                        // For example, inject it above the list or use a toast notification library
                         $('#mobooking-add-new-service-btn').after('<div class="mobooking-global-feedback success" style="padding:10px; margin:10px 0; background-color:#d4edda; border-color:#c3e6cb; color:#155724; border-radius:4px;">' + sanitizeHTML(response.data.message) + '</div>');
                        setTimeout(function() { $('.mobooking-global-feedback').fadeOut(500, function() { $(this).remove(); }); }, 3000);

                    } else {
                        alert(response.data.message || mobooking_services_params.i18n.error_deleting_service);
                    }
                },
                error: function() {
                    alert(mobooking_services_params.i18n.error_deleting_service);
                }
            });
        }
    });

    // Show Add New Service form
    $('#mobooking-add-new-service-btn').on('click', function() {
        serviceForm[0].reset();
        serviceIdField.val(''); // Crucial for Add vs Edit logic
        serviceFormTitle.text(mobooking_services_params.i18n.add_new_service);
        feedbackDiv.empty().hide();
        optionsListContainer.html('<p><em>' + (mobooking_services_params.i18n.save_service_before_options || 'Save service before adding options.') + '</em></p>');
        addServiceOptionBtn.prop('disabled', true);
        serviceFormContainer.slideDown();
         $('html, body').animate({ scrollTop: serviceFormContainer.offset().top - 50 }, 300);
    });

    // Cancel form
    $('#mobooking-cancel-service-form').on('click', function() {
        serviceFormContainer.slideUp();
        feedbackDiv.empty().hide();
    });

    // Form Submission (Add/Update)
    serviceForm.on('submit', function(e) {
        e.preventDefault();
        feedbackDiv.empty().removeClass('success error').hide();

        let dataToSend = {};
        $(this).serializeArray().forEach(function(item) {
            dataToSend[item.name] = item.value;
        });
        dataToSend.action = 'mobooking_save_service';
        dataToSend.nonce = mobooking_services_params.nonce;
        // service_id is already part of serializeArray

        // Collect Service Options
        let service_options = [];
        optionsListContainer.find('.mobooking-service-option-row').each(function(idx) {
            const $row = $(this);
            const option = {
                option_id: $row.find('input[name="options[][option_id]"]').val() || '',
                name: $row.find('input[name="options[][name]"]').val(),
                description: $row.find('textarea[name="options[][description]"]').val(),
                type: $row.find('select[name="options[][type]"]').val(),
                is_required: $row.find('input[name="options[][is_required]"]').val(), // Value from hidden field
                price_impact_type: $row.find('select[name="options[][price_impact_type]"]').val(),
                price_impact_value: $row.find('input[name="options[][price_impact_value]"]').val() || null,
                option_values: $row.find('textarea[name="options[][option_values]"]').val(),
                sort_order: (idx + 1) // Re-calculate sort order on submit
            };
            // Basic validation for option name: only include if name is present
            if (option.name && option.name.trim() !== '') {
                // For select/radio, ensure option_values is valid JSON if provided
                if ((option.type === 'select' || option.type === 'radio') && option.option_values.trim() !== '') {
                    try {
                        JSON.parse(option.option_values.trim());
                    } catch (e) {
                        feedbackDiv.text((mobooking_services_params.i18n.invalid_json_for_option || 'Invalid JSON in Option Values for: ') + sanitizeHTML(option.name)).removeClass('success').addClass('error').show();
                        throw new Error('Invalid JSON for option: ' + option.name); // Stop submission by throwing error
                    }
                }
                service_options.push(option);
            } else if (option.option_id) { // If it's an existing option without a name, it implies deletion or error.
                // For simplicity, we'll filter out nameless options. If it had an ID, it will be removed by the sync process.
            }
        });
        dataToSend.service_options = JSON.stringify(service_options);


        if (!dataToSend.name.trim()) {
            feedbackDiv.text(mobooking_services_params.i18n.name_required || 'Service name is required.').addClass('error').show(); return;
        }
        if (isNaN(parseFloat(dataToSend.price)) || parseFloat(dataToSend.price) < 0) {
            feedbackDiv.text(mobooking_services_params.i18n.valid_price_required || 'Valid price is required.').addClass('error').show(); return;
        }
        if (isNaN(parseInt(dataToSend.duration)) || parseInt(dataToSend.duration) <= 0) {
            feedbackDiv.text(mobooking_services_params.i18n.valid_duration_required || 'Valid positive duration is required.').addClass('error').show(); return;
        }

        const submitButton = $(this).find('button[type="submit"]');
        const originalButtonText = submitButton.text();
        submitButton.prop('disabled', true).text(mobooking_services_params.i18n.saving || 'Saving...');

        $.ajax({
            url: mobooking_services_params.ajax_url,
            type: 'POST',
            data: dataToSend,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    feedbackDiv.text(response.data.message).removeClass('error').addClass('success').show();
                    serviceFormContainer.slideUp(function() {
                        loadServices();
                    });
                     setTimeout(function() { feedbackDiv.fadeOut(500, function() { $(this).empty(); }); }, 3000);
                } else {
                    feedbackDiv.text(response.data.message || mobooking_services_params.i18n.error_saving_service).removeClass('success').addClass('error').show();
                }
            },
            error: function() {
                feedbackDiv.text(mobooking_services_params.i18n.error_saving_service).removeClass('success').addClass('error').show();
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    // Initial load
    if (servicesListContainer.length) {
        loadServices();
    }
});
