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
$stats_data = $bookings_manager->get_booking_statistics($data_user_id);
$monthly_revenue = $bookings_manager->get_monthly_revenue($data_user_id);
$customer_insights = $customers_manager->get_customer_insights($data_user_id);

$total_bookings = $stats_data['total'] ?? 0;
$completed_jobs = $stats_data['by_status']['completed'] ?? 0;
$new_customers = $customer_insights['new_customers'] ?? 0;

$stats = [
    [
        'label' => 'Total Bookings',
        'value' => $total_bookings,
        'change' => '+12%',
        'isPositive' => true,
    ],
    [
        'label' => 'Completed Jobs',
        'value' => $completed_jobs,
        'change' => '+8%',
        'isPositive' => true,
    ],
    [
        'label' => 'Monthly Revenue',
        'value' => '$' . number_format($monthly_revenue, 2),
        'change' => '+24%',
        'isPositive' => true,
    ],
    [
        'label' => 'New Customers',
        'value' => $new_customers,
        'change' => '+18%',
        'isPositive' => true,
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

<main class="p-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard Overview</h1>
        <p class="text-gray-600">
            Welcome back! Here's what's happening with your business today.
        </p>
    </div>
    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <?php foreach ($stats as $stat): ?>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <p class="text-sm font-medium text-gray-600"><?php echo esc_html($stat['label']); ?></p>
                <p class="text-2xl font-bold text-gray-900 mt-2">
                    <?php echo esc_html($stat['value']); ?>
                </p>
                <div
                    class="flex items-center mt-2 text-sm <?php echo $stat['isPositive'] ? 'text-green-600' : 'text-red-600'; ?>">
                    <span><?php echo esc_html($stat['change']); ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-1"><line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline></svg>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Upcoming bookings -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-lg font-bold text-gray-900">
                    Upcoming Bookings
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Customer
                            </th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Service
                            </th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date & Time
                            </th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
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
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">
                                            <?php echo esc_html($booking['customer_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo esc_html($booking['service_address']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo esc_html($service_display); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1 text-gray-400"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                            <?php echo esc_html($date_display . ', ' . $time_display); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $booking['status'] === 'confirmed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php if ($booking['status'] === 'confirmed'): ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                            <?php else: ?>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                            <?php endif; ?>
                                            <?php echo esc_html(ucfirst($booking['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-gray-500">No upcoming bookings.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t text-center">
                <a
                    href="#"
                    class="text-blue-600 hover:text-blue-700 font-medium"
                >
                    View all bookings
                </a>
            </div>
        </div>
        <!-- Calendar -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b">
                <h2 class="text-lg font-bold text-gray-900">Calendar</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <button class="p-2 hover:bg-gray-100 rounded">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="text-gray-600"
                        >
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>
                    <span class="font-medium"><?php echo date('F Y'); ?></span>
                    <button class="p-2 hover:bg-gray-100 rounded">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="text-gray-600"
                        >
                            <path d="M9 18l6-6-6-6" />
                        </svg>
                    </button>
                </div>
                <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium text-gray-500 mb-2">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>
                <div class="grid grid-cols-7 gap-1 text-center">
                    <?php
                    $first_day_of_month = date('N', strtotime("$year-$month-01"));
                    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    for ($i = 1; $i < $first_day_of_month; $i++): ?>
                        <div class="day empty"></div>
                    <?php endfor; ?>

                    <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                        <button
                            class="py-2 rounded-md <?php echo (date('j') == $day && date('m') == $month && date('Y') == $year) ? 'bg-blue-600 text-white font-medium' : 'hover:bg-gray-100'; ?> <?php echo isset($calendar_bookings[$day]) ? 'relative' : ''; ?>"
                        >
                            <?php echo $day; ?>
                            <?php if (isset($calendar_bookings[$day])): ?>
                                <span class="absolute bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-blue-600 rounded-full"></span>
                            <?php endif; ?>
                        </button>
                    <?php endfor; ?>
                </div>
                <div class="mt-6 space-y-3">
                    <?php if (!empty($calendar_bookings[date('j')])): ?>
                        <?php foreach($calendar_bookings[date('j')] as $booking): ?>
                            <div class="flex items-center p-2 bg-blue-50 rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600 mr-2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                <div class="text-sm">
                                    <p class="font-medium text-gray-900"><?php echo esc_html(array_column($booking['items'], 'service_name')[0] ?? 'Booking'); ?></p>
                                    <p class="text-gray-600"><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <!-- Staff performance -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-lg font-bold text-gray-900">
                    Staff Performance
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Staff Member
                            </th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Role
                            </th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Bookings
                            </th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rating
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($staff_members_data)): ?>
                            <?php foreach ($staff_members_data as $staff): ?>
                                <?php
                                $bookings_count = $staff_booking_counts[$staff->ID] ?? 0;
                                $roles = array_map('ucfirst', $staff->roles);
                                if (count($roles) > 1) {
                                    $roles = array_diff($roles, ['Subscriber']);
                                }
                                $role_display = implode(', ', $roles);
                                $role_display = str_replace('Mobooking_worker_staff', 'Cleaner', $role_display);
                                ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">
                                            <?php echo esc_html($staff->display_name); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo esc_html($role_display); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo esc_html($bookings_count); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="text-yellow-500 mr-1">★</span>
                                            <span>4.8</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-gray-500">No staff members found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Popular services -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6 border-b">
                <h2 class="text-lg font-bold text-gray-900">
                    Popular Services
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Service
                            </th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Duration
                            </th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Price
                            </th>
                            <th class="px-6 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Bookings
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($popular_services)): ?>
                            <?php foreach ($popular_services as $service): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">
                                            <?php echo esc_html($service['name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo esc_html($service['duration'] / 60); ?> hours
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo esc_html($currency_symbol . number_format($service['price'], 2)); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo esc_html($service['bookings_count']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-gray-500">No popular services found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
