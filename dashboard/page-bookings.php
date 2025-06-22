<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php
/**
 * Dashboard Page: Bookings
 * @package MoBooking
 */

// Ensure critical classes are loaded
// Assuming dashboard/page-bookings.php is one level down from the directory containing classes/.
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Utils.php';
require_once __DIR__ . '/../classes/Services.php';
require_once __DIR__ . '/../classes/Discounts.php';
require_once __DIR__ . '/../classes/Notifications.php';
require_once __DIR__ . '/../classes/Bookings.php';
// No separate BookingsManager.php was indicated; $bookings_manager is an instance of \MoBooking\Classes\Bookings

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Instantiate necessary classes
// Classes are now explicitly required above.
// If not, require_once statements would be needed here for:
// - MoBooking\Classes\Discounts
// - MoBooking\Classes\Notifications
// - MoBooking\Classes\Services
// - MoBooking\Classes\Bookings
// - MoBooking\Classes\Utils (if currency formatting is used server-side)
// - MoBooking\Classes\Database (if not already loaded for get_table_name)

$current_user_id = get_current_user_id();
$kpi_data = ['bookings_month' => 0, 'revenue_month' => 0, 'upcoming_count' => 0]; // Default KPIs

$currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol('USD'); // Default
if ($current_user_id && isset($GLOBALS['mobooking_settings_manager'])) {
    $currency_code_setting = $GLOBALS['mobooking_settings_manager']->get_setting($current_user_id, 'biz_currency_code', 'USD');
    $currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($currency_code_setting);
}


$bookings_data = null;
$initial_bookings_html = '';
$initial_pagination_html = '';

// Instantiate managers earlier to use for KPIs as well
$services_manager = new \MoBooking\Classes\Services();
$discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
$notifications_manager = new \MoBooking\Classes\Notifications();
$bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

// Basic router for single booking view
if (isset($_GET['action']) && $_GET['action'] === 'view_booking' && isset($_GET['booking_id'])) {
    $single_booking_id = intval($_GET['booking_id']);
    // Pass necessary variables to the single booking page context
    // $bookings_manager, $currency_symbol, $current_user_id are already available

    // Attempt to include the single booking page template
    // The actual file will be created in a later step.
    $single_page_path = __DIR__ . '/page-booking-single.php';
    if (file_exists($single_page_path)) {
        include $single_page_path;
        return; // Stop further processing of the list page
    } else {
        // Handle case where file doesn't exist yet, maybe show an error or fall through to list
        // For now, we'll fall through, but ideally, this would be robust.
         echo '<div class="notice notice-error"><p>Single booking page template not found.</p></div>';
    }
}


