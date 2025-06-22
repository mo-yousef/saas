<?php
/**
 * Dashboard Page: Single Booking Details
 * This file is included by page-bookings.php when action=view_booking is set.
 * Expected variables: $single_booking_id, $bookings_manager, $currency_symbol, $current_user_id
 * @package MoBooking
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! isset( $single_booking_id ) || ! is_numeric( $single_booking_id ) ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid booking ID specified.', 'mobooking' ) . '</p></div>';
    return;
}

$booking_id_to_fetch = $single_booking_id;
$user_id_for_permission_check = $current_user_id; // The logged-in user

// Determine the actual owner ID of the booking for fetching and broad permission.
// The get_booking method in Bookings.php needs the owner's ID.
$booking_owner_id_for_fetch = null;
$booking_to_check_ownership = $bookings_manager->wpdb->get_row( // Direct DB check to find owner first
    $bookings_manager->wpdb->prepare(
        "SELECT user_id FROM " . MoBooking\Classes\Database::get_table_name('bookings') . " WHERE booking_id = %d",
        $booking_id_to_fetch
    )
);

if (!$booking_to_check_ownership) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Booking not found.', 'mobooking' ) . '</p></div>';
    return;
}
$actual_booking_owner_id = (int) $booking_to_check_ownership->user_id;

// Permission Check: Can the current user view this booking?
$can_view = false;
if ( MoBooking\Classes\Auth::is_user_business_owner( $user_id_for_permission_check ) ) {
    if ( $user_id_for_permission_check === $actual_booking_owner_id ) {
        $can_view = true;
        $booking_owner_id_for_fetch = $user_id_for_permission_check;
    }
} elseif ( MoBooking\Classes\Auth::is_user_worker( $user_id_for_permission_check ) ) {
    $worker_owner_id = MoBooking\Classes\Auth::get_business_owner_id_for_worker( $user_id_for_permission_check );
    if ( $worker_owner_id && $worker_owner_id === $actual_booking_owner_id ) {
        $can_view = true;
        $booking_owner_id_for_fetch = $worker_owner_id;
    }
}

if ( ! $can_view ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'You do not have permission to view this booking.', 'mobooking' ) . '</p></div>';
    return;
}

// Fetch the full booking details using the determined owner ID
$booking = $bookings_manager->get_booking( $booking_id_to_fetch, $booking_owner_id_for_fetch );

if ( ! $booking ) {
    // This case should ideally be caught by the previous check, but as a fallback.
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Booking details could not be retrieved or access denied.', 'mobooking' ) . '</p></div>';
    return;
}

// Prepare data for display
$status_display = !empty($booking['status']) ? ucfirst(str_replace('-', ' ', $booking['status'])) : __('N/A', 'mobooking');
$total_price_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2));
$discount_amount_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['discount_amount']), 2));
$booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));
$booking_time_formatted = date_i18n(get_option('time_format'), strtotime($booking['booking_time']));
$created_at_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['created_at']));
$updated_at_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['updated_at']));

$booking_statuses_for_select = [ // To populate the select dropdown
    'pending' => __('Pending', 'mobooking'),
    'confirmed' => __('Confirmed', 'mobooking'),
    'completed' => __('Completed', 'mobooking'),
    'cancelled' => __('Cancelled', 'mobooking'),
    'on-hold' => __('On Hold', 'mobooking'),
    'processing' => __('Processing', 'mobooking'),
];

// Base URL for the main bookings page (for back button)
$main_bookings_page_url = admin_url('admin.php?page=mobooking');

?>
<div class="mobooking-single-booking-page-wrapper">
    <div class="mobooking-page-header">
        <h1><?php printf(esc_html__('Booking Details: %s', 'mobooking'), esc_html($booking['booking_reference'])); ?></h1>
        <a href="<?php echo esc_url($main_bookings_page_url); ?>" class="button"><?php esc_html_e('&laquo; Back to Bookings List', 'mobooking'); ?></a>
    </div>

    <div class="mobooking-details-grid">
        <?php // Column 1: Core Details & Status ?>
        <div class="mobooking-details-column">
            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <h3><?php esc_html_e('Booking Overview', 'mobooking'); ?></h3>
                </div>
                <div class="mobooking-card-content">
                    <p><strong><?php esc_html_e('Reference:', 'mobooking'); ?></strong> <?php echo esc_html($booking['booking_reference']); ?></p>
                    <p><strong><?php esc_html_e('Booked Date:', 'mobooking'); ?></strong> <?php echo esc_html($booking_date_formatted); ?></p>
                    <p><strong><?php esc_html_e('Booked Time:', 'mobooking'); ?></strong> <?php echo esc_html($booking_time_formatted); ?></p>
                    <hr>
                    <div class="mobooking-status-update-section">
                        <p><strong><?php esc_html_e('Current Status:', 'mobooking'); ?></strong> <span id="mobooking-current-status-display" class="mobooking-status-badge mobooking-status-<?php echo esc_attr($booking['status']); ?>"><?php echo esc_html($status_display); ?></span></p>
                        <div class="mobooking-status-form">
                            <label for="mobooking-single-booking-status-select"><?php esc_html_e('Change Status:', 'mobooking'); ?></label>
                            <select id="mobooking-single-booking-status-select" data-booking-id="<?php echo esc_attr($booking['booking_id']); ?>">
                                <?php foreach ($booking_statuses_for_select as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($booking['status'], $value); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="mobooking-single-save-status-btn" class="button button-primary button-small"><?php esc_html_e('Save Status', 'mobooking'); ?></button>
                        </div>
                        <div id="mobooking-single-status-feedback" class="mobooking-status-feedback"></div>
                    </div>
                    <hr>
                    <p><small><strong><?php esc_html_e('Created:', 'mobooking'); ?></strong> <?php echo esc_html($created_at_formatted); ?></small></p>
                    <p><small><strong><?php esc_html_e('Last Updated:', 'mobooking'); ?></strong> <?php echo esc_html($updated_at_formatted); ?></small></p>
                </div>
            </div>

            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <h3><?php esc_html_e('Pricing Information', 'mobooking'); ?></h3>
                </div>
                <div class="mobooking-card-content">
                    <?php if (isset($booking['items']) && is_array($booking['items']) && !empty($booking['items'])): ?>
                        <?php $subtotal = 0; foreach($booking['items'] as $item) { $subtotal += floatval($item['item_total_price']); } ?>
                        <p><strong><?php esc_html_e('Subtotal:', 'mobooking'); ?></strong> <?php echo esc_html($currency_symbol . number_format_i18n($subtotal, 2)); ?></p>
                    <?php endif; ?>
                    <p><strong><?php esc_html_e('Discount Applied:', 'mobooking'); ?></strong> <?php echo esc_html($discount_amount_formatted); ?></p>
                    <p><strong><?php esc_html_e('Final Total:', 'mobooking'); ?></strong> <strong style="font-size: 1.2em;"><?php echo $total_price_formatted; ?></strong></p>
                    <p><strong><?php esc_html_e('Payment Status:', 'mobooking'); ?></strong> <?php echo esc_html(ucfirst($booking['payment_status'])); ?></p>
                </div>
            </div>
        </div>

        <?php // Column 2: Customer & Service Details ?>
        <div class="mobooking-details-column">
            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <h3><?php esc_html_e('Customer Information', 'mobooking'); ?></h3>
                </div>
                <div class="mobooking-card-content">
                    <p><strong><?php esc_html_e('Name:', 'mobooking'); ?></strong> <?php echo esc_html($booking['customer_name']); ?></p>
                    <p><strong><?php esc_html_e('Email:', 'mobooking'); ?></strong> <a href="mailto:<?php echo esc_attr($booking['customer_email']); ?>"><?php echo esc_html($booking['customer_email']); ?></a></p>
                    <p><strong><?php esc_html_e('Phone:', 'mobooking'); ?></strong> <?php echo esc_html($booking['customer_phone'] ? $booking['customer_phone'] : 'N/A'); ?></p>
                    <p><strong><?php esc_html_e('Service Address:', 'mobooking'); ?></strong><br><?php echo nl2br(esc_html($booking['service_address'])); ?></p>
                    <?php if (!empty($booking['zip_code'])): ?>
                        <p><strong><?php esc_html_e('Zip Code:', 'mobooking'); ?></strong> <?php echo esc_html($booking['zip_code']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($booking['special_instructions'])): ?>
            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <h3><?php esc_html_e('Special Instructions', 'mobooking'); ?></h3>
                </div>
                <div class="mobooking-card-content">
                    <p><?php echo nl2br(esc_html($booking['special_instructions'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php // Services and Options Booked - Full Width Card ?>
    <?php if (isset($booking['items']) && is_array($booking['items']) && !empty($booking['items'])): ?>
    <div class="mobooking-card">
        <div class="mobooking-card-header">
            <h3><?php esc_html_e('Services & Options Booked', 'mobooking'); ?></h3>
        </div>
        <div class="mobooking-card-content">
            <ul class="mobooking-service-items-list">
                <?php foreach ($booking['items'] as $item): ?>
                    <li>
                        <strong><?php echo esc_html($item['service_name']); ?></strong>
                        (<?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['service_price']), 2)); ?>)
                        <?php if (!empty($item['selected_options']) && is_array($item['selected_options'])): ?>
                            <ul class="mobooking-service-options-list">
                                <?php foreach ($item['selected_options'] as $option): ?>
                                    <li>
                                        <?php echo esc_html($option['name']); ?>: <?php echo esc_html($option['value']); ?>
                                        (<?php echo ($option['price_impact'] >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n(floatval($option['price_impact']), 2)); ?>)
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <p style="text-align: right;"><em><?php esc_html_e('Item Total:', 'mobooking'); ?> <?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['item_total_price']), 2)); ?></em></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// JavaScript for handling status update on this page
// This reuses the existing AJAX endpoint.
// Note: This script is output directly. In a full plugin, it would be in a separate .js file and enqueued.
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#mobooking-single-save-status-btn').on('click', function() {
        var $button = $(this);
        var bookingId = $('#mobooking-single-booking-status-select').data('booking-id');
        var newStatus = $('#mobooking-single-booking-status-select').val();
        var $feedback = $('#mobooking-single-status-feedback');
        var $currentStatusDisplay = $('#mobooking-current-status-display');

        $feedback.text('<?php esc_js_e('Updating...', 'mobooking'); ?>').removeClass('success error');
        $button.prop('disabled', true);

        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'mobooking_update_booking_status',
                nonce: '<?php echo wp_create_nonce('mobooking_dashboard_nonce'); // Assuming this nonce is still generally used for dashboard actions ?>',
                booking_id: bookingId,
                new_status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    $feedback.text(response.data.message || '<?php esc_js_e('Status updated successfully!', 'mobooking'); ?>').addClass('success').removeClass('error');
                    // Update current status display badge
                    $currentStatusDisplay.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1).replace('-', ' '));
                    $currentStatusDisplay.removeClassWild("mobooking-status-*").addClass("mobooking-status-" + newStatus);

                } else {
                    $feedback.text(response.data.message || '<?php esc_js_e('Error updating status.', 'mobooking'); ?>').addClass('error').removeClass('success');
                }
            },
            error: function() {
                $feedback.text('<?php esc_js_e('AJAX request failed.', 'mobooking'); ?>').addClass('error').removeClass('success');
            },
            complete: function() {
                $button.prop('disabled', false);
                 setTimeout(function() { $feedback.text(''); }, 5000);
            }
        });
    });

    // Helper to remove classes with wildcard
    $.fn.removeClassWild = function(mask) {
        return this.removeClass(function(index, cls) {
            var re = mask.replace(/\*/g, '\\S+');
            return (cls.match(new RegExp('\\b' + re + '', 'g')) || []).join(' ');
        });
    };
});
</script>
