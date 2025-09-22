# NORDBOOKING Discount System

## Overview

The NORDBOOKING Discount System provides comprehensive discount code functionality with real-time validation, service-level control, usage tracking, and seamless integration with the booking system.

## Features

### 🎫 Discount Code Types
- **Percentage Discounts**: Apply percentage-based discounts (e.g., 10% off)
- **Fixed Amount Discounts**: Apply fixed dollar amount discounts (e.g., $5 off)
- **Service-Level Control**: Enable/disable discounts per individual service
- **Usage Limits**: Set maximum usage counts per discount code
- **Expiration Dates**: Set expiration dates for time-limited offers

### 🔄 Real-time Validation
- **AJAX Validation**: Immediate feedback on discount code validity
- **Live Price Updates**: Prices update instantly when discount is applied
- **Error Handling**: Clear error messages for invalid or expired codes
- **Service Compatibility**: Checks if discount is allowed for selected services

### 📊 Usage Tracking
- **Usage Count**: Track how many times each discount has been used
- **Usage Limits**: Enforce maximum usage limits
- **Dashboard Display**: View usage statistics in admin dashboard
- **Automatic Increment**: Usage count increases automatically when discount is applied

## System Architecture

### Database Schema

#### Discounts Table
```sql
CREATE TABLE wp_nordbooking_discounts (
    discount_id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    code varchar(50) NOT NULL,
    type enum('percentage','fixed_amount') NOT NULL,
    value decimal(10,2) NOT NULL,
    usage_limit int(11) DEFAULT NULL,
    usage_count int(11) DEFAULT 0,
    expiry_date date DEFAULT NULL,
    status enum('active','inactive') DEFAULT 'active',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (discount_id),
    KEY idx_user_code (user_id, code),
    KEY idx_status_expiry (status, expiry_date)
);
```

#### Services Table Enhancement
```sql
ALTER TABLE wp_nordbooking_services 
ADD COLUMN disable_discount_code tinyint(1) DEFAULT 0;
```

#### Bookings Table Enhancement
```sql
ALTER TABLE wp_nordbooking_bookings 
ADD COLUMN discount_id int(11) DEFAULT NULL,
ADD COLUMN discount_amount decimal(10,2) DEFAULT 0.00;
```

### Core Classes

#### Discounts Class (`classes/Discounts.php`)
Main discount management class providing:
- Discount code creation and management
- Validation and application logic
- Usage tracking and limits
- Database operations

**Key Methods:**
```php
// Validate discount code
public function validate_discount($code, $user_id);

// Apply discount to booking
public function apply_discount($discount_id, $total_amount);

// Increment usage count
public function increment_discount_usage($discount_id);

// Check service compatibility
public function is_discount_allowed_for_service($service_id);
```

## Frontend Integration

### Booking Form Integration

#### Discount Code Input
- **Conditional Display**: Only shows if at least one selected service allows discounts
- **Real-time Validation**: AJAX validation as user types
- **Visual Feedback**: Success/error states with appropriate styling
- **Price Updates**: Automatic recalculation when discount is applied/removed

#### JavaScript Implementation
```javascript
// Discount validation
function validateDiscountCode(code) {
    if (!code.trim()) {
        clearDiscount();
        return;
    }

    // Check if any selected service allows discounts
    if (!hasDiscountEligibleServices()) {
        showDiscountError('Discount codes are not available for selected services');
        return;
    }

    // AJAX validation
    jQuery.post(nordbooking_ajax.ajaxurl, {
        action: 'nordbooking_validate_discount',
        discount_code: code,
        tenant_id: getCurrentTenantId(),
        nonce: nordbooking_ajax.nonce
    }, function(response) {
        if (response.success) {
            applyDiscount(response.data);
        } else {
            showDiscountError(response.data.message);
        }
    });
}

// Apply discount to pricing
function applyDiscount(discountData) {
    window.currentDiscount = discountData;
    updatePricingWithDiscount();
    showDiscountSuccess(discountData);
}
```

### Service-Level Control

#### Service Edit Page
- **Discount Toggle**: Checkbox to enable/disable discounts for each service
- **Visual Indicator**: Clear indication of discount availability
- **Bulk Operations**: Enable/disable discounts for multiple services

#### Frontend Checking
```javascript
// Check if service allows discounts
function serviceAllowsDiscounts(serviceId) {
    const service = getServiceById(serviceId);
    return service && !service.disable_discount_code;
}

// Check if any selected service allows discounts
function hasDiscountEligibleServices() {
    return selectedServices.some(service => 
        serviceAllowsDiscounts(service.service_id)
    );
}
```

