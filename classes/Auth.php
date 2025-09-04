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
    const CAP_MANAGE_AVAILABILITY = 'mobooking_manage_availability'; // New capability
    const CAP_MANAGE_CUSTOMERS = 'mobooking_manage_customers'; // New capability for managing customers
    const CAP_VIEW_CUSTOMERS = 'mobooking_view_customers';     // New capability for viewing customers
    const CAP_ASSIGN_BOOKINGS = 'mobooking_assign_bookings'; // For assigning staff to bookings
    const CAP_UPDATE_OWN_BOOKING_STATUS = 'mobooking_update_own_booking_status'; // For staff to update status of their assigned bookings


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
                self::CAP_MANAGE_AVAILABILITY => true, // Assign to business owner
                self::CAP_MANAGE_CUSTOMERS => true,    // Assign to business owner
                self::CAP_VIEW_CUSTOMERS => true,      // Assign to business owner
                self::CAP_ASSIGN_BOOKINGS => true,     // Assign to business owner
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
                // self::CAP_MANAGE_BOOKINGS => true, // Removed, staff should not manage all bookings
                self::CAP_VIEW_BOOKINGS => true,   // Staff can view bookings (will be filtered to their own if a staff dashboard is made)
                self::CAP_UPDATE_OWN_BOOKING_STATUS => true, // Staff can update status of their own bookings
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
        add_action( 'wp_ajax_nopriv_mobooking_check_email_exists', [ $this, 'handle_check_email_exists_ajax' ] );
        add_action( 'wp_ajax_nopriv_mobooking_check_company_slug_exists', [ $this, 'handle_check_company_slug_exists_ajax' ] );
        add_action( 'wp_ajax_nopriv_mobooking_send_password_reset_link', [ $this, 'handle_send_password_reset_link_ajax' ] ); // New action
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
                // Send notification email to worker
                $worker_user = get_userdata($worker_user_id);
                if ($worker_user) {
                    $business_owner_user = get_userdata($current_owner_id);
                    $business_name = $business_owner_user ? $business_owner_user->display_name : get_bloginfo('name');

                    $subject = sprintf(__('Your Worker Account Details at %s Have Been Updated', 'mobooking'), get_bloginfo('name'));

                    $updated_fields_messages = [];
                    if (isset($update_args['first_name'])) {
                        $updated_fields_messages[] = sprintf(__('Your first name was updated to: %s', 'mobooking'), esc_html($update_args['first_name']));
                    }
                    if (isset($update_args['last_name'])) {
                        $updated_fields_messages[] = sprintf(__('Your last name was updated to: %s', 'mobooking'), esc_html($update_args['last_name']));
                    }

                    if (!empty($updated_fields_messages)) {
                        $message_lines = [
                            sprintf(__('Hi %s,', 'mobooking'), $worker_user->first_name ?: $worker_user->user_email),
                            '',
                            sprintf(__('Your account details at %s were recently updated by %s:', 'mobooking'), get_bloginfo('name'), esc_html($business_name)),
                            '',
                        ];
                        $message_lines = array_merge($message_lines, $updated_fields_messages);
                        $message_lines[] = '';
                        $message_lines[] = sprintf(__('If you did not expect this change or have concerns, please contact %s.', 'mobooking'), esc_html($business_name));
                        $message_lines[] = '';
                        $message_lines[] = sprintf(__('Regards,', 'mobooking'));
                        $message_lines[] = sprintf(__('The %s Team', 'mobooking'), get_bloginfo('name'));

                        $message = implode("\r\n", $message_lines);

                        if (!wp_mail($worker_user->user_email, $subject, $message)) {
                            error_log("MoBooking: Failed to send account update email to worker: " . $worker_user->user_email);
                        }
                    }
                }
                wp_send_json_success( array( 'message' => __( 'Worker details updated successfully. The worker has been notified if changes were made.', 'mobooking' ) ) );
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

        // Send custom email notification to the new worker
        $business_owner_user = get_userdata($current_user_id);
        $business_name = $business_owner_user ? $business_owner_user->display_name : get_bloginfo('name');

        $subject = sprintf(__('Your Worker Account at %s has been Created', 'mobooking'), get_bloginfo('name'));

        $message_lines = [
            sprintf(__('Hi %s,', 'mobooking'), $first_name ?: $email),
            '',
            sprintf(__('A worker account has been created for you at %s by %s.', 'mobooking'), get_bloginfo('name'), esc_html($business_name)),
            '',
            __('Your login details are:', 'mobooking'),
            '- ' . __('Email:', 'mobooking') . ' ' . $email,
            '- ' . __('Password:', 'mobooking') . ' ' . esc_html($password) . ' (This was set by ' . esc_html($business_name) . ')',
            '',
            sprintf(__('You can log in here: %s', 'mobooking'), esc_url(home_url('/login/'))), // Assuming /login/ is the custom login page
            '',
            __('We recommend changing your password after your first login for security reasons.', 'mobooking'),
            '',
            sprintf(__('Regards,', 'mobooking')),
            sprintf(__('The %s Team', 'mobooking'), get_bloginfo('name')),
        ];
        $message = implode("\r\n", $message_lines);

        if (!wp_mail($email, $subject, $message)) {
            error_log("MoBooking: Failed to send account creation email to new worker: " . $email);
            // Don't fail the whole process, but log the error.
            // The main success message below will still be sent to the admin.
        }

        wp_send_json_success( array( 'message' => __( 'Worker Staff created and assigned successfully. They have been notified by email.', 'mobooking' ) ) );
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

        $notifications = new Notifications();
        $sent = $notifications->send_invitation_email($worker_email, $assigned_role, $inviter_name, $registration_link);

        if ( $sent ) {
            wp_send_json_success( array( 'message' => __( 'Invitation sent successfully to ', 'mobooking' ) . $worker_email ) );
        } else {
            // If email fails, it's good to remove the transient to allow retrying without token collision,
            // or inform the user that the token was created but email failed.
            error_log('MoBooking: wp_mail failed to send invitation to ' . $worker_email . '. Transient mobooking_invitation_ ' . $token . ' deleted.');
            delete_transient( $invitation_option_key );
            wp_send_json_error( array( 'message' => __( 'Invitation created, but failed to send the invitation email. Please check your site\'s email configuration or try again.', 'mobooking' ) ) );
        }
        wp_die();
    }

    public function handle_check_email_exists_ajax() {
        // No nonce check needed for this read-only, public-facing check,
        // but consider adding one if you want to restrict requests.
        // check_ajax_referer('mobooking_check_email_nonce', 'nonce');

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => __('Invalid email format provided for check.', 'mobooking')]);
            wp_die();
        }

        if (email_exists($email)) {
            wp_send_json_success(['exists' => true, 'message' => __('This email is already registered.', 'mobooking')]);
        } else {
            wp_send_json_success(['exists' => false, 'message' => __('This email is available.', 'mobooking')]);
        }
        wp_die();
    }

