<?php
/**
 * Dashboard Page: Subscription
 *
 * @package NORDBOOKING
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$user_id = get_current_user_id();
$subscription_status = get_user_meta( $user_id, '_nordbooking_subscription_status', true );
$trial_ends_at = get_user_meta( $user_id, '_nordbooking_trial_ends_at', true );

?>
<div class="wrap nordbooking-dashboard-wrap">
    <h1 class="wp-heading-inline"><?php _e('Subscription', 'nordbooking'); ?></h1>
    <hr class="wp-header-end">

    <div class="nordbooking-card">
        <div class="nordbooking-card-content">
            <h2><?php _e('Your Subscription Status', 'nordbooking'); ?></h2>
            <p>
                <?php
                if ( $subscription_status === 'active' ) {
                    echo __( 'Your subscription is currently active.', 'nordbooking' );
                } elseif ( $trial_ends_at && time() < $trial_ends_at ) {
                    echo sprintf( __( 'You are currently on a free trial. Your trial ends on %s.', 'nordbooking' ), date( get_option( 'date_format' ), $trial_ends_at ) );
                } else {
                    echo __( 'You do not have an active subscription.', 'nordbooking' );
                }
                ?>
            </p>

            <?php if ( $subscription_status !== 'active' ) : ?>
                <a href="#" id="nordbooking-subscribe-button" class="button button-primary"><?php _e('Subscribe Now', 'nordbooking'); ?></a>
            <?php else : ?>
                <a href="#" id="nordbooking-manage-subscription-button" class="button"><?php _e('Manage Subscription', 'nordbooking'); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>
