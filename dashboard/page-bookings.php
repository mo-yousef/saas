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

    <?php // KPI Section - Using WordPress dashboard widget structure for styling consistency ?>
    <div id="dashboard-widgets-wrap">
        <div id="dashboard_primary" class="metabox-holder">
            <div class="postbox-container mobooking-kpi-grid"> <?php // Added .postbox-container to group KPI cards ?>
                <div class="mobooking-kpi-card postbox">
                    <h2 class="hndle"><span><?php esc_html_e('Bookings This Month', 'mobooking'); ?></span></h2>
                    <div class="inside">
                        <p class="mobooking-kpi-value"><?php echo esc_html($kpi_data['bookings_month']); ?></p>
                    </div>
                </div>

                <?php if ($kpi_data['revenue_month'] !== null) : ?>
                <div class="mobooking-kpi-card postbox">
                    <h2 class="hndle"><span><?php esc_html_e('Revenue This Month', 'mobooking'); ?></span></h2>
                    <div class="inside">
                        <p class="mobooking-kpi-value"><?php echo esc_html($currency_symbol . number_format_i18n(floatval($kpi_data['revenue_month']), 2)); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="mobooking-kpi-card postbox">
                    <h2 class="hndle"><span><?php esc_html_e('Upcoming Confirmed Bookings', 'mobooking'); ?></span></h2>
                    <div class="inside">
                        <p class="mobooking-kpi-value"><?php echo esc_html($kpi_data['upcoming_count']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php // Filters Bar - Using .postbox for card-like appearance ?>
    <div class="postbox mobooking-filters-wrapper" style="margin-top: 20px;">
        <h2 class="hndle mobooking-filters-handle"><span><?php esc_html_e('Filter Bookings', 'mobooking'); ?></span></h2>
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

<style type="text/css">
/* Basic Page Structure & Header */
.mobooking-dashboard-wrap.mobooking-bookings-page-wrapper {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    font-size: 14px;
    line-height: 1.6;
    color: #2c3338; /* WP default text color */
}

.mobooking-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ccd0d4; /* WP default border */
}

.mobooking-page-header .wp-heading-inline {
    margin-bottom: 0; /* Override WP default if any */
}

/* Standardize button styles if needed beyond WP defaults - mostly rely on WP admin styles */
.mobooking-bookings-page-wrapper .page-title-action {
    /* Ensure it aligns well with h1 if WP defaults aren't perfect */
    /* line-height: normal; */ /* Usually not needed as WP handles this */
    vertical-align: middle; /* Good for consistency */
}
.mobooking-bookings-page-wrapper .button {
    vertical-align: middle; /* Ensure all buttons align similarly */
}

/* KPI Cards */
.mobooking-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.mobooking-kpi-card.postbox {
    background-color: #fff;
    /* .postbox already has border and some shadow from WP, can override if needed */
    /* border: 1px solid #e2e8f0; */ /* Shadcn-like border */
    /* box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); */ /* Subtle shadow */
    margin-bottom: 0; /* Remove default postbox margin if it's in a grid */
}

.mobooking-kpi-card .hndle { /* WP uses .hndle for postbox title bar */
    font-size: 1em; /* WP default is 14px for h2.hndle */
    padding: 10px 15px; /* Adjust padding */
    margin: 0;
    border-bottom: 1px solid #ccd0d4; /* WP default */
    /* background-color: #f9f9f9; */ /* Lighter header background */
}

.mobooking-kpi-card .inside {
    padding: 15px;
}

.mobooking-kpi-card .mobooking-kpi-value {
    font-size: 2em; /* Larger font for the value */
    font-weight: 600;
    color: #1d2327; /* Darker for emphasis */
    margin: 0;
    line-height: 1.2;
}

/* Filter Bar */
.mobooking-filters-wrapper.postbox {
    background-color: #fff;
    margin-bottom: 25px; /* Space below filters */
}

.mobooking-filters-wrapper .hndle {
    font-size: 1em;
    padding: 10px 15px;
    margin: 0;
    border-bottom: 1px solid #ccd0d4;
}

.mobooking-filters-wrapper .inside {
    padding: 15px;
}

.mobooking-filters-form .mobooking-filter-row {
    display: flex;
    flex-wrap: wrap; /* Allow items to wrap on smaller screens */
    gap: 15px; /* Spacing between items in a row */
    margin-bottom: 15px;
}
.mobooking-filters-form .mobooking-filter-row:last-child {
    margin-bottom: 0;
}

.mobooking-filter-item {
    display: flex;
    flex-direction: column; /* Stack label on top of input */
    flex-grow: 1; /* Allow items to grow */
    min-width: 180px; /* Minimum width for filter items */
}
.mobooking-filter-item.mobooking-filter-item-search {
    flex-basis: 100%; /* Allow search to take full width if on its own row */
}


.mobooking-filter-item label {
    margin-bottom: 5px;
    font-weight: 500;
    font-size: 0.9em;
    color: #3c434a;
}

.mobooking-filter-item .regular-text,
.mobooking-filter-item .mobooking-datepicker,
.mobooking-filter-item .mobooking-filter-select {
    padding: 6px 8px; /* Consistent padding */
    border: 1px solid #8c8f94; /* WP default input border */
    border-radius: 3px;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.07);
    width: 100%; /* Make inputs take full width of their flex item */
    box-sizing: border-box;
}
.mobooking-filter-item .mobooking-filter-select {
    height: auto; /* Ensure select height matches inputs */
    line-height: normal;
}


.mobooking-filter-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

