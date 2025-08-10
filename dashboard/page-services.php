<?php
/**
 * Dashboard Page: Services (Refactored with shadcn/ui Design)
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Instantiate the Services class
$services_manager = new \MoBooking\Classes\Services();
$user_id = get_current_user_id();

// Fetch business settings for currency formatting
$settings_manager = new \MoBooking\Classes\Settings();
$biz_settings = $settings_manager->get_business_settings($user_id);
$currency_symbol = $biz_settings['biz_currency_symbol'] ?? '$';
$currency_pos = $biz_settings['biz_currency_position'] ?? 'before';
$currency_decimals = 2;
$currency_decimal_sep = '.';
$currency_thousand_sep = ',';

// Initial load parameters
$default_args = [
    'number' => 20,
    'offset' => 0,
    'status' => null,
    'orderby' => 'name',
    'order' => 'ASC',
];

$services_result = $services_manager->get_services_by_user($user_id, $default_args);
$services_list = $services_result['services'] ?? [];
$total_services = $services_result['total_count'] ?? 0;
$per_page = $services_result['per_page'] ?? 20;
$current_page = $services_result['current_page'] ?? 1;
$total_pages = ceil($total_services / $per_page);

// Nonce for AJAX operations
wp_nonce_field('mobooking_services_nonce', 'mobooking_services_nonce_field');
?>

<div class="services-page-container">
    <!-- Header Section -->
    <div class="mobooking-page-header">
        <div class="mobooking-page-header-heading">
            <span class="mobooking-page-header-icon">
                <?php echo mobooking_get_dashboard_menu_icon('services'); ?>
            </span>
            <h1 class="services-title"><?php esc_html_e('Manage Your Services', 'mobooking'); ?></h1>
        </div>
        <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>" class="button button-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14" />
                <path d="M12 5v14" />
            </svg>
            <?php esc_html_e('Add New Service', 'mobooking'); ?>
        </a>
    </div>

    <!-- Controls Section -->
    <div class="services-controls">
        <div class="search-container">
            <input
                type="text"
                id="services-search"
                class="search-input"
                placeholder="<?php esc_attr_e('Search services...', 'mobooking'); ?>"
                value=""
            >
            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m21 21-4.3-4.3" />
                <circle cx="11" cy="11" r="8" />
            </svg>
        </div>

        <select id="status-filter" class="filter-select">
            <option value=""><?php esc_html_e('All Statuses', 'mobooking'); ?></option>
            <option value="active"><?php esc_html_e('Active', 'mobooking'); ?></option>
            <option value="inactive"><?php esc_html_e('Inactive', 'mobooking'); ?></option>
        </select>

        <select id="sort-filter" class="filter-select">
            <option value="name-asc"><?php esc_html_e('Name A-Z', 'mobooking'); ?></option>
            <option value="name-desc"><?php esc_html_e('Name Z-A', 'mobooking'); ?></option>
            <option value="price-asc"><?php esc_html_e('Price Low-High', 'mobooking'); ?></option>
            <option value="price-desc"><?php esc_html_e('Price High-Low', 'mobooking'); ?></option>
            <option value="date-asc"><?php esc_html_e('Oldest First', 'mobooking'); ?></option>
            <option value="date-desc"><?php esc_html_e('Newest First', 'mobooking'); ?></option>
        </select>
    </div>
        
        <!-- Controls Section -->
        <div class="services-controls">
            <div class="search-container">
                <input 
                    type="text" 
                    id="services-search" 
                    class="search-input" 
                    placeholder="<?php esc_attr_e('Search services...', 'mobooking'); ?>"
                    value=""
                >
                <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m21 21-4.3-4.3" />
                    <circle cx="11" cy="11" r="8" />
                </svg>
            </div>
            
            <select id="status-filter" class="filter-select">
                <option value=""><?php esc_html_e('All Statuses', 'mobooking'); ?></option>
                <option value="active"><?php esc_html_e('Active', 'mobooking'); ?></option>
                <option value="inactive"><?php esc_html_e('Inactive', 'mobooking'); ?></option>
            </select>
            
            <select id="sort-filter" class="filter-select">
                <option value="name-asc"><?php esc_html_e('Name A-Z', 'mobooking'); ?></option>
                <option value="name-desc"><?php esc_html_e('Name Z-A', 'mobooking'); ?></option>
                <option value="price-asc"><?php esc_html_e('Price Low-High', 'mobooking'); ?></option>
                <option value="price-desc"><?php esc_html_e('Price High-Low', 'mobooking'); ?></option>
                <option value="date-asc"><?php esc_html_e('Oldest First', 'mobooking'); ?></option>
                <option value="date-desc"><?php esc_html_e('Newest First', 'mobooking'); ?></option>
            </select>
        </div>
    </div>
    
    <!-- Content Section -->
    <div class="services-content">
        <!-- Feedback Messages Container -->
        <div id="services-feedback-container"></div>
        
        <!-- Services List Container -->
        <div id="services-list-container">
            <?php if (empty($services_list)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                            <line x1="10" y1="9" x2="8" y2="9" />
                        </svg>
                    </div>
                    <h3 class="empty-state-title"><?php esc_html_e('No services yet', 'mobooking'); ?></h3>
                    <p class="empty-state-description">
                        <?php esc_html_e('Create your first service to start accepting bookings from customers.', 'mobooking'); ?>
                    </p>
                    <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>" class="add-service-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14" />
                            <path d="M12 5v14" />
                        </svg>
                        <?php esc_html_e('Create First Service', 'mobooking'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="services-grid" id="services-grid">
                    <!-- Services will be rendered here by JavaScript -->
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination Container -->
        <div id="services-pagination-container" class="pagination-container">
            <!-- Pagination will be rendered here by JavaScript -->
        </div>
    </div>
</div>

<!-- Service Card Template -->
<script type="text/template" id="service-card-template">
    <div class="service-card" data-service-id="<%= service_id %>">
        <div class="service-card-header">
            <h3 class="service-name"><%= name %></h3>
            <span class="service-status <%= status %>"><%= display_status %></span>
        </div>
        
        <div class="service-details">
            <div class="service-detail-row">
                <span class="service-detail-label"><?php esc_html_e('Price', 'mobooking'); ?></span>
                <span class="service-detail-value service-price"><%= formatted_price %></span>
            </div>
            <div class="service-detail-row">
                <span class="service-detail-label"><?php esc_html_e('Duration', 'mobooking'); ?></span>
                <span class="service-detail-value"><%= duration %> <?php esc_html_e('min', 'mobooking'); ?></span>
            </div>
            <% if (typeof category !== 'undefined' && category) { %>
            <div class="service-detail-row">
                <span class="service-detail-label"><?php esc_html_e('Category', 'mobooking'); ?></span>
                <span class="service-detail-value"><%= category %></span>
            </div>
            <% } %>
            <% if (typeof description !== 'undefined' && description) { %>
            <div class="service-detail-row">
                <span class="service-detail-label"><?php esc_html_e('Description', 'mobooking'); ?></span>
                <span class="service-detail-value"><%= description.length > 60 ? description.substring(0, 60) + '...' : description %></span>
            </div>
            <% } %>
        </div>
        
        <div class="service-actions">
            <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>?service_id=<%= service_id %>" class="btn btn-edit">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                    <path d="m15 5 4 4" />
                </svg>
                <?php esc_html_e('Edit', 'mobooking'); ?>
            </a>
            <button class="btn btn-delete service-delete-btn" data-service-id="<%= service_id %>" data-service-name="<%= name %>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18" />
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
                <?php esc_html_e('Delete', 'mobooking'); ?>
            </button>
        </div>
    </div>
</script>

<script type="text/javascript">
// Pass initial data to JavaScript
window.mobookingServicesData = {
    initialServices: <?php echo wp_json_encode($services_list); ?>,
    totalServices: <?php echo intval($total_services); ?>,
    currentPage: <?php echo intval($current_page); ?>,
    perPage: <?php echo intval($per_page); ?>,
    totalPages: <?php echo intval($total_pages); ?>,
    currency: {
        symbol: <?php echo wp_json_encode($currency_symbol); ?>,
        position: <?php echo wp_json_encode($currency_pos); ?>,
        decimals: <?php echo intval($currency_decimals); ?>,
        decimalSep: <?php echo wp_json_encode($currency_decimal_sep); ?>,
        thousandSep: <?php echo wp_json_encode($currency_thousand_sep); ?>
    },
    i18n: {
        loading: <?php echo wp_json_encode(__('Loading services...', 'mobooking')); ?>,
        noServices: <?php echo wp_json_encode(__('No services found', 'mobooking')); ?>,
        deleteConfirm: <?php echo wp_json_encode(__('Are you sure you want to delete "%s"? This action cannot be undone.', 'mobooking')); ?>,
        deleteSuccess: <?php echo wp_json_encode(__('Service deleted successfully.', 'mobooking')); ?>,
        deleteError: <?php echo wp_json_encode(__('Failed to delete service. Please try again.', 'mobooking')); ?>,
        ajaxError: <?php echo wp_json_encode(__('An error occurred. Please try again.', 'mobooking')); ?>,
        active: <?php echo wp_json_encode(__('Active', 'mobooking')); ?>,
        inactive: <?php echo wp_json_encode(__('Inactive', 'mobooking')); ?>
    }
};

jQuery(document).ready(function($) {
    'use strict';
    
    // Check if required parameters are available
    if (typeof mobooking_services_params === 'undefined') {
        console.error('MoBooking: Required services parameters not found');
        return;
    }
    
    // Get DOM elements
    const $servicesGrid = $('#services-grid');
    const $servicesContainer = $('#services-list-container');
    const $paginationContainer = $('#services-pagination-container');
    const $feedbackContainer = $('#services-feedback-container');
    const $searchInput = $('#services-search');
    const $statusFilter = $('#status-filter');
    const $sortFilter = $('#sort-filter');
    
    // Current filters and pagination
    let currentFilters = {
        search: '',
        status: '',
        orderby: 'name',
        order: 'ASC',
        paged: 1,
        per_page: 20
    };
    
    // Debounce function for search
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Format currency
    function formatCurrency(amount) {
        const currency = window.mobookingServicesData.currency;
        const formatted = parseFloat(amount || 0).toFixed(currency.decimals);
        const parts = formatted.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, currency.thousandSep);
        const formattedAmount = parts.join(currency.decimalSep);
        
        return currency.position === 'before' 
            ? currency.symbol + formattedAmount 
            : formattedAmount + currency.symbol;
    }
    
    // Show feedback message
    function showFeedback(message, type = 'info') {
        const feedbackHtml = `
            <div class="feedback-message feedback-${type}">
                ${message}
            </div>
        `;
        $feedbackContainer.html(feedbackHtml);
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            $feedbackContainer.find('.feedback-message').fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Simple HTML builder function - no template parsing needed
    function buildServiceCard(service) {
        const formattedPrice = formatCurrency(service.price);
        const displayStatus = service.status === 'active' 
            ? window.mobookingServicesData.i18n.active 
            : window.mobookingServicesData.i18n.inactive;
        
        const shortDescription = service.description 
            ? (service.description.length > 60 
                ? service.description.substring(0, 60) + '...' 
                : service.description)
            : '';
        
        const hasCategory = !!(service.category && service.category.trim());
        const hasDescription = !!(service.description && service.description.trim());
        
        const editUrl = '<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>?service_id=' + service.service_id;
        
        let html = `
            <div class="service-card" data-service-id="${service.service_id}">
                <div class="service-card-header">
                    <h3 class="service-name">${escapeHtml(service.name)}</h3>
                    <span class="service-status ${service.status}">${escapeHtml(displayStatus)}</span>
                </div>
                
                <div class="service-details">
                    <div class="service-detail-row">
                        <span class="service-detail-label"><?php esc_html_e('Price', 'mobooking'); ?></span>
                        <span class="service-detail-value service-price">${escapeHtml(formattedPrice)}</span>
                    </div>
                    <div class="service-detail-row">
                        <span class="service-detail-label"><?php esc_html_e('Duration', 'mobooking'); ?></span>
                        <span class="service-detail-value">${escapeHtml(service.duration)} <?php esc_html_e('min', 'mobooking'); ?></span>
                    </div>`;
        
        if (hasCategory) {
            html += `
                    <div class="service-detail-row">
                        <span class="service-detail-label"><?php esc_html_e('Category', 'mobooking'); ?></span>
                        <span class="service-detail-value">${escapeHtml(service.category)}</span>
                    </div>`;
        }
        
        if (hasDescription) {
            html += `
                    <div class="service-detail-row">
                        <span class="service-detail-label"><?php esc_html_e('Description', 'mobooking'); ?></span>
                        <span class="service-detail-value">${escapeHtml(shortDescription)}</span>
                    </div>`;
        }
        
        html += `
                </div>
                
                <div class="service-actions">
                    <a href="${editUrl}" class="btn btn-edit">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <?php esc_html_e('Edit', 'mobooking'); ?>
                    </a>
                    <button class="btn btn-delete service-delete-btn" data-service-id="${service.service_id}" data-service-name="${escapeHtml(service.name)}">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3,6 5,6 21,6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2 2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <?php esc_html_e('Delete', 'mobooking'); ?>
                    </button>
                </div>
            </div>`;
        
        return html;
    }
    
    // HTML escape function for security
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Render services grid
    function renderServices(services) {
        if (!services || services.length === 0) {
            $servicesContainer.html(`
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h3 class="empty-state-title">${window.mobookingServicesData.i18n.noServices}</h3>
                    <p class="empty-state-description">
                        Try adjusting your search criteria or filters.
                    </p>
                </div>
            `);
            return;
        }
        
        let servicesHtml = '<div class="services-grid" id="services-grid">';
        
        services.forEach(service => {
            servicesHtml += buildServiceCard(service);
        });
        
        servicesHtml += '</div>';
        $servicesContainer.html(servicesHtml);
    }
    
    // Render pagination
    function renderPagination(totalCount, perPage, currentPage) {
        const totalPages = Math.ceil(totalCount / perPage);
        
        if (totalPages <= 1) {
            $paginationContainer.empty();
            return;
        }
        
        let paginationHtml = '<ul class="page-numbers">';
        
        // Previous button
        if (currentPage > 1) {
            paginationHtml += `
                <li>
                    <a href="#" class="page-link" data-page="${currentPage - 1}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6" />
                        </svg>
                    </a>
                </li>
            `;
        }
        
        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            paginationHtml += `<li><a href="#" class="page-link" data-page="1">1</a></li>`;
            if (startPage > 2) {
                paginationHtml += `<li><span>...</span></li>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                paginationHtml += `<li><span class="current">${i}</span></li>`;
            } else {
                paginationHtml += `<li><a href="#" class="page-link" data-page="${i}">${i}</a></li>`;
            }
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<li><span>...</span></li>`;
            }
            paginationHtml += `<li><a href="#" class="page-link" data-page="${totalPages}">${totalPages}</a></li>`;
        }
        
        // Next button
        if (currentPage < totalPages) {
            paginationHtml += `
                <li>
                    <a href="#" class="page-link" data-page="${currentPage + 1}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </a>
                </li>
            `;
        }
        
        paginationHtml += '</ul>';
        $paginationContainer.html(paginationHtml);
    }
    
    // Fetch services via AJAX
    function fetchServices(page = 1, filters = {}) {
        // Show loading state
        $servicesContainer.html(`
            <div class="loading-state">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite;">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                </svg>
                <p>${window.mobookingServicesData.i18n.loading}</p>
            </div>
        `);
        
        // Update current filters
        currentFilters = {
            ...currentFilters,
            ...filters,
            paged: page
        };
        
        // Prepare AJAX data
        const ajaxData = {
            action: 'mobooking_get_services',
            nonce: mobooking_services_params.services_nonce,
            paged: currentFilters.paged,
            per_page: currentFilters.per_page,
            status_filter: currentFilters.status,
            search_query: currentFilters.search,
            orderby: currentFilters.orderby,
            order: currentFilters.order
        };
        
        $.ajax({
            url: mobooking_services_params.ajax_url,
            type: 'POST',
            data: ajaxData,
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    renderServices(response.data.services || []);
                    renderPagination(
                        response.data.total_count || 0,
                        response.data.per_page || 20,
                        response.data.current_page || 1
                    );
                } else {
                    showFeedback(response.data?.message || window.mobookingServicesData.i18n.ajaxError, 'error');
                    renderServices([]);
                }
            },
            error: function() {
                showFeedback(window.mobookingServicesData.i18n.ajaxError, 'error');
                renderServices([]);
            }
        });
    }
    
    // Delete service
    function deleteService(serviceId, serviceName) {
        const confirmMessage = window.mobookingServicesData.i18n.deleteConfirm.replace('%s', serviceName);
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        const $deleteBtn = $(`.service-delete-btn[data-service-id="${serviceId}"]`);
        const originalText = $deleteBtn.html();
        
        // Show loading state
        $deleteBtn.prop('disabled', true).html(`
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation: spin 1s linear infinite;">
                <path d="M21 12a9 9 0 11-6.219-8.56"/>
            </svg>
            Deleting...
        `);
        
        $.ajax({
            url: mobooking_services_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_delete_service',
                nonce: mobooking_services_params.services_nonce,
                service_id: serviceId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showFeedback(window.mobookingServicesData.i18n.deleteSuccess, 'success');
                    // Refresh the current page
                    fetchServices(currentFilters.paged, currentFilters);
                } else {
                    showFeedback(response.data?.message || window.mobookingServicesData.i18n.deleteError, 'error');
                    $deleteBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                showFeedback(window.mobookingServicesData.i18n.ajaxError, 'error');
                $deleteBtn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    // Event handlers
    
    // Search input
    const debouncedSearch = debounce(function() {
        const searchValue = $searchInput.val().trim();
        fetchServices(1, { search: searchValue });
    }, 500);
    
    $searchInput.on('input', debouncedSearch);
    
    // Status filter
    $statusFilter.on('change', function() {
        const statusValue = $(this).val();
        fetchServices(1, { status: statusValue });
    });
    
    // Sort filter
    $sortFilter.on('change', function() {
        const sortValue = $(this).val();
        const [orderby, order] = sortValue.split('-');
        fetchServices(1, { orderby, order: order.toUpperCase() });
    });
    
    // Pagination clicks
    $paginationContainer.on('click', '.page-link', function(e) {
        e.preventDefault();
        const page = parseInt($(this).data('page'));
        if (page && page !== currentFilters.paged) {
            fetchServices(page, currentFilters);
        }
    });
    
    // Delete service clicks
    $servicesContainer.on('click', '.service-delete-btn', function(e) {
        e.preventDefault();
        const serviceId = $(this).data('service-id');
        const serviceName = $(this).data('service-name');
        deleteService(serviceId, serviceName);
    });
    
    // Initial load
    if (window.mobookingServicesData.initialServices && window.mobookingServicesData.initialServices.length > 0) {
        renderServices(window.mobookingServicesData.initialServices);
        renderPagination(
            window.mobookingServicesData.totalServices,
            window.mobookingServicesData.perPage,
            window.mobookingServicesData.currentPage
        );
    } else {
        fetchServices(1, currentFilters);
    }
});
</script>

<style>
/* Add loading animation */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Additional responsive improvements */
@media (max-width: 640px) {
    .services-header {
        padding: 1rem;
    }
    
    .services-content {
        padding: 1rem;
    }
    
    .services-title {
        font-size: 1.5rem;
    }
    
    .services-controls {
        gap: 0.75rem;
    }
    
    .filter-select {
        font-size: 0.8125rem;
    }
    
    .service-card {
        padding: 1rem;
    }
    
    .service-name {
        font-size: 1rem;
    }
    
    .service-detail-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
    
    .page-numbers a,
    .page-numbers span {
        width: 2rem;
        height: 2rem;
        font-size: 0.8125rem;
    }
}

