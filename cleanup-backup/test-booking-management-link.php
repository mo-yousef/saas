<?php
/**
 * Test Booking Management Link Generation
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Test the link generation
$test_booking_id = 123;
$test_email = 'customer@example.com';

if (class_exists('NORDBOOKING\Classes\Bookings')) {
    $link = \NORDBOOKING\Classes\Bookings::generate_customer_booking_link($test_booking_id, $test_email);
    echo "Generated booking management link:\n";
    echo $link . "\n\n";
    
    // Test token verification
    $token = parse_url($link, PHP_URL_QUERY);
    parse_str($token, $params);
    $extracted_token = $params['token'] ?? '';
    
    echo "Extracted token: " . $extracted_token . "\n";
    
    // Generate expected token for verification
    $expected_token = hash('sha256', $test_booking_id . $test_email . wp_salt());
    echo "Expected token: " . $expected_token . "\n";
    
    if ($extracted_token === $expected_token) {
        echo "✅ Token verification: PASSED\n";
    } else {
        echo "❌ Token verification: FAILED\n";
    }
} else {
    echo "❌ NORDBOOKING Bookings class not found\n";
}

// Test if the route is working
echo "\nTesting route availability:\n";
echo "Customer booking management URL: " . home_url('/customer-booking-management/') . "\n";

// Check if rewrite rules include our custom rule
$rewrite_rules = get_option('rewrite_rules');
if (isset($rewrite_rules['customer-booking-management/?$'])) {
    echo "✅ Rewrite rule found\n";
} else {
    echo "❌ Rewrite rule not found - you may need to flush rewrite rules\n";
    echo "Available rewrite rules:\n";
    foreach ($rewrite_rules as $pattern => $replacement) {
        if (strpos($pattern, 'customer') !== false || strpos($pattern, 'booking') !== false) {
            echo "  $pattern => $replacement\n";
        }
    }
}
?>