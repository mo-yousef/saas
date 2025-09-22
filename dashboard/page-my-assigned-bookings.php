<?php
/**
 * Page template for Staff Dashboard.
 * Shows bookings assigned to the logged-in staff member.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Ensure user is a staff member and has access to the dashboard
if ( !current_user_can( NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF ) || !current_user_can( NORDBOOKING\Classes\Auth::ACCESS_NORDBOOKING_DASHBOARD ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'NORDBOOKING' ) );
}

$current_staff_id = get_current_user_id();
$business_owner_id = NORDBOOKING\Classes\Auth::get_business_owner_id_for_worker( $current_staff_id );

if ( !$business_owner_id ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'Could not determine your associated business. Please contact your administrator.', 'NORDBOOKING' ) . '</p></div>';
    return;
}

$services_manager = new \NORDBOOKING\Classes\Services();
$discounts_manager = new \NORDBOOKING\Classes\Discounts($business_owner_id);
$notifications_manager = new \NORDBOOKING\Classes\Notifications();
$bookings_manager = new \NORDBOOKING\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

$currency_symbol = '$'; // Default currency symbol
if (isset($GLOBALS['nordbooking_settings_manager'])) {
    $currency_code_setting = $GLOBALS['nordbooking_settings_manager']->get_setting($business_owner_id, 'biz_currency_code', 'USD');
    $currency_symbol = \NORDBOOKING\Classes\Utils::get_currency_symbol($currency_code_setting);
}

// KPIs for staff - get upcoming bookings assigned to this worker
$upcoming_args = [
    'limit' => 999,
    'filter_by_exactly_assigned_staff_id' => $current_staff_id,
    'status' => ['confirmed', 'pending']
];
$upcoming_bookings_result = $bookings_manager->get_bookings_by_tenant($business_owner_id, $upcoming_args);
$upcoming_count = $upcoming_bookings_result['total_count'];

// Handle single booking view
if (isset($_GET['action']) && $_GET['action'] === 'view_booking' && isset($_GET['booking_id'])) {
    $single_booking_id = intval($_GET['booking_id']);
    
    // For workers, we need to use the business owner ID to fetch the booking
    // since bookings are stored under the business owner's tenant ID
    $booking_to_view = $bookings_manager->get_booking($single_booking_id, $business_owner_id);

    // Debug: Log the booking retrieval attempt
    error_log("NORDBOOKING: Worker {$current_staff_id} attempting to view booking {$single_booking_id}");
    error_log("NORDBOOKING: Business owner ID: {$business_owner_id}");
    
    // Additional debug: Check if the booking exists in the database at all
    global $wpdb;
    $bookings_table = \NORDBOOKING\Classes\Database::get_table_name('bookings');
    $booking_exists = $wpdb->get_row($wpdb->prepare(
        "SELECT booking_id, user_id, assigned_staff_id, customer_name FROM {$bookings_table} WHERE booking_id = %d",
        $single_booking_id
    ), ARRAY_A);
    
    if ($booking_exists) {
        error_log("NORDBOOKING: Raw booking data from DB: " . print_r($booking_exists, true));
    } else {
        error_log("NORDBOOKING: Booking {$single_booking_id} does not exist in database");
    }
    
    // Check if booking exists and is assigned to this worker
    if ($booking_to_view && (int)$booking_to_view['assigned_staff_id'] === $current_staff_id) {
        error_log("NORDBOOKING: Booking access granted for worker {$current_staff_id}");
        
        // Use the worker-specific booking view instead of the complex general one
        $worker_booking_path = __DIR__ . '/page-worker-booking-single.php';
        if (file_exists($worker_booking_path)) {
            include $worker_booking_path;
            return;
        } else {
            // Fallback to the original booking single page with proper variables
            $single_booking_id = $single_booking_id;  // Already set above
            $current_user_id = $current_staff_id;  // Set current_user_id to the worker ID
            
            $single_page_path = __DIR__ . '/page-booking-single.php';
            if (file_exists($single_page_path)) {
                include $single_page_path;
                return;
            }
        }
    } else {
        // Debug information
        error_log("NORDBOOKING: Worker {$current_staff_id} DENIED access to booking {$single_booking_id}");
        if ($booking_to_view) {
            error_log("NORDBOOKING: Booking found but assigned_staff_id is " . ($booking_to_view['assigned_staff_id'] ?? 'null') . " (expected: {$current_staff_id})");
            error_log("NORDBOOKING: Booking data: " . print_r($booking_to_view, true));
        } else {
            error_log("NORDBOOKING: Booking not found or no permission");
        }
        wp_die(esc_html__('You do not have permission to view this booking.', 'NORDBOOKING'));
    }
}

?>

<div class="wrap nordbooking-dashboard-wrap nordbooking-bookings-page-wrapper">

    <div class="nordbooking-page-header">
        <div class="nordbooking-page-header-heading">
            <span class="nordbooking-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('bookings'); ?>
            </span>
            <h1 class="wp-heading-inline"><?php esc_html_e('My Assigned Bookings', 'NORDBOOKING'); ?></h1>
        </div>
    </div>

    <!-- KPI Widgets -->
    <div class="kpi-grid">
        <div class="nordbooking-card">
            <div class="nordbooking-card-header">
                <div class="nordbooking-card-title-group">
                    <span class="nordbooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span>
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Upcoming Confirmed Bookings', 'NORDBOOKING'); ?></h3>
                </div>
            </div>
            <div class="nordbooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($upcoming_count); ?></div>
                <p class="text-xs text-muted-foreground">
                    <?php esc_html_e('Assigned to you', 'NORDBOOKING'); ?>
                </p>
            </div>
        </div>
    </div>

    <div class="nordbooking-card">
        <div class="nordbooking-card-content">
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

            $bookings_result = $bookings_manager->get_bookings_by_tenant($business_owner_id, $args);

            if ( ! empty( $bookings_result['bookings'] ) ) :
            ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 hidden md:table-header-group">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e( 'Ref', 'NORDBOOKING' ); ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e( 'Customer', 'NORDBOOKING' ); ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e( 'Booked Date', 'NORDBOOKING' ); ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e( 'Total', 'NORDBOOKING' ); ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e( 'Status', 'NORDBOOKING' ); ?></th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?php esc_html_e( 'Actions', 'NORDBOOKING' ); ?></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ( $bookings_result['bookings'] as $booking ) :
                                $status_val = $booking['status'];
                                $status_display = !empty($status_val) ? ucfirst(str_replace('-', ' ', $status_val)) : __('N/A', 'NORDBOOKING');
                                $status_icon_html = function_exists('nordbooking_get_status_badge_icon_svg') ? nordbooking_get_status_badge_icon_svg($status_val) : '';
                                $total_price_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2));
                                $booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));
                                $booking_time_formatted = date_i18n(get_option('time_format'), strtotime($booking['booking_time']));
                                $details_page_url = home_url('/dashboard/my-assigned-bookings/?action=view_booking&booking_id=' . $booking['booking_id']);
                            ?>
                                <tr data-booking-id="<?php echo esc_attr($booking['booking_id']); ?>" class="block md:table-row border-b md:border-none">
                                    <td data-label="<?php esc_attr_e('Ref', 'NORDBOOKING'); ?>" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 block md:table-cell"><?php echo esc_html($booking['booking_reference']); ?></td>
                                    <td data-label="<?php esc_attr_e('Customer', 'NORDBOOKING'); ?>" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 block md:table-cell"><?php echo esc_html($booking['customer_name']); ?><br><small><?php echo esc_html($booking['customer_email']); ?></small></td>
                                    <td data-label="<?php esc_attr_e('Booked Date', 'NORDBOOKING'); ?>" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 block md:table-cell"><?php echo esc_html($booking_date_formatted . ' ' . $booking_time_formatted); ?></td>
                                    <td data-label="<?php esc_attr_e('Total', 'NORDBOOKING'); ?>" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 block md:table-cell"><?php echo $total_price_formatted; ?></td>
                                    <td data-label="<?php esc_attr_e('Status', 'NORDBOOKING'); ?>" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 block md:table-cell">
                                        <span class="status-badge status-<?php echo esc_attr($status_val); ?>">
                                            <?php echo $status_icon_html; ?>
                                            <span class="status-text"><?php echo esc_html($status_display); ?></span>
                                        </span>
                                    </td>
                                    <td data-label="<?php esc_attr_e('Actions', 'NORDBOOKING'); ?>" class="px-6 py-4 whitespace-nowrap text-sm font-medium block md:table-cell">
                                        <a href="<?php echo esc_url($details_page_url); ?>" class="btn btn-outline btn-sm"><?php esc_html_e('View Details', 'NORDBOOKING'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                // Pagination
                $total_bookings = $bookings_result['total_count'];
                $total_pages = ceil( $total_bookings / $limit );
                if ( $total_pages > 1 ) {
                    echo '<div class="tablenav bottom"><div class="tablenav-pages"><span class="pagination-links">';
                    echo paginate_links( array(
                        'base'    => add_query_arg( 'paged', '%#%' ),
                        'format'  => '',
                        'current' => $paged,
                        'total'   => $total_pages,
                    ) );
                    echo '</span></div></div>';
                }
                ?>
            <?php else : ?>
                <div class="NORDBOOKING-empty-state">
                    <p><?php esc_html_e('No bookings are currently assigned to you.', 'NORDBOOKING'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
