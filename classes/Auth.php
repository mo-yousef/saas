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
    const ROLE_WORKER_STAFF = 'mobooking_worker_staff';

    const META_KEY_OWNER_ID = 'mobooking_owner_user_id';

    // General Access
    const ACCESS_MOBOOKING_DASHBOARD = 'access_mobooking_dashboard'; // Already used, formalizing definition here

    // Capabilities
    const CAP_MANAGE_BOOKINGS = 'mobooking_manage_bookings';
    const CAP_VIEW_BOOKINGS = 'mobooking_view_bookings';
    const CAP_MANAGE_SERVICES = 'mobooking_manage_services';
    const CAP_VIEW_SERVICES = 'mobooking_view_services';
    const CAP_MANAGE_DISCOUNTS = 'mobooking_manage_discounts';
    const CAP_VIEW_DISCOUNTS = 'mobooking_view_discounts';
    const CAP_MANAGE_AREAS = 'mobooking_manage_areas';
    const CAP_VIEW_AREAS = 'mobooking_view_areas';
    const CAP_MANAGE_BOOKING_FORM = 'mobooking_manage_booking_form';
    const CAP_MANAGE_BUSINESS_SETTINGS = 'mobooking_manage_business_settings';
    const CAP_MANAGE_WORKERS = 'mobooking_manage_workers';

    const LOGIN_NONCE_ACTION = 'mobooking_login_action';
    const REGISTER_NONCE_ACTION = 'mobooking_register_action';

    public function __construct() {
        // Constructor can be used to add initial hooks if needed
        if ( is_admin() ) {
            add_filter( 'manage_users_custom_column', [ __CLASS__, 'display_custom_roles_in_users_list_column' ], 10, 3 );
        }
    }

    /**
     * Displays custom MoBooking role names in the WordPress Users list table.
     *
     * @param string $output      Custom column output. Default empty.
     * @param string $column_name Name of the custom column.
     * @param int    $user_id     ID of the current user.
     * @return string Modified output for the role column.
     */
    public static function display_custom_roles_in_users_list_column( $output, $column_name, $user_id ) {
        if ( $column_name === 'role' ) {
            $user = get_userdata( $user_id );
            if ( ! $user ) {
                return $output;
            }

            $mobooking_role_names = [];
            $all_mobooking_roles = [
                self::ROLE_BUSINESS_OWNER => __( 'Business Owner', 'mobooking' ),
                self::ROLE_WORKER_STAFF   => __( 'Worker Staff', 'mobooking' ),
            ];

            foreach ( $user->roles as $role_slug ) {
                if ( isset( $all_mobooking_roles[$role_slug] ) ) {
                    $mobooking_role_names[] = $all_mobooking_roles[$role_slug];
                }
            }

            if ( ! empty( $mobooking_role_names ) ) {
                // If other roles are present, WordPress usually lists them.
                // This filter primarily ensures our roles are clearly named if they are the ones WordPress picks up.
                // Or, we can choose to *only* display our roles if present.
                // For now, let's return our role names if any are found, potentially replacing default output.
                return implode( ', ', $mobooking_role_names );
            }
        }
        return $output;
    }

    public static function add_business_owner_role() {
        add_role(
            self::ROLE_BUSINESS_OWNER,
            __( 'Business Owner', 'mobooking' ),
            array(
                'read' => true,
                self::ACCESS_MOBOOKING_DASHBOARD => true,
                self::CAP_MANAGE_BOOKINGS => true,
                self::CAP_VIEW_BOOKINGS => true,
                self::CAP_MANAGE_SERVICES => true,
                self::CAP_VIEW_SERVICES => true,
                self::CAP_MANAGE_DISCOUNTS => true,
                self::CAP_VIEW_DISCOUNTS => true,
                self::CAP_MANAGE_AREAS => true,
                self::CAP_VIEW_AREAS => true,
                self::CAP_MANAGE_BOOKING_FORM => true,
                self::CAP_MANAGE_BUSINESS_SETTINGS => true,
                self::CAP_MANAGE_WORKERS => true,
                // 'edit_posts', 'upload_files' - examples, remove if not used by plugin features
            )
        );
    }

    public static function remove_business_owner_role() {
        if ( get_role( self::ROLE_BUSINESS_OWNER ) ) {
            remove_role( self::ROLE_BUSINESS_OWNER );
        }
    }

    public static function add_worker_roles() {
        add_role(
            self::ROLE_WORKER_STAFF,
            __( 'Worker Staff', 'mobooking' ),
            array(
                'read' => true,
                self::ACCESS_MOBOOKING_DASHBOARD => true,
                self::CAP_MANAGE_BOOKINGS => true, // As per requirement
                self::CAP_VIEW_BOOKINGS => true,   // If they manage, they can view
                self::CAP_VIEW_SERVICES => true,
                self::CAP_VIEW_DISCOUNTS => true,
                self::CAP_VIEW_AREAS => true,
            )
        );
    }

    public static function remove_worker_roles() {
        if ( get_role( self::ROLE_WORKER_STAFF ) ) {
            remove_role( self::ROLE_WORKER_STAFF );
        }
    }

    public function init_ajax_handlers() {
        add_action( 'wp_ajax_nopriv_mobooking_login', [ $this, 'handle_ajax_login' ] );
        add_action( 'wp_ajax_nopriv_mobooking_register', [ $this, 'handle_ajax_registration' ] );
        add_action( 'wp_ajax_mobooking_send_invitation', [ $this, 'handle_ajax_send_invitation' ] );
        add_action( 'wp_ajax_mobooking_change_worker_role', [ $this, 'handle_ajax_change_worker_role' ] );
        add_action( 'wp_ajax_mobooking_revoke_worker_access', [ $this, 'handle_ajax_revoke_worker_access' ] );
        add_action( 'wp_ajax_mobooking_direct_add_staff', [ $this, 'handle_ajax_direct_add_staff' ] );
        add_action( 'wp_ajax_mobooking_edit_worker_details', [ $this, 'handle_ajax_edit_worker_details' ] );
        // wp_ajax_mobooking_login for logged-in users if needed, but login is for non-logged-in
    }

    public function handle_ajax_edit_worker_details() {
        $worker_user_id = isset( $_POST['worker_user_id'] ) ? absint( $_POST['worker_user_id'] ) : 0;
        check_ajax_referer( 'mobooking_edit_worker_details_nonce_' . $worker_user_id, 'mobooking_edit_details_nonce_field' );

        if ( ! current_user_can( self::CAP_MANAGE_WORKERS ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to edit workers.', 'mobooking' ) ) );
        }

        if ( empty( $worker_user_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid worker ID.', 'mobooking' ) ) );
        }

        $current_owner_id = get_current_user_id();
        $actual_owner_id = get_user_meta( $worker_user_id, self::META_KEY_OWNER_ID, true );

        if ( (int) $actual_owner_id !== $current_owner_id ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to modify this worker or they are not assigned to you.', 'mobooking' ) ) );
        }

        $first_name = isset( $_POST['edit_first_name'] ) ? sanitize_text_field( $_POST['edit_first_name'] ) : null;
        $last_name  = isset( $_POST['edit_last_name'] ) ? sanitize_text_field( $_POST['edit_last_name'] ) : null;

        $update_args = array( 'ID' => $worker_user_id );
        if ( $first_name !== null ) { // Allow empty string to clear name
            $update_args['first_name'] = $first_name;
        }
        if ( $last_name !== null ) { // Allow empty string to clear name
            $update_args['last_name'] = $last_name;
        }

        // Only proceed if there's something to update besides ID
        if (count($update_args) > 1) {
            $result = wp_update_user( $update_args );
            if ( is_wp_error( $result ) ) {
                wp_send_json_error( array( 'message' => $result->get_error_message() ) );
            } else {
                wp_send_json_success( array( 'message' => __( 'Worker details updated successfully.', 'mobooking' ) ) );
            }
        } else {
            // Nothing to update, but not an error per se. Could be considered success.
            wp_send_json_success( array( 'message' => __( 'No changes detected for worker details.', 'mobooking' ) ) );
        }
    }

    public function handle_ajax_direct_add_staff() {
        check_ajax_referer( 'mobooking_direct_add_staff_nonce', 'mobooking_direct_add_staff_nonce_field' );

        if ( ! current_user_can( self::CAP_MANAGE_WORKERS ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to add workers.', 'mobooking' ) ) );
        }

        $email      = isset( $_POST['direct_add_staff_email'] ) ? sanitize_email( $_POST['direct_add_staff_email'] ) : '';
        $password   = isset( $_POST['direct_add_staff_password'] ) ? $_POST['direct_add_staff_password'] : '';
        $first_name = isset( $_POST['direct_add_staff_first_name'] ) ? sanitize_text_field( $_POST['direct_add_staff_first_name'] ) : '';
        $last_name  = isset( $_POST['direct_add_staff_last_name'] ) ? sanitize_text_field( $_POST['direct_add_staff_last_name'] ) : '';
        $current_user_id = get_current_user_id(); // This is the Business Owner

        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please provide a valid email address.', 'mobooking' ) ) );
        }

        if ( empty( $password ) || strlen( $password ) < 8 ) {
            wp_send_json_error( array( 'message' => __( 'Password must be at least 8 characters long.', 'mobooking' ) ) );
        }

        if ( email_exists( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'This email address is already registered.', 'mobooking' ) ) );
        }
        // Using email as username, so email_exists check is sufficient for username_exists.

        $user_data = array(
            'user_login' => $email,
            'user_email' => $email,
            'user_pass'  => $password,
            'role'       => self::ROLE_WORKER_STAFF,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        );

        $new_user_id = wp_insert_user( $user_data );

        if ( is_wp_error( $new_user_id ) ) {
            wp_send_json_error( array( 'message' => $new_user_id->get_error_message() ) );
        }

        // Assign to Business Owner
        update_user_meta( $new_user_id, self::META_KEY_OWNER_ID, $current_user_id );

        // Optionally, send a welcome email or notification to the new worker
        // wp_new_user_notification( $new_user_id, null, 'user' ); // 'user' to send to user, 'admin' to admin, 'both' for both

        wp_send_json_success( array( 'message' => __( 'Worker Staff created and assigned successfully.', 'mobooking' ) ) );
    }

    public function handle_ajax_change_worker_role() {
        $worker_user_id = isset($_POST['worker_user_id']) ? absint($_POST['worker_user_id']) : 0;
        check_ajax_referer( 'mobooking_change_worker_role_nonce_' . $worker_user_id, 'mobooking_change_role_nonce' );

        if ( ! current_user_can( self::CAP_MANAGE_WORKERS ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to manage workers.', 'mobooking' ) ] );
        }

        $new_role = isset($_POST['new_role']) ? sanitize_text_field($_POST['new_role']) : '';
        $current_owner_id = get_current_user_id();

        $allowed_worker_roles = [
            self::ROLE_WORKER_STAFF,
        ];

        if ( empty($worker_user_id) || empty($new_role) || !in_array($new_role, $allowed_worker_roles) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid data provided.', 'mobooking' ) ] );
        }

        // Verify worker belongs to this owner
        $actual_owner_id = get_user_meta( $worker_user_id, self::META_KEY_OWNER_ID, true );
        if ( (int) $actual_owner_id !== $current_owner_id ) {
            wp_send_json_error( [ 'message' => __( 'This worker is not associated with your business or you do not have permission to modify them.', 'mobooking' ) ] );
        }

        $worker_user = get_userdata( $worker_user_id );
        if ( ! $worker_user ) {
            wp_send_json_error( [ 'message' => __( 'Worker user not found.', 'mobooking' ) ] );
        }

        // Remove existing MoBooking worker roles before adding the new one
        foreach ( $allowed_worker_roles as $role_to_remove ) {
            $worker_user->remove_role( $role_to_remove );
        }

        // Add the new role
        $worker_user->add_role( $new_role );
        // $worker_user->set_role( $new_role ); // set_role replaces all roles, add_role is safer if they could have other non-plugin roles

        // For display in JS callback
        $all_roles_map = [
            // self::ROLE_WORKER_MANAGER => __( 'Manager', 'mobooking' ), // Removed
            self::ROLE_WORKER_STAFF   => __( 'Staff', 'mobooking' ),
            // self::ROLE_WORKER_VIEWER  => __( 'Viewer', 'mobooking' ), // Removed
        ];
        $new_role_display_name = isset($all_roles_map[$new_role]) ? $all_roles_map[$new_role] : $new_role;

        wp_send_json_success( [
            'message' => __( 'Worker role updated successfully.', 'mobooking' ),
            'new_role_display_name' => $new_role_display_name
        ] );
    }

    public function handle_ajax_revoke_worker_access() {
        $worker_user_id = isset($_POST['worker_user_id']) ? absint($_POST['worker_user_id']) : 0;
        check_ajax_referer( 'mobooking_revoke_worker_access_nonce_' . $worker_user_id, 'mobooking_revoke_access_nonce' );

        if ( ! current_user_can( self::CAP_MANAGE_WORKERS ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to manage workers.', 'mobooking' ) ] );
        }

        $current_owner_id = get_current_user_id();

        if ( empty($worker_user_id) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid worker ID.', 'mobooking' ) ] );
        }

        // Verify worker belongs to this owner
        $actual_owner_id = get_user_meta( $worker_user_id, self::META_KEY_OWNER_ID, true );
        if ( (int) $actual_owner_id !== $current_owner_id ) {
            wp_send_json_error( [ 'message' => __( 'This worker is not associated with your business or you do not have permission to modify them.', 'mobooking' ) ] );
        }

        $worker_user = get_userdata( $worker_user_id );
        if ( ! $worker_user ) {
            wp_send_json_error( [ 'message' => __( 'Worker user not found.', 'mobooking' ) ] );
        }

        // Remove MoBooking specific roles
        $mobooking_worker_roles = [
            self::ROLE_WORKER_STAFF,
        ];
        // Also remove manager/viewer roles if they somehow still exist on the user from a previous version
        $legacy_roles_to_check_and_remove = ['mobooking_worker_manager', 'mobooking_worker_viewer'];
        foreach ( array_merge($mobooking_worker_roles, $legacy_roles_to_check_and_remove) as $role_to_remove ) {
            $worker_user->remove_role( $role_to_remove );
        }

        // Delete the association meta key
        delete_user_meta( $worker_user_id, self::META_KEY_OWNER_ID );

        // If user has no other roles, set to subscriber
        if ( empty( $worker_user->roles ) ) {
            $worker_user->set_role( 'subscriber' );
        }

        wp_send_json_success( [ 'message' => __( 'Worker access revoked successfully. The user has been reverted to a standard subscriber role if they had no other roles.', 'mobooking' ) ] );
    }

    public function handle_ajax_send_invitation() {
        check_ajax_referer( 'mobooking_send_invitation_nonce', 'mobooking_nonce' );

        if ( ! current_user_can( self::CAP_MANAGE_WORKERS ) ) {
             wp_send_json_error( array( 'message' => __( 'You do not have permission to invite workers.', 'mobooking' ) ) );
        }

        $current_user_id = get_current_user_id();
        $worker_email = sanitize_email( $_POST['worker_email'] );
        $assigned_role = sanitize_text_field( $_POST['worker_role'] );

        // Validate email
        if ( empty( $worker_email ) || ! is_email( $worker_email ) ) {
            wp_send_json_error( array( 'message' => __( 'Please provide a valid email address for the worker.', 'mobooking' ) ) );
        }

        // Validate role: Ensure it is exactly ROLE_WORKER_STAFF
        if ( $assigned_role !== self::ROLE_WORKER_STAFF ) {
            wp_send_json_error( array( 'message' => __( 'Invalid role. Only \'Worker - Staff\' can be assigned at this time.', 'mobooking' ) ) );
        }

        // Check if email is already registered as any kind of user
        if ( email_exists( $worker_email ) ) {
             wp_send_json_error( array( 'message' => __( 'This email address is already registered on this site.', 'mobooking' ) ) );
        }

        $token = wp_generate_password( 32, false );
        $invitation_option_key = 'mobooking_invitation_' . $token;
        $expiration = 7 * DAY_IN_SECONDS; // 7 days

        $invitation_data = [
            'inviter_id'    => $current_user_id,
            'worker_email'  => $worker_email,
            'assigned_role' => $assigned_role,
            'timestamp'     => time(),
        ];

        // Store the invitation
        $stored = set_transient( $invitation_option_key, $invitation_data, $expiration );

        if ( ! $stored ) {
            wp_send_json_error( array( 'message' => __( 'Could not save invitation. Please try again.', 'mobooking' ) ) );
        }

        // Send email
        $registration_link = add_query_arg( 'invitation_token', $token, home_url( '/register/' ) ); // Assuming '/register/' is your registration page slug

        $inviter_user_data = get_userdata($current_user_id);
        $inviter_name = $inviter_user_data ? $inviter_user_data->display_name : get_bloginfo('name');

        $subject = sprintf( __( 'You have been invited to %s', 'mobooking' ), get_bloginfo( 'name' ) );
        $message = sprintf(
            __( 'Hi %s,', 'mobooking' ) . "\n\n" .
            __( 'You have been invited to join %s as a %s by %s.', 'mobooking' ) . "\n\n" .
            __( 'To accept this invitation and complete your registration, please click on the link below:', 'mobooking' ) . "\n" .
            '%s' . "\n\n" .
            __( 'This link is valid for 7 days.', 'mobooking' ) . "\n\n" .
            __( 'If you were not expecting this invitation, please ignore this email.', 'mobooking' ),
            $worker_email,
            get_bloginfo( 'name' ),
            ucfirst( str_replace( 'mobooking_worker_', '', $assigned_role ) ), // Make role name more friendly
            $inviter_name,
            $registration_link
        );

        $sent = wp_mail( $worker_email, $subject, $message );

        if ( $sent ) {
            wp_send_json_success( array( 'message' => __( 'Invitation sent successfully to ', 'mobooking' ) . $worker_email ) );
        } else {
            // If email fails, it's good to remove the transient to allow retrying without token collision,
            // or inform the user that the token was created but email failed.
            delete_transient( $invitation_option_key );
            wp_send_json_error( array( 'message' => __( 'Invitation created, but failed to send the invitation email. Please check your site\'s email configuration or try again.', 'mobooking' ) ) );
        }
        wp_die();
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

            // Check if this is a worker registration
            if ( isset( $_POST['inviter_id'] ) && isset( $_POST['role_to_assign'] ) ) {
                $inviter_id = absint( $_POST['inviter_id'] );
                $role_to_assign = sanitize_text_field( $_POST['role_to_assign'] );

                // Validate the role
                $worker_roles = [self::ROLE_WORKER_STAFF]; // Only staff can be assigned now
                if ( $inviter_id > 0 && in_array( $role_to_assign, $worker_roles ) ) {
                    // Ensure inviter is a business owner (optional, but good practice)
                    $inviter_user = get_userdata( $inviter_id );
                    if ( $inviter_user && in_array( self::ROLE_BUSINESS_OWNER, (array) $inviter_user->roles ) ) {
                        $user->set_role( $role_to_assign );
                        update_user_meta( $user_id, self::META_KEY_OWNER_ID, $inviter_id );
                        // TODO: Consider if workers need default settings initialized or a different set

                        // If registration was via token, delete the token
                        if ( isset( $_POST['invitation_token'] ) ) {
                            $invitation_token = sanitize_text_field( $_POST['invitation_token'] );
                            if ( ! empty( $invitation_token ) ) {
                                delete_transient( 'mobooking_invitation_' . $invitation_token );
                            }
                        }
                    } else {
                        // Handle error: inviter is not a business owner or doesn't exist
                        // For now, we'll let it fall through to business owner registration,
                        // or you could send an error.
                        // wp_delete_user( $user_id ); // Clean up created user
                        // wp_send_json_error( array( 'message' => __( 'Invalid inviter ID or inviter is not a business owner.', 'mobooking' ) ) );
                        // For this subtask, falling through to default owner role if inviter check fails.
                        $user->set_role( self::ROLE_BUSINESS_OWNER );
                        if (class_exists('MoBooking\Classes\Settings')) {
                            \MoBooking\Classes\Settings::initialize_default_settings( $user_id );
                        }
                    }
                } else {
                    // Invalid role or inviter_id, assign default business owner role
                    $user->set_role( self::ROLE_BUSINESS_OWNER );
                    if (class_exists('MoBooking\Classes\Settings')) {
                        \MoBooking\Classes\Settings::initialize_default_settings( $user_id );
                    }
                }
            } else {
                // Default registration: Business Owner
                $user->set_role( self::ROLE_BUSINESS_OWNER );
                // Initialize default settings for the new business owner
                if (class_exists('MoBooking\Classes\Settings')) {
                    \MoBooking\Classes\Settings::initialize_default_settings( $user_id );
                }
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
            // Check if the user has the correct role (any role that can access dashboard)
            $user = $user_signon;
            if ( $user->has_cap( self::ACCESS_MOBOOKING_DASHBOARD ) ) {
                wp_set_current_user($user->ID); // Ensure user is set for this session
                wp_set_auth_cookie($user->ID, $info['remember']);
                // TODO: Potentially redirect to a specific page based on role, or always to /dashboard/
                wp_send_json_success( array( 'message' => __( 'Login successful. Redirecting...', 'mobooking' ), 'redirect_url' => home_url('/dashboard/') ) );
            } else {
                // If login is valid but user doesn't have dashboard access capability
                wp_logout(); // Clears the cookie set by wp_signon
                wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions to access the dashboard.', 'mobooking' ) ) );
            }
        }
        wp_die();
    }

    // get_business_owner_id_for_worker, is_user_worker, is_user_business_owner can remain as they are
    // as they are utility functions for specific checks not directly tied to the capability system itself,
    // though their usage might be accompanied by capability checks elsewhere.

    public static function get_business_owner_id_for_worker(int $worker_user_id) {
        if ( ! $worker_user_id ) {
            return 0;
        }
        return get_user_meta( $worker_user_id, self::META_KEY_OWNER_ID, true );
    }

    public static function is_user_worker(int $user_id) {
        if ( ! $user_id ) {
            return false;
        }
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }
        $worker_roles = [
            self::ROLE_WORKER_STAFF,
        ];
        // Check for legacy roles as well if needed, but primary check is for current valid worker roles
        foreach ( $worker_roles as $role ) {
            if ( in_array( $role, (array) $user->roles ) ) {
                return true;
            }
        }
        return false;
    }

    public static function is_user_business_owner(int $user_id) {
        if ( ! $user_id ) {
            return false;
        }
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return false;
        }
        return in_array( self::ROLE_BUSINESS_OWNER, (array) $user->roles );
    }
}
