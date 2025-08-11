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
        <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>" class="btn btn-primary">
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

<!--
  Standardized Card Template for Services
  This template is used by dashboard-services.js to render each service card.
  It follows the new `mobooking-card` structure for consistency across the dashboard.
-->
<script type="text/template" id="mobooking-service-item-template">
    <div class="mobooking-card service-card" data-service-id="<%= service_id %>">
        <div class="mobooking-card-header">
            <div class="mobooking-card-title-group">
                <span class="mobooking-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                </span>
                <h3 class="mobooking-card-title"><%= name %></h3>
            </div>
            <div class="mobooking-card-actions">
                 <span class="badge status-<%= status %>"><%= display_status %></span>
                 <a href="<?php echo esc_url(site_url('/dashboard/service-edit/')); ?>?service_id=<%= service_id %>" class="btn btn-icon btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                 </a>
                <button class="btn btn-icon btn-sm btn-destructive mobooking-delete-service-btn" data-id="<%= service_id %>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>
        </div>
        <div class="mobooking-card-content">
            <p class="text-muted-foreground"><%= description %></p>
        </div>
        <div class="mobooking-card-footer">
            <div class="text-lg font-semibold">
                <%= formatted_price %>
            </div>
            <div class="text-sm text-muted-foreground">
                <%= duration %> mins
            </div>
        </div>
    </div>
</script>

<!-- Service Card Template is now handled by assets/js/dashboard-services.js -->