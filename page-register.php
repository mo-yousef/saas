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

            $inviter_user = get_userdata($inviter_id);
            $inviter_display_name = $inviter_user ? $inviter_user->display_name : __('their business', 'mobooking');

            $invitation_message = sprintf(
                esc_html__( 'You have been invited to join %s. Please complete your registration below. Your email is pre-filled.', 'mobooking' ),
                esc_html( $inviter_display_name )
            );

        } else {
            $invitation_error = esc_html__( 'Invalid or expired invitation token. Please proceed with normal registration or request a new invitation.', 'mobooking' );
            $invitation_token = null;
        }
    } else {
        $invitation_error = esc_html__( 'The invitation token is missing or invalid.', 'mobooking' );
        $invitation_token = null;
    }
}

?>
<main id="main" class="site-main">
    <div id="mobooking-register-form-container" class="mobooking-auth-form-wrapper">
        <style>
            .mobooking-auth-form-wrapper { max-width: 500px; margin: 2rem auto; padding: 2rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); }
            /* .mobooking-register-step { animation: fadeIn 0.5s; }
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } } */
            .mobooking-register-step {
                opacity: 0;
                transform: translateY(10px);
                transition: opacity 0.3s ease-out, transform 0.3s ease-out;
                display: none;
                visibility: hidden;
            }
            .mobooking-register-step.active {
                opacity: 1;
                transform: translateY(0);
                display: block;
                visibility: visible;
            }
            .mobooking-register-step h3 { margin-top: 0; margin-bottom: 1.5rem; font-size: 1.25rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem; }
            .form-navigation { margin-top: 1.5rem; display: flex; justify-content: space-between; }
            .form-navigation .button { min-width: 100px; }
            .mobooking-progress-step { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 4px; background-color: #f8fafc; color: #64748b; transition: all 0.3s ease; }
            .mobooking-progress-step.active { background-color: #3b82f6; color: white; border-color: #3b82f6; font-weight: 500; }
            #mobooking-register-message.error { color: #dc2626; background-color: #fee2e2; border: 1px solid #ef4444; padding: 10px; border-radius: 4px;}
            #mobooking-register-message.success { color: #166534; background-color: #dcfce7; border: 1px solid #22c55e; padding: 10px; border-radius: 4px;}
        </style>
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

        <div id="mobooking-progress-bar" style="display: flex; justify-content: space-between; margin-bottom: 20px;">
            <div class="mobooking-progress-step active" data-step="1"><?php esc_html_e('Account', 'mobooking'); ?></div>
            <div class="mobooking-progress-step" data-step="2"><?php esc_html_e('Personal', 'mobooking'); ?></div>
            <div class="mobooking-progress-step" data-step="3"><?php esc_html_e('Business', 'mobooking'); ?></div>
        </div>

        <form id="mobooking-register-form">
            <?php if ( $is_invitation && $invitation_token ) : ?>
                <input type="hidden" name="inviter_id" id="mobooking-inviter-id" value="<?php echo esc_attr( $inviter_id ); ?>" />
                <input type="hidden" name="assigned_role" id="mobooking-assigned-role" value="<?php echo esc_attr( $assigned_role ); ?>" />
                <input type="hidden" name="invitation_token" id="mobooking-invitation-token" value="<?php echo esc_attr( $invitation_token ); ?>" />
            <?php endif; ?>

            <!-- Step 1: Account Setup -->
            <div id="mobooking-register-step-1" class="mobooking-register-step active">
                <h3><?php esc_html_e( 'Step 1: Account Setup', 'mobooking' ); ?></h3>
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
                <div class="form-navigation">
                    <button type="button" id="mobooking-step-1-next" class="button button-primary"><?php esc_html_e( 'Next', 'mobooking' ); ?></button>
                </div>
            </div>

            <!-- Step 2: Personal Details -->
            <div id="mobooking-register-step-2" class="mobooking-register-step" style="display:none;">
                <h3><?php esc_html_e( 'Step 2: Personal Details', 'mobooking' ); ?></h3>
                <p class="register-first-name">
                    <label for="mobooking-first-name"><?php esc_html_e( 'First Name', 'mobooking' ); ?></label>
                    <input type="text" name="first_name" id="mobooking-first-name" class="input" value="" required />
                </p>
                <p class="register-last-name">
                    <label for="mobooking-last-name"><?php esc_html_e( 'Last Name', 'mobooking' ); ?></label>
                    <input type="text" name="last_name" id="mobooking-last-name" class="input" value="" required />
                </p>
                <div class="form-navigation">
                    <button type="button" id="mobooking-step-2-prev" class="button"><?php esc_html_e( 'Previous', 'mobooking' ); ?></button>
                    <button type="button" id="mobooking-step-2-next" class="button button-primary"><?php esc_html_e( 'Next', 'mobooking' ); ?></button>
                </div>
            </div>

            <!-- Step 3: Business Information -->
            <div id="mobooking-register-step-3" class="mobooking-register-step" style="display:none;">
                <h3><?php esc_html_e( 'Step 3: Business Information', 'mobooking' ); ?></h3>
                <p class="register-company-name">
                    <label for="mobooking-company-name"><?php esc_html_e( 'Company Name', 'mobooking' ); ?></label>
                    <input type="text" name="company_name" id="mobooking-company-name" class="input" value="" required />
                    <small><?php esc_html_e( 'This will be used to generate your unique business URL (slug).', 'mobooking' ); ?></small>
                </p>
                <div class="form-navigation">
                    <button type="button" id="mobooking-step-3-prev" class="button"><?php esc_html_e( 'Previous', 'mobooking' ); ?></button>
                    <input type="submit" name="wp-submit" id="mobooking-wp-submit-register" class="button button-primary" value="<?php esc_attr_e( 'Register', 'mobooking' ); ?>" />
                </div>
            </div>

            <div id="mobooking-register-message" style="display:none; margin-top: 15px;"></div>
        </form>
        <p style="margin-top: 20px;">
            <?php esc_html_e( 'Already have an account?', 'mobooking' ); ?>
            <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php esc_html_e( 'Log In', 'mobooking' ); ?></a>
        </p>
    </div>
</main>

<style>
    .mobooking-auth-form-wrapper { max-width: 500px; margin: 2rem auto; padding: 2rem; background: #fff; border: 1px solid #e2e8f0; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); }
    .mobooking-register-step { animation: fadeIn 0.5s; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    .mobooking-register-step h3 { margin-top: 0; margin-bottom: 1.5rem; font-size: 1.25rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.75rem; }
    .form-navigation { margin-top: 1.5rem; display: flex; justify-content: space-between; }
    .form-navigation .button { min-width: 100px; }
    .mobooking-progress-step { padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 4px; background-color: #f8fafc; color: #64748b; transition: all 0.3s ease; }
    .mobooking-progress-step.active { background-color: #3b82f6; color: white; border-color: #3b82f6; font-weight: 500; }
    #mobooking-register-message.error { color: #dc2626; background-color: #fee2e2; border: 1px solid #ef4444; padding: 10px; border-radius: 4px;}
    #mobooking-register-message.success { color: #166534; background-color: #dcfce7; border: 1px solid #22c55e; padding: 10px; border-radius: 4px;}
</style>

<?php
get_footer();
