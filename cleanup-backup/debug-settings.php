<?php
/**
 * Debug Settings Page Issues
 * Add this to your WordPress admin to test settings functionality
 */

// Add this to your functions.php temporarily or create as a separate file
add_action('wp_ajax_nordbooking_debug_settings', 'nordbooking_debug_settings_callback');

function nordbooking_debug_settings_callback() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'User not logged in']);
        return;
    }

    // Check nonce
    if (!check_ajax_referer('nordbooking_dashboard_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
        return;
    }

    // Check if Settings class exists
    if (!class_exists('\NORDBOOKING\Classes\Settings')) {
        wp_send_json_error(['message' => 'Settings class not found']);
        return;
    }

    // Test Settings class instantiation
    try {
        $settings = new \NORDBOOKING\Classes\Settings();
        $user_id = get_current_user_id();
        
        // Test getting settings
        $biz_settings = $settings->get_business_settings($user_id);
        
        wp_send_json_success([
            'message' => 'Settings debug successful',
            'user_id' => $user_id,
            'settings_count' => count($biz_settings),
            'sample_settings' => array_slice($biz_settings, 0, 3, true)
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Settings error: ' . $e->getMessage()]);
    }
}

// Add debug JavaScript to settings page
add_action('wp_footer', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'nordbooking-settings') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('=== NORDBOOKING Settings Debug ===');
            
            // Test AJAX endpoint
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'nordbooking_debug_settings',
                    nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>'
                },
                success: function(response) {
                    console.log('Debug AJAX success:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Debug AJAX error:', xhr, status, error);
                }
            });
            
            // Check if required elements exist
            console.log('Form exists:', $('#NORDBOOKING-business-settings-form').length > 0);
            console.log('Save buttons exist:', $('#NORDBOOKING-save-biz-settings-btn').length > 0);
            console.log('Tabs exist:', $('.nav-tab-wrapper .nav-tab').length);
            console.log('Tab contents exist:', $('.settings-tab-content').length);
            
            // Check if scripts are loaded
            console.log('jQuery loaded:', typeof $ !== 'undefined');
            console.log('WordPress Color Picker loaded:', typeof $.fn.wpColorPicker !== 'undefined');
            console.log('showAlert available:', typeof window.showAlert !== 'undefined');
            console.log('Business settings params loaded:', typeof nordbooking_biz_settings_params !== 'undefined');
            
            if (typeof nordbooking_biz_settings_params !== 'undefined') {
                console.log('AJAX URL:', nordbooking_biz_settings_params.ajax_url);
                console.log('Nonce:', nordbooking_biz_settings_params.nonce);
            }
        });
        </script>
        <?php
    }
});
?>