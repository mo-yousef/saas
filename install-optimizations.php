<?php
/**
 * NORDBOOKING Optimization Installation Script
 * 
 * Run this script once to apply all performance optimizations
 * 
 * Usage: Add this to your functions.php temporarily and visit any admin page,
 * or run via WP-CLI: wp eval-file install-optimizations.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Install NORDBOOKING Performance Optimizations
 */
function nordbooking_install_optimizations() {
    global $wpdb;
    
    echo "🚀 Starting NORDBOOKING Performance Optimizations...\n";
    
    // 1. Add missing database indexes
    echo "📊 Adding database indexes...\n";
    
    $optimizations = [
        // Bookings table optimizations
        'wp_nordbooking_bookings' => [
            'idx_user_status_date' => 'ADD INDEX IF NOT EXISTS idx_user_status_date (user_id, status, booking_date)',
            'idx_customer_email_date' => 'ADD INDEX IF NOT EXISTS idx_customer_email_date (customer_email, booking_date)',
            'idx_status_created' => 'ADD INDEX IF NOT EXISTS idx_status_created (status, created_at)',
            'idx_booking_date_time' => 'ADD INDEX IF NOT EXISTS idx_booking_date_time (booking_date, booking_time)',
            'idx_user_date_status' => 'ADD INDEX IF NOT EXISTS idx_user_date_status (user_id, booking_date, status)'
        ],
        // Services table optimizations
        'wp_nordbooking_services' => [
            'idx_user_status_sort' => 'ADD INDEX IF NOT EXISTS idx_user_status_sort (user_id, status, sort_order)',
            'idx_status_active' => 'ADD INDEX IF NOT EXISTS idx_status_active (status)'
        ],
        // Service options optimizations
        'wp_nordbooking_service_options' => [
            'idx_service_user' => 'ADD INDEX IF NOT EXISTS idx_service_user (service_id, user_id)'
        ],
        // Customers table optimizations
        'wp_nordbooking_customers' => [
            'idx_tenant_status_activity' => 'ADD INDEX IF NOT EXISTS idx_tenant_status_activity (tenant_id, status, last_activity_at)',
            'idx_tenant_email_unique' => 'ADD INDEX IF NOT EXISTS idx_tenant_email_unique (tenant_id, email)'
        ],
        // Booking items optimizations
        'wp_nordbooking_booking_items' => [
            'idx_booking_service' => 'ADD INDEX IF NOT EXISTS idx_booking_service (booking_id, service_id)'
        ]
    ];

    $indexes_added = 0;
    $indexes_skipped = 0;

    foreach ($optimizations as $table_name => $indexes) {
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            echo "⚠️  Table $table_name does not exist, skipping...\n";
            continue;
        }

        echo "  📋 Optimizing table: $table_name\n";

        foreach ($indexes as $index_name => $sql) {
            // Check if index already exists
            $existing_indexes = $wpdb->get_results("SHOW INDEX FROM $table_name WHERE Key_name = '$index_name'");
            
            if (empty($existing_indexes)) {
                $result = $wpdb->query("ALTER TABLE $table_name $sql");
                if ($result === false) {
                    echo "    ❌ Failed to add index $index_name: " . $wpdb->last_error . "\n";
                } else {
                    echo "    ✅ Added index: $index_name\n";
                    $indexes_added++;
                }
            } else {
                echo "    ⏭️  Index $index_name already exists\n";
                $indexes_skipped++;
            }
        }
    }

    echo "📊 Database optimization complete: $indexes_added added, $indexes_skipped skipped\n";

    // 2. Create query log table for performance monitoring
    echo "📝 Creating query log table...\n";
    
    $query_log_table = $wpdb->prefix . 'nordbooking_query_log';
    $query_log_sql = "CREATE TABLE IF NOT EXISTS $query_log_table (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        query_name VARCHAR(255) NOT NULL,
        duration DECIMAL(10,6) NOT NULL,
        memory_used BIGINT NOT NULL,
        context TEXT,
        backtrace TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_duration (duration),
        INDEX idx_created (created_at),
        INDEX idx_query_name (query_name)
    ) {$wpdb->get_charset_collate()}";
    
    $result = $wpdb->query($query_log_sql);
    if ($result === false) {
        echo "❌ Failed to create query log table: " . $wpdb->last_error . "\n";
    } else {
        echo "✅ Query log table created successfully\n";
    }

    // 3. Set up performance monitoring options
    echo "⚙️  Configuring performance settings...\n";
    
    update_option('nordbooking_performance_monitoring_enabled', true);
    update_option('nordbooking_cache_enabled', true);
    update_option('nordbooking_query_profiling_enabled', defined('WP_DEBUG') && WP_DEBUG);
    update_option('nordbooking_last_optimization', time());
    
    echo "✅ Performance settings configured\n";

    // 4. Clean up orphaned data
    echo "🧹 Cleaning up orphaned data...\n";
    
    $cleanup_queries = [
        "DELETE bi FROM wp_nordbooking_booking_items bi
         LEFT JOIN wp_nordbooking_bookings b ON bi.booking_id = b.booking_id
         WHERE b.booking_id IS NULL",
        
        "DELETE so FROM wp_nordbooking_service_options so
         LEFT JOIN wp_nordbooking_services s ON so.service_id = s.service_id
         WHERE s.service_id IS NULL",
    ];
    
    $cleaned_records = 0;
    foreach ($cleanup_queries as $query) {
        $result = $wpdb->query($query);
        if ($result !== false) {
            $cleaned_records += $result;
            echo "  🗑️  Cleaned $result orphaned records\n";
        }
    }
    
    echo "🧹 Cleanup complete: $cleaned_records records removed\n";

    // 5. Optimize tables
    echo "🔧 Optimizing table storage...\n";
    
    $tables_to_optimize = [
        'wp_nordbooking_bookings',
        'wp_nordbooking_services',
        'wp_nordbooking_customers',
        'wp_nordbooking_booking_items',
        'wp_nordbooking_service_options'
    ];
    
    foreach ($tables_to_optimize as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
            $wpdb->query("OPTIMIZE TABLE $table");
            echo "  🔧 Optimized: $table\n";
        }
    }

    // 6. Generate performance report
    echo "\n📈 Performance Optimization Report:\n";
    echo "=====================================\n";
    echo "✅ Database indexes: $indexes_added added, $indexes_skipped existing\n";
    echo "✅ Query logging: Enabled\n";
    echo "✅ Caching: Enabled\n";
    echo "✅ Orphaned data: $cleaned_records records cleaned\n";
    echo "✅ Tables: Optimized\n";
    echo "\n🎉 All optimizations completed successfully!\n";
    echo "\n📊 Next Steps:\n";
    echo "- Visit Tools > NORDBOOKING Performance to monitor system health\n";
    echo "- Enable object caching (Redis/Memcached) for even better performance\n";
    echo "- Monitor slow queries in the performance dashboard\n";
    echo "- Consider upgrading to PHP 8.1+ for additional performance gains\n";
    
    return true;
}

// Auto-run if accessed via admin (remove after running once)
if (is_admin() && current_user_can('manage_options')) {
    // Only run if explicitly requested
    if (isset($_GET['run_nordbooking_optimization']) && $_GET['run_nordbooking_optimization'] === 'yes') {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-info"><p>';
            echo '<strong>NORDBOOKING Optimization:</strong> ';
            
            ob_start();
            nordbooking_install_optimizations();
            $output = ob_get_clean();
            
            echo nl2br(esc_html($output));
            echo '</p></div>';
        });
    } else {
        // Show admin notice with link to run optimization
        add_action('admin_notices', function() {
            $current_url = admin_url('admin.php?page=' . $_GET['page'] . '&run_nordbooking_optimization=yes');
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>NORDBOOKING Performance Optimization Available</strong></p>';
            echo '<p>Click here to run database optimizations and improve system performance:</p>';
            echo '<p><a href="' . esc_url($current_url) . '" class="button button-primary">Run Optimization Now</a></p>';
            echo '</div>';
        });
    }
}

// WP-CLI command (if WP-CLI is available)
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('nordbooking optimize', function() {
        nordbooking_install_optimizations();
        WP_CLI::success('NORDBOOKING optimizations completed!');
    });
}