<?php
/**
 * Template Name: Public Booking Form - Complete Redesigned
 * Description: Modern, responsive booking form with all original steps
 * @package MoBooking
 */

if (!defined('ABSPATH')) exit;

// Get WordPress header
get_header('booking');

// Initialize variables and settings
$tenant_id = get_query_var('mobooking_tenant_id_on_page', 0);
if (empty($tenant_id) && !empty($_GET['tid'])) {
    $tenant_id = intval($_GET['tid']);
}

// Get current user if logged in business owner
if (empty($tenant_id) && is_user_logged_in()) {
    $current_user = wp_get_current_user();
    if (in_array('mobooking_business_owner', $current_user->roles)) {
        $tenant_id = $current_user->ID;
    }
}

// Get settings
$settings_manager = new \MoBooking\Classes\Settings();
$bf_settings = $settings_manager->get_booking_form_settings($tenant_id);
$biz_settings = $settings_manager->get_business_settings($tenant_id);

// Form configuration
$form_config = [
    'tenant_id' => $tenant_id,
    'header_text' => $bf_settings['bf_header_text'] ?? __('Book Our Services', 'mobooking'),
    'show_progress_bar' => ($bf_settings['bf_show_progress_bar'] ?? '1') === '1',
    'show_pricing' => ($bf_settings['bf_show_pricing'] ?? '1') === '1',
    'allow_discount_codes' => ($bf_settings['bf_allow_discount_codes'] ?? '1') === '1',
    'theme_color' => $bf_settings['bf_theme_color'] ?? '#3b82f6',
    'currency_symbol' => $biz_settings['biz_currency_symbol'] ?? '$',
    'currency_position' => $biz_settings['biz_currency_position'] ?? 'before',
    'business_name' => $biz_settings['biz_business_name'] ?? get_bloginfo('name'),
    'business_phone' => $biz_settings['biz_phone'] ?? '',
    'enable_area_check' => ($bf_settings['bf_enable_location_check'] ?? '1') === '1',
    'enable_pet_information' => ($bf_settings['bf_enable_pet_information'] ?? '0') === '1',
    'enable_service_frequency' => ($bf_settings['bf_enable_service_frequency'] ?? '0') === '1',
    'enable_datetime_selection' => ($bf_settings['bf_enable_datetime_selection'] ?? '1') === '1',
    'enable_property_access' => ($bf_settings['bf_enable_property_access'] ?? '1') === '1',
    'success_message' => $bf_settings['bf_confirmation_message'] ?? __('Thank you for your booking! We will contact you shortly to confirm your appointment.', 'mobooking')
];

// Calculate total steps dynamically
$total_steps = 3; // Service selection, Options, Success (always present)
if ($form_config['enable_area_check']) $total_steps++;
if ($form_config['enable_pet_information']) $total_steps++;
if ($form_config['enable_service_frequency']) $total_steps++;
if ($form_config['enable_datetime_selection']) $total_steps++;
if ($form_config['enable_property_access']) $total_steps++;

// Localization for JavaScript
$js_config = [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mobooking_booking_form_nonce'),
    'tenant_id' => $tenant_id,
    'currency_symbol' => $form_config['currency_symbol'],
    'currency_position' => $form_config['currency_position'],
    'total_steps' => $total_steps,
    'enable_area_check' => $form_config['enable_area_check'],
    'enable_pet_information' => $form_config['enable_pet_information'],
    'enable_service_frequency' => $form_config['enable_service_frequency'],
    'enable_datetime_selection' => $form_config['enable_datetime_selection'],
    'enable_property_access' => $form_config['enable_property_access'],
    'messages' => [
        'loading' => __('Loading...', 'mobooking'),
        'select_service' => __('Please select a service.', 'mobooking'),
        'select_date' => __('Please select a date.', 'mobooking'),
        'select_time' => __('Please select a time slot.', 'mobooking'),
        'name_required' => __('Please enter your name.', 'mobooking'),
        'email_required' => __('Please enter a valid email address.', 'mobooking'),
        'phone_required' => __('Please enter your phone number.', 'mobooking'),
        'address_required' => __('Please enter the service address.', 'mobooking'),
        'submitting_booking' => __('Submitting booking...', 'mobooking'),
        'booking_success' => __('Booking submitted successfully!', 'mobooking'),
        'booking_error' => __('There was an error submitting your booking. Please try again.', 'mobooking'),
    ]
];
?>

<style>
/* CSS Variables - Dashboard Style */
:root {
    --mobk-background: hsl(0 0% 100%);
    --mobk-foreground: hsl(222.2 84% 4.9%);
    --mobk-card: hsl(0 0% 100%);
    --mobk-card-foreground: hsl(222.2 84% 4.9%);
    --mobk-primary: <?php echo esc_attr($form_config['theme_color']); ?>;
    --mobk-primary-foreground: hsl(210 40% 98%);
    --mobk-secondary: hsl(210 40% 96.1%);
    --mobk-secondary-foreground: hsl(222.2 84% 4.9%);
    --mobk-muted: hsl(210 40% 96.1%);
    --mobk-muted-foreground: hsl(215.4 16.3% 46.9%);
    --mobk-border: hsl(214.3 31.8% 91.4%);
    --mobk-input: hsl(214.3 31.8% 91.4%);
    --mobk-ring: <?php echo esc_attr($form_config['theme_color']); ?>;
    --mobk-radius: <?php echo esc_attr($bf_settings['bf_border_radius'] ?? '8'); ?>px;
    --mobk-shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --mobk-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    --mobk-shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --mobk-font-sans: <?php echo esc_attr($bf_settings['bf_font_family'] ?? 'system-ui'); ?>, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

/* Reset and Base */
* {
    box-sizing: border-box;
}

body {
    font-family: var(--mobk-font-sans);
    background-color: var(--mobk-background);
    color: var(--mobk-foreground);
    line-height: 1.6;
    margin: 0;
    padding: 0;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Main Container */
.mobooking-container {
    min-height: 100vh;
    background: linear-gradient(135deg, hsl(from var(--mobk-primary) h s l / 0.05) 0%, var(--mobk-muted) 100%);
    padding: 1rem;
}

.mobooking-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    min-height: calc(100vh - 2rem);
}

/* Main Content with Sidebar Inside */
.mobooking-main {
    background: var(--mobk-card);
    border: 1px solid var(--mobk-border);
    border-radius: var(--mobk-radius);
    box-shadow: var(--mobk-shadow-lg);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Header */
.mobooking-header {
    background: linear-gradient(135deg, var(--mobk-primary), hsl(from var(--mobk-primary) h s calc(l - 5%)));
    color: var(--mobk-primary-foreground);
    padding: 2rem;
    text-align: center;
}

.mobooking-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    letter-spacing: -0.025em;
}

.mobooking-header p {
    opacity: 0.9;
    font-size: 1rem;
    margin: 0;
}

.mobooking-business-info {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid hsl(from var(--mobk-primary-foreground) h s l / 0.2);
}

.mobooking-business-info h2 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
}

.mobooking-business-phone {
    margin: 0;
    opacity: 0.9;
}

.mobooking-business-phone a {
    color: inherit;
    text-decoration: none;
    transition: opacity 0.2s;
}

.mobooking-business-phone a:hover {
    opacity: 0.8;
}

/* Progress Bar */
.mobooking-progress {
    background: var(--mobk-muted);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--mobk-border);
}

.mobooking-progress-steps {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 1rem;
    position: relative;
    flex-wrap: wrap;
}

.mobooking-step-indicator {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: var(--mobk-card);
    border: 2px solid var(--mobk-border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    transition: all 0.2s ease;
    position: relative;
    z-index: 2;
}

.mobooking-step-indicator.active {
    background: var(--mobk-primary);
    border-color: var(--mobk-primary);
    color: var(--mobk-primary-foreground);
}

.mobooking-step-indicator.completed {
    background: hsl(142.1 76.2% 36.3%);
    border-color: hsl(142.1 76.2% 36.3%);
    color: white;
}

.mobooking-progress-bar {
    width: 100%;
    height: 4px;
    background: var(--mobk-border);
    border-radius: 2px;
    overflow: hidden;
}

.mobooking-progress-fill {
    height: 100%;
    background: var(--mobk-primary);
    transition: width 0.3s ease;
    width: 0%;
}

.mobooking-progress-text {
    text-align: center;
    font-size: 0.875rem;
    color: var(--mobk-muted-foreground);
    margin-top: 0.5rem;
}

/* Content Area with Sidebar */
.mobooking-content-wrapper {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
    padding: 2rem;
    flex: 1;
}

@media (max-width: 1024px) {
    .mobooking-content-wrapper {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .mobooking-sidebar {
        order: -1;
    }
}

@media (max-width: 768px) {
    .mobooking-content-wrapper {
        padding: 1rem;
        gap: 1rem;
    }
    
    .mobooking-container {
        padding: 0.5rem;
    }
}

/* Step Content */
.mobooking-steps {
    position: relative;
    overflow: hidden;
    min-height: 400px;
}

.mobooking-step {
    display: none;
    opacity: 0;
    transform: translateX(20px);
    transition: all 0.3s ease;
}

.mobooking-step.active {
    display: block;
    opacity: 1;
    transform: translateX(0);
}

.mobooking-step-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--mobk-card-foreground);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mobooking-step-description {
    color: var(--mobk-muted-foreground);
    margin-bottom: 2rem;
    font-size: 0.875rem;
}

/* Form Elements */
.mobooking-form-group {
    margin-bottom: 1.5rem;
}

.mobooking-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 640px) {
    .mobooking-form-row {
        grid-template-columns: 1fr;
    }
}

