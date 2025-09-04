<?php
namespace MoBooking\Classes;

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

                // Email Templates
                'email_booking_conf_subj_customer'    => 'Your Booking Confirmation - Ref: {{booking_reference}}',
                'email_booking_conf_body_customer'    => json_encode([
                    ['type' => 'header', 'text' => 'Booking Confirmed!'],
                    ['type' => 'text', 'text' => "Dear {{customer_name}},\n\nThank you for your booking with {{business_name}}. This email confirms that your booking (Ref: {{booking_reference}}) is confirmed."],
                    ['type' => 'text', 'text' => "Services: {{service_names}}\nDate & Time: {{booking_date_time}}\nTotal Price: {{total_price}}"],
                    ['type' => 'button', 'text' => 'View Booking Details', 'url' => '{{booking_link}}']
                ]),
                'email_booking_conf_subj_admin'       => 'New Booking Received - Ref: {{booking_reference}}',
                'email_booking_conf_body_admin'       => json_encode([
                    ['type' => 'header', 'text' => 'New Booking Received!'],
                    ['type' => 'text', 'text' => "A new booking has been made by {{customer_name}} ({{customer_email}})."],
                    ['type' => 'text', 'text' => "Services: {{service_names}}\nDate & Time: {{booking_date_time}}\nTotal Price: {{total_price}}"],
                    ['type' => 'button', 'text' => 'View in Dashboard', 'url' => '{{admin_booking_link}}']
                ]),
                'email_staff_assign_subj'             => 'New Booking Assignment - Ref: {{booking_reference}}',
                'email_staff_assign_body'             => json_encode([
                    ['type' => 'header', 'text' => 'New Assignment'],
                    ['type' => 'text', 'text' => "Hi {{staff_name}},\n\nYou have been assigned to a new booking for {{customer_name}} on {{booking_date_time}}."],
                    ['type' => 'button', 'text' => 'View Your Assignments', 'url' => '{{staff_dashboard_link}}']
                ]),
                'email_welcome_subj'                  => 'Welcome to {{company_name}}!',
                'email_welcome_body'                  => json_encode([
                    ['type' => 'header', 'text' => 'Welcome!'],
                    ['type' => 'text', 'text' => "Hi {{customer_name}},\n\nThanks for creating an account with us. We're excited to have you!"],
                    ['type' => 'button', 'text' => 'Go to Your Dashboard', 'url' => '{{dashboard_link}}']
                ]),
                'email_invitation_subj'               => 'You have been invited to join {{company_name}}',
                'email_invitation_body'               => json_encode([
                    ['type' => 'header', 'text' => 'You\'re Invited!'],
                    ['type' => 'text', 'text' => "Hi there,\n\n{{inviter_name}} has invited you to join {{company_name}} as a {{worker_role}}."],
                    ['type' => 'button', 'text' => 'Accept & Register', 'url' => '{{registration_link}}']
                ]),
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
        add_action('wp_ajax_mobooking_get_booking_form_settings', [$this, 'handle_get_booking_form_settings_ajax']);
        add_action('wp_ajax_mobooking_save_booking_form_settings', [$this, 'handle_save_booking_form_settings_ajax']);

        // Business Settings
        add_action('wp_ajax_mobooking_get_business_settings', [$this, 'handle_get_business_settings_ajax']);
        add_action('wp_ajax_mobooking_save_business_settings', [$this, 'handle_save_business_settings_ajax']);

        // Utility Actions
        add_action('wp_ajax_mobooking_flush_rewrite_rules', [$this, 'handle_flush_rewrite_rules_ajax']);
    }

    public function handle_flush_rewrite_rules_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce'); // Use existing general dashboard nonce

        if (!current_user_can('manage_options')) { // Typically, only admins should flush rules
            wp_send_json_error(['message' => __('You do not have permission to flush rewrite rules.', 'mobooking')], 403);
            return;
        }

        // Defer rewrite rule flushing to the 'shutdown' action hook.
        // This is the recommended way to flush rules to avoid issues with the $wp_rewrite global object state.
        update_option('mobooking_flush_rewrite_rules_flag', true);

        // Re-register our rules so they are definitely part of the flush
        // Assuming mobooking_add_rewrite_rules() is the function that sets them up and is hooked to init.
        // We need to ensure it's callable or directly call the relevant part if not hooked to init in a way that runs now.
        // For now, we rely on the next init call to register them before shutdown flushes.
        // A more direct way would be to call the function that contains add_rewrite_rule() here if possible and safe.
        // However, the flag and shutdown hook is generally safer for flushing.

        // The actual flushing will be done by a function hooked to 'shutdown' if the flag is true.
        // We need to add that function in functions.php or similar.

        wp_send_json_success(['message' => __('Rewrite rules will be flushed. This may take a moment to reflect on your site.', 'mobooking')]);
    }

    public function handle_get_business_settings_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not authenticated.', 'mobooking')], 403);
            return;
        }
        $settings = $this->get_business_settings($user_id);
        wp_send_json_success(['settings' => $settings]);
    }

    public function handle_save_business_settings_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not authenticated.', 'mobooking')], 403);
            return;
        }

        $settings_data = isset($_POST['settings']) ? (array) $_POST['settings'] : [];

        if (empty($settings_data)) {
            wp_send_json_error(['message' => __('No settings data received.', 'mobooking')], 400);
            return;
        }

        $result = $this->save_business_settings($user_id, $settings_data);

        if ($result) {
            wp_send_json_success(['message' => __('Business settings saved successfully.', 'mobooking')]);
        } else {
            wp_send_json_error(['message' => __('Failed to save some business settings.', 'mobooking')], 500);
        }
    }

    public function handle_get_booking_form_settings_ajax() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not authenticated.', 'mobooking')], 403);
            return;
        }
        $settings = $this->get_booking_form_settings($user_id);
        wp_send_json_success(['settings' => $settings]);
    }

