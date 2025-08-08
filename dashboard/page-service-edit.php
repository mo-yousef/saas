<?php
/**
 * Dashboard Page: Add/Edit Service - Refactored with Modern UI
 * @package MoBooking
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Check user permissions
if ( ! current_user_can( 'mobooking_business_owner' ) ) {
    wp_die( __( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}

// Determine Page Mode (Add vs. Edit) and Set Title
$edit_mode = false;
$service_id = 0;
if ( isset( $_GET['service_id'] ) && ! empty( $_GET['service_id'] ) ) {
    $edit_mode = true;
    $service_id = intval( $_GET['service_id'] );
    $page_title = __( 'Edit Service', 'mobooking' );
} else {
    $page_title = __( 'Add New Service', 'mobooking' );
}

// Initialize Variables
$service_name = '';
$service_description = '';
$service_price = '';
$service_duration = '';
$service_icon = '';
$service_image_url = '';
$service_status = 'active';
$service_options_data = [];
$error_message = '';

// Get current user and business settings
$breadcrumb_services = admin_url('admin.php?page=mobooking-services');
$user_id = get_current_user_id();
$settings_manager = new \MoBooking\Classes\Settings();
$biz_settings = $settings_manager->get_business_settings($user_id);
$currency_symbol = $biz_settings['biz_currency_symbol'];
$currency_pos = $biz_settings['biz_currency_position'];

// Define option types and price types to be globally available for the template
$option_types = [
    'checkbox' => __('Checkbox', 'mobooking'),
    'text' => __('Text Input', 'mobooking'),
    'number' => __('Number', 'mobooking'),
    'select' => __('Dropdown', 'mobooking'),
    'radio' => __('Radio Buttons', 'mobooking'),
    'textarea' => __('Text Area', 'mobooking'),
    'quantity' => __('Quantity', 'mobooking'),
    'sqm' => __('Square Meter (SQM)', 'mobooking') // New SQM option type
];

$price_types = [
    '' => __('None', 'mobooking'), // Empty value for 'None'
    'fixed' => __('Fixed Amount', 'mobooking'),
    'percentage' => __('Percentage', 'mobooking'),
    'multiply' => __('Multiply', 'mobooking')
];

// Fetch Service Data in Edit Mode
if ( $edit_mode && $service_id > 0 ) {
    if ( class_exists('\MoBooking\Classes\Services') ) {
        $services_manager = new \MoBooking\Classes\Services();
        $service_data = $services_manager->get_service( $service_id, $user_id );

        if ( $service_data && ! is_wp_error( $service_data ) ) {
            $service_name = $service_data['name'];
            $service_description = $service_data['description'];
            $service_price = $service_data['price'];
            $service_duration = $service_data['duration'];
            $service_icon = $service_data['icon'];
            $service_image_url = $service_data['image_url'];
            $service_status = $service_data['status'];
            $service_options_data = isset($service_data['options']) && is_array($service_data['options']) ? $service_data['options'] : [];
        } else {
            $error_message = __( 'Service not found or you do not have permission to edit it.', 'mobooking' );
        }
    } else {
        $error_message = __( 'Error: Services manager class not found.', 'mobooking' );
    }
}

?>

<style>
/* Modern Service Edit Page Styles */
:root {
    --mb-primary: hsl(221.2 83.2% 53.3%);
    --mb-primary-hover: hsl(221.2 83.2% 48%);
    --mb-secondary: hsl(210 40% 96%);
    --mb-secondary-hover: hsl(210 40% 91%);
    --mb-destructive: hsl(0 84.2% 60.2%);
    --mb-destructive-hover: hsl(0 84.2% 55%);
    --mb-border: hsl(214.3 31.8% 91.4%);
    --mb-input: hsl(214.3 31.8% 91.4%);
    --mb-background: hsl(0 0% 100%);
    --mb-foreground: hsl(222.2 84% 4.9%);
    --mb-muted: hsl(210 40% 96%);
    --mb-muted-foreground: hsl(215.4 16.3% 46.9%);
    --mb-success: hsl(142.1 76.2% 36.3%);
    --mb-warning: hsl(45.4 93.4% 47.5%);
    --mb-radius: 0.5rem;
}

/* Utility Classes */
.mb-w-full { width: 100%; }
.mb-flex { display: flex; }
.mb-grid { display: grid; }
.mb-hidden { display: none !important; }
.mb-block { display: block; }
.mb-items-center { align-items: center; }
.mb-justify-between { justify-content: space-between; }
.mb-justify-center { justify-content: center; }
.mb-gap-2 { gap: 0.5rem; }
.mb-gap-3 { gap: 0.75rem; }
.mb-gap-4 { gap: 1rem; }
.mb-gap-6 { gap: 1.5rem; }
.mb-space-y-4 > * + * { margin-top: 1rem; }
.mb-space-y-6 > * + * { margin-top: 1.5rem; }
.mb-p-4 { padding: 1rem; }
.mb-p-6 { padding: 1.5rem; }
.mb-px-4 { padding-left: 1rem; padding-right: 1rem; }
.mb-py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
.mb-py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
.mb-mb-4 { margin-bottom: 1rem; }
.mb-mb-6 { margin-bottom: 1.5rem; }
.mb-mt-4 { margin-top: 1rem; }
.mb-text-sm { font-size: 0.875rem; }
.mb-text-base { font-size: 1rem; }
.mb-text-lg { font-size: 1.125rem; }
.mb-font-medium { font-weight: 500; }
.mb-font-semibold { font-weight: 600; }
.mb-font-bold { font-weight: 700; }
.mb-text-destructive { color: var(--mb-destructive); }
.mb-text-muted-foreground { color: var(--mb-muted-foreground); }
.mb-rounded { border-radius: var(--mb-radius); }
.mb-rounded-lg { border-radius: calc(var(--mb-radius) + 2px); }
.mb-shadow-sm { box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
.mb-shadow-md { box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); }

/* Main Layout */
.mb-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.mb-header {
    background: var(--mb-background);
    border-bottom: 1px solid var(--mb-border);
    padding: 1.5rem 0;
    margin-bottom: 2rem;
    border-radius: var(--mb-radius);
}

.mb-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: var(--mb-foreground);
    margin: 0;
}

.mb-breadcrumb {
    color: var(--mb-muted-foreground);
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.mb-breadcrumb a {
    color: var(--mb-primary);
    text-decoration: none;
}

.mb-breadcrumb a:hover {
    text-decoration: underline;
}

/* Form Components */
.mb-form-card {
    background: var(--mb-background);
    border: 1px solid var(--mb-border);
    border-radius: var(--mb-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--mb-shadow-sm);
}

.mb-form-section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--mb-foreground);
}

.mb-form-group {
    margin-bottom: 1rem;
}

.mb-form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--mb-foreground);
}

.mb-form-input, .mb-form-textarea, .mb-form-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--mb-input);
    border-radius: var(--mb-radius);
    font-size: 0.875rem;
    transition: all 0.15s ease;
    background: var(--mb-background);
    color: var(--mb-foreground);
}

.mb-form-input:focus, .mb-form-textarea:focus, .mb-form-select:focus {
    outline: 2px solid var(--mb-primary);
    outline-offset: 2px;
    border-color: var(--mb-primary);
}

.mb-form-textarea {
    min-height: 5rem;
    resize: vertical;
}

.mb-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 768px) {
    .mb-form-grid {
        grid-template-columns: 1fr;
    }
}

/* Button Components */
.mb-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--mb-radius);
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.15s ease;
    padding: 0.5rem 1rem;
    height: 2.5rem;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
}

.mb-btn:disabled {
    pointer-events: none;
    opacity: 0.5;
}

.mb-btn-primary {
    background-color: var(--mb-primary);
    color: white;
}

.mb-btn-primary:hover:not(:disabled) {
    background-color: var(--mb-primary-hover);
}

.mb-btn-secondary {
    background-color: var(--mb-secondary);
    color: var(--mb-foreground);
    border: 1px solid var(--mb-border);
}

.mb-btn-secondary:hover:not(:disabled) {
    background-color: var(--mb-secondary-hover);
}

.mb-btn-destructive {
    background-color: var(--mb-destructive);
    color: white;
}

.mb-btn-destructive:hover:not(:disabled) {
    background-color: var(--mb-destructive-hover);
}

.mb-btn-sm {
    height: 2rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
}

/* Alert Components */
.mb-alert {
    padding: 1rem;
    border-radius: var(--mb-radius);
    margin-bottom: 1rem;
    border-left: 4px solid;
}

.mb-alert-success {
    background-color: hsl(142.1 76.2% 95%);
    border-left-color: var(--mb-success);
    color: hsl(142.1 76.2% 20%);
}

.mb-alert-error {
    background-color: hsl(0 84.2% 95%);
    border-left-color: var(--mb-destructive);
    color: hsl(0 84.2% 20%);
}

.mb-alert-warning {
    background-color: hsl(45.4 93.4% 95%);
    border-left-color: var(--mb-warning);
    color: hsl(45.4 93.4% 20%);
}

/* Radio Button Groups */
.mb-radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.mb-radio-option {
    position: relative;
}

.mb-radio-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.mb-radio-option-label {
    display: inline-block;
    padding: 0.5rem 1rem;
    border: 1px solid var(--mb-border);
    border-radius: var(--mb-radius);
    cursor: pointer;
    background-color: var(--mb-background);
    font-size: 0.875rem;
    transition: all 0.15s ease;
    user-select: none;
}

.mb-radio-option-label:hover {
    background-color: var(--mb-muted);
    border-color: var(--mb-primary);
}

.mb-radio-option input[type="radio"]:checked + .mb-radio-option-label {
    background-color: var(--mb-primary);
    color: white;
    border-color: var(--mb-primary);
}

/* Service Options */
.mb-options-container {
    margin-top: 1.5rem;
}

.mb-option-item {
    background: var(--mb-background);
    border: 1px solid var(--mb-border);
    border-radius: var(--mb-radius);
    padding: 1rem;
    margin-bottom: 1rem;
    position: relative;
}

