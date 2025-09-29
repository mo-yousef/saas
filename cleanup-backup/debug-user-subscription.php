<?php
/**
 * Debug specific user subscription
 * Access this file directly: yourdomain.com/wp-content/themes/yourtheme/debug-user-subscription.php
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

echo '<h1>User Subscription Debug</h1>';

// Get user by email
$user_email = 'ali@test.com'; // Change this to the user's email
$user = get_user_by('email', $user_email);

if (!$user) {
    die('User not found with email: ' . $user_email);
}

$user_id = $user->ID;
echo '<h2>User Information</h2>';
echo '<p><strong>User ID:</strong> ' . $user_id . '</p>';
echo '<p><strong>Email:</strong> ' . $user->user_email . '</p>';
echo '<p><strong>Display Name:</strong> ' . $user->display_name . '</p>';

// Get subscription data
global $wpdb;
$table_name = $wpdb->prefix . 'nordbooking_subscriptions';

$subscription = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d", $user_id),
    ARRAY_A
);

echo '<h2>Local Subscription Data</h2>';
if ($subscription) {
    echo '<table border="1" cellpadding="5">';
    foreach ($subscription as $key => $value) {
        echo '<tr><td><strong>' . $key . '</strong></td><td>' . ($value ?: 'NULL') . '</td></tr>';
    }
    echo '</table>';
} else {
    echo '<p>No subscription found in local database.</p>';
}

// Check Stripe data if subscription ID exists
if ($subscription && !empty($subscription['stripe_subscription_id'])) {
    echo '<h2>Stripe Subscription Data</h2>';
    
    if (\NORDBOOKING\Classes\StripeConfig::is_configured()) {
        try {
            \Stripe\Stripe::setApiKey(\NORDBOOKING\Classes\StripeConfig::get_secret_key());
            $stripe_subscription = \Stripe\Subscription::retrieve($subscription['stripe_subscription_id']);
            
            echo '<p><strong>Stripe Status:</strong> ' . $stripe_subscription->status . '</p>';
            echo '<p><strong>Current Period Start:</strong> ' . date('Y-m-d H:i:s', $stripe_subscription->current_period_start) . '</p>';
            echo '<p><strong>Current Period End:</strong> ' . date('Y-m-d H:i:s', $stripe_subscription->current_period_end) . '</p>';
            echo '<p><strong>Created:</strong> ' . date('Y-m-d H:i:s', $stripe_subscription->created) . '</p>';
            
            if ($stripe_subscription->trial_end) {
                echo '<p><strong>Trial End:</strong> ' . date('Y-m-d H:i:s', $stripe_subscription->trial_end) . '</p>';
            }
            
            // Show items
            if (!empty($stripe_subscription->items->data)) {
                $item = $stripe_subscription->items->data[0];
                echo '<p><strong>Price ID:</strong> ' . $item->price->id . '</p>';
                echo '<p><strong>Amount:</strong> $' . ($item->price->unit_amount / 100) . '</p>';
            }
            
        } catch (\Exception $e) {
            echo '<p style="color: red;"><strong>Stripe Error:</strong> ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p style="color: orange;">Stripe not configured.</p>';
    }
} else {
    echo '<h2>No Stripe Subscription ID</h2>';
    echo '<p>This subscription doesn\'t have a Stripe subscription ID, which means:</p>';
    echo '<ul>';
    echo '<li>Payment might not have been completed</li>';
    echo '<li>Webhook might not have been called</li>';
    echo '<li>Subscription was created manually</li>';
    echo '</ul>';
}

// Get current status using the class method
echo '<h2>Current Status Check</h2>';
$current_status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
echo '<p><strong>Status from get_subscription_status():</strong> ' . $current_status . '</p>';

// Check if there are any Stripe customers for this user
if (\NORDBOOKING\Classes\StripeConfig::is_configured()) {
    echo '<h2>Stripe Customer Search</h2>';
    try {
        \Stripe\Stripe::setApiKey(\NORDBOOKING\Classes\StripeConfig::get_secret_key());
        $customers = \Stripe\Customer::all(['email' => $user->user_email, 'limit' => 10]);
        
        if (!empty($customers->data)) {
            echo '<p>Found ' . count($customers->data) . ' Stripe customers:</p>';
            foreach ($customers->data as $customer) {
                echo '<div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">';
                echo '<p><strong>Customer ID:</strong> ' . $customer->id . '</p>';
                echo '<p><strong>Email:</strong> ' . $customer->email . '</p>';
                echo '<p><strong>Created:</strong> ' . date('Y-m-d H:i:s', $customer->created) . '</p>';
                
                // Get subscriptions for this customer
                $subscriptions = \Stripe\Subscription::all(['customer' => $customer->id]);
                if (!empty($subscriptions->data)) {
                    echo '<p><strong>Subscriptions:</strong></p>';
                    foreach ($subscriptions->data as $sub) {
                        echo '<ul>';
                        echo '<li>ID: ' . $sub->id . '</li>';
                        echo '<li>Status: ' . $sub->status . '</li>';
                        echo '<li>Created: ' . date('Y-m-d H:i:s', $sub->created) . '</li>';
                        echo '</ul>';
                        
                        // Check if this subscription should be linked to our user
                        if ($subscription && empty($subscription['stripe_subscription_id'])) {
                            echo '<p style="color: blue;"><strong>This subscription could be linked to the user!</strong></p>';
                            echo '<form method="post">';
                            echo '<input type="hidden" name="link_subscription" value="1">';
                            echo '<input type="hidden" name="user_id" value="' . $user_id . '">';
                            echo '<input type="hidden" name="stripe_subscription_id" value="' . $sub->id . '">';
                            echo '<input type="hidden" name="stripe_customer_id" value="' . $customer->id . '">';
                            echo '<input type="submit" value="Link This Subscription" class="button">';
                            echo '</form>';
                        }
                    }
                } else {
                    echo '<p>No subscriptions found for this customer.</p>';
                }
                echo '</div>';
            }
        } else {
            echo '<p>No Stripe customers found with email: ' . $user->user_email . '</p>';
        }
    } catch (\Exception $e) {
        echo '<p style="color: red;"><strong>Error searching Stripe:</strong> ' . $e->getMessage() . '</p>';
    }
}

// Handle linking subscription
if (isset($_POST['link_subscription']) && $_POST['link_subscription'] == '1') {
    $user_id = intval($_POST['user_id']);
    $stripe_subscription_id = sanitize_text_field($_POST['stripe_subscription_id']);
    $stripe_customer_id = sanitize_text_field($_POST['stripe_customer_id']);
    
    $result = $wpdb->update(
        $table_name,
        [
            'stripe_subscription_id' => $stripe_subscription_id,
            'stripe_customer_id' => $stripe_customer_id,
            'status' => 'active'
        ],
        ['user_id' => $user_id]
    );
    
    if ($result !== false) {
        echo '<div style="background: green; color: white; padding: 10px; margin: 10px 0;">';
        echo '<p><strong>SUCCESS!</strong> Subscription linked successfully.</p>';
        echo '</div>';
        
        // Refresh the page to show updated data
        echo '<script>setTimeout(function(){ location.reload(); }, 2000);</script>';
    } else {
        echo '<div style="background: red; color: white; padding: 10px; margin: 10px 0;">';
        echo '<p><strong>ERROR!</strong> Failed to link subscription.</p>';
        echo '</div>';
    }
}

echo '<hr>';
echo '<h2>Actions</h2>';
echo '<p><a href="' . admin_url('admin.php?page=nordbooking-consolidated-admin') . '">Go to NORDBOOKING Admin</a></p>';
echo '<p><a href="' . home_url('/dashboard/subscription/') . '">Go to User Subscription Page</a></p>';
?>