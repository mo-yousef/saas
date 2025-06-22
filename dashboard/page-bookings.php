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

    if (!empty($bookings_result['bookings'])) {
        foreach ($bookings_result['bookings'] as $booking) {
            $status_display = !empty($booking['status']) ? ucfirst(str_replace('-', ' ', $booking['status'])) : __('N/A', 'mobooking');
            $total_price_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2));
            $created_at_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['created_at']));
            $booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));

            // Note: Inline styles will be removed/replaced by classes in the CSS step
            $initial_bookings_html .= '<div class="mobooking-booking-item mobooking-card">';
            $initial_bookings_html .= '<div class="mobooking-card-header">';
            $initial_bookings_html .= '<h3>' . __('Booking Ref:', 'mobooking') . ' ' . esc_html($booking['booking_reference']) . '</h3>';
            $initial_bookings_html .= '<span class="mobooking-status-badge mobooking-status-' . esc_attr($booking['status']) . '">' . esc_html($status_display) . '</span>';
            $initial_bookings_html .= '</div>'; // end card-header
            $initial_bookings_html .= '<div class="mobooking-card-content">';
            $initial_bookings_html .= '<p><strong>' . __('Customer:', 'mobooking') . '</strong> ' . esc_html($booking['customer_name']) . ' (' . esc_html($booking['customer_email']) . ')</p>';
            $initial_bookings_html .= '<p><strong>' . __('Booked Date:', 'mobooking') . '</strong> ' . esc_html($booking_date_formatted) . ' at ' . esc_html($booking['booking_time']) . '</p>';
            $initial_bookings_html .= '<p><strong>' . __('Total Price:', 'mobooking') . '</strong> ' . $total_price_formatted . '</p>';
            $initial_bookings_html .= '<p class="mobooking-created-date"><strong>' . __('Created:', 'mobooking') . '</strong> ' . esc_html($created_at_formatted) . '</p>';
            $initial_bookings_html .= '</div>'; // end card-content
            $initial_bookings_html .= '<div class="mobooking-card-actions">';
            $initial_bookings_html .= '<button class="button mobooking-view-booking-details-btn" data-booking-id="' . esc_attr($booking['booking_id']) . '">' . __('View Details', 'mobooking') . '</button>';
            $initial_bookings_html .= '<button class="button mobooking-delete-booking-btn" data-booking-id="' . esc_attr($booking['booking_id']) . '">' . __('Delete', 'mobooking') . '</button>';
            $initial_bookings_html .= '</div>'; // end card-actions
            $initial_bookings_html .= '</div>'; // end booking-item
        }

        // Basic pagination (JS will handle more complex pagination) - plan to restyle this too
        $total_pages = ceil($bookings_result['total_count'] / $bookings_result['per_page']);
        if ($total_pages > 1) {
            $initial_pagination_html .= '<div class="pagination-links">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = ($i == $bookings_result['current_page']) ? 'current' : '';
                $initial_pagination_html .= '<a href="#" class="page-numbers ' . $active_class . '" data-page="' . $i . '">' . $i . '</a> ';
            }
            $initial_pagination_html .= '</div>';
        }

    } else {
        $initial_bookings_html = '<p>' . __('No bookings found.', 'mobooking') . '</p>';
    }
} else {
    $initial_bookings_html = '<p>' . __('Could not load bookings. User not identified.', 'mobooking') . '</p>';
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

    <div id="mobooking-bookings-list-container" class="mobooking-bookings-grid"> <?php // Grid for booking cards ?>
        <?php echo $initial_bookings_html; // WPCS: XSS ok. Escaped above. ?>
    </div>

    <div id="mobooking-bookings-pagination-container" class="mobooking-pagination">
        <?php echo $initial_pagination_html; // WPCS: XSS ok. Escaped above. ?>
    </div>

<script type="text/template" id="mobooking-booking-item-template">
    <div class="mobooking-booking-item mobooking-card">
        <div class="mobooking-card-header">
            <h3><?php esc_html_e('Booking Ref:', 'mobooking'); ?> <%= booking_reference %></h3>
            <span class="mobooking-status-badge mobooking-status-<%= status %>"><%= status_display %></span>
        </div>
        <div class="mobooking-card-content">
            <p><strong><?php esc_html_e('Customer:', 'mobooking'); ?></strong> <%= customer_name %> (<%= customer_email %>)</p>
            <p><strong><?php esc_html_e('Booked Date:', 'mobooking'); ?></strong> <%= booking_date %> at <%= booking_time %></p>
            <p><strong><?php esc_html_e('Total Price:', 'mobooking'); ?></strong> <%= total_price_formatted %></p>
            <p class="mobooking-created-date"><strong><?php esc_html_e('Created:', 'mobooking'); ?></strong> <%= created_at_formatted %></p>
        </div>
        <div class="mobooking-card-actions">
            <button class="button mobooking-view-booking-details-btn" data-booking-id="<%= booking_id %>"><?php esc_html_e('View Details', 'mobooking'); ?></button>
            <button class="button mobooking-delete-booking-btn" data-booking-id="<%= booking_id %>"><?php esc_html_e('Delete', 'mobooking'); ?></button>
        </div>
    </div>
</script>

<div id="mobooking-booking-details-modal" class="mobooking-modal"> <?php // Modal structure remains, styling will be external ?>
    <div class="mobooking-modal-content">
        <button class="mobooking-modal-close">&times;</button> <?php // Changed span to button for better accessibility practice ?>
        <h2 id="modal-booking-title"><?php esc_html_e('Booking Details', 'mobooking'); ?> - <span id="modal-booking-ref"></span></h2>

        <input type="hidden" id="modal-current-booking-id" value="">

        <?php // Temporary Styles - These should be moved to an enqueued CSS file: assets/css/dashboard-bookings-responsive.css ?>
<style>
/*
==========================================================================
General Page & ShadCN-like Card Styling
==========================================================================
*/

/* Apply a base font stack similar to WordPress admin for consistency, can be overridden */
body.wp-admin #wpbody-content .mobooking-bookings-page-wrapper {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    font-size: 14px; /* Consistent base font size */
    /* background-color: #f0f0f0; */ /* Light gray background for the page, typical in modern UIs - WP handles its own body bg */
    /* padding: 20px; */ /* WP handles its own body padding */
}

