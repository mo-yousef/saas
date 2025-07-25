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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="<?php echo esc_url(get_template_directory_uri() . '/assets/css/booking-form-refactored.css'); ?>">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <?php wp_head(); ?>
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
                                    
                                    <div class="mobooking-form-group">
                                        <label for="preferred-datetime" class="mobooking-label">
                                            <?php esc_html_e('Preferred Date & Time', 'mobooking'); ?>
                                            <span class="mobooking-required" aria-label="required">*</span>
                                        </label>
                                        <input type="text" id="preferred-datetime" name="preferred_datetime" class="mobooking-input" required
                                               placeholder="<?php esc_attr_e('Select Date and Time', 'mobooking'); ?>">
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