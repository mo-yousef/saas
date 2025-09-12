<?php
// Ensure Business Owner Role exists on init
function nordbooking_ensure_business_owner_role_exists() {
    if (class_exists('NORDBOOKING\Classes\Auth')) {
        if ( !get_role( NORDBOOKING\Classes\Auth::ROLE_BUSINESS_OWNER ) ) {
            NORDBOOKING\Classes\Auth::add_business_owner_role();
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' .
                     esc_html__('NORDBOOKING: The "Business Owner" user role was missing and has been successfully re-created. Please refresh if you were assigning roles.', 'NORDBOOKING') .
                     '</p></div>';
            });
        }
    }
}
add_action( 'init', 'nordbooking_ensure_business_owner_role_exists' );

// Ensure Worker Roles exist on init
function nordbooking_ensure_worker_roles_exist() {
    if (class_exists('NORDBOOKING\Classes\Auth')) {
        $roles_to_check = array(
            NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF
        );
        $missing_roles = false;
        foreach ($roles_to_check as $role_name) {
            if ( !get_role( $role_name ) ) {
                $missing_roles = true;
                break;
            }
        }

        if ($missing_roles) {
            NORDBOOKING\Classes\Auth::add_worker_roles();
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' .
                     esc_html__('NORDBOOKING: The "Worker Staff" user role was missing and has been successfully re-created. Please refresh if you were assigning roles.', 'NORDBOOKING') .
                     '</p></div>';
            });
        }
    }
}
add_action( 'init', 'nordbooking_ensure_worker_roles_exist' );

// Ensure Custom Database Tables exist on admin_init
function nordbooking_ensure_custom_tables_exist() {
    if (is_admin() && class_exists('NORDBOOKING\Classes\Database')) {
        $current_db_version = get_option('nordbooking_db_version', '1.0');

        if (version_compare($current_db_version, NORDBOOKING_DB_VERSION, '<')) {
            error_log('[NORDBOOKING DB Debug] Database version mismatch. Stored: ' . $current_db_version . ', Required: ' . NORDBOOKING_DB_VERSION . '. Forcing table creation/update.');
            \NORDBOOKING\Classes\Database::create_tables();
            update_option('nordbooking_db_version', NORDBOOKING_DB_VERSION);

            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' .
                     esc_html__('NORDBOOKING: Database updated successfully.', 'NORDBOOKING') .
                     '</p></div>';
            });
        }
    }
}
add_action( 'admin_init', 'nordbooking_ensure_custom_tables_exist' );

// Function to actually flush rewrite rules, hooked to shutdown if a flag is set.
function nordbooking_conditionally_flush_rewrite_rules() {
    if (get_option('nordbooking_flush_rewrite_rules_flag')) {
        delete_option('nordbooking_flush_rewrite_rules_flag');
        // Ensure our rules are registered before flushing
        // nordbooking_add_rewrite_rules(); // This function is hooked to init, so rules should be registered.
        flush_rewrite_rules();
        error_log('[NORDBOOKING] Rewrite rules flushed via shutdown hook.');
    }
}
add_action('shutdown', 'nordbooking_conditionally_flush_rewrite_rules');

// Locale switching functions
function nordbooking_switch_user_locale() {
    static $locale_switched = false; // Track if locale was actually switched

    if ( ! is_user_logged_in() ) {
        return false;
    }

    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return false;
    }

    // Ensure settings manager is available
    if ( ! isset( $GLOBALS['nordbooking_settings_manager'] ) || ! is_object( $GLOBALS['nordbooking_settings_manager'] ) ) {
        // error_log('NORDBOOKING Debug: Settings manager not available for locale switch.');
        return false;
    }

    $settings_manager = $GLOBALS['nordbooking_settings_manager'];
    $user_language = $settings_manager->get_setting( $user_id, 'biz_user_language', '' );

    if ( ! empty( $user_language ) && is_string( $user_language ) ) {
        // Basic validation for locale format xx_XX or xx
        if ( preg_match( '/^[a-z]{2,3}(_[A-Z]{2})?$/', $user_language ) ) {
            if ( get_locale() !== $user_language ) { // Only switch if different
                // error_log("NORDBOOKING Debug: Switching locale from " . get_locale() . " to " . $user_language . " for user " . $user_id);
                if ( switch_to_locale( $user_language ) ) {
                    $locale_switched = true;
                    // Re-load the theme's text domain for the new locale
                    // Note: This assumes 'NORDBOOKING' is the text domain and 'languages' is the path.
                    // unload_textdomain( 'NORDBOOKING' ); // May not be necessary if switch_to_locale handles this context for future loads
                    load_theme_textdomain( 'NORDBOOKING', NORDBOOKING_THEME_DIR . 'languages' );

                    // You might need to reload other text domains if your theme/plugins use them and expect user-specific language
                } else {
                    // error_log("NORDBOOKING Debug: switch_to_locale failed for " . $user_language);
                }
            }
        } else {
            // error_log("NORDBOOKING Debug: Invalid user language format: " . $user_language);
        }
    }
    return $locale_switched; // Return status for potential use by restore function
}
add_action( 'after_setup_theme', 'nordbooking_switch_user_locale', 20 ); // Priority 20 to run after settings manager and main textdomain load

