<?php
/**
 * Rebuilt MoBooking Public Booking Form
 *
 * This template provides a structured, multi-step booking form. It has been rebuilt
 * to ensure functionality, maintainability, and correct data flow.
 */

get_header('booking');

// --- Tenant and Settings Initialization ---
$tenant_id_slug = get_query_var('tenant_id', '');
if (empty($tenant_id_slug) && isset($_GET['tenant'])) {
    $tenant_id_slug = sanitize_text_field($_GET['tenant']);
}

if (empty($tenant_id_slug)) {
    // Try to get it from the URL path, e.g., /booking/your-business/
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/\/booking\/([^\/\?]+)/', $request_uri, $matches)) {
        $tenant_id_slug = sanitize_text_field($matches[1]);
    }
}

if (empty($tenant_id_slug)) {
    echo '<div class="mobooking-error"><p>No business specified. Please use a valid booking link, e.g., /booking/your-business-name/.</p></div>';
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
    echo '<div class="mobooking-error"><p>The requested booking form could not be found for "' . esc_html($tenant_id_slug) . '".</p></div>';
    get_footer('booking');
    return;
}

// --- Initialize Managers ---
global $mobooking_services_manager, $mobooking_settings_manager, $mobooking_areas_manager;
if (!$mobooking_services_manager || !$mobooking_settings_manager || !$mobooking_areas_manager) {
    echo '<div class="mobooking-error"><p>System error: Required components could not be loaded.</p></div>';
    get_footer('booking');
    return;
}

// --- Fetch Settings ---
$bf_settings = $mobooking_settings_manager->get_booking_form_settings($tenant_user_id);
$biz_settings = $mobooking_settings_manager->get_business_settings($tenant_user_id);

// --- Form Configuration ---
$form_config = [
    'enable_location_check' => ($bf_settings['bf_enable_location_check'] ?? '1') === '1',
    'show_progress_bar'     => ($bf_settings['bf_show_progress_bar'] ?? '1') === '1',
    'show_pricing'          => ($bf_settings['bf_show_pricing'] ?? '1') === '1',
    'header_text'           => $bf_settings['bf_header_text'] ?? 'Book Our Services',
    'theme_color'           => $bf_settings['bf_theme_color'] ?? '#1abc9c',
    'allow_discount_codes'  => ($bf_settings['bf_allow_discount_codes'] ?? '1') === '1',
];

$business_info = [
    'name'  => $biz_settings['biz_name'] ?? 'Our Business',
    'phone' => $biz_settings['biz_phone'] ?? '',
];

$currency = [
    'symbol' => $biz_settings['biz_currency_symbol'] ?? '$',
    'code'   => $biz_settings['biz_currency_code'] ?? 'USD',
];

// --- Preload services if location check is disabled ---
$preloaded_services = [];
if (!$form_config['enable_location_check']) {
    // This assumes get_public_services() is a method in the services manager
    $preloaded_services = $mobooking_services_manager->get_services_by_tenant_id($tenant_user_id);
}

// Pass data to JavaScript. The JS file expects 'mobooking_booking_form_params'.
wp_localize_script('mobooking-booking-form', 'mobooking_booking_form_params', [
    'ajax_url'       => admin_url('admin-ajax.php'),
    'nonce'          => wp_create_nonce('mobooking_booking_nonce'),
    'tenant_id'      => $tenant_user_id,
    'form_config'    => $form_config,
    'currency'       => $currency,
    'is_debug_mode'  => defined('WP_DEBUG') && WP_DEBUG,
    'i18n'           => [
        'loading_services'        => __('Loading services...', 'mobooking'),
        'no_services'             => __('No services available at the moment.', 'mobooking'),
        'service_available'       => __('Great! We serve your area.', 'mobooking'),
        'service_not_available'   => __('Sorry, we do not currently service your area.', 'mobooking'),
        'checking_availability'   => __('Checking...', 'mobooking'),
        'zip_required'            => __('Please enter your ZIP code.', 'mobooking'),
        'country_required'        => __('Please select your country.', 'mobooking'),
        'select_service'          => __('Please select a service to continue.', 'mobooking'),
        'network_error'           => __('A network error occurred. Please try again.', 'mobooking'),
    ],
]);

// Also provide services in the global scope as the JS file expects it.
if (!empty($preloaded_services)) {
    echo '<script type="text/javascript">';
    echo 'window.MOB_PRELOADED_SERVICES = ' . wp_json_encode($preloaded_services) . ';';
    echo '</script>';
}

?>

