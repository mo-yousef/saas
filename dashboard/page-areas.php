<?php
/**
 * Template Name: Service Areas Management Dashboard Page
 * 
 * @package MoBooking
 */

defined('ABSPATH') || exit;


// Ensure user is logged in and has proper permissions
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}

$current_user = wp_get_current_user();
if (!in_array('mobooking_business_owner', $current_user->roles)) {
    wp_die(__('Access denied. You must be a business owner to access this page.', 'mobooking'));
}

// Enqueue necessary scripts and styles
wp_enqueue_script('mobooking-dashboard-areas', get_template_directory_uri() . '/assets/js/dashboard-areas.js', ['jquery'], '1.0.0', true);
wp_enqueue_style('mobooking-dashboard-areas', get_template_directory_uri() . '/assets/css/dashboard-areas.css', [], '1.0.0');

// Localize script with AJAX parameters and translations
wp_localize_script('mobooking-dashboard-areas', 'mobooking_areas_params', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
    'user_id' => get_current_user_id(),
    'i18n' => [
        'fields_required' => __('Country and area information are required.', 'mobooking'),
        'error_loading' => __('Error loading areas. Please try again.', 'mobooking'),
        'success_added' => __('Service area added successfully!', 'mobooking'),
        'success_updated' => __('Service area updated successfully!', 'mobooking'),
        'success_deleted' => __('Service area deleted successfully!', 'mobooking'),
        'error_generic' => __('An error occurred. Please try again.', 'mobooking'),
        'confirm_delete' => __('Are you sure you want to delete this service area?', 'mobooking'),
        'no_areas_found' => __('No service areas found. Add your first area below.', 'mobooking'),
        'loading' => __('Loading...', 'mobooking'),
        'select_country' => __('Select a country first', 'mobooking'),
        'select_city' => __('Select a city to view available areas', 'mobooking'),
        'areas_added' => __('Selected areas have been added to your service coverage!', 'mobooking'),
        'no_areas_selected' => __('Please select at least one area to add.', 'mobooking')
    ]
]);
?>

