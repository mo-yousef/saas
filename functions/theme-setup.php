<?php
// Basic theme setup
if ( ! function_exists( 'mobooking_setup' ) ) :
    function mobooking_setup() {
        load_theme_textdomain( 'mobooking', MOBOOKING_THEME_DIR . 'languages' );
        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        register_nav_menus( ['menu-1' => esc_html__( 'Primary', 'mobooking' )] );
        add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
        add_theme_support('custom-background', apply_filters('mobooking_custom_background_args', ['default-color' => 'ffffff', 'default-image' => '']));
        add_theme_support( 'customize-selective-refresh-widgets' );
    }
endif;
add_action( 'after_setup_theme', 'mobooking_setup' );

/**
 * Enqueue scripts and styles for the frontend (public-facing pages).
 */
function mobooking_scripts() {
    wp_enqueue_style( 'mobooking-inter-font', 'https://fonts.googleapis.com/css2?family=Outfit:wght@100;200;300;400;500;600;700;800;900&display=swap', [], null );
    wp_enqueue_style( 'mobooking-reset', MOBOOKING_THEME_URI . 'assets/css/reset.css', [], MOBOOKING_VERSION );
    wp_enqueue_style( 'mobooking-style', get_stylesheet_uri(), ['mobooking-inter-font', 'mobooking-reset'], MOBOOKING_VERSION );

    // Auth pages scripts
    if ( is_page_template( 'page-login.php' ) || is_page_template('page-register.php') || is_page_template('page-forgot-password.php') ) {
        wp_enqueue_style( 'mobooking-auth-pages', MOBOOKING_THEME_URI . 'assets/css/auth-pages.css', ['mobooking-style'], MOBOOKING_VERSION );
        wp_enqueue_script( 'mobooking-auth', MOBOOKING_THEME_URI . 'assets/js/auth.js', ['jquery'], MOBOOKING_VERSION, true );
        wp_localize_script('mobooking-auth', 'mobooking_auth_params', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'login_nonce' => wp_create_nonce( \MoBooking\Classes\Auth::LOGIN_NONCE_ACTION ),
            'register_nonce' => wp_create_nonce( \MoBooking\Classes\Auth::REGISTER_NONCE_ACTION ),
            'check_email_nonce' => wp_create_nonce( 'mobooking_check_email_nonce_action' ),
            'check_slug_nonce' => wp_create_nonce( 'mobooking_check_slug_nonce_action' ),
            'forgot_password_nonce' => wp_create_nonce( 'mobooking_forgot_password_nonce_action' ),
        ]);
    }

    // Public Booking Form scripts
    $page_type_for_scripts = get_query_var('mobooking_page_type');
    if ( is_page_template('templates/booking-form-public.php') || $page_type_for_scripts === 'public_booking' || $page_type_for_scripts === 'embed_booking' ) {
        // Styles and scripts for public booking form
        // ... (This part remains unchanged as it's for the public site)
    }
}
add_action( 'wp_enqueue_scripts', 'mobooking_scripts' );

/**
 * Enqueue scripts and styles for the backend (dashboard pages).
 */
function mobooking_admin_scripts() {
    // This is the correct hook for all dashboard pages.

    // Get current dashboard page slug
    $current_page_slug = '';
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/dashboard/') !== false) {
        $current_page_slug = get_query_var('mobooking_dashboard_page');
        if (empty($current_page_slug)) {
            $path_segments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/'));
            if (isset($path_segments[0]) && $path_segments[0] === 'dashboard') {
                $current_page_slug = isset($path_segments[1]) && !empty($path_segments[1]) ? sanitize_title($path_segments[1]) : 'overview';
            }
        }
        if (empty($current_page_slug)) {
            $current_page_slug = 'overview';
        }
    }

    // Only load dashboard assets if we are on a dashboard page.
    if (!empty($current_page_slug)) {
        wp_enqueue_style('mobooking-dashboard-main', MOBOOKING_THEME_URI . 'assets/css/dashboard-main.css', [], MOBOOKING_VERSION);
        wp_enqueue_script('mobooking-dialog', MOBOOKING_THEME_URI . 'assets/js/dialog.js', [], MOBOOKING_VERSION, true);
        wp_enqueue_script('mobooking-toast', MOBOOKING_THEME_URI . 'assets/js/toast.js', ['jquery'], MOBOOKING_VERSION, true);
        wp_enqueue_script('mobooking-dashboard-header', MOBOOKING_THEME_URI . 'assets/js/dashboard-header.js', ['jquery'], MOBOOKING_VERSION, true);
        wp_localize_script('mobooking-dashboard-header', 'mobooking_dashboard_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mobooking_dashboard_nonce')
        ]);

        // Settings Page Scripts
        if ( $current_page_slug === 'settings' ) {
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_style( 'mobooking-dashboard-settings', MOBOOKING_THEME_URI . 'assets/css/dashboard-settings.css', ['mobooking-dashboard-main'], MOBOOKING_VERSION );
            wp_enqueue_script( 'sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js', [], '1.15.0', true );
            wp_enqueue_script( 'mobooking-dashboard-business-settings', MOBOOKING_THEME_URI . 'assets/js/dashboard-business-settings.js', ['jquery', 'wp-color-picker'], MOBOOKING_VERSION, true );
            wp_enqueue_script( 'mobooking-dashboard-email-settings', MOBOOKING_THEME_URI . 'assets/js/dashboard-email-settings.js', ['jquery', 'sortable-js'], MOBOOKING_VERSION, true );

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
                    'header_placeholder' => __( 'Header Text', 'mobooking' ),
                    'text_placeholder' => __( 'Paragraph text...', 'mobooking' ),
                    'button_placeholder' => __( 'Button Text', 'mobooking' ),
                    'button_url_placeholder' => __( 'Button URL', 'mobooking' ),
                    'spacer_text' => __( 'Spacer', 'mobooking' ),
                    'delete_component_title' => __( 'Delete Component', 'mobooking' ),
                ]
            ]);
        }
        // Add other dashboard page script conditions here...
    }
}
add_action( 'admin_enqueue_scripts', 'mobooking_admin_scripts' );
