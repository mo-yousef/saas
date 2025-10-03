<?php
namespace NORDBOOKING\Classes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Settings {
    private $wpdb;
    private static $default_tenant_settings = null;

    public static function get_default_settings() {
        if (self::$default_tenant_settings === null) {
            self::$default_tenant_settings = [
                // Booking Form Settings (prefix bf_)
                'bf_theme_color'              => '#1abc9c',
                'bf_secondary_color'          => '#34495e',
                'bf_background_color'         => '#ffffff',
                'bf_font_family'              => 'system-ui',
                'bf_border_radius'            => '8',
                'bf_header_text'              => 'Book Our Services Online',
                'bf_progress_display_style'   => 'bar',
                'bf_allow_cancellation_hours' => '24',
                'bf_custom_css'               => '',
                'bf_terms_conditions_url'     => '',
                'bf_business_slug'            => '',
                'bf_step_1_title'             => 'Enter your postal code',
                'bf_step_2_title'             => 'Select service',
                'bf_step_3_title'             => 'Cleaning information',
                'bf_step_4_title'             => 'Do you have any pets?',
                'bf_step_5_title'             => 'Service Frequency',
                'bf_step_6_title'             => 'Select date and time',
                'bf_step_7_title'             => 'Contact & Access Details',
                'bf_step_8_title'             => 'Booking Confirmed',
                'bf_success_message'          => 'Thank you for your booking! We will contact you soon to confirm the details. A confirmation email has been sent to you.',
                'bf_enable_pet_information'     => '1',
                'bf_enable_service_frequency'   => '1',
                'bf_enable_datetime_selection'  => '1',
                'bf_enable_property_access'     => '1',
                'bf_form_enabled'             => '1',
                'bf_maintenance_message'      => 'We are temporarily not accepting new bookings. Please check back later or contact us directly.',
                'bf_allow_service_selection'  => '1',
                'bf_allow_date_time_selection'=> '1',
                'bf_require_phone'            => '1',
                'bf_allow_special_instructions'=> '1',
                'bf_show_pricing'             => '1',
                'bf_allow_discount_codes'     => '1',
                'bf_booking_lead_time_hours'  => '24',
                'bf_max_booking_days_ahead'   => '30',
                'bf_time_slot_duration'       => '30',
                'bf_enable_location_check'    => '1',
                'bf_google_analytics_id'      => '',
                'bf_webhook_url'              => '',
                'bf_enable_recaptcha'         => '0',
                'bf_enable_ssl_required'      => '1',
                'bf_debug_mode'               => '0',
                'bf_service_card_display'     => 'image',

                // Business Settings
                'biz_name'                            => '',
                'biz_email'                           => '',
                'biz_phone'                           => '',
                'biz_address'                         => '',
                'biz_logo_url'                        => '',
                'biz_hours_json'                      => '{}',
                'biz_currency_code'                   => 'USD',
                'biz_user_language'                   => 'en_US',

                // Email Sender Settings
                'email_from_name'                     => '',
                'email_from_address'                  => '',

                // Email Notification Settings
                'email_booking_confirmation_customer_enabled'    => '1',
                'email_booking_confirmation_customer_recipient'  => '',
                'email_booking_confirmation_customer_use_primary' => '1',
                
                'email_booking_confirmation_admin_enabled'       => '1',
                'email_booking_confirmation_admin_recipient'     => '',
                'email_booking_confirmation_admin_use_primary'   => '1',
                
                'email_staff_assignment_enabled'                 => '1',
                'email_staff_assignment_recipient'               => '',
                'email_staff_assignment_use_primary'             => '1',
                
                'email_welcome_enabled'                          => '1',
                'email_welcome_recipient'                        => '',
                'email_welcome_use_primary'                      => '1',
                
                'email_invitation_enabled'                       => '1',
                'email_invitation_recipient'                     => '',
                'email_invitation_use_primary'                   => '1',
            ];
        }
        return self::$default_tenant_settings;
    }

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    public function register_ajax_actions() {
        // Booking Form Settings
        add_action('wp_ajax_nordbooking_get_booking_form_settings', [$this, 'handle_get_booking_form_settings_ajax']);
        add_action('wp_ajax_nordbooking_save_booking_form_settings', [$this, 'handle_save_booking_form_settings_ajax']);

        // Business Settings
        add_action('wp_ajax_nordbooking_get_business_settings', [$this, 'handle_get_business_settings_ajax']);
        add_action('wp_ajax_nordbooking_save_business_settings', [$this, 'handle_save_business_settings_ajax']);

        // Utility Actions
        add_action('wp_ajax_nordbooking_flush_rewrite_rules', [$this, 'handle_flush_rewrite_rules_ajax']);
        add_action('wp_ajax_nordbooking_send_test_email', [$this, 'handle_send_test_email_ajax']);
        add_action('wp_ajax_nordbooking_upload_logo', [$this, 'handle_upload_logo_ajax']);
    }

    public function handle_flush_rewrite_rules_ajax() {
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce'); // Use existing general dashboard nonce

        if (!current_user_can('manage_options')) { // Typically, only admins should flush rules
            wp_send_json_error(['message' => __('You do not have permission to flush rewrite rules.', 'NORDBOOKING')], 403);
            return;
        }

        // Defer rewrite rule flushing to the 'shutdown' action hook.
        // This is the recommended way to flush rules to avoid issues with the $wp_rewrite global object state.
        update_option('nordbooking_flush_rewrite_rules_flag', true);

        // Re-register our rules so they are definitely part of the flush
        // Assuming nordbooking_add_rewrite_rules() is the function that sets them up and is hooked to init.
        // We need to ensure it's callable or directly call the relevant part if not hooked to init in a way that runs now.
        // For now, we rely on the next init call to register them before shutdown flushes.
        // A more direct way would be to call the function that contains add_rewrite_rule() here if possible and safe.
        // However, the flag and shutdown hook is generally safer for flushing.

        // The actual flushing will be done by a function hooked to 'shutdown' if the flag is true.
        // We need to add that function in functions.php or similar.

        wp_send_json_success(['message' => __('Rewrite rules will be flushed. This may take a moment to reflect on your site.', 'NORDBOOKING')]);
    }

    public function handle_send_test_email_ajax() {
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not authenticated.', 'NORDBOOKING')], 403);
            return;
        }

        // Get current user email and business settings
        $user_info = get_userdata($user_id);
        $business_settings = $this->get_business_settings($user_id);
        
        $to_email = $user_info->user_email;
        $from_name = !empty($business_settings['email_from_name']) ? $business_settings['email_from_name'] : get_bloginfo('name');
        $from_email = !empty($business_settings['email_from_address']) ? $business_settings['email_from_address'] : get_option('admin_email');
        
        $subject = sprintf(__('Test Email from %s', 'NORDBOOKING'), $from_name);
        $message = sprintf(
            __("Hello!\n\nThis is a test email from your NORDBOOKING system to verify that email notifications are working correctly.\n\nSent at: %s\n\nBest regards,\n%s", 'NORDBOOKING'),
            current_time('Y-m-d H:i:s'),
            $from_name
        );
        