// Enhanced handle_ajax_registration method for classes/Auth.php

private function setup_invited_worker(\WP_User $user, array $post_data): array {
    error_log('MoBooking: Processing invitation flow for user ID: ' . $user->ID);

    $inviter_id = intval($post_data['inviter_id']);
    $role_to_assign = sanitize_text_field($post_data['role_to_assign']);
    $invitation_token = sanitize_text_field($post_data['invitation_token']);

    // Validate invitation
    $transient_key = 'mobooking_invitation_' . $invitation_token;
    $invitation_data = get_transient($transient_key);

    if (!$invitation_data || !is_array($invitation_data) ||
        $invitation_data['inviter_id'] != $inviter_id ||
        $invitation_data['worker_email'] !== $user->user_email ||
        $invitation_data['assigned_role'] !== $role_to_assign) {

        // Throwing an exception will trigger the cleanup in the main method's catch block
        throw new \Exception(__('Invalid or expired invitation. Please request a new invitation.', 'mobooking'));
    }

    // Only ROLE_WORKER_STAFF is assignable in this flow.
    if ($role_to_assign === self::ROLE_WORKER_STAFF) {
        $user->set_role($role_to_assign);
        update_user_meta($user->ID, self::META_KEY_OWNER_ID, $inviter_id);
        delete_transient($transient_key);

        return [
            'message' => __('Your worker account has been successfully created.', 'mobooking'),
            'redirect_url' => home_url('/dashboard/'),
        ];
    } else {
        // This case should ideally not be reached if frontend is correct, but it's a safeguard.
        throw new \Exception(__('Invalid role assignment. Please contact support.', 'mobooking'));
    }
}

