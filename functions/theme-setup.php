<?php
// Basic theme setup
if ( ! function_exists( 'mobooking_setup' ) ) :
    function mobooking_setup() {
        // Make theme available for translation.
        load_theme_textdomain( 'mobooking', MOBOOKING_THEME_DIR . 'languages' );

        // Add default posts and comments RSS feed links to head.
        add_theme_support( 'automatic-feed-links' );

        // Let WordPress manage the document title.
        add_theme_support( 'title-tag' );

        // Enable support for Post Thumbnails on posts and pages.
        add_theme_support( 'post-thumbnails' );

        // This theme uses wp_nav_menu() in one location.
        register_nav_menus(
            array(
                'menu-1' => esc_html__( 'Primary', 'mobooking' ),
            )
        );

        // Switch default core markup for search form, comment form, and comments to output valid HTML5.
        add_theme_support(
            'html5',
            array(
                'search-form',
                'comment-form',
                'comment-list',
                'gallery',
                'caption',
                'style',
                'script',
            )
        );

        // Set up the WordPress core custom background feature.
        add_theme_support(
            'custom-background',
            apply_filters(
                'mobooking_custom_background_args',
                array(
                    'default-color' => 'ffffff',
                    'default-image' => '',
                )
            )
        );

        // Add theme support for selective refresh for widgets.
        add_theme_support( 'customize-selective-refresh-widgets' );
    }
endif;
add_action( 'after_setup_theme', 'mobooking_setup' );

// Enqueue scripts and styles.
// REPLACE the existing mobooking_scripts() function in your functions.php with this fixed version:

function mobooking_scripts() {
    // Initialize variables to prevent undefined warnings if used before assignment in conditional blocks
    $public_form_currency = [ // Default currency settings
        'code' => 'USD',
        'symbol' => '$'
    ];

    // Enqueue Google Fonts
    wp_enqueue_style( 'mobooking-inter-font', 'https://fonts.googleapis.com/css2?family=Outfit:wght@100;200;300;400;500;600;700;800;900&display=swap', array(), null );

    // Enqueue CSS Reset first
    wp_enqueue_style( 'mobooking-reset', MOBOOKING_THEME_URI . 'assets/css/reset.css', array(), MOBOOKING_VERSION );

    // Enqueue main stylesheet, making it dependent on the reset
    wp_enqueue_style( 'mobooking-style', get_stylesheet_uri(), array('mobooking-inter-font', 'mobooking-reset'), MOBOOKING_VERSION );
    wp_enqueue_style( 'mobooking-toggle-switch', MOBOOKING_THEME_URI . 'assets/css/toggle-switch.css', array('mobooking-style'), MOBOOKING_VERSION );


    if ( is_page_template( 'page-login.php' ) || is_page_template('page-register.php') || is_page_template('page-forgot-password.php') ) {
        // Enqueue the new auth pages specific CSS
        wp_enqueue_style( 'mobooking-auth-pages', MOBOOKING_THEME_URI . 'assets/css/auth-pages.css', array('mobooking-style'), MOBOOKING_VERSION );

        wp_enqueue_script( 'mobooking-auth', MOBOOKING_THEME_URI . 'assets/js/auth.js', array( 'jquery' ), MOBOOKING_VERSION, true );
        wp_localize_script(
            'mobooking-auth',
            'mobooking_auth_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'login_nonce' => wp_create_nonce( MoBooking\Classes\Auth::LOGIN_NONCE_ACTION ),
                'register_nonce' => wp_create_nonce( MoBooking\Classes\Auth::REGISTER_NONCE_ACTION ),
                'check_email_nonce' => wp_create_nonce( 'mobooking_check_email_nonce_action' ),
                'check_slug_nonce' => wp_create_nonce( 'mobooking_check_slug_nonce_action' ),
                'forgot_password_nonce' => wp_create_nonce( 'mobooking_forgot_password_nonce_action' ), // Nonce for forgot password
            )
        );
    }

    // For Public Booking Form page (standard page template OR slug-based route)
