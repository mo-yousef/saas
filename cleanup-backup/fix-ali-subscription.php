<?php
/**
 * Quick fix for Ali's subscription
 * Run this once: yourdomain.com/wp-content/themes/yourtheme/fix-ali-subscription.php
 */

// Load WordPress
$wp_load_paths = [
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('Could not load WordPress. Please run this script from your theme directory.');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be logged in as an administrator to run this script.');
}

echo '<h1>Fix Ali\'s Subscription</h1>';

// Get user
$user = get_user_by('email', 'ali@test.com');
if (!$user) {
    die('User ali@test.com not found.');
}

$user_id = $user->ID;
echo '<p>Found user: ' . $user->display_name . ' (ID: ' . $user_id . ')</p>';

// Try to sync subscription
echo '<h2>Attempting to sync subscription...</h2>';

$sync_result = \NORDBOOKING\Classes\Subscription::sync_subscription_status($user_id);

if ($sync_result) {
    echo '<p style="color: green;"><strong>SUCCESS!</strong> Subscription synced successfully.</p>';
} else {
    echo '<p style="color: orange;"><strong>No automatic sync possible.</strong> Searching manually...</p>';
    
    // Manual search and link
    if (\NORDBOOKING\Classes\StripeConfig::is_configured()) {
        try {
            \Stripe\Stripe::setApiKey(\NORDBOOKING\Classes\StripeConfig::get_secret_key());
            
            echo '<p>Searching Stripe for customers with email: ali@test.com</p>';
            $customers = \Stripe\Customer::all(['email' => 'ali@test.com', 'limit' => 10]);
            
            if (!empty($customers->data)) {
                echo '<p>Found ' . count($customers->data) . ' customers in Stripe.</p>';
                
                foreach ($customers->data as $customer) {
                    echo '<p>Checking customer: ' . $customer->id . '</p>';
                    
                    $subscriptions = \Stripe\Subscription::all(['customer' => $customer->id]);
                    
                    if (!empty($subscriptions->data)) {
                        echo '<p>Found ' . count($subscriptions->data) . ' subscriptions for this customer.</p>';
                        
                        foreach ($subscriptions->data as $stripe_subscription) {
                            echo '<p>Subscription: ' . $stripe_subscription->id . ' - Status: ' . $stripe_subscription->status . '</p>';
                            
                            if (in_array($stripe_subscription->status, ['active', 'trialing'])) {
                                echo '<p style="color: blue;">This subscription is active/trialing. Linking to user...</p>';
                                
                                // Update local subscription
                                global $wpdb;
                                $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
                                
                                $local_subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
                                
                                if ($local_subscription) {
                                    // Update existing
                                    $result = $wpdb->update(
                                        $table_name,
                                        [
                                            'stripe_subscription_id' => $stripe_subscription->id,
                                            'stripe_customer_id' => $customer->id,
                                            'status' => 'active',
                                            'ends_at' => date('Y-m-d H:i:s', $stripe_subscription->current_period_end),
                                        ],
                                        ['user_id' => $user_id]
                                    );
                                } else {
                                    // Create new
                                    $result = $wpdb->insert(
                                        $table_name,
                                        [
                                            'user_id' => $user_id,
                                            'status' => 'active',
                                            'stripe_customer_id' => $customer->id,
                                            'stripe_subscription_id' => $stripe_subscription->id,
                                            'ends_at' => date('Y-m-d H:i:s', $stripe_subscription->current_period_end),
                                            'created_at' => date('Y-m-d H:i:s', $stripe_subscription->created),
                                        ]
                                    );
                                }
                                
                                if ($result !== false) {
                                    echo '<p style="color: green;"><strong>SUCCESS!</strong> Subscription linked successfully.</p>';
                                    break 2; // Break out of both loops
                                } else {
                                    echo '<p style="color: red;">Failed to update database: ' . $wpdb->last_error . '</p>';
                                }
                            }
                        }
                    }
                }
            } else {
                echo '<p style="color: red;">No customers found in Stripe with email ali@test.com</p>';
            }
            
        } catch (\Exception $e) {
            echo '<p style="color: red;">Stripe error: ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p style="color: red;">Stripe is not configured.</p>';
    }
}

// Check final status
echo '<h2>Final Status Check</h2>';
$final_status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
echo '<p><strong>Current Status:</strong> ' . $final_status . '</p>';

$final_subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
if ($final_subscription) {
    echo '<p><strong>Stripe Subscription ID:</strong> ' . ($final_subscription['stripe_subscription_id'] ?: 'None') . '</p>';
    echo '<p><strong>Stripe Customer ID:</strong> ' . ($final_subscription['stripe_customer_id'] ?: 'None') . '</p>';
}

echo '<hr>';
echo '<p><a href="' . home_url('/dashboard/subscription/') . '">Go to Subscription Page</a></p>';
echo '<p><a href="' . admin_url('admin.php?page=nordbooking-consolidated-admin') . '">Go to Admin Panel</a></p>';
?>