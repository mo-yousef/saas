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

<div class="wrap mobooking-dashboard-wrap mobooking-bookings-page-wrapper"> <?php // Main page wrapper with WP admin styles ?>

    <div class="mobooking-page-header">
        <h1 class="wp-heading-inline"><?php esc_html_e('Manage Bookings', 'mobooking'); ?></h1>
        <?php
        // Only show "Add New Booking" button to non-workers (i.e., Business Owners)
        $current_user_can_add_booking = true; // Default to true
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker(get_current_user_id())) {
            $current_user_can_add_booking = false;
        }
        if ($current_user_can_add_booking) :
        ?>
        <button id="mobooking-add-booking-btn" class="page-title-action">
            <?php esc_html_e('Add New Booking', 'mobooking'); ?>
        </button>
        <?php endif; ?>
    </div>

    <?php // KPI Section - Adopting modern KPI card structure from page-overview.php ?>
    <div class="dashboard-kpi-grid mobooking-overview-kpis"> <?php // Add a specific class if needed to target these KPIs if they differ slightly from overview page, or use .mobooking-overview .dashboard-kpi-grid styles directly ?>
        <div class="dashboard-kpi-card"> <?php // Use .kpi-card structure from page-overview.php ?>
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Bookings This Month', 'mobooking'); ?></span>
                <div class="kpi-icon bookings">📅</div> <?php // Example icon, adjust as needed ?>
            </div>
            <div class="kpi-value"><?php echo esc_html($kpi_data['bookings_month']); ?></div>
            <?php /* Placeholder for trend, actual trend data not available here yet
            <div class="kpi-trend positive">
                <span>↗</span> +X%
            </div>
            */ ?>
        </div>

        <?php if ($kpi_data['revenue_month'] !== null) : ?>
        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Revenue This Month', 'mobooking'); ?></span>
                <div class="kpi-icon revenue">💰</div> <?php // Example icon ?>
            </div>
            <div class="kpi-value"><?php echo esc_html($currency_symbol . number_format_i18n(floatval($kpi_data['revenue_month']), 2)); ?></div>
             <?php /* Placeholder for trend
            <div class="kpi-trend positive">
                <span>↗</span> +Y%
            </div>
            */ ?>
        </div>
        <?php endif; ?>

        <div class="dashboard-kpi-card">
            <div class="kpi-header">
                <span class="kpi-title"><?php esc_html_e('Upcoming Confirmed Bookings', 'mobooking'); ?></span>
                 <div class="kpi-icon upcoming">⏰</div> <?php // Example icon ?>
            </div>
            <div class="kpi-value"><?php echo esc_html($kpi_data['upcoming_count']); ?></div>
            <?php /* Placeholder for trend
            <div class="kpi-trend neutral">
                <span>→</span> Next 7 days
            </div>
             */ ?>
        </div>
    </div>

    <?php // Filters Bar - Using .mobooking-card for consistent card appearance ?>
    <div class="mobooking-card mobooking-filters-wrapper">
        <div class="mobooking-card-header"> <?php // Optional: Add a card header for the filter section ?>
            <h3><?php esc_html_e('Filter Bookings', 'mobooking'); ?></h3>
        </div>
        <div class="mobooking-card-content"> <?php // Wrap content in mobooking-card-content ?>
        <div class="inside">
            <form id="mobooking-bookings-filter-form" class="mobooking-filters-form">
                <div class="mobooking-filter-row">
                    <div class="mobooking-filter-item">
                        <label for="mobooking-status-filter"><?php esc_html_e('Status:', 'mobooking'); ?></label>
                        <select id="mobooking-status-filter" name="status_filter" class="mobooking-filter-select">
                            <?php foreach ($booking_statuses as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mobooking-filter-item">
                        <label for="mobooking-date-from-filter"><?php esc_html_e('From:', 'mobooking'); ?></label>
                        <input type="text" id="mobooking-date-from-filter" name="date_from_filter" class="mobooking-datepicker regular-text" placeholder="YYYY-MM-DD">
                    </div>
                    <div class="mobooking-filter-item">
                        <label for="mobooking-date-to-filter"><?php esc_html_e('To:', 'mobooking'); ?></label>
                        <input type="text" id="mobooking-date-to-filter" name="date_to_filter" class="mobooking-datepicker regular-text" placeholder="YYYY-MM-DD">
                    </div>
                </div>
                <div class="mobooking-filter-row">
                     <div class="mobooking-filter-item mobooking-filter-item-search"> <?php // Search on its own row or make it flexible ?>
                        <label for="mobooking-search-query"><?php esc_html_e('Search:', 'mobooking'); ?></label>
                        <input type="search" id="mobooking-search-query" name="search_query" class="regular-text" placeholder="<?php esc_attr_e('Ref, Name, Email', 'mobooking'); ?>">
                    </div>
                </div>
                <div class="mobooking-filter-actions">
                    <button type="submit" class="button button-secondary"><?php esc_html_e('Filter', 'mobooking'); ?></button>
                    <button type="button" id="mobooking-clear-filters-btn" class="button"><?php esc_html_e('Clear Filters', 'mobooking'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php // Bookings List Table ?>
    <div id="mobooking-bookings-list-container" class="mobooking-list-table-wrapper">
        <?php
        // Initial bookings HTML is generated by PHP and includes the table structure
        // Ensure $initial_bookings_html uses class="wp-list-table widefat fixed striped" for the table
        echo $initial_bookings_html; // WPCS: XSS ok. Escaped above.
        ?>
    </div>

    <?php // Pagination ?>
    <div id="mobooking-bookings-pagination-container" class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="pagination-links">
                 <?php echo $initial_pagination_html; // WPCS: XSS ok. Escaped above. ?>
            </span>
        </div>
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

<?php // Inline styles removed. They will be merged into assets/css/dashboard-bookings-responsive.css ?>

<?php // Modal HTML structure and old style blocks were confirmed removed previously. ?>
</div> <?php // This closes .mobooking-bookings-page-wrapper ?>