public function handle_save_booking_form_settings_ajax() {
    // Verify nonce first
    if (!check_ajax_referer('mobooking_dashboard_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => __('Security check failed.', 'mobooking')], 403);
        return;
    }

    // Check user authentication
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(['message' => __('User not authenticated.', 'mobooking')], 403);
        return;
    }

    // Get and validate settings data
    $settings_data = isset($_POST['settings']) ? (array) $_POST['settings'] : [];
    
    if (empty($settings_data)) {
        wp_send_json_error(['message' => __('No settings data received.', 'mobooking')], 400);
        return;
    }

    // === Business Slug Uniqueness Validation ===
    if (isset($settings_data['bf_business_slug'])) {
        $slug_to_check = sanitize_title($settings_data['bf_business_slug']);

        if (!empty($slug_to_check)) {
            $existing_user_id = \MoBooking\Classes\Routes\BookingFormRouter::get_user_id_by_slug($slug_to_check);

            if ($existing_user_id !== null && $existing_user_id != $user_id) {
                // The slug is taken by another user.
                wp_send_json_error([
                    'message' => __('This business slug is already in use. Please choose another one.', 'mobooking'),
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
                'message' => __('Booking form settings saved successfully.', 'mobooking')
            ];
            
            // Include processed slug if it was sanitized
            if (isset($validated_settings['bf_business_slug'])) {
                $response_data['processed_slug'] = $validated_settings['bf_business_slug'];
            }
            
            wp_send_json_success($response_data);
        } else {
            wp_send_json_error([
                'message' => __('Failed to save settings. Please try again.', 'mobooking')
            ], 500);
        }
        
    } catch (Exception $e) {
        error_log('[MoBooking Settings Save] Exception: ' . $e->getMessage());
        wp_send_json_error([
            'message' => __('An error occurred while saving settings.', 'mobooking')
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
            $errors[] = sprintf(__('Invalid value for %s: %s', 'mobooking'), $key, $sanitized_value->get_error_message());
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

    $table_name = Database::get_table_name('tenant_settings');
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
        error_log('[MoBooking Settings Validate - Input] ' . print_r($settings_data, true));
        // Sanitize business slug
        if (isset($settings_data['bf_business_slug'])) {
            $original_slug = $settings_data['bf_business_slug'];
            $settings_data['bf_business_slug'] = sanitize_title($settings_data['bf_business_slug']);
            if ($original_slug !== $settings_data['bf_business_slug']) {
                error_log("[MoBooking Settings Validate] bf_business_slug changed from '$original_slug' to '{$settings_data['bf_business_slug']}'");
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
                     error_log("[MoBooking Settings Validate] Numeric field $field changed from '$original_val' to '{$settings_data[$field]}'");
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
                         error_log("[MoBooking Settings Validate] Color field $field changed from '$original_color' to '{$settings_data[$field]}'");
                    }
                } else {
                    // Log if a color was invalid and is being removed/defaulted
                    error_log("[MoBooking Settings Validate] Invalid color for $field: '$original_color'. Field will revert to default or be unset.");
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
                        error_log("[MoBooking Settings Validate] URL field $field cleared due to invalid value: '$original_url'");
                    } else {
                        $settings_data[$field] = $url;
                        if ($original_url !== $settings_data[$field]) {
                            error_log("[MoBooking Settings Validate] URL field $field sanitized from '$original_url' to '$url'");
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
            'bf_google_analytics_id', 'bf_font_family'
            // Note: bf_custom_css is handled separately to allow certain CSS content.
        ];
        foreach ($text_fields as $field) {
            if (isset($settings_data[$field])) {
                $original_text = $settings_data[$field];
                $settings_data[$field] = sanitize_text_field($settings_data[$field]);
                if ($original_text !== $settings_data[$field]) {
                    error_log("[MoBooking Settings Validate] Text field $field sanitized. Original: '$original_text' New: '{$settings_data[$field]}'");
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
                 error_log("[MoBooking Settings Validate] bf_custom_css was modified by wp_strip_all_tags.");
                 // Consider logging the before and after if it's short enough or a hash of it.
            }
        }

        // Log for boolean/checkbox like values (0 or 1)
        $boolean_like_fields = [
            'bf_show_progress_bar', 'bf_form_enabled', 'bf_allow_service_selection',
            'bf_allow_date_time_selection', 'bf_require_phone', 'bf_allow_special_instructions',
            'bf_show_pricing', 'bf_allow_discount_codes', 'bf_enable_recaptcha',
            'bf_enable_ssl_required', 'bf_debug_mode'
        ];
        foreach ($boolean_like_fields as $field) {
            if (isset($settings_data[$field])) {
                $original_bool_val = $settings_data[$field];
                $settings_data[$field] = ($settings_data[$field] === '1' || $settings_data[$field] === true) ? '1' : '0';
                if ($original_bool_val !== $settings_data[$field]) {
                    error_log("[MoBooking Settings Validate] Boolean field $field normalized from '$original_bool_val' to '{$settings_data[$field]}'");
                }
            }
        }


        error_log('[MoBooking Settings Validate - Output] ' . print_r($settings_data, true));
        return $settings_data;
    }

    public function get_setting(int $user_id, string $setting_name, $default_value = null) {
        $defaults = self::get_default_settings();
        if (empty($user_id) && $user_id !== 0) {
             return array_key_exists($setting_name, $defaults) ? $defaults[$setting_name] : $default_value;
        }

        $table_name = Database::get_table_name('tenant_settings');
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
        if (empty($user_id) && $user_id !== 0) return false;
        if (empty($setting_name)) return false;

        $table_name = Database::get_table_name('tenant_settings');

        $value_to_store = is_array($setting_value) || is_object($setting_value)
                        ? maybe_serialize($setting_value)
                        : (string) $setting_value;

        $result = $this->wpdb->replace(
            $table_name,
            [ 'user_id' => $user_id, 'setting_name' => $setting_name, 'setting_value' => $value_to_store, ],
            ['%d', '%s', '%s']
        );

        return $result !== false;
    }

    private function get_settings_by_prefix_or_keys(int $user_id, array $relevant_defaults): array {
        $settings_from_db = [];
        if (!empty($user_id) && !empty($relevant_defaults)) {
            $table_name = Database::get_table_name('tenant_settings');
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
        $parsed_settings['biz_currency_symbol'] = \MoBooking\Classes\Utils::get_currency_symbol($parsed_settings['biz_currency_code']);
        $parsed_settings['biz_currency_position'] = \MoBooking\Classes\Utils::get_currency_position($parsed_settings['biz_currency_code']);

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
                error_log("[MoBooking Settings] save_settings_group - Key: $key, Value: " . 
                    (is_array($value) ? json_encode($value) : $value) . ', Result: ' . 
                    ($update_result ? 'Success' : 'Failure'));

                if (!$update_result) {
                    $all_successful = false;
                    $db_error = $this->wpdb->last_error;
                    error_log("[MoBooking Settings] DB Error for $key (User: $user_id): " . $db_error);
                }
            } else {
                error_log("[MoBooking Settings] Skipped key (not in default_keys_for_group): $key");
            }
        }
        error_log('[MoBooking Settings] save_settings_group final result for user_id ' . $user_id . ': ' . ($all_successful ? 'All Successful' : 'Some Failed'));
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
                error_log("[MoBooking Settings] Failed to save setting: {$key} for user: {$user_id}");
            }
        } catch (Exception $e) {
            error_log("[MoBooking Settings] Exception saving {$key}: " . $e->getMessage());
        }
    }

    // Consider it successful if at least 80% of settings were saved
    $success_rate = $total_count > 0 ? ($success_count / $total_count) : 0;
    return $success_rate >= 0.8;
}

    public function save_business_settings(int $user_id, array $settings_data): bool {
        $business_setting_keys = array_filter(array_keys(self::get_default_settings()), function($key) {
            return strpos($key, 'biz_') === 0 || strpos($key, 'email_') === 0;
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
                if ($key === 'biz_email' && empty($default_value)) {
                    $value_to_set = $user_info->user_email;
                }
                if ($key === 'email_from_name' && empty($default_value)) {
                    $biz_name_val = $settings_instance->get_setting($user_id, 'biz_name');
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

        self::create_demo_services($user_id);
    }

    private static function create_demo_services(int $user_id)
    {
        if (get_user_meta($user_id, 'demo_services_created', true)) {
            return;
        }

        $services_manager = new \MoBooking\Classes\Services();

        // 1. Home Cleaning
        $home_cleaning_id = $services_manager->add_service($user_id, [
            'name' => 'Home Cleaning',
            'description' => 'A comprehensive cleaning service for your home.',
            'price' => 50,
            'duration' => 120,
            'icon' => 'preset:home-cleaning.svg',
        ]);

        if (!is_wp_error($home_cleaning_id)) {
            $services_manager->service_options_manager->add_service_option($user_id, $home_cleaning_id, [
                'name' => 'Number of Bedrooms',
                'type' => 'dropdown',
                'is_required' => true,
                'option_values' => json_encode([
                    ['label' => '1 Bedroom', 'price_impact' => 0],
                    ['label' => '2 Bedrooms', 'price_impact' => 20],
                    ['label' => '3 Bedrooms', 'price_impact' => 40],
                ]),
            ]);
            $services_manager->service_options_manager->add_service_option($user_id, $home_cleaning_id, [
                'name' => 'Deep Cleaning',
                'type' => 'checkbox',
                'is_required' => false,
                'price_impact_type' => 'fixed',
                'price_impact_value' => 50,
            ]);
        }

        // 2. Window Cleaning
        $window_cleaning_id = $services_manager->add_service($user_id, [
            'name' => 'Window Cleaning',
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
                'price_impact_type' => 'per_unit',
                'price_impact_value' => 5,
            ]);
        }

        // 3. Moving Cleaning
        $moving_cleaning_id = $services_manager->add_service($user_id, [
            'name' => 'Moving Cleaning',
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
                'option_values' => json_encode([
                    ['from' => 0, 'to' => 1000, 'price' => 0],
                    ['from' => 1001, 'to' => 2000, 'price' => 50],
                    ['from' => 2001, 'to' => 3000, 'price' => 100],
                ]),
            ]);
        }

        update_user_meta($user_id, 'demo_services_created', true);
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

        $table_name = Database::get_table_name('tenant_settings');
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

    public function get_email_templates(): array {
        $templates = [
            'booking_confirmation_customer' => [
                'name' => __('Customer Booking Confirmation', 'mobooking'),
                'subject_key' => 'email_booking_conf_subj_customer',
                'body_key' => 'email_booking_conf_body_customer',
                'variables' => ['{{customer_name}}', '{{business_name}}', '{{booking_reference}}', '{{service_names}}', '{{booking_date_time}}', '{{total_price}}', '{{service_address}}', '{{special_instructions}}', '{{booking_link}}']
            ],
            'booking_confirmation_admin' => [
                'name' => __('Admin New Booking Notification', 'mobooking'),
                'subject_key' => 'email_booking_conf_subj_admin',
                'body_key' => 'email_booking_conf_body_admin',
                'variables' => ['{{customer_name}}', '{{customer_email}}', '{{customer_phone}}', '{{business_name}}', '{{booking_reference}}', '{{service_names}}', '{{booking_date_time}}', '{{total_price}}', '{{service_address}}', '{{special_instructions}}', '{{admin_booking_link}}']
            ],
            'staff_assignment' => [
                'name' => __('Staff Assignment Notification', 'mobooking'),
                'subject_key' => 'email_staff_assign_subj',
                'body_key' => 'email_staff_assign_body',
                'variables' => ['{{staff_name}}', '{{customer_name}}', '{{booking_reference}}', '{{booking_date_time}}', '{{staff_dashboard_link}}']
            ],
            'welcome' => [
                'name' => __('Welcome Email', 'mobooking'),
                'subject_key' => 'email_welcome_subj',
                'body_key' => 'email_welcome_body',
                'variables' => ['{{customer_name}}', '{{company_name}}', '{{dashboard_link}}']
            ],
            'invitation' => [
                'name' => __('Invitation Email', 'mobooking'),
                'subject_key' => 'email_invitation_subj',
                'body_key' => 'email_invitation_body',
                'variables' => ['{{worker_email}}', '{{worker_role}}', '{{inviter_name}}', '{{registration_link}}']
            ]
        ];
        return $templates;
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
            'label' => __('Set Business Name', 'mobooking'),
            'completed' => $step1_complete,
        ];

        // Step 2: Add a Service
        $services_manager = new \MoBooking\Classes\Services();
        $services_count = $services_manager->get_services_count($user_id);
        $step2_complete = $services_count > 0;
        $progress['steps'][] = [
            'id' => 'add_service',
            'label' => __('Add Your First Service', 'mobooking'),
            'completed' => $step2_complete,
        ];

        // Step 3: Define Service Area
        $areas_manager = new \MoBooking\Classes\Areas();
        $areas_count = $areas_manager->get_areas_count_by_user($user_id);
        $step3_complete = $areas_count > 0;
        $progress['steps'][] = [
            'id' => 'define_area',
            'label' => __('Define Service Area', 'mobooking'),
            'completed' => $step3_complete,
        ];

        $progress['total_count'] = count($progress['steps']);
        $progress['completed_count'] = count(array_filter($progress['steps'], fn($step) => $step['completed']));
        $progress['is_complete'] = $progress['completed_count'] === $progress['total_count'];

        return $progress;
    }
}