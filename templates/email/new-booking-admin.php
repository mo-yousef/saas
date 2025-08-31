<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<p><?php printf(__('You have received a new booking (Ref: %s).', 'mobooking'), '<strong>%%BOOKING_REFERENCE%%</strong>'); ?></p>
<div class="booking-details">
    <h3><?php _e('Customer Details:', 'mobooking'); ?></h3>
    <ul>
        <li><strong><?php _e('Name:', 'mobooking'); ?></strong> %%CUSTOMER_NAME%%</li>
        <li><strong><?php _e('Email:', 'mobooking'); ?></strong> %%CUSTOMER_EMAIL%%</li>
        <li><strong><?php _e('Phone:', 'mobooking'); ?></strong> %%CUSTOMER_PHONE%%</li>
    </ul>
    <h3><?php _e('Booking Details:', 'mobooking'); ?></h3>
    <ul>
        <li><strong><?php _e('Services:', 'mobooking'); ?></strong> %%SERVICE_NAMES%%</li>
        <li><strong><?php _e('Date & Time:', 'mobooking'); ?></strong> %%BOOKING_DATE_TIME%%</li>
        <li><strong><?php _e('Service Address:', 'mobooking'); ?></strong><br>%%SERVICE_ADDRESS%%</li>
        <li><strong><?php _e('Total Price:', 'mobooking'); ?></strong> %%TOTAL_PRICE%%</li>
        <li><strong><?php _e('Special Instructions:', 'mobooking'); ?></strong><br>%%SPECIAL_INSTRUCTIONS%%</li>
    </ul>
</div>
