jQuery(document).ready(function ($) {
    'use strict';

    // Check if essential params are defined
    if (typeof mobooking_services_params === 'undefined') {
        console.error('MoBooking: mobooking_services_params is not defined.');
        // Provide fallback params to prevent fatal errors
        window.mobooking_services_params = {
            ajax_url: '/wp-admin/admin-ajax.php',
            nonce: '',
            i18n: {},
            currency: {
                symbol: '$',
                position: 'before',
            },
        };
    }

    // --- Logic for Service List Page (page-services.php) ---
    const servicesPageContainer = $('.services-page-container');
    if (!servicesPageContainer.length) {
        return; // Exit if we are not on the services page
    }

    // --- Element Selectors ---
    const searchInput = $('#services-search');
    const statusFilter = $('#status-filter');
    const sortFilter = $('#sort-filter');
    const listContainer = $('#services-list-container');
    const gridContainer = $('#services-grid');
    const paginationContainer = $('#services-pagination-container');
    const feedbackContainer = $('#services-feedback-container');

    // --- State Management ---
    let currentPage = 1;
    let currentRequest = null; // To handle aborting previous AJAX requests

    // --- Debounce Utility ---
    function debounce(func, delay) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // --- Main Data Fetching Function ---
    const fetchServices = () => {
        const searchQuery = searchInput.val();
        const status = statusFilter.val();
        const sort = sortFilter.val().split('-');
        const [orderby, order] = sort;

        // Show loading state
        gridContainer.css('opacity', 0.5);

        // Abort previous request if it's still running
        if (currentRequest) {
            currentRequest.abort();
        }

        currentRequest = $.ajax({
            url: mobooking_services_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_services',
                nonce: mobooking_services_params.nonce,
                search_query: searchQuery,
                status_filter: status,
                orderby: orderby,
                order: order,
                paged: currentPage,
                per_page: 20,
            },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    renderServices(response.data.services);
                    renderPagination(
                        Math.ceil(response.data.total_count / response.data.per_page),
                        response.data.current_page
                    );
                } else {
                    renderError(response.data.message || 'An unknown error occurred.');
                }
            },
            error: function (jqXHR, textStatus) {
                if (textStatus !== 'abort') {
                    renderError('Failed to fetch services. Please try again.');
                }
            },
            complete: function () {
                gridContainer.css('opacity', 1);
                currentRequest = null;
            },
        });
    };

    // --- Rendering Functions ---

    function renderServices(services) {
        gridContainer.empty();
        if (services && services.length > 0) {
            services.forEach((service) => {
                const serviceCardHTML = createServiceCardHTML(service);
                gridContainer.append(serviceCardHTML);
            });
        } else {
            renderNoResults();
        }
    }

    function createServiceCardHTML(service) {
        const {
            currency
        } = mobooking_services_params;
        const priceFormatted = currency.position === 'before' ?
            `${currency.symbol}${parseFloat(service.price).toFixed(2)}` :
            `${parseFloat(service.price).toFixed(2)}${currency.symbol}`;

        const defaultIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>`;
        const cardIcon = service.icon ? service.icon : defaultIcon;

        const thumbnailUrl = service.image_url ?
            `<div class="mobooking-card-thumbnail" style="background-image: url('${service.image_url}');"></div>` :
            '';

        return `
            <div class="mobooking-card service-card" data-service-id="${service.service_id}">
                ${thumbnailUrl}
                <div class="mobooking-card-header">
                    <div class="mobooking-card-title-group">
                        <span class="mobooking-card-icon">${cardIcon}</span>
                        <h3 class="mobooking-card-title">${service.name}</h3>
                    </div>
                    <div class="mobooking-card-actions">
                         <span class="badge status-${service.status}">${service.status.charAt(0).toUpperCase() + service.status.slice(1)}</span>
                         <a href="/dashboard/service-edit/?service_id=${service.service_id}" class="btn btn-icon btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                         </a>
                        <button class="btn btn-icon btn-sm btn-destructive mobooking-delete-service-btn" data-id="${service.service_id}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </div>
                </div>
                <div class="mobooking-card-content">
                    <p class="text-muted-foreground">${service.description || ''}</p>
                </div>
                <div class="mobooking-card-footer">
                    <div class="text-lg font-semibold">${priceFormatted}</div>
                    <div class="text-sm text-muted-foreground">${service.duration} mins</div>
                </div>
            </div>
        `;
    }

    function renderPagination(totalPages, newCurrentPage) {
        currentPage = newCurrentPage;
        paginationContainer.empty();
        if (totalPages <= 1) {
            return;
        }

        let paginationHTML = '<div class="pagination-links">';

        // Previous Button
        paginationHTML += `
            <a href="#" class="pagination-link prev ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}">
                &laquo; Prev
            </a>`;

        // Page Number Links
        for (let i = 1; i <= totalPages; i++) {
            paginationHTML += `
                <a href="#" class="pagination-link ${i === currentPage ? 'active' : ''}" data-page="${i}">
                    ${i}
                </a>`;
        }

        // Next Button
        paginationHTML += `
            <a href="#" class="pagination-link next ${currentPage === totalPages ? 'disabled' : ''}" data-page="${currentPage + 1}">
                Next &raquo;
            </a>`;

        paginationHTML += '</div>';
        paginationContainer.html(paginationHTML);
    }

    function renderNoResults() {
        const noResultsHTML = `
            <div class="empty-state no-results-state">
                <div class="empty-state-icon">
                     <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="13" y1="9" x2="9" y2="13"></line>
                        <line x1="9" y1="9" x2="13" y2="13"></line>
                    </svg>
                </div>
                <h3 class="empty-state-title">No Matching Services Found</h3>
                <p class="empty-state-description">
                    Try adjusting your search or filter criteria to find what you're looking for.
                </p>
            </div>
        `;
        gridContainer.html(noResultsHTML);
    }

    function renderError(message) {
        const errorHTML = `<div class="notice notice-error"><p>${message}</p></div>`;
        feedbackContainer.html(errorHTML);
    }

    // --- Event Handlers ---

    const debouncedFetch = debounce(fetchServices, 300);

    searchInput.on('input', () => {
        currentPage = 1; // Reset to first page on new search
        debouncedFetch();
    });

    statusFilter.on('change', () => {
        currentPage = 1; // Reset to first page on filter change
        fetchServices();
    });

    sortFilter.on('change', () => {
        currentPage = 1; // Reset to first page on sort change
        fetchServices();
    });

    paginationContainer.on('click', 'a.pagination-link', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && page !== currentPage && !$(this).hasClass('disabled')) {
            currentPage = page;
            fetchServices();
        }
    });

    // --- Delete Handler (delegated to list container) ---
    listContainer.on('click', '.mobooking-delete-service-btn', function () {
        const serviceCard = $(this).closest('.service-card');
        const serviceId = $(this).data('id');
        const serviceName = serviceCard.find('.mobooking-card-title').text();

        // Using a more modern confirm dialog if available, otherwise fallback to browser default
        if (confirm(`Are you sure you want to delete the service "${serviceName}"? This action cannot be undone.`)) {
            $.ajax({
                url: mobooking_services_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'mobooking_delete_service',
                    nonce: mobooking_services_params.nonce,
                    service_id: serviceId,
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        // Instead of reloading, just remove the card and maybe show a toast
                        serviceCard.fadeOut(300, function() {
                            $(this).remove();
                            // Optional: check if grid is now empty and show empty state
                            if (gridContainer.children().length === 0) {
                                fetchServices(); // Re-fetch to show correct state (e.g., empty page or previous page)
                            }
                        });
                    } else {
                        alert(response.data.message || 'Error deleting service.');
                    }
                },
                error: function () {
                    alert('An unexpected error occurred. Please try again.');
                },
            });
        }
    });

    // --- Initial Load ---
    // The page is initially rendered by PHP, so we don't need an initial fetch.
    // We just need to render the initial pagination based on the data available in the PHP template.
    // However, the required variables (totalPages, currentPage) are in PHP scope.
    // To simplify, we'll let the JS take over pagination completely.
    // We'll trigger an initial fetch to get the pagination right.
    if (gridContainer.length) {
       fetchServices();
    }
});