        // Set headers
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            sprintf('From: %s <%s>', $from_name, $from_email)
        ];
        
        // Send the email
        $sent = wp_mail($to_email, $subject, $message, $headers);
        
        if ($sent) {
            wp_send_json_success([
                'message' => sprintf(__('Test email sent successfully to %s', 'NORDBOOKING'), $to_email)
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to send test email. Please check your email configuration.', 'NORDBOOKING')
            ], 500);
        }
    }

    public function handle_upload_logo_ajax() {
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not authenticated.', 'NORDBOOKING')], 403);
            return;
        }

        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('No file uploaded or upload error.', 'NORDBOOKING')], 400);
            return;
        }

        $file = $_FILES['logo'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(['message' => __('Invalid file type. Please upload a JPEG, PNG, GIF, or WebP image.', 'NORDBOOKING')], 400);
            return;
        }
        
        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_size) {
            wp_send_json_error(['message' => __('File too large. Maximum size is 5MB.', 'NORDBOOKING')], 400);
            return;
        }

        // Use WordPress media handling
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        // Handle the upload
        $upload_overrides = [
            'test_form' => false,
            'unique_filename_callback' => function($dir, $name, $ext) use ($user_id) {
                return 'nordbooking-logo-' . $user_id . '-' . time() . $ext;
            }
        ];

        $uploaded_file = wp_handle_upload($file, $upload_overrides);

        if (isset($uploaded_file['error'])) {
            wp_send_json_error(['message' => $uploaded_file['error']], 500);
            return;
        }

        // Create attachment
        $attachment = [
            'post_mime_type' => $uploaded_file['type'],
            'post_title' => sanitize_file_name(pathinfo($uploaded_file['file'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attachment_id = wp_insert_attachment($attachment, $uploaded_file['file']);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => __('Failed to create attachment.', 'NORDBOOKING')], 500);
            return;
        }

        // Generate metadata
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded_file['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        // Save the logo URL to user settings
        $logo_url = $uploaded_file['url'];
        $this->save_business_settings($user_id, ['biz_logo_url' => $logo_url]);

        wp_send_json_success([
            'url' => $logo_url,
            'attachment_id' => $attachment_id,
            'message' => __('Logo uploaded successfully.', 'NORDBOOKING')
        ]);
    }

    public function handle_get_business_settings_ajax() {
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not authenticated.', 'NORDBOOKING')], 403);
            return;
        }
        $settings = $this->get_business_settings($user_id);
        
        // Include personal details
        $user_meta = get_user_meta($user_id);
        $settings['first_name'] = isset($user_meta['first_name'][0]) ? $user_meta['first_name'][0] : '';
        $settings['last_name'] = isset($user_meta['last_name'][0]) ? $user_meta['last_name'][0] : '';
        $settings['primary_email'] = get_userdata($user_id)->user_email;
        
        wp_send_json_success(['settings' => $settings]);
    }

    public function handle_save_business_settings_ajax() {
        try {
            // Log the start of the function
            error_log('[NORDBOOKING Settings] AJAX save handler started');
            
            // Verify nonce
            if (!check_ajax_referer('nordbooking_dashboard_nonce', 'nonce', false)) {
                error_log('[NORDBOOKING Settings] Nonce verification failed');
                wp_send_json_error(['message' => __('Security check failed.', 'NORDBOOKING')], 403);
                return;
            }
            
            $user_id = get_current_user_id();
            if (!$user_id) {
                error_log('[NORDBOOKING Settings] User not authenticated');
                wp_send_json_error(['message' => __('User not authenticated.', 'NORDBOOKING')], 403);
                return;
            }

            $settings_data = isset($_POST['settings']) ? (array) $_POST['settings'] : [];
            error_log('[NORDBOOKING Settings] Received settings data: ' . print_r($settings_data, true));

            if (empty($settings_data)) {
                error_log('[NORDBOOKING Settings] No settings data received');
                wp_send_json_error(['message' => __('No settings data received.', 'NORDBOOKING')], 400);
                return;
            }

            // Handle personal details FIRST, before validation (which might filter them out)
            $personal_details_updated = true;
            $personal_details_errors = [];
            
            error_log('[NORDBOOKING Settings] Processing personal details for user: ' . $user_id);
            
            // Handle first_name directly from raw settings
            if (isset($settings_data['first_name'])) {
                $first_name = sanitize_text_field($settings_data['first_name']);
                error_log('[NORDBOOKING Settings] Processing first_name: ' . $first_name);
                
                $update_result = update_user_meta($user_id, 'first_name', $first_name);
                error_log('[NORDBOOKING Settings] first_name update result: ' . var_export($update_result, true));
                
                if ($update_result === false) {
                    $personal_details_updated = false;
                    $personal_details_errors[] = 'first_name';
                    error_log('[NORDBOOKING Settings] FAILED to update first_name');
                }
                
                // Remove from settings_data so it doesn't interfere with business settings
                unset($settings_data['first_name']);
            }
            
            // Handle last_name directly from raw settings
            if (isset($settings_data['last_name'])) {
                $last_name = sanitize_text_field($settings_data['last_name']);
                error_log('[NORDBOOKING Settings] Processing last_name: ' . $last_name);
                
                $update_result = update_user_meta($user_id, 'last_name', $last_name);
                error_log('[NORDBOOKING Settings] last_name update result: ' . var_export($update_result, true));
                
                if ($update_result === false) {
                    $personal_details_updated = false;
                    $personal_details_errors[] = 'last_name';
                    error_log('[NORDBOOKING Settings] FAILED to update last_name');
                }
                
                // Remove from settings_data so it doesn't interfere with business settings
                unset($settings_data['last_name']);
            }
            
            error_log('[NORDBOOKING Settings] Personal details status: ' . ($personal_details_updated ? 'SUCCESS' : 'FAILED'));
            if (!empty($personal_details_errors)) {
                error_log('[NORDBOOKING Settings] Personal details errors: ' . implode(', ', $personal_details_errors));
            }

            // Now validate and sanitize the remaining business settings
            $validated_settings = $this->validate_and_sanitize_business_settings($settings_data);
            if (is_wp_error($validated_settings)) {
                error_log('[NORDBOOKING Settings] Validation failed: ' . $validated_settings->get_error_message());
                wp_send_json_error(['message' => $validated_settings->get_error_message()], 400);
                return;
            }

            // Remove non-business settings from the data
            $non_business_fields = ['nordbooking_dashboard_nonce_field', '_wp_http_referer', 'nonce', 'action'];
            foreach ($non_business_fields as $field) {
                unset($validated_settings[$field]);
            }

            error_log('[NORDBOOKING Settings] Cleaned settings data: ' . print_r($validated_settings, true));
            error_log('[NORDBOOKING Settings] Personal details updated: ' . ($personal_details_updated ? 'true' : 'false'));

            // Check if tenant_settings table exists
            $table_name = \NORDBOOKING\Classes\Database::get_table_name('tenant_settings');
            $table_exists = $this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
            
            if (!$table_exists) {
                error_log('[NORDBOOKING Settings] Table does not exist: ' . $table_name);
                wp_send_json_error(['message' => __('Database table missing. Please contact support.', 'NORDBOOKING')], 500);
                return;
            }

            $business_settings_result = $this->save_business_settings($user_id, $validated_settings);
            error_log('[NORDBOOKING Settings] Business settings result: ' . ($business_settings_result ? 'success' : 'failed'));

            $overall_result = $business_settings_result && $personal_details_updated;

            if ($overall_result) {
                wp_send_json_success(['message' => __('Settings saved successfully.', 'NORDBOOKING')]);
            } else {
                $error_details = [];
                if (!$business_settings_result) {
                    $error_details[] = 'business settings';
                }
                if (!$personal_details_updated) {
                    $error_details[] = 'personal details (' . implode(', ', $personal_details_errors) . ')';
                }
                
                $error_message = __('Failed to save: ', 'NORDBOOKING') . implode(', ', $error_details);
                error_log('[NORDBOOKING Settings] Final error: ' . $error_message);
                wp_send_json_error(['message' => $error_message], 500);
            }
            
        } catch (Exception $e) {
            error_log('[NORDBOOKING Settings] Exception in AJAX handler: ' . $e->getMessage());
            error_log('[NORDBOOKING Settings] Exception trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => __('An unexpected error occurred: ', 'NORDBOOKING') . $e->getMessage()], 500);
        } catch (Error $e) {
            error_log('[NORDBOOKING Settings] Fatal error in AJAX handler: ' . $e->getMessage());
            error_log('[NORDBOOKING Settings] Fatal error trace: ' . $e->getTraceAsString());
            wp_send_json_error(['message' => __('A fatal error occurred. Please check server logs.', 'NORDBOOKING')], 500);
        }
    }

    public function handle_get_booking_form_settings_ajax() {
        check_ajax_referer('nordbooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not authenticated.', 'NORDBOOKING')], 403);
            return;
        }
        $settings = $this->get_booking_form_settings($user_id);
        wp_send_json_success(['settings' => $settings]);
    }

