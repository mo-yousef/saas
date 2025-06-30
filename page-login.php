<?php
/**
 * Template Name: Tenant Login Page
 * The template for displaying the custom tenant login page.
 *
 * @package MoBooking
 */

// Redirect logged-in users to the dashboard
if ( is_user_logged_in() ) {
    $user = wp_get_current_user();
    // Check if user has the capability to access the MoBooking dashboard
    if ( user_can( $user, \MoBooking\Classes\Auth::ACCESS_MOBOOKING_DASHBOARD ) ) {
        wp_redirect( home_url( '/dashboard/' ) ); // Adjust dashboard URL if needed
        exit;
    }
    // For other logged-in users (e.g. standard WordPress subscriber without MoBooking access)
    // you might redirect them to the WP admin profile or the site homepage.
    // For now, let them stay or redirect to home_url() if they are not MoBooking users.
    // If they are logged in but don't have ACCESS_MOBOOKING_DASHBOARD, they shouldn't be on this custom login.
    // Redirecting to home_url() might be a safe default.
    // wp_redirect( home_url() );
    // exit;
    // However, if a logged-in user without dashboard access lands here, it's a bit odd.
    // The current behaviour (showing the login form again) is not ideal but also not breaking.
    // For this task, ensuring dashboard users are redirected is key.
    // The AJAX login handler already prevents login if capability is missing.
    // This top-level redirect is for users already having a session.
    // For other logged-in users, maybe redirect to account or homepage
    // wp_redirect( home_url( '/account/' ) );
    // exit;
}

get_header(); // This will be hidden by CSS in auth-pages.css for these templates
?>

<div class="mobooking-auth-page-container">
    <div class="mobooking-auth-grid">
        <div class="mobooking-auth-image-column">
            <div class="placeholder-content">
                 <!-- You can replace this with an <img> tag or more complex HTML -->
                <h1><?php bloginfo('name'); ?></h1>
                <p><?php esc_html_e('Manage your bookings efficiently and effectively.', 'mobooking'); ?></p>
            </div>
        </div>
        <div class="mobooking-auth-form-column">
            <main id="main" class="site-main">
                <div id="mobooking-login-form-container" class="mobooking-auth-form-wrapper">
                    <h2><?php esc_html_e( 'Business Owner Login', 'mobooking' ); ?></h2>
                    <form id="mobooking-login-form">
                        <p class="login-username">
                            <label for="mobooking-user-login"><?php esc_html_e( 'Email Address', 'mobooking' ); ?></label>
                            <input type="text" name="log" id="mobooking-user-login" class="input" value="" size="20" required />
                        </p>
                        <p class="login-password">
                            <label for="mobooking-user-pass"><?php esc_html_e( 'Password', 'mobooking' ); ?></label>
                            <input type="password" name="pwd" id="mobooking-user-pass" class="input" value="" size="20" required />
                        </p>
                        <?php // Nonce is handled by assets/js/auth.js via localized script parameters ?>
                        <p class="login-remember"><label><input name="rememberme" type="checkbox" id="mobooking-rememberme" value="forever" /> <?php esc_html_e( 'Remember Me', 'mobooking' ); ?></label></p>
                        <p class="login-submit">
                            <input type="submit" name="wp-submit" id="mobooking-wp-submit" class="button button-primary" value="<?php esc_attr_e( 'Log In', 'mobooking' ); ?>" />
                            <input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url( '/dashboard/' ) ); // Adjust as needed ?>" />
                        </p>
                        <div id="mobooking-login-message" style="display:none;"></div>
                    </form>
                    <div class="mobooking-auth-links">
                        <p>
                            <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'mobooking' ); ?></a>
                        </p>
                        <p>
                            <?php esc_html_e( 'Don\'t have an account?', 'mobooking' ); ?>
                            <a href="<?php echo esc_url( home_url( '/register/' ) ); ?>"><?php esc_html_e( 'Register', 'mobooking' ); ?></a>
                        </p>
                    </div>
                </div>
            </main><!-- #main -->
        </div>
    </div>
</div>

<?php
get_footer(); // This will be hidden by CSS in auth-pages.css for these templates
