<?php
/**
 * Class Services
 * Manages cleaning services and their options.
 * @package MoBooking\Classes
 */
namespace MoBooking\Classes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/ServiceOptions.php';

class Services {
    private $wpdb;
    private $service_options_manager;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->service_options_manager = new ServiceOptions();
    }

    private static $preset_icons = [
        'tools' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M21.69 18.56l-1.41-1.41c-.54-.54-1.29-.8-2.09-.69l-1.44.21c-.33.05-.6.31-.6.64v1.5c0 .28.22.5.5.5h.5c2.21 0 4-1.79 4-4v-.5c0-.33-.27-.59-.6-.54l-1.44.21c-.8.11-1.55.38-2.09.92L16.56 17H7.44l-1.41-1.41c-.54-.54-1.29-.8-2.09-.69l-1.44.21c-.33.05-.6.31-.6.64v1.5c0 .28.22.5.5.5h.5c2.21 0 4-1.79 4-4v-.5c0-.33-.27-.59-.6-.54l-1.44.21c-.8.11-1.55.38-2.09.92L1.94 17H1v-2.44l1.41-1.41c.54-.54.8-.1.69-2.09l-.21-1.44c-.05-.33.21-.6.54-.6h1.5c.28 0 .5.22.5.5v.5c0 2.21 1.79 4 4 4h.5c.33 0 .59-.27.54-.6l-.21-1.44c-.11-.8.15-1.55.92-2.09L12 7.44V1H9.56L8.14 2.41c-.54.54-.8 1.29-.69 2.09l.21 1.44c.05.33.31.6.64.6h1.5c.28 0 .5-.22.5-.5v-.5c0-2.21-1.79-4-4-4H1.5c-.33 0-.59.27-.54.6l.21 1.44c.11.8-.15 1.55-.92 2.09L-.44 7H-3v2.44l1.41 1.41c.54.54.8 1.29.69 2.09l-.21 1.44c-.05.33.21-.6.54-.6h1.5c.28 0 .5.22.5.5v.5c0 2.21 1.79 4 4 4h8c2.21 0 4-1.79 4-4v-.5c0-.28-.22-.5-.5-.5h-1.5c-.33 0-.6-.27-.6-.6l.21-1.44c.11-.8-.15-1.55-.92-2.09L14.56 10H12V7.44l1.41-1.41c.54-.54 1.29-.8 2.09-.69l1.44.21c.33.05.6.31.6.64v1.5c0 .28-.22.5-.5.5h-.5c-2.21 0-4 1.79-4 4v.5c0 .33.27.59.6.54l1.44-.21c.8-.11 1.55-.38 2.09-.92l1.41-1.41H21.69zM12 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>fill="currentColor" width="24px" height="24px"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>',
        'star' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
        'heart' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
        'home' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
        'settings' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M19.43 12.98c.04-.32.07-.64.07-.98s-.03-.66-.07-.98l2.11-1.65c.19-.15.24-.42.12-.64l-2-3.46c-.12-.22-.39-.3-.61-.22l-2.49 1c-.52-.4-1.08-.73-1.69-.98l-.38-2.65C14.46 2.18 14.25 2 14 2h-4c-.25 0-.46.18-.49.42l-.38 2.65c-.61.25-1.17.59-1.69.98l-2.49-1c-.23-.08-.49 0-.61.22l-2 3.46c-.13.22-.07.49.12.64l2.11 1.65c-.04.32-.07.65-.07.98s.03.66.07.98l-2.11 1.65c-.19.15-.24.42.12.64l2 3.46c.12.22.39.3.61.22l2.49-1c.52.4 1.08.73 1.69.98l.38 2.65c.03.24.24.42.49.42h4c.25 0 .46-.18.49-.42l.38-2.65c.61-.25 1.17-.59 1.69-.98l2.49 1c.23.08.49 0 .61-.22l2-3.46c.12-.22.07-.49-.12-.64l-2.11-1.65zM12 15.5c-1.93 0-3.5-1.57-3.5-3.5s1.57-3.5 3.5-3.5 3.5 1.57 3.5 3.5-1.57 3.5-3.5 3.5z"/></svg>',
        'cleaning' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M16 15H15V12C15 11.45 14.55 11 14 11H10C9.45 11 9 11.45 9 12V15H8C7.45 15 7 15.45 7 16V20C7 20.55 7.45 21 8 21H16C16.55 21 17 20.55 17 20V16C17 15.45 16.55 15 16 15ZM15 19H9V17H15V19ZM14 15H10V13H14V15ZM21.67 10.02C21.42 8.33 20.06 7 18.34 7H17V3C17 2.45 16.55 2 16 2H8C7.45 2 7 2.45 7 3V7H5.66C3.94 7 2.58 8.33 2.33 10.02L2 12V13H6V12L6.33 10.03C6.55 8.91 7.43 8 8.55 8H15.45C16.57 8 17.45 8.91 17.67 10.03L18 12V13H22V12L21.67 10.02Z"/></svg>',
        'mop' => '<svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><g><rect fill="none" height="24" width="24"/></g><g><g><path d="M21.6,6.29l-2.89-2.89c-0.38-0.38-0.89-0.59-1.42-0.59H16V2c0-0.55-0.45-1-1-1H9C8.45,1,8,1.45,8,2v1h0c0,0,0,0,0,0l0,0H6.71 c-0.53,0-1.04,0.21-1.42,0.59L2.41,6.29C2.04,6.66,1.96,7.27,2.2,7.76l2.89,5.78c0.25,0.5,0.79,0.82,1.35,0.82h11.12 c0.56,0,1.1-0.32,1.35-0.82l2.89-5.78C22.04,7.27,21.96,6.66,21.6,6.29z M15,3h-2v1h2V3z M6.41,5H10v1H5.09L6.41,5z M18.91,6 H14V5h3.59L18.91,6z M19.07,13H4.93L3.12,9h17.76L19.07,13z"/><path d="M6,16h12c0.55,0,1-0.45,1-1v-2c0-0.55-0.45-1-1-1H6c-0.55,0-1,0.45-1,1v2C5,15.55,5.45,16,6,16z M7,14h10v1H7V14z"/><rect height="6" width="12" x="6" y="17"/></g></g></svg>',
        'bucket' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="24px" height="24px"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 4h-3.5l-1-1h-5l-1 1H5v2h14V4zM6 7v12c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6zm8 7v4h-4v-4H8l4-4 4 4h-2z"/></svg>',
    ];

    public static function get_preset_icon_svg(string $key): ?string {
        return self::$preset_icons[$key] ?? null;
    }

    public static function get_all_preset_icons(): array {
        // Return keys and SVG content, or modify if only keys are needed for selection UI
        return self::$preset_icons;
    }

    public function get_service_icon_html(string $icon_identifier_or_url): string {
        if (strpos($icon_identifier_or_url, 'preset:') === 0) {
            $key = substr($icon_identifier_or_url, strlen('preset:'));
            $svg_content = self::get_preset_icon_svg($key);
            return $svg_content ?: ''; // Return raw SVG content or empty string
        } elseif (!empty($icon_identifier_or_url)) {
            // For a URL, return the URL itself for client-side handling (e.g. <img src="..."> or fetch)
            // Or if it's a path to a local SVG file that should be inlined server-side (more complex)
            // For now, returning the URL is simpler and safer for custom SVGs.
            // If it's a full URL, it can be used directly. If it's a relative path from uploads, it needs to be converted to URL.
            // Assuming $icon_identifier_or_url is a full URL if not a preset.
            return esc_url($icon_identifier_or_url); // Ensure it's a safe URL
        }
        return '';
    }

    // --- Ownership Verification Helper Methods ---

    private function _verify_service_ownership(int $service_id, int $user_id): bool {
        if (empty($service_id) || empty($user_id)) return false;
        $table_name = Database::get_table_name('services');
        $service = $this->wpdb->get_var( $this->wpdb->prepare( "SELECT service_id FROM $table_name WHERE service_id = %d AND user_id = %d", $service_id, $user_id ) );
        return !is_null($service);
    }

    // --- Service CRUD Methods ---

    public function add_service(int $user_id, array $data) {
        if ( empty($user_id) ) {
            return new \WP_Error('invalid_user', __('Invalid user ID.', 'mobooking'));
        }
        if ( empty($data['name']) ) {
            return new \WP_Error('missing_name', __('Service name is required.', 'mobooking'));
        }

        $defaults = array(
            'description' => '',
            'price' => 0.00,
            'duration' => 30, // Default duration in minutes
            'icon' => '',
            'image_url' => '',
            'status' => 'active'
        );
        $service_data = wp_parse_args($data, $defaults);

        $table_name = Database::get_table_name('services');

        $inserted = $this->wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'name' => sanitize_text_field($service_data['name']),
                'description' => wp_kses_post($service_data['description']),
                'price' => floatval($service_data['price']),
                'duration' => intval($service_data['duration']),
                'category' => sanitize_text_field($service_data['category']),
                'icon' => sanitize_text_field($service_data['icon']),
                'image_url' => esc_url_raw($service_data['image_url']),
                'status' => sanitize_text_field($service_data['status']),
                'created_at' => current_time('mysql', 1), // GMT
                'updated_at' => current_time('mysql', 1), // GMT
            ),
            array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if (false === $inserted) {
            return new \WP_Error('db_error', __('Could not add service to the database.', 'mobooking'));
        }
        return $this->wpdb->insert_id;
    }

    public function get_service(int $service_id, int $user_id) {
        if ( empty($user_id) || empty($service_id) ) {
            return null;
        }
        if ( !$this->_verify_service_ownership($service_id, $user_id) ) {
            error_log('[MoBooking get_service] Ownership verification failed for service_id: ' . $service_id . ' and user_id: ' . $user_id);
            return null; // Or WP_Error for permission denied
        }
        $table_name = Database::get_table_name('services');
        $service = $this->wpdb->get_row( $this->wpdb->prepare( "SELECT * FROM $table_name WHERE service_id = %d AND user_id = %d", $service_id, $user_id ), ARRAY_A );

        if (is_null($service)) {
            error_log('[MoBooking get_service] Service not found in database for service_id: ' . $service_id . ' and user_id: ' . $user_id);
        }

        if ($service) {
            // Ensure options are fetched as an array of arrays (consistent with get_service_options)
            $options_raw = $this->service_options_manager->get_service_options($service_id, $user_id); // This returns array of arrays/objects
            $options = [];
            if (is_array($options_raw)) {
                foreach ($options_raw as $opt) {
                    $options[] = (array) $opt; // Cast to array if objects
                }
            }
            error_log('[MoBooking get_service] Fetched ' . count($options) . ' options for service_id: ' . $service_id);
            $service['options'] = $options;
        }
        return $service;
    }

    public function get_services_by_user(int $user_id, array $args = []) {
        if ( empty($user_id) ) {
            return array();
        }
        $defaults = array(
            'status' => 'active',
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => 20, // Similar to posts_per_page
            'offset' => 0,
            'search_query' => '' // Added from previous correct version
        );
        $args = wp_parse_args($args, $defaults);

        $table_name = Database::get_table_name('services');

        // Base SQL and parameters for counting
        $sql_count_base = " FROM $table_name WHERE user_id = %d";
        $params = [$user_id];

        // Build WHERE clause for filtering
        $sql_where = "";
        if ( !empty($args['status']) ) {
            $sql_where .= " AND status = %s";
            $params[] = $args['status'];
        }
        // Category filter removed
        if ( !empty($args['search_query']) ) {
            $search_term = '%' . $this->wpdb->esc_like($args['search_query']) . '%';
            $sql_where .= " AND (name LIKE %s OR description LIKE %s)"; // Category search removed
            $params[] = $search_term;
            $params[] = $search_term;
        }

        // Get total count
        $total_count_sql = "SELECT COUNT(service_id)" . $sql_count_base . $sql_where;
        $total_count = $this->wpdb->get_var($this->wpdb->prepare($total_count_sql, ...$params));

        // SQL for fetching services data
        $sql_select = "SELECT *" . $sql_count_base . $sql_where;

        // Order and pagination
        $valid_orderby_columns = ['service_id', 'name', 'price', 'duration', 'status', 'created_at', 'updated_at']; // Category removed
        $orderby = in_array($args['orderby'], $valid_orderby_columns) ? $args['orderby'] : 'name';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $sql_select .= " ORDER BY " . $orderby . " " . $order;
        $sql_select .= $this->wpdb->prepare(" LIMIT %d OFFSET %d", $args['number'], $args['offset']);

        $services_data = $this->wpdb->get_results($this->wpdb->prepare($sql_select, ...$params), ARRAY_A);

        if ($services_data) {
            foreach ($services_data as $key => $service) {
                if (is_array($service)) { // Ensure it's an array before trying to access by key
                    $options_raw = $this->service_options_manager->get_service_options($service['service_id'], $user_id);
                    $options = [];
                    if (is_array($options_raw)) {
                        foreach ($options_raw as $opt) {
                            $options[] = (array) $opt;
                        }
                    }
                    $services_data[$key]['options'] = $options;
                }
            }
        }
        // Return data and pagination info
        return [
            'services' => $services_data,
            'total_count' => intval($total_count),
            'per_page' => intval($args['number']),
            'current_page' => intval($args['offset'] / $args['number']) + 1,
        ];
    }

    public function update_service(int $service_id, int $user_id, array $data) {
        if ( empty($user_id) || empty($service_id) ) {
            return new \WP_Error('invalid_ids', __('Invalid service or user ID.', 'mobooking'));
        }
        if ( !$this->_verify_service_ownership($service_id, $user_id) ) {
            return new \WP_Error('not_owner', __('You do not own this service.', 'mobooking'));
        }
        if ( empty($data) ) {
            return new \WP_Error('no_data', __('No data provided for update.', 'mobooking'));
        }

        $table_name = Database::get_table_name('services');

        // Prepare data and formats dynamically based on what's provided
        $update_data = array();
        $update_formats = array();

        if (isset($data['name'])) { $update_data['name'] = sanitize_text_field($data['name']); $update_formats[] = '%s'; }
        if (isset($data['description'])) { $update_data['description'] = wp_kses_post($data['description']); $update_formats[] = '%s'; }
        if (isset($data['price'])) { $update_data['price'] = floatval($data['price']); $update_formats[] = '%f'; }
        if (isset($data['duration'])) { $update_data['duration'] = intval($data['duration']); $update_formats[] = '%d'; }
        if (isset($data['icon'])) { $update_data['icon'] = sanitize_text_field($data['icon']); $update_formats[] = '%s'; }
        if (isset($data['image_url'])) { $update_data['image_url'] = esc_url_raw($data['image_url']); $update_formats[] = '%s'; }
        if (isset($data['status'])) { $update_data['status'] = sanitize_text_field($data['status']); $update_formats[] = '%s'; }

        if (empty($update_data)) {
            return new \WP_Error('no_valid_data', __('No valid data provided for update.', 'mobooking'));
        }
        $update_data['updated_at'] = current_time('mysql', 1); // GMT
        $update_formats[] = '%s';

        $updated = $this->wpdb->update(
            $table_name,
            $update_data,
            array('service_id' => $service_id, 'user_id' => $user_id),
            $update_formats,
            array('%d', '%d')
        );

        if (false === $updated) {
            return new \WP_Error('db_error', __('Could not update service in the database.', 'mobooking'));
        }
        return true; // Or $updated which is number of rows affected
    }

    public function delete_service(int $service_id, int $user_id) {
        if ( empty($user_id) || empty($service_id) ) {
            return new \WP_Error('invalid_ids', __('Invalid service or user ID.', 'mobooking'));
        }
        if ( !$this->_verify_service_ownership($service_id, $user_id) ) {
            return new \WP_Error('not_owner', __('You do not own this service.', 'mobooking'));
        }

        // Delete associated options first
        $this->service_options_manager->delete_options_for_service($service_id, $user_id); // This also verifies ownership

        $table_name = Database::get_table_name('services');
        $deleted = $this->wpdb->delete(
            $table_name,
            array('service_id' => $service_id, 'user_id' => $user_id),
            array('%d', '%d')
        );

        if (false === $deleted) {
            return new \WP_Error('db_error', __('Could not delete service from the database.', 'mobooking'));
        }
        return true;
    }

    // --- AJAX Handlers ---

    public function register_ajax_actions() {
        add_action('wp_ajax_mobooking_get_services', [$this, 'handle_get_services_ajax']);
        add_action('wp_ajax_mobooking_delete_service', [$this, 'handle_delete_service_ajax']);
        add_action('wp_ajax_mobooking_save_service', [$this, 'handle_save_service_ajax']); // Covers Create and Update for service + options

        // AJAX handlers for individual service options
        add_action('wp_ajax_mobooking_get_service_options', [$this, 'handle_get_service_options_ajax']);
        add_action('wp_ajax_mobooking_add_service_option', [$this, 'handle_add_service_option_ajax']);
        add_action('wp_ajax_mobooking_update_service_option', [$this, 'handle_update_service_option_ajax']);
        add_action('wp_ajax_mobooking_delete_service_option', [$this, 'handle_delete_service_option_ajax']);
        add_action('wp_ajax_mobooking_get_service_details', [$this, 'handle_get_service_details_ajax']); // For editing

        // For public booking form
        add_action('wp_ajax_nopriv_mobooking_get_public_services', [$this, 'handle_get_public_services_ajax']);
        add_action('wp_ajax_mobooking_get_public_services', [$this, 'handle_get_public_services_ajax']);

        // Icon AJAX Handlers
        add_action('wp_ajax_mobooking_upload_service_icon', [$this, 'handle_upload_service_icon_ajax']);
        add_action('wp_ajax_mobooking_delete_service_icon', [$this, 'handle_delete_service_icon_ajax']);

        // Image AJAX Handlers
        add_action('wp_ajax_mobooking_upload_service_image', [$this, 'handle_upload_service_image_ajax']);
        add_action('wp_ajax_mobooking_delete_service_image', [$this, 'handle_delete_service_image_ajax']);
    }

    public function handle_upload_service_image_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        if (empty($_FILES['service_image'])) {
            wp_send_json_error(['message' => __('No image file uploaded.', 'mobooking')], 400);
            return;
        }

        $file_error = $_FILES['service_image']['error'];
        if ($file_error !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('File upload error code: ' . $file_error, 'mobooking')], 400);
            return;
        }

        // WordPress's own upload handling function
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }

        $upload_overrides = [
            'test_form' => false, // Required when not submitted from a form
            'mimes' => ['jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'],
        ];

        // Custom directory within uploads
        $upload_dir_info = wp_upload_dir();
        $user_images_dir_base = 'mobooking-images/' . $user_id;
        $user_images_path = $upload_dir_info['basedir'] . '/' . $user_images_dir_base;
        $user_images_url = $upload_dir_info['baseurl'] . '/' . $user_images_dir_base;

        if (!file_exists($user_images_path)) {
            wp_mkdir_p($user_images_path);
        }

        // Modify the upload path
        add_filter('upload_dir', function($dirs) use ($user_images_dir_base) {
            $dirs['path'] = $dirs['basedir'] . '/' . $user_images_dir_base;
            $dirs['url'] = $dirs['baseurl'] . '/' . $user_images_dir_base;
            $dirs['subdir'] = '/' . $user_images_dir_base; // Not strictly necessary but good for consistency
            return $dirs;
        });

        $uploaded_file = $_FILES['service_image'];
        // Generate a unique filename using WordPress function
        $unique_filename = wp_unique_filename($user_images_path, $uploaded_file['name']);
        $uploaded_file['name'] = $unique_filename; // Use the unique name

        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

        // Remove the filter immediately after use
        remove_filter('upload_dir', 'upload_dir'); // This needs a proper way to remove the anonymous function

        if ($movefile && !isset($movefile['error'])) {
            // $movefile contains 'url', 'file' (path), 'type'
            wp_send_json_success([
                'message' => __('Image uploaded successfully.', 'mobooking'),
                'image_url' => $movefile['url'],
                'file_path' => $movefile['file'] // For reference, not usually sent to client
            ]);
        } else {
            wp_send_json_error(['message' => isset($movefile['error']) ? $movefile['error'] : __('Image upload failed.', 'mobooking')], 500);
        }
    }

    public function handle_delete_service_image_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $image_url_to_delete = isset($_POST['image_url_to_delete']) ? esc_url_raw($_POST['image_url_to_delete']) : '';
        if (empty($image_url_to_delete)) {
            wp_send_json_error(['message' => __('Image URL is required for deletion.', 'mobooking')], 400);
            return;
        }

        $upload_dir_info = wp_upload_dir();

        // Check if the image URL starts with the base upload URL. If not, it's not one of ours.
        if (strpos($image_url_to_delete, $upload_dir_info['baseurl']) !== 0) {
            wp_send_json_error(['message' => __('Invalid image URL. Not a site upload.', 'mobooking')], 400);
            return;
        }

        // Construct the file path from the URL
        $relative_path = str_replace($upload_dir_info['baseurl'], '', $image_url_to_delete);
        $file_path = wp_normalize_path($upload_dir_info['basedir'] . $relative_path);

        // Security check: Ensure the file is within the user's specific directory
        $expected_user_dir_fragment = wp_normalize_path('/mobooking-images/' . $user_id . '/');
        $expected_base_dir = wp_normalize_path($upload_dir_info['basedir']);

        // Check if the file_path starts with the user's specific image directory
        if (strpos($file_path, $expected_base_dir . $expected_user_dir_fragment) !== 0) {
            error_log("Security Alert: User {$user_id} attempted to delete file outside their directory: " . $file_path . ". Expected fragment: " . $expected_user_dir_fragment);
            wp_send_json_error(['message' => __('Access denied or invalid path for deletion.', 'mobooking')], 403);
            return;
        }

        if (file_exists($file_path)) {
            if (wp_delete_file($file_path)) { // wp_delete_file is generally preferred
                wp_send_json_success(['message' => __('Image deleted successfully.', 'mobooking')]);
            } else {
                wp_send_json_error(['message' => __('Could not delete the image file.', 'mobooking')], 500);
            }
        } else {
            wp_send_json_error(['message' => __('Image file not found at specified path.', 'mobooking')], 404);
        }
    }

    public function handle_upload_service_icon_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        if (empty($_FILES['service_icon_svg'])) {
            wp_send_json_error(['message' => __('No file uploaded.', 'mobooking')], 400);
            return;
        }

        $file = $_FILES['service_icon_svg'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('File upload error: ' . $file['error'], 'mobooking')], 400);
            return;
        }

        // Check file type and extension
        $file_type = mime_content_type($file['tmp_name']);
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file_type !== 'image/svg+xml' || $file_ext !== 'svg') {
            wp_send_json_error(['message' => __('Invalid file type. Only SVG files are allowed.', 'mobooking')], 400);
            return;
        }

        // Sanitize SVG content
        $svg_content = file_get_contents($file['tmp_name']);
        if ($svg_content === false) {
            wp_send_json_error(['message' => __('Could not read SVG file content.', 'mobooking')], 500);
            return;
        }

        // Basic check for script tags before more complex sanitization
        if (strpos($svg_content, '<script') !== false) {
            wp_send_json_error(['message' => __('SVG content appears to contain script tags, which are not allowed.', 'mobooking')], 400);
            return;
        }
        $sanitized_svg_content = Utils::sanitize_svg($svg_content);


        // Define upload path
        $upload_dir_info = wp_upload_dir();
        $icons_dir = $upload_dir_info['basedir'] . '/mobooking-icons/' . $user_id . '/';
        if (!file_exists($icons_dir)) {
            wp_mkdir_p($icons_dir); // Creates directory recursively
        }

        $service_id_for_filename = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $filename = 'service_icon_' . ($service_id_for_filename ?: uniqid()) . '.svg';
        $filepath = $icons_dir . $filename;
        $fileurl = $upload_dir_info['baseurl'] . '/mobooking-icons/' . $user_id . '/' . $filename;

        if (file_put_contents($filepath, $sanitized_svg_content) === false) {
            wp_send_json_error(['message' => __('Could not save sanitized SVG file.', 'mobooking')], 500);
            return;
        }

        wp_send_json_success(['message' => __('Icon uploaded successfully.', 'mobooking'), 'icon_url' => $fileurl]);
    }

    public function handle_delete_service_icon_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $icon_url = isset($_POST['icon_url']) ? esc_url_raw($_POST['icon_url']) : '';
        if (empty($icon_url)) {
            wp_send_json_error(['message' => __('Icon URL is required.', 'mobooking')], 400);
            return;
        }

        // Construct path from URL
        $upload_dir_info = wp_upload_dir();
        $base_url = $upload_dir_info['baseurl'] . '/mobooking-icons/' . $user_id . '/';
        $base_dir = $upload_dir_info['basedir'] . '/mobooking-icons/' . $user_id . '/';

        // Check if the icon_url starts with the user's icon directory URL
        if (strpos($icon_url, $base_url) !== 0) {
            wp_send_json_error(['message' => __('Invalid icon URL or permission denied.', 'mobooking')], 400);
            return;
        }

        $filename = basename($icon_url);
        $filepath = $base_dir . $filename;

        if (file_exists($filepath)) {
            if (unlink($filepath)) {
                wp_send_json_success(['message' => __('Icon deleted successfully.', 'mobooking')]);
            } else {
                wp_send_json_error(['message' => __('Could not delete icon file.', 'mobooking')], 500);
            }
        } else {
            wp_send_json_error(['message' => __('Icon file not found.', 'mobooking')], 404);
        }
    }

    public function handle_get_public_services_ajax() {
        if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        if (empty($tenant_id)) {
            wp_send_json_error(['message' => __('Tenant ID is required.', 'mobooking')], 400);
            return;
        }

        // Get only active services for public view.
        // get_services_by_user now returns an array with 'services', 'total_count' etc.
        $result = $this->get_services_by_user($tenant_id, ['status' => 'active', 'number' => -1]); // -1 for all active

        if (is_wp_error($result)) { // Should not happen if get_services_by_user returns array
            wp_send_json_error(['message' => __('Error retrieving services.', 'mobooking')], 500);
        } else {
            $services_for_public = [];
            if (!empty($result['services'])) {
                foreach ($result['services'] as $service_item) {
                    $item = (array) $service_item;
                    if (isset($item['price'])) {
                        $item['price_formatted'] = number_format_i18n(floatval($item['price']), 2);
                    } else {
                        $item['price_formatted'] = __('N/A', 'mobooking');
                    }
                    if (!isset($item['options']) || !is_array($item['options'])) {
                        $item['options'] = [];
                    }
                    $services_for_public[] = $item;
                }
            }
            wp_send_json_success($services_for_public);
        }
    }

    public function handle_get_services_ajax() {
        // error_log('[MoBooking Services Debug] handle_get_services_ajax reached.');
        // error_log('[MoBooking Services Debug] POST data: ' . print_r($_POST, true));

        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $args = [
            'status' => isset($_POST['status_filter']) ? sanitize_text_field($_POST['status_filter']) : null,
            'search_query' => isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : null,
            'number' => isset($_POST['per_page']) ? intval($_POST['per_page']) : 20,
            'offset' => (isset($_POST['paged']) && intval($_POST['paged']) > 0) ? (intval($_POST['paged']) - 1) * intval(isset($_POST['per_page']) ? $_POST['per_page'] : 20) : 0,
            'orderby' => isset($_POST['orderby']) ? sanitize_key($_POST['orderby']) : 'name',
            'order' => isset($_POST['order']) ? sanitize_key($_POST['order']) : 'ASC',
        ];
        if (empty($args['status'])) $args['status'] = null; // Ensure 'all' statuses if filter is empty string

        $result = $this->get_services_by_user($user_id, $args);

        // get_services_by_user now returns an array with 'services', 'total_count' etc.
        // No need to check is_wp_error if it always returns this array structure.
        wp_send_json_success($result);
    }

    public function handle_delete_service_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }
        if (!isset($_POST['service_id']) || !is_numeric($_POST['service_id'])) {
            wp_send_json_error(['message' => __('Invalid service ID.', 'mobooking')], 400);
            return;
        }
        $service_id = intval($_POST['service_id']);
        $result = $this->delete_service($service_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], ('not_owner' === $result->get_error_code() ? 403 : 500) );
        } elseif ($result) {
            wp_send_json_success(['message' => __('Service deleted successfully.', 'mobooking')]);
        } else {
            // This case might not be reached if delete_service always returns WP_Error on failure
            wp_send_json_error(['message' => __('Could not delete service.', 'mobooking')], 500);
        }
    }

    // AJAX handler for service OPTIONS
    public function handle_get_service_options_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        if (empty($service_id)) { wp_send_json_error(['message' => __('Service ID is required.', 'mobooking')], 400); return; }

        // Verify parent service ownership first
        if (!$this->_verify_service_ownership($service_id, $user_id)) {
            wp_send_json_error(['message' => __('Service not found or permission denied.', 'mobooking')], 404); return;
        }

        $options = $this->service_options_manager->get_service_options($service_id, $user_id);
        wp_send_json_success($options);
    }

    public function handle_add_service_option_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $option_data_json = isset($_POST['option_data']) ? stripslashes_deep($_POST['option_data']) : '';
        $option_data = json_decode($option_data_json, true);

        if (empty($service_id) || json_last_error() !== JSON_ERROR_NONE || empty($option_data)) {
            wp_send_json_error(['message' => __('Service ID and valid option data are required.', 'mobooking')], 400);
            return;
        }

        // Verify parent service ownership
        if (!$this->_verify_service_ownership($service_id, $user_id)) {
            wp_send_json_error(['message' => __('Service not found or permission denied for adding option.', 'mobooking')], 404); return;
        }

        // Sanitize option_values if it's part of $option_data and needs to be JSON
        if (isset($option_data['option_values']) && is_array($option_data['option_values'])) {
            $option_data['option_values'] = wp_json_encode($option_data['option_values']);
        }


        $result = $this->service_options_manager->add_service_option($user_id, $service_id, $option_data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        } else {
            $new_option_id = $result;
            $new_option = $this->service_options_manager->get_service_option($new_option_id, $user_id);
            wp_send_json_success(['message' => __('Service option added successfully.', 'mobooking'), 'option' => $new_option]);
        }
    }

    public function handle_update_service_option_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $option_id = isset($_POST['option_id']) ? intval($_POST['option_id']) : 0;
        $option_data_json = isset($_POST['option_data']) ? stripslashes_deep($_POST['option_data']) : '';
        $option_data = json_decode($option_data_json, true);

        if (empty($option_id) || json_last_error() !== JSON_ERROR_NONE || empty($option_data)) {
            wp_send_json_error(['message' => __('Option ID and valid option data are required.', 'mobooking')], 400);
            return;
        }

        // Sanitize option_values if it's part of $option_data and needs to be JSON
        // The update_service_option method in ServiceOptions class already handles wp_json_encode
        // if $option_data['option_values'] is passed as an array.

        $result = $this->service_options_manager->update_service_option($option_id, $user_id, $option_data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], ($result->get_error_code() === 'not_owner' ? 403 : 400) );
        } else {
            $updated_option = $this->service_options_manager->get_service_option($option_id, $user_id);
            wp_send_json_success(['message' => __('Service option updated successfully.', 'mobooking'), 'option' => $updated_option]);
        }
    }

    public function handle_delete_service_option_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) { wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403); return; }

        $option_id = isset($_POST['option_id']) ? intval($_POST['option_id']) : 0;
        if (empty($option_id)) {
            wp_send_json_error(['message' => __('Option ID is required.', 'mobooking')], 400);
            return;
        }

        $result = $this->service_options_manager->delete_service_option($option_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], ($result->get_error_code() === 'not_owner' ? 403 : 400) );
        } else {
            wp_send_json_success(['message' => __('Service option deleted successfully.', 'mobooking')]);
        }
    }

    public function handle_save_service_ajax() {
        ob_start();
        // die('[DEBUG] Reached handle_save_service_ajax start.');
        error_log('[MoBooking SaveSvc Debug] handle_save_service_ajax reached.');
        error_log('[MoBooking SaveSvc Debug] RAW POST data: ' . print_r($_POST, true));

        $nonce_verified = check_ajax_referer('mobooking_services_nonce', 'nonce', false); // false to not die
        if (!$nonce_verified) {
            error_log('[MoBooking SaveSvc Debug] Nonce verification FAILED.');
            if (ob_get_length()) ob_clean();
            wp_send_json_error(['message' => __('Nonce verification failed. Please refresh and try again.', 'mobooking')], 403);
            return;
        }
        // die('[DEBUG] Nonce verification PASSED.');
        error_log('[MoBooking SaveSvc Debug] Nonce verified successfully.');

        $user_id = get_current_user_id();
        if (!$user_id) {
            error_log('[MoBooking SaveSvc Debug] User not logged in.');
            if (ob_get_length()) ob_clean();
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }
        // die('[DEBUG] User ID check PASSED. User ID: ' . $user_id);
        error_log('[MoBooking SaveSvc Debug] User ID: ' . $user_id);

        $service_id = isset($_POST['service_id']) && !empty($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        error_log('[MoBooking SaveSvc Debug] Initial service_id from POST: ' . (isset($_POST['service_id']) ? $_POST['service_id'] : 'Not Set') . ', Processed service_id: ' . $service_id);
        // die('[DEBUG] Service ID determined: ' . $service_id);
        error_log('[MoBooking SaveSvc Debug] Service ID for save/update: ' . $service_id);

    $service_name_from_post = isset($_POST['name']) ? (string) $_POST['name'] : '';
    $trimmed_service_name = trim($service_name_from_post);

    if (empty($trimmed_service_name)) {
        error_log('[MoBooking SaveSvc Debug] Validation Error: Service name (after trim) is required. Original POST name: \'' . $service_name_from_post . '\'');
            if (ob_get_length()) ob_clean();
            wp_send_json_error(['message' => __('Service name is required.', 'mobooking')], 400);
            return;
        }

    $price_from_post = isset($_POST['price']) ? $_POST['price'] : null;
    if (is_null($price_from_post) || !is_numeric($price_from_post)) {
        error_log('[MoBooking SaveSvc Debug] Validation Error: Valid price is required. Received: ' . print_r($price_from_post, true));
            if (ob_get_length()) ob_clean();
            wp_send_json_error(['message' => __('Valid price is required.', 'mobooking')], 400);
            return;
        }

    $duration_from_post = isset($_POST['duration']) ? strval($_POST['duration']) : null;
    if (is_null($duration_from_post) || !ctype_digit($duration_from_post) || intval($duration_from_post) <=0) {
        error_log('[MoBooking SaveSvc Debug] Validation Error: Duration validation failed. Input: \'' . print_r($duration_from_post, true) . '\'. Must be a positive integer.');
            if (ob_get_length()) ob_clean();
        wp_send_json_error(['message' => __('Valid positive duration (integer) is required.', 'mobooking')], 400);
            return;
        }

    // Prepare data, converting empty optional strings to null
    $icon_from_post = isset($_POST['icon']) ? trim($_POST['icon']) : '';
    $image_url_from_post = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';

        $data_for_service_method = [
        'name' => sanitize_text_field($trimmed_service_name),
            'description' => wp_kses_post(isset($_POST['description']) ? $_POST['description'] : ''),
        'price' => floatval($price_from_post),
        'duration' => intval($duration_from_post),
        'icon' => !empty($icon_from_post) ? sanitize_text_field($icon_from_post) : null,
        'image_url' => !empty($image_url_from_post) ? esc_url_raw($image_url_from_post) : null,
            'status' => sanitize_text_field(isset($_POST['status']) ? $_POST['status'] : 'active'),
        ];
    error_log('[MoBooking SaveSvc Debug] Data for add/update_service (with nulls for empty optionals): ' . print_r($data_for_service_method, true));

        $result_service_save = null;
        $message = '';

        // die('[DEBUG] All initial validations passed. About to call add/update service. Service ID: ' . $service_id);
        if ($service_id) { // Update
            error_log('[MoBooking SaveSvc Debug] UPDATE path. Service ID: ' . $service_id);
            error_log('[MoBooking SaveSvc Debug] Attempting to update service ID: ' . $service_id);
            $result_service_save = $this->update_service($service_id, $user_id, $data_for_service_method);
            $message = __('Service updated successfully.', 'mobooking');
        } else { // Add
            error_log('[MoBooking SaveSvc Debug] ADD path. Data for new service: ' . print_r($data_for_service_method, true));
            error_log('[MoBooking SaveSvc Debug] Attempting to add new service.');
            $result_service_save = $this->add_service($user_id, $data_for_service_method);
            error_log('[MoBooking SaveSvc Debug] Result of add_service: ' . print_r($result_service_save, true));
            // $message is set after successful ID validation
            // if (!is_wp_error($result_service_save)) { // This check is now part of the more robust validation below
            //      $service_id = $result_service_save;
            //      error_log('[MoBooking SaveSvc Debug] NEW service_id assigned for options: ' . $service_id);
            //      error_log('[MoBooking SaveSvc Debug] New service added with ID: ' . $service_id);
            // }
        }

        if (is_wp_error($result_service_save)) {
            error_log('[MoBooking SaveSvc Debug] Error saving/updating service (WP_Error): ' . $result_service_save->get_error_message());
            if (ob_get_length()) ob_clean();
            wp_send_json_error(['message' => $result_service_save->get_error_message()], ('not_owner' === $result_service_save->get_error_code() ? 403 : 500) );
            return;
        }

        // If execution reaches here, and it was an 'add' operation, $result_service_save holds the potential new ID.
        // If it was an 'update' operation, $result_service_save is likely true/number of rows, and $service_id is already set.
        if (isset($_POST['service_id']) && !empty($_POST['service_id'])) { // Was an update
             // $service_id is already the correct ID for update. $result_service_save is true or num rows.
             // No special handling needed here for $service_id itself if it was an update.
        } else { // This was an ADD operation
            // More robust check for the result of add_service
            if (empty($result_service_save) || !is_numeric($result_service_save) || intval($result_service_save) <= 0) {
                error_log('[MoBooking SaveSvc Debug] add_service did not return a valid new Service ID. Result: ' . print_r($result_service_save, true));
                if (ob_get_length()) ob_clean();
                wp_send_json_error(['message' => __('Failed to create the new service (ID was invalid), so options could not be saved.', 'mobooking')], 500);
                return; // Stop execution
            }
            $service_id = intval($result_service_save); // Ensure it's an integer
            error_log('[MoBooking SaveSvc Debug] NEW service_id assigned after robust check: ' . $service_id);
            // $message was already set to "Service added successfully."
        }
        // $message is already set based on add/update path.
        error_log('[MoBooking SaveSvc Debug] Service main data saved/updated successfully for service_id: ' . $service_id);

        // Process service options if provided
        error_log('[MoBooking SaveSvc Debug] Checking for service_options. Current service_id: ' . $service_id);
        if (isset($_POST['service_options'])) {
            error_log('[MoBooking SaveSvc Debug] service_options POST data: ' . print_r($_POST['service_options'], true));
            $options_json = stripslashes($_POST['service_options']);
            error_log('[MoBooking SaveSvc Debug] Received service_options JSON string: ' . $options_json);
            $options = json_decode($options_json, true);

            if (is_array($options)) {
                error_log('[MoBooking SaveSvc Debug] Decoded options array: ' . print_r($options, true));
                error_log('[MoBooking SaveSvc Debug] Deleting existing options for service_id: ' . $service_id);
                $this->service_options_manager->delete_options_for_service($service_id, $user_id);

                foreach ($options as $idx => $option_data_from_client) {
                    error_log("[MoBooking SaveSvc Debug] Processing option #{$idx}: " . print_r($option_data_from_client, true));

                    $current_option_values_str = isset($option_data_from_client['option_values']) ? stripslashes($option_data_from_client['option_values']) : null;
                    $processed_option_values_for_db = null;
                    $option_type_for_values = isset($option_data_from_client['type']) ? sanitize_text_field($option_data_from_client['type']) : '';

                    if (!is_null($current_option_values_str) && !empty(trim($current_option_values_str))) {
                        if (in_array($option_type_for_values, ['select', 'radio'])) {
                            $decoded_values = json_decode($current_option_values_str, true);
                            if (is_array($decoded_values)) {
                                $processed_option_values_for_db = wp_json_encode($decoded_values);
                            } else {
                                $processed_option_values_for_db = wp_json_encode([]);
                                error_log("[MoBooking SaveSvc Debug] Invalid JSON for option_values (select/radio) for option '{$option_data_from_client['name']}': " . $current_option_values_str);
                            }
                        } else {
                            $processed_option_values_for_db = null;
                        }
                    } else if (in_array($option_type_for_values, ['select', 'radio'])) {
                        $processed_option_values_for_db = wp_json_encode([]);
                    }

                    $clean_option_data = [
                        'name' => isset($option_data_from_client['name']) ? sanitize_text_field($option_data_from_client['name']) : '',
                        'description' => isset($option_data_from_client['description']) ? wp_kses_post($option_data_from_client['description']) : '',
                        'type' => $option_type_for_values,
                        'is_required' => !empty($option_data_from_client['is_required']) && $option_data_from_client['is_required'] === '1' ? 1 : 0,
                        'price_impact_type' => isset($option_data_from_client['price_impact_type']) ? sanitize_text_field($option_data_from_client['price_impact_type']) : null,
                        'price_impact_value' => !empty($option_data_from_client['price_impact_value']) ? floatval($option_data_from_client['price_impact_value']) : null,
                        'option_values' => $processed_option_values_for_db,
                        'sort_order' => isset($option_data_from_client['sort_order']) ? intval($option_data_from_client['sort_order']) : 0,
                    ];

                    if (!empty($clean_option_data['name'])) {
                         error_log("[MoBooking SaveSvc Debug] Adding option for service_id {$service_id}, user_id {$user_id}. Option data: " . print_r($clean_option_data, true));
                         // error_log("[MoBooking SaveSvc Debug] Adding/updating option: " . print_r($clean_option_data, true)); // Original log, slightly redundant
                         $option_result = $this->service_options_manager->add_service_option($user_id, $service_id, $clean_option_data);
                         error_log("[MoBooking SaveSvc Debug] Result of add_service_option for '{$clean_option_data['name']}': " . print_r($option_result, true));
                         if (is_wp_error($option_result)) {
                            error_log("[MoBooking SaveSvc Debug] Error adding service option '{$clean_option_data['name']}': " . $option_result->get_error_message());
                         } else {
                            error_log("[MoBooking SaveSvc Debug] Successfully added option '{$clean_option_data['name']}'. New option ID: " . $option_result);
                         }
                    } else {
                        error_log("[MoBooking SaveSvc Debug] Skipped processing option #{$idx} due to empty name.");
                    }
                }
                error_log('[MoBooking SaveSvc Debug] Finished processing service options.');

            } else if (!empty($_POST['service_options'])) {
                 error_log('[MoBooking SaveSvc Debug] Error: service_options was not empty but failed json_decode. Original: ' . $options_json);
                 if (ob_get_length()) ob_clean();
                 wp_send_json_error(['message' => __('Invalid format for service options data.', 'mobooking')], 400);
                 return;
            }
        } else {
            error_log('[MoBooking SaveSvc Debug] service_options NOT SET in POST.');
            error_log('[MoBooking SaveSvc Debug] No service_options provided in POST.');
        }

        $saved_service = $this->get_service($service_id, $user_id);
        error_log('[MoBooking SaveSvc Debug] Sending response. Service ID: ' . $service_id . '. Message: ' . $message);
        if ($saved_service) {
            error_log('[MoBooking SaveSvc Debug] Final saved_service data: ' . print_r($saved_service, true));
            error_log('[MoBooking SaveSvc Debug] Successfully saved and retrieved service. Sending success response.');
            if (ob_get_length()) ob_clean();
            wp_send_json_success(['message' => $message, 'service' => $saved_service, 'service_id' => $service_id]); // Also explicitly send service_id
        } else {
            error_log('[MoBooking SaveSvc Debug] Error: Could not retrieve service after saving. Service ID: ' . $service_id);
            if (ob_get_length()) ob_clean();
            wp_send_json_error(['message' => __('Could not retrieve service after saving.', 'mobooking')], 500);
            // No explicit return here, as wp_send_json_error will die.
            // However, if it didn't, the ob_end_clean below would catch it.
        }
        // wp_die(); // Not strictly necessary as wp_send_json_* calls wp_die().
        if (ob_get_length()) ob_end_clean(); // Final cleanup if buffer is still active.
    }

    public function handle_get_service_details_ajax() {
        error_log('[MoBooking ServiceDetails AJAX] Received POST: ' . print_r($_POST, true));
        // Make check_ajax_referer not die, so we can send a custom JSON response
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Error: User not authenticated.', 'mobooking')], 401); // 401 Unauthorized
            return;
        }

        if (!isset($_POST['service_id']) || empty($_POST['service_id']) || !is_numeric($_POST['service_id'])) {
            wp_send_json_error(['message' => __('Error: Service ID is missing or invalid.', 'mobooking')], 400);
            return;
        }
        $service_id = (int) $_POST['service_id'];

        error_log('[MoBooking ServiceDetails AJAX] Calling get_service for service_id: ' . $service_id);
        $service_details = $this->get_service($service_id, $user_id);

        if (is_wp_error($service_details)) {
            error_log('[MoBooking ServiceDetails AJAX] get_service returned WP_Error. Message: ' . $service_details->get_error_message() . ' Code: ' . $service_details->get_error_code()); // Added more detail
            wp_send_json_error(['message' => $service_details->get_error_message()], 500);
            return;
        } elseif (empty($service_details)) {
            error_log('[MoBooking ServiceDetails AJAX] get_service returned null or empty.');
            wp_send_json_error(['message' => __('Error: Service not found or access denied.', 'mobooking')], 404); // Kept original response for client
            return;
        } else {
            error_log('[MoBooking ServiceDetails AJAX] get_service returned data for service_id: ' . $service_id);
        }

        wp_send_json_success(['service' => $service_details]); // Ensure data is keyed under 'service' if JS expects response.data.service
    }
}
