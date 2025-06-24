<?php
/** Dashboard Page: Service Areas @package MoBooking */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Instantiate Areas class and fetch initial data
$areas_manager = new \MoBooking\Classes\Areas();
$user_id = get_current_user_id();

$default_args = [
    'limit' => 20, // Items per page, can be adjusted
    'paged' => 1,  // Start from the first page
];
// Assuming 'zip_code' is the default type for this page
$areas_result = $areas_manager->get_areas_by_user($user_id, 'zip_code', $default_args);

$areas_list = $areas_result['areas'];
$total_areas = $areas_result['total_count'];
$per_page = $areas_result['per_page'];
$current_page = $areas_result['current_page'];
$total_pages = ceil($total_areas / $per_page);

// Nonce for JS operations
wp_nonce_field('mobooking_dashboard_nonce', 'mobooking_dashboard_nonce_field');
?>

<style>
/* Modern shadcn/ui inspired styles */
.mobooking-areas-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

.mobooking-page-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.mobooking-page-title {
    font-size: 2rem;
    font-weight: 600;
    color: #0f172a;
    margin: 0 0 0.5rem 0;
    line-height: 1.2;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.mobooking-page-description {
    color: #64748b;
    font-size: 1rem;
    margin: 0;
    line-height: 1.5;
}

.mobooking-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .mobooking-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

.mobooking-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
    transition: box-shadow 0.2s ease;
}

.mobooking-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.mobooking-card-header {
    margin-bottom: 1.5rem;
}

.mobooking-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mobooking-card-description {
    color: #64748b;
    font-size: 0.875rem;
    margin: 0;
    line-height: 1.4;
}

.mobooking-form-group {
    margin-bottom: 1rem;
}

.mobooking-form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.mobooking-form-input,
.mobooking-form-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    background: #ffffff;
    box-sizing: border-box;
}

/* Enhanced input styles with better focus states */
.mobooking-form-input:focus,
.mobooking-form-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    background-color: #fefefe;
}

.mobooking-form-input:disabled,
.mobooking-form-select:disabled {
    background-color: #f9fafb;
    color: #9ca3af;
    cursor: not-allowed;
}

/* Country code input specific styles */
#mobooking-area-country-code {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-weight: 600;
    letter-spacing: 0.1em;
}

/* Datalist styling enhancement */
#mobooking-area-country-code::-webkit-calendar-picker-indicator {
    display: none;
}

/* Auto-complete styling */
.mobooking-form-input[list]::-webkit-list-button,
.mobooking-form-input[list]::-webkit-calendar-picker-indicator {
    display: none !important;
}

/* Helper text styling */
.mobooking-form-helper {
    color: #6b7280;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: block;
    line-height: 1.4;
}

.mobooking-zip-selector {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #f9fafb;
    padding: 1rem;
    min-height: 120px;
    max-height: 200px;
    overflow-y: auto;
}

.mobooking-zip-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    border-radius: 0.375rem;
    transition: background-color 0.15s ease;
}

.mobooking-zip-option:hover {
    background-color: #f3f4f6;
}

.mobooking-zip-option input[type="checkbox"] {
    margin: 0;
}

.mobooking-zip-option-label {
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.mobooking-zip-option-area {
    font-weight: 500;
}

.mobooking-zip-option-zip {
    font-size: 0.75rem;
    color: #6b7280;
}

.mobooking-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    gap: 0.5rem;
    white-space: nowrap;
}

.mobooking-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.mobooking-btn-primary {
    background-color: #3b82f6;
    color: #ffffff;
}

.mobooking-btn-primary:hover:not(:disabled) {
    background-color: #2563eb;
}

.mobooking-btn-secondary {
    background-color: #f8fafc;
    color: #64748b;
    border: 1px solid #e2e8f0;
}

.mobooking-btn-secondary:hover:not(:disabled) {
    background-color: #f1f5f9;
    color: #475569;
}

.mobooking-btn-danger {
    background-color: #ef4444;
    color: #ffffff;
}

.mobooking-btn-danger:hover:not(:disabled) {
    background-color: #dc2626;
}

.mobooking-btn-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
}

.mobooking-feedback {
    padding: 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    margin-top: 1rem;
    display: none;
}

