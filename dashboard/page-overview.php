<?php
/**
 * Dashboard Page: Overview
 * Fixed version with working widgets and shadcn/ui styling
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

// Get database instance for safe queries
global $wpdb;
$bookings_table = \MoBooking\Classes\Database::get_table_name('bookings');

// Helper function to safely get booking statistics
function get_safe_booking_stats($wpdb, $bookings_table, $user_id, $start_date = null, $end_date = null) {
    $where_conditions = ["user_id = %d"];
    $where_values = [$user_id];
    
    if ($start_date && $end_date) {
        $where_conditions[] = "booking_date BETWEEN %s AND %s";
        $where_values[] = $start_date;
        $where_values[] = $end_date;
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Total bookings
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $bookings_table $where_clause",
        $where_values
    ));
    
    // Bookings by status
    $status_counts = $wpdb->get_results($wpdb->prepare(
        "SELECT status, COUNT(*) as count FROM $bookings_table $where_clause GROUP BY status",
        $where_values
    ), ARRAY_A);
    
    $by_status = [];
    if ($status_counts) {
        foreach ($status_counts as $row) {
            $by_status[$row['status']] = intval($row['count']);
        }
    }
    
    // Revenue (only for completed/confirmed bookings)
    $revenue_conditions = $where_conditions;
    $revenue_conditions[] = "status IN ('completed', 'confirmed')";
    $revenue_where = 'WHERE ' . implode(' AND ', $revenue_conditions);
    
    $total_revenue = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(total_price) FROM $bookings_table $revenue_where",
        $where_values
    ));
    
    return [
        'total' => intval($total),
        'by_status' => $by_status,
        'total_revenue' => floatval($total_revenue ?: 0)
    ];
}

// Helper function to get customer statistics
function get_safe_customer_stats($wpdb, $bookings_table, $user_id, $start_date = null, $end_date = null) {
    $where_conditions = ["user_id = %d"];
    $where_values = [$user_id];
    
    if ($start_date && $end_date) {
        $where_conditions[] = "created_at BETWEEN %s AND %s";
        $where_values[] = $start_date . ' 00:00:00';
        $where_values[] = $end_date . ' 23:59:59';
    }
    
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    
    // Count unique customers by email
    $new_customers = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT customer_email) FROM $bookings_table $where_clause",
        $where_values
    ));
    
    return [
        'new_customers' => intval($new_customers ?: 0)
    ];
}

// Helper function to calculate percentage change
function calculate_percentage_change($current, $previous) {
    if ($previous == 0) {
        return $current > 0 ? '+100%' : '0%';
    }
    $change = (($current - $previous) / $previous) * 100;
    return sprintf('%+.1f%%', $change);
}

// Fetch data for KPI Widgets
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');
$previous_month_start = date('Y-m-01', strtotime('-1 month'));
$previous_month_end = date('Y-m-t', strtotime('-1 month'));

// Get safe stats for current and previous month
$current_month_stats = get_safe_booking_stats($wpdb, $bookings_table, $data_user_id, $current_month_start, $current_month_end);
$previous_month_stats = get_safe_booking_stats($wpdb, $bookings_table, $data_user_id, $previous_month_start, $previous_month_end);
$current_month_customers = get_safe_customer_stats($wpdb, $bookings_table, $data_user_id, $current_month_start, $current_month_end);
$previous_month_customers = get_safe_customer_stats($wpdb, $bookings_table, $data_user_id, $previous_month_start, $previous_month_end);

// Prepare data for stats widgets
$total_bookings = $current_month_stats['total'] ?? 0;
$completed_jobs = $current_month_stats['by_status']['completed'] ?? 0;
$monthly_revenue = $current_month_stats['total_revenue'] ?? 0;
$new_customers = $current_month_customers['new_customers'] ?? 0;
$upcoming_count = $bookings_manager->get_kpi_data($data_user_id)['upcoming_count'];

$prev_total_bookings = $previous_month_stats['total'] ?? 0;
$prev_completed_jobs = $previous_month_stats['by_status']['completed'] ?? 0;
$prev_monthly_revenue = $previous_month_stats['total_revenue'] ?? 0;
$prev_new_customers = $previous_month_customers['new_customers'] ?? 0;

// Calculate percentage changes
$bookings_change = calculate_percentage_change($total_bookings, $prev_total_bookings);
$revenue_change = calculate_percentage_change($monthly_revenue, $prev_monthly_revenue);
$completed_change = calculate_percentage_change($completed_jobs, $prev_completed_jobs);
$customers_change = calculate_percentage_change($new_customers, $prev_new_customers);

// Function to safely check if column exists
function mobooking_column_exists_safe($table_name, $column_name) {
    global $wpdb;
    
    $columns = $wpdb->get_results("DESCRIBE $table_name", ARRAY_A);
    $existing_columns = array_column($columns, 'Field');
    
    return in_array($column_name, $existing_columns);
}

// Check if assigned_staff_id column exists
$has_assigned_staff_column = mobooking_column_exists_safe($bookings_table, 'assigned_staff_id');

// Get recent bookings (last 5) - with safe query
if ($has_assigned_staff_column) {
    $recent_bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT booking_id, customer_name, customer_email, booking_date, booking_time, status, total_price, 
                CASE WHEN assigned_staff_id IS NOT NULL THEN (SELECT display_name FROM {$wpdb->users} WHERE ID = assigned_staff_id) ELSE 'Unassigned' END as assigned_staff_name
         FROM $bookings_table 
         WHERE user_id = %d 
         ORDER BY created_at DESC 
         LIMIT 5",
        $data_user_id
    ), ARRAY_A);
} else {
    $recent_bookings = $wpdb->get_results($wpdb->prepare(
        "SELECT booking_id, customer_name, customer_email, booking_date, booking_time, status, total_price, 
                'Unassigned' as assigned_staff_name
         FROM $bookings_table 
         WHERE user_id = %d 
         ORDER BY created_at DESC 
         LIMIT 5",
        $data_user_id
    ), ARRAY_A);
}

// Fix for assigned staff statistics (safe query without causing errors)
$staff_stats = [];
if ($has_assigned_staff_column) {
    try {
        $staff_assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                CASE WHEN assigned_staff_id IS NOT NULL THEN assigned_staff_id ELSE 0 END as staff_id,
                COUNT(booking_id) as booking_count,
                CASE 
                    WHEN assigned_staff_id IS NOT NULL THEN (SELECT display_name FROM {$wpdb->users} WHERE ID = assigned_staff_id)
                    ELSE 'Unassigned'
                END as staff_name
             FROM $bookings_table 
             WHERE user_id = %d 
             GROUP BY assigned_staff_id
             ORDER BY booking_count DESC
             LIMIT 5",
            $data_user_id
        ), ARRAY_A);
        
        if ($staff_assignments) {
            $staff_stats = $staff_assignments;
        }
    } catch (Exception $e) {
        error_log('MoBooking - Error fetching staff statistics: ' . $e->getMessage());
        $staff_stats = [];
    }
} else {
    // If no assigned_staff_id column, show default data
    $total_bookings_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $bookings_table WHERE user_id = %d",
        $data_user_id
    ));
    
    if ($total_bookings_count > 0) {
        $staff_stats = [
            [
                'staff_id' => 0,
                'staff_name' => 'All Bookings (Unassigned)',
                'booking_count' => $total_bookings_count
            ]
        ];
    }
}

// Currency symbol
$currency_symbol = get_option('mobooking_currency_symbol', '$');

?>




<div class="wrap mobooking-overview-dashboard">
    <?php if ($is_worker) : ?>

        <div class="dashboard-header">
            <h1 class="dashboard-title"><?php esc_html_e('My Dashboard', 'mobooking'); ?></h1>
            <p class="dashboard-subtitle">
                <?php printf(__('Welcome back, %s! Here are your assigned tasks.', 'mobooking'), esc_html($user->display_name)); ?>
            </p>
        </div>

        <!-- Worker-specific content will go here -->
        <?php
        // Get worker-specific data
        $worker_completed_jobs_month = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $bookings_table WHERE assigned_staff_id = %d AND status = 'completed' AND booking_date BETWEEN %s AND %s",
            $current_user_id, $current_month_start, $current_month_end
        ));
        ?>
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title"><?php esc_html_e('Your Upcoming Bookings', 'mobooking'); ?></div>
                    <div class="kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
                </div>
                <div class="kpi-value"><?php echo esc_html($upcoming_count); ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title"><?php esc_html_e('Your Completed Jobs (This Month)', 'mobooking'); ?></div>
                    <div class="kpi-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div>
                </div>
                <div class="kpi-value"><?php echo esc_html($worker_completed_jobs_month); ?></div>
            </div>
        </div>

        <div class="content-grid">
            <div class="content-card">
                <h2 class="card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                    <?php esc_html_e('Your Upcoming Assigned Bookings', 'mobooking'); ?>
                </h2>
                <?php
                $upcoming_bookings = $bookings_manager->get_bookings_by_tenant($current_user_id, [
                    'limit' => 5,
                    'filter_by_exactly_assigned_staff_id' => $current_user_id,
                    'status' => 'confirmed',
                    'orderby' => 'booking_date',
                    'order' => 'ASC'
                ]);

                if (!empty($upcoming_bookings['bookings'])) : ?>
                    <?php foreach ($upcoming_bookings['bookings'] as $booking) : ?>
                        <div class="booking-item">
                            <div class="booking-header">
                                <span class="booking-customer"><?php echo esc_html($booking['customer_name']); ?></span>
                                <span class="booking-status status-<?php echo esc_attr($booking['status']); ?>">
                                    <?php echo esc_html(ucfirst($booking['status'])); ?>
                                </span>
                            </div>
                            <div class="booking-details">
                                <div><?php echo esc_html(date('M j, Y', strtotime($booking['booking_date']))); ?> at <?php echo esc_html($booking['booking_time']); ?></div>
                                <div><a href="<?php echo esc_url(home_url('/dashboard/my-assigned-bookings/?action=view_booking&booking_id=' . $booking['booking_id'])); ?>"><?php esc_html_e('View Details', 'mobooking'); ?></a></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top: 1rem; text-align: center;">
                        <a href="<?php echo esc_url(home_url('/dashboard/my-assigned-bookings/')); ?>" style="color: hsl(var(--primary)); text-decoration: none; font-weight: 500;">
                            <?php esc_html_e('View All Your Bookings', 'mobooking'); ?> →
                        </a>
                    </div>
                <?php else : ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"></polyline><rect x="2" y="7" width="20" height="5"></rect><line x1="12" y1="22" x2="12" y2="7"></line><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"></path><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"></path></svg></div>
                        <div><?php esc_html_e('No upcoming bookings assigned to you. Enjoy the break!', 'mobooking'); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else : ?>

        <div class="dashboard-header">
            <div class="mobooking-page-header-heading">
                <span class="mobooking-page-header-icon">
                    <?php echo mobooking_get_dashboard_menu_icon('overview'); ?>
                </span>
                <div class="heading-wrapper">
                    <h1 class="dashboard-title"><?php esc_html_e('Dashboard Overview', 'mobooking'); ?></h1>
                    <p class="dashboard-subtitle">
                        <?php printf(__('Welcome back, %s! Here\'s your business overview.', 'mobooking'), esc_html($user->display_name)); ?>
                    </p>
                </div>
            </div>

        </div>

        <!-- KPI Widgets -->
        <div class="kpi-grid">
        <div class="mobooking-card">
            <div class="mobooking-card-header">
                <div class="mobooking-card-title-group">
                    <span class="mobooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></span>
                    <h3 class="mobooking-card-title"><?php esc_html_e('Total Bookings', 'mobooking'); ?></h3>
                </div>
            </div>
            <div class="mobooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($total_bookings); ?></div>
                <p class="text-xs text-muted-foreground <?php echo $bookings_change[0] === '+' ? 'text-success' : 'text-destructive'; ?>">
                    <?php echo esc_html($bookings_change); ?> <?php esc_html_e('vs last month', 'mobooking'); ?>
                </p>
            </div>
        </div>

        <div class="mobooking-card">
            <div class="mobooking-card-header">
                <div class="mobooking-card-title-group">
                    <span class="mobooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></span>
                    <h3 class="mobooking-card-title"><?php esc_html_e('Monthly Revenue', 'mobooking'); ?></h3>
                </div>
            </div>
            <div class="mobooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($currency_symbol . number_format($monthly_revenue, 2)); ?></div>
                <p class="text-xs text-muted-foreground <?php echo $revenue_change[0] === '+' ? 'text-success' : 'text-destructive'; ?>">
                    <?php echo esc_html($revenue_change); ?> <?php esc_html_e('vs last month', 'mobooking'); ?>
                </p>
            </div>
        </div>

        <div class="mobooking-card">
            <div class="mobooking-card-header">
                <div class="mobooking-card-title-group">
                    <span class="mobooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></span>
                    <h3 class="mobooking-card-title"><?php esc_html_e('Completed Jobs', 'mobooking'); ?></h3>
                </div>
            </div>
            <div class="mobooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($completed_jobs); ?></div>
                <p class="text-xs text-muted-foreground <?php echo $completed_change[0] === '+' ? 'text-success' : 'text-destructive'; ?>">
                    <?php echo esc_html($completed_change); ?> <?php esc_html_e('vs last month', 'mobooking'); ?>
                </p>
            </div>
        </div>

        <div class="mobooking-card">
            <div class="mobooking-card-header">
                <div class="mobooking-card-title-group">
                    <span class="mobooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
                    <h3 class="mobooking-card-title"><?php esc_html_e('New Customers', 'mobooking'); ?></h3>
                </div>
            </div>
            <div class="mobooking-card-content">
                <div class="card-content-value text-2xl font-bold"><?php echo esc_html($new_customers); ?></div>
                <p class="text-xs text-muted-foreground <?php echo $customers_change[0] === '+' ? 'text-success' : 'text-destructive'; ?>">
                    <?php echo esc_html($customers_change); ?> <?php esc_html_e('vs last month', 'mobooking'); ?>
                </p>
            </div>
        </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Recent Bookings -->
            <div class="mobooking-card">
                <div class="mobooking-card-header">
                    <div class="mobooking-card-title-group">
                        <span class="mobooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg></span>
                        <h3 class="mobooking-card-title"><?php esc_html_e('Recent Bookings', 'mobooking'); ?></h3>
                    </div>
                    <div class="mobooking-card-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mobooking-bookings')); ?>" class="btn btn-sm"><?php esc_html_e('View All', 'mobooking'); ?></a>
                    </div>
                </div>
                <div class="mobooking-card-content">
                    <?php if (!empty($recent_bookings)) : ?>
                        <?php
                        foreach ($recent_bookings as $booking) :
                            $details_page_url = home_url('/dashboard/bookings/?action=view_booking&booking_id=' . $booking['booking_id']);
                            $status_val = $booking['status'];
                            $status_display = !empty($status_val) ? ucfirst(str_replace('-', ' ', $status_val)) : __('N/A', 'mobooking');
                            $status_icon_html = function_exists('mobooking_get_status_badge_icon_svg') ? mobooking_get_status_badge_icon_svg($status_val) : '';
                        ?>
                            <a href="<?php echo esc_url($details_page_url); ?>" class="booking-item-link">
                                <div class="booking-item">
                                    <div class="booking-header">
                                        <span class="booking-customer"><?php echo esc_html($booking['customer_name']); ?></span>
                                        <span class="status-badge status-<?php echo esc_attr($status_val); ?>">
                                            <?php echo $status_icon_html; ?>
                                            <span class="status-text"><?php echo esc_html($status_display); ?></span>
                                        </span>
                                    </div>
                                    <div class="booking-details">
                                        <div><?php echo esc_html($booking['customer_email']); ?></div>
                                        <div><?php echo esc_html(date('M j, Y', strtotime($booking['booking_date']))); ?> at <?php echo esc_html($booking['booking_time']); ?></div>
                                        <div><?php echo esc_html($currency_symbol . number_format($booking['total_price'], 2)); ?> • <?php esc_html_e('Staff:', 'mobooking'); ?> <?php echo esc_html($booking['assigned_staff_name']); ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg></div>
                            <div><?php esc_html_e('No bookings yet', 'mobooking'); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar Content -->
            <div>
                <!-- Staff Performance -->
                <div class="mobooking-card" style="margin-bottom: 1.5rem;">
                    <div class="mobooking-card-header">
                         <div class="mobooking-card-title-group">
                            <span class="mobooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg></span>
                            <h3 class="mobooking-card-title"><?php esc_html_e('Staff Performance', 'mobooking'); ?></h3>
                        </div>
                    </div>
                    <div class="mobooking-card-content">
                        <?php if (!empty($staff_stats)) : ?>
                            <?php foreach ($staff_stats as $staff) : ?>
                                <div class="staff-item">
                                    <span class="staff-name"><?php echo esc_html($staff['staff_name']); ?></span>
                                    <span class="staff-count"><?php echo esc_html($staff['booking_count']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="empty-state">
                                <div class="empty-state-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg></div>
                                <div><?php esc_html_e('No staff data available', 'mobooking'); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mobooking-card">
                    <div class="mobooking-card-header">
                        <div class="mobooking-card-title-group">
                            <span class="mobooking-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg></span>
                            <h3 class="mobooking-card-title"><?php esc_html_e('Quick Actions', 'mobooking'); ?></h3>
                        </div>
                    </div>
                    <div class="mobooking-card-content">
                        <div class="quick-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=mobooking-bookings')); ?>" class="quick-action">
                                <div class="quick-action-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg></div>
                                <div class="quick-action-text"><?php esc_html_e('Manage Bookings', 'mobooking'); ?></div>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=mobooking-services')); ?>" class="quick-action">
                                <div class="quick-action-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg></div>
                                <div class="quick-action-text"><?php esc_html_e('Manage Services', 'mobooking'); ?></div>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=mobooking-customers')); ?>" class="quick-action">
                                <div class="quick-action-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
                                <div class="quick-action-text"><?php esc_html_e('View Customers', 'mobooking'); ?></div>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=mobooking-settings')); ?>" class="quick-action">
                                <div class="quick-action-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></div>
                                <div class="quick-action-text"><?php esc_html_e('Settings', 'mobooking'); ?></div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Add any interactive functionality here
    console.log('MoBooking Overview Dashboard Loaded');
    
    // Example: Add click tracking for quick actions
    $('.quick-action').on('click', function() {
        const action = $(this).find('.quick-action-text').text();
        console.log('Quick action clicked:', action);
    });
    
    // Example: Add hover effects for KPI cards
    $('.kpi-card').hover(
        function() {
            $(this).css('transform', 'translateY(-2px)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
        }
    );
});
</script>