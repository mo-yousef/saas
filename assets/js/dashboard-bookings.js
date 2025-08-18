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

    function renderTemplate(templateHtml, data) {
        let template = templateHtml;
        const noEscapeKeys = ['icon_html', 'delete_button_html'];
        for (const key in data) {
            let value = (typeof data[key] === 'string' || typeof data[key] === 'number') ? data[key] : '';
            if (!noEscapeKeys.includes(key)) {
                value = sanitizeHTML(String(value));
            }
            // A more robust regex to avoid replacing parts of other words
            template = template.replace(new RegExp('<%=\\s*' + key + '\\s*%>', 'g'), value);
        }
        return template;
    }

    function getTableHTML() {
        return `
            <div class="mobooking-table-responsive-wrapper">
                <table class="mobooking-table">
                    <thead>
                        <tr>
                            <th>${mobooking_bookings_params.i18n.ref || 'Ref'}</th>
                            <th>${mobooking_bookings_params.i18n.customer || 'Customer'}</th>
                            <th>${mobooking_bookings_params.i18n.booked_date || 'Booked Date'}</th>
                            <th>${mobooking_bookings_params.i18n.assigned_staff || 'Assigned Staff'}</th>
                            <th>${mobooking_bookings_params.i18n.total || 'Total'}</th>
                            <th>${mobooking_bookings_params.i18n.status || 'Status'}</th>
                            <th>${mobooking_bookings_params.i18n.actions || 'Actions'}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        `;
    }

    function loadBookings(page = 1) {
        currentFilters.paged = page;
        bookingsListContainer.html('<div class="mobooking-spinner"></div>'); // Show spinner
        paginationContainer.empty();

        let ajaxData = {
            action: 'mobooking_get_tenant_bookings',
            nonce: mobooking_bookings_params.nonce,
            ...currentFilters
        };

        // Show clear button if any filter is active
        const isFilterActive = currentFilters.status_filter || currentFilters.date_from_filter || currentFilters.date_to_filter || currentFilters.search_query || currentFilters.assigned_staff_id_filter;
        $('#mobooking-clear-filters-btn').toggle(!!isFilterActive);

        $.ajax({
            url: mobooking_bookings_params.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                bookingsListContainer.empty();
                if (response.success && response.data.bookings && response.data.bookings.length) {
                    bookingsListContainer.html(getTableHTML());
                    const tableBody = bookingsListContainer.find('tbody');
                    response.data.bookings.forEach(function(booking) {
                        let bookingDataForTemplate = {...booking};
                        bookingDataForTemplate.total_price_formatted = currencyCode + ' ' + parseFloat(booking.total_price).toFixed(2);
                        bookingDataForTemplate.status_display = mobooking_bookings_params.statuses[booking.status] || booking.status;

                        try {
                            const bookingDate = new Date(booking.booking_date + 'T' + booking.booking_time);
                            bookingDataForTemplate.booking_date_formatted = bookingDate.toLocaleDateString();
                            bookingDataForTemplate.booking_time_formatted = bookingDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                        } catch(e) {
                            bookingDataForTemplate.booking_date_formatted = booking.booking_date;
                            bookingDataForTemplate.booking_time_formatted = booking.booking_time;
                        }

                        let baseUrl = mobooking_bookings_params.bookings_page_url || 'admin.php?page=mobooking-bookings';
                        bookingDataForTemplate.details_page_url = baseUrl + '&action=view_booking&booking_id=' + booking.booking_id;

                        bookingDataForTemplate.icon_html = mobooking_bookings_params.icons[booking.status] || '';

                        bookingDataForTemplate.assigned_staff_name = booking.assigned_staff_name || (mobooking_bookings_params.i18n.unassigned || 'Unassigned');

                        bookingDataForTemplate.delete_button_html = '';
                        if (mobooking_dashboard_params && mobooking_dashboard_params.currentUserCanDeleteBookings) {
                            bookingDataForTemplate.delete_button_html = `<button class="btn btn-destructive btn-sm mobooking-delete-booking-btn" data-booking-id="${booking.booking_id}">${mobooking_bookings_params.i18n.delete_btn_text || 'Delete'}</button>`;
                        }

                        tableBody.append(renderTemplate(bookingItemTemplate, bookingDataForTemplate));
                    });
                    renderPagination(response.data.total_count, response.data.per_page, response.data.current_page);
                } else if (response.success) {
                    const noResultsHTML = `
                        <div class="mobooking-no-results-message">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            <h4>${mobooking_bookings_params.i18n.no_bookings_found || 'No bookings found'}</h4>
                            <p>${mobooking_bookings_params.i18n.try_different_filters || 'Try adjusting your filters or clearing them to see all bookings.'}</p>
                        </div>
                    `;
                    bookingsListContainer.html(noResultsHTML);
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

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Function to update filters and load bookings
    function applyFilters() {
        currentFilters.status_filter = $('#mobooking-status-filter').val();
        currentFilters.date_from_filter = $('#mobooking-date-from-filter').val();
        currentFilters.date_to_filter = $('#mobooking-date-to-filter').val();
        currentFilters.search_query = $('#mobooking-search-query').val();
        currentFilters.assigned_staff_id_filter = $('#mobooking-staff-filter').val();
        loadBookings(1);
    }

    // Event listeners for filters
    const debouncedApplyFilters = debounce(applyFilters, 500); // 500ms delay

    $('#mobooking-search-query').on('keyup', debouncedApplyFilters);
    $('#mobooking-status-filter, #mobooking-staff-filter').on('change', applyFilters);
    $('.mobooking-datepicker').on('change', applyFilters);

    // Initial filter form submission is now handled by the change events, but we keep this for the submit button as a fallback.
    filterForm.on('submit', function(e) {
        e.preventDefault();
        applyFilters();
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

    // Toggle more filters
    $('#mobooking-toggle-more-filters-btn').on('click', function() {
        const button = $(this);
        $('.mobooking-filters-secondary').slideToggle(function() {
            const text = $(this).is(':visible') ? (mobooking_bookings_params.i18n.less_filters || 'Less') : (mobooking_bookings_params.i18n.more_filters || 'More');
            button.find('.btn-text').text(text);
        });
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
    bookingsListContainer.on('click', '.mobooking-delete-booking-btn', function(e) {
        e.preventDefault();
        const bookingId = $(this).data('booking-id');
        const bookingRef = $(this).closest('tr').find('td:first').text();

        const dialog = new MoBookingDialog({
            title: 'Delete Booking',
            content: `<p>Are you sure you want to delete booking <strong>${bookingRef}</strong>? This action cannot be undone.</p>`,
            icon: 'trash',
            buttons: [
                {
                    label: 'Cancel',
                    class: 'secondary',
                    onClick: (dialog) => dialog.close()
                },
                {
                    label: 'Delete',
                    class: 'destructive',
                    onClick: (dialog) => {
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
                                    loadBookings(currentFilters.paged);
                                    // Assuming a global showAlert function exists for toasts
                                    if (window.showAlert) {
                                        window.showAlert(response.data.message || 'Booking deleted.', 'success');
                                    }
                                } else {
                                    if (window.showAlert) {
                                        window.showAlert('Error: ' + (response.data.message || 'Could not delete booking.'), 'error');
                                    }
                                }
                            },
                            error: function() {
                                if (window.showAlert) {
                                    window.showAlert('AJAX error deleting booking.', 'error');
                                }
                            },
                            complete: () => dialog.close()
                        });
                    }
                }
            ]
        });
        dialog.show();
    });

});
