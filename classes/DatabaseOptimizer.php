<?php
/**
 * Database Optimization Utility
 * Handles database performance optimizations, index management, and query optimization
 * 
 * @package NORDBOOKING\Classes
 */
namespace NORDBOOKING\Classes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class DatabaseOptimizer {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Apply comprehensive database optimizations
     */
    public function applyOptimizations() {
        $results = [
            'indexes_added' => [],
            'indexes_failed' => [],
            'queries_optimized' => [],
            'cache_enabled' => false,
            'summary' => []
        ];
        
        // Add missing indexes
        $index_results = $this->addMissingIndexes();
        $results['indexes_added'] = $index_results['added'];
        $results['indexes_failed'] = $index_results['failed'];
        
        // Optimize table structure
        $this->optimizeTableStructure();
        
        // Enable query caching
        $results['cache_enabled'] = $this->enableQueryCaching();
        
        // Update optimization timestamp
        update_option('nordbooking_last_optimization', time());
        
        $results['summary'][] = count($results['indexes_added']) . ' indexes added successfully';
        if (!empty($results['indexes_failed'])) {
            $results['summary'][] = count($results['indexes_failed']) . ' indexes failed to add';
        }
        
        return $results;
    }
    
    /**
     * Add missing indexes to improve query performance
     */
    public function addMissingIndexes() {
        $results = ['added' => [], 'failed' => []];
        
        // Define critical indexes for each table
        $indexes = [
            // Bookings table - most critical for performance
            $this->wpdb->prefix . 'nordbooking_bookings' => [
                'idx_user_status_date' => 'ADD INDEX idx_user_status_date (user_id, status, booking_date)',
                'idx_customer_email_date' => 'ADD INDEX idx_customer_email_date (customer_email, booking_date)',
                'idx_status_created' => 'ADD INDEX idx_status_created (status, created_at)',
                'idx_booking_date_time' => 'ADD INDEX idx_booking_date_time (booking_date, booking_time)',
                'idx_user_date_status' => 'ADD INDEX idx_user_date_status (user_id, booking_date, status)',
                'idx_assigned_staff' => 'ADD INDEX idx_assigned_staff (assigned_staff_id, status)',
                'idx_payment_status' => 'ADD INDEX idx_payment_status (payment_status, created_at)'
            ],
            
            // Services table
            $this->wpdb->prefix . 'nordbooking_services' => [
                'idx_user_status_sort' => 'ADD INDEX idx_user_status_sort (user_id, status, sort_order)',
                'idx_status_active' => 'ADD INDEX idx_status_active (status)',
                'idx_user_active_sort' => 'ADD INDEX idx_user_active_sort (user_id, status, sort_order) WHERE status = "active"'
            ],
            
            // Service options table
            $this->wpdb->prefix . 'nordbooking_service_options' => [
                'idx_service_user' => 'ADD INDEX idx_service_user (service_id, user_id)',
                'idx_service_required' => 'ADD INDEX idx_service_required (service_id, is_required)'
            ],
            
            // Customers table
            $this->wpdb->prefix . 'nordbooking_customers' => [
                'idx_tenant_status_activity' => 'ADD INDEX idx_tenant_status_activity (tenant_id, status, last_activity_at)',
                'idx_tenant_email_unique' => 'ADD INDEX idx_tenant_email_unique (tenant_id, email)',
                'idx_tenant_active' => 'ADD INDEX idx_tenant_active (tenant_id, status) WHERE status = "active"',
                'idx_last_booking' => 'ADD INDEX idx_last_booking (last_booking_date DESC)'
            ],
            
            // Booking items table
            $this->wpdb->prefix . 'nordbooking_booking_items' => [
                'idx_booking_service' => 'ADD INDEX idx_booking_service (booking_id, service_id)',
                'idx_service_bookings' => 'ADD INDEX idx_service_bookings (service_id, booking_id)'
            ],
            
            // Areas table
            $this->wpdb->prefix . 'nordbooking_areas' => [
                'idx_user_type_status' => 'ADD INDEX idx_user_type_status (user_id, area_type, status)',
                'idx_status_active' => 'ADD INDEX idx_status_active (status) WHERE status = "active"'
            ],
            
            // Availability rules table
            $this->wpdb->prefix . 'nordbooking_availability_rules' => [
                'idx_user_day_active' => 'ADD INDEX idx_user_day_active (user_id, day_of_week, is_active)',
                'idx_day_time' => 'ADD INDEX idx_day_time (day_of_week, start_time, end_time)'
            ],
            
            // Tenant settings table
            $this->wpdb->prefix . 'nordbooking_tenant_settings' => [
                'idx_user_setting_lookup' => 'ADD INDEX idx_user_setting_lookup (user_id, setting_name(100))'
            ]
        ];
        
        foreach ($indexes as $table => $table_indexes) {
            // Check if table exists
            if ($this->wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                continue;
            }
            
            // Get existing indexes
            $existing_indexes = $this->wpdb->get_results("SHOW INDEX FROM $table");
            $existing_names = array_column($existing_indexes, 'Key_name');
            
            foreach ($table_indexes as $index_name => $sql) {
                // Skip if index already exists
                if (in_array($index_name, $existing_names)) {
                    continue;
                }
                
                // Try to add the index
                $full_sql = "ALTER TABLE $table $sql";
                $result = $this->wpdb->query($full_sql);
                
                if ($result !== false) {
                    $results['added'][] = [
                        'table' => basename($table),
                        'index' => $index_name,
                        'sql' => $sql
                    ];
                    error_log("[DatabaseOptimizer] Added index $index_name to $table");
                } else {
                    $results['failed'][] = [
                        'table' => basename($table),
                        'index' => $index_name,
                        'error' => $this->wpdb->last_error,
                        'sql' => $sql
                    ];
                    error_log("[DatabaseOptimizer] Failed to add index $index_name to $table: " . $this->wpdb->last_error);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Optimize table structure and analyze tables
     */
    public function optimizeTableStructure() {
        $tables = [
            $this->wpdb->prefix . 'nordbooking_bookings',
            $this->wpdb->prefix . 'nordbooking_services',
            $this->wpdb->prefix . 'nordbooking_customers',
            $this->wpdb->prefix . 'nordbooking_booking_items',
            $this->wpdb->prefix . 'nordbooking_service_options'
        ];
        
        foreach ($tables as $table) {
            if ($this->wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                continue;
            }
            
            // Analyze table for optimization
            $this->wpdb->query("ANALYZE TABLE $table");
            
            // Optimize table structure
            $this->wpdb->query("OPTIMIZE TABLE $table");
            
            error_log("[DatabaseOptimizer] Analyzed and optimized table: $table");
        }
    }
    
    /**
     * Enable query caching and optimization
     */
    public function enableQueryCaching() {
        // Enable WordPress object caching if not already enabled
        if (!wp_using_ext_object_cache()) {
            // Add basic caching recommendations
            error_log("[DatabaseOptimizer] Object caching not enabled - recommend installing Redis or Memcached");
            return false;
        }
        
        // Set query cache variables if possible
        $cache_queries = [
            "SET SESSION query_cache_type = ON",
            "SET SESSION query_cache_size = 67108864" // 64MB
        ];
        
        foreach ($cache_queries as $query) {
            $this->wpdb->query($query);
        }
        
        return true;
    }
    
    /**
     * Analyze slow queries and provide optimization suggestions
     */
    public function analyzeSlowQueries() {
        $analysis = [
            'slow_queries' => [],
            'suggestions' => [],
            'common_patterns' => []
        ];
        
        // Common slow query patterns in NORDBOOKING
        $common_issues = [
            'SELECT * FROM' => 'Use specific column names instead of SELECT *',
            'ORDER BY RAND()' => 'Avoid ORDER BY RAND() - use alternative randomization',
            'LIKE "%term%"' => 'Avoid leading wildcards in LIKE queries - consider full-text search',
            'NOT IN' => 'Consider using LEFT JOIN with IS NULL instead of NOT IN',
            'OR' => 'Consider splitting OR conditions into separate queries with UNION'
        ];
        
        // Get recent slow queries from WordPress query log
        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            global $wpdb;
            if (!empty($wpdb->queries)) {
                foreach ($wpdb->queries as $query_data) {
                    if ($query_data[1] > 0.1) { // Queries taking more than 100ms
                        $analysis['slow_queries'][] = [
                            'query' => $query_data[0],
                            'time' => $query_data[1],
                            'stack' => $query_data[2]
                        ];
                        
                        // Check for common issues
                        foreach ($common_issues as $pattern => $suggestion) {
                            if (stripos($query_data[0], $pattern) !== false) {
                                $analysis['suggestions'][] = $suggestion;
                            }
                        }
                    }
                }
            }
        }
        
        return $analysis;
    }
    
    /**
     * Get database optimization status
     */
    public function getOptimizationStatus() {
        $status = [
            'last_optimization' => get_option('nordbooking_last_optimization', 0),
            'tables_analyzed' => [],
            'missing_indexes' => [],
            'recommendations' => []
        ];
        
        // Check each table for optimization status
        $tables = [
            'bookings' => $this->wpdb->prefix . 'nordbooking_bookings',
            'services' => $this->wpdb->prefix . 'nordbooking_services',
            'customers' => $this->wpdb->prefix . 'nordbooking_customers'
        ];
        
        foreach ($tables as $name => $table) {
            if ($this->wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                continue;
            }
            
            $row_count = $this->wpdb->get_var("SELECT COUNT(*) FROM $table");
            $indexes = $this->wpdb->get_results("SHOW INDEX FROM $table");
            
            $status['tables_analyzed'][$name] = [
                'row_count' => $row_count,
                'index_count' => count($indexes),
                'needs_optimization' => $row_count > 1000 && count($indexes) < 5
            ];
            
            if ($status['tables_analyzed'][$name]['needs_optimization']) {
                $status['recommendations'][] = "Table $name needs additional indexes for better performance";
            }
        }
        
        // Check if optimization is due
        if (time() - $status['last_optimization'] > DAY_IN_SECONDS) {
            $status['recommendations'][] = 'Database optimization is due - run optimization process';
        }
        
        return $status;
    }
    
    /**
     * Create optimized queries for common operations
     */
    public function getOptimizedQueries() {
        return [
            'user_bookings_recent' => "
                SELECT b.*, c.full_name as customer_name 
                FROM {$this->wpdb->prefix}nordbooking_bookings b
                LEFT JOIN {$this->wpdb->prefix}nordbooking_customers c ON b.mob_customer_id = c.id
                WHERE b.user_id = %d 
                AND b.booking_date >= CURDATE() - INTERVAL 30 DAY
                ORDER BY b.booking_date DESC, b.booking_time DESC
                LIMIT %d
            ",
            
            'user_active_services' => "
                SELECT * FROM {$this->wpdb->prefix}nordbooking_services 
                WHERE user_id = %d AND status = 'active'
                ORDER BY sort_order ASC, name ASC
            ",
            
            'customer_booking_history' => "
                SELECT b.*, GROUP_CONCAT(bi.service_name) as services
                FROM {$this->wpdb->prefix}nordbooking_bookings b
                LEFT JOIN {$this->wpdb->prefix}nordbooking_booking_items bi ON b.booking_id = bi.booking_id
                WHERE b.customer_email = %s AND b.user_id = %d
                GROUP BY b.booking_id
                ORDER BY b.booking_date DESC
                LIMIT %d
            ",
            
            'dashboard_stats' => "
                SELECT 
                    COUNT(*) as total_bookings,
                    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
                    COUNT(CASE WHEN booking_date = CURDATE() THEN 1 END) as today_bookings,
                    SUM(total_price) as total_revenue
                FROM {$this->wpdb->prefix}nordbooking_bookings 
                WHERE user_id = %d 
                AND booking_date >= CURDATE() - INTERVAL 30 DAY
            "
        ];
    }
}