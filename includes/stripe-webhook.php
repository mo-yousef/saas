<?php
/**
 * Stripe Webhook Handler
 *
 * NOTE: For a more robust implementation, it is recommended to use the WordPress REST API
 * to create a custom endpoint for webhooks instead of a standalone file.
 * e.g., register_rest_route( 'nordbooking/v1', '/webhook', ... );
 */

// Load WordPress
require_once(dirname(__FILE__) . '/../../../../wp-load.php');

// Get the Stripe secret key and webhook secret from the settings
$stripe_secret_key = get_option( 'nordbooking_stripe_secret_key' );
$stripe_webhook_secret = get_option( 'nordbooking_stripe_webhook_secret' );

\Stripe\Stripe::setApiKey($stripe_secret_key);

// Get the request body and signature
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $stripe_webhook_secret
    );
} catch(\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// Handle the event
$stripe_manager = new \NORDBOOKING\Classes\Payments\StripeManager();
$stripe_manager->handle_webhook($event);

http_response_code(200);
