<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<h2><?php _e('Booking Confirmed!', 'NORDBOOKING'); ?></h2>
<p><?php printf(__('Thank you for your booking with %s. Your booking (Ref: %s) is confirmed.', 'NORDBOOKING'), '<strong>%%TENANT_BUSINESS_NAME%%</strong>', '<strong>%%BOOKING_REFERENCE%%</strong>'); ?></p>
<div class="booking-details">
    <h3><?php _e('Booking Summary:', 'NORDBOOKING'); ?></h3>
    <ul>
        <li><strong><?php _e('Services:', 'NORDBOOKING'); ?></strong> %%SERVICE_NAMES%%</li>
        <li><strong><?php _e('Date & Time:', 'NORDBOOKING'); ?></strong> %%BOOKING_DATE_TIME%%</li>
        <li><strong><?php _e('Service Address:', 'NORDBOOKING'); ?></strong><br>%%SERVICE_ADDRESS%%</li>
        <li><strong><?php _e('Total Price:', 'NORDBOOKING'); ?></strong> %%TOTAL_PRICE%%</li>
    </ul>
</div>
<p><?php printf(__('If you have any questions, please contact %s.', 'NORDBOOKING'), '%%TENANT_BUSINESS_NAME%%'); ?></p>
