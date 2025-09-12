<?php
/**
 * Template Name: Subscription Required
 *
 * @package NORDBOOKING
 */

get_header();
?>
<div class="wrap">
    <div class="subscription-required-notice">
        <h1><?php _e( 'Subscription Required', 'nordbooking' ); ?></h1>
        <p><?php _e( 'Your trial has ended or your subscription has expired. Please subscribe to continue using the dashboard.', 'nordbooking' ); ?></p>
        <a href="<?php echo esc_url( home_url( '/dashboard/subscription/' ) ); ?>" class="button button-primary"><?php _e( 'Subscribe Now', 'nordbooking' ); ?></a>
    </div>
</div>
<?php
get_footer();
