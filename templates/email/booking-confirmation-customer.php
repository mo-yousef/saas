<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<h2><?php _e('Booking Confirmed!', 'mobooking'); ?></h2>
<p><?php printf(__('Thank you for your booking with %s. Your booking (Ref: %s) is confirmed.', 'mobooking'), '<strong>%%TENANT_BUSINESS_NAME%%</strong>', '<strong>%%BOOKING_REFERENCE%%</strong>'); ?></p>
<div class="booking-details">
    <h3><?php _e('Booking Summary:', 'mobooking'); ?></h3>
    <ul>
        <li><strong><?php _e('Services:', 'mobooking'); ?></strong> %%SERVICE_NAMES%%</li>
        <li><strong><?php _e('Date & Time:', 'mobooking'); ?></strong> %%BOOKING_DATE_TIME%%</li>
        <li><strong><?php _e('Service Address:', 'mobooking'); ?></strong><br>%%SERVICE_ADDRESS%%</li>
        <li><strong><?php _e('Total Price:', 'mobooking'); ?></strong> %%TOTAL_PRICE%%</li>
    </ul>
</div>
<p><?php printf(__('If you have any questions, please contact %s.', 'mobooking'), '%%TENANT_BUSINESS_NAME%%'); ?></p>