.mobooking-bookings-page-wrapper {
    max-width: 1600px; /* Max width for larger screens */
    margin: 0 auto; /* Center the content */
    padding: 1px 15px 15px 15px; /* Padding around the page content, 1px top to avoid margin collapse with WP header */
    /* background-color: var(--wp-admin-theme-color-fresh, #f0f2f5); */ /* Match WP admin bg or a neutral one */
}

.mobooking-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0; /* Softer border color */
    border-radius: 0.5rem; /* shadcn uses 0.5rem for cards */
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03), 0 1px 2px 0 rgba(0, 0, 0, 0.02); /* Subtle shadow */
    margin-bottom: 1.5rem;
    padding: 1.5rem;
}

.mobooking-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.mobooking-card-header h3 {
    margin: 0;
    font-size: 1.125rem; /* 18px */
    font-weight: 600;
    line-height: 1.2;
}

.mobooking-card-content p {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}
.mobooking-card-content p strong {
    font-weight: 500;
    color: #333;
}

.mobooking-card-actions {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 0.5rem; /* Space between buttons */
}
.mobooking-card-actions .button {
    margin-left: 0 !important; /* Override WP default button margin if any */
}


/*
==========================================================================
Page Header
==========================================================================
*/
.mobooking-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e2e8f0;
}
.mobooking-page-header h1 {
    font-size: 1.75rem; /* Larger page titles */
    font-weight: 600;
    margin: 0;
}

/*
==========================================================================
KPI Grid
==========================================================================
*/
.mobooking-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.mobooking-kpi-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1.25rem; /* 20px */
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.03), 0 1px 2px 0 rgba(0, 0, 0, 0.02);
}

.mobooking-kpi-card h4 {
    margin-top: 0;
    margin-bottom: 0.5rem;
    font-size: 0.875rem; /* 14px */
    font-weight: 500;
    color: #4a5568; /* Gray-600 */
}

.mobooking-kpi-card p {
    margin: 0;
    font-size: 1.875rem; /* 30px */
    font-weight: 600;
    color: #1a202c; /* Gray-900 */
}

/*
==========================================================================
Filters Bar
==========================================================================
*/
.mobooking-filters-bar.mobooking-card { /* This is a .mobooking-card now */
    /* padding already handled by .mobooking-card */
}

.mobooking-filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem; /* Spacing between filter items/actions */
    align-items: flex-end; /* Align items to bottom for better look with varied heights */
}