.mobooking-feedback.success {
    background-color: #ecfdf5;
    color: #065f46;
    border: 1px solid #bbf7d0;
}

.mobooking-feedback.error {
    background-color: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

/* Accordion Styles */
.mobooking-accordion {
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    background: #ffffff;
    overflow: hidden;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.mobooking-accordion-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    background: #ffffff;
    border: none;
    cursor: pointer;
    font-size: 1.125rem;
    font-weight: 600;
    text-align: left;
    transition: background-color 0.2s ease;
    color: #1e293b;
}

.mobooking-accordion-trigger:hover {
    background-color: #f8fafc;
}

.mobooking-accordion-trigger[aria-expanded="true"] {
    border-bottom: 1px solid #e2e8f0;
}

.mobooking-accordion-icon {
    width: 1.25rem;
    height: 1.25rem;
    transition: transform 0.2s ease;
}

.mobooking-accordion-trigger[aria-expanded="true"] .mobooking-accordion-icon {
    transform: rotate(180deg);
}

.mobooking-accordion-content {
    padding: 0 1.5rem 1.5rem;
    display: none;
}

.mobooking-accordion-content.open {
    display: block;
}

.mobooking-areas-list-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.mobooking-areas-count {
    font-size: 0.875rem;
    color: #64748b;
    background: #f1f5f9;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-weight: 500;
}

.mobooking-area-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    margin-bottom: 0.5rem;
    transition: all 0.2s ease;
}

.mobooking-area-item:hover {
    border-color: #d1d5db;
    background: #f1f5f9;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.mobooking-area-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.mobooking-area-flag {
    width: 32px;
    height: 24px;
    border-radius: 0.25rem;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.625rem;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: 0.05em;
}

.mobooking-area-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.mobooking-area-name {
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.mobooking-area-zip {
    color: #6b7280;
    font-size: 0.75rem;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
}

.mobooking-area-actions {
    display: flex;
    gap: 0.5rem;
}

.mobooking-empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6b7280;
}

.mobooking-empty-state-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 1rem;
    opacity: 0.3;
}

.mobooking-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.mobooking-pagination .page-numbers {
    padding: 0.5rem 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    transition: all 0.15s ease;
}

.mobooking-pagination .page-numbers:hover {
    background-color: #f9fafb;
    border-color: #d1d5db;
}

.mobooking-pagination .page-numbers.current {
    background-color: #3b82f6;
    color: #ffffff;
    border-color: #3b82f6;
}

.mobooking-icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}

.mobooking-form-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
}

