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
            return MOBOOKING_PLUGIN_DIR . 'templates/booking-form-public.php';
        }
        return $template;
    }
}
