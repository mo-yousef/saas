<?php
/**
 * Template Name: Tenant Registration Page
 *
 * @package NORDBOOKING
 */

if ( is_user_logged_in() ) {
    wp_redirect( home_url( '/dashboard/' ) );
    exit;
}

get_header(); // This will be hidden by CSS in auth-pages.css for these templates

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
        $transient_key = 'nordbooking_invitation_' . $invitation_token;
        $invitation_data = get_transient( $transient_key );

        if ( $invitation_data && is_array( $invitation_data ) &&
             isset( $invitation_data['worker_email'], $invitation_data['assigned_role'], $invitation_data['inviter_id'] ) ) {

            $worker_email = sanitize_email( $invitation_data['worker_email'] );
            $assigned_role = sanitize_text_field( $invitation_data['assigned_role'] );
            $inviter_id = absint( $invitation_data['inviter_id'] );
            $is_invitation = true;

            $inviter_user = get_userdata($inviter_id);
            $inviter_display_name = $inviter_user ? $inviter_user->display_name : __('their business', 'NORDBOOKING');

            $invitation_message = sprintf(
                esc_html__( 'You have been invited to join %s. Please complete your registration below. Your email is pre-filled.', 'NORDBOOKING' ),
                esc_html( $inviter_display_name )
            );

        } else {
            $invitation_error = esc_html__( 'Invalid or expired invitation token. Please proceed with normal registration or request a new invitation.', 'NORDBOOKING' );
            $invitation_token = null;
        }
    } else {
        $invitation_error = esc_html__( 'The invitation token is missing or invalid.', 'NORDBOOKING' );
        $invitation_token = null;
    }
}
?>

<div class="nbk-auth-page-container">
    <div class="nbk-auth-grid">

        <div class="nbk-auth-form-column">
            <main id="main" class="site-main">
                <div id="NORDBOOKING-register-form-container" class="NORDBOOKING-auth-form-wrapper">
                    <h2><?php
                        if ($is_invitation) {
                            esc_html_e( 'Complete Your Worker Registration', 'NORDBOOKING' );
                        } else {
                            esc_html_e( 'Register Your Business', 'NORDBOOKING' );
                        }
                    ?></h2>

                    <?php if ( ! empty( $invitation_error ) ) : ?>
                        <div class="NORDBOOKING-message error"><p><?php echo $invitation_error; ?></p></div>
                    <?php endif; ?>

                    <?php if ( ! empty( $invitation_message ) ) : ?>
                        <div class="NORDBOOKING-message success"><p><?php echo $invitation_message; ?></p></div>
                    <?php endif; ?>

                    <?php 
                    // Show plan selection message
                    $selected_plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : '';
                    if (!empty($selected_plan) && in_array($selected_plan, ['free', 'pro'])) :
                        $plan_names = [
                            'free' => '7-Day Free Trial',
                            'pro' => 'Pro Plan'
                        ];
                        $plan_name = $plan_names[$selected_plan];
                        $plan_message = $selected_plan === 'free' 
                            ? 'You have selected the %s. Complete your registration to start your free trial with full Pro access!'
                            : 'You have selected the %s. Complete your registration to get started!';
                    ?>
                        <div class="NORDBOOKING-message info">
                            <p><?php echo sprintf(esc_html__($plan_message, 'NORDBOOKING'), '<strong>' . esc_html($plan_name) . '</strong>'); ?></p>
                        </div>
                    <?php endif; ?>

                    <form id="NORDBOOKING-register-form">
                        <?php if ( $is_invitation && $invitation_token ) : ?>
                            <input type="hidden" name="inviter_id" id="NORDBOOKING-inviter-id" value="<?php echo esc_attr( $inviter_id ); ?>" />
                            <input type="hidden" name="assigned_role" id="NORDBOOKING-assigned-role" value="<?php echo esc_attr( $assigned_role ); ?>" />
                            <input type="hidden" name="invitation_token" id="NORDBOOKING-invitation-token" value="<?php echo esc_attr( $invitation_token ); ?>" />
                        <?php endif; ?>
                        
                        <?php 
                        // Get plan parameter from URL
                        $selected_plan = isset($_GET['plan']) ? sanitize_text_field($_GET['plan']) : '';
                        if (!empty($selected_plan) && in_array($selected_plan, ['free', 'pro'])) : ?>
                            <input type="hidden" name="plan" id="NORDBOOKING-plan" value="<?php echo esc_attr( $selected_plan ); ?>" />
                        <?php endif; ?>

                        <h3><?php esc_html_e( 'Personal Information', 'NORDBOOKING' ); ?></h3>
                        <div class="form-row">
                            <p class="register-first-name form-group-half">
                                <label for="NORDBOOKING-first-name"><?php esc_html_e( 'First Name', 'NORDBOOKING' ); ?></label>
                                <input type="text" name="first_name" id="NORDBOOKING-first-name" class="input" value="" required />
                            </p>
                            <p class="register-last-name form-group-half">
                                <label for="NORDBOOKING-last-name"><?php esc_html_e( 'Last Name', 'NORDBOOKING' ); ?></label>
                                <input type="text" name="last_name" id="NORDBOOKING-last-name" class="input" value="" required />
                            </p>
                        </div>
                        <p class="register-email">
                            <label for="NORDBOOKING-user-email"><?php esc_html_e( 'Email Address', 'NORDBOOKING' ); ?></label>
                            <input type="email" name="email" id="NORDBOOKING-user-email" class="input" value="<?php echo esc_attr( $worker_email ); ?>" required <?php if ( $is_invitation && !empty($worker_email) ) echo 'readonly'; ?> />
                        </p>
                        <p class="register-password">
                            <label for="NORDBOOKING-user-pass"><?php esc_html_e( 'Password (min. 8 characters)', 'NORDBOOKING' ); ?></label>
                            <input type="password" name="password" id="NORDBOOKING-user-pass" class="input" value="" required />
                        </p>
                        <p class="register-password-confirm">
                            <label for="NORDBOOKING-user-pass-confirm"><?php esc_html_e( 'Confirm Password', 'NORDBOOKING' ); ?></label>
                            <input type="password" name="password_confirm" id="NORDBOOKING-user-pass-confirm" class="input" value="" required />
                        </p>

                        <?php if ( !$is_invitation ): ?>
                            <h3 style="margin-top: 2rem;"><?php esc_html_e( 'Business Information', 'NORDBOOKING' ); ?></h3>
                            <p class="register-company-name">
                                <label for="NORDBOOKING-company-name"><?php esc_html_e( 'Company Name', 'NORDBOOKING' ); ?></label>
                                <input type="text" name="company_name" id="NORDBOOKING-company-name" class="input" value="" />
                                <small><?php esc_html_e( 'This will be used to generate your unique business URL (slug).', 'NORDBOOKING' ); ?></small>
                            </p>
                        <?php else: ?>
                            <input type="hidden" name="company_name" id="NORDBOOKING-company-name" value="" />
                        <?php endif; ?>

                        <!-- Placeholder for reCAPTCHA -->
                        <div id="NORDBOOKING-recaptcha-container" style="margin-top: 1rem;"></div>

                        <div id="NORDBOOKING-register-message" style="display:none; margin-top: 15px;"></div>

                        <div class="form-navigation" style="margin-top: 1.5rem;">
                            <input type="submit" name="wp-submit" id="NORDBOOKING-wp-submit-register" class="button button-primary" value="<?php esc_attr_e( 'Register', 'NORDBOOKING' ); ?>" />
                        </div>
                    </form>
                    <div class="NORDBOOKING-auth-links">
                        <p>
                            <?php esc_html_e( 'Already have an account?', 'NORDBOOKING' ); ?>
                            <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php esc_html_e( 'Log In', 'NORDBOOKING' ); ?></a>
                        </p>
                    </div>
                    <a href="/" class="link hyperlink has-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 8 8 12 12 16"></polyline><line x1="16" y1="12" x2="8" y2="12"></line></svg> Back to home</a>
                </div>
            </main>
        </div>
        <div class="nbk-auth-image-column">
            <div class="placeholder-content">
                <img class="nbk-fade-in" src="<?php echo get_template_directory_uri(); ?>/assets/images/hero-mockup.png" alt="Register Your Business">
            </div>
        </div>
    </div>