## Backend Processing

### Booking Creation Integration

#### Enhanced Booking Handler
All booking handlers include discount processing:

```php
// Process discount code
$discount_amount = 0;
$discount_id = null;
$discount_code = sanitize_text_field($_POST['discount_code'] ?? '');

if (!empty($discount_code)) {
    $discounts_manager = $GLOBALS['nordbooking_discounts_manager'] ?? null;
    if ($discounts_manager) {
        $discount_validation = $discounts_manager->validate_discount($discount_code, $tenant_id);
        if (!is_wp_error($discount_validation)) {
            $discount_id = $discount_validation['discount_id'];
            
            // Calculate discount amount
            if ($discount_validation['type'] === 'percentage') {
                $discount_amount = ($total_amount * floatval($discount_validation['value'])) / 100;
            } elseif ($discount_validation['type'] === 'fixed_amount') {
                $discount_amount = min(floatval($discount_validation['value']), $total_amount);
            }
            
            $total_amount = max(0, $total_amount - $discount_amount);
            
            // Increment usage count
            $discounts_manager->increment_discount_usage($discount_id);
        }
    }
}

// Include discount in booking data
$booking_data = [
    // ... other booking fields
    'discount_id' => $discount_id,
    'discount_amount' => $discount_amount,
    'total_price' => $total_amount,
    // ... rest of booking data
];
```

### AJAX Handlers

#### Discount Validation Handler
```php
add_action('wp_ajax_nordbooking_validate_discount', 'nordbooking_validate_discount_handler');
add_action('wp_ajax_nopriv_nordbooking_validate_discount', 'nordbooking_validate_discount_handler');

function nordbooking_validate_discount_handler() {
    // Security checks
    if (!wp_verify_nonce($_POST['nonce'], 'nordbooking_booking_form_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }

    $discount_code = sanitize_text_field($_POST['discount_code']);
    $tenant_id = intval($_POST['tenant_id']);

    // Validate discount
    $discounts_manager = $GLOBALS['nordbooking_discounts_manager'];
    $result = $discounts_manager->validate_discount($discount_code, $tenant_id);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    } else {
        wp_send_json_success($result);
    }
}
```

## Admin Dashboard Integration

### Discount Management

#### Create/Edit Discounts
- **Code Generation**: Automatic or manual discount code creation
- **Type Selection**: Choose between percentage or fixed amount
- **Value Setting**: Set discount value with validation
- **Usage Limits**: Set maximum usage counts
- **Expiration Dates**: Set time-limited offers
- **Status Control**: Activate/deactivate discounts

#### Discount List View
- **Comprehensive Table**: All discounts with key information
- **Usage Statistics**: Current usage vs. limits
- **Status Indicators**: Visual status badges
- **Quick Actions**: Edit, delete, activate/deactivate
- **Search and Filter**: Find specific discounts

#### Analytics
- **Usage Reports**: Track discount usage over time
- **Revenue Impact**: Calculate discount impact on revenue
- **Popular Codes**: Identify most-used discount codes
- **Conversion Tracking**: Track discount-driven conversions

### Service Management Integration

#### Service Edit Page
- **Discount Control**: Toggle discount availability per service
- **Bulk Operations**: Enable/disable for multiple services
- **Visual Indicators**: Clear indication of discount status

## Testing & Debugging

### Test Files

#### `test-discount-system.php`
Comprehensive system testing:
- Database schema validation
- Discount creation and validation
- Service-level control testing
- Frontend integration testing
- Booking flow testing

#### `debug-discount-system.php`
Step-by-step debugging:
- Configuration validation
- Database connectivity
- AJAX endpoint testing
- Frontend JavaScript testing
- Backend processing validation

#### `test-discount-flow.php`
End-to-end flow testing:
- Complete user journey testing
- Integration point validation
- Error handling verification
- Performance testing

### Debug Tools

#### System Validation
```php
// Check discount system health
function check_discount_system_health() {
    $health = [
        'database' => check_discount_tables(),
        'classes' => check_discount_classes(),
        'ajax' => check_discount_ajax_handlers(),
        'frontend' => check_discount_frontend_integration()
    ];
    
    return $health;
}
```

## Migration & Setup

### Database Migration

#### Migration Script (`migrate-discount-columns-complete.php`)
Comprehensive migration that:
- Creates discount tables if missing
- Adds discount columns to existing tables
- Creates necessary indexes
- Validates data integrity
- Provides rollback capability

