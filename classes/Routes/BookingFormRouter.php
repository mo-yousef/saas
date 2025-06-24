<?php
namespace MoBooking\Classes\Routes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use MoBooking\Classes\Database; // For get_table_name, if used directly by get_user_id_by_slug

/**
 * Handles custom booking form routes, query variables, and template loading.
 */
class BookingFormRouter {

    /**
     * Constructor. Hooks into WordPress.
     */
    public function __construct() {
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'template_include'], 99); // High priority
    }

    /**
     * Registers rewrite rules for the booking form pages.
     * Equivalent to old mobooking_add_rewrite_rules().
     */
    public function register_rewrite_rules() {
        // Dashboard Rules (keeping them here if this router manages all frontend routes)
        // Or these could be in a separate DashboardRouter if preferred.
        // For now, assuming this class handles all non-admin custom routes.
        add_rewrite_rule(
            '^dashboard/?$',
            'index.php?mobooking_dashboard_page=overview',
            'top'
        );
        add_rewrite_rule(
            '^dashboard/([^/]+)/?$',
            'index.php?mobooking_dashboard_page=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^dashboard/([^/]+)/([^/]+)/?$',
            'index.php?mobooking_dashboard_page=$matches[1]&mobooking_dashboard_action=$matches[2]',
            'top'
        );

        // Public Booking Form by Business Slug Rule
        add_rewrite_rule(
            '^bookings/([^/]+)/?$', // Matches bookings/{slug}/
            'index.php?mobooking_slug=$matches[1]&mobooking_page_type=public',
            'top'
        );

        // Embed Booking Form by Business Slug Rule
        add_rewrite_rule(
            '^embed-booking/([^/]+)/?$', // Matches embed-booking/{slug}/
            'index.php?mobooking_slug=$matches[1]&mobooking_page_type=embed',
            'top'
        );
    }

    /**
     * Adds custom query variables to WordPress.
     * Equivalent to old mobooking_add_query_vars().
     * @param array $vars Existing query variables.
     * @return array Modified query variables.
     */
    public function add_query_vars($vars) {
        $vars[] = 'mobooking_dashboard_page'; // For dashboard routes
        $vars[] = 'mobooking_dashboard_action'; // For dashboard routes
        $vars[] = 'mobooking_slug';          // For booking form slug
        $vars[] = 'mobooking_page_type';     // For booking form type (public, embed)
        return $vars;
    }

    /**
     * Loads the correct template for booking form pages.
     * Equivalent to old mobooking_template_include_logic().
     * @param string $template Original template path.
     * @return string Modified template path if a booking form URL is matched.
     */
    public function template_include($template) {
        // Note: MOBOOKING_THEME_DIR needs to be accessible here.
        // It's defined in functions.php. If this class is loaded before that,
        // it might be an issue. Assuming it's available or using get_template_directory().
        $theme_dir = defined('MOBOOKING_THEME_DIR') ? MOBOOKING_THEME_DIR : trailingslashit(get_template_directory());

        error_log('[MoBooking Router Debug] ====== New Request Processing in BookingFormRouter::template_include ======');
        error_log('[MoBooking Router Debug] REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A'));

        $page_type = get_query_var('mobooking_page_type');
        $dashboard_page_slug = get_query_var('mobooking_dashboard_page');
        $business_slug = get_query_var('mobooking_slug');

        error_log('[MoBooking Router Debug] Query Vars: page_type=' . $page_type . '; dashboard_page_slug=' . $dashboard_page_slug . '; business_slug=' . $business_slug);

        // --- Handle Public/Embed Booking Form by Slug ---
        if (($page_type === 'public' || $page_type === 'embed') && !empty($business_slug)) {
            error_log('[MoBooking Router Debug] Matched ' . $page_type . ' page type with slug: ' . $business_slug);
            // Use the static method from this class
            $tenant_id = self::get_user_id_by_slug($business_slug);

            if ($tenant_id) {
                error_log('[MoBooking Router Debug] Found tenant_id: ' . $tenant_id . ' for slug: ' . $business_slug);
                // $GLOBALS['mobooking_public_form_tenant_id_from_slug'] = $tenant_id; // Avoid globals if possible
                set_query_var('mobooking_tenant_id_on_page', $tenant_id);

                $public_booking_template = $theme_dir . 'templates/booking-form-public.php';
                if (file_exists($public_booking_template)) {
                    error_log('[MoBooking Router Debug] Loading public booking form template: ' . $public_booking_template);
                    remove_filter('template_redirect', 'redirect_canonical');
                    status_header(200);
                    return $public_booking_template;
                } else {
                    error_log('[MoBooking Router Debug] CRITICAL ERROR: Public booking form template file not found: ' . $public_booking_template);
                }
            } else {
                error_log('[MoBooking Router Debug] No tenant_id found for slug: ' . $business_slug . '. WordPress will attempt to handle the URL with default template logic (404).');
                // Let WordPress handle it, which should lead to a 404 if no other rule matches.
            }
        }
        // --- Handle Dashboard ---
        // This logic also moved from functions.php.
        // Ensure mobooking_enqueue_dashboard_scripts is available or its logic is also moved/accessible.
        // For now, assuming mobooking_enqueue_dashboard_scripts is a global function.
        $is_dashboard_request = false;
        if (!empty($dashboard_page_slug)) {
            $is_dashboard_request = true;
            error_log('[MoBooking Router Debug] Detected dashboard from query_var "mobooking_dashboard_page": ' . $dashboard_page_slug);
        } else if (empty($page_type) && empty($business_slug)) { // Avoid conflict with booking slugs
            $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
            $segments = explode('/', $path);
            if (isset($segments[0]) && strtolower($segments[0]) === 'dashboard') {
                $is_dashboard_request = true;
                $dashboard_page_slug = isset($segments[1]) && !empty($segments[1]) ? $segments[1] : 'overview';
                set_query_var('mobooking_dashboard_page', $dashboard_page_slug);
                error_log('[MoBooking Router Debug] Detected dashboard from URI segments. Page slug set to: ' . $dashboard_page_slug);
            }
        }

        if ($is_dashboard_request) {
            error_log('[MoBooking Router Debug] Processing as dashboard request for page: ' . $dashboard_page_slug);
            if (!is_user_logged_in() || !current_user_can('read')) { // 'read' is a basic capability for logged-in users
                error_log('[MoBooking Router Debug] User not authenticated for dashboard access. Redirecting to login.');
                // Get current URL to redirect back after login
                $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                wp_redirect(wp_login_url($current_url));
                exit;
            }

            // $GLOBALS['mobooking_current_dashboard_view'] = $dashboard_page_slug; // Avoid globals
            set_query_var('mobooking_current_dashboard_view', $dashboard_page_slug); // Use query var for template access

            if (function_exists('mobooking_enqueue_dashboard_scripts')) {
                 mobooking_enqueue_dashboard_scripts($dashboard_page_slug);
            } else {
                error_log('[MoBooking Router Debug] CRITICAL: mobooking_enqueue_dashboard_scripts() function not found.');
            }

            $dashboard_shell_template = $theme_dir . 'dashboard/dashboard-shell.php';
            if (file_exists($dashboard_shell_template)) {
                error_log('[MoBooking Router Debug] Loading dashboard shell: ' . $dashboard_shell_template);
                remove_filter('template_redirect', 'redirect_canonical');
                status_header(200);
                return $dashboard_shell_template;
            } else {
                error_log('[MoBooking Router Debug] CRITICAL ERROR: Dashboard shell file not found: ' . $dashboard_shell_template);
            }
        }

        error_log('[MoBooking Router Debug] No specific MoBooking template matched by Router. Returning original template: ' . $template);
        return $template;
    }

    /**
     * Get user ID by the bf_business_slug setting.
     * Moved from global functions.php.
     *
     * @param string $slug The business slug to search for.
     * @return int|null User ID if found and valid, otherwise null.
     */
    public static function get_user_id_by_slug(string $slug): ?int {
        // This static method will contain the logic from mobooking_get_user_id_by_slug()
        global $wpdb; // Ensure $wpdb is accessible

        if (empty($slug)) {
            error_log('[MoBooking Router] Attempted slug lookup with empty slug.');
            return null;
        }

        // Ensure the Database class is available for get_table_name
        // Namespace is already imported with `use MoBooking\Classes\Database;`
        if (!class_exists(Database::class)) { // Use ::class for FQCN
            error_log('[MoBooking Router] CRITICAL: MoBooking\Classes\Database class not found for slug lookup.');
            return null;
        }

        $settings_table = Database::get_table_name('tenant_settings');
        if (empty($settings_table)) {
            error_log('[MoBooking Router] CRITICAL: Tenant settings table name is empty for slug lookup.');
            return null;
        }

        $query = $wpdb->prepare(
            // Note: $wpdb->prepare doesn't directly support table names as placeholders for security.
            // Table names from a trusted source (like our Database class) are generally considered safe to interpolate.
            "SELECT user_id FROM {$settings_table} WHERE setting_name = %s AND setting_value = %s",
            'bf_business_slug',
            $slug
        );
        $user_id = $wpdb->get_var($query);

        if ($user_id) {
            $user = get_userdata($user_id);
            // Assuming Auth class and its constants are available.
            // If Auth class is needed here, it should also be imported or fully namespaced.
            // For now, let's assume \MoBooking\Classes\Auth::ROLE_BUSINESS_OWNER is accessible.
            if ($user && (in_array(\MoBooking\Classes\Auth::ROLE_BUSINESS_OWNER, (array)$user->roles) || user_can($user, 'manage_options'))) {
                error_log('[MoBooking Router] Found user_id: ' . $user_id . ' for slug: ' . $slug);
                return (int) $user_id;
            } else {
                error_log('[MoBooking Router] User ID ' . $user_id . ' found for slug ' . $slug . ', but user is not a business owner or admin.');
            }
        } else {
            error_log('[MoBooking Router] No user_id found for slug: ' . $slug . ' in table ' . $settings_table);
        }
        return null;
    }
}
