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

<!-- Service Card Template is now handled by assets/js/dashboard-services.js -->