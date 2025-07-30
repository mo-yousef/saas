<?php
namespace MoBooking\Classes\Routes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MoBooking\Classes\Database;

class BookingFormRouter {

    public function __construct() {
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'template_include']);
    }

    public function register_rewrite_rules() {
        add_rewrite_rule(
            '^booking/([^/]+)/?$',
            'index.php?mobooking_slug=$matches[1]',
            'top'
        );
    }

    public function add_query_vars($vars) {
        $vars[] = 'mobooking_slug';
        return $vars;
    }

    public function template_include($template) {
        if (get_query_var('mobooking_slug')) {
            // Point to the new modern booking form template
            $modern_template = MOBOOKING_THEME_DIR . 'templates/booking-form-modern.php';
            if (file_exists($modern_template)) {
                return $modern_template;
            }
            // Fallback to the old one if the new one doesn't exist for some reason
            return MOBOOKING_THEME_DIR . 'templates/booking-form-public.php';
        }
        return $template;
    }

    public static function get_user_id_by_slug($slug) {
        global $wpdb;
        $settings_table = Database::get_table_name('tenant_settings');
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$settings_table} WHERE setting_name = %s AND setting_value = %s",
            'bf_business_slug',
            $slug
        ));
        return $user_id ? (int) $user_id : null;
    }
}
