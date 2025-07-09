<?php
/**
 * Complete Public Booking Form Template - Updated Structure
 * @package MoBooking
 */

if (!defined('ABSPATH')) exit;

// Initialize managers and get tenant information
$tenant_id = get_query_var('mobooking_tenant_id_on_page');
$business_slug = get_query_var('mobooking_slug');

// --- Debug Mode Logic ---
$mobooking_is_debug_mode_active = false;
if (class_exists('\MoBooking\Classes\Settings')) {
    $settings_manager_for_debug = new \MoBooking\Classes\Settings();
    $debug_check_tenant_id = $tenant_id ?: get_current_user_id();

    if ($debug_check_tenant_id) {
        $bf_settings_for_debug = $settings_manager_for_debug->get_booking_form_settings($debug_check_tenant_id);
        $debug_mode_setting_enabled = isset($bf_settings_for_debug['bf_debug_mode']) && $bf_settings_for_debug['bf_debug_mode'] === '1';

        if ($debug_mode_setting_enabled && current_user_can('manage_options')) {
            $mobooking_is_debug_mode_active = true;
        }
    }
}

// Fallback methods to get tenant_id if not set
if (!$tenant_id) {
    // Method 1: Check URL parameter
    if (isset($_GET['tid'])) {
        $tenant_id = intval($_GET['tid']);
    }
    
    // Method 2: Try to get from business slug
    if (!$tenant_id && $business_slug) {
        $settings_manager = new \MoBooking\Classes\Settings();
        $users = get_users([
            'meta_key' => 'mobooking_setting_bf_business_slug',
            'meta_value' => $business_slug,
            'number' => 1,
            'fields' => 'ID'
        ]);
        if (!empty($users)) {
            $tenant_id = intval($users[0]);
        }
    }
    
    // Method 3: Get first business owner if still no tenant
    if (!$tenant_id) {
        $business_owners = get_users([
            'role' => 'mobooking_business_owner',
            'number' => 1,
            'fields' => 'ID'
        ]);
        if (!empty($business_owners)) {
            $tenant_id = intval($business_owners[0]);
        }
    }
}

// Validate tenant exists
if (!$tenant_id) {
    echo '<div class="mobooking-error">Configuration error: Tenant not found</div>';
    return;
}

// Initialize class managers
$settings_manager = new \MoBooking\Classes\Settings();
$bf_settings = $settings_manager->get_booking_form_settings($tenant_id);
$biz_settings = $settings_manager->get_business_settings($tenant_id);

// Form configuration with secure defaults
$form_config = [
    'form_enabled' => ($bf_settings['bf_form_enabled'] ?? '1') === '1',
    'form_header' => $bf_settings['bf_header_text'] ?? 'Book Our Services',
    'show_pricing' => ($bf_settings['bf_show_pricing'] ?? '1') === '1',
    'enable_location_check' => ($bf_settings['bf_enable_location_check'] ?? '1') === '1',
    'allow_discount_codes' => ($bf_settings['bf_allow_discount_codes'] ?? '1') === '1',
    'theme_color' => $bf_settings['bf_theme_color'] ?? '#1abc9c',
    'secondary_color' => $bf_settings['bf_secondary_color'] ?? '#34495e',
    'background_color' => $bf_settings['bf_background_color'] ?? '#ffffff',
    'border_radius' => $bf_settings['bf_border_radius'] ?? '8',
    'font_family' => $bf_settings['bf_font_family'] ?? 'system-ui',
    'maintenance_message' => $bf_settings['bf_maintenance_message'] ?? 'Booking form is currently unavailable.',
    'show_progress_bar' => ($bf_settings['bf_show_progress_bar'] ?? '1') === '1',
    'step_1_title' => $bf_settings['bf_step_1_title'] ?? 'Check Service Area',
    'step_2_title' => $bf_settings['bf_step_2_title'] ?? 'Select Service',
    'step_3_title' => $bf_settings['bf_step_3_title'] ?? 'Configure Options',
    'step_4_title' => $bf_settings['bf_step_4_title'] ?? 'Your Details',
    'step_5_title' => $bf_settings['bf_step_5_title'] ?? 'Review & Confirm',
    'success_message' => $bf_settings['bf_success_message'] ?? 'Thank you for your booking! We will contact you soon to confirm the details.',
    'terms_conditions_url' => $bf_settings['bf_terms_conditions_url'] ?? '',
    'custom_css' => $bf_settings['bf_custom_css'] ?? '',
];

// Currency settings
$currency = [
    'symbol' => $biz_settings['biz_currency_symbol'] ?? '$',
    'code' => $biz_settings['biz_currency_code'] ?? 'USD',
];

// Business information
$business_info = [
    'name' => $biz_settings['biz_name'] ?? 'Our Business',
    'phone' => $biz_settings['biz_phone'] ?? '',
    'email' => $biz_settings['biz_email'] ?? '',
    'address' => $biz_settings['biz_address'] ?? '',
];

// Check if form is disabled
if (!$form_config['form_enabled']) {
    echo '<div class="mobooking-maintenance"><h3>Booking Unavailable</h3><p>' . esc_html($form_config['maintenance_message']) . '</p></div>';
    return;
}

// Check if this is an embed
$is_embed = get_query_var('mobooking_page_type') === 'embed_booking';

// Enqueue required scripts and styles
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui-datepicker');

// Create nonce for security
$form_nonce = wp_create_nonce('mobooking_booking_form_nonce');

// Pre-load services and options data
$preloaded_services_data = [];
if ($tenant_id) {
    $services_manager = new \MoBooking\Classes\Services();
    global $wpdb;
    $service_options_manager = new \MoBooking\Classes\ServiceOptions();
    $services_table = \MoBooking\Classes\Database::get_table_name('services');
    $db_services = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$services_table} WHERE user_id = %d AND status = 'active' ORDER BY name ASC",
        $tenant_id
    ), ARRAY_A);

    if ($db_services) {
        foreach ($db_services as $service_item) {
            $item = (array) $service_item;
            $item['price'] = floatval($item['price']);
            $item['duration'] = intval($item['duration']);
            $item['service_id'] = intval($item['service_id']);
            $item['name'] = sanitize_text_field($item['name']);
            $item['description'] = wp_kses_post($item['description']);
            $item['category'] = sanitize_text_field($item['category'] ?? '');

            // Handle service icon
            $raw_icon_value = sanitize_text_field($item['icon'] ?? 'fas fa-concierge-bell');
            $item['icon'] = $raw_icon_value;
            $item['icon_svg_content'] = null;

            if (strpos($raw_icon_value, 'preset:') === 0) {
                $preset_key = substr($raw_icon_value, strlen('preset:'));
                if (class_exists('\MoBooking\Classes\Services')) {
                    $item['icon_svg_content'] = \MoBooking\Classes\Services::get_preset_icon_svg($preset_key);
                }
            }

            // Get service options
            $options_raw = $service_options_manager->get_service_options($item['service_id'], $tenant_id);
            $options = [];
            if (is_array($options_raw)) {
                foreach ($options_raw as $opt) {
                    $option_array = (array) $opt;
                    $option_array['option_id'] = intval($option_array['option_id']);
                    $option_array['name'] = sanitize_text_field($option_array['name']);
                    $option_array['description'] = wp_kses_post($option_array['description'] ?? '');
                    $option_array['type'] = sanitize_text_field($option_array['type']);
                    $option_array['is_required'] = ($option_array['is_required'] ?? '0') === '1';
                    $option_array['price_impact'] = floatval($option_array['price_impact'] ?? 0);
                    $option_array['price_impact_type'] = sanitize_text_field($option_array['price_impact_type'] ?? 'fixed');
                    
                    if (!empty($option_array['option_values'])) {
                        $decoded_values = json_decode($option_array['option_values'], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $option_array['option_values'] = $decoded_values;
                        } else {
                            $option_array['option_values'] = [];
                        }
                    } else {
                        $option_array['option_values'] = [];
                    }
                    $options[] = $option_array;
                }
            }
            $item['options'] = $options;
            $preloaded_services_data[] = $item;
        }
    }
}

