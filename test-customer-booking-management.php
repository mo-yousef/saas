<?php
/**
 * Test Customer Booking Management Access
 * 
 * This script helps you test the customer booking management functionality
 * by generating a test token for an existing booking.
 */

// Load WordPress
if (file_exists(__DIR__ . '/../../../wp-load.php')) {
    require_once __DIR__ . '/../../../wp-load.php';
} elseif (file_exists(__DIR__ . '/../../../../wp-load.php')) {
    require_once __DIR__ . '/../../../../wp-load.php';
} else {
    die('WordPress not found. Please check the path to wp-load.php');
}

if (!is_admin() && !current_user_can('manage_options')) {
    die('Access denied. Admin privileges required.');
}

// Get a sample booking for testing
global $wpdb;
$bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');
$sample_booking = $wpdb->get_row(
    "SELECT * FROM $bookings_table WHERE status IN ('pending', 'confirmed') ORDER BY booking_id DESC LIMIT 1"
);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Customer Booking Management</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 40px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px 0 0; }
        .button:hover { background: #005a8b; color: white; }
        .button.secondary { background: #6c757d; }
        .button.secondary:hover { background: #545b62; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .booking-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>Test Customer Booking Management</h1>
    
    <?php if ($sample_booking): ?>
        <?php
        // Generate the secure token
        $booking_token = hash('sha256', $sample_booking->booking_id . $sample_booking->customer_email . wp_salt());
        $test_url = home_url('/customer-booking-management/?token=' . $booking_token);
        ?>
        
        <div class="success">
            <strong>✅ Test Booking Found!</strong><br>
            We found a booking that you can use to test the enhanced customer booking management page.
        </div>
        
        <div class="booking-details">
            <h3>Test Booking Details:</h3>
            <ul>
                <li><strong>Booking ID:</strong> <?php echo esc_html($sample_booking->booking_id); ?></li>
                <li><strong>Reference:</strong> <?php echo esc_html($sample_booking->booking_reference); ?></li>
                <li><strong>Customer:</strong> <?php echo esc_html($sample_booking->customer_name); ?></li>
                <li><strong>Email:</strong> <?php echo esc_html($sample_booking->customer_email); ?></li>
                <li><strong>Date:</strong> <?php echo esc_html(date('F j, Y', strtotime($sample_booking->booking_date))); ?></li>
                <li><strong>Time:</strong> <?php echo esc_html(date('g:i A', strtotime($sample_booking->booking_time))); ?></li>
                <li><strong>Status:</strong> <?php echo esc_html(ucfirst($sample_booking->status)); ?></li>
                <li><strong>Total:</strong> $<?php echo esc_html(number_format($sample_booking->total_price, 2)); ?></li>
            </ul>
        </div>
        
        <div class="info">
            <strong>Test URL Generated:</strong><br>
            <code><?php echo esc_html($test_url); ?></code>
        </div>
        
        <a href="<?php echo esc_url($test_url); ?>" class="button" target="_blank">
            🚀 Test Enhanced Booking Management Page
        </a>
        
        <a href="<?php echo home_url('/dashboard/bookings/'); ?>" class="button secondary">
            📊 Go to Dashboard Bookings
        </a>
        
        <h2>What to Test</h2>
        <ol>
            <li><strong>Timeline Infographic:</strong> Check the visual booking journey at the top</li>
            <li><strong>Information Cards:</strong> Verify the color-coded date, location, and pricing cards</li>
            <li><strong>Service Details:</strong> Review the enhanced service display with options</li>
            <li><strong>Sidebar Actions:</strong> Test the reschedule and cancel buttons</li>
            <li><strong>Invoice Functions:</strong> Try the download, print, and email options</li>
            <li><strong>Mobile Responsiveness:</strong> Test on different screen sizes</li>
            <li><strong>Animations:</strong> Check hover effects and transitions</li>
        </ol>
        
        <h2>Dashboard Integration Test</h2>
        <p>To test the full integration:</p>
        <ol>
            <li>Go to your <a href="<?php echo home_url('/dashboard/bookings/'); ?>">Dashboard Bookings</a> page</li>
            <li>Find the booking with reference <strong><?php echo esc_html($sample_booking->booking_reference); ?></strong></li>
            <li>Click "View Details" - it should redirect to the enhanced page</li>
            <li>Verify all features work as expected</li>
        </ol>
        
    <?php else: ?>
        <div class="warning">
            <strong>⚠️ No Test Bookings Found</strong><br>
            No bookings with 'pending' or 'confirmed' status were found in your database. 
            You'll need to create a booking first to test the customer booking management page.
        </div>
        
        <a href="<?php echo home_url('/dashboard/bookings/'); ?>" class="button">
            📊 Go to Dashboard Bookings
        </a>
        
        <div class="info">
            <strong>To create a test booking:</strong><br>
            1. Go to your booking form on your website<br>
            2. Create a test booking<br>
            3. Return to this page to generate a test link
        </div>
    <?php endif; ?>
    
    <h2>Troubleshooting</h2>
    <ul>
        <li>If the URL doesn't work, run the <code>flush-rewrite-rules-customer-booking.php</code> script</li>
        <li>Make sure your WordPress permalinks are not set to "Plain"</li>
        <li>Check that the <code>page-customer-booking-management.php</code> file exists in your theme</li>
        <li>Verify that the booking token is being generated correctly</li>
    </ul>
</body>
</html>