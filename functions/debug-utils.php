<?php
/**
 * Database Table Verification Script
 * Add this temporarily to your functions.php or create a separate debug page
 */

function debug_mobooking_areas_table() {
    // Only show to administrators
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    echo '<div style="background: #f1f1f1; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
    echo '<h3>🔍 MoBooking Areas Database Debug</h3>';

    // Check table name generation
    echo '<h4>Table Name Check:</h4>';
    if (class_exists('MoBooking\Classes\Database')) {
        $table_name = MoBooking\Classes\Database::get_table_name('areas');
        echo '✅ Table name: ' . esc_html($table_name) . '<br>';
    } else {
        echo '❌ Database class not found<br>';
        // Fallback - try direct table name
        $table_name = $wpdb->prefix . 'mobooking_areas';
        echo '⚠️ Using fallback table name: ' . esc_html($table_name) . '<br>';
    }

    // Check if table exists
    echo '<h4>Table Existence:</h4>';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    if ($table_exists) {
        echo '✅ Table exists<br>';

        // Show table structure
        echo '<h4>Table Structure:</h4>';
        $columns = $wpdb->get_results("DESCRIBE $table_name");
        if ($columns) {
            echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
            echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>';
            foreach ($columns as $column) {
                echo '<tr>';
                echo '<td>' . esc_html($column->Field) . '</td>';
                echo '<td>' . esc_html($column->Type) . '</td>';
                echo '<td>' . esc_html($column->Null) . '</td>';
                echo '<td>' . esc_html($column->Key) . '</td>';
                echo '<td>' . esc_html($column->Default) . '</td>';
                echo '<td>' . esc_html($column->Extra) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }

        // Show sample data
        echo '<h4>Sample Data:</h4>';
        $sample_data = $wpdb->get_results("SELECT * FROM $table_name LIMIT 5", ARRAY_A);
        if ($sample_data) {
            echo '<p>Found ' . count($sample_data) . ' sample records:</p>';
            echo '<pre>' . esc_html(print_r($sample_data, true)) . '</pre>';
        } else {
            echo '<p>No data found in table</p>';
        }

        // Count by user
        echo '<h4>Data Count by User:</h4>';
        $user_counts = $wpdb->get_results("
            SELECT user_id, COUNT(*) as count
            FROM $table_name
            GROUP BY user_id
            ORDER BY count DESC
        ");
        if ($user_counts) {
            foreach ($user_counts as $count) {
                $user = get_user_by('ID', $count->user_id);
                $username = $user ? $user->user_login : 'Unknown';
                echo "User ID {$count->user_id} ({$username}): {$count->count} areas<br>";
            }
        } else {
            echo 'No areas found<br>';
        }

    } else {
        echo '❌ Table does not exist<br>';
        echo '<p>Expected table name: ' . esc_html($table_name) . '</p>';

        // Show all tables starting with mobooking
        echo '<h4>Available MoBooking Tables:</h4>';
        $mobooking_tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}mobooking%'");
        if ($mobooking_tables) {
            foreach ($mobooking_tables as $table) {
                $table_name_val = array_values((array)$table)[0];
                echo '📋 ' . esc_html($table_name_val) . '<br>';
            }
        } else {
            echo 'No MoBooking tables found<br>';
        }
    }

    // Test Areas class methods
    echo '<h4>Areas Class Test:</h4>';
    if (class_exists('MoBooking\Classes\Areas')) {
        echo '✅ Areas class exists<br>';

        try {
            $areas = new MoBooking\Classes\Areas();
            echo '✅ Areas class instantiated<br>';

            // Test get_countries method
            $countries = $areas->get_countries();
            if (is_wp_error($countries)) {
                echo '❌ get_countries() error: ' . esc_html($countries->get_error_message()) . '<br>';
            } else {
                echo '✅ get_countries() returned ' . count($countries) . ' countries<br>';
            }

        } catch (Exception $e) {
            echo '❌ Error testing Areas class: ' . esc_html($e->getMessage()) . '<br>';
        }
    } else {
        echo '❌ Areas class not found<br>';
    }

    // Test current user
    echo '<h4>Current User:</h4>';
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        echo "✅ Logged in as: {$user->user_login} (ID: {$user_id})<br>";

        // Check user's areas
        if ($table_exists) {
            $user_areas = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                $user_id
            ));
            echo "📊 Current user has {$user_areas} areas<br>";
        }
    } else {
        echo '❌ User not logged in<br>';
    }

    echo '</div>';
}

// Hook to admin_notices to show in admin area
add_action('admin_notices', 'debug_mobooking_areas_table');

// Also create a shortcode for testing on frontend
add_shortcode('debug_areas_table', 'debug_mobooking_areas_table');

// Create a test endpoint to manually check table creation
add_action('wp_ajax_create_areas_table', function() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'mobooking_areas';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        area_id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        area_type varchar(50) NOT NULL DEFAULT 'zip_code',
        area_name varchar(255) NOT NULL,
        area_value varchar(100) NOT NULL,
        country_code varchar(10) NOT NULL,
        status varchar(20) DEFAULT 'active',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (area_id),
        KEY idx_user_id (user_id),
        KEY idx_user_country (user_id, country_code),
        KEY idx_user_status (user_id, status)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    echo "Table creation attempted. Check the debug output above.";
    wp_die();
});

?>
