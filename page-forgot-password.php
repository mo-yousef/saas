<?php
/**
 * Template Name: Forgot Password Page
 *
 * @package MoBooking
 */

if ( is_user_logged_in() ) {
    // Redirect logged-in users, perhaps to dashboard or account page
    wp_redirect( home_url( '/dashboard/' ) );
    exit;
}

get_header(); // This will be hidden by CSS in auth-pages.css for this template
?>

<div class="mobooking-auth-page-container">
    <div class="mobooking-auth-grid">
        <div class="mobooking-auth-image-column">
            <div class="placeholder-content">
                <h1><?php bloginfo('name'); ?></h1>
                <p><?php esc_html_e('Reset your password to regain access to your account.', 'mobooking'); ?></p>
            </div>
        </div>
        <div class="mobooking-auth-form-column">
            <main id="main" class="site-main">
                <div id="mobooking-forgot-password-form-container" class="mobooking-auth-form-wrapper">
                    <h2><?php esc_html_e( 'Forgot Your Password?', 'mobooking' ); ?></h2>
                    <p style="text-align: center; color: hsl(215.4 16.3% 46.9%); margin-bottom: 1.5rem;">
                        <?php esc_html_e( 'Enter your email address below, and we\'ll send you a link to reset your password.', 'mobooking' ); ?>
                    </p>
                    <form id="mobooking-forgot-password-form">
                        <p class="forgot-password-email">
                            <label for="mobooking-user-email-forgot"><?php esc_html_e( 'Email Address', 'mobooking' ); ?></label>
                            <input type="email" name="user_email" id="mobooking-user-email-forgot" class="input" value="" required />
                        </p>
                        <p class="forgot-password-submit">
                            <input type="submit" name="wp-submit" id="mobooking-wp-submit-forgot" class="button button-primary" value="<?php esc_attr_e( 'Send Reset Link', 'mobooking' ); ?>" />
                        </p>
                        <div id="mobooking-forgot-password-message" style="display:none; margin-top: 1rem;"></div>
                    </form>
                    <div class="mobooking-auth-links">
                        <p>
                            <?php esc_html_e( 'Remember your password?', 'mobooking' ); ?>
                            <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php esc_html_e( 'Log In', 'mobooking' ); ?></a>
                        </p>
                         <p>
                            <?php esc_html_e( 'Don\'t have an account?', 'mobooking' ); ?>
                            <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>"><?php esc_html_e( 'Register', 'mobooking' ); ?></a>
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<?php
get_footer(); // This will be hidden by CSS