.mb-option-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.mb-option-title {
    font-weight: 600;
    color: var(--mb-foreground);
}

.mb-option-drag-handle {
    cursor: move;
    color: var(--mb-muted-foreground);
    margin-right: 0.5rem;
}

.mb-option-remove-btn {
    background: var(--mb-destructive);
    color: white;
    border: none;
    border-radius: var(--mb-radius);
    width: 2rem;
    height: 2rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.mb-option-remove-btn:hover {
    background: var(--mb-destructive-hover);
}

/* Choice Items */
.mb-choices-container {
    margin-top: 0.75rem;
    padding: 0.75rem;
    background: var(--mb-muted);
    border-radius: var(--mb-radius);
    border: 1px solid var(--mb-border);
}

.mb-choice-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--mb-border);
}

.mb-choice-item:last-child {
    border-bottom: none;
}

.mb-choice-drag-handle {
    cursor: move;
    color: var(--mb-muted-foreground);
}

.mb-choice-input {
    flex: 1;
    padding: 0.375rem 0.5rem;
    border: 1px solid var(--mb-border);
    border-radius: calc(var(--mb-radius) - 2px);
    font-size: 0.875rem;
}

.mb-choice-remove-btn {
    background: var(--mb-destructive);
    color: white;
    border: none;
    border-radius: calc(var(--mb-radius) - 2px);
    width: 1.5rem;
    height: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
}

/* Toggle Switch */
.mb-toggle-switch {
    position: relative;
    display: inline-block;
    width: 3rem;
    height: 1.5rem;
}

.mb-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.mb-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--mb-border);
    transition: 0.4s;
    border-radius: 1.5rem;
}

