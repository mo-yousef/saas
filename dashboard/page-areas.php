<?php
/**
 * Enhanced Service Areas Dashboard Page
 * Country-based selection with persistent visual management
 */

if (!defined('ABSPATH')) exit;

// Security check
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

$current_user_id = get_current_user_id();
?>

<div class="nordbooking-dashboard-page">
    <div class="nordbooking-page-header">
        <div class="nordbooking-page-header-heading">
            <span class="nordbooking-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('areas'); ?>
            </span>
            <h1 class="nordbooking-page-title"><?php esc_html_e('Service Areas', 'NORDBOOKING'); ?></h1>
        </div>
        <p class="nordbooking-page-description">
            <?php esc_html_e('Manage your service coverage by selecting countries, cities and their specific areas. Choose one country at a time to configure your service areas.', 'NORDBOOKING'); ?>
        </p>
    </div>

    <?php
    // Check if location check is disabled and show informational message
    $settings_manager = new \NORDBOOKING\Classes\Settings();
    $location_check_enabled = $settings_manager->get_setting($current_user_id, 'bf_enable_location_check', '1');
    
    if ($location_check_enabled === '0' || $location_check_enabled === 0 || $location_check_enabled === false) :
    ?>
    <div class="nordbooking-info-banner nordbooking-location-check-info">
        <div class="nordbooking-info-banner-content">
            <div class="nordbooking-info-banner-icon">
                <svg class="NORDBOOKING-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="nordbooking-info-banner-text">
                <h4><?php esc_html_e('Location Check is Currently Disabled', 'NORDBOOKING'); ?></h4>
                <p>
                    <?php esc_html_e('Your booking form will accept bookings from any location. To enable location-based restrictions and use the service areas you configure here, please enable "Location Check" in your booking form settings.', 'NORDBOOKING'); ?>
                </p>
                <a href="<?php echo esc_url(home_url('/dashboard/booking-form/')); ?>" class="nordbooking-info-banner-link">
                    <?php esc_html_e('Go to Booking Form Settings', 'NORDBOOKING'); ?>
                    <svg class="NORDBOOKING-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="nordbooking-dashboard-content">
        <!-- Country Selection Card -->
        <div class="nordbooking-card card-bs compact">
            <div class="nordbooking-card-header">
                <h3 class="nordbooking-card-title">
                    <?php esc_html_e('Select Country', 'NORDBOOKING'); ?>
                </h3>
                <p class="nordbooking-card-description">
                    <?php esc_html_e('Choose a country to manage service areas. Only one country can be selected at a time.', 'NORDBOOKING'); ?>
                </p>
            </div>
            <div id="countries-grid-container" class="countries-grid-container">
                <!-- Countries will be loaded here by JavaScript -->
                <div class="NORDBOOKING-loading-state">
                    <div class="NORDBOOKING-spinner"></div>
                    <p><?php esc_html_e('Loading countries...', 'NORDBOOKING'); ?></p>
                </div>
            </div>
        </div>

        <!-- City Selection Card -->
        <div class="nordbooking-card card-bs compact" id="cities-selection-card" style="display: none;">
            <div class="nordbooking-card-header">
                <div class="card-header-main">
                    <h3 class="nordbooking-card-title">
                        <span id="cities-card-title"><?php esc_html_e('Select Cities', 'NORDBOOKING'); ?></span>
                    </h3>
                    <p class="nordbooking-card-description">
                        <span id="cities-card-description"><?php esc_html_e('Click on a city to manage its service areas. Areas can be enabled or disabled individually.', 'NORDBOOKING'); ?></span>
                    </p>
                </div>
                <button type="button" id="change-country-btn" class="btn btn-outline btn-sm">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path
        d="M2 10C2 10 2.12132 9.15076 5.63604 5.63604C9.15076 2.12132 14.8492 2.12132 18.364 5.63604C19.6092 6.88131 20.4133 8.40072 20.7762 10M2 10V4M2 10H8M22 14C22 14 21.8787 14.8492 18.364 18.364C14.8492 21.8787 9.15076 21.8787 5.63604 18.364C4.39076 17.1187 3.58669 15.5993 3.22383 14M22 14V20M22 14H16"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    />