// Store whether locale was switched in a global to be accessible by shutdown action
// because static variables in hooked functions are not easily accessible across different hooks.
$GLOBALS['nordbooking_locale_switched_for_request'] = false;

function nordbooking_set_global_locale_switched_status() {
    // This function is called by the after_setup_theme hook to get the status
    // from nordbooking_switch_user_locale and store it globally.
    // However, nordbooking_switch_user_locale itself is hooked to after_setup_theme.
    // A simpler way: nordbooking_switch_user_locale directly sets this global.
    // Let's modify nordbooking_switch_user_locale to do that.
    // No, the static variable approach for nordbooking_restore_user_locale is better.
    // The static var inside nordbooking_switch_user_locale is not directly accessible by nordbooking_restore_user_locale.
    // The issue is that nordbooking_restore_user_locale needs to know the state of $locale_switched from nordbooking_switch_user_locale.
    // A simple global flag is okay here.
}
// No, this intermediate function is not the best way.
// Let's make nordbooking_switch_user_locale update a global directly.

// Redefining nordbooking_switch_user_locale slightly to set a global flag
// This is generally discouraged, but for shutdown action, it's a common pattern if needed.
// However, restore_current_locale() is safe to call regardless.
// The static var was more about *if* we should call it.
// WordPress's own `restore_current_locale()` checks if a switch happened.
// So, we don't strictly need to track it ourselves for `restore_current_locale`.
// The static var `$locale_switched` inside `nordbooking_switch_user_locale` is fine for its own logic (e.g. logging)
// but `nordbooking_restore_user_locale` can just call `restore_current_locale()`.

// Let's simplify. `restore_current_locale()` is idempotent.

function nordbooking_restore_user_locale() {
    // restore_current_locale() will only do something if switch_to_locale() was successfully called.
    restore_current_locale();
    // error_log("NORDBOOKING Debug: Attempted to restore locale. Current locale after restore: " . get_locale());
}
add_action( 'shutdown', 'nordbooking_restore_user_locale' );

// --- Business Slug for User Profiles (REMOVED as per refactor) ---
// The functions nordbooking_add_business_slug_field_to_profile and nordbooking_save_business_slug_field
// and their associated add_action calls have been removed.
// Business Slug is now managed via Booking Form Settings page.

// The global function nordbooking_get_user_id_by_slug() has been moved to BookingFormRouter::get_user_id_by_slug()
// Any internal theme code (if any) that was calling the global function directly would need to be updated
// to call the static class method instead, e.g., \NORDBOOKING\Classes\Routes\BookingFormRouter::get_user_id_by_slug($slug).
// For the template_include logic, it's now called as self::get_user_id_by_slug() within the BookingFormRouter class.

/**
 * Retrieves an SVG icon string for a given dashboard menu key.
 *
 * @param string $key The key for the icon (e.g., 'overview', 'bookings', 'services').
 * @return string The SVG icon HTML string, or an empty string if not found.
 */
function nordbooking_get_dashboard_menu_icon(string $key): string {
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
            'subscription' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
            'logout' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>',
        ];
    }
    return $icons[$key] ?? '';
}

// Simple icon SVG helper function
function get_simple_icon_svg($icon_name) {
    $icons = [
        'check-square' => '<polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>',
        'type' => '<polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line>',
        'hash' => '<line x1="4" y1="9" x2="20" y2="9"></line><line x1="4" y1="15" x2="20" y2="15"></line><line x1="10" y1="3" x2="8" y2="21"></line><line x1="16" y1="3" x2="14" y2="21"></line>',
        'chevron-down' => '<polyline points="6 9 12 15 18 9"></polyline>',
        'circle' => '<circle cx="12" cy="12" r="10"></circle>',
        'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line>',
        'plus-minus' => '<line x1="5" y1="12" x2="19" y2="12"></line><line x1="12" y1="5" x2="12" y2="19"></line>',
        'square' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>',
        'minus' => '<line x1="5" y1="12" x2="19" y2="12"></line>',
        'dollar-sign' => '<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>',
        'percent' => '<line x1="19" y1="5" x2="5" y2="19"></line><circle cx="6.5" cy="6.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle>',
        'x' => '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>'
    ];
    return isset($icons[$icon_name]) ? $icons[$icon_name] : '<circle cx="12" cy="12" r="10"></circle>';
}

