<?php
/**
 * Admin utility to flush rewrite rules
 * Add this to your WordPress admin or call it directly
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

// Flush rewrite rules
flush_rewrite_rules();

// Show success message
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rewrite Rules Flushed</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
    </style>
</head>
<body>
    <h1>WordPress Rewrite Rules Flushed</h1>
    
    <div class="success">
        ✅ Rewrite rules have been successfully flushed!
    </div>
    
    <div class="info">
        <strong>What this means:</strong><br>
        - The customer booking management route should now be active<br>
        - URLs like <code>/customer-booking-management/?token=...</code> should work<br>
        - Custom routing for the booking system is now enabled
    </div>
    
    <h2>Test the System</h2>
    <p>You can now test the customer booking management system:</p>
    
    <a href="<?php echo home_url('/test-customer-page-access.php'); ?>" class="button" target="_blank">
        Run System Tests
    </a>
    
    <a href="<?php echo home_url('/test-customer-booking-management.php'); ?>" class="button" target="_blank">
        View Test Bookings
    </a>
    
    <a href="<?php echo admin_url(); ?>" class="button">
        Back to WordPress Admin
    </a>
    
    <h2>Next Steps</h2>
    <ol>
        <li>Create a test booking through your booking form</li>
        <li>Check that the booking confirmation email includes the management link</li>
        <li>Test the reschedule and cancel functionality</li>
        <li>Verify that you receive email notifications when customers make changes</li>
    </ol>
</body>
</html>