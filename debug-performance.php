<?php
/**
 * NORDBOOKING Performance Debug Page
 * 
 * Add this temporarily to test performance monitoring
 * Access via: /wp-admin/admin.php?page=nordbooking-debug-performance
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Debug functionality is now integrated into ConsolidatedAdminPage
// Keeping functions for backward compatibility but not adding menu items

// add_action('admin_menu', function() {
//     add_submenu_page(
//         'tools.php',
//         'NORDBOOKING Debug',
//         'NORDBOOKING Debug',
//         'manage_options',
//         'nordbooking-debug-performance',
//         'nordbooking_debug_performance_page'
//     );
// });

function nordbooking_debug_performance_page() {
    ?>
    <div class="wrap">
        <h1>NORDBOOKING Performance Debug</h1>
        
        <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
            <h2>File System Check</h2>
            <?php
            $performance_file = NORDBOOKING_THEME_DIR . 'performance_monitoring.php';
            echo '<p><strong>Theme Directory:</strong> ' . NORDBOOKING_THEME_DIR . '</p>';
            echo '<p><strong>Performance File Exists:</strong> ' . (file_exists($performance_file) ? '✅ Yes' : '❌ No') . '</p>';
            echo '<p><strong>Performance File Path:</strong> ' . $performance_file . '</p>';
            
            if (file_exists($performance_file)) {
                echo '<p><strong>File Size:</strong> ' . filesize($performance_file) . ' bytes</p>';
                echo '<p><strong>File Readable:</strong> ' . (is_readable($performance_file) ? '✅ Yes' : '❌ No') . '</p>';
            }
            ?>
        </div>

        <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
            <h2>Class Loading Check</h2>
            <?php
            $classes = [
                '\NORDBOOKING\Performance\QueryProfiler',
                '\NORDBOOKING\Performance\CacheManager',
                '\NORDBOOKING\Performance\DatabaseHealthMonitor',
                '\NORDBOOKING\Performance\RateLimiter',
                '\NORDBOOKING\Performance\PerformanceDashboard'
            ];
            
            foreach ($classes as $class) {
                $exists = class_exists($class);
                echo '<p><strong>' . $class . ':</strong> ' . ($exists ? '✅ Loaded' : '❌ Not Found') . '</p>';
            }
            ?>
        </div>

        <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
            <h2>Cache Test</h2>
            <?php
            if (class_exists('\NORDBOOKING\Performance\CacheManager')) {
                echo '<p>Testing cache functionality...</p>';
                
                // Test cache set/get
                $test_key = 'debug_test_' . time();
                $test_value = 'test_value_' . rand(1000, 9999);
                
                $set_result = \NORDBOOKING\Performance\CacheManager::set($test_key, $test_value, 60);
                echo '<p><strong>Cache Set:</strong> ' . ($set_result ? '✅ Success' : '❌ Failed') . '</p>';
                
                $get_result = \NORDBOOKING\Performance\CacheManager::get($test_key);
                $get_success = ($get_result === $test_value);
                echo '<p><strong>Cache Get:</strong> ' . ($get_success ? '✅ Success' : '❌ Failed') . '</p>';
                
                if (!$get_success) {
                    echo '<p><strong>Expected:</strong> ' . $test_value . '</p>';
                    echo '<p><strong>Got:</strong> ' . var_export($get_result, true) . '</p>';
                }
                
                // Show cache stats
                $stats = \NORDBOOKING\Performance\CacheManager::getStats();
                echo '<p><strong>Cache Stats:</strong></p>';
                echo '<ul>';
                echo '<li>Hits: ' . $stats['hits'] . '</li>';
                echo '<li>Misses: ' . $stats['misses'] . '</li>';
                echo '<li>Sets: ' . $stats['sets'] . '</li>';
                echo '<li>Hit Rate: ' . $stats['hit_rate'] . '%</li>';
                echo '</ul>';
                
            } else {
                echo '<p style="color: red;">❌ CacheManager class not found</p>';
            }
            ?>
        </div>

        <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
            <h2>Query Profiler Test</h2>
            <?php
            if (class_exists('\NORDBOOKING\Performance\QueryProfiler')) {
                echo '<p>Testing query profiler...</p>';
                
                // Test profiler
                \NORDBOOKING\Performance\QueryProfiler::start('debug_test');
                usleep(100000); // 0.1 second delay
                \NORDBOOKING\Performance\QueryProfiler::end('debug_test');
                
                $stats = \NORDBOOKING\Performance\QueryProfiler::getStats();
                echo '<p><strong>Profiler Stats:</strong></p>';
                echo '<ul>';
                echo '<li>Total Queries: ' . $stats['total_queries'] . '</li>';
                echo '<li>Total Time: ' . round($stats['total_time'], 4) . 's</li>';
                echo '<li>Slow Queries: ' . $stats['slow_queries'] . '</li>';
                echo '</ul>';
                
                if (!empty($stats['queries'])) {
                    echo '<p><strong>Recent Queries:</strong></p>';
                    echo '<ul>';
                    foreach ($stats['queries'] as $name => $data) {
                        echo '<li>' . $name . ': ' . round($data['duration'], 4) . 's</li>';
                    }
                    echo '</ul>';
                }
                
            } else {
                echo '<p style="color: red;">❌ QueryProfiler class not found</p>';
            }
            ?>
        </div>

        <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
            <h2>WordPress Cache Test</h2>
            <?php
            // Test WordPress native caching
            $wp_test_key = 'nordbooking_wp_cache_test';
            $wp_test_value = 'wp_test_' . time();
            
            $wp_set = wp_cache_set($wp_test_key, $wp_test_value, 'nordbooking', 60);
            echo '<p><strong>WP Cache Set:</strong> ' . ($wp_set ? '✅ Success' : '❌ Failed') . '</p>';
            
            $wp_get = wp_cache_get($wp_test_key, 'nordbooking');
            $wp_get_success = ($wp_get === $wp_test_value);
            echo '<p><strong>WP Cache Get:</strong> ' . ($wp_get_success ? '✅ Success' : '❌ Failed') . '</p>';
            
            if (!$wp_get_success) {
                echo '<p><strong>Expected:</strong> ' . $wp_test_value . '</p>';
                echo '<p><strong>Got:</strong> ' . var_export($wp_get, true) . '</p>';
            }
            
            // Check if object cache is enabled
            echo '<p><strong>Object Cache Drop-in:</strong> ' . (file_exists(WP_CONTENT_DIR . '/object-cache.php') ? '✅ Present' : '❌ Not Found') . '</p>';
            ?>
        </div>

        <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
            <h2>System Information</h2>
            <?php
            echo '<ul>';
            echo '<li><strong>PHP Version:</strong> ' . PHP_VERSION . '</li>';
            echo '<li><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</li>';
            echo '<li><strong>Memory Limit:</strong> ' . ini_get('memory_limit') . '</li>';
            echo '<li><strong>Current Memory Usage:</strong> ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB</li>';
            echo '<li><strong>Peak Memory Usage:</strong> ' . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB</li>';
            echo '<li><strong>WP Debug:</strong> ' . (defined('WP_DEBUG') && WP_DEBUG ? '✅ Enabled' : '❌ Disabled') . '</li>';
            echo '</ul>';
            ?>
        </div>

        <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">
            <h2>Actions</h2>
            <p>
                <a href="<?php echo admin_url('tools.php?page=nordbooking-performance'); ?>" class="button button-primary">
                    Go to Performance Dashboard
                </a>
            </p>
            <p>
                <button type="button" class="button" onclick="location.reload()">
                    Refresh Debug Info
                </button>
            </p>
        </div>
    </div>
    <?php
}

// Auto-include this file if it exists
if (is_admin() && file_exists(NORDBOOKING_THEME_DIR . 'debug-performance.php')) {
    // This file includes itself, so we don't need to require it again
}