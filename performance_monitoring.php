<?php
/**
 * NORDBOOKING Performance Monitoring and Optimization Classes
 * 
 * Add this to your theme or create as a separate plugin
 * 
 * @package NORDBOOKING\Performance
 */

namespace NORDBOOKING\Performance;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Query Performance Profiler
 */
class QueryProfiler {
    private static $queries = [];
    private static $enabled = true;
    
    public static function enable() {
        self::$enabled = true;
    }
    
    public static function disable() {
        self::$enabled = false;
    }
    
    public static function start($query_name, $context = []) {
        if (!self::$enabled) return;
        
        self::$queries[$query_name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'context' => $context,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
        ];
    }
    
    public static function end($query_name, $additional_data = []) {
        if (!self::$enabled || !isset(self::$queries[$query_name])) {
            return;
        }
        
        $data = &self::$queries[$query_name];
        $data['end_time'] = microtime(true);
        $data['duration'] = $data['end_time'] - $data['start_time'];
        $data['memory_used'] = memory_get_usage(true) - $data['start_memory'];
        $data['additional_data'] = $additional_data;
        
        // Log slow queries
        if ($data['duration'] > 1.0) {
            error_log(sprintf(
                "NORDBOOKING SLOW QUERY: %s took %.3fs, used %s memory",
                $query_name,
                $data['duration'],
                self::formatBytes($data['memory_used'])
            ));
        }
        
        // Store in database for analysis if duration > threshold
        if ($data['duration'] > 0.5) {
            self::logSlowQuery($query_name, $data);
        }
    }
    
    public static function getStats() {
        $stats = [
            'total_queries' => count(self::$queries),
            'total_time' => 0,
            'total_memory' => 0,
            'slow_queries' => 0,
            'queries' => []
        ];
        
        foreach (self::$queries as $name => $data) {
            if (isset($data['duration'])) {
                $stats['total_time'] += $data['duration'];
                $stats['total_memory'] += $data['memory_used'];
                
                if ($data['duration'] > 0.1) {
                    $stats['slow_queries']++;
                }
                
                $stats['queries'][$name] = [
                    'duration' => $data['duration'],
                    'memory' => $data['memory_used'],
                    'context' => $data['context']
                ];
            }
        }
        
        return $stats;
    }
    
    private static function logSlowQuery($name, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'nordbooking_query_log';
        
        // Create table if it doesn't exist
        $wpdb->query("
            CREATE TABLE IF NOT EXISTS {$table_name} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                query_name VARCHAR(255) NOT NULL,
                duration DECIMAL(10,6) NOT NULL,
                memory_used BIGINT NOT NULL,
                context TEXT,
                backtrace TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_duration (duration),
                INDEX idx_created (created_at)
            )
        ");
        
        $wpdb->insert($table_name, [
            'query_name' => $name,
            'duration' => $data['duration'],
            'memory_used' => $data['memory_used'],
            'context' => json_encode($data['context']),
            'backtrace' => json_encode($data['backtrace'])
        ]);
    }
    
    private static function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

/**
 * Cache Manager for NORDBOOKING
 */
class CacheManager {
    const CACHE_GROUP = 'nordbooking';
    const DEFAULT_EXPIRATION = 300; // 5 minutes
    
    private static $cache_stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0
    ];
    
    public static function get($key, $default = null) {
        $cache_key = self::buildKey($key);
        $value = wp_cache_get($cache_key, self::CACHE_GROUP);
        
        if (false === $value) {
            self::$cache_stats['misses']++;
            return $default;
        }
        
        self::$cache_stats['hits']++;
        return $value;
    }
    
    public static function set($key, $value, $expiration = self::DEFAULT_EXPIRATION) {
        $cache_key = self::buildKey($key);
        $result = wp_cache_set($cache_key, $value, self::CACHE_GROUP, $expiration);
        
        if ($result) {
            self::$cache_stats['sets']++;
        }
        
        return $result;
    }
    
    public static function delete($key) {
        $cache_key = self::buildKey($key);
        return wp_cache_delete($cache_key, self::CACHE_GROUP);
    }
    
    public static function flush() {
        return wp_cache_flush();
    }
    
    public static function getStats() {
        $total_requests = self::$cache_stats['hits'] + self::$cache_stats['misses'];
        $hit_rate = $total_requests > 0 ? (self::$cache_stats['hits'] / $total_requests) * 100 : 0;
        
        return [
            'hits' => self::$cache_stats['hits'],
            'misses' => self::$cache_stats['misses'],
            'sets' => self::$cache_stats['sets'],
            'hit_rate' => round($hit_rate, 2)
        ];
    }
    
