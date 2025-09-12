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
    public function create_checkout_session(int $tenant_id, int $service_id, string $customer_email) {
        // Get the service details to find the price
        $services_manager = new \NORDBOOKING\Classes\Services();
        $service = $services_manager->get_service_by_id($service_id);

        if (is_wp_error($service) || !$service) {
            throw new \Exception('Invalid service.');
        }

        // For simplicity, we'll create a new Stripe Price object on the fly for the subscription.
        $price = \Stripe\Price::create([
            'unit_amount' => $service->price * 100, // Price in cents
            'currency' => 'usd',
            'recurring' => ['interval' => 'month'],
            'product_data' => [
                'name' => $service->name,
            ],
        ]);

        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $price->id,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => home_url('/booking-success/'),
            'cancel_url' => home_url('/booking-canceled/'),
            'customer_email' => $customer_email,
            'metadata' => [
                'tenant_id' => $tenant_id,
                'service_id' => $service_id,
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
        $service_id = $session->metadata->service_id;
        $customer_email = $session->customer_email;
        $stripe_subscription_id = $session->subscription;

        // Find or create customer
        $customers_manager = new \NORDBOOKING\Classes\Customers();
        $customer_data = [
            'full_name' => $customer_email,
            'email'     => $customer_email,
        ];
        $customer_id = $customers_manager->create_or_update_customer_for_booking($tenant_id, $customer_data);

        // Get subscription details from Stripe
        $stripe_subscription = \Stripe\Subscription::retrieve($stripe_subscription_id);

        // Create subscription
        global $wpdb;
        $subscriptions_table = \NORDBOOKING\Classes\Database::get_table_name('subscriptions');
        $wpdb->insert(
            $subscriptions_table,
            [
                'customer_id' => $customer_id,
                'tenant_id' => $tenant_id,
                'stripe_subscription_id' => $stripe_subscription_id,
                'status' => $stripe_subscription->status,
                'current_period_start' => date('Y-m-d H:i:s', $stripe_subscription->current_period_start),
                'current_period_end' => date('Y-m-d H:i:s', $stripe_subscription->current_period_end),
            ]
        );
        $subscription_id = $wpdb->insert_id;

        // Create subscription item
        $subscription_items_table = \NORDBOOKING\Classes\Database::get_table_name('subscription_items');
        $service = (new \NORDBOOKING\Classes\Services())->get_service_by_id($service_id);
        $wpdb->insert(
            $subscription_items_table,
            [
                'subscription_id' => $subscription_id,
                'stripe_subscription_item_id' => $stripe_subscription->items->data[0]->id,
                'service_id' => $service_id,
                'quantity' => 1,
                'price' => $service->price,
            ]
        );
    }

    /**
     * Handles the customer.subscription.updated event.
     *
     * @param object $subscription The Stripe subscription object.
     */
    private function handle_subscription_updated($subscription) {
        global $wpdb;
        $subscriptions_table = \NORDBOOKING\Classes\Database::get_table_name('subscriptions');
        $wpdb->update(
            $subscriptions_table,
            [
                'status' => $subscription->status,
                'current_period_start' => date('Y-m-d H:i:s', $subscription->current_period_start),
                'current_period_end' => date('Y-m-d H:i:s', $subscription->current_period_end),
            ],
            [ 'stripe_subscription_id' => $subscription->id ]
        );
    }

    /**
     * Handles the customer.subscription.deleted event.
     *
     * @param object $subscription The Stripe subscription object.
     */
    private function handle_subscription_deleted($subscription) {
        global $wpdb;
        $subscriptions_table = \NORDBOOKING\Classes\Database::get_table_name('subscriptions');
        $wpdb->update(
            $subscriptions_table,
            [ 'status' => 'canceled' ],
            [ 'stripe_subscription_id' => $subscription->id ]
        );
    }
}
