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
    private function get_styled_email_html(string $subject, string $header_title, string $body_content): string {
        $template_path = get_template_directory() . '/templates/email/base-email-template.php';

        if (!file_exists($template_path)) {
            // Fallback to a simple layout if template is missing
            return "<h1>{$header_title}</h1>{$body_content}";
        }

        ob_start();
        include $template_path;
        $template = ob_get_clean();

        $replacements = [
            '{{SUBJECT}}'      => $subject,
            '{{HEADER_TITLE}}' => $header_title,
            '{{BODY_CONTENT}}' => $body_content,
            '{{SITE_NAME}}'    => get_bloginfo('name'),
            '{{SITE_URL}}'     => home_url('/'),
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

        $body_content  = '<h2>' . __('Booking Confirmed!', 'mobooking') . '</h2>';
        $body_content .= "<p>" . sprintf(__('Dear %s,', 'mobooking'), $customer_name) . "</p>";
        $body_content .= "<p>" . sprintf(__('Thank you for your booking with %s. Your booking (Ref: %s) is confirmed.', 'mobooking'), "<strong>{$tenant_business_name}</strong>", "<strong>{$ref}</strong>") . "</p>";
        $body_content .= '<div class="booking-details">';
        $body_content .= "<h3>" . __('Booking Summary:', 'mobooking') . "</h3>";
        $body_content .= "<ul>";
        $body_content .= "<li><strong>" . __('Services:', 'mobooking') . "</strong> " . $services . "</li>";
        $body_content .= "<li><strong>" . __('Date & Time:', 'mobooking') . "</strong> " . $datetime . "</li>";
        $body_content .= "<li><strong>" . __('Service Address:', 'mobooking') . "</strong><br>" . $address . "</li>";
        $body_content .= "<li><strong>" . __('Total Price:', 'mobooking') . "</strong> " . $price_display . "</li>";
        $body_content .= "</ul>";
        $body_content .= '</div>';
        $body_content .= "<p>" . sprintf(__('If you have any questions, please contact %s.', 'mobooking'), $tenant_business_name) . "</p>";

        $full_email_html = $this->get_styled_email_html($subject, $tenant_business_name, $body_content);

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

        // Subject and message using translated strings
        $subject = sprintf(__('New Booking Received - Ref: %s - %s', 'mobooking'), $ref, $customer_name);

        $body_content  = '<h2>' . __('New Booking Received!', 'mobooking') . '</h2>';
        $body_content .= "<p>" . sprintf(__('You have received a new booking (Ref: %s).', 'mobooking'), "<strong>{$ref}</strong>") . "</p>";
        $body_content .= '<div class="booking-details">';
        $body_content .= "<h3>" . __('Customer Details:', 'mobooking') . "</h3>";
        $body_content .= "<ul>";
        $body_content .= "<li><strong>" . __('Name:', 'mobooking') . "</strong> " . $customer_name . "</li>";
        $body_content .= "<li><strong>" . __('Email:', 'mobooking') . "</strong> " . $customer_email_val . "</li>";
        $body_content .= "<li><strong>" . __('Phone:', 'mobooking') . "</strong> " . $customer_phone . "</li>";
        $body_content .= "</ul>";
        $body_content .= "<h3>" . __('Booking Details:', 'mobooking') . "</h3>";
        $body_content .= "<ul>";
        $body_content .= "<li><strong>" . __('Services:', 'mobooking') . "</strong> " . $services . "</li>";
        $body_content .= "<li><strong>" . __('Date & Time:', 'mobooking') . "</strong> " . $datetime . "</li>";
        $body_content .= "<li><strong>" . __('Service Address:', 'mobooking') . "</strong><br>" . $address . "</li>";
        $body_content .= "<li><strong>" . __('Total Price:', 'mobooking') . "</strong> " . $price_display . "</li>";
        $body_content .= "<li><strong>" . __('Special Instructions:', 'mobooking') . "</strong><br>" . $instructions . "</li>";
        $body_content .= "</ul>";
        $body_content .= '</div>';
        $body_content .= '<p style="text-align:center; margin-top: 24px;"><a href="' . esc_url(home_url('/dashboard/bookings/')) . '" class="button">' . __('View in Dashboard', 'mobooking') . '</a></p>';

        $full_email_html = $this->get_styled_email_html($subject, $tenant_business_name, $body_content);

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

        $subject = sprintf(__('New Booking Assignment - Ref: %s - %s', 'mobooking'), $ref, $tenant_business_name);

        $body_content  = '<h2>' . __('New Booking Assignment', 'mobooking') . '</h2>';
        $body_content .= "<p>" . sprintf(__('Hi %s,', 'mobooking'), esc_html($staff_user->display_name)) . "</p>";
        $body_content .= "<p>" . sprintf(__('You have been assigned a new booking (Ref: %s) for %s.', 'mobooking'), "<strong>{$ref}</strong>", "<strong>{$tenant_business_name}</strong>") . "</p>";
        $body_content .= '<div class="booking-details">';
        $body_content .= "<h3>" . __('Booking Details:', 'mobooking') . "</h3>";
        $body_content .= "<ul>";
        $body_content .= "<li><strong>" . __('Customer:', 'mobooking') . "</strong> " . $customer_name . "</li>";
        $body_content .= "<li><strong>" . __('Date & Time:', 'mobooking') . "</strong> " . $datetime . "</li>";
        $body_content .= "</ul>";
        $body_content .= '</div>';
        $body_content .= '<p style="text-align:center; margin-top: 24px;"><a href="' . esc_url($dashboard_link) . '" class="button">' . __('View Your Assignments', 'mobooking') . '</a></p>';

        $full_email_html = $this->get_styled_email_html($subject, $tenant_business_name, $body_content);

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

        $subject = sprintf(__('Booking Status Updated - Ref: %s - %s', 'mobooking'), $ref, $tenant_business_name);

        $body_content  = '<h2>' . __('Booking Status Updated', 'mobooking') . '</h2>';
        $body_content .= "<p>" . sprintf(__('The status for booking reference %s has been updated.', 'mobooking'), "<strong>{$ref}</strong>") . "</p>";
        $body_content .= '<div class="booking-details">';
        $body_content .= "<ul>";
        $body_content .= "<li><strong>" . __('Old Status:', 'mobooking') . "</strong> " . esc_html(ucfirst($old_status)) . "</li>";
        $body_content .= "<li><strong>" . __('New Status:', 'mobooking') . "</strong> " . esc_html(ucfirst($new_status)) . "</li>";
        $body_content .= "<li><strong>" . __('Updated By:', 'mobooking') . "</strong> " . esc_html($updater_name) . " (ID: {$updated_by_user_id})</li>";
        $body_content .= "</ul>";
        $body_content .= '</div>';
        $body_content .= '<p style="text-align:center; margin-top: 24px;"><a href="' . esc_url($dashboard_link) . '" class="button">' . __('View Booking Details', 'mobooking') . '</a></p>';

        $full_email_html = $this->get_styled_email_html($subject, $tenant_business_name, $body_content);

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
}
