<?php

namespace NORDBOOKING\Classes;

// Include Stripe PHP library
if (!class_exists('\Stripe\Stripe')) {
    $stripe_path = NORDBOOKING_THEME_DIR . 'vendor/stripe/stripe-php/init.php';
    if (file_exists($stripe_path)) {
        require_once $stripe_path;
    } else {
        // Fallback: try to load from WordPress plugins if available
        $wp_stripe_path = WP_PLUGIN_DIR . '/stripe/stripe-php/init.php';
        if (file_exists($wp_stripe_path)) {
            require_once $wp_stripe_path;
        }
    }
}

class Subscription {

    public static function install() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) DEFAULT 'trial' NOT NULL,
            stripe_customer_id varchar(255) DEFAULT NULL,
            stripe_subscription_id varchar(255) DEFAULT NULL,
            trial_ends_at datetime DEFAULT NULL,
            ends_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES {$wpdb->prefix}users(ID) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Fix existing constraint issues - remove problematic unique constraint
        $wpdb->query("ALTER TABLE $table_name DROP INDEX IF EXISTS stripe_subscription_id_unique");
        
        // Clean up any existing empty values that might cause issues
        $wpdb->query("UPDATE $table_name SET stripe_subscription_id = NULL WHERE stripe_subscription_id = ''");
        
        // Note: We're not adding the unique constraint back because it causes issues with NULL values
        // The stripe_subscription_id should be unique when not NULL, but NULL values are allowed
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

        // Check if subscription already exists
        $existing = self::get_subscription($user_id);
        if ($existing) {
            return; // Don't create duplicate
        }

        $trial_days = StripeConfig::get_trial_days();
        $trial_ends_at = date('Y-m-d H:i:s', strtotime("+{$trial_days} days"));

        // Create Stripe customer
        $stripe_customer_id = self::create_stripe_customer($user_id);

        // Prepare data for insertion - exclude stripe_subscription_id to avoid constraint issues
        $data = [
            'user_id'            => $user_id,
            'status'             => 'trial',
            'stripe_customer_id' => $stripe_customer_id,
            'trial_ends_at'      => $trial_ends_at,
        ];

        $result = $wpdb->insert($table_name, $data);
        
        if ($result === false) {
            error_log('Failed to create trial subscription for user ' . $user_id . ': ' . $wpdb->last_error);
            error_log('Database error details: ' . print_r($wpdb->last_query, true));
        } else {
            error_log('Successfully created trial subscription for user ' . $user_id . ' with ID: ' . $wpdb->insert_id);
        }
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

        // If there's a Stripe subscription ID, try to sync with Stripe first
        if (!empty($subscription['stripe_subscription_id']) && StripeConfig::is_configured()) {
            try {
                \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
                $stripe_subscription = \Stripe\Subscription::retrieve($subscription['stripe_subscription_id']);
                
                // Update local status if it differs from Stripe
                $stripe_status = self::map_stripe_status($stripe_subscription->status);
                if ($stripe_status !== $subscription['status']) {
                    self::update_subscription_from_stripe($user_id, $stripe_subscription);
                    // Refresh subscription data
                    $subscription = self::get_subscription($user_id);
                }
            } catch (\Exception $e) {
                // Log error but continue with local status
                error_log('Failed to sync subscription status with Stripe: ' . $e->getMessage());
            }
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
        if (($subscription['status'] === 'active' || $subscription['status'] === 'cancelled') && !empty($subscription['ends_at'])) {
             $ends_at = new \DateTime($subscription['ends_at']);
             // Add a 2-day grace period
             $grace_period_ends = $ends_at->modify('+2 days');
             $now = new \DateTime();
             if ($now > $grace_period_ends) {
                 return 'expired';
             }
             // If cancelled but still within billing period, show as active
             if ($subscription['status'] === 'cancelled') {
                 $ends_at = new \DateTime($subscription['ends_at']);
                 $now = new \DateTime();
                 if ($now <= $ends_at) {
                     return 'active'; // Still has access
                 }
             }
        }

        return $subscription['status'];
    }

    /**
     * Map Stripe subscription status to our internal status
     */
    private static function map_stripe_status($stripe_status) {
        switch ($stripe_status) {
            case 'active':
                return 'active';
            case 'canceled':
                return 'cancelled';
            case 'past_due':
                return 'past_due';
            case 'unpaid':
                return 'unpaid';
            case 'trialing':
                return 'trial';
            default:
                return 'active'; // Default to active for unknown statuses
        }
    }

    /**
     * Get the number of days until the next payment.
     *
     * @param int $user_id The ID of the user.
     * @return int The number of days.
     */
    public static function get_days_until_next_payment($user_id) {
        $subscription = self::get_subscription($user_id);

        if (!$subscription || ($subscription['status'] !== 'active' && $subscription['status'] !== 'trial' && $subscription['status'] !== 'cancelled') || empty($subscription['ends_at'])) {
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
     * Create a Stripe customer for a user
     */
    public static function create_stripe_customer($user_id) {
        if (!StripeConfig::is_configured()) {
            return null;
        }

        try {
            \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
            
            $user = get_userdata($user_id);
            if (!$user) {
                return null;
            }

            $customer = \Stripe\Customer::create([
                'email' => $user->user_email,
                'name' => $user->display_name,
                'metadata' => [
                    'wordpress_user_id' => $user_id,
                    'site_url' => home_url(),
                ]
            ]);

            return $customer->id;
        } catch (\Exception $e) {
            error_log('Stripe customer creation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a Stripe checkout session for subscribing.
     *
     * @param int $user_id The ID of the user.
     * @return string|false The URL of the checkout session or false on failure.
     */
    public static function create_stripe_checkout_session($user_id) {
        if (!StripeConfig::is_configured()) {
            return false;
        }

        try {
            \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
            
            $user = get_userdata($user_id);
            if (!$user) {
                return false;
            }

            // Get or create Stripe customer
            $subscription = self::get_subscription($user_id);
            $customer_id = null;
            
            if ($subscription && !empty($subscription['stripe_customer_id'])) {
                $customer_id = $subscription['stripe_customer_id'];
            } else {
                $customer_id = self::create_stripe_customer($user_id);
                if ($customer_id && $subscription) {
                    // Update subscription with customer ID
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
                    $wpdb->update(
                        $table_name,
                        ['stripe_customer_id' => $customer_id],
                        ['user_id' => $user_id]
                    );
                }
            }

            if (!$customer_id) {
                return false;
            }

            $checkout_session = \Stripe\Checkout\Session::create([
                'customer' => $customer_id,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => StripeConfig::get_price_id(),
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => home_url('/dashboard/subscription/?success=1'),
                'cancel_url' => home_url('/dashboard/subscription/?cancelled=1'),
                'client_reference_id' => $user_id,
                'metadata' => [
                    'wordpress_user_id' => $user_id,
                ]
            ]);

            return $checkout_session->url;
        } catch (\Exception $e) {
            error_log('Stripe checkout session creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create Stripe Customer Portal session
     */
    public static function create_customer_portal_session($user_id) {
        if (!StripeConfig::is_configured()) {
            return false;
        }

        try {
            \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
            
            $subscription = self::get_subscription($user_id);
            if (!$subscription || empty($subscription['stripe_customer_id'])) {
                return false;
            }

            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $subscription['stripe_customer_id'],
                'return_url' => home_url('/dashboard/subscription/'),
            ]);

            return $session->url;
        } catch (\Exception $e) {
            error_log('Stripe customer portal session creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Handle the Stripe webhook.
     */
    public static function handle_stripe_webhook() {
        if (!StripeConfig::is_configured()) {
            http_response_code(400);
            exit();
        }

        \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
        $endpoint_secret = StripeConfig::get_webhook_secret();

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            error_log('Stripe webhook invalid payload: ' . $e->getMessage());
            http_response_code(400);
            exit();
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            error_log('Stripe webhook invalid signature: ' . $e->getMessage());
            http_response_code(400);
            exit();
        }

        // Handle different event types
        switch ($event->type) {
            case 'checkout.session.completed':
                self::handle_checkout_completed($event->data->object);
                break;
            
            case 'invoice.payment_succeeded':
                self::handle_payment_succeeded($event->data->object);
                break;
            
            case 'invoice.payment_failed':
                self::handle_payment_failed($event->data->object);
                break;
            
            case 'customer.subscription.updated':
                self::handle_subscription_updated($event->data->object);
                break;
            
            case 'customer.subscription.deleted':
                self::handle_subscription_cancelled($event->data->object);
                break;
            
            default:
                error_log('Unhandled Stripe webhook event type: ' . $event->type);
        }

        http_response_code(200);
    }

    /**
     * Handle checkout session completed
     */
    private static function handle_checkout_completed($session) {
        $user_id = $session->client_reference_id ?? $session->metadata->wordpress_user_id ?? null;
        
        if (!$user_id) {
            error_log('No user ID found in checkout session');
            return;
        }

        if ($session->mode === 'subscription' && $session->subscription) {
            try {
                $stripe_subscription = \Stripe\Subscription::retrieve($session->subscription);
                self::update_subscription_from_stripe($user_id, $stripe_subscription);
            } catch (\Exception $e) {
                error_log('Failed to retrieve Stripe subscription: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle successful payment
     */
    private static function handle_payment_succeeded($invoice) {
        if ($invoice->subscription) {
            try {
                $stripe_subscription = \Stripe\Subscription::retrieve($invoice->subscription);
                $user_id = self::get_user_id_by_stripe_subscription($stripe_subscription->id);
                
                if ($user_id) {
                    self::update_subscription_from_stripe($user_id, $stripe_subscription);
                }
            } catch (\Exception $e) {
                error_log('Failed to handle payment succeeded: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle failed payment
     */
    private static function handle_payment_failed($invoice) {
        if ($invoice->subscription) {
            try {
                $stripe_subscription = \Stripe\Subscription::retrieve($invoice->subscription);
                $user_id = self::get_user_id_by_stripe_subscription($stripe_subscription->id);
                
                if ($user_id) {
                    // You might want to send an email notification here
                    error_log("Payment failed for user {$user_id}, subscription {$stripe_subscription->id}");
                }
            } catch (\Exception $e) {
                error_log('Failed to handle payment failed: ' . $e->getMessage());
            }
        }
    }

    /**
     * Handle subscription updated
     */
    private static function handle_subscription_updated($stripe_subscription) {
        $user_id = self::get_user_id_by_stripe_subscription($stripe_subscription->id);
        
        if ($user_id) {
            self::update_subscription_from_stripe($user_id, $stripe_subscription);
        }
    }

    /**
     * Handle subscription cancelled
     */
    private static function handle_subscription_cancelled($stripe_subscription) {
        $user_id = self::get_user_id_by_stripe_subscription($stripe_subscription->id);
        
        if ($user_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
            
            $wpdb->update(
                $table_name,
                [
                    'status' => 'cancelled',
                    'ends_at' => date('Y-m-d H:i:s', $stripe_subscription->current_period_end),
                ],
                ['user_id' => $user_id]
            );
        }
    }

    /**
     * Update local subscription from Stripe subscription object
     */
    private static function update_subscription_from_stripe($user_id, $stripe_subscription) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';

        $status = 'active';
        if ($stripe_subscription->status === 'canceled') {
            $status = 'cancelled';
        } elseif ($stripe_subscription->status === 'past_due') {
            $status = 'past_due';
        } elseif ($stripe_subscription->status === 'unpaid') {
            $status = 'unpaid';
        }

        $wpdb->update(
            $table_name,
            [
                'status' => $status,
                'stripe_subscription_id' => $stripe_subscription->id,
                'ends_at' => date('Y-m-d H:i:s', $stripe_subscription->current_period_end),
            ],
            ['user_id' => $user_id]
        );
    }

    /**
     * Get user ID by Stripe subscription ID
     */
    private static function get_user_id_by_stripe_subscription($stripe_subscription_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';

        $user_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT user_id FROM $table_name WHERE stripe_subscription_id = %s",
                $stripe_subscription_id
            )
        );

        return $user_id;
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

    /**
     * Cancel a subscription
     */
    public static function cancel_subscription($user_id) {
        if (!StripeConfig::is_configured()) {
            return false;
        }

        try {
            \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
            
            $subscription = self::get_subscription($user_id);
            if (!$subscription || empty($subscription['stripe_subscription_id'])) {
                return false;
            }

            // Cancel the subscription in Stripe (at period end)
            $stripe_subscription = \Stripe\Subscription::update(
                $subscription['stripe_subscription_id'],
                ['cancel_at_period_end' => true]
            );

            // Update local status
            global $wpdb;
            $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
            
            $wpdb->update(
                $table_name,
                ['status' => 'cancelled'],
                ['user_id' => $user_id]
            );

            return true;
        } catch (\Exception $e) {
            error_log('Failed to cancel subscription: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get subscription pricing information
     */
    public static function get_pricing_info() {
        if (!StripeConfig::is_configured()) {
            return null;
        }

        try {
            \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
            
            $price = \Stripe\Price::retrieve(StripeConfig::get_price_id());
            
            return [
                'amount' => $price->unit_amount,
                'currency' => $price->currency,
                'interval' => $price->recurring->interval,
                'interval_count' => $price->recurring->interval_count,
            ];
        } catch (\Exception $e) {
            error_log('Failed to get pricing info: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Manually sync subscription status with Stripe
     */
    public static function sync_subscription_status($user_id) {
        if (!StripeConfig::is_configured()) {
            return false;
        }

        $subscription = self::get_subscription($user_id);
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }

        try {
            \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
            
            // If we have a subscription ID, sync with it
            if ($subscription && !empty($subscription['stripe_subscription_id'])) {
                $stripe_subscription = \Stripe\Subscription::retrieve($subscription['stripe_subscription_id']);
                self::update_subscription_from_stripe($user_id, $stripe_subscription);
                return true;
            }
            
            // If no subscription ID, search for subscriptions by email
            $customers = \Stripe\Customer::all(['email' => $user->user_email, 'limit' => 10]);
            
            foreach ($customers->data as $customer) {
                $subscriptions = \Stripe\Subscription::all(['customer' => $customer->id]);
                
                foreach ($subscriptions->data as $stripe_subscription) {
                    // Only link active or trialing subscriptions
                    if (in_array($stripe_subscription->status, ['active', 'trialing'])) {
                        // Update or create local subscription
                        if ($subscription) {
                            // Update existing subscription
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
                            
                            $wpdb->update(
                                $table_name,
                                [
                                    'stripe_subscription_id' => $stripe_subscription->id,
                                    'stripe_customer_id' => $customer->id,
                                    'status' => self::map_stripe_status($stripe_subscription->status),
                                    'ends_at' => date('Y-m-d H:i:s', $stripe_subscription->current_period_end),
                                ],
                                ['user_id' => $user_id]
                            );
                        } else {
                            // Create new subscription record
                            self::create_subscription_from_stripe($user_id, $stripe_subscription, $customer->id);
                        }
                        
                        error_log("Linked Stripe subscription {$stripe_subscription->id} to user {$user_id}");
                        return true;
                    }
                }
            }
            
            return false;
        } catch (\Exception $e) {
            error_log('Failed to sync subscription status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create subscription record from Stripe subscription
     */
    private static function create_subscription_from_stripe($user_id, $stripe_subscription, $customer_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        $status = self::map_stripe_status($stripe_subscription->status);
        $trial_ends_at = null;
        
        if ($stripe_subscription->trial_end) {
            $trial_ends_at = date('Y-m-d H:i:s', $stripe_subscription->trial_end);
        }
        
        $data = [
            'user_id' => $user_id,
            'status' => $status,
            'stripe_customer_id' => $customer_id,
            'stripe_subscription_id' => $stripe_subscription->id,
            'trial_ends_at' => $trial_ends_at,
            'ends_at' => date('Y-m-d H:i:s', $stripe_subscription->current_period_end),
            'created_at' => date('Y-m-d H:i:s', $stripe_subscription->created),
        ];
        
        return $wpdb->insert($table_name, $data);
    }

    /**
     * Fix database constraint issues
     */
    public static function fix_database_constraints() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        // First, update all empty strings to NULL
        $wpdb->query("UPDATE $table_name SET stripe_subscription_id = NULL WHERE stripe_subscription_id = ''");
        
        // Remove duplicate NULL entries (keep only the first one)
        $wpdb->query("
            DELETE s1 FROM $table_name s1
            INNER JOIN $table_name s2 
            WHERE s1.id > s2.id 
            AND s1.stripe_subscription_id IS NULL 
            AND s2.stripe_subscription_id IS NULL
            AND s1.user_id = s2.user_id
        ");
        
        // Drop the existing unique constraint
        $wpdb->query("ALTER TABLE $table_name DROP INDEX IF EXISTS stripe_subscription_id_unique");
        
        // Add the unique constraint back (MySQL allows multiple NULL values in unique constraints)
        $wpdb->query("
            ALTER TABLE $table_name 
            ADD UNIQUE KEY stripe_subscription_id_unique (stripe_subscription_id)
        ");
        
        return true;
    }
}
