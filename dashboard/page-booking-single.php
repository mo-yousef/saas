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
        default: $svg = '<!-- icon not found: '.esc_attr($icon_name).' -->'; break;
    }
    return $svg;
}
?>
<style>
    .mobooking-single-booking-page-wrapper { max-width: 900px; margin: 20px auto; }
    .mobooking-sbs-panel { background-color: #fff; border: 1px solid var(--border, #e0e0e0); border-radius: var(--radius, 0.5rem); margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .mobooking-sbs-panel-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border, #e0e0e0); display: flex; align-items: center; gap: 0.75rem;}
    .mobooking-sbs-panel-header h2, .mobooking-sbs-panel-header h3 { margin: 0; font-size: 1.25rem; color: var(--foreground); }
    .mobooking-sbs-panel-header .feather { color: var(--primary); }
    .mobooking-sbs-panel-content { padding: 1.5rem; }
    .mobooking-sbs-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
    .mobooking-sbs-item { margin-bottom: 0.75rem; }
    .mobooking-sbs-item strong { display: block; font-weight: 600; color: var(--foreground); margin-bottom: 0.25rem; font-size:0.9rem; }
    .mobooking-sbs-item span, .mobooking-sbs-item a { color: var(--muted-foreground); font-size:0.95rem; }
    .mobooking-sbs-item a { color: var(--primary); text-decoration: none !important; }
    .mobooking-sbs-item a:hover { text-decoration: underline !important; }
    .mobooking-status-update-section { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border, #e0e0e0); }
    .mobooking-status-form { display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem; flex-wrap: wrap; }
    .mobooking-status-form label { font-weight: 600; }
    .mobooking-service-items-list { list-style: none; padding: 0; }
    .mobooking-service-items-list > li { padding: 0.75rem 0; border-bottom: 1px dashed var(--border, #e0e0e0); }
    .mobooking-service-items-list > li:last-child { border-bottom: none; }
    .mobooking-service-options-list { list-style: disc; padding-left: 1.5rem; margin-top: 0.5rem; font-size: 0.9em; }
    .mobooking-service-options-list li { margin-bottom: 0.25rem; }
    .mobooking-pricing-summary p { margin: 0.5rem 0; display: flex; justify-content: space-between; }
    .mobooking-pricing-summary strong.final-total { font-size: 1.2em; color: var(--primary); }
    .mobooking-meta-info { font-size: 0.8rem; color: var(--muted-foreground); text-align: right; margin-top: 1rem; }
    .mobooking-status-badge { padding: 0.25em 0.6em; font-size: 0.85em; border-radius: var(--radius); color: #fff; }
    .mobooking-status-pending { background-color: #ffc107; color: #333 } /* Amber */
    .mobooking-status-confirmed { background-color: #28a745; } /* Green */
    .mobooking-status-processing { background-color: #17a2b8; } /* Teal */
    .mobooking-status-on-hold { background-color: #fd7e14; } /* Orange */
    .mobooking-status-completed { background-color: #6f42c1; } /* Indigo */
    .mobooking-status-cancelled { background-color: #dc3545; } /* Red */

    .mobooking-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .mobooking-page-header h1 { font-size: 1.8rem; margin:0; color: var(--foreground); }

    .mobooking-status-feedback.success { color: green; margin-top: 0.5rem; }
    .mobooking-status-feedback.error { color: red; margin-top: 0.5rem; }

    @media (max-width: 768px) {
        .mobooking-sbs-grid { grid-template-columns: 1fr; }
        .mobooking-page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
    }

</style>

<div class="mobooking-single-booking-page-wrapper">
    <div class="mobooking-page-header">
        <h1><?php printf(esc_html__('Booking: %s', 'mobooking'), esc_html($booking['booking_reference'])); ?></h1>
        <a href="<?php echo esc_url($main_bookings_page_url); ?>" class="button"><?php esc_html_e('&laquo; Back to Bookings List', 'mobooking'); ?></a>
    </div>

    <!-- Booking & Customer Details Panel -->
    <div class="mobooking-sbs-panel">
        <div class="mobooking-sbs-panel-header">
            <?php echo mobooking_get_feather_icon('info'); ?>
            <h3><?php esc_html_e('Booking & Customer Details', 'mobooking'); ?></h3>
        </div>
        <div class="mobooking-sbs-panel-content">
            <div class="mobooking-sbs-grid">
                <div>
                    <div class="mobooking-sbs-item"><strong><?php esc_html_e('Reference:', 'mobooking'); ?></strong> <span><?php echo esc_html($booking['booking_reference']); ?></span></div>
                    <div class="mobooking-sbs-item"><strong><?php echo mobooking_get_feather_icon('calendar', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Date:', 'mobooking'); ?></strong> <span><?php echo esc_html($booking_date_formatted); ?></span></div>
                    <div class="mobooking-sbs-item"><strong><?php echo mobooking_get_feather_icon('clock', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Time:', 'mobooking'); ?></strong> <span><?php echo esc_html($booking_time_formatted); ?></span></div>
                </div>
                <div>
                    <div class="mobooking-sbs-item"><strong><?php echo mobooking_get_feather_icon('user', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Customer:', 'mobooking'); ?></strong> <span><?php echo esc_html($booking['customer_name']); ?></span></div>
                    <div class="mobooking-sbs-item"><strong><?php echo mobooking_get_feather_icon('mail', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Email:', 'mobooking'); ?></strong> <a href="mailto:<?php echo esc_attr($booking['customer_email']); ?>"><?php echo esc_html($booking['customer_email']); ?></a></div>
                    <div class="mobooking-sbs-item"><strong><?php echo mobooking_get_feather_icon('phone', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Phone:', 'mobooking'); ?></strong> <span><?php echo esc_html($booking['customer_phone'] ? $booking['customer_phone'] : 'N/A'); ?></span></div>
                </div>
            </div>
             <div class="mobooking-sbs-item" style="margin-top:1rem;"><strong><?php echo mobooking_get_feather_icon('map-pin', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Service Address:', 'mobooking'); ?></strong> <span><?php echo nl2br(esc_html($booking['service_address'])); ?><?php if (!empty($booking['zip_code'])) { echo ', ' . esc_html($booking['zip_code']); } ?></span></div>
        </div>
    </div>

    <!-- Status & Admin Actions Panel -->
    <div class="mobooking-sbs-panel">
        <div class="mobooking-sbs-panel-header">
            <?php echo mobooking_get_feather_icon('activity'); ?>
            <h3><?php esc_html_e('Status & Actions', 'mobooking'); ?></h3>
        </div>
        <div class="mobooking-sbs-panel-content">
            <div class="mobooking-status-update-section" style="border-top:none; margin-top:0; padding-top:0;">
                <p class="mobooking-sbs-item"><strong><?php esc_html_e('Current Status:', 'mobooking'); ?></strong> <span id="mobooking-current-status-display" class="mobooking-status-badge mobooking-status-<?php echo esc_attr($booking['status']); ?>"><?php echo esc_html($status_display); ?></span></p>
                <div class="mobooking-status-form">
                    <label for="mobooking-single-booking-status-select"><?php echo mobooking_get_feather_icon('edit', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Change Status:', 'mobooking'); ?></label>
                    <select id="mobooking-single-booking-status-select" data-booking-id="<?php echo esc_attr($booking['booking_id']); ?>">
                        <?php foreach ($booking_statuses_for_select as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($booking['status'], $value); ?>><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button id="mobooking-single-save-status-btn" class="button button-primary button-small"><?php esc_html_e('Save Status', 'mobooking'); ?></button>
                </div>
                <div id="mobooking-single-status-feedback" class="mobooking-status-feedback"></div>
            </div>
             <div class="mobooking-meta-info">
                <p><?php esc_html_e('Created:', 'mobooking'); ?> <?php echo esc_html($created_at_formatted); ?> | <?php esc_html_e('Last Updated:', 'mobooking'); ?> <?php echo esc_html($updated_at_formatted); ?></p>
            </div>
        </div>
    </div>

    <!-- Services & Pricing Panel -->
    <div class="mobooking-sbs-panel">
        <div class="mobooking-sbs-panel-header">
             <?php echo mobooking_get_feather_icon('list'); ?>
            <h3><?php esc_html_e('Services & Pricing', 'mobooking'); ?></h3>
        </div>
        <div class="mobooking-sbs-panel-content">
            <?php if (isset($booking['items']) && is_array($booking['items']) && !empty($booking['items'])): ?>
                <ul class="mobooking-service-items-list">
                    <?php $subtotal_calc = 0; foreach ($booking['items'] as $item): $subtotal_calc += floatval($item['item_total_price']); ?>
                        <li>
                            <strong><?php echo esc_html($item['service_name']); ?></strong>
                            (<?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['service_price']), 2)); ?>)
                            <?php if (!empty($item['selected_options']) && is_array($item['selected_options'])): ?>
                                <ul class="mobooking-service-options-list">
                                    <?php
                                    if (is_array($item['selected_options'])) {
                                        foreach ($item['selected_options'] as $option_key => $option_data):
                                            $option_display_name = ''; $option_display_value = ''; $option_price_text = '';
                                            if (is_array($option_data) && isset($option_data['name']) && isset($option_data['value'])) {
                                                $option_display_name = $option_data['name'];
                                                $option_display_value = is_array($option_data['value']) ? esc_html(wp_json_encode($option_data['value'])) : esc_html($option_data['value']);
                                                $option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0;
                                                $option_price_text = ($option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($option_price, 2));
                                            } elseif (is_string($option_key) && !is_array($option_data)) {
                                                $option_display_name = $option_key; $option_display_value = esc_html($option_data);
                                            } else {
                                                $option_display_name = 'Option'; $option_display_value = esc_html(wp_json_encode($option_data));
                                            }
                                    ?>
                                        <li>
                                            <?php echo esc_html($option_display_name); ?>: <?php echo $option_display_value; ?>
                                            <?php if (!empty($option_price_text)): ?> (<?php echo $option_price_text; ?>)<?php endif; ?>
                                        </li>
                                    <?php endforeach;
                                    } else { echo '<li>' . esc_html__('Options data is not in expected array format.', 'mobooking') . '</li>'; } ?>
                                </ul>
                            <?php endif; ?>
                            <p style="text-align: right; margin-top: 0.25rem;"><em><?php esc_html_e('Item Total:', 'mobooking'); ?> <?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['item_total_price']), 2)); ?></em></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <hr style="margin: 1rem 0;">
                <div class="mobooking-pricing-summary">
                    <p><span><?php esc_html_e('Subtotal:', 'mobooking'); ?></span> <span><?php echo esc_html($currency_symbol . number_format_i18n($subtotal_calc, 2)); ?></span></p>
                    <p><span><?php esc_html_e('Discount Applied:', 'mobooking'); ?></span> <span><?php echo esc_html($discount_amount_formatted); ?></span></p>
                    <p><strong><?php esc_html_e('Final Total:', 'mobooking'); ?></strong> <strong class="final-total"><?php echo $total_price_formatted; ?></strong></p>
                </div>
            <?php else: ?>
                <p><?php esc_html_e('No service items found for this booking.', 'mobooking'); ?></p>
            <?php endif; ?>
             <p style="margin-top: 1rem;"><strong><?php esc_html_e('Payment Status:', 'mobooking'); ?></strong> <?php echo esc_html(ucfirst($booking['payment_status'] ?? 'N/A')); ?></p>
        </div>
    </div>

    <?php if (!empty($booking['special_instructions'])): ?>
    <div class="mobooking-sbs-panel">
        <div class="mobooking-sbs-panel-header">
            <?php echo mobooking_get_feather_icon('message-square'); ?>
            <h3><?php esc_html_e('Special Instructions', 'mobooking'); ?></h3>
        </div>
        <div class="mobooking-sbs-panel-content">
            <p><?php echo nl2br(esc_html($booking['special_instructions'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// JavaScript for handling status update on this page
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
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
                    $feedback.text(response.data.message || '<?php echo esc_js( __( 'Status updated successfully!', 'mobooking' ) ); ?>').addClass('success').removeClass('error');
                    $currentStatusDisplay.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1).replace('-', ' '));
                    $currentStatusDisplay.removeClassWild("mobooking-status-*").addClass("mobooking-status-" + newStatus);
                } else {
                    $feedback.text(response.data.message || '<?php echo esc_js( __( 'Error updating status.', 'mobooking' ) ); ?>').addClass('error').removeClass('success');
                }
            },
            error: function() {
                $feedback.text('<?php echo esc_js( __( 'AJAX request failed.', 'mobooking' ) ); ?>').addClass('error').removeClass('success');
            },
            complete: function() {
                $button.prop('disabled', false);
                 setTimeout(function() { $feedback.text(''); }, 5000);
            }
        });
    });

    $.fn.removeClassWild = function(mask) {
        return this.removeClass(function(index, cls) {
            var re = mask.replace(/\*/g, '\\S+');
            return (cls.match(new RegExp('\\b' + re + '', 'g')) || []).join(' ');
        });
    };
});
</script>
