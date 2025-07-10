<?php
/**
 * Class Database
 * Handles custom database table creation and interaction.
 * @package MoBooking\Classes
 */
namespace MoBooking\Classes;

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
        return $wpdb->prefix . 'mobooking_' . $name;
    }

    public static function create_tables() {
        error_log('[MoBooking DB Debug] Attempting to create/update custom tables...');
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $charset_collate = $wpdb->get_charset_collate();
        $dbDelta_results = [];

        // Services Table
        $table_name = self::get_table_name('services');
        error_log('[MoBooking DB Debug] Preparing SQL for services table: ' . $table_name);
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
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (service_id),
            INDEX user_id_idx (user_id),
            INDEX status_idx (status)
        ) $charset_collate;";
        error_log('[MoBooking DB Debug] SQL for services table: ' . preg_replace('/\s+/', ' ', $sql_services)); // Log condensed SQL
        $dbDelta_results['services'] = dbDelta( $sql_services );

        // Service Options Table
        $table_name = self::get_table_name('service_options');
        error_log('[MoBooking DB Debug] Preparing SQL for service_options table: ' . $table_name);
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
        error_log('[MoBooking DB Debug] SQL for service_options table: ' . preg_replace('/\s+/', ' ', $sql_service_options));
        $dbDelta_results['service_options'] = dbDelta( $sql_service_options );

        // NOTE: The 'customers' table definition below is being removed as it was redundant.
        // The correct table is 'mob_customers', defined later in this method.
        // $table_name = self::get_table_name('customers');
        // error_log('[MoBooking DB Debug] Preparing SQL for customers table: ' . $table_name);
        // $sql_customers = "CREATE TABLE $table_name ( ... ) $charset_collate;";
        // error_log('[MoBooking DB Debug] SQL for customers table: ' . preg_replace('/\s+/', ' ', $sql_customers));
        // $dbDelta_results['customers'] = dbDelta( $sql_customers );

        // Discounts Table (formerly discount_codes)
        $table_name = self::get_table_name('discounts');
        error_log('[MoBooking DB Debug] Preparing SQL for discounts table: ' . $table_name);
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
        error_log('[MoBooking DB Debug] SQL for discounts table: ' . preg_replace('/\s+/', ' ', $sql_discounts));
        $dbDelta_results['discounts'] = dbDelta( $sql_discounts );

        // Bookings Table
        $table_name = self::get_table_name('bookings');
        error_log('[MoBooking DB Debug] Preparing SQL for bookings table: ' . $table_name);
        $sql_bookings = "CREATE TABLE $table_name (
            booking_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL, -- Tenant ID (Business Owner)
            customer_id BIGINT UNSIGNED, -- Original field, maybe WordPress user ID of customer if they are registered users.
            mob_customer_id BIGINT UNSIGNED NULL, -- FK to mobooking_customers table (formerly mob_customers)
            customer_name VARCHAR(255) NOT NULL,
            customer_email VARCHAR(255) NOT NULL,
            customer_phone VARCHAR(50),
            service_address TEXT NOT NULL,
            zip_code VARCHAR(20),
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            special_instructions TEXT,
            total_price DECIMAL(10,2) NOT NULL,
            discount_id BIGINT UNSIGNED,
            discount_amount DECIMAL(10,2) DEFAULT 0.00,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            booking_reference VARCHAR(100) UNIQUE,
            payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (booking_id),
            INDEX user_id_idx (user_id),
            INDEX customer_id_idx (customer_id), -- Original customer_id index
            INDEX mob_customer_id_idx (mob_customer_id), -- Index for the new customer ID
            INDEX customer_email_idx (customer_email),
            INDEX zip_code_idx (zip_code),
            INDEX status_idx (status),
            INDEX discount_id_idx (discount_id),
            assigned_staff_id BIGINT UNSIGNED NULL, -- ID of the assigned staff member (WP User ID)
            INDEX assigned_staff_id_idx (assigned_staff_id)
        ) $charset_collate;";
        error_log('[MoBooking DB Debug] SQL for bookings table: ' . preg_replace('/\s+/', ' ', $sql_bookings));
        $dbDelta_results['bookings'] = dbDelta( $sql_bookings );

        // Booking Items Table
        $table_name = self::get_table_name('booking_items');
        error_log('[MoBooking DB Debug] Preparing SQL for booking_items table: ' . $table_name);
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
        error_log('[MoBooking DB Debug] SQL for booking_items table: ' . preg_replace('/\s+/', ' ', $sql_booking_items));
        $dbDelta_results['booking_items'] = dbDelta( $sql_booking_items );

        // Tenant Settings Table
        $table_name = self::get_table_name('tenant_settings');
        error_log('[MoBooking DB Debug] Preparing SQL for tenant_settings table: ' . $table_name);
        $sql_tenant_settings = "CREATE TABLE $table_name (
            setting_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            setting_name VARCHAR(255) NOT NULL,
            setting_value LONGTEXT,
            PRIMARY KEY (setting_id),
            UNIQUE KEY user_setting_unique (user_id, setting_name(191))
        ) $charset_collate;";
        error_log('[MoBooking DB Debug] SQL for tenant_settings table: ' . preg_replace('/\s+/', ' ', $sql_tenant_settings));
        $dbDelta_results['tenant_settings'] = dbDelta( $sql_tenant_settings );

        // Areas Table (formerly service_areas)
        $table_name = self::get_table_name('areas');
        error_log('[MoBooking DB Debug] Preparing SQL for areas table: ' . $table_name);
        $sql_areas = "CREATE TABLE $table_name (
            area_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            area_type VARCHAR(50) NOT NULL,
            area_value VARCHAR(255) NOT NULL,
            country_code VARCHAR(10),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (area_id),
            INDEX user_id_idx (user_id)
        ) $charset_collate;";
        error_log('[MoBooking DB Debug] SQL for areas table: ' . preg_replace('/\s+/', ' ', $sql_areas));
        $dbDelta_results['areas'] = dbDelta( $sql_areas );

        // Availability Rules Table (Recurring, formerly availability_slots)
        $table_name_rules = self::get_table_name('availability_rules');
        error_log('[MoBooking DB Debug] Preparing SQL for availability_rules table: ' . $table_name_rules);
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
        error_log('[MoBooking DB Debug] SQL for availability_rules table: ' . preg_replace('/\s+/', ' ', $sql_availability_rules));
        $dbDelta_results['availability_rules'] = dbDelta( $sql_availability_rules );

        // Availability Exceptions Table (Specific Dates, formerly availability_overrides)
        $table_name_exceptions = self::get_table_name('availability_exceptions');
        error_log('[MoBooking DB Debug] Preparing SQL for availability_exceptions table: ' . $table_name_exceptions);
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
        // A more robust solution might involve removing start_time/end_time from the override and linking to a new table `mobooking_override_slots` if a date has custom slots.
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
        error_log('[MoBooking DB Debug] SQL for availability_exceptions table: ' . preg_replace('/\s+/', ' ', $sql_availability_exceptions));
        $dbDelta_results['availability_exceptions'] = dbDelta( $sql_availability_exceptions );

        // Customers Table (formerly MoBooking Customers / mob_customers)
        // Renamed to 'customers' to match the expected table name from the debug message.
        $table_name_customers = self::get_table_name('customers');
        error_log('[MoBooking DB Debug] Preparing SQL for customers table: ' . $table_name_customers);
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
        error_log('[MoBooking DB Debug] SQL for customers table: ' . preg_replace('/\s+/', ' ', $sql_customers));
        $dbDelta_results['customers'] = dbDelta( $sql_customers );

        // Booking Meta Table (newly added)
        $table_name_booking_meta = self::get_table_name('booking_meta');
        error_log('[MoBooking DB Debug] Preparing SQL for booking_meta table: ' . $table_name_booking_meta);
        $sql_booking_meta = "CREATE TABLE $table_name_booking_meta (
            meta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT UNSIGNED NOT NULL,
            meta_key VARCHAR(255) DEFAULT NULL,
            meta_value LONGTEXT DEFAULT NULL,
            PRIMARY KEY (meta_id),
            INDEX booking_id_idx (booking_id),
            INDEX meta_key_idx (meta_key(191))
        ) $charset_collate;";
        error_log('[MoBooking DB Debug] SQL for booking_meta table: ' . preg_replace('/\s+/', ' ', $sql_booking_meta));
        $dbDelta_results['booking_meta'] = dbDelta( $sql_booking_meta );

        error_log('[MoBooking DB Debug] dbDelta execution results: ' . print_r($dbDelta_results, true));
        error_log('[MoBooking DB Debug] Custom tables creation/update attempt finished.');
    }
}