public function handle_save_booking_form_settings_ajax() {
    // Verify nonce first
    if (!check_ajax_referer('nordbooking_dashboard_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Security check failed.', 'NORDBOOKING')], 403);
        return;
    }

    // Check user authentication
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not authenticated.', 'NORDBOOKING')], 403);
        return;
    }

    // Get and validate settings data
    $settings_data = isset($_POST['settings']) ? (array) $_POST['settings'] : [];
    
    if (empty($settings_data)) {
        wp_send_json_error(['message' => __('No settings data received.', 'NORDBOOKING')], 400);
        return;
    }

    // === Business Slug Uniqueness Validation ===
    if (isset($settings_data['bf_business_slug'])) {
        $slug_to_check = sanitize_title($settings_data['bf_business_slug']);

        if (!empty($slug_to_check)) {
            $existing_user_id = \NORDBOOKING\Classes\Routes\BookingFormRouter::get_user_id_by_slug($slug_to_check);

            if ($existing_user_id !== null && $existing_user_id != $user_id) {
                // The slug is taken by another user.
                wp_send_json_error([
                    'message' => __('This business slug is already in use. Please choose another one.', 'NORDBOOKING'),
                    'field_id' => 'bf_business_slug' // Optional: for highlighting the field in JS
                ], 400);
                return;
            }
        }
    }
    // === End Validation ===

    // Enhanced validation and sanitization
    $validated_settings = $this->validate_and_sanitize_booking_form_settings($settings_data);
    
    if (is_wp_error($validated_settings)) {
        wp_send_json_error(['message' => $validated_settings->get_error_message()], 400);
        return;
    }

    // Save settings with improved error handling
    try {
        $result = $this->save_booking_form_settings($user_id, $validated_settings);
        
        if ($result) {
            // Return success with any processed data
            $response_data = [
                'message' => __('Booking form settings saved successfully.', 'NORDBOOKING')
            ];
            
            // Include processed slug if it was sanitized
            if (isset($validated_settings['bf_business_slug'])) {
                $response_data['processed_slug'] = $validated_settings['bf_business_slug'];
            }
            
            wp_send_json_success($response_data);
        } else {
            wp_send_json_error([
                'message' => __('Failed to save settings. Please try again.', 'NORDBOOKING')
            ], 500);
        }
        
    } catch (Exception $e) {
        error_log('[NORDBOOKING Settings Save] Exception: ' . $e->getMessage());
        wp_send_json_error([
            'message' => __('An error occurred while saving settings.', 'NORDBOOKING')
        ], 500);
    }
}


/**
 * Enhanced validation and sanitization for booking form settings
 */
private function validate_and_sanitize_booking_form_settings($settings_data) {
    $sanitized = [];
    $errors = [];

    // Define field validation rules
    $validation_rules = [
        'bf_business_slug' => [
            'type' => 'slug',
            'required' => false,
            'max_length' => 50
        ],
        'bf_header_text' => [
            'type' => 'text',
            'max_length' => 200
        ],
        'bf_show_pricing' => [
            'type' => 'boolean'
        ],
        'bf_allow_discount_codes' => [
            'type' => 'boolean'
        ],
        'bf_theme_color' => [
            'type' => 'color'
        ],
        'bf_secondary_color' => [
            'type' => 'color'
        ],
        'bf_background_color' => [
            'type' => 'color'
        ],
        'bf_custom_css' => [
            'type' => 'css',
            'max_length' => 5000
        ],
        'bf_success_message' => [
            'type' => 'textarea',
            'max_length' => 1000
        ],
        'bf_allow_cancellation_hours' => [
            'type' => 'number',
            'min' => 0,
            'max' => 720
        ],
        'bf_booking_lead_time_hours' => [
            'type' => 'number',
            'min' => 0,
            'max' => 168
        ],
        'bf_max_booking_days_ahead' => [
            'type' => 'number',
            'min' => 1,
            'max' => 365
        ],
        'bf_time_slot_duration' => [
            'type' => 'number',
            'min' => 15,
            'max' => 480
        ],
        'bf_border_radius' => [
            'type' => 'number',
            'min' => 0,
            'max' => 50
        ],
        'bf_enable_location_check' => [ // Added for explicit boolean handling
            'type' => 'boolean'
        ],
        'bf_progress_display_style' => [
            'type' => 'choice',
            'choices' => ['bar', 'none']
        ],
        'bf_service_card_display' => [
            'type' => 'text'
        ]
    ];

    // Process each field
    foreach ($settings_data as $key => $value) {
        // Skip if not a valid booking form setting
        if (strpos($key, 'bf_') !== 0 && !isset($validation_rules[$key])) {
            continue;
        }

        $rules = $validation_rules[$key] ?? ['type' => 'text'];
        $sanitized_value = $this->sanitize_field_value($value, $rules);

        if (is_wp_error($sanitized_value)) {
            $errors[] = sprintf(__('Invalid value for %s: %s', 'NORDBOOKING'), $key, $sanitized_value->get_error_message());
            continue;
        }

        $sanitized[$key] = $sanitized_value;
    }

    // Return errors if any
    if (!empty($errors)) {
        return new WP_Error('validation_failed', implode(' ', $errors));
    }

    return $sanitized;
}




/**
 * Sanitize individual field values based on type
 */
private function sanitize_field_value($value, $rules) {
    $type = $rules['type'] ?? 'text';

    switch ($type) {
        case 'slug':
            $sanitized = sanitize_title($value);
            if (!empty($rules['max_length']) && strlen($sanitized) > $rules['max_length']) {
                $sanitized = substr($sanitized, 0, $rules['max_length']);
            }
            // Ensure slug is unique if required
            if (!empty($sanitized) && isset($rules['unique']) && $rules['unique']) {
                $sanitized = $this->ensure_unique_slug($sanitized);
            }
            return $sanitized;

        case 'color':
            $sanitized = sanitize_hex_color($value);
            return $sanitized ?: '';

        case 'choice':
            if (isset($rules['choices']) && in_array($value, $rules['choices'], true)) {
                return $value;
            }
            // Return the first choice as default if the provided value is invalid
            return $rules['choices'][0] ?? '';

        case 'boolean':
            return in_array($value, ['1', 'true', true, 1], true) ? '1' : '0';

        case 'number':
            $number = intval($value);
            if (isset($rules['min']) && $number < $rules['min']) {
                $number = $rules['min'];
            }
            if (isset($rules['max']) && $number > $rules['max']) {
                $number = $rules['max'];
            }
            return $number;

        case 'css':
            // Basic CSS sanitization - remove dangerous functions
            $sanitized = wp_strip_all_tags($value);
            $dangerous_functions = ['expression', 'javascript:', 'eval(', 'import'];
            foreach ($dangerous_functions as $func) {
                $sanitized = str_ireplace($func, '', $sanitized);
            }
            if (!empty($rules['max_length']) && strlen($sanitized) > $rules['max_length']) {
                $sanitized = substr($sanitized, 0, $rules['max_length']);
            }
            return $sanitized;

        case 'textarea':
            $sanitized = sanitize_textarea_field($value);
            if (!empty($rules['max_length']) && strlen($sanitized) > $rules['max_length']) {
                $sanitized = substr($sanitized, 0, $rules['max_length']);
            }
            return $sanitized;

        case 'text':
        default:
            $sanitized = sanitize_text_field($value);
            if (!empty($rules['max_length']) && strlen($sanitized) > $rules['max_length']) {
                $sanitized = substr($sanitized, 0, $rules['max_length']);
            }
            return $sanitized;
    }
}


/**
 * Ensure business slug is unique (optional enhancement)
 */
