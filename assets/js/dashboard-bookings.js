jQuery(document).ready(function($) {
    'use strict';

    const bookingsListContainer = $('#mobooking-bookings-list-container');
    const paginationContainer = $('#mobooking-bookings-pagination-container');
    const filterForm = $('#mobooking-bookings-filter-form');
    const bookingItemTemplate = $('#mobooking-booking-item-template').html();

    // Store current filters and page
    let currentFilters = {
        status_filter: '',
        date_from_filter: '',
        date_to_filter: '',
        search_query: '',
        paged: 1,
        limit: 20 // Default items per page, can be made configurable
    };

    // Basic XSS protection for display
    function sanitizeHTML(str) {
        if (typeof str !== 'string') return '';
        var temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }

    function renderTemplate(templateHtml, data) {
        let template = templateHtml;
        for (const key in data) {
            const value = (typeof data[key] === 'string' || typeof data[key] === 'number') ? data[key] : '';
            template = template.replace(new RegExp('<%=\\s*' + key + '\\s*%>', 'g'), sanitizeHTML(String(value)));
        }
        return template;
    }

    function loadBookings(page = 1) {
        currentFilters.paged = page;
        bookingsListContainer.html('<p>' + (mobooking_bookings_params.i18n.loading_bookings || 'Loading bookings...') + '</p>');
        paginationContainer.empty();

        let ajaxData = {
            action: 'mobooking_get_tenant_bookings',
            nonce: mobooking_bookings_params.nonce,
            ...currentFilters // Spread all filter values
        };

        $.ajax({
            url: mobooking_bookings_params.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                bookingsListContainer.empty();
                if (response.success && response.data.bookings && response.data.bookings.length) {
                    response.data.bookings.forEach(function(booking) {
                        let bookingDataForTemplate = {...booking};
                        // Format data for display
                        bookingDataForTemplate.total_price_formatted = parseFloat(booking.total_price).toFixed(2); // Add currency from settings later
                        bookingDataForTemplate.status_display = mobooking_bookings_params.statuses[booking.status] || booking.status;
                        // Basic date formatting, consider moment.js or similar for complex needs
                        try {
                            const dateObj = new Date(booking.created_at);
                            bookingDataForTemplate.created_at_formatted = dateObj.toLocaleDateString() + ' ' + dateObj.toLocaleTimeString();
                        } catch(e) { bookingDataForTemplate.created_at_formatted = booking.created_at; }

                        bookingsListContainer.append(renderTemplate(bookingItemTemplate, bookingDataForTemplate));
                    });
                    renderPagination(response.data.total_count, response.data.per_page, response.data.current_page);
                } else if (response.success) {
                    bookingsListContainer.html('<p>' + (mobooking_bookings_params.i18n.no_bookings_found || 'No bookings found.') + '</p>');
                } else {
                    bookingsListContainer.html('<p>' + (response.data.message || mobooking_bookings_params.i18n.error_loading_bookings || 'Error loading bookings.') + '</p>');
                }
            },
            error: function() {
                bookingsListContainer.html('<p>' + (mobooking_bookings_params.i18n.error_loading_bookings || 'Error loading bookings.') + '</p>');
            }
        });
    }

    function renderPagination(totalItems, itemsPerPage, currentPage) {
        paginationContainer.empty();
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        if (totalPages <= 1) return;

        let paginationHtml = '<ul class="mobooking-pagination">';
        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `<li class="${i === currentPage ? 'active' : ''}"><a href="#" data-page="${i}">${i}</a></li>`;
        }
        paginationHtml += '</ul>';
        paginationContainer.html(paginationHtml);
    }

    // Filter form submission
    filterForm.on('submit', function(e) {
        e.preventDefault();
        currentFilters.status_filter = $('#mobooking-status-filter').val();
        currentFilters.date_from_filter = $('#mobooking-date-from-filter').val();
        currentFilters.date_to_filter = $('#mobooking-date-to-filter').val();
        currentFilters.search_query = $('#mobooking-search-query').val();
        loadBookings(1); // Reset to page 1 on new filter
    });

    // Clear filters
    $('#mobooking-clear-filters-btn').on('click', function() {
        filterForm[0].reset();
        currentFilters.status_filter = '';
        currentFilters.date_from_filter = '';
        currentFilters.date_to_filter = '';
        currentFilters.search_query = '';
        loadBookings(1);
    });

    // Pagination click
    paginationContainer.on('click', 'a', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        loadBookings(page);
    });

    // Datepicker initialization
    if (typeof $.fn.datepicker === 'function') {
        $('.mobooking-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    } else {
        console.warn('jQuery UI Datepicker not available for booking filters.');
         $('.mobooking-datepicker').attr('type', 'date'); // Fallback to native
    }


    // Modal elements
    const detailsModal = $('#mobooking-booking-details-modal');
    const modalCloseBtn = detailsModal.find('.mobooking-modal-close');
    const modalBookingRef = $('#modal-booking-ref');
    const modalStatusSelect = $('#modal-booking-status-select');
    const modalSaveStatusBtn = $('#modal-save-status-btn');
    const modalStatusFeedback = $('#modal-status-feedback');
    const modalCustomerName = $('#modal-customer-name');
    const modalCustomerEmail = $('#modal-customer-email');
    const modalCustomerPhone = $('#modal-customer-phone');
    const modalServiceAddress = $('#modal-service-address');
    const modalBookingDate = $('#modal-booking-date');
    const modalBookingTime = $('#modal-booking-time');
    const modalSpecialInstructions = $('#modal-special-instructions');
    const modalServicesItemsList = $('#modal-services-items-list');
    const modalDiscountAmount = $('#modal-discount-amount');
    const modalFinalTotal = $('#modal-final-total');
    const modalCurrentBookingIdField = $('#modal-current-booking-id');

    // Modal Handling
    modalCloseBtn.on('click', function() {
        detailsModal.fadeOut();
    });

    $(window).on('click', function(event) {
        if ($(event.target).is(detailsModal)) {
            detailsModal.fadeOut();
        }
    });

    function formatServiceItemsForModal(items) {
        if (!items || items.length === 0) {
            return '<p>' + (mobooking_bookings_params.i18n.no_items_in_booking || 'No items in this booking.') + '</p>';
        }
        let html = '<ul>';
        items.forEach(function(item) {
            html += `<li><strong>${sanitizeHTML(item.service_name)}</strong> (Qty: ${item.quantity || 1}) - Price: ${parseFloat(item.item_total_price).toFixed(2)}`;
            if (item.selected_options && item.selected_options.length > 0) {
                html += '<ul class="option-list">';
                item.selected_options.forEach(function(opt) {
                    html += `<li><em>${sanitizeHTML(opt.name)}:</em> ${sanitizeHTML(opt.value)} ${parseFloat(opt.price_impact) !== 0 ? '(' + (parseFloat(opt.price_impact) > 0 ? '+' : '') + parseFloat(opt.price_impact).toFixed(2) + ')' : ''}</li>`;
                });
                html += '</ul>';
            }
            html += '</li>';
        });
        html += '</ul>';
        return html;
    }


    // View Details button
    bookingsListContainer.on('click', '.mobooking-view-booking-details-btn', function() {
        const bookingId = $(this).data('booking-id');
        modalCurrentBookingIdField.val(bookingId); // Store for status update
        modalStatusFeedback.empty().removeClass('success error');
        modalServicesItemsList.html('<p>' + (mobooking_bookings_params.i18n.loading_details || 'Loading details...') + '</p>');
        detailsModal.fadeIn();

        $.ajax({
            url: mobooking_bookings_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_tenant_booking_details',
                nonce: mobooking_bookings_params.nonce,
                booking_id: bookingId
            },
            success: function(response) {
                if (response.success && response.data) {
                    const booking = response.data;
                    modalBookingRef.text(booking.booking_reference);
                    modalCustomerName.text(booking.customer_name);
                    modalCustomerEmail.text(booking.customer_email);
                    modalCustomerPhone.text(booking.customer_phone);
                    modalServiceAddress.html(sanitizeHTML(booking.service_address).replace(/\n/g, '<br>'));
                    modalBookingDate.text(booking.booking_date);
                    modalBookingTime.text(booking.booking_time);
                    modalSpecialInstructions.html(booking.special_instructions ? sanitizeHTML(booking.special_instructions).replace(/\n/g, '<br>') : (mobooking_bookings_params.i18n.none || 'None'));

                    modalServicesItemsList.html(formatServiceItemsForModal(booking.items));

                    modalDiscountAmount.text(parseFloat(booking.discount_amount).toFixed(2));
                    modalFinalTotal.text(parseFloat(booking.total_price).toFixed(2));

                    // Populate status dropdown
                    modalStatusSelect.empty();
                    const statuses = mobooking_bookings_params.statuses || {};
                    for (const key in statuses) {
                        if (key === "") continue; // Skip "All Statuses" option
                        modalStatusSelect.append($('<option>', { value: key, text: statuses[key] }));
                    }
                    modalStatusSelect.val(booking.status);

                } else {
                    modalServicesItemsList.html('<p>' + (response.data.message || mobooking_bookings_params.i18n.error_loading_details || 'Error loading details.') + '</p>');
                }
            },
            error: function() {
                 modalServicesItemsList.html('<p>' + (mobooking_bookings_params.i18n.error_loading_details || 'Error loading details.') + '</p>');
            }
        });
    });

    // Save Status button in Modal
    modalSaveStatusBtn.on('click', function() {
        const bookingId = modalCurrentBookingIdField.val();
        const newStatus = modalStatusSelect.val();
        if (!bookingId || !newStatus) {
            modalStatusFeedback.text(mobooking_bookings_params.i18n.error_status_update || 'Invalid data for status update.').addClass('error').show();
            return;
        }

        modalStatusFeedback.text(mobooking_bookings_params.i18n.updating_status || 'Updating...').removeClass('success error').show();
        $(this).prop('disabled', true);

        $.ajax({
            url: mobooking_bookings_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_update_booking_status',
                nonce: mobooking_bookings_params.nonce,
                booking_id: bookingId,
                new_status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    modalStatusFeedback.text(response.data.message).addClass('success').removeClass('error').show();
                    loadBookings(currentFilters.paged); // Refresh the main list
                } else {
                    modalStatusFeedback.text(response.data.message || mobooking_bookings_params.i18n.error_status_update).addClass('error').removeClass('success').show();
                }
            },
            error: function() {
                 modalStatusFeedback.text(mobooking_bookings_params.i18n.error_status_update_ajax || 'AJAX error updating status.').addClass('error').removeClass('success').show();
            },
            complete: function() {
                modalSaveStatusBtn.prop('disabled', false);
                setTimeout(function() { modalStatusFeedback.fadeOut(); }, 3000);
            }
        });
    });


    // Initial load is now handled by PHP.
    // loadBookings(); // Removed

    // --- CRUD Operations ---

    // CREATE BOOKING (Placeholder - Needs UI: Add Booking Button & Modal)
    // This requires a new modal and form similar to the public booking form.
    // For now, this is a placeholder. A button with id="mobooking-add-booking-btn" would trigger this.
    $('#mobooking-add-booking-btn').on('click', function() {
        alert('Add New Booking functionality to be implemented. This will require a new form/modal.');
        // 1. Show a modal with form fields for:
        //    - Customer Details (name, email, phone, address)
        //    - Service Selection (complex: needs service list, options, pricing logic)
        //    - Booking Date/Time
        //    - Special Instructions
        // 2. On submit, gather data into a payload matching `create_booking` in PHP.
        //    const bookingData = {
        //        customer_details: { customer_name: '...', ... },
        //        selected_services: [ { service_id: ..., configured_options: [...] } ],
        //        pricing: { subtotal: ..., discount: ..., final_total: ... }, // Server will recalculate
        //        // ... any other fields required by create_booking
        //    };
        // 3. AJAX call:
        //    $.ajax({
        //        url: mobooking_bookings_params.ajax_url,
        //        type: 'POST',
        //        data: {
        //            action: 'mobooking_create_dashboard_booking',
        //            nonce: mobooking_bookings_params.nonce,
        //            booking_data: JSON.stringify(bookingData)
        //        },
        //        success: function(response) { if (response.success) loadBookings(1); /* or prepend */ },
        //        error: function() { /* handle error */ }
        //    });
    });


    // UPDATE BOOKING FIELDS (in existing modal)
    let isEditMode = false;
    const modalEditButton = $('<button class="button" id="modal-edit-fields-btn" style="margin-left: 10px;">' + (mobooking_bookings_params.i18n.edit_fields || 'Edit Fields') + '</button>');
    const modalSaveChangesButton = $('<button class="button button-primary" id="modal-save-fields-btn" style="margin-left: 10px; display:none;">' + (mobooking_bookings_params.i18n.save_changes || 'Save Changes') + '</button>');
    const modalCancelEditButton = $('<button class="button" id="modal-cancel-edit-btn" style="margin-left: 5px; display:none;">' + (mobooking_bookings_params.i18n.cancel || 'Cancel') + '</button>');

    // Insert new buttons after the status save button or in a specific place
    modalSaveStatusBtn.after(modalCancelEditButton).after(modalSaveChangesButton).after(modalEditButton);

    function toggleEditMode(enable) {
        isEditMode = enable;
        modalEditButton.toggle(!enable);
        modalSaveChangesButton.toggle(enable);
        modalCancelEditButton.toggle(enable);
        modalSaveStatusBtn.toggle(!enable); // Hide status save when editing other fields
        modalStatusSelect.prop('disabled', enable); // Disable status select in field edit mode

        // Fields that can be edited (add more as needed)
        const fields = {
            'modal-customer-name': modalCustomerName.text(),
            'modal-customer-email': modalCustomerEmail.text(),
            'modal-customer-phone': modalCustomerPhone.text(),
            'modal-service-address': modalServiceAddress.html().replace(/<br\s*\/?>/gi, '\n'), // Convert <br> to newline for textarea
            'modal-booking-date': modalBookingDate.text(), // Consider datepicker
            'modal-booking-time': modalBookingTime.text(), // Consider timepicker
            'modal-special-instructions': modalSpecialInstructions.html().replace(/<br\s*\/?>/gi, '\n'),
        };

        for (const id in fields) {
            const element = $('#' + id);
            if (enable) {
                let inputType = 'text';
                if (id === 'modal-service-address' || id === 'modal-special-instructions') {
                    inputType = 'textarea';
                }
                const currentVal = fields[id];
                let inputHtml = inputType === 'textarea' ?
                    `<textarea id="${id}-input" class="widefat" style="min-height:60px;">${sanitizeHTML(currentVal)}</textarea>` :
                    `<input type="${inputType}" id="${id}-input" value="${sanitizeHTML(currentVal)}" class="widefat" />`;

                if (id === 'modal-booking-date' && typeof $.fn.datepicker === 'function') {
                     element.html(inputHtml);
                     $(`#${id}-input`).datepicker({ dateFormat: 'yy-mm-dd' });
                } else if (id === 'modal-booking-date') {
                     element.html(inputHtml); // native date picker might be `type="date"`
                     $(`#${id}-input`).prop('type', 'date');
                }
                else {
                    element.html(inputHtml);
                }
            } else {
                // Restore original text (or updated text if save was successful)
                // This part will be enhanced by saving the original values or re-fetching
                element.html(fields[id].replace(/\n/g, '<br>')); // Display <br> again
            }
        }
    }

    modalEditButton.on('click', function() {
        toggleEditMode(true);
    });

    modalCancelEditButton.on('click', function() {
        toggleEditMode(false);
        // TODO: Restore original values if no save occurred. Need to store them when edit mode starts.
        // For now, it just toggles back, potentially losing user input if they didn't save.
        // A proper implementation would re-populate with data fetched when modal opened or last saved.
        // This can be done by re-triggering the AJAX call or storing initial modal data.
        // For simplicity here, we'll just revert to text, which might not be original if user typed then cancelled.
        // The best is to re-fetch or use stored original data.
         const bookingId = modalCurrentBookingIdField.val();
         // Re-fetch to reset fields (simplest way to handle "cancel" without complex state management)
         if(bookingId) viewBookingDetails(bookingId, false); // false to not re-enable edit mode
    });

    modalSaveChangesButton.on('click', function() {
        const bookingId = modalCurrentBookingIdField.val();
        if (!bookingId) return;

        const fieldsToUpdate = {
            customer_name: $('#modal-customer-name-input').val(),
            customer_email: $('#modal-customer-email-input').val(),
            customer_phone: $('#modal-customer-phone-input').val(),
            service_address: $('#modal-service-address-input').val(),
            booking_date: $('#modal-booking-date-input').val(),
            booking_time: $('#modal-booking-time-input').val(),
            special_instructions: $('#modal-special-instructions-input').val(),
        };

        modalStatusFeedback.text(mobooking_bookings_params.i18n.saving_changes || 'Saving...').removeClass('success error').show();
        $(this).prop('disabled', true);

        $.ajax({
            url: mobooking_bookings_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_update_dashboard_booking_fields',
                nonce: mobooking_bookings_params.nonce,
                booking_id: bookingId,
                fields_to_update: JSON.stringify(fieldsToUpdate)
            },
            success: function(response) {
                if (response.success && response.data.booking_data) {
                    modalStatusFeedback.text(response.data.message).addClass('success').show();
                    toggleEditMode(false);
                    // Update displayed fields in modal with response.data.booking_data
                    populateModalWithBookingData(response.data.booking_data);
                    loadBookings(currentFilters.paged); // Refresh the main list to reflect changes
                } else {
                    modalStatusFeedback.text(response.data.message || 'Error saving changes.').addClass('error').show();
                }
            },
            error: function() {
                modalStatusFeedback.text('AJAX error saving changes.').addClass('error').show();
            },
            complete: function() {
                modalSaveChangesButton.prop('disabled', false);
                setTimeout(function() { modalStatusFeedback.fadeOut(); }, 3000);
            }
        });
    });

    // DELETE BOOKING
    bookingsListContainer.on('click', '.mobooking-delete-booking-btn', function(e) {
        e.preventDefault();
        const bookingId = $(this).data('booking-id');
        const bookingRef = $(this).closest('.mobooking-booking-item').find('h3').text(); // Get ref for confirm message

        if (confirm( (mobooking_bookings_params.i18n.confirm_delete || 'Are you sure you want to delete booking %s?').replace('%s', bookingRef) )) {
            $.ajax({
                url: mobooking_bookings_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mobooking_delete_dashboard_booking',
                    nonce: mobooking_bookings_params.nonce,
                    booking_id: bookingId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message); // Or use a less obtrusive notification
                        loadBookings(currentFilters.paged); // Reload current page
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('AJAX error deleting booking.');
                }
            });
        }
    });

    // Helper to populate modal (used after initial load and after field update)
    function populateModalWithBookingData(booking) {
        modalBookingRef.text(booking.booking_reference);
        modalCustomerName.text(booking.customer_name);
        modalCustomerEmail.text(booking.customer_email);
        modalCustomerPhone.text(booking.customer_phone || (mobooking_bookings_params.i18n.not_provided || 'N/A') );
        modalServiceAddress.html(sanitizeHTML(booking.service_address || '').replace(/\n/g, '<br>'));
        modalBookingDate.text(booking.booking_date); // Consider formatting
        modalBookingTime.text(booking.booking_time);
        modalSpecialInstructions.html(booking.special_instructions ? sanitizeHTML(booking.special_instructions).replace(/\n/g, '<br>') : (mobooking_bookings_params.i18n.none || 'None'));

        modalServicesItemsList.html(formatServiceItemsForModal(booking.items));

        modalDiscountAmount.text(parseFloat(booking.discount_amount || 0).toFixed(2));
        modalFinalTotal.text(parseFloat(booking.total_price).toFixed(2));

        modalStatusSelect.empty();
        const statuses = mobooking_bookings_params.statuses || {};
        for (const key in statuses) {
            if (key === "") continue;
            modalStatusSelect.append($('<option>', { value: key, text: statuses[key] }));
        }
        modalStatusSelect.val(booking.status);
        modalCurrentBookingIdField.val(booking.booking_id);
    }

    // Modified View Details to use the helper and manage edit mode
    function viewBookingDetails(bookingId, enterEditMode = false) {
        modalCurrentBookingIdField.val(bookingId);
        modalStatusFeedback.empty().removeClass('success error');
        modalServicesItemsList.html('<p>' + (mobooking_bookings_params.i18n.loading_details || 'Loading details...') + '</p>');

        // Ensure edit mode is reset if not explicitly requested
        if (isEditMode && !enterEditMode) {
            toggleEditMode(false);
        }

        detailsModal.fadeIn();

        $.ajax({
            url: mobooking_bookings_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_tenant_booking_details',
                nonce: mobooking_bookings_params.nonce,
                booking_id: bookingId
            },
            success: function(response) {
                if (response.success && response.data) {
                    populateModalWithBookingData(response.data);
                    if (enterEditMode) {
                        toggleEditMode(true);
                    }
                } else {
                    modalServicesItemsList.html('<p>' + (response.data.message || mobooking_bookings_params.i18n.error_loading_details || 'Error loading details.') + '</p>');
                }
            },
            error: function() {
                 modalServicesItemsList.html('<p>' + (mobooking_bookings_params.i18n.error_loading_details || 'Error loading details.') + '</p>');
            }
        });
    }

    bookingsListContainer.on('click', '.mobooking-view-booking-details-btn', function() {
        const bookingId = $(this).data('booking-id');
        viewBookingDetails(bookingId);
    });

});