/* Bookings Table and Responsive Wrapper */
.mobooking-list-table-wrapper {
    margin-bottom: 20px;
}

.mobooking-table-responsive-wrapper {
    overflow-x: auto; /* Enable horizontal scroll for the table on small screens */
    background: #fff; /* White background for the table area */
    border: 1px solid #ccd0d4; /* WP default border */
    border-radius: 3px;
    margin-bottom: 10px; /* Space before pagination or other elements */
}

.mobooking-table.wp-list-table {
    /* Ensure it takes full width of its scrollable container, not viewport */
    width: 100%;
    min-width: 768px; /* Minimum width before scrollbar appears, adjust as needed */
    border: none; /* Remove individual table border if wrapper has one */
}

.mobooking-table.wp-list-table th,
.mobooking-table.wp-list-table td {
    padding: 10px 12px; /* Consistent padding */
    vertical-align: middle;
}

.mobooking-table.wp-list-table th {
    background-color: #f5f5f5; /* Lighter header for table */
    font-weight: 500;
}

.mobooking-table.wp-list-table tbody tr:nth-child(odd) {
    /* background-color: #f9f9f9; */ /* Already handled by .striped in WP */
}
.mobooking-table.wp-list-table tbody tr:hover {
    background-color: #f0f0f1; /* WP default hover color */
}

.mobooking-table-actions .button {
    margin-right: 5px;
    margin-bottom: 5px; /* For small screens where buttons might wrap */
}
.mobooking-table-actions .button:last-child {
    margin-right: 0;
}

/* If using data-colname for mobile view (more complex, not implemented in this pass) */
@media screen and (max-width: 768px) {
    /*
    .mobooking-table.wp-list-table thead { display: none; }
    .mobooking-table.wp-list-table tr { display: block; margin-bottom: 10px; border: 1px solid #e5e5e5; }
    .mobooking-table.wp-list-table td { display: block; text-align: right; padding-left: 50%; position: relative; }
    .mobooking-table.wp-list-table td:before {
        content: attr(data-colname);
        position: absolute;
        left: 10px;
        font-weight: bold;
        text-align: left;
    }
    */
}

/* Status Badges */
.mobooking-status-badge {
    display: inline-block;
    padding: 4px 10px;
    font-size: 0.85em;
    font-weight: 600;
    line-height: 1.2;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem; /* Bootstrap-like rounded corners */
    color: #fff; /* Default text color, overridden by specific statuses */
}

.mobooking-status-badge.mobooking-status-pending,
.mobooking-status-badge.mobooking-status-on-hold {
    background-color: #ffc107; /* Amber/Yellow */
    color: #212529; /* Dark text for yellow background */
}

.mobooking-status-badge.mobooking-status-confirmed,
.mobooking-status-badge.mobooking-status-processing {
    background-color: #17a2b8; /* Info/Blue */
}

.mobooking-status-badge.mobooking-status-completed {
    background-color: #28a745; /* Green/Success */
}

.mobooking-status-badge.mobooking-status-cancelled {
    background-color: #6c757d; /* Gray/Secondary */
}

/* Fallback for any other status not explicitly defined */
.mobooking-status-badge:not([class*="mobooking-status-pending"]):not([class*="mobooking-status-confirmed"]):not([class*="mobooking-status-completed"]):not([class*="mobooking-status-cancelled"]):not([class*="mobooking-status-on-hold"]):not([class*="mobooking-status-processing"]) {
    background-color: #6c757d; /* Default to gray */
    color: #fff;
}

/* Pagination */
.tablenav.bottom .tablenav-pages {
    /* WP default styling is usually okay, but we can ensure alignment and spacing */
    padding: 10px 0; /* Add some vertical padding if needed */
}

.tablenav-pages .pagination-links {
    display: flex; /* Use flex for alignment */
    align-items: center;
    gap: 5px; /* Spacing between page numbers/links */
}

.tablenav-pages .pagination-links .page-numbers {
    padding: 6px 12px;
    text-decoration: none;
    border: 1px solid #ccd0d4; /* WP default border */
    border-radius: 3px;
    background-color: #f5f5f5;
    color: #0073aa; /* WP default link color */
    transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}

.tablenav-pages .pagination-links .page-numbers:hover {
    background-color: #f0f0f0;
    border-color: #0073aa;
}

.tablenav-pages .pagination-links .page-numbers.current {
    background-color: #0073aa; /* WP primary blue */
    border-color: #0073aa;
    color: #fff;
    font-weight: 600;
    cursor: default;
}

/* For JS-driven pagination, if the structure is ul > li > a */
.mobooking-pagination ul.mobooking-pagination { /* Target the ul generated by JS */
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0; /* Add some top margin if not using .tablenav */
    display: flex;
    justify-content: flex-start; /* Or center/flex-end */
    gap: 5px;
}
.mobooking-pagination ul.mobooking-pagination li a {
    padding: 6px 12px;
    text-decoration: none;
    border: 1px solid #ccd0d4;
    border-radius: 3px;
    background-color: #f5f5f5;
    color: #0073aa;
    transition: background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
}
.mobooking-pagination ul.mobooking-pagination li a:hover {
    background-color: #f0f0f0;
    border-color: #0073aa;
}
.mobooking-pagination ul.mobooking-pagination li.active a {
    background-color: #0073aa;
    border-color: #0073aa;
    color: #fff;
    font-weight: 600;
    cursor: default;
}


</style>

<?php // Modal HTML structure and old style blocks were confirmed removed previously. ?>
</div> <?php // This closes .mobooking-bookings-page-wrapper ?>
