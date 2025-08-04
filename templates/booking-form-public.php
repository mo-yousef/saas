<?php
/**
 * Rebuilt MoBooking Public Booking Form with Tailwind-like layout
 */

get_header('booking');

// --- Tenant and Settings Initialization ---
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

if (empty($tenant_id_slug)) {
    echo '<div class="mobooking-error"><p>No business specified. Please use a valid booking link.</p></div>';
    get_footer('booking');
    return;
}

global $wpdb;
$settings_table = MoBooking\Classes\Database::get_table_name('tenant_settings');
$tenant_user_id = $wpdb->get_var($wpdb->prepare(
    "SELECT user_id FROM $settings_table WHERE setting_name = 'bf_business_slug' AND setting_value = %s",
    $tenant_id_slug
));

if (empty($tenant_user_id)) {
    $user = get_user_by('slug', $tenant_id_slug);
    if ($user) {
        $tenant_user_id = $user->ID;
    }
}

$tenant_user_id = intval($tenant_user_id);

if (!$tenant_user_id) {
    echo '<div class="mobooking-error"><p>The requested booking form could not be found.</p></div>';
    get_footer('booking');
    return;
}

global $mobooking_services_manager, $mobooking_settings_manager;
$bf_settings = $mobooking_settings_manager->get_booking_form_settings($tenant_user_id);
$biz_settings = $mobooking_settings_manager->get_business_settings($tenant_user_id);

$form_config = [
    'enable_location_check' => ($bf_settings['bf_enable_location_check'] ?? '1') === '1',
    'show_progress_bar'     => ($bf_settings['bf_show_progress_bar'] ?? '1') === '1',
    'header_text'           => $bf_settings['bf_header_text'] ?? 'Book Our Services',
    'theme_color'           => $bf_settings['bf_theme_color'] ?? '#1abc9c',
];

$preloaded_services = [];
if (!$form_config['enable_location_check']) {
    $preloaded_services = $mobooking_services_manager->get_services_by_tenant_id($tenant_user_id);
}

wp_enqueue_style('mobooking-booking-form-refactored', get_theme_file_uri('/assets/css/booking-form-refactored.css'));

wp_localize_script('mobooking-booking-form', 'mobooking_booking_form_params', [
    'ajax_url'       => admin_url('admin-ajax.php'),
    'nonce'          => wp_create_nonce('mobooking_booking_nonce'),
    'tenant_id'      => $tenant_user_id,
    'form_config'    => $form_config,
    'currency'       => ['symbol' => $biz_settings['biz_currency_symbol'] ?? '$'],
    'is_debug_mode'  => defined('WP_DEBUG') && WP_DEBUG,
    'i18n'           => [
        'loading_services' => __('Loading services...', 'mobooking'),
        'select_service'   => __('Please select a service to continue.', 'mobooking'),
    ],
]);

if (!empty($preloaded_services)) {
    echo '<script type="text/javascript">window.MOB_PRELOADED_SERVICES = ' . wp_json_encode($preloaded_services) . ';</script>';
}
?>