private function ensure_unique_slug($slug, $user_id = null) {
    global $wpdb;
    
    if (empty($slug)) {
        return $slug;
    }

    $table_name = \NORDBOOKING\Classes\Database::get_table_name('tenant_settings');
    $original_slug = $slug;
    $counter = 1;

    while (true) {
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE setting_name = 'bf_business_slug' AND setting_value = %s",
            $slug
        );

        // Exclude current user if provided
        if ($user_id) {
            $query .= $wpdb->prepare(" AND user_id != %d", $user_id);
        }

        $exists = $wpdb->get_var($query);

        if (!$exists) {
            break;
        }

        $slug = $original_slug . '-' . $counter;
        $counter++;

        // Prevent infinite loop
        if ($counter > 100) {
            $slug = $original_slug . '-' . time();
            break;
        }
    }

    return $slug;
}
    /**
     * Validate and sanitize booking form settings
     */
    private function validate_booking_form_settings($settings_data) {
        error_log('[NORDBOOKING Settings Validate - Input] ' . print_r($settings_data, true));
        // Sanitize business slug
        if (isset($settings_data['bf_business_slug'])) {
            $original_slug = $settings_data['bf_business_slug'];
            $settings_data['bf_business_slug'] = sanitize_title($settings_data['bf_business_slug']);
            if ($original_slug !== $settings_data['bf_business_slug']) {
                error_log("[NORDBOOKING Settings Validate] bf_business_slug changed from '$original_slug' to '{$settings_data['bf_business_slug']}'");
            }
        }

        // Validate numeric fields
        $numeric_fields = [
            'bf_allow_cancellation_hours' => ['min' => 0, 'max' => 720], // 30 days max
            'bf_booking_lead_time_hours' => ['min' => 0, 'max' => 168], // 1 week max
            'bf_max_booking_days_ahead' => ['min' => 1, 'max' => 365], // 1 year max
            'bf_time_slot_duration' => ['min' => 15, 'max' => 480], // 8 hours max
            'bf_border_radius' => ['min' => 0, 'max' => 50]
        ];

        foreach ($numeric_fields as $field => $limits) {
            if (isset($settings_data[$field])) {
                $original_val = $settings_data[$field];
                $value = intval($settings_data[$field]);
                $settings_data[$field] = max($limits['min'], min($limits['max'], $value));
                if ($original_val != $settings_data[$field]) { // Use != because intval can change type
                     error_log("[NORDBOOKING Settings Validate] Numeric field $field changed from '$original_val' to '{$settings_data[$field]}'");
                }
            }
        }

        // Validate color fields
        $color_fields = ['bf_theme_color', 'bf_secondary_color', 'bf_background_color'];
        foreach ($color_fields as $field) {
            if (isset($settings_data[$field])) {
                $original_color = $settings_data[$field];
                $color = sanitize_hex_color($settings_data[$field]);
                if ($color) {
                    $settings_data[$field] = $color;
                    if ($original_color !== $settings_data[$field]) {
                         error_log("[NORDBOOKING Settings Validate] Color field $field changed from '$original_color' to '{$settings_data[$field]}'");
                    }
                } else {
                    // Log if a color was invalid and is being removed/defaulted
                    error_log("[NORDBOOKING Settings Validate] Invalid color for $field: '$original_color'. Field will revert to default or be unset.");
                    unset($settings_data[$field]);
                }
            }
        }

        // Validate URLs
        $url_fields = ['bf_terms_conditions_url', 'bf_webhook_url'];
        foreach ($url_fields as $field) {
            if (isset($settings_data[$field])) { // Process even if empty to ensure it's cleared or validated
                $original_url = $settings_data[$field];
                if (!empty($settings_data[$field])) {
                    $url = esc_url_raw($settings_data[$field]);
                    if (!$url) {
                        $settings_data[$field] = ''; // Clear invalid URL
                        error_log("[NORDBOOKING Settings Validate] URL field $field cleared due to invalid value: '$original_url'");
                    } else {
                        $settings_data[$field] = $url;
                        if ($original_url !== $settings_data[$field]) {
                            error_log("[NORDBOOKING Settings Validate] URL field $field sanitized from '$original_url' to '$url'");
                        }
                    }
                } else {
                    $settings_data[$field] = ''; // Ensure empty if submitted empty
                }
            }
        }

        // Sanitize text fields
        $text_fields = [
            'bf_header_text', 'bf_maintenance_message', 'bf_success_message',
            'bf_google_analytics_id', 'bf_font_family', 'biz_name', 'biz_phone', 'biz_address'
            // Note: bf_custom_css is handled separately to allow certain CSS content.
        ];
        foreach ($text_fields as $field) {
            if (isset($settings_data[$field])) {
                $original_text = $settings_data[$field];
                $settings_data[$field] = sanitize_text_field($settings_data[$field]);
                if ($original_text !== $settings_data[$field]) {
                    error_log("[NORDBOOKING Settings Validate] Text field $field sanitized. Original: '$original_text' New: '{$settings_data[$field]}'");
                }
            }
        }

        // Sanitize textarea fields (like bf_custom_css)
        // For bf_custom_css, wp_strip_all_tags might be too aggressive if user needs to input e.g. specific CSS functions or selectors.
        // A more nuanced sanitization might be needed if issues arise, or rely on user inputting safe CSS.
        // For now, wp_strip_all_tags is a strong security measure.
        if (isset($settings_data['bf_custom_css'])) {
            $original_css = $settings_data['bf_custom_css'];
            // Using wp_kses_post might be an alternative if some HTML/CSS like structures are needed, but for raw CSS, this is tricky.
            // wp_strip_all_tags is very restrictive.
            // Let's use a more balanced approach: remove script tags and dangerous attributes.
            // However, a simpler approach for now is just to log if it changes.
            // For security, keeping wp_strip_all_tags is safer if we're unsure about all possible malicious inputs.
            $settings_data['bf_custom_css'] = wp_strip_all_tags($settings_data['bf_custom_css']); // Keeping it strict for now
            if ($original_css !== $settings_data['bf_custom_css']) {
                 error_log("[NORDBOOKING Settings Validate] bf_custom_css was modified by wp_strip_all_tags.");
                 // Consider logging the before and after if it's short enough or a hash of it.
            }
        }

        // Validate email fields
        $email_fields = ['biz_email'];
        // Add email notification recipient fields
        $email_notification_types = ['booking_confirmation_customer', 'booking_confirmation_admin', 'staff_assignment', 'welcome', 'invitation'];
        foreach ($email_notification_types as $type) {
            $email_fields[] = 'email_' . $type . '_recipient';
        }
        
        foreach ($email_fields as $field) {
            if (isset($settings_data[$field])) {
                $original_email = $settings_data[$field];
                if (!empty($settings_data[$field])) {
                    $email = sanitize_email($settings_data[$field]);
                    if (!$email) {
                        $settings_data[$field] = ''; // Clear invalid email
                        error_log("[NORDBOOKING Settings Validate] Email field $field cleared due to invalid value: '$original_email'");
                    } else {
                        $settings_data[$field] = $email;
                        if ($original_email !== $settings_data[$field]) {
                            error_log("[NORDBOOKING Settings Validate] Email field $field sanitized from '$original_email' to '$email'");
                        }
                    }
                } else {
                    $settings_data[$field] = ''; // Ensure empty if submitted empty
                }
            }
        }

        // Log for boolean/checkbox like values (0 or 1)
        $boolean_like_fields = [
            'bf_show_progress_bar', 'bf_form_enabled', 'bf_allow_service_selection',
            'bf_allow_date_time_selection', 'bf_require_phone', 'bf_allow_special_instructions',
            'bf_show_pricing', 'bf_allow_discount_codes', 'bf_enable_recaptcha',
            'bf_enable_ssl_required', 'bf_debug_mode'
        ];
        
        // Add email notification enabled fields
        foreach ($email_notification_types as $type) {
            $boolean_like_fields[] = 'email_' . $type . '_enabled';
            $boolean_like_fields[] = 'email_' . $type . '_use_primary';
        }
        foreach ($boolean_like_fields as $field) {
            if (isset($settings_data[$field])) {
                $original_bool_val = $settings_data[$field];
                $settings_data[$field] = ($settings_data[$field] === '1' || $settings_data[$field] === true) ? '1' : '0';
                if ($original_bool_val !== $settings_data[$field]) {
                    error_log("[NORDBOOKING Settings Validate] Boolean field $field normalized from '$original_bool_val' to '{$settings_data[$field]}'");
                }
            }
        }


        error_log('[NORDBOOKING Settings Validate - Output] ' . print_r($settings_data, true));
        return $settings_data;
    }

    /**
     * Get user billing information combining personal and business details
     */
    public function get_user_billing_info(int $user_id): array {
        $user = get_userdata($user_id);
        $user_meta = get_user_meta($user_id);
        $business_settings = $this->get_business_settings($user_id);
        
        return [
            // Personal Details
            'first_name' => isset($user_meta['first_name'][0]) ? $user_meta['first_name'][0] : '',
            'last_name' => isset($user_meta['last_name'][0]) ? $user_meta['last_name'][0] : '',
            'primary_email' => $user ? $user->user_email : '',
            'full_name' => trim((isset($user_meta['first_name'][0]) ? $user_meta['first_name'][0] : '') . ' ' . (isset($user_meta['last_name'][0]) ? $user_meta['last_name'][0] : '')),
            
            // Business Details
            'business_name' => $business_settings['biz_name'] ?? '',
            'business_email' => $business_settings['biz_email'] ?? '',
            'business_phone' => $business_settings['biz_phone'] ?? '',
            'business_address' => $business_settings['biz_address'] ?? '',
            
            // Billing Display Info
            'billing_name' => !empty($business_settings['biz_name']) ? $business_settings['biz_name'] : trim((isset($user_meta['first_name'][0]) ? $user_meta['first_name'][0] : '') . ' ' . (isset($user_meta['last_name'][0]) ? $user_meta['last_name'][0] : '')),
            'billing_email' => !empty($business_settings['biz_email']) ? $business_settings['biz_email'] : ($user ? $user->user_email : ''),
        ];
    }

    public function get_setting(int $user_id, string $setting_name, $default_value = null) {
        $defaults = self::get_default_settings();
        if (empty($user_id) && $user_id !== 0) {
             return array_key_exists($setting_name, $defaults) ? $defaults[$setting_name] : $default_value;
        }

        $table_name = \NORDBOOKING\Classes\Database::get_table_name('tenant_settings');
        $value = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT setting_value FROM $table_name WHERE user_id = %d AND setting_name = %s",
            $user_id, $setting_name
        ));

        if (is_null($value)) {
            return array_key_exists($setting_name, $defaults) ?
                $defaults[$setting_name] : $default_value;
        }
        return maybe_unserialize($value);
    }

    public function update_setting(int $user_id, string $setting_name, $setting_value): bool {
        if (empty($user_id) && $user_id !== 0) {
            error_log('[NORDBOOKING Settings] Invalid user_id: ' . $user_id);
            return false;
        }
        if (empty($setting_name)) {
            error_log('[NORDBOOKING Settings] Empty setting_name provided');
            return false;
        }

        $table_name = \NORDBOOKING\Classes\Database::get_table_name('tenant_settings');
        
        // Check if table exists
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare("SHOW TABLES LIKE %s", $table_name));
        if (!$table_exists) {
            error_log('[NORDBOOKING Settings] Table does not exist: ' . $table_name);
            return false;
        }

        $value_to_store = is_array($setting_value) || is_object($setting_value)
                        ? maybe_serialize($setting_value)
                        : (string) $setting_value;

        // Log the operation
        error_log('[NORDBOOKING Settings] Updating setting - User: ' . $user_id . ', Name: ' . $setting_name . ', Value length: ' . strlen($value_to_store));

        $result = $this->wpdb->replace(
            $table_name,
            [ 
                'user_id' => $user_id, 
                'setting_name' => $setting_name, 
                'setting_value' => $value_to_store 
            ],
            ['%d', '%s', '%s']
        );

        if ($result === false) {
            error_log('[NORDBOOKING Settings] Database error: ' . $this->wpdb->last_error);
            error_log('[NORDBOOKING Settings] Failed query: ' . $this->wpdb->last_query);
        } else {
            error_log('[NORDBOOKING Settings] Successfully updated setting: ' . $setting_name . ' for user: ' . $user_id);
        }

        return $result !== false;
    }

    private function get_settings_by_prefix_or_keys(int $user_id, array $relevant_defaults): array {
        $settings_from_db = [];
        if (!empty($user_id) && !empty($relevant_defaults)) {
            $table_name = \NORDBOOKING\Classes\Database::get_table_name('tenant_settings');
            $setting_name_placeholders = implode(', ', array_fill(0, count(array_keys($relevant_defaults)), '%s'));
            $query_params = array_merge([$user_id], array_keys($relevant_defaults));

            $results = $this->wpdb->get_results($this->wpdb->prepare(
                "SELECT setting_name, setting_value FROM $table_name WHERE user_id = %d AND setting_name IN ($setting_name_placeholders)",
                ...$query_params
            ), ARRAY_A);

            if ($results) {
                foreach ($results as $row) {
                    if (array_key_exists($row['setting_name'], $relevant_defaults)) {
                         $settings_from_db[$row['setting_name']] = maybe_unserialize($row['setting_value']);
                    }
                }
            }
        }
        return wp_parse_args($settings_from_db, $relevant_defaults);
    }

    public function get_booking_form_settings(int $user_id): array {
        $booking_form_defaults = array_filter(self::get_default_settings(), function($key) {
            return strpos($key, 'bf_') === 0;
        }, ARRAY_FILTER_USE_KEY);
        return $this->get_settings_by_prefix_or_keys($user_id, $booking_form_defaults);
    }

    public function get_business_settings(int $user_id): array {
        $business_setting_keys = array_filter(array_keys(self::get_default_settings()), function($key) {
            return strpos($key, 'biz_') === 0 || strpos($key, 'email_') === 0;
        });
        $business_defaults = array_intersect_key(self::get_default_settings(), array_flip($business_setting_keys));

        $parsed_settings = $this->get_settings_by_prefix_or_keys($user_id, $business_defaults);

        // Ensure biz_currency_code has a default value
        if (empty($parsed_settings['biz_currency_code'])) {
            $parsed_settings['biz_currency_code'] = 'USD'; // Default to USD
        }

        // Add currency symbol and position
        $parsed_settings['biz_currency_symbol'] = \NORDBOOKING\Classes\Utils::get_currency_symbol($parsed_settings['biz_currency_code']);
        $parsed_settings['biz_currency_position'] = \NORDBOOKING\Classes\Utils::get_currency_position($parsed_settings['biz_currency_code']);

        if ($user_id > 0) {
            $user_info = null;
            if (empty($parsed_settings['biz_email'])) {
                $user_info = $user_info ?: get_userdata($user_id);
                if ($user_info) {
                    $parsed_settings['biz_email'] = $user_info->user_email;
                }
            }
            if (empty($parsed_settings['email_from_name'])) {
                $user_info = $user_info ?: get_userdata($user_id);
                if ($user_info) {
                    $parsed_settings['email_from_name'] = !empty($parsed_settings['biz_name']) 
                        ? $parsed_settings['biz_name'] 
                        : $user_info->display_name;
                }
            }
            if (empty($parsed_settings['email_from_address'])) {
                $parsed_settings['email_from_address'] = !empty($parsed_settings['biz_email']) 
                    ? $parsed_settings['biz_email'] 
                    : get_option('admin_email');
            }
        }

        return $parsed_settings;
    }

    private function save_settings_group(int $user_id, array $settings_data, array $default_keys_for_group): bool {
        if (empty($user_id) && $user_id !== 0) return false;
        if (empty($settings_data)) return true; // Nothing to save is technically successful

        $all_successful = true;
        foreach ($settings_data as $key => $value) {
            if (array_key_exists($key, $default_keys_for_group)) {
                $update_result = $this->update_setting($user_id, $key, $value);
                error_log("[NORDBOOKING Settings] save_settings_group - Key: $key, Value: " . 
                    (is_array($value) ? json_encode($value) : $value) . ', Result: ' . 
                    ($update_result ? 'Success' : 'Failure'));

                if (!$update_result) {
                    $all_successful = false;
                    $db_error = $this->wpdb->last_error;
                    error_log("[NORDBOOKING Settings] DB Error for $key (User: $user_id): " . $db_error);
                }
            } else {
                error_log("[NORDBOOKING Settings] Skipped key (not in default_keys_for_group): $key");
            }
        }
        error_log('[NORDBOOKING Settings] save_settings_group final result for user_id ' . $user_id . ': ' . ($all_successful ? 'All Successful' : 'Some Failed'));
        return $all_successful;
    }