if ($current_user_id) {
    // Fetch KPI data
    // Determine the user ID for fetching data (owner if current user is worker)
    $data_fetch_user_id = $current_user_id;
    $is_worker_viewing = false;
    if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
        $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
        if ($owner_id) {
            $data_fetch_user_id = $owner_id;
            $is_worker_viewing = true;
        }
    }
    $kpi_data = $bookings_manager->get_kpi_data($data_fetch_user_id);
    if ($is_worker_viewing) { // Workers should not see revenue
        $kpi_data['revenue_month'] = null;
    }


    $default_args = [
        'limit' => 20, // Same as in Bookings::get_bookings_by_tenant default
        'paged' => 1,
        'orderby' => 'booking_date',
        'order' => 'DESC',
    ];
    // get_bookings_by_tenant now correctly handles if $current_user_id is a worker
    $bookings_result = $bookings_manager->get_bookings_by_tenant($current_user_id, $default_args);

    // Prepare table structure for initial bookings
    if (!empty($bookings_result['bookings'])) {
        $initial_bookings_html .= '<div class="mobooking-table-responsive-wrapper">';
        $initial_bookings_html .= '<table class="mobooking-table wp-list-table widefat fixed striped">';
        $initial_bookings_html .= '<thead><tr>';
        $initial_bookings_html .= '<th>' . esc_html__('Ref', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Customer', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Booked Date', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Total', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Status', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Actions', 'mobooking') . '</th>';
        $initial_bookings_html .= '</tr></thead>';
        $initial_bookings_html .= '<tbody>';

        foreach ($bookings_result['bookings'] as $booking) {
            $status_display = !empty($booking['status']) ? ucfirst(str_replace('-', ' ', $booking['status'])) : __('N/A', 'mobooking');
            $total_price_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2));
            $booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));
            $booking_time_formatted = date_i18n(get_option('time_format'), strtotime($booking['booking_time'])); // Assuming booking_time is just time

            $details_page_url = admin_url('admin.php?page=mobooking&action=view_booking&booking_id=' . $booking['booking_id']);

            $initial_bookings_html .= '<tr data-booking-id="' . esc_attr($booking['booking_id']) . '">';
            $initial_bookings_html .= '<td data-colname="' . esc_attr__('Ref', 'mobooking') . '">' . esc_html($booking['booking_reference']) . '</td>';
            $initial_bookings_html .= '<td data-colname="' . esc_attr__('Customer', 'mobooking') . '">' . esc_html($booking['customer_name']) . '<br><small>' . esc_html($booking['customer_email']) . '</small></td>';
            $initial_bookings_html .= '<td data-colname="' . esc_attr__('Booked Date', 'mobooking') . '">' . esc_html($booking_date_formatted . ' ' . $booking_time_formatted) . '</td>';
            $initial_bookings_html .= '<td data-colname="' . esc_attr__('Total', 'mobooking') . '">' . $total_price_formatted . '</td>';
            $initial_bookings_html .= '<td data-colname="' . esc_attr__('Status', 'mobooking') . '"><span class="mobooking-status-badge mobooking-status-' . esc_attr($booking['status']) . '">' . esc_html($status_display) . '</span></td>';
            $initial_bookings_html .= '<td data-colname="' . esc_attr__('Actions', 'mobooking') . '" class="mobooking-table-actions">';
            $initial_bookings_html .= '<a href="' . esc_url($details_page_url) . '" class="button button-small">' . __('View Details', 'mobooking') . '</a> ';
            $initial_bookings_html .= '<button class="button button-small mobooking-delete-booking-btn" data-booking-id="' . esc_attr($booking['booking_id']) . '">' . __('Delete', 'mobooking') . '</button>';
            $initial_bookings_html .= '</td></tr>';
        }
        $initial_bookings_html .= '</tbody></table>';
        $initial_bookings_html .= '</div>'; // end mobooking-table-responsive-wrapper
    } else {
        $initial_bookings_html = '<p>' . __('No bookings found.', 'mobooking') . '</p>'; // Keep this if no bookings
    }

    // Basic pagination (JS will handle more complex pagination) - plan to restyle this too
    if (isset($bookings_result['total_count']) && isset($bookings_result['per_page']) && $bookings_result['total_count'] > 0) { // Ensure keys exist before calculation
        $total_pages = ceil($bookings_result['total_count'] / $bookings_result['per_page']);
        if ($total_pages > 1) {
            $initial_pagination_html .= '<div class="pagination-links">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = (isset($bookings_result['current_page']) && $i == $bookings_result['current_page']) ? 'current' : '';
                $initial_pagination_html .= '<a href="#" class="page-numbers ' . $active_class . '" data-page="' . $i . '">' . $i . '</a> ';
            }
            $initial_pagination_html .= '</div>';
        }
    }
    // Removed the redundant 'else' that was causing the parse error.
    // The main 'else' for 'if ($current_user_id)' is below and handles the case where user is not identified.

} else { // This 'else' corresponds to 'if ($current_user_id)'
    $initial_bookings_html = '<p>' . __('Could not load bookings. User not identified.', 'mobooking') . '</p>';
    // KPIs would also not be loaded, $kpi_data would remain default.
    // $initial_pagination_html would remain empty.
}


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
<?php
// NOTE: The <style> block for the modal has been removed from here.
// These styles will be moved to a dedicated CSS file as part of the CSS implementation step.
?>

