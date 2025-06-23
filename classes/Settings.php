<?php
namespace MoBooking\Classes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Settings {
    private $wpdb;

    // Combined default settings for all tenant-specific configurations
    private static $default_tenant_settings = [
        // Booking Form Settings (prefix bf_)
        'bf_theme_color'              => '#1abc9c',
        'bf_header_text'              => 'Book Our Services Online',
        'bf_show_progress_bar'        => '1', // '1' for true, '0' for false
        'bf_allow_cancellation_hours' => 24,  // Integer hours, 0 for no cancellation allowed
        'bf_custom_css'               => '',
        'bf_terms_conditions_url'     => '',
        'bf_business_slug'            => '', // New setting for business slug
        'bf_step_1_title'             => 'Step 1: Location & Date',
        'bf_step_2_title'             => 'Step 2: Choose Services',
        'bf_step_3_title'             => 'Step 3: Service Options',
        'bf_step_4_title'             => 'Step 4: Your Details',
        'bf_step_5_title'             => 'Step 5: Review & Confirm',
        'bf_thank_you_message'        => 'Thank you for your booking! A confirmation email has been sent to you.',

        // Business Settings (prefix biz_ or email_)
        'biz_name'                            => '', // Tenant's business name
        'biz_email'                           => '', // Tenant's primary business email (dynamic default: user's registration email)
        'biz_phone'                           => '',
        'biz_address'                         => '', // Multiline address
        'biz_logo_url'                        => '', // URL to business logo
        'biz_hours_json'                      => '{}', // JSON string for business hours, e.g., {"monday":{"open":"09:00","close":"17:00","is_closed":false}, ...}
        'biz_currency_code'                   => 'USD', // Uppercase, 3 characters
        'biz_user_language'                   => 'en_US', // Format like xx_XX

        // Email Sender Settings
        'email_from_name'                     => '', // Name for outgoing emails (dynamic default: biz_name or site title)
        'email_from_address'                  => '', // Email address for outgoing emails (dynamic default: biz_email or site admin email)

        // Email Template Settings (Customer Confirmation)
        'email_booking_conf_subj_customer'    => 'Your Booking Confirmation - Ref: {{booking_reference}}',
        'email_booking_conf_body_customer'    => "Dear {{customer_name}},

Thank you for your booking with {{business_name}}. Your booking (Ref: {{booking_reference}}) is confirmed.

Booking Summary:
Services: {{service_names}}
Date & Time: {{booking_date_time}}
Service Address:
{{service_address}}
Total Price: {{total_price}}

If you have any questions, please contact {{business_name}}.

Thank you,
{{business_name}}",

        // Email Template Settings (Admin Notification)
        'email_booking_conf_subj_admin'       => 'New Booking Received - Ref: {{booking_reference}} for {{customer_name}}',
        'email_booking_conf_body_admin'       => "You have received a new booking (Ref: {{booking_reference}}).

Customer Details:
Name: {{customer_name}}
Email: {{customer_email}}
Phone: {{customer_phone}}

Booking Details:
Services: {{service_names}}
Date & Time: {{booking_date_time}}
Service Address:
{{service_address}}
Total Price: {{total_price}}
Special Instructions:
{{special_instructions}}

Please review this booking in your dashboard: {{admin_booking_link}}",
    ];

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

        error_log('[MoBooking Settings] handle_save_booking_form_settings_ajax: Received POST settings: ' . print_r($_POST['settings'], true));
        $result = $this->save_booking_form_settings($user_id, $settings_data);

