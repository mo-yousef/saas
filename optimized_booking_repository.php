<?php
/**
 * Optimized Booking Repository Pattern Implementation
 * 
 * This file provides an optimized data access layer for bookings
 * that addresses the performance issues identified in the audit.
 * 
 * @package NORDBOOKING\Repository
 */

namespace NORDBOOKING\Repository;

use NORDBOOKING\Performance\QueryProfiler;
use NORDBOOKING\Performance\CacheManager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Optimized Booking Repository
 */
class BookingRepository {
    private $wpdb;
    private $bookings_table;
    private $booking_items_table;
    private $services_table;
    private $customers_table;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->bookings_table = $wpdb->prefix . 'nordbooking_bookings';
        $this->booking_items_table = $wpdb->prefix . 'nordbooking_booking_items';
        $this->services_table = $wpdb->prefix . 'nordbooking_services';
        $this->customers_table = $wpdb->prefix . 'nordbooking_customers';
    }
    
    /**
     * Get bookings with pagination and optimized queries
     */
    public function findByUserWithPagination($user_id, $page = 1, $per_page = 20, $filters = []) {
        QueryProfiler::start('booking_pagination', ['user_id' => $user_id, 'page' => $page]);
        
        $cache_key = "user_bookings_{$user_id}_page_{$page}_" . md5(serialize($filters));
        $cached_result = CacheManager::get($cache_key);
        
        if ($cached_result !== null) {
            QueryProfiler::end('booking_pagination', ['cache_hit' => true]);
            return $cached_result;
        }
        
        $offset = ($page - 1) * $per_page;
        $where_clauses = ['b.user_id = %d'];
        $params = [$user_id];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $where_clauses[] = 'b.status = %s';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'b.booking_date >= %s';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'b.booking_date <= %s';
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $where_clauses[] = '(b.customer_name LIKE %s OR b.customer_email LIKE %s OR b.booking_reference LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        // Optimized query with single JOIN to get all data
        $sql = "
            SELECT 
                b.*,
                GROUP_CONCAT(bi.service_name ORDER BY bi.item_id SEPARATOR ', ') as service_names,
                COUNT(bi.item_id) as service_count,
                SUM(bi.item_total_price) as calculated_total,
                c.full_name as customer_full_name,
                c.phone_number as customer_phone_alt
            FROM {$this->bookings_table} b
            LEFT JOIN {$this->booking_items_table} bi ON b.booking_id = bi.booking_id
            LEFT JOIN {$this->customers_table} c ON b.mob_customer_id = c.id
            WHERE {$where_sql}
            GROUP BY b.booking_id
            ORDER BY b.booking_date DESC, b.booking_time DESC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $per_page;
        $params[] = $offset;
        
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, ...$params)
        );
        
        // Cache the results
        CacheManager::set($cache_key, $results, 300); // 5 minutes
        
        QueryProfiler::end('booking_pagination', [
            'cache_hit' => false,
            'result_count' => count($results)
        ]);
        
        return $results;
    }
    
    /**
     * Get booking count with filters (optimized)
     */
    public function countByUser($user_id, $filters = []) {
        QueryProfiler::start('booking_count', ['user_id' => $user_id]);
        
        $cache_key = "user_booking_count_{$user_id}_" . md5(serialize($filters));
        $cached_count = CacheManager::get($cache_key);
        
        if ($cached_count !== null) {
            QueryProfiler::end('booking_count', ['cache_hit' => true]);
            return $cached_count;
        }
        
        $where_clauses = ['user_id = %d'];
        $params = [$user_id];
        
        // Apply same filters as pagination
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'booking_date >= %s';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'booking_date <= %s';
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $where_clauses[] = '(customer_name LIKE %s OR customer_email LIKE %s OR booking_reference LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $count = (int) $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->bookings_table} WHERE {$where_sql}",
                ...$params
            )
        );
        
        CacheManager::set($cache_key, $count, 300);
        
        QueryProfiler::end('booking_count', [
            'cache_hit' => false,
            'count' => $count
        ]);
        
        return $count;
    }
    
    /**
     * Get booking by ID with all related data (optimized)
     */
    public function findById($booking_id, $user_id = null) {
        QueryProfiler::start('booking_by_id', ['booking_id' => $booking_id]);
        
        $cache_key = "booking_{$booking_id}";
        $cached_booking = CacheManager::get($cache_key);
        
        if ($cached_booking !== null) {
            // Verify user access if user_id provided
            if ($user_id && $cached_booking->user_id != $user_id) {
                QueryProfiler::end('booking_by_id', ['access_denied' => true]);
                return null;
            }
            QueryProfiler::end('booking_by_id', ['cache_hit' => true]);
            return $cached_booking;
        }
        
        $sql = "
            SELECT 
                b.*,
                c.full_name as customer_full_name,
                c.phone_number as customer_phone_alt,
                c.address_line_1,
                c.city,
                c.state,
                c.zip_code as customer_zip,
                c.country
            FROM {$this->bookings_table} b
            LEFT JOIN {$this->customers_table} c ON b.mob_customer_id = c.id
            WHERE b.booking_id = %d
        ";
        
        $params = [$booking_id];
        
        if ($user_id) {
            $sql .= " AND b.user_id = %d";
            $params[] = $user_id;
        }
        
        $booking = $this->wpdb->get_row(
            $this->wpdb->prepare($sql, ...$params)
        );
        
        if ($booking) {
            // Get booking items
            $booking->items = $this->getBookingItems($booking_id);
            
            // Cache the complete booking
            CacheManager::set($cache_key, $booking, 600); // 10 minutes
        }
        
        QueryProfiler::end('booking_by_id', [
            'cache_hit' => false,
            'found' => $booking !== null
        ]);
        
        return $booking;
    }
    
    /**
     * Get booking items for a booking
     */
    private function getBookingItems($booking_id) {
        $cache_key = "booking_items_{$booking_id}";
        $cached_items = CacheManager::get($cache_key);
        
        if ($cached_items !== null) {
            return $cached_items;
        }
        
        $items = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->booking_items_table} WHERE booking_id = %d ORDER BY item_id",
                $booking_id
            )
        );
        
        // Parse selected_options JSON
        foreach ($items as $item) {
            if (!empty($item->selected_options)) {
                $item->selected_options_parsed = json_decode($item->selected_options, true);
            }
        }
        
        CacheManager::set($cache_key, $items, 600);
        
        return $items;
    }
    
    /**
     * Create booking with transaction support
     */
    public function create($booking_data, $booking_items = []) {
        QueryProfiler::start('booking_create');
        
        $this->wpdb->query('START TRANSACTION');
        
        try {
            // Insert main booking record
            $result = $this->wpdb->insert(
                $this->bookings_table,
                $booking_data,
                $this->getBookingDataFormats($booking_data)
            );
            
            if (false === $result) {
                throw new \Exception('Failed to insert booking: ' . $this->wpdb->last_error);
            }
            
            $booking_id = $this->wpdb->insert_id;
            
            // Insert booking items
            foreach ($booking_items as $item) {
                $item['booking_id'] = $booking_id;
                
                $item_result = $this->wpdb->insert(
                    $this->booking_items_table,
                    $item,
                    $this->getBookingItemFormats($item)
                );
                
                if (false === $item_result) {
                    throw new \Exception('Failed to insert booking item: ' . $this->wpdb->last_error);
                }
            }
            
            $this->wpdb->query('COMMIT');
            
            // Invalidate relevant caches
            CacheManager::invalidateUserCache($booking_data['user_id']);
            
            QueryProfiler::end('booking_create', [
                'success' => true,
                'booking_id' => $booking_id
            ]);
            
            return $booking_id;
            
        } catch (\Exception $e) {
            $this->wpdb->query('ROLLBACK');
            
            QueryProfiler::end('booking_create', [
                'success' => false,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Update booking status with optimistic locking
     */
    public function updateStatus($booking_id, $new_status, $user_id = null, $expected_version = null) {
        QueryProfiler::start('booking_update_status');
        
        $where_clause = ['booking_id = %d'];
        $params = [$booking_id];
        
        if ($user_id) {
            $where_clause[] = 'user_id = %d';
            $params[] = $user_id;
        }
        
        // Optimistic locking if version provided
        if ($expected_version !== null) {
            $where_clause[] = 'updated_at = %s';
            $params[] = $expected_version;
        }
        
        $update_data = [
            'status' => $new_status,
            'updated_at' => current_time('mysql')
        ];
        
        $result = $this->wpdb->update(
            $this->bookings_table,
            $update_data,
            array_combine($where_clause, $params),
            ['%s', '%s'],
            array_fill(0, count($params), '%s')
        );
        
        if ($result === false) {
            QueryProfiler::end('booking_update_status', ['success' => false]);
            return false;
        }
        
        if ($result === 0 && $expected_version !== null) {
            // Possible version conflict
            QueryProfiler::end('booking_update_status', ['version_conflict' => true]);
            return 'version_conflict';
        }
        
        // Invalidate caches
        CacheManager::delete("booking_{$booking_id}");
        if ($user_id) {
            CacheManager::invalidateUserCache($user_id);
        }
        
        QueryProfiler::end('booking_update_status', ['success' => true]);
        
        return true;
    }
    
    /**
     * Get dashboard statistics (optimized with caching)
     */
    public function getDashboardStats($user_id, $date_range = 30) {
        QueryProfiler::start('dashboard_stats');
        
        $cache_key = "dashboard_stats_{$user_id}_{$date_range}";
        $cached_stats = CacheManager::get($cache_key);
        
        if ($cached_stats !== null) {
            QueryProfiler::end('dashboard_stats', ['cache_hit' => true]);
            return $cached_stats;
        }
        
        $date_from = date('Y-m-d', strtotime("-{$date_range} days"));
        
        // Single query to get all stats
        $sql = "
            SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
                SUM(CASE WHEN status IN ('completed', 'confirmed') THEN total_price ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status IN ('completed', 'confirmed') THEN total_price ELSE NULL END) as avg_booking_value,
                COUNT(DISTINCT customer_email) as unique_customers,
                SUM(CASE WHEN booking_date >= %s THEN 1 ELSE 0 END) as recent_bookings
            FROM {$this->bookings_table}
            WHERE user_id = %d
        ";
        
        $stats = $this->wpdb->get_row(
            $this->wpdb->prepare($sql, $date_from, $user_id)
        );
        
        // Convert to array and add calculated fields
        $stats_array = (array) $stats;
        $stats_array['completion_rate'] = $stats->total_bookings > 0 
            ? round(($stats->completed_bookings / $stats->total_bookings) * 100, 1) 
            : 0;
        
        CacheManager::set($cache_key, $stats_array, 300); // 5 minutes
        
        QueryProfiler::end('dashboard_stats', [
            'cache_hit' => false,
            'total_bookings' => $stats->total_bookings
        ]);
        
        return $stats_array;
    }
    
    /**
     * Batch operations for better performance
     */
    public function batchUpdateStatus($booking_ids, $new_status, $user_id) {
        QueryProfiler::start('batch_update_status');
        
        if (empty($booking_ids)) {
            return false;
        }
        
        $placeholders = implode(',', array_fill(0, count($booking_ids), '%d'));
        $params = array_merge([$new_status, current_time('mysql')], $booking_ids, [$user_id]);
        
        $sql = "
            UPDATE {$this->bookings_table} 
            SET status = %s, updated_at = %s 
            WHERE booking_id IN ({$placeholders}) 
            AND user_id = %d
        ";
        
        $result = $this->wpdb->query(
            $this->wpdb->prepare($sql, ...$params)
        );
        
        if ($result !== false) {
            // Invalidate caches for affected bookings
            foreach ($booking_ids as $booking_id) {
                CacheManager::delete("booking_{$booking_id}");
            }
            CacheManager::invalidateUserCache($user_id);
        }
        
        QueryProfiler::end('batch_update_status', [
            'affected_rows' => $result,
            'booking_count' => count($booking_ids)
        ]);
        
        return $result;
    }
    
    /**
     * Get data format arrays for wpdb operations
     */
    private function getBookingDataFormats($data) {
        $formats = [];
        $format_map = [
            'user_id' => '%d',
            'customer_id' => '%d',
            'mob_customer_id' => '%d',
            'assigned_staff_id' => '%d',
            'total_duration' => '%d',
            'total_price' => '%f',
            'discount_amount' => '%f',
            'booking_date' => '%s',
            'booking_time' => '%s',
            'created_at' => '%s',
            'updated_at' => '%s'
        ];
        
        foreach ($data as $key => $value) {
            $formats[] = $format_map[$key] ?? '%s';
        }
        
        return $formats;
    }
    
    private function getBookingItemFormats($data) {
        $formats = [];
        $format_map = [
            'booking_id' => '%d',
            'service_id' => '%d',
            'quantity' => '%d',
            'service_price' => '%f',
            'item_total_price' => '%f'
        ];
        
        foreach ($data as $key => $value) {
            $formats[] = $format_map[$key] ?? '%s';
        }
        
        return $formats;
    }
}

/**
 * Booking Service Layer
 */
class BookingService {
    private $repository;
    private $validator;
    
    public function __construct(BookingRepository $repository = null) {
        $this->repository = $repository ?: new BookingRepository();
    }
    
    /**
     * Create booking with full validation and error handling
     */
    public function createBooking($user_id, $booking_data, $booking_items = []) {
        QueryProfiler::start('booking_service_create');
        
        try {
            // Validate booking data
            $validation_result = $this->validateBookingData($booking_data);
            if (!$validation_result['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation_result['errors']
                ];
            }
            
            // Add system fields
            $booking_data['user_id'] = $user_id;
            $booking_data['created_at'] = current_time('mysql');
            $booking_data['updated_at'] = current_time('mysql');
            
            // Generate booking reference if not provided
            if (empty($booking_data['booking_reference'])) {
                $booking_data['booking_reference'] = $this->generateBookingReference();
            }
            
            // Create booking
            $booking_id = $this->repository->create($booking_data, $booking_items);
            
            QueryProfiler::end('booking_service_create', ['success' => true]);
            
            return [
                'success' => true,
                'booking_id' => $booking_id,
                'booking_reference' => $booking_data['booking_reference']
            ];
            
        } catch (\Exception $e) {
            QueryProfiler::end('booking_service_create', ['success' => false]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function validateBookingData($data) {
        $errors = [];
        
        // Required fields
        $required = ['customer_name', 'customer_email', 'booking_date', 'booking_time'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Field {$field} is required";
            }
        }
        
        // Email validation
        if (!empty($data['customer_email']) && !is_email($data['customer_email'])) {
            $errors[] = "Invalid email format";
        }
        
        // Date validation
        if (!empty($data['booking_date'])) {
            $date = \DateTime::createFromFormat('Y-m-d', $data['booking_date']);
            if (!$date || $date < new \DateTime()) {
                $errors[] = "Booking date must be in the future";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    private function generateBookingReference() {
        return 'NB-' . date('Ymd') . '-' . strtoupper(wp_generate_password(6, false));
    }
}

// Usage example:
/*
// Initialize the optimized booking system
$booking_repository = new BookingRepository();
$booking_service = new BookingService($booking_repository);

// Get bookings with pagination
$bookings = $booking_repository->findByUserWithPagination(
    $user_id, 
    $page, 
    $per_page, 
    ['status' => 'pending', 'date_from' => '2024-01-01']
);

// Create a new booking
$result = $booking_service->createBooking($user_id, $booking_data, $booking_items);
*/