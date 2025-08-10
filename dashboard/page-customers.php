<?php
/**
 * Dashboard Page: Customers
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

$customers_manager = new \MoBooking\Classes\Customers();

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
$customers = $customers_manager->get_customers_by_tenant_id($tenant_id, $args);
$total_customers_count = $customers_manager->get_customer_count_by_tenant_id($tenant_id, $args);
$kpi_data = $customers_manager->get_kpi_data($tenant_id);


// Currency symbol
$currency_symbol = get_option('mobooking_currency_symbol', '$');

// Pagination
$total_pages = ceil($total_customers_count / $per_page);
?>


<div class="wrap mobooking-customers-dashboard">
    <div class="mobooking-page-header">
        <div class="mobooking-page-header-heading">
            <span class="mobooking-page-header-icon">
                <?php echo mobooking_get_dashboard_menu_icon('clients'); ?>
            </span>
            <h1 class="page-title"><?php esc_html_e('Customers', 'mobooking'); ?></h1>
        </div>
        <a href="#" id="mobooking-add-customer-btn" class="button button-primary">
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
            <div class="kpi-value"><?php echo esc_html($currency_symbol . number_format($kpi_data['avg_order_value'] ?? 0, 2)); ?></div>
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