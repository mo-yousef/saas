<?php

namespace NORDBOOKING\Classes;

class Subscription {

    public static function install() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) DEFAULT 'trial' NOT NULL,
            trial_ends_at datetime DEFAULT NULL,
            ends_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function schedule_events() {
        if (!wp_next_scheduled('nordbooking_daily_subscription_checks')) {
            wp_schedule_event(time(), 'daily', 'nordbooking_daily_subscription_checks');
        }
    }

    public function __construct() {
        add_action('nordbooking_daily_subscription_checks', array($this, 'daily_subscription_checks'));
    }

    public static function daily_subscription_checks() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';

        // Check for expired trials
        $expired_trials = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE status = 'trial' AND trial_ends_at < NOW() AND trial_ends_at > DATE_SUB(NOW(), INTERVAL 1 DAY)"
        );

        $notifications = new \NORDBOOKING\Classes\Notifications();

        foreach ($expired_trials as $subscription) {
            $notifications->send_trial_expired_email($subscription->user_id);
        }

        // Check for subscription renewal reminders
        $renewal_reminders = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE status = 'active' AND ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 DAY)"
            )
        );

        foreach ($renewal_reminders as $subscription) {
            $notifications->send_renewal_reminder_email($subscription->user_id);
        }
    }

    /**
     * Create a trial subscription for a new user.
     *
     * @param int $user_id The ID of the user.
     */
    public static function create_trial_subscription($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';

        $trial_ends_at = date('Y-m-d H:i:s', strtotime('+7 days'));

        $wpdb->insert(
            $table_name,
            [
                'user_id'       => $user_id,
                'status'        => 'trial',
                'trial_ends_at' => $trial_ends_at,
            ]
        );
    }

    /**
     * Get the subscription for a user.
     *
     * @param int $user_id The ID of the user.
     * @return array|null The subscription data or null if not found.
     */
    public static function get_subscription($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';

        $subscription = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );

        return $subscription;
    }

    /**
     * Get the subscription status for a user.
     *
     * @param int $user_id The ID of the user.
     * @return string The subscription status.
     */
    public static function get_subscription_status($user_id) {
        $subscription = self::get_subscription($user_id);

        if (!$subscription) {
            return 'unsubscribed';
        }

        // Check for expired trial
        if ($subscription['status'] === 'trial' && !empty($subscription['trial_ends_at'])) {
            $trial_ends_at = new \DateTime($subscription['trial_ends_at']);
            $now = new \DateTime();
            if ($now > $trial_ends_at) {
                return 'expired_trial';
            }
        }

        // Check for expired subscription
        if ($subscription['status'] === 'active' && !empty($subscription['ends_at'])) {
             $ends_at = new \DateTime($subscription['ends_at']);
             // Add a 2-day grace period
             $grace_period_ends = $ends_at->modify('+2 days');
             $now = new \DateTime();
             if ($now > $grace_period_ends) {
                 return 'expired';
             }
        }


        return $subscription['status'];
    }

    /**
     * Get the number of days until the next payment.
     *
     * @param int $user_id The ID of the user.
     * @return int The number of days.
     */
    public static function get_days_until_next_payment($user_id) {
        $subscription = self::get_subscription($user_id);

        if (!$subscription || ($subscription['status'] !== 'active' && $subscription['status'] !== 'trial') || empty($subscription['ends_at'])) {
            if ($subscription && $subscription['status'] === 'trial' && !empty($subscription['trial_ends_at'])) {
                $ends_at = new \DateTime($subscription['trial_ends_at']);
            } else {
                return 0;
            }
        } else {
            $ends_at = new \DateTime($subscription['ends_at']);
        }

        $now = new \DateTime();
        if ($now > $ends_at) {
            return 0;
        }

        $interval = $now->diff($ends_at);

        return $interval->days;
    }

    /**
     * Create a Stripe checkout session for subscribing.
     *
     * @param int $user_id The ID of the user.
     * @return string The URL of the checkout session.
     */
    public static function create_stripe_checkout_session($user_id) {
        \Stripe\Stripe::setApiKey('YOUR_STRIPE_SECRET_KEY'); // Replace with your secret key

        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => 'YOUR_STRIPE_PRICE_ID', // Replace with your price ID
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => home_url('/dashboard/'),
            'cancel_url' => home_url('/dashboard/'),
            'client_reference_id' => $user_id,
        ]);

        return $checkout_session->url;
    }

    /**
     * Handle the Stripe webhook.
     */
    public static function handle_stripe_webhook() {
        \Stripe\Stripe::setApiKey('YOUR_STRIPE_SECRET_KEY'); // Replace with your secret key
        $endpoint_secret = 'YOUR_STRIPE_WEBHOOK_SECRET'; // Replace with your webhook secret

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
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
        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;
            $user_id = $session->client_reference_id;
            $subscription_id = $session->subscription;

            // Get the subscription details from Stripe
            $stripe_subscription = \Stripe\Subscription::retrieve($subscription_id);

            self::update_subscription_status($user_id, 'active', $stripe_subscription->current_period_end);
        }

        http_response_code(200);
    }

    /**
     * Update the subscription status for a user.
     *
     * @param int $user_id The ID of the user.
     * @param string $status The new status.
     * @param int $ends_at The timestamp when the subscription ends.
     */
    public static function update_subscription_status($user_id, $status, $ends_at) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';

        $wpdb->update(
            $table_name,
            [
                'status' => $status,
                'ends_at' => date('Y-m-d H:i:s', $ends_at),
            ],
            ['user_id' => $user_id]
        );
    }
}
