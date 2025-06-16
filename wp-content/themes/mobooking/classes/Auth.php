<?php
/**
 * Class Auth
 * Handles authentication, user roles, and registration.
 * @package MoBooking\Classes
 */
namespace MoBooking\Classes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Auth {
    const ROLE_BUSINESS_OWNER = 'mobooking_business_owner';
    const LOGIN_NONCE_ACTION = 'mobooking_login_action';
    const REGISTER_NONCE_ACTION = 'mobooking_register_action';

    public function __construct() {
        // Constructor can be used to add initial hooks if needed
    }

    public static function add_business_owner_role() {
        add_role(
            self::ROLE_BUSINESS_OWNER,
            __( 'Business Owner', 'mobooking' ),
            array(
                'read' => true,
                'edit_posts' => true, // Example capability
                'upload_files' => true, // Example capability
                // Add more relevant capabilities later
                // 'manage_bookings', 'edit_theme_options' (careful with this one)
            )
        );
    }

    public static function remove_business_owner_role() {
        if ( get_role( self::ROLE_BUSINESS_OWNER ) ) {
            remove_role( self::ROLE_BUSINESS_OWNER );
        }
    }

    public function init_ajax_handlers() {
        add_action( 'wp_ajax_nopriv_mobooking_login', [ $this, 'handle_ajax_login' ] );
        add_action( 'wp_ajax_nopriv_mobooking_register', [ $this, 'handle_ajax_registration' ] );
        // wp_ajax_mobooking_login for logged-in users if needed, but login is for non-logged-in
    }

    public function handle_ajax_registration() {
        check_ajax_referer( self::REGISTER_NONCE_ACTION, 'nonce' );

        $email    = sanitize_email( $_POST['email'] );
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please provide a valid email address.', 'mobooking' ) ) );
        }
        if ( empty( $password ) ) {
            wp_send_json_error( array( 'message' => __( 'Please enter a password.', 'mobooking' ) ) );
        }
        if ( $password !== $password_confirm ) {
            wp_send_json_error( array( 'message' => __( 'Passwords do not match.', 'mobooking' ) ) );
        }
        if ( strlen( $password ) < 8 ) { // Example: enforce minimum password length
            wp_send_json_error( array( 'message' => __( 'Password must be at least 8 characters long.', 'mobooking' ) ) );
        }

        if ( username_exists( $email ) || email_exists( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'This email is already registered. Please login.', 'mobooking' ) ) );
        }

        // Use email as username
        $user_id = wp_create_user( $email, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
        } else {
            $user = new \WP_User( $user_id );
            $user->set_role( self::ROLE_BUSINESS_OWNER );

            // Placeholder for initializing default settings
            if (class_exists('MoBooking\Classes\Settings')) {
                // MoBooking\Classes\Settings::initialize_default_settings( $user_id );
                // For now, we'll just note this. Actual implementation in Settings class later.
            }

            // Log the user in
            wp_set_current_user( $user_id, $email );
            wp_set_auth_cookie( $user_id, true, is_ssl() );

            wp_send_json_success( array(
                'message' => __( 'Registration successful. Redirecting to your dashboard...', 'mobooking' ),
                'redirect_url' => home_url( '/dashboard/' )
            ) );
        }
        wp_die();
    }

    public function handle_ajax_login() {
        check_ajax_referer( self::LOGIN_NONCE_ACTION, 'nonce' );

        $info = array();
        $info['user_login'] = sanitize_user( $_POST['log'] );
        $info['user_password'] = $_POST['pwd'];
        $info['remember'] = isset( $_POST['rememberme'] ) && $_POST['rememberme'] == 'forever';

        $user_signon = wp_signon( $info, is_ssl() );

        if ( is_wp_error( $user_signon ) ) {
            wp_send_json_error( array( 'message' => $user_signon->get_error_message() ) );
        } else {
            // Check if the user has the correct role
            $user = $user_signon;
            if ( in_array( self::ROLE_BUSINESS_OWNER, (array) $user->roles ) ) {
                wp_set_current_user($user->ID); // Ensure user is set for this session
                wp_set_auth_cookie($user->ID, $info['remember']);
                wp_send_json_success( array( 'message' => __( 'Login successful. Redirecting...', 'mobooking' ), 'redirect_url' => home_url('/dashboard/') ) );
            } else {
                // If login is valid but not the target role, log them out of this attempt and send error
                // Or, handle as a generic failed login for security (don't reveal role existence)
                wp_logout(); // Clears the cookie set by wp_signon
                wp_send_json_error( array( 'message' => __( 'Invalid credentials or not a business owner account.', 'mobooking' ) ) );
            }
        }
        wp_die();
    }

    // Registration method will be added later
    // public static function register_user( $email, $password ) { ... }
}