/* Enhanced hover and focus states */
.service-card:focus-within {
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    transform: translateY(-1px);
}

.btn:focus {
    outline: 2px solid hsl(221.2 83.2% 53.3%);
    outline-offset: 2px;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Improved empty state */
.empty-state {
    min-height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* Loading state improvements */
.loading-state {
    min-height: 200px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.loading-state p {
    margin: 0;
    font-weight: 500;
}

/* Feedback message improvements */
.feedback-message {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
}

.feedback-message::before {
    content: '';
    width: 4px;
    height: 100%;
    position: absolute;
    left: 0;
    top: 0;
    border-radius: 0 0.375rem 0.375rem 0;
}

.feedback-success::before {
    background-color: hsl(142 76% 36%);
}

.feedback-error::before {
    background-color: hsl(0 84.2% 60.2%);
}

/* Service status badge improvements */
.service-status {
    white-space: nowrap;
    flex-shrink: 0;
}

/* Price highlighting */
.service-price {
    font-weight: 700;
    font-size: 1.0625rem;
}

/* Action buttons improvements */
.service-actions {
    margin-top: auto;
    padding-top: 0.5rem;
}

/* Ensure proper text truncation */
.service-detail-value {
    word-break: break-word;
    overflow-wrap: break-word;
}

/* Pagination improvements */
.pagination-container {
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

@media (min-width: 768px) {
    .pagination-container {
        justify-content: center;
    }
}
</style>