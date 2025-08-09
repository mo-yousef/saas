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


/**
 * Database Table Fix and Diagnostic for MoBooking
 * Add this to your functions.php or create as a separate plugin
 */

// Add admin menu for diagnostics
add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'MoBooking Database Diagnostic',
        'MoBooking DB Diagnostic',
        'manage_options',
        'mobooking-db-diagnostic',
        'mobooking_db_diagnostic_page'
    );
});

function mobooking_db_diagnostic_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    global $wpdb;

    echo '<div class="wrap">';
    echo '<h1>🔍 MoBooking Database Diagnostic</h1>';

    // Check bookings table
    $bookings_table = $wpdb->prefix . 'mobooking_bookings';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") == $bookings_table;

    echo '<div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">';
    echo '<h2>📊 Bookings Table Analysis</h2>';

    if ($table_exists) {
        echo '<p>✅ Table exists: <code>' . $bookings_table . '</code></p>';

        // Get table structure
        $columns = $wpdb->get_results("DESCRIBE $bookings_table");
        $column_names = array_column($columns, 'Field');

        echo '<h3>Current Table Structure:</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>';
        echo '<tbody>';
        foreach ($columns as $column) {
            echo '<tr>';
            echo '<td><code>' . esc_html($column->Field) . '</code></td>';
            echo '<td>' . esc_html($column->Type) . '</td>';
            echo '<td>' . esc_html($column->Null) . '</td>';
            echo '<td>' . esc_html($column->Key) . '</td>';
            echo '<td>' . esc_html($column->Default) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        // Check for required columns
        $required_columns = [
            'booking_id', 'user_id', 'booking_reference', 'customer_name',
            'customer_email', 'customer_phone', 'customer_address',
            'booking_date', 'booking_time', 'total_amount', 'status',
            'special_instructions', 'service_frequency', 'selected_services',
            'pet_information', 'property_access', 'created_at', 'updated_at'
        ];

        echo '<h3>Required Columns Check:</h3>';
        echo '<ul>';
        foreach ($required_columns as $required_col) {
            if (in_array($required_col, $column_names)) {
                echo '<li>✅ <code>' . $required_col . '</code></li>';
            } else {
                echo '<li>❌ <code>' . $required_col . '</code> - MISSING</li>';
            }
        }
        echo '</ul>';

        // Show row count
        $row_count = $wpdb->get_var("SELECT COUNT(*) FROM $bookings_table");
        echo '<p>📈 Total bookings: <strong>' . $row_count . '</strong></p>';

        // Show sample data if exists
        if ($row_count > 0) {
            $sample = $wpdb->get_row("SELECT * FROM $bookings_table ORDER BY booking_id DESC LIMIT 1", ARRAY_A);
            echo '<h3>Latest Booking Sample:</h3>';
            echo '<pre style="background: #f6f7f7; padding: 10px; overflow: auto;">';
            foreach ($sample as $key => $value) {
                echo esc_html($key) . ': ' . esc_html(substr($value, 0, 100)) . "\n";
            }
            echo '</pre>';
        }

    } else {
        echo '<p>❌ Table does not exist: <code>' . $bookings_table . '</code></p>';
        echo '<p>The table needs to be created first.</p>';
    }

    echo '</div>';

    // Fix button
    echo '<div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4;">';
    echo '<h2>🔧 Database Fix Actions</h2>';

    if (isset($_POST['action']) && $_POST['action'] === 'fix_database') {
        if (wp_verify_nonce($_POST['nonce'], 'mobooking_db_fix')) {
            echo '<div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; margin: 10px 0;">';
            echo '<h3>🛠️ Running Database Fix...</h3>';

            $result = mobooking_fix_database_tables();

            if ($result['success']) {
                echo '<p>✅ Database fix completed successfully!</p>';
                foreach ($result['messages'] as $message) {
                    echo '<p>• ' . esc_html($message) . '</p>';
                }
            } else {
                echo '<p>❌ Database fix failed:</p>';
                foreach ($result['errors'] as $error) {
                    echo '<p>• ' . esc_html($error) . '</p>';
                }
            }

            echo '</div>';
        }
    }

    echo '<form method="post">';
    wp_nonce_field('mobooking_db_fix', 'nonce');
    echo '<input type="hidden" name="action" value="fix_database">';
    echo '<button type="submit" class="button button-primary">🔧 Fix Database Tables</button>';
    echo '<p class="description">This will create/update the bookings table with the correct structure.</p>';
    echo '</form>';

    echo '</div>';
    echo '</div>';
}

function mobooking_fix_database_tables() {
    global $wpdb;

    $result = ['success' => false, 'messages' => [], 'errors' => []];

    try {
        $charset_collate = $wpdb->get_charset_collate();
        $bookings_table = $wpdb->prefix . 'mobooking_bookings';

        // Create or update bookings table with correct structure
        $sql = "CREATE TABLE $bookings_table (
            booking_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED NULL,
            booking_reference VARCHAR(100) NULL,
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(50) NULL,
            customer_address TEXT NULL,
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            special_instructions TEXT NULL,
            service_frequency VARCHAR(20) DEFAULT 'one-time',
            selected_services LONGTEXT NULL,
            pet_information LONGTEXT NULL,
            property_access LONGTEXT NULL,
            discount_id BIGINT UNSIGNED NULL,
            discount_amount DECIMAL(10,2) DEFAULT 0.00,
            payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (booking_id),
            INDEX user_id_idx (user_id),
            INDEX customer_email_idx (customer_email),
            INDEX status_idx (status),
            INDEX booking_date_idx (booking_date),
            UNIQUE KEY booking_reference_unique (booking_reference)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $dbdelta_result = dbDelta($sql);

        $result['messages'][] = 'Bookings table created/updated successfully';
        $result['messages'][] = 'dbDelta result: ' . print_r($dbdelta_result, true);

        // Verify table was created
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") == $bookings_table;
        if ($table_exists) {
            $result['messages'][] = 'Table verification: ✅ Table exists';

            // Check columns
            $columns = $wpdb->get_results("DESCRIBE $bookings_table");
            $result['messages'][] = 'Table has ' . count($columns) . ' columns';

            $result['success'] = true;
        } else {
            $result['errors'][] = 'Table verification failed - table does not exist after creation';
        }

    } catch (Exception $e) {
        $result['errors'][] = 'Exception: ' . $e->getMessage();
    }

    return $result;
}

?>