<div class="mobooking-dashboard-content">
    <!-- Page Header -->
    <div class="mobooking-page-header">
        <div class="mobooking-page-header-content">
            <h1 class="mobooking-page-title">
                <svg class="mobooking-page-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                </svg>
                <?php esc_html_e('Service Areas', 'mobooking'); ?>
            </h1>
            <p class="mobooking-page-description">
                <?php esc_html_e('Manage your service coverage areas to control where you offer your cleaning services. You can select from available regions or add specific locations manually for precise coverage control.', 'mobooking'); ?>
            </p>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="mobooking-dashboard-grid">
        
        <!-- Quick Area Selection Card -->
        <div class="mobooking-card" id="mobooking-area-selection-wrapper">
            <div class="mobooking-card-header">
                <h3 class="mobooking-card-title">
                    <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <?php esc_html_e('Quick Area Selection', 'mobooking'); ?>
                </h3>
                <p class="mobooking-card-description">
                    <?php esc_html_e('Browse and select service areas by country and city. This method allows you to quickly add multiple areas from predefined regional data.', 'mobooking'); ?>
                </p>
            </div>

            <div class="mobooking-form-row">
                <div class="mobooking-form-group">
                    <label for="mobooking-country-selector" class="mobooking-form-label">
                        <?php esc_html_e('Select Country', 'mobooking'); ?>
                    </label>
                    <select id="mobooking-country-selector" name="mobooking_country_selector" class="mobooking-form-select">
                        <option value=""><?php esc_html_e('Choose a country...', 'mobooking'); ?></option>
                    </select>
                </div>

                <div class="mobooking-form-group">
                    <label for="mobooking-city-selector" class="mobooking-form-label">
                        <?php esc_html_e('Select City', 'mobooking'); ?>
                    </label>
                    <select id="mobooking-city-selector" name="mobooking_city_selector" class="mobooking-form-select" disabled>
                        <option value=""><?php esc_html_e('First select a country...', 'mobooking'); ?></option>
                    </select>
                </div>
            </div>

            <div class="mobooking-form-group">
                <label class="mobooking-form-label">
                    <?php esc_html_e('Available Areas & ZIP Codes', 'mobooking'); ?>
                </label>
                <div id="mobooking-area-zip-selector-container" class="mobooking-zip-selector">
                    <div class="mobooking-empty-state">
                        <svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <p class="mobooking-empty-state-text">
                            <?php esc_html_e('Select a country and city to browse available service areas with their ZIP codes', 'mobooking'); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="mobooking-form-actions">
                <button type="button" id="mobooking-add-selected-areas-btn" class="mobooking-btn mobooking-btn-primary" disabled>
                    <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <?php esc_html_e('Add Selected Areas', 'mobooking'); ?>
                </button>
            </div>

            <div id="mobooking-selection-feedback" class="mobooking-feedback" style="display: none;"></div>
        </div>

        <!-- Manual Entry Card -->
        <div class="mobooking-card" id="mobooking-area-form-wrapper">
            <div class="mobooking-card-header">
                <h3 class="mobooking-card-title" id="mobooking-area-form-title">
                    <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <?php esc_html_e('Manual Area Entry', 'mobooking'); ?>
                </h3>
                <p class="mobooking-card-description">
                    <?php esc_html_e('Add individual service areas by manually entering the country name and specific area details. Perfect for locations not covered in the quick selection above.', 'mobooking'); ?>
                </p>
            </div>

            <form id="mobooking-area-form">
                <input type="hidden" id="mobooking-area-id" name="area_id" value="">
                
                <div class="mobooking-form-row">
                    <div class="mobooking-form-group">
                        <label for="mobooking-area-country" class="mobooking-form-label">
                            <?php esc_html_e('Country', 'mobooking'); ?>
                            <span class="mobooking-required">*</span>
                        </label>
                        <select 
                            id="mobooking-area-country" 
                            name="country_name" 
                            required 
                            class="mobooking-form-select"
                        >
                            <option value=""><?php esc_html_e('Select a country...', 'mobooking'); ?></option>
                        </select>
                        <small class="mobooking-form-helper">
                            <?php esc_html_e('Choose the country where this service area is located', 'mobooking'); ?>
                        </small>
                    </div>
                </div>

                <div class="mobooking-form-row">
                    <div class="mobooking-form-group">
                        <label for="mobooking-area-name" class="mobooking-form-label">
                            <?php esc_html_e('Area Name', 'mobooking'); ?>
                            <span class="mobooking-required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="mobooking-area-name" 
                            name="area_name" 
                            required 
                            class="mobooking-form-input" 
                            placeholder="<?php esc_attr_e('e.g., Downtown Manhattan, Brooklyn Heights, Westminster', 'mobooking'); ?>"
                            maxlength="100"
                        >
                        <small class="mobooking-form-helper">
                            <?php esc_html_e('Enter a descriptive name for this service area or neighborhood', 'mobooking'); ?>
                        </small>
                    </div>

                    <div class="mobooking-form-group">
                        <label for="mobooking-area-zipcode" class="mobooking-form-label">
                            <?php esc_html_e('ZIP / Postal Code', 'mobooking'); ?>
                            <span class="mobooking-required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="mobooking-area-zipcode" 
                            name="area_zipcode" 
                            required 
                            class="mobooking-form-input" 
                            placeholder="<?php esc_attr_e('e.g., 10001, M5V 3A3, SW1A 1AA', 'mobooking'); ?>"
                            maxlength="15"
                        >
                        <small class="mobooking-form-helper">
                            <?php esc_html_e('Enter the ZIP or postal code for this service area', 'mobooking'); ?>
                        </small>
                    </div>
                </div>

                <div id="mobooking-area-form-feedback" class="mobooking-feedback" style="display: none;"></div>

                <div class="mobooking-form-actions">
                    <button type="submit" id="mobooking-save-area-btn" class="mobooking-btn mobooking-btn-primary">
                        <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <?php esc_html_e('Add Area', 'mobooking'); ?>
                    </button>
                    <button type="button" id="mobooking-cancel-edit-area-btn" class="mobooking-btn mobooking-btn-secondary" style="display: none;">
                        <?php esc_html_e('Cancel Edit', 'mobooking'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Current Service Areas List -->
        <div class="mobooking-card mobooking-card-full-width">
            <div class="mobooking-card-header">
                <h3 class="mobooking-card-title">
                    <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-3-4a2 2 0 00-2-2h-6a2 2 0 00-2 2v1h10V5z"/>
                    </svg>
                    <?php esc_html_e('Current Service Areas', 'mobooking'); ?>
                </h3>
                <p class="mobooking-card-description">
                    <?php esc_html_e('Manage your existing service areas. You can edit or remove areas as your business coverage changes.', 'mobooking'); ?>
                </p>
            </div>

            <!-- Filter and Search -->
            <div class="mobooking-filters-row">
                <div class="mobooking-search-wrapper">
                    <input 
                        type="text" 
                        id="mobooking-areas-search" 
                        class="mobooking-search-input" 
                        placeholder="<?php esc_attr_e('Search areas by name or ZIP code...', 'mobooking'); ?>"
                    >
                    <svg class="mobooking-search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                
                <div class="mobooking-filter-group">
                    <select id="mobooking-country-filter" class="mobooking-form-select mobooking-form-select-sm">
                        <option value=""><?php esc_html_e('All Countries', 'mobooking'); ?></option>
                    </select>
                    <button type="button" id="mobooking-clear-filters" class="mobooking-btn mobooking-btn-text">
                        <?php esc_html_e('Clear Filters', 'mobooking'); ?>
                    </button>
                </div>
            </div>

            <!-- Areas List Container -->
            <div id="mobooking-areas-list-container" class="mobooking-areas-list">
                <div class="mobooking-loading-state">
                    <div class="mobooking-spinner"></div>
                    <p><?php esc_html_e('Loading your service areas...', 'mobooking'); ?></p>
                </div>
            </div>

            <!-- Pagination -->
            <div id="mobooking-areas-pagination-container" class="mobooking-pagination-wrapper"></div>
        </div>

    </div>
</div>

<!-- Area Item Template -->
<template id="mobooking-area-item-template">
    <div class="mobooking-area-item" data-area-id="{{area_id}}">
        <div class="mobooking-area-info">
            <div class="mobooking-area-primary">
                <h4 class="mobooking-area-name">{{area_name}}</h4>
                <span class="mobooking-area-zipcode">{{area_zipcode}}</span>
            </div>
            <div class="mobooking-area-secondary">
                <span class="mobooking-area-country">{{country_name}}</span>
                <span class="mobooking-area-date" title="<?php esc_attr_e('Date Added', 'mobooking'); ?>">
                    {{created_at_formatted}}
                </span>
            </div>
        </div>

        
        <div class="mobooking-area-actions">
            <button type="button" class="mobooking-btn mobooking-btn-sm mobooking-btn-outline mobooking-edit-area-btn" 
                    data-area-id="{{area_id}}" title="<?php esc_attr_e('Edit Area', 'mobooking'); ?>">
                <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <?php esc_html_e('Edit', 'mobooking'); ?>
            </button>
            <button type="button" class="mobooking-btn mobooking-btn-sm mobooking-btn-danger mobooking-delete-area-btn" 
                    data-area-id="{{area_id}}" title="<?php esc_attr_e('Delete Area', 'mobooking'); ?>">
                <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <?php esc_html_e('Delete', 'mobooking'); ?>
            </button>
        </div>
    </div>
</template>

<?php
/**
 * Debug Helper for Areas Issue
 * Add this to functions.php or create as a separate file to debug the issue
 */

// Debug AJAX handler and its action hook have been moved to Classes/Areas.php

// Add this JavaScript to your page-areas.php for debugging
?>
<script>
function debugAreasIssue() {
    console.log('Running areas debug...');
    
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
        type: 'POST',
        data: {
            action: 'mobooking_debug_areas'
        },
        success: function(response) {
            console.log('Debug response:', response);
            
            if (response.success) {
                console.log('=== AREAS DEBUG INFO ===');
                console.log('User logged in:', response.data.user_logged_in);
                console.log('User ID:', response.data.current_user_id);
                console.log('JSON file exists:', response.data.json_file_exists);
                console.log('JSON file path:', response.data.json_file_path);
                console.log('JSON content length:', response.data.json_content_length);
                console.log('JSON first 100 chars:', response.data.json_first_100_chars);
                console.log('JSON decode error:', response.data.json_decode_error);
                console.log('JSON data type:', response.data.json_data_type);
                console.log('JSON keys:', response.data.json_keys);
                console.log('JSON count:', response.data.json_count);
                console.log('First country:', response.data.first_country_code, response.data.first_country_data);
                console.log('Areas class exists:', response.data.areas_class_exists);
                console.log('========================');
            }
        },
        error: function(xhr, status, error) {
            console.log('Debug AJAX error:', error);
            console.log('Status:', status);
            console.log('Response:', xhr.responseText);
        }
    });
}

