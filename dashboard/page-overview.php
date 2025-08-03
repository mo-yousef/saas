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
$stats = $bookings_manager->get_booking_statistics($data_user_id);
$monthly_revenue = $bookings_manager->get_monthly_revenue($data_user_id);
$customer_insights = $customers_manager->get_customer_insights($data_user_id);

$total_bookings = $stats['total'] ?? 0;
$completed_jobs = $stats['by_status']['completed'] ?? 0;
$new_customers = $customer_insights['new_customers'] ?? 0;

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
$staff_members = get_users([
    'meta_key' => \MoBooking\Classes\Auth::META_KEY_OWNER_ID,
    'meta_value' => $data_user_id,
]);
$staff_booking_counts = $bookings_manager->get_booking_counts_by_staff($data_user_id);

// Fetch data for Popular Services
$popular_services = $services_manager->get_popular_services($data_user_id, 4);

// Get currency symbol
$currency_symbol = \MoBooking\Classes\Utils::get_currency_symbol($settings_manager->get_setting($data_user_id, 'biz_currency_code', 'USD'));
?>

<div class="mobooking-overview-enhanced">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1>Dashboard Overview</h1>
        <p>Welcome back! Here's what's happening with your business today.</p>
    </div>

    <!-- Main Overview Grid -->
    <div class="overview-grid">
        <!-- KPI Cards -->
        <div class="grid-span-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Total Bookings</h3>
                </div>
                <div class="card-content">
                    <div class="kpi-value"><?php echo esc_html($total_bookings); ?></div>
                    <div class="kpi-trend positive">+12%</div>
                </div>
            </div>
        </div>
        <div class="grid-span-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Completed Jobs</h3>
                </div>
                <div class="card-content">
                    <div class="kpi-value"><?php echo esc_html($completed_jobs); ?></div>
                    <div class="kpi-trend positive">+8%</div>
                </div>
            </div>
        </div>
        <div class="grid-span-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Monthly Revenue</h3>
                </div>
                <div class="card-content">
                    <div class="kpi-value"><?php echo esc_html($currency_symbol . number_format($monthly_revenue, 2)); ?></div>
                    <div class="kpi-trend positive">+24%</div>
                </div>
            </div>
        </div>
        <div class="grid-span-3">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Customers</h3>
                </div>
                <div class="card-content">
                    <div class="kpi-value"><?php echo esc_html($new_customers); ?></div>
                    <div class="kpi-trend positive">+18%</div>
                </div>
            </div>
        </div>

        <!-- Upcoming Bookings -->
        <div class="grid-span-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Upcoming Bookings</h3>
                </div>
                <div class="card-content">
                    <table class="mobooking-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
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
                                        <td>
                                            <?php echo esc_html($booking['customer_name']); ?><br>
                                            <small><?php echo esc_html($booking['service_address']); ?></small>
                                        </td>
                                        <td><?php echo esc_html($service_display); ?></td>
                                        <td><?php echo esc_html($date_display . ', ' . $time_display); ?></td>
                                        <td><span class="mobooking-badge <?php echo esc_attr(strtolower($booking['status'])); ?>"><?php echo esc_html(ucfirst($booking['status'])); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No upcoming bookings.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Calendar -->
        <div class="grid-span-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Calendar</h3>
                </div>
                <div class="card-content">
                    <?php
                    $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    $first_day_of_month = date('N', strtotime("$year-$month-01"));
                    ?>
                    <div class="mobooking-calendar">
                        <div class="calendar-header">
                            <h2><?php echo date('F Y', strtotime("$year-$month-01")); ?></h2>
                        </div>
                        <div class="calendar-grid">
                            <div class="day-name">Sun</div>
                            <div class="day-name">Mon</div>
                            <div class="day-name">Tue</div>
                            <div class="day-name">Wed</div>
                            <div class="day-name">Thu</div>
                            <div class="day-name">Fri</div>
                            <div class="day-name">Sat</div>

                            <?php for ($i = 1; $i < $first_day_of_month; $i++): ?>
                                <div class="day empty"></div>
                            <?php endfor; ?>

                            <?php for ($day = 1; $day <= $days_in_month; $day++): ?>
                                <div class="day <?php if (date('j') == $day && date('m') == $month && date('Y') == $year) echo 'today'; ?>">
                                    <div class="day-number"><?php echo $day; ?></div>
                                    <?php if (isset($calendar_bookings[$day])): ?>
                                        <?php foreach($calendar_bookings[$day] as $booking): ?>
                                            <div class="booking-event">
                                                <?php
                                                $service_names = array_column($booking['items'], 'service_name');
                                                echo esc_html(!empty($service_names) ? $service_names[0] : 'Booking');
                                                ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Performance -->
        <div class="grid-span-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Staff Performance</h3>
                </div>
                <div class="card-content">
                    <table class="mobooking-table">
                        <thead>
                            <tr>
                                <th>Staff Member</th>
                                <th>Role</th>
                                <th>Bookings</th>
                                <th>Rating</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($staff_members)): ?>
                                <?php foreach ($staff_members as $staff): ?>
                                    <?php
                                    $bookings_count = $staff_booking_counts[$staff->ID] ?? 0;
                                    $roles = array_map('ucfirst', $staff->roles);
                                    // Remove the 'subscriber' role if other roles are present
                                    if (count($roles) > 1) {
                                        $roles = array_diff($roles, ['Subscriber']);
                                    }
                                    $role_display = implode(', ', $roles);
                                    // Replace role slug with a more friendly name
                                    $role_display = str_replace('Mobooking_worker_staff', 'Cleaner', $role_display);
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($staff->display_name); ?></td>
                                        <td><?php echo esc_html($role_display); ?></td>
                                        <td><?php echo esc_html($bookings_count); ?></td>
                                        <td>★ 4.8</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No staff members found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Popular Services -->
        <div class="grid-span-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Popular Services</h3>
                </div>
                <div class="card-content">
                    <table class="mobooking-table">
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Duration</th>
                                <th>Price</th>
                                <th>Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($popular_services)): ?>
                                <?php foreach ($popular_services as $service): ?>
                                    <tr>
                                        <td><?php echo esc_html($service['name']); ?></td>
                                        <td><?php echo esc_html($service['duration'] / 60); ?> hours</td>
                                        <td><?php echo esc_html($currency_symbol . number_format($service['price'], 2)); ?></td>
                                        <td><?php echo esc_html($service['bookings_count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No popular services found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