.mb-toggle-slider:before {
    position: absolute;
    content: "";
    height: 1.125rem;
    width: 1.125rem;
    left: 0.1875rem;
    bottom: 0.1875rem;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .mb-toggle-slider {
    background-color: var(--mb-primary);
}

input:checked + .mb-toggle-slider:before {
    transform: translateX(1.5rem);
}

/* Sortable functionality */
.mb-sortable {
    position: relative;
}

.mb-sortable .mb-option-item {
    transition: all 0.2s ease;
    cursor: move;
}

.mb-sortable .mb-option-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.mb-sortable .mb-option-item.ui-sortable-helper {
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    transform: rotate(2deg);
    z-index: 1000;
}

.mb-sortable .mb-option-item.ui-sortable-placeholder {
    border: 2px dashed var(--mb-border);
    background-color: var(--mb-muted);
    visibility: visible !important;
    height: 100px;
    margin: 0.5rem 0;
}

.mb-choices-sortable {
    position: relative;
}

.mb-choices-sortable .mb-choice-item {
    transition: all 0.2s ease;
    cursor: move;
}

.mb-choices-sortable .mb-choice-item:hover {
    background-color: rgba(var(--mb-primary), 0.05);
}

.mb-choices-sortable .mb-choice-item.ui-sortable-helper {
    background-color: var(--mb-background);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    z-index: 1000;
}

.mb-choices-sortable .mb-choice-item.ui-sortable-placeholder {
    border: 2px dashed var(--mb-border);
    background-color: var(--mb-muted);
    visibility: visible !important;
    height: 40px;
    margin: 0.25rem 0;
}

/* Drag handle states */
.mb-option-drag-handle,
.mb-choice-drag-handle {
    opacity: 0.5;
    transition: opacity 0.2s ease;
}

.mb-option-item:hover .mb-option-drag-handle,
.mb-choice-item:hover .mb-choice-drag-handle {
    opacity: 1;
}

/* Loading and States */
.mb-loading {
    opacity: 0.6;
    pointer-events: none;
}

.mb-spinner {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border: 2px solid var(--mb-muted-foreground);
    border-radius: 50%;
    border-top-color: var(--mb-primary);
    animation: mb-spin 1s ease-in-out infinite;
    margin-left: 0.5rem;
}

@keyframes mb-spin {
    to { transform: rotate(360deg); }
}

/* Save button enhancements */
.mb-btn-with-icon {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.mb-btn-icon {
    width: 1rem;
    height: 1rem;
    flex-shrink: 0;
}

/* No Options State */
.mb-no-options {
    text-align: center;
    padding: 2rem;
    color: var(--mb-muted-foreground);
    font-style: italic;
}

/* Image Preview */
.mb-image-preview {
    width: 150px;
    height: 150px;
    border: 2px dashed var(--mb-border);
    border-radius: var(--mb-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    overflow: hidden;
    cursor: pointer;
}

.mb-image-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.mb-image-placeholder {
    color: var(--mb-muted-foreground);
    text-align: center;
    font-size: 0.875rem;
}

/* Responsive */
@media (max-width: 768px) {
    .mb-container {
        padding: 1rem;
    }

    .mb-radio-group {
        gap: 0.25rem;
    }

    .mb-radio-option-label {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }
}
</style>

<div class="wrap mobooking-wrap">
    <!-- Header -->
        <h1 id="mb-page-title"><?php echo esc_html($page_title); ?></h1>


    <!-- Error Message -->
    <?php if (!empty($error_message)): ?>
        <div class="mb-alert mb-alert-error">
            <?php echo esc_html($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Alert Container -->
    <div id="mb-alert-container"></div>

    <!-- Main Form -->
    <form id="mobooking-service-form" class="mb-space-y-6">
        <?php wp_nonce_field('mobooking_services_nonce', 'mobooking_services_nonce'); ?>
        
        <?php if ($edit_mode): ?>
            <input type="hidden" name="service_id" value="<?php echo esc_attr($service_id); ?>">
        <?php endif; ?>

        <!-- Basic Information -->
        <div class="mb-form-card">
            <h2 class="mb-form-section-title"><?php esc_html_e('Basic Information', 'mobooking'); ?></h2>
            
            <div class="mb-space-y-4">
                <div class="mb-form-grid">
                    <div class="mb-form-group">
                        <label class="mb-form-label" for="service-name">
                            <?php esc_html_e('Service Name', 'mobooking'); ?> <span class="mb-text-destructive">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="service-name" 
                            name="name" 
                            class="mb-form-input" 
                            placeholder="<?php esc_attr_e('e.g., Deep House Cleaning', 'mobooking'); ?>"
                            value="<?php echo esc_attr($service_name); ?>"
                            required
                        >
                    </div>

                    <div class="mb-form-group">
                        <label class="mb-form-label" for="service-price">
                            <?php 
                            printf(
                                esc_html__('Price (%s)', 'mobooking'),
                                esc_html($currency_symbol)
                            ); 
                            ?> <span class="mb-text-destructive">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="service-price" 
                            name="price" 
                            class="mb-form-input" 
                            step="0.01" 
                            min="0"
                            placeholder="0.00"
                            value="<?php echo esc_attr($service_price); ?>"
                            required
                        >
                    </div>
                </div>

                <div class="mb-form-grid">
                    <div class="mb-form-group">
                        <label class="mb-form-label" for="service-duration">
                            <?php esc_html_e('Duration (minutes)', 'mobooking'); ?> <span class="mb-text-destructive">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="service-duration" 
                            name="duration" 
                            class="mb-form-input" 
                            min="15" 
                            step="15"
                            placeholder="60"
                            value="<?php echo esc_attr($service_duration); ?>"
                            required
                        >
                    </div>

                    <div class="mb-form-group">
                        <label class="mb-form-label"><?php esc_html_e('Status', 'mobooking'); ?></label>
                        <div class="mb-flex mb-items-center mb-gap-3">
                            <label class="mobooking-toggle-switch">
                                <input type="checkbox" id="service-status" name="status" <?php checked($service_status, 'active'); ?>>
                                <span class="slider"></span>
                            </label>
                            <span id="status-text" class="mb-text-sm toggle-label">
                                <?php echo $service_status === 'active' ? esc_html__('Active', 'mobooking') : esc_html__('Inactive', 'mobooking'); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mb-form-group">
                    <label class="mb-form-label" for="service-description"><?php esc_html_e('Description', 'mobooking'); ?></label>
                    <textarea 
                        id="service-description" 
                        name="description" 
                        class="mb-form-textarea" 
                        rows="3"
                        placeholder="<?php esc_attr_e('Describe your service in detail...', 'mobooking'); ?>"
                    ><?php echo esc_textarea($service_description); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Media -->
        <div class="mb-form-card">
            <h2 class="mb-form-section-title"><?php esc_html_e('Media', 'mobooking'); ?></h2>
            
            <div class="mb-form-grid">
                <div class="mb-form-group">
                    <label class="mb-form-label"><?php esc_html_e('Service Image', 'mobooking'); ?></label>
                    <div class="mb-image-preview" id="image-preview">
                        <?php if (!empty($service_image_url)): ?>
                            <img src="<?php echo esc_url($service_image_url); ?>" alt="<?php esc_attr_e('Service Image', 'mobooking'); ?>">
                        <?php else: ?>
                            <div class="mb-image-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                    <circle cx="9" cy="9" r="2"/>
                                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                                </svg>
                                <div class="mb-mt-2"><?php esc_html_e('Click to upload', 'mobooking'); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="file" id="image-upload" accept="image/*" class="mb-hidden">
                    <input type="hidden" id="image-url" name="image_url" value="<?php echo esc_attr($service_image_url); ?>">
                    <div class="mb-flex mb-gap-2">
                        <button type="button" id="upload-image-btn" class="mb-btn mb-btn-secondary mb-btn-sm">
                            <?php esc_html_e('Upload Image', 'mobooking'); ?>
                        </button>
                        <button type="button" id="remove-image-btn" class="mb-btn mb-btn-destructive mb-btn-sm <?php echo empty($service_image_url) ? 'mb-hidden' : ''; ?>">
                            <?php esc_html_e('Remove', 'mobooking'); ?>
                        </button>
                    </div>
                </div>

                <div class="mb-form-group">
                    <label class="mb-form-label"><?php esc_html_e('Icon', 'mobooking'); ?></label>
                    <div id="icon-preview" class="mb-image-preview">
                        <?php
                        if (!empty($service_icon)):
                            $icon_html = '';
                            if (strpos($service_icon, 'preset:') === 0) {
                                $key = substr($service_icon, strlen('preset:'));
                                // We need access to Services class to get SVG content
                                if (class_exists('\MoBooking\Classes\Services')) {
                                    $icon_html = \MoBooking\Classes\Services::get_preset_icon_svg($key);
                                }
                            } elseif (filter_var($service_icon, FILTER_VALIDATE_URL)) {
                                $icon_html = '<img src="' . esc_url($service_icon) . '" alt="' . esc_attr__('Service Icon', 'mobooking') . '" style="width: 48px; height: 48px; object-fit: contain;">';
                            }
                            // Fallback for old dashicons or if SVG content is empty
                            if (empty($icon_html) && !filter_var($service_icon, FILTER_VALIDATE_URL) && strpos($service_icon, 'dashicons-') === 0) {
                                $icon_html = '<span class="' . esc_attr($service_icon) . '" style="font-size: 48px;"></span>';
                            }
                            echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is constructed with esc_url/esc_attr or from trusted SVG source.
                        else: ?>
                            <div class="mb-image-placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="m9 12 2 2 4-4"/>
                                </svg>
                                <div class="mb-mt-2"><?php esc_html_e('Select Icon', 'mobooking'); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="icon-value" name="icon" value="<?php echo esc_attr($service_icon); ?>">
                    <input type="file" id="custom-icon-upload" accept=".svg" class="mb-hidden">
                    <div class="mb-flex mb-gap-2">
                        <button type="button" id="select-icon-btn" class="mb-btn mb-btn-secondary mb-btn-sm">
                            <?php esc_html_e('Select Preset Icon', 'mobooking'); ?>
                        </button>
                        <button type="button" id="upload-custom-icon-btn" class="mb-btn mb-btn-secondary mb-btn-sm">
                            <?php esc_html_e('Upload SVG Icon', 'mobooking'); ?>
                        </button>
                        <button type="button" id="remove-icon-btn" class="mb-btn mb-btn-destructive mb-btn-sm <?php echo empty($service_icon) ? 'mb-hidden' : ''; ?>">
                            <?php esc_html_e('Remove', 'mobooking'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Icon Selector Modal -->
        <div id="icon-selector-modal" class="mb-hidden" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1050;">
            <div style="background: white; padding: 20px; border-radius: var(--mb-radius); width: 80%; max-width: 600px; max-height: 80vh; overflow-y: auto;">
                <div class="mb-flex mb-justify-between mb-items-center mb-mb-4">
                    <h3 class="mb-form-section-title" style="margin-bottom: 0;"><?php esc_html_e('Select a Preset Icon', 'mobooking'); ?></h3>
                    <button type="button" id="close-icon-modal-btn" class="mb-btn mb-btn-sm">&times;</button>
                </div>
                <div id="preset-icons-grid" class="mb-grid mb-gap-2" style="grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));">
                    <?php /* Preset icons will be loaded here by JavaScript */ ?>
                </div>
                 <div class="mb-mt-4 mb-text-center">
                    <button type="button" id="cancel-icon-select-btn" class="mb-btn mb-btn-secondary"><?php esc_html_e('Cancel', 'mobooking'); ?></button>
                </div>
            </div>
        </div>

        <!-- Service Options -->
        <div class="mb-form-card">
            <div class="mb-flex mb-items-center mb-justify-between mb-mb-4">
                <h2 class="mb-form-section-title" style="margin-bottom: 0;"><?php esc_html_e('Service Options', 'mobooking'); ?></h2>
                <button type="button" id="add-option-btn" class="mb-btn mb-btn-secondary mb-btn-sm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"/>
                        <path d="m12 5 7 7-7 7"/>
                    </svg>
                    <?php esc_html_e('Add Option', 'mobooking'); ?>
                </button>
            </div>

            <div id="options-container" class="mb-options-container mb-sortable">
                <?php if (empty($service_options_data)): ?>
                    <div class="mb-no-options">
                        <?php esc_html_e('No options added yet. Click "Add Option" to create customization options for this service.', 'mobooking'); ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($service_options_data as $index => $option): ?>
                        <div class="mb-option-item" data-option-index="<?php echo esc_attr($index); ?>">
                            <div class="mb-option-header">
                                <div class="mb-flex mb-items-center">
                                    <span class="mb-option-drag-handle">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="9" cy="12" r="1"/>
                                            <circle cx="9" cy="5" r="1"/>
                                            <circle cx="9" cy="19" r="1"/>
                                            <circle cx="15" cy="12" r="1"/>
                                            <circle cx="15" cy="5" r="1"/>
                                            <circle cx="15" cy="19" r="1"/>
                                        </svg>
                                    </span>
                                    <span class="mb-option-title"><?php echo esc_html($option['name'] ?: __('New Option', 'mobooking')); ?></span>
                                </div>
                                <button type="button" class="mb-option-remove-btn" title="<?php esc_attr_e('Remove option', 'mobooking'); ?>">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m18 6-12 12"/>
                                        <path d="m6 6 12 12"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="mb-space-y-4">
                                <input type="hidden" name="options[<?php echo esc_attr($index); ?>][option_id]" value="<?php echo esc_attr($option['option_id'] ?? ''); ?>">
                                <input type="hidden" name="options[<?php echo esc_attr($index); ?>][sort_order]" value="<?php echo esc_attr($index + 1); ?>">

                                <div class="mb-form-grid">
                                    <div class="mb-form-group">
                                        <label class="mb-form-label">
                                            <?php esc_html_e('Option Name', 'mobooking'); ?> <span class="mb-text-destructive">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            name="options[<?php echo esc_attr($index); ?>][name]" 
                                            class="mb-form-input" 
                                            placeholder="<?php esc_attr_e('e.g., Room Size', 'mobooking'); ?>"
                                            value="<?php echo esc_attr($option['name'] ?? ''); ?>"
                                            required
                                        >
                                    </div>

                                    <div class="mb-form-group">
                                        <label class="mb-form-label"><?php esc_html_e('Required', 'mobooking'); ?></label>
                                        <div class="mb-flex mb-items-center mb-gap-3">
                                            <label class="mobooking-toggle-switch">
                                                <input type="checkbox" name="options[<?php echo esc_attr($index); ?>][is_required]" value="1" <?php checked(!empty($option['is_required'])); ?>>
                                                <span class="slider"></span>
                                            </label>
                                            <span class="mb-text-sm mb-text-muted-foreground toggle-label"><?php esc_html_e('Customer must select this option', 'mobooking'); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-form-group">
                                    <label class="mb-form-label"><?php esc_html_e('Description', 'mobooking'); ?></label>
                                    <textarea 
                                        name="options[<?php echo esc_attr($index); ?>][description]" 
                                        class="mb-form-textarea" 
                                        rows="2"
                                        placeholder="<?php esc_attr_e('Helpful description for customers...', 'mobooking'); ?>"
                                    ><?php echo esc_textarea($option['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-form-group">
                                    <label class="mb-form-label"><?php esc_html_e('Option Type', 'mobooking'); ?></label>
                                    <div class="mb-radio-group">
                                        <?php 
                                        // $option_types is now defined globally at the top of the script
                                        foreach ($option_types as $type_value => $type_label): 
                                        ?>
                                            <div class="mb-radio-option">
                                                <input type="radio" name="options[<?php echo esc_attr($index); ?>][type]" value="<?php echo esc_attr($type_value); ?>" id="type-<?php echo esc_attr($type_value); ?>-<?php echo esc_attr($index); ?>" <?php checked($option['type'] ?? 'checkbox', $type_value); ?>>
                                                <label for="type-<?php echo esc_attr($type_value); ?>-<?php echo esc_attr($index); ?>" class="mb-radio-option-label"><?php echo esc_html($type_label); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="mb-form-grid">
                                    <div class="mb-form-group">
                                        <label class="mb-form-label"><?php esc_html_e('Price Impact Type', 'mobooking'); ?></label>
                                        <div class="mb-radio-group">
                                            <?php 
                                            // $price_types is now defined globally at the top of the script
                                            foreach ($price_types as $price_value => $price_label): 
                                            ?>
                                                <div class="mb-radio-option">
                                                    <input type="radio" name="options[<?php echo esc_attr($index); ?>][price_impact_type]" value="<?php echo esc_attr($price_value); ?>" id="price-<?php echo esc_attr($price_value ?: 'none'); ?>-<?php echo esc_attr($index); ?>" <?php checked($option['price_impact_type'] ?? '', $price_value); ?>>
                                                    <label for="price-<?php echo esc_attr($price_value ?: 'none'); ?>-<?php echo esc_attr($index); ?>" class="mb-radio-option-label"><?php echo esc_html($price_label); ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="mb-form-group">
                                        <label class="mb-form-label"><?php esc_html_e('Price Impact Value', 'mobooking'); ?></label>
                                        <input 
                                            type="number" 
                                            name="options[<?php echo esc_attr($index); ?>][price_impact_value]" 
                                            class="mb-form-input" 
                                            step="0.01" 
                                            min="0"
                                            placeholder="0.00"
                                            value="<?php echo esc_attr($option['price_impact_value'] ?? ''); ?>"
                                        >
                                    </div>
                                </div>

                                <!-- Choices Container (for select/radio types) -->
                                <div class="mb-choices-container mb-option-choices" style="display: <?php echo in_array($option['type'] ?? '', ['select', 'radio']) ? 'block' : 'none'; ?>;">
                                    <div class="mb-flex mb-items-center mb-justify-between mb-mb-3">
                                        <label class="mb-form-label" style="margin-bottom: 0;"><?php esc_html_e('Option Choices', 'mobooking'); ?></label>
                                        <button type="button" class="mb-btn mb-btn-secondary mb-btn-sm add-choice-btn">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M5 12h14"/>
                                                <path d="m12 5 7 7-7 7"/>
                                            </svg>
                                            <?php esc_html_e('Add Choice', 'mobooking'); ?>
                                        </button>
                                    </div>
                                    <div class="mb-choices-list mb-choices-sortable">
                                        <?php 
                                        $choices = [];
                                        if (!empty($option['option_values']) && ($option['type'] === 'select' || $option['type'] === 'radio')) {
                                            $choices = is_string($option['option_values']) ? json_decode($option['option_values'], true) : $option['option_values'];
                                            if (!is_array($choices)) $choices = [];
                                        }
                                        
                                        if (!empty($choices)):
                                            foreach ($choices as $choice_index => $choice):
                                        ?>
                                            <div class="mb-choice-item">
                                                <span class="mb-choice-drag-handle">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="9" cy="12" r="1"/>
                                                        <circle cx="9" cy="5" r="1"/>
                                                        <circle cx="9" cy="19" r="1"/>
                                                        <circle cx="15" cy="12" r="1"/>
                                                        <circle cx="15" cy="5" r="1"/>
                                                        <circle cx="15" cy="19" r="1"/>
                                                    </svg>
                                                </span>
                                                <input 
                                                    type="text" 
                                                    name="choice_label[]" 
                                                    class="mb-choice-input" 
                                                    placeholder="<?php esc_attr_e('Choice label', 'mobooking'); ?>"
                                                    value="<?php echo esc_attr($choice['label'] ?? ''); ?>"
                                                    required
                                                >
                                                <input 
                                                    type="text" 
                                                    name="choice_value[]" 
                                                    class="mb-choice-input" 
                                                    placeholder="<?php esc_attr_e('Value', 'mobooking'); ?>"
                                                    value="<?php echo esc_attr($choice['value'] ?? ''); ?>"
                                                    required
                                                >
                                                <input 
                                                    type="number" 
                                                    name="choice_price[]" 
                                                    class="mb-choice-input" 
                                                    placeholder="<?php esc_attr_e('Price', 'mobooking'); ?>"
                                                    step="0.01"
                                                    style="max-width: 80px;"
                                                    value="<?php echo esc_attr($choice['price'] ?? ''); ?>"
                                                >
                                                <button type="button" class="mb-choice-remove-btn" title="<?php esc_attr_e('Remove choice', 'mobooking'); ?>">×</button>
                                            </div>
                                        <?php 
                                            endforeach;
                                        endif; 
                                        ?>
                                    </div>
                                </div>

                                <!-- SQM Ranges Container (for sqm type) -->
                                <div class="mb-sqm-ranges-container" style="display: <?php echo ($option['type'] ?? '') === 'sqm' ? 'block' : 'none'; ?>;">
                                    <div class="mb-flex mb-items-center mb-justify-between mb-mb-3">
                                        <label class="mb-form-label" style="margin-bottom: 0;"><?php esc_html_e('SQM Pricing Ranges', 'mobooking'); ?></label>
                                        <button type="button" class="mb-btn mb-btn-secondary mb-btn-sm add-sqm-range-btn">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M5 12h14"/>
                                                <path d="m12 5 7 7-7 7"/>
                                            </svg>
                                            <?php esc_html_e('Add New Range', 'mobooking'); ?>
                                        </button>
                                    </div>
                                    <div class="mb-sqm-ranges-list">
                                        <!-- Table Header -->
                                        <div class="mb-grid mb-gap-2 mb-text-sm mb-font-medium mb-text-muted-foreground" style="grid-template-columns: 1fr 1fr 1fr auto; padding-bottom: 0.5rem; border-bottom: 1px solid var(--mb-border);">
                                            <div><?php esc_html_e('From (SQM)', 'mobooking'); ?></div>
                                            <div><?php esc_html_e('To (SQM)', 'mobooking'); ?></div>
                                            <div><?php esc_html_e('Price per SQM', 'mobooking'); ?></div>
                                            <div><?php esc_html_e('Action', 'mobooking'); ?></div>
                                        </div>
                                        <!-- SQM Range Items will be populated here by JS or PHP for existing options -->
                                        <?php
                                        $sqm_ranges = [];
                                        if (!empty($option['option_values']) && ($option['type'] === 'sqm')) {
                                            $sqm_ranges = is_string($option['option_values']) ? json_decode($option['option_values'], true) : $option['option_values'];
                                            if (!is_array($sqm_ranges)) $sqm_ranges = [];
                                        }

                                        if (!empty($sqm_ranges)):
                                            foreach ($sqm_ranges as $range_idx => $range):
                                        ?>
                                        <div class="mb-sqm-range-item mb-grid mb-gap-2 mb-items-center" style="grid-template-columns: 1fr 1fr 1fr auto; padding-top: 0.5rem;">
                                            <input type="number" name="sqm_range_from[]" class="mb-form-input mb-form-input-sm" placeholder="0" value="<?php echo esc_attr($range['from_sqm'] ?? ''); ?>" min="0" step="1" required>
                                            <input type="text" name="sqm_range_to[]" class="mb-form-input mb-form-input-sm" placeholder="<?php esc_attr_e('e.g., 50 or ∞', 'mobooking'); ?>" value="<?php echo ($range['to_sqm'] == INF || strtolower($range['to_sqm'] ?? '') === 'infinity') ? '∞' : esc_attr($range['to_sqm'] ?? ''); ?>" required>
                                            <input type="number" name="sqm_range_price[]" class="mb-form-input mb-form-input-sm" placeholder="5.00" value="<?php echo esc_attr($range['price_per_sqm'] ?? ''); ?>" min="0.01" step="0.01" required>
                                            <button type="button" class="mb-btn mb-btn-destructive mb-btn-sm remove-sqm-range-btn" title="<?php esc_attr_e('Remove range', 'mobooking'); ?>">×</button>
                                        </div>
                                        <?php
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="mb-form-card">
            <div class="mb-flex mb-items-center mb-justify-between">
                <button type="button" id="cancel-btn" class="mb-btn mb-btn-secondary">
                    <?php esc_html_e('Cancel', 'mobooking'); ?>
                </button>
                <button type="submit" id="save-btn" class="mb-btn mb-btn-primary mb-btn-with-icon">
                    <svg class="mb-btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
                    </svg>
                    <span id="save-text">
                        <?php echo $edit_mode ? esc_html__('Update Service', 'mobooking') : esc_html__('Create Service', 'mobooking'); ?>
                    </span>
                    <span id="save-spinner" class="mb-spinner mb-hidden"></span>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Option Template -->
<script type="text/template" id="option-template">
    <div class="mb-option-item" data-option-index="">
        <div class="mb-option-header">
            <div class="mb-flex mb-items-center">
                <span class="mb-option-drag-handle">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="12" r="1"/>
                        <circle cx="9" cy="5" r="1"/>
                        <circle cx="9" cy="19" r="1"/>
                        <circle cx="15" cy="12" r="1"/>
                        <circle cx="15" cy="5" r="1"/>
                        <circle cx="15" cy="19" r="1"/>
                    </svg>
                </span>
                <span class="mb-option-title"><?php esc_html_e('New Option', 'mobooking'); ?></span>
            </div>
            <button type="button" class="mb-option-remove-btn" title="<?php esc_attr_e('Remove option', 'mobooking'); ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m18 6-12 12"/>
                    <path d="m6 6 12 12"/>
                </svg>
            </button>
        </div>

        <div class="mb-space-y-4">
            <input type="hidden" name="options[][option_id]" value="">
            <input type="hidden" name="options[][sort_order]" value="0">

            <div class="mb-form-grid">
                <div class="mb-form-group">
                    <label class="mb-form-label">
                        <?php esc_html_e('Option Name', 'mobooking'); ?> <span class="mb-text-destructive">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="options[][name]" 
                        class="mb-form-input" 
                        placeholder="<?php esc_attr_e('e.g., Room Size', 'mobooking'); ?>"
                        required
                    >
                </div>

                <div class="mb-form-group">
                    <label class="mb-form-label"><?php esc_html_e('Required', 'mobooking'); ?></label>
                    <div class="mb-flex mb-items-center mb-gap-3">
                            <label class="mobooking-toggle-switch">
                            <input type="checkbox" name="options[][is_required]" value="1">
                                <span class="slider"></span>
                        </label>
                            <span class="mb-text-sm mb-text-muted-foreground toggle-label"><?php esc_html_e('Customer must select this option', 'mobooking'); ?></span>
                    </div>
                </div>
            </div>

            <div class="mb-form-group">
                <label class="mb-form-label"><?php esc_html_e('Description', 'mobooking'); ?></label>
                <textarea 
                    name="options[][description]" 
                    class="mb-form-textarea" 
                    rows="2"
                    placeholder="<?php esc_attr_e('Helpful description for customers...', 'mobooking'); ?>"
                ></textarea>
            </div>

            <div class="mb-form-group">
                <label class="mb-form-label"><?php esc_html_e('Option Type', 'mobooking'); ?></label>
                <div class="mb-radio-group">
                    <?php foreach ($option_types as $type_value => $type_label): ?>
                        <div class="mb-radio-option">
                            <input type="radio" name="options[][type]" value="<?php echo esc_attr($type_value); ?>" id="type-<?php echo esc_attr($type_value); ?>-{INDEX}" <?php checked($type_value, 'checkbox'); ?>>
                            <label for="type-<?php echo esc_attr($type_value); ?>-{INDEX}" class="mb-radio-option-label"><?php echo esc_html($type_label); ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-form-grid">
                <div class="mb-form-group">
                    <label class="mb-form-label"><?php esc_html_e('Price Impact Type', 'mobooking'); ?></label>
                    <div class="mb-radio-group">
                        <?php foreach ($price_types as $price_value => $price_label): ?>
                            <div class="mb-radio-option">
                                <input type="radio" name="options[][price_impact_type]" value="<?php echo esc_attr($price_value); ?>" id="price-<?php echo esc_attr($price_value ?: 'none'); ?>-{INDEX}" <?php checked($price_value, ''); ?>>
                                <label for="price-<?php echo esc_attr($price_value ?: 'none'); ?>-{INDEX}" class="mb-radio-option-label"><?php echo esc_html($price_label); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-form-group">
                    <label class="mb-form-label"><?php esc_html_e('Price Impact Value', 'mobooking'); ?></label>
                    <input 
                        type="number" 
                        name="options[][price_impact_value]" 
                        class="mb-form-input" 
                        step="0.01" 
                        min="0"
                        placeholder="0.00"
                    >
                </div>
            </div>

            <!-- Choices Container (for select/radio types) -->
            <div class="mb-choices-container mb-option-choices" style="display: none;">
                <div class="mb-flex mb-items-center mb-justify-between mb-mb-3">
                    <label class="mb-form-label" style="margin-bottom: 0;"><?php esc_html_e('Option Choices', 'mobooking'); ?></label>
                    <button type="button" class="mb-btn mb-btn-secondary mb-btn-sm add-choice-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"/>
                            <path d="m12 5 7 7-7 7"/>
                        </svg>
                        <?php esc_html_e('Add Choice', 'mobooking'); ?>
                    </button>
                </div>
                <div class="mb-choices-list mb-choices-sortable"></div>
            </div>

            <!-- SQM Ranges Container (for sqm type) -->
            <div class="mb-sqm-ranges-container" style="display: none;">
                <div class="mb-flex mb-items-center mb-justify-between mb-mb-3">
                    <label class="mb-form-label" style="margin-bottom: 0;"><?php esc_html_e('SQM Pricing Ranges', 'mobooking'); ?></label>
                    <button type="button" class="mb-btn mb-btn-secondary mb-btn-sm add-sqm-range-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"/>
                            <path d="m12 5 7 7-7 7"/>
                        </svg>
                        <?php esc_html_e('Add New Range', 'mobooking'); ?>
                    </button>
                </div>
                <div class="mb-sqm-ranges-list">
                    <!-- Table Header -->
                    <div class="mb-grid mb-gap-2 mb-text-sm mb-font-medium mb-text-muted-foreground" style="grid-template-columns: 1fr 1fr 1fr auto; padding-bottom: 0.5rem; border-bottom: 1px solid var(--mb-border);">
                        <div><?php esc_html_e('From (SQM)', 'mobooking'); ?></div>
                        <div><?php esc_html_e('To (SQM)', 'mobooking'); ?></div>
                        <div><?php esc_html_e('Price per SQM', 'mobooking'); ?></div>
                        <div><?php esc_html_e('Action', 'mobooking'); ?></div>
                    </div>
                    <!-- SQM Range Items will be populated by JS -->
                </div>
            </div>
        </div>
    </div>
</script>

<!-- Choice Template -->
<script type="text/template" id="choice-template">
    <div class="mb-choice-item">
        <span class="mb-choice-drag-handle">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="9" cy="12" r="1"/>
                <circle cx="9" cy="5" r="1"/>
                <circle cx="9" cy="19" r="1"/>
                <circle cx="15" cy="12" r="1"/>
                <circle cx="15" cy="5" r="1"/>
                <circle cx="15" cy="19" r="1"/>
            </svg>
        </span>
        <input 
            type="text" 
            name="choice_label[]" 
            class="mb-choice-input" 
            placeholder="<?php esc_attr_e('Choice label', 'mobooking'); ?>"
            required
        >
        <input 
            type="text" 
            name="choice_value[]" 
            class="mb-choice-input" 
            placeholder="<?php esc_attr_e('Value', 'mobooking'); ?>"
            required
        >
        <input 
            type="number" 
            name="choice_price[]" 
            class="mb-choice-input" 
            placeholder="<?php esc_attr_e('Price', 'mobooking'); ?>"
            step="0.01"
            style="max-width: 80px;"
        >
        <button type="button" class="mb-choice-remove-btn" title="<?php esc_attr_e('Remove choice', 'mobooking'); ?>">×</button>
    </div>
</script>

<!-- SQM Range Item Template -->
<script type="text/template" id="sqm-range-item-template">
    <div class="mb-sqm-range-item mb-grid mb-gap-2 mb-items-center" style="grid-template-columns: 1fr 1fr 1fr auto; padding-top: 0.5rem;">
        <input type="number" name="sqm_range_from[]" class="mb-form-input mb-form-input-sm" placeholder="0" min="0" step="1" required>
        <input type="text" name="sqm_range_to[]" class="mb-form-input mb-form-input-sm" placeholder="<?php esc_attr_e('e.g., 50 or ∞', 'mobooking'); ?>" required>
        <input type="number" name="sqm_range_price[]" class="mb-form-input mb-form-input-sm" placeholder="5.00" min="0.01" step="0.01" required>
        <button type="button" class="mb-btn mb-btn-destructive mb-btn-sm remove-sqm-range-btn" title="<?php esc_attr_e('Remove range', 'mobooking'); ?>">×</button>
    </div>
</script>

<script>
jQuery(document).ready(function($) {
    'use strict';

    // Global variables
    let optionIndex = <?php echo count($service_options_data); ?>;
    const isEditMode = <?php echo $edit_mode ? 'true' : 'false'; ?>;
    const serviceId = <?php echo $service_id ? intval($service_id) : 'null'; ?>;
    const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
    const nonce = '<?php echo wp_create_nonce('mobooking_services_nonce'); ?>';

    // Localized strings
    const strings = {
        confirmDeleteOption: '<?php echo esc_js(__('Are you sure you want to remove this option?', 'mobooking')); ?>',
        newOption: '<?php echo esc_js(__('New Option', 'mobooking')); ?>',
        noOptionsYet: '<?php echo esc_js(__('No options added yet. Click "Add Option" to create customization options for this service.', 'mobooking')); ?>',
        creating: '<?php echo esc_js(__('Creating...', 'mobooking')); ?>',
        updating: '<?php echo esc_js(__('Updating...', 'mobooking')); ?>',
        createService: '<?php echo esc_js(__('Create Service', 'mobooking')); ?>',
        updateService: '<?php echo esc_js(__('Update Service', 'mobooking')); ?>',
        active: '<?php echo esc_js(__('Active', 'mobooking')); ?>',
        inactive: '<?php echo esc_js(__('Inactive', 'mobooking')); ?>',
        networkError: '<?php echo esc_js(__('Network error occurred', 'mobooking')); ?>',
        invalidImageFile: '<?php echo esc_js(__('Please select a valid image file.', 'mobooking')); ?>',
        imageTooLarge: '<?php echo esc_js(__('Image size must be less than 5MB.', 'mobooking')); ?>',
        iconSelectorComingSoon: '<?php echo esc_js(__('Icon selector coming soon!', 'mobooking')); ?>',
        loading: '<?php echo esc_js(__('Loading...', 'mobooking')); ?>',
        errorGeneric: '<?php echo esc_js(__('An error occurred. Please try again.', 'mobooking')); ?>',
        invalidSvgFile: '<?php echo esc_js(__('Please select a valid SVG file.', 'mobooking')); ?>',
        svgTooLarge: '<?php echo esc_js(__('SVG file size must be less than 100KB.', 'mobooking')); ?>'
    };

    // Initialize the page
    initializePage();
    setupEventListeners();
    initializeSortable();

    function initializePage() {
        // Update page title and button text
        if (isEditMode) {
            $('#mb-page-title').text('<?php echo esc_js(__('Edit Service', 'mobooking')); ?>');
            $('#save-text').text(strings.updateService);
        } else {
            $('#mb-page-title').text('<?php echo esc_js(__('Add New Service', 'mobooking')); ?>');
            $('#save-text').text(strings.createService);
        }
    }

    function initializeSortable() {
        // Initialize sortable for options container
        $('#options-container').sortable({
            handle: '.mb-option-drag-handle',
            placeholder: 'ui-sortable-placeholder mb-option-item',
            tolerance: 'pointer',
            cursor: 'move',
            opacity: 0.8,
            distance: 5,
            update: function(event, ui) {
                updateOptionSortOrders();
            },
            start: function(event, ui) {
                ui.placeholder.height(ui.item.height());
            }
        });

        // Initialize sortable for existing choices
        initializeChoicesSortable();
    }

    function initializeChoicesSortable() {
        $('.mb-choices-sortable').sortable({
            handle: '.mb-choice-drag-handle',
            placeholder: 'ui-sortable-placeholder mb-choice-item',
            tolerance: 'pointer',
            cursor: 'move',
            opacity: 0.8,
            distance: 5,
            start: function(event, ui) {
                ui.placeholder.height(ui.item.height());
            }
        });
    }

    function updateOptionSortOrders() {
        $('.mb-option-item').each(function(index) {
            $(this).find('input[name*="[sort_order]"]').val(index + 1);
            $(this).attr('data-option-index', index);
            
            // Update input names to maintain correct indexing
            updateOptionInputNames($(this), index);
        });
    }

    function updateOptionInputNames($optionItem, newIndex) {
        $optionItem.find('input, textarea, select').each(function() {
            const $input = $(this);
            let name = $input.attr('name');
            if (name && name.includes('options[')) {
                // Replace the index in the name attribute
                name = name.replace(/options\[\d+\]/, `options[${newIndex}]`);
                $input.attr('name', name);
            }
        });

        // Update radio button IDs and labels
        $optionItem.find('input[type="radio"]').each(function() {
            const $input = $(this);
            const oldId = $input.attr('id');
            if (oldId) {
                const newId = oldId.replace(/-\d+$/, `-${newIndex}`);
                $input.attr('id', newId);
                
                // Update corresponding label
                const $label = $optionItem.find(`label[for="${oldId}"]`);
                if ($label.length) {
                    $label.attr('for', newId);
                }
            }
        });
    }

    function setupEventListeners() {
        // Form submission
        $('#mobooking-service-form').on('submit', handleFormSubmit);
        
        // Cancel button
        $('#cancel-btn').on('click', function() {
            window.location.href = '<?php echo esc_url($breadcrumb_services); ?>';
        });

        // Status toggle
        $('#service-status').on('change', function() {
            $('#status-text').text(this.checked ? strings.active : strings.inactive);
        });

        // Add option button
        $('#add-option-btn').on('click', addNewOption);

        // Image upload
        $('#upload-image-btn').on('click', function() {
            $('#image-upload').click();
        });
        
        $('#image-upload').on('change', handleImageUpload);
        $('#remove-image-btn').on('click', removeImage);

        // Icon selection
        $('#select-icon-btn').on('click', openPresetIconSelector); // Changed from openIconSelector
        $('#upload-custom-icon-btn').on('click', function() {
            $('#custom-icon-upload').click();
        });
        $('#custom-icon-upload').on('change', handleCustomIconUpload);
        $('#remove-icon-btn').on('click', removeIcon);
        $('#close-icon-modal-btn, #cancel-icon-select-btn').on('click', closePresetIconSelector);
        $('body').on('click', '.preset-icon-item', function() {
            const iconKey = $(this).data('icon-key');
            const iconSvg = $(this).html();
            selectPresetIcon(iconKey, iconSvg);
        });


        // Image preview click
        $('#image-preview').on('click', function() {
            $('#image-upload').click();
        });

        // Options container event delegation
        const $optionsContainer = $('#options-container');
        
        // Option removal
        $optionsContainer.on('click', '.mb-option-remove-btn', function() {
            removeOption($(this).closest('.mb-option-item'));
        });

        // Option type change
        $optionsContainer.on('change', 'input[name*="[type]"]', function() {
            if (this.checked) {
                handleOptionTypeChange($(this));
            }
        });

        // Add choice buttons
        $optionsContainer.on('click', '.add-choice-btn', function() {
            addChoice($(this).closest('.mb-option-item'));
        });

        // Remove choice buttons
        $optionsContainer.on('click', '.mb-choice-remove-btn', function() {
            removeChoice($(this).closest('.mb-choice-item'));
        });

        // Option name updates
        $optionsContainer.on('input', 'input[name*="[name]"]', function() {
            updateOptionTitle($(this));
        });

        // Add SQM range button
        $optionsContainer.on('click', '.add-sqm-range-btn', function() {
            addSqmRange($(this).closest('.mb-option-item'));
        });

        // Remove SQM range button
        $optionsContainer.on('click', '.remove-sqm-range-btn', function() {
            removeSqmRange($(this).closest('.mb-sqm-range-item'));
        });
    }

    function addNewOption() {
        addOption(null, optionIndex++);
    }

    function addOption(optionData = null, index = 0) {
        // console.log('[MoBooking Debug] addOption called. Index:', index, 'OptionData:', optionData);
        const template = $('#option-template').html();
        if (!template) {
            // console.error('[MoBooking Debug] Option template not found!');
            return;
        }
        const $optionsContainer = $('#options-container');
        $optionsContainer.find('.mb-no-options').remove();

        let optionHtml = template.replace(/{INDEX}/g, index);
        const $optionElement = $(optionHtml);
        
        $optionElement.attr('data-option-index', index);
        
        $optionElement.find('input, textarea, select').each(function() {
            const $input = $(this);
            let name = $input.attr('name');
            if (name && name.includes('[]')) {
                name = name.replace('[]', `[${index}]`);
                $input.attr('name', name);
            }
        });

        $optionElement.find('input[name*="[sort_order]"]').val(index + 1);

        if (optionData) {
            populateOptionData($optionElement, optionData);
        } else {
            // For new options, if SQM type is default or selected, add an initial range
            const $checkedType = $optionElement.find('input[name*="[type]"]:checked');
            if ($checkedType.val() === 'sqm') {
                 addSqmRange($optionElement, { from_sqm: 0, to_sqm: '∞', price_per_sqm: '' }); // Add a default initial range
            }
        }

        $optionsContainer.append($optionElement);

        const $checkedOptionType = $optionElement.find('input[name*="[type]"]:checked');
        if ($checkedOptionType.length) {
            $checkedOptionType.trigger('change');
        } else {
            // Default to checkbox if nothing is checked in template (should not happen)
            $optionElement.find('input[name*="[type]"][value="checkbox"]').prop('checked', true).trigger('change');
        }
        
        initializeChoicesSortableForElement($optionElement.find('.mb-choices-sortable'));
        // SQM ranges are not sortable by default in this design, but if they were:
        // initializeSqmRangesSortableForElement($optionElement.find('.mb-sqm-ranges-list'));
        
        if (!optionData) {
            $optionElement.find('input[name*="[name]"]').focus();
        }
        updateOptionSortOrders();
    }

    function initializeChoicesSortableForElement($element) {
        if ($element.length && !$element.hasClass('ui-sortable-instance')) {
            $element.sortable({
                handle: '.mb-choice-drag-handle',
                placeholder: 'ui-sortable-placeholder mb-choice-item',
                // ... other sortable options
            }).addClass('ui-sortable-instance');
        }
    }


    function populateOptionData($optionElement, optionData) {
        // Basic fields
        $optionElement.find('input[name*="[name]"]').val(optionData.name || '');
        $optionElement.find('textarea[name*="[description]"]').val(optionData.description || '');
        $optionElement.find('input[name*="[is_required]"]').prop('checked', optionData.is_required == 1);
        $optionElement.find('input[name*="[option_id]"]').val(optionData.option_id || '');

        // Type selection
        $optionElement.find('input[name*="[type]"]').filter(`[value="${optionData.type}"]`).prop('checked', true).trigger('change');

        // Price impact (will be hidden by JS if type is SQM)
        $optionElement.find('input[name*="[price_impact_type]"]').filter(`[value="${optionData.price_impact_type || ''}"]`).prop('checked', true);
        $optionElement.find('input[name*="[price_impact_value]"]').val(optionData.price_impact_value || '');

        $optionElement.find('.mb-option-title').text(optionData.name || strings.newOption);

        // Handle choices for select/radio types OR SQM ranges
        if (optionData.option_values) {
            let values;
            try {
                values = typeof optionData.option_values === 'string'
                    ? JSON.parse(optionData.option_values) 
                    : optionData.option_values;
            } catch (e) {
                // console.error('Error parsing option_values for option:', optionData.name, e);
                values = [];
            }

            if (Array.isArray(values)) {
                if (optionData.type === 'select' || optionData.type === 'radio') {
                    const $choicesList = $optionElement.find('.mb-choices-list');
                    $choicesList.empty(); // Clear any template defaults
                    values.forEach(choice => addChoiceToContainer($choicesList, choice));
                } else if (optionData.type === 'sqm') {
                    const $sqmRangesList = $optionElement.find('.mb-sqm-ranges-list');
                     // Clear existing (header row is not part of this div, so it's safe)
                    $sqmRangesList.find('.mb-sqm-range-item').remove();
                    values.forEach(range => addSqmRangeToContainer($sqmRangesList, range));
                }
            }
        }
         // Trigger change on the type radio again after populating to ensure UI consistency
        $optionElement.find('input[name*="[type]"]:checked').trigger('change');
    }


    function removeOption($optionElement) {
        if (confirm(strings.confirmDeleteOption)) {
            $optionElement.remove();
            updateOptionSortOrders();
            const $optionsContainer = $('#options-container');
            if ($optionsContainer.find('.mb-option-item').length === 0) {
                $optionsContainer.html('<div class="mb-no-options">' + strings.noOptionsYet + '</div>');
            }
        }
    }

    function handleOptionTypeChange($typeInput) {
        const $optionItem = $typeInput.closest('.mb-option-item');
        const selectedType = $typeInput.val();

        // Hide/show relevant sections based on type
        const $choicesContainer = $optionItem.find('.mb-option-choices');
        const $sqmRangesContainer = $optionItem.find('.mb-sqm-ranges-container');
        const $priceImpactGroup = $optionItem.find('input[name*="[price_impact_type]"]').closest('.mb-form-grid'); // Assuming type and value are in same grid

        if (selectedType === 'select' || selectedType === 'radio') {
            $choicesContainer.show();
            $sqmRangesContainer.hide();
            $priceImpactGroup.show(); // Or hide, depending on whether choices have their own price impacts
            if ($choicesContainer.find('.mb-choices-list .mb-choice-item').length === 0) {
                addChoiceToContainer($choicesContainer.find('.mb-choices-list'));
            }
        } else if (selectedType === 'sqm') {
            $choicesContainer.hide();
            $sqmRangesContainer.show();
            $priceImpactGroup.hide(); // SQM ranges define price, so hide generic price impact fields
            if ($sqmRangesContainer.find('.mb-sqm-ranges-list .mb-sqm-range-item').length === 0) {
                 addSqmRangeToContainer($sqmRangesContainer.find('.mb-sqm-ranges-list'), { from_sqm: 0, to_sqm: '∞', price_per_sqm: '' });
            }
        } else { // For text, number, checkbox, textarea, quantity
            $choicesContainer.hide();
            $sqmRangesContainer.hide();
            $priceImpactGroup.show();
        }
    }


    function addChoice($optionItem) {
        const $choicesList = $optionItem.find('.mb-choices-list');
        addChoiceToContainer($choicesList);
    }

    function addChoiceToContainer($choicesContainer, choiceData = null) {
        const template = $('#choice-template').html();
        if (!template) return;
        const $choiceElement = $(template);
        
        if (choiceData) {
            $choiceElement.find('input[name="choice_label[]"]').val(choiceData.label || '');
            $choiceElement.find('input[name="choice_value[]"]').val(choiceData.value || '');
            $choiceElement.find('input[name="choice_price[]"]').val(choiceData.price || '');
        }
        $choicesContainer.append($choiceElement);
        initializeChoicesSortableForElement($choicesContainer);
    }

    function removeChoice($choiceItem) {
        $choiceItem.remove();
    }

    function addSqmRange($optionItem, rangeData = null) {
        const $sqmRangesList = $optionItem.find('.mb-sqm-ranges-list');
        addSqmRangeToContainer($sqmRangesList, rangeData);
    }

    function addSqmRangeToContainer($sqmRangesList, rangeData = null) {
        const template = $('#sqm-range-item-template').html();
        if(!template) return;
        const $rangeElement = $(template);

        if (rangeData) {
            $rangeElement.find('input[name="sqm_range_from[]"]').val(rangeData.from_sqm);
            // Handle infinity for display
            const toValue = (rangeData.to_sqm === Infinity || String(rangeData.to_sqm).toLowerCase() === 'infinity' || rangeData.to_sqm === '∞') ? '∞' : rangeData.to_sqm;
            $rangeElement.find('input[name="sqm_range_to[]"]').val(toValue);
            $rangeElement.find('input[name="sqm_range_price[]"]').val(rangeData.price_per_sqm);
        }
        $sqmRangesList.append($rangeElement);
    }

    function removeSqmRange($rangeItem) {
        $rangeItem.remove();
    }

    function updateOptionTitle($nameInput) {
        const $optionItem = $nameInput.closest('.mb-option-item');
        const $titleElement = $optionItem.find('.mb-option-title');
        $titleElement.text($nameInput.val() || strings.newOption);
    }

    function validateSqmRanges($optionItem) {
        const $sqmRangesList = $optionItem.find('.mb-sqm-ranges-list');
        let isValid = true;
        let prevToSqm = -1;
        const ranges = [];

        $sqmRangesList.find('.mb-sqm-range-item').each(function(index) {
            const $row = $(this);
            const fromSqmStr = $row.find('input[name="sqm_range_from[]"]').val();
            const toSqmStr = $row.find('input[name="sqm_range_to[]"]').val();
            const pricePerSqmStr = $row.find('input[name="sqm_range_price[]"]').val();

            const fromSqm = parseFloat(fromSqmStr);
            const pricePerSqm = parseFloat(pricePerSqmStr);
            const isLastRange = index === $sqmRangesList.find('.mb-sqm-range-item').length - 1;
            let toSqm = (toSqmStr === '∞' || toSqmStr === '' && isLastRange) ? Infinity : parseFloat(toSqmStr);


            if (isNaN(fromSqm) || fromSqm < 0) {
                showAlert(`Range ${index + 1}: "From SQM" must be a non-negative number.`, 'error');
                isValid = false; return false;
            }
            if (isNaN(pricePerSqm) || pricePerSqm <= 0) {
                showAlert(`Range ${index + 1}: "Price per SQM" must be a positive number.`, 'error');
                isValid = false; return false;
            }
            if (toSqm !== Infinity && (isNaN(toSqm) || toSqm < 0)) {
                showAlert(`Range ${index + 1}: "To SQM" must be a non-negative number or ∞.`, 'error');
                isValid = false; return false;
            }
            if (toSqm !== Infinity && fromSqm >= toSqm) {
                showAlert(`Range ${index + 1}: "From SQM" must be less than "To SQM".`, 'error');
                isValid = false; return false;
            }
            if (isLastRange && toSqm !== Infinity) {
                 // Allow empty "To" for last range to mean infinity
                if (toSqmStr !== '' && toSqmStr !== '∞') {
                    showAlert(`Last range's "To SQM" must be empty or ∞ for infinity.`, 'error');
                    isValid = false; return false;
                }
                toSqm = Infinity; // Normalize for internal check
            }
            if (!isLastRange && toSqm === Infinity) {
                showAlert(`Only the last range's "To SQM" can be ∞.`, 'error');
                isValid = false; return false;
            }

            // Continuity and overlap (simplified for client-side, server will do more robust check)
            if (index > 0) {
                if (prevToSqm !== Infinity && fromSqm != (prevToSqm + 1)) {
                     // This check is strict for integer continuity.
                     // showAlert(`Range ${index + 1}: "From SQM" should be ${prevToSqm + 1} to be continuous.`, 'warning');
                     // isValid = false; return false; // Decide if this is a hard stop or warning
                }
                if (prevToSqm !== Infinity && fromSqm <= prevToSqm) {
                    showAlert(`Range ${index + 1}: "From SQM" overlaps with previous range.`, 'error');
                    isValid = false; return false;
                }
            }

            ranges.push({from_sqm: fromSqm, to_sqm: toSqm, price_per_sqm: pricePerSqm});
            prevToSqm = toSqm;
        });

        if (!isValid) return false; // Validation failed

        // Check if first range starts at 0 or 1 if there are any ranges
        if (ranges.length > 0 && ranges[0].from_sqm !== 0 && ranges[0].from_sqm !== 1) {
            // showAlert('The first SQM range should ideally start from 0 or 1.', 'warning');
            // This might be a soft warning, not a validation failure depending on requirements.
        }

        return ranges; // Return collected ranges if valid
    }


    function handleFormSubmit(e) {
        e.preventDefault();
        hideAlert(); // Clear previous alerts

        let allSqmOptionsValid = true;
        // Perform SQM validation before collecting all data
        $('.mb-option-item').each(function() {
            const $item = $(this);
            const $selectedType = $item.find('input[name*="[type]"]:checked');
            if ($selectedType.val() === 'sqm') {
                const ranges = validateSqmRanges($item);
                if (ranges === false) { // validateSqmRanges returns false on validation failure
                    allSqmOptionsValid = false;
                    return false; // Break .each loop
                }
            }
        });

        if (!allSqmOptionsValid) {
            // showAlert is called by validateSqmRanges
            return; // Stop submission
        }
        
        const $saveBtn = $('#save-btn');
        const $saveText = $('#save-text');
        const $saveSpinner = $('#save-spinner');
        const $saveIcon = $saveBtn.find('.mb-btn-icon');
        
        $saveBtn.prop('disabled', true);
        $saveText.text(isEditMode ? strings.updating : strings.creating);
        $saveSpinner.removeClass('mb-hidden');
        $saveIcon.hide();
        
        const formData = collectFormData();
        // console.log('Submitting form data:', formData);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_save_service',
                nonce: nonce,
                ...formData // Spread collected data
            },
            success: function(response) {
                // console.log('Server response:', response);
                if (response.success) {
                    showAlert(response.data.message, 'success');
                    setTimeout(function() {
                        if (!isEditMode && response.data.service_id) {
                            let currentUrl = new URL(window.location.href);
                            currentUrl.searchParams.set('service_id', response.data.service_id);
                            window.location.href = currentUrl.toString();
                        } else {
                            window.location.href = '<?php echo esc_url(admin_url("admin.php?page=mobooking-services")); ?>';
                        }
                    }, 1500);
                } else {
                    showAlert(response.data?.message || strings.errorGeneric, 'error');
                }
            },
            error: function(xhr, status, error) {
                // console.error('AJAX Error:', {xhr, status, error, responseText: xhr.responseText});
                let errorMessage = strings.networkError;
                if (xhr.responseText) {
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        errorMessage = errorData.data?.message || errorMessage;
                    } catch (e) { /* Do nothing, use default */ }
                }
                showAlert(errorMessage, 'error');
            },
            complete: function() {
                $saveBtn.prop('disabled', false);
                $saveText.text(isEditMode ? strings.updateService : strings.createService);
                $saveSpinner.addClass('mb-hidden');
                $saveIcon.show();
            }
        });
    }


    function collectFormData() {
        const $form = $('#mobooking-service-form');
        const serviceData = {}; // Changed name from 'data' to 'serviceData' to avoid conflict
        
        // Collect basic service data (non-option fields)
        $form.find('input[type="text"], input[type="number"], input[type="hidden"], textarea, select').not('[name*="options["], [name*="choice_"], [name*="sqm_range_"]').each(function() {
            const $input = $(this);
            const name = $input.attr('name');
            if (name) {
                 // Check if it's the status toggle
                if (name === 'status' && $input.attr('type') === 'checkbox') {
                    serviceData[name] = $input.is(':checked') ? 'active' : 'inactive';
                } else if ($input.attr('type') !== 'radio' || $input.is(':checked')) {
                    serviceData[name] = $input.val();
                }
            }
        });
        // Ensure status from the actual toggle if it wasn't caught above
        if (typeof serviceData.status === 'undefined') {
             serviceData.status = $('#service-status').is(':checked') ? 'active' : 'inactive';
        }

        const options = [];
        $('.mb-option-item').each(function(index) {
            const $item = $(this);
            const optionType = $item.find('input[name*="[type]"]:checked').val();
            const currentOption = {
                option_id: $item.find('input[name*="[option_id]"]').val() || '',
                name: $item.find('input[name*="[name]"]').val() || '',
                description: $item.find('textarea[name*="[description]"]').val() || '',
                is_required: $item.find('input[name*="[is_required]"]').is(':checked') ? 1 : 0,
                sort_order: index + 1,
                type: optionType
            };

            if (optionType === 'sqm') {
                const ranges = [];
                $item.find('.mb-sqm-range-item').each(function() {
                    const $row = $(this);
                    const fromStr = $row.find('input[name="sqm_range_from[]"]').val();
                    const toStr = $row.find('input[name="sqm_range_to[]"]').val();
                    const priceStr = $row.find('input[name="sqm_range_price[]"]').val();

                    // Convert to appropriate types, handle '∞' for 'to_sqm'
                    ranges.push({
                        from_sqm: parseFloat(fromStr),
                        to_sqm: (toStr === '∞' || toStr === '') ? 'infinity' : parseFloat(toStr), // Server will handle 'infinity' string
                        price_per_sqm: parseFloat(priceStr)
                    });
                });
                currentOption.option_values = JSON.stringify(ranges);
                // For SQM, price_impact_type and price_impact_value are typically null
                currentOption.price_impact_type = null;
                currentOption.price_impact_value = null;
            } else if (optionType === 'select' || optionType === 'radio') {
                const choices = [];
                $item.find('.mb-choice-item').each(function(choiceIndex) {
                    const $choiceItem = $(this);
                    choices.push({
                        label: $choiceItem.find('input[name="choice_label[]"]').val(),
                        value: $choiceItem.find('input[name="choice_value[]"]').val(),
                        price: $choiceItem.find('input[name="choice_price[]"]').val() || 0,
                        sort_order: choiceIndex + 1
                    });
                });
                currentOption.option_values = JSON.stringify(choices);
                currentOption.price_impact_type = $item.find('input[name*="[price_impact_type]"]:checked').val() || '';
                currentOption.price_impact_value = $item.find('input[name*="[price_impact_value]"]').val() || '';
            } else {
                // For other types like text, number, checkbox, quantity, textarea
                currentOption.option_values = null; // Or specific value if needed, e.g. for Quantity default value
                currentOption.price_impact_type = $item.find('input[name*="[price_impact_type]"]:checked').val() || '';
                currentOption.price_impact_value = $item.find('input[name*="[price_impact_value]"]').val() || '';
            }
            options.push(currentOption);
        });
        
        serviceData.service_options = JSON.stringify(options);
        
        if (isEditMode && serviceId) {
            serviceData.service_id = serviceId;
        }
        
        // console.log('Collected form data for submission:', serviceData);
        return serviceData;
    }

    function showAlert(message, type = 'info') {
        window.showAlert(message, type);
    }

    function hideAlert() {
        // This is now a no-op as the global toast system handles its own lifecycle.
        // Kept for backward compatibility if any code calls it directly.
    }

    // Image handling
    function handleImageUpload(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Validate file type
        if (!file.type.startsWith('image/')) {
            showAlert(strings.invalidImageFile, 'error');
            return;
        }
        
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            showAlert(strings.imageTooLarge, 'error');
            return;
        }
        
        // Create preview
        const reader = new FileReader();
        reader.onload = function(e) {
            setImagePreview(e.target.result);
        };
        reader.readAsDataURL(file);
        
        // uploadImageToServer(file); // This was the TODO

        const formData = new FormData();
        formData.append('service_image', file);
        formData.append('action', 'mobooking_upload_service_image');
        formData.append('nonce', nonce); // Ensure 'nonce' is available in this scope (it is globally)
        // Optionally add service_id if needed by backend for naming, though backend handles uniqueness.
        // if (isEditMode && serviceId) { formData.append('service_id', serviceId); }

        const $preview = $('#image-preview');
        const originalPreview = $preview.html(); // Save original for potential restore on error
        $preview.html('<div class="mb-spinner" style="margin: auto; width: 32px; height: 32px;"></div>'); // Show spinner

        $.ajax({
            url: ajaxUrl, // Ensure 'ajaxUrl' is available (it is globally)
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success && response.data.image_url) {
                    setImagePreview(response.data.image_url); // This updates the preview and hidden input
                    showAlert(response.data.message || strings.imageUploadedSuccess || 'Image uploaded successfully.', 'success');
                } else {
                    showAlert(response.data?.message || strings.imageUploadFailed || 'Failed to upload image.', 'error');
                    $preview.html(originalPreview); // Restore original preview
                }
            },
            error: function(xhr) {
                let errorMsg = strings.networkError || 'Network error during image upload.';
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMsg = xhr.responseJSON.data.message;
                }
                showAlert(errorMsg, 'error');
                $preview.html(originalPreview); // Restore original preview
            },
            complete: function() {
                // Clear the file input so the same file can be selected again if needed
                $('#image-upload').val('');
            }
        });
    }

    function setImagePreview(imageUrl) {
        const $preview = $('#image-preview');
        const $imageUrlInput = $('#image-url');
        const $removeBtn = $('#remove-image-btn');
        
        if (imageUrl && imageUrl.trim() !== '') {
            $preview.html('<img src="' + encodeURI(imageUrl) + '" alt="<?php esc_attr_e('Service Image', 'mobooking'); ?>">');
            $imageUrlInput.val(imageUrl);
            $removeBtn.removeClass('mb-hidden');
        } else {
            // Show placeholder if imageUrl is empty
            $preview.html(
                '<div class="mb-image-placeholder">' +
                    '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">' +
                        '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>' +
                        '<circle cx="9" cy="9" r="2"/>' +
                        '<path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>' +
                    '</svg>' +
                    '<div class="mb-mt-2"><?php esc_html_e('Click to upload', 'mobooking'); ?></div>' +
                '</div>'
            );
            $imageUrlInput.val('');
            $removeBtn.addClass('mb-hidden');
        }
    }

    function removeImage() {
        const $preview = $('#image-preview');
        const $imageUrlInput = $('#image-url');
        const $removeBtn = $('#remove-image-btn');
        
        $preview.html(
            '<div class="mb-image-placeholder">' +
                '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">' +
                    '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>' +
                    '<circle cx="9" cy="9" r="2"/>' +
                    '<path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>' +
                '</svg>' +
                '<div class="mb-mt-2"><?php esc_html_e('Click to upload', 'mobooking'); ?></div>' +
            '</div>'
        );
        $imageUrlInput.val('');
        $removeBtn.addClass('mb-hidden');
    }

    // Icon handling
    function openPresetIconSelector() {
        const $modal = $('#icon-selector-modal');
        const $grid = $('#preset-icons-grid');
        $grid.html('<p>' + (strings.loading || 'Loading...') + '</p>'); // Add a loading string
        $modal.removeClass('mb-hidden');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mobooking_get_preset_icons',
                nonce: nonce
            },
            success: function(response) {
                if (response.success && response.data.icons) {
                    $grid.empty();
                    for (const key in response.data.icons) {
                        const iconSvg = response.data.icons[key];
                        const $iconItem = $(
                            '<div class="preset-icon-item mb-p-2 mb-flex mb-items-center mb-justify-center mb-rounded" style="cursor: pointer; border: 1px solid var(--mb-border);">' +
                                iconSvg +
                            '</div>'
                        );
                        $iconItem.attr('data-icon-key', key);
                        $iconItem.attr('title', key);
                        $iconItem.hover(function() { $(this).css('background-color', 'var(--mb-muted)'); }, function() { $(this).css('background-color', 'transparent'); });
                        $grid.append($iconItem);
                    }
                } else {
                    $grid.html('<p>' + (response.data?.message || strings.errorGeneric || 'Error loading icons.') + '</p>');
                }
            },
            error: function() {
                $grid.html('<p>' + (strings.networkError || 'Network error loading icons.') + '</p>');
            }
        });
    }

    function closePresetIconSelector() {
        $('#icon-selector-modal').addClass('mb-hidden');
    }

    function selectPresetIcon(iconKey, iconSvg) {
        setIconPreview(iconSvg, `preset:${iconKey}`);
        closePresetIconSelector();
    }

    function handleCustomIconUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (file.type !== 'image/svg+xml') {
            showAlert(strings.invalidSvgFile || 'Please select a valid SVG file.', 'error');
            return;
        }
        if (file.size > 100 * 1024) { // 100KB limit for SVGs
            showAlert(strings.svgTooLarge || 'SVG file size must be less than 100KB.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('service_icon_svg', file);
        formData.append('action', 'mobooking_upload_service_icon');
        formData.append('nonce', nonce);
        // Optionally add service_id if in edit mode and needed for filename, though backend uses uniqid if not
        if (isEditMode && serviceId) {
            formData.append('service_id', serviceId);
        }

        // Show some loading state on the icon preview
        const $preview = $('#icon-preview');
        const originalPreview = $preview.html();
        $preview.html('<div class="mb-spinner" style="margin: auto;"></div>');


        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success && response.data.icon_url) {
                    // For SVG, we might want to fetch and embed the SVG for preview
                    // or just use a generic icon placeholder if the URL is opaque
                    // For now, let's assume the backend sanitized it and we can display it if it's small
                    // Fetching and displaying remote SVG can be complex due to security (scripts etc)
                    // The backend returns a URL. We should store this URL.
                    // The setIconPreview function will now handle URL display correctly.
                    // Pass the URL as the iconInputValue. The first arg (iconHtmlOrSvg) is not strictly needed when type is URL.
                    setIconPreview(null, response.data.icon_url);
                    showAlert(response.data.message || 'Icon uploaded successfully.', 'success');
                } else {
                    showAlert(response.data?.message || strings.errorGeneric || 'Failed to upload icon.', 'error');
                    $preview.html(originalPreview); // Restore original preview on error
                }
            },
            error: function() {
                showAlert(strings.networkError || 'Network error uploading icon.', 'error');
                $preview.html(originalPreview); // Restore original preview on error
            },
            complete: function() {
                // Clear the file input so the same file can be selected again if needed
                $('#custom-icon-upload').val('');
            }
        });
    }


    function setIconPreview(iconHtmlOrSvg, iconInputValue) { // Removed isUrl parameter
        const $preview = $('#icon-preview');
        const $iconInput = $('#icon-value');
        const $removeBtn = $('#remove-icon-btn');

        if (iconInputValue && iconInputValue.startsWith('preset:')) {
            // It's a preset icon, iconHtmlOrSvg is the actual SVG content
            $preview.html(iconHtmlOrSvg);
        } else if (iconInputValue) {
            // It's a URL for a custom uploaded SVG
            $preview.html('<img src="' + encodeURI(iconInputValue) + '" alt="Service Icon" style="width: 48px; height: 48px; object-fit: contain;">');
        } else {
            // No icon selected or value cleared
            $preview.html(
                '<div class="mb-image-placeholder">' +
                    '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">' +
                        '<circle cx="12" cy="12" r="10"/>' +
                        '<path d="m9 12 2 2 4-4"/>' +
                    '</svg>' +
                    '<div class="mb-mt-2"><?php esc_html_e('Select or Upload Icon', 'mobooking'); ?></div>' +
                '</div>'
            );
        }
        
        $iconInput.val(iconInputValue);

        if (iconInputValue) {
            $removeBtn.removeClass('mb-hidden');
        } else {
            $removeBtn.addClass('mb-hidden');
        }
    }

    function removeIcon() {
        const $preview = $('#icon-preview');
        const $iconInput = $('#icon-value');
        const $removeBtn = $('#remove-icon-btn');
        
        $preview.html(
            '<div class="mb-image-placeholder">' +
                '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">' +
                    '<circle cx="12" cy="12" r="10"/>' +
                    '<path d="m9 12 2 2 4-4"/>' +
                '</svg>' +
                '<div class="mb-mt-2"><?php esc_html_e('Select or Upload Icon', 'mobooking'); ?></div>' +
            '</div>'
        );
        $iconInput.val('');
        $removeBtn.addClass('mb-hidden');
    }

    // Make functions available globally for debugging
    window.moBookingServiceEdit = {
        addNewOption: addNewOption,
        collectFormData: collectFormData,
        showAlert: showAlert,
        hideAlert: hideAlert,
        updateOptionSortOrders: updateOptionSortOrders,
        initializeSortable: initializeSortable
    };
});
</script>

