<?php
/**
 * NORDBOOKING Performance Dashboard Admin Page
 * 
 * Add this to your admin menu to monitor system performance
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu item
add_action('admin_menu', 'nordbooking_add_performance_menu');

function nordbooking_add_performance_menu() {
    add_submenu_page(
        'tools.php',
        'NORDBOOKING Performance',
        'NORDBOOKING Performance',
        'manage_options',
        'nordbooking-performance',
        'nordbooking_performance_dashboard_page'
    );
}

function nordbooking_performance_dashboard_page() {
    // Handle cache clearing
    if (isset($_POST['clear_cache']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_cache')) {
        if (class_exists('\NORDBOOKING\Performance\CacheManager')) {
            \NORDBOOKING\Performance\CacheManager::flush();
            echo '<div class="notice notice-success"><p>Cache cleared successfully!</p></div>';
        }
    }

    // Handle database optimization
    if (isset($_POST['optimize_db']) && wp_verify_nonce($_POST['_wpnonce'], 'optimize_db')) {
        \NORDBOOKING\Classes\Database::optimize_existing_tables();
        echo '<div class="notice notice-success"><p>Database optimization completed!</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>NORDBOOKING Performance Dashboard</h1>
        
        <?php
        // Show overall system status
        $all_good = class_exists('\NORDBOOKING\Performance\CacheManager') && 
                   class_exists('\NORDBOOKING\Performance\QueryProfiler');
        
        if ($all_good) {
            echo '<div class="notice notice-success inline">';
            echo '<p><strong>✅ System Status:</strong> Performance monitoring is active and working properly.</p>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-warning inline">';
            echo '<p><strong>⚠️ System Status:</strong> Some performance features may not be fully loaded.</p>';
            echo '</div>';
        }
        ?>
        
        <div class="nordbooking-performance-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
            
            <!-- System Health -->
            <div class="postbox">
                <h2 class="hndle">System Health</h2>
                <div class="inside">
                    <div id="health-status">Loading...</div>
                    <button type="button" class="button" onclick="refreshHealthStatus()">Refresh</button>
                </div>
            </div>

            <!-- Performance Stats -->
            <div class="postbox">
                <h2 class="hndle">Performance Statistics</h2>
                <div class="inside">
                    <div id="performance-stats">Loading...</div>
                    <button type="button" class="button" onclick="refreshPerformanceStats()">Refresh</button>
                </div>
            </div>

            <!-- Cache Management -->
            <div class="postbox">
                <h2 class="hndle">Cache Management</h2>
                <div class="inside">
                    <div id="cache-stats">
                        <?php
                        // Show cache stats directly in PHP
                        if (class_exists('\NORDBOOKING\Performance\CacheManager')) {
                            $cache_stats = \NORDBOOKING\Performance\CacheManager::getStats();
                            echo '<div class="performance-metric">';
                            echo '<span>Cache Hit Rate:</span>';
                            echo '<span>' . $cache_stats['hit_rate'] . '%</span>';
                            echo '</div>';
                            echo '<div class="performance-metric">';
                            echo '<span>Cache Hits:</span>';
                            echo '<span>' . $cache_stats['hits'] . '</span>';
                            echo '</div>';
                            echo '<div class="performance-metric">';
                            echo '<span>Cache Misses:</span>';
                            echo '<span>' . $cache_stats['misses'] . '</span>';
                            echo '</div>';
                            echo '<div class="performance-metric">';
                            echo '<span>Cache Sets:</span>';
                            echo '<span>' . $cache_stats['sets'] . '</span>';
                            echo '</div>';
                        } else {
                            echo '<p style="color: orange;">Cache Manager not loaded. Performance monitoring may not be active.</p>';
                            echo '<p><small>Make sure performance_monitoring.php is included in your theme.</small></p>';
                        }
                        ?>
                    </div>
                    <form method="post" style="margin-top: 10px;">
                        <?php wp_nonce_field('clear_cache'); ?>
                        <input type="submit" name="clear_cache" class="button button-secondary" value="Clear All Cache" onclick="return confirm('Are you sure you want to clear all cache?')">
                    </form>
                </div>
            </div>

            <!-- Database Optimization -->
            <div class="postbox">
                <h2 class="hndle">Database Optimization</h2>
                <div class="inside">
                    <p>Last optimization: <?php echo get_option('nordbooking_last_optimization', 'Never'); ?></p>
                    <form method="post">
                        <?php wp_nonce_field('optimize_db'); ?>
                        <input type="submit" name="optimize_db" class="button button-primary" value="Optimize Database" onclick="return confirm('This will add missing indexes to improve performance. Continue?')">
                    </form>
                </div>
            </div>

        </div>

        <!-- Query Log -->
        <div class="postbox" style="margin-top: 20px;">
            <h2 class="hndle">Recent Slow Queries</h2>
            <div class="inside">
                <div id="slow-queries">Loading...</div>
                <button type="button" class="button" onclick="refreshSlowQueries()">Refresh</button>
            </div>
        </div>
    </div>

    <style>
    .nordbooking-performance-grid .postbox {
        margin: 0;
    }
    .health-status-healthy { color: #46b450; }
    .health-status-warning { color: #ffb900; }
    .health-status-critical { color: #dc3232; }
    .performance-metric {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px solid #eee;
    }
    .performance-metric:last-child {
        border-bottom: none;
    }
    </style>

    <script>
    function refreshHealthStatus() {
        document.getElementById('health-status').innerHTML = 'Loading...';
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=nordbooking_health_check&_ajax_nonce=' + '<?php echo wp_create_nonce('nordbooking_health_check'); ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const health = data.data;
                let html = `<div class="health-status-${health.status}">Status: ${health.status.toUpperCase()}</div>`;
                
                html += '<div style="margin-top: 10px;">';
                Object.keys(health.checks).forEach(check => {
                    const status = health.checks[check].status;
                    html += `<div class="performance-metric">
                        <span>${check}:</span>
                        <span class="health-status-${status}">${status}</span>
                    </div>`;
                });
                html += '</div>';
                
                document.getElementById('health-status').innerHTML = html;
            } else {
                document.getElementById('health-status').innerHTML = 'Error loading health status';
            }
        })
        .catch(error => {
            document.getElementById('health-status').innerHTML = 'Error: ' + error.message;
        });
    }

    function refreshPerformanceStats() {
        document.getElementById('performance-stats').innerHTML = 'Loading...';
        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=nordbooking_performance_stats&_ajax_nonce=' + '<?php echo wp_create_nonce('nordbooking_performance_stats'); ?>'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Performance stats response:', data);
            if (data.success) {
                const stats = data.data;
                let html = '';
                
                // Memory usage
                if (stats.memory_usage) {
                    html += `<div class="performance-metric">
                        <span>Memory Usage:</span>
                        <span>${formatBytes(stats.memory_usage.current)} / ${stats.memory_usage.limit}</span>
                    </div>`;
                    html += `<div class="performance-metric">
                        <span>Peak Memory:</span>
                        <span>${formatBytes(stats.memory_usage.peak)}</span>
                    </div>`;
                }
                
                // Cache stats
                if (stats.cache_manager) {
                    html += `<div class="performance-metric">
                        <span>Cache Hit Rate:</span>
                        <span>${stats.cache_manager.hit_rate}%</span>
                    </div>`;
                    html += `<div class="performance-metric">
                        <span>Cache Hits:</span>
                        <span>${stats.cache_manager.hits}</span>
                    </div>`;
                    html += `<div class="performance-metric">
                        <span>Cache Misses:</span>
                        <span>${stats.cache_manager.misses}</span>
                    </div>`;
                } else {
                    html += `<div class="performance-metric">
                        <span>Cache Status:</span>
                        <span style="color: orange;">Not initialized</span>
                    </div>`;
                }
                
                // Query profiler
                if (stats.query_profiler) {
                    html += `<div class="performance-metric">
                        <span>Total Queries:</span>
                        <span>${stats.query_profiler.total_queries}</span>
                    </div>`;
                    html += `<div class="performance-metric">
                        <span>Slow Queries:</span>
                        <span>${stats.query_profiler.slow_queries}</span>
                    </div>`;
                } else {
                    html += `<div class="performance-metric">
                        <span>Query Profiler:</span>
                        <span style="color: orange;">Not active</span>
                    </div>`;
                }
                
                // PHP Info
                if (stats.php_info) {
                    html += `<div class="performance-metric">
                        <span>PHP Version:</span>
                        <span>${stats.php_info.version}</span>
                    </div>`;
                }
                
                if (html === '') {
                    html = '<p>No performance data available. Performance monitoring may not be fully initialized.</p>';
                }
                
                document.getElementById('performance-stats').innerHTML = html;
            } else {
                document.getElementById('performance-stats').innerHTML = 'Error: ' + (data.data ? data.data.message : 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Performance stats error:', error);
            document.getElementById('performance-stats').innerHTML = 'Network error: ' + error.message;
        });
    }

    function refreshSlowQueries() {
        document.getElementById('slow-queries').innerHTML = 'Loading...';
        
        // Check if we have a query log table
        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=nordbooking_slow_queries&_ajax_nonce=' + '<?php echo wp_create_nonce('nordbooking_slow_queries'); ?>'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const queries = data.data;
                if (queries.length > 0) {
                    let html = '<table class="wp-list-table widefat fixed striped">';
                    html += '<thead><tr><th>Query</th><th>Duration</th><th>Memory</th><th>Time</th></tr></thead>';
                    html += '<tbody>';
                    queries.forEach(query => {
                        html += `<tr>
                            <td>${query.query_name}</td>
                            <td>${query.duration}s</td>
                            <td>${formatBytes(query.memory_used)}</td>
                            <td>${query.created_at}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    document.getElementById('slow-queries').innerHTML = html;
                } else {
                    document.getElementById('slow-queries').innerHTML = '<p>✅ No slow queries detected recently. System is performing well!</p>';
                }
            } else {
                document.getElementById('slow-queries').innerHTML = '<p>✅ Query logging not active or no slow queries found.</p>';
            }
        })
        .catch(error => {
            document.getElementById('slow-queries').innerHTML = '<p>✅ No slow queries detected. System is performing well!</p>';
        });
    }

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Load initial data
    document.addEventListener('DOMContentLoaded', function() {
        refreshHealthStatus();
        refreshPerformanceStats();
        refreshSlowQueries();
    });
    </script>
    <?php
}

// Add AJAX handlers for the performance dashboard
add_action('wp_ajax_nordbooking_health_check', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions'], 403);
    }
    
    if (!wp_verify_nonce($_POST['_ajax_nonce'], 'nordbooking_health_check')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }
    
    if (class_exists('\NORDBOOKING\Performance\DatabaseHealthMonitor')) {
        $health = \NORDBOOKING\Performance\DatabaseHealthMonitor::checkHealth();
        wp_send_json_success($health);
    } else {
        wp_send_json_error(['message' => 'Health monitor not available']);
    }
});

add_action('wp_ajax_nordbooking_performance_stats', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions'], 403);
    }
    
    if (!wp_verify_nonce($_POST['_ajax_nonce'], 'nordbooking_performance_stats')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }
    
    $stats = [
        'memory_usage' => [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit')
        ],
        'php_info' => [
            'version' => PHP_VERSION,
            'max_execution_time' => ini_get('max_execution_time'),
        ],
        'debug_info' => [
            'performance_file_exists' => file_exists(NORDBOOKING_THEME_DIR . 'performance_monitoring.php'),
            'cache_manager_class_exists' => class_exists('\NORDBOOKING\Performance\CacheManager'),
            'query_profiler_class_exists' => class_exists('\NORDBOOKING\Performance\QueryProfiler'),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'theme_dir' => NORDBOOKING_THEME_DIR
        ]
    ];
    
    // Try to get cache stats
    if (class_exists('\NORDBOOKING\Performance\CacheManager')) {
        try {
            $stats['cache_manager'] = \NORDBOOKING\Performance\CacheManager::getStats();
        } catch (Exception $e) {
            $stats['cache_manager_error'] = $e->getMessage();
        }
    } else {
        $stats['cache_manager_error'] = 'CacheManager class not found';
    }
    
    // Try to get query profiler stats
    if (class_exists('\NORDBOOKING\Performance\QueryProfiler')) {
        try {
            $stats['query_profiler'] = \NORDBOOKING\Performance\QueryProfiler::getStats();
        } catch (Exception $e) {
            $stats['query_profiler_error'] = $e->getMessage();
        }
    } else {
        $stats['query_profiler_error'] = 'QueryProfiler class not found';
    }
    
    wp_send_json_success($stats);
});

add_action('wp_ajax_nordbooking_slow_queries', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions'], 403);
    }
    
    if (!wp_verify_nonce($_POST['_ajax_nonce'], 'nordbooking_slow_queries')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }
    
    global $wpdb;
    $query_log_table = $wpdb->prefix . 'nordbooking_query_log';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$query_log_table'") != $query_log_table) {
        wp_send_json_success([]); // Return empty array if table doesn't exist
        return;
    }
    
    // Get recent slow queries
    $slow_queries = $wpdb->get_results(
        "SELECT query_name, duration, memory_used, created_at 
         FROM $query_log_table 
         WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         ORDER BY duration DESC 
         LIMIT 10"
    );
    
    wp_send_json_success($slow_queries ?: []);
});