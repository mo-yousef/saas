<?php
/**
 * Dashboard Page: Customers - Targeted Fix for Your Database Setup
 * Works with your existing wp_mobooking_customers and wp_mobooking_mob_customers tables
 * @package MoBooking
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Check permissions
if ( ! current_user_can( \MoBooking\Classes\Auth::CAP_MANAGE_CUSTOMERS ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'mobooking' ) );
}

// Get current user and tenant ID
$current_user_id = get_current_user_id();
$tenant_id = \MoBooking\Classes\Auth::get_effective_tenant_id_for_user( $current_user_id );

// Debug: Let's check what tenant_id we're getting
error_log("MoBooking Customers Debug - Current User ID: $current_user_id, Tenant ID: $tenant_id");

// Helper function to get customers from the correct table
function mobooking_get_customers_data($tenant_id, $args = []) {
    global $wpdb;
    
    // Check which customer tables exist and have data
    $customers_table = $wpdb->prefix . 'mobooking_customers';
    $mob_customers_table = $wpdb->prefix . 'mobooking_mob_customers';
    $bookings_table = $wpdb->prefix . 'mobooking_bookings';
    
    // First, let's check which tables have data
    $customers_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $customers_table WHERE tenant_id = %d",
        $tenant_id
    ));
    
    $mob_customers_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $mob_customers_table WHERE tenant_id = %d", 
        $tenant_id
    ));
    
    $bookings_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT customer_email) FROM $bookings_table WHERE user_id = %d",
        $tenant_id
    ));
    
    error_log("MoBooking Debug - Customers: $customers_count, Mob Customers: $mob_customers_count, Bookings: $bookings_count");
    
    // Extract arguments
    $page = isset($args['page']) ? absint($args['page']) : 1;
    $per_page = isset($args['per_page']) ? absint($args['per_page']) : 20;
    $search = isset($args['search']) ? sanitize_text_field($args['search']) : '';
    $orderby = isset($args['orderby']) ? sanitize_key($args['orderby']) : 'full_name';
    $order = isset($args['order']) ? strtoupper(sanitize_key($args['order'])) : 'ASC';
    
    $offset = ($page - 1) * $per_page;
    
    // Build WHERE conditions for search
    $search_conditions = '';
    $search_params = [];
    
    if (!empty($search)) {
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $search_conditions = " AND (full_name LIKE %s OR email LIKE %s OR phone_number LIKE %s)";
        $search_params = [$search_term, $search_term, $search_term];
    }
    
    // Try mob_customers table first (as it has more data based on your table info)
    if ($mob_customers_count > 0) {
        error_log("MoBooking Debug - Using mob_customers table");
        
        $customers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $mob_customers_table 
             WHERE tenant_id = %d $search_conditions
             ORDER BY $orderby $order
             LIMIT %d OFFSET %d",
            array_merge([$tenant_id], $search_params, [$per_page, $offset])
        ), ARRAY_A);
        
        // Convert to objects for consistency
        return array_map(function($customer) {
            return (object) $customer;
        }, $customers ?: []);
    }
    
    // Try customers table
    if ($customers_count > 0) {
        error_log("MoBooking Debug - Using customers table");
        
        $customers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $customers_table 
             WHERE tenant_id = %d $search_conditions
             ORDER BY $orderby $order
             LIMIT %d OFFSET %d",
            array_merge([$tenant_id], $search_params, [$per_page, $offset])
        ), ARRAY_A);
        
        return array_map(function($customer) {
            return (object) $customer;
        }, $customers ?: []);
    }
    
    // Fallback to bookings table
    if ($bookings_count > 0) {
        error_log("MoBooking Debug - Using bookings table fallback");
        
        $customers = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                customer_email as email,
                customer_name as full_name,
                customer_phone as phone_number,
                service_address,
                COUNT(*) as total_bookings,
                MAX(booking_date) as last_booking_date,
                SUM(total_price) as total_spent,
                MAX(created_at) as created_at,
                'active' as status
             FROM $bookings_table 
             WHERE user_id = %d $search_conditions
             GROUP BY customer_email, customer_name
             ORDER BY $orderby $order
             LIMIT %d OFFSET %d",
             array_merge([$tenant_id], $search_params, [$per_page, $offset])
        ), ARRAY_A);
        
        return array_map(function($customer) {
            return (object) $customer;
        }, $customers ?: []);
    }
    
    return [];
}

// Helper function to get customer count
function mobooking_get_customers_count($tenant_id, $args = []) {
    global $wpdb;
    
    $customers_table = $wpdb->prefix . 'mobooking_customers';
    $mob_customers_table = $wpdb->prefix . 'mobooking_mob_customers';
    $bookings_table = $wpdb->prefix . 'mobooking_bookings';
    
    $search = isset($args['search']) ? sanitize_text_field($args['search']) : '';
    
    $search_conditions = '';
    $search_params = [];
    
    if (!empty($search)) {
        $search_term = '%' . $wpdb->esc_like($search) . '%';
        $search_conditions = " AND (full_name LIKE %s OR email LIKE %s OR phone_number LIKE %s)";
        $search_params = [$search_term, $search_term, $search_term];
    }
    
    // Check mob_customers first
    $mob_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $mob_customers_table WHERE tenant_id = %d",
        $tenant_id
    ));
    
    if ($mob_count > 0) {
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $mob_customers_table WHERE tenant_id = %d $search_conditions",
            array_merge([$tenant_id], $search_params)
        ));
    }
    
    // Check customers table
    $customers_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $customers_table WHERE tenant_id = %d",
        $tenant_id
    ));
    
    if ($customers_count > 0) {
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $customers_table WHERE tenant_id = %d $search_conditions",
            array_merge([$tenant_id], $search_params)
        ));
    }
    
    // Fallback to bookings
    $search_conditions_bookings = '';
    if (!empty($search)) {
        $search_conditions_bookings = " AND (customer_name LIKE %s OR customer_email LIKE %s OR customer_phone LIKE %s)";
    }
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT customer_email) FROM $bookings_table WHERE user_id = %d $search_conditions_bookings",
        array_merge([$tenant_id], $search_params)
    ));
}

// Helper function to get KPI data
function mobooking_get_kpi_data($tenant_id) {
    global $wpdb;
    
    $customers_table = $wpdb->prefix . 'mobooking_customers';
    $mob_customers_table = $wpdb->prefix . 'mobooking_mob_customers';
    $bookings_table = $wpdb->prefix . 'mobooking_bookings';
    
    // Check which table has data
    $mob_customers_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $mob_customers_table WHERE tenant_id = %d", 
        $tenant_id
    ));
    
    $customers_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $customers_table WHERE tenant_id = %d",
        $tenant_id
    ));
    
    if ($mob_customers_count > 0) {
        // Use mob_customers table
        $total_customers = $mob_customers_count;
        
        $current_month_start = date('Y-m-01');
        $new_customers_month = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $mob_customers_table WHERE tenant_id = %d AND created_at >= %s",
            $tenant_id, $current_month_start
        ));
        
        $active_customers = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $mob_customers_table WHERE tenant_id = %d AND status = 'active'",
            $tenant_id
        ));
        
    } elseif ($customers_count > 0) {
        // Use customers table
        $total_customers = $customers_count;
        
        $current_month_start = date('Y-m-01');
        $new_customers_month = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $customers_table WHERE tenant_id = %d AND created_at >= %s",
            $tenant_id, $current_month_start
        ));
        
        $active_customers = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $customers_table WHERE tenant_id = %d AND status = 'active'",
            $tenant_id
        ));
        
    } else {
        // Use bookings table
        $total_customers = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT customer_email) FROM $bookings_table WHERE user_id = %d",
            $tenant_id
        ));
        
        $current_month_start = date('Y-m-01');
        $new_customers_month = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(T.customer_email) FROM (
                SELECT customer_email, MIN(created_at) as first_booking_date
                FROM $bookings_table
                WHERE user_id = %d
                GROUP BY customer_email
            ) AS T
            WHERE DATE(T.first_booking_date) >= %s",
            $tenant_id, $current_month_start
        ));
        
        $active_customers = $total_customers; // All customers from bookings are considered active
    }
    
    // Calculate average order value from bookings
    $avg_order_value = $wpdb->get_var($wpdb->prepare(
        "SELECT AVG(total_price) FROM $bookings_table WHERE user_id = %d AND status IN ('completed', 'confirmed')",
        $tenant_id
    ));
    
    return [
        'total_customers' => intval($total_customers ?: 0),
        'new_customers_month' => intval($new_customers_month ?: 0),
        'active_customers' => intval($active_customers ?: 0),
        'avg_order_value' => floatval($avg_order_value ?: 0)
    ];
}

// Prepare arguments for fetching customers
$page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$per_page = 20;
$search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$sort_by = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'full_name';
$sort_order = isset( $_GET['order'] ) ? strtoupper( sanitize_key( $_GET['order'] ) ) : 'ASC';

$args = [
    'page' => $page,
    'per_page' => $per_page,
    'search' => $search,
    'orderby' => $sort_by,
    'order' => $sort_order,
];

// Get customers and KPI data
$customers = mobooking_get_customers_data($tenant_id, $args);
$total_customers_count = mobooking_get_customers_count($tenant_id, $args);
$kpi_data = mobooking_get_kpi_data($tenant_id);

// Debug output
error_log("MoBooking Debug - Found " . count($customers) . " customers, Total count: $total_customers_count");
if (!empty($customers)) {
    error_log("MoBooking Debug - First customer: " . print_r($customers[0], true));
}

// Currency symbol
$currency_symbol = get_option('mobooking_currency_symbol', '$');

// Pagination
$total_pages = ceil($total_customers_count / $per_page);
?>

<style>
/* Enhanced styles for customers page */
:root {
  --background: 0 0% 100%;
  --foreground: 222.2 84% 4.9%;
  --card: 0 0% 100%;
  --card-foreground: 222.2 84% 4.9%;
  --primary: 221.2 83.2% 53.3%;
  --primary-foreground: 210 40% 98%;
  --secondary: 210 40% 96%;
  --secondary-foreground: 222.2 84% 4.9%;
  --muted: 210 40% 96%;
  --muted-foreground: 215.4 16.3% 46.9%;
  --border: 214.3 31.8% 91.4%;
  --input: 214.3 31.8% 91.4%;
  --radius: 0.5rem;
  --success: 142 76% 36%;
  --warning: 38 92% 50%;
  --destructive: 0 84.2% 60.2%;
}

