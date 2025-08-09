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

<style>
/* shadcn/ui inspired styles */
:root {
  --background: 0 0% 100%;
  --foreground: 222.2 84% 4.9%;
  --card: 0 0% 100%;
  --card-foreground: 222.2 84% 4.9%;
  --popover: 0 0% 100%;
  --popover-foreground: 222.2 84% 4.9%;
  --primary: 221.2 83.2% 53.3%;
  --primary-foreground: 210 40% 98%;
  --secondary: 210 40% 96%;
  --secondary-foreground: 222.2 84% 4.9%;
  --muted: 210 40% 96%;
  --muted-foreground: 215.4 16.3% 46.9%;
  --accent: 210 40% 96%;
  --accent-foreground: 222.2 84% 4.9%;
  --destructive: 0 84.2% 60.2%;
  --destructive-foreground: 210 40% 98%;
  --border: 214.3 31.8% 91.4%;
  --input: 214.3 31.8% 91.4%;
  --ring: 221.2 83.2% 53.3%;
  --radius: 0.5rem;
}

.mobooking-overview-dashboard {
  max-width: 1200px;
  margin: 0 auto;
}

.dashboard-header {
  margin-bottom: 2rem;
}

.dashboard-title {
  font-size: 2rem;
  font-weight: 700;
  color: hsl(var(--foreground));
  margin: 0 0 0.5rem 0;
}

.dashboard-subtitle {
  color: hsl(var(--muted-foreground));
  font-size: 0.875rem;
}

/* KPI Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.kpi-card {
  background: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  padding: 1.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  transition: all 0.2s ease;
}

.kpi-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  transform: translateY(-1px);
}

.kpi-header {
  display: flex;
  justify-content: between;
  align-items: center;
  margin-bottom: 1rem;
}

.kpi-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: hsl(var(--muted-foreground));
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.kpi-icon {
  width: 2rem;
  height: 2rem;
  background: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.kpi-value {
  font-size: 2rem;
  font-weight: 700;
  color: hsl(var(--foreground));
  margin-bottom: 0.5rem;
}

.kpi-change {
  font-size: 0.875rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.kpi-change.positive {
  color: #10b981;
}

.kpi-change.negative {
  color: #ef4444;
}

.kpi-change.neutral {
  color: hsl(var(--muted-foreground));
}

/* Content Grid */
.content-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.content-card {
  background: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  padding: 1.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: hsl(var(--foreground));
  margin: 0 0 1rem 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* Recent Bookings */
.booking-item {
  padding: 1rem;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  margin-bottom: 0.75rem;
  transition: all 0.2s ease;
}

.booking-item:hover {
  background: hsl(var(--muted));
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.05);
}

.booking-item:last-child {
  margin-bottom: 0;
}

.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.booking-customer {
  font-weight: 600;
  color: hsl(var(--foreground));
}

.booking-status {
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-confirmed {
  background: #dbeafe;
  color: #1e40af;
}

.status-completed {
  background: #d1fae5;
  color: #065f46;
}

.status-cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.booking-details {
  color: hsl(var(--muted-foreground));
  font-size: 0.875rem;
}

/* Staff Stats */
.staff-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  margin-bottom: 0.5rem;
}

.staff-item:last-child {
  margin-bottom: 0;
}

.staff-name {
  font-weight: 500;
  color: hsl(var(--foreground));
}

