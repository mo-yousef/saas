<?php
/**
 * Debug page for subscriptions - to be accessed via WordPress admin
 * Add this to your WordPress admin by visiting: /wp-admin/admin.php?page=debug-subscriptions
 */

// Add this to functions.php or create as a plugin
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Debug Subscriptions',
        'Debug Subscriptions',
        'manage_options',
        'debug-subscriptions',
        'nordbooking_debug_subscriptions_page'
    );
});

function nordbooking_debug_subscriptions_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
    
    echo '<div class="wrap">';
    echo '<h1>NORDBOOKING Subscription Debug</h1>';
    
    // Check if we should create missing subscriptions
    if (isset($_POST['create_missing_subscriptions'])) {
        $auth_class = '\NORDBOOKING\Classes\Auth';
        $business_owners = get_users([
            'role' => $auth_class::ROLE_BUSINESS_OWNER,
        ]);
        
        $created_count = 0;
        foreach ($business_owners as $owner) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                $owner->ID
            ));
            
            if (!$existing) {
                \NORDBOOKING\Classes\Subscription::create_trial_subscription($owner->ID);
                $created_count++;
            }
        }
        
        echo '<div class="notice notice-success"><p>Created ' . $created_count . ' trial subscriptions.</p></div>';
    }
    
    // Check table existence
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    echo '<h2>1. Table Status</h2>';
    echo '<p>Subscription table exists: <strong>' . ($table_exists ? 'YES' : 'NO') . '</strong></p>';
    
    if (!$table_exists) {
        echo '<p><em>Creating subscription table...</em></p>';
        \NORDBOOKING\Classes\Subscription::install();
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        echo '<p>Table created: <strong>' . ($table_exists ? 'YES' : 'NO') . '</strong></p>';
    }
    
    if ($table_exists) {
        // Count total subscriptions
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        echo '<h2>2. Subscription Count</h2>';
        echo '<p>Total subscriptions: <strong>' . $total_count . '</strong></p>';
        
        // Show subscription breakdown by status
        $status_breakdown = $wpdb->get_results("
            SELECT status, COUNT(*) as count 
            FROM $table_name 
            GROUP BY status
        ");
        
        if (!empty($status_breakdown)) {
            echo '<h2>3. Status Breakdown</h2>';
            echo '<ul>';
            foreach ($status_breakdown as $status) {
                echo '<li>' . ucfirst($status->status) . ': <strong>' . $status->count . '</strong></li>';
            }
            echo '</ul>';
        }
        
        // Show recent subscriptions
        $recent_subs = $wpdb->get_results("
            SELECT s.*, u.user_email, u.display_name
            FROM $table_name s
            LEFT JOIN {$wpdb->prefix}users u ON s.user_id = u.ID
            ORDER BY s.created_at DESC
            LIMIT 10
        ");
        
        if (!empty($recent_subs)) {
            echo '<h2>4. Recent Subscriptions</h2>';
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>User</th><th>Email</th><th>Status</th><th>Trial Ends</th><th>Created</th></tr></thead>';
            echo '<tbody>';
            foreach ($recent_subs as $sub) {
                $user_name = $sub->display_name ?: 'Unknown';
                echo '<tr>';
                echo '<td>' . esc_html($user_name) . '</td>';
                echo '<td>' . esc_html($sub->user_email) . '</td>';
                echo '<td>' . esc_html($sub->status) . '</td>';
                echo '<td>' . esc_html($sub->trial_ends_at ?: 'N/A') . '</td>';
                echo '<td>' . esc_html($sub->created_at) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        
        // Check for business owners without subscriptions
        $auth_class = '\NORDBOOKING\Classes\Auth';
        $business_owners = get_users([
            'role' => $auth_class::ROLE_BUSINESS_OWNER,
        ]);
        
        $owners_without_subs = [];
        foreach ($business_owners as $owner) {
            $has_subscription = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                $owner->ID
            ));
            
            if (!$has_subscription) {
                $owners_without_subs[] = $owner;
            }
        }
        
        echo '<h2>5. Missing Subscriptions</h2>';
        echo '<p>Business owners without subscriptions: <strong>' . count($owners_without_subs) . '</strong></p>';
        
        if (!empty($owners_without_subs)) {
            echo '<ul>';
            foreach ($owners_without_subs as $owner) {
                echo '<li>' . esc_html($owner->display_name ?: $owner->user_email) . ' (ID: ' . $owner->ID . ')</li>';
            }
            echo '</ul>';
            
            echo '<form method="post">';
            echo '<input type="submit" name="create_missing_subscriptions" class="button button-primary" value="Create Missing Trial Subscriptions" />';
            echo '</form>';
        }
    }
    
    // Check Stripe configuration
    echo '<h2>6. Stripe Configuration</h2>';
    if (class_exists('\NORDBOOKING\Classes\StripeConfig')) {
        $is_configured = \NORDBOOKING\Classes\StripeConfig::is_configured();
        echo '<p>Stripe configured: <strong>' . ($is_configured ? 'YES' : 'NO') . '</strong></p>';
        
        if ($is_configured) {
            $test_mode = get_option('nordbooking_stripe_test_mode', true);
            echo '<p>Test mode: <strong>' . ($test_mode ? 'YES' : 'NO') . '</strong></p>';
            
            $price_id = \NORDBOOKING\Classes\StripeConfig::get_price_id();
            echo '<p>Price ID: <strong>' . ($price_id ?: 'NOT SET') . '</strong></p>';
        }
    } else {
        echo '<p><em>StripeConfig class not found</em></p>';
    }
    
    echo '</div>';
}
?>