.mobooking-customers-dashboard {
  padding: 1.5rem;
  max-width: 1400px;
  margin: 0 auto;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  background: hsl(var(--card));
  padding: 1.5rem;
  border-radius: var(--radius);
  border: 1px solid hsl(var(--border));
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: hsl(var(--foreground));
  margin: 0;
}

.add-btn {
  background: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: var(--radius);
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-decoration: none;
}

.add-btn:hover {
  background: hsl(221.2 83.2% 47.3%);
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Debug info */
.debug-info {
  background: #f0f9ff;
  border: 1px solid #0ea5e9;
  border-radius: var(--radius);
  padding: 1rem;
  margin-bottom: 1.5rem;
  font-family: monospace;
  font-size: 0.875rem;
}

/* KPI Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.kpi-card {
  background: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  padding: 1.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.kpi-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.kpi-header {
  display: flex;
  justify-content: space-between;
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
  width: 2.5rem;
  height: 2.5rem;
  background: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
}

.kpi-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: hsl(var(--foreground));
  line-height: 1;
}

/* Controls */
.controls-section {
  background: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  padding: 1.5rem;
  margin-bottom: 1.5rem;
}

.controls-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.search-form {
  display: flex;
  gap: 0.5rem;
  flex: 1;
  max-width: 400px;
}

.search-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  font-size: 0.875rem;
  background: hsl(var(--background));
}

