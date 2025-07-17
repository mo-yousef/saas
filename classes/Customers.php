<?php
/**
 * Class Customers
 * Handles customer data management for the MoBooking plugin.
 *
 * @package MoBooking\Classes
 */
namespace MoBooking\Classes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Customers {

    private $db;
    private $table_name;
    private $bookings_table_name; // For future use to calculate total_bookings, last_booking_date

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->table_name = Database::get_table_name('mob_customers');
        $this->bookings_table_name = Database::get_table_name('bookings'); // Initialize for future use
    }

    /**
     * Registers AJAX actions for customer management.
     */
    public function register_ajax_actions() {
        add_action('wp_ajax_mobooking_get_customers', [$this, 'ajax_get_customers']);
        // Add other AJAX actions here as needed (e.g., update_customer_status, add_note)
    }

    /**
     * AJAX handler for fetching customers.
     */
    public function ajax_get_customers() {
        check_ajax_referer('mobooking_dashboard_nonce', 'nonce');

        $current_user_id = get_current_user_id();
        if ( ! Auth::is_user_business_owner_or_worker($current_user_id) ) {
            wp_send_json_error(['message' => __('Access denied.', 'mobooking')], 403);
            return;
        }

        $tenant_id = Auth::get_effective_tenant_id_for_user($current_user_id);
        if ( ! $tenant_id ) {
            wp_send_json_error(['message' => __('Could not determine tenant ID.', 'mobooking')], 400);
            return;
        }

        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 20;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $status_filter = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $sort_by = isset($_POST['sort_by']) ? sanitize_key($_POST['sort_by']) : 'full_name';
        $sort_order = isset($_POST['sort_order']) ? strtoupper(sanitize_key($_POST['sort_order'])) : 'ASC';

        // Validate sort_by and sort_order
        $valid_sort_columns = ['full_name', 'email', 'phone_number', 'total_bookings', 'last_booking_date', 'status', 'created_at'];
        if (!in_array($sort_by, $valid_sort_columns)) {
            $sort_by = 'full_name';
        }
        if (!in_array($sort_order, ['ASC', 'DESC'])) {
            $sort_order = 'ASC';
        }

        $args = [
            'page' => $page,
            'per_page' => $per_page,
            'search' => $search,
            'status' => $status_filter,
            'orderby' => $sort_by,
            'order' => $sort_order,
        ];

        $customers = $this->get_customers_by_tenant_id($tenant_id, $args);
        $total_customers = $this->get_customer_count_by_tenant_id($tenant_id, $args);

        if (is_wp_error($customers)) {
            wp_send_json_error(['message' => $customers->get_error_message()], 500);
        } else {
            wp_send_json_success([
                'customers' => $customers,
                'pagination' => [
                    'total_items' => $total_customers,
                    'total_pages' => ceil($total_customers / $per_page),
                    'current_page' => $page,
                    'per_page' => $per_page,
                ]
            ]);
        }
    }

    /**
     * Get customers for a specific tenant with pagination, search, and sorting.
     *
     * @param int   $tenant_id The ID of the business owner.
     * @param array $args      Arguments for filtering, pagination, sorting.
     *                         - page (int) Current page number.
     *                         - per_page (int) Items per page.
     *                         - search (string) Search term for name or email.
     *                         - status (string) Filter by customer status.
     *                         - orderby (string) Column to sort by.
     *                         - order (string) Sort order (ASC or DESC).
     * @return array|WP_Error Array of customer objects or WP_Error on failure.
     */
    public function get_customers_by_tenant_id($tenant_id, $args = []) {
        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'search' => '',
            'status' => '',
            'orderby' => 'full_name', // Default sort column
            'order' => 'ASC',       // Default sort order
        ];
        $args = wp_parse_args($args, $defaults);

        $offset = ($args['page'] - 1) * $args['per_page'];

        $sql = "SELECT * FROM {$this->table_name}";
        $where_clauses = ["tenant_id = %d"];
        $sql_params = [$tenant_id];

        if (!empty($args['search'])) {
            $search_term = '%' . $this->db->esc_like($args['search']) . '%';
            $where_clauses[] = "(full_name LIKE %s OR email LIKE %s OR phone_number LIKE %s)";
            $sql_params[] = $search_term;
            $sql_params[] = $search_term;
            $sql_params[] = $search_term;
        }

        if (!empty($args['status'])) {
            $where_clauses[] = "status = %s";
            $sql_params[] = $args['status'];
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        // Validate orderby column to prevent SQL injection
        $allowed_orderby_columns = ['id', 'full_name', 'email', 'phone_number', 'status', 'total_bookings', 'last_booking_date', 'created_at', 'last_activity_at'];
        if (in_array($args['orderby'], $allowed_orderby_columns)) {
            $sql .= " ORDER BY " . esc_sql($args['orderby']); // esc_sql for column names is generally safe if validated against a whitelist
            $sql .= (strtoupper($args['order']) === 'DESC') ? " DESC" : " ASC";
        } else {
            $sql .= " ORDER BY full_name ASC"; // Default sort
        }

        $sql .= " LIMIT %d OFFSET %d";
        $sql_params[] = $args['per_page'];
        $sql_params[] = $offset;

        $prepared_sql = $this->db->prepare($sql, $sql_params);
        $results = $this->db->get_results($prepared_sql);

        if ($this->db->last_error) {
            error_log("MoBooking DB Error (get_customers_by_tenant_id): " . $this->db->last_error);
            return new \WP_Error('db_error', __('Error fetching customers.', 'mobooking'));
        }

        // TODO: Future enhancement - Calculate total_bookings and last_booking_date
        // This might involve a JOIN or separate queries for performance.
        // For now, these fields will be whatever is in the mob_customers table.

        return $results;
    }

    /**
     * Get the total count of customers for a specific tenant, considering filters.
     *
     * @param int   $tenant_id The ID of the business owner.
     * @param array $args      Arguments for filtering (search, status).
     * @return int|WP_Error Total number of customers or WP_Error on failure.
     */
    public function get_customer_count_by_tenant_id($tenant_id, $args = []) {
        $defaults = [
            'search' => '',
            'status' => '',
        ];
        $args = wp_parse_args($args, $defaults);

        $sql = "SELECT COUNT(*) FROM {$this->table_name}";
        $where_clauses = ["tenant_id = %d"];
        $sql_params = [$tenant_id];

        if (!empty($args['search'])) {
            $search_term = '%' . $this->db->esc_like($args['search']) . '%';
            $where_clauses[] = "(full_name LIKE %s OR email LIKE %s OR phone_number LIKE %s)";
            $sql_params[] = $search_term;
            $sql_params[] = $search_term;
            $sql_params[] = $search_term;
        }

        if (!empty($args['status'])) {
            $where_clauses[] = "status = %s";
            $sql_params[] = $args['status'];
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $prepared_sql = $this->db->prepare($sql, $sql_params);
        $count = $this->db->get_var($prepared_sql);

        if ($this->db->last_error) {
            error_log("MoBooking DB Error (get_customer_count_by_tenant_id): " . $this->db->last_error);
            return new \WP_Error('db_error', __('Error counting customers.', 'mobooking'));
        }

        return absint($count);
    }

    /**
     * Creates a new customer or updates an existing one based on email for a given tenant.
     * This is useful when a booking is made by a new or existing customer.
     *
     * @param int   $tenant_id      The ID of the business owner.
     * @param array $customer_data  Associative array of customer data.
     *                              Expected keys: 'full_name', 'email', 'phone_number',
     *                              'address_line_1', 'address_line_2', 'city', 'state', 'zip_code', 'country'.
     *                              Optional: 'wp_user_id' if the customer is a WP user.
     * @return int|WP_Error The customer ID (new or existing) or WP_Error on failure.
     */
    public function create_or_update_customer_for_booking($tenant_id, $customer_data) {
        if (empty($tenant_id) || empty($customer_data['email']) || empty($customer_data['full_name'])) {
            return new \WP_Error('missing_data', __('Tenant ID, customer email, and full name are required.', 'mobooking'));
        }

        $email = sanitize_email($customer_data['email']);
        $existing_customer = $this->db->get_row(
            $this->db->prepare(
                "SELECT id FROM {$this->table_name} WHERE tenant_id = %d AND email = %s",
                $tenant_id,
                $email
            )
        );

        $data_to_save = [
            'tenant_id' => $tenant_id,
            'full_name' => sanitize_text_field($customer_data['full_name']),
            'email' => $email,
            'phone_number' => isset($customer_data['phone_number']) ? sanitize_text_field($customer_data['phone_number']) : null,
            'address_line_1' => isset($customer_data['address_line_1']) ? sanitize_text_field($customer_data['address_line_1']) : null,
            'address_line_2' => isset($customer_data['address_line_2']) ? sanitize_text_field($customer_data['address_line_2']) : null,
            'city' => isset($customer_data['city']) ? sanitize_text_field($customer_data['city']) : null,
            'state' => isset($customer_data['state']) ? sanitize_text_field($customer_data['state']) : null,
            'zip_code' => isset($customer_data['zip_code']) ? sanitize_text_field($customer_data['zip_code']) : null,
            'country' => isset($customer_data['country']) ? sanitize_text_field($customer_data['country']) : null,
            'last_activity_at' => current_time('mysql', 1), // GMT
        ];
        $data_format = ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

        if (isset($customer_data['wp_user_id']) && intval($customer_data['wp_user_id']) > 0) {
            $data_to_save['wp_user_id'] = intval($customer_data['wp_user_id']);
            $data_format[] = '%d';
        }


        if ($existing_customer) {
            // Update existing customer
            $this->db->update($this->table_name, $data_to_save, ['id' => $existing_customer->id], $data_format, ['%d']);
            if ($this->db->last_error) {
                error_log("MoBooking DB Error (update_customer): " . $this->db->last_error);
                return new \WP_Error('db_error', __('Error updating customer.', 'mobooking'));
            }
            return $existing_customer->id;
        } else {
            // Create new customer
            // Add default status and created_at for new entries
            $data_to_save['status'] = 'active';
            $data_format[] = '%s';
            // created_at is handled by DB default

            $this->db->insert($this->table_name, $data_to_save, $data_format);
            if ($this->db->last_error) {
                error_log("MoBooking DB Error (insert_customer): " . $this->db->last_error);
                return new \WP_Error('db_error', __('Error creating customer.', 'mobooking'));
            }
            return $this->db->insert_id;
        }
    }

    /**
     * Updates customer's booking stats after a new booking.
     *
     * @param int $customer_id The ID of the customer in mob_customers table.
     * @param string $booking_date The date of the new booking (Y-m-d H:i:s).
     * @return bool True on success, false on failure.
     */
    public function update_customer_booking_stats($customer_id, $booking_date) {
        $customer_id = absint($customer_id);
        if (!$customer_id) {
            return false;
        }

        $result = $this->db->query(
            $this->db->prepare(
                "UPDATE {$this->table_name}
                 SET total_bookings = total_bookings + 1,
                     last_booking_date = %s,
                     last_activity_at = %s
                 WHERE id = %d",
                $booking_date,
                current_time('mysql', 1), // GMT
                $customer_id
            )
        );

        if ($result === false) {
            error_log("MoBooking DB Error (update_customer_booking_stats): " . $this->db->last_error);
            return false;
        }
        return true;
    }

    public function get_customer_by_id( $customer_id ) {
        $customer_id = absint( $customer_id );
        if ( ! $customer_id ) {
            return null;
        }

        $customer = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $customer_id
            )
        );

        if ( $customer ) {
            $customer->booking_overview = $this->get_booking_overview( $customer_id );
        }

        return $customer;
    }

    public function get_booking_overview( $customer_id ) {
        $customer_id = absint( $customer_id );
        if ( ! $customer_id ) {
            return null;
        }

        $bookings_table = Database::get_table_name('bookings');

        $query = $this->db->prepare(
            "SELECT
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
                SUM(total_price) as total_spent,
                AVG(total_price) as average_booking_value
             FROM {$bookings_table}
             WHERE customer_id = %d",
            $customer_id
        );

        return $this->db->get_row( $query );
    }

    public function get_kpi_data($tenant_id) {
        $tenant_id = absint($tenant_id);
        if (!$tenant_id) {
            return [
                'total_customers' => 0,
                'new_customers_month' => 0,
                'active_customers' => 0,
            ];
        }

        // Total Customers
        $total_customers = $this->get_customer_count_by_tenant_id($tenant_id);

        // New Customers This Month
        $current_month_start = date('Y-m-01 00:00:00');
        $new_customers_month = $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE tenant_id = %d AND created_at >= %s",
                $tenant_id,
                $current_month_start
            )
        );

        // Active Customers
        $active_customers = $this->get_customer_count_by_tenant_id($tenant_id, ['status' => 'active']);

        return [
            'total_customers' => is_wp_error($total_customers) ? 0 : $total_customers,
            'new_customers_month' => $new_customers_month ? absint($new_customers_month) : 0,
            'active_customers' => is_wp_error($active_customers) ? 0 : $active_customers,
        ];
    }


