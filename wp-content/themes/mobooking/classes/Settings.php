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

    public function get_setting(int $user_id, string $setting_name, $default_value = null) {
        if (empty($user_id) && $user_id !== 0) { // Allow user_id 0 for potential global (non-tenant) settings if ever needed
             return array_key_exists($setting_name, self::$default_tenant_settings) ? self::$default_tenant_settings[$setting_name] : $default_value;
        }

        $table_name = Database::get_table_name('tenant_settings');
        $value = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT setting_value FROM $table_name WHERE user_id = %d AND setting_name = %s",
            $user_id, $setting_name
        ));

        if (is_null($value)) {
            // If not found in DB, return the static default for this known setting key.
            // Specific dynamic defaults (like biz_email based on user's actual email) are handled by group getters.
            return array_key_exists($setting_name, self::$default_tenant_settings) ? self::$default_tenant_settings[$setting_name] : $default_value;
        }
        return maybe_unserialize($value);
    }

    public function update_setting(int $user_id, string $setting_name, $setting_value): bool {
        if (empty($user_id) && $user_id !== 0) return false; // Require user_id unless explicitly handling global settings
        if (empty($setting_name)) return false;

        $table_name = Database::get_table_name('tenant_settings');

        $value_to_store = is_array($setting_value) || is_object($setting_value)
                        ? maybe_serialize($setting_value)
                        : (string) $setting_value;

        // Using REPLACE which is simpler for upsert if the table structure supports it (unique key on user_id, setting_name)
        $result = $this->wpdb->replace(
            $table_name,
            [
                'user_id' => $user_id,
                'setting_name' => $setting_name,
                'setting_value' => $value_to_store,
            ],
            ['%d', '%s', '%s']
        );

        return $result !== false;
    }

    private function get_settings_by_prefix(int $user_id, array $relevant_defaults): array {
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
                    if (array_key_exists($row['setting_name'], $relevant_defaults)) { // Ensure only relevant keys are processed
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
        return $this->get_settings_by_prefix($user_id, $booking_form_defaults);
    }

    public function get_business_settings(int $user_id): array {
        $business_setting_keys = array_filter(array_keys(self::$default_tenant_settings), function($key) {
            return strpos($key, 'biz_') === 0 || strpos($key, 'email_') === 0;
        });
        $business_defaults = array_intersect_key(self::$default_tenant_settings, array_flip($business_setting_keys));

        $parsed_settings = $this->get_settings_by_prefix($user_id, $business_defaults);

        // Apply dynamic defaults if values are still empty after fetching from DB
        if ($user_id > 0) {
            $user_info = null; // Lazy load user_info
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
                // If biz_email_val is still empty here, it means either the stored biz_email was invalid or user's registration email was invalid.
                // Fallback to site admin email is safer.
                $parsed_settings['email_from_address'] = !empty($biz_email_val) ? $biz_email_val : get_option('admin_email');
            }
        }
        return $parsed_settings;
    }

    public function save_settings_group(int $user_id, array $settings_data, array $default_keys_for_group): bool {
        if (empty($user_id)) return false;
        $all_successful = true;

        foreach ($settings_data as $key => $value) {
            if (array_key_exists($key, $default_keys_for_group)) {
                $sanitized_value = $value;
                // Common sanitizations
                if (is_string($value)) $sanitized_value = sanitize_text_field($value);
                if (is_numeric($value)) $sanitized_value = strval($value); // Store as string

                // Specific sanitizations by key prefix or full key
                if (strpos($key, 'bf_') === 0) { // Booking form settings
                    switch ($key) {
                        case 'bf_theme_color': $sanitized_value = sanitize_hex_color($value); break;
                        case 'bf_header_text': case 'bf_step_1_title': case 'bf_step_2_title': case 'bf_step_3_title': case 'bf_step_4_title': case 'bf_step_5_title': case 'bf_thank_you_message':
                            $sanitized_value = sanitize_text_field($value); break;
                        case 'bf_show_progress_bar': $sanitized_value = ($value === '1' || $value === true || $value === 'on') ? '1' : '0'; break;
                        case 'bf_allow_cancellation_hours': $sanitized_value = intval($value); if ($sanitized_value < 0) $sanitized_value = 0; break;
                        case 'bf_custom_css': $sanitized_value = sanitize_textarea_field($value); break; // Basic, consider more advanced CSS sanitization if needed
                        case 'bf_terms_conditions_url': $sanitized_value = esc_url_raw($value); break;
                    }
                } elseif (strpos($key, 'biz_') === 0 || strpos($key, 'email_') === 0) { // Business settings
                     switch ($key) {
                        case 'biz_name': case 'biz_phone': case 'email_from_name': case 'email_booking_conf_subj_customer': case 'email_booking_conf_subj_admin':
                            $sanitized_value = sanitize_text_field($value); break;
                        case 'biz_email': case 'email_from_address':
                            $sanitized_value = sanitize_email($value); break;
                        case 'biz_address': case 'email_booking_conf_body_customer': case 'email_booking_conf_body_admin':
                            $sanitized_value = sanitize_textarea_field($value); break; // Use wp_kses_post if HTML is allowed and rules are defined
                        case 'biz_logo_url':
                            $sanitized_value = esc_url_raw($value); break;
                        case 'biz_hours_json':
                            $json_val = stripslashes($value);
                            if (is_null(json_decode($json_val)) && !empty($json_val) && $json_val !== '{}' && $json_val !== '[]') {
                                $sanitized_value = '{}'; // Invalid JSON, default to empty object
                            } else { $sanitized_value = sanitize_text_field($json_val); } // Store as string
                            break;
                    }
                }

                if (!$this->update_setting($user_id, $key, $sanitized_value)) {
                    $all_successful = false;
                }
            }
        }
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
        if (empty($user_id) && $user_id !== 0) return; // Should have a valid user_id

        $settings_instance = new self();
        $user_info = get_userdata($user_id);

        foreach (self::$default_tenant_settings as $key => $default_value) {
            $value_to_set = $default_value; // Start with the static default

            // Apply dynamic defaults for specific keys
            if ($key === 'biz_email' && empty($default_value)) { // If static default for biz_email is empty
                $value_to_set = $user_info ? $user_info->user_email : '';
            }
            // For email_from_name and email_from_address, their defaults depend on biz_name and biz_email
            // If those are also part of $default_tenant_settings, they might get set first, or use their static defaults.
            if ($key === 'email_from_name' && empty($default_value)) {
                // Get biz_name (it might have been set dynamically if biz_email was, or it's static default)
                $biz_name_val = isset(self::$default_tenant_settings['biz_name']) && !empty(self::$default_tenant_settings['biz_name'])
                                ? self::$default_tenant_settings['biz_name']
                                : ($user_info ? $user_info->display_name : ''); // Fallback to display_name if biz_name default is empty
                $value_to_set = !empty($biz_name_val) ? $biz_name_val : get_bloginfo('name');
            }
            if ($key === 'email_from_address' && empty($default_value)) {
                $biz_email_val = isset($dynamic_defaults['biz_email']) ? $dynamic_defaults['biz_email'] : ($user_info ? $user_info->user_email : '');
                $value_to_set = !empty($biz_email_val) && is_email($biz_email_val) ? $biz_email_val : get_option('admin_email');
            }

            $settings_instance->update_setting($user_id, $key, $value_to_set);
        }
    }
}
