<?php
/**
 * Template Name: Tenant Login Page
 * The template for displaying the custom tenant login page.
 *
 * @package MoBooking
 */

// Redirect logged-in users to the dashboard
if ( is_user_logged_in() ) {
    // Check if user has the 'mobooking_business_owner' role
    $user = wp_get_current_user();
    if ( in_array( 'mobooking_business_owner', (array) $user->roles ) ) {
        wp_redirect( home_url( '/dashboard/' ) ); // Adjust dashboard URL if needed
        exit;
    }
    // For other logged-in users, maybe redirect to account or homepage
    // wp_redirect( home_url( '/account/' ) );
    // exit;
}

get_header();
?>

<main id="main" class="site-main">
    <div id="mobooking-login-form-container">
        <h2><?php esc_html_e( 'Business Owner Login', 'mobooking' ); ?></h2>
        <form id="mobooking-login-form">
            <p class="login-username">
                <label for="mobooking-user-login"><?php esc_html_e( 'Email Address', 'mobooking' ); ?></label>
                <input type="text" name="log" id="mobooking-user-login" class="input" value="" size="20" />
            </p>
            <p class="login-password">
                <label for="mobooking-user-pass"><?php esc_html_e( 'Password', 'mobooking' ); ?></label>
                <input type="password" name="pwd" id="mobooking-user-pass" class="input" value="" size="20" />
            </p>
            <?php // Nonce is handled by assets/js/auth.js via localized script parameters ?>
            <p class="login-remember"><label><input name="rememberme" type="checkbox" id="mobooking-rememberme" value="forever" /> <?php esc_html_e( 'Remember Me', 'mobooking' ); ?></label></p>
            <p class="login-submit">
                <input type="submit" name="wp-submit" id="mobooking-wp-submit" class="button button-primary" value="<?php esc_attr_e( 'Log In', 'mobooking' ); ?>" />
                <input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url( '/dashboard/' ) ); // Adjust as needed ?>" />
            </p>
            <div id="mobooking-login-message" style="display:none;"></div>
        </form>
        <p>
            <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Lost your password?', 'mobooking' ); ?></a>
        </p>
        <p>
            <?php // Placeholder for registration link - will point to a custom registration page or process ?>
            <a href="<?php echo esc_url( home_url( '/register/' ) ); // Placeholder ?>"><?php esc_html_e( 'Don\'t have an account? Register', 'mobooking' ); ?></a>
        </p>
    </div>
</main><!-- #main -->

<?php
// We might not want the standard footer on the login page, or a simplified one.
// For now, including the standard one.
get_footer();