    private static function buildKey($key) {
        return 'nb_' . md5($key);
    }
    
    // Cache invalidation patterns
    public static function invalidateUserCache($user_id) {
        $patterns = [
            "user_bookings_{$user_id}",
            "user_services_{$user_id}",
            "user_stats_{$user_id}",
            "user_customers_{$user_id}"
        ];
        
        foreach ($patterns as $pattern) {
            self::delete($pattern);
        }
    }
    
    public static function invalidateBookingCache($booking_id, $user_id = null) {
        self::delete("booking_{$booking_id}");
        
        if ($user_id) {
            self::invalidateUserCache($user_id);
        }
    }
}

/**
 * Database Health Monitor
 */
class DatabaseHealthMonitor {
    
    public static function checkHealth() {
        global $wpdb;
        
        $health = [
            'status' => 'healthy',
            'checks' => [],
            'recommendations' => []
        ];
        
        // Check database connection
        $health['checks']['connection'] = self::checkConnection();
        
        // Check table sizes
        $health['checks']['table_sizes'] = self::checkTableSizes();
        
        // Check for missing indexes
        $health['checks']['indexes'] = self::checkIndexes();
        
        // Check query performance
        $health['checks']['query_performance'] = self::checkQueryPerformance();
        
        // Check for orphaned data
        $health['checks']['data_integrity'] = self::checkDataIntegrity();
        
        // Determine overall status
        foreach ($health['checks'] as $check) {
            if ($check['status'] === 'critical') {
                $health['status'] = 'critical';
                break;
            } elseif ($check['status'] === 'warning' && $health['status'] === 'healthy') {
                $health['status'] = 'warning';
            }
        }
        
        return $health;
    }
    
    private static function checkConnection() {
        global $wpdb;
        
        $start = microtime(true);
        $result = $wpdb->get_var("SELECT 1");
        $duration = microtime(true) - $start;
        
        $status = 'healthy';
        if ($result !== '1') {
            $status = 'critical';
        } elseif ($duration > 1.0) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'response_time' => $duration,
            'connected' => $result === '1',
            'last_error' => $wpdb->last_error
        ];
    }
    
    private static function checkTableSizes() {
        global $wpdb;
        
        $query = "
            SELECT 
                TABLE_NAME,
                ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS size_mb,
                TABLE_ROWS,
                ROUND((INDEX_LENGTH / 1024 / 1024), 2) AS index_size_mb
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = %s 
            AND TABLE_NAME LIKE 'wp_nordbooking_%'
            ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
        ";
        
        $tables = $wpdb->get_results($wpdb->prepare($query, DB_NAME));
        
        $status = 'healthy';
        $large_tables = [];
        
        foreach ($tables as $table) {
            if ($table->size_mb > 100) { // Tables over 100MB
                $large_tables[] = $table->TABLE_NAME;
                $status = 'warning';
            }
        }
        
        return [
            'status' => $status,
            'tables' => $tables,
            'large_tables' => $large_tables
        ];
    }
    
    private static function checkIndexes() {
        global $wpdb;
        
        // Check for tables without proper indexes
        $missing_indexes = [];
        
        // Critical indexes that should exist
        $required_indexes = [
            'wp_nordbooking_bookings' => ['user_id', 'status', 'booking_date'],
            'wp_nordbooking_services' => ['user_id', 'status'],
            'wp_nordbooking_customers' => ['tenant_id', 'email']
        ];
        
        foreach ($required_indexes as $table => $columns) {
            $existing_indexes = $wpdb->get_results($wpdb->prepare(
                "SHOW INDEX FROM {$table}"
            ));
            
            $indexed_columns = array_column($existing_indexes, 'Column_name');
            
            foreach ($columns as $column) {
                if (!in_array($column, $indexed_columns)) {
                    $missing_indexes[] = "{$table}.{$column}";
                }
            }
        }
        
        $status = empty($missing_indexes) ? 'healthy' : 'warning';
        
        return [
            'status' => $status,
            'missing_indexes' => $missing_indexes
        ];
    }
    
    private static function checkQueryPerformance() {
        global $wpdb;
        
        // Get slow query log if available
        $slow_queries = [];
        
        // Check if we have our custom query log
        $log_table = $wpdb->prefix . 'nordbooking_query_log';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$log_table}'") === $log_table) {
            $slow_queries = $wpdb->get_results("
                SELECT query_name, AVG(duration) as avg_duration, COUNT(*) as count
                FROM {$log_table}
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY query_name
                HAVING avg_duration > 0.5
                ORDER BY avg_duration DESC
                LIMIT 10
            ");
        }
        
        $status = empty($slow_queries) ? 'healthy' : 'warning';
        
        return [
            'status' => $status,
            'slow_queries' => $slow_queries
        ];
    }
    
    private static function checkDataIntegrity() {
        global $wpdb;
        
        $issues = [];
        
        // Check for orphaned booking items
        $orphaned_items = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM wp_nordbooking_booking_items bi
            LEFT JOIN wp_nordbooking_bookings b ON bi.booking_id = b.booking_id
            WHERE b.booking_id IS NULL
        ");
        
        if ($orphaned_items > 0) {
            $issues[] = "Orphaned booking items: {$orphaned_items}";
        }
        
        // Check for orphaned service options
        $orphaned_options = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM wp_nordbooking_service_options so
            LEFT JOIN wp_nordbooking_services s ON so.service_id = s.service_id
            WHERE s.service_id IS NULL
        ");
        
        if ($orphaned_options > 0) {
            $issues[] = "Orphaned service options: {$orphaned_options}";
        }
        
        $status = empty($issues) ? 'healthy' : 'warning';
        
        return [
            'status' => $status,
            'issues' => $issues
        ];
    }
}