// Feather Icons - define a helper function or include them directly
if (!function_exists('nordbooking_get_feather_icon')) { // Check if function exists to avoid re-declaration if included elsewhere
    function nordbooking_get_feather_icon($icon_name, $attrs = 'width="18" height="18"') {
        $svg = '';
        switch ($icon_name) {
            case 'calendar': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'; break;
            case 'clock': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>'; break;
            case 'check-circle': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'; break;
            case 'loader': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>'; break;
            case 'pause-circle': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="10" y1="15" x2="10" y2="9"></line><line x1="14" y1="15" x2="14" y2="9"></line></svg>'; break;
            case 'check-square': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>'; break;
            case 'x-circle': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'; break;
            case 'sliders': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>'; break;
            case 'x': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>'; break;
            case 'filter': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>'; break;
            case 'user': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>'; break;
            case 'dollar-sign': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>'; break;
            case 'hash': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-hash"><line x1="4" y1="9" x2="20" y2="9"></line><line x1="4" y1="15" x2="20" y2="15"></line><line x1="10" y1="3" x2="8" y2="21"></line><line x1="16" y1="3" x2="14" y2="21"></line></svg>'; break;
            case 'arrow-left': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>'; break;

            case 'mail': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-mail"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>'; break;

            case 'phone': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-phone"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>'; break;

            case 'map-pin': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-map-pin"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>'; break;

            default: $svg = '<!-- icon not found: '.esc_attr($icon_name).' -->'; break;
        }
        return $svg;
    }
}

if (!function_exists('nordbooking_get_status_badge_icon_svg')) { // Check if function exists
    function nordbooking_get_status_badge_icon_svg($status) {
        $attrs = 'class="feather"'; // CSS will handle size and margin
        $icon_name = '';
        switch ($status) {
            case 'pending': $icon_name = 'clock'; break;
            case 'confirmed': $icon_name = 'check-circle'; break;
            case 'processing': $icon_name = 'loader'; break;
            case 'on-hold': $icon_name = 'pause-circle'; break;
            case 'completed': $icon_name = 'check-square'; break;
            case 'cancelled': $icon_name = 'x-circle'; break;
            default: return '';
        }
        return nordbooking_get_feather_icon($icon_name, $attrs);
    }
}

function nordbooking_get_booking_form_tab_icon(string $key): string {
    $icon_svg = '';
    $icon_path = NORDBOOKING_THEME_DIR . 'assets/svg-icons/' . $key . '.svg';
    $presets_path = NORDBOOKING_THEME_DIR . 'assets/svg-icons/presets/' . $key . '.svg';

    if (file_exists($icon_path)) {
        $icon_svg = file_get_contents($icon_path);
    } elseif (file_exists($presets_path)) {
        $icon_svg = file_get_contents($presets_path);
    }

    // Fallback for keys that might not have a file but we want to provide an icon for
    if (empty($icon_svg)) {
        switch ($key) {
            case 'general':
                // Using a generic settings/cog icon
                $icon_svg = '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61 l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41 h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.25C8.66,5.49,8.13,5.81,7.63,6.19L5.24,5.23C5.02,5.16,4.77,5.23,4.65,5.45l-1.92,3.32 c-0.12,0.2-0.07,0.47,0.12,0.61L4.9,11.06C4.85,11.36,4.82,11.68,4.82,12s0.02,0.64,0.07,0.94l-2.03,1.58 c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.44 c0.04,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.44c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96 c0.22,0.08,0.47,0.01,0.59-0.22l1.92-3.32c0.12-0.2,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6 s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z"/></svg>';
                break;
            case 'design':
                 $icon_svg = '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12,17.27L18.18,21l-1.64-7.03L22,9.24l-7.19-0.61L12,2L9.19,8.63L2,9.24l5.46,4.73L5.82,21L12,17.27z"/></svg>';
                break;
        }
    }

    return $icon_svg;
}
?>