.mobooking-filter-item {
    display: flex;
    flex-direction: column; /* Stack label and input */
    gap: 0.25rem; /* Space between label and input */
    flex-grow: 1; /* Allow items to grow */
    min-width: 150px; /* Minimum width before wrapping */
}
.mobooking-filter-item label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #4a5568;
}
.mobooking-filter-item select,
.mobooking-filter-item input[type="text"],
.mobooking-filter-item input[type="date"] { /* Assuming datepicker might become input type date */
    padding: 0.5rem 0.75rem;
    border: 1px solid #cbd5e0; /* Gray-300 */
    border-radius: 0.375rem; /* Smaller radius for inputs */
    font-size: 0.875rem;
    width: 100%; /* Make inputs take full width of their flex item */
    box-sizing: border-box;
}
.mobooking-filter-item input[type="text"].mobooking-datepicker {
    /* Specific styles for datepicker if needed, otherwise inherits text input */
}

.mobooking-filter-actions {
    display: flex;
    gap: 0.5rem;
    padding-top: 1.1em; /* Align with inputs if labels are above for WP context */
}


/*
==========================================================================
Bookings List / Grid
==========================================================================
*/
.mobooking-bookings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Responsive cards */
    gap: 1.5rem;
}

.mobooking-booking-item.mobooking-card {
    /* General card styles already defined */
    display: flex;
    flex-direction: column; /* Stack header, content, actions */
}
.mobooking-booking-item .mobooking-card-content {
    flex-grow: 1; /* Allow content to take available space */
}

.mobooking-created-date {
    font-size: 0.8rem;
    color: #718096; /* Gray-500 */
}

/* Status Badges */
.mobooking-status-badge {
    padding: 0.25em 0.6em;
    font-size: 0.75rem; /* 12px */
    font-weight: 500;
    border-radius: 0.25rem; /* Pill like */
    text-transform: capitalize;
    line-height: 1;
    white-space: nowrap;
}
.mobooking-status-pending { background-color: #feebc8; color: #9c4221; } /* Orange-200, Orange-700 */
.mobooking-status-confirmed { background-color: #c6f6d5; color: #2f855a; } /* Green-200, Green-700 */
.mobooking-status-completed { background-color: #bee3f8; color: #2c5282; } /* Blue-200, Blue-700 */
.mobooking-status-cancelled { background-color: #fed7d7; color: #c53030; } /* Red-200, Red-700 */
.mobooking-status-on-hold { background-color: #faf089; color: #b7791f; } /* Yellow-200, Yellow-700 */
.mobooking-status-processing { background-color: #e9d8fd; color: #6b46c1; } /* Purple-200, Purple-700 */


/*
==========================================================================
Pagination
==========================================================================
*/
.mobooking-pagination {
    margin-top: 2rem;
    text-align: center;
}
.mobooking-pagination .page-numbers {
    display: inline-block;
    padding: 0.5rem 0.75rem;
    margin: 0 0.25rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    color: #2d3748; /* Gray-700 */
    text-decoration: none;
    transition: background-color 0.2s, color 0.2s;
}
.mobooking-pagination .page-numbers:hover {
    background-color: #edf2f7; /* Gray-100 */
    border-color: #cbd5e0; /* Gray-300 */
}
.mobooking-pagination .page-numbers.current {
    background-color: var(--wp-admin-theme-color, #2271b1); /* Primary blue or WP admin theme color */
    color: #ffffff;
    border-color: var(--wp-admin-theme-color, #2271b1);
    font-weight: 600;
}

/*
==========================================================================
Modal Styling (replaces inline styles)
==========================================================================
*/
.mobooking-modal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 1050; /* High z-index */
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto; /* Enable scroll if needed */
    background-color: rgba(0, 0, 0, 0.6); /* Darker overlay */
}

.mobooking-modal-content {
    background-color: #ffffff;
    margin: 5% auto; /* Centered, more top margin */
    padding: 2rem; /* More padding */
    border: none; /* Remove border, rely on shadow */
    width: 90%;
    max-width: 700px; /* Max width */
    border-radius: 0.5rem; /* Consistent with cards */
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); /* ShadCN Dialog shadow */
    position: relative;
    display: flex;
    flex-direction: column;
}

.mobooking-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    color: #a0aec0; /* Gray-500 */
    font-size: 1.5rem; /* Larger close icon */
    font-weight: normal; /* Not bold */
    line-height: 1;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.25rem;
}
.mobooking-modal-close:hover {
    color: #1a202c; /* Gray-900 */
}

.mobooking-modal .modal-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0; /* Softer separator */
}
.mobooking-modal .modal-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.mobooking-modal h2 { /* Modal Title */
    margin-top: 0;
    margin-bottom: 1.5rem;
    font-size: 1.25rem; /* 20px */
    font-weight: 600;
    color: #1a202c;
}
.mobooking-modal h4 { /* Section Titles */
    font-size: 1rem; /* 16px */
    font-weight: 600;
    color: #2d3748;
    margin-top: 0;
    margin-bottom: 0.75rem;
}

#modal-services-items-list ul {
    padding-left: 1rem; /* Less indent */
    margin-top: 0.5rem;
    list-style: none;
}
#modal-services-items-list li {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}
#modal-services-items-list .option-list {
    padding-left: 1rem;
    font-size: 0.8rem;
    color: #718096;
}

#modal-booking-status-select {
    padding: 0.5rem 0.75rem;
    border: 1px solid #cbd5e0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    margin-right: 0.5rem;
    min-width: 180px;
}
#modal-status-feedback {
    font-style: italic;
    font-size: 0.875rem;
    color: #4a5568;
}

