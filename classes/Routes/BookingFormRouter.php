<?php
namespace NORDBOOKING\Classes\Routes;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use NORDBOOKING\Classes\Database;

/**
 * Handles custom booking form routes, query variables, and template loading.
 * Enhanced version with better debugging and error handling.
 */
class BookingFormRouter {

    /**
     * Constructor. Hooks into WordPress.
     */
    public function __construct() {
        add_action('init', [$this, 'register_rewrite_rules'], 5); // Earlier priority
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('template_include', [$this, 'template_include'], 99);
        
        // Add debugging hooks if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_footer', [$this, 'debug_routing']);
        }
    }

    /**
     * Registers rewrite rules for the booking form pages.
     */
    public function register_rewrite_rules() {
        // Dashboard Rules
        add_rewrite_rule(
            '^dashboard/?$',
            'index.php?nordbooking_dashboard_page=overview',
            'top'
        );
        add_rewrite_rule(
            '^dashboard/([^/]+)/?$',
            'index.php?nordbooking_dashboard_page=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^dashboard/([^/]+)/([^/]+)/?$',
            'index.php?nordbooking_dashboard_page=$matches[1]&nordbooking_dashboard_action=$matches[2]',
            'top'
        );

        // Public Booking Form by Business Slug Rule
        add_rewrite_rule(
            '^booking/([^/]+)/?$',
            'index.php?nordbooking_slug=$matches[1]&nordbooking_page_type=public',
            'top'
        );

        // Embed Booking Form by Business Slug Rule
        add_rewrite_rule(
            '^embed-booking/([^/]+)/?$',
            'index.php?nordbooking_slug=$matches[1]&nordbooking_page_type=embed',
            'top'
        );
        
        error_log('[NORDBOOKING Router] Rewrite rules registered');
    }

    /**
     * Adds custom query variables to WordPress.
     */
    public function add_query_vars($vars) {
        $vars[] = 'nordbooking_dashboard_page';
        $vars[] = 'nordbooking_dashboard_action';
        $vars[] = 'nordbooking_slug';
        $vars[] = 'nordbooking_page_type';
        return $vars;
    }

    /**
     * Loads the correct template for booking form pages.
     */
    public function template_include($template) {
        global $wp;
        
        $theme_dir = defined('NORDBOOKING_THEME_DIR') ? NORDBOOKING_THEME_DIR : trailingslashit(get_template_directory());
        $request_path = isset($wp->request) ? trim($wp->request, '/') : '';
        $path_segments = $request_path ? explode('/', $request_path) : [];

        error_log('[NORDBOOKING Router] Processing request: /' . $request_path);
        error_log('[NORDBOOKING Router] Path segments: ' . print_r($path_segments, true));

        // --- Match /booking/{slug}/ ---
        if (isset($path_segments[0]) && $path_segments[0] === 'booking' && isset($path_segments[1])) {
            return $this->handle_public_booking_route($path_segments[1], $theme_dir, $template);
        }

        // --- Match /embed-booking/{slug}/ ---
        else if (isset($path_segments[0]) && $path_segments[0] === 'embed-booking' && isset($path_segments[1])) {
            return $this->handle_embed_booking_route($path_segments[1], $theme_dir, $template);
        }

        // --- Match /dashboard/... ---
        else if (isset($path_segments[0]) && $path_segments[0] === 'dashboard') {
            return $this->handle_dashboard_route($path_segments, $theme_dir, $template, $request_path);
        }

        // --- Default: Not a NORDBOOKING custom route ---
        return $template;
    }

    /**
     * Handle public booking form route
     */
    private function handle_public_booking_route($business_slug, $theme_dir, $template) {
        $business_slug = sanitize_title($business_slug);
        
        error_log('[NORDBOOKING Router] Handling public booking route for slug: ' . $business_slug);
        
        // Set query variables
        set_query_var('nordbooking_slug', $business_slug);
        set_query_var('nordbooking_page_type', 'public_booking');

        $tenant_id = $this->get_user_id_by_slug($business_slug);
        error_log('[NORDBOOKING Router] Tenant ID lookup result: ' . ($tenant_id ?: 'NULL'));
        
        if ($tenant_id) {
            set_query_var('nordbooking_tenant_id_on_page', $tenant_id);
            
            $public_booking_template = $theme_dir . 'templates/booking-form-public.php';
            error_log('[NORDBOOKING Router] Checking template: ' . $public_booking_template);
            
            if (file_exists($public_booking_template)) {
                error_log('[NORDBOOKING Router] SUCCESS: Loading public booking form template');
                
                // Prevent canonical redirects that might interfere
                remove_filter('template_redirect', 'redirect_canonical');
                status_header(200);
                
                return $public_booking_template;
            } else {
                error_log('[NORDBOOKING Router] ERROR: Public booking template not found: ' . $public_booking_template);
            }
        } else {
            error_log('[NORDBOOKING Router] ERROR: No tenant found for slug: ' . $business_slug);
        }
        
        // If we get here, something went wrong - return original template (likely will 404)
        return $template;
    }

    /**
     * Handle embed booking form route
     */
    private function handle_embed_booking_route($business_slug, $theme_dir, $template) {
        $business_slug = sanitize_title($business_slug);
        
        error_log('[NORDBOOKING Router] Handling embed booking route for slug: ' . $business_slug);
        
        set_query_var('nordbooking_slug', $business_slug);
        set_query_var('nordbooking_page_type', 'embed');

        $tenant_id = $this->get_user_id_by_slug($business_slug);
        
        if ($tenant_id) {
            set_query_var('nordbooking_tenant_id_on_page', $tenant_id);
            
            $public_booking_template = $theme_dir . 'templates/booking-form-public.php';
            
            if (file_exists($public_booking_template)) {
                error_log('[NORDBOOKING Router] Loading embed booking form template');
                remove_filter('template_redirect', 'redirect_canonical');
                status_header(200);
                return $public_booking_template;
            }
        }
        
        return $template;
    }

    /**
     * Handle dashboard route
     */
    private function handle_dashboard_route($path_segments, $theme_dir, $template, $request_path) {
        $dashboard_page_slug = isset($path_segments[1]) && !empty($path_segments[1]) ? sanitize_title($path_segments[1]) : 'overview';
        $dashboard_action = isset($path_segments[2]) && !empty($path_segments[2]) ? sanitize_title($path_segments[2]) : '';

        set_query_var('nordbooking_dashboard_page', $dashboard_page_slug);
        if (!empty($dashboard_action)) {
            set_query_var('nordbooking_dashboard_action', $dashboard_action);
        }

        $GLOBALS['nordbooking_current_dashboard_view'] = $dashboard_page_slug;

        error_log('[NORDBOOKING Router] Dashboard route - Page: ' . $dashboard_page_slug . ', Action: ' . $dashboard_action);

        if (!is_user_logged_in() || !current_user_can('read')) { // Basic 'read' capability check
            error_log('[NORDBOOKING Router] User not authenticated or lacks basic read capability for dashboard path: ' . $request_path);
            $current_url = home_url($request_path);
            wp_redirect(wp_login_url($current_url)); // Redirect to login
            exit;
        }

        // Define valid dashboard pages to prevent arbitrary file inclusion through slugs
        $valid_dashboard_pages = [
            'overview', 'bookings', 'calendar', 'services', 'service-edit',
            'discounts', 'areas', 'workers', 'booking-form', 'settings',
            'availability', 'customers', 'customer-details', 'my-assigned-bookings' // Added for staff
        ];

        if (!in_array($dashboard_page_slug, $valid_dashboard_pages)) {
            error_log('[NORDBOOKING Router] Invalid dashboard page requested: ' . $dashboard_page_slug . '. Redirecting to overview.');
            // Optionally redirect to dashboard overview or show a 404 specific to the dashboard context
            // For now, let dashboard-shell.php handle it if it defaults to overview or shows an error for missing page-X.php
            // Or, more explicitly:
            // wp_redirect(home_url('/dashboard/'));
            // exit;
            // For now, we'll let dashboard-shell try to load page-overview if page-{$dashboard_page_slug} doesn't exist.
            // Or, we can be stricter:
            status_header(404);
            // And then perhaps include a 404 template or let WP handle it.
            // For simplicity, if an invalid slug is passed, dashboard-shell.php will attempt to load page-overview.php if page-{$invalid_slug}.php isn't found.
            // This is acceptable for now. A future enhancement could be a more explicit 404 within the dashboard.
        }


        // Enqueue scripts specific to the dashboard page being loaded
        if (function_exists('nordbooking_enqueue_dashboard_scripts')) {
            nordbooking_enqueue_dashboard_scripts($dashboard_page_slug);
        }

        $dashboard_shell_template = $theme_dir . 'dashboard/dashboard-shell.php';
        if (file_exists($dashboard_shell_template)) {
            error_log('[NORDBOOKING Router] Loading dashboard shell');
            remove_filter('template_redirect', 'redirect_canonical');
            status_header(200);
            return $dashboard_shell_template;
        }
        
        error_log('[NORDBOOKING Router] ERROR: Dashboard shell template not found: ' . $dashboard_shell_template);
        return $template;
    }

    /**
     * Get user ID by business slug with enhanced error handling
     */
    public static function get_user_id_by_slug($slug) { // Changed to public static
        global $wpdb;

        if (empty($slug)) {
            error_log('[NORDBOOKING Router] get_user_id_by_slug called with empty slug');
            return null;
        }

        // Check if Database class is available
        if (!class_exists('NORDBOOKING\\Classes\\Database')) {
            error_log('[NORDBOOKING Router] ERROR: Database class not found');
            return null;
        }

        try {
            $settings_table = Database::get_table_name('tenant_settings');
            if (empty($settings_table)) {
                error_log('[NORDBOOKING Router] ERROR: Could not get settings table name');
                return null;
            }

            error_log('[NORDBOOKING Router] Looking up slug "' . $slug . '" in table: ' . $settings_table);

            $query = $wpdb->prepare(
                "SELECT user_id FROM {$settings_table} WHERE setting_name = %s AND setting_value = %s",
                'bf_business_slug',
                $slug
            );

            $user_id = $wpdb->get_var($query);
            error_log('[NORDBOOKING Router] Query result: ' . ($user_id ?: 'NULL'));

            if ($user_id) {
                $user = get_userdata($user_id);

                // A slug is only valid for routing if it belongs to an existing business owner.
                if ($user && \NORDBOOKING\Classes\Auth::is_user_business_owner($user->ID)) {
                    error_log('[NORDBOOKING Router] Slug "' . $slug . '" is valid for routing to user ID: ' . $user_id);
                    return (int) $user_id;
                }

                // If the user doesn't exist or isn't a business owner, the slug is not routable.
                // This also means that for slug uniqueness checks during registration, this function
                // will now incorrectly report that slugs taken by non-owners are available.
                // This needs to be handled by a more specific uniqueness check in the registration logic itself.
                error_log('[NORDBOOKING Router] Slug "' . $slug . '" is considered invalid for routing (user not found or not a business owner).');

            } else {
                error_log('[NORDBOOKING Router] No user found for slug: ' . $slug);
                
                // Debug: Show all available slugs
                $all_slugs = $wpdb->get_results($wpdb->prepare(
                    "SELECT user_id, setting_value FROM {$settings_table} WHERE setting_name = %s",
                    'bf_business_slug'
                ));
                error_log('[NORDBOOKING Router] Available slugs: ' . print_r($all_slugs, true));
            }
        } catch (Exception $e) {
            error_log('[NORDBOOKING Router] Exception in get_user_id_by_slug: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Debug routing information (only shown if WP_DEBUG is true)
     */
    public function debug_routing() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wp;
        $request_path = isset($wp->request) ? trim($wp->request, '/') : '';
        
        // Only show debug for booking URLs
        if (strpos($request_path, 'booking/') !== 0) {
            return;
        }

        echo '<!-- NORDBOOKING Router Debug -->';
        echo '<script>console.log("NORDBOOKING Router Debug", ' . json_encode([
            'request_path' => $request_path,
            'nordbooking_slug' => get_query_var('nordbooking_slug'),
            'nordbooking_page_type' => get_query_var('nordbooking_page_type'),
            'nordbooking_tenant_id_on_page' => get_query_var('nordbooking_tenant_id_on_page'),
        ]) . ');</script>';
    }
}