// Helper function for hex to rgb conversion
function hex2rgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }
    return implode(', ', [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ]);
}

// Add CSS variables for theming
$css_variables = "
<style>
:root {
    --primary-color: " . esc_attr($form_config['theme_color']) . ";
    --primary-rgb: " . esc_attr(hex2rgb($form_config['theme_color'])) . ";
    --secondary-color: " . esc_attr($form_config['secondary_color']) . ";
    --background-color: " . esc_attr($form_config['background_color']) . ";
    --border-radius: " . esc_attr($form_config['border_radius']) . "px;
    --font-family: " . esc_attr($form_config['font_family']) . ";
}
</style>";

// Add custom CSS if defined
if (!empty($form_config['custom_css'])) {
    $css_variables .= '<style>' . wp_strip_all_tags($form_config['custom_css']) . '</style>';
}

echo $css_variables;
?>

<!DOCTYPE html>
<html lang="<?php echo esc_attr(get_locale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($form_config['form_header']); ?> - <?php echo esc_html($business_info['name']); ?></title>
    <?php wp_head(); ?>

    <style>
        /*
 * Complete Booking Form CSS
 * File: assets/css/booking-form-modern.css
 * Compatible with the updated booking-form-public.php template
 */

/* CSS Variables - These can be overridden by PHP */
:root {
    --primary-color: #1abc9c;
    --primary-rgb: 26, 188, 156;
    --secondary-color: #34495e;
    --background-color: #ffffff;
    --foreground: #1a1a1a;
    --card: #ffffff;
    --border: #e5e7eb;
    --muted: #f3f4f6;
    --muted-foreground: #6b7280;
    --success: #10b981;
    --error: #ef4444;
    --warning: #f59e0b;
    --info: #3b82f6;
    --border-radius: 8px;
    --font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    --transition-fast: all 0.15s ease-in-out;
}

/* Reset and Base Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body.mobooking-body {
    font-family: var(--font-family);
    line-height: 1.6;
    color: var(--foreground);
    background: var(--background-color);
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Container Styles */
.mobooking-container {
    min-height: 100vh;
    padding: 1rem;
    background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.05) 0%, var(--background-color) 100%);
}

.mobooking-form-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    background: var(--card);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}

/* Header Styles */
.mobooking-header {
    background: linear-gradient(135deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 80%, black));
    color: white;
    padding: 2rem;
    text-align: center;
    position: relative;
}

.mobooking-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    pointer-events: none;
}

.mobooking-header h1 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    position: relative;
    z-index: 1;
}

.mobooking-header .business-name {
    font-size: 1.125rem;
    opacity: 0.9;
    margin-top: 0.5rem;
    position: relative;
    z-index: 1;
}

/* Progress Bar */
.mobooking-progress {
    background: var(--muted);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid var(--border);
}

.mobooking-progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 600px;
    margin: 0 auto;
    position: relative;
}

.mobooking-progress-line {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--border);
    z-index: 1;
    border-radius: 1.5px;
}

.mobooking-progress-line-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 80%, white));
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    width: 20%;
    border-radius: 1.5px;
}

.mobooking-progress-step {
    position: relative;
    z-index: 2;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: white;
    border: 3px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.125rem;
    transition: var(--transition);
    cursor: pointer;
}

.mobooking-progress-step.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
    transform: scale(1.1);
    box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.2);
}

.mobooking-progress-step.completed {
    background: var(--success);
    color: white;
    border-color: var(--success);
    transform: scale(1.05);
}

.mobooking-progress-step.completed .step-number {
    display: none;
}

.mobooking-progress-step.completed::after {
    content: '✓';
    font-size: 1.25rem;
    font-weight: 700;
}

/* Step Content */
.mobooking-steps {
    position: relative;
    min-height: 500px;
}

.mobooking-step {
    display: none;
    padding: 2rem;
    opacity: 0;
    transform: translateX(20px);
    transition: var(--transition);
    animation: slideIn 0.4s ease-out forwards;
}

.mobooking-step.active {
    display: block;
    opacity: 1;
    transform: translateX(0);
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.mobooking-step-title {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--foreground);
}

.mobooking-step-title i {
    color: var(--primary-color);
    font-size: 1.5rem;
}

.mobooking-step-description {
    color: var(--muted-foreground);
    margin-bottom: 2rem;
    font-size: 1.125rem;
    line-height: 1.7;
}

/* Layout for steps with sidebar */
.mobooking-step-with-sidebar {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
    align-items: start;
}

.mobooking-step-main {
    min-height: 400px;
}

/* Form Elements */
.mobooking-form-section {
    margin-bottom: 2rem;
}

.mobooking-form-section h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--foreground);
    border-bottom: 2px solid var(--border);
    padding-bottom: 0.5rem;
}

.mobooking-form-group {
    margin-bottom: 1.5rem;
}

.mobooking-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.mobooking-label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--foreground);
    font-size: 0.9rem;
}

.mobooking-required {
    color: var(--error);
    font-weight: 700;
}

.mobooking-optional {
    color: var(--muted-foreground);
    font-weight: 400;
    font-size: 0.875rem;
}

.mobooking-input,
.mobooking-textarea,
.mobooking-select {
    width: 100%;
    border: 2px solid var(--border);
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: var(--transition-fast);
    background: white;
    font-family: inherit;
}

.mobooking-input:focus,
.mobooking-textarea:focus,
.mobooking-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    transform: translateY(-1px);
}

.mobooking-input:invalid,
.mobooking-textarea:invalid,
.mobooking-select:invalid {
    border-color: var(--error);
}

.mobooking-textarea {
    min-height: 120px;
    resize: vertical;
    font-family: inherit;
}

.mobooking-select {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

/* Buttons */
.mobooking-form-actions {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border);
}

.mobooking-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    border: 2px solid transparent;
    border-radius: var(--border-radius);
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition-fast);
    text-decoration: none;
    min-width: 140px;
    font-family: inherit;
    position: relative;
    overflow: hidden;
}

.mobooking-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.6s;
}

.mobooking-btn:hover::before {
    left: 100%;
}

.mobooking-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.mobooking-btn:disabled::before {
    display: none;
}

.mobooking-btn-primary {
    background: linear-gradient(135deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 80%, black));
    color: white;
    border-color: var(--primary-color);
}

.mobooking-btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, color-mix(in srgb, var(--primary-color) 90%, black), color-mix(in srgb, var(--primary-color) 70%, black));
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.mobooking-btn-primary:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: var(--shadow);
}

.mobooking-btn-secondary {
    background: var(--muted);
    color: var(--foreground);
    border-color: var(--border);
}

