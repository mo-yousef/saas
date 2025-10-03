<?php
/**
 * Template Name: Forgot Password Page
 *
 * @package NORDBOOKING
 */

if ( is_user_logged_in() ) {
    // Redirect logged-in users, perhaps to dashboard or account page
    wp_redirect( home_url( '/dashboard/' ) );
    exit;
}

get_header(); // This will be hidden by CSS in auth-pages.css for this template
?>

<div class="nbk-auth-page-container">
    <div class="nbk-auth-grid">

        <div class="nbk-auth-form-column">
            <main id="main" class="site-main">
                <div id="NORDBOOKING-forgot-password-form-container" class="NORDBOOKING-auth-form-wrapper">
                    <h2><?php esc_html_e( 'Forgot Your Password?', 'NORDBOOKING' ); ?></h2>
                    <p style="text-align: center; color: hsl(215.4 16.3% 46.9%); margin-bottom: 1.5rem;">
                        <?php esc_html_e( 'Enter your email address below, and we\'ll send you a link to reset your password.', 'NORDBOOKING' ); ?>
                    </p>
                    <form id="NORDBOOKING-forgot-password-form">
                        <p class="forgot-password-email">
                            <label for="NORDBOOKING-user-email-forgot"><?php esc_html_e( 'Email Address', 'NORDBOOKING' ); ?></label>
                            <input type="email" name="user_email" id="NORDBOOKING-user-email-forgot" class="input" value="" required />
                        </p>
                        <p class="forgot-password-submit">
                            <button type="submit" id="NORDBOOKING-wp-submit-forgot" class="nb-btn nb-btn--primary nb-btn--lg nb-btn--block"><?php esc_html_e( 'Send Reset Link', 'NORDBOOKING' ); ?></button>
                        </p>
                        <div id="NORDBOOKING-forgot-password-message" style="display:none; margin-top: 1rem;"></div>
                    </form>
                    <div class="NORDBOOKING-auth-links">
                        <p>
                            <?php esc_html_e( 'Remember your password?', 'NORDBOOKING' ); ?>
                            <a href="<?php echo esc_url( home_url( '/login/' ) ); ?>"><?php esc_html_e( 'Log In', 'NORDBOOKING' ); ?></a>
                        </p>
                         <p>
                            <?php esc_html_e( 'Don\'t have an account?', 'NORDBOOKING' ); ?>
                            <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>"><?php esc_html_e( 'Register', 'NORDBOOKING' ); ?></a>
                        </p>
                    </div>
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
