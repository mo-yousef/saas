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
        $initial_bookings_html .= '<div class="overflow-x-auto">';
        $initial_bookings_html .= '<table class="min-w-full divide-y divide-gray-200">';
        $initial_bookings_html .= '<thead class="bg-gray-50 hidden md:table-header-group"><tr>';
        $initial_bookings_html .= '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . esc_html__('Ref', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . esc_html__('Customer', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . esc_html__('Booked Date', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . esc_html__('Assigned Staff', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . esc_html__('Total', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . esc_html__('Status', 'mobooking') . '</th>';
        $initial_bookings_html .= '<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . esc_html__('Actions', 'mobooking') . '</th>';
        $initial_bookings_html .= '</tr></thead>';
        $initial_bookings_html .= '<tbody class="bg-white divide-y divide-gray-200">';

        foreach ($bookings_result['bookings'] as $booking) {
            $status_val = $booking['status'];
            $status_display = !empty($status_val) ? ucfirst(str_replace('-', ' ', $status_val)) : __('N/A', 'mobooking');
            $status_icon_html = mobooking_get_status_badge_icon_svg($status_val);

            $total_price_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2));
            $booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));
            $booking_time_formatted = date_i18n(get_option('time_format'), strtotime($booking['booking_time']));
            $assigned_staff_name = isset($booking['assigned_staff_name']) ? esc_html($booking['assigned_staff_name']) : esc_html__('Unassigned', 'mobooking');

            $details_page_url = home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $booking['booking_id']);

            $initial_bookings_html .= '<tr data-booking-id="' . esc_attr($booking['booking_id']) . '" class="block md:table-row border-b md:border-none">';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Ref', 'mobooking') . '" class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 block md:table-cell">' . esc_html($booking['booking_reference']) . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Customer', 'mobooking') . '" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 block md:table-cell">' . esc_html($booking['customer_name']) . '<br><small>' . esc_html($booking['customer_email']) . '</small></td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Booked Date', 'mobooking') . '" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 block md:table-cell">' . esc_html($booking_date_formatted . ' ' . $booking_time_formatted) . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Assigned Staff', 'mobooking') . '" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 block md:table-cell">' . $assigned_staff_name . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Total', 'mobooking') . '" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 block md:table-cell">' . $total_price_formatted . '</td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Status', 'mobooking') . '" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 block md:table-cell"><span class="status-badge status-' . esc_attr($status_val) . '">' . $status_icon_html . '<span class="status-text">' . esc_html($status_display) . '</span></span></td>';
            $initial_bookings_html .= '<td data-label="' . esc_attr__('Actions', 'mobooking') . '" class="px-6 py-4 whitespace-nowrap text-sm font-medium block md:table-cell">';
            $initial_bookings_html .= '<a href="' . esc_url($details_page_url) . '" class="button button-small">' . __('View Details', 'mobooking') . '</a> ';
            if (class_exists('MoBooking\Classes\Auth') && !\MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
                $initial_bookings_html .= '<button class="button button-small mobooking-delete-booking-btn" data-booking-id="' . esc_attr($booking['booking_id']) . '">' . __('Delete', 'mobooking') . '</button>';
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
<!-- ======== main-content start ======== -->
<section class="p-4 md:p-6 2xl:p-10">
    <!-- Breadcrumb Start -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-semibold text-black dark:text-white">
            Bookings
        </h2>
        <nav>
            <ol class="flex items-center gap-2">
                <li><a href="<?php echo esc_url(home_url('/dashboard/')); ?>">Dashboard /</a></li>
                <li class="text-primary">Bookings</li>
            </ol>
        </nav>
    </div>
    <!-- Breadcrumb End -->

    <!-- ====== Stats Grid Start -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6 xl:grid-cols-3 2xl:gap-7.5">
        <!-- Card Item -->
        <div class="rounded-sm border border-stroke bg-white py-6 px-7.5 shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="flex h-11.5 w-11.5 items-center justify-center rounded-full bg-meta-2 dark:bg-meta-4">
                <svg class="w-8 h-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.122-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.122-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <h4 class="text-title-md font-bold text-black dark:text-white"><?php echo esc_html($kpi_data['bookings_month']); ?></h4>
                    <span class="text-sm font-medium">Bookings This Month</span>
                </div>
            </div>
        </div>

        <?php if ($kpi_data['revenue_month'] !== null) : ?>
        <!-- Card Item -->
        <div class="rounded-sm border border-stroke bg-white py-6 px-7.5 shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="flex h-11.5 w-11.5 items-center justify-center rounded-full bg-meta-2 dark:bg-meta-4">
                <svg class="w-8 h-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01" />
                </svg>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <h4 class="text-title-md font-bold text-black dark:text-white"><?php echo esc_html($currency_symbol . number_format_i18n(floatval($kpi_data['revenue_month']), 2)); ?></h4>
                    <span class="text-sm font-medium">Revenue This Month</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Card Item -->
        <div class="rounded-sm border border-stroke bg-white py-6 px-7.5 shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="flex h-11.5 w-11.5 items-center justify-center rounded-full bg-meta-2 dark:bg-meta-4">
                 <svg class="w-8 h-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="mt-4 flex items-end justify-between">
                <div>
                    <h4 class="text-title-md font-bold text-black dark:text-white"><?php echo esc_html($kpi_data['upcoming_count']); ?></h4>
                    <span class="text-sm font-medium">Upcoming Confirmed</span>
                </div>
            </div>
        </div>
    </div>
    <!-- ====== Stats Grid End -->

    <!-- ====== Table Section Start -->
    <div class="mt-8 flex flex-col">
        <div class="rounded-sm border border-stroke bg-white px-5 pt-6 pb-2.5 shadow-default dark:border-strokedark dark:bg-boxdark sm:px-7.5 xl:pb-1">
            <div class="max-w-full overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-2 text-left dark:bg-meta-4">
                            <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white">Ref</th>
                            <th class="min-w-[220px] py-4 px-4 font-medium text-black dark:text-white">Customer</th>
                            <th class="min-w-[150px] py-4 px-4 font-medium text-black dark:text-white">Booked Date</th>
                            <th class="min-w-[150px] py-4 px-4 font-medium text-black dark:text-white">Assigned Staff</th>
                            <th class="min-w-[120px] py-4 px-4 font-medium text-black dark:text-white">Total</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Status</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bookings_result['bookings'])) : ?>
                            <?php foreach ($bookings_result['bookings'] as $booking) : ?>
                                <?php
                                $status_val = $booking['status'];
                                $status_display = !empty($status_val) ? ucfirst(str_replace('-', ' ', $status_val)) : __('N/A', 'mobooking');
                                $total_price_formatted = esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2));
                                $booking_date_formatted = date_i18n(get_option('date_format'), strtotime($booking['booking_date']));
                                $booking_time_formatted = date_i18n(get_option('time_format'), strtotime($booking['booking_time']));
                                $assigned_staff_name = isset($booking['assigned_staff_name']) ? esc_html($booking['assigned_staff_name']) : esc_html__('Unassigned', 'mobooking');
                                $details_page_url = home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $booking['booking_id']);

                                $status_classes = '';
                                switch ($status_val) {
                                    case 'confirmed': $status_classes = 'bg-success text-white'; break;
                                    case 'pending': $status_classes = 'bg-warning text-white'; break;
                                    case 'cancelled': $status_classes = 'bg-danger text-white'; break;
                                    default: $status_classes = 'bg-gray-400 text-white'; break;
                                }
                                ?>
                                <tr>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="text-black dark:text-white"><?php echo esc_html($booking['booking_reference']); ?></p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="font-medium text-black dark:text-white"><?php echo esc_html($booking['customer_name']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo esc_html($booking['customer_email']); ?></p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="text-black dark:text-white"><?php echo esc_html($booking_date_formatted . ' ' . $booking_time_formatted); ?></p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="text-black dark:text-white"><?php echo $assigned_staff_name; ?></p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="text-black dark:text-white"><?php echo $total_price_formatted; ?></p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <p class="inline-flex rounded-full bg-opacity-10 py-1 px-3 text-sm font-medium <?php echo $status_classes; ?>">
                                            <?php echo esc_html($status_display); ?>
                                        </p>
                                    </td>
                                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                        <div class="flex items-center space-x-3.5">
                                            <a href="<?php echo esc_url($details_page_url); ?>" class="hover:text-primary">
                                                <svg class="fill-current" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M8.99981 14.8219C3.43106 14.8219 0.674805 9.50624 0.562305 9.28124C0.47793 9.11249 0.47793 8.88749 0.562305 8.71874C0.674805 8.49374 3.43106 3.17812 8.99981 3.17812C14.5686 3.17812 17.3248 8.49374 17.4373 8.71874C17.5217 8.88749 17.5217 9.11249 17.4373 9.28124C17.3248 9.50624 14.5686 14.8219 8.99981 14.8219ZM1.85605 8.99999C2.4748 10.0406 4.89356 13.5 8.99981 13.5C13.1061 13.5 15.5248 10.0406 16.1436 8.99999C15.5248 7.95936 13.1061 4.5 8.99981 4.5C4.89356 4.5 2.4748 7.95936 1.85605 8.99999Z" fill=""></path>
                                                    <path d="M9 11.25C7.75736 11.25 6.75 10.2426 6.75 9C6.75 7.75736 7.75736 6.75 9 6.75C10.2426 6.75 11.25 7.75736 11.25 9C11.25 10.2426 10.2426 11.25 9 11.25ZM9 7.875C8.30659 7.875 7.875 8.30659 7.875 9C7.875 9.69341 8.30659 10.125 9 10.125C9.69341 10.125 10.125 9.69341 10.125 9C10.125 8.30659 9.69341 7.875 9 7.875Z" fill=""></path>
                                                </svg>
                                            </a>
                                            <?php if (class_exists('MoBooking\Classes\Auth') && !\MoBooking\Classes\Auth::is_user_worker($current_user_id)) : ?>
                                            <button class="hover:text-primary mobooking-delete-booking-btn" data-booking-id="<?php echo esc_attr($booking['booking_id']); ?>">
                                                <svg class="fill-current" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M13.7535 2.47502H11.5879V1.9969C11.5879 1.15315 10.9129 0.478149 10.0691 0.478149H7.93164C7.08789 0.478149 6.41289 1.15315 6.41289 1.9969V2.47502H4.24727C3.62852 2.47502 3.12227 2.98127 3.12227 3.60002V4.50002C3.12227 4.58439 3.18789 4.65002 3.27227 4.65002H14.7281C14.8125 4.65002 14.8781 4.58439 14.8781 4.50002V3.60002C14.8781 2.98127 14.3719 2.47502 13.7535 2.47502ZM7.66289 1.9969C7.66289 1.82815 7.79102 1.70002 7.93164 1.70002H10.0691C10.2098 1.70002 10.3379 1.82815 10.3379 1.9969V2.47502H7.66289V1.9969Z" fill=""></path>
                                                    <path d="M14.0348 6.0001H3.96561C3.56286 6.0001 3.23474 6.36573 3.27224 6.76848L4.09724 15.7685C4.15349 16.3688 4.641 16.8469 5.24136 16.8469H12.759C13.3594 16.8469 13.8469 16.3688 13.9031 15.7685L14.7281 6.76848C14.7656 6.36573 14.4375 6.0001 14.0348 6.0001Z" fill=""></path>
                                                </svg>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="7" class="text-center py-10">
                                    <p class="text-lg text-gray-400">No bookings found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="py-4 px-4">
                <?php echo $initial_pagination_html; ?>
            </div>
        </div>
    </div>
    <!-- ====== Table Section End -->
</section>
<!-- ======== main-content end ======== -->
