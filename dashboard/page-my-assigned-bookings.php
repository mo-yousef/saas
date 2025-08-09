<?php
/**
 * Page template for Staff Dashboard.
 * Shows bookings assigned to the logged-in staff member.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure user is a staff member and has access to the dashboard
if ( !current_user_can( MoBooking\Classes\Auth::ROLE_WORKER_STAFF ) || !current_user_can( MoBooking\Classes\Auth::ACCESS_MOBOOKING_DASHBOARD ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}

$current_staff_id = get_current_user_id();
$business_owner_id = MoBooking\Classes\Auth::get_business_owner_id_for_worker( $current_staff_id );

if ( !$business_owner_id ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Could not determine your associated business. Please contact your administrator.', 'mobooking' ) . '</p></div>';
    return;
}

$services_manager = new \MoBooking\Classes\Services();
$discounts_manager = new \MoBooking\Classes\Discounts($business_owner_id);
$notifications_manager = new \MoBooking\Classes\Notifications();
$bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

$currency_symbol = '$'; // Default currency symbol
if (isset($GLOBALS['mobooking_settings_manager'])) {
    $currency_code_setting = $GLOBALS['mobooking_settings_manager']->get_setting($business_owner_id, 'biz_currency_code', 'USD');
    $currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($currency_code_setting);
}

$kpi_data = $bookings_manager->get_kpi_data($business_owner_id);
// For workers, we might want to show different KPIs, e.g., only their upcoming bookings
$upcoming_args = [
    'limit' => 999, // High limit to count all
    'filter_by_exactly_assigned_staff_id' => $current_staff_id,
    'status' => 'confirmed' // Only confirmed upcoming
];
$upcoming_bookings_result = $bookings_manager->get_bookings_by_tenant($current_staff_id, $upcoming_args);
$kpi_data['upcoming_count'] = $upcoming_bookings_result['total_count'];


if (isset($_GET['action']) && $_GET['action'] === 'view_booking' && isset($_GET['booking_id'])) {
    $single_booking_id = intval($_GET['booking_id']);
    // For workers, we need to ensure they can only see bookings assigned to them
    $booking_to_view = $bookings_manager->get_booking($single_booking_id, $business_owner_id);
    if ($booking_to_view && (int)$booking_to_view['assigned_staff_id'] === $current_staff_id) {
        $single_page_path = __DIR__ . '/page-booking-single.php';
        if (file_exists($single_page_path)) {
            include $single_page_path;
            return;
        }
    } else {
        wp_die(esc_html__('You do not have permission to view this booking.', 'mobooking'));
    }
}

?>

<div class="wrap mobooking-dashboard-wrap mobooking-bookings-page-wrapper">

    <div class="mobooking-page-header">
        <h1 class="wp-heading-inline"><?php esc_html_e('My Assigned Bookings', 'mobooking'); ?></h1>
    </div>

    <div class="dashboard-kpi-grid mobooking-overview-kpis">
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Upcoming Confirmed Bookings', 'mobooking'); ?></span>
                 <div class="kpi-icon upcoming">⏰</div>
            </div>
            <div class="kpi-value"><?php echo esc_html($kpi_data['upcoming_count']); ?></div>
        </div>
    </div>

    <div class="mobooking-card">
        <div class="mobooking-card-content">
            <?php
            $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
            $limit = 20;

            $args = [
                'limit'    => $limit,
                'paged'    => $paged,
                'orderby'  => 'booking_date',
                'order'    => 'ASC',
                'filter_by_exactly_assigned_staff_id' => $current_staff_id,
            ];

            $bookings_result = $bookings_manager->get_bookings_by_tenant($current_staff_id, $args);

            if ( ! empty( $bookings_result['bookings'] ) ) :
            ?>
                <div class="mobooking-workers-grid">
                    <?php foreach ( $bookings_result['bookings'] as $booking ) : ?>
                        <div class="mobooking-worker-card">
                            <div class="mobooking-card-content">
                                <div class="mobooking-worker-info">
                                    <div class="mobooking-worker-details">
                                        <h3 class="mobooking-worker-name"><?php echo esc_html($booking['customer_name']); ?></h3>
                                        <p class="mobooking-worker-email"><?php echo esc_html($booking['booking_reference']); ?></p>
                                        <p class="mobooking-worker-email">
                                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($booking['booking_date'])) . ' ' . date_i18n(get_option('time_format'), strtotime($booking['booking_time']))); ?>
                                        </p>
                                        <div class="mobooking-badge mobooking-badge-secondary">
                                            <?php echo esc_html(ucfirst($booking['status'])); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="mobooking-worker-actions">
                                    <a href="<?php echo esc_url(home_url('/dashboard/my-assigned-bookings/?action=view_booking&booking_id=' . $booking['booking_id'])); ?>" class="mobooking-button mobooking-button-sm mobooking-button-outline">
                                        <?php esc_html_e('View Details', 'mobooking'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="mobooking-empty-state">
                    <p><?php esc_html_e('No bookings are currently assigned to you.', 'mobooking'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