@media (max-width: 640px) {
    .mobooking-area-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .mobooking-area-actions {
        align-self: stretch;
        justify-content: flex-end;
    }
    
    .mobooking-form-actions {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<div class="mobooking-areas-container">
    <!-- Page Header -->
    <div class="mobooking-page-header">
        <h1 class="mobooking-page-title">
            <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <?php esc_html_e('Service Areas Management', 'mobooking'); ?>
        </h1>
        <p class="mobooking-page-description">
            <?php esc_html_e('Define and manage the geographic areas where you offer your cleaning services. You can select from available regions or add ZIP codes manually for precise coverage control.', 'mobooking'); ?>
        </p>
    </div>

    <!-- Main Grid Layout -->
    <div class="mobooking-grid">
        <!-- Area Selection Card -->
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

            <div class="mobooking-form-group">
                <label class="mobooking-form-label">
                    <?php esc_html_e('Available Areas & ZIP Codes', 'mobooking'); ?>
                </label>
                <div id="mobooking-area-zip-selector-container" class="mobooking-zip-selector">
                    <div class="mobooking-empty-state">
                        <svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <small style="color: #9ca3af;"><?php esc_html_e('Select a country and city to browse available service areas with their ZIP codes', 'mobooking'); ?></small>
                    </div>
                </div>
            </div>

            <button type="button" id="mobooking-add-selected-areas-btn" class="mobooking-btn mobooking-btn-primary" disabled>
                <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <?php esc_html_e('Add Selected Areas', 'mobooking'); ?>
            </button>

            <div id="mobooking-selection-feedback" class="mobooking-feedback"></div>
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
                    <?php esc_html_e('Add individual service areas by manually entering the country code and ZIP/postal code. Perfect for specific locations not covered in the quick selection.', 'mobooking'); ?>
                </p>
            </div>

            <form id="mobooking-area-form">
                <input type="hidden" id="mobooking-area-id" name="area_id" value="">
                
                <div class="mobooking-form-group">
                    <label for="mobooking-area-country-code" class="mobooking-form-label">
                        <?php esc_html_e('Country Code', 'mobooking'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="mobooking-area-country-code" 
                        name="country_code" 
                        required 
                        class="mobooking-form-input" 
                        placeholder="<?php esc_attr_e('e.g., US, CA, GB, DE', 'mobooking'); ?>"
                        maxlength="3"
                        style="text-transform: uppercase;"
                        list="country-codes-list"
                    >
                    <datalist id="country-codes-list">
                        <!-- Will be populated by JavaScript -->
                    </datalist>
                    <small class="mobooking-form-helper">
                        <?php esc_html_e('Use 2-3 letter country codes (ISO format)', 'mobooking'); ?>
                    </small>
                </div>

                <div class="mobooking-form-group">
                    <label for="mobooking-area-value" class="mobooking-form-label">
                        <?php esc_html_e('ZIP / Postal Code', 'mobooking'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="mobooking-area-value" 
                        name="area_value" 
                        required 
                        class="mobooking-form-input" 
                        placeholder="<?php esc_attr_e('e.g., 10001, M5V 3A3, SW1A 1AA', 'mobooking'); ?>"
                    >
                </div>

                <div class="mobooking-form-actions">
                    <button type="submit" id="mobooking-save-area-btn" class="mobooking-btn mobooking-btn-primary">
                        <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <?php esc_html_e('Add Area', 'mobooking'); ?>
                    </button>
                    <button type="button" id="mobooking-cancel-edit-area-btn" class="mobooking-btn mobooking-btn-secondary" style="display:none;">
                        <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        <?php esc_html_e('Cancel Edit', 'mobooking'); ?>
                    </button>
                </div>

                <div id="mobooking-area-form-feedback" class="mobooking-feedback"></div>
            </form>
        </div>
    </div>

    <!-- Service Areas List - Accordion -->
    <div class="mobooking-accordion">
        <button class="mobooking-accordion-trigger" type="button" aria-expanded="true" data-target="mobooking-areas-content">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span><?php esc_html_e('Your Service Areas', 'mobooking'); ?></span>
                <?php if ($total_areas > 0) : ?>
                    <span class="mobooking-areas-count">
                        <?php echo sprintf(esc_html__('%d configured', 'mobooking'), $total_areas); ?>
                    </span>
                <?php endif; ?>
            </div>
            <svg class="mobooking-accordion-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        
        <div class="mobooking-accordion-content open" id="mobooking-areas-content">
            <div id="mobooking-areas-list-container">
                <?php if (!empty($areas_list)) : ?>
                    <?php foreach ($areas_list as $area) : ?>
                        <div class="mobooking-area-item" data-area-id="<?php echo esc_attr($area['area_id']); ?>" data-country-code="<?php echo esc_attr($area['country_code']); ?>" data-area-value="<?php echo esc_attr($area['area_value']); ?>">
                            <div class="mobooking-area-info">
                                <div class="mobooking-area-flag">
                                    <?php echo esc_html(strtoupper($area['country_code'])); ?>
                                </div>
                                <div class="mobooking-area-details">
                                    <span class="mobooking-area-name">
                                        <?php echo esc_html($area['country_code']); ?> - 
                                        <?php 
                                        // Display area name if available, otherwise just show the area type
                                        echo esc_html(isset($area['area_name']) && !empty($area['area_name']) ? $area['area_name'] : 'Service Area'); 
                                        ?>
                                    </span>
                                    <span class="mobooking-area-zip">ZIP: <?php echo esc_html($area['area_value']); ?></span>
                                </div>
                            </div>
                            <div class="mobooking-area-actions">
                                <button class="mobooking-btn mobooking-btn-secondary mobooking-btn-sm mobooking-edit-area-btn" data-id="<?php echo esc_attr($area['area_id']); ?>">
                                    <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    <?php esc_html_e('Edit', 'mobooking'); ?>
                                </button>
                                <button class="mobooking-btn mobooking-btn-danger mobooking-btn-sm mobooking-delete-area-btn" data-id="<?php echo esc_attr($area['area_id']); ?>">
                                    <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                    <?php esc_html_e('Delete', 'mobooking'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="mobooking-empty-state">
                        <svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <h4 style="margin: 0 0 0.5rem 0; color: #374151;"><?php esc_html_e('No service areas configured', 'mobooking'); ?></h4>
                        <p style="margin: 0; font-size: 0.875rem; color: #9ca3af;">
                            <?php esc_html_e('Start by adding your first service area using one of the methods above.', 'mobooking'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1) : ?>
                <div id="mobooking-areas-pagination-container" class="mobooking-pagination">
                    <?php
                    echo paginate_links(array(
                        'base' => '#%#%',
                        'format' => '?paged=%#%',
                        'current' => $current_page,
                        'total' => $total_pages,
                        'prev_text' => __('‹ Previous', 'mobooking'),
                        'next_text' => __('Next ›', 'mobooking'),
                        'add_fragment' => '',
                        'type' => 'list'
                    ));
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Template for dynamically added areas -->
<script type="text/template" id="mobooking-area-item-template">
    <div class="mobooking-area-item" data-area-id="<%= area_id %>" data-country-code="<%= country_code %>" data-area-value="<%= area_value %>">
        <div class="mobooking-area-info">
            <div class="mobooking-area-flag">
                <%= country_code.toUpperCase() %>
            </div>
            <div class="mobooking-area-details">
                <span class="mobooking-area-name">
                    <%= country_code %> - <%= area_name || 'Service Area' %>
                </span>
                <span class="mobooking-area-zip">ZIP: <%= area_value %></span>
            </div>
        </div>
        <div class="mobooking-area-actions">
            <button class="mobooking-btn mobooking-btn-secondary mobooking-btn-sm mobooking-edit-area-btn" data-id="<%= area_id %>">
                <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                <?php esc_html_e('Edit', 'mobooking'); ?>
            </button>
            <button class="mobooking-btn mobooking-btn-danger mobooking-btn-sm mobooking-delete-area-btn" data-id="<%= area_id %>">
                <svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <?php esc_html_e('Delete', 'mobooking'); ?>
            </button>
        </div>
    </div>
</script>

<script>
jQuery(document).ready(function($) {
    'use strict';

    // Accordion functionality
    $('.mobooking-accordion-trigger').on('click', function() {
        const $trigger = $(this);
        const $content = $('#' + $trigger.data('target'));
        const isExpanded = $trigger.attr('aria-expanded') === 'true';
        
        $trigger.attr('aria-expanded', !isExpanded);
        $content.toggleClass('open');
        
        if (!isExpanded && $content.hasClass('open')) {
            $content.slideDown(300);
        } else {
            $content.slideUp(300);
        }
    });

    // Initialize accordion state
    const $accordionContent = $('.mobooking-accordion-content');
    if ($accordionContent.hasClass('open')) {
        $accordionContent.show();
    }

    // Form variables
    const $areasListContainer = $("#mobooking-areas-list-container");
    const $areaForm = $("#mobooking-area-form");
    const $areaFormTitle = $("#mobooking-area-form-title");
    const $areaIdField = $("#mobooking-area-id");
    const $areaCountryCodeField = $("#mobooking-area-country-code");
    const $areaValueField = $("#mobooking-area-value");
    const $saveAreaBtn = $("#mobooking-save-area-btn");
    const $cancelEditBtn = $("#mobooking-cancel-edit-area-btn");
    const $feedbackDiv = $("#mobooking-area-form-feedback").hide();
    const $paginationContainer = $("#mobooking-areas-pagination-container");
    const itemTemplate = $("#mobooking-area-item-template").html();

    // Area selection variables
    const $countrySelector = $("#mobooking-country-selector");
    const $citySelector = $("#mobooking-city-selector");
    const $areaZipSelectorContainer = $("#mobooking-area-zip-selector-container");
    const $addSelectedAreasBtn = $("#mobooking-add-selected-areas-btn");
    const $selectionFeedbackDiv = $("#mobooking-selection-feedback").hide();

    let currentFilters = { paged: 1, limit: 20 };

    // Ensure mobooking_areas_params exists
    window.mobooking_areas_params = window.mobooking_areas_params || {};
    window.mobooking_areas_params.i18n = window.mobooking_areas_params.i18n || {};
    const i18n = window.mobooking_areas_params.i18n;

    // Basic XSS protection for display
    function sanitizeHTML(str) {
        if (typeof str !== "string") return "";
        var temp = document.createElement("div");
        temp.textContent = str;
        return temp.innerHTML;
    }

    function renderTemplate(templateHtml, data) {
        if (!templateHtml) return "";
        let currentItemHtml = templateHtml;
        for (const key in data) {
            const value = data[key] === null || typeof data[key] === "undefined" ? "" : data[key];
            currentItemHtml = currentItemHtml.replace(
                new RegExp("<%=\\s*" + key + "\\s*%>", "g"),
                sanitizeHTML(String(value))
            );
        }
        return currentItemHtml;
    }

    function fetchAndRenderAreas(page = 1) {
        currentFilters.paged = page;
        $areasListContainer.html(
            "<p>" + (i18n.loading || "Loading...") + "</p>"
        );
        $paginationContainer.empty();

        $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: "POST",
            data: {
                action: "mobooking_get_areas",
                nonce: mobooking_areas_params.nonce,
                ...currentFilters,
            },
            success: function (response) {
                $areasListContainer.empty();
                if (response.success && response.data.areas && response.data.areas.length) {
                    response.data.areas.forEach(function (area) {
                        $areasListContainer.append(renderTemplate(itemTemplate, area));
                    });
                    renderPagination(
                        response.data.total_count,
                        response.data.per_page,
                        response.data.current_page
                    );
                } else if (response.success) {
                    $areasListContainer.html(
                        '<div class="mobooking-empty-state">' +
                        '<svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>' +
                        '</svg>' +
                        '<h4 style="margin: 0 0 0.5rem 0; color: #374151;">' + (i18n.no_areas || "No service areas found") + '</h4>' +
                        '<p style="margin: 0; font-size: 0.875rem; color: #9ca3af;">' + (i18n.add_first_area || "Start by adding your first service area.") + '</p>' +
                        '</div>'
                    );
                } else {
                    $areasListContainer.html(
                        "<p>" + sanitizeHTML(response.data.message || i18n.error_loading) + "</p>"
                    );
                }
            },
            error: function () {
                $areasListContainer.html(
                    "<p>" + (i18n.error_loading || "Error loading areas.") + "</p>"
                );
            },
        });
    }

    function renderPagination(totalItems, itemsPerPage, currentPage) {
        $paginationContainer.empty();
        const totalPages = Math.ceil(totalItems / itemsPerPage);
        if (totalPages <= 1) return;
        
        let paginationHtml = '<ul class="pagination-links">';
        for (let i = 1; i <= totalPages; i++) {
            paginationHtml += `<li style="display:inline; margin-right:5px;"><a href="#" data-page="${i}" class="page-numbers ${
                i == currentPage ? "current" : ""
            }">${i}</a></li>`;
        }
        paginationHtml += "</ul>";
        $paginationContainer.html(paginationHtml);
    }

    function resetForm() {
        $areaForm[0].reset();
        $areaIdField.val("");
        $areaFormTitle.find('svg').after(' <?php esc_html_e("Manual Area Entry", "mobooking"); ?>');
        $saveAreaBtn.html('<svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg><?php esc_html_e("Add Area", "mobooking"); ?>');
        $cancelEditBtn.hide();
        $feedbackDiv.empty().removeClass("success error").hide();
    }

    // Form submission
    $areaForm.on("submit", function (e) {
        e.preventDefault();
        $feedbackDiv.empty().removeClass("success error").hide();

        const areaId = $areaIdField.val();
        const countryCode = $areaCountryCodeField.val().trim().toUpperCase();
        const areaValue = $areaValueField.val().trim();

        if (!countryCode || !areaValue) {
            $feedbackDiv
                .text(i18n.fields_required || "Country code and ZIP/Area value are required.")
                .addClass("error")
                .show();
            return;
        }

        const originalButtonText = $saveAreaBtn.html();
        $saveAreaBtn
            .prop("disabled", true)
            .html('<svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>' + (i18n.saving || "Saving..."));

        let ajaxData = {
            nonce: mobooking_areas_params.nonce,
            country_code: countryCode,
            area_value: areaValue,
        };

        if (areaId) {
            ajaxData.action = "mobooking_update_area";
            ajaxData.area_id = areaId;
        } else {
            ajaxData.action = "mobooking_add_area";
        }

        $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: "POST",
            data: ajaxData,
            success: function (response) {
                if (response.success) {
                    $feedbackDiv
                        .text(response.data.message)
                        .removeClass("error")
                        .addClass("success")
                        .show();
                    resetForm();
                    fetchAndRenderAreas(areaId ? currentFilters.paged : 1);
                } else {
                    $feedbackDiv
                        .text(response.data.message || i18n.error_general)
                        .removeClass("success")
                        .addClass("error")
                        .show();
                }
            },
            error: function () {
                $feedbackDiv
                    .text(i18n.error_general || "An AJAX error occurred.")
                    .removeClass("success")
                    .addClass("error")
                    .show();
            },
            complete: function () {
                $saveAreaBtn.prop("disabled", false).html(originalButtonText);
                setTimeout(function () {
                    $feedbackDiv.fadeOut().empty();
                }, 4000);
            },
        });
    });

    // Edit button click handler - FIXED
    $areasListContainer.on("click", ".mobooking-edit-area-btn", function () {
        const $itemRow = $(this).closest(".mobooking-area-item");
        const areaIdToEdit = $itemRow.data("area-id");
        const countryCode = $itemRow.data("country-code");
        const areaValue = $itemRow.data("area-value");

        // Populate form fields
        $areaIdField.val(areaIdToEdit);
        $areaCountryCodeField.val(countryCode);
        $areaValueField.val(areaValue);

        // Update form UI
        $areaFormTitle.html(
            '<svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>' +
            '</svg>' +
            '<?php esc_html_e("Edit Service Area", "mobooking"); ?>'
        );
        $saveAreaBtn.html(
            '<svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>' +
            '</svg>' +
            '<?php esc_html_e("Save Changes", "mobooking"); ?>'
        );
        $cancelEditBtn.show();
        $feedbackDiv.empty().hide();
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $("#mobooking-area-form-wrapper").offset().top - 50
        }, 500);
    });

    // Cancel edit button
    $cancelEditBtn.on("click", function () {
        resetForm();
    });

    // Delete button click handler
    $areasListContainer.on("click", ".mobooking-delete-area-btn", function () {
        const areaIdToDelete = $(this).data("id");
        if (confirm(i18n.confirm_delete || "Are you sure you want to delete this area?")) {
            $.ajax({
                url: mobooking_areas_params.ajax_url,
                type: "POST",
                data: {
                    action: "mobooking_delete_area",
                    nonce: mobooking_areas_params.nonce,
                    area_id: areaIdToDelete,
                },
                success: function (response) {
                    if (response.success) {
                        fetchAndRenderAreas(currentFilters.paged);
                        // Update accordion count if needed
                        if (window.location.hash !== '#refresh') {
                            window.location.hash = 'refresh';
                            window.location.reload();
                        }
                    } else {
                        alert(sanitizeHTML(response.data.message || i18n.error_deleting));
                    }
                },
                error: function () {
                    alert(i18n.error_deleting || "AJAX error deleting area.");
                },
            });
        }
    });

    // Pagination click handler
    $paginationContainer.on("click", "a.page-numbers", function (e) {
        e.preventDefault();
        const page = $(this).data("page") || $(this).attr("href").split("paged=")[1]?.split("&")[0];
        if (page) {
            fetchAndRenderAreas(parseInt(page));
        }
    });

    // Country selector change handler
    $countrySelector.on("change", function() {
        const countryCode = $(this).val();
        $citySelector.empty().append($("<option>", { 
            value: "", 
            text: countryCode ? "Loading cities..." : "First select a country..." 
        })).prop("disabled", !countryCode);
        
        $areaZipSelectorContainer.html(
            '<div class="mobooking-empty-state">' +
            '<svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>' +
            '</svg>' +
            '<small style="color: #9ca3af;">Select a city to see available areas</small>' +
            '</div>'
        );
        $addSelectedAreasBtn.prop("disabled", true);
        $selectionFeedbackDiv.hide().empty();

        if (countryCode) {
            // Load cities for selected country - FIXED AJAX ACTION NAME
            $.ajax({
                url: mobooking_areas_params.ajax_url,
                type: "POST",
                data: {
                    action: "mobooking_get_cities_for_country", // Fixed action name
                    nonce: mobooking_areas_params.nonce,
                    country_code: countryCode,
                },
                success: function (response) {
                    $citySelector.empty().append($("<option>", { value: "", text: "Select a city..." }));
                    if (response.success && response.data.cities) {
                        response.data.cities.forEach(function (city) {
                            $citySelector.append($("<option>", { value: city.name, text: city.name }));
                        });
                        $citySelector.prop("disabled", false);
                    } else {
                        $citySelector.append($("<option>", { value: "", text: "No cities available" }));
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error loading cities:', xhr.responseText);
                    $citySelector.empty().append($("<option>", { value: "", text: "Error loading cities" }));
                }
            });
        }
    });

    // City selector change handler
    $citySelector.on("change", function() {
        const cityCode = $(this).val();
        const countryCode = $countrySelector.val();
        
        if (cityCode && countryCode) {
            $areaZipSelectorContainer.html('<div style="padding: 2rem; text-align: center; color: #6b7280;">Loading areas...</div>');
            
            $.ajax({
                url: mobooking_areas_params.ajax_url,
                type: "POST",
                data: {
                    action: "mobooking_get_areas_for_city", // Fixed action name
                    nonce: mobooking_areas_params.nonce,
                    country_code: countryCode,
                    city_name: cityCode, // Fixed parameter name
                },
                success: function (response) {
                    if (response.success && response.data.areas && response.data.areas.length) {
                        let areasHtml = '';
                        response.data.areas.forEach(function (area) {
                            const areaName = area.name || area.area_name || 'Area';
                            const zipCode = area.zip || area.zip_code || area.code;
                            areasHtml += 
                                '<div class="mobooking-zip-option">' +
                                '<input type="checkbox" id="zip-' + zipCode + '" value="' + zipCode + '" data-area-name="' + areaName + '">' +
                                '<label for="zip-' + zipCode + '" class="mobooking-zip-option-label">' +
                                '<span class="mobooking-zip-option-area">' + areaName + '</span>' +
                                '<span class="mobooking-zip-option-zip">ZIP: ' + zipCode + '</span>' +
                                '</label>' +
                                '</div>';
                        });
                        $areaZipSelectorContainer.html(areasHtml);
                        $addSelectedAreasBtn.prop("disabled", false);
                    } else {
                        $areaZipSelectorContainer.html(
                            '<div class="mobooking-empty-state">' +
                            '<small style="color: #9ca3af;">No areas available for this city</small>' +
                            '</div>'
                        );
                        $addSelectedAreasBtn.prop("disabled", true);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error loading areas:', xhr.responseText);
                    $areaZipSelectorContainer.html(
                        '<div class="mobooking-empty-state">' +
                        '<small style="color: #ef4444;">Error loading areas</small>' +
                        '</div>'
                    );
                    $addSelectedAreasBtn.prop("disabled", true);
                }
            });
        } else {
            $areaZipSelectorContainer.html(
                '<div class="mobooking-empty-state">' +
                '<svg class="mobooking-empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>' +
                '</svg>' +
                '<small style="color: #9ca3af;">Select a city to see available areas</small>' +
                '</div>'
            );
            $addSelectedAreasBtn.prop("disabled", true);
        }
    });

    // Add selected areas button
    $addSelectedAreasBtn.on("click", function () {
        const $selectedCheckboxes = $areaZipSelectorContainer.find("input[type='checkbox']:checked");
        const countryCode = $countrySelector.val();
        $selectionFeedbackDiv.hide().empty().removeClass("success error");

        if (!countryCode) {
            $selectionFeedbackDiv.text(i18n.select_country_first || "Please select a country first.").addClass("error").show();
            return;
        }

        if ($selectedCheckboxes.length === 0) {
            $selectionFeedbackDiv.text(i18n.no_area_selected || "Please select at least one area to add.").addClass("error").show();
            return;
        }

        let promises = [];
        let results = { success: [], error: [] };
        const originalButtonText = $(this).html();
        $(this).prop("disabled", true).html(
            '<svg class="mobooking-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>' +
            '</svg>' + (i18n.adding_areas || "Adding...")
        );

        $selectedCheckboxes.each(function () {
            const zip = $(this).val();
            const areaName = $(this).data("area-name");

            promises.push(
                $.ajax({
                    url: mobooking_areas_params.ajax_url,
                    type: "POST",
                    data: {
                        action: "mobooking_add_area",
                        nonce: mobooking_areas_params.nonce,
                        country_code: countryCode,
                        area_value: zip,
                        area_name: areaName,
                        area_type: "zip_code"
                    },
                }).then(
                    response => {
                        if (response.success) {
                            results.success.push(`${areaName || zip}: ${response.data.message || 'Added successfully'}`);
                        } else {
                            results.error.push(`${areaName || zip}: ${response.data.message || 'Error adding area'}`);
                        }
                    },
                    () => {
                        results.error.push(`${areaName || zip}: AJAX error adding area`);
                    }
                )
            );
        });

        Promise.all(promises.map(p => p.catch(e => e))).then(() => {
            let feedbackMessage = '';
            if (results.success.length > 0) {
                feedbackMessage += `Successfully added ${results.success.length} area(s).\n`;
            }
            if (results.error.length > 0) {
                feedbackMessage += `Failed to add ${results.error.length} area(s).\n`;
            }

            $selectionFeedbackDiv
                .text(feedbackMessage)
                .addClass(results.error.length > 0 ? "error" : "success")
                .show();

            // Refresh the areas list
            fetchAndRenderAreas(1);
            
            // Clear selections
            $selectedCheckboxes.prop('checked', false);
            
            $addSelectedAreasBtn.prop("disabled", false).html(originalButtonText);
            
            setTimeout(() => {
                $selectionFeedbackDiv.fadeOut();
            }, 5000);
        });
    });

    // Initialize countries dropdown and populate datalist for manual entry
    if (mobooking_areas_params && mobooking_areas_params.ajax_url) {
        $.ajax({
            url: mobooking_areas_params.ajax_url,
            type: "POST",
            data: {
                action: "mobooking_get_countries",
                nonce: mobooking_areas_params.nonce,
            },
            success: function (response) {
                if (response.success && response.data.countries) {
                    // Populate country selector dropdown
                    $countrySelector.empty().append($("<option>", { value: "", text: "Choose a country..." }));
                    
                    // Populate datalist for manual entry
                    const $datalist = $('#country-codes-list');
                    $datalist.empty();
                    
                    response.data.countries.forEach(function (country) {
                        // Add to dropdown
                        $countrySelector.append($("<option>", { 
                            value: country.code, 
                            text: country.name + " (" + country.code + ")" 
                        }));
                        
                        // Add to datalist for manual entry autocomplete
                        $datalist.append($("<option>", { 
                            value: country.code,
                            label: country.name + " (" + country.code + ")"
                        }));
                    });
                } else {
                    console.error("Error loading countries:", response.data ? response.data.message : "Unknown error");
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error("AJAX error loading countries:", textStatus, errorThrown);
            }
        });
    }

    // Auto-fill country code when user selects from quick selection
    $countrySelector.on('change', function() {
        const selectedCountryCode = $(this).val();
        if (selectedCountryCode && !$areaCountryCodeField.val()) {
            $areaCountryCodeField.val(selectedCountryCode);
            
            // Add visual feedback
            $areaCountryCodeField.css({
                'background-color': '#ecfdf5',
                'border-color': '#10b981'
            });
            
            // Show helper text
            const $helper = $areaCountryCodeField.next('.mobooking-form-helper');
            $helper.text('<?php esc_html_e("Auto-filled from country selection above", "mobooking"); ?>').css('color', '#059669');
            
            // Reset styling after 2 seconds
            setTimeout(function() {
                $areaCountryCodeField.css({
                    'background-color': '',
                    'border-color': ''
                });
                $helper.text('<?php esc_html_e("Use 2-3 letter country codes (ISO format)", "mobooking"); ?>').css('color', '');
            }, 2000);
        }
    });

    // Clear auto-fill styling when user types
    $areaCountryCodeField.on('input', function() {
        $(this).css({
            'background-color': '',
            'border-color': ''
        });
        $(this).next('.mobooking-form-helper').text('<?php esc_html_e("Use 2-3 letter country codes (ISO format)", "mobooking"); ?>').css('color', '');
    });
});
</script>