</div>

<?php
get_footer(); // This will be hidden by CSS
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('NORDBOOKING-register-form');
    const submitButton = document.getElementById('NORDBOOKING-wp-submit-register');
    const messageContainer = document.getElementById('NORDBOOKING-register-message');

    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Disable submit button
            submitButton.disabled = true;
            submitButton.value = 'Registering...';
            
            // Clear previous messages
            messageContainer.style.display = 'none';
            messageContainer.innerHTML = '';
            
            // Collect form data
            const formData = new FormData();
            formData.append('action', 'nordbooking_register');
            formData.append('nonce', '<?php echo wp_create_nonce('nordbooking_register_nonce'); ?>');
            formData.append('first_name', document.getElementById('NORDBOOKING-first-name').value);
            formData.append('last_name', document.getElementById('NORDBOOKING-last-name').value);
            formData.append('email', document.getElementById('NORDBOOKING-user-email').value);
            formData.append('password', document.getElementById('NORDBOOKING-user-pass').value);
            formData.append('password_confirm', document.getElementById('NORDBOOKING-user-pass-confirm').value);
            formData.append('company_name', document.getElementById('NORDBOOKING-company-name').value);
            
            // Add plan if selected
            const planInput = document.getElementById('NORDBOOKING-plan');
            if (planInput) {
                formData.append('plan', planInput.value);
            }
            
            // Add invitation data if present
            const inviterIdInput = document.getElementById('NORDBOOKING-inviter-id');
            const assignedRoleInput = document.getElementById('NORDBOOKING-assigned-role');
            const invitationTokenInput = document.getElementById('NORDBOOKING-invitation-token');
            
            if (inviterIdInput) formData.append('inviter_id', inviterIdInput.value);
            if (assignedRoleInput) formData.append('assigned_role', assignedRoleInput.value);
            if (invitationTokenInput) formData.append('invitation_token', invitationTokenInput.value);
            
            // Submit form
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    messageContainer.innerHTML = '<div class="NORDBOOKING-message success"><p>' + data.data.message + '</p></div>';
                    messageContainer.style.display = 'block';
                    
                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = data.data.redirect_url;
                    }, 1500);
                } else {
                    // Show error message
                    messageContainer.innerHTML = '<div class="NORDBOOKING-message error"><p>' + (data.data.message || 'Registration failed. Please try again.') + '</p></div>';
                    messageContainer.style.display = 'block';
                    
                    // Re-enable submit button
                    submitButton.disabled = false;
                    submitButton.value = '<?php esc_attr_e('Register', 'NORDBOOKING'); ?>';
                }
            })
            .catch(error => {
                console.error('Registration error:', error);
                messageContainer.innerHTML = '<div class="NORDBOOKING-message error"><p>Network error. Please try again.</p></div>';
                messageContainer.style.display = 'block';
                
                // Re-enable submit button
                submitButton.disabled = false;
                submitButton.value = '<?php esc_attr_e('Register', 'NORDBOOKING'); ?>';
            });
        });
    }
});
</script>
