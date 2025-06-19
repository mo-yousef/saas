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

        // Customers Table
        $table_name = self::get_table_name('customers');
        error_log('[MoBooking DB Debug] Preparing SQL for customers table: ' . $table_name);
        $sql_customers = "CREATE TABLE $table_name (
            customer_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            address_line_1 VARCHAR(255),
            address_line_2 VARCHAR(255),
            city VARCHAR(100),
            state VARCHAR(100),
            zip_code VARCHAR(20),
            country VARCHAR(100),
            notes TEXT,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (customer_id),
            INDEX user_id_idx (user_id),
            INDEX email_idx (email)
        ) $charset_collate;";
        error_log('[MoBooking DB Debug] SQL for customers table: ' . preg_replace('/\s+/', ' ', $sql_customers));
        $dbDelta_results['customers'] = dbDelta( $sql_customers );

        // Discount Codes Table
        $table_name = self::get_table_name('discount_codes');
        error_log('[MoBooking DB Debug] Preparing SQL for discount_codes table: ' . $table_name);
        $sql_discount_codes = "CREATE TABLE $table_name (
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
        error_log('[MoBooking DB Debug] SQL for discount_codes table: ' . preg_replace('/\s+/', ' ', $sql_discount_codes));
        $dbDelta_results['discount_codes'] = dbDelta( $sql_discount_codes );

        // Bookings Table
        $table_name = self::get_table_name('bookings');
        error_log('[MoBooking DB Debug] Preparing SQL for bookings table: ' . $table_name);
        $sql_bookings = "CREATE TABLE $table_name (
            booking_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            customer_id BIGINT UNSIGNED,
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
            INDEX customer_id_idx (customer_id),
            INDEX customer_email_idx (customer_email),
            INDEX zip_code_idx (zip_code),
            INDEX status_idx (status),
            INDEX discount_id_idx (discount_id)
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

        // Service Areas Table
        $table_name = self::get_table_name('service_areas');
        error_log('[MoBooking DB Debug] Preparing SQL for service_areas table: ' . $table_name);
        $sql_service_areas = "CREATE TABLE $table_name (
            area_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            area_type VARCHAR(50) NOT NULL,
            area_value VARCHAR(255) NOT NULL,
            country_code VARCHAR(10),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (area_id),
            INDEX user_id_idx (user_id)
        ) $charset_collate;";
        error_log('[MoBooking DB Debug] SQL for service_areas table: ' . preg_replace('/\s+/', ' ', $sql_service_areas));
        $dbDelta_results['service_areas'] = dbDelta( $sql_service_areas );

        error_log('[MoBooking DB Debug] dbDelta execution results: ' . print_r($dbDelta_results, true));
        error_log('[MoBooking DB Debug] Custom tables creation/update attempt finished.');
    }
}
