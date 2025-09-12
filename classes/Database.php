<?php
/**
 * Class Database
 * Handles custom database table creation and interaction.
 * @package NORDBOOKING\Classes
 */
namespace NORDBOOKING\Classes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Database {

    public function __construct() {
        // The hook is better placed in functions.php after ensuring class exists
    }

    public static function get_table_name( $name ) {
        global $wpdb;
        return $wpdb->prefix . 'nordbooking_' . $name;
    }

    public function migrate_to_enhanced_booking_form() {
        global $wpdb;

        $bookings_table = $this->get_table_name('bookings');

        // Check if new columns already exist
        $columns = $wpdb->get_results("DESCRIBE {$bookings_table}");
        $existing_columns = array_column($columns, 'Field');

        $new_columns = [
            'has_pets' => 'BOOLEAN DEFAULT FALSE',
            'pet_details' => 'TEXT',
            'service_frequency' => 'VARCHAR(20) DEFAULT "one-time"',
            'property_access_method' => 'VARCHAR(50)',
            'property_access_details' => 'TEXT',
            'street_address' => 'VARCHAR(255)',
            'apartment' => 'VARCHAR(100)'
        ];

        foreach ($new_columns as $column => $definition) {
            if (!in_array($column, $existing_columns)) {
                $sql = "ALTER TABLE {$bookings_table} ADD COLUMN {$column} {$definition}";
                $wpdb->query($sql);

                if ($wpdb->last_error) {
                    error_log("NORDBOOKING Migration Error: " . $wpdb->last_error);
                }
            }
        }

        // Update database version
        update_option('nordbooking_db_version', '2.0.0');
    }
    public static function create_tables() {
        error_log('[NORDBOOKING DB Debug] Attempting to create/update custom tables...');
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $charset_collate = $wpdb->get_charset_collate();
        $dbDelta_results = [];

        // Services Table
        $table_name = self::get_table_name('services');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for services table: ' . $table_name);
        $sql_services = "CREATE TABLE $table_name (
            service_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            duration INT NOT NULL DEFAULT 30,
            icon VARCHAR(100),
            image_url VARCHAR(255),
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            disable_pet_question BOOLEAN NOT NULL DEFAULT 0,
            disable_frequency_option BOOLEAN NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (service_id),
            INDEX user_id_idx (user_id),
            INDEX status_idx (status),
            INDEX sort_order_idx (sort_order)
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for services table: ' . preg_replace('/\s+/', ' ', $sql_services)); // Log condensed SQL
        $dbDelta_results['services'] = dbDelta( $sql_services );

        // Service Options Table
        $table_name = self::get_table_name('service_options');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for service_options table: ' . $table_name);
        $sql_service_options = "CREATE TABLE $table_name (
            option_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            service_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            type VARCHAR(50) NOT NULL,
            is_required BOOLEAN NOT NULL DEFAULT 0,
            price_impact_type VARCHAR(20),
            price_impact_value DECIMAL(10,2),
            option_values TEXT,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (option_id),
            INDEX service_id_idx (service_id),
            INDEX user_id_idx (user_id)
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for service_options table: ' . preg_replace('/\s+/', ' ', $sql_service_options));
        $dbDelta_results['service_options'] = dbDelta( $sql_service_options );

        // NOTE: The 'customers' table definition below is being removed as it was redundant.
        // The correct table is 'mob_customers', defined later in this method.
        // $table_name = self::get_table_name('customers');
        // error_log('[NORDBOOKING DB Debug] Preparing SQL for customers table: ' . $table_name);
        // $sql_customers = "CREATE TABLE $table_name ( ... ) $charset_collate;";
        // error_log('[NORDBOOKING DB Debug] SQL for customers table: ' . preg_replace('/\s+/', ' ', $sql_customers));
        // $dbDelta_results['customers'] = dbDelta( $sql_customers );

        // Discounts Table (formerly discount_codes)
        $table_name = self::get_table_name('discounts');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for discounts table: ' . $table_name);
        $sql_discounts = "CREATE TABLE $table_name (
            discount_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            code VARCHAR(100) NOT NULL,
            type VARCHAR(20) NOT NULL,
            value DECIMAL(10,2) NOT NULL,
            expiry_date DATE,
            usage_limit INT,
            times_used INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (discount_id),
            INDEX user_id_idx (user_id),
            UNIQUE KEY user_code_unique (user_id, code)
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for discounts table: ' . preg_replace('/\s+/', ' ', $sql_discounts));
        $dbDelta_results['discounts'] = dbDelta( $sql_discounts );

        // Bookings Table
        $table_name = self::get_table_name('bookings');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for bookings table: ' . $table_name);
        $sql_bookings = "CREATE TABLE $table_name (
            booking_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL, -- Tenant ID (Business Owner)
            customer_id BIGINT UNSIGNED, -- Original field, maybe WordPress user ID of customer if they are registered users.
            mob_customer_id BIGINT UNSIGNED NULL, -- FK to nordbooking_customers table (formerly mob_customers)
            assigned_staff_id BIGINT UNSIGNED NULL, -- ID of the assigned staff member (WP User ID)
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(50),
            service_address TEXT NOT NULL,
            street_address VARCHAR(255),
            apartment VARCHAR(100),
            zip_code VARCHAR(20),
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            total_duration INT,
            special_instructions TEXT,
            total_price DECIMAL(10,2) NOT NULL,
            discount_id BIGINT UNSIGNED,
            discount_amount DECIMAL(10,2) DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            booking_reference VARCHAR(100) UNIQUE,
            payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            has_pets BOOLEAN DEFAULT FALSE,
            pet_details TEXT,
            service_frequency VARCHAR(20) DEFAULT 'one-time',
            property_access_method VARCHAR(50),
            property_access_details TEXT,
            PRIMARY KEY (booking_id),
            INDEX user_id_idx (user_id),
            INDEX customer_id_idx (customer_id), -- Original customer_id index
            INDEX mob_customer_id_idx (mob_customer_id), -- Index for the new customer ID
            INDEX customer_email_idx (customer_email),
            INDEX zip_code_idx (zip_code),
            INDEX status_idx (status),
            INDEX discount_id_idx (discount_id),
            INDEX assigned_staff_id_idx (assigned_staff_id)
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for bookings table: ' . preg_replace('/\s+/', ' ', $sql_bookings));
        $dbDelta_results['bookings'] = dbDelta( $sql_bookings );

        // Booking Items Table
        $table_name = self::get_table_name('booking_items');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for booking_items table: ' . $table_name);
        $sql_booking_items = "CREATE TABLE $table_name (
            item_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT UNSIGNED NOT NULL,
            service_id BIGINT UNSIGNED NOT NULL,
            service_name VARCHAR(255) NOT NULL,
            service_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            selected_options TEXT,
            item_total_price DECIMAL(10,2) NOT NULL,
            PRIMARY KEY (item_id),
            INDEX booking_id_idx (booking_id),
            INDEX service_id_idx (service_id)
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for booking_items table: ' . preg_replace('/\s+/', ' ', $sql_booking_items));
        $dbDelta_results['booking_items'] = dbDelta( $sql_booking_items );

        // Tenant Settings Table
        $table_name = self::get_table_name('tenant_settings');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for tenant_settings table: ' . $table_name);
        $sql_tenant_settings = "CREATE TABLE $table_name (
            setting_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            setting_name VARCHAR(255) NOT NULL,
            setting_value LONGTEXT,
            PRIMARY KEY (setting_id),
            UNIQUE KEY user_setting_unique (user_id, setting_name(191))
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for tenant_settings table: ' . preg_replace('/\s+/', ' ', $sql_tenant_settings));
        $dbDelta_results['tenant_settings'] = dbDelta( $sql_tenant_settings );

        // Areas Table (formerly service_areas)
        $table_name = self::get_table_name('areas');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for areas table: ' . $table_name);
        $sql_areas = "CREATE TABLE $table_name (
            area_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            area_type VARCHAR(50) NOT NULL,
            area_value VARCHAR(255) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            country_code VARCHAR(10),
            area_data JSON,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (area_id),
            INDEX user_id_idx (user_id),
            INDEX status_idx (status)
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for areas table: ' . preg_replace('/\s+/', ' ', $sql_areas));
        $dbDelta_results['areas'] = dbDelta( $sql_areas );

        // Availability Rules Table (Recurring, formerly availability_slots)
        $table_name_rules = self::get_table_name('availability_rules');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for availability_rules table: ' . $table_name_rules);
        $sql_availability_rules = "CREATE TABLE $table_name_rules (
            slot_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            day_of_week TINYINT UNSIGNED NOT NULL COMMENT '0 for Sunday, 1 for Monday, ..., 6 for Saturday',
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            capacity INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Number of concurrent bookings allowed',
            is_active BOOLEAN NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (slot_id),
            INDEX user_id_day_idx (user_id, day_of_week)
            -- CONSTRAINT check_day_of_week CHECK (day_of_week BETWEEN 0 AND 6) -- Removed for dbDelta compatibility
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for availability_rules table: ' . preg_replace('/\s+/', ' ', $sql_availability_rules));
        $dbDelta_results['availability_rules'] = dbDelta( $sql_availability_rules );

        // Availability Exceptions Table (Specific Dates, formerly availability_overrides)
        $table_name_exceptions = self::get_table_name('availability_exceptions');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for availability_exceptions table: ' . $table_name_exceptions);
        $sql_availability_exceptions = "CREATE TABLE $table_name_exceptions (
            override_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            override_date DATE NOT NULL,
            start_time TIME,
            end_time TIME,
            capacity INT UNSIGNED DEFAULT 1,
            is_unavailable BOOLEAN NOT NULL DEFAULT 0 COMMENT 'If true, this whole day (or specific time range if start/end provided) is off',
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (override_id),
            UNIQUE KEY user_date_unique (user_id, override_date, start_time, end_time)
        ) $charset_collate;";
        // Note: The UNIQUE KEY on user_date_unique might be too restrictive if multiple distinct override slots are allowed on the same day.
        // For now, it assumes an override is for the whole day (is_unavailable=true) or one specific slot if start/end are given.
        // If multiple custom slots are needed for a single override day, this key needs adjustment or the table structure needs rethinking (e.g., a separate table for override slots).
        // For the initial plan (one override definition per day, or marking day off), this unique key is okay.
        // Let's adjust the unique key to be more flexible: a user can have multiple overrides on the same date IF they have different start/end times (or one is null for full day).
        // To simplify, let's assume for now an override either makes the whole day unavailable OR defines a single available block for that day.
        // A more robust solution might involve removing start_time/end_time from the override and linking to a new table `nordbooking_override_slots` if a date has custom slots.
        // For now, let's keep it simple: an override can define a single slot OR mark the day unavailable.
        // The unique key should probably be `user_id` and `override_date` if we only allow one "override instruction" per day.
        // Let's refine the unique key to `user_id, override_date` and handle logic in PHP if multiple slots are needed for an override day (e.g. by creating multiple override entries if that's the design).
        // For simplicity of initial implementation: one override entry per date. If it's not `is_unavailable`, then `start_time` and `end_time` define the single available block for that day.
        // Removing the start_time, end_time from unique key to allow a day to be marked unavailable, and also have specific slots (though that's a conflict, business logic must handle).
        // Let's stick to: an override is EITHER "day off" OR "this is the new schedule for the day".
        // So a unique key on (user_id, override_date) is appropriate. If start_time/end_time are NULL and is_unavailable=1, it's a day off.
        // If start_time/end_time are NOT NULL and is_unavailable=0, it's a custom slot.
        // It doesn't make sense to have is_unavailable=1 AND specific times. This will be handled by application logic.
        $sql_availability_exceptions = "CREATE TABLE $table_name_exceptions (
            exception_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, -- Renamed from override_id
            user_id BIGINT UNSIGNED NOT NULL,
            exception_date DATE NOT NULL, -- Renamed from override_date
            start_time TIME NULL, -- Null if is_unavailable is true for the whole day
            end_time TIME NULL,   -- Null if is_unavailable is true for the whole day
            capacity INT UNSIGNED DEFAULT 1, -- Relevant if not is_unavailable
            is_unavailable BOOLEAN NOT NULL DEFAULT 0 COMMENT 'True if this date is entirely unavailable, ignoring start/end time',
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (exception_id), -- Renamed from override_id
            UNIQUE KEY user_exception_date_unique (user_id, exception_date) -- Renamed from user_override_date_unique
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for availability_exceptions table: ' . preg_replace('/\s+/', ' ', $sql_availability_exceptions));
        $dbDelta_results['availability_exceptions'] = dbDelta( $sql_availability_exceptions );