.mobooking-btn-secondary:hover:not(:disabled) {
    background: color-mix(in srgb, var(--muted) 80%, black);
    border-color: var(--muted-foreground);
    transform: translateY(-2px);
    box-shadow: var(--shadow);
}

/* Service Cards */
.mobooking-services-grid {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
}

.mobooking-service-card {
    border: 2px solid var(--border);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    cursor: pointer;
    transition: var(--transition);
    background: white;
    position: relative;
    overflow: hidden;
}

.mobooking-service-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-color), color-mix(in srgb, var(--primary-color) 80%, white));
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.mobooking-service-card:hover {
    border-color: var(--primary-color);
    box-shadow: var(--shadow-lg);
    transform: translateY(-4px);
}

.mobooking-service-card:hover::before {
    transform: scaleX(1);
}

.mobooking-service-card.selected {
    border-color: var(--primary-color);
    background: rgba(var(--primary-rgb), 0.05);
    box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.1);
}

.mobooking-service-card.selected::before {
    transform: scaleX(1);
}

.mobooking-service-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.mobooking-service-icon {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius);
    background: rgba(var(--primary-rgb), 0.1);
    color: var(--primary-color);
    font-size: 1.5rem;
    overflow: hidden;
    flex-shrink: 0;
}

.mobooking-service-icon img,
.mobooking-service-icon svg {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.mobooking-service-icon svg path {
    fill: currentColor;
}

.mobooking-service-info {
    flex: 1;
}

.mobooking-service-name {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: var(--foreground);
}

.mobooking-service-price {
    font-size: 1.375rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-top: 0.5rem;
}

.mobooking-service-description {
    color: var(--muted-foreground);
    margin-bottom: 1rem;
    line-height: 1.6;
}

.mobooking-service-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--muted-foreground);
}

.mobooking-service-meta span {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.mobooking-service-meta i {
    font-size: 0.75rem;
    opacity: 0.8;
}

/* Hidden checkbox for services */
.service-checkbox {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

/* Sidebar */
.mobooking-sidebar {
    background: var(--muted);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    height: fit-content;
    position: sticky;
    top: 1rem;
    border: 1px solid var(--border);
}

.mobooking-sidebar-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--foreground);
}

.mobooking-sidebar-title i {
    color: var(--primary-color);
}

.mobooking-summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border);
}

.mobooking-summary-item:last-child {
    border-bottom: none;
}

.mobooking-summary-total {
    font-weight: 700;
    font-size: 1.25rem;
    color: var(--primary-color);
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Review Section */
.mobooking-review-section {
    margin-bottom: 2rem;
}

.mobooking-review-group {
    margin-bottom: 1.5rem;
    padding: 1.5rem;
    background: var(--muted);
    border-radius: var(--border-radius);
    border: 1px solid var(--border);
}

.mobooking-review-group h4 {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--foreground);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mobooking-review-content {
    color: var(--muted-foreground);
    line-height: 1.6;
}

.mobooking-review-content p {
    margin-bottom: 0.5rem;
}

.mobooking-review-content strong {
    color: var(--foreground);
}

/* Discount Section */
.mobooking-discount-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: rgba(var(--primary-rgb), 0.05);
    border-radius: var(--border-radius);
    border: 1px solid rgba(var(--primary-rgb), 0.2);
}

.mobooking-discount-section h4 {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--foreground);
}

/* Pricing Summary */
.mobooking-pricing-summary {
    background: var(--muted);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border);
}

.mobooking-pricing-summary .mobooking-summary-item {
    font-size: 1.125rem;
    font-weight: 500;
}

.mobooking-pricing-summary .mobooking-summary-total {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
}

.discount-applied {
    color: var(--success) !important;
}

/* Terms Section */
.mobooking-terms-section {
    margin-bottom: 2rem;
}

.mobooking-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
    font-size: 0.9rem;
    line-height: 1.6;
}

.mobooking-checkbox-label input[type="checkbox"] {
    width: 1.25rem;
    height: 1.25rem;
    margin: 0;
    cursor: pointer;
    flex-shrink: 0;
}

.mobooking-checkbox-label a {
    color: var(--primary-color);
    text-decoration: none;
}

.mobooking-checkbox-label a:hover {
    text-decoration: underline;
}

/* Feedback Messages */
.mobooking-feedback {
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    display: none;
    border: 1px solid;
    font-weight: 500;
}

.mobooking-feedback.success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
    border-color: rgba(16, 185, 129, 0.3);
}

.mobooking-feedback.error {
    background: rgba(239, 68, 68, 0.1);
    color: var(--error);
    border-color: rgba(239, 68, 68, 0.3);
}

.mobooking-feedback.info {
    background: rgba(var(--primary-rgb), 0.1);
    color: var(--primary-color);
    border-color: rgba(var(--primary-rgb), 0.3);
}

.mobooking-feedback.warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
    border-color: rgba(245, 158, 11, 0.3);
}

/* Loading State */
.mobooking-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 2rem;
    color: var(--muted-foreground);
    font-size: 1.125rem;
}

.mobooking-spinner {
    width: 24px;
    height: 24px;
    border: 3px solid var(--border);
    border-top: 3px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Success Page */
.mobooking-success {
    text-align: center;
    padding: 3rem 2rem;
}

.mobooking-success-icon {
    font-size: 5rem;
    color: var(--success);
    margin-bottom: 1.5rem;
    animation: bounceIn 0.6s ease-out;
}

@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); opacity: 1; }
}

.mobooking-success h2 {
    font-size: 2.25rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: var(--foreground);
}

.mobooking-success p {
    font-size: 1.125rem;
    color: var(--muted-foreground);
    margin-bottom: 2rem;
}

.mobooking-success-details {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    border-radius: var(--border-radius);
    padding: 2rem;
    margin: 2rem 0;
    text-align: left;
}

.mobooking-success-details .success-info p {
    margin-bottom: 0.75rem;
    font-size: 1rem;
    color: var(--foreground);
}

.mobooking-success-details .success-info strong {
    color: var(--success);
}

/* Contact Information */
.mobooking-contact-info {
    background: var(--muted);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-top: 2rem;
    border: 1px solid var(--border);
}

.mobooking-contact-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--foreground);
}

.mobooking-contact-info > p {
    color: var(--muted-foreground);
    margin-bottom: 1rem;
}

.contact-details {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.contact-item strong {
    color: var(--foreground);
}

.contact-item a {
    color: var(--primary-color);
    text-decoration: none;
}

.contact-item a:hover {
    text-decoration: underline;
}

/* Debug Sidebar */
#mobooking-debug-sidebar {
    position: fixed;
    top: 0;
    right: 0;
    width: 380px;
    height: 100vh;
    background: #23282d;
    color: #f0f0f0;
    padding: 1.5rem;
    z-index: 999999;
    overflow-y: auto;
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    font-size: 0.8rem;
    line-height: 1.4;
    box-shadow: -4px 0 10px rgba(0, 0, 0, 0.3);
    border-left: 3px solid var(--primary-color);
}

#mobooking-debug-sidebar h4 {
    color: #6495ED;
    margin-bottom: 1rem;
    border-bottom: 2px solid #444;
    padding-bottom: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
}

#mobooking-debug-sidebar .debug-section {
    margin-bottom: 1.5rem;
}

#mobooking-debug-sidebar .debug-section h5 {
    color: #90EE90;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    font-weight: 500;
}