<div class="mobooking-form-wrapper bg-gray-100 p-4">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
        <div class="mobooking-header text-center mb-4">
            <h1 class="text-xl font-bold"><?php echo esc_html($form_config['header_text']); ?></h1>
        </div>

        <form id="mobooking-main-form" class="space-y-4">
            <!-- Step 1: Location Check -->
            <div id="mobooking-step-1" class="mobooking-step" data-step="1">
                <div class="mobooking-step-header">
                    <h3><?php esc_html_e('Service Area', 'mobooking'); ?></h3>
                    <p><?php esc_html_e('Enter your location to see if we serve your area.', 'mobooking'); ?></p>
                </div>
                <input type="text" id="mobooking-zip" name="zip_code" class="form-input" placeholder="ZIP Code" required>
            </div>

            <!-- Step 2: Select Service -->
            <div id="mobooking-step-2" class="mobooking-step" data-step="2" style="display: none;">
                <div class="mobooking-step-header">
                    <h3><?php esc_html_e('Choose Your Service', 'mobooking'); ?></h3>
                </div>
                <div id="mobooking-services-container" class="grid grid-cols-1 gap-4"></div>
            </div>

            <!-- Step 3: Service Options -->
            <div id="mobooking-step-3" class="mobooking-step" data-step="3" style="display: none;">
                <div class="mobooking-step-header">
                    <h3><?php esc_html_e('Customize Your Service', 'mobooking'); ?></h3>
                </div>
                <div id="mobooking-service-options" class="space-y-4"></div>
            </div>

            <!-- Step 4: Pet Information -->
            <div id="mobooking-step-4" class="mobooking-step" data-step="4" style="display: none;">
                <div class="mobooking-step-header">
                    <h3><?php esc_html_e('Pet Information', 'mobooking'); ?></h3>
                </div>
                <div class="mobooking-radio-group">
                    <label><input type="radio" name="has_pets" value="yes"> <?php esc_html_e('Yes', 'mobooking'); ?></label>
                    <label><input type="radio" name="has_pets" value="no" checked> <?php esc_html_e('No', 'mobooking'); ?></label>
                </div>
                <div id="pet-details-section" style="display: none;" class="mt-4">
                    <textarea id="pet-details" class="form-input" placeholder="Tell us about your pets"></textarea>
                </div>
            </div>

            <!-- Step 5: Service Frequency -->
            <div id="mobooking-step-5" class="mobooking-step" data-step="5" style="display: none;">
                <div class="mobooking-step-header">
                    <h3><?php esc_html_e('Service Frequency', 'mobooking'); ?></h3>
                </div>
                <div class="mobooking-radio-group">
                    <label><input type="radio" name="service_frequency" value="one-time" checked> <?php esc_html_e('One-Time', 'mobooking'); ?></label>
                    <label><input type="radio" name="service_frequency" value="daily"> <?php esc_html_e('Daily', 'mobooking'); ?></label>
                    <label><input type="radio" name="service_frequency" value="weekly"> <?php esc_html_e('Weekly', 'mobooking'); ?></label>
                    <label><input type="radio" name="service_frequency" value="monthly"> <?php esc_html_e('Monthly', 'mobooking'); ?></label>
                </div>
            </div>

            <!-- Step 6: Date & Time -->
            <div id="mobooking-step-6" class="mobooking-step" data-step="6" style="display: none;">
                <div class="mobooking-step-header">
                    <h3><?php esc_html_e('Date & Time', 'mobooking'); ?></h3>
                </div>
                <input type="text" id="preferred-datetime" class="form-input" placeholder="Select Date & Time" required>
            </div>

            <!-- Step 7: Customer Details & Property Access -->
            <div id="mobooking-step-7" class="mobooking-step" data-step="7" style="display: none;">
                <div class="mobooking-step-header">
                    <h3><?php esc_html_e('Your Information & Access', 'mobooking'); ?></h3>
                </div>
                <div class="space-y-4">
                    <input type="text" id="customer-name" class="form-input" placeholder="Full Name" required>
                    <input type="email" id="customer-email" class="form-input" placeholder="Email Address" required>
                    <input type="tel" id="customer-phone" class="form-input" placeholder="Phone Number" required>
                    <input type="text" id="service-address" class="form-input" placeholder="Service Address" required>
                    <textarea id="special-instructions" class="form-input" placeholder="Special instructions"></textarea>
                </div>
                <div id="mobooking-access-form" class="mt-4">
                     <div class="mobooking-radio-group">
                        <label><input type="radio" name="property_access" value="will-be-home" checked> <?php esc_html_e('I will be home', 'mobooking'); ?></label>
                        <label><input type="radio" name="property_access" value="other"> <?php esc_html_e('Other', 'mobooking'); ?></label>
                    </div>
                    <div id="custom-access-details" style="display: none;" class="mt-4">
                        <textarea id="access-details" class="form-input" placeholder="Provide access details"></textarea>
                    </div>
                </div>
            </div>

            <!-- Step 8: Review & Confirm -->
            <div id="mobooking-step-8" class="mobooking-step" data-step="8" style="display: none;">
                <div class="mobooking-step-header">
                    <h3><?php esc_html_e('Review & Confirm', 'mobooking'); ?></h3>
                </div>
                <div id="mobooking-final-summary"></div>
            </div>

            <!-- Step 9: Success -->
            <div id="mobooking-step-9" class="mobooking-step" data-step="9" style="display: none;">
                <div class="text-center">
                    <h3 class="text-lg font-bold"><?php esc_html_e('Booking Complete!', 'mobooking'); ?></h3>
                    <p><?php esc_html_e('A confirmation has been sent to your email.', 'mobooking'); ?></p>
                </div>
            </div>

            <!-- Navigation -->
            <div class="mobooking-navigation flex justify-between mt-4">
                <button type="button" data-step-back class="btn">Back</button>
                <button type="button" data-step-next class="btn btn-primary">Next</button>
                <button type="button" id="final-submit-btn" class="btn btn-primary" style="display: none;">Confirm Booking</button>
            </div>
        </form>
    </div>
</div>

<?php
get_footer('booking');
?>
