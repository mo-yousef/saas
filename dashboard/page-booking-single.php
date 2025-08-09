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
    .mobooking-status-form label { font-weight: 600; display: inline-flex; align-items: center; gap: 0.25rem;}
    .mobooking-service-items-list { list-style: none; padding: 0; }
    .mobooking-service-items-list > li { padding: 0.75rem 0; border-bottom: 1px dashed var(--border, #e0e0e0); }
    .mobooking-service-items-list > li:last-child { border-bottom: none; }
    .mobooking-service-options-list { list-style: disc; padding-left: 1.5rem; margin-top: 0.5rem; font-size: 0.9em; }
    .mobooking-service-options-list li { margin-bottom: 0.25rem; }
    .mobooking-pricing-summary p { margin: 0.5rem 0; display: flex; justify-content: space-between; }
    .mobooking-pricing-summary strong.final-total { font-size: 1.2em; color: var(--primary); }
    .mobooking-meta-info { font-size: 0.8rem; color: var(--muted-foreground); text-align: right; margin-top: 1rem; }
    /* New Status Badge Styles (ShadCN Inspired) */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25em 0.6em; /* Keep similar padding */
        font-size: 0.85em;     /* Keep similar font size */
        font-weight: 500;      /* ShadCN uses medium weight */
        border-radius: var(--radius, 0.5rem);
        border: 1px solid transparent;
        line-height: 1.2;      /* Ensure consistent line height */
    }

    .status-badge .feather {
        width: 1em;
        height: 1em;
        margin-right: 0.4em;
        stroke-width: 2.5;
    }

    .status-badge.status-pending {
        background-color: hsl(var(--muted)); /* Light gray */
        color: hsl(var(--muted-foreground)); /* Darker gray text */
        border-color: hsl(var(--border));    /* Subtle border */
    }
    .status-badge.status-pending .feather {
        color: hsl(var(--muted-foreground));
    }

    .status-badge.status-confirmed {
        background-color: hsl(var(--primary));
        color: hsl(var(--primary-foreground));
        border-color: hsl(var(--primary));
    }
    .status-badge.status-confirmed .feather {
        color: hsl(var(--primary-foreground));
    }

    .status-badge.status-processing {
        background-color: hsl(200, 80%, 95%); /* Lighter Blue */
        color: hsl(200, 70%, 40%);            /* Darker Blue text */
        border-color: hsl(200, 70%, 70%);     /* Blue border */
    }
    .status-badge.status-processing .feather {
        color: hsl(200, 70%, 40%);
    }

    .status-badge.status-on-hold {
        background-color: hsl(45, 100%, 95%); /* Lighter Yellow/Amber */
        color: hsl(45, 100%, 25%);            /* Darker text for yellow */
        border-color: hsl(45, 100%, 70%);     /* Yellow/Amber border */
    }
    .status-badge.status-on-hold .feather {
        color: hsl(45, 100%, 25%);
    }

    .status-badge.status-completed {
        background-color: hsl(145, 63%, 95%); /* Lighter Green */
        color: hsl(145, 63%, 22%);            /* Darker Green text */
        border-color: hsl(145, 63%, 72%);     /* Green border */
    }
    .status-badge.status-completed .feather {
        color: hsl(145, 63%, 22%);
    }

    .status-badge.status-cancelled {
        background-color: hsl(var(--destructive) / 0.1); /* Lighter Destructive Background */
        color: hsl(var(--destructive));                  /* Destructive Text Color */
        border-color: hsl(var(--destructive) / 0.3);     /* Destructive Border Color */
    }
    .status-badge.status-cancelled .feather {
        color: hsl(var(--destructive));
    }
    /* End New Status Badge Styles */

    .mobooking-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .mobooking-page-header h1 { font-size: 1.8rem; margin:0; color: var(--foreground); }

    .mobooking-status-feedback.success { color: green; margin-top: 0.5rem; }
    .mobooking-status-feedback.error { color: red; margin-top: 0.5rem; }

    /* Responsive Table for Services/Options */
    .mobooking-services-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    .mobooking-services-table th, .mobooking-services-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--border, #e0e0e0);
    }
    .mobooking-services-table th {
        background-color: hsl(var(--muted)/0.5);
        font-weight: 600;
        font-size: 0.9em;
    }
    .mobooking-services-table .service-name-cell { font-weight: 600; }
    .mobooking-services-table .option-row td { padding-left: 2.5rem; font-size: 0.9em; }
    .mobooking-services-table .option-name { color: var(--muted-foreground); }
    .mobooking-services-table .price-cell { text-align: right; white-space: nowrap; }

    @media (max-width: 768px) {
        .mobooking-sbs-grid { grid-template-columns: 1fr; }
        .mobooking-services-table thead { display: none; } /* Hide table headers on mobile */
        .mobooking-services-table tr { display: block; margin-bottom: 1rem; border: 1px solid var(--border, #e0e0e0); border-radius: var(--radius, 0.5rem); }
        .mobooking-services-table td { display: block; text-align: right; padding-left: 50%; position: relative; border-bottom: 1px dashed var(--border, #e0e0e0); }
        .mobooking-services-table td:last-child { border-bottom: none; }
        .mobooking-services-table td::before {
            content: attr(data-label);
            position: absolute;
            left: 0.75rem;
            width: calc(50% - 1.5rem); /* 50% minus padding */
            padding-right: 0.75rem;
            font-weight: 600;
            text-align: left;
            white-space: nowrap;
        }
        .mobooking-services-table .option-row td { padding-left: 1.5rem; /* Adjust for stacked mobile */ }
        .mobooking-services-table .option-row td::before { padding-left: 1.5rem; /* Indent data label for options */ }
        .mobooking-services-table .price-cell { text-align: right !important; } /* Ensure price is right aligned */
        .mobooking-services-table td.service-name-cell { font-weight: bold; background-color: hsl(var(--muted)/0.3); padding-top: 1rem; padding-bottom: 1rem; text-align: left; padding-left: 0.75rem;}
        .mobooking-services-table td.service-name-cell::before { display: none; } /* No data-label for the main service name cell */

    }

    @media (max-width: 480px) {
        .mobooking-services-table td { padding-left: 40%; } /* Adjust for very small screens */
        .mobooking-services-table td::before { width: calc(40% - 1.5rem); }
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
                <p class="mobooking-sbs-item"><strong><?php esc_html_e('Current Status:', 'mobooking'); ?></strong>
                    <span id="mobooking-current-status-display" class="status-badge status-<?php echo esc_attr($booking['status']); ?>">
                        <?php echo mobooking_get_status_badge_icon_svg($booking['status']); ?>
                        <span class="status-text"><?php echo esc_html($status_display); ?></span>
                    </span>
                </p>
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

            <!-- Staff Assignment Section -->
            <?php
            if (current_user_can(MoBooking\Classes\Auth::CAP_ASSIGN_BOOKINGS) || current_user_can(MoBooking\Classes\Auth::CAP_MANAGE_BOOKINGS)) :
                $workers = get_users([
                    'meta_key'   => \MoBooking\Classes\Auth::META_KEY_OWNER_ID,
                    'meta_value' => $booking_owner_id_for_fetch, // Use the owner ID determined earlier
                    'role__in'   => [\MoBooking\Classes\Auth::ROLE_WORKER_STAFF],
                ]);
            ?>
            <div class="mobooking-staff-assignment-section" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed var(--border, #e0e0e0);">
                <p class="mobooking-sbs-item"><strong><?php esc_html_e('Assigned Staff:', 'mobooking'); ?></strong>
                    <span id="mobooking-current-assigned-staff">
                        <?php echo isset($booking['assigned_staff_name']) ? esc_html($booking['assigned_staff_name']) : esc_html__('Unassigned', 'mobooking'); ?>
                    </span>
                </p>
                <div class="mobooking-staff-assignment-form" style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem; flex-wrap: wrap;">
                    <label for="mobooking-single-assign-staff-select"><?php echo mobooking_get_feather_icon('user-plus', 'width="16" height="16" style="vertical-align:middle; margin-right:0.25rem;"'); ?> <?php esc_html_e('Assign to Staff:', 'mobooking'); ?></label>
                    <select id="mobooking-single-assign-staff-select" data-booking-id="<?php echo esc_attr($booking['booking_id']); ?>">
                        <option value="0"><?php esc_html_e('-- Unassign --', 'mobooking'); ?></option>
                        <?php if (!empty($workers)) : ?>
                            <?php foreach ($workers as $worker) : ?>
                                <option value="<?php echo esc_attr($worker->ID); ?>" <?php selected(isset($booking['assigned_staff_id']) ? $booking['assigned_staff_id'] : 0, $worker->ID); ?>>
                                    <?php echo esc_html($worker->display_name); ?> (<?php echo esc_html($worker->user_email); ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled><?php esc_html_e('No staff available for this business.', 'mobooking'); ?></option>
                        <?php endif; ?>
                    </select>
                    <button id="mobooking-single-save-staff-assignment-btn" class="button button-primary button-small"><?php esc_html_e('Save Assignment', 'mobooking'); ?></button>
                </div>
                <div id="mobooking-single-staff-assignment-feedback" class="mobooking-status-feedback"></div>
            </div>
            <?php endif; ?>
            <!-- End Staff Assignment Section -->

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
                <table class="mobooking-services-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Service / Option', 'mobooking'); ?></th>
                            <th><?php esc_html_e('Details', 'mobooking'); ?></th>
                            <th class="price-cell"><?php esc_html_e('Price', 'mobooking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $subtotal_calc = 0; foreach ($booking['items'] as $item): $subtotal_calc += floatval($item['item_total_price']); ?>
                            <tr>
                                <td data-label="<?php esc_attr_e('Service', 'mobooking'); ?>" class="service-name-cell">
                                    <?php echo esc_html($item['service_name']); ?>
                                </td>
                                <td data-label="<?php esc_attr_e('Base Price', 'mobooking'); ?>" class="price-cell">
                                    <?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['service_price']), 2)); ?>
                                </td>
                                <td data-label="<?php esc_attr_e('Item Total', 'mobooking'); ?>" class="price-cell">
                                    <?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['item_total_price']), 2)); ?>
                                </td>
                            </tr>
                            <?php if (!empty($item['selected_options']) && is_array($item['selected_options'])): ?>
                                <?php
                                if (is_array($item['selected_options'])) {
                                    foreach ($item['selected_options'] as $option_key => $option_data):
                                        $option_field_label = ''; // The label of the option field itself, e.g., "Number of Doors"
                                        $option_selected_value_display = ''; // The specific value chosen, e.g., "3"
                                        $option_price_text = ''; // Price of this specific choice

                                        // $option_data is expected to be an array like ['name' => 'Field Label', 'value' => 'Selected Value/JSON String', 'price' => X]
                                        if (is_array($option_data) && isset($option_data['name'])) {
                                            $option_field_label = $option_data['name']; // This is the 'name' from selected_options_summary
                                            $value_from_db = $option_data['value'] ?? '';

                                            if (is_string($value_from_db)) {
                                                $decoded_value = json_decode($value_from_db, true);
                                                if (is_array($decoded_value) && isset($decoded_value['name']) && isset($decoded_value['value'])) {
                                                    // If value was '{"name":"Doors", "value":"22", "price":0}'
                                                    // $option_field_label might be something generic like "Extra Options",
                                                    // and $decoded_value['name'] is "Doors", $decoded_value['value'] is "22"
                                                    // For clarity, let's use the decoded name if available, and append to field_label or use as primary
                                                    $option_field_label = $decoded_value['name']; // Use the name from JSON if more specific
                                                    $option_selected_value_display = esc_html($decoded_value['value']);
                                                    $current_option_price = isset($decoded_value['price']) ? floatval($decoded_value['price']) : 0;
                                                    $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                } elseif (is_array($decoded_value)) {
                                                    $option_selected_value_display = esc_html(wp_json_encode($decoded_value));
                                                     // Price might be in $option_data['price'] if the value itself is complex but price is top-level for the option
                                                    $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : (isset($decoded_value['price']) ? floatval($decoded_value['price']) : 0);
                                                    $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                } else {
                                                    // $value_from_db is a simple string
                                                    $option_selected_value_display = esc_html($value_from_db);
                                                    $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0; // Check original $option_data for price
                                                    if ($current_option_price != 0) { // Only show price if it's part of this $option_data
                                                       $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                    }
                                                }
                                            } elseif (is_array($value_from_db)) {
                                                // If $option_data['value'] is already an array
                                                $option_selected_value_display = esc_html(wp_json_encode($value_from_db));
                                                $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0;
                                                if ($current_option_price != 0) {
                                                    $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                }
                                            } else {
                                                // Scalar value
                                                $option_selected_value_display = esc_html($value_from_db);
                                                $current_option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0;
                                                 if ($current_option_price != 0) {
                                                    $option_price_text = ($current_option_price >= 0 ? '+' : '') . esc_html($currency_symbol . number_format_i18n($current_option_price, 2));
                                                 }
                                            }
                                        } else {
                                            // Fallback for very different structures
                                            $option_field_label = is_string($option_key) ? esc_html($option_key) : 'Additional Option';
                                            $option_selected_value_display = esc_html(wp_json_encode($option_data));
                                        }
                                ?>
                                    <tr class="option-row">
                                        <td data-label="<?php echo esc_attr($option_field_label); ?>" class="option-name">
                                            └ <?php echo esc_html($option_field_label); ?>
                                        </td>
                                        <td data-label="<?php esc_attr_e('Selected', 'mobooking'); ?>">
                                            <?php echo $option_selected_value_display; ?>
                                        </td>
                                        <td data-label="<?php esc_attr_e('Price', 'mobooking'); ?>" class="price-cell">
                                            <?php echo $option_price_text; ?>
                                        </td>
                                    </tr>
                                <?php endforeach;
                                } ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <hr style="margin: 1.5rem 0;">
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

    <div class="mobooking-sbs-panel">
        <div class="mobooking-sbs-panel-header">
            <?php echo mobooking_get_feather_icon('info'); ?>
            <h3><?php esc_html_e('Advanced Details', 'mobooking'); ?></h3>
        </div>
        <div class="mobooking-sbs-panel-content">
            <div class="mobooking-sbs-grid">
                <div class="mobooking-sbs-item">
                    <strong><?php esc_html_e('Service Frequency:', 'mobooking'); ?></strong>
                    <span><?php echo esc_html(ucfirst($booking['service_frequency'] ?? 'one-time')); ?></span>
                </div>
                <div class="mobooking-sbs-item">
                    <strong><?php esc_html_e('Has Pets:', 'mobooking'); ?></strong>
                    <span><?php echo ($booking['has_pets'] ?? false) ? 'Yes' : 'No'; ?></span>
                </div>
                <?php if ($booking['has_pets'] ?? false): ?>
                <div class="mobooking-sbs-item">
                    <strong><?php esc_html_e('Pet Details:', 'mobooking'); ?></strong>
                    <span><?php echo nl2br(esc_html($booking['pet_details'] ?? '')); ?></span>
                </div>
                <?php endif; ?>
                <div class="mobooking-sbs-item">
                    <strong><?php esc_html_e('Property Access Method:', 'mobooking'); ?></strong>
                    <span><?php echo esc_html(ucfirst($booking['property_access_method'] ?? 'N/A')); ?></span>
                </div>
                <?php if (!empty($booking['property_access_details'])): ?>
                <div class="mobooking-sbs-item">
                    <strong><?php esc_html_e('Property Access Details:', 'mobooking'); ?></strong>
                    <span><?php echo nl2br(esc_html($booking['property_access_details'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
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
