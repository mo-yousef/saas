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

<div class="mobooking-dashboard-page">
    <div class="mobooking-page-header">
        <div class="mobooking-page-header-heading">
            <span class="mobooking-page-header-icon">
                <?php echo mobooking_get_dashboard_menu_icon('areas'); ?>
            </span>
            <h1 class="mobooking-page-title"><?php esc_html_e('Service Areas', 'mobooking'); ?></h1>
        </div>
        <p class="mobooking-page-description">
            <?php esc_html_e('Manage your service coverage by selecting cities and their specific areas within Sweden.', 'mobooking'); ?>
        </p>
    </div>

    <div class="mobooking-dashboard-content">
        <!-- City Selection Card -->
        <div class="mobooking-card card-bs">
            <div class="mobooking-card-header">
                <h3 class="mobooking-card-title">
                    <?php esc_html_e('Select Swedish Cities', 'mobooking'); ?>
                </h3>
                <p class="mobooking-card-description">
                    <?php esc_html_e('Click on a city to manage its service areas. Areas can be enabled or disabled individually.', 'mobooking'); ?>
                </p>
            </div>
            <div id="cities-grid-container" class="cities-grid-container">
                <!-- Cities will be loaded here by JavaScript -->
                <div class="mobooking-loading-state">
                    <div class="mobooking-spinner"></div>
                    <p><?php esc_html_e('Loading Swedish cities...', 'mobooking'); ?></p>
                </div>
            </div>
        </div>

        <!-- Current Service Coverage -->
        <div class="mobooking-card mobooking-card-full-width">
            <div class="mobooking-card-header">
                <h3 class="mobooking-card-title">
                    <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php esc_html_e('Your Service Coverage', 'mobooking'); ?>
                </h3>
                <p class="mobooking-card-description">
                    <?php esc_html_e('Manage your active service areas by country. You can enable/disable specific cities or areas, or remove entire countries.', 'mobooking'); ?>
                </p>
            </div>

            <!-- Search and Filter Controls -->
            <div class="mobooking-table-controls">
                <div class="mobooking-search-filter-row">
                    <div class="mobooking-search-group">
                        <div class="mobooking-search-input-wrapper">
                            <input type="text" id="coverage-search" class="mobooking-search-input" placeholder="<?php esc_attr_e('Search cities or areas...', 'mobooking'); ?>">
                        </div>
                    </div>
                    <div class="mobooking-filter-group">
                        <select id="city-filter" class="mobooking-form-select mobooking-form-select-sm">
                            <option value=""><?php esc_html_e('All Cities', 'mobooking'); ?></option>
                        </select>
                        <select id="status-filter" class="mobooking-form-select mobooking-form-select-sm">
                            <option value=""><?php esc_html_e('All Statuses', 'mobooking'); ?></option>
                            <option value="active"><?php esc_html_e('Active', 'mobooking'); ?></option>
                            <option value="inactive"><?php esc_html_e('Inactive', 'mobooking'); ?></option>
                        </select>
                        <button type="button" id="clear-coverage-filters-btn" class="btn btn-secondary btn-sm">
                            <?php esc_html_e('Clear', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Service Coverage Display -->
            <div class="service-coverage-container">
                <div id="coverage-loading" class="mobooking-loading-state" style="display: none;">
                    <div class="mobooking-spinner"></div>
                    <p><?php esc_html_e('Loading your service coverage...', 'mobooking'); ?></p>
                </div>

                <div id="service-coverage-list">
                    <!-- Coverage will be loaded here -->
                </div>

                <div id="no-coverage-state" class="mobooking-empty-state" style="display: none;">
                    <svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <h4 class="mobooking-empty-state-title"><?php esc_html_e('No Service Areas Yet', 'mobooking'); ?></h4>
                    <p class="mobooking-empty-state-text">
                        <?php esc_html_e('Start by selecting a country above to define your service coverage areas.', 'mobooking'); ?>
                    </p>
                </div>
            </div>

            <!-- Pagination -->
            <div id="coverage-pagination" class="mobooking-pagination-container"></div>
        </div>
    </div>
</div>

<!-- The Area Selection Modal is now handled by the MoBookingDialog component -->

<script>
// Enhanced localization for JavaScript
window.mobooking_areas_i18n = <?php echo json_encode([
    // Basic messages
    'loading' => __('Loading...', 'mobooking'),
    'error' => __('Error', 'mobooking'),
    'success' => __('Success', 'mobooking'),
    'saving' => __('Saving...', 'mobooking'),
    
    // Selection flow
    'choose_country' => __('Choose a country to add...', 'mobooking'),
    'select_cities' => __('Select cities in', 'mobooking'),
    'select_areas' => __('Select areas in', 'mobooking'),
    'no_cities_available' => __('No cities available for this country', 'mobooking'),
    'no_areas_available' => __('No areas available for this city', 'mobooking'),
    
    // Actions
    'add_country' => __('Add Country', 'mobooking'),
    'save_selections' => __('Save Selected Areas', 'mobooking'),
    'back_to_cities' => __('← Back to Cities', 'mobooking'),
    'cancel' => __('Cancel', 'mobooking'),
    
    // Status and feedback
    'areas_selected' => __('{{count}} areas selected', 'mobooking'),
    'cities_selected' => __('{{count}} cities selected', 'mobooking'),
    'country_added_success' => __('Service areas added successfully for {{country}}!', 'mobooking'),
    'selection_saved' => __('Your service area selections have been saved.', 'mobooking'),
    
    // Management
    'enable_area' => __('Enable Area', 'mobooking'),
    'disable_area' => __('Disable Area', 'mobooking'),
    'remove_country' => __('Remove Country', 'mobooking'),
    'confirm_remove_country' => __('Are you sure you want to remove all service areas for {{country}}?', 'mobooking'),
    'confirm_disable_area' => __('Disable this service area?', 'mobooking'),
    
    // Filters
    'all_countries' => __('All Countries', 'mobooking'),
    'all_status' => __('All Status', 'mobooking'),
    'active' => __('Active', 'mobooking'),
    'inactive' => __('Inactive', 'mobooking'),
    'clear' => __('Clear', 'mobooking'),
    
    // Pagination
    'previous' => __('Previous', 'mobooking'),
    'next' => __('Next', 'mobooking'),
]); ?>
</script>

<?php
// Enqueue enhanced scripts and styles
wp_enqueue_script('mobooking-enhanced-areas', get_template_directory_uri() . '/assets/js/enhanced-areas.js', ['jquery', 'wp-i18n'], '1.0.0', true);

wp_localize_script('mobooking-enhanced-areas', 'mobooking_areas_params', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
    'user_id' => $current_user_id,
    'country_code' => 'SE', // Hardcode Sweden
    'i18n' => [
        'loading_cities' => __('Loading Swedish cities...', 'mobooking'),
        'loading_areas' => __('Loading areas...', 'mobooking'),
        'no_cities_available' => __('No cities available to configure.', 'mobooking'),
        'no_areas_available' => __('No areas found for this city.', 'mobooking'),
        'save_areas' => __('Save Areas', 'mobooking'),
        'saving' => __('Saving...', 'mobooking'),
        'areas_saved_success' => __('Service areas for %s have been updated.', 'mobooking'),
        'error_saving' => __('An error occurred while saving. Please try again.', 'mobooking'),
        'confirm_remove_city' => __('Are you sure you want to remove all service areas for %s? This cannot be undone.', 'mobooking'),
        'city_removed_success' => __('All service areas for %s have been removed.', 'mobooking'),
        'error_removing_city' => __('Failed to remove city. Please try again.', 'mobooking'),
    ],
]);
?>