<div class="mobooking-bookings-page-wrapper"> <?php // Main page wrapper ?>

    <div class="mobooking-page-header">
        <h1><?php esc_html_e('Manage Bookings', 'mobooking'); ?></h1>
        <button id="mobooking-add-booking-btn" class="button button-primary">
            <?php esc_html_e('Add New Booking', 'mobooking'); ?>
        </button>
    </div>

    <?php // KPI Section ?>
    <div class="mobooking-kpi-grid">
        <div class="mobooking-kpi-card">
            <h4><?php esc_html_e('Bookings This Month', 'mobooking'); ?></h4>
            <p><?php echo esc_html($kpi_data['bookings_month']); ?></p>
        </div>
        <?php if ($kpi_data['revenue_month'] !== null) : // Only show revenue if not a worker or if worker is allowed (currently not) ?>
        <div class="mobooking-kpi-card">
            <h4><?php esc_html_e('Revenue This Month', 'mobooking'); ?></h4>
            <p><?php echo esc_html($currency_symbol . number_format_i18n(floatval($kpi_data['revenue_month']), 2)); ?></p>
        </div>
        <?php endif; ?>
        <div class="mobooking-kpi-card">
            <h4><?php esc_html_e('Upcoming Confirmed Bookings', 'mobooking'); ?></h4>
            <p><?php echo esc_html($kpi_data['upcoming_count']); ?></p>
        </div>
    </div>

    <div id="mobooking-bookings-filters" class="mobooking-filters-bar mobooking-card"> <?php // Styled as a card ?>
        <form id="mobooking-bookings-filter-form" class="mobooking-filters-form">
            <div class="mobooking-filter-item">
                <label for="mobooking-status-filter"><?php esc_html_e('Status:', 'mobooking'); ?></label>
                <select id="mobooking-status-filter" name="status_filter">
                    <?php foreach ($booking_statuses as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mobooking-filter-item">
                <label for="mobooking-date-from-filter"><?php esc_html_e('From:', 'mobooking'); ?></label>
                <input type="text" id="mobooking-date-from-filter" name="date_from_filter" class="mobooking-datepicker" placeholder="YYYY-MM-DD">
            </div>

            <div class="mobooking-filter-item">
                <label for="mobooking-date-to-filter"><?php esc_html_e('To:', 'mobooking'); ?></label>
                <input type="text" id="mobooking-date-to-filter" name="date_to_filter" class="mobooking-datepicker" placeholder="YYYY-MM-DD">
            </div>

            <div class="mobooking-filter-item">
                <label for="mobooking-search-query"><?php esc_html_e('Search:', 'mobooking'); ?></label>
                <input type="text" id="mobooking-search-query" name="search_query" placeholder="<?php esc_attr_e('Ref, Name, Email', 'mobooking'); ?>">
            </div>

            <div class="mobooking-filter-actions">
                <button type="submit" class="button button-secondary"><?php esc_html_e('Filter', 'mobooking'); ?></button>
                <button type="button" id="mobooking-clear-filters-btn" class="button"><?php esc_html_e('Clear', 'mobooking'); ?></button>
            </div>
        </form>
    </div>

    <div id="mobooking-bookings-list-container"> <?php // Container for the table or "no bookings" message ?>
        <?php echo $initial_bookings_html; // WPCS: XSS ok. Escaped above. ?>
    </div>

    <div id="mobooking-bookings-pagination-container" class="mobooking-pagination">
        <?php echo $initial_pagination_html; // WPCS: XSS ok. Escaped above. ?>
    </div>

<script type="text/template" id="mobooking-booking-item-template">
    <tr data-booking-id="<%= booking_id %>">
        <td data-colname="<?php esc_attr_e('Ref', 'mobooking'); ?>"><%= booking_reference %></td>
        <td data-colname="<?php esc_attr_e('Customer', 'mobooking'); ?>"><%= customer_name %><br><small><%= customer_email %></small></td>
        <td data-colname="<?php esc_attr_e('Booked Date', 'mobooking'); ?>"><%= booking_date_formatted %> <%= booking_time_formatted %></td>
        <td data-colname="<?php esc_attr_e('Total', 'mobooking'); ?>"><%= total_price_formatted %></td>
        <td data-colname="<?php esc_attr_e('Status', 'mobooking'); ?>"><span class="mobooking-status-badge mobooking-status-<%= status %>"><%= status_display %></span></td>
        <td data-colname="<?php esc_attr_e('Actions', 'mobooking'); ?>" class="mobooking-table-actions">
            <a href="<%= details_page_url %>" class="button button-small"><?php esc_html_e('View Details', 'mobooking'); ?></a>
            <button class="button button-small mobooking-delete-booking-btn" data-booking-id="<%= booking_id %>"><?php esc_html_e('Delete', 'mobooking'); ?></button>
        </td>
    </tr>
</script>

<?php // The Modal HTML structure is now removed as per the new plan. ?>

        <?php
        // Temporary Styles block has been removed.
        // All styles are now expected to be loaded from 'assets/css/dashboard-bookings-responsive.css'
        // which should be enqueued by the plugin's main asset handling logic.
        ?>

        <?php // The modal sections are also removed as the modal itself is gone. ?>
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
