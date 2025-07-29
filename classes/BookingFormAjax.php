<?php
namespace MoBooking\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class BookingFormAjax {

    public function __construct() {
        add_action('wp_ajax_nopriv_mobooking_get_services', [$this, 'get_services']);
        add_action('wp_ajax_mobooking_get_services', [$this, 'get_services']);
    }

    public function get_services() {
        if (!check_ajax_referer('mobooking_booking_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'mobooking')], 403);
            return;
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        if (empty($tenant_id)) {
            wp_send_json_error(['message' => __('Tenant ID is required.', 'mobooking')], 400);
            return;
        }

        $services_manager = new Services();
        $services = $services_manager->get_services_by_tenant_id($tenant_id);

        if (is_wp_error($services)) {
            wp_send_json_error(['message' => $services->get_error_message()], 500);
        } else {
            wp_send_json_success($services);
        }
    }
}