        // Customers Table (formerly NORDBOOKING Customers / mob_customers)
        // Renamed to 'customers' to match the expected table name from the debug message.
        $table_name_customers = self::get_table_name('customers');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for customers table: ' . $table_name_customers);
        $sql_customers = "CREATE TABLE $table_name_customers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_user_id BIGINT UNSIGNED NULL, -- Link to WordPress user table if the customer is a registered WP user
            tenant_id BIGINT UNSIGNED NOT NULL, -- The business owner (user_id from wp_users) this customer belongs to
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone_number VARCHAR(50),
            address_line_1 VARCHAR(255),
            address_line_2 VARCHAR(255),
            city VARCHAR(100),
            state VARCHAR(100),
            zip_code VARCHAR(20),
            country VARCHAR(100),
            status VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'e.g., active, inactive, blacklisted',
            total_bookings INT UNSIGNED NOT NULL DEFAULT 0,
            last_booking_date DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_activity_at DATETIME NULL,
            PRIMARY KEY (id),
            INDEX tenant_id_email_idx (tenant_id, email), -- Unique customer per tenant by email
            INDEX tenant_id_status_idx (tenant_id, status),
            INDEX wp_user_id_idx (wp_user_id),
            INDEX tenant_id_idx (tenant_id)
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for customers table: ' . preg_replace('/\s+/', ' ', $sql_customers));
        $dbDelta_results['customers'] = dbDelta( $sql_customers );

