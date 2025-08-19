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

/* Additional CSS fixes for service selection */
.mobooking-service-card {
    position: relative;
    transition: all 0.3s ease;
}

.mobooking-service-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15);
}

.mobooking-service-card.selected {
    border-color: var(--mobk-primary, #3b82f6);
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.1) 100%);
}

.mobooking-selected-indicator {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 24px;
    height: 24px;
    background: #22c55e;
    border-radius: 50%;
    display: none;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
}

.mobooking-service-card.selected .mobooking-selected-indicator {
    display: flex;
}

.mobooking-feedback {
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
    display: none;
}

.mobooking-feedback.error {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.2);
    display: block;
}

.mobooking-feedback.success {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.2);
    display: block;
}

.mobooking-loading {
    text-align: center;
    padding: 3rem;
    color: #64748b;
}

.mobooking-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f1f5f9;
    border-top: 4px solid #3b82f6;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
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

<!-- Fixed Service Selection Step -->
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

    <!-- Hidden input to store selected service -->
    <input type="hidden" id="mobooking-selected-service" name="selected_service" value="">

    <div class="mobooking-form-actions">
        <?php if ($form_config['enable_area_check']): ?>
        <button type="button" class="mobooking-btn mobooking-btn-secondary" onclick="previousStep()">
            <i class="fas fa-arrow-left"></i>
            <?php _e('Back', 'mobooking'); ?>
        </button>
        <?php else: ?>
        <div></div>
        <?php endif; ?>
        <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="nextStep()" disabled>
            <?php _e('Continue', 'mobooking'); ?>
            <i class="fas fa-arrow-right"></i>
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
    document.addEventListener('DOMContentLoaded', function () {
        const सेवाContainer = document.getElementById('mobooking-services-container');
        const प्रतिक्रियाContainer = document.getElementById('mobooking-services-feedback');
        const serviceInput = document.getElementById('mobooking-selected-service');
        const continueBtn = document.querySelector('#mobooking-step-services .mobooking-btn-primary');

        // Function to show feedback messages
        function showFeedback(message, type = 'error') {
            प्रतिक्रियाContainer.innerHTML = `<div class="mobooking-feedback ${type}">${message}</div>`;
            प्रतिक्रियाContainer.style.display = 'block';
        }

        // Function to fetch services
        function loadServices() {
            const data = new FormData();
            data.append('action', 'mobooking_get_public_services');
            data.append('nonce', '<?php echo wp_create_nonce('mobooking_public_nonce'); ?>');
            data.append('tenant_id', '<?php echo esc_attr($tenant_id); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: data,
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        renderServices(result.data.services);
                    } else {
                        const errorMessage = result.data && result.data.message ? result.data.message : 'An unknown error occurred.';
                        showFeedback(`Could not load services. Error: ${errorMessage}`);
                        सेवाContainer.innerHTML = '';
                    }
                })
                .catch(error => {
                    showFeedback('A network error occurred while trying to load services.');
                    console.error('Service loading error:', error);
                    सेवाContainer.innerHTML = '';
                });
        }

        // Function to render services
        function renderServices(services) {
            if (!services || services.length === 0) {
                showFeedback('No services available at this time.', 'success');
                सेवाContainer.innerHTML = ''; // Clear loading spinner
                return;
            }

            const servicesHTML = services.map(service => {
                const price = parseFloat(service.price);
                const displayPrice = isNaN(price) ? 'Price not available' : `<?php echo esc_js($form_config['currency_symbol']); ?>${price.toFixed(2)}`;

                return `
                    <div class="mobooking-service-card" data-service-id="${service.id}">
                        <div class="mobooking-selected-indicator">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="mobooking-service-header">
                            <div class="mobooking-service-icon">
                                <i class="${service.icon || 'fas fa-concierge-bell'}"></i>
                            </div>
                            <div class="mobooking-service-info">
                                <h3>${service.name}</h3>
                                <div class="mobooking-service-price">${displayPrice}</div>
                            </div>
                        </div>
                        <p class="mobooking-service-description">${service.description || ''}</p>
                    </div>
                `;
            }).join('');

            सेवाContainer.innerHTML = `<div class="mobooking-services-grid">${servicesHTML}</div>`;
            addCardEventListeners();
        }

        // Function to add event listeners to service cards
        function addCardEventListeners() {
            document.querySelectorAll('.mobooking-service-card').forEach(card => {
                card.addEventListener('click', function () {
                    // Deselect all other cards
                    document.querySelectorAll('.mobooking-service-card').forEach(otherCard => {
                        otherCard.classList.remove('selected');
                    });

                    // Select the clicked card
                    this.classList.add('selected');
                    const serviceId = this.getAttribute('data-service-id');
                    serviceInput.value = serviceId;

                    // Enable the continue button
                    if (continueBtn) {
                        continueBtn.disabled = false;
                    }

                    // Hide feedback if a service is selected
                    प्रतिक्रियाContainer.style.display = 'none';
                });
            });
        }

        // Initial load
        loadServices();
    });
</script>