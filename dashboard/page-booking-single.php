<?php
/**
 * Dashboard Page: Single Booking Details (Redesigned)
 * This file is included by page-bookings.php when action=view_booking is set.
 * Expected variables: $single_booking_id, $bookings_manager, $currency_symbol, $current_user_id
 * @package MoBooking
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Ensure variables are set (they should be by page-bookings.php)
if ( ! isset( $single_booking_id ) || ! is_numeric( $single_booking_id ) ||
     ! isset( $bookings_manager ) || ! isset( $currency_symbol ) || ! isset( $current_user_id ) ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Required data not available to display booking.', 'mobooking' ) . '</p></div>';
    return;
}

$booking_id_to_fetch = $single_booking_id;
$user_id_for_permission_check = $current_user_id;

$actual_booking_owner_id = $bookings_manager->get_booking_owner_id($booking_id_to_fetch);
$booking_owner_id_for_fetch = null;

if ($actual_booking_owner_id === null) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Booking not found or owner could not be determined.', 'mobooking' ) . '</p></div>';
    return;
}

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

$booking = $bookings_manager->get_booking( $booking_id_to_fetch, $booking_owner_id_for_fetch );

if ( ! $booking ) {
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

$booking_statuses_for_select = [
    'pending' => __('Pending', 'mobooking'),
    'confirmed' => __('Confirmed', 'mobooking'),
    'processing' => __('Processing', 'mobooking'),
    'on-hold' => __('On Hold', 'mobooking'),
    'completed' => __('Completed', 'mobooking'),
    'cancelled' => __('Cancelled', 'mobooking'),
];

$main_bookings_page_url = home_url('/dashboard/bookings/');

// Feather Icons - define a helper function or include them directly
if (!function_exists('mobooking_get_feather_icon')) {
    function mobooking_get_feather_icon($icon_name, $attrs = 'width="18" height="18"') {
        $svg = '';
        // This is a simplified list. You'd have the full SVG paths here.
        switch ($icon_name) {
            case 'calendar': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'; break;
            case 'clock': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>'; break;
            case 'user': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>'; break;
            case 'mail': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>'; break;
            case 'phone': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>'; break;
            case 'map-pin': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>'; break;
            case 'activity': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>'; break;
            case 'edit': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>'; break;
            case 'list': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>'; break;
            case 'dollar-sign': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>'; break;
            case 'info': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'; break;
            case 'message-square': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>'; break;
            case 'check-circle': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'; break;
            case 'loader': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>'; break;
            case 'pause-circle': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="10" y1="15" x2="10" y2="9"></line><line x1="14" y1="15" x2="14" y2="9"></line></svg>'; break;
            case 'check-square': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>'; break;
            case 'x-circle': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'; break;
            case 'user-plus': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="17" y1="11" x2="23" y2="11"></line></svg>'; break;
            default: $svg = '<!-- icon not found: '.esc_attr($icon_name).' -->'; break;
        }
        return $svg;
    }
}

// Helper function to get icon based on status for badges
if (!function_exists('mobooking_get_status_badge_icon_svg')) {
    function mobooking_get_status_badge_icon_svg($status) {
        $attrs = 'class="feather"'; // CSS will handle size and margin
        $icon_name = '';
        switch ($status) {
            case 'pending': $icon_name = 'clock'; break;
            case 'confirmed': $icon_name = 'check-circle'; break;
            case 'processing': $icon_name = 'loader'; break;
            case 'on-hold': $icon_name = 'pause-circle'; break;
            case 'completed': $icon_name = 'check-square'; break;
            case 'cancelled': $icon_name = 'x-circle'; break;
            default: return '';
        }
        return mobooking_get_feather_icon($icon_name, $attrs);
    }
}
?>
<div class="container mx-auto my-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-700 dark:text-gray-200"><?php printf(esc_html__('Booking: %s', 'mobooking'), esc_html($booking['booking_reference'])); ?></h1>
        <a href="<?php echo esc_url($main_bookings_page_url); ?>" class="px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500">&laquo; Back to Bookings List</a>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        <div class="md:col-span-2">
            <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Services & Pricing</h2>
                <div class="mt-4 -mx-6 overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Service / Option</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Details</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">Price</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            <?php $subtotal_calc = 0; foreach ($booking['items'] as $item): $subtotal_calc += floatval($item['item_total_price']); ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html($item['service_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['service_price']), 2)); ?></td>
                                    <td class="px-6 py-4 text-right whitespace-no-wrap"><?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['item_total_price']), 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-end mt-4">
                    <div class="w-full max-w-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500 dark:text-gray-400">Subtotal:</span>
                            <span><?php echo esc_html($currency_symbol . number_format_i18n($subtotal_calc, 2)); ?></span>
                        </div>
                        <div class="flex justify-between mt-2">
                            <span class="text-gray-500 dark:text-gray-400">Discount Applied:</span>
                            <span><?php echo esc_html($discount_amount_formatted); ?></span>
                        </div>
                        <div class="flex justify-between mt-2 text-lg font-semibold">
                            <span>Final Total:</span>
                            <span><?php echo $total_price_formatted; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="p-6 bg-white rounded-md shadow-md dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Booking & Customer Details</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Reference:</span>
                        <span><?php echo esc_html($booking['booking_reference']); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Date:</span>
                        <span><?php echo esc_html($booking_date_formatted); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Time:</span>
                        <span><?php echo esc_html($booking_time_formatted); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Customer:</span>
                        <span><?php echo esc_html($booking['customer_name']); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Email:</span>
                        <a href="mailto:<?php echo esc_attr($booking['customer_email']); ?>" class="text-indigo-600 hover:text-indigo-900 dark:hover:text-indigo-400"><?php echo esc_html($booking['customer_email']); ?></a>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Phone:</span>
                        <span><?php echo esc_html($booking['customer_phone'] ? $booking['customer_phone'] : 'N/A'); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Service Address:</span>
                        <span><?php echo nl2br(esc_html($booking['service_address'])); ?><?php if (!empty($booking['zip_code'])) { echo ', ' . esc_html($booking['zip_code']); } ?></span>
                    </div>
                </div>
            </div>
            <div class="p-6 mt-6 bg-white rounded-md shadow-md dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-700 capitalize dark:text-white">Status & Actions</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200">Current Status:</span>
                        <span class="px-2 py-1 text-xs font-semibold leading-tight text-green-700 bg-green-100 rounded-full dark:bg-green-700 dark:text-green-100"><?php echo esc_html($status_display); ?></span>
                    </div>
                    <div>
                        <label for="mobooking-single-booking-status-select" class="font-medium text-gray-700 dark:text-gray-200">Change Status:</label>
                        <div class="flex mt-2">
                            <select id="mobooking-single-booking-status-select" data-booking-id="<?php echo esc_attr($booking['booking_id']); ?>" class="w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-l-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                                <?php foreach ($booking_statuses_for_select as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($booking['status'], $value); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="mobooking-single-save-status-btn" class="px-4 py-2 font-medium tracking-wide text-white capitalize transition-colors duration-200 transform bg-indigo-600 rounded-r-md hover:bg-indigo-500 focus:outline-none focus:bg-indigo-500">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// JavaScript for handling status update on this page
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Pre-generate icon HTML for dynamic updates
    var statusIcons = {};
    <?php
        foreach (array_keys($booking_statuses_for_select) as $status_key) {
            // Ensure SVG output is properly escaped for JavaScript string literal
            echo "statusIcons['" . esc_js($status_key) . "'] = '" . str_replace(["\r", "\n"], "", addslashes(mobooking_get_status_badge_icon_svg($status_key))) . "';\n";
        }
    ?>

    $('#mobooking-single-save-status-btn').on('click', function() {
        var $button = $(this);
        var bookingId = $('#mobooking-single-booking-status-select').data('booking-id');
        var newStatus = $('#mobooking-single-booking-status-select').val();
        var $feedback = $('#mobooking-single-status-feedback');
        var $currentStatusDisplay = $('#mobooking-current-status-display');

        $feedback.text('<?php echo esc_js( __( 'Updating...', 'mobooking' ) ); ?>').removeClass('success error');
        $button.prop('disabled', true);

        $.ajax({
            url: mobooking_dashboard_params.ajax_url, // Use localized ajax_url
            type: 'POST',
            data: {
                action: 'mobooking_update_booking_status',
                nonce: '<?php echo wp_create_nonce('mobooking_dashboard_nonce'); ?>',
                booking_id: bookingId,
                new_status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    window.showAlert(response.data.message || '<?php echo esc_js( __( 'Status updated successfully!', 'mobooking' ) ); ?>', 'success');

                    var newStatusText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1).replace('-', ' ');
                    $currentStatusDisplay.find('.status-text').text(newStatusText);

                    // Update classes for styling
                    var newClass = 'status-badge status-' + newStatus;
                    $currentStatusDisplay.attr('class', newClass);

                    // Update icon
                    if (statusIcons[newStatus]) {
                        $currentStatusDisplay.find('.feather').remove(); // Remove old icon
                        $currentStatusDisplay.prepend(statusIcons[newStatus]); // Add new icon
                    } else {
                        $currentStatusDisplay.find('.feather').remove(); // Remove icon if new status has no icon
                    }

                } else {
                    window.showAlert(response.data.message || '<?php echo esc_js( __( 'Error updating status.', 'mobooking' ) ); ?>', 'error');
                }
            },
            error: function() {
                window.showAlert('<?php echo esc_js( __( 'AJAX request failed.', 'mobooking' ) ); ?>', 'error');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    $('#mobooking-single-save-staff-assignment-btn').on('click', function() {
        var $button = $(this);
        var bookingId = $('#mobooking-single-assign-staff-select').data('booking-id');
        var staffId = $('#mobooking-single-assign-staff-select').val();
        var $currentStaffDisplay = $('#mobooking-current-assigned-staff');
        var selectedStaffName = $('#mobooking-single-assign-staff-select option:selected').text();

        $button.prop('disabled', true);

        $.ajax({
            url: mobooking_dashboard_params.ajax_url,
            type: 'POST',
            data: {
                action: 'mobooking_assign_staff_to_booking',
                nonce: '<?php echo wp_create_nonce('mobooking_dashboard_nonce'); ?>',
                booking_id: bookingId,
                staff_id: staffId
            },
            success: function(response) {
                if (response.success) {
                    window.showAlert(response.data.message || '<?php echo esc_js( __( 'Assignment updated successfully!', 'mobooking' ) ); ?>', 'success');
                    if (staffId === "0" || staffId === 0) {
                        $currentStaffDisplay.text('<?php echo esc_js(__('Unassigned', 'mobooking')); ?>');
                    } else {
                        $currentStaffDisplay.text(selectedStaffName.split(' (')[0]);
                    }
                } else {
                    window.showAlert(response.data.message || '<?php echo esc_js( __( 'Error updating assignment.', 'mobooking' ) ); ?>', 'error');
                }
            },
            error: function() {
                window.showAlert('<?php echo esc_js( __( 'AJAX request failed.', 'mobooking' ) ); ?>', 'error');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
});
</script>
