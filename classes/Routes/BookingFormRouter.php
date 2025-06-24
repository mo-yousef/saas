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

        $theme_dir = defined('MOBOOKING_THEME_DIR') ? MOBOOKING_THEME_DIR : trailingslashit(get_template_directory());
        $request_path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        $path_segments = explode('/', $request_path);

        error_log('[MoBooking Router Debug] URI-First Check. Path: /' . $request_path);

        // --- Match /bookings/{slug}/ ---
        if (isset($path_segments[0]) && $path_segments[0] === 'bookings' && isset($path_segments[1])) {
            $business_slug = sanitize_title($path_segments[1]);
            set_query_var('mobooking_slug', $business_slug); // Ensure query var is set
            set_query_var('mobooking_page_type', 'public'); // Ensure query var is set
            error_log('[MoBooking Router Debug] Matched URI pattern: /bookings/' . $business_slug);

            $tenant_id = self::get_user_id_by_slug($business_slug);
            if ($tenant_id) {
                set_query_var('mobooking_tenant_id_on_page', $tenant_id);
                $public_booking_template = $theme_dir . 'templates/booking-form-public.php';
                if (file_exists($public_booking_template)) {
                    error_log('[MoBooking Router Debug] Loading public booking form template.');
                    remove_filter('template_redirect', 'redirect_canonical');
                    status_header(200);
                    return $public_booking_template;
                }
                error_log('[MoBooking Router Debug] CRITICAL: Public booking template not found: ' . $public_booking_template);
            } else {
                error_log('[MoBooking Router Debug] No tenant_id for slug: ' . $business_slug . '. Letting WP 404.');
                // WordPress will naturally 404 if no other rule matches and we don't return a template.
                // Explicitly setting 404 here can be done if needed:
                // global $wp_query; $wp_query->set_404(); status_header(404);
            }
            return $template; // Fallback to WP's default handling (likely 404) if tenant not found or template missing
        }

        // --- Match /embed-booking/{slug}/ ---
        else if (isset($path_segments[0]) && $path_segments[0] === 'embed-booking' && isset($path_segments[1])) {
            $business_slug = sanitize_title($path_segments[1]);
            set_query_var('mobooking_slug', $business_slug); // Ensure query var is set
            set_query_var('mobooking_page_type', 'embed');   // Ensure query var is set
            error_log('[MoBooking Router Debug] Matched URI pattern: /embed-booking/' . $business_slug);

            $tenant_id = self::get_user_id_by_slug($business_slug);
            if ($tenant_id) {
                set_query_var('mobooking_tenant_id_on_page', $tenant_id);
                $public_booking_template = $theme_dir . 'templates/booking-form-public.php'; // Same template, JS/PHP inside handles 'embed' type
                if (file_exists($public_booking_template)) {
                    error_log('[MoBooking Router Debug] Loading embed booking form template.');
                    remove_filter('template_redirect', 'redirect_canonical');
                    status_header(200);
                    return $public_booking_template;
                }
                error_log('[MoBooking Router Debug] CRITICAL: Embed booking template not found: ' . $public_booking_template);
            } else {
                error_log('[MoBooking Router Debug] No tenant_id for embed slug: ' . $business_slug . '. Letting WP 404.');
            }
            return $template; // Fallback to WP's default handling
        }

        // --- Match /dashboard/... ---
        else if (isset($path_segments[0]) && $path_segments[0] === 'dashboard') {
            $dashboard_page_slug = isset($path_segments[1]) && !empty($path_segments[1]) ? sanitize_title($path_segments[1]) : 'overview';
            $dashboard_action = isset($path_segments[2]) && !empty($path_segments[2]) ? sanitize_title($path_segments[2]) : '';

            set_query_var('mobooking_dashboard_page', $dashboard_page_slug);
            if(!empty($dashboard_action)) set_query_var('mobooking_dashboard_action', $dashboard_action);

            error_log('[MoBooking Router Debug] Matched URI pattern: /dashboard/. Page: ' . $dashboard_page_slug . ', Action: ' . $dashboard_action);

            if (!is_user_logged_in() || !current_user_can('read')) {
                error_log('[MoBooking Router Debug] User not authenticated for dashboard. Redirecting to login.');
                $current_url = home_url($request_path); // Reconstruct full URL
                wp_redirect(wp_login_url($current_url));
                exit;
            }

            set_query_var('mobooking_current_dashboard_view', $dashboard_page_slug);
            if (function_exists('mobooking_enqueue_dashboard_scripts')) {
                mobooking_enqueue_dashboard_scripts($dashboard_page_slug);
            } else {
                error_log('[MoBooking Router Debug] CRITICAL: mobooking_enqueue_dashboard_scripts() function not found.');
            }

            $dashboard_shell_template = $theme_dir . 'dashboard/dashboard-shell.php';
            if (file_exists($dashboard_shell_template)) {
                error_log('[MoBooking Router Debug] Loading dashboard shell.');
                remove_filter('template_redirect', 'redirect_canonical');
                status_header(200);
                return $dashboard_shell_template;
            }
            error_log('[MoBooking Router Debug] CRITICAL: Dashboard shell template not found: ' . $dashboard_shell_template);
            return $template; // Fallback if shell is missing
        }

        // --- Default: Not a MoBooking custom route ---
        error_log('[MoBooking Router Debug] URI /' . $request_path . ' did not match any custom MoBooking patterns. Returning original template: ' . $template);
        return $template; // This is crucial
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
