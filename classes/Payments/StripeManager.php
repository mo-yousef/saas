<?php
/**
 * Class StripeManager
 *
 * Handles all interactions with the Stripe API.
 *
 * @package NORDBOOKING\Classes\Payments
 */

namespace NORDBOOKING\Classes\Payments;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class StripeManager {

    /**
     * StripeManager constructor.
     */
    public function __construct() {
        // Get the Stripe secret key from the settings.
        $stripe_secret_key = get_option( 'nordbooking_stripe_secret_key' );
        if ( ! empty( $stripe_secret_key ) ) {
            \Stripe\Stripe::setApiKey( $stripe_secret_key );
        }
    }

    /**
     * Creates a new Stripe Checkout session for a subscription.
     *
     * @param int $tenant_id
     * @param int $service_id
     * @param string $customer_email
     * @return \Stripe\Checkout\Session
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function create_checkout_session(int $tenant_id, string $customer_email) {
        $price_in_cents = get_option( 'nordbooking_stripe_subscription_price', 49 ) * 100;

        $price = \Stripe\Price::create([
            'unit_amount' => $price_in_cents,
            'currency' => 'usd',
            'recurring' => ['interval' => 'month'],
            'product_data' => [
                'name' => 'NORDBOOKING Pro Plan',
            ],
        ]);

        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $price->id,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => home_url('/dashboard/'),
            'cancel_url' => home_url('/dashboard/subscription/'),
            'customer_email' => $customer_email,
            'metadata' => [
                'tenant_id' => $tenant_id,
            ]
        ]);

        return $checkout_session;
    }

    /**
     * Handles Stripe webhook events.
     *
     * @param object $event The Stripe event object.
     */
    public function handle_webhook($event) {
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handle_checkout_session_completed($event->data->object);
                break;
            case 'customer.subscription.updated':
                $this->handle_subscription_updated($event->data->object);
                break;
            case 'customer.subscription.deleted':
                $this->handle_subscription_deleted($event->data->object);
                break;
            default:
                // Unexpected event type
                http_response_code(400);
                exit();
        }
    }

    /**
     * Handles the checkout.session.completed event.
     *
     * @param object $session The Stripe session object.
     */
    private function handle_checkout_session_completed($session) {
        $tenant_id = $session->metadata->tenant_id;
        $stripe_subscription_id = $session->subscription;
        $stripe_customer_id = $session->customer;

        // Get subscription details from Stripe
        $stripe_subscription = \Stripe\Subscription::retrieve($stripe_subscription_id);

        update_user_meta( $tenant_id, '_nordbooking_subscription_status', $stripe_subscription->status );
        update_user_meta( $tenant_id, '_nordbooking_stripe_subscription_id', $stripe_subscription_id );
        update_user_meta( $tenant_id, '_nordbooking_stripe_customer_id', $stripe_customer_id );
    }

    /**
     * Handles the customer.subscription.updated event.
     *
     * @param object $subscription The Stripe subscription object.
     */
    private function handle_subscription_updated($subscription) {
        $stripe_subscription_id = $subscription->id;
        $user = get_users(['meta_key' => '_nordbooking_stripe_subscription_id', 'meta_value' => $stripe_subscription_id]);
        if ( ! empty($user) ) {
            $user_id = $user[0]->ID;
            update_user_meta( $user_id, '_nordbooking_subscription_status', $subscription->status );
            update_user_meta( $user_id, '_nordbooking_subscription_updated_at', time() );
        }
    }

    /**
     * Handles the customer.subscription.deleted event.
     *
     * @param object $subscription The Stripe subscription object.
     */
    private function handle_subscription_deleted($subscription) {
        $stripe_subscription_id = $subscription->id;
        $user = get_users(['meta_key' => '_nordbooking_stripe_subscription_id', 'meta_value' => $stripe_subscription_id]);
        if ( ! empty($user) ) {
            $user_id = $user[0]->ID;
            update_user_meta( $user_id, '_nordbooking_subscription_status', 'canceled' );
        }
    }
}
