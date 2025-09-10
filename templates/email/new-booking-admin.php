<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<p><?php printf(__('You have received a new booking (Ref: %s).', 'NORDBOOKING'), '<strong>%%BOOKING_REFERENCE%%</strong>'); ?></p>
<div class="booking-details">
    <h3><?php _e('Customer Details:', 'NORDBOOKING'); ?></h3>
    <ul>
        <li><strong><?php _e('Name:', 'NORDBOOKING'); ?></strong> %%CUSTOMER_NAME%%</li>
        <li><strong><?php _e('Email:', 'NORDBOOKING'); ?></strong> %%CUSTOMER_EMAIL%%</li>
        <li><strong><?php _e('Phone:', 'NORDBOOKING'); ?></strong> %%CUSTOMER_PHONE%%</li>
    </ul>
    <h3><?php _e('Booking Details:', 'NORDBOOKING'); ?></h3>
    <ul>
        <li><strong><?php _e('Services:', 'NORDBOOKING'); ?></strong> %%SERVICE_NAMES%%</li>
        <li><strong><?php _e('Date & Time:', 'NORDBOOKING'); ?></strong> %%BOOKING_DATE_TIME%%</li>
        <li><strong><?php _e('Service Address:', 'NORDBOOKING'); ?></strong><br>%%SERVICE_ADDRESS%%</li>
        <li><strong><?php _e('Total Price:', 'NORDBOOKING'); ?></strong> %%TOTAL_PRICE%%</li>
        <li><strong><?php _e('Special Instructions:', 'NORDBOOKING'); ?></strong><br>%%SPECIAL_INSTRUCTIONS%%</li>
    </ul>
</div>
