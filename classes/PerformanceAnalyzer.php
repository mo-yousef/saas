<?php
/**
 * Performance Analysis Utility
 * Analyzes database queries, page load times, and identifies bottlenecks
 * 
 * @package NORDBOOKING\Classes
 */
namespace NORDBOOKING\Classes;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class PerformanceAnalyzer {
    
    private $query_log = [];
    private $start_time;
    private $memory_start;
    
    public function __construct() {
        $this->start_time = microtime(true);
        $this->memory_start = memory_get_usage();
        
        // Hook into WordPress query logging
        add_filter('query', [$this, 'log_query']);
    }
    
    /**
     * Log database queries for analysis
     */
    public function log_query($query) {
        $start_time = microtime(true);
        
        // Execute the query and measure time
        global $wpdb;
        $result = $wpdb->get_results($query);
        
        $execution_time = microtime(true) - $start_time;
        
        $this->query_log[] = [
            'query' => $query,
            'execution_time' => $execution_time,
            'rows_affected' => $wpdb->num_rows,
            'timestamp' => time(),
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];
        
        return $query;
    }
    
    /**
     * Analyze dashboard page performance
     */
    public function analyzeDashboardPerformance() {
        $analysis = [
            'page_load_time' => microtime(true) - $this->start_time,
            'memory_usage' => memory_get_usage() - $this->memory_start,
            'peak_memory' => memory_get_peak_usage(),
            'query_count' => count($this->query_log),
            'slow_queries' => [],
            'recommendations' => []
        ];
        
        // Identify slow queries (>100ms)
        foreach ($this->query_log as $query_data) {
            if ($query_data['execution_time'] > 0.1) {
                $analysis['slow_queries'][] = [
                    'query' => substr($query_data['query'], 0, 200) . '...',
                    'time' => $query_data['execution_time'],
                    'rows' => $query_data['rows_affected']
                ];
            }
        }
        
        // Generate recommendations
        if ($analysis['page_load_time'] > 2.0) {
            $analysis['recommendations'][] = 'Page load time exceeds 2 seconds - optimization needed';
        }
        
        if ($analysis['query_count'] > 50) {
            $analysis['recommendations'][] = 'High query count (' . $analysis['query_count'] . ') - consider query optimization';
        }
        
        if (count($analysis['slow_queries']) > 0) {
            $analysis['recommendations'][] = count($analysis['slow_queries']) . ' slow queries detected - add indexes or optimize';
        }
        
        if ($analysis['memory_usage'] > 50 * 1024 * 1024) { // 50MB
            $analysis['recommendations'][] = 'High memory usage - optimize data loading';
        }
        
        return $analysis;
    }
    
    /**
     * Analyze specific database tables for optimization opportunities
     */
    public function analyzeTablePerformance() {
        global $wpdb;
        
        $tables = [
            'bookings' => $wpdb->prefix . 'nordbooking_bookings',
            'services' => $wpdb->prefix . 'nordbooking_services',
            'customers' => $wpdb->prefix . 'nordbooking_customers',
            'booking_items' => $wpdb->prefix . 'nordbooking_booking_items'
        ];
        
        $analysis = [];
        
        foreach ($tables as $name => $table) {
            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                continue;
            }
            
            // Get table stats
            $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            $table_size = $wpdb->get_var("SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size' FROM information_schema.TABLES WHERE table_schema = DATABASE() AND table_name = '$table'");
            
            // Get index information
            $indexes = $wpdb->get_results("SHOW INDEX FROM $table");
            
            // Analyze for missing indexes
            $missing_indexes = $this->suggestMissingIndexes($name, $table);
            
            $analysis[$name] = [
                'table_name' => $table,
                'row_count' => $row_count,
                'size_mb' => $table_size,
                'index_count' => count($indexes),
                'indexes' => $indexes,
                'missing_indexes' => $missing_indexes,
                'recommendations' => []
            ];
            
            // Generate recommendations
            if ($row_count > 1000 && count($missing_indexes) > 0) {
                $analysis[$name]['recommendations'][] = 'Add missing indexes for better performance';
            }
            
            if ($table_size > 100) {
                $analysis[$name]['recommendations'][] = 'Large table size - consider archiving old data';
            }
        }
        
        return $analysis;
    }
    
    /**
     * Suggest missing indexes based on common query patterns
     */
    private function suggestMissingIndexes($table_name, $table) {
        global $wpdb;
        
        $suggestions = [];
        
        // Get existing indexes
        $existing_indexes = $wpdb->get_results("SHOW INDEX FROM $table");
        $existing_columns = [];
        foreach ($existing_indexes as $index) {
            $existing_columns[] = $index->Column_name;
        }
        
        // Common index patterns by table
        $recommended_indexes = [
            'bookings' => [
                ['user_id', 'status', 'booking_date'],
                ['customer_email', 'booking_date'],
                ['status', 'created_at'],
                ['booking_date', 'booking_time']
            ],
            'services' => [
                ['user_id', 'status', 'sort_order'],
                ['status']
            ],
            'customers' => [
                ['tenant_id', 'status', 'last_activity_at'],
                ['tenant_id', 'email']
            ],
            'booking_items' => [
                ['booking_id', 'service_id']
            ]
        ];
        
        if (isset($recommended_indexes[$table_name])) {
            foreach ($recommended_indexes[$table_name] as $index_columns) {
                $missing_columns = array_diff($index_columns, $existing_columns);
                if (empty($missing_columns)) {
                    continue; // Index already exists
                }
                
                $suggestions[] = [
                    'columns' => $index_columns,
                    'sql' => "ALTER TABLE $table ADD INDEX idx_" . implode('_', $index_columns) . " (" . implode(', ', $index_columns) . ")"
                ];
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Generate comprehensive performance report
     */
    public function generatePerformanceReport() {
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'dashboard_performance' => $this->analyzeDashboardPerformance(),
            'table_analysis' => $this->analyzeTablePerformance(),
            'query_log' => $this->query_log,
            'system_info' => [
                'php_version' => PHP_VERSION,
                'wordpress_version' => get_bloginfo('version'),
                'theme_version' => wp_get_theme()->get('Version'),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ]
        ];
    }
    
    /**
     * Get quick performance metrics
     */
    public function getQuickMetrics() {
        return [
            'page_load_time' => round(microtime(true) - $this->start_time, 3),
            'memory_usage' => round((memory_get_usage() - $this->memory_start) / 1024 / 1024, 2),
            'query_count' => count($this->query_log),
            'slow_query_count' => count(array_filter($this->query_log, function($q) { 
                return $q['execution_time'] > 0.1; 
            }))
        ];
    }
}