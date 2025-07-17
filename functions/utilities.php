<?php
// Ensure Business Owner Role exists on init
function mobooking_ensure_business_owner_role_exists() {
    if (class_exists('MoBooking\Classes\Auth')) {
        if ( !get_role( MoBooking\Classes\Auth::ROLE_BUSINESS_OWNER ) ) {
            MoBooking\Classes\Auth::add_business_owner_role();
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' .
                     esc_html__('MoBooking: The "Business Owner" user role was missing and has been successfully re-created. Please refresh if you were assigning roles.', 'mobooking') .
                     '</p></div>';
            });
        }
    }
}
add_action( 'init', 'mobooking_ensure_business_owner_role_exists' );

// Ensure Worker Roles exist on init
function mobooking_ensure_worker_roles_exist() {
    if (class_exists('MoBooking\Classes\Auth')) {
        $roles_to_check = array(
            MoBooking\Classes\Auth::ROLE_WORKER_STAFF
        );
        $missing_roles = false;
        foreach ($roles_to_check as $role_name) {
            if ( !get_role( $role_name ) ) {
                $missing_roles = true;
                break;
            }
        }

        if ($missing_roles) {
            MoBooking\Classes\Auth::add_worker_roles();
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' .
                     esc_html__('MoBooking: The "Worker Staff" user role was missing and has been successfully re-created. Please refresh if you were assigning roles.', 'mobooking') .
                     '</p></div>';
            });
        }
    }
}
add_action( 'init', 'mobooking_ensure_worker_roles_exist' );

// Ensure Custom Database Tables exist on admin_init
function mobooking_ensure_custom_tables_exist() {
    if (is_admin() && class_exists('MoBooking\Classes\Database')) {
        global $wpdb;
        $services_table_name = \MoBooking\Classes\Database::get_table_name('services');

        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $services_table_name)) != $services_table_name) {
            error_log('[MoBooking DB Debug] Key table ' . $services_table_name . ' not found during admin_init check. Forcing table creation.');
            \MoBooking\Classes\Database::create_tables();

            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible"><p>' .
                     esc_html__('MoBooking: Core database tables were missing and an attempt was made to create them. Please verify their integrity or contact support if issues persist.', 'mobooking') .
                     '</p></div>';
            });
        }
    }
}
add_action( 'admin_init', 'mobooking_ensure_custom_tables_exist' );

// Function to actually flush rewrite rules, hooked to shutdown if a flag is set.
function mobooking_conditionally_flush_rewrite_rules() {
    if (get_option('mobooking_flush_rewrite_rules_flag')) {
        delete_option('mobooking_flush_rewrite_rules_flag');
        // Ensure our rules are registered before flushing
        // mobooking_add_rewrite_rules(); // This function is hooked to init, so rules should be registered.
        flush_rewrite_rules();
        error_log('[MoBooking] Rewrite rules flushed via shutdown hook.');
    }
}
add_action('shutdown', 'mobooking_conditionally_flush_rewrite_rules');

// Locale switching functions
function mobooking_switch_user_locale() {
    static $locale_switched = false; // Track if locale was actually switched

    if ( ! is_user_logged_in() ) {
        return false;
    }

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return false;
    }

    // Ensure settings manager is available
    if ( ! isset( $GLOBALS['mobooking_settings_manager'] ) || ! is_object( $GLOBALS['mobooking_settings_manager'] ) ) {
        // error_log('MoBooking Debug: Settings manager not available for locale switch.');
        return false;
    }

    $settings_manager = $GLOBALS['mobooking_settings_manager'];
    $user_language = $settings_manager->get_setting( $user_id, 'biz_user_language', '' );

    if ( ! empty( $user_language ) && is_string( $user_language ) ) {
        // Basic validation for locale format xx_XX or xx
        if ( preg_match( '/^[a-z]{2,3}(_[A-Z]{2})?$/', $user_language ) ) {
            if ( get_locale() !== $user_language ) { // Only switch if different
                // error_log("MoBooking Debug: Switching locale from " . get_locale() . " to " . $user_language . " for user " . $user_id);
                if ( switch_to_locale( $user_language ) ) {
                    $locale_switched = true;
                    // Re-load the theme's text domain for the new locale
                    // Note: This assumes 'mobooking' is the text domain and 'languages' is the path.
                    // unload_textdomain( 'mobooking' ); // May not be necessary if switch_to_locale handles this context for future loads
                    load_theme_textdomain( 'mobooking', MOBOOKING_THEME_DIR . 'languages' );

                    // You might need to reload other text domains if your theme/plugins use them and expect user-specific language
                } else {
                    // error_log("MoBooking Debug: switch_to_locale failed for " . $user_language);
                }
            }
        } else {
            // error_log("MoBooking Debug: Invalid user language format: " . $user_language);
        }
    }
    return $locale_switched; // Return status for potential use by restore function
}
add_action( 'after_setup_theme', 'mobooking_switch_user_locale', 20 ); // Priority 20 to run after settings manager and main textdomain load

