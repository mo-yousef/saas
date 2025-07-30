jQuery(document).ready(function($) {
    'use strict';

    const bookingsListContainer = $('#mobooking-bookings-list-container');
    const paginationContainer = $('#mobooking-bookings-pagination-container');
    const filterForm = $('#mobooking-bookings-filter-form');
    const bookingItemTemplate = $('#mobooking-booking-item-template').html();
    const currencyCode = mobooking_bookings_params.currency_code || 'USD';

    // Store current filters and page
    let currentFilters = {
        status_filter: '',
        date_from_filter: '',
        date_to_filter: '',
        search_query: '',
        assigned_staff_id_filter: '', // New filter
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
                        bookingDataForTemplate.total_price_formatted = currencyCode + ' ' + parseFloat(booking.total_price).toFixed(2);
                        bookingDataForTemplate.status_display = mobooking_bookings_params.statuses[booking.status] || booking.status;

                        // Date and Time formatting for booking_date and booking_time
                        // These should match the format used in the initial PHP render if possible
                        // For simplicity, using toLocaleDateString and toLocaleTimeString
                        // A more robust solution might involve a date formatting library or server-side formatted strings
                        try {
                            const bookingDate = new Date(booking.booking_date + 'T' + booking.booking_time); // Combine date and time for proper Date object
                            bookingDataForTemplate.booking_date_formatted = bookingDate.toLocaleDateString();
                            bookingDataForTemplate.booking_time_formatted = bookingDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        } catch(e) {
                            bookingDataForTemplate.booking_date_formatted = booking.booking_date;
                            bookingDataForTemplate.booking_time_formatted = booking.booking_time;
                        }

                        // Construct details_page_url
                        // Assuming mobooking_bookings_params.bookings_page_base_url is like 'admin.php?page=mobooking'
                        // This might need to be passed from PHP if not already available.
                        // For now, constructing it based on a common pattern.
                        // A better approach is to have a bookings_page_url in mobooking_bookings_params.
                        let baseUrl = mobooking_bookings_params.bookings_page_url || 'admin.php?page=mobooking';
                        bookingDataForTemplate.details_page_url = baseUrl + '&action=view_booking&booking_id=' + booking.booking_id;


                        // The renderTemplate function expects all keys in the template to be present in bookingDataForTemplate
                        // Ensure all fields used in the template (`booking_reference`, `customer_name`, `customer_email`, `status`)
                        // are directly available on `booking` or added to `bookingDataForTemplate`.
                        // `created_at_formatted` is no longer in the table template.

                        const tableBody = bookingsListContainer.find('.mobooking-table-body');
                        if(tableBody.length === 0) {
                            bookingsListContainer.html('<div class="mobooking-table"><div class="mobooking-table-body"></div></div>');
                        }
                        tableBody.append(bookingItemTemplate.replace(/<%= booking_id %>/g, booking.booking_id)
                            .replace(/<%= booking_reference %>/g, booking.booking_reference)
                            .replace(/<%= customer_name %>/g, booking.customer_name)
                            .replace(/<%= customer_email %>/g, booking.customer_email)
                            .replace(/<%= booking_date_formatted %>/g, bookingDataForTemplate.booking_date_formatted)
                            .replace(/<%= booking_time_formatted %>/g, bookingDataForTemplate.booking_time_formatted)
                            .replace(/<%= assigned_staff_name %>/g, booking.assigned_staff_name || 'Unassigned')
                            .replace(/<%= total_price_formatted %>/g, bookingDataForTemplate.total_price_formatted)
                            .replace(/<%= status %>/g, booking.status)
                            .replace(/<%= status_display %>/g, bookingDataForTemplate.status_display)
                            .replace(/<%= icon_html %>/g, '') // Icons are now in the CSS
                            .replace(/<%= details_page_url %>/g, bookingDataForTemplate.details_page_url)
                        );
                    });
                    renderPagination(response.data.total_count, response.data.per_page, response.data.current_page);
                } else if (response.success) {
                    bookingsListContainer.html('<div class="mobooking-table"><div class="mobooking-table-body"><div class="mobooking-table-row no-items"><div class="mobooking-table-cell" colspan="7">' + (mobooking_bookings_params.i18n.no_bookings_found || 'No bookings found.') + '</div></div></div></div>');
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
        currentFilters.assigned_staff_id_filter = $('#mobooking-staff-filter').val(); // Get staff filter value
        loadBookings(1); // Reset to page 1 on new filter
    });

    // Clear filters
    $('#mobooking-clear-filters-btn').on('click', function() {
        filterForm[0].reset();
        currentFilters.status_filter = '';
        currentFilters.date_from_filter = '';
        currentFilters.date_to_filter = '';
        currentFilters.search_query = '';
        currentFilters.assigned_staff_id_filter = ''; // Clear staff filter
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

    // --- Modal related code removed ---
    // const detailsModal = ... (and all related modal variables and functions)
    // modalCloseBtn.on('click', ...)
    // $(window).on('click', ...) for modal
    // formatServiceItemsForModal(...)
    // bookingsListContainer.on('click', '.mobooking-view-booking-details-btn', ...) - this is now a link
    // modalSaveStatusBtn.on('click', ...)
    // Edit fields functionality (modalEditButton, toggleEditMode, etc.)
    // populateModalWithBookingData(...)
    // viewBookingDetails(...) function

    // Initial load is now handled by PHP.
    // loadBookings(); // Removed, as initial content is server-rendered. Filters will trigger AJAX.

    // --- CRUD Operations ---

    // CREATE BOOKING (Placeholder - Needs UI: Add Booking Button & Modal)
    // This functionality is not part of the current refactor to table view / single page details.
    // If it were to be re-implemented, it would likely involve a separate page or a new modal.
    $('#mobooking-add-booking-btn').on('click', function() {
        // For now, this button might link to a future "Add New Booking" page,
        // or trigger a new modal designed for creation if that's preferred.
        // The existing alert is a placeholder.
        alert('Add New Booking functionality to be implemented. This will require a new form/modal or a separate page.');
    });


    // DELETE BOOKING
    // Adjusted to work with table rows.
    bookingsListContainer.on('click', '.mobooking-delete-booking-btn', function(e) {
        e.preventDefault();
        const bookingId = $(this).data('booking-id');
        const $row = $(this).closest('tr');
        // Attempt to get booking reference from the first cell (td) in the row
        const bookingRef = $row.find('td:first').text();

        if (confirm( (mobooking_bookings_params.i18n.confirm_delete || 'Are you sure you want to delete booking %s?').replace('%s', bookingRef) )) {
            $.ajax({
                url: mobooking_bookings_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mobooking_delete_dashboard_booking',
                    nonce: mobooking_bookings_params.nonce, // Ensure this nonce is still valid and appropriate
                    booking_id: bookingId
                },
                success: function(response) {
                    if (response.success) {
                        // On successful deletion, remove the row from the table or reload.
                        // For simplicity, reloading the current view.
                        loadBookings(currentFilters.paged);
                        // Alternatively, to remove the row directly without full reload:
                        // $row.fadeOut(300, function() { $(this).remove(); });
                        // If removing directly, also update total counts if displayed.
                        if(mobooking_bookings_params.i18n.booking_deleted_successfully) {
                             alert(mobooking_bookings_params.i18n.booking_deleted_successfully);
                        } else {
                            alert(response.data.message || 'Booking deleted.');
                        }
                    } else {
                        alert('Error: ' + (response.data.message || mobooking_bookings_params.i18n.error_deleting_booking || 'Could not delete booking.'));
                    }
                },
                error: function() {
                    alert(mobooking_bookings_params.i18n.error_deleting_booking_ajax || 'AJAX error deleting booking.');
                }
            });
        }
    });

});
