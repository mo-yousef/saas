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
?>
<main id="main" class="site-main">
    <div id="mobooking-register-form-container">
        <h2><?php esc_html_e( 'Register Your Business', 'mobooking' ); ?></h2>
        <form id="mobooking-register-form">
            <p class="register-email">
                <label for="mobooking-user-email"><?php esc_html_e( 'Email Address', 'mobooking' ); ?></label>
                <input type="email" name="email" id="mobooking-user-email" class="input" value="" required />
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
