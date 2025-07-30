<?php
namespace MoBooking\Classes\Routes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DashboardRouter {

    public function __construct() {
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'template_include']);
    }

    public function register_rewrite_rules() {
        add_rewrite_rule(
            '^dashboard/([^/]+)/?$',
            'index.php?mobooking_dashboard_page=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^dashboard/?$',
            'index.php?mobooking_dashboard_page=overview',
            'top'
        );
    }

    public function add_query_vars($vars) {
        $vars[] = 'mobooking_dashboard_page';
        return $vars;
    }

    public function template_include($template) {
        $dashboard_page = get_query_var('mobooking_dashboard_page');

        if ($dashboard_page) {
            // If the user is not logged in, redirect to the login page.
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(site_url('/dashboard')));
                exit;
            }

            // If we have a dashboard page, load the dashboard shell.
            $dashboard_template = MOBOOKING_THEME_DIR . 'dashboard/dashboard-shell.php';
            if (file_exists($dashboard_template)) {
                return $dashboard_template;
            }
        }

        return $template;
    }
}
