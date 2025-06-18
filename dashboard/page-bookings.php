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
$bookings_data = null;
$initial_bookings_html = '';
$initial_pagination_html = '';

if ($current_user_id) {
    // These might typically be retrieved from a central plugin container or service locator
    $services_manager = new \MoBooking\Classes\Services();
    $discounts_manager = new \MoBooking\Classes\Discounts($current_user_id); // Assuming constructor needs user_id or it's handled internally
    $notifications_manager = new \MoBooking\Classes\Notifications();
    $bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

    $default_args = [
        'limit' => 20, // Same as in Bookings::get_bookings_by_tenant default
        'paged' => 1,
        'orderby' => 'booking_date',
        'order' => 'DESC',
    ];
    $bookings_result = $bookings_manager->get_bookings_by_tenant($current_user_id, $default_args);

    if (!empty($bookings_result['bookings'])) {
        foreach ($bookings_result['bookings'] as $booking) {
            // Adapt the JS template logic here in PHP
            $status_display = !empty($booking['status']) ? ucfirst(str_replace('-', ' ', $booking['status'])) : __('N/A', 'mobooking');
            $total_price_formatted = \MoBooking\Classes\Utils::format_currency($booking['total_price']); // Assuming a utility class for currency
            $created_at_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($booking['created_at']));
            $booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));

            $initial_bookings_html .= '<div class="mobooking-booking-item" style="border:1px solid #e0e0e0; padding:15px; margin-bottom:10px; background:#fff; border-radius:3px;">';
            $initial_bookings_html .= '<h3 style="margin-top:0; margin-bottom:10px; font-size:1.1em;">Booking Ref: ' . esc_html($booking['booking_reference']) . '</h3>';
            $initial_bookings_html .= '<p><strong>' . __('Customer:', 'mobooking') . '</strong> ' . esc_html($booking['customer_name']) . ' (' . esc_html($booking['customer_email']) . ')</p>';
            $initial_bookings_html .= '<p><strong>' . __('Booked Date:', 'mobooking') . '</strong> ' . esc_html($booking_date_formatted) . ' at ' . esc_html($booking['booking_time']) . '</p>';
            $initial_bookings_html .= '<p><strong>' . __('Total Price:', 'mobooking') . '</strong> ' . esc_html($total_price_formatted) . '</p>';
            $initial_bookings_html .= '<p><strong>' . __('Status:', 'mobooking') . '</strong> <span class="booking-status booking-status-' . esc_attr($booking['status']) . '" style="padding: 3px 6px; border-radius: 3px; background-color: #eee; font-weight:bold;">' . esc_html($status_display) . '</span></p>';
            $initial_bookings_html .= '<p style="font-size:0.9em; color:#777;"><strong>' . __('Created:', 'mobooking') . '</strong> ' . esc_html($created_at_formatted) . '</p>';
            $initial_bookings_html .= '<div class="booking-actions" style="margin-top:10px;">';
            $initial_bookings_html .= '<button class="button mobooking-view-booking-details-btn" data-booking-id="' . esc_attr($booking['booking_id']) . '">' . __('View Details', 'mobooking') . '</button>';
            $initial_bookings_html .= '<button class="button mobooking-delete-booking-btn" data-booking-id="' . esc_attr($booking['booking_id']) . '" style="margin-left: 5px; color: #a00; border-color: #a00;">' . __('Delete', 'mobooking') . '</button>';
            $initial_bookings_html .= '</div></div>';
        }

        // Basic pagination (JS will handle more complex pagination)
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
<style>
    .mobooking-modal { display: none; position: fixed; z-index: 1050; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); }
    .mobooking-modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 700px; position: relative; border-radius: 5px; box-shadow: 0 5px 15px rgba(0,0,0,.5); }
    .mobooking-modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; line-height: 1; }
    .mobooking-modal-close:hover, .mobooking-modal-close:focus { color: black; text-decoration: none; cursor: pointer; }
    .mobooking-modal .modal-section { margin-bottom: 20px; padding-bottom:15px; border-bottom: 1px solid #eee; }
    .mobooking-modal .modal-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0;}
    .mobooking-modal h2 { margin-top: 0; font-size: 24px; }
    .mobooking-modal h4 { font-size: 16px; margin-bottom: 8px; color: #333; }
    #modal-services-items-list ul { padding-left: 20px; margin-top: 5px; }
    #modal-services-items-list li { margin-bottom: 5px; font-size: 0.95em; }
    #modal-services-items-list .option-list { padding-left: 15px; font-size: 0.9em; color: #555; }
    #modal-booking-status-select { margin-right: 10px; }
    #modal-status-feedback { font-style: italic; }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
    <h1><?php esc_html_e('Manage Bookings', 'mobooking'); ?></h1>
    <button id="mobooking-add-booking-btn" class="button button-primary">
        <?php esc_html_e('Add New Booking', 'mobooking'); ?>
    </button>
</div>

<div id="mobooking-bookings-filters" class="mobooking-filters-bar" style="margin-bottom: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 4px;">
    <form id="mobooking-bookings-filter-form" class="form-inline">
        <label for="mobooking-status-filter" style="margin-right:5px;"><?php esc_html_e('Status:', 'mobooking'); ?></label>
        <select id="mobooking-status-filter" name="status_filter" style="margin-right: 15px;">
            <?php foreach ($booking_statuses as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="mobooking-date-from-filter" style="margin-right:5px;"><?php esc_html_e('From:', 'mobooking'); ?></label>
        <input type="text" id="mobooking-date-from-filter" name="date_from_filter" class="mobooking-datepicker" placeholder="YYYY-MM-DD" style="margin-right: 15px; width: 120px;">

        <label for="mobooking-date-to-filter" style="margin-right:5px;"><?php esc_html_e('To:', 'mobooking'); ?></label>
        <input type="text" id="mobooking-date-to-filter" name="date_to_filter" class="mobooking-datepicker" placeholder="YYYY-MM-DD" style="margin-right: 15px; width: 120px;">

        <label for="mobooking-search-query" style="margin-right:5px;"><?php esc_html_e('Search:', 'mobooking'); ?></label>
        <input type="text" id="mobooking-search-query" name="search_query" placeholder="<?php esc_attr_e('Ref, Name, Email', 'mobooking'); ?>" style="margin-right: 15px;">

        <button type="submit" class="button button-secondary"><?php esc_html_e('Filter', 'mobooking'); ?></button>
        <button type="button" id="mobooking-clear-filters-btn" class="button" style="margin-left:5px;"><?php esc_html_e('Clear', 'mobooking'); ?></button>
    </form>
</div>

<div id="mobooking-bookings-list-container">
    <?php echo $initial_bookings_html; // WPCS: XSS ok. Escaped above. ?>
</div>

<div id="mobooking-bookings-pagination-container" style="margin-top: 20px; text-align: center;">
    <?php echo $initial_pagination_html; // WPCS: XSS ok. Escaped above. ?>
</div>

<script type="text/template" id="mobooking-booking-item-template">
    <div class="mobooking-booking-item" style="border:1px solid #e0e0e0; padding:15px; margin-bottom:10px; background:#fff; border-radius:3px;">
        <h3 style="margin-top:0; margin-bottom:10px; font-size:1.1em;">Booking Ref: <%= booking_reference %></h3>
        <p><strong><?php esc_html_e('Customer:', 'mobooking'); ?></strong> <%= customer_name %> (<%= customer_email %>)</p>
        <p><strong><?php esc_html_e('Booked Date:', 'mobooking'); ?></strong> <%= booking_date %> at <%= booking_time %></p>
        <p><strong><?php esc_html_e('Total Price:', 'mobooking'); ?></strong> <%= total_price_formatted %></p>
        <p><strong><?php esc_html_e('Status:', 'mobooking'); ?></strong> <span class="booking-status booking-status-<%= status %>" style="padding: 3px 6px; border-radius: 3px; background-color: #eee; font-weight:bold;"><%= status_display %></span></p>
        <p style="font-size:0.9em; color:#777;"><strong><?php esc_html_e('Created:', 'mobooking'); ?></strong> <%= created_at_formatted %></p>
        <div class="booking-actions" style="margin-top:10px;">
            <button class="button mobooking-view-booking-details-btn" data-booking-id="<%= booking_id %>"><?php esc_html_e('View Details', 'mobooking'); ?></button>
            <button class="button mobooking-delete-booking-btn" data-booking-id="<%= booking_id %>" style="margin-left: 5px; color: #a00; border-color: #a00;"><?php esc_html_e('Delete', 'mobooking'); ?></button>
        </div>
    </div>
</script>

<div id="mobooking-booking-details-modal" class="mobooking-modal">
    <div class="mobooking-modal-content">
        <span class="mobooking-modal-close">&times;</span>
        <h2 id="modal-booking-title"><?php esc_html_e('Booking Details', 'mobooking'); ?> - <span id="modal-booking-ref"></span></h2>

        <input type="hidden" id="modal-current-booking-id" value="">

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
