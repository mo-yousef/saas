<?php
/**
 * Flush Rewrite Rules for Customer Booking Management
 * 
 * Run this script once to activate the new customer booking management URL structure.
 * After running this, you can access: /customer-booking-management/?token=...
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

// Flush rewrite rules
flush_rewrite_rules();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Booking Management - Rewrite Rules Flushed</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 40px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px 0 0; }
        .button:hover { background: #005a8b; color: white; }
    </style>
</head>
<body>
    <h1>Customer Booking Management - URL Setup Complete</h1>
    
    <div class="success">
        <strong>✅ Success!</strong><br>
        Rewrite rules have been flushed and the customer booking management URL is now active.
    </div>
    
    <div class="info">
        <strong>What this means:</strong><br>
        - The enhanced customer booking management page is now accessible<br>
        - URLs like <code>/customer-booking-management/?token=...</code> will work<br>
        - "View Details" links from the dashboard will redirect to the enhanced page<br>
        - The new infographic-style timeline and enhanced sidebar are now active
    </div>
    
    <h2>Test the Enhanced System</h2>
    <p>You can now test the enhanced customer booking management system:</p>
    
    <a href="<?php echo home_url('/dashboard/bookings/'); ?>" class="button">
        Go to Dashboard Bookings
    </a>
    
    <h2>How It Works</h2>
    <ol>
        <li>Go to your dashboard bookings page</li>
        <li>Click "View Details" on any booking</li>
        <li>You'll be redirected to the enhanced customer booking management page</li>
        <li>Experience the new timeline infographic and improved interface</li>
    </ol>
    
    <h2>Features of the Enhanced Page</h2>
    <ul>
        <li><strong>Timeline Infographic:</strong> Visual booking journey with progress indicators</li>
        <li><strong>Enhanced Information Cards:</strong> Color-coded cards for date, location, and pricing</li>
        <li><strong>Improved Sidebar:</strong> Quick actions, invoice management, and support</li>
        <li><strong>Better Service Display:</strong> Enhanced service cards with options and pricing</li>
        <li><strong>Mobile Responsive:</strong> Optimized for all devices</li>
        <li><strong>Modern Design:</strong> Professional appearance with gradients and animations</li>
    </ul>
    
    <div class="info">
        <strong>Note:</strong> If you encounter any issues, make sure your WordPress permalinks are set to "Post name" or another custom structure (not "Plain").
    </div>
</body>
</html>