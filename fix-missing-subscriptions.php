<?php
/**
 * Fix missing subscriptions for business owners
 * Run this file once by accessing it directly in your browser: yoursite.com/wp-content/themes/yourtheme/fix-missing-subscriptions.php
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

echo '<h1>NORDBOOKING Subscription Fix</h1>';

// Ensure subscription table exists
global $wpdb;
$table_name = $wpdb->prefix . 'nordbooking_subscriptions';

$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
if (!$table_exists) {
    echo '<p>Creating subscription table...</p>';
    \NORDBOOKING\Classes\Subscription::install();
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    echo '<p>Table created: ' . ($table_exists ? 'YES' : 'NO') . '</p>';
}

if (!$table_exists) {
    die('Failed to create subscription table.');
}

// Get all business owners
$auth_class = '\NORDBOOKING\Classes\Auth';
$business_owners = get_users([
    'role' => $auth_class::ROLE_BUSINESS_OWNER,
]);

echo '<p>Found ' . count($business_owners) . ' business owners.</p>';

$created_count = 0;
$existing_count = 0;

foreach ($business_owners as $owner) {
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
        $owner->ID
    ));
    
    if ($existing) {
        $existing_count++;
        echo '<p>✓ ' . esc_html($owner->display_name ?: $owner->user_email) . ' already has a subscription.</p>';
    } else {
        \NORDBOOKING\Classes\Subscription::create_trial_subscription($owner->ID);
        $created_count++;
        echo '<p>+ Created trial subscription for ' . esc_html($owner->display_name ?: $owner->user_email) . '</p>';
    }
}

echo '<hr>';
echo '<h2>Summary</h2>';
echo '<p>Existing subscriptions: <strong>' . $existing_count . '</strong></p>';
echo '<p>Created subscriptions: <strong>' . $created_count . '</strong></p>';

// Show current subscription status
$total_subs = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
echo '<p>Total subscriptions in database: <strong>' . $total_subs . '</strong></p>';

$status_breakdown = $wpdb->get_results("
    SELECT status, COUNT(*) as count 
    FROM $table_name 
    GROUP BY status
");

if (!empty($status_breakdown)) {
    echo '<h3>Status Breakdown:</h3>';
    echo '<ul>';
    foreach ($status_breakdown as $status) {
        echo '<li>' . ucfirst($status->status) . ': <strong>' . $status->count . '</strong></li>';
    }
    echo '</ul>';
}

echo '<p><strong>Fix complete!</strong> You can now check the subscription management page in your admin.</p>';
echo '<p><a href="' . admin_url('admin.php?page=nordbooking-consolidated-admin') . '">Go to NORDBOOKING Admin</a></p>';
?>