/**
 * Get customer insights for dashboard
 * Add this method to the Customers class
 */
public function get_customer_insights($tenant_id) {
    $customers_table = Database::get_table_name('mob_customers');
    $bookings_table = Database::get_table_name('bookings');
    
    // Get new customers this month
    $new_customers_month = $this->db->get_var(
        $this->db->prepare(
            "SELECT COUNT(*) FROM {$customers_table} 
             WHERE tenant_id = %d 
             AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)",
            $tenant_id
        )
    );
    
    // Get returning customers (customers with more than 1 booking)
    $returning_customers = $this->db->get_var(
        $this->db->prepare(
            "SELECT COUNT(DISTINCT customer_email) FROM {$bookings_table} 
             WHERE user_id = %d 
             AND customer_email IN (
                 SELECT customer_email FROM {$bookings_table} 
                 WHERE user_id = %d 
                 GROUP BY customer_email 
                 HAVING COUNT(*) > 1
             )",
            $tenant_id, $tenant_id
        )
    );
    
    // Get total customers
    $total_customers = $this->db->get_var(
        $this->db->prepare(
            "SELECT COUNT(DISTINCT customer_email) FROM {$bookings_table} WHERE user_id = %d",
            $tenant_id
        )
    );
    
    // Calculate retention rate
    $retention_rate = 0;
    if ($total_customers > 0) {
        $retention_rate = round(($returning_customers / $total_customers) * 100, 1);
    }
    
    return [
        'new_customers' => intval($new_customers_month ?: 0),
        'returning_customers' => intval($returning_customers ?: 0),
        'total_customers' => intval($total_customers ?: 0),
        'retention_rate' => $retention_rate
    ];
}

}

// Initialize and register AJAX actions (if not already handled by a central manager)
// This is typically done once, for example, in functions.php or a plugin loader.
// For now, let's assume it will be instantiated and its register_ajax_actions method called
// similarly to other manager classes (e.g., Services, Bookings).
// if (class_exists('MoBooking\Classes\Customers')) {
//    $mobooking_customers_manager = new \MoBooking\Classes\Customers();
//    $mobooking_customers_manager->register_ajax_actions();
// }

?>
