<?php
namespace MoBooking\Classes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Notifications {

    public function __construct() {
        // Constructor can be used if we need to load settings or helpers
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
        $price = isset($booking_details['total_price']) ? number_format_i18n($booking_details['total_price'], 2) : __('N/A', 'mobooking');
        $customer_name = isset($booking_details['customer_name']) ? esc_html($booking_details['customer_name']) : __('Customer', 'mobooking');
        $address = isset($booking_details['service_address']) ? nl2br(esc_html($booking_details['service_address'])) : __('N/A', 'mobooking');
        // TODO: Add currency symbol from settings

        $subject = sprintf(__('Your Booking Confirmation with %s - Ref: %s', 'mobooking'), $tenant_business_name, $ref);

        $message  = "<p>" . sprintf(__('Dear %s,', 'mobooking'), $customer_name) . "</p>";
        $message .= "<p>" . sprintf(__('Thank you for your booking with %s. Your booking (Ref: %s) is confirmed.', 'mobooking'), $tenant_business_name, $ref) . "</p>";
        $message .= "<h3>" . __('Booking Summary:', 'mobooking') . "</h3>";
        $message .= "<ul>";
        $message .= "<li><strong>" . __('Services:', 'mobooking') . "</strong> " . $services . "</li>";
        $message .= "<li><strong>" . __('Date & Time:', 'mobooking') . "</strong> " . $datetime . "</li>";
        $message .= "<li><strong>" . __('Service Address:', 'mobooking') . "</strong><br>" . $address . "</li>";
        $message .= "<li><strong>" . __('Total Price:', 'mobooking') . "</strong> " . $price . "</li>";
        $message .= "</ul>";
        $message .= "<p>" . sprintf(__('If you have any questions, please contact %s.', 'mobooking'), $tenant_business_name) . "</p>";
        $message .= "<p>" . __('Thank you,', 'mobooking') . "<br>" . $tenant_business_name . "</p>";

        $headers = $this->get_email_headers($tenant_user_id);

        return wp_mail($customer_email, $subject, $message, $headers);
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

        $tenant_info = get_userdata($tenant_user_id);
        if (!$tenant_info) {
            // error_log('MoBooking Notifications: Invalid tenant_user_id for admin confirmation.');
            return false;
        }
        $admin_email = $tenant_info->user_email;

        $ref = isset($booking_details['booking_reference']) ? esc_html($booking_details['booking_reference']) : __('N/A', 'mobooking');
        $services = isset($booking_details['service_names']) ? esc_html($booking_details['service_names']) : __('N/A', 'mobooking');
        $datetime = isset($booking_details['booking_date_time']) ? esc_html($booking_details['booking_date_time']) : __('N/A', 'mobooking');
        $price = isset($booking_details['total_price']) ? number_format_i18n($booking_details['total_price'], 2) : __('N/A', 'mobooking');
        $customer_name = isset($booking_details['customer_name']) ? esc_html($booking_details['customer_name']) : __('N/A', 'mobooking');
        $customer_email_val = isset($booking_details['customer_email']) ? esc_html($booking_details['customer_email']) : __('N/A', 'mobooking');
        $customer_phone = isset($booking_details['customer_phone']) ? esc_html($booking_details['customer_phone']) : __('N/A', 'mobooking');
        $address = isset($booking_details['service_address']) ? nl2br(esc_html($booking_details['service_address'])) : __('N/A', 'mobooking');
        $instructions = isset($booking_details['special_instructions']) && !empty($booking_details['special_instructions']) ? nl2br(esc_html($booking_details['special_instructions'])) : __('None', 'mobooking');
        // TODO: Add currency symbol

        $subject = sprintf(__('New Booking Received - Ref: %s - %s', 'mobooking'), $ref, $customer_name);

        $message  = "<p>" . sprintf(__('You have received a new booking (Ref: %s).', 'mobooking'), $ref) . "</p>";
        $message .= "<h3>" . __('Customer Details:', 'mobooking') . "</h3>";
        $message .= "<ul>";
        $message .= "<li><strong>" . __('Name:', 'mobooking') . "</strong> " . $customer_name . "</li>";
        $message .= "<li><strong>" . __('Email:', 'mobooking') . "</strong> " . $customer_email_val . "</li>";
        $message .= "<li><strong>" . __('Phone:', 'mobooking') . "</strong> " . $customer_phone . "</li>";
        $message .= "</ul>";
        $message .= "<h3>" . __('Booking Details:', 'mobooking') . "</h3>";
        $message .= "<ul>";
        $message .= "<li><strong>" . __('Services:', 'mobooking') . "</strong> " . $services . "</li>";
        $message .= "<li><strong>" . __('Date & Time:', 'mobooking') . "</strong> " . $datetime . "</li>";
        $message .= "<li><strong>" . __('Service Address:', 'mobooking') . "</strong><br>" . $address . "</li>";
        $message .= "<li><strong>" . __('Total Price:', 'mobooking') . "</strong> " . $price . "</li>";
        $message .= "<li><strong>" . __('Special Instructions:', 'mobooking') . "</strong><br>" . $instructions . "</li>";
        $message .= "</ul>";
        $message .= "<p>" . __('Please review this booking in your dashboard.', 'mobooking') . "</p>";

        $headers = $this->get_email_headers($tenant_user_id);

        return wp_mail($admin_email, $subject, $message, $headers);
    }

    // Placeholder for other notifications
    // public function send_booking_update_email(...) {}
    // public function send_password_reset_email(...) {} // Usually handled by WP default
}