.search-btn {
  background: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  border: none;
  padding: 0.75rem 1rem;
  border-radius: var(--radius);
  cursor: pointer;
  font-weight: 500;
  white-space: nowrap;
}

.table-container {
  background: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.customers-table {
  width: 100%;
  border-collapse: collapse;
}

.customers-table th {
  background: hsl(var(--muted));
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: hsl(var(--foreground));
  border-bottom: 1px solid hsl(var(--border));
  position: relative;
}

.customers-table td {
  padding: 1rem;
  border-bottom: 1px solid hsl(var(--border));
  color: hsl(var(--card-foreground));
}

.customers-table tr:hover {
  background: hsl(var(--muted) / 0.5);
}

.customers-table th a {
  color: hsl(var(--foreground));
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.customers-table th a:hover {
  color: hsl(var(--primary));
}

.sort-arrow {
  opacity: 0.5;
  transition: opacity 0.2s;
}

.sortable:hover .sort-arrow {
  opacity: 1;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status-active {
  background: hsl(var(--success) / 0.1);
  color: hsl(var(--success));
  border: 1px solid hsl(var(--success) / 0.2);
}

.status-inactive {
  background: hsl(var(--destructive) / 0.1);
  color: hsl(var(--destructive));
  border: 1px solid hsl(var(--destructive) / 0.2);
}

.customer-actions {
  display: flex;
  gap: 0.25rem;
  justify-content: flex-start;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border: 1px solid hsl(var(--border));
  background: hsl(var(--background));
  cursor: pointer;
  border-radius: var(--radius);
  color: hsl(var(--foreground));
  transition: all 0.2s ease;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
}

.action-btn:hover {
  background: hsl(var(--primary));
  border-color: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.action-btn:focus {
  outline: 2px solid hsl(var(--primary));
  outline-offset: 2px;
}

.action-text {
  white-space: nowrap;
}

@media (max-width: 768px) {
  .action-text {
    display: none;
  }
  
  .action-btn {
    padding: 0.5rem;
    min-width: 40px;
    justify-content: center;
  }
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: hsl(var(--muted-foreground));
}

.empty-icon {
  width: 5rem;
  height: 5rem;
  margin: 0 auto 1.5rem;
  background: hsl(var(--muted));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-top: 2rem;
}

.pagination a,
.pagination span {
  padding: 0.5rem 0.75rem;
  border: 1px solid hsl(var(--border));
  border-radius: var(--radius);
  text-decoration: none;
  color: hsl(var(--foreground));
  background: hsl(var(--card));
  transition: all 0.2s ease;
}

.pagination a:hover {
  background: hsl(var(--muted));
  border-color: hsl(var(--primary));
}

.pagination .current {
  background: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  border-color: hsl(var(--primary));
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .kpi-grid {
    grid-template-columns: 1fr;
  }
  
  .controls-row {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-form {
    max-width: none;
  }
  
  .table-container {
    overflow-x: auto;
  }
  
  .customers-table {
    min-width: 600px;
  }
}
</style>

<div class="wrap mobooking-customers-dashboard">
    <div class="page-header">
        <h1 class="page-title"><?php esc_html_e('Customers', 'mobooking'); ?></h1>
        <a href="#" id="mobooking-add-customer-btn" class="add-btn">
            <span>➕</span>
            <?php esc_html_e('Add Customer', 'mobooking'); ?>
        </a>
    </div>

    <!-- Debug Info (remove in production) -->
    <?php if (WP_DEBUG): ?>
    <div class="debug-info">
        <strong>Debug Info:</strong><br>
        Current User ID: <?php echo $current_user_id; ?><br>
        Tenant ID: <?php echo $tenant_id; ?><br>
        Customers Found: <?php echo count($customers); ?><br>
        Total Count: <?php echo $total_customers_count; ?><br>
        Search Term: "<?php echo esc_html($search); ?>"<br>
        Sort: <?php echo $sort_by . ' ' . $sort_order; ?>
    </div>
    <?php endif; ?>

    <!-- Feedback Messages -->
    <div id="mobooking-customers-feedback" class="notice" style="display:none;">
        <p></p>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-header">
                <div class="kpi-title"><?php esc_html_e('Total Customers', 'mobooking'); ?></div>
                <div class="kpi-icon">👥</div>
            </div>
            <div class="kpi-value"><?php echo esc_html($kpi_data['total_customers']); ?></div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <div class="kpi-title"><?php esc_html_e('New This Month', 'mobooking'); ?></div>
                <div class="kpi-icon">✨</div>
            </div>
            <div class="kpi-value"><?php echo esc_html($kpi_data['new_customers_month']); ?></div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <div class="kpi-title"><?php esc_html_e('Active Customers', 'mobooking'); ?></div>
                <div class="kpi-icon">🟢</div>
            </div>
            <div class="kpi-value"><?php echo esc_html($kpi_data['active_customers']); ?></div>
        </div>

        <div class="kpi-card">
            <div class="kpi-header">
                <div class="kpi-title"><?php esc_html_e('Avg. Order Value', 'mobooking'); ?></div>
                <div class="kpi-icon">💰</div>
            </div>
            <div class="kpi-value"><?php echo esc_html($currency_symbol . number_format($kpi_data['avg_order_value'], 2)); ?></div>
        </div>
    </div>

    <!-- Search and Controls -->
    <div class="controls-section">
        <div class="controls-row">
            <form method="get" action="" class="search-form">
                <input type="hidden" name="page" value="mobooking-customers">
                <?php foreach ($_GET as $key => $value): ?>
                    <?php if ($key !== 's' && $key !== 'page'): ?>
                        <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <input 
                    type="text" 
                    name="s" 
                    class="search-input" 
                    placeholder="<?php esc_attr_e('Search customers by name, email, or phone...', 'mobooking'); ?>"
                    value="<?php echo esc_attr($search); ?>"
                >
                <button type="submit" class="search-btn">
                    🔍 <?php esc_html_e('Search', 'mobooking'); ?>
                </button>
            </form>
            
            <div style="font-size: 0.875rem; color: hsl(var(--muted-foreground));">
                <?php printf(__('Showing %d of %d customers', 'mobooking'), count($customers), $total_customers_count); ?>
            </div>
        </div>
    </div>

    <!-- Customers Table -->
    <div class="table-container">
        <?php if (!empty($customers)) : ?>
            <table class="customers-table">
                <thead>
                    <tr>
                        <?php
                        $columns = [
                            'full_name' => __('Name', 'mobooking'),
                            'email' => __('Email', 'mobooking'),
                            'phone_number' => __('Phone', 'mobooking'),
                            'total_bookings' => __('Bookings', 'mobooking'),
                            'last_booking_date' => __('Last Booking', 'mobooking'),
                            'total_spent' => __('Total Spent', 'mobooking'),
                            'status' => __('Status', 'mobooking'),
                            'actions' => __('Actions', 'mobooking')
                        ];
                        
                        foreach ($columns as $column_key => $column_title) {
                            $is_sortable = in_array($column_key, ['full_name', 'email', 'total_bookings', 'last_booking_date', 'total_spent']);
                            
                            if ($is_sortable) {
                                $order_class = '';
                                $new_order = 'ASC';
                                
                                if ($sort_by === $column_key) {
                                    $order_class = strtolower($sort_order);
                                    $new_order = $sort_order === 'ASC' ? 'DESC' : 'ASC';
                                }
                                
                                $url = add_query_arg([
                                    'orderby' => $column_key,
                                    'order' => $new_order,
                                    's' => $search,
                                    'paged' => 1 // Reset to first page when sorting
                                ]);
                                
                                echo "<th class='sortable {$order_class}'>";
                                echo "<a href='" . esc_url($url) . "'>";
                                echo esc_html($column_title);
                                echo "<span class='sort-arrow'>" . ($order_class === 'asc' ? '↑' : ($order_class === 'desc' ? '↓' : '↕')) . "</span>";
                                echo "</a>";
                                echo "</th>";
                            } else {
                                echo "<th>" . esc_html($column_title) . "</th>";
                            }
                        }
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($customer->full_name ?: 'Unknown'); ?></strong>
                            </td>
                            <td>
                                <?php echo esc_html($customer->email); ?>
                            </td>
                            <td>
                                <?php 
                                $phone = isset($customer->phone_number) ? $customer->phone_number : '';
                                if (!empty($phone)) {
                                    echo esc_html($phone);
                                } else {
                                    echo '<span style="color: hsl(var(--muted-foreground));">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <span style="font-weight: 600;">
                                    <?php 
                                    $total_bookings = isset($customer->total_bookings) ? intval($customer->total_bookings) : 0;
                                    echo esc_html($total_bookings); 
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $last_booking = isset($customer->last_booking_date) ? $customer->last_booking_date : '';
                                if (!empty($last_booking)) {
                                    $date = date('M j, Y', strtotime($last_booking));
                                    echo '<span style="color: hsl(var(--foreground));">' . esc_html($date) . '</span>';
                                } else {
                                    echo '<span style="color: hsl(var(--muted-foreground));">-</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: hsl(var(--success));">
                                    <?php 
                                    $total_spent = isset($customer->total_spent) ? floatval($customer->total_spent) : 0;
                                    echo esc_html($currency_symbol . number_format($total_spent, 2)); 
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr(isset($customer->status) ? $customer->status : 'active'); ?>">
                                    <?php 
                                    $status = isset($customer->status) ? $customer->status : 'active';
                                    echo esc_html(ucfirst($status)); 
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="customer-actions">
<a 
    href="<?php echo esc_url(home_url('/dashboard/customer-details/?customer_id=' . urlencode($customer->id))); ?>"
    class="action-btn view-btn" 
    title="<?php esc_attr_e('View Customer Details', 'mobooking'); ?>"
>
    <span style="font-size: 14px;">👁️</span>
    <span class="action-text"><?php esc_html_e('View Details', 'mobooking'); ?></span>
</a>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="empty-state">
                <div class="empty-icon">👥</div>
                <h3 style="margin: 0 0 1rem 0; color: hsl(var(--foreground));">
                    <?php 
                    if (!empty($search)) {
                        esc_html_e('No customers found matching your search', 'mobooking');
                    } else {
                        esc_html_e('No customers found', 'mobooking'); 
                    }
                    ?>
                </h3>
                <p style="margin: 0 0 1.5rem 0;">
                    <?php 
                    if (!empty($search)) {
                        printf(__('Try adjusting your search terms or <a href="%s">view all customers</a>.', 'mobooking'), 
                               esc_url(remove_query_arg('s')));
                    } else {
                        esc_html_e('Customers will appear here once you start taking bookings.', 'mobooking');
                    }
                    ?>
                </p>
                <?php if (empty($search)): ?>
                <button class="add-btn" onclick="document.getElementById('mobooking-add-customer-btn').click()">
                    ➕ <?php esc_html_e('Add Your First Customer', 'mobooking'); ?>
                </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1) : ?>
        <div class="pagination">
            <?php
            $pagination_args = [
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'current' => $page,
                'total' => $total_pages,
                'prev_text' => '← ' . __('Previous', 'mobooking'),
                'next_text' => __('Next', 'mobooking') . ' →',
                'show_all' => false,
                'end_size' => 1,
                'mid_size' => 2,
                'type' => 'array'
            ];
            
            $pagination_links = paginate_links($pagination_args);
            
            if ($pagination_links) {
                foreach ($pagination_links as $link) {
                    echo $link;
                }
            }
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- Customer Details Modal (enhanced) -->
<div id="customer-details-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 10000; backdrop-filter: blur(4px);">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: hsl(var(--background)); padding: 2rem; border-radius: var(--radius); max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; border: 1px solid hsl(var(--border)); box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; border-bottom: 1px solid hsl(var(--border)); padding-bottom: 1rem;">
            <h2 style="margin: 0; color: hsl(var(--foreground)); font-size: 1.5rem; font-weight: 700;">Customer Details</h2>
            <button onclick="closeCustomerModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: hsl(var(--muted-foreground)); width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.2s;" onmouseover="this.style.background='hsl(var(--muted))'" onmouseout="this.style.background='none'">×</button>
        </div>
        <div id="customer-details-content">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<style>
/* Add spinner animation for loading states */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Enhance modal styling */
#customer-details-modal .status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

#customer-details-modal .status-active {
    background: hsl(var(--success) / 0.1);
    color: hsl(var(--success));
    border: 1px solid hsl(var(--success) / 0.2);
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add customer button functionality
    $('#mobooking-add-customer-btn').on('click', function(e) {
        e.preventDefault();
        
        // For now, show an alert. You can replace this with your add customer functionality
        alert('<?php esc_js(__("Add customer functionality will be implemented here. You can create a modal form or redirect to an add customer page.", "mobooking")); ?>');
        
        // Example: Redirect to add customer page
        // window.location.href = '<?php echo esc_url(admin_url("admin.php?page=mobooking-add-customer")); ?>';
        
        // Example: Open modal
        // openAddCustomerModal();
    });
    
    // Auto-hide feedback messages
    setTimeout(function() {
        $('.notice').fadeOut();
    }, 5000);
    
    // Remove hover effects from table to prevent clickable appearance
    $('.customers-table tbody tr').css('cursor', 'default');
    
    // Enhance action buttons with better hover effects
    $('.action-btn').hover(
        function() {
            $(this).css('transform', 'translateY(-1px)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
        }
    );
});
</script>