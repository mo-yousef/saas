<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php
/**
 * Dashboard Page: Bookings
 * @package NORDBOOKING
 */

// Ensure critical classes are loaded
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Utils.php';
require_once __DIR__ . '/../classes/Services.php';
require_once __DIR__ . '/../classes/Discounts.php';
require_once __DIR__ . '/../classes/Notifications.php';
require_once __DIR__ . '/../classes/Bookings.php';

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


$current_user_id = get_current_user_id();
$kpi_data = ['bookings_month' => 0, 'revenue_month' => 0, 'upcoming_count' => 0];

$currency_symbol = \NORDBOOKING\Classes\Utils::get_currency_symbol('USD');
if ($current_user_id && isset($GLOBALS['nordbooking_settings_manager'])) {
    $currency_code_setting = $GLOBALS['nordbooking_settings_manager']->get_setting($current_user_id, 'biz_currency_code', 'USD');
    $currency_symbol = \NORDBOOKING\Classes\Utils::get_currency_symbol($currency_code_setting);
}

$bookings_data = null;
$initial_bookings_html = '';
$initial_pagination_html = '';

$services_manager = new \NORDBOOKING\Classes\Services();
$discounts_manager = new \NORDBOOKING\Classes\Discounts($current_user_id);
$notifications_manager = new \NORDBOOKING\Classes\Notifications();
$bookings_manager = new \NORDBOOKING\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);

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
    if (class_exists('NORDBOOKING\Classes\Auth') && \NORDBOOKING\Classes\Auth::is_user_worker($current_user_id)) {
        $owner_id = \NORDBOOKING\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
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
        $initial_bookings_html .= '<div class="nordbooking-table-responsive-wrapper">';
        $initial_bookings_html .= '<table class="nordbooking-table">';
        $initial_bookings_html .= '<thead><tr>';
        $initial_bookings_html .= '<th>' . esc_html__('Ref', 'NORDBOOKING') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Customer', 'NORDBOOKING') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Booked Date', 'NORDBOOKING') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Assigned Staff', 'NORDBOOKING') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Total', 'NORDBOOKING') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Status', 'NORDBOOKING') . '</th>';
        $initial_bookings_html .= '<th>' . esc_html__('Actions', 'NORDBOOKING') . '</th>';
        $initial_bookings_html .= '</tr></thead>';
        $initial_bookings_html .= '<tbody>';

        foreach ($bookings_result['bookings'] as $booking) {
            $status_val = $booking['status'];
            $status_display = !empty($status_val) ? ucfirst(str_replace('-', ' ', $status_val)) : __('N/A', 'NORDBOOKING');
            $status_icon_html = nordbooking_get_status_badge_icon_svg($status_val);

            $total_price_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2));
            $booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));
            $booking_time_formatted = date_i18n(get_option('time_format'), strtotime($booking['booking_time']));
            $assigned_staff_name = isset($booking['assigned_staff_name']) ? esc_html($booking['assigned_staff_name']) : esc_html__('Unassigned', 'NORDBOOKING');

            $details_page_url = home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $booking['booking_id']);

            $initial_bookings_html .= '<tr data-booking-id="' . esc_attr($booking['booking_id']) . '">';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Ref', 'NORDBOOKING') . '">' . esc_html($booking['booking_reference']) . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Customer', 'NORDBOOKING') . '">' . esc_html($booking['customer_name']) . '<br><small>' . esc_html($booking['customer_email']) . '</small></td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Booked Date', 'NORDBOOKING') . '">' . esc_html($booking_date_formatted . ' ' . $booking_time_formatted) . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Assigned Staff', 'NORDBOOKING') . '">' . $assigned_staff_name . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Total', 'NORDBOOKING') . '">' . $total_price_formatted . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Status', 'NORDBOOKING') . '"><span class="status-badge status-' . esc_attr($status_val) . '">' . $status_icon_html . '<span class="status-text">' . esc_html($status_display) . '</span></span></td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Actions', 'NORDBOOKING') . '" class="nordbooking-table-actions">';
            $initial_bookings_html .= '<a href="' . esc_url($details_page_url) . '" class="btn btn-outline btn-sm" title="' . esc_attr__('View enhanced booking details', 'NORDBOOKING') . '">' . __('View Details', 'NORDBOOKING') . '</a> ';
            if (class_exists('NORDBOOKING\Classes\Auth') && !\NORDBOOKING\Classes\Auth::is_user_worker($current_user_id)) {
                $initial_bookings_html .= '<button class="btn btn-destructive btn-sm NORDBOOKING-delete-booking-btn" data-booking-id="' . esc_attr($booking['booking_id']) . '">' . __('Delete', 'NORDBOOKING') . '</button>';
            }
            $initial_bookings_html .= '</td></tr>';
        }
        $initial_bookings_html .= '</tbody></table>';
        $initial_bookings_html .= '</div>';
    } else {
        $initial_bookings_html = '<p>' . __('No bookings found.', 'NORDBOOKING') . '</p>';
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
    $initial_bookings_html = '<p>' . __('Could not load bookings. User not identified.', 'NORDBOOKING') . '</p>';
}

