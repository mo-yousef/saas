<?php
/**
 * Flush WordPress rewrite rules to enable new customer booking management routes
 */

if (!defined('ABSPATH')) {
    require_once('../../../wp-config.php');
}

// Flush rewrite rules to enable the new customer booking management route
flush_rewrite_rules();

echo "Rewrite rules flushed successfully!\n";
echo "The customer booking management route should now be available at:\n";
echo home_url('/customer-booking-management/') . "\n";
?>