<div id="mobooking-form-wrapper" class="mobooking-form-wrapper">

    <!-- Progress Bar -->
    <?php if ($form_config['show_progress_bar']): ?>
    <div class="mobooking-progress-bar-wrapper">
        <div class="mobooking-progress-steps">
            <!-- JS will populate this -->
        </div>
        <div class="mobooking-progress-line">
            <div class="mobooking-progress-line-fill"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="mobooking-header">
        <h1><?php echo esc_html($form_config['header_text']); ?></h1>
        <?php if (!empty($business_info['name'])): ?>
            <h2><?php echo esc_html($business_info['name']); ?></h2>
        <?php endif; ?>
         <?php if (!empty($business_info['phone'])): ?>
            <p class="business-phone">
                <a href="tel:<?php echo esc_attr($business_info['phone']); ?>"><?php echo esc_html($business_info['phone']); ?></a>
            </p>
        <?php endif; ?>
    </div>

    <div id="mobooking-feedback-global" class="mobooking-feedback" style="display: none;"></div>

    <form id="mobooking-main-form" onsubmit="return false;">

        <!-- Step 1: Location Check -->
        <div id="mobooking-step-1" class="mobooking-step" data-step="1">
            <div class="mobooking-step-header">
                <h3><?php esc_html_e('Service Area', 'mobooking'); ?></h3>
                <p><?php esc_html_e('Enter your location to see if we serve your area.', 'mobooking'); ?></p>
            </div>
            <form id="mobooking-location-form">
                <div class="mobooking-form-group">
                    <label for="mobooking-zip"><?php esc_html_e('ZIP Code', 'mobooking'); ?></label>
                    <input type="text" id="mobooking-zip" name="zip_code" class="mobooking-input" required>
                </div>
                <div class="mobooking-form-group">
                     <label for="mobooking-country"><?php esc_html_e('Country', 'mobooking'); ?></label>
                    <input type="text" id="mobooking-country" name="country_code" class="mobooking-input" value="US" required> <!-- Default to US -->
                </div>
                <div id="mobooking-location-feedback" class="mobooking-feedback"></div>
                <button type="submit" class="mobooking-btn mobooking-btn-primary mobooking-btn-block"><?php esc_html_e('Check Availability', 'mobooking'); ?></button>
            </form>
        </div>

        <!-- Step 2: Select Service -->
        <div id="mobooking-step-2" class="mobooking-step" data-step="2" style="display: none;">
            <div class="mobooking-step-header">
                <h3><?php esc_html_e('Choose Your Service', 'mobooking'); ?></h3>
                <p><?php esc_html_e('Select one of our services to get started.', 'mobooking'); ?></p>
            </div>
            <div id="mobooking-services-container" class="mobooking-services-container">
                <div class="mobooking-loading">
                    <div class="mobooking-spinner"></div>
                    <p><?php esc_html_e('Loading services...', 'mobooking'); ?></p>
                </div>
            </div>
             <div id="mobooking-services-feedback" class="mobooking-feedback"></div>
        </div>

        <!-- Step 3: Service Options -->
        <div id="mobooking-step-3" class="mobooking-step" data-step="3" style="display: none;">
            <div class="mobooking-step-header">
                <h3><?php esc_html_e('Customize Your Service', 'mobooking'); ?></h3>
                <p><?php esc_html_e('Add any extra options you need.', 'mobooking'); ?></p>
            </div>
            <div id="mobooking-service-options"></div>
        </div>

        <!-- Step 4: Customer Details & Date/Time -->
        <div id="mobooking-step-4" class="mobooking-step" data-step="4" style="display: none;">
            <div class="mobooking-step-header">
                <h3><?php esc_html_e('Your Information', 'mobooking'); ?></h3>
                <p><?php esc_html_e('Tell us about you and when you\'d like service.', 'mobooking'); ?></p>
            </div>
            <div id="mobooking-details-form">
                <input type="text" id="customer-name" placeholder="Full Name" class="mobooking-input" required>
                <input type="email" id="customer-email" placeholder="Email Address" class="mobooking-input" required>
                <input type="tel" id="customer-phone" placeholder="Phone Number" class="mobooking-input" required>
                <input type="text" id="service-address" placeholder="Full Service Address" class="mobooking-input" required>
                <input type="text" id="preferred-datetime" placeholder="Select Date & Time" class="mobooking-input" required>
                <textarea id="special-instructions" placeholder="Any special instructions?" class="mobooking-textarea"></textarea>
            </div>
            <div id="mobooking-details-feedback" class="mobooking-feedback"></div>
        </div>

        <!-- Step 5: Review & Confirm -->
        <div id="mobooking-step-5" class="mobooking-step" data-step="5" style="display: none;">
            <div class="mobooking-step-header">
                <h3><?php esc_html_e('Review & Confirm', 'mobooking'); ?></h3>
            </div>
            <div id="mobooking-final-summary"></div>
            <?php if ($form_config['allow_discount_codes']): ?>
            <div class="mobooking-discount-section">
                <input type="text" id="discount-code" placeholder="Discount Code" class="mobooking-input">
                <button type="button" id="apply-discount-btn" class="mobooking-btn"><?php esc_html_e('Apply', 'mobooking'); ?></button>
                <div id="discount-feedback" class="mobooking-feedback"></div>
            </div>
            <?php endif; ?>
            <div id="mobooking-review-feedback" class="mobooking-feedback"></div>
        </div>

        <!-- Step 6: Success -->
        <div id="mobooking-step-6" class="mobooking-step" data-step="6" style="display: none;">
            <div class="mobooking-success-message">
                <h3><?php esc_html_e('Booking Complete!', 'mobooking'); ?></h3>
                <p><?php esc_html_e('Thank you for your booking. A confirmation has been sent to your email.', 'mobooking'); ?></p>
                <div id="success-details"></div>
                <button type="button" onclick="location.reload();" class="mobooking-btn mobooking-btn-primary"><?php esc_html_e('Make Another Booking', 'mobooking'); ?></button>
            </div>
        </div>

        <!-- Summary Sidebar (always visible from step 3) -->
        <div id="mobooking-summary-sidebar" class="mobooking-summary-sidebar" style="display: none;">
            <h4><?php esc_html_e('Booking Summary', 'mobooking'); ?></h4>
            <div id="mobooking-summary-content"></div>
            <div class="pricing-summary">
                <div class="pricing-row"><span><?php esc_html_e('Subtotal', 'mobooking'); ?></span><span id="pricing-subtotal">$0.00</span></div>
                <div class="pricing-row discount-applied hidden"><span><?php esc_html_e('Discount', 'mobooking'); ?></span><span id="pricing-discount">-$0.00</span></div>
                <div class="pricing-row total"><span><?php esc_html_e('Total', 'mobooking'); ?></span><span id="pricing-total">$0.00</span></div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="mobooking-navigation">
            <button type="button" data-step-back class="mobooking-btn" style="display: none;"><?php esc_html_e('Back', 'mobooking'); ?></button>
            <button type="button" data-step-next class="mobooking-btn mobooking-btn-primary"><?php esc_html_e('Next', 'mobooking'); ?></button>
            <button type="button" id="final-submit-btn" class="mobooking-btn mobooking-btn-primary" style="display: none;"><?php esc_html_e('Confirm Booking', 'mobooking'); ?></button>
        </div>
    </form>