$booking_statuses = [
    '' => __('All Statuses', 'NORDBOOKING'),
    'pending' => __('Pending', 'NORDBOOKING'),
    'confirmed' => __('Confirmed', 'NORDBOOKING'),
    'completed' => __('Completed', 'NORDBOOKING'),
    'cancelled' => __('Cancelled', 'NORDBOOKING'),
    'on-hold' => __('On Hold', 'NORDBOOKING'),
    'processing' => __('Processing', 'NORDBOOKING'),
];
?>

<div class="wrap nordbooking-dashboard-wrap nordbooking-bookings-page-wrapper">

    <div class="nordbooking-page-header">
        <div class="nordbooking-page-header-heading">
            <span class="nordbooking-page-header-icon">
                <?php echo nordbooking_get_dashboard_menu_icon('bookings'); ?>
            </span>
            <h1 class="wp-heading-inline"><?php esc_html_e('Manage Bookings', 'NORDBOOKING'); ?></h1>
        </div>
        <?php
        $current_user_can_add_booking = true;
        if (class_exists('NORDBOOKING\Classes\Auth') && \NORDBOOKING\Classes\Auth::is_user_worker(get_current_user_id())) {
            $current_user_can_add_booking = false;
        }
        if ($current_user_can_add_booking) :
        ?>
        <button id="nordbooking-add-booking-btn" class="btn btn-primary">
            <?php esc_html_e('Add New Booking', 'NORDBOOKING'); ?>
        </button>
        <?php endif; ?>
    </div>

    <div class="kpi-grid">
        <div class="nordbooking-card">
            <div class="nordbooking-card-header">
                <div class="nordbooking-card-title-group">
                    <span class="nordbooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span>
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Bookings This Month', 'NORDBOOKING'); ?></h3>
                </div>
            </div>
            <div class="nordbooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($kpi_data['bookings_month']); ?></div>
            </div>
        </div>

        <?php if ($kpi_data['revenue_month'] !== null) : ?>
        <div class="nordbooking-card">
            <div class="nordbooking-card-header">
                <div class="nordbooking-card-title-group">
                    <span class="nordbooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></span>
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Revenue This Month', 'NORDBOOKING'); ?></h3>
                </div>
            </div>
            <div class="nordbooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($currency_symbol . number_format_i18n(floatval($kpi_data['revenue_month']), 2)); ?></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="nordbooking-card">
            <div class="nordbooking-card-header">
                <div class="nordbooking-card-title-group">
                    <span class="nordbooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></span>
                    <h3 class="nordbooking-card-title"><?php esc_html_e('Upcoming Confirmed Bookings', 'NORDBOOKING'); ?></h3>
                </div>
            </div>
            <div class="nordbooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($kpi_data['upcoming_count']); ?></div>
            </div>
        </div>
    </div>

    <style>
        .nordbooking-filters-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }        .nordbooking-filters-main {
            flex-grow: 1;
        }
        .nordbooking-filter-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .nordbooking-filter-item-search {
            flex-grow: 1;
        }
        .nordbooking-filters-secondary {
            width: 100%;
            display: none; /* Hidden by default */
        }
        .nordbooking-filters-secondary-inner {
    display: flex
;
    gap: 1.5rem;
}