// Store whether locale was switched in a global to be accessible by shutdown action
// because static variables in hooked functions are not easily accessible across different hooks.
$GLOBALS['mobooking_locale_switched_for_request'] = false;

function mobooking_set_global_locale_switched_status() {
    // This function is called by the after_setup_theme hook to get the status
    // from mobooking_switch_user_locale and store it globally.
    // However, mobooking_switch_user_locale itself is hooked to after_setup_theme.
    // A simpler way: mobooking_switch_user_locale directly sets this global.
    // Let's modify mobooking_switch_user_locale to do that.
    // No, the static variable approach for mobooking_restore_user_locale is better.
    // The static var inside mobooking_switch_user_locale is not directly accessible by mobooking_restore_user_locale.
    // The issue is that mobooking_restore_user_locale needs to know the state of $locale_switched from mobooking_switch_user_locale.
    // A simple global flag is okay here.
}
// No, this intermediate function is not the best way.
// Let's make mobooking_switch_user_locale update a global directly.

// Redefining mobooking_switch_user_locale slightly to set a global flag
// This is generally discouraged, but for shutdown action, it's a common pattern if needed.
// However, restore_current_locale() is safe to call regardless.
// The static var was more about *if* we should call it.
// WordPress's own `restore_current_locale()` checks if a switch happened.
// So, we don't strictly need to track it ourselves for `restore_current_locale`.
// The static var `$locale_switched` inside `mobooking_switch_user_locale` is fine for its own logic (e.g. logging)
// but `mobooking_restore_user_locale` can just call `restore_current_locale()`.

// Let's simplify. `restore_current_locale()` is idempotent.

function mobooking_restore_user_locale() {
    // restore_current_locale() will only do something if switch_to_locale() was successfully called.
    restore_current_locale();
    // error_log("MoBooking Debug: Attempted to restore locale. Current locale after restore: " . get_locale());
}
add_action( 'shutdown', 'mobooking_restore_user_locale' );

// --- Business Slug for User Profiles (REMOVED as per refactor) ---
// The functions mobooking_add_business_slug_field_to_profile and mobooking_save_business_slug_field
// and their associated add_action calls have been removed.
// Business Slug is now managed via Booking Form Settings page.

// The global function mobooking_get_user_id_by_slug() has been moved to BookingFormRouter::get_user_id_by_slug()
// Any internal theme code (if any) that was calling the global function directly would need to be updated
// to call the static class method instead, e.g., \MoBooking\Classes\Routes\BookingFormRouter::get_user_id_by_slug($slug).
// For the template_include logic, it's now called as self::get_user_id_by_slug() within the BookingFormRouter class.

/**
 * Retrieves an SVG icon string for a given dashboard menu key.
 *
 * @param string $key The key for the icon (e.g., 'overview', 'bookings', 'services').
 * @return string The SVG icon HTML string, or an empty string if not found.
 */
function mobooking_get_dashboard_menu_icon(string $key): string {
    static $icons = null;
    if (is_null($icons)) {
        $icons = [
            'overview' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect></svg>',
            'bookings' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
            'booking_form' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.5 22H18a2 2 0 0 0 2-2V7l-5-5H6a2 2 0 0 0-2 2v9.5"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path><path d="M13.378 15.626a1 1 0 1 0-3.004-3.004l-5.01 5.012a2 2 0 0 0-.506.854l-.837 2.87a.5.5 0 0 0 .62.62l2.87-.837a2 2 0 0 0 .854-.506z"></path></svg>',
            'services' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><rect width="20" height="14" x="2" y="6" rx="2"></rect></svg>',
            'clients' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>', // Using 'workers' icon for 'clients'
            'availability' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"></path><path d="M16 2v4"></path><rect width="18" height="18" x="3" y="4" rx="2"></rect><path d="M3 10h18"></path><path d="M8 14h.01"></path><path d="M12 14h.01"></path><path d="M16 14h.01"></path><path d="M8 18h.01"></path><path d="M12 18h.01"></path><path d="M16 18h.01"></path></svg>', // Calendar icon
            'discounts' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle></svg>',
            'areas' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.106 5.553a2 2 0 0 0 1.788 0l3.659-1.83A1 1 0 0 1 21 4.619v12.764a1 1 0 0 1-.553.894l-4.553 2.277a2 2 0 0 1-1.788 0l-4.212-2.106a2 2 0 0 0-1.788 0l-3.659 1.83A1 1 0 0 1 3 19.381V6.618a1 1 0 0 1 .553-.894l4.553-2.277a2 2 0 0 1 1.788 0z"></path><path d="M15 5.764v15"></path><path d="M9 3.236v15"></path></svg>',
            'workers' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
            'settings' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
        ];
    }
    return $icons[$key] ?? '';
}
?>
