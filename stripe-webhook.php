<?php
/**
 * Stripe Webhook Handler
 *
 * @package NORDBOOKING
 */

// Load WordPress environment
require_once( __DIR__ . '/wp-load.php' );

// Handle the webhook
if ( class_exists( 'NORDBOOKING\Classes\Subscription' ) ) {
    NORDBOOKING\Classes\Subscription::handle_stripe_webhook();
}
