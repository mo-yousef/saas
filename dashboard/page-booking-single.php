<?php
/**
 * Dashboard Page: Single Booking Details (Redesigned)
 * This file is included by page-bookings.php when action=view_booking is set.
 * Expected variables: $single_booking_id, $bookings_manager, $currency_symbol, $current_user_id
 * @package NORDBOOKING
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Ensure variables are set (they should be by page-bookings.php)
if ( ! isset( $single_booking_id ) || ! is_numeric( $single_booking_id ) ||
     ! isset( $bookings_manager ) || ! isset( $currency_symbol ) || ! isset( $current_user_id ) ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Required data not available to display booking.', 'NORDBOOKING' ) . '</p></div>';
    return;
}

$booking_id_to_fetch = $single_booking_id;
$user_id_for_permission_check = $current_user_id;

$actual_booking_owner_id = $bookings_manager->get_booking_owner_id($booking_id_to_fetch);
$booking_owner_id_for_fetch = null;

if ($actual_booking_owner_id === null) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Booking not found or owner could not be determined.', 'NORDBOOKING' ) . '</p></div>';
    return;
}

$can_view = false;
if ( NORDBOOKING\Classes\Auth::is_user_business_owner( $user_id_for_permission_check ) ) {
    if ( $user_id_for_permission_check === $actual_booking_owner_id ) {
        $can_view = true;
        $booking_owner_id_for_fetch = $user_id_for_permission_check;
    }
} elseif ( NORDBOOKING\Classes\Auth::is_user_worker( $user_id_for_permission_check ) ) {
    $worker_owner_id = NORDBOOKING\Classes\Auth::get_business_owner_id_for_worker( $user_id_for_permission_check );
    $booking_to_check = $bookings_manager->get_booking( $booking_id_to_fetch, $actual_booking_owner_id );
    if ( $worker_owner_id && $worker_owner_id === $actual_booking_owner_id && $booking_to_check && (int)$booking_to_check['assigned_staff_id'] === $user_id_for_permission_check ) {
        $can_view = true;
        $booking_owner_id_for_fetch = $worker_owner_id;
    }
}

if ( ! $can_view ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'You do not have permission to view this booking.', 'NORDBOOKING' ) . '</p></div>';
    return;
}

$booking = $bookings_manager->get_booking( $booking_id_to_fetch, $booking_owner_id_for_fetch );

if ( ! $booking ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Booking details could not be retrieved or access denied.', 'NORDBOOKING' ) . '</p></div>';
    return;
}

// Handle invoice download request
if ( isset( $_GET['download_invoice'] ) && $_GET['download_invoice'] === 'true' ) {
    $invoice_template_path = get_template_directory() . '/includes/invoice-generator.php';
    if ( file_exists( $invoice_template_path ) ) {
        // The included file will have access to variables in the current scope,
        // such as $single_booking_id, $bookings_manager, $currency_symbol, etc.
        include $invoice_template_path;
        exit; // Stop further execution to prevent rendering the HTML page
    } else {
        // Optional: handle case where invoice template is missing
        wp_die( 'Invoice template not found. Please contact support.' );
    }
}

// Prepare data for display
$status_display = !empty($booking['status']) ? ucfirst(str_replace('-', ' ', $booking['status'])) : __('N/A', 'NORDBOOKING');
$total_price_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2));
$discount_amount_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['discount_amount']), 2));
$booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));
$booking_time_formatted = date_i18n(get_option('time_format'), strtotime($booking['booking_time']));
$created_at_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['created_at']));
$updated_at_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['updated_at']));

$booking_statuses_for_select = [
    'pending' => __('Pending', 'NORDBOOKING'),
    'confirmed' => __('Confirmed', 'NORDBOOKING'),
    'processing' => __('Processing', 'NORDBOOKING'),
    'on-hold' => __('On Hold', 'NORDBOOKING'),
    'completed' => __('Completed', 'NORDBOOKING'),
    'cancelled' => __('Cancelled', 'NORDBOOKING'),
];

$main_bookings_page_url = home_url('/dashboard/bookings/');

// Feather Icons - define a helper function or include them directly
if (!function_exists('nordbooking_get_feather_icon')) {
    function nordbooking_get_feather_icon($icon_name, $attrs = 'width="18" height="18"') {
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
            case 'download': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>'; break;
            default: $svg = '<!-- icon not found: '.esc_attr($icon_name).' -->'; break;
        }
        return $svg;
    }
}

// Helper function to get icon based on status for badges
if (!function_exists('nordbooking_get_status_badge_icon_svg')) {
    function nordbooking_get_status_badge_icon_svg($status) {
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
        return nordbooking_get_feather_icon($icon_name, $attrs);
    }
}
?>

<div class="NORDBOOKING-single-booking-page-wrapper">
    <div class="nordbooking-page-header">
        <h1><?php printf(esc_html__('Booking: %s', 'NORDBOOKING'), esc_html($booking['booking_reference'])); ?></h1>
        <a href="<?php echo esc_url($main_bookings_page_url); ?>" class="btn btn-secondary"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-arrow-left"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg><?php esc_html_e(' Back to Bookings List', 'NORDBOOKING'); ?></a>
    </div>

    <div class="NORDBOOKING-edit-layout-grid">
        <div class="NORDBOOKING-main-content">
            <!-- Customer Details Card -->
            <div class="nordbooking-card card-bs">
                <div class="nordbooking-card-header">
                    <div class="nordbooking-card-title-group">
                        <span class="nordbooking-card-icon"><?php echo nordbooking_get_feather_icon('user'); ?></span>
                        <h3 class="nordbooking-card-title"><?php esc_html_e('Customer Details', 'NORDBOOKING'); ?></h3>
                    </div>
                </div>
                <div class="nordbooking-card-content">
                    <div class="kpi-grid">
                        <div>
                            <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php echo nordbooking_get_feather_icon('user', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Customer:', 'NORDBOOKING'); ?></strong> <span class="text-sm text-muted-foreground"><?php echo esc_html($booking['customer_name']); ?></span></div>
                            <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php echo nordbooking_get_feather_icon('mail', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Email:', 'NORDBOOKING'); ?></strong> <a href="mailto:<?php echo esc_attr($booking['customer_email']); ?>" class="text-sm text-primary hover:underline"><?php echo esc_html($booking['customer_email']); ?></a></div>
                            <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php echo nordbooking_get_feather_icon('phone', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Phone:', 'NORDBOOKING'); ?></strong> <span class="text-sm text-muted-foreground"><?php echo esc_html($booking['customer_phone'] ? $booking['customer_phone'] : 'N/A'); ?></span></div>
                        </div>
                        <div>
                             <div class="mt-4"><strong class="block font-semibold text-sm mb-1"><?php echo nordbooking_get_feather_icon('map-pin', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Service Address:', 'NORDBOOKING'); ?></strong> <span class="text-sm text-muted-foreground"><?php echo nl2br(esc_html($booking['service_address'])); ?><?php if (!empty($booking['zip_code'])) { echo ', ' . esc_html($booking['zip_code']); } ?></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Information Card -->
            <div class="nordbooking-card card-bs">
                <div class="nordbooking-card-header">
                    <div class="nordbooking-card-title-group">
                        <span class="nordbooking-card-icon"><?php echo nordbooking_get_feather_icon('info'); ?></span>
                        <h3 class="nordbooking-card-title"><?php esc_html_e('Booking Information', 'NORDBOOKING'); ?></h3>
                    </div>
                </div>
                <div class="nordbooking-card-content">
                    <div class="kpi-grid">
                        <div>
                            <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php esc_html_e('Reference:', 'NORDBOOKING'); ?></strong> <span class="text-sm text-muted-foreground"><?php echo esc_html($booking['booking_reference']); ?></span></div>
                            <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php echo nordbooking_get_feather_icon('calendar', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Date:', 'NORDBOOKING'); ?></strong> <span class="text-sm text-muted-foreground"><?php echo esc_html($booking_date_formatted); ?></span></div>
                            <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php echo nordbooking_get_feather_icon('clock', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Time:', 'NORDBOOKING'); ?></strong> <span class="text-sm text-muted-foreground"><?php echo esc_html($booking_time_formatted); ?></span></div>
                        </div>
                        <div>
                            <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php esc_html_e('Service Frequency:', 'NORDBOOKING'); ?></strong> <span class="text-sm text-muted-foreground"><?php echo esc_html(ucfirst($booking['service_frequency'] ?? 'one-time')); ?></span></div>
                            <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php esc_html_e('Has Pets:', 'NORDBOOKING'); ?></strong> <span class="text-sm text-muted-foreground"><?php echo ($booking['has_pets'] ?? false) ? 'Yes' : 'No'; ?></span></div>
                            <?php if ($booking['has_pets'] ?? false): ?>
                            <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php esc_html_e('Pet Details:', 'NORDBOOKING'); ?></strong> <span class="text-sm text-muted-foreground"><?php echo nl2br(esc_html($booking['pet_details'] ?? '')); ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="kpi-grid">
                        <div>
                            <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php esc_html_e('Property Access Method:', 'NORDBOOKING'); ?></strong> <span class="text-sm text-muted-foreground"><?php echo esc_html(ucfirst($booking['property_access_method'] ?? 'N/A')); ?></span></div>
                            <?php if (!empty($booking['property_access_details'])): ?>
                            <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php esc_html_e('Property Access Details:', 'NORDBOOKING'); ?></strong> <span class="text-sm text-muted-foreground"><?php echo nl2br(esc_html($booking['property_access_details'])); ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($booking['special_instructions'])): ?>
                    <div class="mt-4"><strong class="block font-semibold text-sm mb-1"><?php echo nordbooking_get_feather_icon('message-square', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Special Instructions:', 'NORDBOOKING'); ?></strong>
                        <p class="text-sm text-muted-foreground"><?php echo nl2br(esc_html($booking['special_instructions'])); ?></p>
                    </div>
                    <?php endif; ?>

                    <hr style="margin: 1.5rem 0;">

                    <h4 class="font-semibold text-md mb-2"><?php esc_html_e('Pricing Details', 'NORDBOOKING'); ?></h4>
                    <?php if (isset($booking['items']) && is_array($booking['items']) && !empty($booking['items'])): ?>
                        <table class="NORDBOOKING-services-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Service / Option', 'NORDBOOKING'); ?></th>
                                    <th><?php esc_html_e('Details', 'NORDBOOKING'); ?></th>
                                    <th class="price-cell"><?php esc_html_e('Price', 'NORDBOOKING'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $subtotal_calc = 0; foreach ($booking['items'] as $item): $subtotal_calc += floatval($item['item_total_price']); ?>
                                    <tr>
                                        <td data-label="<?php esc_attr_e('Service', 'NORDBOOKING'); ?>" class="service-name-cell">
                                            <?php echo esc_html($item['service_name']); ?>
                                        </td>
                                        <td data-label="<?php esc_attr_e('Base Price', 'NORDBOOKING'); ?>" class="price-cell">
                                            <?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['service_price']), 2)); ?>
                                        </td>
                                        <td data-label="<?php esc_attr_e('Item Total', 'NORDBOOKING'); ?>" class="price-cell">
                                            <?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['item_total_price']), 2)); ?>
                                        </td>
                                    </tr>
                                    <?php
                                        // Ensure selected options are available as an array (decode JSON string if needed)
                                        $selected_options_raw = $item['selected_options'] ?? [];
                                        if (is_string($selected_options_raw)) {
                                            $decoded = json_decode($selected_options_raw, true);
                                            if (json_last_error() === JSON_ERROR_NONE) {
                                                $selected_options = $decoded;
                                            } else {
                                                $selected_options = [];
                                            }
                                        } else {
                                            $selected_options = is_array($selected_options_raw) ? $selected_options_raw : [];
                                        }
                                    ?>
                                    <?php if (!empty($selected_options) && is_array($selected_options)): ?>
                                        <?php
                                            foreach ($selected_options as $option_key => $option_data):
                                                $option_field_label = '';
                                                $option_selected_value_display = '';
                                                $option_price_text = '';
                                                if (is_array($option_data) && isset($option_data['name'])) {
                                                    $option_field_label = $option_data['name'];
                                                    $value_from_db = $option_data['value'] ?? '';
                                                    if (is_string($value_from_db)) {
                                                        $decoded_value = json_decode($value_from_db, true);
                                                        if (is_array($decoded_value) && isset($decoded_value['name']) && isset($decoded_value['value'])) {
                                                            $option_field_label = $decoded_value['name'];
                                                            $option_selected_value_display = esc_html($decoded_value['value']);
                                                            $current_option_price = isset($decoded_value['price']) ? floatval($decoded_value['price']) : 0;
                                                            $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                        } elseif (is_array($decoded_value)) {
                                                            $option_selected_value_display = esc_html(wp_json_encode($decoded_value));
                                                            $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : (isset($decoded_value['price']) ? floatval($decoded_value['price']) : 0);
                                                            $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                        } else {
                                                            $option_selected_value_display = esc_html($value_from_db);
                                                            $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0;
                                                            if ($current_option_price != 0) {
                                                                $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                            }
                                                        }
                                                    } elseif (is_array($value_from_db)) {
                                                        $option_selected_value_display = esc_html(wp_json_encode($value_from_db));
                                                        $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0;
                                                        if ($current_option_price != 0) {
                                                            $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                        }
                                                    } else {
                                                        $option_selected_value_display = esc_html($value_from_db);
                                                        $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0;
                                                        if ($current_option_price != 0) {
                                                            $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                        }
                                                    }
                                                } else {
                                                    $option_field_label = is_string($option_key) ? esc_html($option_key) : 'Additional Option';
                                                    $option_selected_value_display = esc_html(wp_json_encode($option_data));
                                                }
                                            ?>
                                            <tr class="option-row">
                                                <td data-label="<?php echo esc_attr($option_field_label); ?>" class="option-name">
                                                    └ <?php echo esc_html($option_field_label); ?>
                                                </td>
                                                <td data-label="<?php esc_attr_e('Selected', 'NORDBOOKING'); ?>">
                                                    <?php echo $option_selected_value_display; ?>
                                                </td>
                                                <td data-label="<?php esc_attr_e('Price', 'NORDBOOKING'); ?>" class="price-cell">
                                                    <?php echo $option_price_text; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <hr style="margin: 1.5rem 0;">
                        <div class="NORDBOOKING-pricing-summary">
                            <p><span><?php esc_html_e('Subtotal:', 'NORDBOOKING'); ?></span> <span><?php echo esc_html($currency_symbol . number_format_i18n($subtotal_calc, 2)); ?></span></p>
                            <p><span><?php esc_html_e('Discount Applied:', 'NORDBOOKING'); ?></span> <span><?php echo esc_html($discount_amount_formatted); ?></span></p>
                            <p><strong><?php esc_html_e('Final Total:', 'NORDBOOKING'); ?></strong> <strong class="final-total"><?php echo $total_price_formatted; ?></strong></p>
                        </div>
                    <?php else: ?>
                        <p><?php esc_html_e('No service items found for this booking.', 'NORDBOOKING'); ?></p>
                    <?php endif; ?>
                     <p style="margin-top: 1rem;"><strong><?php esc_html_e('Payment Status:', 'NORDBOOKING'); ?></strong> <?php echo esc_html(ucfirst($booking['payment_status'] ?? 'N/A')); ?></p>
                </div>
            </div>
        </div>
        <div class="NORDBOOKING-sidebar">
            <!-- Status & Admin Actions Card -->
            <div class="nordbooking-card card-bs">
                <div class="nordbooking-card-header">
                    <div class="nordbooking-card-title-group">
                        <span class="nordbooking-card-icon"><?php echo nordbooking_get_feather_icon('activity'); ?></span>
                        <h3 class="nordbooking-card-title"><?php esc_html_e('Status & Actions', 'NORDBOOKING'); ?></h3>
                    </div>
                </div>
                <div class="nordbooking-card-content">
                    <div class="border-b border-dashed pb-4 mb-4">
                        <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php esc_html_e('Current Status:', 'NORDBOOKING'); ?></strong>
                            <span id="NORDBOOKING-current-status-display" class="status-badge status-<?php echo esc_attr($booking['status']); ?>">
                                <?php echo nordbooking_get_status_badge_icon_svg($booking['status']); ?>
                                <span class="status-text"><?php echo esc_html($status_display); ?></span>
                            </span>
                        </div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <label for="NORDBOOKING-single-booking-status-select" class="font-semibold text-sm flex items-center gap-1"><?php echo nordbooking_get_feather_icon('edit', 'width="16" height="16"'); ?> <?php esc_html_e('Change Status:', 'NORDBOOKING'); ?></label>
                            <select id="NORDBOOKING-single-booking-status-select" data-booking-id="<?php echo esc_attr($booking['booking_id']); ?>" class="nordbooking-filter-select">
                                <?php foreach ($booking_statuses_for_select as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($booking['status'], $value); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button id="NORDBOOKING-single-save-status-btn" class="btn btn-primary btn-sm"><?php echo nordbooking_get_feather_icon('save', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Save Status', 'NORDBOOKING'); ?></button>
                        </div>
                        <div id="NORDBOOKING-single-status-feedback" class="text-sm mt-2"></div>
                    </div>

                    <!-- Download Invoice Button -->
                    <div class="pt-4 border-b border-dashed pb-4 mb-4">
                        <strong class="block font-semibold text-sm mb-2"><?php echo nordbooking_get_feather_icon('download', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Downloads', 'NORDBOOKING'); ?></strong>
                        <a href="<?php echo esc_url( add_query_arg( 'download_invoice', 'true' ) ); ?>" class="btn btn-secondary" target="_blank">
                            <?php echo nordbooking_get_feather_icon('download', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?>
                            <?php esc_html_e('Download Invoice', 'NORDBOOKING'); ?>
                        </a>
                    </div>

                    <!-- Staff Assignment Section -->
                    <?php
                    if (current_user_can(NORDBOOKING\Classes\Auth::CAP_ASSIGN_BOOKINGS) || current_user_can(NORDBOOKING\Classes\Auth::CAP_MANAGE_BOOKINGS)) :
                        $workers = get_users([
                            'meta_key'   => \NORDBOOKING\Classes\Auth::META_KEY_OWNER_ID,
                            'meta_value' => $booking_owner_id_for_fetch, // Use the owner ID determined earlier
                            'role__in'   => [\NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF],
                        ]);
                    ?>
                    <div class="pt-4">
                        <div class="mb-3"><strong class="block font-semibold text-sm mb-1"><?php echo nordbooking_get_feather_icon('user', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Assigned Staff:', 'NORDBOOKING'); ?></strong>
                            <span id="NORDBOOKING-current-assigned-staff" class="text-sm text-muted-foreground">
                                <?php echo isset($booking['assigned_staff_name']) ? esc_html($booking['assigned_staff_name']) : esc_html__('Unassigned', 'NORDBOOKING'); ?>
                            </span>
                        </div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <label for="NORDBOOKING-single-assign-staff-select" class="font-semibold text-sm flex items-center gap-1"><?php echo nordbooking_get_feather_icon('user-plus', 'width="16" height="16"'); ?> <?php esc_html_e('Assign to Staff:', 'NORDBOOKING'); ?></label>
                            <select id="NORDBOOKING-single-assign-staff-select" data-booking-id="<?php echo esc_attr($booking['booking_id']); ?>" class="nordbooking-filter-select">
                                <option value="0"><?php esc_html_e('-- Unassign --', 'NORDBOOKING'); ?></option>
                                <?php if (!empty($workers)) : ?>
                                    <?php foreach ($workers as $worker) : ?>
                                        <option value="<?php echo esc_attr($worker->ID); ?>" <?php selected(isset($booking['assigned_staff_id']) ? $booking['assigned_staff_id'] : 0, $worker->ID); ?>>
                                            <?php echo esc_html($worker->display_name); ?> (<?php echo esc_html($worker->user_email); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled><?php esc_html_e('No staff available for this business.', 'NORDBOOKING'); ?></option>
                                <?php endif; ?>
                            </select>
                            <button id="NORDBOOKING-single-save-staff-assignment-btn" class="btn btn-primary btn-sm"><?php echo nordbooking_get_feather_icon('save', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Save Assignment', 'NORDBOOKING'); ?></button>
                        </div>
                        <div id="NORDBOOKING-single-staff-assignment-feedback" class="text-sm mt-2"></div>
                    </div>
                    <?php endif; ?>
                    <!-- End Staff Assignment Section -->

                     <div class="text-xs text-muted-foreground text-right mt-4">
                        <p><?php esc_html_e('Created:', 'NORDBOOKING'); ?> <?php echo esc_html($created_at_formatted); ?> | <?php esc_html_e('Last Updated:', 'NORDBOOKING'); ?> <?php echo esc_html($updated_at_formatted); ?></p>
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
            echo "statusIcons['" . esc_js($status_key) . "'] = '" . str_replace(["\r", "\n"], "", addslashes(nordbooking_get_status_badge_icon_svg($status_key))) . "';\n";
        }
    ?>

    $('#NORDBOOKING-single-save-status-btn').on('click', function() {
        var $button = $(this);
        var bookingId = $('#NORDBOOKING-single-booking-status-select').data('booking-id');
        var newStatus = $('#NORDBOOKING-single-booking-status-select').val();
        var $feedback = $('#NORDBOOKING-single-status-feedback');
        var $currentStatusDisplay = $('#NORDBOOKING-current-status-display');

        $feedback.text('<?php echo esc_js( __( 'Updating...', 'NORDBOOKING' ) ); ?>').removeClass('success error');
        $button.prop('disabled', true);

        $.ajax({
            url: nordbooking_dashboard_params.ajax_url, // Use localized ajax_url
            type: 'POST',
            data: {
                action: 'nordbooking_update_booking_status',
                nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>',
                booking_id: bookingId,
                new_status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    window.showAlert(response.data.message || '<?php echo esc_js( __( 'Status updated successfully!', 'NORDBOOKING' ) ); ?>', 'success');

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
                    window.showAlert(response.data.message || '<?php echo esc_js( __( 'Error updating status.', 'NORDBOOKING' ) ); ?>', 'error');
                }
            },
            error: function() {
                window.showAlert('<?php echo esc_js( __( 'AJAX request failed.', 'NORDBOOKING' ) ); ?>', 'error');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });

    $('#NORDBOOKING-single-save-staff-assignment-btn').on('click', function() {
        var $button = $(this);
        var bookingId = $('#NORDBOOKING-single-assign-staff-select').data('booking-id');
        var staffId = $('#NORDBOOKING-single-assign-staff-select').val();
        var $currentStaffDisplay = $('#NORDBOOKING-current-assigned-staff');
        var selectedStaffName = $('#NORDBOOKING-single-assign-staff-select option:selected').text();

        $button.prop('disabled', true);

        $.ajax({
            url: nordbooking_dashboard_params.ajax_url,
            type: 'POST',
            data: {
                action: 'nordbooking_assign_staff_to_booking',
                nonce: '<?php echo wp_create_nonce('nordbooking_dashboard_nonce'); ?>',
                booking_id: bookingId,
                staff_id: staffId
            },
            success: function(response) {
                if (response.success) {
                    window.showAlert(response.data.message || '<?php echo esc_js( __( 'Assignment updated successfully!', 'NORDBOOKING' ) ); ?>', 'success');
                    if (staffId === "0" || staffId === 0) {
                        $currentStaffDisplay.text('<?php echo esc_js(__('Unassigned', 'NORDBOOKING')); ?>');
                    } else {
                        $currentStaffDisplay.text(selectedStaffName.split(' (')[0]);
                    }
                } else {
                    window.showAlert(response.data.message || '<?php echo esc_js( __( 'Error updating assignment.', 'NORDBOOKING' ) ); ?>', 'error');
                }
            },
            error: function() {
                window.showAlert('<?php echo esc_js( __( 'AJAX request failed.', 'NORDBOOKING' ) ); ?>', 'error');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
});
</script>