/* Responsive adjustments */
@media (max-width: 782px) { /* WordPress admin breakpoint */
    .mobooking-kpi-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
    }
    .mobooking-kpi-card p {
        font-size: 1.5rem; /* Smaller KPI values on mobile */
    }

    .mobooking-filters-form {
        flex-direction: column;
        align-items: stretch; /* Make filter items take full width */
    }
    .mobooking-filter-item {
        min-width: 100%; /* Full width on mobile */
    }
    .mobooking-filter-actions {
        padding-top: 0.5rem;
        justify-content: flex-start;
    }

    .mobooking-bookings-grid {
        grid-template-columns: 1fr; /* Single column for booking cards */
        gap: 1rem;
    }
    .mobooking-card {
        padding: 1rem;
    }
    .mobooking-card-header h3 {
        font-size: 1rem;
    }

    .mobooking-modal-content {
        margin: 5% auto;
        width: 95%;
        padding: 1.5rem;
    }
}

@media (max-width: 480px) {
    .mobooking-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    .mobooking-page-header h1 {
        font-size: 1.5rem;
    }
    .mobooking-kpi-card {
        padding: 1rem;
    }
     .mobooking-kpi-card h4 {
        font-size: 0.8rem;
    }
    .mobooking-kpi-card p {
        font-size: 1.3rem;
    }
}

/* WordPress Button Overrides (if necessary, for consistency) */
.mobooking-bookings-page-wrapper .button {
    /* Example: Standardize button padding/height if WP defaults are inconsistent */
    /* padding: 0.5rem 1rem; */
    /* line-height: 1.5; */
    border-radius: 0.375rem; /* ShadCN often uses rounded buttons */
}
.mobooking-bookings-page-wrapper .button-primary {
    /* background-color: var(--wp-admin-theme-color, #2271b1); */
    /* border-color: var(--wp-admin-theme-color, #2271b1); */
    /* color: #fff; */
}
/* Add more specific overrides if default WP admin button styles clash too much */

/* Ensure delete button has a distinct, ShadCN-like destructive style */
.mobooking-card-actions .mobooking-delete-booking-btn {
    background-color: #fed7d7; /* Red-200 */
    border-color: #fed7d7;
    color: #c53030; /* Red-700 */
    font-weight: 500;
}
.mobooking-card-actions .mobooking-delete-booking-btn:hover,
.mobooking-card-actions .mobooking-delete-booking-btn:focus {
    background-color: #fbb6b6; /* Red-300 */
    border-color: #fbb6b6;
    color: #9b2c2c; /* Red-800 */
}

/* Ensure primary action buttons in modal are styled consistently */
#modal-save-status-btn.button-primary {
   /* Already inherits general button styles, WP might handle this well */
}

/* Helper class if needed for visually hidden labels (for accessibility) */
.mobooking-visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>
        <?php // End Temporary Styles ?>

        <div class="modal-section">
            <h4><?php esc_html_e('Update Status', 'mobooking'); ?></h4>
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