</svg>

                    <?php esc_html_e('Change Country', 'NORDBOOKING'); ?>
                </button>
            </div>
            <div id="cities-grid-container" class="cities-grid-container">
                <!-- Cities will be loaded here by JavaScript -->
                <div class="NORDBOOKING-loading-state">
                    <div class="NORDBOOKING-spinner"></div>
                    <p><?php esc_html_e('Loading cities...', 'NORDBOOKING'); ?></p>
                </div>
            </div>
        </div>

        <!-- Service Coverage Management -->
        <div class="nordbooking-card nordbooking-card-full-width compact">
            <div class="nordbooking-card-header">
                <div class="card-header-main">
                    <h3 class="nordbooking-card-title">
                        <svg class="NORDBOOKING-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <?php esc_html_e('Service Coverage Management', 'NORDBOOKING'); ?>
                    </h3>
                    <p class="nordbooking-card-description">
                        <?php esc_html_e('Manage your service areas with bulk actions. Select multiple cities to enable, disable, or remove them at once.', 'NORDBOOKING'); ?>
                    </p>
                </div>
                <div class="coverage-stats" id="coverage-stats">
                    <span class="stat-item">
                        <span class="stat-number" id="total-cities">0</span>
                        <span class="stat-label"><?php esc_html_e('Cities', 'NORDBOOKING'); ?></span>
                    </span>
                    <span class="stat-item">
                        <span class="stat-number" id="active-cities">0</span>
                        <span class="stat-label"><?php esc_html_e('Active', 'NORDBOOKING'); ?></span>
                    </span>
                </div>
            </div>

            <!-- Filters and Bulk Actions -->
            <div class="coverage-controls">
                <!-- Search and Filter Controls -->
                <div class="nordbooking-filters-wrapper">
                    <form id="NORDBOOKING-areas-filter-form" class="nordbooking-filters-form">
                        <div class="nordbooking-filters-main">
                            <div class="nordbooking-filter-item nordbooking-filter-item-search">
                                <input type="search" id="coverage-search" class="regular-text" placeholder="<?php esc_attr_e('Search cities...', 'NORDBOOKING'); ?>">
                            </div>
                            <div class="nordbooking-filter-item">
                                <select id="country-filter" class="nordbooking-filter-select">
                                    <option value=""><?php esc_html_e('All Countries', 'NORDBOOKING'); ?></option>
                                </select>
                            </div>
                            <div class="nordbooking-filter-item">
                                <select id="status-filter" class="nordbooking-filter-select">
                                    <option value=""><?php esc_html_e('All Statuses', 'NORDBOOKING'); ?></option>
                                    <option value="active"><?php esc_html_e('Active', 'NORDBOOKING'); ?></option>
                                    <option value="inactive"><?php esc_html_e('Inactive', 'NORDBOOKING'); ?></option>
                                </select>
                            </div>
                            <button type="button" id="clear-coverage-filters-btn" class="btn btn-outline btn-sm">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                                <?php esc_html_e('Clear', 'NORDBOOKING'); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Bulk Actions Bar -->
                <div class="bulk-actions-bar" id="bulk-actions-bar" style="display: none;">
                    <div class="bulk-actions-info">
                        <span id="selected-count">0</span> <?php esc_html_e('cities selected', 'NORDBOOKING'); ?>
                    </div>
                    <div class="bulk-actions-buttons">
                        <button type="button" id="bulk-enable-btn" class="btn btn-success btn-sm">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                            <?php esc_html_e('Enable', 'NORDBOOKING'); ?>
                        </button>
                        <button type="button" id="bulk-disable-btn" class="btn btn-warning btn-sm">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6"/><path d="M9 9l6 6"/></svg>
                            <?php esc_html_e('Disable', 'NORDBOOKING'); ?>
                        </button>
                        <button type="button" id="bulk-remove-btn" class="btn btn-destructive btn-sm">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><path d="m19 6-1 14H6L5 6"/></svg>
                            <?php esc_html_e('Remove', 'NORDBOOKING'); ?>
                        </button>
                        <button type="button" id="bulk-cancel-btn" class="btn btn-outline btn-sm">
                            <?php esc_html_e('Cancel', 'NORDBOOKING'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Service Coverage Display -->
            <div class="service-coverage-container">
                <div id="coverage-loading" class="NORDBOOKING-loading-state" style="display: none;">
                    <div class="NORDBOOKING-spinner"></div>
                    <p><?php esc_html_e('Loading your service coverage...', 'NORDBOOKING'); ?></p>
                </div>

                <!-- Coverage Table Header -->
                <div class="coverage-table-header" id="coverage-table-header" style="display: none;">
                    <div class="coverage-header-row">
                        <div class="coverage-header-cell select-cell">
                            <input type="checkbox" id="select-all-coverage" class="coverage-checkbox">
                        </div>
                        <div class="coverage-header-cell country-cell">
                            <?php esc_html_e('Country', 'NORDBOOKING'); ?>
                        </div>
                        <div class="coverage-header-cell city-cell">
                            <?php esc_html_e('City', 'NORDBOOKING'); ?>
                        </div>
                        <div class="coverage-header-cell areas-cell">
                            <?php esc_html_e('Areas', 'NORDBOOKING'); ?>
                        </div>
                        <div class="coverage-header-cell status-cell">
                            <?php esc_html_e('Status', 'NORDBOOKING'); ?>
                        </div>
                        <div class="coverage-header-cell actions-cell">
                            <?php esc_html_e('Actions', 'NORDBOOKING'); ?>
                        </div>
                    </div>
                </div>

                <div id="service-coverage-list" class="coverage-table-body">
                    <!-- Coverage will be loaded here -->
                </div>

                <div id="no-coverage-state" class="NORDBOOKING-empty-state" style="display: none;">
                    <svg class="NORDBOOKING-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <h4 class="NORDBOOKING-empty-state-title"><?php esc_html_e('No Service Areas Yet', 'NORDBOOKING'); ?></h4>
                    <p class="NORDBOOKING-empty-state-text">
                        <?php esc_html_e('Start by selecting a country above to define your service coverage areas.', 'NORDBOOKING'); ?>
                    </p>
                </div>
            </div>

            <!-- Pagination -->
            <div id="coverage-pagination" class="NORDBOOKING-pagination-container"></div>
        </div>
    </div>
</div>

<!-- The Area Selection Modal is now handled by the MoBookingDialog component -->

<script>
// Enhanced localization for JavaScript
window.nordbooking_areas_i18n = <?php echo json_encode([
    // Basic messages
    'loading' => __('Loading...', 'NORDBOOKING'),
    'error' => __('Error', 'NORDBOOKING'),
    'success' => __('Success', 'NORDBOOKING'),
    'saving' => __('Saving...', 'NORDBOOKING'),
    
    // Selection flow
    'choose_country' => __('Choose a country to add...', 'NORDBOOKING'),
    'select_cities' => __('Select cities in', 'NORDBOOKING'),
    'select_areas' => __('Select areas in', 'NORDBOOKING'),
    'no_cities_available' => __('No cities available for this country', 'NORDBOOKING'),
    'no_areas_available' => __('No areas available for this city', 'NORDBOOKING'),
    'country_change_warning' => __('Changing country will remove all previously selected cities and areas. Are you sure you want to continue?', 'NORDBOOKING'),
    'loading_countries' => __('Loading countries...', 'NORDBOOKING'),
    
    // Actions
    'add_country' => __('Add Country', 'NORDBOOKING'),
    'save_selections' => __('Save Selected Areas', 'NORDBOOKING'),
    'back_to_cities' => __('← Back to Cities', 'NORDBOOKING'),
    'cancel' => __('Cancel', 'NORDBOOKING'),
    
    // Status and feedback
    'areas_selected' => __('{{count}} areas selected', 'NORDBOOKING'),
    'cities_selected' => __('{{count}} cities selected', 'NORDBOOKING'),
    'country_added_success' => __('Service areas added successfully for {{country}}!', 'NORDBOOKING'),
    'selection_saved' => __('Your service area selections have been saved.', 'NORDBOOKING'),
    
    // Management
    'enable_area' => __('Enable Area', 'NORDBOOKING'),
    'disable_area' => __('Disable Area', 'NORDBOOKING'),
    'remove_country' => __('Remove Country', 'NORDBOOKING'),
    'confirm_remove_country' => __('Are you sure you want to remove all service areas for {{country}}?', 'NORDBOOKING'),
    'confirm_disable_area' => __('Disable this service area?', 'NORDBOOKING'),
    
    // Bulk actions
    'bulk_enable_confirm' => __('Enable {{count}} selected cities?', 'NORDBOOKING'),
    'bulk_disable_confirm' => __('Disable {{count}} selected cities?', 'NORDBOOKING'),
    'bulk_remove_confirm' => __('Remove {{count}} selected cities? This action cannot be undone.', 'NORDBOOKING'),
    'bulk_action_success' => __('Bulk action completed successfully.', 'NORDBOOKING'),
    'bulk_action_error' => __('Some items failed to process during bulk action.', 'NORDBOOKING'),
    
    // Filters
    'all_countries' => __('All Countries', 'NORDBOOKING'),
    'all_status' => __('All Status', 'NORDBOOKING'),
    'active' => __('Active', 'NORDBOOKING'),
    'inactive' => __('Inactive', 'NORDBOOKING'),
    'clear' => __('Clear', 'NORDBOOKING'),
    
    // Pagination
    'previous' => __('Previous', 'NORDBOOKING'),
    'next' => __('Next', 'NORDBOOKING'),
]); ?>
</script>

<?php
// Enqueue enhanced scripts and styles
wp_enqueue_script('NORDBOOKING-country-flags', get_template_directory_uri() . '/assets/js/country-flags.js', [], '1.0.0', true);
wp_enqueue_script('NORDBOOKING-enhanced-areas', get_template_directory_uri() . '/assets/js/enhanced-areas.js', ['jquery', 'wp-i18n', 'nordbooking-dialog', 'NORDBOOKING-country-flags'], '1.0.0', true);

wp_localize_script('NORDBOOKING-enhanced-areas', 'nordbooking_areas_params', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('nordbooking_dashboard_nonce'),
    'user_id' => $current_user_id,
    'country_code' => '', // Will be set dynamically
    'i18n' => [
        'loading_countries' => __('Loading countries...', 'NORDBOOKING'),
        'loading_cities' => __('Loading cities...', 'NORDBOOKING'),
        'loading_areas' => __('Loading areas...', 'NORDBOOKING'),
        'no_countries_available' => __('No countries available to configure.', 'NORDBOOKING'),
        'no_cities_available' => __('No cities available to configure.', 'NORDBOOKING'),
        'no_areas_available' => __('No areas found for this city.', 'NORDBOOKING'),
        'save_areas' => __('Save Areas', 'NORDBOOKING'),
        'saving' => __('Saving...', 'NORDBOOKING'),
        'areas_saved_success' => __('Service areas for %s have been updated.', 'NORDBOOKING'),
        'error_saving' => __('An error occurred while saving. Please try again.', 'NORDBOOKING'),
        'confirm_remove_city' => __('Are you sure you want to remove all service areas for %s? This cannot be undone.', 'NORDBOOKING'),
        'city_removed_success' => __('All service areas for %s have been removed.', 'NORDBOOKING'),
        'error_removing_city' => __('Failed to remove city. Please try again.', 'NORDBOOKING'),
        'country_change_warning' => __('Changing country will remove all previously selected cities and areas. Are you sure you want to continue?', 'NORDBOOKING'),
        'select_cities_in' => __('Select cities in %s', 'NORDBOOKING'),
    ],
]);
?>