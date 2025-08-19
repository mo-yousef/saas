jQuery(document).ready(function($) {
    'use strict';

    // Caching jQuery selectors for performance
    const customersListContainer = $('#mobooking-customers-list-container');
    const paginationContainer = $('#mobooking-customers-pagination-container');
    const filterForm = $('#mobooking-customers-filter-form');
    const customerItemTemplate = $('#mobooking-customer-item-template').html();
    const clearFiltersBtn = $('#mobooking-clear-filters-btn');

    // Store current filters and page
    let currentFilters = {
        search_query: '',
        status_filter: '',
        date_from_filter: '',
        date_to_filter: '',
        paged: 1,
        limit: 20
    };

    /**
     * Sanitizes a string to prevent XSS.
     * @param {string} str The string to sanitize.
     * @returns {string} The sanitized string.
     */
    function sanitizeHTML(str) {
        if (typeof str !== 'string') return '';
        var temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }

    /**
     * Renders a template with the given data.
     * @param {string} templateHtml The HTML template string.
     * @param {object} data The data to populate the template with.
     * @returns {string} The rendered HTML.
     */
    function renderTemplate(templateHtml, data) {
        let template = templateHtml;
        const noEscapeKeys = ['status_icon_html'];
        for (const key in data) {
            let value = (typeof data[key] === 'string' || typeof data[key] === 'number') ? data[key] : '';
            if (!noEscapeKeys.includes(key)) {
                value = sanitizeHTML(String(value));
            }
            template = template.replace(new RegExp('<%=\\s*' + key + '\\s*%>', 'g'), value);
        }
        return template;
    }
    /**
     * Generates the main table structure.
     * @returns {string} HTML string for the table.
     */
    function getTableHTML() {
        const i18n = mobooking_customers_params.i18n;
        return `
            <div class="mobooking-table-responsive-wrapper">
                <table class="mobooking-table">
                    <thead>
                        <tr>
                            <th>${i18n.customer || 'Customer'}</th>
                            <th>${i18n.contact || 'Contact'}</th>
                            <th>${i18n.bookings || 'Bookings'}</th>
                            <th>${i18n.last_booking || 'Last Booking'}</th>
                            <th>${i18n.status || 'Status'}</th>
                            <th>${i18n.actions || 'Actions'}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        `;
    }

    /**
     * Loads customers via AJAX based on current filters.
     * @param {number} [page=1] The page number to load.
     */
    function loadCustomers(page = 1) {
        currentFilters.paged = page;
        customersListContainer.html('<div class="mobooking-spinner"></div>');
        paginationContainer.empty();

        const ajaxData = {
            action: 'mobooking_get_customers',
            nonce: mobooking_customers_params.nonce,
            ...currentFilters
        };

        // Show/hide the clear filters button
        const isFilterActive = Object.values(currentFilters).some(val => val && val !== 1 && val !== 20);
        clearFiltersBtn.toggle(isFilterActive);

        $.ajax({
            url: mobooking_customers_params.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                customersListContainer.empty();
                if (response.success && response.data.customers && response.data.customers.length) {
                    customersListContainer.html(getTableHTML());
                    const tableBody = customersListContainer.find('tbody');
                    const i18n = mobooking_customers_params.i18n;

                    response.data.customers.forEach(function(customer) {
                        let customerDataForTemplate = { ...customer };

                        // Prepare data for the template
                        customerDataForTemplate.status_display = mobooking_customers_params.statuses[customer.status] || customer.status.charAt(0).toUpperCase() + customer.status.slice(1);
                        customerDataForTemplate.status_icon_html = mobooking_customers_params.icons[customer.status] || '';
                        customerDataForTemplate.details_page_url = `${mobooking_customers_params.details_page_base_url}?customer_id=${customer.id}`;
                        customerDataForTemplate.last_booking_date_formatted = customer.last_booking_date ? new Date(customer.last_booking_date).toLocaleDateString() : i18n.na || 'N/A';
                        customerDataForTemplate.phone_number = customer.phone_number || '';
                        customerDataForTemplate.total_bookings = customer.total_bookings || 0;

                        tableBody.append(renderTemplate(customerItemTemplate, customerDataForTemplate));
                    });

                    renderPagination(response.data.pagination);
                } else {
                    const noResultsHTML = `
                        <div class="mobooking-no-results-message">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            <h4>${mobooking_customers_params.i18n.no_customers_found || 'No customers found'}</h4>
                            <p>${mobooking_customers_params.i18n.try_different_filters || 'Try adjusting your filters or clearing them to see all customers.'}</p>
                        </div>
                    `;
                    customersListContainer.html(noResultsHTML);
                }
            },
            error: function() {
                customersListContainer.html(`<p>${mobooking_customers_params.i18n.error_loading || 'Error loading customers.'}</p>`);
            }
        });
    }

    /**
     * Renders pagination links.
     * @param {object} paginationData The pagination data from the server.
     */
    function renderPagination(paginationData) {
        paginationContainer.empty();
        if (!paginationData || paginationData.total_pages <= 1) return;

        let paginationHtml = '<ul class="mobooking-pagination">';
        for (let i = 1; i <= paginationData.total_pages; i++) {
            paginationHtml += `<li class="${i === paginationData.current_page ? 'active' : ''}"><a href="#" data-page="${i}">${i}</a></li>`;
        }
        paginationHtml += '</ul>';
        paginationContainer.html(paginationHtml);
    }

    // Debounce function to limit the rate of function execution
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Function to apply filters and trigger customer load
    function applyFilters() {
        currentFilters = {
            ...currentFilters,
            search_query: $('#mobooking-search-query').val(),
            status_filter: $('#mobooking-status-filter').val(),
            date_from_filter: $('#mobooking-date-from-filter').val(),
            date_to_filter: $('#mobooking-date-to-filter').val(),
        };
        loadCustomers(1); // Reset to page 1 on filter change
    }

    // --- Event Listeners ---

    // Debounced search input
    $('#mobooking-search-query').on('keyup', debounce(applyFilters, 500));

    // Other filters
    $('#mobooking-status-filter, .mobooking-datepicker').on('change', applyFilters);

    // Form submission prevention
    filterForm.on('submit', function(e) {
        e.preventDefault();
        applyFilters();
    });

    // Clear filters button
    clearFiltersBtn.on('click', function() {
        filterForm[0].reset();
        // Manually trigger change for selects if needed
        filterForm.find('select').trigger('change');
        applyFilters();
    });

    // Pagination clicks
    paginationContainer.on('click', 'a', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page) {
            loadCustomers(page);
        }
    });

    // Toggle more filters
    $('#mobooking-toggle-more-filters-btn').on('click', function() {
        const secondaryFilters = $('.mobooking-filters-secondary');
        const button = $(this);
        secondaryFilters.slideToggle(200, function() {
            const text = $(this).is(':visible') ? (mobooking_customers_params.i18n.less_filters || 'Less') : (mobooking_customers_params.i18n.more_filters || 'More');
            button.find('.btn-text').text(text);
        });
    });

    // Initialize datepickers
    if (typeof $.fn.datepicker === 'function') {
        $('.mobooking-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    } else {
        // Fallback for browsers that support date type input
        $('.mobooking-datepicker').attr('type', 'date');
    }

    // Initial load is handled by PHP, so no initial `loadCustomers()` call here.
});
