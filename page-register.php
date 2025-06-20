<?php
/**
 * Template Name: Tenant Registration Page
 *
 * @package MoBooking
 */

if ( is_user_logged_in() ) {
    wp_redirect( home_url( '/dashboard/' ) );
    exit;
}

get_header();

$invitation_token = null;
$invitation_data = null;
$worker_email = '';
$assigned_role = '';
$inviter_id = '';
$is_invitation = false;
$invitation_error = '';
$invitation_message = '';

if ( isset( $_GET['invitation_token'] ) ) {
    $invitation_token = sanitize_text_field( $_GET['invitation_token'] );
    if ( ! empty( $invitation_token ) ) {
        $transient_key = 'mobooking_invitation_' . $invitation_token;
        $invitation_data = get_transient( $transient_key );

        if ( $invitation_data && is_array( $invitation_data ) &&
             isset( $invitation_data['worker_email'], $invitation_data['assigned_role'], $invitation_data['inviter_id'] ) ) {

            $worker_email = sanitize_email( $invitation_data['worker_email'] );
            $assigned_role = sanitize_text_field( $invitation_data['assigned_role'] );
            $inviter_id = absint( $invitation_data['inviter_id'] );
            $is_invitation = true;

            // Try to get inviter's business name or display name
            $inviter_user = get_userdata($inviter_id);
            $inviter_display_name = $inviter_user ? $inviter_user->display_name : __('their business', 'mobooking');
            // A more specific business name could be stored in user meta if available.
            // For now, using display name or a generic term.

            $invitation_message = sprintf(
                esc_html__( 'You have been invited to join %s. Please complete your registration below. Your email is pre-filled.', 'mobooking' ),
                esc_html( $inviter_display_name )
            );

        } else {
            $invitation_error = esc_html__( 'Invalid or expired invitation token. Please proceed with normal registration or request a new invitation.', 'mobooking' );
            // Invalidate token so it's not accidentally used
            $invitation_token = null;
        }
    } else {
        $invitation_error = esc_html__( 'The invitation token is missing or invalid.', 'mobooking' );
        $invitation_token = null;
    }
}

?>
<main id="main" class="site-main">
    <div id="mobooking-register-form-container">
        <h2><?php
            if ($is_invitation) {
                esc_html_e( 'Complete Your Worker Registration', 'mobooking' );
            } else {
                esc_html_e( 'Register Your Business', 'mobooking' );
            }
        ?></h2>

        <?php if ( ! empty( $invitation_error ) ) : ?>
            <div class="mobooking-message error"><p><?php echo $invitation_error; ?></p></div>
        <?php endif; ?>

        <?php if ( ! empty( $invitation_message ) ) : ?>
            <div class="mobooking-message success"><p><?php echo $invitation_message; ?></p></div>
        <?php endif; ?>

        <form id="mobooking-register-form">
            <?php if ( $is_invitation && $invitation_token ) : ?>
                <input type="hidden" name="inviter_id" id="mobooking-inviter-id" value="<?php echo esc_attr( $inviter_id ); ?>" />
                <input type="hidden" name="assigned_role" id="mobooking-assigned-role" value="<?php echo esc_attr( $assigned_role ); ?>" />
                <input type="hidden" name="invitation_token" id="mobooking-invitation-token" value="<?php echo esc_attr( $invitation_token ); ?>" />
            <?php endif; ?>

            <p class="register-email">
                <label for="mobooking-user-email"><?php esc_html_e( 'Email Address', 'mobooking' ); ?></label>
                <input type="email" name="email" id="mobooking-user-email" class="input" value="<?php echo esc_attr( $worker_email ); ?>" required <?php if ( $is_invitation && !empty($worker_email) ) echo 'readonly'; ?> />
            </p>
            <p class="register-password">
                <label for="mobooking-user-pass"><?php esc_html_e( 'Password (min. 8 characters)', 'mobooking' ); ?></label>
                <input type="password" name="password" id="mobooking-user-pass" class="input" value="" required />
            </p>
            <p class="register-password-confirm">
                <label for="mobooking-user-pass-confirm"><?php esc_html_e( 'Confirm Password', 'mobooking' ); ?></label>
                <input type="password" name="password_confirm" id="mobooking-user-pass-confirm" class="input" value="" required />
            </p>
            <p class="register-submit">
                <input type="submit" name="wp-submit" id="mobooking-wp-submit-register" class="button button-primary" value="<?php esc_attr_e( 'Register', 'mobooking' ); ?>" />
            </p>
            <div id="mobooking-register-message" style="display:none;"></div>
        </form>
        <p>
            <?php esc_html_e( 'Already have an account?', 'mobooking' ); ?>
            <a href="<?php echo esc_url( home_url( '/login/' ) ); // Assuming a page with slug 'login' uses page-login.php ?>"><?php esc_html_e( 'Log In', 'mobooking' ); ?></a>
        </p>
    </div>
</main>
<?php
get_footer();
