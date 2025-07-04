jQuery(document).ready(function ($) {
    'use strict';

    const $tableBody = $('#the-list');
    const $paginationContainer = $('.mobooking-pagination');
    const $feedbackDiv = $('#mobooking-customers-feedback');

    // Filter elements
    const $searchInput = $('#mobooking-customer-search');
    const $statusFilter = $('#mobooking-customer-status-filter');
    const $applyFiltersButton = $('#mobooking-apply-customer-filters');
    const $resetFiltersButton = $('#mobooking-reset-customer-filters');

    // Sorting state
    let currentSortBy = 'full_name';
    let currentSortOrder = 'ASC';

    // Debounce function for search input
    function debounce(func, delay) {
        let timeout;
        return function (...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), delay);
        };
    }

    function displayFeedback(message, type = 'info') {
        $feedbackDiv.removeClass('notice-success notice-error notice-info notice-warning');
        $feedbackDiv.addClass('notice-' + type).show();
        $feedbackDiv.find('p').html(message);
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $feedbackDiv.fadeOut();
        }, 5000);
    }

    function fetchCustomers(page = 1) {
        $tableBody.html('<tr><td colspan="7">' + (mobooking_customers_params?.i18n?.loading_customers || 'Loading customers...') + '</td></tr>');

        const searchVal = $searchInput.val();
        const statusVal = $statusFilter.val();

        $.ajax({
            url: mobooking_customers_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_customers',
                nonce: mobooking_customers_params.nonce,
                page: page,
                per_page: mobooking_customers_params.per_page || 20,
                search: searchVal,
                status: statusVal,
                sort_by: currentSortBy,
                sort_order: currentSortOrder,
            },
            success: function (response) {
                if (response.success) {
                    renderTable(response.data.customers);
                    renderPagination(response.data.pagination);
                    if (response.data.customers.length === 0 && (searchVal || statusVal)) {
                         $tableBody.html('<tr><td colspan="7">' + (mobooking_customers_params?.i18n?.no_customers_found_filters || 'No customers found matching your criteria.') + '</td></tr>');
                    } else if (response.data.customers.length === 0) {
                        $tableBody.html('<tr><td colspan="7">' + (mobooking_customers_params?.i18n?.no_customers_yet || 'No customers found.') + '</td></tr>');
                    }
                } else {
                    displayFeedback(response.data?.message || (mobooking_customers_params?.i18n?.error_loading_customers || 'Error loading customers.'), 'error');
                    $tableBody.html('<tr><td colspan="7">' + (mobooking_customers_params?.i18n?.error_loading_customers || 'Error loading customers.') + '</td></tr>');
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error:", status, error, xhr.responseText);
                displayFeedback(mobooking_customers_params?.i18n?.error_ajax || 'An AJAX error occurred. Please try again.', 'error');
                $tableBody.html('<tr><td colspan="7">' + (mobooking_customers_params?.i18n?.error_ajax || 'AJAX error.') + '</td></tr>');
            }
        });
    }

    function renderTable(customers) {
        $tableBody.empty();
        if (customers.length === 0) {
            // This case should ideally be handled by the success callback in fetchCustomers
            return;
        }

        customers.forEach(function (customer) {
            let actionsHtml = '<a href="#" class="view-customer-details" data-customer-id="' + customer.id + '">' + (mobooking_customers_params?.i18n?.view_details || 'View Details') + '</a>';
            // Add more actions as needed, e.g., Edit, Send Notification

            // Format dates if they exist
            const lastBookingDate = customer.last_booking_date ? new Date(customer.last_booking_date).toLocaleDateString() : (mobooking_customers_params?.i18n?.n_a || 'N/A');

            // Status badge
            let statusClass = '';
            if (customer.status === 'active') statusClass = 'status-active';
            else if (customer.status === 'inactive') statusClass = 'status-inactive';
            else if (customer.status === 'blacklisted') statusClass = 'status-cancelled'; // Using cancelled style for blacklisted

            const statusText = customer.status.charAt(0).toUpperCase() + customer.status.slice(1);


            $tableBody.append(`
                <tr id="customer-${customer.id}">
                    <td data-label="${mobooking_customers_params?.i18n?.full_name || 'Full Name'}">${customer.full_name || ''}</td>
                    <td data-label="${mobooking_customers_params?.i18n?.email || 'Email'}">${customer.email || ''}</td>
                    <td data-label="${mobooking_customers_params?.i18n?.phone_number || 'Phone Number'}">${customer.phone_number || (mobooking_customers_params?.i18n?.n_a || 'N/A')}</td>
                    <td data-label="${mobooking_customers_params?.i18n?.total_bookings || 'Total Bookings'}">${customer.total_bookings || 0}</td>
                    <td data-label="${mobooking_customers_params?.i18n?.last_booking_date || 'Last Booking Date'}">${lastBookingDate}</td>
                    <td data-label="${mobooking_customers_params?.i18n?.status || 'Status'}"><span class="booking-status ${statusClass}">${statusText}</span></td>
                    <td data-label="${mobooking_customers_params?.i18n?.actions || 'Actions'}">${actionsHtml}</td>
                </tr>
            `);
        });
    }

    function renderPagination(pagination) {
        $paginationContainer.empty();
        if (pagination.total_pages <= 1) {
            return;
        }

        // Previous page link
        if (pagination.current_page > 1) {
            $paginationContainer.append('<li><a href="#" data-page="' + (pagination.current_page - 1) + '">&laquo; ' + (mobooking_customers_params?.i18n?.previous || 'Previous') + '</a></li>');
        } else {
            $paginationContainer.append('<li class="disabled"><span>&laquo; ' + (mobooking_customers_params?.i18n?.previous || 'Previous') + '</span></li>');
        }

        // Page number links
        // Simple pagination: show first, last, current, and +/- 2 pages around current
        let startPage = Math.max(1, pagination.current_page - 2);
        let endPage = Math.min(pagination.total_pages, pagination.current_page + 2);

        if (startPage > 1) {
            $paginationContainer.append('<li><a href="#" data-page="1">1</a></li>');
            if (startPage > 2) {
                $paginationContainer.append('<li class="disabled"><span>...</span></li>');
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            if (i === pagination.current_page) {
                $paginationContainer.append('<li class="active"><span>' + i + '</span></li>');
            } else {
                $paginationContainer.append('<li><a href="#" data-page="' + i + '">' + i + '</a></li>');
            }
        }

        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                $paginationContainer.append('<li class="disabled"><span>...</span></li>');
            }
            $paginationContainer.append('<li><a href="#" data-page="' + pagination.total_pages + '">' + pagination.total_pages + '</a></li>');
        }

        // Next page link
        if (pagination.current_page < pagination.total_pages) {
            $paginationContainer.append('<li><a href="#" data-page="' + (pagination.current_page + 1) + '">' + (mobooking_customers_params?.i18n?.next || 'Next') + ' &raquo;</a></li>');
        } else {
            $paginationContainer.append('<li class="disabled"><span>' + (mobooking_customers_params?.i18n?.next || 'Next') + ' &raquo;</span></li>');
        }
    }

    // Event Handlers

    // Pagination click
    $paginationContainer.on('click', 'a', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        fetchCustomers(page);
    });

    // Debounced search input
    const debouncedFetchCustomers = debounce(function() {
        fetchCustomers(1); // Reset to page 1 on new search
    }, 500);
    $searchInput.on('keyup', debouncedFetchCustomers);


    // Apply filters button
    $applyFiltersButton.on('click', function () {
        fetchCustomers(1); // Reset to page 1 on filter application
    });

    // Reset filters button
    $resetFiltersButton.on('click', function () {
        $searchInput.val('');
        $statusFilter.val('');
        // Reset sorting to default
        currentSortBy = 'full_name';
        currentSortOrder = 'ASC';
        updateSortIndicators();
        fetchCustomers(1);
    });

    // Sorting click
    $('.wp-list-table thead').on('click', 'th.sortable a', function (e) {
        e.preventDefault();
        const newSortBy = $(this).data('sort');

        if (currentSortBy === newSortBy) {
            currentSortOrder = (currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
        } else {
            currentSortBy = newSortBy;
            currentSortOrder = 'ASC';
        }
        updateSortIndicators();
        fetchCustomers(1);
    });

    function updateSortIndicators() {
        $('.wp-list-table thead th.sortable').removeClass('sorted asc desc');
        $('.wp-list-table thead th.sortable .sorting-indicator').html(''); // Clear old indicators

        const $activeTh = $('.wp-list-table thead th.sortable a[data-sort="' + currentSortBy + '"]').closest('th');
        $activeTh.addClass('sorted ' + currentSortOrder.toLowerCase());

        // Add visual indicator (WordPress style)
        // const indicatorSpan = $activeTh.find('.sorting-indicator');
        // if (currentSortOrder === 'ASC') {
        //     indicatorSpan.html('<span class="dashicons dashicons-arrow-up"></span>');
        // } else {
        //     indicatorSpan.html('<span class="dashicons dashicons-arrow-down"></span>');
        // }
        // The CSS handles the triangle indicators based on .asc/.desc classes on <th>
    }

    // Placeholder for "View Details" click
    $tableBody.on('click', '.view-customer-details', function(e){
        e.preventDefault();
        const customerId = $(this).data('customer-id');
        // In a real app, this would open a modal or navigate to a detail page
        displayFeedback('Viewing details for customer ID: ' + customerId + '. (Feature not yet implemented)', 'info');
        console.log('View details for customer ID:', customerId);
    });


    // Initial load
    if (typeof mobooking_customers_params !== 'undefined') {
        fetchCustomers();
        updateSortIndicators(); // Set initial sort indicator
    } else {
        console.error('MoBooking Customers JS parameters (mobooking_customers_params) are not defined.');
        displayFeedback('Configuration error: Parameters not loaded. Please contact support.', 'error');
    }
});