.mobooking-label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--mobk-card-foreground);
    font-size: 0.875rem;
}

.mobooking-required {
    color: hsl(0 84.2% 60.2%);
}

.mobooking-input,
.mobooking-textarea,
.mobooking-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--mobk-input);
    border-radius: calc(var(--mobk-radius) - 2px);
    font-size: 0.875rem;
    transition: all 0.2s ease;
    background: var(--mobk-card);
    color: var(--mobk-card-foreground);
    font-family: inherit;
}

.mobooking-input:focus,
.mobooking-textarea:focus,
.mobooking-select:focus {
    outline: none;
    border-color: var(--mobk-ring);
    box-shadow: 0 0 0 3px hsl(from var(--mobk-ring) h s l / 0.1);
}

.mobooking-textarea {
    min-height: 120px;
    resize: vertical;
}

/* Service Cards */
.mobooking-services-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

.mobooking-service-card {
    border: 2px solid var(--mobk-border);
    border-radius: var(--mobk-radius);
    padding: 1.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    background: var(--mobk-card);
}

.mobooking-service-card:hover {
    border-color: var(--mobk-primary);
    box-shadow: var(--mobk-shadow);
}

.mobooking-service-card.selected {
    border-color: var(--mobk-primary);
    background: hsl(from var(--mobk-primary) h s l / 0.05);
}

.mobooking-service-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.mobooking-service-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--mobk-radius);
    background: hsl(from var(--mobk-primary) h s l / 0.1);
    color: var(--mobk-primary);
    font-size: 1.5rem;
}

.mobooking-service-info h3 {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0 0 0.25rem 0;
    color: var(--mobk-card-foreground);
}

.mobooking-service-price {
    font-size: 1rem;
    font-weight: 500;
    color: var(--mobk-primary);
}

.mobooking-service-description {
    color: var(--mobk-muted-foreground);
    font-size: 0.875rem;
    line-height: 1.5;
}

/* Options */
.mobooking-options-grid {
    display: grid;
    gap: 1rem;
}

.mobooking-option-group {
    border: 1px solid var(--mobk-border);
    border-radius: var(--mobk-radius);
    padding: 1rem;
    background: var(--mobk-card);
}

.mobooking-option-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    display: block;
    color: var(--mobk-card-foreground);
}

/* Time Slots */
.mobooking-time-slots {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.75rem;
    margin-top: 1rem;
}

.mobooking-time-slot {
    padding: 0.75rem;
    border: 2px solid var(--mobk-border);
    border-radius: var(--mobk-radius);
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: var(--mobk-card);
    font-size: 0.875rem;
    font-weight: 500;
}

.mobooking-time-slot:hover {
    border-color: var(--mobk-primary);
}

.mobooking-time-slot.selected {
    background: var(--mobk-primary);
    border-color: var(--mobk-primary);
    color: var(--mobk-primary-foreground);
}

.mobooking-time-slot.unavailable {
    opacity: 0.5;
    cursor: not-allowed;
    background: var(--mobk-muted);
}

/* Pet Information */
.mobooking-pet-grid {
    display: grid;
    gap: 1rem;
}

.mobooking-pet-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--mobk-border);
    border-radius: var(--mobk-radius);
    background: var(--mobk-card);
}

.mobooking-pet-info {
    flex: 1;
}

.mobooking-pet-info h4 {
    margin: 0 0 0.25rem 0;
    font-weight: 500;
    color: var(--mobk-card-foreground);
}

.mobooking-pet-info p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--mobk-muted-foreground);
}

/* Frequency Options */
.mobooking-frequency-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.mobooking-frequency-card {
    border: 2px solid var(--mobk-border);
    border-radius: var(--mobk-radius);
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: var(--mobk-card);
}

.mobooking-frequency-card:hover {
    border-color: var(--mobk-primary);
}

.mobooking-frequency-card.selected {
    border-color: var(--mobk-primary);
    background: hsl(from var(--mobk-primary) h s l / 0.05);
}

.mobooking-frequency-card h4 {
    margin: 0 0 0.5rem 0;
    font-weight: 600;
    color: var(--mobk-card-foreground);
}

.mobooking-frequency-card p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--mobk-muted-foreground);
}

/* Buttons */
.mobooking-form-actions {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--mobk-border);
}

@media (max-width: 640px) {
    .mobooking-form-actions {
        flex-direction: column;
    }
}

.mobooking-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: calc(var(--mobk-radius) - 2px);
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    min-width: 120px;
    font-family: inherit;
}

.mobooking-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.mobooking-btn-primary {
    background: var(--mobk-primary);
    color: var(--mobk-primary-foreground);
}

.mobooking-btn-primary:hover:not(:disabled) {
    background: hsl(from var(--mobk-primary) h s calc(l - 5%));
}

.mobooking-btn-secondary {
    background: var(--mobk-secondary);
    color: var(--mobk-secondary-foreground);
}

.mobooking-btn-secondary:hover:not(:disabled) {
    background: hsl(from var(--mobk-secondary) h s calc(l - 5%));
}

/* Sidebar */
.mobooking-sidebar {
    background: var(--mobk-muted);
    border: 1px solid var(--mobk-border);
    border-radius: var(--mobk-radius);
    padding: 1.5rem;
    align-self: flex-start;
    position: sticky;
    top: 1rem;
    max-height: calc(100vh - 200px);
    overflow-y: auto;
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.mobooking-sidebar.hidden {
    opacity: 0;
    transform: translateX(20px);
    pointer-events: none;
}

.mobooking-sidebar-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--mobk-border);
    color: var(--mobk-card-foreground);
}

.mobooking-sidebar-content {
    font-size: 0.875rem;
    color: var(--mobk-muted-foreground);
}

.mobooking-sidebar-content p {
    margin-bottom: 0.5rem;
}

.mobooking-sidebar-empty {
    text-align: center;
    color: var(--mobk-muted-foreground);
    font-style: italic;
    padding: 2rem 1rem;
}

/* Pricing Section */
.mobooking-pricing-section {
    border-top: 1px solid var(--mobk-border);
    padding-top: 1rem;
    margin-top: 1rem;
}

.mobooking-price-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    color: var(--mobk-muted-foreground);
}

.mobooking-price-item:last-child {
    margin-bottom: 0;
}

.mobooking-price-item span:last-child {
    font-weight: 500;
    color: var(--mobk-card-foreground);
}

.mobooking-price-total {
    font-size: 1rem;
    font-weight: 600;
    color: var(--mobk-card-foreground);
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--mobk-border);
}

.mobooking-price-total span:last-child {
    font-size: 1.125rem;
    color: var(--mobk-primary);
}

/* Success State */
.mobooking-success {
    text-align: center;
    padding: 2rem;
}

.mobooking-success-icon {
    font-size: 4rem;
    color: hsl(142.1 76.2% 36.3%);
    margin-bottom: 1rem;
}

.mobooking-success h2 {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--mobk-card-foreground);
}

.mobooking-success p {
    color: var(--mobk-muted-foreground);
    margin-bottom: 2rem;
}

/* Feedback Messages */
.mobooking-feedback {
    padding: 0.75rem 1rem;
    border-radius: calc(var(--mobk-radius) - 2px);
    margin-top: 1rem;
    font-size: 0.875rem;
    display: none;
}

.mobooking-feedback.error {
    background: hsl(0 84.2% 60.2% / 0.1);
    border: 1px solid hsl(0 84.2% 60.2% / 0.2);
    color: hsl(0 84.2% 30%);
}

.mobooking-feedback.success {
    background: hsl(142.1 76.2% 36.3% / 0.1);
    border: 1px solid hsl(142.1 76.2% 36.3% / 0.2);
    color: hsl(142.1 76.2% 25%);
}

/* Loading States */
.mobooking-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--mobk-muted-foreground);
}