div#ui-datepicker-div {
    background: #fff;
    gap: 0.75rem;
    background-color: var(--mobk-card);
    border: 1px solid var(--mobk-border);
    border-radius: var(--mobk-radius);
    padding: 16px;
}


        .nordbooking-filter-actions {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }
        .NORDBOOKING-no-results-message {
            text-align: center;
            padding: 4rem 2rem;
            border: 2px dashed #e2e8f0;
            border-radius: 0.5rem;
            margin-top: 2rem;
        }
        .NORDBOOKING-no-results-message svg {
            width: 3rem;
            height: 3rem;
            stroke-width: 1.5;
            color: #94a3b8;
            margin-inline: auto;
            margin-bottom: 1rem;
        }
        .NORDBOOKING-no-results-message h4 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }
        .NORDBOOKING-no-results-message p {
            color: #64748b;
            margin: 0;
        }
        .nordbooking-filters-secondary .nordbooking-filter-item {
            flex-basis: calc(50% - 1rem);
            flex-grow: 1;
        }
        .nordbooking-filters-secondary .NORDBOOKING-datepicker {
            background-color: #ffffff;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
    </style>
    <div class="nordbooking-card nordbooking-filters-wrapper">
        <div class="nordbooking-card-content">
            <form id="nordbooking-bookings-filter-form" class="nordbooking-filters-form">
                <div class="nordbooking-filters-main">
                    <div class="nordbooking-filter-item nordbooking-filter-item-search">
                        <label for="NORDBOOKING-search-query"><?php esc_html_e('Search', 'NORDBOOKING'); ?></label>
                        <input type="search" id="NORDBOOKING-search-query" name="search_query" class="regular-text" placeholder="<?php esc_attr_e('Ref, Name, Email', 'NORDBOOKING'); ?>">
                    </div>
                    <div class="nordbooking-filter-item">
                        <label for="NORDBOOKING-status-filter"><?php esc_html_e('Status', 'NORDBOOKING'); ?></label>
                        <select id="NORDBOOKING-status-filter" name="status_filter" class="nordbooking-filter-select">
                            <?php foreach ($booking_statuses as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="nordbooking-filter-actions">
                        <button type="submit" class="btn btn-secondary" style="display:none;"><?php echo nordbooking_get_feather_icon('filter'); ?> <?php esc_html_e('Filter', 'NORDBOOKING'); ?></button>
                        <button type="button" id="NORDBOOKING-toggle-more-filters-btn" class="btn btn-outline"><?php echo nordbooking_get_feather_icon('sliders'); ?> <span class="btn-text"><?php esc_html_e('More', 'NORDBOOKING'); ?></span></button>
                        <button type="button" id="NORDBOOKING-clear-filters-btn" class="btn btn-outline"><?php echo nordbooking_get_feather_icon('x'); ?> <span class="btn-text"><?php esc_html_e('Clear', 'NORDBOOKING'); ?></span></button>
                    </div>
                </div>
                <div class="nordbooking-filters-secondary">
                    <div class="nordbooking-filters-secondary-inner">
                        <div class="nordbooking-filter-item">
                            <label for="NORDBOOKING-date-from-filter"><?php esc_html_e('From:', 'NORDBOOKING'); ?></label>
                            <input type="text" id="NORDBOOKING-date-from-filter" name="date_from_filter" class="NORDBOOKING-datepicker regular-text" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="nordbooking-filter-item">
                            <label for="NORDBOOKING-date-to-filter"><?php esc_html_e('To:', 'NORDBOOKING'); ?></label>
                            <input type="text" id="NORDBOOKING-date-to-filter" name="date_to_filter" class="NORDBOOKING-datepicker regular-text" placeholder="YYYY-MM-DD">
                        </div>
                        <div class="nordbooking-filter-item">
                            <label for="NORDBOOKING-staff-filter"><?php esc_html_e('Staff:', 'NORDBOOKING'); ?></label>
                            <select id="NORDBOOKING-staff-filter" name="staff_filter" class="nordbooking-filter-select">
                                <option value=""><?php esc_html_e('All Staff', 'NORDBOOKING'); ?></option>
                                <option value="0"><?php esc_html_e('Unassigned', 'NORDBOOKING'); ?></option>
                                <?php
                                $owner_id_for_staff_filter = $current_user_id;
                                if (class_exists('NORDBOOKING\Classes\Auth') && \NORDBOOKING\Classes\Auth::is_user_worker($current_user_id)) {
                                    $owner_id_for_staff_filter = \NORDBOOKING\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
                                }

                                if ($owner_id_for_staff_filter) {
                                    $staff_users = get_users([
                                        'meta_key'   => \NORDBOOKING\Classes\Auth::META_KEY_OWNER_ID,
                                        'meta_value' => $owner_id_for_staff_filter,
                                        'role__in'   => [\NORDBOOKING\Classes\Auth::ROLE_WORKER_STAFF],
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
                </div>
            </form>
        </div>
    </div>

    <div id="nordbooking-bookings-list-container" class="nordbooking-list-table-wrapper">
        <?php echo $initial_bookings_html; // WPCS: XSS ok. Escaped above. ?>
    </div>

    <div id="nordbooking-bookings-pagination-container" class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="pagination-links">
                 <?php echo $initial_pagination_html; // WPCS: XSS ok. Escaped above. ?>
            </span>
        </div>
    </div>

<script type="text/template" id="nordbooking-booking-item-template">
    <tr data-booking-id="<%= booking_id %>">
        <td data-colname="<?php esc_attr_e('Ref', 'NORDBOOKING'); ?>"><%= booking_reference %></td>
        <td data-colname="<?php esc_attr_e('Customer', 'NORDBOOKING'); ?>"><%= customer_name %><br><small><%= customer_email %></small></td>
        <td data-colname="<?php esc_attr_e('Booked Date', 'NORDBOOKING'); ?>"><%= booking_date_formatted %> <%= booking_time_formatted %></td>
        <td data-colname="<?php esc_attr_e('Assigned Staff', 'NORDBOOKING'); ?>"><%= assigned_staff_name %></td>
        <td data-colname="<?php esc_attr_e('Total', 'NORDBOOKING'); ?>"><%= total_price_formatted %></td>
        <td data-colname="<?php esc_attr_e('Status', 'NORDBOOKING'); ?>">
            <span class="status-badge status-<%= status %>">
                <%= icon_html %> <span class="status-text"><%= status_display %></span>
            </span>
        </td>
        <td data-colname="<?php esc_attr_e('Actions', 'NORDBOOKING'); ?>" class="nordbooking-table-actions">
            <a href="<%= details_page_url %>" class="btn btn-outline btn-sm"><?php esc_html_e('View Details', 'NORDBOOKING'); ?></a>
            <%= delete_button_html %>
        </td>
    </tr>
</script>

</div>