        if ($result) {
            wp_send_json_success(['message' => __('Booking form settings saved successfully.', 'mobooking')]);
        } else {
            error_log('[MoBooking Settings] handle_save_booking_form_settings_ajax: save_booking_form_settings returned false.');
            wp_send_json_error(['message' => __('Failed to save some settings. Please check logs if issues persist.', 'mobooking')], 500);
        }
    }

    public function get_setting(int $user_id, string $setting_name, $default_value = null) {
        if (empty($user_id) && $user_id !== 0) {
             return array_key_exists($setting_name, self::$default_tenant_settings) ? self::$default_tenant_settings[$setting_name] : $default_value;
        }

        $table_name = Database::get_table_name('tenant_settings');
        $value = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT setting_value FROM $table_name WHERE user_id = %d AND setting_name = %s",
            $user_id, $setting_name
        ));

        if (is_null($value)) {
            return array_key_exists($setting_name, self::$default_tenant_settings) ? self::$default_tenant_settings[$setting_name] : $default_value;
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
        $booking_form_defaults = array_filter(self::$default_tenant_settings, function($key) {
            return strpos($key, 'bf_') === 0;
        }, ARRAY_FILTER_USE_KEY);
        return $this->get_settings_by_prefix_or_keys($user_id, $booking_form_defaults);
    }

    public function get_business_settings(int $user_id): array {
        $business_setting_keys = array_filter(array_keys(self::$default_tenant_settings), function($key) {
            return strpos($key, 'biz_') === 0 || strpos($key, 'email_') === 0;
        });
        $business_defaults = array_intersect_key(self::$default_tenant_settings, array_flip($business_setting_keys));

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
                $parsed_settings['biz_email'] = $user_info ? $user_info->user_email : '';
            }
            if (empty($parsed_settings['email_from_name'])) {
                $biz_name_val = !empty($parsed_settings['biz_name']) ? $parsed_settings['biz_name'] : '';
                $parsed_settings['email_from_name'] = !empty($biz_name_val) ? $biz_name_val : get_bloginfo('name');
            }
            if (empty($parsed_settings['email_from_address'])) {
                 $biz_email_val = !empty($parsed_settings['biz_email']) && is_email($parsed_settings['biz_email']) ? $parsed_settings['biz_email'] : '';
                $parsed_settings['email_from_address'] = !empty($biz_email_val) ? $biz_email_val : get_option('admin_email');
            }
        }
        return $parsed_settings;
    }

    public function save_settings_group(int $user_id, array $settings_data, array $default_keys_for_group): bool {
        if (empty($user_id)) return false;
        $all_successful = true;
        error_log('[MoBooking Settings] save_settings_group for user_id: ' . $user_id . ' with data: ' . print_r($settings_data, true));


        foreach ($settings_data as $key => $value) {
            if (array_key_exists($key, $default_keys_for_group)) {
                error_log("[MoBooking Settings] Processing key: $key, Original value: " . print_r($value, true));
                $sanitized_value = $value;

                // General sanitization based on expected type from default
                $default_type = gettype($default_keys_for_group[$key]);
                if (is_string($value)) {
                    if (strpos($key, 'email_') === 0 && strpos($key, 'body') !== false) {
                        $sanitized_value = sanitize_textarea_field($value);
                    } else if (strpos($key, 'css') !== false) {
                         $sanitized_value = sanitize_textarea_field($value); // Keep CSS as is, but WP might strip some.
                    } else {
                        $sanitized_value = sanitize_text_field($value);
                    }
                } elseif (is_bool($value) || $default_type === 'boolean' || $key === 'bf_show_progress_bar') {
                     $sanitized_value = ($value === '1' || $value === true || $value === 'on') ? '1' : '0';
                } elseif (is_numeric($value) || $default_type === 'integer' || $default_type === 'double') {
                     if (strpos($key, 'hours') !== false) $sanitized_value = intval($value); else $sanitized_value = strval($value); // Ensure numbers are numbers
                } else {
                     // Fallback for other types if any, though most should be covered
                     $sanitized_value = sanitize_text_field(strval($value));
                }
                error_log("[MoBooking Settings] General Sanitized value for $key: " . print_r($sanitized_value, true));

                // Specific key-based sanitization overrides
                switch ($key) {
                    case 'bf_theme_color': $sanitized_value = sanitize_hex_color($value); break;
                    case 'bf_terms_conditions_url': case 'biz_logo_url': $sanitized_value = esc_url_raw($value); break;
                    case 'bf_business_slug':
                        $sanitized_value = sanitize_title($value);
                        // Ensure it's not empty after sanitization, could fall back to a default or disallow empty
                        if (empty($sanitized_value)) {
                            // Option: generate a unique one, or rely on validation to prevent empty save
                            // For now, allow empty, which might mean "don't use slug" or use user ID.
                            // However, the form logic tries to prefill from biz_name, so it's unlikely to be empty if biz_name exists.
                        }
                        break;
                    case 'biz_email': case 'email_from_address': $sanitized_value = sanitize_email($value); break;
                    case 'biz_hours_json':
                        $json_val = stripslashes($value); // Remove slashes added by WP
                        // Basic check if it's a potentially valid JSON structure before decoding
                        if (is_string($json_val) && strlen($json_val) > 1 && 
                            (($json_val[0] == '{' && $json_val[strlen($json_val)-1] == '}') || 
                             ($json_val[0] == '[' && $json_val[strlen($json_val)-1] == ']'))) {
                            
                            json_decode($json_val); // Try to decode
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $sanitized_value = wp_kses_post($json_val); // Sanitize string content if JSON is valid
                            } else {
                                error_log("[MoBooking Settings] Invalid JSON for $key: " . $json_val . " - Error: " . json_last_error_msg());
                                $sanitized_value = '{}'; // Default to empty JSON object if invalid
                            }
                        } elseif (empty($json_val)) {
                            $sanitized_value = '{}';
                        } else {
                             error_log("[MoBooking Settings] Non-JSON string or empty for $key: " . $json_val);
                            $sanitized_value = '{}'; // Default if not a typical JSON structure string
                        }
                        break;
                    case 'biz_currency_code':
                        $sanitized_code = preg_replace('/[^A-Z]/', '', strtoupper(trim($value)));
                        if (strlen($sanitized_code) === 3 && ctype_upper($sanitized_code)) {
                            $sanitized_value = $sanitized_code;
                        } else {
                            $sanitized_value = 'USD'; // Default if validation fails
                            error_log("[MoBooking Settings] Invalid currency code '$value', defaulted to USD for key $key.");
                        }
                        break;
                    case 'biz_user_language':
                         if (preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/', trim($value))) { // allow xx or xx_XX
                            $sanitized_value = trim($value);
                        } else {
                            $sanitized_value = 'en_US'; // Default if validation fails
                            error_log("[MoBooking Settings] Invalid language code '$value', defaulted to en_US for key $key.");
                        }
                        break;
                    case 'bf_custom_css': // Re-affirm specific handling for CSS if wp_kses_post is too aggressive
                        $sanitized_value = wp_strip_all_tags(stripslashes($value)); // Basic tag stripping, but allows CSS characters
                        break;
                }
                error_log("[MoBooking Settings] Specific Sanitized value for $key: " . print_r($sanitized_value, true));


                error_log("[MoBooking Settings] Attempting to save for user $user_id, key $key, value: " . print_r($sanitized_value, true));
                $update_result = $this->update_setting($user_id, $key, $sanitized_value);
                error_log("[MoBooking Settings] Result of update_setting for $key: " . ($update_result ? 'Success' : 'Failure'));

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

    public function save_booking_form_settings(int $user_id, array $settings_data): bool {
        $booking_form_defaults = array_filter(self::$default_tenant_settings, function($key) {
            return strpos($key, 'bf_') === 0;
        }, ARRAY_FILTER_USE_KEY);
        return $this->save_settings_group($user_id, $settings_data, $booking_form_defaults);
    }

    public function save_business_settings(int $user_id, array $settings_data): bool {
        $business_setting_keys = array_filter(array_keys(self::$default_tenant_settings), function($key) {
            return strpos($key, 'biz_') === 0 || strpos($key, 'email_') === 0;
        });
        $business_defaults = array_intersect_key(self::$default_tenant_settings, array_flip($business_setting_keys));
        return $this->save_settings_group($user_id, $settings_data, $business_defaults);
    }

    public static function initialize_default_settings(int $user_id) {
        if (empty($user_id) && $user_id !== 0) return;

        $settings_instance = new self();
        $user_info = get_userdata($user_id);

        foreach (self::$default_tenant_settings as $key => $default_value) {
            $value_to_set = $default_value;

            if ($user_info) {
                if ($key === 'biz_email' && empty($default_value)) {
                    $value_to_set = $user_info->user_email;
                }
                if ($key === 'email_from_name' && empty($default_value)) {
                    $biz_name_val = $settings_instance->get_setting($user_id, 'biz_name');
                    if (empty($biz_name_val)) $biz_name_val = isset(self::$default_tenant_settings['biz_name']) ? self::$default_tenant_settings['biz_name'] : '';
                    if (empty($biz_name_val)) $biz_name_val = $user_info->display_name;

                    $value_to_set = !empty($biz_name_val) ? $biz_name_val : get_bloginfo('name');
                }
                if ($key === 'email_from_address' && empty($default_value)) {
                    // Prefer dynamically set biz_email if available, then user_info->user_email
                    $biz_email_val = $settings_instance->get_setting($user_id, 'biz_email');
                    if (empty($biz_email_val) || !is_email($biz_email_val)) $biz_email_val = $user_info->user_email;

                    $value_to_set = !empty($biz_email_val) && is_email($biz_email_val) ? $biz_email_val : get_option('admin_email');
                }
            }

            $settings_instance->update_setting($user_id, $key, $value_to_set);
        }
    }
}
