<?php
// Basic theme setup
if ( ! function_exists( 'mobooking_setup' ) ) :
    function mobooking_setup() {
        load_theme_textdomain( 'mobooking', MOBOOKING_THEME_DIR . 'languages' );
        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        register_nav_menus(
            array( 'menu-1' => esc_html__( 'Primary', 'mobooking' ) )
        );
        add_theme_support(
            'html5',
            array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
        );
        add_theme_support(
            'custom-background',
            apply_filters(
                'mobooking_custom_background_args',
                array( 'default-color' => 'ffffff', 'default-image' => '' )
            )
        );
        add_theme_support( 'customize-selective-refresh-widgets' );
    }
endif;
add_action( 'after_setup_theme', 'mobooking_setup' );

// Enqueue scripts and styles for the front-end.
function mobooking_scripts() {
    $public_form_currency = [ 'code' => 'USD', 'symbol' => '$' ];

    wp_enqueue_style( 'mobooking-inter-font', 'https://fonts.googleapis.com/css2?family=Outfit:wght@100;200;300;400;500;600;700;800;900&display=swap', array(), null );
    wp_enqueue_style( 'mobooking-reset', MOBOOKING_THEME_URI . 'assets/css/reset.css', array(), MOBOOKING_VERSION );
    wp_enqueue_style( 'mobooking-style', get_stylesheet_uri(), array('mobooking-inter-font', 'mobooking-reset'), MOBOOKING_VERSION );
    wp_enqueue_style( 'mobooking-toggle-switch', MOBOOKING_THEME_URI . 'assets/css/toggle-switch.css', array('mobooking-style'), MOBOOKING_VERSION );

    if ( is_page_template( 'page-login.php' ) || is_page_template('page-register.php') || is_page_template('page-forgot-password.php') ) {
        wp_enqueue_style( 'mobooking-auth-pages', MOBOOKING_THEME_URI . 'assets/css/auth-pages.css', array('mobooking-style'), MOBOOKING_VERSION );
        wp_enqueue_script( 'mobooking-auth', MOBOOKING_THEME_URI . 'assets/js/auth.js', array( 'jquery' ), MOBOOKING_VERSION, true );
        wp_localize_script( 'mobooking-auth', 'mobooking_auth_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'login_nonce' => wp_create_nonce( MoBooking\Classes\Auth::LOGIN_NONCE_ACTION ),
                'register_nonce' => wp_create_nonce( MoBooking\Classes\Auth::REGISTER_NONCE_ACTION ),
                'check_email_nonce' => wp_create_nonce( 'mobooking_check_email_nonce_action' ),
                'check_slug_nonce' => wp_create_nonce( 'mobooking_check_slug_nonce_action' ),
                'forgot_password_nonce' => wp_create_nonce( 'mobooking_forgot_password_nonce_action' ),
            )
        );
    }

    $page_type_for_scripts = get_query_var('mobooking_page_type');
    if ( is_page_template('templates/booking-form-public.php') || $page_type_for_scripts === 'public_booking' || $page_type_for_scripts === 'embed_booking' ) {
        wp_enqueue_style( 'mobooking-booking-form-refactored', MOBOOKING_THEME_URI . 'assets/css/booking-form-refactored.css', array('mobooking-style'), MOBOOKING_VERSION );
        wp_enqueue_style( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.9' );
        wp_enqueue_script( 'flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), '4.6.9', true );
        wp_enqueue_script('mobooking-public-booking-form', MOBOOKING_THEME_URI . 'assets/js/booking-form-public.js', array('jquery', 'flatpickr'), MOBOOKING_VERSION, true);

        $effective_tenant_id_for_public_form = 0;
        if ($page_type_for_scripts === 'public_booking' || $page_type_for_scripts === 'embed_booking') { $effective_tenant_id_for_public_form = get_query_var('mobooking_tenant_id_on_page', 0); }
        if (empty($effective_tenant_id_for_public_form) && !empty($_GET['tid'])) { $effective_tenant_id_for_public_form = intval($_GET['tid']); }
        if (empty($effective_tenant_id_for_public_form) && is_user_logged_in()) {
            $current_user = wp_get_current_user();
            if (in_array('mobooking_business_owner', $current_user->roles)) { $effective_tenant_id_for_public_form = $current_user->ID; }
        }
        if (empty($effective_tenant_id_for_public_form)) {
            $business_owners = get_users(['role' => 'mobooking_business_owner', 'number' => 1, 'fields' => 'ID']);
            if (!empty($business_owners)) { $effective_tenant_id_for_public_form = $business_owners[0]; }
        }

        $tenant_settings = [];
        if ($effective_tenant_id_for_public_form) {
            $settings_manager = new \MoBooking\Classes\Settings();
            $tenant_settings = $settings_manager->get_booking_form_settings($effective_tenant_id_for_public_form);
            $biz_settings = $settings_manager->get_business_settings($effective_tenant_id_for_public_form);
            if (!empty($biz_settings['biz_currency_code'])) { $public_form_currency['code'] = $biz_settings['biz_currency_code']; }
            if (!empty($biz_settings['biz_currency_symbol'])) { $public_form_currency['symbol'] = $biz_settings['biz_currency_symbol']; }
        }

        $i18n_strings = [
            'zip_required' => __('Please enter your ZIP code.', 'mobooking'), 'country_code_required' => __('Please enter your Country Code.', 'mobooking'),
            'tenant_id_missing' => __('Business identifier is missing. Cannot check availability.', 'mobooking'), 'tenant_id_missing_refresh' => __('Business ID is missing. Please refresh and try again or contact support.', 'mobooking'),
            'checking' => __('Checking...', 'mobooking'), 'error_generic' => __('An unexpected error occurred. Please try again.', 'mobooking'),
            'loading_services' => __('Loading services...', 'mobooking'), 'no_services_available' => __('No services are currently available for this area. Please try another location or check back later.', 'mobooking'),
            'error_loading_services' => __('Could not load services. Please try again.', 'mobooking'), 'select_one_service' => __('Please select at least one service.', 'mobooking'),
            'loading_options' => __('Loading service options...', 'mobooking'), 'no_options_available' => __('No additional options are available for the selected services.', 'mobooking'),
            'error_loading_options' => __('Could not load service options. Please try again.', 'mobooking'),
            'name_required' => __('Please enter your name.', 'mobooking'), 'email_required' => __('Please enter your email address.', 'mobooking'),
            'phone_required' => __('Please enter your phone number.', 'mobooking'), 'address_required' => __('Please enter your service address.', 'mobooking'),
            'date_required' => __('Please select a preferred date.', 'mobooking'), 'time_required' => __('Please select a preferred time.', 'mobooking'),
            'discount_code_required' => __('Please enter a discount code.', 'mobooking'), 'enter_discount_code' => __('Please enter a discount code.', 'mobooking'),
            'invalid_discount_code' => __('Invalid discount code.', 'mobooking'), 'discount_applied' => __('Discount applied successfully!', 'mobooking'),
            'error_applying_discount' => __('Error applying discount. Please try again.', 'mobooking'), 'discount_error' => __('Error applying discount. Please try again.', 'mobooking'),
            'submitting' => __('Submitting your booking...', 'mobooking'), 'booking_submitted' => __('Your booking has been submitted successfully!', 'mobooking'),
            'booking_error' => __('There was an error submitting your booking. Please try again.', 'mobooking'),
            'error_ajax' => __('A network error occurred. Please check your connection and try again.', 'mobooking'),
            'continue' => __('Continue', 'mobooking'), 'back' => __('Back', 'mobooking'), 'submit_booking' => __('Submit Booking', 'mobooking'),
        ];

        wp_localize_script('mobooking-public-booking-form', 'MOBOOKING_CONFIG', [
            'ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('mobooking_booking_form_nonce'),
            'tenant_id' => $effective_tenant_id_for_public_form, 'currency' => $public_form_currency, 'site_url' => site_url(),
            'i18n' => $i18n_strings,
            'settings' => [
                'bf_header_text' => isset($tenant_settings['bf_header_text']) ? esc_js($tenant_settings['bf_header_text']) : '',
                'bf_show_pricing' => $tenant_settings['bf_show_pricing'] ?? '1', 'bf_allow_discount_codes' => $tenant_settings['bf_allow_discount_codes'] ?? '1',
                'bf_theme_color' => $tenant_settings['bf_theme_color'] ?? '#1abc9c', 'bf_custom_css' => $tenant_settings['bf_custom_css'] ?? '',
                'bf_form_enabled' => $tenant_settings['bf_form_enabled'] ?? '1', 'bf_maintenance_message' => isset($tenant_settings['bf_maintenance_message']) ? esc_js($tenant_settings['bf_maintenance_message']) : '',
                'bf_enable_location_check' => $tenant_settings['bf_enable_location_check'] ?? '1',
            ],
        ]);
    }
}
add_action( 'wp_enqueue_scripts', 'mobooking_scripts' );

// Enqueue scripts and styles for the admin dashboard.
function mobooking_admin_scripts( $hook ) {
    if ( strpos( $hook, 'mobooking' ) === false && ( !isset($_GET['page']) || strpos($_GET['page'], 'mobooking-') === false ) && strpos($_SERVER['REQUEST_URI'] ?? '', '/dashboard/') === false) { return; }

    $current_page_slug = '';
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
    $path_segments = explode('/', $path);
    if (isset($path_segments[0]) && $path_segments[0] === 'dashboard') {
        $current_page_slug = $path_segments[1] ?? 'overview';
    } else if (isset($_GET['page'])) {
        $current_page_slug = str_replace('mobooking-', '', $_GET['page']);
    }
    $current_page_slug = sanitize_title($current_page_slug);
    if (empty($current_page_slug)) { $current_page_slug = 'overview'; }

    wp_enqueue_style('mobooking-dashboard-main', MOBOOKING_THEME_URI . 'assets/css/dashboard-main.css', array(), MOBOOKING_VERSION);
    if ($current_page_slug === 'bookings' && isset($_GET['action']) && $_GET['action'] === 'view_booking') {
        wp_enqueue_style('mobooking-dashboard-booking-single', MOBOOKING_THEME_URI . 'assets/css/dashboard-booking-single.css', array('mobooking-dashboard-main'), MOBOOKING_VERSION);
    }
    wp_enqueue_script('mobooking-dialog', MOBOOKING_THEME_URI . 'assets/js/dialog.js', array(), MOBOOKING_VERSION, true);
    wp_enqueue_script('mobooking-toast', MOBOOKING_THEME_URI . 'assets/js/toast.js', array('jquery'), MOBOOKING_VERSION, true);
    wp_enqueue_script('mobooking-dashboard-header', MOBOOKING_THEME_URI . 'assets/js/dashboard-header.js', array('jquery'), MOBOOKING_VERSION, true);
    wp_localize_script('mobooking-dashboard-header', 'mobooking_dashboard_params', [ 'ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('mobooking_dashboard_nonce') ]);

    if ( $current_page_slug === 'settings' ) {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_style( 'mobooking-dashboard-settings', MOBOOKING_THEME_URI . 'assets/css/dashboard-settings.css', array('mobooking-dashboard-main'), MOBOOKING_VERSION );
        wp_enqueue_script( 'sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js', array(), '1.15.0', true );
        wp_enqueue_script( 'mobooking-dashboard-business-settings', MOBOOKING_THEME_URI . 'assets/js/dashboard-business-settings.js', array('jquery', 'wp-color-picker'), MOBOOKING_VERSION, true );
        wp_enqueue_script( 'mobooking-dashboard-email-settings', MOBOOKING_THEME_URI . 'assets/js/dashboard-email-settings.js', array('jquery', 'sortable-js'), MOBOOKING_VERSION, true );
        $settings_manager = new \MoBooking\Classes\Settings();
        $user_id = get_current_user_id();
        wp_localize_script('mobooking-dashboard-business-settings', 'mobooking_biz_settings_params', [
            'ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
            'i18n' => [ 'saving' => __('Saving...', 'mobooking'), 'save_success' => __('Settings saved successfully.', 'mobooking'), 'error_saving' => __('Error saving settings.', 'mobooking'), 'error_ajax' => __('An unexpected error occurred. Please try again.', 'mobooking'), ]
        ]);
        wp_localize_script('mobooking-dashboard-email-settings', 'mobooking_email_settings_params', [
            'ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
            'templates' => $settings_manager->get_email_templates(), 'biz_settings' => $settings_manager->get_business_settings($user_id),
            'base_template_url' => MOBOOKING_THEME_URI . 'templates/email/base-email-template.php', 'site_url' => site_url(),
            'dummy_data' => \MoBooking\Classes\Notifications::get_dummy_data_for_preview(),
            'i18n' => [ 'header_placeholder' => __( 'Header Text', 'mobooking' ), 'text_placeholder' => __( 'Paragraph text...', 'mobooking' ), 'button_placeholder' => __( 'Button Text', 'mobooking' ), 'button_url_placeholder' => __( 'Button URL', 'mobooking' ), 'spacer_text' => __( 'Spacer', 'mobooking' ), 'delete_component_title' => __( 'Delete Component', 'mobooking' ), ]
        ]);
    }
    if ( $current_page_slug === 'services' ) {
        wp_enqueue_style('mobooking-dashboard-services-redesigned', MOBOOKING_THEME_URI . 'assets/css/dashboard-services-redesigned.css', array('mobooking-dashboard-main'), MOBOOKING_VERSION);
        wp_enqueue_script('mobooking-dashboard-services', MOBOOKING_THEME_URI . 'assets/js/dashboard-services.js', array('jquery'), MOBOOKING_VERSION, true);
        wp_localize_script('mobooking-dashboard-services', 'mobooking_services_params', [
            'ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('mobooking_services_nonce'),
            'i18n' => [ 'loading_services' => __('Loading...', 'mobooking'), 'no_services_found' => __('No services found.', 'mobooking'), 'confirm_delete_service' => __('Are you sure you want to delete this service?', 'mobooking'), ],
        ]);
    }
    if ( $current_page_slug === 'workers' ) {
        wp_enqueue_script( 'mobooking-dashboard-workers', MOBOOKING_THEME_URI . 'assets/js/dashboard-workers.js', array( 'jquery', 'mobooking-dialog' ), MOBOOKING_VERSION, true );
        wp_localize_script( 'mobooking-dashboard-workers', 'mobooking_workers_params', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'i18n' => array( 'error_occurred' => __( 'An error occurred. Please try again.', 'mobooking' ), 'error_ajax' => __( 'An AJAX error occurred. Please check your connection.', 'mobooking' ), 'confirm_delete' => __( 'Are you sure you want to revoke access for this worker? This action cannot be undone.', 'mobooking' ), 'error_deleting_worker' => __( 'Error deleting worker.', 'mobooking' ), 'error_saving_worker' => __( 'Error saving worker details.', 'mobooking' ), 'no_name_set' => __( 'No name set', 'mobooking' ), ),
        ));
    }
    if ( $current_page_slug === 'customers' || $current_page_slug === 'customer-details' ) {
        $customer_params = [
            'ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('mobooking_dashboard_nonce'),
            'details_page_base_url' => home_url('/dashboard/customer-details/'),
            'i18n' => [ 'customer' => __('Customer', 'mobooking'), 'contact' => __('Contact', 'mobooking'), 'bookings' => __('Bookings', 'mobooking'), 'last_booking' => __('Last Booking', 'mobooking'), 'status' => __('Status', 'mobooking'), 'actions' => __('Actions', 'mobooking'), 'na' => __('N/A', 'mobooking'), 'no_customers_found' => __('No customers found', 'mobooking'), 'try_different_filters' => __('Try adjusting your filters or clearing them to see all customers.', 'mobooking'), 'error_loading' => __('Error loading customers.', 'mobooking'), 'less_filters' => __('Less', 'mobooking'), 'more_filters' => __('More', 'mobooking'), ],
            'statuses' => [ 'active' => __('Active', 'mobooking'), 'inactive' => __('Inactive', 'mobooking'), 'lead' => __('Lead', 'mobooking'), ],
            'icons' => [ 'active' => mobooking_get_status_badge_icon_svg('active'), 'inactive' => mobooking_get_status_badge_icon_svg('inactive'), 'lead' => mobooking_get_status_badge_icon_svg('lead'), ]
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
}
add_action( 'admin_enqueue_scripts', 'mobooking_admin_scripts' );
?>
