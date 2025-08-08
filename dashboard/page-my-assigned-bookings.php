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

// Instantiate necessary managers if not already available globally or passed
// For simplicity, assuming they might be needed.
// Ensure these classes are loaded (typically via an autoloader or direct require_once statements in the main plugin file)
if ( !class_exists('MoBooking\Classes\Bookings') || !class_exists('MoBooking\Classes\Services') || !class_exists('MoBooking\Classes\Discounts') || !class_exists('MoBooking\Classes\Notifications') ) {
    // This is a fallback, ideally class loading is handled more centrally
    // require_once MOBOOKING_PLUGIN_DIR . 'classes/Services.php'; // Example path
    // require_once MOBOOKING_PLUGIN_DIR . 'classes/Discounts.php';
    // require_once MOBOOKING_PLUGIN_DIR . 'classes/Notifications.php';
    // require_once MOBOOKING_PLUGIN_DIR . 'classes/Bookings.php';
}

// These would typically be instantiated or retrieved from a central DI container or service locator
// $services_manager = new \MoBooking\Classes\Services();
// $discounts_manager = new \MoBooking\Classes\Discounts($business_owner_id); // Discounts are per-owner
// $notifications_manager = new \MoBooking\Classes\Notifications();
// $bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

// For now, assume $bookings_manager is available, similar to other dashboard pages or passed via context
// This part will be fleshed out once we know how $bookings_manager is typically made available in dashboard pages.
// For the purpose of this step, we will focus on the structure and the query modification.

$currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol(); // Or get specific to business owner
if (isset($GLOBALS['mobooking_settings_manager'])) {
    $currency_code_setting = $GLOBALS['mobooking_settings_manager']->get_setting($business_owner_id, 'biz_currency_code', 'USD');
    $currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($currency_code_setting);
}


?>

<div>
    <h3 class="text-3xl font-medium text-gray-700 dark:text-gray-200">My Assigned Bookings</h3>

    <div class="mt-8">
        <div class="flex flex-col">
            <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200 dark:border-gray-700">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Ref</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Customer</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Booked Date</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Total</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Status</th>
                                <th class="px-6 py-3 text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            <?php
                            if (!isset($bookings_manager) || !$bookings_manager instanceof \MoBooking\Classes\Bookings) {
                                if (class_exists('MoBooking\Classes\Bookings') && class_exists('MoBooking\Classes\Services') && class_exists('MoBooking\Classes\Discounts') && class_exists('MoBooking\Classes\Notifications')) {
                                    $services_manager_local = new \MoBooking\Classes\Services();
                                    $discounts_manager_local = new \MoBooking\Classes\Discounts($business_owner_id);
                                    $notifications_manager_local = new \MoBooking\Classes\Notifications();
                                    $bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager_local, $notifications_manager_local, $services_manager_local);
                                } else {
                                    $bookings_manager = null;
                                }
                            }

                            if ($bookings_manager) {
                                $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                                $limit = 20;
                                $args = [
                                    'limit' => $limit,
                                    'paged' => $paged,
                                    'orderby' => 'booking_date',
                                    'order' => 'DESC',
                                    'filter_by_exactly_assigned_staff_id' => $current_staff_id,
                                ];
                                $bookings_result = $bookings_manager->get_bookings_by_tenant($current_staff_id, $args);

                                if (!empty($bookings_result['bookings'])) {
                                    foreach ($bookings_result['bookings'] as $booking) {
                                        $status_val = $booking['status'];
                                        $status_display = !empty($status_val) ? ucfirst(str_replace('-', ' ', $status_val)) : __('N/A', 'mobooking');
                                        $total_price_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2));
                                        $booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));
                                        $booking_time_formatted = date_i18n(get_option('time_format'), strtotime($booking['booking_time']));
                                        $details_page_url = home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $booking['booking_id']);
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html($booking['booking_reference']); ?></td>
                                            <td class="px-6 py-4 whitespace-no-wrap">
                                                <div><?php echo esc_html($booking['customer_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo esc_html($booking['customer_email']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-no-wrap"><?php echo esc_html($booking_date_formatted . ' ' . $booking_time_formatted); ?></td>
                                            <td class="px-6 py-4 whitespace-no-wrap"><?php echo $total_price_formatted; ?></td>
                                            <td class="px-6 py-4 whitespace-no-wrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_val === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo esc_html($status_display); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-no-wrap text-sm font-medium">
                                                <a href="<?php echo esc_url($details_page_url); ?>" class="text-indigo-600 hover:text-indigo-900 dark:hover:text-indigo-400">View Details</a>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No bookings are currently assigned to you.</td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-red-500">Booking system components are missing.</td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// We can reuse parts of assets/js/dashboard-bookings.js or create a new minimal script
// For now, let's assume the existing script might be adapted or a new one created.
// The key will be to call loadBookings with the staff_id filter.
?>
<script type="text/template" id="mobooking-staff-booking-item-template">
    <%
    // This template will be very similar to #mobooking-booking-item-template
    // but might have fewer actions or slightly different data display.
    // For now, let's assume it's the same and adjust if needed.
    %>
    <tr data-booking-id="<%= booking_id %>">
        <td data-colname="<?php esc_attr_e('Ref', 'mobooking'); ?>"><%= booking_reference %></td>
        <td data-colname="<?php esc_attr_e('Customer', 'mobooking'); ?>"><%= customer_name %><br><small><%= customer_email %></small></td>
        <td data-colname="<?php esc_attr_e('Booked Date', 'mobooking'); ?>"><%= booking_date_formatted %> <%= booking_time_formatted %></td>
        <td data-colname="<?php esc_attr_e('Total', 'mobooking'); ?>"><%= total_price_formatted %></td>
        <td data-colname="<?php esc_attr_e('Status', 'mobooking'); ?>">
            <span class="status-badge status-<%= status %>">
                <%= icon_html %> <span class="status-text"><%= status_display %></span>
            </span>
        </td>
        <td data-colname="<?php esc_attr_e('Actions', 'mobooking'); ?>" class="mobooking-table-actions">
            <a href="<%= details_page_url %>" class="button button-small"><?php esc_html_e('View Details', 'mobooking'); ?></a>
            <% if (typeof mobooking_dashboard_params !== 'undefined' && mobooking_dashboard_params.currentUserCanUpdateOwnBookingStatus) { %>
            <% /* Staff might have a quick status update dropdown here or on the details page */ %>
            <% } %>
        </td>
    </tr>
</script>
