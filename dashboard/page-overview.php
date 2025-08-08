<?php
/**
 * Dashboard Page: Overview
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Get current user and initialize managers
$user = wp_get_current_user();
$current_user_id = get_current_user_id();

// Initialize managers
$services_manager = new \MoBooking\Classes\Services();
$discounts_manager = new \MoBooking\Classes\Discounts($current_user_id);
$notifications_manager = new \MoBooking\Classes\Notifications();
$bookings_manager = new \MoBooking\Classes\Bookings($discounts_manager, $notifications_manager, $services_manager);
$settings_manager = new \MoBooking\Classes\Settings();
$customers_manager = new \MoBooking\Classes\Customers();

// Determine user for data fetching (handle workers)
$data_user_id = $current_user_id;
$is_worker = false;
if (class_exists('MoBooking\Classes\Auth') && \MoBooking\Classes\Auth::is_user_worker($current_user_id)) {
    $owner_id = \MoBooking\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
    if ($owner_id) {
        $data_user_id = $owner_id;
        $is_worker = true;
    }
}

// Fetch data for KPI Widgets
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');
$previous_month_start = date('Y-m-01', strtotime('-1 month'));
$previous_month_end = date('Y-m-t', strtotime('-1 month'));

// Get stats for current and previous month
$current_month_stats = $bookings_manager->get_booking_statistics($data_user_id, $current_month_start, $current_month_end);
$previous_month_stats = $bookings_manager->get_booking_statistics($data_user_id, $previous_month_start, $previous_month_end);
$current_month_customers = $customers_manager->get_customer_insights($data_user_id, $current_month_start, $current_month_end);
$previous_month_customers = $customers_manager->get_customer_insights($data_user_id, $previous_month_start, $previous_month_end);

// Helper function to calculate percentage change
function calculate_percentage_change($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? '100%' : '0%';
    }
    $change = (($current - $previous) / $previous) * 100;
    return sprintf('%+.0f%%', $change);
}

// Prepare data for stats widgets
$total_bookings = $current_month_stats['total'] ?? 0;
$completed_jobs = $current_month_stats['by_status']['completed'] ?? 0;
$monthly_revenue = $current_month_stats['total_revenue'] ?? 0;
$new_customers = $current_month_customers['new_customers'] ?? 0;

$prev_total_bookings = $previous_month_stats['total'] ?? 0;
$prev_completed_jobs = $previous_month_stats['by_status']['completed'] ?? 0;
$prev_monthly_revenue = $previous_month_stats['total_revenue'] ?? 0;
$prev_new_customers = $previous_month_customers['new_customers'] ?? 0;

$stats = [
    [
        'label' => 'Total Bookings',
        'value' => $total_bookings,
        'change' => calculate_percentage_change($total_bookings, $prev_total_bookings),
        'isPositive' => $total_bookings >= $prev_total_bookings,
    ],
    [
        'label' => 'Completed Jobs',
        'value' => $completed_jobs,
        'change' => calculate_percentage_change($completed_jobs, $prev_completed_jobs),
        'isPositive' => $completed_jobs >= $prev_completed_jobs,
    ],
    [
        'label' => 'Monthly Revenue',
        'value' => '$' . number_format($monthly_revenue, 2),
        'change' => calculate_percentage_change($monthly_revenue, $prev_monthly_revenue),
        'isPositive' => $monthly_revenue >= $prev_monthly_revenue,
    ],
    [
        'label' => 'New Customers',
        'value' => $new_customers,
        'change' => calculate_percentage_change($new_customers, $prev_new_customers),
        'isPositive' => $new_customers >= $prev_new_customers,
    ],
];


// Fetch data for Upcoming Bookings
$upcoming_bookings = $bookings_manager->get_upcoming_bookings($data_user_id, 7);

// Fetch data for Calendar
$month = date('m');
$year = date('Y');
$start_of_month = date('Y-m-01');
$end_of_month = date('Y-m-t');
$calendar_bookings_result = $bookings_manager->get_bookings_by_tenant($data_user_id, [
    'limit' => -1, // Get all
    'date_from' => $start_of_month,
    'date_to' => $end_of_month
]);
$calendar_bookings = [];
foreach ($calendar_bookings_result['bookings'] as $booking) {
    $day = date('j', strtotime($booking['booking_date']));
    $calendar_bookings[$day][] = $booking;
}


// Fetch data for Staff Performance
$staff_members_data = get_users([
    'meta_key' => \MoBooking\Classes\Auth::META_KEY_OWNER_ID,
    'meta_value' => $data_user_id,
]);
$staff_booking_counts = $bookings_manager->get_booking_counts_by_staff($data_user_id);

// Fetch data for Popular Services
$popular_services = $services_manager->get_popular_services($data_user_id, 4);

// Get currency symbol
$currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($settings_manager->get_setting($data_user_id, 'biz_currency_code', 'USD'));
?>

<div>
    <h3 class="text-3xl font-medium text-gray-700 dark:text-gray-200">Dashboard</h3>
    <div class="mt-4">
        <div class="flex flex-wrap -mx-6">
            <?php foreach ($stats as $stat): ?>
            <div class="w-full px-6 sm:w-1/2 xl:w-1/4">
                <div class="flex items-center px-5 py-6 bg-white rounded-md shadow-sm dark:bg-gray-800">
                    <div class="p-3 bg-indigo-600 bg-opacity-75 rounded-full">
                        <svg class="w-8 h-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.122-1.28-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.122-1.28.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="mx-5">
                        <h4 class="text-2xl font-semibold text-gray-700 dark:text-gray-200"><?php echo esc_html($stat['value']); ?></h4>
                        <div class="text-gray-500 dark:text-gray-400"><?php echo esc_html($stat['label']); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="flex flex-col mt-8">
        <div class="-my-2 py-2 overflow-x-auto sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <div class="align-middle inline-block min-w-full shadow overflow-hidden sm:rounded-lg border-b border-gray-200 dark:border-gray-700">
                <table class="min-w-full">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Service</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 border-b border-gray-200 bg-gray-50 dark:bg-gray-700 dark:border-gray-700 text-left text-xs leading-4 font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800">
                        <?php if (!empty($upcoming_bookings)): ?>
                            <?php foreach ($upcoming_bookings as $booking): ?>
                                <?php
                                $service_names = array_column($booking['items'], 'service_name');
                                $service_display = !empty($service_names) ? implode(', ', $service_names) : 'N/A';

                                $booking_datetime = strtotime($booking['booking_date'] . ' ' . $booking['booking_time']);
                                $date_display = date('M j, Y', $booking_datetime);
                                if (date('Y-m-d') == $booking['booking_date']) {
                                    $date_display = 'Today';
                                } elseif (date('Y-m-d', strtotime('+1 day')) == $booking['booking_date']) {
                                    $date_display = 'Tomorrow';
                                }
                                $time_display = date('g:i A', $booking_datetime);
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center">
                                            <div class="ml-4">
                                                <div class="text-sm leading-5 font-medium text-gray-900 dark:text-gray-200"><?php echo esc_html($booking['customer_name']); ?></div>
                                                <div class="text-sm leading-5 text-gray-500 dark:text-gray-400"><?php echo esc_html($booking['service_address']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                        <div class="text-sm leading-5 text-gray-900 dark:text-gray-200"><?php echo esc_html($service_display); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                        <div class="text-sm leading-5 text-gray-900 dark:text-gray-200"><?php echo esc_html($date_display . ', ' . $time_display); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200 dark:border-gray-700">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo esc_html(ucfirst($booking['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-gray-500 dark:text-gray-400">No upcoming bookings.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
