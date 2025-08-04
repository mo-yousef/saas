<?php
/**
 * Clean MoBooking Public Booking Form Template
 * File: templates/booking-form-public.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Debug data collection
$debug_data = [
    'timestamp' => current_time('mysql'),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'database_tables' => [],
    'loaded_data' => [],
    'user_data' => [],
    'errors' => []
];

try {
    // Get tenant ID from URL
    $tenant_id_slug = get_query_var('tenant_id', '');
    if (empty($tenant_id_slug) && isset($_GET['tenant'])) {
        $tenant_id_slug = sanitize_text_field($_GET['tenant']);
    }
    if (empty($tenant_id_slug)) {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('/\/booking\/([^\/\?]+)/', $request_uri, $matches)) {
            $tenant_id_slug = sanitize_text_field($matches[1]);
        }
    }

    $debug_data['loaded_data']['tenant_slug'] = $tenant_id_slug;

    if (empty($tenant_id_slug)) {
        $debug_data['errors'][] = 'No business specified in URL';
        throw new Exception('No business specified');
    }

    // Find tenant user ID
    global $wpdb;
    $settings_table = MoBooking\Classes\Database::get_table_name('tenant_settings');
    $debug_data['database_tables'][] = $settings_table;

    $tenant_user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $settings_table WHERE setting_name = 'bf_business_slug' AND setting_value = %s",
        $tenant_id_slug
    ));

    if (empty($tenant_user_id)) {
        $user = get_user_by('slug', $tenant_id_slug);
        if ($user) {
            $tenant_user_id = $user->ID;
            $debug_data['loaded_data']['lookup_method'] = 'WordPress user slug';
        }
    } else {
        $debug_data['loaded_data']['lookup_method'] = 'Business slug in settings';
    }

    $tenant_user_id = intval($tenant_user_id);
    $debug_data['user_data']['tenant_user_id'] = $tenant_user_id;

    if (!$tenant_user_id) {
        $debug_data['errors'][] = 'Business not found in database';
        throw new Exception('Business not found');
    }

    // Load settings
    global $mobooking_settings_manager;
    $bf_settings = $mobooking_settings_manager->get_booking_form_settings($tenant_user_id);
    $biz_settings = $mobooking_settings_manager->get_business_settings($tenant_user_id);

    $debug_data['loaded_data']['settings_loaded'] = true;
    $debug_data['database_tables'][] = MoBooking\Classes\Database::get_table_name('services');
    $debug_data['database_tables'][] = MoBooking\Classes\Database::get_table_name('service_options');

    // Form configuration
    $form_config = [
        'enable_location_check' => ($bf_settings['bf_enable_location_check'] ?? '1') === '1',
        'show_progress_bar' => ($bf_settings['bf_show_progress_bar'] ?? '1') === '1',
        'header_text' => $bf_settings['bf_header_text'] ?? 'Book Our Services',
        'theme_color' => $bf_settings['bf_theme_color'] ?? '#1abc9c',
    ];

    // Load preloaded services if location check is disabled
    $preloaded_services = [];
    if (!$form_config['enable_location_check']) {
        global $mobooking_services_manager;
        $preloaded_services = $mobooking_services_manager->get_services_by_tenant_id($tenant_user_id);
        $debug_data['loaded_data']['preloaded_services_count'] = count($preloaded_services);
    }

    // Script localization data
    $script_data = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking_booking_form_nonce'),
        'tenant_id' => $tenant_user_id,
        'form_config' => $form_config,
        'currency' => ['symbol' => $biz_settings['biz_currency_symbol'] ?? '$'],
        'is_debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
        'i18n' => [
            'loading_services' => __('Loading services...', 'mobooking'),
            'select_service' => __('Please select a service to continue.', 'mobooking'),
            'loading_options' => __('Loading service options...', 'mobooking'),
            'booking_submitted' => __('Your booking has been submitted successfully!', 'mobooking'),
        ],
    ];

} catch (Exception $e) {
    $debug_data['errors'][] = 'Exception: ' . $e->getMessage();
    error_log('MoBooking Public Form Error: ' . $e->getMessage());
}

// Enqueue assets
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui-core');
wp_enqueue_script('jquery-ui-datepicker');
wp_enqueue_style('jquery-ui-datepicker', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');

get_header('booking');
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($form_config['header_text'] ?? 'Book Our Services'); ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '<?php echo esc_js($form_config['theme_color'] ?? '#1abc9c'); ?>',
                        'primary-dark': '#16a085',
                        secondary: '#3498db',
                        success: '#27ae60',
                        warning: '#f39c12',
                        danger: '#e74c3c'
                    }
                }
            }
        }
    </script>
    
    <style>
        .step-indicator {
            transition: all 0.3s ease-in-out;
        }
        .step-indicator.active {
            background-color: <?php echo esc_attr($form_config['theme_color'] ?? '#1abc9c'); ?>;
            border-color: <?php echo esc_attr($form_config['theme_color'] ?? '#1abc9c'); ?>;
            color: white;
        }
        .step-indicator.completed {
            background-color: #27ae60;
            border-color: #27ae60;
            color: white;
        }
        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .service-card {
            transition: all 0.3s ease;
        }
        .service-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .service-card.selected {
            border-color: <?php echo esc_attr($form_config['theme_color'] ?? '#1abc9c'); ?>;
            background-color: rgba(26, 188, 156, 0.05);
        }
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid <?php echo esc_attr($form_config['theme_color'] ?? '#1abc9c'); ?>;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .debug-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .debug-section h3 {
            margin: 0 0 15px 0;
            color: #495057;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 16px;
            font-weight: 600;
        }
        .debug-section pre {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 12px;
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
    
    <?php wp_head(); ?>
</head>

<body class="bg-gray-50">

<?php if (!empty($debug_data['errors'])): ?>
    <!-- Error State -->
    <div class="min-h-screen bg-gray-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full mx-4">
            <div class="text-center">
                <div class="text-red-500 text-4xl mb-4">⚠️</div>
                <h1 class="text-xl font-semibold text-gray-900 mb-2">
                    <?php echo count($debug_data['errors']) > 1 ? 'Multiple Issues Found' : 'Issue Found'; ?>
                </h1>
                <div class="text-gray-600 mb-6 text-left">
                    <?php foreach ($debug_data['errors'] as $error): ?>
                        <p class="mb-2">• <?php echo esc_html($error); ?></p>
                    <?php endforeach; ?>
                </div>
                <a href="<?php echo home_url(); ?>" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Go Home
                </a>
            </div>
        </div>
    </div>
    
    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
        <div class="max-w-4xl mx-auto px-4 py-8">
            <div class="debug-section">
                <h3>🔧 MoBooking Debug Information</h3>
                <pre><?php echo esc_html(print_r($debug_data, true)); ?></pre>
            </div>
        </div>
    <?php endif; ?>
    
<?php else: ?>

    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        <?php echo esc_html($biz_settings['biz_name'] ?? $form_config['header_text']); ?>
                    </h1>
                    <p class="text-gray-600 text-sm">
                        <?php echo esc_html($bf_settings['bf_subtitle'] ?? 'Select your service and schedule your appointment'); ?>
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Powered by</div>
                    <div class="font-semibold" style="color: <?php echo esc_attr($form_config['theme_color']); ?>">
                        MoBooking
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Progress Bar -->
    <?php if ($form_config['show_progress_bar']): ?>
    <div class="bg-white border-b">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="step-indicator active flex items-center justify-center w-8 h-8 rounded-full border-2 text-sm font-semibold">1</div>
                    <div class="h-0.5 w-16 bg-gray-300 step-connector"></div>
                    <div class="step-indicator flex items-center justify-center w-8 h-8 rounded-full border-2 border-gray-300 text-gray-500 text-sm font-semibold">2</div>
                    <div class="h-0.5 w-16 bg-gray-300 step-connector"></div>
                    <div class="step-indicator flex items-center justify-center w-8 h-8 rounded-full border-2 border-gray-300 text-gray-500 text-sm font-semibold">3</div>
                    <div class="h-0.5 w-16 bg-gray-300 step-connector"></div>
                    <div class="step-indicator flex items-center justify-center w-8 h-8 rounded-full border-2 border-gray-300 text-gray-500 text-sm font-semibold">4</div>
                </div>
                <div class="text-sm text-gray-600">
                    Step <span id="current-step">1</span> of 4
                </div>
            </div>
            <div class="mt-2">
                <div class="text-xs text-gray-500 flex justify-between">
                    <span>Select Service</span>
                    <span>Service Options</span>
                    <span>Date & Time</span>
                    <span>Contact Info</span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-8">
        
        <!-- Loading State -->
        <div id="loading-state" class="text-center py-12">
            <div class="loading-spinner mx-auto mb-4"></div>
            <p class="text-gray-600"><?php _e('Loading services...', 'mobooking'); ?></p>
        </div>

        <!-- Error State -->
        <div id="error-state" class="hidden bg-red-50 border border-red-200 rounded-lg p-6 text-center">
            <div class="text-red-600 text-4xl mb-2">⚠️</div>
            <h3 class="text-red-800 font-semibold mb-2"><?php _e('Something went wrong', 'mobooking'); ?></h3>
            <p class="text-red-600 mb-4" id="error-message">
                <?php _e('Unable to load services. Please try again.', 'mobooking'); ?>
            </p>
            <button onclick="location.reload()" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                <?php _e('Retry', 'mobooking'); ?>
            </button>
        </div>

        <!-- Success State -->
        <div id="success-state" class="hidden bg-green-50 border border-green-200 rounded-lg p-6 text-center">
            <div class="text-green-600 text-4xl mb-2">✅</div>
            <h3 class="text-green-800 font-semibold mb-2"><?php _e('Booking Submitted!', 'mobooking'); ?></h3>
            <p class="text-green-600 mb-4">
                <?php _e('Your booking request has been submitted successfully. We will contact you soon to confirm.', 'mobooking'); ?>
            </p>
            <button onclick="location.reload()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                <?php _e('Book Another Service', 'mobooking'); ?>
            </button>
        </div>

        <!-- Booking Form -->
        <form id="booking-form" class="hidden space-y-8">
            
            <!-- Step 1: Service Selection -->
            <div class="form-step active bg-white rounded-lg shadow-sm p-6" id="step-1">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    <?php _e('Select Your Service', 'mobooking'); ?>
                </h2>
                <div id="services-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Services loaded via JavaScript -->
                </div>
                <div class="mt-8 flex justify-end">
                    <button type="button" id="step-1-next" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        <?php _e('Continue', 'mobooking'); ?>
                    </button>
                </div>
            </div>

            <!-- Step 2: Service Options -->
            <div class="form-step bg-white rounded-lg shadow-sm p-6" id="step-2">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    <?php _e('Service Options', 'mobooking'); ?>
                </h2>
                <div id="service-options-container">
                    <!-- Service options loaded via JavaScript -->
                </div>
                <div class="mt-8 flex justify-between">
                    <button type="button" id="step-2-back" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        <?php _e('Back', 'mobooking'); ?>
                    </button>
                    <button type="button" id="step-2-next" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                        <?php _e('Continue', 'mobooking'); ?>
                    </button>
                </div>
            </div>

            <!-- Step 3: Date & Time -->
            <div class="form-step bg-white rounded-lg shadow-sm p-6" id="step-3">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    <?php _e('Select Date & Time', 'mobooking'); ?>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Preferred Date', 'mobooking'); ?>
                        </label>
                        <input type="date" id="booking-date" name="booking_date" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Preferred Time', 'mobooking'); ?>
                        </label>
                        <select id="booking-time" name="booking_time" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                            <option value=""><?php _e('Select time...', 'mobooking'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="mt-8 flex justify-between">
                    <button type="button" id="step-3-back" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        <?php _e('Back', 'mobooking'); ?>
                    </button>
                    <button type="button" id="step-3-next" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark transition-colors">
                        <?php _e('Continue', 'mobooking'); ?>
                    </button>
                </div>
            </div>

            <!-- Step 4: Contact Information -->
            <div class="form-step bg-white rounded-lg shadow-sm p-6" id="step-4">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">
                    <?php _e('Contact Information', 'mobooking'); ?>
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('First Name', 'mobooking'); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="customer-first-name" name="customer_first_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Last Name', 'mobooking'); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="customer-last-name" name="customer_last_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Email', 'mobooking'); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="customer-email" name="customer_email" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Phone', 'mobooking'); ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="tel" id="customer-phone" name="customer_phone" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Address', 'mobooking'); ?>
                        </label>
                        <input type="text" id="customer-address" name="customer_address" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                               placeholder="<?php esc_attr_e('Street address, city, postal code', 'mobooking'); ?>">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <?php _e('Special Notes', 'mobooking'); ?>
                        </label>
                        <textarea id="customer-notes" name="customer_notes" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"
                                  placeholder="<?php esc_attr_e('Any special instructions or requests...', 'mobooking'); ?>"></textarea>
                    </div>
                </div>

                <!-- Booking Summary -->
                <div class="mt-8 bg-gray-50 rounded-lg p-4">
                    <h3 class="font-semibold text-gray-900 mb-3"><?php _e('Booking Summary', 'mobooking'); ?></h3>
                    <div id="booking-summary">
                        <!-- Summary populated via JavaScript -->
                    </div>
                </div>

                <div class="mt-8 flex justify-between">
                    <button type="button" id="step-4-back" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        <?php _e('Back', 'mobooking'); ?>
                    </button>
                    <button type="submit" id="submit-booking" class="bg-success text-white px-8 py-2 rounded-lg hover:bg-green-600 transition-colors">
                        <span class="submit-text"><?php _e('Submit Booking', 'mobooking'); ?></span>
                        <span class="submit-loading hidden">
                            <div class="loading-spinner inline-block mr-2"></div>
                            <?php _e('Submitting...', 'mobooking'); ?>
                        </span>
                    </button>
                </div>
            </div>

        </form>

    </main>

    <!-- Debug Section -->
    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="debug-section">
            <h3>🔧 MoBooking Debug Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="bg-white p-4 rounded border">
                    <h4 class="font-semibold mb-2" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">Database Tables Accessed:</h4>
                    <ul class="text-sm">
                        <?php foreach ($debug_data['database_tables'] as $table): ?>
                            <li>• <?php echo esc_html($table); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="bg-white p-4 rounded border">
                    <h4 class="font-semibold mb-2" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">User Data Retrieved:</h4>
                    <ul class="text-sm">
                        <li>• Tenant User ID: <?php echo esc_html($debug_data['user_data']['tenant_user_id'] ?? 'N/A'); ?></li>
                        <li>• Lookup Method: <?php echo esc_html($debug_data['loaded_data']['lookup_method'] ?? 'N/A'); ?></li>
                        <li>• Services Count: <?php echo esc_html($debug_data['loaded_data']['preloaded_services_count'] ?? '0'); ?></li>
                    </ul>
                </div>
            </div>

            <details>
                <summary class="cursor-pointer font-medium text-gray-700 hover:text-gray-900" style="font-family: -apple-system, BlinkMacSystemFont, sans-serif;">
                    Click to view full debug data
                </summary>
                <pre><?php echo esc_html(print_r($debug_data, true)); ?></pre>
            </details>
        </div>
    </div>
    <?php endif; ?>

<?php endif; ?>

<!-- JavaScript -->
<script type="text/javascript">
// Localize script data
window.mobooking_booking_form_params = <?php echo wp_json_encode($script_data ?? []); ?>;

// Preloaded services data
<?php if (!empty($preloaded_services)): ?>
window.MOB_PRELOADED_SERVICES = <?php echo wp_json_encode($preloaded_services); ?>;
<?php endif; ?>

jQuery(document).ready(function($) {
    console.log('MoBooking Public Form: Initializing...');
    
    // Configuration
    const config = window.mobooking_booking_form_params || {};
    const preloadedServices = window.MOB_PRELOADED_SERVICES || null;
    
    // Form state
    let currentStep = 1;
    let selectedService = null;
    let selectedOptions = {};
    let availableTimeSlots = [];
    
    // Debug logging
    function debugLog(message, data = null) {
        if (config.is_debug_mode) {
            console.log(`[MoBooking Debug] ${message}`, data || '');
        }
    }
    
    debugLog('Form initialized', { config, preloadedServices });
    
    // Initialize
    initializeForm();
    
    function initializeForm() {
        if (preloadedServices && preloadedServices.length > 0) {
            debugLog('Using preloaded services', preloadedServices);
            displayServices(preloadedServices);
            hideLoading();
            showForm();
        } else {
            debugLog('Loading services via AJAX');
            loadServices();
        }
        
        bindEventHandlers();
    }
    
    function loadServices() {
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_public_services',
                nonce: config.nonce,
                tenant_id: config.tenant_id
            },
            success: function(response) {
                debugLog('Services AJAX response', response);
                
                if (response.success && response.data) {
                    displayServices(response.data);
                    hideLoading();
                    showForm();
                } else {
                    const errorMsg = response.data?.message || 'Failed to load services';
                    debugLog('Services loading failed', response);
                    showError('Failed to load services: ' + errorMsg);
                }
            },
            error: function(xhr, status, error) {
                debugLog('AJAX error loading services', { xhr, status, error, responseText: xhr.responseText });
                
                let errorMessage = 'Network error loading services. Please check your connection.';
                
                // Try to extract more specific error info
                if (xhr.responseText) {
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.data && errorResponse.data.message) {
                            errorMessage = errorResponse.data.message;
                        }
                    } catch (e) {
                        // responseText is not JSON, use default message
                    }
                }
                
                showError(errorMessage);
            }
        });
    }
    
    function displayServices(services) {
        const grid = $('#services-grid');
        grid.empty();
        
        if (!services || services.length === 0) {
            grid.html(`
                <div class="col-span-full text-center py-8">
                    <div class="text-gray-400 text-4xl mb-4">📋</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Services Available</h3>
                    <p class="text-gray-600">Please contact us directly to make a booking.</p>
                </div>
            `);
            return;
        }
        
        services.forEach(service => {
            const serviceCard = createServiceCard(service);
            grid.append(serviceCard);
        });
        
        debugLog(`Displayed ${services.length} services`);
    }
    
    function createServiceCard(service) {
        // Fix price display
        let price = 'Contact for pricing';
        if (service.price && service.price > 0) {
            if (service.price_formatted) {
                price = `${config.currency.symbol}${service.price_formatted}`;
            } else {
                price = `${config.currency.symbol}${parseFloat(service.price).toFixed(2)}`;
            }
        }
        
        // Fix duration display
        let duration = 'Duration varies';
        if (service.duration && service.duration > 0) {
            duration = `${service.duration} min`;
        }
        
        // Safely get service name and description
        const name = service.name || 'Unnamed Service';
        const description = service.description || '';
        
        return $(`
            <div class="service-card border border-gray-200 rounded-lg p-4 cursor-pointer hover:shadow-lg transition-all" 
                 data-service-id="${service.service_id}">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900 mb-1">${escapeHtml(name)}</h3>
                        <p class="text-sm text-gray-600 mb-2">${escapeHtml(description)}</p>
                    </div>
                    ${service.icon_url ? `<img src="${service.icon_url}" alt="" class="w-8 h-8 ml-2">` : ''}
                </div>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-primary font-semibold">${price}</span>
                    <span class="text-gray-500">${duration}</span>
                </div>
                ${service.options && service.options.length > 0 ? 
                    `<div class="mt-2 text-xs text-gray-500">${service.options.length} options available</div>` : 
                    ''
                }
            </div>
        `);
    }
    
    function bindEventHandlers() {
        // Service selection
        $(document).on('click', '.service-card', function() {
            $('.service-card').removeClass('selected');
            $(this).addClass('selected');
            
            const serviceId = $(this).data('service-id');
            selectedService = preloadedServices ? 
                preloadedServices.find(s => s.service_id == serviceId) : 
                { service_id: serviceId };
                
            debugLog('Service selected', selectedService);
            $('#step-1-next').prop('disabled', false);
        });
        
        // Step navigation
        $('#step-1-next').click(() => goToStep(2));
        $('#step-2-back').click(() => goToStep(1));
        $('#step-2-next').click(() => goToStep(3));
        $('#step-3-back').click(() => goToStep(2));
        $('#step-3-next').click(() => goToStep(4));
        $('#step-4-back').click(() => goToStep(3));
        
        // Date change handler
        $('#booking-date').change(function() {
            const selectedDate = $(this).val();
            if (selectedDate) {
                loadAvailableTimeSlots(selectedDate);
            }
        });
        
        // Form submission
        $('#booking-form').submit(function(e) {
            e.preventDefault();
            submitBooking();
        });
        
        // Store options selections
        $(document).on('change', '.service-option-input', function() {
            const optionId = $(this).data('option-id');
            let value = $(this).val();
            
            if ($(this).is(':checkbox')) {
                if ($(this).is(':checked')) {
                    selectedOptions[optionId] = true;
                } else {
                    delete selectedOptions[optionId];
                }
            } else if ($(this).is(':radio')) {
                if ($(this).is(':checked')) {
                    selectedOptions[optionId] = value;
                } else if (selectedOptions[optionId] === value) {
                    delete selectedOptions[optionId];
                }
            } else if (value && value.trim() !== '') {
                selectedOptions[optionId] = value;
            } else {
                delete selectedOptions[optionId];
            }
            
            debugLog('Option selection updated', { optionId, value, selectedOptions });
        });
    }
    
    function goToStep(stepNumber) {
        debugLog(`Going to step ${stepNumber}`);
        
        // Validate current step
        if (!validateCurrentStep(currentStep)) {
            return;
        }
        
        // Load step-specific data
        if (stepNumber === 2) {
            loadServiceOptions();
        } else if (stepNumber === 3) {
            const today = new Date().toISOString().split('T')[0];
            $('#booking-date').attr('min', today);
        } else if (stepNumber === 4) {
            updateBookingSummary();
        }
        
        // Hide current step
        $('.form-step').removeClass('active');
        
        // Show new step
        $(`#step-${stepNumber}`).addClass('active');
        
        // Update progress indicators
        updateProgressIndicators(stepNumber);
        
        currentStep = stepNumber;
        $('#current-step').text(stepNumber);
        
        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
    
    function validateCurrentStep(step) {
        switch (step) {
            case 1:
                if (!selectedService) {
                    alert(config.i18n.select_service);
                    return false;
                }
                break;
            case 2:
                const requiredOptions = $('.service-option[data-required="true"]');
                for (let option of requiredOptions) {
                    const optionId = $(option).data('option-id');
                    if (!selectedOptions[optionId]) {
                        alert('Please select all required options.');
                        return false;
                    }
                }
                break;
            case 3:
                if (!$('#booking-date').val() || !$('#booking-time').val()) {
                    alert('Please select both date and time.');
                    return false;
                }
                break;
            case 4:
                const requiredFields = ['#customer-first-name', '#customer-last-name', '#customer-email', '#customer-phone'];
                for (let field of requiredFields) {
                    if (!$(field).val().trim()) {
                        alert('Please fill in all required fields.');
                        $(field).focus();
                        return false;
                    }
                }
                break;
        }
        return true;
    }
    
    function loadServiceOptions() {
        const container = $('#service-options-container');
        container.html('<div class="text-center py-8"><div class="loading-spinner mx-auto mb-4"></div><p class="text-gray-600">Loading service options...</p></div>');
        
        debugLog('Selected service for options', selectedService);
        
        if (!selectedService || !selectedService.options || selectedService.options.length === 0) {
            container.html(`
                <div class="text-center py-8">
                    <div class="text-gray-400 text-4xl mb-4">✓</div>
                    <p class="text-gray-600">No additional options for this service.</p>
                </div>
            `);
            return;
        }
        
        let optionsHtml = '';
        selectedService.options.forEach(option => {
            optionsHtml += createOptionHtml(option);
        });
        
        container.html(optionsHtml);
        debugLog('Service options rendered', selectedService.options);
    }
    
    function createOptionHtml(option) {
        const required = option.required || option.is_required;
        const requiredLabel = required ? '<span class="text-red-500">*</span>' : '';
        
        let optionInput = '';
        
        debugLog('Creating option HTML for', option);
        
        switch (option.type) {
            case 'checkbox':
                const priceImpact = option.price_impact_value || option.price_impact || 0;
                optionInput = `
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" class="service-option-input" 
                               data-option-id="${option.option_id}" 
                               data-price-impact="${priceImpact}"
                               ${required ? 'required' : ''}>
                        <span>${escapeHtml(option.name)} ${requiredLabel}</span>
                        ${priceImpact > 0 ? `<span class="text-sm text-gray-500">(+${config.currency.symbol}${priceImpact})</span>` : ''}
                    </label>
                `;
                break;
                
            case 'select':
                let optionValues = [];
                try {
                    if (option.option_values) {
                        if (typeof option.option_values === 'string') {
                            optionValues = JSON.parse(option.option_values);
                        } else if (Array.isArray(option.option_values)) {
                            optionValues = option.option_values;
                        }
                    }
                } catch (e) {
                    debugLog('Error parsing option values', e);
                    optionValues = [];
                }
                
                optionInput = `
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">${escapeHtml(option.name)} ${requiredLabel}</span>
                        <select class="service-option-input mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" 
                                data-option-id="${option.option_id}" ${required ? 'required' : ''}>
                            <option value="">Choose...</option>
                            ${optionValues.map(v => {
                                const value = v.value || v;
                                const label = v.label || v.name || v;
                                const price = v.price || 0;
                                return `<option value="${escapeHtml(value)}" data-price="${price}">${escapeHtml(label)}</option>`;
                            }).join('')}
                        </select>
                    </label>
                `;
                break;
                
            case 'radio':
                let radioValues = [];
                try {
                    if (option.option_values) {
                        if (typeof option.option_values === 'string') {
                            radioValues = JSON.parse(option.option_values);
                        } else if (Array.isArray(option.option_values)) {
                            radioValues = option.option_values;
                        }
                    }
                } catch (e) {
                    debugLog('Error parsing radio values', e);
                    radioValues = [];
                }
                
                optionInput = `
                    <div class="block">
                        <span class="text-sm font-medium text-gray-700">${escapeHtml(option.name)} ${requiredLabel}</span>
                        <div class="mt-1 space-y-2">
                            ${radioValues.map((v, index) => {
                                const value = v.value || v;
                                const label = v.label || v.name || v;
                                const price = v.price || 0;
                                return `
                                    <label class="flex items-center space-x-2">
                                        <input type="radio" name="option_${option.option_id}" 
                                               class="service-option-input" 
                                               data-option-id="${option.option_id}" 
                                               value="${escapeHtml(value)}" 
                                               data-price="${price}"
                                               ${required ? 'required' : ''}>
                                        <span>${escapeHtml(label)}</span>
                                        ${price > 0 ? `<span class="text-sm text-gray-500">(+${config.currency.symbol}${price})</span>` : ''}
                                    </label>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
                break;
                
            case 'text':
                optionInput = `
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">${escapeHtml(option.name)} ${requiredLabel}</span>
                        <input type="text" class="service-option-input mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" 
                               data-option-id="${option.option_id}" 
                               placeholder="${escapeHtml(option.description || '')}"
                               ${required ? 'required' : ''}>
                    </label>
                `;
                break;
                
            case 'number':
            case 'quantity':
                optionInput = `
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">${escapeHtml(option.name)} ${requiredLabel}</span>
                        <input type="number" class="service-option-input mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" 
                               data-option-id="${option.option_id}" 
                               placeholder="${escapeHtml(option.description || '')}"
                               ${required ? 'required' : ''}
                               min="0" step="1">
                    </label>
                `;
                break;
                
            case 'textarea':
                optionInput = `
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">${escapeHtml(option.name)} ${requiredLabel}</span>
                        <textarea class="service-option-input mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" 
                                  data-option-id="${option.option_id}" 
                                  placeholder="${escapeHtml(option.description || '')}"
                                  rows="3"
                                  ${required ? 'required' : ''}></textarea>
                    </label>
                `;
                break;
                
            case 'sqm':
                optionInput = `
                    <label class="block">
                        <span class="text-sm font-medium text-gray-700">${escapeHtml(option.name)} ${requiredLabel}</span>
                        <input type="number" class="service-option-input mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg" 
                               data-option-id="${option.option_id}" 
                               placeholder="Enter square meters"
                               ${required ? 'required' : ''}
                               min="0" step="0.1">
                        <p class="text-xs text-gray-500 mt-1">Price will be calculated based on square meter ranges</p>
                    </label>
                `;
                break;
                
            default:
                optionInput = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                        <p class="text-yellow-800">Option type "${option.type}" not fully supported yet.</p>
                        <p class="text-sm text-gray-600 mt-1">${escapeHtml(option.name)}</p>
                    </div>
                `;
        }
        
        return `
            <div class="service-option border border-gray-200 rounded-lg p-4 mb-4" 
                 data-option-id="${option.option_id}" 
                 data-required="${required}">
                ${optionInput}
                ${option.description ? `<p class="text-sm text-gray-600 mt-1">${escapeHtml(option.description)}</p>` : ''}
            </div>
        `;
    }
    
    function loadAvailableTimeSlots(date) {
        const timeSelect = $('#booking-time');
        timeSelect.html('<option value="">Loading...</option>').prop('disabled', true);
        
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_get_available_slots',
                nonce: config.nonce,
                tenant_id: config.tenant_id,
                service_id: selectedService.service_id,
                date: date
            },
            success: function(response) {
                debugLog('Time slots loaded', response);
                
                timeSelect.prop('disabled', false);
                
                if (response.success && response.data && response.data.length > 0) {
                    timeSelect.html('<option value="">Select time...</option>');
                    response.data.forEach(slot => {
                        timeSelect.append(`<option value="${slot.time}">${slot.display}</option>`);
                    });
                    availableTimeSlots = response.data;
                } else {
                    timeSelect.html('<option value="">No times available</option>');
                    availableTimeSlots = [];
                }
            },
            error: function() {
                timeSelect.html('<option value="">Error loading times</option>').prop('disabled', false);
            }
        });
    }
    
    function updateProgressIndicators(step) {
        $('.step-indicator').each(function(index) {
            const stepNum = index + 1;
            $(this).removeClass('active completed');
            
            if (stepNum < step) {
                $(this).addClass('completed');
            } else if (stepNum === step) {
                $(this).addClass('active');
            }
        });
    }
    
    function updateBookingSummary() {
        let summary = `
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="font-medium">Service:</span>
                    <span>${escapeHtml(selectedService.name)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Date:</span>
                    <span>${$('#booking-date').val()}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium">Time:</span>
                    <span>${$('#booking-time option:selected').text()}</span>
                </div>
        `;
        
        // Add selected options
        $('.service-option-input').each(function() {
            const $input = $(this);
            const optionId = $input.data('option-id');
            const optionName = $input.closest('.service-option').find('label').first().text().replace('*', '').trim();
            
            if ($input.is(':checked') || ($input.is('select') && $input.val()) || ($input.is('input[type="text"], input[type="number"]') && $input.val())) {
                let value = $input.val();
                if ($input.is(':checkbox')) {
                    value = 'Yes';
                }
                
                summary += `
                    <div class="flex justify-between text-sm">
                        <span>${escapeHtml(optionName)}:</span>
                        <span>${escapeHtml(value)}</span>
                    </div>
                `;
            }
        });
        
        // Calculate total price
        let totalPrice = parseFloat(selectedService.price || 0);
        $('.service-option-input').each(function() {
            const $input = $(this);
            const priceImpact = parseFloat($input.data('price-impact') || 0);
            
            if ($input.is(':checked') || ($input.is('select') && $input.val())) {
                if ($input.is('select')) {
                    const selectedOption = $input.find('option:selected');
                    const optionPrice = parseFloat(selectedOption.data('price') || 0);
                    totalPrice += optionPrice;
                } else {
                    totalPrice += priceImpact;
                }
            }
        });
        
        summary += `
                <hr class="my-3">
                <div class="flex justify-between font-semibold text-lg">
                    <span>Total:</span>
                    <span>${config.currency.symbol}${totalPrice.toFixed(2)}</span>
                </div>
            </div>
        `;
        
        $('#booking-summary').html(summary);
    }
    
    function submitBooking() {
        const submitButton = $('#submit-booking');
        const submitText = submitButton.find('.submit-text');
        const submitLoading = submitButton.find('.submit-loading');
        
        // Show loading state
        submitText.addClass('hidden');
        submitLoading.removeClass('hidden');
        submitButton.prop('disabled', true);
        
        // Collect all form data
        const formData = {
            action: 'mobooking_submit_booking',
            nonce: config.nonce,
            tenant_id: config.tenant_id,
            service_id: selectedService.service_id,
            booking_date: $('#booking-date').val(),
            booking_time: $('#booking-time').val(),
            customer_first_name: $('#customer-first-name').val(),
            customer_last_name: $('#customer-last-name').val(),
            customer_email: $('#customer-email').val(),
            customer_phone: $('#customer-phone').val(),
            customer_address: $('#customer-address').val(),
            customer_notes: $('#customer-notes').val(),
            service_options: {}
        };
        
        // Collect service options
        $('.service-option-input').each(function() {
            const $input = $(this);
            const optionId = $input.data('option-id');
            
            if ($input.is(':checkbox')) {
                if ($input.is(':checked')) {
                    formData.service_options[optionId] = true;
                }
            } else if ($input.val()) {
                formData.service_options[optionId] = $input.val();
            }
        });
        
        debugLog('Submitting booking', formData);
        
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                debugLog('Booking submission response', response);
                
                if (response.success) {
                    showSuccess();
                } else {
                    const errorMsg = response.data?.message || 'Unknown error occurred';
                    showError('Booking submission failed: ' + errorMsg);
                    resetSubmitButton();
                }
            },
            error: function(xhr, status, error) {
                debugLog('Booking submission error', { xhr, status, error, responseText: xhr.responseText });
                let errorMessage = 'Network error submitting booking. Please try again.';
                
                // Try to extract more specific error info
                if (xhr.responseText) {
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.data && errorResponse.data.message) {
                            errorMessage = 'Error: ' + errorResponse.data.message;
                        }
                    } catch (e) {
                        // responseText is not JSON, use default message
                    }
                }
                
                showError(errorMessage);
                resetSubmitButton();
            }
        });
    }
    
    function resetSubmitButton() {
        const submitButton = $('#submit-booking');
        const submitText = submitButton.find('.submit-text');
        const submitLoading = submitButton.find('.submit-loading');
        
        submitText.removeClass('hidden');
        submitLoading.addClass('hidden');
        submitButton.prop('disabled', false);
    }
    
    function showError(message) {
        $('#loading-state, #booking-form').hide();
        $('#error-message').text(message);
        $('#error-state').removeClass('hidden');
        debugLog('Error shown', message);
    }
    
    function showSuccess() {
        $('#booking-form').hide();
        $('#success-state').removeClass('hidden');
        debugLog('Success shown');
    }
    
    function hideLoading() {
        $('#loading-state').hide();
    }
    
    function showForm() {
        $('#booking-form').removeClass('hidden');
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php wp_footer(); ?>
</body>
</html>