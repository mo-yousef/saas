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

<div>
    <h3 class="text-3xl font-medium text-gray-700 dark:text-gray-200">Service Areas</h3>
    <div class="mt-4">
        <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Select Swedish Cities</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Click on a city to manage its service areas. Areas can be enabled or disabled individually.
            </p>
            <div id="cities-grid-container" class="grid grid-cols-2 gap-6 mt-6 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                <!-- Cities will be loaded here by JavaScript -->
                <div class="text-center text-gray-500 dark:text-gray-400">
                    <div class="w-8 h-8 mx-auto border-4 border-t-4 border-gray-200 rounded-full animate-spin" style="border-top-color: #4f46e5;"></div>
                    <p class="mt-2">Loading Swedish cities...</p>
                </div>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Your Service Coverage</h2>
            <div class="flex justify-between mt-2">
                <div>
                    <input type="text" id="coverage-search" placeholder="Search cities or areas..."
                           class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-200">
                </div>
                <div class="flex">
                    <select id="city-filter"
                            class="px-4 py-2 border-t border-b border-l border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <option value="">All Cities</option>
                    </select>
                    <select id="status-filter"
                            class="px-4 py-2 border-t border-b border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <button id="clear-coverage-filters-btn"
                            class="px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-r-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500">
                        Clear
                    </button>
                </div>
            </div>
            <div id="service-coverage-list" class="mt-6">
                <!-- Coverage will be loaded here -->
            </div>
            <div id="no-coverage-state" class="hidden mt-6 text-center text-gray-500 dark:text-gray-400">
                <h3 class="text-xl font-medium">No Service Areas Yet</h3>
                <p class="mt-2">Start by selecting a country above to define your service coverage areas.</p>
            </div>
            <div id="coverage-pagination" class="mt-6"></div>
        </div>
    </div>
</div>

<div id="area-selection-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden bg-black bg-opacity-50">
    <div class="w-full max-w-2xl p-6 bg-white rounded-md shadow-lg dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <h3 class="text-2xl"><?php esc_html_e('Select Areas for', 'mobooking'); ?> <span id="modal-city-name"></span></h3>
            <button type="button" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    onclick="document.getElementById('area-selection-modal').classList.add('hidden')">
                &times;
            </button>
        </div>
        <div class="mt-4">
            <div class="flex justify-between">
                <button type="button" id="modal-select-all"
                        class="text-indigo-600 hover:text-indigo-900 dark:hover:text-indigo-400">Select All
                </button>
                <button type="button" id="modal-deselect-all"
                        class="text-indigo-600 hover:text-indigo-900 dark:hover:text-indigo-400">Deselect All
                </button>
            </div>
            <div id="modal-areas-grid" class="grid grid-cols-2 gap-4 mt-4 overflow-y-auto max-h-96">
                <!-- Areas for the selected city will be loaded here -->
            </div>
        </div>
        <div class="flex justify-end mt-6">
            <button type="button" onclick="document.getElementById('area-selection-modal').classList.add('hidden')"
                    class="px-4 py-2 mr-2 font-medium tracking-wide text-gray-700 capitalize transition-colors duration-200 transform bg-gray-200 rounded-md hover:bg-gray-300 focus:outline-none focus:bg-gray-300">
                Cancel
            </button>
            <button type="button" id="modal-save-btn"
                    class="px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500">
                Save Areas
            </button>
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
wp_enqueue_script('mobooking-enhanced-areas', get_template_directory_uri() . '/assets/js/enhanced-areas.js', ['jquery', 'wp-i18n'], '1.0.0', true);
wp_enqueue_style('mobooking-enhanced-areas', get_template_directory_uri() . '/assets/css/enhanced-areas.css', [], '1.0.0');

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