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
    private static $preset_icons_path; // Store the path to presets

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->service_options_manager = new ServiceOptions();
        // Define the path to the preset icons directory. MOBOOKING_PLUGIN_DIR should be defined in the main plugin file.
        self::$preset_icons_path = defined('MOBOOKING_PLUGIN_DIR') ? MOBOOKING_PLUGIN_DIR . 'assets/svg-icons/presets/' : plugin_dir_path(__FILE__) . '../../assets/svg-icons/presets/';
    }

    public static function get_preset_icon_svg(string $filename): ?string {
        $filepath = self::$preset_icons_path . sanitize_file_name($filename);
        if (file_exists($filepath) && strtolower(pathinfo($filepath, PATHINFO_EXTENSION)) === 'svg') {
            // It's crucial to sanitize SVG content before outputting it.
            // For simplicity here, assuming SVGs are trusted or pre-sanitized.
            // In a real scenario, use a proper SVG sanitization library.
            $content = file_get_contents($filepath);
            return Utils::sanitize_svg($content); // Assuming Utils::sanitize_svg exists and is robust
        }
        return null;
    }

    public static function get_all_preset_icons(): array {
        $icons = [];
        $path = self::$preset_icons_path;

        if (!is_dir($path) || !is_readable($path)) {
            error_log('[MoBooking Error] Preset icons directory not found or not readable at: ' . $path);
            return [];
        }

        $files = scandir($path);
        if ($files === false) {
            error_log('[MoBooking Error] Could not scan preset icons directory: ' . $path);
            return [];
        }

        foreach ($files as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'svg') {
                $filepath = $path . $file;
                $content = file_get_contents($filepath);
                if ($content) {
                    $icons[sanitize_file_name($file)] = Utils::sanitize_svg($content);
                } else {
                    error_log('[MoBooking Warning] Could not read content for preset icon: ' . $filepath);
                }
            }
        }

        return $icons;
    }

    public function get_service_icon_html(string $icon_identifier_or_url): string {
        if (strpos($icon_identifier_or_url, 'preset:') === 0) {
            $filename = substr($icon_identifier_or_url, strlen('preset:'));
            $svg_content = self::get_preset_icon_svg($filename);
            if ($svg_content) {
                return '<div class="mobooking-preset-icon">' . $svg_content . '</div>';
            }
            return '';
        } elseif (filter_var($icon_identifier_or_url, FILTER_VALIDATE_URL)) {
            return '<img src="' . esc_url($icon_identifier_or_url) . '" alt="Service Icon" class="mobooking-custom-icon"/>';
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
                'icon' => sanitize_text_field($service_data['icon']),
                'image_url' => esc_url_raw($service_data['image_url']),
                'status' => sanitize_text_field($service_data['status']),
                'created_at' => current_time('mysql', 1), // GMT
                'updated_at' => current_time('mysql', 1), // GMT
            ),
            array('%d', '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        if (false === $inserted) {
            // Log the actual database error
            error_log('[MoBooking Services DB Error] add_service failed: ' . $this->wpdb->last_error);
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
        error_log('[MoBooking Services Debug] get_services_by_user called for user_id: ' . $user_id . ' with args: ' . print_r($args, true));
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

        error_log('[MoBooking Services Debug] SQL query: ' . $this->wpdb->prepare($sql_select, ...$params));
        error_log('[MoBooking Services Debug] Found ' . count($services_data) . ' services.');

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
        // Category handling removed for update_service
        // if (array_key_exists('category', $data)) { ... }
        if (isset($data['icon'])) { $update_data['icon'] = sanitize_text_field($data['icon']); $update_formats[] = '%s'; }
        if (isset($data['image_url'])) { $update_data['image_url'] = esc_url_raw($data['image_url']); $update_formats[] = '%s'; }
        if (isset($data['status'])) { $update_data['status'] = sanitize_text_field($data['status']); $update_formats[] = '%s'; }

        if (empty($update_data)) {
            // If only 'category' was provided, $update_data might be empty now.
            // However, the original check for 'no_data' was before adding 'updated_at'.
            // If $update_data only contained 'category' and is now empty, we should probably not proceed.
            // For now, if $update_data is empty here, it means no *valid* fields were provided.
            return new \WP_Error('no_valid_data', __('No valid fields provided for update.', 'mobooking'));
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
            // Log the actual database error
            error_log('[MoBooking Services DB Error] update_service failed for service_id ' . $service_id . ': ' . $this->wpdb->last_error);
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

    public function register_actions() {
        add_action('wp_ajax_mobooking_get_services', [$this, 'handle_get_services_ajax']);
        add_action('admin_post_mobooking_delete_service', [$this, 'handle_mobooking_delete_service_form']);
        add_action('wp_ajax_mobooking_search_services', [$this, 'handle_search_services_ajax']);
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
        add_action('wp_ajax_nopriv_mobooking_get_public_service_options', [$this, 'handle_get_public_service_options_ajax']);
        add_action('wp_ajax_mobooking_get_public_service_options', [$this, 'handle_get_public_service_options_ajax']);

        // Icon AJAX Handlers
        add_action('wp_ajax_mobooking_get_preset_icons', [$this, 'handle_get_preset_icons_ajax']); // New handler
        add_action('wp_ajax_mobooking_upload_service_icon', [$this, 'handle_upload_service_icon_ajax']);
        add_action('wp_ajax_mobooking_delete_service_icon', [$this, 'handle_delete_service_icon_ajax']);

        // Image AJAX Handlers
        add_action('wp_ajax_mobooking_upload_service_image', [$this, 'handle_upload_service_image_ajax']);
        add_action('wp_ajax_mobooking_delete_service_image', [$this, 'handle_delete_service_image_ajax']);

        // Delete from edit page
        add_action('wp_ajax_mobooking_delete_service_ajax', [$this, 'handle_delete_service_ajax']);
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

        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        if (empty($service_id)) {
            wp_send_json_error(['message' => __('Service ID is required.', 'mobooking')], 400);
            return;
        }

        $result = $this->delete_service($service_id, $user_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 400);
        } else {
            wp_send_json_success(['message' => __('Service deleted successfully. Redirecting...', 'mobooking')]);
        }
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
        $custom_upload_dir_filter = function($dirs) use ($user_images_dir_base) {
            $dirs['path'] = $dirs['basedir'] . '/' . $user_images_dir_base;
            $dirs['url'] = $dirs['baseurl'] . '/' . $user_images_dir_base;
            $dirs['subdir'] = '/' . $user_images_dir_base; // Not strictly necessary but good for consistency
            return $dirs;
        };
        add_filter('upload_dir', $custom_upload_dir_filter);

        $uploaded_file = $_FILES['service_image'];
        // Generate a unique filename using WordPress function
        $unique_filename = wp_unique_filename($user_images_path, $uploaded_file['name']);
        $uploaded_file['name'] = $unique_filename; // Use the unique name

        $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

        // Remove the filter immediately after use
        remove_filter('upload_dir', $custom_upload_dir_filter);

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
    error_log('[MoBooking Debug] handle_get_public_services_ajax called.');
    // Check nonce
    if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
        error_log('[MoBooking Debug] Nonce verification failed.');
        wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
        return;
    }
    error_log('[MoBooking Debug] Nonce verified.');

    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    error_log('[MoBooking Debug] Tenant ID: ' . $tenant_id);
    if (empty($tenant_id)) {
        wp_send_json_error(['message' => __('Tenant ID is required.', 'mobooking')], 400);
        return;
    }

    // DEBUG: Log the request
    error_log('[MoBooking Services Debug] Getting public services for tenant_id: ' . $tenant_id);

    // Validate that the tenant user exists and has the right role
    $tenant_user = get_user_by('ID', $tenant_id);
    if (!$tenant_user) {
        error_log('[MoBooking Services Debug] Tenant user not found for ID: ' . $tenant_id);
        wp_send_json_error(['message' => __('Invalid business identifier.', 'mobooking')], 404);
        return;
    }

    if (!in_array('mobooking_business_owner', $tenant_user->roles)) {
        error_log('[MoBooking Services Debug] User ID ' . $tenant_id . ' is not a business owner. Roles: ' . print_r($tenant_user->roles, true));
        wp_send_json_error(['message' => __('Invalid business identifier.', 'mobooking')], 404);
        return;
    }

    // Get services using the existing method but with specific filters for public use
    try {
        $table_name = Database::get_table_name('services');
        
        // Get all active services for this user
        $all_services = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND status = 'active' ORDER BY name ASC",
            $tenant_id
        ), ARRAY_A);
        
        error_log('[MoBooking Services Debug] Raw database query returned ' . count($all_services) . ' active services');
        
        $services_for_public = [];
        
        if ($all_services) {
            $availability_manager = new Availability();
            foreach ($all_services as $service_item) {
                error_log('[MoBooking Services Debug] Processing service: ' . $service_item['name']);
                
                $item = (array) $service_item;
                
                // Enhanced price formatting
                if (isset($item['price']) && is_numeric($item['price'])) {
                    $item['price_formatted'] = number_format_i18n(floatval($item['price']), 2);
                    $item['price'] = floatval($item['price']); // Ensure it's a float for JS
                } else {
                    $item['price_formatted'] = __('Contact for pricing', 'mobooking');
                    $item['price'] = 0;
                }
                
                // Ensure duration is an integer
                $item['duration'] = isset($item['duration']) ? intval($item['duration']) : 60;
                
                // Sanitize output fields
                $item['service_id'] = intval($item['service_id']);
                $item['name'] = sanitize_text_field($item['name']);
                $item['description'] = wp_kses_post($item['description']);
                $item['category'] = sanitize_text_field($item['category'] ?? '');
                
                // Get service options if they exist
                $options_raw = $this->service_options_manager->get_service_options($item['service_id'], $tenant_id);
                $options = [];
                if (is_array($options_raw)) {
                    foreach ($options_raw as $opt) {
                        $option_array = (array) $opt;
                        // Sanitize option data
                        $option_array['option_id'] = intval($option_array['option_id']);
                        $option_array['name'] = sanitize_text_field($option_array['name']);
                        $option_array['description'] = wp_kses_post($option_array['description'] ?? '');
                        $option_array['type'] = sanitize_text_field($option_array['type']);
                        $option_array['required'] = (bool) ($option_array['required'] ?? false);
                        $option_array['price_impact'] = floatval($option_array['price_impact'] ?? 0);
                        $options[] = $option_array;
                    }
                }
                $item['options'] = $options;
                $item['availability'] = $availability_manager->get_recurring_schedule($tenant_id);
                
                $services_for_public[] = $item;
                error_log('[MoBooking Services Debug] Added service: ' . $item['name'] . ' with ' . count($options) . ' options');
            }
        }

        error_log('[MoBooking Services Debug] Final services count for public: ' . count($services_for_public));

        // Always return success with the services array (even if empty)
        wp_send_json_success($services_for_public);
        
    } catch (Exception $e) {
        error_log('[MoBooking Services Debug] Exception in handle_get_public_services_ajax: ' . $e->getMessage());
        wp_send_json_error(['message' => __('An error occurred while loading services.', 'mobooking')], 500);
    }
}


    public function handle_get_services_ajax() {
        error_log('[MoBooking Services Debug] handle_get_services_ajax reached.');
        error_log('[MoBooking Services Debug] POST data: ' . print_r($_POST, true));

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

        // Process icons before sending
        if (!empty($result['services'])) {
            foreach ($result['services'] as &$service) {
                if (!empty($service['icon'])) {
                    $service['icon'] = $this->get_service_icon_html($service['icon']);
                }
            }
            unset($service); // Unset reference
        }

        // get_services_by_user now returns an array with 'services', 'total_count' etc.
        // No need to check is_wp_error if it always returns this array structure.
        wp_send_json_success($result);
    }

    public function handle_mobooking_delete_service_form() {
        if (!isset($_POST['action']) || $_POST['action'] !== 'mobooking_delete_service') {
            wp_die('Invalid action.');
        }

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'mobooking_delete_service_nonce')) {
            wp_die('Nonce verification failed. Please go back and try again.');
        }

        $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
        $user_id = get_current_user_id();

        if (empty($service_id) || empty($user_id)) {
            wp_die('Missing required information (service id or user).');
        }

        // The delete_service method already verifies ownership
        $result = $this->delete_service($service_id, $user_id);

        if (is_wp_error($result)) {
            // Optional: Add a transient to show an error message on the services page
            set_transient('mobooking_admin_notice', ['type' => 'error', 'message' => $result->get_error_message()], 60);
        } else {
            // Optional: Add a transient for a success message
            set_transient('mobooking_admin_notice', ['type' => 'success', 'message' => __('Service deleted successfully.', 'mobooking')], 60);
        }

        // Redirect back to the services page
        $redirect_url = esc_url_raw(site_url('/dashboard/services/'));
        wp_redirect($redirect_url);
        exit;
    }

    public function handle_search_services_ajax() {
        if (!check_ajax_referer('mobooking_search_services_nonce', 'nonce', false)) {
            wp_die('Nonce verification failed.');
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die('User not logged in.');
        }

        $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';

        $args = [
            'search_query' => $search_query,
            'number' => 100,
            'status' => null,
        ];

        $result = $this->get_services_by_user($user_id, $args);
        $services_list = $result['services'] ?? [];

        ob_start();

        if (empty($services_list)) {
            // You can create a more styled empty state message here
            echo '<div class="text-center py-12"><p>' . esc_html__('No services found matching your search.', 'mobooking') . '</p></div>';
        } else {
            // To render the cards, we need access to some functions and variables from the main page.
            // This is a good reason to have a reusable template part.
            // Let's define what we need here.
            $settings_manager = new \MoBooking\Classes\Settings();
            $biz_settings = $settings_manager->get_business_settings($user_id);
            $currency_symbol = $biz_settings['biz_currency_symbol'] ?? '$';
            $currency_pos = $biz_settings['biz_currency_position'] ?? 'before';

            echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="services-grid">';

            // This is not ideal, but for now we define the function here.
            // A better solution is a utility class or putting it in the theme's functions.php
            if (!function_exists('format_currency')) {
                function format_currency($amount, $symbol, $position) {
                    $formatted_amount = number_format_i18n($amount, 2);
                    return $position === 'before' ? $symbol . $formatted_amount : $formatted_amount . $symbol;
                }
            }
             if (!function_exists('get_default_service_icon')) {
                function get_default_service_icon() {
                    return '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>';
                }
            }


            foreach ($services_list as $service) {
                // To avoid duplicating the entire card HTML, we should use a template part.
                // For now, let's assume `mobooking_get_template_part` exists and can load 'dashboard/template-parts/service-card.php'
                // set_query_var('service', $service);
                // load_template(MOBOOKING_PLUGIN_DIR . 'dashboard/template-parts/service-card.php');

                // Since I cannot create new files, I will duplicate the HTML here.
                // This is technical debt that should be addressed.
                $price_formatted = format_currency($service['price'], $currency_symbol, $currency_pos);
                $service_icon = !empty($service['icon'])
                    ? $this->get_service_icon_html($service['icon'])
                    : get_default_service_icon();
                $options_count = !empty($service['options']) ? count($service['options']) : 0;
                ?>
                <div class="card" data-service-id="<?php echo esc_attr($service['service_id']); ?>">
                    <div class="card-header p-0 relative">
                        <?php if (!empty($service['image_url'])): ?>
                            <img src="<?php echo esc_url($service['image_url']); ?>" alt="<?php echo esc_attr($service['name']); ?>" class="w-full h-48 object-cover">
                        <?php else: ?>
                            <div class="w-full h-48 bg-muted flex items-center justify-center">
                                <svg class="w-12 h-12 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                            </div>
                        <?php endif; ?>
                        <div class="badge badge-<?php echo esc_attr($service['status']); ?> absolute top-2 right-2"><?php echo esc_html(ucfirst($service['status'])); ?></div>
                    </div>
                    <div class="card-content p-4">
                        <div class="flex items-start gap-4 mb-4">
                            <div class="text-primary"><?php echo $service_icon; ?></div>
                            <div>
                                <h3 class="font-semibold"><?php echo esc_html($service['name']); ?></h3>
                                <p class="text-primary font-bold"><?php echo esc_html($price_formatted); ?></p>
                            </div>
                        </div>
                        <?php if (!empty($service['description'])): ?>
                            <p class="text-sm text-muted-foreground mb-4 line-clamp-3"><?php echo esc_html($service['description']); ?></p>
                        <?php endif; ?>
                        <div class="text-xs text-muted-foreground space-y-2">
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span><?php echo esc_html($service['duration']); ?> <?php esc_html_e('min', 'mobooking'); ?></span>
                            </div>
                            <?php if ($options_count > 0): ?>
                            <div class="flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M9 12l2 2 4-4"/><path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"/></svg>
                                <span><?php echo esc_html($options_count); ?> <?php esc_html_e('Options', 'mobooking'); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer p-4 flex gap-2">
                        <a href="<?php echo esc_url(site_url('/dashboard/service-edit/?service_id=' . $service['service_id'])); ?>" class="btn btn-primary w-full">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                            <?php esc_html_e('View', 'mobooking'); ?>
                        </a>
                        <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-service-name="<?php echo esc_attr($service['name']); ?>">
                            <input type="hidden" name="action" value="mobooking_delete_service">
                            <input type="hidden" name="service_id" value="<?php echo esc_attr($service['service_id']); ?>">
                            <?php wp_nonce_field('mobooking_delete_service_nonce'); ?>
                            <button type="submit" class="btn btn-destructive">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
        }

        $output = ob_get_clean();
        echo $output;
        wp_die();
    }


    // AJAX handler for service OPTIONS
    public function handle_get_public_service_options_ajax() {
        if (!check_ajax_referer('mobooking_booking_form_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }

        $service_ids_raw = isset($_POST['service_ids']) ? $_POST['service_ids'] : [];
        if (!is_array($service_ids_raw)) {
            $service_ids_raw = [$service_ids_raw];
        }
        $service_ids = array_map('intval', $service_ids_raw);

        if (empty($service_ids)) {
            wp_send_json_error(['message' => __('Service ID(s) are required.', 'mobooking')], 400);
            return;
        }

        $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
        if (empty($tenant_id)) {
            wp_send_json_error(['message' => __('Tenant ID is required.', 'mobooking')], 400);
            return;
        }

        $all_options = [];
        foreach ($service_ids as $service_id) {
            $options = $this->service_options_manager->get_service_options($service_id, $tenant_id);
            if (is_array($options)) {
                $all_options = array_merge($all_options, $options);
            }
        }

        wp_send_json_success($all_options);
    }

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

    // Prepare data, converting empty optional strings to null
    $icon_from_post = isset($_POST['icon']) ? trim($_POST['icon']) : '';
    $image_url_from_post = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';

        $data_for_service_method = [
        'name' => sanitize_text_field($trimmed_service_name),
            'description' => wp_kses_post(isset($_POST['description']) ? $_POST['description'] : ''),
        'price' => floatval($price_from_post),
        // Add category to the data prepared for save/update methods
        'category' => isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '', // Default to empty string if not set, for consistency
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

                    $option_type_for_values = isset($option_data_from_client['type']) ? sanitize_text_field($option_data_from_client['type']) : '';
                    $current_option_values_str = isset($option_data_from_client['option_values']) ? stripslashes($option_data_from_client['option_values']) : null;
                    $processed_option_values_for_db;

                    if ($option_type_for_values === 'sqm') {
                        // For SQM, option_values is already a JSON string of ranges from the client.
                        // ServiceOptions::add_service_option will validate this JSON string.
                        $processed_option_values_for_db = $current_option_values_str;
                    } elseif (in_array($option_type_for_values, ['select', 'radio'])) {
                        if (!is_null($current_option_values_str) && !empty(trim($current_option_values_str))) {
                            $decoded_values = json_decode($current_option_values_str, true);
                            if (is_array($decoded_values)) {
                                // Re-encode to ensure it's a valid JSON string for the DB,
                                // even if it was already JSON from client.
                                $processed_option_values_for_db = wp_json_encode($decoded_values);
                            } else {
                                $processed_option_values_for_db = wp_json_encode([]); // Default to empty JSON array
                                error_log("[MoBooking SaveSvc Debug] Invalid JSON for option_values (select/radio) for option '{$option_data_from_client['name']}': " . $current_option_values_str);
                            }
                        } else {
                            $processed_option_values_for_db = wp_json_encode([]); // Default to empty JSON array if no values provided
                        }
                    } else {
                        // For other types (text, number, checkbox, quantity, textarea), option_values is generally not used or is null.
                        // If a specific type needs to store something in option_values, handle it here.
                        $processed_option_values_for_db = null;
                    }

                    $clean_option_data = [
                        'name' => isset($option_data_from_client['name']) ? sanitize_text_field($option_data_from_client['name']) : '',
                        'description' => isset($option_data_from_client['description']) ? wp_kses_post($option_data_from_client['description']) : '',
                        'type' => $option_type_for_values,
                        'is_required' => !empty($option_data_from_client['is_required']) && $option_data_from_client['is_required'] === '1' ? 1 : 0,
                        // price_impact_type and price_impact_value are set to null for 'sqm' type in ServiceOptions class
                        'price_impact_type' => isset($option_data_from_client['price_impact_type']) ? sanitize_text_field($option_data_from_client['price_impact_type']) : null,
                        'price_impact_value' => !empty($option_data_from_client['price_impact_value']) ? floatval($option_data_from_client['price_impact_value']) : null,
                        'option_values' => $processed_option_values_for_db, // This is now correctly populated for SQM
                        'sort_order' => isset($option_data_from_client['sort_order']) ? intval($option_data_from_client['sort_order']) : 0,
                    ];

                    if (!empty($clean_option_data['name'])) {
                         error_log("[MoBooking SaveSvc Debug] Adding option for service_id {$service_id}, user_id {$user_id}. Option data: " . print_r($clean_option_data, true));
                         $option_result = $this->service_options_manager->add_service_option($user_id, $service_id, $clean_option_data);
                         error_log("[MoBooking SaveSvc Debug] Result of add_service_option for '{$clean_option_data['name']}': " . print_r($option_result, true));
                         if (is_wp_error($option_result)) {
                            error_log("[MoBooking SaveSvc Debug] Error adding service option '{$clean_option_data['name']}': " . $option_result->get_error_message());
                            // IMPORTANT: Send error and exit if an option fails to save
                            if (ob_get_length()) ob_clean();
                            wp_send_json_error(['message' => __('Error saving option: ', 'mobooking') . $option_result->get_error_message()], 400);
                            return; // Exit the handle_save_service_ajax function
                         } else {
                            error_log("[MoBooking SaveSvc Debug] Successfully added option '{$clean_option_data['name']}'. New option ID: " . $option_result);
                         }
                    } else {
                        error_log("[MoBooking SaveSvc Debug] Skipped processing option #{$idx} due to empty name.");
                    }
                }
                error_log('[MoBooking SaveSvc Debug] Finished processing service options.');

            } else if (!empty($_POST['service_options'])) { // This means service_options was sent, but json_decode failed
                 error_log('[MoBooking SaveSvc Debug] Error: service_options was provided but failed json_decode. Original: ' . $options_json);
                 if (ob_get_length()) ob_clean();
                 wp_send_json_error(['message' => __('Invalid format for service options data. Could not decode JSON.', 'mobooking')], 400);
                 return; // Exit the handle_save_service_ajax function
            }
            // If $_POST['service_options'] was not set at all, it's fine, just means no options to process.
        } else {
            error_log('[MoBooking SaveSvc Debug] service_options NOT SET in POST or was empty string.');
        }


        // If we've reached here, all options (if any) were saved successfully.
        $saved_service = $this->get_service($service_id, $user_id);
        error_log('[MoBooking SaveSvc Debug] Sending success response. Service ID: ' . $service_id . '. Message: ' . $message);

        if ($saved_service) {
            error_log('[MoBooking SaveSvc Debug] Final saved_service data: ' . print_r($saved_service, true));
            if (ob_get_length()) ob_clean(); // Clean buffer before sending JSON
            wp_send_json_success(['message' => $message, 'service' => $saved_service, 'service_id' => $service_id]);
        } else {
            // This case indicates an issue with get_service or the service was somehow deleted post-save.
            error_log('[MoBooking SaveSvc Debug] Error: Could not retrieve service (ID: ' . $service_id . ') after saving, though main service and options (if any) reported success.');
            if (ob_get_length()) ob_clean(); // Clean buffer
            wp_send_json_error(['message' => __('Service was saved, but could not be retrieved. Please check the services list.', 'mobooking')], 500);
        }
        // wp_send_json_success / wp_send_json_error call wp_die() internally, so no need for explicit die/return here.
        // Final ob_clean just in case, though it should be unreachable if wp_send_json_* is called.
        if (ob_get_length()) ob_end_clean();
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

    public function handle_get_preset_icons_ajax() {
        if (!check_ajax_referer('mobooking_services_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Error: Nonce verification failed.', 'mobooking')], 403);
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('User not logged in.', 'mobooking')], 403);
            return;
        }

        $preset_icons = self::get_all_preset_icons();
        wp_send_json_success(['icons' => $preset_icons]);
    }

    public function get_popular_services($tenant_id, $limit = 4) {
        $services_table = Database::get_table_name('services');
        $booking_items_table = Database::get_table_name('booking_items');
        $bookings_table = Database::get_table_name('bookings');

        // This query joins services with booking items and bookings to count how many times each service has been booked.
        $sql = "
            SELECT
                s.service_id,
                s.name,
                s.duration,
                s.price,
                COUNT(bi.item_id) as bookings_count
            FROM {$services_table} s
            LEFT JOIN {$booking_items_table} bi ON s.service_id = bi.service_id
            JOIN {$bookings_table} b ON bi.booking_id = b.booking_id
            WHERE b.user_id = %d AND s.status = 'active'
            GROUP BY s.service_id, s.name, s.duration, s.price
            ORDER BY bookings_count DESC
            LIMIT %d
        ";

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $tenant_id, $limit),
            ARRAY_A
        );

        return $results ?: [];
    }

    /**
     * Get top services by booking count and revenue
     * Add this method to the Services class
     */
    public function get_top_services($tenant_id, $limit = 5) {
        $services_table = Database::get_table_name('services');
        $bookings_table = Database::get_table_name('bookings');
        
        $sql = "
            SELECT 
                s.service_id,
                s.name,
                s.price,
                COUNT(b.booking_id) as bookings_count,
                SUM(b.total_price) as revenue
            FROM {$services_table} s
            LEFT JOIN {$bookings_table} b ON s.service_id = b.service_id AND b.user_id = %d
            WHERE s.user_id = %d AND s.status = 'active'
            GROUP BY s.service_id
            ORDER BY bookings_count DESC, revenue DESC
            LIMIT %d
        ";
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $tenant_id, $tenant_id, $limit),
            ARRAY_A
        );
        
        return $results ?: [];
    }
    /**
     * Get services count for a tenant
     * Add this method to the Services class
     */
    public function get_services_count($tenant_id) {
        $services_table = Database::get_table_name('services');
        
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$services_table} WHERE user_id = %d AND status = 'active'",
                $tenant_id
            )
        );
        
        return intval($count);
    }

    public function get_services_by_tenant_id($tenant_id) {
        error_log('[MoBooking Services Debug] Getting services for tenant_id: ' . $tenant_id);

        $table_name = Database::get_table_name('services');
        $sql = $this->wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d AND status = 'active'", $tenant_id);

        error_log('[MoBooking Services Debug] SQL: ' . $sql);

        $services = $this->wpdb->get_results($sql, ARRAY_A);

        if ($this->wpdb->last_error) {
            error_log('[MoBooking Services DB Error] get_services_by_tenant_id failed: ' . $this->wpdb->last_error);
            return new \WP_Error('db_error', __('Could not get services from the database.', 'mobooking'));
        }

        if ($services) {
            foreach ($services as $key => $service) {
                if (is_array($service)) { // Ensure it's an array before trying to access by key
                    $options_raw = $this->service_options_manager->get_service_options($service['service_id'], $tenant_id);
                    $options = [];
                    if (is_array($options_raw)) {
                        foreach ($options_raw as $opt) {
                            $options[] = (array) $opt;
                        }
                    }
                    $services[$key]['options'] = $options;
                }
            }
        }

        error_log('[MoBooking Services Debug] Found ' . count($services) . ' services for tenant_id: ' . $tenant_id);

        return $services;
    }
}
