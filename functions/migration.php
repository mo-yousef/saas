<?php
/**
 * Database Migration for Service Options Price Type Enhancement
 * This migration adds the new price_type and price_value fields while maintaining backward compatibility
 */
namespace MoBooking\Functions;
use MoBooking\Classes\Database;

if (!class_exists('ServiceOptionsPriceMigration')) {
    class ServiceOptionsPriceMigration {
        private $wpdb;

        public function __construct() {
            global $wpdb;
            $this->wpdb = $wpdb;
        }

        /**
         * Run the migration
         */
        public function migrate() {
            $table_name = Database::get_table_name('service_options');

            // Schema changes are handled by dbDelta. This script now only handles data migration.
            $this->migrate_existing_data($table_name);

            return true;
        }

        /**
         * Migrate existing data from legacy fields to new fields
         */
        private function migrate_existing_data($table_name) {
            // Get all existing options with legacy pricing information.
            // The version check in the calling hook prevents this from running multiple times.
            $existing_options = $this->wpdb->get_results(
                "SELECT option_id, price_impact_type, price_impact_value, type
                 FROM {$table_name}
                 WHERE price_impact_type IS NOT NULL AND price_impact_type != ''",
                ARRAY_A
            );

            if (empty($existing_options)) {
                error_log("MoBooking Migration: No legacy service options found to migrate.");
                return;
            }

            foreach ($existing_options as $option) {
                $option_id = $option['option_id'];
                $legacy_type = $option['price_impact_type'];
                $legacy_value = $option['price_impact_value'];
                $option_type = $option['type'];

                // Convert legacy type to new type
                $new_price_type = $this->convert_legacy_to_new_price_type($legacy_type, $option_type);
                $new_price_value = $legacy_value;

                // Update the record
                $this->wpdb->update(
                    $table_name,
                    [
                        'price_type' => $new_price_type,
                        'price_value' => $new_price_value,
                        'updated_at' => current_time('mysql', 1)
                    ],
                    ['option_id' => $option_id],
                    ['%s', '%f', '%s'],
                    ['%d']
                );
            }

            error_log("MoBooking Migration: Migrated " . count($existing_options) . " service options to new pricing structure");
        }

        /**
         * Convert legacy price_impact_type to new price_type
         */
        private function convert_legacy_to_new_price_type($legacy_type, $option_type) {
            // For range-based options, always use no_price as pricing is handled in ranges
            if (in_array($option_type, ['sqm', 'kilometers'])) {
                return 'no_price';
            }

            switch ($legacy_type) {
                case 'fixed':
                    return 'fixed';
                case 'percentage':
                    return 'percentage';
                case 'multiply':
                    return 'multiplication';
                case null:
                case '':
                default:
                    return 'fixed'; // Default to fixed for existing options without pricing
            }
        }
    }
}


/**
 * Helper function to run the migration
 */
function mobooking_run_price_migration() {
    $migration = new \MoBooking\Functions\ServiceOptionsPriceMigration();
    return $migration->migrate();
}

/**
 * Hook to run migration on plugin activation or update
 */
add_action('admin_init', function() {
    $current_version = get_option('mobooking_db_version', '1.0.0');
    $target_version = '1.1.0'; // Version with price type features

    if (version_compare($current_version, $target_version, '<')) {
        if (mobooking_run_price_migration()) {
            update_option('mobooking_db_version', $target_version);
            error_log("MoBooking: Successfully migrated to version {$target_version}");
        } else {
            error_log("MoBooking: Failed to migrate to version {$target_version}");
        }
    }
});
