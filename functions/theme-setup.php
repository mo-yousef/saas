<?php
// Basic theme setup
if ( ! function_exists( 'nordbooking_setup' ) ) :
    function nordbooking_setup() {
        // Make theme available for translation.
        load_theme_textdomain( 'NORDBOOKING', NORDBOOKING_THEME_DIR . 'languages' );

        // Add default posts and comments RSS feed links to head.
        add_theme_support( 'automatic-feed-links' );

        // Let WordPress manage the document title.
        add_theme_support( 'title-tag' );

        // Enable support for Post Thumbnails on posts and pages.
        add_theme_support( 'post-thumbnails' );

        // This theme uses wp_nav_menu() in one location.
        register_nav_menus(
            array(
                'menu-1' => esc_html__( 'Primary', 'NORDBOOKING' ),
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
                'nordbooking_custom_background_args',
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
add_action( 'after_setup_theme', 'nordbooking_setup' );


// Enqueue scripts and styles.
// REPLACE the existing nordbooking_scripts() function in your functions.php with this fixed version:

function nordbooking_scripts() {
    // Initialize variables to prevent undefined warnings if used before assignment in conditional blocks
    $public_form_currency = [ // Default currency settings
        'code' => 'USD',
        'symbol' => '$'
    ];

    // Enqueue Google Fonts
    wp_enqueue_style( 'NORDBOOKING-inter-font', 'https://fonts.googleapis.com/css2?family=Outfit:wght@100;200;300;400;500;600;700;800;900&display=swap', array(), null );

    // Enqueue CSS Reset first
    wp_enqueue_style( 'NORDBOOKING-reset', NORDBOOKING_THEME_URI . 'assets/css/reset.css', array(), NORDBOOKING_VERSION );

    // Enqueue main stylesheet, making it dependent on the reset
    wp_enqueue_style( 'NORDBOOKING-style', get_stylesheet_uri(), array('NORDBOOKING-inter-font', 'NORDBOOKING-reset'), NORDBOOKING_VERSION );
    wp_enqueue_style( 'NORDBOOKING-toggle-switch', NORDBOOKING_THEME_URI . 'assets/css/toggle-switch.css', array('NORDBOOKING-style'), NORDBOOKING_VERSION );

    // Enqueue new-front-page.css on all pages that are not the dashboard.
    // We are using strpos to check for the dashboard URL slug, as is_admin() will not work
    // for this theme's custom dashboard pages.
    if ( strpos($_SERVER['REQUEST_URI'] ?? '', '/dashboard/') === false ) {
        wp_enqueue_style( 'NORDBOOKING-new-front-page', NORDBOOKING_THEME_URI . 'assets/css/new-front-page.css', array('NORDBOOKING-style'), NORDBOOKING_VERSION );
    }


    if ( is_page_template( 'page-login.php' ) || is_page_template('page-register.php') || is_page_template('page-forgot-password.php') ) {
        // Enqueue the new auth pages specific CSS
        wp_enqueue_style( 'NORDBOOKING-auth-pages', NORDBOOKING_THEME_URI . 'assets/css/auth-pages.css', array('NORDBOOKING-style'), NORDBOOKING_VERSION );

        wp_enqueue_script( 'NORDBOOKING-auth', NORDBOOKING_THEME_URI . 'assets/js/auth.js', array( 'jquery' ), NORDBOOKING_VERSION, true );
        wp_localize_script(
            'NORDBOOKING-auth',
            'nordbooking_auth_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'login_nonce' => wp_create_nonce( NORDBOOKING\Classes\Auth::LOGIN_NONCE_ACTION ),
                'register_nonce' => wp_create_nonce( NORDBOOKING\Classes\Auth::REGISTER_NONCE_ACTION ),
                'check_email_nonce' => wp_create_nonce( 'nordbooking_check_email_nonce_action' ),
                'check_slug_nonce' => wp_create_nonce( 'nordbooking_check_slug_nonce_action' ),
                'forgot_password_nonce' => wp_create_nonce( 'nordbooking_forgot_password_nonce_action' ), // Nonce for forgot password
            )
        );
    }

    // For Public Booking Form page (standard page template OR slug-based route)
// For Public Booking Form page (standard page template OR slug-based route)
$page_type_for_scripts = get_query_var('nordbooking_page_type');
if ( is_page_template('templates/booking-form-public.php') || $page_type_for_scripts === 'public_booking' || $page_type_for_scripts === 'embed_booking' ) {
    // Enqueue the new modern booking form CSS
    wp_enqueue_style( 'nordbooking-booking-form-redesigned', NORDBOOKING_THEME_URI . 'assets/css/booking-form-redesigned.css', array('NORDBOOKING-style'), NORDBOOKING_VERSION );
    wp_enqueue_style( 'nordbooking-booking-form-validation', NORDBOOKING_THEME_URI . 'assets/css/booking-form-validation.css', array('nordbooking-booking-form-redesigned'), NORDBOOKING_VERSION );

    wp_enqueue_style( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.9' );
    wp_enqueue_script( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), '4.6.9', true );
    // wp_enqueue_script('nordbooking-booking-form', NORDBOOKING_THEME_URI . 'assets/js/booking-form.js', array('jquery', 'jquery-ui-datepicker'), NORDBOOKING_VERSION, true); // Commented out old script
    wp_enqueue_script('NORDBOOKING-public-booking-form', NORDBOOKING_THEME_URI . 'assets/js/booking-form-public.js', array('jquery', 'flatpickr'), NORDBOOKING_VERSION, true); // Enqueue new script

    $effective_tenant_id_for_public_form = 0;

    // Method 1: Prioritize tenant_id set by the slug-based routing via query_var
    if ($page_type_for_scripts === 'public_booking' || $page_type_for_scripts === 'embed_booking') {
        $effective_tenant_id_for_public_form = get_query_var('nordbooking_tenant_id_on_page', 0);
        error_log('[NORDBOOKING Scripts] Booking form (' . esc_html($page_type_for_scripts) . ' route). Tenant ID from query_var nordbooking_tenant_id_on_page: ' . $effective_tenant_id_for_public_form);
    }

    // Method 2: Fallback to ?tid if not a slug route or if tenant_id_on_page wasn't set by slug logic
    if (empty($effective_tenant_id_for_public_form) && !empty($_GET['tid'])) {
        $effective_tenant_id_for_public_form = intval($_GET['tid']);
        error_log('[NORDBOOKING Scripts] Public booking form (tid route). Tenant ID from $_GET[tid]: ' . $effective_tenant_id_for_public_form);
    }

    // Method 3: NEW - If still no tenant ID and user is logged in, use current user
    if (empty($effective_tenant_id_for_public_form) && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if (in_array('nordbooking_business_owner', $current_user->roles)) {
            $effective_tenant_id_for_public_form = $current_user->ID;
            error_log('[NORDBOOKING Scripts] Using current logged-in business owner as tenant ID: ' . $effective_tenant_id_for_public_form);
        }
    }

    // Method 4: NEW - If still no tenant ID, check if there's a default business owner in the system
    if (empty($effective_tenant_id_for_public_form)) {
        $business_owners = get_users([
            'role' => 'nordbooking_business_owner',
            'number' => 1,
            'fields' => 'ID'
        ]);

        if (!empty($business_owners)) {
            $effective_tenant_id_for_public_form = $business_owners[0];
            error_log('[NORDBOOKING Scripts] Using first available business owner as tenant ID: ' . $effective_tenant_id_for_public_form);
        }
    }

    // Load tenant settings for this specific tenant
    $tenant_settings = [];
    if ($effective_tenant_id_for_public_form) {
        $settings_manager = new \NORDBOOKING\Classes\Settings();
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
        'zip_required' => __('Please enter your ZIP code.', 'NORDBOOKING'),
        'country_code_required' => __('Please enter your Country Code.', 'NORDBOOKING'),
        'tenant_id_missing' => __('Business identifier is missing. Cannot check availability.', 'NORDBOOKING'),
        'tenant_id_missing_refresh' => __('Business ID is missing. Please refresh and try again or contact support.', 'NORDBOOKING'),
        'checking' => __('Checking...', 'NORDBOOKING'),
        'error_generic' => __('An unexpected error occurred. Please try again.', 'NORDBOOKING'),
        // Step 2
        'loading_services' => __('Loading services...', 'NORDBOOKING'),
        'no_services_available' => __('No services are currently available for this area. Please try another location or check back later.', 'NORDBOOKING'),
        'error_loading_services' => __('Could not load services. Please try again.', 'NORDBOOKING'),
        'select_one_service' => __('Please select at least one service.', 'NORDBOOKING'),
        // Step 3
        'loading_options' => __('Loading service options...', 'NORDBOOKING'),
        'no_options_available' => __('No additional options are available for the selected services.', 'NORDBOOKING'),
        'error_loading_options' => __('Could not load service options. Please try again.', 'NORDBOOKING'),
        // Step 4
        'name_required' => __('Please enter your name.', 'NORDBOOKING'),
        'email_required' => __('Please enter your email address.', 'NORDBOOKING'),
        'phone_required' => __('Please enter your phone number.', 'NORDBOOKING'),
        'address_required' => __('Please enter your service address.', 'NORDBOOKING'),
        'date_required' => __('Please select a preferred date.', 'NORDBOOKING'),
        'time_required' => __('Please select a preferred time.', 'NORDBOOKING'),
        // Step 5
        'discount_code_required' => __('Please enter a discount code.', 'NORDBOOKING'),
        'enter_discount_code' => __('Please enter a discount code.', 'NORDBOOKING'),
        'invalid_discount_code' => __('Invalid discount code.', 'NORDBOOKING'),
        'discount_applied' => __('Discount applied successfully!', 'NORDBOOKING'),
        'error_applying_discount' => __('Error applying discount. Please try again.', 'NORDBOOKING'),
        'discount_error' => __('Error applying discount. Please try again.', 'NORDBOOKING'),
        // Step 6
        'submitting' => __('Submitting your booking...', 'NORDBOOKING'),
        'booking_submitted' => __('Your booking has been submitted successfully!', 'NORDBOOKING'),
        'booking_error' => __('There was an error submitting your booking. Please try again.', 'NORDBOOKING'),
        // General
        'error_ajax' => __('A network error occurred. Please check your connection and try again.', 'NORDBOOKING'),
        'continue' => __('Continue', 'NORDBOOKING'),
        'back' => __('Back', 'NORDBOOKING'),
        'submit_booking' => __('Submit Booking', 'NORDBOOKING'),
    ];


    // Add custom CSS from settings if present and form is enabled
    if (!empty($tenant_settings['bf_custom_css']) && ($tenant_settings['bf_form_enabled'] ?? '1') === '1') {
        wp_add_inline_style('nordbooking-booking-form-modern', $tenant_settings['bf_custom_css']);
    }

    $theme_color = $tenant_settings['bf_theme_color'] ?? '#1abc9c';
    $dynamic_css = "
        .NORDBOOKING-header h1 {
            color: {$theme_color};
        }
        .NORDBOOKING-step-indicator.active {
            background: {$theme_color};
            color: white;
        }
        .NORDBOOKING-progress-fill {
            background: linear-gradient(90deg, {$theme_color}, #10b981);
        }
        .NORDBOOKING-input:focus {
            border-color: {$theme_color};
        }
        .NORDBOOKING-service-card:hover {
            border-color: {$theme_color};
        }
        .NORDBOOKING-service-card.selected {
            border-color: {$theme_color};
            background: rgba(26, 188, 156, 0.05);
        }
        .NORDBOOKING-service-price {
            color: {$theme_color};
        }
        .NORDBOOKING-radio-option:hover {
            border-color: {$theme_color};
        }
        .NORDBOOKING-time-slot:hover {
            border-color: {$theme_color};
        }
        .NORDBOOKING-time-slot.selected {
            border-color: {$theme_color};
            background: {$theme_color};
            color: white;
        }
        .NORDBOOKING-btn-primary {
            background: {$theme_color};
            color: white;
        }
        .NORDBOOKING-spinner {
            border-top-color: {$theme_color};
        }
    ";
    wp_add_inline_style('nordbooking-booking-form-modern', $dynamic_css);

    wp_localize_script('NORDBOOKING-public-booking-form', 'NORDBOOKING_CONFIG', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('nordbooking_booking_form_nonce'),
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
            'query_var_tenant_id' => get_query_var('nordbooking_tenant_id_on_page', 0),
            'get_tid' => $_GET['tid'] ?? null,
            'user_logged_in' => is_user_logged_in(),
            'current_user_id' => get_current_user_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
        ],
        // Pass PHP debug data if available
        'is_debug_mode' => $GLOBALS['nordbooking_is_debug_mode_active_flag'] ?? false,
        'initial_debug_info' => $GLOBALS['nordbooking_initial_php_debug_data'] ?? []
    ]);
}


    // FIXED: Get current dashboard page slug if we're on a dashboard page
    // This prevents the undefined variable error
    $current_page_slug = '';
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/dashboard/') !== false) {
        // Try to get from query vars first (set by router)
        $current_page_slug = get_query_var('nordbooking_dashboard_page');

        // Fallback to global variable
        if (empty($current_page_slug)) {
            $current_page_slug = isset($GLOBALS['nordbooking_current_dashboard_view']) ? $GLOBALS['nordbooking_current_dashboard_view'] : '';
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
        wp_enqueue_style('nordbooking-dashboard-main', NORDBOOKING_THEME_URI . 'assets/css/dashboard-main.css', array('NORDBOOKING-style'), NORDBOOKING_VERSION);

        // Enqueue the single booking page stylesheet if we are on the single booking page
        if ($current_page_slug === 'bookings' && isset($_GET['action']) && $_GET['action'] === 'view_booking') {
            wp_enqueue_style('nordbooking-dashboard-booking-single', NORDBOOKING_THEME_URI . 'assets/css/dashboard-booking-single.css', array('nordbooking-dashboard-main'), NORDBOOKING_VERSION);
        }

        // Enqueue scripts
        wp_enqueue_script('nordbooking-dialog', NORDBOOKING_THEME_URI . 'assets/js/dialog.js', array(), NORDBOOKING_VERSION, true);
        wp_enqueue_script('NORDBOOKING-toast', NORDBOOKING_THEME_URI . 'assets/js/toast.js', array('jquery'), NORDBOOKING_VERSION, true);
        wp_enqueue_script('nordbooking-dashboard-header', NORDBOOKING_THEME_URI . 'assets/js/dashboard-header.js', array('jquery'), NORDBOOKING_VERSION, true);
        wp_localize_script('nordbooking-dashboard-header', 'nordbooking_dashboard_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nordbooking_dashboard_nonce')
        ]);

        if ( $current_page_slug === 'settings' || $current_page_slug === 'booking-form' ) {
            // Enqueue styles for color picker
            wp_enqueue_style( 'wp-color-picker' );
            // Enqueue the settings page specific CSS
            wp_enqueue_style( 'nordbooking-dashboard-settings', NORDBOOKING_THEME_URI . 'assets/css/dashboard-settings.css', array('nordbooking-dashboard-main'), NORDBOOKING_VERSION );

            wp_enqueue_script( 'nordbooking-dashboard-booking-form-settings', NORDBOOKING_THEME_URI . 'assets/js/dashboard-booking-form-settings.js', array('jquery', 'wp-color-picker'), NORDBOOKING_VERSION, true );

            // Enqueue SortableJS for the email builder
            wp_enqueue_script( 'sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js', array(), '1.15.0', true );

            // Enqueue the business settings script (handles tabs, saving, logo upload)
            wp_enqueue_script( 'nordbooking-dashboard-business-settings', NORDBOOKING_THEME_URI . 'assets/js/dashboard-business-settings.js', array('jquery', 'wp-color-picker'), NORDBOOKING_VERSION, true );

            // Enqueue the email settings script (handles the new editor)
            wp_enqueue_script( 'nordbooking-dashboard-email-settings', NORDBOOKING_THEME_URI . 'assets/js/dashboard-email-settings.js', array('jquery', 'sortable-js'), NORDBOOKING_VERSION, true );

            // Localize data for both scripts
            $settings_manager = new \NORDBOOKING\Classes\Settings();
            $user_id = get_current_user_id();
            $biz_settings = $settings_manager->get_business_settings($user_id);
            $email_templates = $settings_manager->get_email_templates();

            $email_templates_data = [];
            foreach ($email_templates as $key => $template) {
                $subject = isset($biz_settings[$template['subject_key']]) ? $biz_settings[$template['subject_key']] : '';
                $body_json = isset($biz_settings[$template['body_key']]) ? $biz_settings[$template['body_key']] : '[]';
                $body = json_decode($body_json, true);

                if (!is_array($body)) {
                    $body = [];
                }

                $email_templates_data[$key] = [
                    'subject' => $subject,
                    'body' => $body,
                ];
            }

            wp_localize_script('nordbooking-dashboard-business-settings', 'nordbooking_biz_settings_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nordbooking_dashboard_nonce'),
                'i18n' => [
                    'saving' => __('Saving...', 'NORDBOOKING'),
                    'save_success' => __('Settings saved successfully.', 'NORDBOOKING'),
                    'error_saving' => __('Error saving settings.', 'NORDBOOKING'),
                    'error_ajax' => __('An unexpected error occurred. Please try again.', 'NORDBOOKING'),
                ]
            ]);

            wp_localize_script('nordbooking-dashboard-email-settings', 'nordbooking_email_settings_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nordbooking_dashboard_nonce'),
                'templates' => $email_templates,
                'email_templates_data' => $email_templates_data,
                'biz_settings' => $biz_settings,
                'base_template_url' => NORDBOOKING_THEME_URI . 'templates/email/base-email-template.php',
                'site_url' => site_url(),
                'dummy_data' => \NORDBOOKING\Classes\Notifications::get_dummy_data_for_preview(),
                'i18n' => [
                    // Component-related translations
                    'header_placeholder' => __( 'Header Text', 'NORDBOOKING' ),
                    'text_placeholder' => __( 'Paragraph text...', 'NORDBOOKING' ),
                    'button_placeholder' => __( 'Button Text', 'NORDBOOKING' ),
                    'button_url_placeholder' => __( 'Button URL', 'NORDBOOKING' ),
                    'spacer_text' => __( 'Spacer', 'NORDBOOKING' ),
                    'delete_component_title' => __( 'Delete Component', 'NORDBOOKING' ),
                ]
            ]);
        }

        if ( $current_page_slug === 'services' ) {
            wp_enqueue_style('nordbooking-dashboard', NORDBOOKING_THEME_URI . 'assets/css/dashboard.css', array('nordbooking-dashboard-main'), NORDBOOKING_VERSION);
            wp_enqueue_style('nordbooking-dashboard-services-redesigned', NORDBOOKING_THEME_URI . 'assets/css/dashboard-services-redesigned.css', array('nordbooking-dashboard-main'), NORDBOOKING_VERSION);
            wp_enqueue_script('nordbooking-dashboard-services', NORDBOOKING_THEME_URI . 'assets/js/dashboard-services.js', array('jquery', 'jquery-ui-sortable'), NORDBOOKING_VERSION, true);
            wp_localize_script('nordbooking-dashboard-services', 'nordbooking_services_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'services_nonce' => wp_create_nonce('nordbooking_services_nonce'),
                'i18n' => [
                    'loading_services' => __('Loading...', 'NORDBOOKING'),
                    'no_services_found' => __('No services found.', 'NORDBOOKING'),
                    'confirm_delete_service' => __('Are you sure you want to delete this service?', 'NORDBOOKING'),
                ],
            ]);
        }

        if ( $current_page_slug === 'workers' ) {
            wp_enqueue_script( 'nordbooking-dashboard-workers', NORDBOOKING_THEME_URI . 'assets/js/dashboard-workers.js', array( 'jquery', 'nordbooking-dialog' ), NORDBOOKING_VERSION, true );
            wp_localize_script( 'nordbooking-dashboard-workers', 'nordbooking_workers_params', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'i18n' => array(
                    'error_occurred' => __( 'An error occurred. Please try again.', 'NORDBOOKING' ),
                    'error_ajax' => __( 'An AJAX error occurred. Please check your connection.', 'NORDBOOKING' ),
                    'confirm_delete' => __( 'Are you sure you want to revoke access for this worker? This action cannot be undone.', 'NORDBOOKING' ),
                    'error_deleting_worker' => __( 'Error deleting worker.', 'NORDBOOKING' ),
                    'error_saving_worker' => __( 'Error saving worker details.', 'NORDBOOKING' ),
                    'no_name_set' => __( 'No name set', 'NORDBOOKING' ),
                ),
            ));
        }

        if ( $current_page_slug === 'customers' || $current_page_slug === 'customer-details' ) {
            $customer_params = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nordbooking_dashboard_nonce'),
                'details_page_base_url' => home_url('/dashboard/customer-details/'),
                 'i18n' => [
                    'customer' => __('Customer', 'NORDBOOKING'),
                    'contact' => __('Contact', 'NORDBOOKING'),
                    'bookings' => __('Bookings', 'NORDBOOKING'),
                    'last_booking' => __('Last Booking', 'NORDBOOKING'),
                    'status' => __('Status', 'NORDBOOKING'),
                    'actions' => __('Actions', 'NORDBOOKING'),
                    'na' => __('N/A', 'NORDBOOKING'),
                    'no_customers_found' => __('No customers found', 'NORDBOOKING'),
                    'try_different_filters' => __('Try adjusting your filters or clearing them to see all customers.', 'NORDBOOKING'),
                    'error_loading' => __('Error loading customers.', 'NORDBOOKING'),
                    'less_filters' => __('Less', 'NORDBOOKING'),
                    'more_filters' => __('More', 'NORDBOOKING'),
                ],
                'statuses' => [
                    'active' => __('Active', 'NORDBOOKING'),
                    'inactive' => __('Inactive', 'NORDBOOKING'),
                    'lead' => __('Lead', 'NORDBOOKING'),
                ],
                'icons' => [
                    'active' => nordbooking_get_status_badge_icon_svg('active'),
                    'inactive' => nordbooking_get_status_badge_icon_svg('inactive'),
                    'lead' => nordbooking_get_status_badge_icon_svg('lead'),
                ]
            ];

            if ($current_page_slug === 'customers') {
                wp_enqueue_script('nordbooking-dashboard-customers', NORDBOOKING_THEME_URI . 'assets/js/dashboard-customers.js', array('jquery', 'jquery-ui-datepicker'), NORDBOOKING_VERSION, true);
                wp_localize_script('nordbooking-dashboard-customers', 'nordbooking_customers_params', $customer_params);
            }

            if ($current_page_slug === 'customer-details') {
                wp_enqueue_script('nordbooking-dashboard-customer-details', NORDBOOKING_THEME_URI . 'assets/js/dashboard-customer-details.js', array('jquery'), NORDBOOKING_VERSION, true);
                wp_localize_script('nordbooking-dashboard-customer-details', 'nordbooking_customers_params', $customer_params);
            }
        }

    }
    flush_rewrite_rules();
}
add_action( 'wp_enqueue_scripts', 'nordbooking_scripts' );

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
