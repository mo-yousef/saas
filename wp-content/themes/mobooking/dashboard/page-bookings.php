<?php
/**
 * Dashboard Page: Bookings
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$booking_statuses = [
    '' => __('All Statuses', 'mobooking'),
    'pending' => __('Pending', 'mobooking'),
    'confirmed' => __('Confirmed', 'mobooking'),
    'completed' => __('Completed', 'mobooking'),
    'cancelled' => __('Cancelled', 'mobooking'),
    'on-hold' => __('On Hold', 'mobooking'),
    'processing' => __('Processing', 'mobooking'),
];
?>
<style>
    .mobooking-modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
    .mobooking-modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 700px; position: relative; border-radius: 5px; box-shadow: 0 5px 15px rgba(0,0,0,.5); }
    .mobooking-modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; line-height: 1; }
    .mobooking-modal-close:hover, .mobooking-modal-close:focus { color: black; text-decoration: none; cursor: pointer; }
    .mobooking-modal .modal-section { margin-bottom: 20px; padding-bottom:15px; border-bottom: 1px solid #eee; }
    .mobooking-modal .modal-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0;}
    .mobooking-modal h2 { margin-top: 0; font-size: 24px; }
    .mobooking-modal h4 { font-size: 16px; margin-bottom: 8px; color: #333; }
    #modal-services-items-list ul { padding-left: 20px; margin-top: 5px; }
    #modal-services-items-list li { margin-bottom: 5px; font-size: 0.95em; }
    #modal-services-items-list .option-list { padding-left: 15px; font-size: 0.9em; color: #555; }
    #modal-booking-status-select { margin-right: 10px; }
    #modal-status-feedback { font-style: italic; }
</style>

<h1><?php esc_html_e('Manage Bookings', 'mobooking'); ?></h1>

<div id="mobooking-bookings-filters" class="mobooking-filters-bar" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 4px;">
    <form id="mobooking-bookings-filter-form" class="form-inline">
        <label for="mobooking-status-filter" style="margin-right:5px;"><?php esc_html_e('Status:', 'mobooking'); ?></label>
        <select id="mobooking-status-filter" name="status_filter" style="margin-right: 15px;">
            <?php foreach ($booking_statuses as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="mobooking-date-from-filter" style="margin-right:5px;"><?php esc_html_e('From:', 'mobooking'); ?></label>
        <input type="text" id="mobooking-date-from-filter" name="date_from_filter" class="mobooking-datepicker" placeholder="YYYY-MM-DD" style="margin-right: 15px; width: 120px;">

        <label for="mobooking-date-to-filter" style="margin-right:5px;"><?php esc_html_e('To:', 'mobooking'); ?></label>
        <input type="text" id="mobooking-date-to-filter" name="date_to_filter" class="mobooking-datepicker" placeholder="YYYY-MM-DD" style="margin-right: 15px; width: 120px;">

        <label for="mobooking-search-query" style="margin-right:5px;"><?php esc_html_e('Search:', 'mobooking'); ?></label>
        <input type="text" id="mobooking-search-query" name="search_query" placeholder="<?php esc_attr_e('Ref, Name, Email', 'mobooking'); ?>" style="margin-right: 15px;">

        <button type="submit" class="button button-secondary"><?php esc_html_e('Filter', 'mobooking'); ?></button>
        <button type="button" id="mobooking-clear-filters-btn" class="button" style="margin-left:5px;"><?php esc_html_e('Clear', 'mobooking'); ?></button>
    </form>
</div>

<div id="mobooking-bookings-list-container">
    <p><?php esc_html_e('Loading bookings...', 'mobooking'); ?></p>
</div>

<div id="mobooking-bookings-pagination-container" style="margin-top: 20px; text-align: center;"></div>

<script type="text/template" id="mobooking-booking-item-template">
    <div class="mobooking-booking-item" style="border:1px solid #e0e0e0; padding:15px; margin-bottom:10px; background:#fff; border-radius:3px;">
        <h3 style="margin-top:0; margin-bottom:10px; font-size:1.1em;">Booking Ref: <%= booking_reference %></h3>
        <p><strong><?php esc_html_e('Customer:', 'mobooking'); ?></strong> <%= customer_name %> (<%= customer_email %>)</p>
        <p><strong><?php esc_html_e('Booked Date:', 'mobooking'); ?></strong> <%= booking_date %> at <%= booking_time %></p>
        <p><strong><?php esc_html_e('Total Price:', 'mobooking'); ?></strong> <%= total_price_formatted %></p>
        <p><strong><?php esc_html_e('Status:', 'mobooking'); ?></strong> <span class="booking-status booking-status-<%= status %>" style="padding: 3px 6px; border-radius: 3px; background-color: #eee; font-weight:bold;"><%= status_display %></span></p>
        <p style="font-size:0.9em; color:#777;"><strong><?php esc_html_e('Created:', 'mobooking'); ?></strong> <%= created_at_formatted %></p>
        <div class="booking-actions" style="margin-top:10px;">
            <button class="button mobooking-view-booking-details-btn" data-booking-id="<%= booking_id %>"><?php esc_html_e('View Details', 'mobooking'); ?></button>
        </div>
    </div>
</script>

<div id="mobooking-booking-details-modal" class="mobooking-modal">
    <div class="mobooking-modal-content">
        <span class="mobooking-modal-close">&times;</span>
        <h2 id="modal-booking-title"><?php esc_html_e('Booking Details', 'mobooking'); ?> - <span id="modal-booking-ref"></span></h2>

        <input type="hidden" id="modal-current-booking-id" value="">

        <div class="modal-section">
            <h4><?php esc_html_e('Update Status', 'mobooking'); ?></h4>
            <select id="modal-booking-status-select" style="min-width:150px;"></select> <?php // Options populated by JS ?>
            <button id="modal-save-status-btn" class="button button-primary button-small"><?php esc_html_e('Save Status', 'mobooking'); ?></button>
            <span id="modal-status-feedback" style="margin-left:10px; font-style:italic;"></span>
        </div>

        <div class="modal-section">
            <h4><?php esc_html_e('Customer Information', 'mobooking'); ?></h4>
            <p><strong><?php esc_html_e('Name:', 'mobooking'); ?></strong> <span id="modal-customer-name"></span></p>
            <p><strong><?php esc_html_e('Email:', 'mobooking'); ?></strong> <span id="modal-customer-email"></span></p>
            <p><strong><?php esc_html_e('Phone:', 'mobooking'); ?></strong> <span id="modal-customer-phone"></span></p>
            <p><strong><?php esc_html_e('Address:', 'mobooking'); ?></strong><br><span id="modal-service-address" style="white-space: pre-wrap;"></span></p>
        </div>

        <div class="modal-section">
            <h4><?php esc_html_e('Booking Schedule & Details', 'mobooking'); ?></h4>
            <p><strong><?php esc_html_e('Booked Date:', 'mobooking'); ?></strong> <span id="modal-booking-date"></span></p>
            <p><strong><?php esc_html_e('Booked Time:', 'mobooking'); ?></strong> <span id="modal-booking-time"></span></p>
            <p><strong><?php esc_html_e('Special Instructions:', 'mobooking'); ?></strong><br><span id="modal-special-instructions" style="white-space: pre-wrap;"></span></p>
        </div>

        <div class="modal-section">
            <h4><?php esc_html_e('Services & Options Booked', 'mobooking'); ?></h4>
            <div id="modal-services-items-list"></div>
        </div>

        <div class="modal-section">
            <h4><?php esc_html_e('Pricing Information', 'mobooking'); ?></h4>
            <p><strong><?php esc_html_e('Discount Applied:', 'mobooking'); ?></strong> <span id="modal-discount-amount">0.00</span></p>
            <p><strong><?php esc_html_e('Final Total:', 'mobooking'); ?></strong> <span id="modal-final-total" style="font-weight:bold;">0.00</span></p>
        </div>
    </div>
</div>