/**
 * Enhanced save method with better error handling
 */
public function save_booking_form_settings(int $user_id, array $settings_data): bool {
    if (empty($user_id) || empty($settings_data)) {
        return false;
    }

    $success_count = 0;
    $total_count = 0;

    foreach ($settings_data as $key => $value) {
        $total_count++;
        
        try {
            $result = $this->update_setting($user_id, $key, $value);
            if ($result) {
                $success_count++;
            } else {
                error_log("[NORDBOOKING Settings] Failed to save setting: {$key} for user: {$user_id}");
            }
        } catch (Exception $e) {
            error_log("[NORDBOOKING Settings] Exception saving {$key}: " . $e->getMessage());
        }
    }

    // Consider it successful if at least 80% of settings were saved
    $success_rate = $total_count > 0 ? ($success_count / $total_count) : 0;
    return $success_rate >= 0.8;
}

    /**
     * Validate and sanitize business settings data
     */
    private function validate_and_sanitize_business_settings($settings_data) {
        $sanitized = [];
        $errors = [];

        // Define validation rules for business settings
        $validation_rules = [
            'first_name' => ['type' => 'text', 'max_length' => 50, 'required' => false],
            'last_name' => ['type' => 'text', 'max_length' => 50, 'required' => false],
            'biz_name' => ['type' => 'text', 'max_length' => 200, 'required' => false],
            'biz_email' => ['type' => 'email', 'required' => false],
            'biz_phone' => ['type' => 'text', 'max_length' => 20, 'required' => false],
            'biz_address' => ['type' => 'textarea', 'max_length' => 500, 'required' => false],
            'biz_logo_url' => ['type' => 'url', 'required' => false],
            'biz_hours_json' => ['type' => 'json', 'required' => false],
            'biz_currency_code' => ['type' => 'text', 'max_length' => 3, 'required' => false],
            'biz_user_language' => ['type' => 'text', 'max_length' => 10, 'required' => false],
            'email_from_name' => ['type' => 'text', 'max_length' => 100, 'required' => false],
            'email_from_address' => ['type' => 'email', 'required' => false],
            
            // Branding Settings
            'bf_theme_color' => ['type' => 'color', 'required' => false],
            'bf_secondary_color' => ['type' => 'color', 'required' => false],
            'bf_background_color' => ['type' => 'color', 'required' => false],
            'bf_border_radius' => ['type' => 'number', 'min' => 0, 'max' => 50, 'required' => false],
            'bf_font_family' => ['type' => 'text', 'max_length' => 50, 'required' => false],
            'bf_custom_css' => ['type' => 'css', 'max_length' => 5000, 'required' => false],
        ];

        // Add email notification settings
        $email_notification_types = ['booking_confirmation_customer', 'booking_confirmation_admin', 'staff_assignment', 'welcome', 'invitation'];
        foreach ($email_notification_types as $type) {
            $validation_rules['email_' . $type . '_enabled'] = ['type' => 'boolean'];
            $validation_rules['email_' . $type . '_recipient'] = ['type' => 'email', 'required' => false];
            $validation_rules['email_' . $type . '_use_primary'] = ['type' => 'boolean'];
        }

        // Process each field
        error_log('[NORDBOOKING Settings Validation] Processing ' . count($settings_data) . ' fields');
        error_log('[NORDBOOKING Settings Validation] Input fields: ' . implode(', ', array_keys($settings_data)));
        
        foreach ($settings_data as $key => $value) {
            error_log('[NORDBOOKING Settings Validation] Processing field: ' . $key . ' = ' . (is_array($value) ? json_encode($value) : $value));
            
            // Skip if not a valid business setting
            if (!isset($validation_rules[$key])) {
                error_log('[NORDBOOKING Settings Validation] Skipping field (no validation rule): ' . $key);
                continue;
            }

            $rules = $validation_rules[$key];
            $sanitized_value = $this->sanitize_business_field_value($value, $rules);

            if (is_wp_error($sanitized_value)) {
                $error_msg = sprintf(__('Invalid value for %s: %s', 'NORDBOOKING'), $key, $sanitized_value->get_error_message());
                $errors[] = $error_msg;
                error_log('[NORDBOOKING Settings Validation] Validation error for ' . $key . ': ' . $error_msg);
                continue;
            }

            $sanitized[$key] = $sanitized_value;
            error_log('[NORDBOOKING Settings Validation] Successfully validated ' . $key . ' = ' . (is_array($sanitized_value) ? json_encode($sanitized_value) : $sanitized_value));
        }
        
        error_log('[NORDBOOKING Settings Validation] Final sanitized fields: ' . implode(', ', array_keys($sanitized)));

        // Return errors if any
        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(' ', $errors));
        }

        return $sanitized;
    }

    /**
     * Sanitize individual business field values
     */
    private function sanitize_business_field_value($value, $rules) {
        $type = $rules['type'] ?? 'text';

        switch ($type) {
            case 'email':
                if (empty($value)) {
                    return '';
                }
                $sanitized = sanitize_email($value);
                if (!$sanitized && !empty($value)) {
                    return new WP_Error('invalid_email', 'Invalid email format');
                }
                return $sanitized;

            case 'url':
                if (empty($value)) {
                    return '';
                }
                $sanitized = esc_url_raw($value);
                if (!$sanitized && !empty($value)) {
                    return new WP_Error('invalid_url', 'Invalid URL format');
                }
                return $sanitized;

            case 'boolean':
                return in_array($value, ['1', 'true', true, 1], true) ? '1' : '0';

            case 'json':
                if (empty($value)) {
                    return '{}';
                }
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        return new WP_Error('invalid_json', 'Invalid JSON format');
                    }
                    return $value;
                }
                return json_encode($value);

            case 'color':
                if (empty($value)) {
                    return '';
                }
                $sanitized = sanitize_hex_color($value);
                if (!$sanitized && !empty($value)) {
                    return new WP_Error('invalid_color', 'Invalid color format');
                }
                return $sanitized;

            case 'number':
                $number = intval($value);
                if (isset($rules['min']) && $number < $rules['min']) {
                    $number = $rules['min'];
                }
                if (isset($rules['max']) && $number > $rules['max']) {
                    $number = $rules['max'];
                }
                return $number;

            case 'css':
                // Basic CSS sanitization - remove dangerous functions
                $sanitized = wp_strip_all_tags($value);
                $dangerous_functions = ['expression', 'javascript:', 'eval(', 'import'];
                foreach ($dangerous_functions as $func) {
                    $sanitized = str_ireplace($func, '', $sanitized);
                }
                if (!empty($rules['max_length']) && strlen($sanitized) > $rules['max_length']) {
                    $sanitized = substr($sanitized, 0, $rules['max_length']);
                }
                return $sanitized;

            case 'textarea':
                $sanitized = sanitize_textarea_field($value);
                if (!empty($rules['max_length']) && strlen($sanitized) > $rules['max_length']) {
                    $sanitized = substr($sanitized, 0, $rules['max_length']);
                }
                return $sanitized;

            case 'text':
            default:
                $sanitized = sanitize_text_field($value);
                if (!empty($rules['max_length']) && strlen($sanitized) > $rules['max_length']) {
                    $sanitized = substr($sanitized, 0, $rules['max_length']);
                }
                return $sanitized;
        }
    }

    public function save_business_settings(int $user_id, array $settings_data): bool {
        $business_setting_keys = array_filter(array_keys(self::get_default_settings()), function($key) {
            return strpos($key, 'biz_') === 0 || strpos($key, 'email_') === 0 || strpos($key, 'bf_') === 0;
        });
        $business_defaults = array_intersect_key(self::get_default_settings(), array_flip($business_setting_keys));
        return $this->save_settings_group($user_id, $settings_data, $business_defaults);
    }

    public static function initialize_default_settings(int $user_id) {
        if (empty($user_id) && $user_id !== 0) return;

        $settings_instance = new self();
        $user_info = get_userdata($user_id);

        foreach (self::get_default_settings() as $key => $default_value) {
            $value_to_set = $default_value;

            if ($user_info) {
                // Set business name from user meta if available
                if ($key === 'biz_name' && empty($default_value)) {
                    $company_name_meta = get_user_meta($user_id, 'nordbooking_company_name', true);
                    if (!empty($company_name_meta)) {
                        $value_to_set = $company_name_meta;
                    }
                }
                
                if ($key === 'biz_email' && empty($default_value)) {
                    $value_to_set = $user_info->user_email;
                }
                
                if ($key === 'email_from_name' && empty($default_value)) {
                    $biz_name_val = $settings_instance->get_setting($user_id, 'biz_name');
                    if (empty($biz_name_val)) {
                        // Try to get from user meta first
                        $company_name_meta = get_user_meta($user_id, 'nordbooking_company_name', true);
                        $biz_name_val = !empty($company_name_meta) ? $company_name_meta : '';
                    }
                    if (empty($biz_name_val)) $biz_name_val = isset(self::get_default_settings()['biz_name']) ? self::get_default_settings()['biz_name'] : '';
                    if (empty($biz_name_val)) $biz_name_val = $user_info->display_name;

                    $value_to_set = !empty($biz_name_val) ? $biz_name_val : get_bloginfo('name');
                }
                
                if ($key === 'email_from_address' && empty($default_value)) {
                    $biz_email_val = $settings_instance->get_setting($user_id, 'biz_email');
                    if (empty($biz_email_val)) $biz_email_val = $user_info->user_email;
                    if (empty($biz_email_val)) $biz_email_val = get_option('admin_email');
                    $value_to_set = $biz_email_val;
                }
            }

            if (!is_null($value_to_set)) {
                $settings_instance->update_setting($user_id, $key, $value_to_set);
            }
        }

        // Demo services creation is now handled by Auth class during registration
        // to ensure it matches the specific requirements for account setup automation
    }

    private static function create_demo_services(int $user_id)
    {
        $demo_services_created = get_user_meta($user_id, 'demo_services_created', true);
        if (!empty($demo_services_created)) {
            error_log('[NORDBOOKING Demo Services] Demo services already created for user ' . $user_id);
            return;
        }

        error_log('[NORDBOOKING Demo Services] Creating demo services for user ' . $user_id);

        try {
            $services_manager = new \NORDBOOKING\Classes\Services();

            // 1. Home Cleaning
            $home_cleaning_id = $services_manager->add_service($user_id, [
                'name' => 'Demo - Home Cleaning',
                'description' => 'A comprehensive cleaning service for your home.',
                'price' => 50,
                'duration' => 120,
                'icon' => 'preset:home-cleaning.svg',
            ]);

            if (is_wp_error($home_cleaning_id)) {
                error_log('[NORDBOOKING Demo Services] Error creating Home Cleaning service: ' . $home_cleaning_id->get_error_message());
            } else {
                error_log('[NORDBOOKING Demo Services] Created Home Cleaning service with ID: ' . $home_cleaning_id);
            }

        if (!is_wp_error($home_cleaning_id)) {
            $services_manager->service_options_manager->add_service_option($user_id, $home_cleaning_id, [
                'name' => 'Number of Bedrooms',
                'type' => 'select',
                'is_required' => true,
                'option_values' => json_encode([
                    ['label' => '1 Bedroom', 'price' => 0],
                    ['label' => '2 Bedrooms', 'price' => 20],
                    ['label' => '3 Bedrooms', 'price' => 40],
                ]),
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $home_cleaning_id, [
                'name' => 'Deep Cleaning',
                'type' => 'toggle',
                'is_required' => false,
                'price_impact_type' => 'fixed',
                'price_impact_value' => 50,
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $home_cleaning_id, [
                'name' => 'Inside Fridge',
                'type' => 'toggle',
                'is_required' => false,
                'price_impact_type' => 'fixed',
                'price_impact_value' => 25,
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $home_cleaning_id, [
                'name' => 'Inside Oven',
                'type' => 'toggle',
                'is_required' => false,
                'price_impact_type' => 'fixed',
                'price_impact_value' => 25,
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $home_cleaning_id, [
                'name' => 'Cleaning Frequency',
                'type' => 'radio',
                'is_required' => true,
                'option_values' => json_encode([
                    ['label' => 'One-time Cleaning', 'price' => 0],
                    ['label' => 'Weekly (10% discount)', 'price' => -10],
                    ['label' => 'Bi-weekly (5% discount)', 'price' => -5],
                    ['label' => 'Monthly', 'price' => 0],
                ]),
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $home_cleaning_id, [
                'name' => 'Additional Services',
                'type' => 'checkbox',
                'is_required' => false,
                'option_values' => json_encode([
                    ['label' => 'Garage Cleaning', 'price' => 30],
                    ['label' => 'Basement Cleaning', 'price' => 25],
                    ['label' => 'Attic Cleaning', 'price' => 35],
                    ['label' => 'Laundry Room Deep Clean', 'price' => 20],
                ]),
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $home_cleaning_id, [
                'name' => 'Special Instructions',
                'type' => 'textarea',
                'is_required' => false,
                'description' => 'Any special requests or areas that need extra attention?',
            ]);
        }

        // 2. Window Cleaning
        $window_cleaning_id = $services_manager->add_service($user_id, [
            'name' => 'Demo - Window Cleaning',
            'description' => 'Crystal clear windows for your home or office.',
            'price' => 30,
            'duration' => 60,
            'icon' => 'preset:window-cleaning.svg',
        ]);

        if (!is_wp_error($window_cleaning_id)) {
            $services_manager->service_options_manager->add_service_option($user_id, $window_cleaning_id, [
                'name' => 'Number of Windows',
                'type' => 'number',
                'is_required' => true,
                'price_impact_type' => 'fixed',
                'price_impact_value' => 5,
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $window_cleaning_id, [
                'name' => 'Window Type',
                'type' => 'radio',
                'is_required' => true,
                'option_values' => json_encode([
                    ['label' => 'Standard Windows', 'price' => 0],
                    ['label' => 'French Windows', 'price' => 10],
                    ['label' => 'Bay Windows', 'price' => 15],
                ]),
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $window_cleaning_id, [
                'name' => 'Skylights',
                'type' => 'toggle',
                'is_required' => false,
                'price_impact_type' => 'fixed',
                'price_impact_value' => 30,
            ]);
        }

        // 3. Moving Cleaning
        $moving_cleaning_id = $services_manager->add_service($user_id, [
            'name' => 'Demo - Moving Cleaning',
            'description' => 'A thorough cleaning for when you are moving in or out.',
            'price' => 150,
            'duration' => 240,
            'icon' => 'preset:apartment-cleaning.svg',
        ]);

        if (!is_wp_error($moving_cleaning_id)) {
            $services_manager->service_options_manager->add_service_option($user_id, $moving_cleaning_id, [
                'name' => 'Square Footage',
                'type' => 'sqm',
                'is_required' => true,
                'price_impact_type' => 'fixed',
                'price_impact_value' => 0.1,
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $moving_cleaning_id, [
                'name' => 'Carpet Shampooing',
                'type' => 'toggle',
                'is_required' => false,
                'price_impact_type' => 'fixed',
                'price_impact_value' => 80,
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $moving_cleaning_id, [
                'name' => 'Wall Washing',
                'type' => 'toggle',
                'is_required' => false,
                'price_impact_type' => 'fixed',
                'price_impact_value' => 60,
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $moving_cleaning_id, [
                'name' => 'Property Condition',
                'type' => 'select',
                'is_required' => true,
                'option_values' => json_encode([
                    ['label' => 'Light Cleaning Needed', 'price' => 0],
                    ['label' => 'Moderate Cleaning Required', 'price' => 50],
                    ['label' => 'Heavy Cleaning Required', 'price' => 100],
                ]),
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $moving_cleaning_id, [
                'name' => 'Extra Services',
                'type' => 'checkbox',
                'is_required' => false,
                'option_values' => json_encode([
                    ['label' => 'Paint Touch-ups', 'price' => 75],
                    ['label' => 'Light Fixture Cleaning', 'price' => 40],
                    ['label' => 'Cabinet Interior Cleaning', 'price' => 60],
                    ['label' => 'Appliance Deep Clean', 'price' => 85],
                ]),
            ]);
        }

            update_user_meta($user_id, 'demo_services_created', true);
            error_log('[NORDBOOKING Demo Services] Demo services creation completed for user ' . $user_id);
        } catch (Exception $e) {
            error_log('[NORDBOOKING Demo Services] Error creating demo services for user ' . $user_id . ': ' . $e->getMessage());
        }
    }

    /**
     * Check if booking form is enabled for a specific user
     */
    public function is_booking_form_enabled(int $user_id): bool {
        $form_enabled = $this->get_setting($user_id, 'bf_form_enabled', '1');
        return $form_enabled === '1';
    }

    /**
     * Get the public booking form URL for a user
     */
    public function get_public_booking_url(int $user_id): string {
        $business_slug = $this->get_setting($user_id, 'bf_business_slug', '');
        if (!empty($business_slug)) {
            // Use the new standardized URL structure
            return trailingslashit(site_url()) . 'bookings/' . $business_slug . '/';
        }
        return '';
    }

    /**
     * Check if a business slug is unique
     */
    public function is_business_slug_unique(string $slug, int $exclude_user_id = 0): bool {
        if (empty($slug)) return false;

        $table_name = \NORDBOOKING\Classes\Database::get_table_name('tenant_settings');
        $query = "SELECT user_id FROM $table_name WHERE setting_name = 'bf_business_slug' AND setting_value = %s";
        $params = [$slug];

        if ($exclude_user_id > 0) {
            $query .= " AND user_id != %d";
            $params[] = $exclude_user_id;
        }

        $existing_user = $this->wpdb->get_var($this->wpdb->prepare($query, ...$params));
        return is_null($existing_user);
    }

    /**
     * Get all default settings (useful for initialization)
     */
    public static function get_all_default_settings(): array {
        return self::get_default_settings();
    }

    /**
     * Get static email templates (simplified version)
     */
    public function get_static_email_templates(): array {
        return [
            'booking_confirmation_customer' => [
                'name' => __('Customer Booking Confirmation', 'NORDBOOKING'),
                'subject' => 'Booking Confirmation - Ref: {{booking_reference}}',
                'body' => "Dear {{customer_name}},\n\nThank you for your booking with {{business_name}}. Your booking has been confirmed.\n\nBooking Details:\nReference: {{booking_reference}}\nServices: {{service_names}}\nDate & Time: {{booking_date_time}}\nTotal Price: {{total_price}}\nLocation: {{service_address}}\n\nWe look forward to serving you!\n\nBest regards,\n{{business_name}}"
            ],
            'booking_confirmation_admin' => [
                'name' => __('Admin New Booking Notification', 'NORDBOOKING'),
                'subject' => 'New Booking Received - Ref: {{booking_reference}}',
                'body' => "A new booking has been received.\n\nCustomer Details:\nName: {{customer_name}}\nEmail: {{customer_email}}\nPhone: {{customer_phone}}\n\nBooking Details:\nReference: {{booking_reference}}\nServices: {{service_names}}\nDate & Time: {{booking_date_time}}\nTotal Price: {{total_price}}\nLocation: {{service_address}}\n\nSpecial Instructions: {{special_instructions}}"
            ],
            'staff_assignment' => [
                'name' => __('Staff Assignment Notification', 'NORDBOOKING'),
                'subject' => 'New Booking Assignment - Ref: {{booking_reference}}',
                'body' => "Hi {{staff_name}},\n\nYou have been assigned to a new booking.\n\nBooking Details:\nCustomer: {{customer_name}}\nReference: {{booking_reference}}\nDate & Time: {{booking_date_time}}\n\nPlease check your dashboard for more details."
            ],
            'welcome' => [
                'name' => __('Welcome Email', 'NORDBOOKING'),
                'subject' => 'Welcome to {{business_name}}!',
                'body' => "Hi {{customer_name}},\n\nWelcome to {{business_name}}! We're excited to have you as a customer.\n\nYou can manage your bookings and account settings through your dashboard.\n\nIf you have any questions, please don't hesitate to contact us.\n\nBest regards,\n{{business_name}}"
            ],
            'invitation' => [
                'name' => __('Staff Invitation Email', 'NORDBOOKING'),
                'subject' => 'You have been invited to join {{business_name}}',
                'body' => "Hi there,\n\n{{inviter_name}} has invited you to join {{business_name}} as a {{worker_role}}.\n\nTo accept this invitation and set up your account, please click the link below:\n{{registration_link}}\n\nWe look forward to working with you!\n\nBest regards,\n{{business_name}}"
            ]
        ];
    }

    /**
     * Get email notification settings for a user
     */
    public function get_email_notification_settings(int $user_id): array {
        $notification_types = ['booking_confirmation_customer', 'booking_confirmation_admin', 'staff_assignment', 'welcome', 'invitation'];
        $settings = [];
        
        foreach ($notification_types as $type) {
            $settings[$type] = [
                'enabled' => $this->get_setting($user_id, 'email_' . $type . '_enabled', '1'),
                'recipient' => $this->get_setting($user_id, 'email_' . $type . '_recipient', ''),
                'use_primary' => $this->get_setting($user_id, 'email_' . $type . '_use_primary', '1')
            ];
        }
        
        return $settings;
    }

    /**
     * Get the recipient email for a specific notification type
     */
    public function get_notification_recipient(int $user_id, string $notification_type): string {
        $use_primary = $this->get_setting($user_id, 'email_' . $notification_type . '_use_primary', '1');
        
        if ($use_primary === '1') {
            // Use primary business email
            $primary_email = $this->get_setting($user_id, 'biz_email', '');
            if (empty($primary_email)) {
                // Fallback to user email
                $user_info = get_userdata($user_id);
                $primary_email = $user_info ? $user_info->user_email : get_option('admin_email');
            }
            return $primary_email;
        } else {
            // Use custom email
            return $this->get_setting($user_id, 'email_' . $notification_type . '_recipient', '');
        }
    }

    /**
     * Check if a notification type is enabled
     */
    public function is_notification_enabled(int $user_id, string $notification_type): bool {
        return $this->get_setting($user_id, 'email_' . $notification_type . '_enabled', '1') === '1';
    }

    public function get_setup_progress(int $user_id): array {
        $progress = [
            'steps' => [],
            'completed_count' => 0,
            'total_count' => 0,
            'is_complete' => false,
        ];

        // Step 1: Business Name
        $biz_name = $this->get_setting($user_id, 'biz_name', '');
        $step1_complete = !empty($biz_name);
        $progress['steps'][] = [
            'id' => 'business_name',
            'label' => __('Set Business Name', 'NORDBOOKING'),
            'completed' => $step1_complete,
        ];

        // Step 2: Add a Service
        $services_manager = new \NORDBOOKING\Classes\Services();
        $services_count = $services_manager->get_services_count($user_id);
        $step2_complete = $services_count > 0;
        $progress['steps'][] = [
            'id' => 'add_service',
            'label' => __('Add Your First Service', 'NORDBOOKING'),
            'completed' => $step2_complete,
        ];

        // Step 3: Define Service Area
        $areas_manager = new \NORDBOOKING\Classes\Areas();
        $areas_count = $areas_manager->get_areas_count_by_user($user_id);
        $step3_complete = $areas_count > 0;
        $progress['steps'][] = [
            'id' => 'define_area',
            'label' => __('Define Service Area', 'NORDBOOKING'),
            'completed' => $step3_complete,
        ];

        $progress['total_count'] = count($progress['steps']);
        $progress['completed_count'] = count(array_filter($progress['steps'], fn($step) => $step['completed']));
        $progress['is_complete'] = $progress['completed_count'] === $progress['total_count'];

        return $progress;
    }
}