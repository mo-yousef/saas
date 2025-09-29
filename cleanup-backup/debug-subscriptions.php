<?php
/**
 * Debug script to check subscription functionality
 */

// Include WordPress
require_once('wp-config.php');

// Check if subscription table exists
global $wpdb;
$table_name = $wpdb->prefix . 'nordbooking_subscriptions';

echo "=== NORDBOOKING Subscription Debug ===\n\n";

// Check table existence
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
echo "1. Subscription table exists: " . ($table_exists ? "YES" : "NO") . "\n";

if (!$table_exists) {
    echo "   Creating subscription table...\n";
    \NORDBOOKING\Classes\Subscription::install();
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    echo "   Table created: " . ($table_exists ? "YES" : "NO") . "\n";
}

if ($table_exists) {
    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE $table_name");
    echo "\n2. Table structure:\n";
    foreach ($columns as $column) {
        echo "   - {$column->Field}: {$column->Type}\n";
    }
    
    // Count total subscriptions
    $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "\n3. Total subscriptions in database: $total_count\n";
    
    if ($total_count > 0) {
        // Show subscription breakdown by status
        $status_breakdown = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $table_name 
            GROUP BY status
        ");
        
        echo "\n4. Subscription breakdown by status:\n";
        foreach ($status_breakdown as $status) {
            echo "   - {$status->status}: {$status->count}\n";
        }
        
        // Show recent subscriptions
        $recent_subs = $wpdb->get_results("
            SELECT s.*, u.user_email, u.display_name
            FROM $table_name s
            LEFT JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
            ORDER BY s.created_at DESC
            LIMIT 5
        ");
        
        echo "\n5. Recent subscriptions:\n";
        foreach ($recent_subs as $sub) {
            $user_name = $sub->display_name ?: $sub->user_email ?: 'Unknown';
            echo "   - User: $user_name, Status: {$sub->status}, Created: {$sub->created_at}\n";
        }
    }
    
    // Check for business owners without subscriptions
    $auth_class = '\NORDBOOKING\Classes\Auth';
    $business_owners = get_users([
        'role' => $auth_class::ROLE_BUSINESS_OWNER,
        'fields' => 'ID'
    ]);
    
    $owners_without_subs = [];
    foreach ($business_owners as $owner_id) {
        $has_subscription = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $owner_id
        ));
        
        if (!$has_subscription) {
            $user = get_userdata($owner_id);
            $owners_without_subs[] = $user->user_email;
        }
    }
    
    echo "\n6. Business owners without subscriptions: " . count($owners_without_subs) . "\n";
    if (!empty($owners_without_subs)) {
        foreach ($owners_without_subs as $email) {
            echo "   - $email\n";
        }
        
        echo "\n   Creating missing trial subscriptions...\n";
        foreach ($business_owners as $owner_id) {
            $has_subscription = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                $owner_id
            ));
            
            if (!$has_subscription) {
                \NORDBOOKING\Classes\Subscription::create_trial_subscription($owner_id);
                $user = get_userdata($owner_id);
                echo "   - Created trial for: {$user->user_email}\n";
            }
        }
    }
}

// Check Stripe configuration
echo "\n7. Stripe configuration:\n";
if (class_exists('\NORDBOOKING\Classes\StripeConfig')) {
    $is_configured = \NORDBOOKING\Classes\StripeConfig::is_configured();
    echo "   - Stripe configured: " . ($is_configured ? "YES" : "NO") . "\n";
    
    if ($is_configured) {
        $test_mode = get_option('nordbooking_stripe_test_mode', true);
        echo "   - Test mode: " . ($test_mode ? "YES" : "NO") . "\n";
        
        $price_id = \NORDBOOKING\Classes\StripeConfig::get_price_id();
        echo "   - Price ID: " . ($price_id ?: "NOT SET") . "\n";
    }
} else {
    echo "   - StripeConfig class not found\n";
}

echo "\n=== Debug Complete ===\n";