.staff-count {
  background: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

/* Empty States */
.empty-state {
  text-align: center;
  padding: 2rem;
  color: hsl(var(--muted-foreground));
}

.empty-state-icon {
  width: 3rem;
  height: 3rem;
  margin: 0 auto 1rem;
  background: hsl(var(--muted));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

/* Quick Actions */
.quick-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.quick-action {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.5rem;
  background: hsl(var(--muted));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  text-decoration: none;
  color: hsl(var(--foreground));
  transition: all 0.2s ease;
}

.quick-action:hover {
  background: hsl(var(--accent));
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.quick-action-icon {
  width: 2.5rem;
  height: 2.5rem;
  background: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  margin-bottom: 0.5rem;
}

.quick-action-text {
  font-weight: 500;
  text-align: center;
}

/* Responsive Design */
@media (max-width: 768px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  
  .content-grid {
    grid-template-columns: 1fr;
  }
  
  .quick-actions {
    grid-template-columns: 1fr;
  }
  
  .booking-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>

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
                    <div class="kpi-icon">📅</div>
                </div>
                <div class="kpi-value"><?php echo esc_html($upcoming_count); ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title"><?php esc_html_e('Your Completed Jobs (This Month)', 'mobooking'); ?></div>
                    <div class="kpi-icon">✅</div>
                </div>
                <div class="kpi-value"><?php echo esc_html($worker_completed_jobs_month); ?></div>
            </div>
        </div>

        <div class="content-grid">
            <div class="content-card">
                <h2 class="card-title">
                    📋 <?php esc_html_e('Your Upcoming Assigned Bookings', 'mobooking'); ?>
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
                        <div class="empty-state-icon">🎉</div>
                        <div><?php esc_html_e('No upcoming bookings assigned to you. Enjoy the break!', 'mobooking'); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else : ?>

        <div class="dashboard-header">
            <h1 class="dashboard-title"><?php esc_html_e('Dashboard Overview', 'mobooking'); ?></h1>
            <p class="dashboard-subtitle">
                <?php printf(__('Welcome back, %s! Here\'s your business overview.', 'mobooking'), esc_html($user->display_name)); ?>
            </p>
        </div>

        <!-- KPI Widgets -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title"><?php esc_html_e('Total Bookings', 'mobooking'); ?></div>
                    <div class="kpi-icon">📅</div>
                </div>
                <div class="kpi-value"><?php echo esc_html($total_bookings); ?></div>
                <div class="kpi-change <?php echo $bookings_change[0] === '+' ? 'positive' : ($bookings_change === '0%' ? 'neutral' : 'negative'); ?>">
                    <?php echo esc_html($bookings_change); ?> <?php esc_html_e('vs last month', 'mobooking'); ?>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title"><?php esc_html_e('Monthly Revenue', 'mobooking'); ?></div>
                    <div class="kpi-icon">💰</div>
                </div>
                <div class="kpi-value"><?php echo esc_html($currency_symbol . number_format($monthly_revenue, 2)); ?></div>
                <div class="kpi-change <?php echo $revenue_change[0] === '+' ? 'positive' : ($revenue_change === '0%' ? 'neutral' : 'negative'); ?>">
                    <?php echo esc_html($revenue_change); ?> <?php esc_html_e('vs last month', 'mobooking'); ?>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title"><?php esc_html_e('Completed Jobs', 'mobooking'); ?></div>
                    <div class="kpi-icon">✅</div>
                </div>
                <div class="kpi-value"><?php echo esc_html($completed_jobs); ?></div>
                <div class="kpi-change <?php echo $completed_change[0] === '+' ? 'positive' : ($completed_change === '0%' ? 'neutral' : 'negative'); ?>">
                    <?php echo esc_html($completed_change); ?> <?php esc_html_e('vs last month', 'mobooking'); ?>
                </div>
            </div>

            <div class="kpi-card">
                <div class="kpi-header">
                    <div class="kpi-title"><?php esc_html_e('New Customers', 'mobooking'); ?></div>
                    <div class="kpi-icon">👥</div>
                </div>
                <div class="kpi-value"><?php echo esc_html($new_customers); ?></div>
                <div class="kpi-change <?php echo $customers_change[0] === '+' ? 'positive' : ($customers_change === '0%' ? 'neutral' : 'negative'); ?>">
                    <?php echo esc_html($customers_change); ?> <?php esc_html_e('vs last month', 'mobooking'); ?>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Recent Bookings -->
            <div class="content-card">
                <h2 class="card-title">
                    📋 <?php esc_html_e('Recent Bookings', 'mobooking'); ?>
                </h2>
                
                <?php if (!empty($recent_bookings)) : ?>
                    <?php foreach ($recent_bookings as $booking) : ?>
                        <div class="booking-item">
                            <div class="booking-header">
                                <span class="booking-customer"><?php echo esc_html($booking['customer_name']); ?></span>
                                <span class="booking-status status-<?php echo esc_attr($booking['status']); ?>">
                                    <?php echo esc_html(ucfirst($booking['status'])); ?>
                                </span>
                            </div>
                            <div class="booking-details">
                                <div><?php echo esc_html($booking['customer_email']); ?></div>
                                <div><?php echo esc_html(date('M j, Y', strtotime($booking['booking_date']))); ?> at <?php echo esc_html($booking['booking_time']); ?></div>
                                <div><?php echo esc_html($currency_symbol . number_format($booking['total_price'], 2)); ?> • <?php esc_html_e('Staff:', 'mobooking'); ?> <?php echo esc_html($booking['assigned_staff_name']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div style="margin-top: 1rem; text-align: center;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mobooking-bookings')); ?>" style="color: hsl(var(--primary)); text-decoration: none; font-weight: 500;">
                            <?php esc_html_e('View All Bookings', 'mobooking'); ?> →
                        </a>
                    </div>
                <?php else : ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <div><?php esc_html_e('No bookings yet', 'mobooking'); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar Content -->
            <div>
                <!-- Staff Performance -->
                <div class="content-card" style="margin-bottom: 1.5rem;">
                    <h2 class="card-title">
                        👨‍💼 <?php esc_html_e('Staff Performance', 'mobooking'); ?>
                    </h2>
                    
                    <?php if (!empty($staff_stats)) : ?>
                        <?php foreach ($staff_stats as $staff) : ?>
                            <div class="staff-item">
                                <span class="staff-name"><?php echo esc_html($staff['staff_name']); ?></span>
                                <span class="staff-count"><?php echo esc_html($staff['booking_count']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">👨‍💼</div>
                            <div><?php esc_html_e('No staff data available', 'mobooking'); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="content-card">
                    <h2 class="card-title">
                        ⚡ <?php esc_html_e('Quick Actions', 'mobooking'); ?>
                    </h2>
                    
                    <div class="quick-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=mobooking-bookings')); ?>" class="quick-action">
                            <div class="quick-action-icon">📅</div>
                            <div class="quick-action-text"><?php esc_html_e('Manage Bookings', 'mobooking'); ?></div>
                        </a>

                        <a href="<?php echo esc_url(admin_url('admin.php?page=mobooking-services')); ?>" class="quick-action">
                            <div class="quick-action-icon">🛠️</div>
                            <div class="quick-action-text"><?php esc_html_e('Manage Services', 'mobooking'); ?></div>
                        </a>

                        <a href="<?php echo esc_url(admin_url('admin.php?page=mobooking-customers')); ?>" class="quick-action">
                            <div class="quick-action-icon">👥</div>
                            <div class="quick-action-text"><?php esc_html_e('View Customers', 'mobooking'); ?></div>
                        </a>

                        <a href="<?php echo esc_url(admin_url('admin.php?page=mobooking-settings')); ?>" class="quick-action">
                            <div class="quick-action-icon">⚙️</div>
                            <div class="quick-action-text"><?php esc_html_e('Settings', 'mobooking'); ?></div>
                        </a>
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