        // Booking Meta Table (newly added)
        $table_name_booking_meta = self::get_table_name('booking_meta');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for booking_meta table: ' . $table_name_booking_meta);
        $sql_booking_meta = "CREATE TABLE $table_name_booking_meta (
            meta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT UNSIGNED NOT NULL,
            meta_key VARCHAR(255) DEFAULT NULL,
            meta_value LONGTEXT DEFAULT NULL,
            PRIMARY KEY (meta_id),
            INDEX booking_id_idx (booking_id),
            INDEX meta_key_idx (meta_key(191))
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for booking_meta table: ' . preg_replace('/\s+/', ' ', $sql_booking_meta));
        $dbDelta_results['booking_meta'] = dbDelta( $sql_booking_meta );

        // Subscriptions Table
        $table_name = self::get_table_name('subscriptions');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for subscriptions table: ' . $table_name);
        $sql_subscriptions = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            customer_id BIGINT UNSIGNED NOT NULL,
            tenant_id BIGINT UNSIGNED NOT NULL,
            stripe_subscription_id VARCHAR(255) NOT NULL,
            status VARCHAR(50) NOT NULL,
            current_period_start DATETIME,
            current_period_end DATETIME,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY stripe_subscription_id_unique (stripe_subscription_id),
            INDEX customer_id_idx (customer_id),
            INDEX tenant_id_idx (tenant_id),
            INDEX status_idx (status)
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for subscriptions table: ' . preg_replace('/\s+/', ' ', $sql_subscriptions));
        $dbDelta_results['subscriptions'] = dbDelta( $sql_subscriptions );

