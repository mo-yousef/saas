<?php
namespace MoBooking\Classes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Notifications {

    public function __construct() {
        // Constructor can be used if we need to load settings or helpers
    }

    /**
     * Wraps email content in a standardized HTML template.
     * @param string $subject The email subject.
     * @param string $header_title The title to display in the email header.
     * @param string $body_content The main HTML content of the email.
     * @return string The full HTML email.
     */
    private function hex_to_rgba($hex, $alpha = 0.1) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return "rgba($r, $g, $b, $alpha)";
    }

    private function get_styled_email_html(string $subject, string $header_title, string $body_content, int $user_id): string {
        $template_path = get_template_directory() . '/templates/email/base-email-template.php';

        if (!file_exists($template_path)) {
            // Fallback to a simple layout if template is missing
            return "<h1>{$header_title}</h1>{$body_content}";
        }

        $settings_manager = new Settings();
        $biz_settings = $settings_manager->get_business_settings($user_id);
        $booking_form_settings = $settings_manager->get_booking_form_settings($user_id);

        ob_start();
        include $template_path;
        $template = ob_get_clean();

        $replacements = [
            '{{SUBJECT}}'          => $subject,
            '{{BODY_CONTENT}}'     => $body_content,
            '{{LOGO_URL}}'         => esc_url($biz_settings['biz_logo_url']),
            '{{SITE_NAME}}'        => esc_html($biz_settings['biz_name']),
            '{{SITE_URL}}'         => home_url('/'),
            '{{BIZ_NAME}}'         => esc_html($biz_settings['biz_name']),
            '{{BIZ_ADDRESS}}'      => esc_html($biz_settings['biz_address']),
            '{{BIZ_PHONE}}'        => esc_html($biz_settings['biz_phone']),
            '{{BIZ_EMAIL}}'        => esc_html($biz_settings['biz_email']),
            '{{THEME_COLOR}}'      => esc_attr($booking_form_settings['bf_theme_color']),
            '{{THEME_COLOR_LIGHT}}'=> $this->hex_to_rgba($booking_form_settings['bf_theme_color'], 0.1),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Builds common email headers.
     * @param int $tenant_user_id The ID of the tenant/business owner.
     * @return array Array of email headers.
     */
    private function get_email_headers(int $tenant_user_id = 0) {
        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $from_name = get_bloginfo('name');
        $from_email = get_option('admin_email'); // Default to WordPress admin email

        if ($tenant_user_id) {
            $tenant_info = get_userdata($tenant_user_id);
            if ($tenant_info) {
                // Attempt to get a configured business name for the tenant
                $tenant_business_name_setting = get_user_meta($tenant_user_id, 'mobooking_business_name', true);
                if (!empty($tenant_business_name_setting)) {
                    $from_name = $tenant_business_name_setting;
                } elseif (!empty($tenant_info->display_name) && $tenant_info->display_name !== $tenant_info->user_login) {
                    $from_name = $tenant_info->display_name;
                }
                // else it remains site name or previously set tenant business name

                // TODO: Allow tenants to configure a specific 'From Email'.
                // Using tenant's user_email as 'From' can cause deliverability issues.
                // For now, we'll use the site's admin_email to avoid this,
                // but ideally, this should be a configurable 'noreply@yourplatform.com'
                // or a verified sender email for the tenant.
                // $from_email = $tenant_info->user_email; // Example of using tenant's email, not recommended for 'From'
            }
        }

        // Using a generic site 'From' address is safer for deliverability.
        // A 'Reply-To' header can be set to the tenant's actual email address.
        $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';

        if ($tenant_user_id && isset($tenant_info) && $tenant_info) {
            // If tenant has a different preferred reply-to email, use it.
            // For now, using their main user email for reply-to.
             $reply_to_email = $tenant_info->user_email;
             $headers[] = 'Reply-To: ' . $from_name . ' <' . $reply_to_email . '>';
        }

        return $headers;
    }

    /**
     * Sends booking confirmation to the customer.
     * @param array $booking_details Expected keys: booking_reference, service_names (string), booking_date_time (string), total_price, customer_name, service_address.
     * @param string $customer_email
     * @param int $tenant_user_id
     * @return bool
     */
    public function send_booking_confirmation_customer(array $booking_details, string $customer_email, int $tenant_user_id) {
        if (empty($customer_email) || !is_email($customer_email) || empty($booking_details)) {
            // error_log('MoBooking Notifications: Missing data for send_booking_confirmation_customer.');
            return false;
        }

        $settings_manager = new Settings();
        $locale_switched_for_email = false;
        $original_locale = get_locale();
        $user_language = '';

        if ($tenant_user_id) {
            $user_language = $settings_manager->get_setting($tenant_user_id, 'biz_user_language', '');
            if (!empty($user_language) && is_string($user_language) && preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/', $user_language)) {
                if ($original_locale !== $user_language) {
                    if (switch_to_locale($user_language)) {
                        $locale_switched_for_email = true;
                        // Ensure MOBOOKING_THEME_DIR is available or use get_template_directory()
                        $theme_dir = defined('MOBOOKING_THEME_DIR') ? MOBOOKING_THEME_DIR : get_template_directory();
                        load_theme_textdomain('mobooking', $theme_dir . '/languages');
                    }
                }
            }
        }

        $tenant_business_name = get_bloginfo('name'); // Default to site name
        if ($tenant_user_id) {
            $tenant_info = get_userdata($tenant_user_id);
            if ($tenant_info) {
                 $tenant_business_name_setting = get_user_meta($tenant_user_id, 'mobooking_business_name', true);
                 // Use business name setting, fallback to display name (if not login), then site name
                 if (!empty($tenant_business_name_setting)) {
                    $tenant_business_name = $tenant_business_name_setting;
                 } elseif (!empty($tenant_info->display_name) && $tenant_info->display_name !== $tenant_info->user_login) {
                    $tenant_business_name = $tenant_info->display_name;
                 }
            }
        }

        $ref = isset($booking_details['booking_reference']) ? esc_html($booking_details['booking_reference']) : __('N/A', 'mobooking');
        $services = isset($booking_details['service_names']) ? esc_html($booking_details['service_names']) : __('N/A', 'mobooking');
        $datetime = isset($booking_details['booking_date_time']) ? esc_html($booking_details['booking_date_time']) : __('N/A', 'mobooking');
        $raw_total_price = isset($booking_details['total_price']) ? floatval($booking_details['total_price']) : 0;
        $customer_name = isset($booking_details['customer_name']) ? esc_html($booking_details['customer_name']) : __('Customer', 'mobooking');
        $address = isset($booking_details['service_address']) ? nl2br(esc_html($booking_details['service_address'])) : __('N/A', 'mobooking');

        $settings_manager = new Settings();
        $biz_currency_code = $settings_manager->get_setting($tenant_user_id, 'biz_currency_code', 'USD');
        $price_display = $biz_currency_code . ' ' . number_format_i18n($raw_total_price, 2);

        $subject = sprintf(__('Your Booking Confirmation with %s - Ref: %s', 'mobooking'), $tenant_business_name, $ref);

        $subject_template = $settings_manager->get_setting($tenant_user_id, 'email_booking_conf_subj_customer', 'Your Booking Confirmation - Ref: {{booking_reference}}');
        $body_template = $settings_manager->get_setting($tenant_user_id, 'email_booking_conf_body_customer', "Dear {{customer_name}},

Thank you for your booking with {{business_name}}. Your booking (Ref: {{booking_reference}}) is confirmed.

Booking Summary:
Services: {{service_names}}
Date & Time: {{booking_date_time}}
Service Address:
{{service_address}}
Total Price: {{total_price}}

If you have any questions, please contact {{business_name}}.

Thank you,
{{business_name}}");

        $replacements = [
            '{{customer_name}}' => $customer_name,
            '{{business_name}}' => $tenant_business_name,
            '{{booking_reference}}' => $ref,
            '{{service_names}}' => $services,
            '{{booking_date_time}}' => $datetime,
            '{{total_price}}' => $price_display,
            '{{service_address}}' => $address,
            '{{special_instructions}}' => '', // Not available in this context
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject_template);
        $body_content = nl2br(str_replace(array_keys($replacements), array_values($replacements), $body_template));

        $full_email_html = $this->get_styled_email_html($subject, $tenant_business_name, $body_content, $tenant_user_id);

        $headers = $this->get_email_headers($tenant_user_id);
        $email_sent = wp_mail($customer_email, $subject, $full_email_html, $headers);

        if ($locale_switched_for_email) {
            restore_current_locale();
            // Reload text domain for original locale
            $theme_dir = defined('MOBOOKING_THEME_DIR') ? MOBOOKING_THEME_DIR : get_template_directory();
            load_theme_textdomain('mobooking', $theme_dir . '/languages');
        }

        return $email_sent;
    }

    /**
     * Sends new booking notification to the admin/business owner.
     * @param array $booking_details Expected keys: booking_reference, service_names, booking_date_time, total_price, customer_name, customer_email, customer_phone, service_address, special_instructions.
     * @param int $tenant_user_id
     * @return bool
     */
    public function send_booking_confirmation_admin(array $booking_details, int $tenant_user_id) {
        if (empty($tenant_user_id) || empty($booking_details)) {
            // error_log('MoBooking Notifications: Missing data for send_booking_confirmation_admin.');
            return false;
        }

        $settings_manager = new Settings(); // Moved up for language setting
        $locale_switched_for_email = false;
        $original_locale = get_locale();
        $user_language = '';

        if ($tenant_user_id) { // tenant_user_id is the admin/business owner here
            $user_language = $settings_manager->get_setting($tenant_user_id, 'biz_user_language', '');
            if (!empty($user_language) && is_string($user_language) && preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/', $user_language)) {
                if ($original_locale !== $user_language) {
                    if (switch_to_locale($user_language)) {
                        $locale_switched_for_email = true;
                        $theme_dir = defined('MOBOOKING_THEME_DIR') ? MOBOOKING_THEME_DIR : get_template_directory();
                        load_theme_textdomain('mobooking', $theme_dir . '/languages');
                    }
                }
            }
        }

        $tenant_info = get_userdata($tenant_user_id);
        if (!$tenant_info) {
            // error_log('MoBooking Notifications: Invalid tenant_user_id for admin confirmation.');
            // Restore locale if it was switched before returning false
            if ($locale_switched_for_email) {
                restore_current_locale();
                $theme_dir = defined('MOBOOKING_THEME_DIR') ? MOBOOKING_THEME_DIR : get_template_directory();
                load_theme_textdomain('mobooking', $theme_dir . '/languages');
            }
            return false;
        }
        $admin_email = $tenant_info->user_email;

        // Business name for email content (can be different from site name)
        $tenant_business_name = $settings_manager->get_setting($tenant_user_id, 'biz_name', get_bloginfo('name'));
        if (empty($tenant_business_name)) {
            $tenant_business_name = get_bloginfo('name');
        }


        $ref = isset($booking_details['booking_reference']) ? esc_html($booking_details['booking_reference']) : __('N/A', 'mobooking');
        $services = isset($booking_details['service_names']) ? esc_html($booking_details['service_names']) : __('N/A', 'mobooking');
        $datetime = isset($booking_details['booking_date_time']) ? esc_html($booking_details['booking_date_time']) : __('N/A', 'mobooking');
        // $price = isset($booking_details['total_price']) ? number_format_i18n($booking_details['total_price'], 2) : __('N/A', 'mobooking'); // Price variable unused, raw_total_price is used below
        $raw_total_price = isset($booking_details['total_price']) ? floatval($booking_details['total_price']) : 0;
        $customer_name = isset($booking_details['customer_name']) ? esc_html($booking_details['customer_name']) : __('N/A', 'mobooking');
        $customer_email_val = isset($booking_details['customer_email']) ? esc_html($booking_details['customer_email']) : __('N/A', 'mobooking');
        $customer_phone = isset($booking_details['customer_phone']) ? esc_html($booking_details['customer_phone']) : __('N/A', 'mobooking');
        $address = isset($booking_details['service_address']) ? nl2br(esc_html($booking_details['service_address'])) : __('N/A', 'mobooking');
        $instructions = isset($booking_details['special_instructions']) && !empty($booking_details['special_instructions']) ? nl2br(esc_html($booking_details['special_instructions'])) : __('None', 'mobooking');

        // Settings manager already instantiated above
        $biz_currency_code = $settings_manager->get_setting($tenant_user_id, 'biz_currency_code', 'USD');
        $price_display = $biz_currency_code . ' ' . number_format_i18n($raw_total_price, 2);

        $subject_template = $settings_manager->get_setting($tenant_user_id, 'email_booking_conf_subj_admin', 'New Booking Received - Ref: {{booking_reference}} for {{customer_name}}');
        $body_template = $settings_manager->get_setting($tenant_user_id, 'email_booking_conf_body_admin', "You have received a new booking (Ref: {{booking_reference}}).

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

Please review this booking in your dashboard: {{admin_booking_link}}");

        $replacements = [
            '{{customer_name}}' => $customer_name,
            '{{customer_email}}' => $customer_email_val,
            '{{customer_phone}}' => $customer_phone,
            '{{business_name}}' => $tenant_business_name,
            '{{booking_reference}}' => $ref,
            '{{service_names}}' => $services,
            '{{booking_date_time}}' => $datetime,
            '{{total_price}}' => $price_display,
            '{{service_address}}' => $address,
            '{{special_instructions}}' => $instructions,
            '{{admin_booking_link}}' => esc_url(home_url('/dashboard/bookings/')),
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject_template);
        $body_content = nl2br(str_replace(array_keys($replacements), array_values($replacements), $body_template));

        $full_email_html = $this->get_styled_email_html($subject, $tenant_business_name, $body_content, $tenant_user_id);

        $headers = $this->get_email_headers($tenant_user_id);
        $email_sent = wp_mail($admin_email, $subject, $full_email_html, $headers);

        if ($locale_switched_for_email) {
            restore_current_locale();
            $theme_dir = defined('MOBOOKING_THEME_DIR') ? MOBOOKING_THEME_DIR : get_template_directory();
            load_theme_textdomain('mobooking', $theme_dir . '/languages');
        }

        return $email_sent;
    }

    // Placeholder for other notifications
    // public function send_booking_update_email(...) {}
    // public function send_password_reset_email(...) {} // Usually handled by WP default

    /**
     * Sends a notification to a staff member when a booking is assigned to them.
     * @param int $staff_user_id The ID of the staff member.
     * @param int $booking_id The ID of the booking.
     * @param array $booking_details Basic booking details (e.g., ref, customer name, date/time).
     * @param int $tenant_user_id The ID of the business owner.
     * @return bool
     */
    public function send_staff_assignment_notification(int $staff_user_id, int $booking_id, array $booking_details, int $tenant_user_id) {
        $staff_user = get_userdata($staff_user_id);
        if (!$staff_user || empty($staff_user->user_email)) {
            error_log("MoBooking Notifications: Invalid staff user ID or email for assignment notification. Staff ID: {$staff_user_id}");
            return false;
        }

        $settings_manager = new Settings();
        $locale_switched = false;
        $original_locale = get_locale();
        // Prefer staff member's own language setting if available, otherwise tenant's, then site default.
        $staff_language = $settings_manager->get_setting($staff_user_id, 'biz_user_language', ''); // Assuming staff can also have language settings
        if (empty($staff_language) && $tenant_user_id) {
            $staff_language = $settings_manager->get_setting($tenant_user_id, 'biz_user_language', '');
        }

        if (!empty($staff_language) && is_string($staff_language) && preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/', $staff_language)) {
            if ($original_locale !== $staff_language) {
                if (switch_to_locale($staff_language)) {
                    $locale_switched = true;
                    $theme_dir = defined('MOBOOKING_THEME_DIR') ? MOBOOKING_THEME_DIR : get_template_directory();
                    load_theme_textdomain('mobooking', $theme_dir . '/languages');
                }
            }
        }

        $tenant_business_name = get_bloginfo('name');
        if ($tenant_user_id) {
            $tenant_business_name_setting = $settings_manager->get_setting($tenant_user_id, 'biz_name', get_bloginfo('name'));
            if (!empty($tenant_business_name_setting)) {
                $tenant_business_name = $tenant_business_name_setting;
            }
        }

        $ref = isset($booking_details['booking_reference']) ? esc_html($booking_details['booking_reference']) : __('N/A', 'mobooking');
        $customer_name = isset($booking_details['customer_name']) ? esc_html($booking_details['customer_name']) : __('N/A', 'mobooking');
        $datetime = (isset($booking_details['booking_date']) && isset($booking_details['booking_time'])) ?
                    esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking_details['booking_date'] . ' ' . $booking_details['booking_time']))) :
                    __('N/A', 'mobooking');
        $dashboard_link = home_url('/dashboard/my-assigned-bookings/'); // Or a direct link to the booking if preferred.

        $subject_template = $settings_manager->get_setting($tenant_user_id, 'email_staff_assign_subj', 'New Booking Assignment - Ref: {{booking_reference}}');
        $body_template = $settings_manager->get_setting($tenant_user_id, 'email_staff_assign_body', "Hi {{staff_name}},

You have been assigned a new booking (Ref: {{booking_reference}}).

Customer: {{customer_name}}
Date & Time: {{booking_date_time}}

Please review this assignment in your dashboard: {{staff_dashboard_link}}");

        $replacements = [
            '{{staff_name}}' => esc_html($staff_user->display_name),
            '{{customer_name}}' => $customer_name,
            '{{booking_reference}}' => $ref,
            '{{booking_date_time}}' => $datetime,
            '{{staff_dashboard_link}}' => esc_url($dashboard_link),
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject_template);
        $body_content = nl2br(str_replace(array_keys($replacements), array_values($replacements), $body_template));

        $full_email_html = $this->get_styled_email_html($subject, $tenant_business_name, $body_content, $tenant_user_id);

        $headers = $this->get_email_headers($tenant_user_id); // From the perspective of the business
        $email_sent = wp_mail($staff_user->user_email, $subject, $full_email_html, $headers);

        if ($locale_switched) {
            restore_current_locale();
            $theme_dir = defined('MOBOOKING_THEME_DIR') ? MOBOOKING_THEME_DIR : get_template_directory();
            load_theme_textdomain('mobooking', $theme_dir . '/languages');
        }

        if (!$email_sent) {
            error_log("MoBooking Notifications: Failed to send assignment email to staff {$staff_user_id} for booking {$booking_id}.");
        }
        return $email_sent;
    }

    /**
     * Sends a notification to the admin/business owner when a booking status changes.
     * @param int $booking_id The ID of the booking.
     * @param string $new_status The new status.
     * @param string $old_status The old status.
     * @param array $booking_details Basic booking details.
     * @param int $tenant_user_id The ID of the business owner.
     * @param int $updated_by_user_id The ID of the user who made the change.
     * @return bool
     */
    public function send_admin_status_change_notification(int $booking_id, string $new_status, string $old_status, array $booking_details, int $tenant_user_id, int $updated_by_user_id) {
        $admin_user = get_userdata($tenant_user_id);
        if (!$admin_user || empty($admin_user->user_email)) {
            error_log("MoBooking Notifications: Invalid admin user ID or email for status change notification. Admin ID: {$tenant_user_id}");
            return false;
        }

        $settings_manager = new Settings();
        $locale_switched = false;
        $original_locale = get_locale();
        $admin_language = $settings_manager->get_setting($tenant_user_id, 'biz_user_language', '');

        if (!empty($admin_language) && is_string($admin_language) && preg_match('/^[a-z]{2,3}(_[A-Z]{2})?$/', $admin_language)) {
            if ($original_locale !== $admin_language) {
                if (switch_to_locale($admin_language)) {
                    $locale_switched = true;
                    $theme_dir = defined('MOBOOKING_THEME_DIR') ? MOBOOKING_THEME_DIR : get_template_directory();
                    load_theme_textdomain('mobooking', $theme_dir . '/languages');
                }
            }
        }

        $tenant_business_name = $settings_manager->get_setting($tenant_user_id, 'biz_name', get_bloginfo('name'));
         if (empty($tenant_business_name)) {
            $tenant_business_name = get_bloginfo('name');
        }

        $updater_user = get_userdata($updated_by_user_id);
        $updater_name = $updater_user ? $updater_user->display_name : __('Unknown User', 'mobooking');

        $ref = isset($booking_details['booking_reference']) ? esc_html($booking_details['booking_reference']) : __('N/A', 'mobooking');
        $dashboard_link = home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $booking_id);

        $subject_template = $settings_manager->get_setting($tenant_user_id, 'email_admin_status_change_subj', 'Booking Status Updated - Ref: {{booking_reference}}');
        $body_template = $settings_manager->get_setting($tenant_user_id, 'email_admin_status_change_body', "The status for booking (Ref: {{booking_reference}}) has been updated from {{old_status}} to {{new_status}} by {{updater_name}}.");

        $replacements = [
            '{{booking_reference}}' => $ref,
            '{{old_status}}' => esc_html(ucfirst($old_status)),
            '{{new_status}}' => esc_html(ucfirst($new_status)),
            '{{updater_name}}' => esc_html($updater_name),
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject_template);
        $body_content = nl2br(str_replace(array_keys($replacements), array_values($replacements), $body_template));

        $full_email_html = $this->get_styled_email_html($subject, $tenant_business_name, $body_content, $tenant_user_id);

        $headers = $this->get_email_headers($tenant_user_id);
        $email_sent = wp_mail($admin_user->user_email, $subject, $full_email_html, $headers);

        if ($locale_switched) {
            restore_current_locale();
            $theme_dir = defined('MOBOOKING_THEME_DIR') ? MOBOOKING_THEME_DIR : get_template_directory();
            load_theme_textdomain('mobooking', $theme_dir . '/languages');
        }

        if (!$email_sent) {
            error_log("MoBooking Notifications: Failed to send status change email to admin {$tenant_user_id} for booking {$booking_id}.");
        }
        return $email_sent;
    }

    /**
     * Sends a welcome email to a new business owner.
     * @param int $user_id The ID of the new user.
     * @param string $display_name The display name of the new user.
     * @return bool
     */
    public function send_welcome_email(int $user_id, string $display_name): bool {
        $user_info = get_userdata($user_id);
        if (!$user_info) {
            return false;
        }
        $user_email = $user_info->user_email;

        $settings_manager = new Settings();
        $subject_template = $settings_manager->get_setting($user_id, 'email_welcome_subj', 'Welcome to {{company_name}}!');
        $body_template = $settings_manager->get_setting($user_id, 'email_welcome_body', "Hi {{customer_name}},

Thanks for joining {{company_name}}! We're excited to have you.

You can access your dashboard here: {{dashboard_link}}");

        $replacements = [
            '{{customer_name}}' => $display_name,
            '{{company_name}}' => get_bloginfo('name'),
            '{{dashboard_link}}' => esc_url(home_url('/dashboard/')),
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject_template);
        $body_content = nl2br(str_replace(array_keys($replacements), array_values($replacements), $body_template));

        $full_email_html = $this->get_styled_email_html($subject, get_bloginfo('name'), $body_content, $user_id);

        $headers = $this->get_email_headers();
        $email_sent = wp_mail($user_email, $subject, $full_email_html, $headers);

        if (!$email_sent) {
            error_log('MoBooking: Failed to send welcome email to ' . $user_email);
        }

        return $email_sent;
    }

    /**
     * Sends an invitation email to a new worker.
     * @param string $worker_email The email of the worker to invite.
     * @param string $assigned_role The role assigned to the worker.
     * @param string $inviter_name The name of the person inviting the worker.
     * @param string $registration_link The link to the registration page.
     * @return bool
     */
    public function send_invitation_email(string $worker_email, string $assigned_role, string $inviter_name, string $registration_link): bool {
        $settings_manager = new Settings();
        $user_id = get_current_user_id();
        $subject_template = $settings_manager->get_setting($user_id, 'email_invitation_subj', 'You have been invited to join {{company_name}}');
        $body_template = $settings_manager->get_setting($user_id, 'email_invitation_body', "Hi {{worker_email}},

You've been invited to join {{company_name}} as a {{worker_role}} by {{inviter_name}}.

Click here to register: {{registration_link}}");

        $replacements = [
            '{{worker_email}}' => $worker_email,
            '{{worker_role}}' => ucfirst(str_replace('mobooking_worker_', '', $assigned_role)),
            '{{inviter_name}}' => $inviter_name,
            '{{registration_link}}' => esc_url($registration_link),
            '{{company_name}}' => get_bloginfo('name'),
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject_template);
        $body_content = nl2br(str_replace(array_keys($replacements), array_values($replacements), $body_template));

        $full_email_html = $this->get_styled_email_html($subject, get_bloginfo('name'), $body_content, $user_id);

        $headers = $this->get_email_headers();
        return wp_mail($worker_email, $subject, $full_email_html, $headers);
    }

    public function send_test_email(int $user_id) {
        $user_info = get_userdata($user_id);
        if (!$user_info) {
            return false;
        }
        $user_email = $user_info->user_email;

        $settings_manager = new Settings();
        $biz_settings = $settings_manager->get_business_settings($user_id);

        $subject = sprintf(__('Test Email from %s', 'mobooking'), $biz_settings['biz_name']);

        $body_content = '<h2>' . __('This is a Test Email', 'mobooking') . '</h2>';
        $body_content .= '<p>' . __('This is a test email to preview your email template settings.', 'mobooking') . '</p>';
        $body_content .= '<p>' . __('The logo, colors, and footer should reflect your current settings.', 'mobooking') . '</p>';

        $full_email_html = $this->get_styled_email_html($subject, $biz_settings['biz_name'], $body_content, $user_id);

        $headers = $this->get_email_headers($user_id);
        return wp_mail($user_email, $subject, $full_email_html, $headers);
    }
}