#### Manual Migration
```sql
-- Add discount columns to services table
ALTER TABLE wp_nordbooking_services 
ADD COLUMN IF NOT EXISTS disable_discount_code tinyint(1) DEFAULT 0;

-- Add discount columns to bookings table
ALTER TABLE wp_nordbooking_bookings 
ADD COLUMN IF NOT EXISTS discount_id int(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS discount_amount decimal(10,2) DEFAULT 0.00;

-- Add indexes for performance
ALTER TABLE wp_nordbooking_discounts
ADD INDEX IF NOT EXISTS idx_user_code (user_id, code),
ADD INDEX IF NOT EXISTS idx_status_expiry (status, expiry_date);
```

### Configuration

#### Required Settings
- Discount system must be enabled in theme settings
- Proper database permissions for table creation
- AJAX endpoints must be accessible
- JavaScript must be enabled for frontend functionality

## Usage Guide

### For Business Owners

#### Creating Discount Codes
1. Go to Dashboard → Discounts
2. Click "Create New Discount"
3. Enter discount code (or generate automatically)
4. Select type (percentage or fixed amount)
5. Set value and usage limits
6. Set expiration date if needed
7. Save discount

#### Managing Service Discounts
1. Go to Dashboard → Services
2. Edit the desired service
3. Toggle "Allow Discount Codes" option
4. Save service settings

#### Monitoring Usage
1. Go to Dashboard → Discounts
2. View usage statistics for each code
3. Monitor conversion rates
4. Analyze revenue impact

### For Customers

#### Using Discount Codes
1. Select desired services in booking form
2. Enter discount code in "Discount Code" field
3. Code is validated automatically
4. Price updates to reflect discount
5. Complete booking with discounted price

## Troubleshooting

### Common Issues

#### Discount Field Not Showing
**Symptoms**: Discount code input not visible in booking form
**Solutions**:
1. Check if selected services allow discounts
2. Verify JavaScript is loading correctly
3. Check browser console for errors
4. Ensure at least one service has discounts enabled

#### Discount Not Applied to Price
**Symptoms**: Valid discount code doesn't reduce price
**Solutions**:
1. Check JavaScript console for calculation errors
2. Verify discount validation is successful
3. Ensure `updatePricingWithDiscount()` is being called
4. Check discount value and type settings

#### Usage Count Not Updating
**Symptoms**: Discount usage count doesn't increase
**Solutions**:
1. Verify discount_id is being passed to backend
2. Check that `increment_discount_usage()` is being called
3. Refresh dashboard page to see updated count
4. Check database for proper discount_id storage

#### Booking Saves Wrong Price
**Symptoms**: Booking saved with incorrect total
**Solutions**:
1. Check that discount fields are in booking data array
2. Verify database columns exist (run migration)
3. Check database insert format strings include discount fields
4. Validate discount calculation logic

### Debug Steps

1. **Run Migration**: Execute `migrate-discount-columns-complete.php`
2. **Test System**: Run all test files to verify functionality
3. **Check Database**: Verify all tables and columns exist
4. **Test Frontend**: Create test discount and booking
5. **Monitor Usage**: Check dashboard for usage tracking
6. **Validate Integration**: Test complete booking flow

## Security Considerations

### Input Validation
- All discount codes sanitized and validated
- SQL injection prevention through prepared statements
- XSS prevention through proper output escaping
- Rate limiting on discount validation requests

### Access Control
- Discount management restricted to business owners
- Proper capability checks for admin functions
- Nonce verification for all AJAX requests
- User authentication required for all operations

### Data Integrity
- Transaction support for booking creation
- Proper error handling and rollback
- Usage count validation and limits
- Expiration date enforcement

## Performance Optimization

### Database Optimization
- Proper indexing for discount queries
- Efficient query patterns
- Connection pooling support
- Query result caching

### Frontend Optimization
- Debounced AJAX requests
- Efficient DOM manipulation
- Minimal JavaScript footprint
- Optimized CSS delivery

### Caching Strategy
- Discount validation caching
- Service configuration caching
- Usage statistics caching
- Cache invalidation on updates

## Future Enhancements

### Planned Features
- Advanced discount rules (minimum order, specific services)
- Bulk discount operations
- Discount analytics dashboard
- Integration with email marketing
- Automated discount campaigns

### API Extensions
- REST API endpoints for discount management
- Webhook notifications for discount usage
- Third-party integration capabilities
- Mobile app support

This discount system provides comprehensive functionality for managing promotional codes with real-time validation, detailed tracking, and seamless integration with the booking system.