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
            width: 48px;
            height: 48px;
            background: var(--primary-color);
            color: white;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
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

    <script>
        // Configuration object
        window.MoBookingForm = {
            config: {
                tenantId: <?php echo json_encode($tenant_id); ?>,
                nonce: <?php echo json_encode($form_nonce); ?>,
                ajaxUrl: <?php echo json_encode(admin_url('admin-ajax.php')); ?>,
                currency: <?php echo json_encode($currency); ?>,
                settings: <?php echo json_encode($form_config); ?>,
                i18n: {
                    zipRequired: <?php echo json_encode(__('ZIP code is required', 'mobooking')); ?>,
                    countryRequired: <?php echo json_encode(__('Country is required', 'mobooking')); ?>,
                    checking: <?php echo json_encode(__('Checking...', 'mobooking')); ?>,
                    loadingServices: <?php echo json_encode(__('Loading services...', 'mobooking')); ?>,
                    noServicesAvailable: <?php echo json_encode(__('No services available', 'mobooking')); ?>,
                    selectServiceRequired: <?php echo json_encode(__('Please select a service', 'mobooking')); ?>,
                    fillRequiredFields: <?php echo json_encode(__('Please fill in all required fields', 'mobooking')); ?>,
                    invalidEmail: <?php echo json_encode(__('Please enter a valid email address', 'mobooking')); ?>,
                    submitting: <?php echo json_encode(__('Submitting...', 'mobooking')); ?>,
                    bookingConfirmed: <?php echo json_encode(__('Booking Confirmed!', 'mobooking')); ?>,
                    connectionError: <?php echo json_encode(__('Connection error. Please try again.', 'mobooking')); ?>,
                    discountApplied: <?php echo json_encode(__('Discount applied', 'mobooking')); ?>,
                    invalidDiscount: <?php echo json_encode(__('Invalid discount code', 'mobooking')); ?>,
                    enterDiscountCode: <?php echo json_encode(__('Please enter a discount code', 'mobooking')); ?>
                }
            },
            
            // Current state
            state: {
                currentStep: 1,
                selectedService: null,
                selectedOptions: {},
                customerDetails: {},
                discountInfo: null,
                totalPrice: 0,
                locationVerified: false
            },

            // Initialize the form
            init: function() {
                this.bindEvents();
                this.initializeFirstStep();
                console.log('MoBooking Form initialized', this.config);
            },

            // Bind all event handlers
            bindEvents: function() {
                // Location form
                jQuery('#mobooking-location-form').on('submit', this.handleLocationSubmit.bind(this));
                
                // Navigation buttons
                jQuery(document).on('click', '[data-step-back]', this.handleStepBack.bind(this));
                jQuery(document).on('click', '[data-step-next]', this.handleStepNext.bind(this));
                
                // Service selection
                jQuery(document).on('click', '.mobooking-service-card', this.handleServiceSelection.bind(this));
                
                // Customer details form
                jQuery('#mobooking-details-form').on('input change', this.handleDetailsChange.bind(this));
                
                // Final submission
                jQuery('#final-submit-btn').on('click', this.handleFinalSubmit.bind(this));
                
                // Discount code
                if (this.config.settings.allow_discount_codes) {
                    jQuery('#apply-discount-btn').on('click', this.handleDiscountApplication.bind(this));
                }
            },

            // Initialize first step based on configuration
            initializeFirstStep: function() {
                if (!this.config.settings.enable_location_check) {
                    // Skip location check, go directly to services
                    this.state.locationVerified = true;
                    this.showStep(2);
                } else {
                    this.showStep(1);
                }
            },

            // Show specific step
            showStep: function(stepNumber) {
                // Hide all steps
                jQuery('.mobooking-step').removeClass('active');
                
                // Show target step with animation
                const targetStep = jQuery('.mobooking-step[data-step="' + stepNumber + '"]');
                setTimeout(() => {
                    targetStep.addClass('active');
                }, 50);
                
                // Update progress
                this.updateProgress(stepNumber);
                this.state.currentStep = stepNumber;
                
                // Load step-specific data
                this.loadStepData(stepNumber);
            },

            // Update progress bar
            updateProgress: function(currentStep) {
                const progressPercentage = ((currentStep - 1) / 4) * 100;
                jQuery('.mobooking-progress-line-fill').css('width', progressPercentage + '%');
                
                jQuery('.mobooking-progress-step').each(function() {
                    const stepNum = parseInt(jQuery(this).data('step'));
                    jQuery(this).removeClass('active completed');
                    
                    if (stepNum < currentStep) {
                        jQuery(this).addClass('completed');
                        jQuery(this).find('.step-number').text('');
                    } else if (stepNum === currentStep) {
                        jQuery(this).addClass('active');
                        jQuery(this).find('.step-number').text(stepNum);
                    } else {
                        jQuery(this).find('.step-number').text(stepNum);
                    }
                });
            },

            // Load step-specific data
            loadStepData: function(stepNumber) {
                switch(stepNumber) {
                    case 2:
                        this.loadServices();
                        break;
                    case 3:
                        this.loadServiceOptions();
                        break;
                    case 4:
                        this.updateSummary();
                        break;
                    case 5:
                        this.loadReviewData();
                        break;
                }
            },

            // Handle location form submission
            handleLocationSubmit: function(e) {
                e.preventDefault();
                
                const zipCode = jQuery('#mobooking-zip').val().trim();
                const countryCode = jQuery('#mobooking-country').val().trim();
                
                if (!this.validateLocationForm(zipCode, countryCode)) {
                    return;
                }
                
                this.checkServiceArea(zipCode, countryCode);
            },

            // Validate location form
            validateLocationForm: function(zipCode, countryCode) {
                const feedback = jQuery('#mobooking-location-feedback');
                
                if (!zipCode) {
                    this.showFeedback(feedback, 'error', this.config.i18n.zipRequired);
                    return false;
                }
                
                if (!countryCode) {
                    this.showFeedback(feedback, 'error', this.config.i18n.countryRequired);
                    return false;
                }
                
                return true;
            },

            // Check service area via AJAX
            checkServiceArea: function(zipCode, countryCode) {
                const submitBtn = jQuery('#mobooking-location-form button[type="submit"]');
                const originalText = submitBtn.html();
                const feedback = jQuery('#mobooking-location-feedback');
                
                // Show loading state
                submitBtn.prop('disabled', true).html('<div class="mobooking-spinner"></div> ' + this.config.i18n.checking);
                this.showFeedback(feedback, 'info', this.config.i18n.checking);
                
                jQuery.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mobooking_check_service_area',
                        nonce: this.config.nonce,
                        zip_code: zipCode,
                        country_code: countryCode,
                        tenant_id: this.config.tenantId
                    },
                    success: (response) => {
                        if (response.success && response.data.serviced) {
                            this.showFeedback(feedback, 'success', response.data.message);
                            this.state.locationVerified = true;
                            setTimeout(() => this.showStep(2), 1500);
                        } else {
                            this.showFeedback(feedback, 'error', response.data.message || 'Service area not available');
                        }
                    },
                    error: () => {
                        this.showFeedback(feedback, 'error', this.config.i18n.connectionError);
                    },
                    complete: () => {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            },

            // Load available services
            loadServices: function() {
                const container = jQuery('#mobooking-services-container');
                container.html('<div class="mobooking-loading"><div class="mobooking-spinner"></div>' + this.config.i18n.loadingServices + '</div>');
                
                jQuery.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mobooking_get_public_services',
                        nonce: this.config.nonce,
                        tenant_id: this.config.tenantId
                    },
                    success: (response) => {
                        if (response.success && response.data && response.data.length > 0) {
                            this.displayServices(response.data);
                        } else {
                            container.html('<p>' + this.config.i18n.noServicesAvailable + '</p>');
                        }
                    },
                    error: () => {
                        container.html('<p>' + this.config.i18n.connectionError + '</p>');
                        this.showFeedback(jQuery('#mobooking-services-feedback'), 'error', this.config.i18n.connectionError);
                    }
                });
            },

            // Display services
            displayServices: function(services) {
                const container = jQuery('#mobooking-services-container');
                let html = '';
                
                services.forEach(service => {
                    const priceDisplay = this.config.settings.show_pricing ? 
                        `<div class="mobooking-service-price">${this.config.currency.symbol}${this.formatPrice(service.price || 0)}</div>` : '';
                    
                    html += `
                        <div class="mobooking-service-card" data-service-id="${service.service_id}" data-service-price="${service.price || 0}">
                            <div class="mobooking-service-header">
                                <div class="mobooking-service-icon">
                                    <i class="${service.icon || 'fas fa-broom'}"></i>
                                </div>
                                <div class="mobooking-service-info">
                                    <div class="mobooking-service-name">${this.escapeHtml(service.name)}</div>
                                    ${priceDisplay}
                                </div>
                                <input type="radio" name="selected_service" value="${service.service_id}" style="margin-left: auto;">
                            </div>
                            ${service.description ? `<div class="mobooking-service-description">${this.escapeHtml(service.description)}</div>` : ''}
                            <div class="mobooking-service-meta">
                                ${service.duration ? `<div><i class="fas fa-clock"></i> ${service.duration} min</div>` : ''}
                                ${service.category ? `<div><i class="fas fa-tag"></i> ${this.escapeHtml(service.category)}</div>` : ''}
                            </div>
                        </div>
                    `;
                });
                
                container.html(html);
            },

            // Handle service selection
            handleServiceSelection: function(e) {
                const card = jQuery(e.currentTarget);
                const serviceId = card.data('service-id');
                const servicePrice = parseFloat(card.data('service-price') || 0);
                const serviceName = card.find('.mobooking-service-name').text();
                
                // Update UI
                jQuery('.mobooking-service-card').removeClass('selected');
                card.addClass('selected');
                card.find('input[type="radio"]').prop('checked', true);
                
                // Update state
                this.state.selectedService = {
                    id: serviceId,
                    name: serviceName,
                    price: servicePrice
                };
                
                // Enable next button
                jQuery('[data-step-next="3"]').prop('disabled', false);
            },

            // Load service options
            loadServiceOptions: function() {
                if (!this.state.selectedService) {
                    jQuery('#mobooking-service-options').html('<p>No service selected.</p>');
                    return;
                }
                
                const container = jQuery('#mobooking-service-options');
                container.html('<div class="mobooking-loading"><div class="mobooking-spinner"></div>Loading service options...</div>');
                
                jQuery.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mobooking_get_service_options',
                        nonce: this.config.nonce,
                        service_id: this.state.selectedService.id,
                        tenant_id: this.config.tenantId
                    },
                    success: (response) => {
                        if (response.success && response.data) {
                            this.displayServiceOptions(response.data);
                        } else {
                            container.html('<p>No additional options for this service.</p>');
                        }
                        this.updateSummary();
                    },
                    error: () => {
                        container.html('<p>' + this.config.i18n.connectionError + '</p>');
                    }
                });
            },

            // Display service options
            displayServiceOptions: function(options) {
                const container = jQuery('#mobooking-service-options');
                
                if (!options || options.length === 0) {
                    container.html('<p>No additional options for this service.</p>');
                    return;
                }
                
                let html = '';
                options.forEach(option => {
                    html += this.generateOptionHTML(option);
                });
                
                container.html(html);
                
                // Bind option change events
                container.find('input, select, textarea').on('change input', this.handleOptionChange.bind(this));
            },

            // Generate HTML for individual option
            generateOptionHTML: function(option) {
                const required = option.required === '1' ? ' required' : '';
                const requiredIndicator = option.required === '1' ? '<span class="mobooking-required">*</span>' : '';
                const priceDisplay = this.formatOptionPrice(option);
                
                switch(option.option_type) {
                    case 'checkbox':
                        return `
                            <div class="mobooking-form-group">
                                <label>
                                    <input type="checkbox" name="option_${option.option_id}" value="1" 
                                           data-option-id="${option.option_id}" data-price="${option.price_impact || 0}" 
                                           data-price-type="${option.price_impact_type || 'fixed'}"${required}>
                                    ${this.escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                                </label>
                                ${option.description ? `<div class="option-description">${this.escapeHtml(option.description)}</div>` : ''}
                            </div>
                        `;
                    
                    case 'text':
                        return `
                            <div class="mobooking-form-group">
                                <label for="option_${option.option_id}" class="mobooking-label">
                                    ${this.escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                                </label>
                                <input type="text" id="option_${option.option_id}" name="option_${option.option_id}" 
                                       class="mobooking-input" data-option-id="${option.option_id}" 
                                       data-price="${option.price_impact || 0}" data-price-type="${option.price_impact_type || 'fixed'}"${required}>
                                ${option.description ? `<div class="option-description">${this.escapeHtml(option.description)}</div>` : ''}
                            </div>
                        `;
                    
                    case 'textarea':
                        return `
                            <div class="mobooking-form-group">
                                <label for="option_${option.option_id}" class="mobooking-label">
                                    ${this.escapeHtml(option.name)} ${priceDisplay} ${requiredIndicator}
                                </label>
                                <textarea id="option_${option.option_id}" name="option_${option.option_id}" 
                                          class="mobooking-textarea" data-option-id="${option.option_id}" 
                                          data-price="${option.price_impact || 0}" data-price-type="${option.price_impact_type || 'fixed'}"${required}></textarea>
                                ${option.description ? `<div class="option-description">${this.escapeHtml(option.description)}</div>` : ''}
                            </div>
                        `;
                    
                    default:
                        return '';
                }
            },

            // Format option price display
            formatOptionPrice: function(option) {
                if (!option.price_impact || option.price_impact == 0) {
                    return '';
                }
                
                if (option.price_impact_type === 'percentage') {
                    return `<span class="option-price">(+${option.price_impact}%)</span>`;
                } else {
                    return `<span class="option-price">(+${this.config.currency.symbol}${this.formatPrice(option.price_impact)})</span>`;
                }
            },

            // Handle option changes
            handleOptionChange: function(e) {
                const input = jQuery(e.target);
                const optionId = input.data('option-id');
                const price = parseFloat(input.data('price') || 0);
                const priceType = input.data('price-type') || 'fixed';
                
                if (input.is(':checkbox')) {
                    if (input.is(':checked')) {
                        this.state.selectedOptions[optionId] = {
                            value: input.val(),
                            price: price,
                            priceType: priceType
                        };
                    } else {
                        delete this.state.selectedOptions[optionId];
                    }
                } else {
                    const value = input.val().trim();
                    if (value) {
                        this.state.selectedOptions[optionId] = {
                            value: value,
                            price: price,
                            priceType: priceType
                        };
                    } else {
                        delete this.state.selectedOptions[optionId];
                    }
                }
                
                this.updateSummary();
            },

            // Update sidebar summary
            updateSummary: function() {
                if (!this.state.selectedService) {
                    return;
                }
                
                let subtotal = this.state.selectedService.price;
                let html = `
                    <div class="mobooking-summary-item">
                        <span>${this.escapeHtml(this.state.selectedService.name)}</span>
                        <span>${this.config.currency.symbol}${this.formatPrice(this.state.selectedService.price)}</span>
                    </div>
                `;
                
                // Add options
                Object.values(this.state.selectedOptions).forEach(option => {
                    if (option.priceType === 'percentage') {
                        const optionPrice = (this.state.selectedService.price * option.price) / 100;
                        subtotal += optionPrice;
                        html += `
                            <div class="mobooking-summary-item">
                                <span>+ Option (${option.price}%)</span>
                                <span>${this.config.currency.symbol}${this.formatPrice(optionPrice)}</span>
                            </div>
                        `;
                    } else {
                        subtotal += option.price;
                        html += `
                            <div class="mobooking-summary-item">
                                <span>+ Option</span>
                                <span>${this.config.currency.symbol}${this.formatPrice(option.price)}</span>
                            </div>
                        `;
                    }
                });
                
                // Calculate final total (with discount if applied)
                let finalTotal = subtotal;
                if (this.state.discountInfo) {
                    const discount = this.calculateDiscount(subtotal, this.state.discountInfo);
                    finalTotal = subtotal - discount;
                    
                    html += `
                        <div class="mobooking-summary-item">
                            <span>Discount</span>
                            <span>-${this.config.currency.symbol}${this.formatPrice(discount)}</span>
                        </div>
                    `;
                }
                
                html += `
                    <div class="mobooking-summary-total">
                        <span>Total: ${this.config.currency.symbol}${this.formatPrice(finalTotal)}</span>
                    </div>
                `;
                
                this.state.totalPrice = finalTotal;
                
                // Update all summary containers
                jQuery('#mobooking-summary-content, #mobooking-summary-content-step4, #mobooking-final-summary').html(html);
                
                // Update pricing displays
                jQuery('#pricing-subtotal').text(this.config.currency.symbol + this.formatPrice(subtotal));
                jQuery('#pricing-total').text(this.config.currency.symbol + this.formatPrice(finalTotal));
            },

            // Handle step navigation
            handleStepBack: function(e) {
                const targetStep = parseInt(jQuery(e.target).closest('[data-step-back]').data('step-back'));
                this.showStep(targetStep);
            },

            handleStepNext: function(e) {
                const button = jQuery(e.target).closest('[data-step-next]');
                const targetStep = parseInt(button.data('step-next'));
                
                // Validate current step before proceeding
                if (this.validateCurrentStep()) {
                    this.showStep(targetStep);
                }
            },

            // Validate current step
            validateCurrentStep: function() {
                switch(this.state.currentStep) {
                    case 2:
                        if (!this.state.selectedService) {
                            this.showFeedback(jQuery('#mobooking-services-feedback'), 'error', this.config.i18n.selectServiceRequired);
                            return false;
                        }
                        break;
                    case 4:
                        return this.validateCustomerDetails();
                }
                return true;
            },

            // Handle customer details changes
            handleDetailsChange: function() {
                // Real-time validation can be added here
                this.updateCustomerDetailsState();
            },

            // Update customer details state
            updateCustomerDetailsState: function() {
                this.state.customerDetails = {
                    name: jQuery('#customer-name').val().trim(),
                    email: jQuery('#customer-email').val().trim(),
                    phone: jQuery('#customer-phone').val().trim(),
                    address: jQuery('#service-address').val().trim(),
                    date: jQuery('#preferred-date').val(),
                    time: jQuery('#preferred-time').val(),
                    instructions: jQuery('#special-instructions').val().trim()
                };
            },

            // Validate customer details
            validateCustomerDetails: function() {
                this.updateCustomerDetailsState();
                const feedback = jQuery('#mobooking-details-feedback');
                
                const required = ['name', 'email', 'phone', 'address'];
                for (let field of required) {
                    if (!this.state.customerDetails[field]) {
                        this.showFeedback(feedback, 'error', this.config.i18n.fillRequiredFields);
                        return false;
                    }
                }
                
                // Validate email
                if (!this.isValidEmail(this.state.customerDetails.email)) {
                    this.showFeedback(feedback, 'error', this.config.i18n.invalidEmail);
                    return false;
                }
                
                return true;
            },

            // Load review data
            loadReviewData: function() {
                let html = `
                    <div class="mobooking-review-section">
                        <h4>Service Details</h4>
                        <div class="review-item">
                            <strong>Service:</strong> ${this.escapeHtml(this.state.selectedService.name)}
                        </div>
                        <div class="review-item">
                            <strong>Price:</strong> ${this.config.currency.symbol}${this.formatPrice(this.state.selectedService.price)}
                        </div>
                `;
                
                // Add options if any
                if (Object.keys(this.state.selectedOptions).length > 0) {
                    html += '<div class="review-item"><strong>Options:</strong><ul>';
                    Object.values(this.state.selectedOptions).forEach(option => {
                        html += `<li>${this.escapeHtml(option.value)}</li>`;
                    });
                    html += '</ul></div>';
                }
                
                html += `
                    </div>
                    <div class="mobooking-review-section">
                        <h4>Customer Information</h4>
                        <div class="review-item"><strong>Name:</strong> ${this.escapeHtml(this.state.customerDetails.name)}</div>
                        <div class="review-item"><strong>Email:</strong> ${this.escapeHtml(this.state.customerDetails.email)}</div>
                        <div class="review-item"><strong>Phone:</strong> ${this.escapeHtml(this.state.customerDetails.phone)}</div>
                        <div class="review-item"><strong>Address:</strong> ${this.escapeHtml(this.state.customerDetails.address)}</div>
                `;
                
                if (this.state.customerDetails.date) {
                    html += `<div class="review-item"><strong>Preferred Date:</strong> ${this.escapeHtml(this.state.customerDetails.date)}</div>`;
                }
                
                if (this.state.customerDetails.time) {
                    html += `<div class="review-item"><strong>Preferred Time:</strong> ${this.escapeHtml(this.state.customerDetails.time)}</div>`;
                }
                
                if (this.state.customerDetails.instructions) {
                    html += `<div class="review-item"><strong>Special Instructions:</strong> ${this.escapeHtml(this.state.customerDetails.instructions)}</div>`;
                }
                
                html += '</div>';
                
                jQuery('#mobooking-review-details').html(html);
            },

            // Handle discount application
            handleDiscountApplication: function() {
                const code = jQuery('#discount-code').val().trim();
                const feedback = jQuery('#discount-feedback');
                
                if (!code) {
                    this.showFeedback(feedback, 'error', this.config.i18n.enterDiscountCode);
                    return;
                }
                
                const button = jQuery('#apply-discount-btn');
                const originalText = button.text();
                button.prop('disabled', true).text('Applying...');
                
                jQuery.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mobooking_validate_discount',
                        nonce: this.config.nonce,
                        discount_code: code,
                        tenant_id: this.config.tenantId,
                        subtotal: this.state.totalPrice
                    },
                    success: (response) => {
                        if (response.success) {
                            this.state.discountInfo = response.data;
                            this.showFeedback(feedback, 'success', this.config.i18n.discountApplied);
                            jQuery('.discount-applied').removeClass('hidden');
                            this.updateSummary();
                        } else {
                            this.showFeedback(feedback, 'error', response.data.message || this.config.i18n.invalidDiscount);
                        }
                    },
                    error: () => {
                        this.showFeedback(feedback, 'error', this.config.i18n.connectionError);
                    },
                    complete: () => {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            },

            // Handle final submission
            handleFinalSubmit: function() {
                const button = jQuery('#final-submit-btn');
                const originalText = button.html();
                const feedback = jQuery('#mobooking-review-feedback');
                
                // Disable button and show loading
                button.prop('disabled', true).html('<div class="mobooking-spinner"></div> ' + this.config.i18n.submitting);
                
                // Prepare submission data
                const submissionData = {
                    action: 'mobooking_create_booking',
                    nonce: this.config.nonce,
                    tenant_id: this.config.tenantId,
                    selected_services: JSON.stringify([{
                        service_id: this.state.selectedService.id,
                        options: this.state.selectedOptions
                    }]),
                    booking_details: JSON.stringify(this.state.customerDetails),
                    discount_info: this.state.discountInfo ? JSON.stringify(this.state.discountInfo) : '',
                    zip_code: jQuery('#mobooking-zip').val(),
                    country_code: jQuery('#mobooking-country').val()
                };
                
                jQuery.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: submissionData,
                    success: (response) => {
                        if (response.success) {
                            this.handleBookingSuccess(response.data);
                        } else {
                            this.showFeedback(feedback, 'error', response.data.message || 'Booking submission failed');
                        }
                    },
                    error: () => {
                        this.showFeedback(feedback, 'error', this.config.i18n.connectionError);
                    },
                    complete: () => {
                        button.prop('disabled', false).html(originalText);
                    }
                });
            },

            // Handle successful booking
            handleBookingSuccess: function(data) {
                // Show success step
                this.showStep(6);
                
                // Populate success details
                const successDetails = `
                    <div class="success-detail">
                        <strong>Booking Reference:</strong> ${this.escapeHtml(data.booking_reference || 'N/A')}
                    </div>
                    <div class="success-detail">
                        <strong>Service:</strong> ${this.escapeHtml(this.state.selectedService.name)}
                    </div>
                    <div class="success-detail">
                        <strong>Customer:</strong> ${this.escapeHtml(this.state.customerDetails.name)}
                    </div>
                    <div class="success-detail">
                        <strong>Email:</strong> ${this.escapeHtml(this.state.customerDetails.email)}
                    </div>
                    <div class="success-detail">
                        <strong>Total:</strong> ${this.config.currency.symbol}${this.formatPrice(data.final_total || this.state.totalPrice)}
                    </div>
                    <p style="margin-top: 1rem; color: var(--muted-foreground);">
                        You will receive a confirmation email shortly at ${this.escapeHtml(this.state.customerDetails.email)}
                    </p>
                `;
                
                jQuery('#success-details').html(successDetails);
            },

            // Utility functions
            showFeedback: function(element, type, message) {
                element.removeClass('success error info')
                       .addClass(type)
                       .text(message)
                       .show();
                
                if (type === 'success') {
                    setTimeout(() => element.fadeOut(), 3000);
                }
            },

            formatPrice: function(price) {
                return parseFloat(price || 0).toFixed(2);
            },

            calculateDiscount: function(subtotal, discountInfo) {
                if (!discountInfo) return 0;
                
                if (discountInfo.discount_type === 'percentage') {
                    return (subtotal * discountInfo.discount_value) / 100;
                } else {
                    return Math.min(discountInfo.discount_value, subtotal);
                }
            },

            isValidEmail: function(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            },

            escapeHtml: function(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };

        // Initialize when DOM is ready
        jQuery(document).ready(function() {
            window.MoBookingForm.init();
        });
    </script>

    <?php
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
 * Additional CSS for discount and review sections
 */