private function setup_new_business_owner(\WP_User $user, string $company_name): array {
    error_log('MoBooking: Processing business owner registration for user ID: ' . $user->ID);

    $user->set_role(self::ROLE_BUSINESS_OWNER);
    update_user_meta($user->ID, 'mobooking_company_name', $company_name);

    error_log('MoBooking: Business owner role assigned and company name saved');

    // Generate and save unique business slug
    if (!empty($company_name)) {
        error_log('MoBooking: Generating business slug');

        if (class_exists('MoBooking\Classes\Settings') && class_exists('MoBooking\Classes\Routes\BookingFormRouter')) {
            // Initialize settings manager
            if (!isset($GLOBALS['mobooking_settings_manager'])) {
                $GLOBALS['mobooking_settings_manager'] = new \MoBooking\Classes\Settings();
            }
            $settings_manager = $GLOBALS['mobooking_settings_manager'];

            // Generate unique slug
            $base_slug = sanitize_title($company_name);
            $final_slug = $base_slug;
            $counter = 1;

            global $wpdb;
            $settings_table = \MoBooking\Classes\Database::get_table_name('tenant_settings');

            if (empty($settings_table)) {
                throw new \Exception('Database settings table name could not be retrieved.');
            }

            $slug_is_taken = true;
            error_log("MoBooking: Starting slug generation for base: {$base_slug}");

            while ($slug_is_taken) {
                $query = $wpdb->prepare(
                    "SELECT user_id FROM {$settings_table} WHERE setting_name = %s AND setting_value = %s",
                    'bf_business_slug',
                    $final_slug
                );
                $existing_user_id = $wpdb->get_var($query);

                if ($existing_user_id === null) {
                    $slug_is_taken = false;
                } else {
                    $counter++;
                    $final_slug = $base_slug . '-' . $counter;
                    error_log("MoBooking: Slug collision detected. Trying next slug: {$final_slug}");
                    if ($counter > 50) {
                        error_log("MoBooking: Slug generation loop exceeded 50 iterations. Breaking loop.");
                        $final_slug .= '-' . wp_rand(100, 999);
                        break;
                    }
                }
            }

            $settings_manager->update_setting($user->ID, 'bf_business_slug', $final_slug);
            error_log("MoBooking: Business slug created and saved: {$final_slug}");
        }
    }

    // Initialize default settings for new business owner
    error_log('MoBooking: Initializing default settings');
    if (class_exists('MoBooking\Classes\Settings') && method_exists('MoBooking\Classes\Settings', 'initialize_default_settings')) {
        \MoBooking\Classes\Settings::initialize_default_settings($user->ID);
        error_log('MoBooking: Default settings initialized successfully');
    }

    return [
        'message' => sprintf(
            __('Welcome to %s! Your business account has been successfully created.', 'mobooking'),
            get_bloginfo('name')
        ),
        'redirect_url' => home_url('/dashboard/'),
    ];
}


