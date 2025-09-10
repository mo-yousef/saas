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

<div class="NORDBOOKING-dashboard-page">
    <div class="NORDBOOKING-page-header">
        <div class="NORDBOOKING-page-header-heading">
            <span class="NORDBOOKING-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('areas'); ?>
            </span>
            <h1 class="NORDBOOKING-page-title"><?php esc_html_e('Service Areas', 'NORDBOOKING'); ?></h1>
        </div>
        <p class="NORDBOOKING-page-description">
            <?php esc_html_e('Manage your service coverage by selecting cities and their specific areas within Sweden.', 'NORDBOOKING'); ?>
        </p>
    </div>

    <div class="NORDBOOKING-dashboard-content">
        <!-- City Selection Card -->
        <div class="NORDBOOKING-card card-bs">
            <div class="NORDBOOKING-card-header">
                <h3 class="NORDBOOKING-card-title">
                    <?php esc_html_e('Select Swedish Cities', 'NORDBOOKING'); ?>
                </h3>
                <p class="NORDBOOKING-card-description">
                    <?php esc_html_e('Click on a city to manage its service areas. Areas can be enabled or disabled individually.', 'NORDBOOKING'); ?>
                </p>
            </div>
            <div id="cities-grid-container" class="cities-grid-container">
                <!-- Cities will be loaded here by JavaScript -->
                <div class="NORDBOOKING-loading-state">
                    <div class="NORDBOOKING-spinner"></div>
                    <p><?php esc_html_e('Loading Swedish cities...', 'NORDBOOKING'); ?></p>
                </div>
            </div>
        </div>

        <!-- Current Service Coverage -->
        <div class="NORDBOOKING-card NORDBOOKING-card-full-width">
            <div class="NORDBOOKING-card-header">
                <h3 class="NORDBOOKING-card-title">
                    <svg class="NORDBOOKING-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php esc_html_e('Your Service Coverage', 'NORDBOOKING'); ?>
                </h3>
                <p class="NORDBOOKING-card-description">
                    <?php esc_html_e('Manage your active service areas by country. You can enable/disable specific cities or areas, or remove entire countries.', 'NORDBOOKING'); ?>
                </p>
            </div>

            <!-- Search and Filter Controls -->
            <div class="NORDBOOKING-filters-wrapper">
                <form id="NORDBOOKING-areas-filter-form" class="NORDBOOKING-filters-form">
                    <div class="NORDBOOKING-filters-main">
                        <div class="NORDBOOKING-filter-item NORDBOOKING-filter-item-search">
                            <label for="coverage-search"><?php esc_html_e('Search', 'NORDBOOKING'); ?></label>
                            <input type="search" id="coverage-search" class="regular-text" placeholder="<?php esc_attr_e('Search cities...', 'NORDBOOKING'); ?>">
                        </div>
                        <div class="NORDBOOKING-filter-item">
                            <label for="city-filter"><?php esc_html_e('City', 'NORDBOOKING'); ?></label>
                            <select id="city-filter" class="NORDBOOKING-filter-select">
                                <option value=""><?php esc_html_e('All Cities', 'NORDBOOKING'); ?></option>
                            </select>
                        </div>
                        <div class="NORDBOOKING-filter-item">
                            <label for="status-filter"><?php esc_html_e('Status', 'NORDBOOKING'); ?></label>
                            <select id="status-filter" class="NORDBOOKING-filter-select">
                                <option value=""><?php esc_html_e('All Statuses', 'NORDBOOKING'); ?></option>
                                <option value="active"><?php esc_html_e('Active', 'NORDBOOKING'); ?></option>
                                <option value="inactive"><?php esc_html_e('Inactive', 'NORDBOOKING'); ?></option>
                            </select>
                        </div>
                        <div class="NORDBOOKING-filter-actions">
                            <button type="button" id="clear-coverage-filters-btn" class="btn btn-outline">
                                <?php echo nordbooking_get_feather_icon('x'); ?>
                                <span class="btn-text"><?php esc_html_e('Clear', 'NORDBOOKING'); ?></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Service Coverage Display -->
            <div class="service-coverage-container">
                <div id="coverage-loading" class="NORDBOOKING-loading-state" style="display: none;">
                    <div class="NORDBOOKING-spinner"></div>
                    <p><?php esc_html_e('Loading your service coverage...', 'NORDBOOKING'); ?></p>
                </div>

                <div id="service-coverage-list">
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
wp_enqueue_script('NORDBOOKING-enhanced-areas', get_template_directory_uri() . '/assets/js/enhanced-areas.js', ['jquery', 'wp-i18n'], '1.0.0', true);

wp_localize_script('NORDBOOKING-enhanced-areas', 'nordbooking_areas_params', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('nordbooking_dashboard_nonce'),
    'user_id' => $current_user_id,
    'country_code' => 'SE', // Hardcode Sweden
    'i18n' => [
        'loading_cities' => __('Loading Swedish cities...', 'NORDBOOKING'),
        'loading_areas' => __('Loading areas...', 'NORDBOOKING'),
        'no_cities_available' => __('No cities available to configure.', 'NORDBOOKING'),
        'no_areas_available' => __('No areas found for this city.', 'NORDBOOKING'),
        'save_areas' => __('Save Areas', 'NORDBOOKING'),
        'saving' => __('Saving...', 'NORDBOOKING'),
        'areas_saved_success' => __('Service areas for %s have been updated.', 'NORDBOOKING'),
        'error_saving' => __('An error occurred while saving. Please try again.', 'NORDBOOKING'),
        'confirm_remove_city' => __('Are you sure you want to remove all service areas for %s? This cannot be undone.', 'NORDBOOKING'),
        'city_removed_success' => __('All service areas for %s have been removed.', 'NORDBOOKING'),
        'error_removing_city' => __('Failed to remove city. Please try again.', 'NORDBOOKING'),
    ],
]);
?>