// For Public Booking Form page (standard page template OR slug-based route)
$page_type_for_scripts = get_query_var('mobooking_page_type');
if ( is_page_template('templates/booking-form-public.php') || $page_type_for_scripts === 'public_booking' || $page_type_for_scripts === 'embed_booking' ) {
    // Enqueue the new modern booking form CSS
    wp_enqueue_style( 'mobooking-booking-form-redesigned', MOBOOKING_THEME_URI . 'assets/css/booking-form-redesigned.css', array('mobooking-style'), MOBOOKING_VERSION );
    wp_enqueue_style( 'mobooking-booking-form-validation', MOBOOKING_THEME_URI . 'assets/css/booking-form-validation.css', array('mobooking-booking-form-redesigned'), MOBOOKING_VERSION );

    wp_enqueue_style( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.9' );
    wp_enqueue_script( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), '4.6.9', true );
    // wp_enqueue_script('mobooking-booking-form', MOBOOKING_THEME_URI . 'assets/js/booking-form.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true); // Commented out old script
    wp_enqueue_script('mobooking-public-booking-form', MOBOOKING_THEME_URI . 'assets/js/booking-form-public.js', array('jquery', 'flatpickr'), MOBOOKING_VERSION, true); // Enqueue new script

    $effective_tenant_id_for_public_form = 0;

    // Method 1: Prioritize tenant_id set by the slug-based routing via query_var
    if ($page_type_for_scripts === 'public_booking' || $page_type_for_scripts === 'embed_booking') {
        $effective_tenant_id_for_public_form = get_query_var('mobooking_tenant_id_on_page', 0);
        error_log('[MoBooking Scripts] Booking form (' . esc_html($page_type_for_scripts) . ' route). Tenant ID from query_var mobooking_tenant_id_on_page: ' . $effective_tenant_id_for_public_form);
    }

    // Method 2: Fallback to ?tid if not a slug route or if tenant_id_on_page wasn't set by slug logic
    if (empty($effective_tenant_id_for_public_form) && !empty($_GET['tid'])) {
        $effective_tenant_id_for_public_form = intval($_GET['tid']);
        error_log('[MoBooking Scripts] Public booking form (tid route). Tenant ID from $_GET[tid]: ' . $effective_tenant_id_for_public_form);
    }

    // Method 3: NEW - If still no tenant ID and user is logged in, use current user
    if (empty($effective_tenant_id_for_public_form) && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (in_array('mobooking_business_owner', $current_user->roles)) {
            $effective_tenant_id_for_public_form = $current_user->ID;
            error_log('[MoBooking Scripts] Using current logged-in business owner as tenant ID: ' . $effective_tenant_id_for_public_form);
        }
    }

    // Method 4: NEW - If still no tenant ID, check if there's a default business owner in the system
    if (empty($effective_tenant_id_for_public_form)) {
        $business_owners = get_users([
            'role' => 'mobooking_business_owner',
            'number' => 1,
            'fields' => 'ID'
        ]);

        if (!empty($business_owners)) {
            $effective_tenant_id_for_public_form = $business_owners[0];
            error_log('[MoBooking Scripts] Using first available business owner as tenant ID: ' . $effective_tenant_id_for_public_form);
        }
    }

    // Load tenant settings for this specific tenant
    $tenant_settings = [];
    if ($effective_tenant_id_for_public_form) {
        $settings_manager = new \MoBooking\Classes\Settings();
        $tenant_settings = $settings_manager->get_booking_form_settings($effective_tenant_id_for_public_form);

        // Get currency from business settings
        $biz_settings = $settings_manager->get_business_settings($effective_tenant_id_for_public_form);
        // Update the $public_form_currency array
        if (!empty($biz_settings['biz_currency_code'])) {
            $public_form_currency['code'] = $biz_settings['biz_currency_code'];
        }
        if (!empty($biz_settings['biz_currency_symbol'])) {
            $public_form_currency['symbol'] = $biz_settings['biz_currency_symbol'];
        }
    }

    // Localization strings for booking form
    $i18n_strings = [
        // Step 1
        'zip_required' => __('Please enter your ZIP code.', 'mobooking'),
        'country_code_required' => __('Please enter your Country Code.', 'mobooking'),
        'tenant_id_missing' => __('Business identifier is missing. Cannot check availability.', 'mobooking'),
        'tenant_id_missing_refresh' => __('Business ID is missing. Please refresh and try again or contact support.', 'mobooking'),
        'checking' => __('Checking...', 'mobooking'),
        'error_generic' => __('An unexpected error occurred. Please try again.', 'mobooking'),
        // Step 2
        'loading_services' => __('Loading services...', 'mobooking'),
        'no_services_available' => __('No services are currently available for this area. Please try another location or check back later.', 'mobooking'),
        'error_loading_services' => __('Could not load services. Please try again.', 'mobooking'),
        'select_one_service' => __('Please select at least one service.', 'mobooking'),
        // Step 3
        'loading_options' => __('Loading service options...', 'mobooking'),
        'no_options_available' => __('No additional options are available for the selected services.', 'mobooking'),
        'error_loading_options' => __('Could not load service options. Please try again.', 'mobooking'),
        // Step 4
        'name_required' => __('Please enter your name.', 'mobooking'),
        'email_required' => __('Please enter your email address.', 'mobooking'),
        'phone_required' => __('Please enter your phone number.', 'mobooking'),
        'address_required' => __('Please enter your service address.', 'mobooking'),
        'date_required' => __('Please select a preferred date.', 'mobooking'),
        'time_required' => __('Please select a preferred time.', 'mobooking'),
        // Step 5
        'discount_code_required' => __('Please enter a discount code.', 'mobooking'),
        'enter_discount_code' => __('Please enter a discount code.', 'mobooking'),
        'invalid_discount_code' => __('Invalid discount code.', 'mobooking'),
        'discount_applied' => __('Discount applied successfully!', 'mobooking'),
        'error_applying_discount' => __('Error applying discount. Please try again.', 'mobooking'),
        'discount_error' => __('Error applying discount. Please try again.', 'mobooking'),
        // Step 6
        'submitting' => __('Submitting your booking...', 'mobooking'),
        'booking_submitted' => __('Your booking has been submitted successfully!', 'mobooking'),
        'booking_error' => __('There was an error submitting your booking. Please try again.', 'mobooking'),
        // General
        'error_ajax' => __('A network error occurred. Please check your connection and try again.', 'mobooking'),
        'continue' => __('Continue', 'mobooking'),
        'back' => __('Back', 'mobooking'),
        'submit_booking' => __('Submit Booking', 'mobooking'),
    ];


    // Add custom CSS from settings if present and form is enabled
    if (!empty($tenant_settings['bf_custom_css']) && ($tenant_settings['bf_form_enabled'] ?? '1') === '1') {
        wp_add_inline_style('mobooking-booking-form-modern', $tenant_settings['bf_custom_css']);
    }

    $theme_color = $tenant_settings['bf_theme_color'] ?? '#1abc9c';
    $dynamic_css = "
        .mobooking-header h1 {
            color: {$theme_color};
        }
        .mobooking-step-indicator.active {
            background: {$theme_color};
            color: white;
        }
        .mobooking-progress-fill {
            background: linear-gradient(90deg, {$theme_color}, #10b981);
        }
        .mobooking-input:focus {
            border-color: {$theme_color};
        }
        .mobooking-service-card:hover {
            border-color: {$theme_color};
        }
        .mobooking-service-card.selected {
            border-color: {$theme_color};
            background: rgba(26, 188, 156, 0.05);
        }
        .mobooking-service-price {
            color: {$theme_color};
        }
        .mobooking-radio-option:hover {
            border-color: {$theme_color};
        }
        .mobooking-time-slot:hover {
            border-color: {$theme_color};
        }
        .mobooking-time-slot.selected {
            border-color: {$theme_color};
            background: {$theme_color};
            color: white;
        }
        .mobooking-btn-primary {
            background: {$theme_color};
            color: white;
        }
        .mobooking-spinner {
            border-top-color: {$theme_color};
        }
    ";
    wp_add_inline_style('mobooking-booking-form-modern', $dynamic_css);

    wp_localize_script('mobooking-public-booking-form', 'MOBOOKING_CONFIG', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mobooking_booking_form_nonce'),
        'tenant_id' => $effective_tenant_id_for_public_form,
        'currency' => $public_form_currency, // Pass the currency object
        'site_url' => site_url(),
        'i18n' => $i18n_strings,
        'settings' => [
            // Ensure specific string settings are JS-escaped
            'bf_header_text' => isset($tenant_settings['bf_header_text']) ? esc_js($tenant_settings['bf_header_text']) : '',
            'bf_show_pricing' => $tenant_settings['bf_show_pricing'] ?? '1',
            'bf_allow_discount_codes' => $tenant_settings['bf_allow_discount_codes'] ?? '1',
            'bf_theme_color' => $tenant_settings['bf_theme_color'] ?? '#1abc9c',
            // For bf_custom_css, esc_js might be too aggressive if it contains valid CSS quotes.
            // However, if it's breaking JS, it needs care. wp_json_encode (used by wp_localize_script) should handle it.
            // If bf_custom_css is truly the issue, it implies very unusual characters.
            'bf_custom_css' => $tenant_settings['bf_custom_css'] ?? '',
            'bf_form_enabled' => $tenant_settings['bf_form_enabled'] ?? '1',
            'bf_maintenance_message' => isset($tenant_settings['bf_maintenance_message']) ? esc_js($tenant_settings['bf_maintenance_message']) : '',
            'bf_enable_location_check' => $tenant_settings['bf_enable_location_check'] ?? '1',
            'bf_service_card_display' => $tenant_settings['bf_service_card_display'] ?? 'image',
            // Add any other settings, ensuring strings that might contain problematic characters are escaped
            // For example, if there were other free-text fields:
            // 'another_free_text_setting' => isset($tenant_settings['another_free_text_setting']) ? esc_js($tenant_settings['another_free_text_setting']) : '',
        ],
        // Add debug info
        'debug_info' => [
            'page_type' => $page_type_for_scripts,
            'query_var_tenant_id' => get_query_var('mobooking_tenant_id_on_page', 0),
            'get_tid' => $_GET['tid'] ?? null,
            'user_logged_in' => is_user_logged_in(),
            'current_user_id' => get_current_user_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        ],
        // Pass PHP debug data if available
        'is_debug_mode' => $GLOBALS['mobooking_is_debug_mode_active_flag'] ?? false,
        'initial_debug_info' => $GLOBALS['mobooking_initial_php_debug_data'] ?? []
    ]);
}


    // FIXED: Get current dashboard page slug if we're on a dashboard page
    // This prevents the undefined variable error
    $current_page_slug = '';
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/dashboard/') !== false) {
        // Try to get from query vars first (set by router)
        $current_page_slug = get_query_var('mobooking_dashboard_page');

        // Fallback to global variable
        if (empty($current_page_slug)) {
            $current_page_slug = isset($GLOBALS['mobooking_current_dashboard_view']) ? $GLOBALS['mobooking_current_dashboard_view'] : '';
        }

        // Parse from URL as final fallback
        if (empty($current_page_slug)) {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
            $path_segments = explode('/', $path);

            if (isset($path_segments[0]) && $path_segments[0] === 'dashboard') {
                $current_page_slug = isset($path_segments[1]) && !empty($path_segments[1]) ? sanitize_title($path_segments[1]) : 'overview';
            }
        }

        // Final fallback to overview
        if (empty($current_page_slug)) {
            $current_page_slug = 'overview';
        }
    }

    // Dashboard-specific scripts (only load if we're actually on a dashboard page)
    if (!empty($current_page_slug)) {
        // Enqueue the main dashboard stylesheet
        wp_enqueue_style('mobooking-dashboard-main', MOBOOKING_THEME_URI . 'assets/css/dashboard-main.css', array('mobooking-style'), MOBOOKING_VERSION);

        // Enqueue the single booking page stylesheet if we are on the single booking page
        if ($current_page_slug === 'bookings' && isset($_GET['action']) && $_GET['action'] === 'view_booking') {
            wp_enqueue_style('mobooking-dashboard-booking-single', MOBOOKING_THEME_URI . 'assets/css/dashboard-booking-single.css', array('mobooking-dashboard-main'), MOBOOKING_VERSION);
        }

        // Enqueue scripts
        wp_enqueue_script('mobooking-dialog', MOBOOKING_THEME_URI . 'assets/js/dialog.js', array(), MOBOOKING_VERSION, true);
        wp_enqueue_script('mobooking-toast', MOBOOKING_THEME_URI . 'assets/js/toast.js', array('jquery'), MOBOOKING_VERSION, true);
        wp_enqueue_script('mobooking-dashboard-header', MOBOOKING_THEME_URI . 'assets/js/dashboard-header.js', array('jquery'), MOBOOKING_VERSION, true);
        wp_localize_script('mobooking-dashboard-header', 'mobooking_dashboard_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mobooking_dashboard_nonce')
        ]);

        if ( $current_page_slug === 'settings' || $current_page_slug === 'booking-form' ) {
            // Enqueue styles for color picker
            wp_enqueue_style( 'wp-color-picker' );
            // Enqueue the settings page specific CSS
            wp_enqueue_style( 'mobooking-dashboard-settings', MOBOOKING_THEME_URI . 'assets/css/dashboard-settings.css', array('mobooking-dashboard-main'), MOBOOKING_VERSION );

            wp_enqueue_script( 'mobooking-dashboard-booking-form-settings', MOBOOKING_THEME_URI . 'assets/js/dashboard-booking-form-settings.js', array('jquery', 'wp-color-picker'), MOBOOKING_VERSION, true );

            // Enqueue SortableJS for the email builder
            wp_enqueue_script( 'sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js', array(), '1.15.0', true );

            // Enqueue the business settings script (handles tabs, saving, logo upload)
            wp_enqueue_script( 'mobooking-dashboard-business-settings', MOBOOKING_THEME_URI . 'assets/js/dashboard-business-settings.js', array('jquery', 'wp-color-picker'), MOBOOKING_VERSION, true );

            // Enqueue the email settings script (handles the new editor)
            wp_enqueue_script( 'mobooking-dashboard-email-settings', MOBOOKING_THEME_URI . 'assets/js/dashboard-email-settings.js', array('jquery', 'sortable-js'), MOBOOKING_VERSION, true );

            // Localize data for both scripts
            $settings_manager = new \MoBooking\Classes\Settings();
            $user_id = get_current_user_id();

            wp_localize_script('mobooking-dashboard-business-settings', 'mobooking_biz_settings_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
                'i18n' => [
                    'saving' => __('Saving...', 'mobooking'),
                    'save_success' => __('Settings saved successfully.', 'mobooking'),
                    'error_saving' => __('Error saving settings.', 'mobooking'),
                    'error_ajax' => __('An unexpected error occurred. Please try again.', 'mobooking'),
                ]
            ]);

            wp_localize_script('mobooking-dashboard-email-settings', 'mobooking_email_settings_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
                'templates' => $settings_manager->get_email_templates(),
                'biz_settings' => $settings_manager->get_business_settings($user_id),
                'base_template_url' => MOBOOKING_THEME_URI . 'templates/email/base-email-template.php',
                'site_url' => site_url(),
                'dummy_data' => \MoBooking\Classes\Notifications::get_dummy_data_for_preview(),
                'i18n' => [
                    // Component-related translations
                    'header_placeholder' => __( 'Header Text', 'mobooking' ),
                    'text_placeholder' => __( 'Paragraph text...', 'mobooking' ),
                    'button_placeholder' => __( 'Button Text', 'mobooking' ),
                    'button_url_placeholder' => __( 'Button URL', 'mobooking' ),
                    'spacer_text' => __( 'Spacer', 'mobooking' ),
                    'delete_component_title' => __( 'Delete Component', 'mobooking' ),
                ]
            ]);
        }

        if ( $current_page_slug === 'services' ) {
            wp_enqueue_style('mobooking-dashboard-services-redesigned', MOBOOKING_THEME_URI . 'assets/css/dashboard-services-redesigned.css', array('mobooking-dashboard-main'), MOBOOKING_VERSION);
            wp_enqueue_script('mobooking-dashboard-services', MOBOOKING_THEME_URI . 'assets/js/dashboard-services.js', array('jquery', 'jquery-ui-sortable'), MOBOOKING_VERSION, true);
            wp_localize_script('mobooking-dashboard-services', 'mobooking_services_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'services_nonce' => wp_create_nonce('mobooking_services_nonce'),
                'i18n' => [
                    'loading_services' => __('Loading...', 'mobooking'),
                    'no_services_found' => __('No services found.', 'mobooking'),
                    'confirm_delete_service' => __('Are you sure you want to delete this service?', 'mobooking'),
                ],
            ]);
        }

        if ( $current_page_slug === 'workers' ) {
            wp_enqueue_script( 'mobooking-dashboard-workers', MOBOOKING_THEME_URI . 'assets/js/dashboard-workers.js', array( 'jquery', 'mobooking-dialog' ), MOBOOKING_VERSION, true );
            wp_localize_script( 'mobooking-dashboard-workers', 'mobooking_workers_params', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'i18n' => array(
                    'error_occurred' => __( 'An error occurred. Please try again.', 'mobooking' ),
                    'error_ajax' => __( 'An AJAX error occurred. Please check your connection.', 'mobooking' ),
                    'confirm_delete' => __( 'Are you sure you want to revoke access for this worker? This action cannot be undone.', 'mobooking' ),
                    'error_deleting_worker' => __( 'Error deleting worker.', 'mobooking' ),
                    'error_saving_worker' => __( 'Error saving worker details.', 'mobooking' ),
                    'no_name_set' => __( 'No name set', 'mobooking' ),
                ),
            ));
        }

        if ( $current_page_slug === 'customers' || $current_page_slug === 'customer-details' ) {
            $customer_params = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
                'details_page_base_url' => home_url('/dashboard/customer-details/'),
                 'i18n' => [
                    'customer' => __('Customer', 'mobooking'),
                    'contact' => __('Contact', 'mobooking'),
                    'bookings' => __('Bookings', 'mobooking'),
                    'last_booking' => __('Last Booking', 'mobooking'),
                    'status' => __('Status', 'mobooking'),
                    'actions' => __('Actions', 'mobooking'),
                    'na' => __('N/A', 'mobooking'),
                    'no_customers_found' => __('No customers found', 'mobooking'),
                    'try_different_filters' => __('Try adjusting your filters or clearing them to see all customers.', 'mobooking'),
                    'error_loading' => __('Error loading customers.', 'mobooking'),
                    'less_filters' => __('Less', 'mobooking'),
                    'more_filters' => __('More', 'mobooking'),
                ],
                'statuses' => [
                    'active' => __('Active', 'mobooking'),
                    'inactive' => __('Inactive', 'mobooking'),
                    'lead' => __('Lead', 'mobooking'),
                ],
                'icons' => [
                    'active' => mobooking_get_status_badge_icon_svg('active'),
                    'inactive' => mobooking_get_status_badge_icon_svg('inactive'),
                    'lead' => mobooking_get_status_badge_icon_svg('lead'),
                ]
            ];

            if ($current_page_slug === 'customers') {
                wp_enqueue_script('mobooking-dashboard-customers', MOBOOKING_THEME_URI . 'assets/js/dashboard-customers.js', array('jquery', 'jquery-ui-datepicker'), MOBOOKING_VERSION, true);
                wp_localize_script('mobooking-dashboard-customers', 'mobooking_customers_params', $customer_params);
            }

            if ($current_page_slug === 'customer-details') {
                wp_enqueue_script('mobooking-dashboard-customer-details', MOBOOKING_THEME_URI . 'assets/js/dashboard-customer-details.js', array('jquery'), MOBOOKING_VERSION, true);
                wp_localize_script('mobooking-dashboard-customer-details', 'mobooking_customers_params', $customer_params);
            }
        }

        if ( $current_page_slug === 'settings' ) {
            // Enqueue styles for color picker
            wp_enqueue_style( 'wp-color-picker' );
            // Enqueue the settings page specific CSS
            wp_enqueue_style( 'mobooking-dashboard-settings', MOBOOKING_THEME_URI . 'assets/css/dashboard-settings.css', array('mobooking-dashboard-main'), MOBOOKING_VERSION );

            // Enqueue SortableJS for the email builder
            wp_enqueue_script( 'sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js', array(), '1.15.0', true );

            // Enqueue the business settings script (handles tabs, saving, logo upload)
            wp_enqueue_script( 'mobooking-dashboard-business-settings', MOBOOKING_THEME_URI . 'assets/js/dashboard-business-settings.js', array('jquery', 'wp-color-picker'), MOBOOKING_VERSION, true );

            // Enqueue the email settings script (handles the new editor)
            wp_enqueue_script( 'mobooking-dashboard-email-settings', MOBOOKING_THEME_URI . 'assets/js/dashboard-email-settings.js', array('jquery', 'sortable-js'), MOBOOKING_VERSION, true );

            // Localize data for both scripts
            $settings_manager = new \MoBooking\Classes\Settings();
            $user_id = get_current_user_id();

            wp_localize_script('mobooking-dashboard-business-settings', 'mobooking_biz_settings_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
                'i18n' => [
                    'saving' => __('Saving...', 'mobooking'),
                    'save_success' => __('Settings saved successfully.', 'mobooking'),
                    'error_saving' => __('Error saving settings.', 'mobooking'),
                    'error_ajax' => __('An unexpected error occurred. Please try again.', 'mobooking'),
                ]
            ]);

            wp_localize_script('mobooking-dashboard-email-settings', 'mobooking_email_settings_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
                'templates' => $settings_manager->get_email_templates(),
                'biz_settings' => $settings_manager->get_business_settings($user_id),
                'base_template_url' => MOBOOKING_THEME_URI . 'templates/email/base-email-template.php',
                'site_url' => site_url(),
                'dummy_data' => \MoBooking\Classes\Notifications::get_dummy_data_for_preview(),
                'i18n' => [
                    // Component-related translations
                    'header_placeholder' => __( 'Header Text', 'mobooking' ),
                    'text_placeholder' => __( 'Paragraph text...', 'mobooking' ),
                    'button_placeholder' => __( 'Button Text', 'mobooking' ),
                    'button_url_placeholder' => __( 'Button URL', 'mobooking' ),
                    'spacer_text' => __( 'Spacer', 'mobooking' ),
                    'delete_component_title' => __( 'Delete Component', 'mobooking' ),
                ]
            ]);
        }
    }
    flush_rewrite_rules();
}
add_action( 'wp_enqueue_scripts', 'mobooking_scripts' );

function create_legal_pages() {
    $pages = [
        'privacy-policy' => [
            'title' => 'Privacy Policy',
            'content' => 'legal/privacy-policy.html',
        ],
        'terms-of-use' => [
            'title' => 'Terms of Use',
            'content' => 'legal/terms-of-use.html',
        ],
        'cookies-policy' => [
            'title' => 'Cookies Policy',
            'content' => 'legal/cookies-policy.html',
        ],
        'refund-policy' => [
            'title' => 'Refund Policy',
            'content' => 'legal/refund-policy.html',
        ],
    ];

    foreach ($pages as $slug => $page) {
        // Check if page exists
        $page_obj = get_page_by_path($slug);
        if (!$page_obj) {
            $page_id = wp_insert_post([
                'post_title' => $page['title'],
                'post_name' => $slug,
                'post_content' => file_get_contents(get_template_directory() . '/assets/' . $page['content']),
                'post_status' => 'publish',
                'post_type' => 'page',
            ]);
        }
    }
}
add_action('after_switch_theme', 'create_legal_pages');
?>