.mobooking-spinner {
    width: 1rem;
    height: 1rem;
    border: 2px solid var(--mobk-border);
    border-top-color: var(--mobk-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 0.5rem;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Hidden Class */
.hidden {
    display: none !important;
}

/* Custom CSS from settings */
<?php if (!empty($bf_settings['bf_custom_css'])): ?>
<?php echo wp_kses_post($bf_settings['bf_custom_css']); ?>
<?php endif; ?>
</style>

<div class="mobooking-container">
    <div class="mobooking-wrapper">
        <div class="mobooking-main">
            <!-- Header -->
            <div class="mobooking-header">
                <h1><?php echo esc_html($form_config['header_text']); ?></h1>
                <p><?php _e('Complete the steps below to schedule your service', 'mobooking'); ?></p>
                
                <?php if (!empty($form_config['business_name']) || !empty($form_config['business_phone'])): ?>
                <div class="mobooking-business-info">
                    <?php if (!empty($form_config['business_name'])): ?>
                    <h2><?php echo esc_html($form_config['business_name']); ?></h2>
                    <?php endif; ?>
                    
                    <?php if (!empty($form_config['business_phone'])): ?>
                    <p class="mobooking-business-phone">
                        <a href="tel:<?php echo esc_attr($form_config['business_phone']); ?>">
                            <i class="fas fa-phone"></i> <?php echo esc_html($form_config['business_phone']); ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Progress Bar -->
            <?php if ($form_config['show_progress_bar']): ?>
            <div class="mobooking-progress">
                <div class="mobooking-progress-steps" id="mobooking-progress-steps">
                    <!-- Steps will be generated by JavaScript based on enabled features -->
                </div>
                <div class="mobooking-progress-bar">
                    <div class="mobooking-progress-fill" id="mobooking-progress-fill"></div>
                </div>
                <div class="mobooking-progress-text" id="mobooking-progress-text">
                    <?php _e('Step 1', 'mobooking'); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Content Wrapper with Sidebar -->
            <div class="mobooking-content-wrapper">
                <!-- Main Steps -->
                <div class="mobooking-steps">
                    <!-- Step 1: Area Check (if enabled) -->
                    <?php if ($form_config['enable_area_check']): ?>
                    <div class="mobooking-step active" id="mobooking-step-1">
                        <h2 class="mobooking-step-title">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo esc_html($bf_settings['bf_step_1_title'] ?? __('Service Area Check', 'mobooking')); ?>
                        </h2>
                        <p class="mobooking-step-description">
                            <?php _e('Enter your location to check if we provide services in your area.', 'mobooking'); ?>
                        </p>
                        
                        <form id="mobooking-location-form">
                            <div class="mobooking-form-group">
                                <label for="mobooking-location" class="mobooking-label">
                                    <?php _e('Your Location', 'mobooking'); ?> <span class="mobooking-required">*</span>
                                </label>
                                <input type="text" id="mobooking-location" class="mobooking-input" 
                                       placeholder="<?php esc_attr_e('Enter your address or postal code', 'mobooking'); ?>" required>
                            </div>
                            
                            <div id="mobooking-location-feedback" class="mobooking-feedback"></div>
                            
                            <div class="mobooking-form-actions">
                                <div></div>
                                <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="checkServiceArea()">
                                    <?php _e('Check Area', 'mobooking'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Step: Service Selection -->
                    <div class="mobooking-step <?php echo !$form_config['enable_area_check'] ? 'active' : ''; ?>" id="mobooking-step-services">
                        <h2 class="mobooking-step-title">
                            <i class="fas fa-list"></i>
                            <?php _e('Select Service', 'mobooking'); ?>
                        </h2>
                        <p class="mobooking-step-description">
                            <?php _e('Choose the service you would like to book.', 'mobooking'); ?>
                        </p>
                        
                        <div id="mobooking-services-container">
                            <div class="mobooking-loading">
                                <div class="mobooking-spinner"></div>
                                <?php _e('Loading services...', 'mobooking'); ?>
                            </div>
                        </div>
                        
                        <div id="mobooking-services-feedback" class="mobooking-feedback"></div>
                        
                        <div class="mobooking-form-actions">
                            <?php if ($form_config['enable_area_check']): ?>
                            <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="previousStep()">
                                <?php _e('Back', 'mobooking'); ?>
                            </button>
                            <?php else: ?>
                            <div></div>
                            <?php endif; ?>
                            <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="nextStep()" disabled>
                                <?php _e('Continue', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Step: Service Options -->
                    <div class="mobooking-step" id="mobooking-step-options">
                        <h2 class="mobooking-step-title">
                            <i class="fas fa-cog"></i>
                            <?php _e('Service Options', 'mobooking'); ?>
                        </h2>
                        <p class="mobooking-step-description">
                            <?php _e('Customize your service with additional options.', 'mobooking'); ?>
                        </p>
                        
                        <div id="mobooking-options-container">
                            <div class="mobooking-loading">
                                <div class="mobooking-spinner"></div>
                                <?php _e('Loading options...', 'mobooking'); ?>
                            </div>
                        </div>
                        
                        <div id="mobooking-options-feedback" class="mobooking-feedback"></div>
                        
                        <div class="mobooking-form-actions">
                            <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="previousStep()">
                                <?php _e('Back', 'mobooking'); ?>
                            </button>
                            <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="nextStep()">
                                <?php _e('Continue', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Step: Pet Information (if enabled) -->
                    <?php if ($form_config['enable_pet_information']): ?>
                    <div class="mobooking-step" id="mobooking-step-pets">
                        <h2 class="mobooking-step-title">
                            <i class="fas fa-paw"></i>
                            <?php echo esc_html($bf_settings['bf_step_pets_title'] ?? __('Pet Information', 'mobooking')); ?>
                        </h2>
                        <p class="mobooking-step-description">
                            <?php _e('Tell us about your pets so we can provide the best service.', 'mobooking'); ?>
                        </p>
                        
                        <div class="mobooking-form-group">
                            <label for="mobooking-pet-count" class="mobooking-label">
                                <?php _e('Number of Pets', 'mobooking'); ?>
                            </label>
                            <select id="mobooking-pet-count" class="mobooking-select" onchange="updatePetFields()">
                                <option value="0"><?php _e('No pets', 'mobooking'); ?></option>
                                <option value="1">1 <?php _e('pet', 'mobooking'); ?></option>
                                <option value="2">2 <?php _e('pets', 'mobooking'); ?></option>
                                <option value="3">3 <?php _e('pets', 'mobooking'); ?></option>
                                <option value="4">4 <?php _e('pets', 'mobooking'); ?></option>
                                <option value="5">5+ <?php _e('pets', 'mobooking'); ?></option>
                            </select>
                        </div>
                        
                        <div id="mobooking-pet-details" class="mobooking-pet-grid hidden">
                            <!-- Pet detail fields will be generated here -->
                        </div>
                        
                        <div class="mobooking-form-group">
                            <label for="mobooking-pet-notes" class="mobooking-label">
                                <?php _e('Special Pet Instructions', 'mobooking'); ?>
                            </label>
                            <textarea id="mobooking-pet-notes" class="mobooking-textarea" 
                                      placeholder="<?php esc_attr_e('Any special instructions about your pets? (temperament, health issues, etc.)', 'mobooking'); ?>"></textarea>
                        </div>
                        
                        <div id="mobooking-pets-feedback" class="mobooking-feedback"></div>
                        
                        <div class="mobooking-form-actions">
                            <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="previousStep()">
                                <?php _e('Back', 'mobooking'); ?>
                            </button>
                            <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="nextStep()">
                                <?php _e('Continue', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Step: Service Frequency (if enabled) -->
                    <?php if ($form_config['enable_service_frequency']): ?>
                    <div class="mobooking-step" id="mobooking-step-frequency">
                        <h2 class="mobooking-step-title">
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo esc_html($bf_settings['bf_step_frequency_title'] ?? __('Service Frequency', 'mobooking')); ?>
                        </h2>
                        <p class="mobooking-step-description">
                            <?php _e('How often would you like this service?', 'mobooking'); ?>
                        </p>
                        
                        <div class="mobooking-frequency-grid">
                            <div class="mobooking-frequency-card" onclick="selectFrequency('one-time')" data-frequency="one-time">
                                <h4><?php _e('One-time', 'mobooking'); ?></h4>
                                <p><?php _e('Single service visit', 'mobooking'); ?></p>
                            </div>
                            <div class="mobooking-frequency-card" onclick="selectFrequency('weekly')" data-frequency="weekly">
                                <h4><?php _e('Weekly', 'mobooking'); ?></h4>
                                <p><?php _e('Every week', 'mobooking'); ?></p>
                            </div>
                            <div class="mobooking-frequency-card" onclick="selectFrequency('bi-weekly')" data-frequency="bi-weekly">
                                <h4><?php _e('Bi-weekly', 'mobooking'); ?></h4>
                                <p><?php _e('Every 2 weeks', 'mobooking'); ?></p>
                            </div>
                            <div class="mobooking-frequency-card" onclick="selectFrequency('monthly')" data-frequency="monthly">
                                <h4><?php _e('Monthly', 'mobooking'); ?></h4>
                                <p><?php _e('Once a month', 'mobooking'); ?></p>
                            </div>
                        </div>
                        
                        <div id="mobooking-frequency-feedback" class="mobooking-feedback"></div>
                        
                        <div class="mobooking-form-actions">
                            <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="previousStep()">
                                <?php _e('Back', 'mobooking'); ?>
                            </button>
                            <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="nextStep()">
                                <?php _e('Continue', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Step: Date & Time Selection (if enabled) -->
                    <?php if ($form_config['enable_datetime_selection']): ?>
                    <div class="mobooking-step" id="mobooking-step-datetime">
                        <h2 class="mobooking-step-title">
                            <i class="fas fa-calendar-check"></i>
                            <?php echo esc_html($bf_settings['bf_step_datetime_title'] ?? __('Select Date & Time', 'mobooking')); ?>
                        </h2>
                        <p class="mobooking-step-description">
                            <?php _e('Choose your preferred date and time for the service.', 'mobooking'); ?>
                        </p>
                        
                        <div class="mobooking-form-group">
                            <label for="mobooking-service-date" class="mobooking-label">
                                <?php _e('Preferred Date', 'mobooking'); ?> <span class="mobooking-required">*</span>
                            </label>
                            <input type="date" id="mobooking-service-date" class="mobooking-input" 
                                   min="<?php echo date('Y-m-d'); ?>" onchange="loadTimeSlots()" required>
                        </div>
                        
                        <div class="mobooking-form-group hidden" id="mobooking-time-slots-container">
                            <label class="mobooking-label">
                                <?php _e('Available Time Slots', 'mobooking'); ?> <span class="mobooking-required">*</span>
                            </label>
                            <div id="mobooking-time-slots" class="mobooking-time-slots">
                                <!-- Time slots will be populated here -->
                            </div>
                        </div>
                        
                        <div id="mobooking-datetime-feedback" class="mobooking-feedback"></div>
                        
                        <div class="mobooking-form-actions">
                            <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="previousStep()">
                                <?php _e('Back', 'mobooking'); ?>
                            </button>
                            <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="nextStep()">
                                <?php _e('Continue', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Step: Contact & Property Access -->
                    <?php if ($form_config['enable_property_access']): ?>
                    <div class="mobooking-step" id="mobooking-step-contact">
                        <h2 class="mobooking-step-title">
                            <i class="fas fa-user"></i>
                            <?php echo esc_html($bf_settings['bf_step_contact_title'] ?? __('Contact & Property Details', 'mobooking')); ?>
                        </h2>
                        <p class="mobooking-step-description">
                            <?php _e('Please provide your contact information and property access details.', 'mobooking'); ?>
                        </p>
                        
                        <form id="mobooking-contact-form">
                            <div class="mobooking-form-row">
                                <div class="mobooking-form-group">
                                    <label for="mobooking-name" class="mobooking-label">
                                        <?php _e('Full Name', 'mobooking'); ?> <span class="mobooking-required">*</span>
                                    </label>
                                    <input type="text" id="mobooking-name" class="mobooking-input" 
                                           placeholder="<?php esc_attr_e('Your full name', 'mobooking'); ?>" required>
                                </div>
                                
                                <div class="mobooking-form-group">
                                    <label for="mobooking-email" class="mobooking-label">
                                        <?php _e('Email Address', 'mobooking'); ?> <span class="mobooking-required">*</span>
                                    </label>
                                    <input type="email" id="mobooking-email" class="mobooking-input" 
                                           placeholder="<?php esc_attr_e('your@email.com', 'mobooking'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mobooking-form-row">
                                <div class="mobooking-form-group">
                                    <label for="mobooking-phone" class="mobooking-label">
                                        <?php _e('Phone Number', 'mobooking'); ?> <span class="mobooking-required">*</span>
                                    </label>
                                    <input type="tel" id="mobooking-phone" class="mobooking-input" 
                                           placeholder="<?php esc_attr_e('Your phone number', 'mobooking'); ?>" required>
                                </div>
                                
                                <div class="mobooking-form-group">
                                    <label for="mobooking-alt-phone" class="mobooking-label">
                                        <?php _e('Alternative Phone', 'mobooking'); ?>
                                    </label>
                                    <input type="tel" id="mobooking-alt-phone" class="mobooking-input" 
                                           placeholder="<?php esc_attr_e('Alternative contact number', 'mobooking'); ?>">
                                </div>
                            </div>
                            
                            <div class="mobooking-form-group">
                                <label for="mobooking-address" class="mobooking-label">
                                    <?php _e('Service Address', 'mobooking'); ?> <span class="mobooking-required">*</span>
                                </label>
                                <input type="text" id="mobooking-address" class="mobooking-input" 
                                       placeholder="<?php esc_attr_e('Where should we provide the service?', 'mobooking'); ?>" required>
                            </div>
                            
                            <div class="mobooking-form-group">
                                <label for="mobooking-access-details" class="mobooking-label">
                                    <?php _e('Property Access Details', 'mobooking'); ?>
                                </label>
                                <textarea id="mobooking-access-details" class="mobooking-textarea" 
                                          placeholder="<?php esc_attr_e('How can we access your property? (gate codes, key location, parking instructions, etc.)', 'mobooking'); ?>"></textarea>
                            </div>
                            
                            <div class="mobooking-form-group">
                                <label for="mobooking-notes" class="mobooking-label">
                                    <?php _e('Additional Notes', 'mobooking'); ?>
                                </label>
                                <textarea id="mobooking-notes" class="mobooking-textarea" 
                                          placeholder="<?php esc_attr_e('Any additional information or special requests...', 'mobooking'); ?>"></textarea>
                            </div>
                        </form>
                        
                        <div id="mobooking-contact-feedback" class="mobooking-feedback"></div>
                        
                        <div class="mobooking-form-actions">
                            <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="previousStep()">
                                <?php _e('Back', 'mobooking'); ?>
                            </button>
                            <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="nextStep()">
                                <?php _e('Review Booking', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Simplified Contact Step if property access is disabled -->
                    <div class="mobooking-step" id="mobooking-step-contact">
                        <h2 class="mobooking-step-title">
                            <i class="fas fa-user"></i>
                            <?php _e('Your Details', 'mobooking'); ?>
                        </h2>
                        <p class="mobooking-step-description">
                            <?php _e('Please provide your contact information.', 'mobooking'); ?>
                        </p>
                        
                        <form id="mobooking-contact-form">
                            <div class="mobooking-form-row">
                                <div class="mobooking-form-group">
                                    <label for="mobooking-name" class="mobooking-label">
                                        <?php _e('Full Name', 'mobooking'); ?> <span class="mobooking-required">*</span>
                                    </label>
                                    <input type="text" id="mobooking-name" class="mobooking-input" 
                                           placeholder="<?php esc_attr_e('Your full name', 'mobooking'); ?>" required>
                                </div>
                                
                                <div class="mobooking-form-group">
                                    <label for="mobooking-email" class="mobooking-label">
                                        <?php _e('Email Address', 'mobooking'); ?> <span class="mobooking-required">*</span>
                                    </label>
                                    <input type="email" id="mobooking-email" class="mobooking-input" 
                                           placeholder="<?php esc_attr_e('your@email.com', 'mobooking'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mobooking-form-row">
                                <div class="mobooking-form-group">
                                    <label for="mobooking-phone" class="mobooking-label">
                                        <?php _e('Phone Number', 'mobooking'); ?> <span class="mobooking-required">*</span>
                                    </label>
                                    <input type="tel" id="mobooking-phone" class="mobooking-input" 
                                           placeholder="<?php esc_attr_e('Your phone number', 'mobooking'); ?>" required>
                                </div>
                                
                                <div class="mobooking-form-group">
                                    <label for="mobooking-address" class="mobooking-label">
                                        <?php _e('Service Address', 'mobooking'); ?> <span class="mobooking-required">*</span>
                                    </label>
                                    <input type="text" id="mobooking-address" class="mobooking-input" 
                                           placeholder="<?php esc_attr_e('Where should we provide the service?', 'mobooking'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mobooking-form-group">
                                <label for="mobooking-notes" class="mobooking-label">
                                    <?php _e('Additional Notes', 'mobooking'); ?>
                                </label>
                                <textarea id="mobooking-notes" class="mobooking-textarea" 
                                          placeholder="<?php esc_attr_e('Any additional information or special requests...', 'mobooking'); ?>"></textarea>
                            </div>
                        </form>
                        
                        <div id="mobooking-contact-feedback" class="mobooking-feedback"></div>
                        
                        <div class="mobooking-form-actions">
                            <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="previousStep()">
                                <?php _e('Back', 'mobooking'); ?>
                            </button>
                            <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="nextStep()">
                                <?php _e('Review Booking', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Step: Review & Confirm -->
                    <div class="mobooking-step" id="mobooking-step-review">
                        <h2 class="mobooking-step-title">
                            <i class="fas fa-check-circle"></i>
                            <?php _e('Review & Confirm', 'mobooking'); ?>
                        </h2>
                        <p class="mobooking-step-description">
                            <?php _e('Please review your booking details and confirm your appointment.', 'mobooking'); ?>
                        </p>
                        
                        <div id="mobooking-review-summary" class="mobooking-review-summary">
                            <!-- Summary will be populated by JavaScript -->
                        </div>
                        
                        <?php if ($form_config['allow_discount_codes']): ?>
                        <div class="mobooking-form-group">
                            <label for="mobooking-discount-code" class="mobooking-label">
                                <?php _e('Discount Code', 'mobooking'); ?>
                            </label>
                            <div style="display: flex; gap: 0.5rem;">
                                <input type="text" id="mobooking-discount-code" class="mobooking-input" 
                                       placeholder="<?php esc_attr_e('Enter discount code', 'mobooking'); ?>">
                                <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="applyDiscountCode()">
                                    <?php _e('Apply', 'mobooking'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div id="mobooking-discount-feedback" class="mobooking-feedback"></div>
                        <?php endif; ?>
                        
                        <div id="mobooking-final-feedback" class="mobooking-feedback"></div>
                        
                        <div class="mobooking-form-actions">
                            <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="previousStep()">
                                <?php _e('Back', 'mobooking'); ?>
                            </button>
                            <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="submitBooking()">
                                <i class="fas fa-calendar-plus"></i>
                                <?php _e('Confirm Booking', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Step: Success -->
                    <div class="mobooking-step" id="mobooking-step-success">
                        <div class="mobooking-success">
                            <div class="mobooking-success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h2><?php _e('Booking Confirmed!', 'mobooking'); ?></h2>
                            <p><?php echo esc_html($form_config['success_message']); ?></p>
                            
                            <div id="mobooking-booking-reference" style="margin: 2rem 0;"></div>
                            
                            <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="resetForm()">
                                <?php _e('Book Another Service', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Sidebar inside main container -->
                <div class="mobooking-sidebar" id="mobooking-sidebar">
                    <div class="mobooking-sidebar-title">
                        <?php _e('Booking Summary', 'mobooking'); ?>
                    </div>
                    
                    <div class="mobooking-sidebar-content" id="mobooking-sidebar-content">
                        <p class="mobooking-sidebar-empty">
                            <?php _e('Select a service to see pricing details', 'mobooking'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden fields -->
<input type="hidden" id="mobooking-tenant-id" value="<?php echo esc_attr($tenant_id); ?>">
<input type="hidden" id="mobooking-selected-service" value="">
<input type="hidden" id="mobooking-selected-options" value="">
<input type="hidden" id="mobooking-selected-frequency" value="">
<input type="hidden" id="mobooking-selected-date" value="">
<input type="hidden" id="mobooking-selected-time" value="">

<script>
// Configuration
const MoBookingConfig = <?php echo wp_json_encode($js_config); ?>;

// Global variables
let currentStep = 1;
let stepMapping = {}; // Maps step names to numbers
let selectedService = null;
let selectedOptions = {};
let selectedFrequency = null;
let selectedDate = null;
let selectedTime = null;
let petInformation = {};
let currentPricing = {
    subtotal: 0,
    discountAmount: 0,
    finalTotal: 0
};
let appliedDiscount = null;

// Initialize step mapping based on enabled features
function initializeStepMapping() {
    let stepNumber = 1;
    
    if (MoBookingConfig.enable_area_check) {
        stepMapping['area'] = stepNumber++;
    }
    
    stepMapping['services'] = stepNumber++;
    stepMapping['options'] = stepNumber++;
    
    if (MoBookingConfig.enable_pet_information) {
        stepMapping['pets'] = stepNumber++;
    }
    
    if (MoBookingConfig.enable_service_frequency) {
        stepMapping['frequency'] = stepNumber++;
    }
    
    if (MoBookingConfig.enable_datetime_selection) {
        stepMapping['datetime'] = stepNumber++;
    }
    
    stepMapping['contact'] = stepNumber++;
    stepMapping['review'] = stepNumber++;
    stepMapping['success'] = stepNumber++;
    
    // Set initial step
    if (!MoBookingConfig.enable_area_check) {
        currentStep = stepMapping['services'];
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initializeStepMapping();
    initializeProgressSteps();
    updateProgressBar();
    loadServices();
    updateSidebarVisibility();
});

// Progress Steps Management
function initializeProgressSteps() {
    const progressSteps = document.getElementById('mobooking-progress-steps');
    if (!progressSteps) return;
    
    let html = '';
    for (let i = 1; i <= MoBookingConfig.total_steps; i++) {
        const isActive = i === currentStep ? 'active' : '';
        html += `<div class="mobooking-step-indicator ${isActive}" data-step="${i}">${i}</div>`;
    }
    
    progressSteps.innerHTML = html;
}

// Step Management
function nextStep() {
    if (validateCurrentStep()) {
        const nextStepNum = getNextStep();
        if (nextStepNum) {
            currentStep = nextStepNum;
            showStep(currentStep);
            updateProgressBar();
            updateSidebarVisibility();
        }
    }
}

function previousStep() {
    const prevStepNum = getPreviousStep();
    if (prevStepNum) {
        currentStep = prevStepNum;
        showStep(currentStep);
        updateProgressBar();
        updateSidebarVisibility();
    }
}


function getNextStep() {
    const stepKeys = Object.keys(stepMapping);
    const currentStepKey = Object.keys(stepMapping).find(key => stepMapping[key] === currentStep);
    const currentIndex = stepKeys.indexOf(currentStepKey);
    
    if (currentIndex >= 0 && currentIndex < stepKeys.length - 1) {
        return stepMapping[stepKeys[currentIndex + 1]];
    }
    return null;
}

function getPreviousStep() {
    const stepKeys = Object.keys(stepMapping);
    const currentStepKey = Object.keys(stepMapping).find(key => stepMapping[key] === currentStep);
    const currentIndex = stepKeys.indexOf(currentStepKey);
    
    if (currentIndex > 0) {
        return stepMapping[stepKeys[currentIndex - 1]];
    }
    return null;
}

function showStep(stepNumber) {
    // Hide all steps
    document.querySelectorAll('.mobooking-step').forEach(stepEl => {
        stepEl.classList.remove('active');
    });
    
    // Show current step based on step number
    const stepKey = Object.keys(stepMapping).find(key => stepMapping[key] === stepNumber);
    let stepElementId = '';
    
    switch(stepKey) {
        case 'area':
            stepElementId = 'mobooking-step-1';
            break;
        case 'services':
            stepElementId = 'mobooking-step-services';
            break;
        case 'options':
            stepElementId = 'mobooking-step-options';
            break;
        case 'pets':
            stepElementId = 'mobooking-step-pets';
            break;
        case 'frequency':
            stepElementId = 'mobooking-step-frequency';
            break;
        case 'datetime':
            stepElementId = 'mobooking-step-datetime';
            break;
        case 'contact':
            stepElementId = 'mobooking-step-contact';
            break;
        case 'review':
            stepElementId = 'mobooking-step-review';
            break;
        case 'success':
            stepElementId = 'mobooking-step-success';
            break;
    }
    
    const stepElement = document.getElementById(stepElementId);
    if (stepElement) {
        stepElement.classList.add('active');
    }
    
    // Special handling for review step
    if (stepKey === 'review') {
        setTimeout(updateReviewSummary, 100);
    }
}

function updateProgressBar() {
    const progressFill = document.getElementById('mobooking-progress-fill');
    const progressText = document.getElementById('mobooking-progress-text');
    const stepIndicators = document.querySelectorAll('.mobooking-step-indicator');
    
    if (progressFill && progressText) {
        const progress = ((currentStep - 1) / (MoBookingConfig.total_steps - 1)) * 100;
        progressFill.style.width = progress + '%';
        progressText.textContent = `Step ${currentStep} of ${MoBookingConfig.total_steps}`;
    }
    
    stepIndicators.forEach((indicator, index) => {
        const stepNum = index + 1;
        indicator.classList.remove('active', 'completed');
        
        if (stepNum === currentStep) {
            indicator.classList.add('active');
        } else if (stepNum < currentStep) {
            indicator.classList.add('completed');
            indicator.innerHTML = '<i class="fas fa-check"></i>';
        } else {
            indicator.textContent = stepNum;
        }
    });
}

function updateSidebarVisibility() {
    const sidebar = document.getElementById('mobooking-sidebar');
    if (!sidebar) return;
    
    const stepKey = Object.keys(stepMapping).find(key => stepMapping[key] === currentStep);
    
    // Hide sidebar on contact details step
    if (stepKey === 'contact') {
        sidebar.classList.add('hidden');
    } else if (stepKey === 'review') {
        // Show only total on review step
        sidebar.classList.remove('hidden');
        updateSidebarForReview();
    } else if (stepKey === 'success') {
        // Hide sidebar on success
        sidebar.classList.add('hidden');
    } else {
        sidebar.classList.remove('hidden');
    }
}

function updateSidebarForReview() {
    const sidebarContent = document.getElementById('mobooking-sidebar-content');
    if (!selectedService || !sidebarContent) return;
    
    const html = `
        <div class="mobooking-pricing-section">
            <div class="mobooking-price-total">
                <span>Total:</span>
                <span>${formatPrice(currentPricing.finalTotal)}</span>
            </div>
        </div>
    `;
    
    sidebarContent.innerHTML = html;
}

// Validation
function validateCurrentStep() {
    const stepKey = Object.keys(stepMapping).find(key => stepMapping[key] === currentStep);
    
    switch(stepKey) {
        case 'area':
            return validateAreaCheck();
        case 'services':
            return validateServiceSelection();
        case 'options':
            return validateOptions();
        case 'pets':
            return validatePets();
        case 'frequency':
            return validateFrequency();
        case 'datetime':
            return validateDateTime();
        case 'contact':
            return validateContactDetails();
        case 'review':
            return true;
        default:
            return true;
    }
}

function validateAreaCheck() {
    const location = document.getElementById('mobooking-location');
    if (!location || !location.value.trim()) {
        showFeedback('mobooking-location-feedback', 'Please enter your location.', 'error');
        return false;
    }
    return true;
}

function validateServiceSelection() {
    if (!selectedService) {
        showFeedback('mobooking-services-feedback', MoBookingConfig.messages.select_service, 'error');
        return false;
    }
    return true;
}

function validateOptions() {
    return true; // Options are optional
}

function validatePets() {
    return true; // Pet information is optional
}

function validateFrequency() {
    if (!selectedFrequency) {
        showFeedback('mobooking-frequency-feedback', 'Please select a service frequency.', 'error');
        return false;
    }
    return true;
}

function validateDateTime() {
    if (!selectedDate) {
        showFeedback('mobooking-datetime-feedback', MoBookingConfig.messages.select_date, 'error');
        return false;
    }
    if (!selectedTime) {
        showFeedback('mobooking-datetime-feedback', MoBookingConfig.messages.select_time, 'error');
        return false;
    }
    return true;
}

function validateContactDetails() {
    const name = document.getElementById('mobooking-name');
    const email = document.getElementById('mobooking-email');
    const phone = document.getElementById('mobooking-phone');
    const address = document.getElementById('mobooking-address');
    
    if (!name || !name.value.trim()) {
        showFeedback('mobooking-contact-feedback', MoBookingConfig.messages.name_required, 'error');
        return false;
    }
    
    if (!email || !email.value.trim() || !isValidEmail(email.value)) {
        showFeedback('mobooking-contact-feedback', MoBookingConfig.messages.email_required, 'error');
        return false;
    }
    
    if (!phone || !phone.value.trim()) {
        showFeedback('mobooking-contact-feedback', MoBookingConfig.messages.phone_required, 'error');
        return false;
    }
    
    if (!address || !address.value.trim()) {
        showFeedback('mobooking-contact-feedback', MoBookingConfig.messages.address_required, 'error');
        return false;
    }
    
    return true;
}

// Service Loading and Selection
function loadServices() {
    const container = document.getElementById('mobooking-services-container');
    if (!container) return;
    
    const data = new FormData();
    data.append('action', 'mobooking_get_public_services');
    data.append('nonce', MoBookingConfig.nonce);
    data.append('tenant_id', MoBookingConfig.tenant_id);
    
    fetch(MoBookingConfig.ajax_url, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.data && typeof result.data.services !== 'undefined') {
            renderServices(result.data.services);
        } else {
            container.innerHTML = `
                <div class="mobooking-feedback error" style="display: block;">
                    ${result.data?.message || 'Failed to load services'}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading services:', error);
        container.innerHTML = `
            <div class="mobooking-feedback error" style="display: block;">
                Failed to load services. Please try again.
            </div>
        `;
    });
}

function renderServices(services) {
    const container = document.getElementById('mobooking-services-container');
    if (!container || !services.length) {
        container.innerHTML = `
            <div class="mobooking-feedback error" style="display: block;">
                No services available at this time.
            </div>
        `;
        return;
    }
    
    const html = `
        <div class="mobooking-services-grid">
            ${services.map(service => `
                <div class="mobooking-service-card" onclick="selectService(${service.id})" data-service-id="${service.id}">
                    <div class="mobooking-service-header">
                        <div class="mobooking-service-icon">
                            <i class="${service.icon || 'fas fa-cog'}"></i>
                        </div>
                        <div class="mobooking-service-info">
                            <h3>${escapeHtml(service.name)}</h3>
                            <div class="mobooking-service-price">${formatPrice(service.price)}</div>
                        </div>
                    </div>
                    ${service.description ? `<div class="mobooking-service-description">${escapeHtml(service.description)}</div>` : ''}
                </div>
            `).join('')}
        </div>
    `;
    
    container.innerHTML = html;
}

function selectService(serviceId) {
    // Remove previous selection
    document.querySelectorAll('.mobooking-service-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked card
    const selectedCard = document.querySelector(`[data-service-id="${serviceId}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
    }
    
    // Store selection
    selectedService = serviceId;
    document.getElementById('mobooking-selected-service').value = serviceId;
    
    // Enable continue button
    const stepKey = Object.keys(stepMapping).find(key => stepMapping[key] === currentStep);
    if (stepKey === 'services') {
        const continueBtn = document.querySelector('#mobooking-step-services .mobooking-btn-primary');
        if (continueBtn) {
            continueBtn.disabled = false;
        }
    }
    
    // Load service options and update sidebar
    loadServiceOptions(serviceId);
    updateSidebar();
}

function loadServiceOptions(serviceId) {
    const data = new FormData();
    data.append('action', 'mobooking_get_service_options');
    data.append('nonce', MoBookingConfig.nonce);
    data.append('service_id', serviceId);
    
    fetch(MoBookingConfig.ajax_url, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.data) {
            renderServiceOptions(result.data);
        }
    })
    .catch(error => {
        console.error('Error loading service options:', error);
    });
}

function renderServiceOptions(options) {
    const container = document.getElementById('mobooking-options-container');
    if (!container) return;
    
    if (!options || !options.length) {
        container.innerHTML = `
            <p style="text-align: center; color: var(--mobk-muted-foreground); font-style: italic;">
                No additional options available for this service.
            </p>
        `;
        return;
    }
    
    const html = `
        <div class="mobooking-options-grid">
            ${options.map(option => renderOptionHTML(option)).join('')}
        </div>
    `;
    
    container.innerHTML = html;
    
    // Add event listeners
    container.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('change', updateOptionsAndPricing);
        input.addEventListener('input', updateOptionsAndPricing);
    });
}

function renderOptionHTML(option) {
    switch(option.type) {
        case 'checkbox':
            return `
                <div class="mobooking-option-group">
                    <label class="mobooking-option-label">
                        <input type="checkbox" data-option-id="${option.id}" data-price="${option.price || 0}">
                        ${escapeHtml(option.name)}
                        ${option.price > 0 ? ` (+${formatPrice(option.price)})` : ''}
                    </label>
                    ${option.description ? `<p style="font-size: 0.875rem; color: var(--mobk-muted-foreground); margin-top: 0.25rem;">${escapeHtml(option.description)}</p>` : ''}
                </div>
            `;
        
        case 'select':
            return `
                <div class="mobooking-option-group">
                    <label class="mobooking-option-label">${escapeHtml(option.name)}</label>
                    <select data-option-id="${option.id}" class="mobooking-select">
                        <option value="">Select an option</option>
                        ${(option.values || []).map(value => `
                            <option value="${escapeHtml(value.value)}" data-price="${value.price || 0}">
                                ${escapeHtml(value.label)}${value.price > 0 ? ` (+${formatPrice(value.price)})` : ''}
                            </option>
                        `).join('')}
                    </select>
                    ${option.description ? `<p style="font-size: 0.875rem; color: var(--mobk-muted-foreground); margin-top: 0.25rem;">${escapeHtml(option.description)}</p>` : ''}
                </div>
            `;
        
        case 'number':
        case 'quantity':
            return `
                <div class="mobooking-option-group">
                    <label class="mobooking-option-label">${escapeHtml(option.name)}</label>
                    <input type="number" data-option-id="${option.id}" data-price="${option.price || 0}" 
                           class="mobooking-input" min="0" step="1" placeholder="0">
                    ${option.description ? `<p style="font-size: 0.875rem; color: var(--mobk-muted-foreground); margin-top: 0.25rem;">${escapeHtml(option.description)}</p>` : ''}
                </div>
            `;
        
        case 'text':
            return `
                <div class="mobooking-option-group">
                    <label class="mobooking-option-label">${escapeHtml(option.name)}</label>
                    <input type="text" data-option-id="${option.id}" class="mobooking-input" 
                           placeholder="${escapeHtml(option.placeholder || '')}">
                    ${option.description ? `<p style="font-size: 0.875rem; color: var(--mobk-muted-foreground); margin-top: 0.25rem;">${escapeHtml(option.description)}</p>` : ''}
                </div>
            `;
        
        case 'textarea':
            return `
                <div class="mobooking-option-group">
                    <label class="mobooking-option-label">${escapeHtml(option.name)}</label>
                    <textarea data-option-id="${option.id}" class="mobooking-textarea" 
                              placeholder="${escapeHtml(option.placeholder || '')}"></textarea>
                    ${option.description ? `<p style="font-size: 0.875rem; color: var(--mobk-muted-foreground); margin-top: 0.25rem;">${escapeHtml(option.description)}</p>` : ''}
                </div>
            `;
        
        default:
            return '';
    }
}

function updateOptionsAndPricing() {
    selectedOptions = {};
    currentPricing.subtotal = selectedService ? parseFloat(getServicePrice(selectedService)) : 0;
    
    // Collect selected options and calculate pricing
    document.querySelectorAll('[data-option-id]').forEach(input => {
        const optionId = input.dataset.optionId;
        let value = null;
        let price = 0;
        
        if (input.type === 'checkbox' && input.checked) {
            value = '1';
            price = parseFloat(input.dataset.price) || 0;
        } else if (input.type === 'number' && input.value) {
            value = input.value;
            price = (parseFloat(input.dataset.price) || 0) * parseInt(input.value);
        } else if (input.tagName === 'SELECT' && input.value) {
            const selectedOption = input.options[input.selectedIndex];
            value = input.value;
            price = parseFloat(selectedOption.dataset.price) || 0;
        } else if ((input.type === 'text' || input.tagName === 'TEXTAREA') && input.value) {
            value = input.value;
        }
        
        if (value !== null) {
            selectedOptions[optionId] = {
                value: value,
                price: price
            };
            currentPricing.subtotal += price;
        }
    });
    
    // Apply discount if any
    currentPricing.finalTotal = currentPricing.subtotal - currentPricing.discountAmount;
    
    // Update sidebar
    updateSidebar();
    
    // Store options
    document.getElementById('mobooking-selected-options').value = JSON.stringify(selectedOptions);
}

function updateSidebar() {
    const sidebarContent = document.getElementById('mobooking-sidebar-content');
    if (!sidebarContent || !selectedService) return;
    
    const service = getSelectedServiceData();
    if (!service) return;
    
    let html = `
        <div style="margin-bottom: 1rem;">
            <h4 style="font-weight: 600; margin-bottom: 0.5rem; color: var(--mobk-card-foreground);">
                ${escapeHtml(service.name)}
            </h4>
            <div style="display: flex; justify-content: space-between; font-size: 0.875rem; color: var(--mobk-muted-foreground);">
                <span>Base Price:</span>
                <span style="font-weight: 500; color: var(--mobk-card-foreground);">${formatPrice(service.price)}</span>
            </div>
        </div>
    `;
    
    // Show selected options
    if (Object.keys(selectedOptions).length > 0) {
        html += '<div style="margin-bottom: 1rem;"><h5 style="font-weight: 500; margin-bottom: 0.5rem; color: var(--mobk-card-foreground);">Options:</h5>';
        
        Object.entries(selectedOptions).forEach(([optionId, option]) => {
            if (option.price > 0) {
                html += `
                    <div style="display: flex; justify-content: space-between; font-size: 0.875rem; margin-bottom: 0.25rem; color: var(--mobk-muted-foreground);">
                        <span>${escapeHtml(option.value)}</span>
                        <span style="font-weight: 500; color: var(--mobk-card-foreground);">+${formatPrice(option.price)}</span>
                    </div>
                `;
            }
        });
        
        html += '</div>';
    }
    
    // Show pricing
    html += `
        <div class="mobooking-pricing-section">
            <div class="mobooking-price-item">
                <span>Subtotal:</span>
                <span>${formatPrice(currentPricing.subtotal)}</span>
            </div>
            ${currentPricing.discountAmount > 0 ? `
                <div class="mobooking-price-item">
                    <span>Discount:</span>
                    <span>-${formatPrice(currentPricing.discountAmount)}</span>
                </div>
            ` : ''}
            <div class="mobooking-price-total">
                <span>Total:</span>
                <span>${formatPrice(currentPricing.finalTotal)}</span>
            </div>
        </div>
    `;
    
    sidebarContent.innerHTML = html;
}

// Pet Information Functions
function updatePetFields() {
    const petCount = document.getElementById('mobooking-pet-count').value;
    const petDetails = document.getElementById('mobooking-pet-details');
    
    if (petCount === '0') {
        petDetails.classList.add('hidden');
        petInformation = {};
        return;
    }
    
    petDetails.classList.remove('hidden');
    
    let html = '';
    const count = parseInt(petCount);
    for (let i = 1; i <= count; i++) {
        html += `
            <div class="mobooking-pet-item">
                <div class="mobooking-pet-info">
                    <h4>Pet ${i}</h4>
                    <div class="mobooking-form-row">
                        <div class="mobooking-form-group">
                            <label class="mobooking-label">Type</label>
                            <select class="mobooking-select" data-pet="${i}" data-field="type">
                                <option value="">Select type</option>
                                <option value="dog">Dog</option>
                                <option value="cat">Cat</option>
                                <option value="bird">Bird</option>
                                <option value="rabbit">Rabbit</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mobooking-form-group">
                            <label class="mobooking-label">Size</label>
                            <select class="mobooking-select" data-pet="${i}" data-field="size">
                                <option value="">Select size</option>
                                <option value="small">Small</option>
                                <option value="medium">Medium</option>
                                <option value="large">Large</option>
                                <option value="extra-large">Extra Large</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    petDetails.innerHTML = html;
    
    // Add event listeners
    petDetails.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', updatePetInformation);
    });
}

function updatePetInformation() {
    const petSelects = document.querySelectorAll('[data-pet]');
    petInformation = {};
    
    petSelects.forEach(select => {
        const petNumber = select.dataset.pet;
        const field = select.dataset.field;
        
        if (!petInformation[petNumber]) {
            petInformation[petNumber] = {};
        }
        
        petInformation[petNumber][field] = select.value;
    });
}

// Frequency Selection
function selectFrequency(frequency) {
    // Remove previous selection
    document.querySelectorAll('.mobooking-frequency-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Add selection to clicked card
    const selectedCard = document.querySelector(`[data-frequency="${frequency}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
    }
    
    selectedFrequency = frequency;
    document.getElementById('mobooking-selected-frequency').value = frequency;
}

// Date and Time Selection
function loadTimeSlots() {
    const date = document.getElementById('mobooking-service-date').value;
    if (!date) return;
    
    selectedDate = date;
    document.getElementById('mobooking-selected-date').value = date;
    
    const container = document.getElementById('mobooking-time-slots-container');
    const slotsDiv = document.getElementById('mobooking-time-slots');
    
    container.classList.remove('hidden');
    
    // Show loading
    slotsDiv.innerHTML = `
        <div class="mobooking-loading">
            <div class="mobooking-spinner"></div>
            Loading time slots...
        </div>
    `;
    
    const data = new FormData();
    data.append('action', 'mobooking_get_time_slots');
    data.append('nonce', MoBookingConfig.nonce);
    data.append('service_id', selectedService);
    data.append('date', date);
    
    fetch(MoBookingConfig.ajax_url, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.data) {
            renderTimeSlots(result.data);
        } else {
            slotsDiv.innerHTML = `
                <div style="text-align: center; color: var(--mobk-muted-foreground); font-style: italic;">
                    No time slots available for this date.
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading time slots:', error);
        slotsDiv.innerHTML = `
            <div style="text-align: center; color: hsl(0 84.2% 60.2%); font-style: italic;">
                Error loading time slots.
            </div>
        `;
    });
}

function renderTimeSlots(slots) {
    const slotsDiv = document.getElementById('mobooking-time-slots');
    
    if (!slots || !slots.length) {
        slotsDiv.innerHTML = `
            <div style="text-align: center; color: var(--mobk-muted-foreground); font-style: italic;">
                No time slots available for this date.
            </div>
        `;
        return;
    }
    
    const html = slots.map(slot => `
        <div class="mobooking-time-slot ${slot.available ? '' : 'unavailable'}" 
             onclick="${slot.available ? `selectTimeSlot('${slot.time}')` : ''}" 
             data-time="${slot.time}">
            ${slot.time}
        </div>
    `).join('');
    
    slotsDiv.innerHTML = html;
}

function selectTimeSlot(time) {
    // Remove previous selection
    document.querySelectorAll('.mobooking-time-slot').forEach(slot => {
        slot.classList.remove('selected');
    });
    
    // Add selection to clicked slot
    const selectedSlot = document.querySelector(`[data-time="${time}"]`);
    if (selectedSlot) {
        selectedSlot.classList.add('selected');
    }
    
    selectedTime = time;
    document.getElementById('mobooking-selected-time').value = time;
}

// Area Check
function checkServiceArea() {
    const location = document.getElementById('mobooking-location');
    if (!validateAreaCheck()) return;
    
    // For demo purposes, always allow
    showFeedback('mobooking-location-feedback', 'Great! We provide services in your area.', 'success');
    
    setTimeout(() => {
        nextStep();
    }, 1000);
}

// Discount Code
function applyDiscountCode() {
    const codeInput = document.getElementById('mobooking-discount-code');
    if (!codeInput || !codeInput.value.trim()) {
        showFeedback('mobooking-discount-feedback', 'Please enter a discount code.', 'error');
        return;
    }
    
    const data = new FormData();
    data.append('action', 'mobooking_apply_discount');
    data.append('nonce', MoBookingConfig.nonce);
    data.append('code', codeInput.value.trim());
    data.append('subtotal', currentPricing.subtotal);
    
    fetch(MoBookingConfig.ajax_url, {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.data) {
            appliedDiscount = result.data;
            currentPricing.discountAmount = result.data.amount;
            currentPricing.finalTotal = currentPricing.subtotal - currentPricing.discountAmount;
            
            showFeedback('mobooking-discount-feedback', `Discount applied: ${result.data.description}`, 'success');
            updateSidebar();
        } else {
            showFeedback('mobooking-discount-feedback', result.data?.message || 'Invalid discount code.', 'error');
        }
    })
    .catch(error => {
        console.error('Error applying discount:', error);
        showFeedback('mobooking-discount-feedback', 'Error applying discount code.', 'error');
    });
}

// Booking Submission
function submitBooking() {
    const submitBtn = document.querySelector('#mobooking-step-review .mobooking-btn-primary');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<div class="mobooking-spinner"></div> ' + MoBookingConfig.messages.submitting_booking;
    
    // Collect all form data
    const bookingData = {
        action: 'mobooking_submit_booking',
        nonce: MoBookingConfig.nonce,
        tenant_id: MoBookingConfig.tenant_id,
        service_id: selectedService,
        options: selectedOptions,
        pricing: currentPricing,
        discount: appliedDiscount,
        frequency: selectedFrequency,
        date: selectedDate,
        time: selectedTime,
        pets: petInformation,
        customer_details: {
            name: document.getElementById('mobooking-name')?.value || '',
            email: document.getElementById('mobooking-email')?.value || '',
            phone: document.getElementById('mobooking-phone')?.value || '',
            alt_phone: document.getElementById('mobooking-alt-phone')?.value || '',
            address: document.getElementById('mobooking-address')?.value || '',
            access_details: document.getElementById('mobooking-access-details')?.value || '',
            notes: document.getElementById('mobooking-notes')?.value || '',
            pet_notes: document.getElementById('mobooking-pet-notes')?.value || ''
        }
    };
    
    fetch(MoBookingConfig.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(bookingData)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Move to success step
            currentStep = stepMapping['success'];
            showStep(currentStep);
            updateProgressBar();
            updateSidebarVisibility();
            
            // Update booking reference if provided
            const referenceDiv = document.getElementById('mobooking-booking-reference');
            if (referenceDiv && result.data?.booking_reference) {
                referenceDiv.innerHTML = `
                    <div style="background: var(--mobk-muted); padding: 1rem; border-radius: var(--mobk-radius); text-align: center;">
                        <strong>Booking Reference:</strong> ${escapeHtml(result.data.booking_reference)}
                    </div>
                `;
            }
            
        } else {
            showFeedback('mobooking-final-feedback', result.data?.message || MoBookingConfig.messages.booking_error, 'error');
        }
    })
    .catch(error => {
        console.error('Error submitting booking:', error);
        showFeedback('mobooking-final-feedback', MoBookingConfig.messages.booking_error, 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

// Reset Form
function resetForm() {
    // Reset variables
    currentStep = MoBookingConfig.enable_area_check ? stepMapping['area'] : stepMapping['services'];
    selectedService = null;
    selectedOptions = {};
    selectedFrequency = null;
    selectedDate = null;
    selectedTime = null;
    petInformation = {};
    currentPricing = { subtotal: 0, discountAmount: 0, finalTotal: 0 };
    appliedDiscount = null;
    
    // Reset form fields
    document.querySelectorAll('input, select, textarea').forEach(field => {
        if (field.type === 'checkbox' || field.type === 'radio') {
            field.checked = false;
        } else {
            field.value = '';
        }
    });
    
    // Reset UI
    document.querySelectorAll('.mobooking-service-card, .mobooking-frequency-card, .mobooking-time-slot').forEach(card => {
        card.classList.remove('selected');
    });
    
    document.querySelectorAll('.mobooking-feedback').forEach(feedback => {
        feedback.style.display = 'none';
        feedback.className = 'mobooking-feedback';
    });
    
    // Hide conditional elements
    document.getElementById('mobooking-time-slots-container')?.classList.add('hidden');
    document.getElementById('mobooking-pet-details')?.classList.add('hidden');
    
    // Reset buttons
    const continueBtn = document.querySelector('#mobooking-step-services .mobooking-btn-primary');
    if (continueBtn) {
        continueBtn.disabled = true;
    }
    
    // Show first step
    showStep(currentStep);
    updateProgressBar();
    updateSidebarVisibility();
    
    // Reset sidebar content
    const sidebarContent = document.getElementById('mobooking-sidebar-content');
    if (sidebarContent) {
        sidebarContent.innerHTML = `
            <p class="mobooking-sidebar-empty">
                Select a service to see pricing details
            </p>
        `;
    }
    
    // Reload services
    loadServices();
}

// Review Summary
function updateReviewSummary() {
    const summaryDiv = document.getElementById('mobooking-review-summary');
    if (!summaryDiv || !selectedService) return;
    
    const service = getSelectedServiceData();
    const customerDetails = {
        name: document.getElementById('mobooking-name')?.value || '',
        email: document.getElementById('mobooking-email')?.value || '',
        phone: document.getElementById('mobooking-phone')?.value || '',
        address: document.getElementById('mobooking-address')?.value || '',
        access_details: document.getElementById('mobooking-access-details')?.value || '',
        notes: document.getElementById('mobooking-notes')?.value || '',
        pet_notes: document.getElementById('mobooking-pet-notes')?.value || ''
    };
    
    let html = `
        <div style="background: var(--mobk-muted); padding: 1.5rem; border-radius: var(--mobk-radius); margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; color: var(--mobk-card-foreground);">
                Booking Summary
            </h3>
            
            <div style="display: grid; gap: 1rem;">
                <div>
                    <h4 style="font-weight: 500; margin-bottom: 0.5rem; color: var(--mobk-card-foreground);">Service</h4>
                    <p style="color: var(--mobk-muted-foreground); margin: 0;">${escapeHtml(service.name)}</p>
                </div>
    `;
    
    // Add frequency if selected
    if (selectedFrequency) {
        const frequencyLabels = {
            'one-time': 'One-time',
            'weekly': 'Weekly',
            'bi-weekly': 'Bi-weekly',
            'monthly': 'Monthly'
        };
        html += `
            <div>
                <h4 style="font-weight: 500; margin-bottom: 0.5rem; color: var(--mobk-card-foreground);">Frequency</h4>
                <p style="color: var(--mobk-muted-foreground); margin: 0;">${frequencyLabels[selectedFrequency] || selectedFrequency}</p>
            </div>
        `;
    }
    
    // Add date and time if selected
    if (selectedDate || selectedTime) {
        html += `
            <div>
                <h4 style="font-weight: 500; margin-bottom: 0.5rem; color: var(--mobk-card-foreground);">Date & Time</h4>
                <div style="color: var(--mobk-muted-foreground); font-size: 0.875rem;">
                    ${selectedDate ? `<p style="margin: 0 0 0.25rem 0;"><strong>Date:</strong> ${selectedDate}</p>` : ''}
                    ${selectedTime ? `<p style="margin: 0 0 0.25rem 0;"><strong>Time:</strong> ${selectedTime}</p>` : ''}
                </div>
            </div>
        `;
    }
    
    // Add customer details
    html += `
        <div>
            <h4 style="font-weight: 500; margin-bottom: 0.5rem; color: var(--mobk-card-foreground);">Customer Details</h4>
            <div style="color: var(--mobk-muted-foreground); font-size: 0.875rem;">
                <p style="margin: 0 0 0.25rem 0;"><strong>Name:</strong> ${escapeHtml(customerDetails.name)}</p>
                <p style="margin: 0 0 0.25rem 0;"><strong>Email:</strong> ${escapeHtml(customerDetails.email)}</p>
                <p style="margin: 0 0 0.25rem 0;"><strong>Phone:</strong> ${escapeHtml(customerDetails.phone)}</p>
                <p style="margin: 0 0 0.25rem 0;"><strong>Address:</strong> ${escapeHtml(customerDetails.address)}</p>
            </div>
        </div>
    `;
    
    // Add pet information if any
    if (Object.keys(petInformation).length > 0) {
        html += `
            <div>
                <h4 style="font-weight: 500; margin-bottom: 0.5rem; color: var(--mobk-card-foreground);">Pet Information</h4>
                <div style="color: var(--mobk-muted-foreground); font-size: 0.875rem;">
        `;
        
        Object.entries(petInformation).forEach(([petNumber, pet]) => {
            if (pet.type || pet.size) {
                html += `<p style="margin: 0 0 0.25rem 0;">Pet ${petNumber}: ${pet.type || 'Unknown type'}${pet.size ? ` (${pet.size})` : ''}</p>`;
            }
        });
        
        if (customerDetails.pet_notes) {
            html += `<p style="margin: 0.5rem 0 0 0;"><strong>Pet Notes:</strong> ${escapeHtml(customerDetails.pet_notes)}</p>`;
        }
        
        html += '</div></div>';
    }
    
    // Add selected options
    if (Object.keys(selectedOptions).length > 0) {
        html += `
            <div>
                <h4 style="font-weight: 500; margin-bottom: 0.5rem; color: var(--mobk-card-foreground);">Selected Options</h4>
                <div style="color: var(--mobk-muted-foreground); font-size: 0.875rem;">
                    ${Object.entries(selectedOptions).map(([optionId, option]) => `
                        <p style="margin: 0 0 0.25rem 0;">${escapeHtml(option.value)}${option.price > 0 ? ` (+${formatPrice(option.price)})` : ''}</p>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    // Add additional notes if any
    if (customerDetails.access_details || customerDetails.notes) {
        html += `
            <div>
                <h4 style="font-weight: 500; margin-bottom: 0.5rem; color: var(--mobk-card-foreground);">Additional Information</h4>
                <div style="color: var(--mobk-muted-foreground); font-size: 0.875rem;">
                    ${customerDetails.access_details ? `<p style="margin: 0 0 0.5rem 0;"><strong>Property Access:</strong> ${escapeHtml(customerDetails.access_details)}</p>` : ''}
                    ${customerDetails.notes ? `<p style="margin: 0;"><strong>Notes:</strong> ${escapeHtml(customerDetails.notes)}</p>` : ''}
                </div>
            </div>
        `;
    }
    
    html += `
            </div>
        </div>
        
        <div style="background: var(--mobk-card); border: 1px solid var(--mobk-border); padding: 1.5rem; border-radius: var(--mobk-radius);">
            <h3 style="font-size: 1.125rem; font-weight: 600; margin-bottom: 1rem; color: var(--mobk-card-foreground);">
                Pricing Breakdown
            </h3>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--mobk-muted-foreground);">
                <span>Service:</span>
                <span style="font-weight: 500; color: var(--mobk-card-foreground);">${formatPrice(service.price)}</span>
            </div>
    `;
    
    // Show option prices
    Object.entries(selectedOptions).forEach(([optionId, option]) => {
        if (option.price > 0) {
            html += `
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--mobk-muted-foreground);">
                    <span>${escapeHtml(option.value)}:</span>
                    <span style="font-weight: 500; color: var(--mobk-card-foreground);">${formatPrice(option.price)}</span>
                </div>
            `;
        }
    });
    
    html += `
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--mobk-muted-foreground); padding-top: 0.5rem; border-top: 1px solid var(--mobk-border);">
                <span>Subtotal:</span>
                <span style="font-weight: 500; color: var(--mobk-card-foreground);">${formatPrice(currentPricing.subtotal)}</span>
            </div>
    `;
    
    if (currentPricing.discountAmount > 0) {
        html += `
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem; color: var(--mobk-muted-foreground);">
                <span>Discount:</span>
                <span style="font-weight: 500; color: hsl(142.1 76.2% 36.3%);">-${formatPrice(currentPricing.discountAmount)}</span>
            </div>
        `;
    }
    
    html += `
            <div style="display: flex; justify-content: space-between; font-size: 1.125rem; font-weight: 600; color: var(--mobk-card-foreground); padding-top: 0.75rem; border-top: 1px solid var(--mobk-border);">
                <span>Total:</span>
                <span style="color: var(--mobk-primary);">${formatPrice(currentPricing.finalTotal)}</span>
            </div>
        </div>
    `;
    
    summaryDiv.innerHTML = html;
}

// Utility Functions
function formatPrice(amount) {
    const price = parseFloat(amount).toFixed(2);
    
    if (MoBookingConfig.currency_position === 'after') {
        return price + MoBookingConfig.currency_symbol;
    } else {
        return MoBookingConfig.currency_symbol + price;
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showFeedback(elementId, message, type) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    element.className = `mobooking-feedback ${type}`;
    element.textContent = message;
    element.style.display = 'block';
    
    // Auto-hide success messages after 3 seconds
    if (type === 'success') {
        setTimeout(() => {
            element.style.display = 'none';
        }, 3000);
    }
}

function getSelectedServiceData() {
    // This would normally come from the AJAX response
    // For demo purposes, return mock data based on selectedService
    return {
        id: selectedService,
        name: 'House Cleaning Service',
        price: 100,
        description: 'Professional house cleaning service'
    };
}

function getServicePrice(serviceId) {
    // This would normally come from the AJAX response
    // For demo purposes, return mock price
    return 100;
}
</script>