public function handle_ajax_registration() {
    error_log('MoBooking: Registration process started');
    $user_id = null; // Initialize user_id to null

    try {
        // 1. Verify nonce
        if (!check_ajax_referer(self::REGISTER_NONCE_ACTION, 'nonce', false)) {
            throw new \Exception(__('Security check failed. Please refresh the page and try again.', 'mobooking'));
        }

        // Verify reCAPTCHA
        $recaptcha_token = isset($_POST['recaptcha_token']) ? $_POST['recaptcha_token'] : null;
        // IMPORTANT: The secret key should be stored securely, e.g., in wp-config.php or WordPress options, not hardcoded.
        $recaptcha_secret_key = defined('MOBOOKING_RECAPTCHA_SECRET_KEY') ? MOBOOKING_RECAPTCHA_SECRET_KEY : null;

        // Only verify if the token and a secret key are present.
        if ($recaptcha_token && $recaptcha_secret_key) {
            error_log('MoBooking: Verifying reCAPTCHA token.');
            $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret'   => $recaptcha_secret_key,
                    'response' => $recaptcha_token,
                    'remoteip' => $_SERVER['REMOTE_ADDR'],
                ],
            ]);

            if (is_wp_error($response)) {
                throw new \Exception(__('Could not contact reCAPTCHA service.', 'mobooking'));
            }

            $response_body = wp_remote_retrieve_body($response);
            $recaptcha_data = json_decode($response_body);

            if (!$recaptcha_data || !isset($recaptcha_data->success)) {
                throw new \Exception(__('Invalid response from reCAPTCHA service.', 'mobooking'));
            }

            // 0.5 is a common threshold for reCAPTCHA v3.
            if ($recaptcha_data->success !== true || $recaptcha_data->score < 0.5) {
                error_log('MoBooking: reCAPTCHA verification failed. Score: ' . ($recaptcha_data->score ?? 'N/A'));
                throw new \Exception(__('Human verification failed. Please try again.', 'mobooking'));
            }

            error_log('MoBooking: reCAPTCHA verification successful. Score: ' . $recaptcha_data->score);
        } elseif (defined('MOBOOKING_RECAPTCHA_SECRET_KEY') && !empty(MOBOOKING_RECAPTCHA_SECRET_KEY)) {
            // If reCAPTCHA is configured on the backend but no token was sent, it's an error.
            throw new \Exception(__('Human verification token not received. Please refresh the page.', 'mobooking'));
        }

        // 2. Sanitize and validate input data
        $first_name = isset($_POST['first_name']) ? sanitize_text_field(trim($_POST['first_name'])) : '';
        $last_name = isset($_POST['last_name']) ? sanitize_text_field(trim($_POST['last_name'])) : '';
        $email = isset($_POST['email']) ? sanitize_email(trim($_POST['email'])) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
        $company_name = isset($_POST['company_name']) ? sanitize_text_field(trim($_POST['company_name'])) : '';
        $is_invitation_flow = isset($_POST['inviter_id']) && isset($_POST['role_to_assign']);

        error_log("MoBooking: Registration attempt for email: {$email}");

        $errors = [];
        if (empty($first_name)) $errors[] = __('First name is required.', 'mobooking');
        if (empty($last_name)) $errors[] = __('Last name is required.', 'mobooking');
        if (empty($email) || !is_email($email)) $errors[] = __('A valid email address is required.', 'mobooking');
        if (empty($password) || strlen($password) < 8) $errors[] = __('Password must be at least 8 characters long.', 'mobooking');
        if ($password !== $password_confirm) $errors[] = __('Passwords do not match.', 'mobooking');
        if (!$is_invitation_flow && empty($company_name)) $errors[] = __('Company name is required for business registration.', 'mobooking');
        if (!empty($email) && (username_exists($email) || email_exists($email))) $errors[] = __('This email is already registered. Please use a different email or try logging in.', 'mobooking');

        if (!empty($errors)) {
            throw new \Exception(implode('<br>', $errors));
        }

        // 3. Create the user
        error_log('MoBooking: Creating WordPress user');
        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            throw new \Exception(sprintf(__('Account creation failed: %s', 'mobooking'), $user_id->get_error_message()));
        }

        error_log("MoBooking: WordPress user created successfully with ID: {$user_id}");
        $user = new \WP_User($user_id);

        // 4. Update basic user profile
        $display_name = trim($first_name . ' ' . $last_name);
        wp_update_user(['ID' => $user_id, 'first_name' => $first_name, 'last_name' => $last_name, 'display_name' => $display_name]);
        error_log('MoBooking: User profile data updated successfully');

        // 5. Setup user based on registration type (Business Owner or Worker)
        if ($is_invitation_flow) {
            $result = $this->setup_invited_worker($user, $_POST);
        } else {
            $result = $this->setup_new_business_owner($user, $company_name);
        }

        // 6. Log the new user in
        error_log('MoBooking: Logging user in and setting auth cookie');
        wp_set_current_user($user_id, $user->user_login);
        wp_set_auth_cookie($user_id, true, is_ssl());
        do_action('wp_login', $user->user_login, $user);
        error_log("MoBooking: User {$user_id} logged in, wp_login action hook fired.");

        // 7. Send welcome email (only for business owners)
        if (!$is_invitation_flow) {
            $this->send_welcome_email($user_id, $display_name);
        }

        // 8. Send success response
        error_log(sprintf('MoBooking: Successful registration for %s (%s) - User ID: %d, Type: %s', $display_name, $email, $user_id, $is_invitation_flow ? 'Worker' : 'Business Owner'));
        wp_send_json_success([
            'message' => $result['message'],
            'redirect_url' => $result['redirect_url'],
            'user_data' => ['id' => $user_id, 'name' => $display_name, 'email' => $email, 'type' => $is_invitation_flow ? 'worker' : 'business_owner']
        ]);

    } catch (\Exception $e) {
        // Centralized error handling and cleanup
        if ($user_id && is_int($user_id)) {
            wp_delete_user($user_id);
            error_log("MoBooking: Cleaned up user {$user_id} due to registration failure: " . $e->getMessage());
        }
        error_log('MoBooking Registration Error: ' . $e->getMessage());
        
        wp_send_json_error(['message' => $e->getMessage()]);
    }

    wp_die();
}

    /**
     * Send welcome email to new business owner
     * 
     * @param int $user_id
     * @param string $display_name
     */
    private function send_welcome_email( $user_id, $display_name ) {
        try {
            $notifications = new Notifications();
            $notifications->send_welcome_email($user_id, $display_name);
        } catch ( Exception $e ) {
            error_log( 'MoBooking: Welcome email error - ' . $e->getMessage() );
        }
    }

    public function handle_check_company_slug_exists_ajax() {
        // Consider adding a nonce check for security if desired
        // check_ajax_referer('mobooking_check_slug_nonce_action', 'nonce');

        $company_name = isset($_POST['company_name']) ? sanitize_text_field(trim($_POST['company_name'])) : '';

        if (empty($company_name)) {
            wp_send_json_error(['message' => __('Company name not provided for check.', 'mobooking')]);
            wp_die();
        }

        if (!class_exists('MoBooking\Classes\Routes\BookingFormRouter')) {
            wp_send_json_error(['message' => __('System error: Router class not found.', 'mobooking')]);
            wp_die();
        }

        $base_slug = sanitize_title($company_name);
        $original_slug_check_user_id = \MoBooking\Classes\Routes\BookingFormRouter::get_user_id_by_slug($base_slug);

        if ($original_slug_check_user_id !== null) {
            // Slug already exists, return a hard error message.
            wp_send_json_success([
                'exists' => true,
                'message' => __('This company name is already taken. Please choose another.', 'mobooking')
            ]);
        } else {
            wp_send_json_success([
                'exists' => false,
                'message' => __('This company name looks available!', 'mobooking'),
                'slug_preview' => $base_slug
            ]);
        }
        wp_die();
    }

    public function handle_send_password_reset_link_ajax() {
        // It's good practice to use a nonce here, even for "public" forms, to prevent abuse.
        check_ajax_referer('mobooking_forgot_password_nonce_action', 'nonce');

        $email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => __('Please provide a valid email address.', 'mobooking')]);
            wp_die();
        }

        $user_data = get_user_by('email', $email);

        if (empty($user_data)) {
            // Email does not exist, but send a generic success message to prevent user enumeration.
            wp_send_json_success(['message' => __('If an account with that email exists, a password reset link has been sent.', 'mobooking')]);
            wp_die();
        }

        // Generate password reset key
        $key = get_password_reset_key($user_data);
        if (is_wp_error($key)) {
            wp_send_json_error(['message' => __('Error generating reset key. Please try again later.', 'mobooking')]);
            wp_die();
        }

        // Construct the password reset URL.
        // This uses the standard WordPress reset mechanism.
        $reset_link = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_data->user_login), 'login');

        // Prepare email content
        $message = __('Someone has requested a password reset for the following account:') . "\r\n\r\n";
        $message .= sprintf(__('Site Name: %s'), get_bloginfo('name')) . "\r\n\r\n";
        $message .= sprintf(__('Username: %s'), $user_data->user_login) . "\r\n\r\n";
        $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
        $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
        $message .= $reset_link . "\r\n";

        $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $title = sprintf(__('[%s] Password Reset'), $blogname);

        // Send the email
        if (wp_mail($email, $title, $message)) {
            wp_send_json_success(['message' => __('If an account with that email exists, a password reset link has been sent.', 'mobooking')]);
        } else {
            wp_send_json_error(['message' => __('The email could not be sent. Please try again later or contact an administrator.', 'mobooking')]);
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

    public static function get_effective_tenant_id_for_user(int $user_id) {
        if (self::is_user_worker($user_id)) {
            return self::get_business_owner_id_for_worker($user_id);
        }
        return $user_id;
    }
}
