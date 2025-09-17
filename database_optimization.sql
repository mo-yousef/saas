-- NORDBOOKING Database Optimization Script
-- Run these queries to improve database performance and scalability

-- ============================================================================
-- CRITICAL INDEXES FOR PERFORMANCE
-- ============================================================================

-- Bookings table optimizations
ALTER TABLE wp_nordbooking_bookings 
ADD INDEX IF NOT EXISTS idx_user_status_date (user_id, status, booking_date);

ALTER TABLE wp_nordbooking_bookings 
ADD INDEX IF NOT EXISTS idx_customer_email_date (customer_email, booking_date);

ALTER TABLE wp_nordbooking_bookings 
ADD INDEX IF NOT EXISTS idx_status_created (status, created_at);

ALTER TABLE wp_nordbooking_bookings 
ADD INDEX IF NOT EXISTS idx_booking_date_time (booking_date, booking_time);

ALTER TABLE wp_nordbooking_bookings 
ADD INDEX IF NOT EXISTS idx_user_date_status (user_id, booking_date, status);

-- Services table optimizations
ALTER TABLE wp_nordbooking_services 
ADD INDEX IF NOT EXISTS idx_user_status_sort (user_id, status, sort_order);

ALTER TABLE wp_nordbooking_services 
ADD INDEX IF NOT EXISTS idx_status_active (status) WHERE status = 'active';

-- Service options optimizations
ALTER TABLE wp_nordbooking_service_options 
ADD INDEX IF NOT EXISTS idx_service_user (service_id, user_id);

-- Customers table optimizations
ALTER TABLE wp_nordbooking_customers 
ADD INDEX IF NOT EXISTS idx_tenant_status_activity (tenant_id, status, last_activity_at);

ALTER TABLE wp_nordbooking_customers 
ADD INDEX IF NOT EXISTS idx_tenant_email_unique (tenant_id, email);

-- Booking items optimizations
ALTER TABLE wp_nordbooking_booking_items 
ADD INDEX IF NOT EXISTS idx_booking_service (booking_id, service_id);

-- Areas table optimizations
ALTER TABLE wp_nordbooking_areas 
ADD INDEX IF NOT EXISTS idx_user_type_status (user_id, area_type, status);

-- Discounts table optimizations
ALTER TABLE wp_nordbooking_discounts 
ADD INDEX IF NOT EXISTS idx_user_code_status (user_id, code, status);

ALTER TABLE wp_nordbooking_discounts 
ADD INDEX IF NOT EXISTS idx_expiry_status (expiry_date, status);

-- Availability rules optimizations
ALTER TABLE wp_nordbooking_availability_rules 
ADD INDEX IF NOT EXISTS idx_user_day_active (user_id, day_of_week, is_active);

-- Availability exceptions optimizations
ALTER TABLE wp_nordbooking_availability_exceptions 
ADD INDEX IF NOT EXISTS idx_user_date_unavailable (user_id, exception_date, is_unavailable);

-- Settings table optimizations
ALTER TABLE wp_nordbooking_tenant_settings 
ADD INDEX IF NOT EXISTS idx_user_setting_name (user_id, setting_name(191));

-- ============================================================================
-- FOREIGN KEY CONSTRAINTS FOR DATA INTEGRITY
-- ============================================================================