#mobooking-debug-sidebar .debug-content {
    background: #1e1e1e;
    padding: 0.75rem;
    border-radius: 4px;
    border: 1px solid #333;
    white-space: pre-wrap;
    word-break: break-word;
    max-height: 200px;
    overflow-y: auto;
}

#mobooking-debug-sidebar .debug-content ul {
    list-style: none;
    padding: 0;
}

#mobooking-debug-sidebar .debug-content li {
    padding: 0.25rem 0;
    font-size: 0.75rem;
}

#mobooking-debug-sidebar .status-ok {
    color: #7CFC00;
    font-weight: 600;
}

#mobooking-debug-sidebar .status-error {
    color: #FF6347;
    font-weight: 600;
}

#mobooking-debug-sidebar .status-warn {
    color: #FFA500;
    font-weight: 600;
}

#debug-js-logs {
    font-size: 0.7rem;
    max-height: 300px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #555 #333;
}

#debug-js-logs::-webkit-scrollbar {
    width: 6px;
}

#debug-js-logs::-webkit-scrollbar-track {
    background: #333;
}

#debug-js-logs::-webkit-scrollbar-thumb {
    background: #555;
    border-radius: 3px;
}

/* Utility Classes */
.hidden {
    display: none !important;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

.text-center {
    text-align: center;
}

.text-left {
    text-align: left;
}

.text-right {
    text-align: right;
}

.mb-0 { margin-bottom: 0 !important; }
.mb-1 { margin-bottom: 0.25rem !important; }
.mb-2 { margin-bottom: 0.5rem !important; }
.mb-3 { margin-bottom: 0.75rem !important; }

    </style>
</head>

<body class="mobooking-body">
    <div class="mobooking-container">
        <div class="mobooking-form-wrapper">
            <!-- Header -->
            <?php if (!$is_embed): ?>
            <div class="mobooking-header">
                <h1><?php echo esc_html($form_config['form_header']); ?></h1>
                <?php if (!empty($business_info['name'])): ?>
                    <p class="business-name"><?php echo esc_html($business_info['name']); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Progress Bar -->
            <?php if ($form_config['show_progress_bar']): ?>
            <div class="mobooking-progress">
                <div class="mobooking-progress-steps">
                    <div class="mobooking-progress-line">
                        <div class="mobooking-progress-line-fill"></div>
                    </div>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="mobooking-progress-step <?php echo $i === 1 ? 'active' : ''; ?>" data-step="<?php echo $i; ?>">
                        <span class="step-number"><?php echo $i; ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Steps Container -->
            <div class="mobooking-steps">
                <!-- Step 1: Location Check -->
                <div class="mobooking-step active" data-step="1">
                    <h2 class="mobooking-step-title">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <?php echo esc_html($form_config['step_1_title']); ?>
                    </h2>
                    <p class="mobooking-step-description">
                        <?php esc_html_e('Please enter your location to check if we service your area.', 'mobooking'); ?>
                    </p>
                    
                    <div id="mobooking-location-feedback" class="mobooking-feedback" role="alert"></div>
                    
                    <?php if ($form_config['enable_location_check']): ?>
                    <form id="mobooking-location-form" novalidate>
                        <div class="mobooking-form-row">
                            <div class="mobooking-form-group">
                                <label for="mobooking-zip" class="mobooking-label">
                                    <?php esc_html_e('ZIP/Postal Code', 'mobooking'); ?>
                                    <span class="mobooking-required" aria-label="required">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="mobooking-zip" 
                                    name="zip_code" 
                                    class="mobooking-input" 
                                    placeholder="<?php esc_attr_e('Enter your ZIP code', 'mobooking'); ?>" 
                                    required 
                                    aria-describedby="zip-error"
                                >
                                <div id="zip-error" class="sr-only" aria-live="polite"></div>
                            </div>
                            <div class="mobooking-form-group">
                                <label for="mobooking-country" class="mobooking-label">
                                    <?php esc_html_e('Country', 'mobooking'); ?>
                                    <span class="mobooking-required" aria-label="required">*</span>
                                </label>
                                <select id="mobooking-country" name="country_code" class="mobooking-select" required>
                                    <option value=""><?php esc_html_e('Select Country', 'mobooking'); ?></option>
                                    <option value="US"><?php esc_html_e('United States', 'mobooking'); ?></option>
                                    <option value="CA"><?php esc_html_e('Canada', 'mobooking'); ?></option>
                                    <option value="GB"><?php esc_html_e('United Kingdom', 'mobooking'); ?></option>
                                    <option value="AU"><?php esc_html_e('Australia', 'mobooking'); ?></option>
                                    <option value="DE"><?php esc_html_e('Germany', 'mobooking'); ?></option>
                                    <option value="FR"><?php esc_html_e('France', 'mobooking'); ?></option>
                                    <option value="SE"><?php esc_html_e('Sweden', 'mobooking'); ?></option>
                                    <option value="NO"><?php esc_html_e('Norway', 'mobooking'); ?></option>
                                    <option value="DK"><?php esc_html_e('Denmark', 'mobooking'); ?></option>
                                    <option value="FI"><?php esc_html_e('Finland', 'mobooking'); ?></option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mobooking-form-actions">
                            <div></div>
                            <button type="submit" class="mobooking-btn mobooking-btn-primary">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                <?php esc_html_e('Check Availability', 'mobooking'); ?>
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="mobooking-form-actions">
                        <div></div>
                        <button type="button" class="mobooking-btn mobooking-btn-primary" data-step-next="2">
                            <?php esc_html_e('Continue', 'mobooking'); ?>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Step 2: Service Selection -->
                <div class="mobooking-step" data-step="2">
                    <h2 class="mobooking-step-title">
                        <i class="fas fa-broom" aria-hidden="true"></i>
                        <?php echo esc_html($form_config['step_2_title']); ?>
                    </h2>
                    <p class="mobooking-step-description">
                        <?php esc_html_e('Choose the service you would like to book.', 'mobooking'); ?>
                    </p>
                    
                    <div id="mobooking-services-feedback" class="mobooking-feedback" role="alert"></div>
                    
                    <div id="mobooking-services-container" class="mobooking-services-grid">
                        <?php if (!empty($preloaded_services_data)): ?>
                            <?php foreach ($preloaded_services_data as $service): ?>
                            <div class="mobooking-service-card" data-service-id="<?php echo esc_attr($service['service_id']); ?>">
                                <div class="mobooking-service-header">
                                    <div class="mobooking-service-icon">
                                        <?php if (!empty($service['icon_svg_content'])): ?>
                                            <?php echo $service['icon_svg_content']; ?>
                                        <?php elseif (filter_var($service['icon'], FILTER_VALIDATE_URL)): ?>
                                            <img src="<?php echo esc_url($service['icon']); ?>" alt="<?php echo esc_attr($service['name']); ?>">
                                        <?php else: ?>
                                            <i class="<?php echo esc_attr($service['icon']); ?>" aria-hidden="true"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mobooking-service-info">
                                        <h3 class="mobooking-service-name"><?php echo esc_html($service['name']); ?></h3>
                                        <?php if ($form_config['show_pricing']): ?>
                                            <div class="mobooking-service-price">
                                                <?php echo esc_html($currency['symbol'] . number_format($service['price'], 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if (!empty($service['description'])): ?>
                                <div class="mobooking-service-description">
                                    <?php echo wp_kses_post($service['description']); ?>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mobooking-service-meta">
                                    <?php if ($service['duration'] > 0): ?>
                                        <span class="duration">
                                            <i class="fas fa-clock" aria-hidden="true"></i>
                                            <?php echo sprintf(__('%d min', 'mobooking'), $service['duration']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($service['category'])): ?>
                                        <span class="category">
                                            <i class="fas fa-tag" aria-hidden="true"></i>
                                            <?php echo esc_html($service['category']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <input type="checkbox" 
                                       class="service-checkbox" 
                                       id="service-<?php echo esc_attr($service['service_id']); ?>" 
                                       value="<?php echo esc_attr($service['service_id']); ?>"
                                       data-name="<?php echo esc_attr($service['name']); ?>"
                                       data-price="<?php echo esc_attr($service['price']); ?>"
                                       data-duration="<?php echo esc_attr($service['duration']); ?>"
                                       style="display: none;">
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="mobooking-loading">
                                <div class="mobooking-spinner" aria-hidden="true"></div>
                                <?php esc_html_e('Loading available services...', 'mobooking'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mobooking-form-actions">
                        <button type="button" class="mobooking-btn mobooking-btn-secondary" data-step-back="1">
                            <i class="fas fa-arrow-left" aria-hidden="true"></i>
                            <?php esc_html_e('Back', 'mobooking'); ?>
                        </button>
                        <button type="button" class="mobooking-btn mobooking-btn-primary" data-step-next="3" disabled>
                            <?php esc_html_e('Continue', 'mobooking'); ?>
                            <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Service Options -->
                <div class="mobooking-step" data-step="3">
                    <div class="mobooking-step-with-sidebar">
                        <div class="mobooking-step-main">
                            <h2 class="mobooking-step-title">
                                <i class="fas fa-cogs" aria-hidden="true"></i>
                                <?php echo esc_html($form_config['step_3_title']); ?>
                            </h2>
                            <p class="mobooking-step-description">
                                <?php esc_html_e('Customize your service with additional options.', 'mobooking'); ?>
                            </p>
                            
                            <div id="mobooking-options-feedback" class="mobooking-feedback" role="alert"></div>
                            
                            <div id="mobooking-service-options">
                                <div class="mobooking-loading">
                                    <div class="mobooking-spinner" aria-hidden="true"></div>
                                    <?php esc_html_e('Loading service options...', 'mobooking'); ?>
                                </div>
                            </div>
                            
                            <div class="mobooking-form-actions">
                                <button type="button" class="mobooking-btn mobooking-btn-secondary" data-step-back="2">
                                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                    <?php esc_html_e('Back', 'mobooking'); ?>
                                </button>
                                <button type="button" class="mobooking-btn mobooking-btn-primary" data-step-next="4">
                                    <?php esc_html_e('Continue', 'mobooking'); ?>
                                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mobooking-sidebar">
                            <h3 class="mobooking-sidebar-title">
                                <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                                <?php esc_html_e('Summary', 'mobooking'); ?>
                            </h3>
                            <div id="mobooking-summary-content">
                                <p><?php esc_html_e('Configure options to see pricing', 'mobooking'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Customer Details -->
                <div class="mobooking-step" data-step="4">
                    <div class="mobooking-step-with-sidebar">
                        <div class="mobooking-step-main">
                            <h2 class="mobooking-step-title">
                                <i class="fas fa-user" aria-hidden="true"></i>
                                <?php echo esc_html($form_config['step_4_title']); ?>
                            </h2>
                            <p class="mobooking-step-description">
                                <?php esc_html_e('Please provide your contact information and service details.', 'mobooking'); ?>
                            </p>
                            
                            <div id="mobooking-details-feedback" class="mobooking-feedback" role="alert"></div>
                            
                            <form id="mobooking-details-form" novalidate>
                                <div class="mobooking-form-section">
                                    <h3><?php esc_html_e('Contact Information', 'mobooking'); ?></h3>
                                    
                                    <div class="mobooking-form-row">
                                        <div class="mobooking-form-group">
                                            <label for="customer-name" class="mobooking-label">
                                                <?php esc_html_e('Full Name', 'mobooking'); ?>
                                                <span class="mobooking-required" aria-label="required">*</span>
                                            </label>
                                            <input type="text" id="customer-name" name="customer_name" class="mobooking-input" required
                                                   placeholder="<?php esc_attr_e('Enter your full name', 'mobooking'); ?>">
                                        </div>
                                        <div class="mobooking-form-group">
                                            <label for="customer-email" class="mobooking-label">
                                                <?php esc_html_e('Email Address', 'mobooking'); ?>
                                                <span class="mobooking-required" aria-label="required">*</span>
                                            </label>
                                            <input type="email" id="customer-email" name="customer_email" class="mobooking-input" required
                                                   placeholder="<?php esc_attr_e('Enter your email address', 'mobooking'); ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mobooking-form-row">
                                        <div class="mobooking-form-group">
                                            <label for="customer-phone" class="mobooking-label">
                                                <?php esc_html_e('Phone Number', 'mobooking'); ?>
                                                <span class="mobooking-required" aria-label="required">*</span>
                                            </label>
                                            <input type="tel" id="customer-phone" name="customer_phone" class="mobooking-input" required
                                                   placeholder="<?php esc_attr_e('Enter your phone number', 'mobooking'); ?>">
                                        </div>
                                        <div class="mobooking-form-group">
                                            <label for="service-address" class="mobooking-label">
                                                <?php esc_html_e('Service Address', 'mobooking'); ?>
                                                <span class="mobooking-required" aria-label="required">*</span>
                                            </label>
                                            <input type="text" id="service-address" name="service_address" class="mobooking-input" required
                                                   placeholder="<?php esc_attr_e('Enter the service address', 'mobooking'); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mobooking-form-section">
                                    <h3><?php esc_html_e('Service Details', 'mobooking'); ?></h3>
                                    
                                    <div class="mobooking-form-row">
                                        <div class="mobooking-form-group">
                                            <label for="preferred-date" class="mobooking-label">
                                                <?php esc_html_e('Preferred Date', 'mobooking'); ?>
                                                <span class="mobooking-required" aria-label="required">*</span>
                                            </label>
                                            <input type="date" id="preferred-date" name="preferred_date" class="mobooking-input" required
                                                   min="<?php echo esc_attr(date('Y-m-d')); ?>">
                                        </div>
                                        <div class="mobooking-form-group">
                                            <label for="preferred-time" class="mobooking-label">
                                                <?php esc_html_e('Preferred Time', 'mobooking'); ?>
                                                <span class="mobooking-required" aria-label="required">*</span>
                                            </label>
                                            <input type="time" id="preferred-time" name="preferred_time" class="mobooking-input" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mobooking-form-group">
                                        <label for="special-instructions" class="mobooking-label">
                                            <?php esc_html_e('Special Instructions', 'mobooking'); ?>
                                            <span class="mobooking-optional">(<?php esc_html_e('Optional', 'mobooking'); ?>)</span>
                                        </label>
                                        <textarea id="special-instructions" name="special_instructions" class="mobooking-textarea" 
                                                  placeholder="<?php esc_attr_e('Any special requirements or notes...', 'mobooking'); ?>"></textarea>
                                    </div>
                                </div>
                            </form>
                            
                            <div class="mobooking-form-actions">
                                <button type="button" class="mobooking-btn mobooking-btn-secondary" data-step-back="3">
                                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                    <?php esc_html_e('Back', 'mobooking'); ?>
                                </button>
                                <button type="button" class="mobooking-btn mobooking-btn-primary" data-step-next="5">
                                    <?php esc_html_e('Continue', 'mobooking'); ?>
                                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mobooking-sidebar">
                            <h3 class="mobooking-sidebar-title">
                                <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                                <?php esc_html_e('Order Summary', 'mobooking'); ?>
                            </h3>
                            <div id="mobooking-summary-content-step4">
                                <!-- Summary content populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Review & Confirm -->
                <div class="mobooking-step" data-step="5">
                    <div class="mobooking-step-with-sidebar">
                        <div class="mobooking-step-main">
                            <h2 class="mobooking-step-title">
                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                                <?php echo esc_html($form_config['step_5_title']); ?>
                            </h2>
                            <p class="mobooking-step-description">
                                <?php esc_html_e('Please review your booking details before confirming.', 'mobooking'); ?>
                            </p>
                            
                            <div id="mobooking-review-feedback" class="mobooking-feedback" role="alert"></div>
                            
                            <!-- Booking Review Details -->
                            <div id="mobooking-review-details" class="mobooking-review-section">
                                <div class="mobooking-review-group">
                                    <h4><?php esc_html_e('Customer Information', 'mobooking'); ?></h4>
                                    <div id="customer-info-review" class="mobooking-review-content">
                                        <!-- Populated by JavaScript -->
                                    </div>
                                </div>
                                
                                <div class="mobooking-review-group">
                                    <h4><?php esc_html_e('Service Details', 'mobooking'); ?></h4>
                                    <div id="service-details-review" class="mobooking-review-content">
                                        <!-- Populated by JavaScript -->
                                    </div>
                                </div>
                                
                                <div class="mobooking-review-group">
                                    <h4><?php esc_html_e('Booking Information', 'mobooking'); ?></h4>
                                    <div id="booking-info-review" class="mobooking-review-content">
                                        <!-- Populated by JavaScript -->
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($form_config['allow_discount_codes']): ?>
                            <!-- Discount Code Section -->
                            <div class="mobooking-discount-section">
                                <h4><?php esc_html_e('Discount Code', 'mobooking'); ?></h4>
                                <div class="mobooking-form-row">
                                    <div class="mobooking-form-group">
                                        <input type="text" id="discount-code" name="discount_code"
                                               placeholder="<?php esc_attr_e('Enter discount code', 'mobooking'); ?>" 
                                               class="mobooking-input">
                                    </div>
                                    <div class="mobooking-form-group">
                                        <button type="button" id="apply-discount-btn" class="mobooking-btn mobooking-btn-secondary">
                                            <?php esc_html_e('Apply', 'mobooking'); ?>
                                        </button>
                                    </div>
                                </div>
                                <div id="discount-feedback" class="mobooking-feedback"></div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Pricing Summary -->
                            <div class="mobooking-pricing-summary">
                                <div class="mobooking-summary-item">
                                    <span><?php esc_html_e('Subtotal:', 'mobooking'); ?></span>
                                    <span id="pricing-subtotal"><?php echo esc_html($currency['symbol']); ?>0.00</span>
                                </div>
                                <?php if ($form_config['allow_discount_codes']): ?>
                                <div class="mobooking-summary-item discount-applied hidden">
                                    <span><?php esc_html_e('Discount:', 'mobooking'); ?></span>
                                    <span id="pricing-discount">-<?php echo esc_html($currency['symbol']); ?>0.00</span>
                                </div>
                                <?php endif; ?>
                                <div class="mobooking-summary-total">
                                    <span><?php esc_html_e('Total:', 'mobooking'); ?></span>
                                    <span id="pricing-total"><?php echo esc_html($currency['symbol']); ?>0.00</span>
                                </div>
                            </div>
                            
                            <?php if (!empty($form_config['terms_conditions_url'])): ?>
                            <div class="mobooking-terms-section">
                                <label class="mobooking-checkbox-label">
                                    <input type="checkbox" id="terms-acceptance" name="terms_acceptance" required>
                                    <span class="checkmark"></span>
                                    <?php echo sprintf(
                                        __('I agree to the <a href="%s" target="_blank" rel="noopener">Terms and Conditions</a>', 'mobooking'),
                                        esc_url($form_config['terms_conditions_url'])
                                    ); ?>
                                </label>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mobooking-form-actions">
                                <button type="button" class="mobooking-btn mobooking-btn-secondary" data-step-back="4">
                                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                                    <?php esc_html_e('Back', 'mobooking'); ?>
                                </button>
                                <button type="button" id="final-submit-btn" class="mobooking-btn mobooking-btn-primary">
                                    <i class="fas fa-check" aria-hidden="true"></i>
                                    <?php esc_html_e('Confirm Booking', 'mobooking'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mobooking-sidebar">
                            <h3 class="mobooking-sidebar-title">
                                <i class="fas fa-receipt" aria-hidden="true"></i>
                                <?php esc_html_e('Final Summary', 'mobooking'); ?>
                            </h3>
                            <div id="mobooking-final-summary">
                                <!-- Final summary populated by JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 6: Success -->
                <div class="mobooking-step" data-step="6">
                    <div class="mobooking-success">
                        <div class="mobooking-success-icon">
                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                        </div>
                        <h2><?php esc_html_e('Booking Confirmed!', 'mobooking'); ?></h2>
                        <p><?php echo esc_html($form_config['success_message']); ?></p>
                        
                        <div id="success-details" class="mobooking-success-details">
                            <!-- Success details populated by JavaScript -->
                        </div>
                        
                        <div class="mobooking-form-actions">
                            <button type="button" class="mobooking-btn mobooking-btn-primary" onclick="location.reload();">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                                <?php esc_html_e('Book Another Service', 'mobooking'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information (if available) -->
    <?php if (!empty($business_info['phone']) || !empty($business_info['email'])): ?>
    <div class="mobooking-contact-info">
        <h3><?php esc_html_e('Need Help?', 'mobooking'); ?></h3>
        <p><?php esc_html_e('Contact us directly:', 'mobooking'); ?></p>
        <div class="contact-details">
            <?php if (!empty($business_info['phone'])): ?>
                <div class="contact-item">
                    <strong><?php esc_html_e('Phone:', 'mobooking'); ?></strong>
                    <a href="tel:<?php echo esc_attr($business_info['phone']); ?>"><?php echo esc_html($business_info['phone']); ?></a>
                </div>
            <?php endif; ?>
            <?php if (!empty($business_info['email'])): ?>
                <div class="contact-item">
                    <strong><?php esc_html_e('Email:', 'mobooking'); ?></strong>
                    <a href="mailto:<?php echo esc_attr($business_info['email']); ?>"><?php echo esc_html($business_info['email']); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Debug Sidebar -->
    <div id="mobooking-debug-sidebar" style="<?php echo $mobooking_is_debug_mode_active ? 'display: block !important;' : 'display: none;'; ?>">
        <h4>Debug Information</h4>

        <div class="debug-section">
            <h5>Form Submission Status</h5>
            <div id="debug-submission-status" class="debug-content">Ready</div>
        </div>

        <div class="debug-section">
            <h5>Database Status</h5>
            <div id="debug-db-status" class="debug-content">
                <?php
                if ($mobooking_is_debug_mode_active) {
                    global $wpdb;
                    if ($wpdb->ready) {
                        $db_check = $wpdb->get_var("SELECT 1");
                        if ($db_check == 1) {
                            echo '<span class="status-ok">Connected</span> (WPDB ready)';
                        } else {
                            echo '<span class="status-error">Connection Issue</span>';
                            if ($wpdb->last_error) {
                                echo '<br>Error: ' . esc_html($wpdb->last_error);
                            }
                        }
                    } else {
                        echo '<span class="status-error">WPDB Not Ready</span>';
                    }
                } else {
                    echo 'N/A';
                }
                ?>
            </div>
        </div>

        <div class="debug-section">
            <h5>Table Checks</h5>
            <div id="debug-table-checks" class="debug-content">
                <?php
                if ($mobooking_is_debug_mode_active && class_exists('\MoBooking\Classes\Database')) {
                    global $wpdb;
                    $table_keys = ['services', 'service_options', 'bookings', 'customers', 'discounts', 'areas', 'availability_rules', 'availability_exceptions', 'tenant_settings', 'booking_meta'];
                    $output = '<ul>';
                    foreach ($table_keys as $key) {
                        $table_name = \MoBooking\Classes\Database::get_table_name($key);
                        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) == $table_name) {
                            $output .= '<li>' . esc_html($key) . ': <span class="status-ok">Exists</span></li>';
                        } else {
                            $output .= '<li>' . esc_html($key) . ': <span class="status-error">MISSING</span></li>';
                        }
                    }
                    $output .= '</ul>';
                    echo $output;
                } elseif ($mobooking_is_debug_mode_active) {
                    echo '<span class="status-error">Database class not found.</span>';
                } else {
                    echo 'N/A';
                }
                ?>
            </div>
        </div>

        <div class="debug-section">
            <h5>Client-Side Logs</h5>
            <pre id="debug-js-logs" class="debug-content">Waiting for JavaScript...</pre>
        </div>
    </div>

    <!-- Hidden Form Data -->
    <input type="hidden" id="tenant-id" value="<?php echo esc_attr($tenant_id); ?>">
    <input type="hidden" id="form-nonce" value="<?php echo esc_attr($form_nonce); ?>">
    <input type="hidden" id="currency-symbol" value="<?php echo esc_attr($currency['symbol']); ?>">
    <input type="hidden" id="currency-code" value="<?php echo esc_attr($currency['code']); ?>">
    <input type="hidden" id="show-pricing" value="<?php echo esc_attr($form_config['show_pricing'] ? '1' : '0'); ?>">
    <input type="hidden" id="allow-discount-codes" value="<?php echo esc_attr($form_config['allow_discount_codes'] ? '1' : '0'); ?>">
    <input type="hidden" id="enable-location-check" value="<?php echo esc_attr($form_config['enable_location_check'] ? '1' : '0'); ?>">

    <script type="text/javascript">
        // Global variables for JavaScript
        window.MOB_PRELOADED_SERVICES = <?php echo wp_json_encode($preloaded_services_data); ?>;
        window.MOB_FORM_CONFIG = <?php echo wp_json_encode($form_config); ?>;
        window.MOB_CURRENCY = <?php echo wp_json_encode($currency); ?>;
        window.MOB_BUSINESS_INFO = <?php echo wp_json_encode($business_info); ?>;
        window.MOB_TENANT_ID = <?php echo intval($tenant_id); ?>;
        window.MOB_FORM_NONCE = <?php echo wp_json_encode($form_nonce); ?>;
        window.MOB_AJAX_URL = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
        window.MOB_IS_EMBED = <?php echo $is_embed ? 'true' : 'false'; ?>;
        window.MOB_DEBUG_MODE = <?php echo $mobooking_is_debug_mode_active ? 'true' : 'false'; ?>;

        // Localized strings
        window.MOB_I18N = {
            'loading': <?php echo wp_json_encode(__('Loading...', 'mobooking')); ?>,
            'error': <?php echo wp_json_encode(__('An error occurred. Please try again.', 'mobooking')); ?>,
            'success': <?php echo wp_json_encode(__('Success!', 'mobooking')); ?>,
            'required_field': <?php echo wp_json_encode(__('This field is required.', 'mobooking')); ?>,
            'invalid_email': <?php echo wp_json_encode(__('Please enter a valid email address.', 'mobooking')); ?>,
            'select_service': <?php echo wp_json_encode(__('Please select at least one service.', 'mobooking')); ?>,
            'submitting': <?php echo wp_json_encode(__('Submitting your booking...', 'mobooking')); ?>,
            'booking_confirmed': <?php echo wp_json_encode(__('Booking confirmed!', 'mobooking')); ?>,
            'booking_failed': <?php echo wp_json_encode(__('Booking failed. Please try again.', 'mobooking')); ?>,
            'discount_applied': <?php echo wp_json_encode(__('Discount applied successfully!', 'mobooking')); ?>,
            'invalid_discount': <?php echo wp_json_encode(__('Invalid discount code.', 'mobooking')); ?>,
            'network_error': <?php echo wp_json_encode(__('Network error. Please check your connection.', 'mobooking')); ?>,
            'terms_required': <?php echo wp_json_encode(__('Please accept the terms and conditions.', 'mobooking')); ?>,
            'zip_required': <?php echo wp_json_encode(__('Please enter your ZIP code.', 'mobooking')); ?>,
            'country_required': <?php echo wp_json_encode(__('Please select your country.', 'mobooking')); ?>,
            'name_required': <?php echo wp_json_encode(__('Please enter your name.', 'mobooking')); ?>,
            'email_required': <?php echo wp_json_encode(__('Please enter your email address.', 'mobooking')); ?>,
            'phone_required': <?php echo wp_json_encode(__('Please enter your phone number.', 'mobooking')); ?>,
            'address_required': <?php echo wp_json_encode(__('Please enter your service address.', 'mobooking')); ?>,
            'date_required': <?php echo wp_json_encode(__('Please select a preferred date.', 'mobooking')); ?>,
            'time_required': <?php echo wp_json_encode(__('Please select a preferred time.', 'mobooking')); ?>,
            'checking_availability': <?php echo wp_json_encode(__('Checking availability...', 'mobooking')); ?>,
            'service_available': <?php echo wp_json_encode(__('Service is available in your area!', 'mobooking')); ?>,
            'service_not_available': <?php echo wp_json_encode(__('Sorry, service is not available in your area.', 'mobooking')); ?>,
            'loading_services': <?php echo wp_json_encode(__('Loading services...', 'mobooking')); ?>,
            'no_services': <?php echo wp_json_encode(__('No services available.', 'mobooking')); ?>,
            'loading_options': <?php echo wp_json_encode(__('Loading options...', 'mobooking')); ?>,
            'no_options': <?php echo wp_json_encode(__('No additional options available.', 'mobooking')); ?>,
            'continue': <?php echo wp_json_encode(__('Continue', 'mobooking')); ?>,
            'back': <?php echo wp_json_encode(__('Back', 'mobooking')); ?>,
            'submit_booking': <?php echo wp_json_encode(__('Submit Booking', 'mobooking')); ?>
        };

        // Initialize booking form when DOM is ready
        jQuery(document).ready(function($) {
            if (typeof initializeBookingForm === 'function') {
                initializeBookingForm();
            }
            
            // Debug logging function
            if (window.MOB_DEBUG_MODE) {
                window.debugLog = function(message) {
                    const debugLogs = document.getElementById('debug-js-logs');
                    if (debugLogs) {
                        const timestamp = new Date().toLocaleTimeString();
                        debugLogs.textContent += `[${timestamp}] ${message}\n`;
                        debugLogs.scrollTop = debugLogs.scrollHeight;
                    }
                    console.log('MoBooking Debug:', message);
                };
                
                window.debugLog('Booking form initialized');
                window.debugLog('Tenant ID: ' + window.MOB_TENANT_ID);
                window.debugLog('Services loaded: ' + window.MOB_PRELOADED_SERVICES.length);
            }
        });
    </script>

    <!-- Enhanced booking form submission with JSON fix -->
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            'use strict';

            // Enhanced JSON encoding with proper escaping
            function safeJsonEncode(data) {
                try {
                    const cleanData = cleanDataForJson(data);
                    const jsonString = JSON.stringify(cleanData);
                    
                    if (window.MOB_DEBUG_MODE) {
                        window.debugLog('JSON encoding: ' + jsonString.substring(0, 100) + '...');
                    }
                    
                    return jsonString;
                } catch (error) {
                    if (window.MOB_DEBUG_MODE) {
                        window.debugLog('JSON encoding failed: ' + error.message);
                    }
                    console.error('JSON encoding failed:', error, data);
                    return null;
                }
            }
            
            // Clean data to prevent JSON encoding issues
            function cleanDataForJson(data) {
                if (typeof data === 'string') {
                    return data
                        .replace(/[\u0000-\u001F\u007F-\u009F]/g, '')
                        .replace(/\\/g, '\\\\')
                        .replace(/"/g, '\\"')
                        .trim();
                } else if (Array.isArray(data)) {
                    return data.map(cleanDataForJson);
                } else if (data && typeof data === 'object') {
                    const cleaned = {};
                    for (const key in data) {
                        if (data.hasOwnProperty(key)) {
                            cleaned[key] = cleanDataForJson(data[key]);
                        }
                    }
                    return cleaned;
                }
                return data;
            }
            
            // Enhanced booking submission function
            window.submitBookingWithJsonFix = function() {
                if (window.MOB_DEBUG_MODE) {
                    window.debugLog('Starting booking submission...');
                }

                const tenantId = $('#tenant-id').val();
                const nonce = $('#form-nonce').val();

                if (!tenantId || !nonce) {
                    console.error('Missing required data:', { tenantId, nonce });
                    alert('Missing required form data. Please refresh the page and try again.');
                    return;
                }

                // Collect selected services
                const selectedServices = [];
                $('.service-checkbox:checked').each(function() {
                    const serviceId = parseInt($(this).val());
                    const serviceName = $(this).data('name');
                    const servicePrice = parseFloat($(this).data('price'));
                    
                    selectedServices.push({
                        service_id: serviceId,
                        name: serviceName,
                        price: servicePrice,
                        configured_options: {}
                    });
                });

                // Collect customer details
                const customerDetails = {
                    name: $('#customer-name').val().toString().trim(),
                    email: $('#customer-email').val().toString().trim(),
                    phone: $('#customer-phone').val().toString().trim(),
                    address: $('#service-address').val().toString().trim(),
                    date: $('#preferred-date').val().toString().trim(),
                    time: $('#preferred-time').val().toString().trim(),
                    instructions: $('#special-instructions').val().toString().trim()
                };

                // Validate required fields
                const requiredFields = ['name', 'email', 'phone', 'address', 'date', 'time'];
                const missingFields = requiredFields.filter(field => !customerDetails[field]);

                if (missingFields.length > 0) {
                    console.error('Missing required fields:', missingFields);
                    alert('Please fill in all required fields: ' + missingFields.join(', '));
                    return;
                }

                // Email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(customerDetails.email)) {
                    console.error('Invalid email format:', customerDetails.email);
                    alert('Please enter a valid email address.');
                    return;
                }

                // Encode JSON safely
                const selectedServicesJson = safeJsonEncode(selectedServices);
                const customerDetailsJson = safeJsonEncode(customerDetails);

                if (!selectedServicesJson || !customerDetailsJson) {
                    console.error('JSON encoding failed');
                    alert('Error processing form data. Please try again.');
                    return;
                }

                // Calculate total
                let total = 0;
                selectedServices.forEach(service => total += service.price);

                // Prepare submission data
                const submissionData = {
                    action: 'mobooking_create_booking',
                    nonce: nonce,
                    tenant_id: tenantId,
                    selected_services: selectedServicesJson,
                    customer_details: customerDetailsJson,
                    discount_info: '',
                    zip_code: $('#mobooking-zip').val() || '',
                    country_code: $('#mobooking-country').val() || '',
                    pricing: JSON.stringify({
                        subtotal: total,
                        discount: 0,
                        total: total
                    })
                };

                if (window.MOB_DEBUG_MODE) {
                    window.debugLog('Submitting booking data...');
                }

                // Submit with enhanced error handling
                $.ajax({
                    url: window.MOB_AJAX_URL,
                    type: 'POST',
                    data: submissionData,
                    timeout: 30000,
                    beforeSend: function() {
                        $('#final-submit-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + window.MOB_I18N.submitting);
                    },
                    success: function(response) {
                        if (window.MOB_DEBUG_MODE) {
                            window.debugLog('Booking submission successful');
                        }
                        
                        if (response.success) {
                            // Show success step
                            $('.mobooking-step').removeClass('active');
                            $('.mobooking-step[data-step="6"]').addClass('active');
                            
                            // Update progress
                            $('.mobooking-progress-step').removeClass('active').addClass('completed');
                            $('.mobooking-progress-line-fill').css('width', '100%');
                            
                            // Show success details
                            $('#success-details').html(`
                                <div class="success-info">
                                    <p><strong>Booking Reference:</strong> ${response.data.booking_reference || 'N/A'}</p>
                                    <p><strong>Total Amount:</strong> ${window.MOB_CURRENCY.symbol}${(response.data.final_total || 0).toFixed(2)}</p>
                                    <p>A confirmation email has been sent to ${customerDetails.email}</p>
                                </div>
                            `);
                        } else {
                            alert('Booking failed: ' + (response.data?.message || 'Unknown error'));
                        }
                    },
                    error: function(xhr, status, error) {
                        if (window.MOB_DEBUG_MODE) {
                            window.debugLog('Booking submission failed: ' + error);
                        }
                        
                        console.error('Booking submission failed:', { xhr, status, error });
                        let errorMessage = 'Booking submission failed.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                            errorMessage = xhr.responseJSON.data.message;
                        }
                        
                        alert(errorMessage);
                    },
                    complete: function() {
                        $('#final-submit-btn').prop('disabled', false).html('<i class="fas fa-check"></i> ' + window.MOB_I18N.submit_booking);
                    }
                });
            };

            // Bind the final submit button
            $(document).on('click', '#final-submit-btn', function(e) {
                e.preventDefault();
                
                // Check terms acceptance if required
                if ($('#terms-acceptance').length && !$('#terms-acceptance').is(':checked')) {
                    alert(window.MOB_I18N.terms_required);
                    return;
                }
                
                submitBookingWithJsonFix();
            });
        });
    </script>

    <?php wp_footer(); ?>
</body>
</html>