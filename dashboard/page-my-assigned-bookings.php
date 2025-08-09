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

$currency_symbol = '$'; // Default currency symbol
if (isset($GLOBALS['mobooking_settings_manager'])) {
    $currency_code_setting = $GLOBALS['mobooking_settings_manager']->get_setting($business_owner_id, 'biz_currency_code', 'USD');
    $currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($currency_code_setting);
}


?>

<div class="wrap mobooking-dashboard-page mobooking-staff-dashboard-page">
    <h1><?php esc_html_e( 'My Assigned Bookings', 'mobooking' ); ?></h1>

    <div id="mobooking-feedback-area" class="notice" style="display:none;">
        <p></p>
    </div>

    <div class="mobooking-card">
        <div class="mobooking-card-header">
            <h3><?php esc_html_e( 'Bookings Assigned to Me', 'mobooking' ); ?></h3>
        </div>
        <div class="mobooking-card-content">
            <div id="mobooking-staff-bookings-list-container">
                <p><?php esc_html_e( 'Loading your bookings...', 'mobooking' ); ?></p>
                <?php
                // Ensure $bookings_manager is available
                // This typically would be set up in dashboard-shell.php or a similar central place
                if (!isset($bookings_manager) || !$bookings_manager instanceof \MoBooking\Classes\Bookings) {
                    // Fallback instantiation if not already available
                    if (class_exists('MoBooking\Classes\Bookings') &&
                        class_exists('MoBooking\Classes\Services') &&
                        class_exists('MoBooking\Classes\Discounts') &&
                        class_exists('MoBooking\Classes\Notifications')) {

                        $services_manager_local = new \MoBooking\Classes\Services();
                        $discounts_manager_local = new \MoBooking\Classes\Discounts($business_owner_id);
                        $notifications_manager_local = new \MoBooking\Classes\Notifications();
                        $bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager_local, $notifications_manager_local, $services_manager_local);
                    } else {
                        echo '<div class="notice notice-error"><p>' . esc_html__('Booking system components are missing.', 'mobooking') . '</p></div>';
                        $bookings_manager = null; // Ensure it's null if not properly instantiated
                    }
                }

                if ($bookings_manager) {
                    $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
                    $limit = 20; // Or make this configurable

                    $args = [
                        'limit'    => $limit,
                        'paged'    => $paged,
                        'orderby'  => 'booking_date', // Default order
                        'order'    => 'DESC',
                        'filter_by_exactly_assigned_staff_id' => $current_staff_id,
                    ];

                    // Note: get_bookings_by_tenant expects the first argument to be the ID of the user whose context we are operating in
                    // For a staff member viewing their own bookings, this is their own ID, but the data is owned by $business_owner_id.
                    // The method get_bookings_by_tenant itself handles the logic:
                    // if current_logged_in_user_id is a worker, it fetches for their owner_id.
                    // Then, our new filter_by_exactly_assigned_staff_id further filters these results.
                    $bookings_result = $bookings_manager->get_bookings_by_tenant($current_staff_id, $args);

                    if ( ! empty( $bookings_result['bookings'] ) ) {
                        echo '<div class="mobooking-table-responsive-wrapper">';
                        echo '<table class="mobooking-table wp-list-table widefat fixed striped">';
                        echo '<thead><tr>';
                        echo '<th>' . esc_html__( 'Ref', 'mobooking' ) . '</th>';
                        echo '<th>' . esc_html__( 'Customer', 'mobooking' ) . '</th>';
                        echo '<th>' . esc_html__( 'Booked Date', 'mobooking' ) . '</th>';
                        echo '<th>' . esc_html__( 'Total', 'mobooking' ) . '</th>';
                        echo '<th>' . esc_html__( 'Status', 'mobooking' ) . '</th>';
                        echo '<th>' . esc_html__( 'Actions', 'mobooking' ) . '</th>';
                        echo '</tr></thead>';
                        echo '<tbody>';

                        foreach ( $bookings_result['bookings'] as $booking ) {
                            $status_val = $booking['status'];
                            $status_display = !empty($status_val) ? ucfirst(str_replace('-', ' ', $status_val)) : __('N/A', 'mobooking');
                            // Assuming mobooking_get_status_badge_icon_svg is available or included
                            $status_icon_html = function_exists('mobooking_get_status_badge_icon_svg') ? mobooking_get_status_badge_icon_svg($status_val) : '';


                            $total_price_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2));
                            $booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));
                            $booking_time_formatted = date_i18n(get_option('time_format'), strtotime($booking['booking_time']));

                            // Link to the main booking details page
                            $details_page_url = home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $booking['booking_id']);

                            echo '<tr data-booking-id="' . esc_attr( $booking['booking_id'] ) . '">';
                            echo '<td data-colname="' . esc_attr__( 'Ref', 'mobooking' ) . '">' . esc_html( $booking['booking_reference'] ) . '</td>';
                            echo '<td data-colname="' . esc_attr__( 'Customer', 'mobooking' ) . '">' . esc_html( $booking['customer_name'] ) . '<br><small>' . esc_html( $booking['customer_email'] ) . '</small></td>';
                            echo '<td data-colname="' . esc_attr__( 'Booked Date', 'mobooking' ) . '">' . esc_html( $booking_date_formatted . ' ' . $booking_time_formatted ) . '</td>';
                            echo '<td data-colname="' . esc_attr__( 'Total', 'mobooking' ) . '">' . $total_price_formatted . '</td>';
                            echo '<td data-colname="' . esc_attr__( 'Status', 'mobooking' ) . '"><span class="status-badge status-' . esc_attr( $status_val ) . '">' . $status_icon_html . '<span class="status-text">' . esc_html( $status_display ) . '</span></span></td>';
                            echo '<td data-colname="' . esc_attr__( 'Actions', 'mobooking' ) . '" class="mobooking-table-actions">';
                            echo '<a href="' . esc_url( $details_page_url ) . '" class="button button-small">' . __( 'View Details', 'mobooking' ) . '</a> ';
                            // Staff might have other actions here later, like a quick status update if allowed.
                            echo '</td></tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>'; // end mobooking-table-responsive-wrapper

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

                    } else {
                        echo '<p>' . esc_html__( 'No bookings are currently assigned to you.', 'mobooking' ) . '</p>';
                    }
                }
                ?>
            </div>
            <div id="mobooking-staff-bookings-pagination-container" class="tablenav bottom">
                 <?php // Pagination is now rendered above directly with PHP ?>
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
