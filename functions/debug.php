<?php
// Add this debug function to your functions.php temporarily to troubleshoot
function nordbooking_debug_booking_form_access() {
    if (!current_user_can('manage_options')) {
        return; // Only allow admins to see debug info
    }

    if (isset($_GET['nordbooking_debug']) && $_GET['nordbooking_debug'] === '1') {
        global $wpdb;

        echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
        echo '<h2>NORDBOOKING Debug Information</h2>';

        // Check if rewrite rules are working
        echo '<h3>1. Rewrite Rules Check</h3>';
        $rules = get_option('rewrite_rules');
        $booking_rules = array_filter($rules, function($key) {
            return strpos($key, 'booking') !== false;
        }, ARRAY_FILTER_USE_KEY);
        echo '<pre>Booking-related rewrite rules: ' . print_r($booking_rules, true) . '</pre>';

        // Check current user settings
        echo '<h3>2. Current User Settings</h3>';
        $user_id = get_current_user_id();
        $settings_table = \NORDBOOKING\Classes\Database::get_table_name('tenant_settings');
        $user_settings = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$settings_table} WHERE user_id = %d AND setting_name LIKE 'bf_%'",
            $user_id
        ));
        echo '<pre>Current user booking form settings: ' . print_r($user_settings, true) . '</pre>';

        // Check all business slugs
        echo '<h3>3. All Business Slugs</h3>';
        $all_slugs = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, setting_value FROM {$settings_table} WHERE setting_name = %s",
            'bf_business_slug'
        ));
        echo '<pre>All business slugs: ' . print_r($all_slugs, true) . '</pre>';

        // Test slug lookup
        echo '<h3>4. Test Slug Lookup</h3>';
        if (!empty($user_settings)) {
            foreach ($user_settings as $setting) {
                if ($setting->setting_name === 'bf_business_slug' && !empty($setting->setting_value)) {
                    $test_user_id = nordbooking_get_user_id_by_slug($setting->setting_value);
                    echo '<p>Testing slug "' . $setting->setting_value . '" returns user_id: ' . ($test_user_id ?: 'NULL') . '</p>';

                    // Test the actual URL
                    $test_url = home_url('/' . $setting->setting_value . '/booking/');
                    echo '<p>Expected booking URL: <a href="' . $test_url . '" target="_blank">' . $test_url . '</a></p>';
                }
            }
        }

        // Check template file existence
        echo '<h3>5. Template File Check</h3>';
        $template_path = get_template_directory() . '/templates/booking-form-public.php';
        echo '<p>Template exists: ' . (file_exists($template_path) ? 'YES' : 'NO') . '</p>';
        echo '<p>Template path: ' . $template_path . '</p>';

        echo '</div>';
    }
}
add_action('wp_footer', 'nordbooking_debug_booking_form_access');
add_action('admin_footer', 'nordbooking_debug_booking_form_access');
?>