</div>

<!-- Re-add the dynamic styles -->
<style type="text/css">
:root {
    --mobooking-primary-color: <?php echo esc_attr($bf_settings['bf_theme_color'] ?? '#1abc9c'); ?>;
    --mobooking-border-radius: <?php echo esc_attr($bf_settings['bf_border_radius'] ?? '4'); ?>px;
}
/* Add some base styles for the new structure */
.mobooking-form-wrapper { max-width: 800px; margin: 2rem auto; font-family: sans-serif; }
.mobooking-header { text-align: center; margin-bottom: 2rem; }
.mobooking-step { margin-bottom: 1.5rem; }
.mobooking-step-header { margin-bottom: 1rem; }
.mobooking-input, .mobooking-textarea { width: 100%; padding: 0.75rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: var(--mobooking-border-radius); }
.mobooking-btn { padding: 0.75rem 1.5rem; border: none; background-color: #eee; border-radius: var(--mobooking-border-radius); cursor: pointer; }
.mobooking-btn-primary { background-color: var(--mobooking-primary-color); color: white; }
.mobooking-navigation { display: flex; justify-content: space-between; margin-top: 2rem; }
.mobooking-feedback { padding: 1rem; margin-bottom: 1rem; border-radius: var(--mobooking-border-radius); display: none; }
.mobooking-feedback.error { background-color: #f8d7da; color: #721c24; }
.mobooking-feedback.success { background-color: #d4edda; color: #155724; }
.mobooking-feedback.info { background-color: #cce5ff; color: #004085; }
.mobooking-services-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; }
.mobooking-service-card { border: 2px solid #eee; padding: 1rem; border-radius: var(--mobooking-border-radius); cursor: pointer; }
.mobooking-service-card.selected { border-color: var(--mobooking-primary-color); }
.mobooking-spinner { border: 4px solid #f3f3f3; border-top: 4px solid var(--mobooking-primary-color); border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 2rem auto; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<?php
get_footer('booking');
?>