// Run debug automatically
jQuery(document).ready(function() {
    debugAreasIssue();
    
    // Also test the actual countries endpoint
    setTimeout(function() {
        console.log('Testing actual countries endpoint...');
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'mobooking_get_countries',
                nonce: '<?php echo wp_create_nonce('mobooking_dashboard_nonce'); ?>'
            },
            success: function(response) {
                console.log('Countries endpoint response:', response);
            },
            error: function(xhr, status, error) {
                console.log('Countries endpoint error:', error);
                console.log('Response text:', xhr.responseText);
            }
        });
    }, 1000);
});
</script>





<script>
// Add this to monitor when dropdowns get cleared
function monitorDropdowns() {
    console.log('Setting up dropdown monitors...');
    
    const countrySelector = jQuery("#mobooking-country-selector");
    const areaCountryField = jQuery("#mobooking-area-country");
    
    // Monitor changes to the dropdowns
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                const target = mutation.target;
                const optionCount = target.options ? target.options.length : 0;
                
                if (target.id === 'mobooking-country-selector') {
                    console.log('Country Selector changed! Now has', optionCount, 'options');
                    if (optionCount === 1) {
                        console.log('WARNING: Country selector was cleared to just default option!');
                        console.trace('Call stack:');
                    }
                }
                
                if (target.id === 'mobooking-area-country') {
                    console.log('Area Country Field changed! Now has', optionCount, 'options');
                    if (optionCount === 1) {
                        console.log('WARNING: Area country field was cleared to just default option!');
                        console.trace('Call stack:');
                    }
                }
            }
        });
    });
    
    // Start observing
    if (countrySelector.length > 0) {
        observer.observe(countrySelector[0], { childList: true });
    }
    if (areaCountryField.length > 0) {
        observer.observe(areaCountryField[0], { childList: true });
    }
    
    console.log('Dropdown monitors active');
}

// Run the monitor
monitorDropdowns();
</script>