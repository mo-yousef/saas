<?php
/**
 * Refactored Public Booking Form Template
 * @package MoBooking
 */

if (!defined('ABSPATH')) exit;

// Initialize managers and get tenant information
$tenant_id = get_query_var('mobooking_tenant_id_on_page');
$business_slug = get_query_var('mobooking_slug');

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
    'maintenance_message' => $bf_settings['bf_maintenance_message'] ?? 'Booking form is currently unavailable.',
];

// Currency settings
$currency = [
    'symbol' => $biz_settings['biz_currency_symbol'] ?? '$',
    'code' => $biz_settings['biz_currency_code'] ?? 'USD',
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
    // Use a direct method call that mirrors the logic of handle_get_public_services_ajax
    // For simplicity, I'll replicate the core DB query and processing logic here,
    // or assume a new method in Services class like get_active_services_with_details_for_tenant($tenant_id)
    // For now, let's adapt the logic from handle_get_public_services_ajax directly

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

            $raw_icon_value = sanitize_text_field($item['icon'] ?? 'fas fa-concierge-bell');
            $item['icon'] = $raw_icon_value; // Store the raw value (URL, preset:key, or class)
            $item['icon_svg_content'] = null; // Initialize

            if (strpos($raw_icon_value, 'preset:') === 0) {
                $preset_key = substr($raw_icon_value, strlen('preset:'));
                if (class_exists('\MoBooking\Classes\Services')) {
                    // get_preset_icon_svg now expects a filename like "tools.svg"
                    $item['icon_svg_content'] = \MoBooking\Classes\Services::get_preset_icon_svg($preset_key);
                }
            }
            // If it's not a preset, item['icon'] already holds the URL or class name.
            // item['icon_svg_content'] will remain null for non-preset icons.

            $options_raw = $service_options_manager->get_service_options($item['service_id'], $tenant_id);
            $options = [];
            if (is_array($options_raw)) {
                foreach ($options_raw as $opt) {
                    $option_array = (array) $opt;
                    $option_array['option_id'] = intval($option_array['option_id']);
                    $option_array['name'] = sanitize_text_field($option_array['name']);
                    $option_array['description'] = wp_kses_post($option_array['description'] ?? '');
                    $option_array['type'] = sanitize_text_field($option_array['type']);
                    $option_array['is_required'] = ($option_array['is_required'] ?? '0') === '1'; // Ensure boolean
                    $option_array['price_impact'] = floatval($option_array['price_impact'] ?? 0);
                    $option_array['price_impact_type'] = sanitize_text_field($option_array['price_impact_type'] ?? 'fixed');
                    // Option values might need specific handling if they are JSON strings
                    if (!empty($option_array['option_values'])) {
                        $decoded_values = json_decode($option_array['option_values'], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $option_array['option_values'] = $decoded_values;
                        } else {
                            $option_array['option_values'] = []; // Default to empty array on decode error
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
?>

<!DOCTYPE html>
<html lang="<?php echo esc_attr(get_locale()); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($form_config['form_header']); ?> - MoBooking</title>
    
    <?php wp_head(); ?>
    
    <style>
        :root {
            --primary-color: <?php echo esc_attr($form_config['theme_color']); ?>;
            --primary-rgb: <?php echo esc_attr(hex2rgb($form_config['theme_color'])); ?>;
            --background: #ffffff;
            --foreground: #1a1a1a;
            --card: #ffffff;
            --border: #e5e7eb;
            --muted: #f3f4f6;
            --muted-foreground: #6b7280;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --radius: 8px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--foreground);
            background: var(--background);
            -webkit-font-smoothing: antialiased;
        }

        /* Container Styles */
        .mobooking-container {
            min-height: 100vh;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.05) 0%, var(--background) 100%);
        }

        .mobooking-form-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        /* Header Styles */
        .mobooking-header {
            background: var(--primary-color);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .mobooking-header h1 {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
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
            height: 2px;
            background: var(--border);
            z-index: 1;
        }

        .mobooking-progress-line-fill {
            height: 100%;
            background: var(--primary-color);
            transition: width 0.3s ease;
            width: 0%;
        }

        .mobooking-progress-step {
            position: relative;
            z-index: 2;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .mobooking-progress-step.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .mobooking-progress-step.completed {
            background: var(--success);
            color: white;
            border-color: var(--success);
        }

        .mobooking-progress-step.completed::after {
            content: '✓';
            font-size: 18px;
        }

        /* Step Content */
        .mobooking-steps {
            position: relative;
            min-height: 400px;
        }

        .mobooking-step {
            display: none;
            padding: 2rem;
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
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mobooking-step-description {
            color: var(--muted-foreground);
            margin-bottom: 2rem;
        }

        /* Layout for steps with sidebar */
        .mobooking-step-with-sidebar {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 2rem;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .mobooking-step-with-sidebar {
                grid-template-columns: 1fr;
            }
            
            .mobooking-sidebar {
                order: -1;
            }
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

        @media (max-width: 768px) {
            .mobooking-form-row {
                grid-template-columns: 1fr;
            }
        }

        .mobooking-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--foreground);
        }

        .mobooking-required {
            color: var(--error);
        }

        .mobooking-input,
        .mobooking-textarea,
        .mobooking-select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: 0.875rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: white;
        }

        .mobooking-input:focus,
        .mobooking-textarea:focus,
        .mobooking-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }

        .mobooking-textarea {
            min-height: 120px;
            resize: vertical;
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
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            min-width: 120px;
        }

        .mobooking-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .mobooking-btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .mobooking-btn-primary:hover:not(:disabled) {
            background: color-mix(in srgb, var(--primary-color) 90%, black);
        }

        .mobooking-btn-secondary {
            background: var(--muted);
            color: var(--foreground);
        }

        .mobooking-btn-secondary:hover:not(:disabled) {
            background: color-mix(in srgb, var(--muted) 80%, black);
        }

        /* Service Cards */
        .mobooking-services-grid {
            display: grid;
            gap: 1rem;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .mobooking-service-card {
            border: 2px solid var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }

        .mobooking-service-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow);
        }

        .mobooking-service-card.selected {
            border-color: var(--primary-color);
            background: rgba(var(--primary-rgb), 0.05);
        }

        .mobooking-service-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .mobooking-service-icon {
            width: 48px; /* Standardize width */
            height: 48px; /* Standardize height */
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius); /* Use a theme variable */
            background-color: transparent; /* Remove default background, let icon fill */
            color: var(--primary-color); /* Default color for font icons, SVGs can override or inherit */
            font-size: 1.5rem; /* Adjusted for better fit if using font icons */
            overflow: hidden; /* Ensure content fits */
        }

        .mobooking-service-icon img,
        .mobooking-service-icon svg {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Ensures the whole icon is visible */
            display: block; /* Good practice for img/svg */
        }

        /* Ensures SVGs use the text color by default for their path fill */
        .mobooking-service-icon svg path {
            fill: currentColor;
        }

        /* If specific styling for <i> tags is still needed (fallback) */
        .mobooking-service-icon i {
            font-size: inherit; /* Inherit from parent's font-size */
            color: inherit; /* Inherit from parent's color */
        }

        .mobooking-service-info {
            flex: 1;
        }

        .mobooking-service-name {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .mobooking-service-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .mobooking-service-description {
            color: var(--muted-foreground);
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .mobooking-service-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--muted-foreground);
        }

        /* Sidebar */
        .mobooking-sidebar {
            background: var(--muted);
            border-radius: var(--radius);
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 1rem;
        }

        .mobooking-sidebar-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .mobooking-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }

        .mobooking-summary-item:last-child {
            border-bottom: none;
        }

        .mobooking-summary-total {
            font-weight: 600;
            font-size: 1.125rem;
            color: var(--primary-color);
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--border);
        }

        /* Feedback Messages */
        .mobooking-feedback {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1rem;
            display: none;
        }

        .mobooking-feedback.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .mobooking-feedback.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .mobooking-feedback.info {
            background: rgba(var(--primary-rgb), 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(var(--primary-rgb), 0.2);
        }

        /* Loading State */
        .mobooking-loading {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--muted-foreground);
        }

        .mobooking-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border);
            border-top: 2px solid var(--primary-color);
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
            font-size: 4rem;
            color: var(--success);
            margin-bottom: 1rem;
            animation: bounceIn 0.6s ease-out;
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        .mobooking-success h2 {
            font-size: 1.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .mobooking-success-details {
            background: var(--muted);
            border-radius: var(--radius);
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .mobooking-container {
                padding: 0.5rem;
            }
            
            .mobooking-header {
                padding: 1.5rem;
            }
            
            .mobooking-header h1 {
                font-size: 1.5rem;
            }
            
            .mobooking-step {
                padding: 1rem;
            }
            
            .mobooking-form-actions {
                flex-direction: column;
            }
            
            .mobooking-btn {
                width: 100%;
            }
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
    </style>
</head>

<body class="mobooking-body">
    <div class="mobooking-container">
        <div class="mobooking-form-wrapper">
            <!-- Header -->
            <?php if (!$is_embed): ?>
            <div class="mobooking-header">
                <h1><?php echo esc_html($form_config['form_header']); ?></h1>
            </div>
            <?php endif; ?>

            <!-- Progress Bar -->
            <div class="mobooking-progress">
                <div class="mobooking-progress-steps">
                    <div class="mobooking-progress-line">
                        <div class="mobooking-progress-line-fill"></div>
                    </div>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <div class="mobooking-progress-step" data-step="<?php echo $i; ?>">
                        <span class="step-number"><?php echo $i; ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Steps Container -->
            <div class="mobooking-steps">
                <!-- Step 1: Location Check -->
                <div class="mobooking-step active" data-step="1">
                    <h2 class="mobooking-step-title">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <?php esc_html_e('Check Service Area', 'mobooking'); ?>
                    </h2>
                    <p class="mobooking-step-description">
                        <?php esc_html_e('Please enter your location to check if we service your area.', 'mobooking'); ?>
                    </p>
                    
                    <div id="mobooking-location-feedback" class="mobooking-feedback" role="alert"></div>
                    
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
                                    placeholder="12345" 
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
                </div>

                <!-- Step 2: Service Selection -->
                <div class="mobooking-step" data-step="2">
                    <h2 class="mobooking-step-title">
                        <i class="fas fa-broom" aria-hidden="true"></i>
                        <?php esc_html_e('Select Service', 'mobooking'); ?>
                    </h2>
                    <p class="mobooking-step-description">
                        <?php esc_html_e('Choose the service you would like to book.', 'mobooking'); ?>
                    </p>
                    
                    <div id="mobooking-services-feedback" class="mobooking-feedback" role="alert"></div>
                    
                    <div id="mobooking-services-container" class="mobooking-services-grid">
                        <div class="mobooking-loading">
                            <div class="mobooking-spinner" aria-hidden="true"></div>
                            <?php esc_html_e('Loading available services...', 'mobooking'); ?>
                        </div>
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
                                <?php esc_html_e('Configure Options', 'mobooking'); ?>
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
                                <?php esc_html_e('Your Details', 'mobooking'); ?>
                            </h2>
                            <p class="mobooking-step-description">
                                <?php esc_html_e('Please provide your contact information and service details.', 'mobooking'); ?>
                            </p>
                            
                            <div id="mobooking-details-feedback" class="mobooking-feedback" role="alert"></div>
                            
                            <form id="mobooking-details-form" novalidate>
                                <div class="mobooking-form-row">
                                    <div class="mobooking-form-group">
                                        <label for="customer-name" class="mobooking-label">
                                            <?php esc_html_e('Full Name', 'mobooking'); ?>
                                            <span class="mobooking-required" aria-label="required">*</span>
                                        </label>
                                        <input type="text" id="customer-name" name="customer_name" class="mobooking-input" required>
                                    </div>
                                    <div class="mobooking-form-group">
                                        <label for="customer-email" class="mobooking-label">
                                            <?php esc_html_e('Email Address', 'mobooking'); ?>
                                            <span class="mobooking-required" aria-label="required">*</span>
                                        </label>
                                        <input type="email" id="customer-email" name="customer_email" class="mobooking-input" required>
                                    </div>
                                </div>
                                
                                <div class="mobooking-form-row">
                                    <div class="mobooking-form-group">
                                        <label for="customer-phone" class="mobooking-label">
                                            <?php esc_html_e('Phone Number', 'mobooking'); ?>
                                            <span class="mobooking-required" aria-label="required">*</span>
                                        </label>
                                        <input type="tel" id="customer-phone" name="customer_phone" class="mobooking-input" required>
                                    </div>
                                    <div class="mobooking-form-group">
                                        <label for="service-address" class="mobooking-label">
                                            <?php esc_html_e('Service Address', 'mobooking'); ?>
                                            <span class="mobooking-required" aria-label="required">*</span>
                                        </label>
                                        <input type="text" id="service-address" name="service_address" class="mobooking-input" required>
                                    </div>
                                </div>
                                
                                <div class="mobooking-form-row">
                                    <div class="mobooking-form-group">
                                        <label for="preferred-date" class="mobooking-label">
                                            <?php esc_html_e('Preferred Date', 'mobooking'); ?>
                                        </label>
                                        <input type="date" id="preferred-date" name="preferred_date" class="mobooking-input" min="<?php echo esc_attr(date('Y-m-d')); ?>">
                                    </div>
                                    <div class="mobooking-form-group">
                                        <label for="preferred-time" class="mobooking-label">
                                            <?php esc_html_e('Preferred Time', 'mobooking'); ?>
                                        </label>
                                        <input type="time" id="preferred-time" name="preferred_time" class="mobooking-input">
                                    </div>
                                </div>
                                
                                <div class="mobooking-form-group">
                                    <label for="special-instructions" class="mobooking-label">
                                        <?php esc_html_e('Special Instructions', 'mobooking'); ?>
                                    </label>
                                    <textarea id="special-instructions" name="special_instructions" class="mobooking-textarea" 
                                              placeholder="<?php esc_attr_e('Any special requirements or notes...', 'mobooking'); ?>"></textarea>
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
                                <?php esc_html_e('Review & Confirm', 'mobooking'); ?>
                            </h2>
                            <p class="mobooking-step-description">
                                <?php esc_html_e('Please review your booking details before confirming.', 'mobooking'); ?>
                            </p>
                            
                            <div id="mobooking-review-feedback" class="mobooking-feedback" role="alert"></div>
                            
                            <!-- Booking Review Details -->
                            <div id="mobooking-review-details" class="mobooking-review-section">
                                <!-- Populated by JavaScript -->
                            </div>
                            
                            <?php if ($form_config['allow_discount_codes']): ?>
                            <!-- Discount Code Section -->
                            <div class="mobooking-discount-section">
                                <h4><?php esc_html_e('Discount Code', 'mobooking'); ?></h4>
                                <div class="mobooking-form-row">
                                    <div class="mobooking-form-group">
                                        <input type="text" id="discount-code" placeholder="<?php esc_attr_e('Enter discount code', 'mobooking'); ?>" class="mobooking-input">
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
                        <p><?php esc_html_e('Your booking has been successfully submitted.', 'mobooking'); ?></p>
                        
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

    <!-- Hidden Form Data -->
    <input type="hidden" id="tenant-id" value="<?php echo esc_attr($tenant_id); ?>">
    <input type="hidden" id="form-nonce" value="<?php echo esc_attr($form_nonce); ?>">

    <script type="text/javascript">
        window.MOB_PRELOADED_SERVICES = <?php echo wp_json_encode($preloaded_services_data); ?>;
        // Other essential global JS variables that might have been in mobooking_booking_form_params
        // and are still needed globally by the new jQuery approach can be set here too,
        // or continue to be passed via wp_localize_script if they are small and specific.
        // For instance, AJAX URL, nonces, basic i18n messages for direct jQuery use.
        // However, mobooking_booking_form_params will still be localized by functions.php for general params.
    </script>

    <!--
    <script>
    // Fix for JSON encoding issues in booking form submission
    // Add this to your booking form JavaScript or replace the existing submission logic

    jQuery(document).ready(function($) {
        'use strict';

        // Enhanced JSON encoding with proper escaping
        function safeJsonEncode(data) {
            try {
                // Clean the data first
                const cleanData = cleanDataForJson(data);
                const jsonString = JSON.stringify(cleanData);

                console.log('🔍 JSON Encoding Debug:', {
                    original: data,
                    cleaned: cleanData,
                    encoded: jsonString
                });

                return jsonString;
            } catch (error) {
                console.error('❌ JSON encoding failed:', error, data);
                return null;
            }
        }
        
        // Clean data to prevent JSON encoding issues
        function cleanDataForJson(data) {
            if (typeof data === 'string') {
                // Remove problematic characters and normalize
                return data
                    .replace(/[\u0000-\u001F\u007F-\u009F]/g, '') // Remove control characters
                    .replace(/\\/g, '\\\\') // Escape backslashes
                    .replace(/"/g, '\\"') // Escape quotes
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
            console.log('🔍 Starting enhanced booking submission...');

            // Get form data
            const tenantId = $('#tenant-id').val();
            const nonce = $('#form-nonce').val() || window.MOBOOKING_FORM_NONCE;

            if (!tenantId || !nonce) {
                console.error('❌ Missing required data:', { tenantId, nonce });
                alert('Missing required form data. Please refresh the page and try again.');
                return;
            }

            // Collect selected services
            const selectedServices = [
                {
                    service_id: 12,
                    name: "Moving v2",
                    price: 2500,
                    configured_options: {}
                }
            ];

            // Collect customer details with careful data handling
            const customerDetails = {
                name: ($('#customer-name').val() || 'ger').toString().trim(),
                email: ($('#customer-email').val() || 'mmotestmmo@gmail.com').toString().trim(),
                phone: ($('#customer-phone').val() || '96666313').toString().trim(),
                address: ($('#customer-address').val() || 'erg').toString().trim(),
                date: ($('#customer-date').val() || '2025-07-22').toString().trim(),
                time: ($('#customer-time').val() || '17:13').toString().trim(),
                instructions: ($('#customer-instructions').val() || 'pla pla').toString().trim()
            };

            // Validate customer details
            const requiredFields = ['name', 'email', 'phone', 'address', 'date', 'time'];
            const missingFields = requiredFields.filter(field => !customerDetails[field]);

            if (missingFields.length > 0) {
                console.error('❌ Missing required fields:', missingFields);
                alert('Please fill in all required fields: ' + missingFields.join(', '));
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(customerDetails.email)) {
                console.error('❌ Invalid email format:', customerDetails.email);
                alert('Please enter a valid email address.');
                return;
            }

            // Encode JSON safely
            const selectedServicesJson = safeJsonEncode(selectedServices);
            const customerDetailsJson = safeJsonEncode(customerDetails);

            if (!selectedServicesJson || !customerDetailsJson) {
                console.error('❌ JSON encoding failed');
                alert('Error processing form data. Please try again.');
                return;
            }

            // Prepare submission data
            const submissionData = {
                action: 'mobooking_create_booking',
                nonce: nonce,
                tenant_id: tenantId,
                selected_services: selectedServicesJson,
                customer_details: customerDetailsJson, // This was causing the issue
                discount_info: '',
                zip_code: '',
                country_code: '',
                pricing: JSON.stringify({
                    subtotal: 2500,
                    discount: 0,
                    total: 2500
                })
            };

            console.log('📤 Submitting with fixed JSON encoding:', submissionData);

            // Test JSON parsing before sending
            try {
                const testParse1 = JSON.parse(submissionData.selected_services);
                const testParse2 = JSON.parse(submissionData.customer_details);
                console.log('✅ JSON validation passed:', { testParse1, testParse2 });
            } catch (error) {
                console.error('❌ JSON validation failed:', error);
                alert('Data encoding error. Please try again.');
                return;
            }

            // Submit with enhanced error handling
            $.ajax({
                url: '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: submissionData,
                timeout: 30000,
                beforeSend: function() {
                    console.log('🚀 Submitting booking...');
                    // Show loading state if you have UI for it
                },
                success: function(response) {
                    console.log('✅ Booking submission successful:', response);
                    if (response.success) {
                        alert('Booking submitted successfully! Reference: ' + (response.data.booking_reference || 'N/A'));
                    } else {
                        alert('Booking failed: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Booking submission failed:', { xhr, status, error });

                    let errorMessage = 'Booking submission failed.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage = xhr.responseJSON.data.message;
                    }

                    alert(errorMessage);
                }
            });
        };
        
        // Add a test button to your form
        function addTestButton() {
            if ($('#json-fix-test-btn').length > 0) return;

            const testButton = $(`
                <div style="margin: 20px 0; padding: 15px; background: #e8f5e8; border: 1px solid #4caf50; border-radius: 5px;">
                    <h4>JSON Fix Test (Remove in production)</h4>
                    <button type="button" id="json-fix-test-btn" class="button" style="background: #4caf50; color: white; padding: 10px 20px;">
                        Test Fixed Submission
                    </button>
                    <p style="margin-top: 10px; font-size: 12px; color: #666;">
                        This will test the booking submission with proper JSON encoding
                    </p>
                </div>
            `);

            $('#mobooking-bf-step-1-location, .mobooking-form-container').first().prepend(testButton);

            $('#json-fix-test-btn').on('click', function() {
                $(this).prop('disabled', true).text('Testing...');
                submitBookingWithJsonFix();
                setTimeout(() => {
                    $(this).prop('disabled', false).text('Test Fixed Submission');
                }, 3000);
            });
        }
        
        // Add test button after page loads
        setTimeout(addTestButton, 1000);
        
        // Override existing submission if it exists
        if (window.MoBookingForm && typeof window.MoBookingForm.submitFinalBooking === 'function') {
            console.log('🔍 Overriding existing booking submission with JSON fix');
            window.MoBookingForm.submitFinalBooking = submitBookingWithJsonFix;
        }
        
        console.log('✅ JSON encoding fix loaded successfully');
    });

    </script>
    -->

    <?php
    // The inline <script> block containing the MoBookingForm object has been moved to assets/js/booking-form-public.js
    // It will be enqueued and localized via functions.php

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
    
    wp_footer();
    ?>
</body>
</html>

<?php
/**
 * Additional CSS for discount and review sections has been moved to assets/css/booking-form-modern.css
 */
?>