-- Add foreign key constraints (if they don't exist)
-- Note: These may fail if there's existing orphaned data

-- Bookings to users
ALTER TABLE wp_nordbooking_bookings 
ADD CONSTRAINT fk_booking_user 
FOREIGN KEY (user_id) REFERENCES wp_users(ID) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Bookings to customers
ALTER TABLE wp_nordbooking_bookings 
ADD CONSTRAINT fk_booking_customer 
FOREIGN KEY (mob_customer_id) REFERENCES wp_nordbooking_customers(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Services to users
ALTER TABLE wp_nordbooking_services 
ADD CONSTRAINT fk_service_user 
FOREIGN KEY (user_id) REFERENCES wp_users(ID) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Service options to services
ALTER TABLE wp_nordbooking_service_options 
ADD CONSTRAINT fk_option_service 
FOREIGN KEY (service_id) REFERENCES wp_nordbooking_services(service_id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Booking items to bookings
ALTER TABLE wp_nordbooking_booking_items 
ADD CONSTRAINT fk_item_booking 
FOREIGN KEY (booking_id) REFERENCES wp_nordbooking_bookings(booking_id) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- Customers to tenant users
ALTER TABLE wp_nordbooking_customers 
ADD CONSTRAINT fk_customer_tenant 
FOREIGN KEY (tenant_id) REFERENCES wp_users(ID) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- ============================================================================
-- PERFORMANCE VIEWS FOR COMMON QUERIES
-- ============================================================================

-- Create view for booking summary with services
CREATE OR REPLACE VIEW v_booking_summary AS
SELECT 
    b.booking_id,
    b.user_id,
    b.booking_reference,
    b.customer_name,
    b.customer_email,
    b.booking_date,
    b.booking_time,
    b.status,
    b.total_price,
    b.created_at,
    GROUP_CONCAT(bi.service_name SEPARATOR ', ') as service_names,
    COUNT(bi.item_id) as service_count
FROM wp_nordbooking_bookings b
LEFT JOIN wp_nordbooking_booking_items bi ON b.booking_id = bi.booking_id
GROUP BY b.booking_id;

-- Create view for customer statistics
CREATE OR REPLACE VIEW v_customer_stats AS
SELECT 
    c.id as customer_id,
    c.tenant_id,
    c.full_name,
    c.email,
    c.status,
    c.created_at,
    COUNT(b.booking_id) as total_bookings,
    SUM(CASE WHEN b.status IN ('completed', 'confirmed') THEN b.total_price ELSE 0 END) as total_spent,
    MAX(b.booking_date) as last_booking_date,
    AVG(CASE WHEN b.status IN ('completed', 'confirmed') THEN b.total_price ELSE NULL END) as avg_booking_value
FROM wp_nordbooking_customers c
LEFT JOIN wp_nordbooking_bookings b ON c.email = b.customer_email AND c.tenant_id = b.user_id
GROUP BY c.id;

-- Create view for service performance
CREATE OR REPLACE VIEW v_service_performance AS
SELECT 
    s.service_id,
    s.user_id,
    s.name,
    s.price,
    s.status,
    COUNT(bi.item_id) as times_booked,
    SUM(bi.item_total_price) as total_revenue,
    AVG(bi.item_total_price) as avg_price_per_booking
FROM wp_nordbooking_services s
LEFT JOIN wp_nordbooking_booking_items bi ON s.service_id = bi.service_id
GROUP BY s.service_id;

-- ============================================================================
-- CLEANUP ORPHANED DATA
-- ============================================================================

-- Remove orphaned booking items (bookings that don't exist)
DELETE bi FROM wp_nordbooking_booking_items bi
LEFT JOIN wp_nordbooking_bookings b ON bi.booking_id = b.booking_id
WHERE b.booking_id IS NULL;

-- Remove orphaned service options (services that don't exist)
DELETE so FROM wp_nordbooking_service_options so
LEFT JOIN wp_nordbooking_services s ON so.service_id = s.service_id
WHERE s.service_id IS NULL;

-- Remove orphaned bookings (users that don't exist)
DELETE b FROM wp_nordbooking_bookings b
LEFT JOIN wp_users u ON b.user_id = u.ID
WHERE u.ID IS NULL;

-- Remove orphaned services (users that don't exist)
DELETE s FROM wp_nordbooking_services s
LEFT JOIN wp_users u ON s.user_id = u.ID
WHERE u.ID IS NULL;

-- ============================================================================
-- TABLE OPTIMIZATION
-- ============================================================================

-- Optimize tables for better performance
OPTIMIZE TABLE wp_nordbooking_bookings;
OPTIMIZE TABLE wp_nordbooking_services;
OPTIMIZE TABLE wp_nordbooking_customers;
OPTIMIZE TABLE wp_nordbooking_booking_items;
OPTIMIZE TABLE wp_nordbooking_service_options;
OPTIMIZE TABLE wp_nordbooking_discounts;
OPTIMIZE TABLE wp_nordbooking_areas;
OPTIMIZE TABLE wp_nordbooking_availability_rules;
OPTIMIZE TABLE wp_nordbooking_availability_exceptions;
OPTIMIZE TABLE wp_nordbooking_tenant_settings;

-- ============================================================================
-- PERFORMANCE MONITORING QUERIES
-- ============================================================================

-- Query to check index usage
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    CARDINALITY,
    SUB_PART,
    NULLABLE,
    INDEX_TYPE
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME LIKE 'wp_nordbooking_%'
ORDER BY TABLE_NAME, INDEX_NAME;

-- Query to check table sizes
SELECT 
    TABLE_NAME,
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) AS 'Size (MB)',
    TABLE_ROWS,
    ROUND((INDEX_LENGTH / 1024 / 1024), 2) AS 'Index Size (MB)'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME LIKE 'wp_nordbooking_%'
ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;

-- Query to identify slow queries (if query log is enabled)
-- SELECT * FROM mysql.slow_log WHERE sql_text LIKE '%nordbooking%' ORDER BY start_time DESC LIMIT 10;

-- ============================================================================
-- MAINTENANCE PROCEDURES
-- ============================================================================

-- Create procedure for regular maintenance
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS sp_nordbooking_maintenance()
BEGIN
    -- Update customer statistics
    UPDATE wp_nordbooking_customers c
    SET 
        total_bookings = (
            SELECT COUNT(*) 
            FROM wp_nordbooking_bookings b 
            WHERE b.customer_email = c.email AND b.user_id = c.tenant_id
        ),
        last_booking_date = (
            SELECT MAX(booking_date) 
            FROM wp_nordbooking_bookings b 
            WHERE b.customer_email = c.email AND b.user_id = c.tenant_id
        );
    
    -- Clean up expired transients (if using custom transient table)
    -- DELETE FROM wp_options WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP();
    -- DELETE FROM wp_options WHERE option_name LIKE '_transient_%' AND option_name NOT LIKE '_transient_timeout_%';
    
    -- Analyze tables for query optimization
    ANALYZE TABLE wp_nordbooking_bookings;
    ANALYZE TABLE wp_nordbooking_services;
    ANALYZE TABLE wp_nordbooking_customers;
END //
DELIMITER ;

-- ============================================================================
-- BACKUP RECOMMENDATIONS
-- ============================================================================

-- Before running this script, create a backup:
-- mysqldump -u username -p database_name > nordbooking_backup_$(date +%Y%m%d_%H%M%S).sql

-- Test queries on a copy first:
-- CREATE DATABASE nordbooking_test;
-- mysql -u username -p nordbooking_test < nordbooking_backup.sql

-- ============================================================================
-- MONITORING QUERIES FOR ONGOING PERFORMANCE
-- ============================================================================

-- Check for missing indexes (run periodically)
SELECT 
    CONCAT('ALTER TABLE ', TABLE_NAME, ' ADD INDEX idx_', COLUMN_NAME, ' (', COLUMN_NAME, ');') as suggested_index
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME LIKE 'wp_nordbooking_%'
AND COLUMN_NAME IN ('user_id', 'status', 'created_at', 'booking_date', 'customer_email')
AND CONCAT(TABLE_NAME, '_', COLUMN_NAME) NOT IN (
    SELECT CONCAT(TABLE_NAME, '_', COLUMN_NAME)
    FROM information_schema.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE()
);

-- Check query performance (requires performance_schema enabled)
-- SELECT 
--     DIGEST_TEXT,
--     COUNT_STAR,
--     AVG_TIMER_WAIT/1000000000 as avg_time_seconds,
--     SUM_TIMER_WAIT/1000000000 as total_time_seconds
-- FROM performance_schema.events_statements_summary_by_digest 
-- WHERE DIGEST_TEXT LIKE '%nordbooking%'
-- ORDER BY AVG_TIMER_WAIT DESC
-- LIMIT 10;