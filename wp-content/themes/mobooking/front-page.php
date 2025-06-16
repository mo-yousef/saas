<?php
/**
 * The template for displaying the front page.
 *
 * @package MoBooking
 */

get_header();
?>

<main id="main" class="site-main">
    <section id="hero">
        <h1><?php esc_html_e( 'MoBooking - Manage Your Cleaning Business Online', 'mobooking' ); ?></h1>
        <p><?php esc_html_e( 'The ultimate SaaS platform for cleaning service companies.', 'mobooking' ); ?></p>
        <a href="<?php echo esc_url( home_url( '/wp-login.php?action=register' ) ); // Placeholder for actual registration page ?>" class="button button-primary">
            <?php esc_html_e( 'Get Started', 'mobooking' ); ?>
        </a>
        <p><?php // The description mentions "WooCommerce + Stripe" on front-page.php - adding a note here. ?>
            <?php esc_html_e( 'Integrates with WooCommerce and Stripe for seamless payments.', 'mobooking' ); ?>
        </p>
    </section>

    <section id="features">
        <h2><?php esc_html_e( 'Features', 'mobooking' ); ?></h2>
        <ul>
            <li><?php esc_html_e( 'Multi-Tenant System', 'mobooking' ); ?></li>
            <li><?php esc_html_e( 'Service Management', 'mobooking' ); ?></li>
            <li><?php esc_html_e( 'Online Booking Form', 'mobooking' ); ?></li>
            <li><?php esc_html_e( 'Business Owner Dashboard', 'mobooking' ); ?></li>
            <li><?php esc_html_e( 'Area Management', 'mobooking' ); ?></li>
            <li><?php esc_html_e( 'Discount Codes', 'mobooking' ); ?></li>
        </ul>
    </section>

    <section id="cta">
        <h2><?php esc_html_e( 'Ready to streamline your bookings?', 'mobooking' ); ?></h2>
        <a href="<?php echo esc_url( home_url( '/wp-login.php?action=register' ) ); // Placeholder ?>" class="button button-secondary">
            <?php esc_html_e( 'Sign Up Now', 'mobooking' ); ?>
        </a>
    </section>
</main><!-- #main -->

<?php
get_footer();