        // Subscription Items Table
        $table_name = self::get_table_name('subscription_items');
        error_log('[NORDBOOKING DB Debug] Preparing SQL for subscription_items table: ' . $table_name);
        $sql_subscription_items = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            subscription_id BIGINT UNSIGNED NOT NULL,
            stripe_subscription_item_id VARCHAR(255) NOT NULL,
            service_id BIGINT UNSIGNED NOT NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY stripe_subscription_item_id_unique (stripe_subscription_item_id),
            INDEX subscription_id_idx (subscription_id),
            INDEX service_id_idx (service_id)
        ) $charset_collate;";
        error_log('[NORDBOOKING DB Debug] SQL for subscription_items table: ' . preg_replace('/\s+/', ' ', $sql_subscription_items));
        $dbDelta_results['subscription_items'] = dbDelta( $sql_subscription_items );

        error_log('[NORDBOOKING DB Debug] dbDelta execution results: ' . print_r($dbDelta_results, true));
        error_log('[NORDBOOKING DB Debug] Custom tables creation/update attempt finished.');
    }

    public static function register_diagnostic_page() {
        add_submenu_page(
            'tools.php',
            'NORDBOOKING Database Diagnostic',
            'NORDBOOKING DB Diagnostic',
            'manage_options',
            'NORDBOOKING-db-diagnostic',
            array(__CLASS__, 'display_diagnostic_page')
        );
    }

    public static function display_diagnostic_page() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        global $wpdb;

        echo '<div class="wrap">';
        echo '<h1>🔍 NORDBOOKING Database Diagnostic</h1>';

        // Check bookings table
        $bookings_table = self::get_table_name('bookings');
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
            if (wp_verify_nonce($_POST['nonce'], 'nordbooking_db_fix')) {
                echo '<div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; margin: 10px 0;">';
                echo '<h3>🛠️ Running Database Fix...</h3>';

                $result = self::fix_database_tables();

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
        wp_nonce_field('nordbooking_db_fix', 'nonce');
        echo '<input type="hidden" name="action" value="fix_database">';
        echo '<button type="submit" class="button button-primary">🔧 Fix Database Tables</button>';
        echo '<p class="description">This will create/update the bookings table with the correct structure.</p>';
        echo '</form>';

        echo '</div>';
        echo '</div>';
    }

    public static function fix_database_tables() {
        global $wpdb;

        $result = ['success' => false, 'messages' => [], 'errors' => []];

        try {
            $charset_collate = $wpdb->get_charset_collate();
            $bookings_table = self::get_table_name('bookings');

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
}