/**
 * Rate Limiter for API endpoints
 */
class RateLimiter {
    
    public static function check($action, $identifier, $limit = 10, $window = 60) {
        $key = "rate_limit_{$action}_{$identifier}";
        $current = (int) get_transient($key);
        
        if ($current >= $limit) {
            return false;
        }
        
        set_transient($key, $current + 1, $window);
        return true;
    }
    
    public static function getRemainingAttempts($action, $identifier, $limit = 10) {
        $key = "rate_limit_{$action}_{$identifier}";
        $current = (int) get_transient($key);
        
        return max(0, $limit - $current);
    }
    
    public static function reset($action, $identifier) {
        $key = "rate_limit_{$action}_{$identifier}";
        delete_transient($key);
    }
}

/**
 * Performance Dashboard
 */
class PerformanceDashboard {
    
    public static function init() {
        add_action('wp_ajax_nordbooking_performance_stats', [__CLASS__, 'getPerformanceStats']);
        add_action('wp_ajax_nordbooking_health_check', [__CLASS__, 'getHealthCheck']);
        add_action('wp_ajax_nordbooking_clear_cache', [__CLASS__, 'clearCache']);
    }
    
    public static function getPerformanceStats() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }
        
        $stats = [
            'query_profiler' => QueryProfiler::getStats(),
            'cache_manager' => CacheManager::getStats(),
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ],
            'php_info' => [
                'version' => PHP_VERSION,
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ]
        ];
        
        wp_send_json_success($stats);
    }
    
    public static function getHealthCheck() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }
        
        $health = DatabaseHealthMonitor::checkHealth();
        wp_send_json_success($health);
    }
    
    public static function clearCache() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }
        
        $result = CacheManager::flush();
        
        if ($result) {
            wp_send_json_success(['message' => 'Cache cleared successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to clear cache']);
        }
    }
}

// Initialize performance monitoring
add_action('init', function() {
    // Always enable for testing, not just in debug mode
    QueryProfiler::enable();
    
    PerformanceDashboard::init();
    
    // Log that performance monitoring is loaded
    error_log('NORDBOOKING Performance Monitoring: Initialized successfully');
}, 5);

// Hook into WordPress to track query performance
add_action('shutdown', function() {
    $stats = QueryProfiler::getStats();
    if ($stats['slow_queries'] > 0) {
        error_log("NORDBOOKING Performance: {$stats['slow_queries']} slow queries detected");
    }
});

// Test the cache manager on init
add_action('init', function() {
    // Test cache functionality
    CacheManager::set('test_key', 'test_value', 60);
    $test_value = CacheManager::get('test_key');
    
    if ($test_value === 'test_value') {
        error_log('NORDBOOKING Cache Manager: Working correctly');
    } else {
        error_log('NORDBOOKING Cache Manager: Test failed - cache not working');
    }
}, 10);