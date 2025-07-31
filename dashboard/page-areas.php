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
        <h2 class="mobooking-page-title">
            <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            </svg>
            <?php esc_html_e('Service Areas Management', 'mobooking'); ?>
        </h2>
        <p class="mobooking-page-description">
            <?php esc_html_e('Build your service coverage by selecting countries, then choosing specific cities and areas within them.', 'mobooking'); ?>
        </p>
    </div>

    <div class="mobooking-dashboard-content">
        <!-- Country Display Card -->
        <div class="mobooking-card">
            <div class="mobooking-card-header">
                <h3 class="mobooking-card-title">
                    <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?php esc_html_e('Service Country', 'mobooking'); ?>
                </h3>
                <p class="mobooking-card-description">
                    <?php esc_html_e('Your service areas are configured for Sweden.', 'mobooking'); ?>
                </p>
            </div>
            <div class="country-selection-form">
                <div class="mobooking-form-group">
                    <label class="mobooking-form-label">
                        <?php esc_html_e('Selected Country', 'mobooking'); ?>
                    </label>
                    <div class="static-country-display">Sweden</div>
                </div>
            </div>
        </div>

        <!-- Cities & Areas Selection Card (Initially Hidden) -->
        <div class="mobooking-card" id="cities-areas-selection-card" style="display: none;">
            <div class="mobooking-card-header">
                <div class="selection-header-content">
                    <div>
                        <h3 class="mobooking-card-title">
                            <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <?php esc_html_e('Select Cities & Areas', 'mobooking'); ?>
                            <span id="selected-country-name" class="country-badge"></span>
                        </h3>
                        <p class="mobooking-card-description">
                            <?php esc_html_e('Choose specific cities and areas within the selected country for your service coverage.', 'mobooking'); ?>
                        </p>
                    </div>
                    <button type="button" id="cancel-selection-btn" class="mobooking-btn mobooking-btn-secondary mobooking-btn-sm">
                        <?php esc_html_e('Cancel', 'mobooking'); ?>
                    </button>
                </div>
            </div>

            <!-- Cities Selection -->
            <div class="cities-selection-section">
                <h4 class="section-title"><?php esc_html_e('Available Cities', 'mobooking'); ?></h4>
                <div id="cities-grid" class="selection-grid">
                    <!-- Cities will be loaded here -->
                </div>
            </div>

            <!-- Areas Selection (Initially Hidden) -->
            <div class="areas-selection-section" id="areas-selection-section" style="display: none;">
                <h4 class="section-title">
                    <?php esc_html_e('Available Areas in', 'mobooking'); ?> 
                    <span id="selected-city-name" class="city-badge"></span>
                </h4>
                <div id="areas-grid" class="selection-grid">
                    <!-- Areas will be loaded here -->
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mobooking-form-actions" id="selection-actions" style="display: none;">
                <button type="button" id="save-selections-btn" class="mobooking-btn mobooking-btn-success">
                    <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <?php esc_html_e('Save Selected Areas', 'mobooking'); ?>
                </button>
                <button type="button" id="back-to-cities-btn" class="mobooking-btn mobooking-btn-secondary" style="display: none;">
                    <?php esc_html_e('← Back to Cities', 'mobooking'); ?>
                </button>
            </div>

            <div id="selection-feedback" class="mobooking-feedback" style="display: none;"></div>
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
                            <svg class="mobooking-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" id="coverage-search" class="mobooking-search-input" placeholder="<?php esc_attr_e('Search countries, cities, areas...', 'mobooking'); ?>">
                        </div>
                    </div>

                    <div class="mobooking-filter-group">
                        <select id="country-filter" class="mobooking-form-select mobooking-form-select-sm">
                            <option value=""><?php esc_html_e('All Countries', 'mobooking'); ?></option>
                        </select>
                        <select id="status-filter" class="mobooking-form-select mobooking-form-select-sm">
                            <option value=""><?php esc_html_e('All Status', 'mobooking'); ?></option>
                            <option value="active"><?php esc_html_e('Active', 'mobooking'); ?></option>
                            <option value="inactive"><?php esc_html_e('Inactive', 'mobooking'); ?></option>
                        </select>
                        <button type="button" id="clear-coverage-filters-btn" class="mobooking-btn mobooking-btn-secondary mobooking-btn-sm">
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
wp_enqueue_script('mobooking-enhanced-areas', get_template_directory_uri() . '/assets/js/enhanced-areas.js', ['jquery'], '1.0.0', true);
wp_enqueue_style('mobooking-enhanced-areas', get_template_directory_uri() . '/assets/css/enhanced-areas.css', [], '1.0.0');

wp_localize_script('mobooking-enhanced-areas', 'mobooking_areas_params', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
    'user_id' => $current_user_id,
]);
?>
<?php
// // Enqueue required scripts and styles
//         wp_enqueue_style('mobooking-dashboard-areas-refactored', 
//             get_template_directory_uri() . '/assets/css/dashboard-areas-refactored.css', 
//             [], '1.0.0');
// wp_enqueue_script('mobooking-dashboard-areas-refactored', get_template_directory_uri() . '/assets/js/dashboard-areas-refactored.js', ['jquery'], '1.0.0', true);
// wp_localize_script('mobooking-dashboard-areas-refactored', 'mobooking_areas_params', [
//     'ajax_url' => admin_url('admin-ajax.php'),
//     'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
//     'user_id' => $current_user_id,
// ]);
?>


<style>
svg {
    max-width: 20px;
}


</style>