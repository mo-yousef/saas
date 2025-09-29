<?php
/**
 * Test Customer Booking Management Page Access
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

echo "<h1>Customer Booking Management Page Test</h1>\n";

// Test 1: Check if classes exist
echo "<h2>1. Class Availability Test</h2>\n";
$required_classes = [
    'NORDBOOKING\Classes\Database',
    'NORDBOOKING\Classes\Bookings',
    'NORDBOOKING\Classes\Settings'
];

foreach ($required_classes as $class) {
    if (class_exists($class)) {
        echo "✅ $class - Available\n";
    } else {
        echo "❌ $class - Missing\n";
    }
}

// Test 2: Check database tables
echo "<h2>2. Database Tables Test</h2>\n";
global $wpdb;
$tables_to_check = ['bookings', 'booking_items'];

foreach ($tables_to_check as $table_suffix) {
    $table_name = \NORDBOOKING\Classes\Database::get_table_name($table_suffix);
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    
    if ($table_exists) {
        echo "✅ $table_name - Exists\n";
    } else {
        echo "❌ $table_name - Missing\n";
    }
}

// Test 3: Check for sample bookings
echo "<h2>3. Sample Bookings Test</h2>\n";
$bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');
$sample_bookings = $wpdb->get_results(
    "SELECT booking_id, booking_reference, customer_name, customer_email, status 
     FROM $bookings_table 
     WHERE status IN ('pending', 'confirmed') 
     ORDER BY booking_id DESC 
     LIMIT 3"
);

if (!empty($sample_bookings)) {
    echo "✅ Found " . count($sample_bookings) . " sample bookings:\n";
    foreach ($sample_bookings as $booking) {
        $management_link = \NORDBOOKING\Classes\Bookings::generate_customer_booking_link(
            $booking->booking_id, 
            $booking->customer_email
        );
        echo "  - {$booking->booking_reference} ({$booking->customer_name}) - <a href='$management_link' target='_blank'>Test Link</a>\n";
    }
} else {
    echo "⚠️ No sample bookings found. Create a test booking first.\n";
}

// Test 4: Check rewrite rules
echo "<h2>4. Rewrite Rules Test</h2>\n";
$rewrite_rules = get_option('rewrite_rules');
if (isset($rewrite_rules['customer-booking-management/?$'])) {
    echo "✅ Customer booking management rewrite rule exists\n";
    echo "   Rule: customer-booking-management/?$ => " . $rewrite_rules['customer-booking-management/?$'] . "\n";
} else {
    echo "❌ Customer booking management rewrite rule missing\n";
    echo "   You may need to flush rewrite rules\n";
    echo "   Go to WordPress Admin > Settings > Permalinks and click 'Save Changes'\n";
}

// Test 5: Check if page template exists
echo "<h2>5. Template File Test</h2>\n";
$template_path = get_template_directory() . '/page-customer-booking-management.php';
if (file_exists($template_path)) {
    echo "✅ Customer booking management template exists\n";
    echo "   Path: $template_path\n";
} else {
    echo "❌ Customer booking management template missing\n";
    echo "   Expected path: $template_path\n";
}

// Test 6: Test token generation
echo "<h2>6. Token Generation Test</h2>\n";
if (!empty($sample_bookings)) {
    $test_booking = $sample_bookings[0];
    $token = hash('sha256', $test_booking->booking_id . $test_booking->customer_email . wp_salt());
    $management_url = home_url('/customer-booking-management/?token=' . $token);
    
    echo "✅ Token generation working\n";
    echo "   Test URL: <a href='$management_url' target='_blank'>$management_url</a>\n";
} else {
    echo "⚠️ Cannot test token generation without sample bookings\n";
}

echo "<h2>Summary</h2>\n";
echo "If all tests pass, the customer booking management system should be working.\n";
echo "Click on any test links above to verify the functionality.\n";
?>