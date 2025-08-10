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
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Utils.php';
require_once __DIR__ . '/../classes/Services.php';
require_once __DIR__ . '/../classes/Discounts.php';
require_once __DIR__ . '/../classes/Notifications.php';
require_once __DIR__ . '/../classes/Bookings.php';

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Feather Icons - define a helper function or include them directly
if (!function_exists('mobooking_get_feather_icon')) { // Check if function exists to avoid re-declaration if included elsewhere
    function mobooking_get_feather_icon($icon_name, $attrs = 'width="18" height="18"') {
        $svg = '';
        switch ($icon_name) {
            case 'calendar': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'; break;
            case 'clock': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>'; break;
            case 'check-circle': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'; break;
            case 'loader': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>'; break;
            case 'pause-circle': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="10" y1="15" x2="10" y2="9"></line><line x1="14" y1="15" x2="14" y2="9"></line></svg>'; break;
            case 'check-square': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>'; break;
            case 'x-circle': $svg = '<svg xmlns="http://www.w3.org/2000/svg" '.$attrs.' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'; break;
            default: $svg = '<!-- icon not found: '.esc_attr($icon_name).' -->'; break;
        }
        return $svg;
    }
}

if (!function_exists('mobooking_get_status_badge_icon_svg')) { // Check if function exists
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


$current_user_id = get_current_user_id();
$kpi_data = ['bookings_month' => 0, 'revenue_month' => 0, 'upcoming_count' => 0];

$currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol('USD');
if ($current_user_id && isset($GLOBALS['mobooking_settings_manager'])) {
    $currency_code_setting = $GLOBALS['mobooking_settings_manager']->get_setting($current_user_id, 'biz_currency_code', 'USD');
    $currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($currency_code_setting);
}

$bookings_data = null;
$initial_bookings_html = '';
$initial_pagination_html = '';

$services_manager = new \MoBooking\Classes\Services();
$discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
$notifications_manager = new \MoBooking\Classes\Notifications();
$bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

if (isset($_GET['action']) && $_GET['action'] === 'view_booking' && isset($_GET['booking_id'])) {
    $single_booking_id = intval($_GET['booking_id']);
    $single_page_path = __DIR__ . '/page-booking-single.php';
    if (file_exists($single_page_path)) {
        include $single_page_path;
        return;
    } else {
         echo '<div class="notice notice-error"><p>Single booking page template not found.</p></div>';
    }
}

if ($current_user_id) {
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
    if ($is_worker_viewing) {
        $kpi_data['revenue_month'] = null;
    }

    $default_args = [
        'limit' => 20,
        'paged' => 1,
        'orderby' => 'booking_date',
        'order' => 'DESC',
    ];
    $bookings_result = $bookings_manager->get_bookings_by_tenant($current_user_id, $default_args);

    if (!empty($bookings_result['bookings'])) {
        $initial_bookings_html .= '<div class="mobooking-table-responsive-wrapper">';
        $initial_bookings_html .= '<table class="mobooking-table">';
        $initial_bookings_html .= '<thead><tr>';
        $initial_bookings_html .= '<th>' . esc_html__('Ref', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Customer', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Booked Date', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Assigned Staff', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Total', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Status', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Actions', 'mobooking') . '</th>';
        $initial_bookings_html .= '</tr></thead>';
        $initial_bookings_html .= '<tbody>';

        foreach ($bookings_result['bookings'] as $booking) {
            $status_val = $booking['status'];
            $status_display = !empty($status_val) ? ucfirst(str_replace('-', ' ', $status_val)) : __('N/A', 'mobooking');
            $status_icon_html = mobooking_get_status_badge_icon_svg($status_val);

            $total_price_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2));
            $booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));
            $booking_time_formatted = date_i18n(get_option('time_format'), strtotime($booking['booking_time']));
            $assigned_staff_name = isset($booking['assigned_staff_name']) ? esc_html($booking['assigned_staff_name']) : esc_html__('Unassigned', 'mobooking');

            $details_page_url = home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $booking['booking_id']);

            $initial_bookings_html .= '<tr data-booking-id="' . esc_attr($booking['booking_id']) . '">';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Ref', 'mobooking') . '">' . esc_html($booking['booking_reference']) . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Customer', 'mobooking') . '">' . esc_html($booking['customer_name']) . '<br><small>' . esc_html($booking['customer_email']) . '</small></td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Booked Date', 'mobooking') . '">' . esc_html($booking_date_formatted . ' ' . $booking_time_formatted) . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Assigned Staff', 'mobooking') . '">' . $assigned_staff_name . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Total', 'mobooking') . '">' . $total_price_formatted . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Status', 'mobooking') . '"><span class="status-badge status-' . esc_attr($status_val) . '">' . $status_icon_html . '<span class="status-text">' . esc_html($status_display) . '</span></span></td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Actions', 'mobooking') . '" class="mobooking-table-actions">';
            $initial_bookings_html .= '<a href="' . esc_url($details_page_url) . '" class="btn btn-outline btn-sm">' . __('View Details', 'mobooking') . '</a> ';
            if (class_exists('MoBooking\Classes\Auth') && !\MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                $initial_bookings_html .= '<button class="btn btn-destructive btn-sm mobooking-delete-booking-btn" data-booking-id="' . esc_attr($booking['booking_id']) . '">' . __('Delete', 'mobooking') . '</button>';
            }
            $initial_bookings_html .= '</td></tr>';
        }
        $initial_bookings_html .= '</tbody></table>';
        $initial_bookings_html .= '</div>';
    } else {
        $initial_bookings_html = '<p>' . __('No bookings found.', 'mobooking') . '</p>';
    }

    if (isset($bookings_result['total_count']) && isset($bookings_result['per_page']) && $bookings_result['total_count'] > 0) {
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

<div class="wrap mobooking-dashboard-wrap mobooking-bookings-page-wrapper">

    <div class="mobooking-page-header">
        <div class="mobooking-page-header-heading">
            <span class="mobooking-page-header-icon">
                <?php echo mobooking_get_dashboard_menu_icon('bookings'); ?>
            </span>
            <h1 class="wp-heading-inline"><?php esc_html_e('Manage Bookings', 'mobooking'); ?></h1>
        </div>
        <?php
        $current_user_can_add_booking = true;
        if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker(get_current_user_id())) {
            $current_user_can_add_booking = false;
        }
        if ($current_user_can_add_booking) :
        ?>
        <button id="mobooking-add-booking-btn" class="btn btn-primary">
            <?php esc_html_e('Add New Booking', 'mobooking'); ?>
        </button>
        <?php endif; ?>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-header">
                <div class="kpi-title"><?php esc_html_e('Bookings This Month', 'mobooking'); ?></div>
                <div class="kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
            </div>
            <div class="kpi-value"><?php echo esc_html($kpi_data['bookings_month']); ?></div>
        </div>

        <?php if ($kpi_data['revenue_month'] !== null) : ?>
        <div class="kpi-card">
            <div class="kpi-header">
                <div class="kpi-title"><?php esc_html_e('Revenue This Month', 'mobooking'); ?></div>
                <div class="kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div>
            </div>
            <div class="kpi-value"><?php echo esc_html($currency_symbol . number_format_i18n(floatval($kpi_data['revenue_month']), 2)); ?></div>
        </div>
        <?php endif; ?>

        <div class="kpi-card">
            <div class="kpi-header">
                <div class="kpi-title"><?php esc_html_e('Upcoming Confirmed Bookings', 'mobooking'); ?></div>
                <div class="kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div>
            </div>
            <div class="kpi-value"><?php echo esc_html($kpi_data['upcoming_count']); ?></div>
        </div>
    </div>

    <div class="mobooking-card mobooking-filters-wrapper">
        <div class="mobooking-card-header">
            <h3><?php esc_html_e('Filter Bookings', 'mobooking'); ?></h3>
        </div>
        <div class="mobooking-card-content">
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
                     <div class="mobooking-filter-item mobooking-filter-item-search">
                        <label for="mobooking-search-query"><?php esc_html_e('Search:', 'mobooking'); ?></label>
                        <input type="search" id="mobooking-search-query" name="search_query" class="regular-text" placeholder="<?php esc_attr_e('Ref, Name, Email', 'mobooking'); ?>">
                    </div>
                    <div class="mobooking-filter-item">
                        <label for="mobooking-staff-filter"><?php esc_html_e('Staff:', 'mobooking'); ?></label>
                        <select id="mobooking-staff-filter" name="staff_filter" class="mobooking-filter-select">
                            <option value=""><?php esc_html_e('All Staff', 'mobooking'); ?></option>
                            <option value="0"><?php esc_html_e('Unassigned', 'mobooking'); ?></option>
                            <?php
                            // Fetch workers for the current business owner
                            $owner_id_for_staff_filter = $current_user_id;
                            if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                                $owner_id_for_staff_filter = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
                            }

                            if ($owner_id_for_staff_filter) {
                                $staff_users = get_users([
                                    'meta_key'   => \MoBooking\Classes\Auth::META_KEY_OWNER_ID,
                                    'meta_value' => $owner_id_for_staff_filter,
                                    'role__in'   => [\MoBooking\Classes\Auth::ROLE_WORKER_STAFF],
                                    'orderby'    => 'display_name',
                                    'order'      => 'ASC',
                                ]);
                                foreach ($staff_users as $staff_user) {
                                    echo '<option value="' . esc_attr($staff_user->ID) . '">' . esc_html($staff_user->display_name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="mobooking-filter-actions">
                    <button type="submit" class="btn btn-secondary"><?php esc_html_e('Filter', 'mobooking'); ?></button>
                    <button type="button" id="mobooking-clear-filters-btn" class="btn btn-outline"><?php esc_html_e('Clear Filters', 'mobooking'); ?></button>
                </div>
            </form>
        </div>
    </div>

    <div id="mobooking-bookings-list-container" class="mobooking-list-table-wrapper">
        <?php echo $initial_bookings_html; // WPCS: XSS ok. Escaped above. ?>
    </div>

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
        <td data-colname="<?php esc_attr_e('Assigned Staff', 'mobooking'); ?>"><%= assigned_staff_name || '<?php echo esc_js(__('Unassigned', 'mobooking')); ?>' %></td>
        <td data-colname="<?php esc_attr_e('Total', 'mobooking'); ?>"><%= total_price_formatted %></td>
        <td data-colname="<?php esc_attr_e('Status', 'mobooking'); ?>">
            <span class="status-badge status-<%= status %>">
                <%= icon_html %> <span class="status-text"><%= status_display %></span>
            </span>
        </td>
        <td data-colname="<?php esc_attr_e('Actions', 'mobooking'); ?>" class="mobooking-table-actions">
            <a href="<%= details_page_url %>" class="btn btn-outline btn-sm"><?php esc_html_e('View Details', 'mobooking'); ?></a>
            <% if (typeof mobooking_dashboard_params !== 'undefined' && mobooking_dashboard_params.currentUserCanDeleteBookings) { %>
                <button class="btn btn-destructive btn-sm mobooking-delete-booking-btn" data-booking-id="<%= booking_id %>"><?php esc_html_e('Delete', 'mobooking'); ?></button>
            <% } %>
        </td>
    </tr>
</script>

</div>
