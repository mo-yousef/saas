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

    const paginationContainer = $('#mobooking-services-pagination-container');
    const mainServiceItemTemplate = $('#mobooking-service-item-template').html();
    $('#mobooking-service-item-template').remove(); // Remove from DOM

    let currentServiceFilters = { // For storing current filter/pagination state
        paged: 1,
        per_page: 20, // Default, can be changed
        status_filter: '',
        category_filter: '',
        search_query: '',
        orderby: 'name',
        order: 'ASC'
    };
    let servicesDataCache = {}; // Cache for service details to populate edit form
    let optionIndex = 0;

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

    // Function to fetch and render services
    function fetchAndRenderServices(page = 1, filters = {}) {
        currentServiceFilters.paged = page;
        currentServiceFilters = { ...currentServiceFilters, ...filters };

        servicesListContainer.html('<p>' + (mobooking_services_params.i18n.loading_services || 'Loading services...') + '</p>');
        paginationContainer.empty();
        servicesDataCache = {}; // Clear cache on new list load

        let ajaxData = {
            action: 'mobooking_get_services',
            nonce: mobooking_services_params.nonce, // This should be localized
            ...currentServiceFilters
        };

        $.ajax({
            url: mobooking_services_params.ajax_url,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function(response) {
                servicesListContainer.empty();
                if (response.success && response.data.services && response.data.services.length) {
                    response.data.services.forEach(function(service) {
                        servicesDataCache[service.service_id] = service; // Cache for editing
                        let serviceDataForTemplate = { ...service };
                        serviceDataForTemplate.formatted_price = mobooking_services_params.currency_symbol + parseFloat(service.price).toFixed(2); // Basic formatting
                        serviceDataForTemplate.display_status = service.status.charAt(0).toUpperCase() + service.status.slice(1);

                        let itemHtml = mainServiceItemTemplate;
                        for (const key in serviceDataForTemplate) {
                            itemHtml = itemHtml.replace(new RegExp('<%=\\s*' + key + '\\s*%>', 'g'), sanitizeHTML(String(serviceDataForTemplate[key])));
                        }
                        servicesListContainer.append(itemHtml);
                    });
                    renderPagination(response.data.total_count, response.data.per_page, response.data.current_page);
                } else if (response.success) {
                    servicesListContainer.html('<p>' + (mobooking_services_params.i18n.no_services_found || 'No services found.') + '</p>');
                } else {
                    servicesListContainer.html('<p>' + (response.data.message || mobooking_services_params.i18n.error_loading_services || 'Error loading services.') + '</p>');
                }
            },
            error: function() {
                servicesListContainer.html('<p>' + (mobooking_services_params.i18n.error_loading_services || 'Error loading services.') + '</p>');
            }
        });
    }

    function renderPagination(totalItems, itemsPerPage, currentPage) {
        paginationContainer.empty();
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        if (totalPages <= 1) return;

        let paginationHtml = '<ul class="pagination-links">'; // WordPress uses .page-numbers
        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `<li style="display:inline; margin-right:5px;"><a href="#" data-page="${i}" class="page-numbers ${i === currentPage ? 'current' : ''}">${i}</a></li>`;
        }
        paginationHtml += '</ul>';
        paginationContainer.html(paginationHtml);
    }

    // Initial load is handled by PHP. Event handlers for pagination etc. are below.
    // Populate cache from initial PHP-rendered data
    if (typeof mobooking_initial_services_list_for_cache !== 'undefined' && Array.isArray(mobooking_initial_services_list_for_cache)) {
        mobooking_initial_services_list_for_cache.forEach(function(service) {
            if (service && service.service_id) {
                servicesDataCache[service.service_id] = service;
            }
        });
    }
    // fetchAndRenderServices(currentServiceFilters.paged, currentServiceFilters); // Not needed if PHP renders first page

    // Delegated Edit Service Button Click - Now fetches fresh data
    servicesListContainer.on('click', '.mobooking-edit-service-btn', function() {
        const serviceId = $(this).data('id');
        feedbackDiv.empty().hide(); // Clear previous form feedback

        // Show loading state or disable button
        const $editButton = $(this);
        const originalButtonText = $editButton.text();
        $editButton.prop('disabled', true).text(mobooking_services_params.i18n.loading_details || 'Loading...');

        $.ajax({
            url: mobooking_services_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_service_details',
                nonce: mobooking_services_params.nonce,
                service_id: serviceId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.service) {
                    servicesDataCache[serviceId] = response.data.service; // Update cache with fresh data
                    serviceFormTitle.text(mobooking_services_params.i18n.edit_service || 'Edit Service');
                    populateForm(response.data.service);
                    $('#mobooking-service-form-modal-backdrop').show();
                    serviceFormContainer.show();
                    $('body').addClass('mobooking-modal-open');
                } else {
                    alert(response.data.message || mobooking_services_params.i18n.error_fetching_service_details || 'Error: Could not fetch service details.');
                }
            },
            error: function() {
                alert(mobooking_services_params.i18n.error_ajax || 'AJAX error fetching service details.');
            },
            complete: function() {
                $editButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    // Delegated Delete Service
    servicesListContainer.on('click', '.mobooking-delete-service-btn', function() {
        const serviceId = $(this).data('id');
        const serviceName = $(this).closest('.mobooking-service-item').find('h3').text();
        if (confirm( (mobooking_services_params.i18n.confirm_delete_service || 'Are you sure you want to delete "%s"?').replace('%s', serviceName) )) {
            $.ajax({
                url: mobooking_services_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mobooking_delete_service',
                    nonce: mobooking_services_params.nonce, // Ensure this is available
                    service_id: serviceId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showGlobalFeedback(response.data.message || (mobooking_services_params.i18n.service_deleted || 'Service deleted.'), 'success');
                        // Remove item from DOM or reload list
                        fetchAndRenderServices(currentServiceFilters.paged, currentServiceFilters); // Reload current page
                    } else {
                        showGlobalFeedback(response.data.message || mobooking_services_params.i18n.error_deleting_service, 'error');
                    }
                },
                error: function() {
                     showGlobalFeedback(mobooking_services_params.i18n.error_deleting_service_ajax || 'AJAX error deleting service.', 'error');
                }
            });
        }
    });

    // Show Add New Service form
    $('#mobooking-add-new-service-btn').on('click', function() {
        // Reset form for new service, populateForm handles this.
        populateForm({ service_id: '', name: '', description: '', price: '0.00', duration: '30', category: '', icon: '', image_url: '', status: 'active', options: [] });
        serviceFormTitle.text(mobooking_services_params.i18n.add_new_service || 'Add New Service');
        feedbackDiv.empty().hide();
        // optionsListContainer is handled by populateForm
        addServiceOptionBtn.prop('disabled', true); // Disabled for new service until saved
        $('#mobooking-service-form-modal-backdrop').show();
        serviceFormContainer.show();
        $('body').addClass('mobooking-modal-open');
        $('#mobooking-service-name').focus();
    });

    // Cancel form - Modal interaction
    $('#mobooking-cancel-service-form').on('click', function() {
        serviceFormContainer.hide();
        $('#mobooking-service-form-modal-backdrop').hide();
        $('body').removeClass('mobooking-modal-open');
        feedbackDiv.empty().hide();
    });

    // Show global feedback message
    function showGlobalFeedback(message, type = 'info') {
        $('.mobooking-global-feedback').remove(); // Remove existing
        const feedbackHtml = `<div class="mobooking-global-feedback ${type}" style="padding:10px; margin:10px 0; border-radius:4px; background-color:${type === 'success' ? '#d4edda' : '#f8d7da'}; border-color:${type === 'success' ? '#c3e6cb' : '#f5c6cb'}; color:${type === 'success' ? '#155724' : '#721c24'};">${sanitizeHTML(message)}</div>`;
        $('#mobooking-add-new-service-btn').after(feedbackHtml);
        // Auto-remove after a few seconds
        setTimeout(function() { $('.mobooking-global-feedback').fadeOut(500, function() { $(this).remove(); }); }, 5000);
    }

    // Pagination click
    paginationContainer.on('click', 'a.page-numbers', function(e) {
        e.preventDefault();
        const page = $(this).data('page') || $(this).attr('href').split('paged=')[1]?.split('&')[0];
        if (page) {
            fetchAndRenderServices(parseInt(page), currentServiceFilters);
        }
    });

    // Form Submission (Add/Update)
    // The following is the correct, single submit handler for the form.
    // The duplicate, simpler one has been removed.
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
            url: mobooking_services_params.ajax_url, // Make sure mobooking_services_params is localized
            type: 'POST',
            data: dataToSend,
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.service) {
                    serviceFormContainer.hide();
                    $('#mobooking-service-form-modal-backdrop').hide();
                    $('body').removeClass('mobooking-modal-open');
                    showGlobalFeedback(response.data.message || (dataToSend.service_id ? mobooking_services_params.i18n.service_updated : mobooking_services_params.i18n.service_added), 'success');

                    // Dynamic list update
                    fetchAndRenderServices(dataToSend.service_id ? currentServiceFilters.paged : 1, currentServiceFilters);

                } else { // Error reported by server
                    feedbackDiv.text(response.data.message || mobooking_services_params.i18n.error_saving_service).removeClass('success').addClass('error').show();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) { // More detailed error
                console.error("Save Service AJAX Error:", textStatus, errorThrown, jqXHR.responseText);
                feedbackDiv.text(mobooking_services_params.i18n.error_saving_service_ajax || 'AJAX error saving service. Check console.').removeClass('success').addClass('error').show();
            },
            complete: function() {
                submitButton.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    // Initial setup:
    // PHP renders the initial list. JS handles interactions.
    // Make sure mobooking_services_params (especially nonce, ajax_url, i18n strings) is localized.
    if (typeof mobooking_services_params === 'undefined') {
        console.error('mobooking_services_params is not defined. Ensure it is localized via wp_localize_script.');
        // Provide default i18n to prevent crashes if params are missing.
        window.mobooking_services_params = { i18n: {}, currency_symbol: '$' };
    }


    // Close modal if clicking on backdrop
    $('#mobooking-service-form-modal-backdrop').on('click', function() {
        serviceFormContainer.hide();
        $(this).hide();
        $('body').removeClass('mobooking-modal-open');
        feedbackDiv.empty().hide();
    });

});