?>
<style>
    .mobooking-discount-section {
        background: var(--muted);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin: 2rem 0;
    }

    .mobooking-discount-section h4 {
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .mobooking-pricing-summary {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin: 2rem 0;
    }

    .mobooking-review-section {
        background: var(--muted);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .mobooking-review-section h4 {
        margin-bottom: 1rem;
        font-weight: 600;
        color: var(--primary-color);
    }

    .review-item {
        margin-bottom: 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--border);
    }

    .review-item:last-child {
        border-bottom: none;
    }

    .review-item strong {
        font-weight: 600;
        min-width: 120px;
    }

    .review-item ul {
        margin: 0;
        padding-left: 1rem;
        flex: 1;
        text-align: right;
    }

    .success-detail {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border);
    }

    .success-detail:last-child {
        border-bottom: none;
    }

    .success-detail strong {
        font-weight: 600;
    }

    .option-description {
        font-size: 0.875rem;
        color: var(--muted-foreground);
        margin-top: 0.25rem;
    }

    .option-price {
        color: var(--primary-color);
        font-weight: 500;
        margin-left: 0.5rem;
    }

    /* Mobile optimizations */
    @media (max-width: 768px) {
        .review-item {
            flex-direction: column;
            align-items: flex-start;
        }

        .review-item strong {
            min-width: auto;
            margin-bottom: 0.25rem;
        }

        .success-detail {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>